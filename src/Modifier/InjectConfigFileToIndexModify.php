<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use Masterminds\HTML5;
use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use StaticServer\Modifier\Iterator\Transfer;

final class InjectConfigFileToIndexModify implements ModifyInterface, ConfigurationAwareInterface
{
    use ConfigurationAwareTrait;

    /**
     * Injects the __config.js to top of <head> tag.
     * It will be block content rendering, so not recommended.
     */
    private const INJECT_TO_HEAD = 'head';

    /**
     * Injects the __config.js before first <script> tag in DOM document.
     * Better than `head` variant.
     */
    private const INJECT_BEFORE_SCRIPT = 'before_script';

    /**
     * Server location of __config.js file.
     *
     * @var string
     */
    private string $location;

    /**
     * Utility to parse and modify html code.
     *
     * @var \Masterminds\HTML5
     */
    private HTML5 $html5;

    /**
     * InjectConfigFileToIndexModify constructor.
     *
     * @param string $location
     */
    public function __construct(string $location = '/__config.js')
    {
        $this->location = $location;
        $this->html5    = new HTML5();
    }

    /**
     * If index.html (server.index) will be found from array of files,
     * it parse DOM document with two strategies: `head` and `before_script`.
     * See constants to learn more.
     *
     * @param \StaticServer\Modifier\Iterator\Transfer $changed
     * @param \StaticServer\Modifier\Iterator\Transfer $origin
     * @return \StaticServer\Modifier\Iterator\Transfer
     */
    public function __invoke(Transfer $changed, Transfer $origin): Transfer
    {
        if ($changed->filename !== $this->configuration->get('server.index')) {
            return $changed;
        }

        $dom = $this->html5->loadHTML($changed->content);
        $script = $dom->createElement('script');
        $script->setAttribute('src', $this->location);

        if ($this->configuration->get('server.modify.inject') === self::INJECT_TO_HEAD) {
            return $this->toTopOfHead($dom, $script, $changed);
        }

        if ($this->configuration->get('server.modify.inject') === self::INJECT_BEFORE_SCRIPT) {
            return $this->beforeFirstScript($dom, $script, $changed);
        }

        throw new InvalidArgumentException('For config.inject possible two values [head] and [before_script], please choose one.');
    }

    /**
     * Updates this file, where $changed object may be contains changes
     * from previous Modifier and where $origin object contains first
     * state of original file.
     * Injects the __config.js to top of <head> tag.
     * If <head> tag not found injecting will be skipped.
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $script
     * @param \StaticServer\Modifier\Iterator\Transfer $changed
     * @return \StaticServer\Modifier\Iterator\Transfer
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

        $changed->content = $this->html5->saveHTML($dom);

        return $changed;
    }

    /**
     * Injects the __config.js before first <script> tag in DOM document.
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $script
     * @param \StaticServer\Modifier\Iterator\Transfer $changed
     * @return \StaticServer\Modifier\Iterator\Transfer
     */
    private function beforeFirstScript(DOMDocument $dom, DOMElement $script, Transfer $changed): Transfer
    {
        $this->configPreload($dom);

        $scripts = $dom->getElementsByTagName('script');

        // If can't found any <script> tag, we will skip injecting.
        if ($scripts->length < 1) {
            return $changed;
        }

        $first = $scripts->item(0);
        $first->parentNode->insertBefore($script, $first);

        $changed->content = $this->html5->saveHTML($dom);

        return $changed;
    }

    /**
     * Preloading __config.js
     *
     * https://developers.google.com/web/tools/lighthouse/audits/preload
     *
     * @param \DOMDocument $dom
     *
     * @return void
     */
    private function configPreload(DOMDocument $dom): void
    {
        $preload = $dom->createElement('link');
        $preload->setAttribute('rel', 'preload');
        $preload->setAttribute('href', $this->location);
        $preload->setAttribute('as', 'script');

        $link = $dom->getElementsByTagName('link');

        if ($link->length > 0) {
            $link->item(0)->parentNode->insertBefore($preload, $link->item(0));
            return;
        }

        $head = $dom->getElementsByTagName('head');

        if ($head->length > 0) {
            $head->item(0)->appendChild($preload);
            return;
        }
    }
}
