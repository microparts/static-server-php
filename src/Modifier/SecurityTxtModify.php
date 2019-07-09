<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use Microparts\Configuration\ConfigurationInterface;
use StaticServer\Transfer;

final class SecurityTxtModify implements ModifyInterface
{
    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * SecurityTxtModify constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Updates this file, where $changed object may be contains changes
     * from previous Modifier and where $origin object contains first
     * state of original file.
     *
     * Prepares security.txt file from stub.
     *
     * @param Transfer $changed
     * @param Transfer $origin
     *
     * @return Transfer
     */
    public function __invoke(Transfer $changed, Transfer $origin): Transfer
    {
        if ($origin->getFilename() !== 'security.txt') {
            return $changed;
        }

        $changed->setContent(sprintf(
            trim($changed->getContent()),
            $this->conf->get('server.security_txt.contact'),
            $this->conf->get('server.security_txt.preferred_lang'),
        ));

        return $changed;
    }
}
