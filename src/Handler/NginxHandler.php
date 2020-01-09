<?php declare(strict_types=1);

namespace StaticServer\Handler;

use InvalidArgumentException;
use JJG\Ping;
use League\Plates\Engine;
use LogicException;
use RuntimeException;
use StaticServer\Header\HeaderInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class NginxHandler extends AbstractHandler
{
    /**
     * @var \League\Plates\Engine
     */
    private Engine $templates;

    /**
     * @var string
     */
    private string $pidFile;

    /**
     * @var string
     */
    private string $configFile;

    /**
     * @var bool
     */
    private bool $moduleBrotliInstalled = false;

    /**
     * @var bool
     */
    private bool $platformSupportsAsyncIo = false;

    /**
     * NginxHandler constructor.
     *
     * @param array<string, string> $options
     */
    public function __construct(array $options = [])
    {
        $this->templates  = new Engine(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
        $this->pidFile    = $this->expandPathLikeShell($options['pid']);
        $this->configFile = $this->expandPathLikeShell($options['config']);

        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    public function checkDependenciesBeforeStart(): void
    {
        $this->checkIfNginxInstalled();
        $this->checkIfBrotliModuleInstalled();
        $this->checkIfPlatformSupportsAsyncIo();
        $this->checkIfPrerenderUrlIsAvailable();
    }

    /**
     * @param \StaticServer\Header\HeaderInterface $header
     */
    public function generateConfig(HeaderInterface $header): void
    {
        $this->logger->info("Nginx PID location: {$this->pidFile}");
        $this->makePathForFiles([$this->pidFile, $this->configFile]);
        $this->touchFile($this->pidFile);

        $data = $this->templates->render('nginx_default.conf', [
            'serverRoot' => realpath($this->getServerRoot()),
            'serverIndex' => $this->configuration->get('server.index'),
            'serverPort' => $this->configuration->get('server.port'),
            'serverHost' => $this->configuration->get('server.host'),
            'prerenderEnabled' => $this->configuration->get('server.prerender.enabled'),
            'prerenderUrl' => $this->getHostWithoutTrailingSlash('server.prerender.url'),
            'prerenderToken' => $this->configuration->get('server.prerender.token'),
            'prerenderHost' => $this->getHostWithoutTrailingSlash('server.prerender.host'),
            'headers' => $header->convert($this->configuration),
            'connProcMethod' => $this->getConnectionProcessingMethod(),
            'pidLocation' => $this->pidFile,
            'moduleBrotliInstalled' => $this->moduleBrotliInstalled,
            'platformSupportsAsyncIo' => $this->platformSupportsAsyncIo,
        ]);

        file_put_contents($this->configFile, $data);
    }

    public function checkConfig(): void
    {
        $this->runProcess(['nginx', '-c', $this->configFile, '-t']);
    }

    /**
     * Start the web-server.
     */
    public function start(): void
    {
        $this->runProcess(['nginx', '-c', $this->configFile], function () {
            $this->logger->info(sprintf(
                'Server started at: %s:%d',
                $this->configuration->get('server.host'),
                $this->configuration->get('server.port')
            ));
        });
    }

    public function reload(): void
    {
        if (!file_exists($this->pidFile)) {
            throw new LogicException('Can\'t reload server. Pid file not found.');
        }

        if (empty(file_get_contents($this->pidFile))) {
            throw new LogicException('Can\'t reload server. Pid file is empty.');
        }

        $this->runProcess(['nginx', '-c', $this->configFile, '-s', 'reload']);
    }

    public function stop(): void
    {
        $this->runProcess(['nginx', '-c', $this->configFile, '-s', 'stop']);

        if ($this->filesystem->exists($this->configFile)) {
            $this->filesystem->remove($this->configFile);
        }
    }

    /**
     * @param string $key
     * @return bool|string
     */
    private function getHostWithoutTrailingSlash(string $key)
    {
        if (empty($this->configuration->get($key))) {
            return false;
        }

        return rtrim($this->configuration->get($key), '/');
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    private function getConnectionProcessingMethod(): string
    {
        switch (PHP_OS_FAMILY) {
            case 'Linux':
                return  'epoll';
            case 'Darwin':
            case 'BSD':
                return  'kqueue';
            default:
                return 'poll';
        }
    }

    /**
     * @throws \Exception
     */
    private function checkIfPrerenderUrlIsAvailable(): void
    {
        if (!$this->configuration->get('server.prerender.enabled', false)) {
            $this->logger->info('Prerender is not enabled, skip check.');
            return;
        }

        $scheme = parse_url($this->configuration->get('server.prerender.url', ''), PHP_URL_SCHEME);

        if ($scheme) {
            throw new InvalidArgumentException("Prerender url has a scheme: [$scheme], please remove it.");
        }

        $url = $this->configuration->get('server.prerender.url', false);

        if (!$url) {
            throw new InvalidArgumentException('Prerender URL not set. Check server.prerender.url config key.');
        }

        $this->logger->info('Ping prerender url...');

        $ping = new Ping($url);
        $latency = $ping->ping('fsockopen');

        if ($latency !== false) {
            $this->logger->info('Prerender url is available.');
            return;
        }

        $this->logger->warning('Prerender url could not be reached: ' . $url);
    }

    /**
     * @return void
     */
    private function checkIfPlatformSupportsAsyncIo(): void
    {
        $this->logger->info('Check if platform supports async io...');

        switch (PHP_OS_FAMILY) {
            case 'Linux':
            case 'BSD':
                $this->logger->info('Platform supports async io, turning it on.');
                $this->platformSupportsAsyncIo = true;
                break;
            default:
                $this->logger->info('Platform does not supports async io, turning it off.');
                $this->platformSupportsAsyncIo = false;
        }
    }

    /**
     * @return void
     */
    private function checkIfNginxInstalled(): void
    {
        $proc = new Process(['which', 'nginx']);

        try {
            $proc->mustRun();
        } catch (ProcessFailedException $e) {
            $this->logger->critical('Command [which nginx] failed: ' . $e->getMessage());
            throw $e;
        }

        if (strpos($proc->getOutput(), 'nginx') === false) {
            throw new RuntimeException('Unexpected error.');
        }
    }

    private function checkIfBrotliModuleInstalled(): void
    {
        $proc = new Process(['nginx', '-V']);

        try {
            $proc->mustRun();
        } catch (ProcessFailedException $e) {
            $this->logger->critical('Command [nginx -V] failed: ' . $e->getMessage());
            throw $e;
        }

        # nginx -V send outputs to STDERR. https://trac.nginx.org/nginx/ticket/592
        if (strpos($proc->getErrorOutput(), 'brotli') === false) {
            $this->logger->info('Nginx Brotli module not installed. Turning off this compression method.');
            $this->moduleBrotliInstalled = false;
        } else {
            $this->logger->info('Nginx Brotli module installed. Turning it on.');
            $this->moduleBrotliInstalled = true;
        }
    }
}
