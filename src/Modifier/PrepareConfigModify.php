<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use StaticServer\Transfer;

final class PrepareConfigModify implements ModifyInterface, ConfigurationAwareInterface
{
    use ConfigurationAwareTrait;

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
     * @param string $stage
     * @param string $vcsSha1
     */
    public function __construct(string $stage, string $vcsSha1 = '')
    {
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
        $format = $this->configuration->get('server.log_info');
        $message = sprintf($format, $this->stage, $this->vcsSha1);

        return sprintf(
            trim($transfer->getContent()),
            $this->stage,
            json_encode($this->cleanupServerKeyFromConfig()),
            $this->vcsSha1,
            $message
        );
    }

    /**
     * Removed server key from config for security reasons.
     *
     * @return array
     */
    private function cleanupServerKeyFromConfig(): array
    {
        $array = $this->configuration->all();
        unset($array['server']);

        return $array;
    }
}
