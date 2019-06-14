<?php declare(strict_types=1);

namespace StaticServer;

use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
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
     * Object to sent default headers.
     *
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
     * @var string
     */
    private $stage;

    /**
     * @var string
     */
    private $sha1;

    /**
     * HttpApplication constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @param string $stage
     * @param string $sha1
     */
    public function __construct(ConfigurationInterface $conf, string $stage = '', string $sha1 = '')
    {
        $this->server = $this->createServer($conf);
        $this->conf   = $conf;
        $this->stage  = $stage;
        $this->sha1   = $sha1;

        $this->header = new Header($conf);

        // disable logs and modifiers by default
        $this->setLogger(new NullLogger());
        $this->setModifier(new NullModify());
        $this->setProcessor(new SpaProcessor($conf));
        $this->setIterator(new RecursiveIterator($conf, $this->logger));
    }

    /**
     * Processor to process incoming request with defined logic.
     *
     * @param \StaticServer\Processor\ProcessorInterface $processor
     *
     * @return void
     */
    public function setProcessor(ProcessorInterface $processor): void
    {
        $this->processor = $processor;
    }

    /**
     * Iterator to iterate files in server.root.
     *
     * @param \StaticServer\Iterator\IteratorInterface $iterator
     *
     * @return void
     */
    public function setIterator(IteratorInterface $iterator): void
    {
        $this->iterator = $iterator;
    }

    /**
     * Modifier for modify incoming files or add new one.
     *
     * @param \StaticServer\Modifier\GenericModifyInterface $modify
     *
     * @return void
     */
    public function setModifier(GenericModifyInterface $modify): void
    {
        $this->modify = $modify;
    }

    /**
     * Dry run without start of server.
     *
     * @return void
     */
    public function dryRun(): void
    {
        $this->makeReady();
        $this->registerOnStartListener();
        $this->registerOnRequestListener();
    }

    /**
     * Run server.
     *
     * @return void
     */
    public function run(): void
    {
        $this->makeReady();
        $this->registerOnStartListener();
        $this->registerOnRequestListener();

        $this->server->start();
    }

    /**
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     *
     * @return \Swoole\Http\Server
     */
    private function createServer(ConfigurationInterface $conf): Server
    {
        $server = new Server(
            $conf->get('server.host'),
            $conf->get('server.port'),
            SWOOLE_PROCESS
        );

        // swoole compression disabled and it not possible to override.
        $compress = [
            # Latest version of swoole (2019-06-04) can't compress response output if present Accept-Encoding header.
            # Doesn't work any method: gzip, br.
            'http_compression' => false,
            'http_compression_level' => 0,
        ];

        $server->set(array_merge(
            ['worker_num' => 4], // should be possible to override worker_num parameter from server config.
            array_merge($conf->get('server.swoole'), $compress)
        ));

        return $server;
    }

    /**
     * @return void
     */
    private function registerOnStartListener(): void
    {
        $this->server->on('start', function ($server) {
            $this->logger->info(sprintf('Application state is: STAGE=%s SHA1=%s', $this->stage, $this->sha1));
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
