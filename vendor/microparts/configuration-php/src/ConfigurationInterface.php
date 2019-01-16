<?php

namespace Microparts\Configuration;

/**
 * Interface ConfigurationInterface
 *
 * @package Microparts\Configuration
 */
interface ConfigurationInterface
{
    /**
     * Get's a value from config by dot notation
     * E.g get('x.y', 'foo') => returns the value of $config['x']['y']
     * And if not exist, return 'foo'
     * 
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Gets all the tree config
     * 
     * @return array
     */
    public function all();
}
