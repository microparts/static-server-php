<?php declare(strict_types=1);

namespace StaticServer\Processor;

use Microparts\Configuration\ConfigurationInterface;
use StaticServer\Compression\Compress;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class SpaProcessor implements ProcessorInterface
{
    /**
     * Cached files.
     *
     * @var array
     */
    private static $cached = [
        'mimes'     => [],
        'files'     => [],
        'extension' => [],
    ];

    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var \StaticServer\Compression\Compress
     */
    private $compress;

    /**
     * SpaProcessor constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;
        $this->compress = new Compress($conf);
    }

    /**
     * Load to memory modified files.
     *
     * @param iterable $files
     */
    public function load(iterable $files): void
    {
        /** @var \StaticServer\Transfer $item */
        foreach ($files as $item) {
            self::$cached['files'][$item->getLocation()] = $item->getContent();
            self::$cached['mimes'][$item->getLocation()] = $this->conf->get('server.mimes.' . $item->getExtension(), 'text/plain');
            self::$cached['extension'][$item->getLocation()] = $item->getExtension();

            // Default page.
            if ($item->getFilename() === $this->conf->get('server.index')) {
                self::$cached['files']['/'] = $item->getContent();
                self::$cached['mimes']['/'] = $this->conf->get('server.mimes.html');
                self::$cached['extension']['/'] = 'html';
            }
        }

        unset($files);
    }

    /**
     * @param string $body
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return void
     */
    public function process(string & $body, Request $request, Response $response): void
    {
        $uri = $request->server['request_uri'];

        // if passed URI is file from fs, return it.
        if (isset(self::$cached['files'][$uri])) {
            $response->header('Content-Type', self::$cached['mimes'][$uri] . '; charset=utf-8');
            $body = self::$cached['files'][$uri];
            // if file not found in memory and it has extension, return 404
        } elseif (pathinfo($uri, PATHINFO_EXTENSION)) {
            $response->status(404);
            $body = '404 not found';
        } else {
            // otherwise to forward the request to index file to handle it within javascript router.
            $response->header('Content-Type', self::$cached['files']['/'] . '; charset=utf-8');
            $body = self::$cached['files']['/'];
        }

        // It will be compressed output response if accept-encoding header
        // are present.
        $this->compress->handle($body, self::$cached, $request, $response);
    }
}
