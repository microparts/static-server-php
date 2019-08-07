<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use StaticServer\Transfer;

final class SecurityTxtModify implements ModifyInterface, ConfigurationAwareInterface
{
    use ConfigurationAwareTrait;

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
            $this->configuration->get('server.security_txt.contact'),
            $this->configuration->get('server.security_txt.preferred_lang'),
        ));

        return $changed;
    }
}
