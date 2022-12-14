<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

/**
 * The `JSON` class takes a JSON formatted string and converts it to XML.
 * The majority of this class was originally written by Brent Burgoyne, thank you.
 *
 * @since Symphony 2.3
 * @author Brent Burgoyne
 */
class JSON
{
    private static $dom;

    /**
     * Given a JSON formatted string, this function will convert it to an
     * equivalent XML version (either standalone or as a fragment). The JSON
     * will be added under a root node of `<data>`.
     *
     * @throws JSONException
     * @param string $json
     *  The JSON formatted class
     * @param boolean $standalone
     *  If passed true (which is the default), this parameter will cause
     *  the function to return the XML with an XML declaration, otherwise
     *  the XML will be returned as a fragment.
     * @return string
     *  Returns a XML string
     */
    public static function convertToXML($json, $standalone = true)
    {
        self::$dom = new DOMDocument('1.0', 'utf-8');
        self::$dom->formatOutput = true;

        // remove callback functions from JSONP
        if (preg_match('/(\{|\[).*(\}|\])/s', $json, $matches)) {
            $json = $matches[0];
        } else {
            throw new JSONException(__("JSON not formatted correctly"));
        }

        $data = json_decode($json);
        if (function_exists('json_last_error')) {
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new JSONException(__("JSON not formatted correctly"), json_last_error());
            }
        } elseif (!$data) {
            throw new JSONException(__("JSON not formatted correctly"));
        }

        $data_element = self::_process($data, self::$dom->createElement('data'));
        self::$dom->appendChild($data_element);

        if ($standalone) {
            return self::$dom->saveXML();
        } else {
            return self::$dom->saveXML(self::$dom->documentElement);
        }
    }

    /**
     * This function recursively iterates over `$data` and uses `self::$dom`
     * to create an XML structure that mirrors the JSON. The results are added
     * to `$element` and then returned. Any arrays that are encountered are added
     * to 'item' elements.
     *
     * @param mixed $data
     *  The initial call to this function will be of `stdClass` and directly
     *  from `json_decode`. Recursive calls after that may be of `stdClass`,
     *  `array` or `string` types.
     * @param DOMElement $element
     *  The `DOMElement` to append the data to. The root node is `<data>`.
     * @return DOMElement
     */
    private static function _process($data, DOMElement $element)
    {
        if (is_array($data)) {
            foreach ($data as $item) {
                $item_element = self::_process($item, self::$dom->createElement('item'));
                $element->appendChild($item_element);
            }
        } elseif (is_object($data)) {
            $vars = get_object_vars($data);
            foreach ($vars as $key => $value) {
                $key = self::_valid_element_name($key);

                $var_element = self::_process($value, $key);
                $element->appendChild($var_element);
            }
        } else {
            $element->appendChild(self::$dom->createTextNode($data));
        }

        return $element;
    }

    /**
     * This function takes a string and returns an empty DOMElement with a valid
     * name. If the passed `$name` is a valid QName, the handle of this name will
     * be the name of the element, otherwise this will fallback to 'key'.
     *
     * @see toolkit.Lang#createHandle
     * @param string $name
     *  If the `$name` is not a valid QName it will be ignored and replaced with
     *  `key`. If this happens, the `$name` will be run through `Lang::createHandle`
     *  to create a `@handle` attribute. Additionally, a `@value` attribute will be
     *  set with the sanitized original `$name`.
     *  If `$name` is a valid QName, it will be run through `Lang::createHandle`
     *  to create the element name. No attributes will be added to the element.
     * @return DOMElement
     *  An empty DOMElement, possibly with `@handle` and `@value` attributes.
     */
    private static function _valid_element_name(string $name)
    {
        $valid_name = preg_match('/^[\p{L}]([0-9\p{L}\.\-\_]+)?$/u', $name);

        if ($valid_name) {
            $xKey = self::$dom->createElement(Lang::createHandle($name));
        } else {
            $xKey = self::$dom->createElement('key');
            $xKey->setAttribute('handle', Lang::createHandle($name));
            $xKey->setAttribute('value', General::sanitize($name));
        }

        return $xKey;
    }
}
