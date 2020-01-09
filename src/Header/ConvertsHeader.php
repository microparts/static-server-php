<?php declare(strict_types=1);

namespace StaticServer\Header;

use InvalidArgumentException;
use Microparts\Configuration\ConfigurationInterface;

final class ConvertsHeader implements HeaderInterface
{
    /**
     * Converts headers declared in Yaml configuration to real.
     * Due to backward compatibility.
     *
     * https://tools.ietf.org/html/rfc5988#section-5.5
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @return array<string, string>
     */
    public function convert(ConfigurationInterface $conf): array
    {
        $results = [];

        foreach ($conf->get('server.headers') as $header => $values) {
            if (!isset(self::CONFIG_MAP[$header])) {
                throw new InvalidArgumentException('Header not supported.');
            }

            $item = self::CONFIG_MAP[$header];

            // Backward compatibility.
            if (!isset($values[0]['value'])) {
                $results[$item] = join('; ', (array) $values);
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

                $results[$item] = join(', ', $array);
            }
        }

        return $results;
    }
}
