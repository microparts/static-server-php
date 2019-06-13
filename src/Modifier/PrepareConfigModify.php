<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use Microparts\Configuration\ConfigurationInterface;
use SplFileInfo;
use StaticServer\Transfer;

final class PrepareConfigModify implements ModifyInterface
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
     * PrepareConfigModify constructor.
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
     * @param \StaticServer\Transfer $changed
     * @param \StaticServer\Transfer $origin
     * @return \StaticServer\Transfer
     */
    public function __invoke(Transfer $changed, Transfer $origin): Transfer
    {
        if ($origin->getFilename() !== '__config.js') {
            return $changed;
        }

        $changed->setContent(
            $this->prepare($changed)
        );

        return $changed;
    }

    /**
     * @param \StaticServer\Transfer $transfer
     * @return string
     */
    private function prepare(Transfer $transfer)
    {
        $information = sprintf(
            $this->conf->get('server.log_info'),
            $this->stage,
            $this->vcsSha1
        );

        return sprintf(
            trim($transfer->getContent()),
            $this->stage,
            json_encode($this->cleanupServerKeyFromConfig()),
            $this->vcsSha1,
            $information
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
