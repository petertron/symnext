<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use Symnext\Core\App;
use Symnext\Core\DateTimeObj;
use Symnext\Toolkit\XMLDocument;
use ArrayAccess, JsonSerializable;

/**
 * The Section class represents a Symphony Section object. A section is a model
 * of a data structure using one or more Fields. Sections are stored in the database
 * and are used as repositories for Entry objects, which are a model for this data
 * structure. This class contains functions for finding Fields within a Section and
 * saving a Section's settings.
 *
 * @since Symphony 3.0.0 it implements the ArrayAccess interface.
 */
class Section implements ArrayAccess, JsonSerializable
{
    use Settings;

    /**
     * An array of the Section's settings
     * @var array
     */
    protected array $settings = [
        'id' => [
            'type' => 'string',
        ],
        'name' => [
            'type' => 'string',
        ],
        'handle' =>  [
            'type' => 'string',
        ],
        'nav_group' =>  [
            'type' => 'string',
            'default' => 'content',
        ],
        'hidden' => [
            'type' => 'string',
            'choice' => ['yes', 'no'],
            'default' => 'no'
        ],
        'filter' => [
            'type' => 'string',
            'choice' => ['yes', 'no'],
            'default' => 'no'
        ],
    ];

    /**
     * Array of field objects.
     */
    protected $fields = [];

    protected $has_errors = false;

    /**
     * Returns the default field this Section will be sorted by. This is
     * determined by the first visible field that is allowed to be sorted
     * (defined by the field's `isSortable()` function).
     * If no fields exist or none of them are visible in the entries table,
     * 'system:id' is returned instead.
     *
     * @since Symphony 2.3
     * @since Symphony 3.0.0
     *  Returns 'system:id' instead of 'id'
     * @throws Exception
     * @return string
     *  Either the field ID or the string 'system:id'.
     */
    public function getDefaultSortingField()
    {
        $fields = $this->fetchVisibleColumns();

        foreach ($fields as $field) {
            if (!$field->isSortable()) {
                continue;
            }

            return (string)$field->get('id');
        }

        return 'system:id';
    }

    /**
     * Returns the field this Section will be sorted by, or calls
     * `getDefaultSortingField()` if the configuration file doesn't
     * contain any settings for that Section.
     *
     * @since Symphony 2.3
     * @throws Exception
     * @return string
     *  Either the field ID or the string 'id'.
     */
    public function getSortingField()
    {
        $result = null;
        /**
         * Just prior to getting the configured sorting field.
         *
         * @delegate SectionGetSortingField
         * @since Symphony 3.0.0
         * @param string $context
         * '/publish/'
         * @param string $section-handle
         *  The handle of the current section
         * @param string &$field
         *  The field as set by extensions
         */
        App::ExtensionManager()->notifyMembers(
            'SectionGetSortingField', '/publish/', [
            'section-handle' => $this->get('handle'),
            'field' => &$result,
        ]);

        if (!$result) {
            $result = App::Configuration()->get('section_' . $this->get('handle') . '_sortby', 'sorting');
        }

        return (!$result ? $this->getDefaultSortingField() : (string)$result);
    }

    /**
     * Returns the sort order for this Section. Defaults to 'asc'.
     *
     * @since Symphony 2.3
     * @return string
     *  Either 'asc' or 'desc'.
     */
    public function getSortingOrder()
    {
        $result = null;
        /**
         * Just prior to getting the configured sorting order.
         *
         * @delegate SectionGetSortingOrder
         * @since Symphony 3.0.0
         * @param string $context
         * '/publish/'
         * @param string $section-handle
         *  The handle of the current section
         * @param string &$order
         *  The order as set by extensions
         */
        App::ExtensionManager()->notifyMembers('SectionGetSortingOrder', '/publish/', [
            'section-handle' => $this->get('handle'),
            'order' => &$result
        ]);

        if (!$result) {
            $result = App::Configuration()->get('section_' . $this->get('handle') . '_order', 'sorting');
        }

        return (!$result ? 'asc' : (string)$result);
    }

    /**
     * Saves the new field this Section will be sorted by.
     *
     * @since Symphony 2.3
     * @param string $sort
     *  The field ID or the string 'id'.
     * @param boolean $write
     *  If false, the new settings won't be written on the configuration file.
     *  Defaults to true.
     */
    public function setSortingField($sort, $write = true)
    {
        $updated = false;
        /**
         * Just prior to setting the configured sorting field.
         *
         * @delegate SectionSetSortingField
         * @since Symphony 3.0.0
         * @param string $context
         * '/publish/'
         * @param string $section-handle
         *  The handle of the current section
         * @param string $field
         *  The field as passed to the setSortingField function
         * @param boolean $updated
         *  The updated flag, set by extensions, which prevents the saving of the value
         */
        App::ExtensionManager()->notifyMembers('SectionSetSortingField', '/publish/', [
            'section-handle' => $this->get('handle'),
            'field' => $sort,
            'updated' => &$updated,
        ]);

        // The delegate handled the request, don't set the default.
        if (!$updated) {
            App::Configuration()->set('section_' . $this->get('handle') . '_sortby', $sort, 'sorting');

            if ($write) {
                App::Configuration()->write();
            }
        }
    }

