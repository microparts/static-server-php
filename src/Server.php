<?php declare(strict_types=1);

namespace StaticServer;

use Microparts\Logger\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use StaticServer\Modifier\Modify;
use StaticServer\Modifier\InjectConfigFileToIndexModify;
use StaticServer\Modifier\NullGenericModify;
use StaticServer\Modifier\PrepareConfigModify;
use StaticServer\Modifier\SecurityTxtModify;

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
     * @var \StaticServer\Application
     */
    private $app;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Server constructor.
     *
     * @param \StaticServer\Application $app
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(Application $app = null, LoggerInterface $logger = null)
    {
        $this->app    = $app;
        $this->logger = $logger ?: Logger::default('Server');
    }

    /**
     * @param string $stage
     */
    public function setStage(string $stage): void
    {
        $this->stage = $stage;
    }

    /**
     * @param string $sha1
     */
    public function setSha1(string $sha1): void
    {
        $this->sha1 = $sha1;
    }

    /**
     * @param string $level
     */
    public function setLevel(string $level): void
    {
        $this->level = $level;
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
     * @param bool $modify
     * @return \StaticServer\Server
     */
    public static function new(bool $modify = true): self
    {
        $server = new Server();
        $server->setFromGlobals();
        $server->setDefaultPreferences($modify);

        return $server;
    }

    /**
     * @param bool $modify
     * @return \StaticServer\Server
     */
    public static function silent(bool $modify = true): self
    {
        $server = new Server(null, new NullLogger());
        $server->setFromGlobals();
        $server->setDefaultPreferences($modify);

        return $server;
    }

    /**
     * Start server.
     *
     * @param bool $dryRun
     *
     * @return void
     */
    public function run(bool $dryRun = false): void
    {
        $dryRun ? $this->app->dryRun() : $this->app->run();
    }

    /**
     * DryRun.
     */
    public function dryRun(): void
    {
        $this->app->dryRun();
    }

    /**
     * For debug only.
     *
     * @return void
     */
    public function dump(): void
    {
        $conf = $this->app->getConfiguration();

        printf("CONFIG_PATH = %s\n", $conf->getPath());
        printf("STAGE = %s\n", $this->getStage());
        printf("VCS_SHA1 = %s\n", $this->getSha1());
        printf("LOG_LEVEL = %s\n", $this->getLevel());

        printf('%s', $conf->dump());
    }

    /**
     * Set common variables from globals.
     *
     * @return void
     */
    public function setFromGlobals(): void
    {
        $stage = getenv('STAGE') ?: 'defaults';
        $sha1  = getenv('VCS_SHA1') ?: '';
        $level = getenv('LOG_LEVEL') ?: LogLevel::INFO;

        $this->setStage($stage);
        $this->setSha1($sha1);
        $this->setLevel($level);
    }

    /**
     * Creates Application object with default dependencies.
     *
     * @param bool $modify
     *
     * @return void
     */
    public function setDefaultPreferences(bool $modify = true): void
    {
        if ($modify) {
            $mod = new Modify();
            $location = $this->getConfigName('/__config.js');

            $mod->addTemplate(__DIR__ . '/stub/__config.js', $location);
            $mod->addTemplate(__DIR__ . '/stub/security.txt', '/.well-known/security.txt');
            $mod->addModifier(new PrepareConfigModify($this->stage, $this->sha1));
            $mod->addModifier(new SecurityTxtModify());
            $mod->addModifier(new InjectConfigFileToIndexModify($location));
        } else {
            $mod = new NullGenericModify();
        }

        $app = new Application($this->stage, $this->sha1);
        $app->setLogger($this->logger);
        $app->setModifier($mod);

        $this->app = $app;
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
