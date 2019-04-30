<?php declare(strict_types=1);

namespace StaticServer\Handler;

use Microparts\Configuration\ConfigurationInterface;
use SplFileInfo;
use StaticServer\Transfer;

final class PrepareConfigHandler implements HandlerInterface
{
    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var string
     */
    private $stage;

    /**
     * @var string
     */
    private $vcsSha1;

    /**
     * PrepareConfigHandler constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @param string $stage
     * @param string $vcsSha1
     */
    public function __construct(ConfigurationInterface $conf, string $stage, string $vcsSha1)
    {
        $this->conf = $conf;
        $this->stage = $stage;
        $this->vcsSha1 = $vcsSha1;
    }

    /**
     * @param Transfer $carry
     * @param \SplFileInfo $item
     * @return Transfer
     */
    public function __invoke($carry, SplFileInfo $item): Transfer
    {
        if ($item->getFilename() !== '__config.js') {
            return $carry;
        }

        $location = realpath($this->conf->get('server.root')) . '/__config.js';
        $carry->setRealpath($location);

        $carry->setContent(
            $this->prepare($carry)
        );

        return $carry;
    }

    /**
     * @param \StaticServer\Transfer $transfer
     * @return string
     */
    private function prepare(Transfer $transfer)
    {
        return sprintf(
            $transfer->getContent(),
            $this->stage,
            json_encode($this->cleanupServerKeyFromConfig()),
            $this->vcsSha1,
            $this->conf->get('server.log_info.security'),
            $this->conf->get('server.log_info.job')
        );
    }

    /**
     * @return array
     */
    private function cleanupServerKeyFromConfig(): array
    {
        $array = $this->conf->all();
        unset($array['server']);

        return $array;
    }
}
