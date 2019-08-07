<?php declare(strict_types=1);

namespace StaticServer;

use Microparts\Configuration\Configuration;
use Microparts\Configuration\ConfigurationAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StaticServer\Generic\ClearCacheInterface;
use StaticServer\Generic\PrepareInterface;
use StaticServer\Iterator\IteratorInterface;
use StaticServer\Iterator\RecursiveIterator;
use StaticServer\Modifier\GenericModifyInterface;
use StaticServer\Modifier\NullGenericModify;
use StaticServer\Processor\ProcessorInterface;
use StaticServer\Processor\SpaProcessor;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

final class Application
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
     * @var \StaticServer\Processor\ProcessorInterface|\StaticServer\Generic\ClearCacheInterface
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
     * @var float|int
     */
    private $cpuNum;

    /**
     * Application constructor.
     *
     * @param string $stage
     * @param string $sha1
     */
    public function __construct(string $stage = '', string $sha1 = '')
    {
        $this->stage  = $stage;
        $this->sha1   = $sha1;
        $this->cpuNum = swoole_cpu_num() * 2;
        $this->conf   = $this->loadConfiguration();

        $this->header = new Header($this->conf);

        // disable logs and modifiers by default
        $this->setLogger(new NullLogger());
        $this->setModifier(new NullGenericModify());
        $this->setProcessor(new SpaProcessor());
        $this->setIterator(new RecursiveIterator($this->logger));
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
        if ($processor instanceof ConfigurationAwareInterface) {
            $processor->setConfiguration($this->conf);
        }

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
        if ($iterator instanceof ConfigurationAwareInterface) {
            $iterator->setConfiguration($this->conf);
        }

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
        if ($modify instanceof ConfigurationAwareInterface) {
            $modify->setConfiguration($this->conf);
        }

        $this->modify = $modify;
    }

    /**
     * Get the server configuration.
     *
     * @return \Microparts\Configuration\Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->conf;
    }

    /**
     * Dry run without start of server.
     *
     * @return void
     */
    public function dryRun(): void
    {
        $this->start();
        $this->ready();
    }

    /**
     * Run server.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function run(): void
    {
        $server = $this->createServer();

        $this->registerOnStartListener($server);
        $this->registerOnShutdownListener($server);
        $this->registerOnWorkerStartListener($server);
        $this->registerOnRequestListener($server);

        $server->start();
    }

    /**
     * Prepare server to run.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function ready(): void
    {
        $this->logger->debug('Reload configuration');
        $this->conf = $this->loadConfiguration();

        $this->logger->debug('Clears headers cache and prepare theirs');
        $this->header->clearCache();
        $this->header->prepare();

        if ($this->processor instanceof ClearCacheInterface) {
            $this->logger->debug(sprintf('Clears cache for handled files. Used: [%s]', get_class($this->processor)));
            $this->processor->clearCache();
        }

        if ($this->processor instanceof PrepareInterface) {
            $this->processor->prepare();
        }

        $this->logger->debug('Iterates files from server root dir');
        $files = $this->iterator->iterate();

        $this->logger->debug(sprintf('Modifying files and add templates. Used: [%s]', get_class($this->modify)));
        $modified = $this->modify->modify($files);

        $this->logger->debug(sprintf('Loads files to memory. Used: [%s]', get_class($this->processor)));
        $this->processor->load($modified);
    }

    /**
     * @return \Swoole\Http\Server
     */
    private function createServer(): Server
    {
        $server = new Server(
            $this->conf->get('server.host'),
            $this->conf->get('server.port'),
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
            ['worker_num' => $this->cpuNum], // should be possible to override worker_num parameter from server config.
            array_merge($this->conf->get('server.swoole'), $compress)
        ));

        return $server;
    }

    /**
     * @param int|null $pid
     * @param string|null $host
     * @param int|null $port
     *
     * @return void
     */
    private function start(?int $pid = null, ?string $host = null, ?int $port = null): void
    {
        $host = $host ?: 'dryRun';

        $format = 'State: STAGE=%s SHA1=%s WORKERS=%s PID=%d CONFIG_PATH=%s';
        $message = sprintf($format, $this->stage, $this->sha1, $this->cpuNum, $pid, $this->conf->getPath());
        $this->logger->info($message);
        $this->logger->info(sprintf('Server started at %s:%d', $host, $port));
    }

    /**
     * @codeCoverageIgnore
     * @param \Swoole\Http\Server $server
     *
     * @return void
     */
    private function registerOnStartListener(Server $server): void
    {
        $server->on('start', function (Server $process) {
            $this->start($process->master_pid, $process->host, $process->port);
            $this->saveMasterProcessIdentifier($process);
        });
    }

    /**
     * @codeCoverageIgnore
     * @param \Swoole\Http\Server $server
     *
     * @return void
     */
    private function registerOnShutdownListener(Server $server): void
    {
        $server->on('shutdown', function () {
            $this->removeMasterProcessIdentifier();
        });
    }

    /**
     * @codeCoverageIgnore
     * @param \Swoole\Http\Server $server
     *
     * @return void
     */
    private function registerOnWorkerStartListener(Server $server): void
    {
        $server->on('workerStart', function () {
            $this->ready();
        });
    }

    /**
     * Registers handler to process client requests.
     *
     * @codeCoverageIgnore
     * @param \Swoole\Http\Server $server
     *
     * @return void
     */
    private function registerOnRequestListener(Server $server): void
    {
        $server->on('request', function (Request $request, Response $response) {
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
     * @return Configuration
     */
    private function loadConfiguration(): Configuration
    {
        $conf = Configuration::auto($this->stage);
        $conf->load();

        return $conf;
    }

    /**
     * Save master pid to disk if enabled.
     *
     * @param \Swoole\Http\Server $server
     *
     * @return void
     */
    private function saveMasterProcessIdentifier(Server $server): void
    {
        $save = $this->conf->get('server.pid.save');
        $location = $this->conf->get('server.pid.location');

        if ($save) {
            file_put_contents($location, $server->master_pid, LOCK_EX);
        }
    }

    /**
     * Remove master pid from disk if it exists.
     *
     * @return void
     */
    private function removeMasterProcessIdentifier(): void
    {
        $location = $this->conf->get('server.pid.location');

        if (file_exists($location)) {
            unlink($location);
        }
    }
}
