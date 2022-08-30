<?php

/**
 * @package Core
 */

namespace Symnext\Core;

use Symnext\Toolkit\General;
use SimpleXMLIterator, XMLWriter;

 /**
  * The Configuration class acts as a property => value store for settings
  * used throughout Symphony. The result of this class is a string containing
  * a PHP representation of the properties (and their values) set by the Configuration.
  * Symphony's configuration file is saved at `CONFIG`. The initial
  * file is generated by the Symphony installer, and then subsequent use of Symphony
  * loads in this file for each page view. Like minded properties can be grouped.
  */
class Configuration
{
    /**
     * An associative array of the properties for this Configuration object
     * @var array
     */
    private $properties = [];

    /**
     * Whether all properties and group keys will be forced to be lowercase.
     * By default this is false, which makes all properties case sensitive
     * @var boolean
     */
    private $_forceLowerCase = false;

    /**
     * The constructor for the Configuration class takes one parameter,
     * `$forceLowerCase` which will make all property and
     * group names lowercase or not. By default they are left to the case
     * the user provides
     *
     * @param boolean $forceLowerCase
     *  False by default, if true this will make all property and group names
     *  lowercase
     */
    public function __construct($forceLowerCase = false)
    {
        $this->_forceLowerCase = $forceLowerCase;
    }

    /**
     * Setter for the `$this->properties`. The properties array
     * can be grouped to be an 'array' of an 'array' of properties. For instance
     * a 'region' key may be an array of 'properties' (that is name/value), or it
     * may be a 'value' itself.
     *
     * @param string $name
     *  The name of the property to set, eg 'timezone'
     * @param array|string|integer|float|boolean $value
     *  The value for the property to set, eg. '+10:00'
     * @param string $group
     *  The group for this property, eg. 'region'
     */
    public function set(string $name, $value, string $group = null): void
    {
        if ($this->_forceLowerCase) {
            $name = strtolower($name);
            $group = strtolower($group);
        }

        if ($group) {
            $this->properties[$group][$name] = $value;
        } else {
            $this->properties[$name] = $value;
        }
    }

    /**
     * A quick way to set a large number of properties. Given an associative
     * array or a nested associative array (where the key will be the group),
     * this function will merge the `$array` with the existing configuration.
     * By default the given `$array` will overwrite any existing keys unless
     * the `$overwrite` parameter is passed as false.
     *
     * @since Symphony 2.3.2 The `$overwrite` parameter is available
     * @param array $array
     *  An associative array of properties, 'property' => 'value' or
     *  'group' => array('property' => 'value')
     * @param boolean $overwrite
     *  An optional boolean parameter to indicate if it is safe to use array_merge
     *  or if the provided array should be integrated using the 'set()' method
     *  to avoid possible change collision. Defaults to false.
     */
    public function setArray(array $array, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->properties = array_merge($this->properties, $array);
        } else {
            foreach ($array as $set => $values) {
                foreach ($values as $key => $val) {
                    self::set($key, $val, $set);
                }
            }
        }
    }

    /**
     * Accessor function for the `$this->properties`.
     *
     * @param string $name
     *  The name of the property to retrieve
     * @param string $group
     *  The group that this property will be in
     * @return array|string|integer|float|boolean
     *  If `$name` or `$group` are not
     *  provided this function will return the full `$this->properties`
     *  array.
     */
    public function get(string $name = null, string $group = null)
    {

        // Return the whole array if no name or index is requested
        if (!$name && !$group) {
            return $this->properties;
        }

        if ($this->_forceLowerCase) {
            $name = strtolower($name);
            $group = strtolower($group);
        }

        if ($group) {
            return $this->properties[$group][$name] ?? null;
        }

        return $this->properties[$name] ?? null;
    }

    /**
     * The remove function will unset a property by `$name`.
     * It is possible to remove an entire 'group' by passing the group
     * name as the `$name`
     *
     * @param string $name
     *  The name of the property to unset. This can also be the group name
     * @param string $group
     *  The group of the property to unset
     */
    public function remove(string $name, string $group = null): void
    {
        if ($this->_forceLowerCase) {
            $name = strtolower($name);
            $group = strtolower($group);
        }

        if ($group && isset($this->properties[$group][$name])) {
            unset($this->properties[$group][$name]);
        } elseif ($this->properties[$name]) {
            unset($this->properties[$name]);
        }
    }

    /**
     * Empties all the Configuration values by setting `$this->properties`
     * to an empty array
     */
    public function flush()
    {
        $this->properties = [];
    }

    /**
     * This magic `__toString` function converts the internal
     * `$this->properties` array into a string representation. Symphony
     * generates the `MANIFEST/config.php` file in this manner.
     * @see ArraySerializer::asPHPFile()
     * @return string
     *  A string that contains a array representation of `$this->properties`.
     *  This is used by Symphony to write the `config.php` file.
     */
    public function __toString()
    {
        return (new ArraySerializer($this->properties))->asPHPFile();
    }

    public function load(string $file = null)
    {
        $file = $file ?? \CONFIG;

        $sxi = new SimpleXmlIterator($file, null, true);
        $this->properties = $this->sxiToArray($sxi);
    }

    protected function sxiToArray($sxi)
    {
        $array = [];
        for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {
            /*if (!array_key_exists($sxi->key(), $array)) {
                $array[$sxi->key()] = [];
            }*/
            if ($sxi->hasChildren()) {
                $array[$sxi->key()] = $this->sxiToArray($sxi->current());
            } else {
                $array[$sxi->key()] = strval($sxi->current());
            }
        }
        return $array;
    }

    /**
     * Function will write the current Configuration object to
     * a specified `$file` with the given `$permissions`.
     *
     * @param string $file
     *  the path of the file to write.
     * @param integer|null $permissions (optional)
     *  the permissions as an octal number to set set on the resulting file.
     *  If this is not provided it will use the permissions defined in [`write_mode`][`file`]
     * @return boolean
     */
    public function write(string $file = null, int $permissions = null): bool
    {
        if (is_null($permissions) && isset($this->properties['file']['write_mode'])) {
            $permissions = $this->properties['file']['write_mode'];
        }

        $file = $file ?? CONFIG;

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString('   ');
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('configuration');
        $writer->text(PHP_EOL);
        foreach ($this->properties as $name => $value) {
            $writer->text(PHP_EOL);
            $writer->writeComment(
                ' ' . str_replace('_', ' ', strtoupper($name)) . ' '
            );
            $writer->startElement($name);
            if (is_array($value)) {
                $this->arrayToXml($writer, $value);
            } else {
                $writer->text($value);
            }
            $writer->endElement();
        }
        $writer->text(PHP_EOL);
        $writer->endElement();
        $writer->endDocument();
        #echo $writer->outputMemory();
        return General::writeFile($file, $writer->outputMemory(), $permissions);
    }

    private function arrayToXml(XMLWriter $writer, array $array)
    {
        foreach ($array as $name => $value) {
            $writer->startElement($name);
            if (is_array($value)) {
                $this->arrayToXml($writer, $value);
            } else {
                $writer->text($value);
            }
            $writer->endElement();
        }
    }

    /*
     * Substitute config values into text.
     */
    public function configSub(string $string): string
    {
        return preg_replace_callback(
            '/(\$\{(?<config1>.\w+)\.(?<config2>\w+)\})|(\{(?<const>.+?)\})/',
            function (?array $match): string|null
            {
                if (!empty($match['config1'])) {
                    return $this->get($match['config2'], $match['config1']);
                } else {
                    $constants = \get_defined_constants(true)['user'];
                    return $constants[$match['const']] ?? null;
                }
            },
            $string
        );
    }
}
