<?php declare(strict_types=1);

namespace StaticServer\Handler;

use DOMElement;
use Masterminds\HTML5;
use Microparts\Configuration\ConfigurationInterface;
use SplFileInfo;
use StaticServer\Transfer;

final class InjectConfigFileToIndexHandler implements HandlerInterface
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
     * InjectConfigFileToIndexHandler constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;
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

        $script = $dom->createElement('script');
        $script->setAttribute('src', '/__config.js');
        $head = $dom->getElementsByTagName('head');

        // ignore injecting if <head> tag not found in the html file.
        if ($head->length < 1) {
            return $carry;
        }

        $first = null;
        foreach ($head->item(0)->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $first = $node;
                break;
            }
        }

        // if <head> contains child tags we will be inserted script before the first tag.
        if ($first !== null) {
            $first->parentNode->insertBefore($script, $first);
        } else {
            // otherwise <head> tag is empty
            $head->item(0)->appendChild($script);
        }

        $carry->setContent(
            $this->html5->saveHTML($dom)
        );

        return $carry;
    }
}
