<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-19
 */

namespace StaticServer;

use LogicException;
use Microparts\Configuration\Configuration;
use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use StaticServer\Handler\InjectConfigFileToIndexHandler;
use StaticServer\Handler\PrepareConfigHandler;
use StaticServer\Middleware\ContentSecurityPolicyMiddleware;
use Symfony\Component\Yaml\Yaml;

final class SimpleInit
{
    /**
     * Possible path's of configuration.
     *
     * @var array
     */
    private $possibleConfLocations = [
        '/app/configuration',
        './configuration',
        __DIR__ . '/../configuration',
    ];

    /**
     * @var string
     */
    private $path;

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
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * SimpleInit constructor.
     *
     * @param string $stage
     * @param string $sha1
     * @param string $level
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(string $stage, string $sha1, string $level = LogLevel::INFO, LoggerInterface $logger = null)
    {
        $this->path   = $this->findConfiguration();
        $this->stage  = $stage;
        $this->sha1   = $sha1;
        $this->level  = $level;
        $this->logger = $logger ?: PrettyLogger::create($this->level);
        $this->conf   = $this->loadConf();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
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
     * @return \StaticServer\SimpleInit
     */
    public static function new()
    {
        $stage = getenv('STAGE') ?: 'local';
        $sha1  = getenv('VCS_SHA1') ?: '';
        $level = getenv('LOG_LEVEL') ?: LogLevel::INFO;

        return new SimpleInit($stage, $sha1, $level);
    }

    /**
     * @return \StaticServer\SimpleInit
     */
    public static function silent()
    {
        $stage = getenv('STAGE') ?: 'local';
        $sha1  = getenv('VCS_SHA1') ?: '';
        $level = getenv('LOG_LEVEL') ?: LogLevel::INFO;

        return new SimpleInit($stage, $sha1, $level, new NullLogger());
    }

    /**
     * Start server with default configuration.
     *
     * @param bool $dryRun
     */
    public function run(bool $dryRun = false)
    {
        $walker = new FileWalker($this->logger);
        $walker->addGhostFile(__DIR__ . '/stub/__config.js');
        $walker->addHandler(new InjectConfigFileToIndexHandler($this->conf));
        $walker->addHandler(new PrepareConfigHandler($this->conf, $this->stage, $this->sha1));

        $http = new HttpApplication($this->conf, $this->logger, $walker);
//        $http->use(new ContentSecurityPolicyMiddleware($this->conf));

        $dryRun ? $http->dryRun() : $http->run();
    }

    /**
     * For debug only.
     *
     * @return string
     */
    public function dump(): string
    {
        printf("CONFIG_PATH = %s\n", $this->getPath());
        printf("STAGE = %s\n", $this->getStage());
        printf("VCS_SHA1 = %s\n", $this->getSha1());
        printf("LOG_LEVEL = %s\n", $this->getLevel());

        return PHP_EOL . Yaml::dump($this->conf->all(), 10, 2);
    }

    /**
     * @return \Microparts\Configuration\ConfigurationInterface
     */
    private function loadConf(): ConfigurationInterface
    {
        $conf = new Configuration($this->path, $this->stage);
        $conf->setLogger($this->logger);
        $conf->load();

        return $conf;
    }

    /**
     * @return mixed
     */
    private function findConfiguration()
    {
        if ($value = trim((string) getenv('CONFIG_PATH'))) {
            $this->possibleConfLocations[] = $value;
        }

        foreach ($this->possibleConfLocations as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new LogicException('Configuration of server not found in known path\'s');
    }
}
