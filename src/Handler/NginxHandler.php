<?php declare(strict_types=1);

namespace StaticServer\Handler;

use League\Plates\Engine;
use StaticServer\Header\HeaderInterface;

class NginxHandler extends AbstractHandler
{
    /**
     * @var \League\Plates\Engine
     */
    private Engine $templates;

    /**
     * @var string
     */
    private string $generatedConfig;

    /**
     * NginxHandler constructor.
     */
    public function __construct()
    {
        $this->templates       = new Engine(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
        $this->generatedConfig = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'generated_nginx.conf';
    }

    public function checkDependenciesBeforeStart(): void
    {
        // TODO: Implement checkDependenciesBeforeStart() method.
    }

    /**
     * @param \StaticServer\Header\HeaderInterface $header
     */
    public function generateConfig(HeaderInterface $header): void
    {
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
            'connProcMethod' => $this->getConnectionProcessingMethod()
        ]);

        $this->logger->info("Generated config stored here: {$this->generatedConfig}");

        file_put_contents($this->generatedConfig, $data);
    }

    public function checkConfig(): void
    {
        $this->runProcess(['nginx', '-c', $this->generatedConfig, '-t']);
    }

    /**
     * Start the web-server.
     */
    public function start(): void
    {
        $this->runProcess(['nginx', '-c', $this->generatedConfig], function () {
            $this->logger->info(sprintf(
                'Server started at: %s:%d',
                $this->configuration->get('server.host'),
                $this->configuration->get('server.port')
            ));
        });
    }

    public function reload(): void
    {
        $this->runProcess(['nginx', '-c', $this->generatedConfig, '-s', 'reload']);
    }

    public function stop(): void
    {
        $this->runProcess(['nginx', '-c', $this->generatedConfig, '-s', 'stop']);
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
