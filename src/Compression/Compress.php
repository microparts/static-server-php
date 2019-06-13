<?php declare(strict_types=1);

namespace StaticServer\Compression;

use Microparts\Configuration\ConfigurationInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class Compress
{
    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var array
     */
    private static $objects = [];

    /**
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
     * @var array
     */
    private static $compressed = [];

    /**
     * Compress constructor.
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
     * @param string $body
     * @param array $cached
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public function handle(string & $body, array & $cached, Request $request, Response $response): void
    {
        $uri = $request->server['request_uri'];
        $accept = $request->header['accept-encoding'] ?? false;
        $ext = $cached['extension'][$uri] ?? false;

        if ($this->conf->get('server.compression.enabled') && $accept && isset(self::$extensions[$ext])) {
            $method = $this->parseHeader($accept);
            $response->header('Content-Encoding', $method);
            $this->compressOrNot($uri, $method, $body);
        }
    }

    /**
     * Pre init compression objects.
     */
    private function preInitializationCompressionObjects(): void
    {
        foreach ($this->conf->get('server.compression.algorithms') as $algorithm) {
            self::$objects[$algorithm['method']] = CompressionFactory::create($algorithm['method'], $algorithm['level']);
        }
    }

    /**
     * Pre init allowed extensions.
     */
    private function preInitializationAllowedExtensions(): void
    {
        self::$extensions = array_fill_keys($this->conf->get('server.compression.extensions'), true);
    }

    /**
     * Return once-parsed encoding.
     *
     * @param string $header
     * @return string
     */
    private function parseHeader(string $header): string
    {
        if (isset(self::$accept[$header])) {
            return self::$accept[$header];
        }

        $enc = array_map('trim', explode(',', strtolower($header)));

        foreach ($this->conf->get('server.compression.algorithms') as $algorithm) {
            if (in_array($algorithm['method'], $enc, true)) {
                self::$accept[$header] = $algorithm['method'];
                break;
            }
        }

        return self::$accept[$header];
    }

    /**
     * @param string $uri
     * @param string $method
     * @param string $body
     *
     * @return void
     */
    private function compressOrNot(string $uri, string $method, string & $body): void
    {
        if (isset(self::$compressed[$uri])) {
            $body = self::$compressed[$uri];
        } else {
            $body = self::$compressed[$uri] = self::$objects[$method]->compress($body);
        }
    }
}
