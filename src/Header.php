<?php declare(strict_types=1);

namespace StaticServer;

use InvalidArgumentException;
use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use StaticServer\Generic\ClearCacheInterface;
use StaticServer\Generic\PrepareInterface;
use Swoole\Http\Response;

final class Header implements PrepareInterface, ClearCacheInterface, ConfigurationAwareInterface
{
    use ConfigurationAwareTrait;

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
        'link'                   => 'Link',
    ];

    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * Prepared headers to sent.
     *
     * @var array
     */
    private $prepared = [];

    /**
     * Prepare headers before handling requests.
     *
     * https://tools.ietf.org/html/rfc5988#section-5.5
     *
     * @return void
     */
    public function prepare(): void
    {
        foreach ($this->configuration->get('server.headers') as $header => $values) {
            if (!isset(self::CONFIG_MAP[$header])) {
                throw new InvalidArgumentException('Header not supported.');
            }

            $item = self::CONFIG_MAP[$header];

            // Backward compatibility.
            if (!isset($values[0]['value'])) {
                $this->prepared[$item] = join('; ', (array) $values);
            }

            // Checks new extended format for sent headers from yaml values.
            if (is_array($values) && count($values) > 0 && isset($values[0]['value'])) {
                $array = [];
                foreach ($values as $value) {
                    if (!isset($value['value'])) {
                        throw new InvalidArgumentException('Invalid header format, see docs & examples.');
                    }

                    $array[] = join('; ', (array) $value['value']);
                }

                $this->prepared[$item] = join(', ', $array);
            }
        }
    }

    /**
     * Sent prepared headers.
     *
     * @param \Swoole\Http\Response $response
     *
     * @return void
     */
    public function sent(Response $response): void
    {
        $response->header('software-server', '');
        $response->header('server', '');

        foreach ($this->prepared as $header => $value) {
            $response->header($header, $value);
        }
    }

    /**
     * Clear an object cache.
     */
    public function clearCache(): void
    {
        $this->prepared = [];
    }
}
