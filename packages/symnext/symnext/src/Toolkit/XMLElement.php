<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use DOMDocument, DOMElement, DOMNodeList, ArrayAccess;

/**
 * `XMLElement` is an enhanced version of PHP's `DOMElement` class.
 */
class XMLElement extends DOMElement implements ArrayAccess
{
    /**
     * An accessor function for this Section's settings. If the
     * $key param is omitted, an array of all settings will
     * be returned. Otherwise it will return the data for
     * the setting given.
     *
     * @param null|string $key
     * @return array|string
     *    If setting is provided, returns a string, if setting is omitted
     *    returns an associative array of this Section's settings
     */
    public function offsetGet($offset)
    {
        foreach ($this->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) continue;
            if ($node->nodeName == $offset) {
                return $node;
            }
        }
    }

    /**
     * A setter function that will save a section's setting into
     * the poorly named `$this->settings` variable
     *
     * @param string $key
     *  The setting name
     * @param string $value
     *  The setting value
     */
    public function offsetSet($name, $value)
    {
        if (is_null($this[$name])) {
            $element = $this->appendElement($name, $value);
        } else {
            $this[$name]->nodeValue = $value;
        }
    }

    /*public function offsetSet($name, $value)
    {
        if (!isset($this->child_elements[$name])) {
            $element = $this->appendElement($name, $value);
            $this->child_elements[$name] = $element;
        } else {
            $this->child_elements[$name]->nodeValue = $value;
        }
    }*/

    public function offsetUnset($offset)
    {
        if (isset($this->child_elements[$offset])) {
            unset($this->child_elements[$offset]);
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->child_elements[$offset]);
    }

    /**
     * Append XML element.
     */
    public function appendElement(
        string $name,
        string $value = null,
        array $attributes = null
    ): self
    {
        $doc = $this->ownerDocument;
        $name_split = explode(':', $name);
        $prefix = null;
        $namespace = null;
        if (isset($name_split[1])) {
            $prefix = $name_split[0];
            $name = $name_split[1];
        }
        if ($prefix) {
            $namespace = $doc->documentElement->getAttribute("xmlns:$prefix") ?? null;
        }
        $element = $doc->createElementNS($namespace, $name, $value);
        if ($attributes) {
            $element->setAttributes($attributes);
        }
        $this->appendChild($element);
        $this->child_elements[$name] = $element;
        return $element;
    }

    /**
     * A convenience method to add multiple attributes.
     *
     * @param array $attributes
     *  Associative array with the key being the name and
     *  the value being the value of the attribute.
     * @return XMLElement
     *  The current instance
     */
    public function setAttributes(array $attributes)
    {
        if (empty($attributes)) return;
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Return an associative array of attribute names and values.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $return = [];
        foreach ($this->attributes as $attribute) {
            $return[$attribute->name] = strval($attribute->value);
        }
        return $return;
    }

    public function appendElementList(string $element_names): self
    {
        foreach (str_getcsv($element_names) as $name) {
            $this->appendElement($name);
        }
        return $this;
    }

    public function find(string $path): XMLElement|null
    {
        $result = $this->findAll($path);
        return $result[0] ?? null;
    }

    public function findAll(string $path): DOMNodeList|null
    {
        if (!isset($this->xpath)) {
            $this->xpath = new \DOMXPath($this->ownerDocument);
        }
        return $this->xpath->query($path, $this);
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

    /**
     * Return a new DOMDocument.
     */
    public static function newDocument(string $doc_class = null): DOMDocument
    {
        $doc_class = $doc_class ?? 'DOMDocument';
        $doc = new $doc_class;
        $doc->registerNodeClass('DOMElement', 'Symnext\Toolkit\XMLElement');
        return $doc;
    }
}
