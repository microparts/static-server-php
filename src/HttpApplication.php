<?php declare(strict_types=1);

namespace StaticServer;

use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StaticServer\Compression\Compress;
use StaticServer\Iterator\IteratorInterface;
use StaticServer\Iterator\RecursiveIterator;
use StaticServer\Modifier\GenericModifyInterface;
use StaticServer\Modifier\NullModify;
use StaticServer\Processor\ProcessorInterface;
use StaticServer\Processor\SpaProcessor;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

final class HttpApplication
{
    use LoggerAwareTrait;

    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var \StaticServer\Compression\Compress
     */
    private $compress;

    /**
     * @var \StaticServer\Header
     */
    private $header;

    /**
     * @var \StaticServer\Processor\ProcessorInterface
     */
    private $processor;

    /**
     * @var \StaticServer\Iterator\IteratorInterface
     */
    private $iterator;

    /**
     * @var \StaticServer\Modifier\GenericModifyInterface
     */
    private $modify;

    /**
     * HttpApplication constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->server = $this->createServer($conf);
        $this->conf   = $conf;

        $this->compress = new Compress($conf);
        $this->header   = new Header($conf);

        $this->setLogger(new NullLogger());
        $this->setModifier(new NullModify());
        $this->setProcessor(new SpaProcessor($conf));
        $this->setIterator(new RecursiveIterator($conf, $this->logger));
    }

    /**
     * @param \StaticServer\Processor\ProcessorInterface $processor
     */
    public function setProcessor(ProcessorInterface $processor): void
    {
        $this->processor = $processor;
    }

    /**
     * @param \StaticServer\Iterator\IteratorInterface $iterator
     */
    public function setIterator(IteratorInterface $iterator): void
    {
        $this->iterator = $iterator;
    }

    /**
     * @param \StaticServer\Modifier\GenericModifyInterface $modify
     */
    public function setModifier(GenericModifyInterface $modify): void
    {
        $this->modify = $modify;
    }

    /**
     * Dry run without start of server.
     */
    public function dryRun()
    {
        $this->makeReady();
        $this->registerOnStartListener();
        $this->registerOnRequestListener();
    }

    /**
     * Run server.
     */
    public function run()
    {
        $this->makeReady();
        $this->registerOnStartListener();
        $this->registerOnRequestListener();

        $this->server->start();
    }

    /**
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @return \Swoole\Http\Server
     */
    private function createServer(ConfigurationInterface $conf): Server
    {
        $server = new Server(
            $conf->get('server.host'),
            $conf->get('server.port'),
            SWOOLE_PROCESS
        );

        $server->set(array_merge($conf->get('server.swoole'), [
            # Latest version of swoole (2019-06-04) can't compress response output if present Accept-Encoding header.
            # Doesn't work any method: gzip, br.
            'http_compression' => false,
            'http_compression_level' => 0,
            'worker_num' => 4,
        ]));

        return $server;
    }

    /**
     * @return void
     */
    private function registerOnStartListener(): void
    {
        $this->server->on('start', function ($server) {
            $this->logger->info(sprintf('HTTP static server started at %s:%s', $server->host, $server->port));
        });
    }

    /**
     * Registers handler to process client requests.
     *
     * @return void
     */
    private function registerOnRequestListener(): void
    {
        $this->server->on('request', function (Request $request, Response $response) {
            // Send secure headers to client
            $this->header->sent($response);

            // It will be processed the requests of clients (SPA-preferred)
            // from memory.
            $body = '';
            $this->processor->process($body, $request, $response);

            // It will be compressed output response if accept-encoding header
            // are present.
            $this->compress->handle($body, $request, $response);
            $response->end($body);
        });
    }

    /**
     * Prepare server to run.
     *
     * @return void
     */
    private function makeReady(): void
    {
        $files = $this->iterator->iterate();

        $this->processor->load(
            $this->modify->modify($files)
        );
    }
}
