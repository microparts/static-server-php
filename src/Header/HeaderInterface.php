<?php declare(strict_types=1);

namespace StaticServer\Header;

use Microparts\Configuration\ConfigurationInterface;

interface HeaderInterface
{
    /**
     * Map headers names in config to real http headers names
     */
    public const CONFIG_MAP = [
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
     * Converts headers declared in Yaml configuration to real.
     * Due to backward compatibility.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @return array
     */
    public function convert(ConfigurationInterface $conf): array;
}
