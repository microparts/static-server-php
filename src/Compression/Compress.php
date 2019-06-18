<?php declare(strict_types=1);

namespace StaticServer\Compression;

use Microparts\Configuration\ConfigurationInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class Compress
{
    /**
     * Configuration object.
     *
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * Pre-loaded objects to compress data to any format.
     *
     * @var array
     */
    private static $objects = [];

    /**
     * Cache for once parsed header.
     *
     * @var array
     */
    private static $accept = [];

    /**
     * Allowed extensions to compress.
     *
     * @var array
     */
    private static $extensions = [];

    /**
     * Cache for once compressed contents.
     *
     * @var array
     */
    private static $compressed = [];

    /**
     * Compress constructor with prior initialization
     * before requests handling.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;

        $this->preInitializationCompressionObjects();
        $this->preInitializationAllowedExtensions();
    }

    /**
     * Handle compression of loaded contents to server cache.
     * Specially without blocking return operation.
     *
     * @param string $body
     * @param array $cached
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     *
     * @return void
     */
    public function handle(string & $body, array & $cached, Request $request, Response $response): void
    {
        $uri    = $request->server['request_uri'];
        $accept = $request->header['accept-encoding'] ?? false;
        $ext    = $cached['extension'][$uri] ?? false;

        if ($this->conf->get('server.compression.enabled') && $accept && isset(self::$extensions[$ext])) {
            $method = '';
            $this->parseHeader($accept, $method);
            $response->header('Content-Encoding', $method);
            $this->compressOrNot($uri, $method, $body);
        }
    }

    /**
     * Pre init compression objects.
     *
     * @return void
     */
    private function preInitializationCompressionObjects(): void
    {
        foreach ($this->conf->get('server.compression.algorithms') as $algorithm) {
            self::$objects[$algorithm['method']] = CompressionFactory::create($algorithm['method'], $algorithm['level']);
        }
    }

    /**
     * Pre init allowed extensions.
     *
     * @return void
     */
    private function preInitializationAllowedExtensions(): void
    {
        self::$extensions = array_fill_keys($this->conf->get('server.compression.extensions'), true);
    }

    /**
     * Return once-parsed encoding.
     * Specially without blocking return operation.
     *
     * @param string $header
     * @param string $method
     *
     * @return void
     */
    private function parseHeader(string $header, string & $method): void
    {
        if ($header === '*') {
            $method = 'br';
        } elseif (isset(self::$accept[$header])) {
            $method = self::$accept[$header];
        } else {
            $enc = array_map('trim', explode(',', strtolower($header)));

            foreach ($this->conf->get('server.compression.algorithms') as $algorithm) {
                if (in_array($algorithm['method'], $enc, true)) {
                    self::$accept[$header] = $algorithm['method'];
                    break;
                }
            }

            // matches header or uses fallback if it broken
            $method = self::$accept[$header] ?? $this->conf->get('server.compression.fallback');
        }
    }

    /**
     * Return once-compressed contents.
     * Specially without blocking return operation.
     *
     * @param string $uri
     * @param string $method
     * @param string $body
     *
     * @return void
     */
    private function compressOrNot(string $uri, string $method, string & $body): void
    {
        if (isset(self::$compressed[$method][$uri])) {
            $body = self::$compressed[$method][$uri];
        } else {
            $body = self::$compressed[$method][$uri] = self::$objects[$method]->compress($body);
        }
    }
}
