<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use DOMElement, DOMNodeList;

/**
 * `XMLElement` is an enhanced version of PHP's `DOMElement` class.
 */
class XMLElement extends DOMElement
{
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
        $result = $this->ownerDocument->xPathQuery($path, $this);
        return $result ? $result[0] : null;
    }

    public function findAll(string $path): DOMNodeList|null
    {
        return $this->ownerDocument->xPathQuery($path, $this);
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
}
