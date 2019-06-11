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
    private $algorithms;

    /**
     * Compress constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;

        foreach ($this->conf->get('server.compression.algorithms') as $algorithm) {
            $this->algorithms[] = [$algorithm['method'], CompressionFactory::create(
                $algorithm['method'],
                $algorithm['level']
            )];
        }
    }

    /**
     * @param string $body
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public function handle(string & $body, Request $request, Response $response): void
    {
        $accept = $request->header['accept-encoding'] ?? false;

        if ($this->conf->get('server.compression.enabled') && $accept) {
            $encoding = array_map('trim', explode(',', strtolower($accept)));
            foreach ($this->algorithms as [$method, $algorithm]) {
                /** @var \StaticServer\Compression\CompressionInterface $algorithm */
                if (in_array($method, $encoding, true)) {
                    $response->header('Content-Encoding', $method);
                    $body = $algorithm->compress($body);
                    break;
                }
            }
        }
    }
}