    /**
     * Saves the new sort order for this Section.
     *
     * @since Symphony 2.3
     * @param string $order
     *  Either 'asc' or 'desc'.
     * @param boolean $write
     *  If false, the new settings won't be written on the configuration file.
     *  Defaults to true.
     */
    public function setSortingOrder($order, $write = true)
    {
        $updated = false;
        /**
         * Just prior to setting the configured sorting order.
         *
         * @delegate SectionSetSortingOrder
         * @since Symphony 3.0.0
         * @param string $context
         * '/publish/'
         * @param string $section-handle
         *  The handle of the current section
         * @param string $order
         *  The order as passed to the setSortingOrder function
         * @param boolean $updated
         *  The updated flag, set by extensions, which prevents the saving of the value
         */
        App::ExtensionManager()->notifyMembers('SectionSetSortingOrder', '/publish/', [
            'section-handle' => $this->get('handle'),
            'order' => $order,
            'updated' => &$updated,
        ]);

        // The delegate handled the request, don't set the default.
        if (!$updated) {
            App::Configuration()->set('section_' . $this->get('handle') . '_order', $order, 'sorting');

            if ($write) {
                App::Configuration()->write();
            }
        }
    }

    /**
     * Returns any section associations this section has with other sections
     * linked using fields, and where this section is the parent in the association.
     * Has an optional parameter, `$respect_visibility` that
     * will only return associations that are deemed visible by a field that
     * created the association. eg. An articles section may link to the authors
     * section, but the field that links these sections has hidden this association
     * so an Articles column will not appear on the Author's Publish Index
     *
     * @since Symphony 2.3.3
     * @param boolean $respect_visibility
     *  Whether to return all the section associations regardless of if they
     *  are deemed visible or not. Defaults to false, which will return all
     *  associations.
     * @return array
     */
    public function fetchChildAssociations(bool $respect_visibility = false): array
    {
        return SectionManager::fetchChildAssociations($this->get('id'), $respect_visibility);
    }

    /**
     * Returns any section associations this section has with other sections
     * linked using fields, and where this section is the child in the association.
     * Has an optional parameter, `$respect_visibility` that
     * will only return associations that are deemed visible by a field that
     * created the association. eg. An articles section may link to the authors
     * section, but the field that links these sections has hidden this association
     * so an Articles column will not appear on the Author's Publish Index
     *
     * @since Symphony 2.3.3
     * @param boolean $respect_visibility
     *  Whether to return all the section associations regardless of if they
     *  are deemed visible or not. Defaults to false, which will return all
     *  associations.
     * @return array
     */
    public function fetchParentAssociations($respect_visibility = false)
    {
        return SectionManager::fetchParentAssociations($this->get('id'), $respect_visibility);
    }

    /**
     * Returns an array of all the fields in this section that are to be displayed
     * on the entries table page ordered by the order in which they appear
     * in the Section Editor interface
     *
     * @throws Exception
     * @return array
     */
    public function fetchVisibleColumns()
    {
    }

    /**
     * Returns an array of all the fields that can be filtered.
     *
     * @param string $location
     *    The location of the fields in the entry creator, whether they are
     *    'main' or 'sidebar'
     * @throws Exception
     * @return array
     */
    public function fetchFilterableFields($location = null)
    {
        $fieldQuery = (new FieldManager)
            ->select()
            ->section($this->get('id'))
            ->sort('sortorder');

        if ($location) {
            $fieldQuery->location($location);
        }
        return $fieldQuery
            ->execute()
            ->restrict(Field::__FILTERABLE_ONLY__)
            ->rows();
    }

    /**
     * Returns an array of all the fields that can be toggled. This function
     * is used to help build the With Selected drop downs on the Publish
     * Index pages
     *
     * @param string $location
     *    The location of the fields in the entry creator, whether they are
     *    'main' or 'sidebar'
     * @throws Exception
     * @return array
     */
    public function fetchToggleableFields($location = null)
    {
        $fieldQuery = (new FieldManager)
            ->select()
            ->section($this->get('id'))
            ->sort('sortorder');

        if ($location) {
            $fieldQuery->location($location);
        }
        return $fieldQuery
            ->execute()
            ->restrict(Field::__TOGGLEABLE_ONLY__)
            ->rows();
    }

    /**
     * Returns the Schema of this section which includes all this sections
     * fields and their settings.
     *
     * @return array
     */
    public function fetchFieldsSchema()
    {
        return FieldManager::fetchFieldsSchema($this->get('id'));
    }

    public function loadFromFile(string $handle): void
    {
        //$data = arrayFromXMLFile('file:/' . \SECTIONS . '/section.' . $handle . '.xml');
    }

