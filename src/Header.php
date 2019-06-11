<?php declare(strict_types=1);

namespace StaticServer;

use Microparts\Configuration\ConfigurationInterface;
use Swoole\Http\Response;

final class Header
{
    /**
     * Map headers names in config to real http headers names
     */
    private const CONFIG_MAP = [
        'pragma'                 => 'Pragma',
        'cache_control'          => 'Cache-Control',
        'frame_options'          => 'X-Frame-Options',
        'referer_policy'         => 'Referrer-Policy',
        'feature_policy'         => 'Feature-Policy',
        'csp'                    => 'Content-Security-Policy',
        'xss_protection'         => 'X-XSS-Protection',
        'x_content_type'         => 'X-Content-Type',
        'x_content_type_options' => 'X-Content-Type-Options',
        'x_ua_compatible'        => 'X-UA-Compatible',
        'sts'                    => 'Strict-Transport-Security',
    ];

    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var array
     */
    private $prepared = [];

    /**
     * Header constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;

        $this->prepareBeforeRequest();
    }

    /**
     * Prepare headers before request.
     */
    private function prepareBeforeRequest(): void
    {
        foreach ($this->conf->get('server.headers') as $header => $value) {
            $this->prepared[self::CONFIG_MAP[$header]] = join('; ', (array) $value);
        }
    }

    /**
     * @param \Swoole\Http\Response $response
     */
    public function sent(Response $response): void
    {
        $response->header('software-server', '');
        $response->header('server', '');

        foreach ($this->prepared as $header => $value) {
            $response->header($header, $value);
        }
    }
}
