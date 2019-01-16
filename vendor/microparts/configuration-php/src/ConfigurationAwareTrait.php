<?php
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 18/11/2018
 */

namespace Microparts\Configuration;

/**
 * Basic Implementation of ConfigurationAwareInterface.
 */
trait ConfigurationAwareTrait
{
    /**
     * The Configuration instance.
     *
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    protected $configuration;

    /**
     * Sets a configuration.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $configuration
     */
    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }
}

