<?php declare(strict_types=1);

namespace StaticServer;

use LogicException;
use Microparts\Configuration\Configuration;
use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationInterface;
use Microparts\Logger\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use StaticServer\Handler\HandlerFactory;
use StaticServer\Handler\HandlerInterface;
use StaticServer\Header\ConvertsHeader;
use StaticServer\Modifier\DefaultPreferencesConfigurator;
use StaticServer\Modifier\GenericModifyInterface;
use StaticServer\Modifier\Iterator\IteratorInterface;
use StaticServer\Modifier\Iterator\RecursiveIterator;
use StaticServer\Modifier\ModifyConfiguratorInterface;
use Throwable;

final class Application
{
    public const VERSION = '2.0.3';
    private const SERVER_LOG_CHANNEL = 'Server';

    private Configuration $conf;
    private HandlerInterface $handler;
    private IteratorInterface $iterator;
    private ModifyConfiguratorInterface $modifyConfigurator;
    private GenericModifyInterface $modify;
    private string $stage;
    private string $sha1;
    private LoggerInterface $logger;

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

        $this->setModifyConfigurator(new DefaultPreferencesConfigurator($stage, $sha1));
        $this->setIterator(new RecursiveIterator());
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
     * ModifyConfigurationObject to configure how to files can be modified.
     *
     * @param \StaticServer\Modifier\ModifyConfiguratorInterface $configurator
     * @return void
     */
    public function setModifyConfigurator(ModifyConfiguratorInterface $configurator): void
    {
        $this->modifyConfigurator = $configurator;
    }

    /**
     * Dry run without start of server.
     *
     * @return void
     * @throws \Exception
     */
    public function dryRun(): void
    {
        $this->loadConfiguration();
        $this->loadLogger();

        $this->ready();
    }

    /**
     * Run server.
     *
     * @codeCoverageIgnore
     * @return void
     * @throws \Throwable
     */
    public function run(): void
    {
        $this->loadConfiguration();
        $this->loadLogger();

        try {
            $this->ready();
            $this->handler->start();
        } catch (Throwable $e) {
            if (isset($this->handler)) {
                $this->handler->stop();
            }
            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function reload(): void
    {
        $this->loadConfiguration();
        $this->loadLogger();

        $this->logger->info('Reload configuration');

        try {
            $this->ready();
            $this->handler->reload();
            $this->logger->info('Configuration reloaded');
        } catch (Throwable $e) {
            if (isset($this->handler)) {
                $this->handler->stop();
            }
            throw $e;
        }
    }

    public function stop(): void
    {
        $this->loadConfiguration();
        $this->loadLogger();

        $this->logger->info('Stopping server...');

        $this->shutUpLogger();

        $this->ready(false);
        $this->handler->stop();
    }

    /**
     * Prepare server to run.
     *
     * @codeCoverageIgnore
     * @param bool $checkConfig
     * @return void
     * @throws \Exception
     */
    private function ready(bool $checkConfig = true): void
    {
        $format = 'State: STAGE=%s SHA1=%s VERSION=%s CONFIG_PATH=%s';
        $message = sprintf($format, $this->stage, $this->sha1, self::VERSION, $this->conf->getPath());
        $this->logger->info($message);

        $this->putModifyConfiguratorDependenciesToObject();
        $this->modify = $this->modifyConfigurator->getModifier();
        $this->handler = HandlerFactory::createHandler(
            $this->conf->get('server.handler.name'),
            $this->conf->get('server.handler.options')
        );

        $this->putLoggerToObjects();
        $this->putConfigurationToObjects();

        $this->logger->debug('Iterates files from server root dir');
        $files = $this->iterator->iterate();

        $this->logger->debug(sprintf('Modifying files, add templates and override existing. Used: [%s]', get_class($this->modifyConfigurator)));
        $this->modify->modifyAndSaveToDisk($files);

        $this->logger->debug(sprintf('Generate configuration for Handler. Used: [%s]', get_class($this->handler)));
        $this->handler->checkDependenciesBeforeStart();
        $this->handler->generateConfig(new ConvertsHeader());

        if ($checkConfig) {
            $this->handler->checkConfig();
        }
    }

    /**
     * Put some objects to object. Lol.
     *
     * @return void
     */
    private function putModifyConfiguratorDependenciesToObject(): void
    {
        if ($this->modifyConfigurator instanceof LoggerAwareInterface) {
            $this->logger->debug(sprintf('Put logger to: %s', get_class($this->modifyConfigurator)));
            $this->modifyConfigurator->setLogger($this->logger);
        }

        if ($this->modifyConfigurator instanceof ConfigurationAwareInterface) {
            $this->logger->debug(sprintf('Put configuration to: %s', get_class($this->modifyConfigurator)));
            $this->modifyConfigurator->setConfiguration($this->conf);
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
            $this->logger->debug(sprintf('Put logger to: %s', get_class($this->logger)));
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
     * @return void
     */
    private function loadConfiguration(): void
    {
        $this->conf = Configuration::auto($this->stage);
        $this->conf->load();
    }

    /**
     * @return void
     */
    private function loadLogger(): void
    {
        if (!$this->conf instanceof ConfigurationInterface) {
            throw new LogicException('Can\'t load Server logger before Configuration.');
        }

        if (!$this->conf->get('server.logger.enabled')) {
            $this->shutUpLogger();
            return;
        }

        $this->logger = Logger::default(
            self::SERVER_LOG_CHANNEL,
            $this->conf->get('server.logger.level')
        );
    }

    private function shutUpLogger(): void
    {
        $this->logger = new NullLogger();
    }
}
