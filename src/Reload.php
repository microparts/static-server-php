<?php declare(strict_types=1);

namespace StaticServer;

use LogicException;
use Microparts\Configuration\ConfigurationInterface;

class Reload
{
    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * Reload constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Reload server without restart main process.
     *
     * @return int
     */
    public function send(): int
    {
        $save = $this->conf->get('server.pid.save');
        $location = $this->conf->get('server.pid.location');

        if (!$save) {
            throw new LogicException('Before reload the server, please enable PID-file saving to disk. $STAGE is correct?');
        }

        if (!file_exists($location)) {
            throw new LogicException('PID-file not found, please enable it saving to disk. $STAGE is correct?');
        }

        $pid = (int) file_get_contents($location);

        $code = 1;
        printf('%s', system("kill -USR1 {$pid}", $code));

        return $code;
    }
}
