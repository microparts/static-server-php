<?php declare(strict_types=1);

namespace StaticServer;

use Microparts\Configuration\Configuration;

final class Server
{
    /**
     * @var \StaticServer\Application
     */
    private ?Application $app;

    /**
     * Server constructor.
     *
     * @param \StaticServer\Application $app
     */
    public function __construct(?Application $app = null)
    {
        $this->app = $app;
    }

    /**
     * @return \StaticServer\Server
     */
    public static function fromGlobals(): self
    {
        $stage = getenv('STAGE') ?: 'defaults';
        $sha1  = getenv('VCS_SHA1') ?: '';

        return new Server(new Application($stage, $sha1));
    }

    /**
     * Start server.
     *
     * @param bool $dryRun
     * @return void
     * @throws \Throwable
     */
    public function run(bool $dryRun = false): void
    {
        $dryRun ? $this->app->dryRun() : $this->app->run();
    }

    public function stop(): void
    {
        $this->app->stop();
    }

    /**
     * @throws \Throwable
     */
    public function reload(): void
    {
        $this->app->reload();
    }

    /**
     * For debug only.
     *
     * @return void
     */
    public function dump(): void
    {
        $conf = Configuration::auto();
        $conf->load();

        printf("CONFIG_PATH = %s\n", $conf->getPath());
        printf("STAGE = %s\n", $conf->getStage());

        printf('%s', $conf->dump());
    }
}
