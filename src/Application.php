<?php declare(strict_types=1);

namespace StaticServer;

use Microparts\Configuration\Configuration;
use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StaticServer\Handler\HandlerInterface;
use StaticServer\Header\ConvertsHeader;
use StaticServer\Modifier\Iterator\IteratorInterface;
use StaticServer\Modifier\Iterator\RecursiveIterator;
use StaticServer\Modifier\GenericModifyInterface;
use StaticServer\Modifier\NullGenericModify;

final class Application
{
    use LoggerAwareTrait;

    private ConfigurationInterface $conf;
    private HandlerInterface $handler;
    private IteratorInterface $iterator;
    private GenericModifyInterface $modify;
    private string $stage;
    private string $sha1;

    /**
     * Application constructor.
     *
     * @param string $stage
     * @param string $sha1
     */
    public function __construct(string $stage = '', string $sha1 = '')
    {
        $this->stage = $stage;
        $this->sha1  = $sha1;
        $this->conf  = $this->loadConfiguration();

        // disable logs and modifiers by default
        $this->setLogger(new NullLogger());
        $this->setModifier(new NullGenericModify());
        $this->setIterator(new RecursiveIterator($this->logger));
    }

    /**
     * @param \StaticServer\Handler\HandlerInterface $handler
     * @return Application
     */
    public function setHandler(HandlerInterface $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Iterator to iterate files in server.root.
     *
     * @param \StaticServer\Modifier\Iterator\IteratorInterface $iterator
     * @return void
     */
    public function setIterator(IteratorInterface $iterator): void
    {
        $this->iterator = $iterator;
    }

    /**
     * Modifier for modify incoming files or add new one.
     *
     * @param \StaticServer\Modifier\GenericModifyInterface $modify
     *
     * @return void
     */
    public function setModifier(GenericModifyInterface $modify): void
    {
        $this->modify = $modify;
    }

    /**
     * Get the server configuration.
     *
     * @return \Microparts\Configuration\Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->conf;
    }

    /**
     * Dry run without start of server.
     *
     * @return void
     */
    public function dryRun(): void
    {
        $this->ready();
    }

    /**
     * Run server.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function run(): void
    {
        $this->ready();
        $this->handler->start();
    }

    public function stop(): void
    {
        $this->logger->info('Stopping server...');

        $this->setLogger(new NullLogger());
        $this->ready(false);
        $this->handler->stop();
    }

    public function reload(): void
    {
        $this->ready();
        $this->handler->reload();
    }

    /**
     * Prepare server to run.
     *
     * @codeCoverageIgnore
     * @param bool $checkConfig
     * @return void
     */
    private function ready(bool $checkConfig = true): void
    {
        $format = 'State: STAGE=%s SHA1=%s CONFIG_PATH=%s';
        $message = sprintf($format, $this->stage, $this->sha1, $this->conf->getPath());
        $this->logger->info($message);

        $this->logger->debug('Reload configuration');
        $this->conf = $this->loadConfiguration();

        $this->putLoggerToObjects();
        $this->putConfigurationToObjects();

        $this->logger->debug('Iterates files from server root dir');
        $files = $this->iterator->iterate();

        $this->logger->debug(sprintf('Modifying files, add templates and override existing. Used: [%s]', get_class($this->modify)));
        $this->modify->modifyAndSaveToDisk($files);

        $this->logger->debug(sprintf('Generate configuration for Handler. Used: [%s]', get_class($this->handler)));
        $this->handler->checkDependenciesBeforeStart();
        $this->handler->generateConfig(new ConvertsHeader());

        if ($checkConfig) {
            $this->handler->checkConfig();
        }
    }

    /**
     * Put logger to objects if it supports.
     *
     * @return void
     */
    private function putLoggerToObjects(): void
    {
        if ($this->iterator instanceof LoggerAwareInterface) {
            $this->logger->debug(sprintf('Put logger to: %s', get_class($this->iterator)));
            $this->iterator->setLogger($this->logger);
        }

        if ($this->modify instanceof LoggerAwareInterface) {
            $this->logger->debug(sprintf('Put logger to: %s', get_class($this->modify)));
            $this->modify->setLogger($this->logger);
        }

        if ($this->handler instanceof LoggerAwareInterface) {
            $this->logger->debug(sprintf('Put logger to: %s', get_class($this->handler)));
            $this->handler->setLogger($this->logger);
        }
    }

    /**
     * Put config to objects if it supports.
     *
     * @return void
     */
    private function putConfigurationToObjects(): void
    {
        if ($this->iterator instanceof ConfigurationAwareInterface) {
            $this->logger->debug(sprintf('Put configuration to: %s', get_class($this->iterator)));
            $this->iterator->setConfiguration($this->conf);
        }

        if ($this->modify instanceof ConfigurationAwareInterface) {
            $this->logger->debug(sprintf('Put configuration to: %s', get_class($this->modify)));
            $this->modify->setConfiguration($this->conf);
        }

        if ($this->handler instanceof ConfigurationAwareInterface) {
            $this->logger->debug(sprintf('Put configuration to: %s', get_class($this->handler)));
            $this->handler->setConfiguration($this->conf);
        }
    }

    /**
     * @return Configuration
     */
    private function loadConfiguration(): Configuration
    {
        $conf = Configuration::auto($this->stage);
        $conf->load();

        return $conf;
    }
}
