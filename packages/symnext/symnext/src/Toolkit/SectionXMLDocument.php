<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use Symnext\Core\App;
use Symnext\Toolkit\XMLElement;

/**
 * `XMLElement` is an enhanced version of PHP's `SimpleXMLElement` class.
 */
class SectionXMLDocument extends \DOMDocument
{

    protected $settings;

    protected $fields;

    protected $field_handles = [];

    protected $has_errors = false;

    public function __construct()
    {
        parent::__construct();

        $this->formatOutput = true;
        $this->registerNodeClass('DOMElement', 'Symnext\Toolkit\XMLElement');
        $this->appendChild($this->createElement('section'));
        $this->documentElement->appendChild(
            $this->createComment(
                " Values may be edited but do not edit the attributes. "
            )
        );
        $this->settings = $this->documentElement->appendElement('meta');
        $this->fields = $this->documentElement->appendElement('fields');
    }

    public function setValues(array $values)
    {
        $name = trim($values['name'] ?? '');
        $current_handle = trim($values['current_handle'] ?? '');
        $handle = trim($values['handle'] ?? '');
        $errors = [];
        $this->settings->appendElement('name', $name);
        if (!empty($name)) {
            $handle = $handle ?? Lang::createHandle($name);
        } else {
            $errors['name'] = __('This is a required field');
        }

        $this->documentElement->setAttribute('handle', $handle);
        if (isset($handle)) {
            if ($handle !== $current_handle) {
                if (SectionManager::sectionExists($handle)) {
                    $errors['handle'] = 'Handle already exists';
                }
            }
        }

        if (!empty($errors)) {
            #var_dump($errors);die;
            $this->addErrorsToXML($this->settings, $errors);
        }
    }

    public function fieldExists(string $handle)
    {
        return boolval($this->fields->find("field[@handle=$handle]"));
    }

    public function addField(array &$values): void
    {
        if (empty($values)) die("No values for field.");
        $class_name = $values['class'] ?? null;
        if (!class_exists($class_name)) {
            die("No such field class as <code>$class_name</code>.");
        }
        $errors = [];
        $name = trim($values['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('This is a required field');
        }
        $handle = trim($values['handle'] ?? '');
        if (empty($handle)) {
            if (isset($name)) {
                $handle = Lang::createHandle($name);
            } else {
                $errors['handle'] = 'Required field';
            }
        }
        $values['handle'] = $handle;

        if (isset($handle)) {
            if (in_array($handle, $this->field_handles)) {
                $errors['handle'] = 'Handle already used';
            }
            $this->field_handles[] = $handle;
        }

        $x_field = $this->fields->appendElement(
            'field', null, ['class' => $class_name, 'handle' => $handle]
        );
        $x_field->appendElement('name', $name);
        $location = in_array($values['location'] ?? '', ['main', 'sidebar'])
            ? $values['location'] : $class_name::DEFAULT_LOCATION;
        $x_field->appendElement('location', $location);

        $class_name::addValuesToXMLDoc($x_field, $values);
        if (!empty($errors)) {
            $this->addErrorsToXML($x_field, $errors);
        }
    }

    protected function addErrorsToXML(\DOMElement $x_parent, array $errors)
    {
        if (!empty($errors)) {
            $x_errors = $x_parent->appendElement('errors');
            foreach ($errors as $key => $message) {
                $x_errors->appendElement($key, $message);
            }
            $this->has_errors = true;
        }
    }

    public function hasErrors(): bool
    {
        return $this->has_errors;
    }
}
/*
        if (!empty($values)) {
            $settings = array_merge(
                $settings, array_intersect_key($settings, $values)
            );
        }
*/
