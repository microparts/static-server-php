<?php
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 18/11/2018
 */

namespace Microparts\Configuration;

/**
 * Describes a configuration-aware instance.
 */
interface ConfigurationAwareInterface
{
    /**
     * Sets a configuration instance on the object.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $configuration
     *
     * @return void
     */
    public function setConfiguration(ConfigurationInterface $configuration);
}

