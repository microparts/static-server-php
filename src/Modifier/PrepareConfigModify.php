<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use Microparts\Configuration\ConfigurationInterface;
use StaticServer\Transfer;

final class PrepareConfigModify implements ModifyInterface
{
    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * Application stage. Like dev/prod/local/what-else.
     *
     * @var string
     */
    private $stage;

    /**
     * Application SHA1 from git.
     *
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
    public function __construct(ConfigurationInterface $conf, string $stage, string $vcsSha1 = '')
    {
        $this->conf    = $conf;
        $this->stage   = $stage;
        $this->vcsSha1 = $vcsSha1;
    }

    /**
     * Updates this file, where $changed object may be contains changes
     * from previous Modifier and where $origin object contains first
     * state of original file.
     *
     * Prepares __config.js file from stub for inject it to server.index file in future.
     *
     * @param Transfer $changed
     * @param Transfer $origin
     *
     * @return Transfer
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
     * Prepares __config.js file... I like writing documentation [sad smile].
     *
     * @param \StaticServer\Transfer $transfer
     *
     * @return string
     */
    private function prepare(Transfer $transfer): string
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
     * Removed server key from config for security reasons.
     *
     * @return array
     */
    private function cleanupServerKeyFromConfig(): array
    {
        $array = $this->conf->all();
        unset($array['server']);

        return $array;
    }
}