    public function setFromPost(array $data): void
    {
        $meta = $data['meta'] ?? null;
        if (!is_array($meta)) die("No meta data");
        $this->setFromArray($meta);
        $fields = $data['fields'] ?? null;
        if (is_array($fields) and !empty($fields)) {
            foreach ($fields as $values) {
                if (!is_array($values)) continue;
                $class = $values['class'] ?? null;
                if (!$class or !class_exists($class)) continue;
                $field = new $class;
                $field->setFromArray($values);
                $this->fields[] = $field;
            }
        }
        $this->validate();
    }

    protected function validate()
    {
        if (empty($this['name'])) {
            $this->setError('name', __('This is a required field'));
        } else {
            if (empty($this['handle'])) {
                $this['handle'] = Lang::createHandle($this['name']);
            }
        }

        $handles = [];
        foreach ($this->fields as $field) {
            $field->validate($handles);
        }
    }

    /**
     * Create or update section
     */
    /*public function createOrAlter(array $structure)
    {
        $meta = &$structure['meta'];
        if (!isset($meta['current_handle'])) {
            // New section.
            $handle = $meta['handle'] ?? null;
            if (!$handle) exit('No section handle.');
            $db_table_name = "tbl_section:$handle";
            if (App::Database()->tableExists($db_table_name)) {
                exit("Table $handle already exists.");
            }
            $meta['modification_date'] = DateTimeObj::get('Y-m-d H:i:s');
            $meta['modification_date_utc'] = DateTimeObj::getGMT('Y-m-d H:i:s');
            $meta['creation_date'] = $meta['modification_date'];
            $meta['creation_date_utc'] = $meta['modification_date_utc'];
            $meta['author_id'] = 1;
            $meta['modification_author_id'] = 1;
        }
        $this*/

    public function writeXMLString(bool $indent = true)
    {
        $note = $indent ?
            ' Values may be edited but do not alter the attributes. ' :
            ' Do not edit or delete this file. ';
        $meta = $this->get();
        $doc = new XMLWriter2();
        $doc->openMemory();
        $doc->setIndent($indent);
        $doc->startDocument('1.0', 'UTF-8');
        $doc->writeComment($note);
        $doc->startElement('section');
        $doc->writeAttribute('id', $meta['handle']);
        $doc->startElement('meta');
        $doc->writeElementArray($meta, ['id']);
        if (!empty($this->errors)) {
            $doc->startElement('errors');
            $doc->writeElementArray($this->errors);
            $doc->endElement();
        }
        $doc->endElement();
        $doc->startElement('fields');
        if (!empty($this->fields)) {
            foreach ($this->fields as $field) {
                $values = $field->get();
                $doc->startElement('field');
                $doc->writeAttribute('class', get_class($field));
                $doc->writeAttribute('id', $values['handle'] ?? '');
                $doc->writeElementArray($values, ['id']);
                $errors = $field->getErrors();
                if (!empty($errors)) {
                    $doc->startElement('errors');
                    $doc->writeElementArray($errors);
                    $doc->endElement();
                }
                $doc->endElement();
            }
        }
        $doc->endElement();
        $doc->endDocument();
        return $doc->outputMemory();
    }

    /*public function writeSectionFile()
    {
        $instructions = <<<END

    Values may be edited but to do not alter the 'type' or 'current_handle' attributes.

END;
        $doc = new XMLDocument('1.0');
        $root = $doc->appendElement('section', null);
        if ($this['id']) {
            $root->setAttribute('id', $this['id']);
        }
        $root->appendChild($doc->createComment($instructions));
        $x_meta = $root->appendElement('meta');
        foreach ($this->settings as $key => $value) {
            $x_meta->appendElement($key, $value);
        }
        $x_fields = $doc->appendElement('fields');
        if (is_array($fields) and !empty($fields)) {
            foreach ($fields as $field) {
                $x_field = $x_fields->appendElement('field', null, ['class' => $field['class']);
                if ($field['id']) {
                    $x_field->setAttribute('id', $field['id']);
                }
                foreach ($field as $key => $value) {
                    if (!in_array($key, ['class', 'id'])) {
                        $x_field->appendElement($key, $value);
                    }
                }
            }
        }
        /*file_put_contents(
            \SECTIONS . '/section.' . $meta['handle'] . '.xml',
            $doc->outputMemory()
        );
    }*/

    protected function setError(string $key, string $message)
    {
        $this->errors[$key] = $message;
        //$this->has_errors = true;
    }

    public function hasErrors()
    {
        if (!empty($this->errors)) return true;
        foreach ($this->fields as $field) {
            if ($field->hasErrors()) return true;
        }
        return false;
    }

    public function jsonSerialize()
    {
        $array = $this->get();
        if (!empty($this->fields)) {
            $array['fields'] = [];
            foreach ($this->fields as $field) {
                $array['fields'][] = $field->get();
            }
        }
        return $array;
    }
}
