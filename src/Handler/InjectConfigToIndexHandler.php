<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-16
 */

namespace StaticServer\Handler;

use Masterminds\HTML5;
use Microparts\Configuration\ConfigurationInterface;
use SplFileInfo;
use StaticServer\Transfer;

final class InjectConfigToIndexHandler implements HandlerInterface
{
    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var \Masterminds\HTML5
     */
    private $html5;

    /**
     * @var string
     */
    private $stage;

    /**
     * @var string
     */
    private $vcsSha1;

    /**
     * InjectConfigToIndexHandler constructor.
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
        $this->html5 = new HTML5();
    }

    /**
     * @param Transfer $carry
     * @param \SplFileInfo $item
     * @return Transfer
     */
    public function __invoke($carry, SplFileInfo $item): Transfer
    {
        if ($item->getFilename() !== $this->conf->get('server.index')) {
            return $carry;
        }

        $dom = $this->html5->loadHTML($carry->getContent());
        $script = $dom->createElement('script', $this->javascript());
        $meta = $dom->getElementsByTagName('meta');
        $meta->item(0)->appendChild($script);

        $carry->setContent(
            $this->html5->saveHTML($dom)
        );

        return $carry;
    }

    /**
     * @return string
     */
    private function javascript()
    {
        $template = <<<JS
    window.__stage = '%s';
    window.__config = JSON.parse('%s');
    window.__vcs = '%s';

    console.log('%s', 'color: #009688', 'color: #F44336');
    console.log('%s', 'color: #009688', 'color: #F44336');
JS;

        return sprintf(
            $template,
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
