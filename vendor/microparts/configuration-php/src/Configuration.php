<?php declare(strict_types=1);

namespace Microparts\Configuration;

use ArrayAccess;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 *
 * @package Microparts\Configuration
 */
class Configuration implements ConfigurationInterface, ArrayAccess, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Name of CONFIG_PATH variable
     */
    private const CONFIG_PATH = 'CONFIG_PATH';

    /**
     * Name of stage ENV variable
     */
    private const STAGE = 'STAGE';

    /**
     * Config tree goes here
     */
    private $config;

    /**
     * @var null|string
     */
    private $path;

    /**
     * @var null|string
     */
    private $stage;

    /**
     * Configuration constructor.
     *
     * @param null|string $path
     * @param null|string $stage
     */
    public function __construct(?string $path = null, ?string $stage = null)
    {
        if (null === $path) {
            $this->setPath($this->getEnvVariable(self::CONFIG_PATH, '/app/configuration'));
        } else {
            $this->setPath($path);
        }

        if (null === $stage) {
            $this->setStage($this->getEnvVariable(self::STAGE, 'local'));
        } else {
            $this->setStage($stage);
        }

        // Set up default black hole logger.
        // If u want to see logs and see how load process working,
        // change it from outside to your default logger object in you application
        $this->setLogger(new NullLogger());
    }

    /**
     * Get's a value from config by dot notation
     * E.g get('x.y', 'foo') => returns the value of $config['x']['y']
     * And if not exist, return 'foo'
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $config = $this->config;

        array_map(function ($key) use (&$config, $default) {
            $config = $config[$key] ?? $default;
        }, explode('.', $key));

        return $config;
    }

    /**
     * Gets all the tree config
     *
     * @return mixed
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * Set the configuration path
     *
     * @param null|string $path
     * @return \Microparts\Configuration\Configuration
     */
    public function setPath(?string $path): Configuration
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the configuration path
     *
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param null|string $stage
     * @return Configuration
     */
    public function setStage(?string $stage): Configuration
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getStage(): ?string
    {
        return $this->stage;
    }

    /**
     * Whether a offset exists
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return ! empty($this->get($offset));
    }

    /**
     * Offset to retrieve
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('Not allowed here.');
    }

    /**
     * Offset to unset
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('Not allowed here.');
    }

    /**
     * Initialize all the magic down here
     */
    public function load(): Configuration
    {
        $this->logger->info(self::CONFIG_PATH . ' = ' . $this->getPath());
        $this->logger->info(self::STAGE . ' = ' . $this->getStage());

        $this->config = $this->arrayMergeRecursiveDistinct(
            $this->parseConfiguration(),
            $this->parseConfiguration($this->getStage())
        );

        $this->logger->info('Configuration module loaded');

        return $this;
    }

    /**
     * Parses configuration and makes a tree of it
     *
     * @param string $stage
     * @return array
     */
    protected function parseConfiguration($stage = 'defaults')
    {
        $pattern = $this->getPath() . '/' . $stage . '/*.yaml';
        $files = glob($pattern, GLOB_NOSORT | GLOB_ERR);

        if ($files === false) {
            $message = "Glob does not walk to files, pattern: {$pattern}. Path is correct?";
            $this->logger->info($message);
            throw new InvalidArgumentException($message);
        }

        $this->logger->debug('Following config files found:', $files);

        $config = [];
        foreach ($files as $filename) {
            $yamlFileContent = $this->parseYamlFile($filename);

            if (empty($yamlFileContent)) {
                $this->logger->debug("Parse error while reading file: {$filename}, skip it.");
                continue;
            }

            $config = $this->arrayMergeRecursiveDistinct($config, current($yamlFileContent));
        }

        return $config;
    }

    /**
     * Parses the yaml file
     *
     * @param $path
     * @return array|mixed
     */
    protected function parseYamlFile($path)
    {
        try {
            return Yaml::parseFile($path);
        } catch (ParseException $e) {
            return [];
        }
    }

    /**
     * Takes an env variable and returns default if not exist
     *
     * @param $variable
     * @param $default
     * @return array|false|string
     */
    protected function getEnvVariable($variable, $default)
    {
        return getenv($variable) ?: $default;
    }

    /**
     * Merges any number of arrays / parameters recursively, replacing
     * entries with string keys with values from latter arrays.
     * If the entry or the next value to be assigned is an array, then it
     * automagically treats both arguments as an array.
     * Numeric entries are appended, not replaced, but only if they are
     * unique
     *
     * @param array ...$arrays
     * @return array|mixed
     */
    private function arrayMergeRecursiveDistinct(array ...$arrays)
    {
        $base = array_shift($arrays);
        if ( ! is_array($base)) {
            $base = empty($base) ? [] : [$base];
        }

        foreach ($arrays as $append) {
            if ( ! is_array($append)) {
                $append = [$append];
            }
            foreach ($append as $key => $value) {
                if ( ! array_key_exists($key, $base) && ! is_numeric($key)) {
                    $base[$key] = $append[$key];
                    continue;
                }

                if (is_array($value) || is_array($base[$key])) {
                    $base[$key] = $this->arrayMergeRecursiveDistinct($base[$key], $append[$key]);
                } else {
                    if (is_numeric($key)) {
                        if ( ! in_array($value, $base)) {
                            $base[] = $value;
                        }
                    } else {
                        $base[$key] = $value;
                    }
                }
            }
        }

        return $base;
    }
}
