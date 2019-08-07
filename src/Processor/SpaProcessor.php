<?php declare(strict_types=1);

namespace StaticServer\Processor;

use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use StaticServer\Generic\ClearCacheInterface;
use StaticServer\Compression\Compress;
use StaticServer\Generic\PrepareInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class SpaProcessor implements ProcessorInterface, ClearCacheInterface, ConfigurationAwareInterface, PrepareInterface
{
    use ConfigurationAwareTrait;

    /**
     * Cached files, mimes, extensions.
     *
     * $cached['mimes']['/js/app.js'] = 'application/javascript';
     *
     * @var array
     */
    private static $cached = [
        'mimes'     => [],
        'files'     => [],
        'extension' => [],
    ];

    /**
     * Object who responsible to compress outputs data.
     * It should be uses in Processor because needs loaded cache.
     *
     * @var \StaticServer\Compression\Compress
     */
    private $compress;

    /**
     * Prepare some data before accept server requests.
     *
     * @return void
     */
    public function prepare(): void
    {
        if (!is_null($this->compress)) {
            $this->compress->clearCache();
        }

        $this->compress = new Compress($this->configuration);
        $this->compress->prepare();
    }

    /**
     * Clear an object cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        self::$cached = [
            'mimes'     => [],
            'files'     => [],
            'extension' => [],
        ];
    }

    /**
     * Load to memory modified files.
     *
     * @param iterable $files
     *
     * @return void
     */
    public function load(iterable $files): void
    {
        /** @var \StaticServer\Transfer $item */
        foreach ($files as $item) {
            self::$cached['files'][$item->getLocation()] = $item->getContent();
            self::$cached['mimes'][$item->getLocation()] = $this->configuration->get('server.mimes.' . $item->getExtension(), 'text/plain');
            self::$cached['extension'][$item->getLocation()] = $item->getExtension();

            // Default page.
            if ($item->getFilename() === $this->configuration->get('server.index')) {
                self::$cached['files']['/'] = $item->getContent();
                self::$cached['mimes']['/'] = $this->configuration->get('server.mimes.html');
                self::$cached['extension']['/'] = 'html';
            }
        }

        // if index.html not provided.
        if (!isset(self::$cached['files']['/'])) {
            self::$cached['files']['/'] = 'default index page';
            self::$cached['mimes']['/'] = 'text/html';
            self::$cached['extension']['/'] = 'html';
        }

        unset($files);
    }

    /**
     * Processes incoming request with defined logic.
     * Specially without blocking return operation.
     *
     * @param string $body
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     *
     * @return void
     */
    public function process(string & $body, Request $request, Response $response): void
    {
        $uri = $request->server['request_uri'];

        if ($uri === '/healthcheck') {
            $response->header('Content-Type', 'text/plain; charset=utf-8');
            $body = 'ok';
            // if passed URI is file from fs, return it.
        } elseif (isset(self::$cached['files'][$uri])) {
            $response->header('Content-Type', self::$cached['mimes'][$uri] . '; charset=utf-8');
            $body = self::$cached['files'][$uri];
            // if file not found in memory and it has extension, return 404
        } elseif (pathinfo($uri, PATHINFO_EXTENSION)) {
            $response->status(404);
            $body = '404 not found';
        } else {
            // otherwise to forward the request to index file to handle it within javascript router.
            $response->header('Content-Type', self::$cached['mimes']['/'] . '; charset=utf-8');
            $body = self::$cached['files']['/'];
        }

        // It will be compressed output response if accept-encoding header
        // are present.
        $this->compress->handle($body, self::$cached, $request, $response);
    }
}
