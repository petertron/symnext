<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use Symnext\Core\App;
use Symnext\Toolkit\XMLElement;
use DOMDocument, DOMXPath;

/**
 * `XMLElement` is an enhanced version of PHP's `SimpleXMLElement` class.
 */
class XMLDocument extends DOMDocument
{

    protected $xpath;

    public function __construct()
    {
        parent::__construct();
        $this->registerNodeClass('\DOMElement', 'Symnext\Toolkit\XMLElement');
    }

    public function load(string $file, int $options = 0)
    {
        $this->loadXML(\file_get_contents($file));
    }

    public function loadXML(string $xml, int $options = 0)
    {
        $xml = self::subs($xml);
        parent::loadXML($xml);
    }

    public function xPathQuery(string $path, XMLElement $ref_node = null)
    {
        if (!$this->xpath) {
            $this->xpath = new DOMXPath($this);
        }
        return $this->xpath->query($path, $ref_node);
    }

    public function find(string $path): XMLElement|null
    {
        $result = $this->findAll($path)[0] ?? null;
        return $result;
    }

    public function findAll(string $path): \DOMNodeList|null
    {
        $xpath = new \DOMXPath($this);
        return $xpath->query($path) ?? null;
    }

    /**
     * This function strips characters that are not allowed in XML
     *
     * @since Symphony 2.3
     * @link http://www.w3.org/TR/xml/#charsets
     * @link http://www.phpedit.net/snippet/Remove-Invalid-XML-Characters
     * @param string $value
     * @return string
     */
    public static function stripInvalidXMLCharacters($value)
    {
        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $value);
    }

    public static function subs(string $xml)
    {
        if (empty($xml)) {
            return;
        }
        $xml = preg_replace_callback(
            '/(\{\$(?<config1>.+?)\.(?<config2>.*?)\$\})|(\{%(?<const>[\w\\\\]+?)%\})/',
            function ($match) {
                #print_r($match);
                if (!empty($match['config1'])) {
                    return App::Configuration()->get($match['config2'], $match['config1']);
                } else {
                    $constants = \get_defined_constants(true)['user'];
                    return $constants[$match['const']] ?? null;
                }
            },
            $xml
        );
        return $xml;
    }
}
