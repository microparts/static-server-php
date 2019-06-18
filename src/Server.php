<?php declare(strict_types=1);

namespace StaticServer;

use Microparts\Configuration\Configuration;
use Microparts\Logger\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use StaticServer\Modifier\Modify;
use StaticServer\Modifier\InjectConfigFileToIndexModify;
use StaticServer\Modifier\NullGenericModify;
use StaticServer\Modifier\PrepareConfigModify;

final class Server
{
    /**
     * @var string
     */
    private $stage;

    /**
     * @var string
     */
    private $sha1;

    /**
     * @var string
     */
    private $level;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Microparts\Configuration\Configuration
     */
    private $conf;

    /**
     * Server constructor.
     *
     * @param string $stage
     * @param string $sha1
     * @param string $level
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(string $stage, string $sha1, string $level = LogLevel::INFO, LoggerInterface $logger = null)
    {
        $this->stage  = $stage;
        $this->sha1   = $sha1;
        $this->level  = $level;
        $this->logger = $logger ?: Logger::default('Server', $level);
        $this->conf   = $this->loadConf();
    }

    /**
     * @return string
     */
    public function getStage(): string
    {
        return $this->stage;
    }

    /**
     * @return string
     */
    public function getSha1(): string
    {
        return $this->sha1;
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * @return \StaticServer\Server
     */
    public static function new(): self
    {
        $stage = getenv('STAGE') ?: 'defaults';
        $sha1  = getenv('VCS_SHA1') ?: '';
        $level = getenv('LOG_LEVEL') ?: LogLevel::INFO;

        return new Server($stage, $sha1, $level);
    }

    /**
     * @return \StaticServer\Server
     */
    public static function silent(): self
    {
        $stage = getenv('STAGE') ?: 'defaults';
        $sha1  = getenv('VCS_SHA1') ?: '';
        $level = getenv('LOG_LEVEL') ?: LogLevel::INFO;

        return new Server($stage, $sha1, $level, new NullLogger());
    }

    /**
     * Start server with default configuration.
     *
     * @param bool $modify
     * @param bool $dryRun
     *
     * @return void
     */
    public function run(bool $modify = true, bool $dryRun = false): void
    {
        if ($modify) {
            $location = $this->getConfigName('/__config.js');
            $mod = new Modify();
            $mod->addTemplate(__DIR__ . '/stub/__config.js', $location);
            $mod->addModifier(new PrepareConfigModify($this->conf, $this->stage, $this->sha1));
            $mod->addModifier(new InjectConfigFileToIndexModify($this->conf, $location));
        } else {
            $mod = new NullGenericModify();
        }

        $http = new HttpApplication($this->conf, $this->stage, $this->sha1);
        $http->setLogger($this->logger);
        $http->setModifier($mod);

        $dryRun ? $http->dryRun() : $http->run();
    }

    /**
     * DryRun.
     *
     * @param bool $modify
     */
    public function dryRun(bool $modify = true): void
    {
        $this->run($modify, true);
    }

    /**
     * For debug only.
     *
     * @return void
     */
    public function dump(): void
    {
        printf("CONFIG_PATH = %s\n", $this->conf->getPath());
        printf("STAGE = %s\n", $this->getStage());
        printf("VCS_SHA1 = %s\n", $this->getSha1());
        printf("LOG_LEVEL = %s\n", $this->getLevel());

        printf('%s', $this->conf->dump());
    }

    /**
     * @return Configuration
     */
    private function loadConf(): Configuration
    {
        $conf = Configuration::auto($this->getStage());
        $conf->setLogger($this->logger);
        $conf->load();

        return $conf;
    }

    /**
     * @param string $location
     *
     * @return string
     */
    private function getConfigName(string $location): string
    {
        if (strlen($this->getSha1()) < 1) {
            return $location;
        }

        $file = pathinfo($location);

        return sprintf(
            '%s%s_%s.%s',
            $file['dirname'],
            $file['filename'],
            $this->getSha1(),
            $file['extension']
        );
    }
}
