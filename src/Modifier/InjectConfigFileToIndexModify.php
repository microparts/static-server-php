<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use InvalidArgumentException;
use Masterminds\HTML5;
use Microparts\Configuration\ConfigurationInterface;
use StaticServer\Transfer;

final class InjectConfigFileToIndexModify implements ModifyInterface
{
    private const INJECT_TO_HEAD = 'head';
    private const INJECT_BEFORE_SCRIPT = 'before_script';

    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var string
     */
    private $location;

    /**
     * @var \Masterminds\HTML5
     */
    private $html5;

    /**
     * InjectConfigFileToIndexModify constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @param string $location
     */
    public function __construct(ConfigurationInterface $conf, string $location = '/__config.js')
    {
        $this->conf     = $conf;
        $this->location = $location;
        $this->html5    = new HTML5();
    }

    /**
     * @param \StaticServer\Transfer $changed
     * @param \StaticServer\Transfer $origin
     * @return \StaticServer\Transfer
     */
    public function __invoke(Transfer $changed, Transfer $origin): Transfer
    {
        if ($changed->getFilename() !== $this->conf->get('server.index')) {
            return $changed;
        }

        $dom = $this->html5->loadHTML($changed->getContent());
        $script = $dom->createElement('script');
        $script->setAttribute('src', $this->location);

        if ($this->conf->get('server.config.inject') === self::INJECT_TO_HEAD) {
            return $this->toTopOfHead($dom, $script, $changed);
        }

        if ($this->conf->get('server.config.inject') === self::INJECT_BEFORE_SCRIPT) {
            return $this->beforeFirstScript($dom, $script, $changed);
        }

        throw new InvalidArgumentException('Config.inject must be [head] or [before_script] value.');
    }

    /**
     * @param \DOMDocument $dom
     * @param \DOMElement $script
     * @param \StaticServer\Transfer $changed
     * @return \StaticServer\Transfer
     */
    private function toTopOfHead(DOMDocument $dom, DOMElement $script, Transfer $changed): Transfer
    {
        $head = $dom->getElementsByTagName('head');

        // ignore injecting if <head> tag not found in the html file.
        if ($head->length < 1) {
            return $changed;
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

        $changed->setContent(
            $this->html5->saveHTML($dom)
        );

        return $changed;
    }

    /**
     * @param \DOMDocument $dom
     * @param \DOMElement $script
     * @param \StaticServer\Transfer $changed
     * @return \StaticServer\Transfer
     */
    private function beforeFirstScript(DOMDocument $dom, DOMElement $script, Transfer $changed): Transfer
    {
        $body = $dom->getElementsByTagName('body');

        if ($body->length < 1) {
            return $changed;
        }

        $first = $this->findScriptTagInNestedNodes($body->item(0)->childNodes);

        if ($first !== null) {
            $first->parentNode->insertBefore($script, $first);
        } else {
            return $changed;
        }

        $changed->setContent(
            $this->html5->saveHTML($dom)
        );

        return $changed;
    }

    /**
     * @param \DOMNodeList $list
     * @return \DOMElement|null
     */
    private function findScriptTagInNestedNodes(DOMNodeList $list)
    {
        foreach ($list as $node) {
            if ($node->nodeName === 'script') {
                return $node;
            } elseif ($node->childNodes !== null && $node->childNodes->length > 0) {
                return $this->findScriptTagInNestedNodes($node->childNodes);
            }
        }

        return null;
    }
}
