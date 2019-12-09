<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class DefaultPreferencesConfigurator implements ModifyConfiguratorInterface, ConfigurationAwareInterface, LoggerAwareInterface
{
    use ConfigurationAwareTrait, LoggerAwareTrait;

    /**
     * @var string|null
     */
    private string $stage = '';

    /**
     * @var string
     */
    private string $sha1 = '';

    /**
     * DefaultPreferencesConfigurator constructor.
     *
     * @param string|null $stage
     * @param string|null $sha1
     */
    public function __construct(string $stage = '', string $sha1 = '')
    {
        $this->stage = $stage;
        $this->sha1 = $sha1;
    }

    /**
     * @inheritDoc
     */
    public function getModifier(): GenericModifyInterface
    {
        if (!$this->configuration->get('server.modify.enabled')) {
            $this->logger->info('Files modification disabled. To use it again, enable the [server.modify.enabled] config opt.');

            return new NullGenericModify();
        }

        $this->logger->info('Files modification enabled. Have fun!.');

        $mod = new Modify();
        $location = $this->getConfigName('/__config.js');

        $mod->addTemplate(__DIR__ . '/../stub/__config.js', $location);
        $mod->addTemplate(__DIR__ . '/../stub/security.txt', '/.well-known/security.txt');
        $mod->addModifier(new PrepareConfigModify($this->stage, $this->sha1));
        $mod->addModifier(new SecurityTxtModify());
        $mod->addModifier(new InjectConfigFileToIndexModify($location));

        return $mod;
    }

    /**
     * @param string $location
     *
     * @return string
     */
    private function getConfigName(string $location): string
    {
        if (strlen($this->sha1) < 1) {
            return $location;
        }

        $file = pathinfo($location);

        return sprintf(
            '%s%s_%s.%s',
            $file['dirname'],
            $file['filename'],
            $this->sha1,
            $file['extension']
        );
    }
}
