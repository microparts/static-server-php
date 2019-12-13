<?php declare(strict_types=1);

namespace StaticServer\Handler;

use League\Plates\Engine;
use LogicException;
use StaticServer\Header\HeaderInterface;

class NginxHandler extends AbstractHandler
{
    /**
     * @var \League\Plates\Engine
     */
    private Engine $templates;

    /**
     * @var array
     */
    private array $options;

    /**
     * NginxHandler constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->templates = new Engine(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
        $this->options   = $options;
    }

    public function checkDependenciesBeforeStart(): void
    {
        // TODO: Implement checkDependenciesBeforeStart() method.
        // check if nginx is installed
        // check brotli is installed
        // ping prerender url
    }

    /**
     * @param \StaticServer\Header\HeaderInterface $header
     */
    public function generateConfig(HeaderInterface $header): void
    {
        $this->logger->info("Nginx PID location: {$this->options['pid']}");

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
            'pidLocation' => $this->options['pid'],
        ]);

        file_put_contents($this->options['config'], $data);
    }

    public function checkConfig(): void
    {
        $this->runProcess(['nginx', '-c', $this->options['config'], '-t']);
    }

    /**
     * Start the web-server.
     */
    public function start(): void
    {
        $this->runProcess(['nginx', '-c', $this->options['config']], function () {
            $this->logger->info(sprintf(
                'Server started at: %s:%d',
                $this->configuration->get('server.host'),
                $this->configuration->get('server.port')
            ));
        });
    }

    public function reload(): void
    {
        if (file_exists($this->options['config'])) {
            throw new LogicException('Can\'t reload server. Pid file not found.');
        }

        if (empty(file_get_contents($this->options['config']))) {
            throw new LogicException('Can\'t reload server. Pid file is empty.');
        }

        $this->runProcess(['nginx', '-c', $this->options['config'], '-s', 'reload']);
    }

    public function stop(): void
    {
        $this->runProcess(['nginx', '-c', $this->options['config'], '-s', 'stop']);
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
}
