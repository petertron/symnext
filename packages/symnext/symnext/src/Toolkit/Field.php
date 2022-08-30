<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use ArrayAccess;
use Symnext\Core\App;

/**
 * The Field class represents a Symphony Field object. Fields are the building
 * blocks for Sections. All fields instances are unique and can only be used once
 * in a Symphony install. Fields have their own field table which records where
 * instances of this field type have been used in other sections and their settings.
 * They also spinoff other `tbl_entry_data_{id}` tables that actually store data
 * for
 * entries particular to this field.
 *
 * @since Symphony 3.0.0 it implements the ArrayAccess interface.
 */
abstract class Field implements ArrayAccess
{
    use Settings;

    /**
     * The desired result when creating a field in the section editor
     * @var integer
     */
    const __OK__ = 100;

    /**
     * If an error occurring when saving a section because of this field,
     * this will be returned
     * @var integer
     */
    const __ERROR__ = 150;

    /**
     * When saving a section, if a value that is required is missing,
     * this will be returned
     * @var integer
     */
    const __MISSING_FIELDS__ = 200;

    /**
     * If a value for a setting is invalid, this will be returned
     * @var integer
     */
    const __INVALID_FIELDS__ = 220;

    /**
     * If there already is an instance of this field in this section and
     * `mustBeUnique()` returns true, this will be returned
     * @var integer
     * @see mustBeUnique()
     */
    const __DUPLICATE__ = 300;

    /**
     * Fields can returned this is an error occurred when saving the
     * field's settings that doesn't fit another `Field` constant
     * @var integer
     */
    const __ERROR_CUSTOM__ = 400;

    /**
     * If the field name is not a valid QName, this error will be returned
     * @var integer
     */
    const __INVALID_QNAME__ = 500;

    /**
     * Used by the `FieldManager` to return fields that can be toggled
     * @var integer
     */
    const __TOGGLEABLE_ONLY__ = 600;

    /**
     * Used by the `FieldManager` to return fields that can't be toggled
     * @var integer
     */
    const __UNTOGGLEABLE_ONLY__ = 700;

    /**
     * Used by the `FieldManager` to return fields that can be filtered
     * @var integer
     */
    const __FILTERABLE_ONLY__ = 800;

    /**
     * Used by the `FieldManager` to return fields that can't be filtered
     * @var integer
     */
    const __UNFILTERABLE_ONLY__ = 900;

    /**
     * Used by the `FieldManager` to just return all fields
     * @var integer
     */
    const __FIELD_ALL__ = 1000;

    const DEFAULT_LOCATION = 'main';

    static $common_table_columns = [
    	'id' => [
            'INT',
            'NOT NULL',
            'AUTO_INCREMENT',
            'KEY'
        ],
        'entry_id' => [
            'INT',
            'UNIQUE'
        ]
    ];

    /**
     * The handle of this field object
     * @var string
     */
    protected $handle = null;

    /**
     * The handle of this field object
     */
    protected string $section_handle;

    /**
     * The name of this field object
     */
    protected string $name;

    /**
     * Whether this field is required inherently, defaults to false.
     * @var boolean
     */
    protected $_required = false;

    /**
     * Whether this field can be viewed on the entries table. Note
     * that this is not the same variable as the one set when saving
     * a field in the section editor, rather just the if the field has
     * the ability to be shown. Defaults to true.
     * @var boolean
     */
    protected $_showcolumn = true;

    /**
     * Whether this field has an association that should be shown on
     * the Publish Index. This does not mean that it will be, but just
     * that this field has the ability too. Defaults to false.
     * @var boolean
     */
    protected $_showassociation = false;

    /**
     * The entry query field adapter object, responsible for filter and sort.
     * The default class does not set a default EntryQueryFieldAdapter object
     * to allow the compatibility layer to work. In later versions, this can change.
     * @since Symphony 3.0.0
     * @var EntryQueryFieldAdapter
     */
    protected $entryQueryFieldAdapter = null;

    protected array $settings = [];

    protected array $errors = [];

    /**
     * Construct a new instance of this field.
     */
    public function __construct()
    {
        $this->handle = (strtolower(get_class($this)) == 'field' ? 'field' : strtolower(substr(get_class($this), 5)));
    }

    /**
     * Adjust the newly cloned field in order to fix circular references.
     *
     * @return void
     */
    public function __clone(): void
    {
        if ($this->entryQueryFieldAdapter) {
            $eqfaClass = get_class($this->entryQueryFieldAdapter);
            $this->entryQueryFieldAdapter = new $eqfaClass($this);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Test whether this field can show the table column.
     *
     * @return boolean
     *  true if this can, false otherwise.
     */
    public function canShowTableColumn(): bool
    {
        return $this->showcolumn;
    }

    /**
     * Test whether this field can show the association column in
     * the Publish Index.
     *
     * @since Symphony 2.6.0
     * @return boolean
     *  true if this can, false otherwise.
     */
    public function canShowAssociationColumn(): bool
    {
        return $this->showassociation;
    }

    /**
     * Test whether this field can be toggled using the With Selected menu
     * on the Publish Index.
     *
     * @return boolean
     *  true if it can be toggled, false otherwise.
     */
    public function canToggle(): bool
    {
        return false;
    }

    /**
     * Accessor to the toggle states. This default implementation returns
     * an empty array.
     *
     * @return array
     *  the array of toggle states.
     */
    public function getToggleStates(): array
    {
        return [];
    }

    public function setSectionHandle(string $handle)
    {
        $this->section_handle = $handle;
    }

    /**
     * Toggle the field data. This default implementation always returns
     * the input data.
     *
     * @param array $data
     *   the data to toggle.
     * @param string $newState
     *   the new value to set
     * @param integer $entry_id (optional)
     *   an optional entry ID for more intelligent processing. defaults to null
     * @return array
     *   the toggled data.
     */
    public function toggleFieldData(
        array $data,
        string $newState,
        int $entry_id
    ): array
    {
        return $data;
    }

    /**
     * Test whether this field can be filtered. This default implementation
     * prohibits filtering. Filtering allows the XML output results to be limited
     * according to an input parameter. Subclasses should override this if
     * filtering is supported.
     *
     * @return boolean
     *  true if this can be filtered, false otherwise.
     */
    public function canFilter(): bool
    {
        return false;
    }

    /**
     * Test whether this field can be filtered in the publish index. This default
     * implementation prohibts filtering. Publish Filtering allows the index view
     * to filter results. Subclasses should override this if
     * filtering is supported.
     *
     * @return boolean
     *  true if this can be publish-filtered, false otherwise.
     */
    public function canPublishFilter(): bool
    {
        return $this->canFilter();
    }

    /**
     * Test whether this field can be prepopulated with data. This default
     * implementation does not support pre-population and, thus, returns false.
     *
     * @return boolean
     *  true if this can be pre-populated, false otherwise.
     */
    public function canPrePopulate(): bool
    {
        return false;
    }

    /**
     * Test whether this field can be sorted. This default implementation
     * returns false.
     *
     * @return boolean
     *  true if this field is sortable, false otherwise.
     */
    public function isSortable(): bool
    {
        return false;
    }

    /**
     * Test whether this field must be unique in a section, that is, only one of
     * this field's type is allowed per section. This default implementation
     * always returns false.
     *
     * @return boolean
     *  true if the content of this field must be unique, false otherwise.
     */
    public function mustBeUnique(): bool
    {
        return false;
    }

    /**
     * Test whether this field supports data source output grouping. This
     * default implementation prohibits grouping. Data-source grouping allows
     * clients of this field to group the XML output according to this field.
     * Subclasses should override this if grouping is supported.
     *
     * @return boolean
     *  true if this field does support data source grouping, false otherwise.
     */
    public function allowDatasourceOutputGrouping(): bool
    {
        return false;
    }

    /**
     * Test whether this field requires grouping. If this function returns true
     * SQL statements generated in the `EntryManager` will include the `DISTINCT` keyword
     * to only return a single row for an entry regardless of how many 'matches' it
     * might have. This default implementation returns false.
     *
     * @return boolean
     *  true if this field requires grouping, false otherwise.
     */
    public function requiresSQLGrouping(): bool
    {
        return false;
    }

    /**
     * Test whether this field supports data source parameter output. This
     * default implementation prohibits parameter output. Data-source
     * parameter output allows this field to be provided as a parameter
     * to other data sources or XSLT. Subclasses should override this if
     * parameter output is supported.
     *
     * @return boolean
     *  true if this supports data source parameter output, false otherwise.
     */
    public function allowDatasourceParamOutput(): bool
    {
        return false;
    }

    /**
     * Accessor to the handle of this field object. The Symphony convention is
     * for field subclass names to be prefixed with field. Handle removes this prefix
     * so that the class handle can be used as the field type.
     *
     * @return string
     *  The field classname minus the field prefix.
     */
    public function handle(): string
    {
        return $this->handle;
    }

    /**
     * Accessor to the name of this field object. The name may contain characters
     * that normally would be stripped in the handle while also allowing the field
     * name to be localized. If a name is not set, it will return the handle of the
     * the field
     *
     * @return string
     *  The field name
     */
    public function name(): string
    {
        return $this->name ? $this->name : $this->handle;
    }

    /**
     * Clean the input value using html entity decode.
     *
     * @param string $value
     *  the value to clean.
     * @return string
     *  the cleaned value.
     */
    public function cleanValue(string $value): string
    {
        return html_entity_decode($value);
    }

    public function get()
    {
        #return ['vop' => 'dop', 'lo' => 'vo'];
    }

    protected function initialiseSettings(array $additional_settings): void
    {
        $this->settings = [
            'id' => [
                'type' => 'string',
            ],
            'name' =>  [
                'type' => 'string',
            ],
            'handle' =>  [
                'type' => 'string',
            ],
            'location' =>  [
                'type' => 'string',
                'values_allowed' => ['main', 'sidebar'],
                'default_value' => self::DEFAULT_LOCATION
            ],
            ...$additional_settings
        ];
    }

    /**
     * Return database column name.
     */
    public function dbColumnName(string $name)
    {
        return $this['handle'] . ':' . $name;
    }

    /**
     * Create database table.
     */
    public static function createTable()
    {
        App::Database()->create(
            'field_' . static::HANDLE,
            array_merge(static::$common_table_columns, static::$table_columns)
        );
    }

    /**
     * Getter for this field's EntryQuery operations object.
     *
     * @since Symphony 3.0.0
     * @return EntryQueryFieldAdapter
     */
    public function getEntryQueryFieldAdapter(): EntryQueryFieldAdapter
    {
        return $this->entryQueryFieldAdapter;
    }

    /**
     * Just prior to the field being deleted, this function allows
     * Fields to cleanup any additional things before it is removed
     * from the section. This may be useful to remove data from any
     * custom field tables or the configuration.
     *
     * @since Symphony 2.2.1
     * @return boolean
     */
    public function tearDown(): bool
    {
        return true;
    }

    /**
     * Allows a field to set default settings.
     *
     * @param array $settings
     *  the array of settings to populate with their defaults.
     */
    public function findDefaults(array &$settings)
    {
    }

    /**
     * Append a validator selector to a given `XMLElement`. Note that this
     * function differs from the other two similarly named build functions in
     * that it takes an `XMLElement` to append the Validator to as a parameter,
     * and does not return anything.
     *
     * @param XMLElement $wrapper
     *    the parent element to append the XMLElement of the Validation select to,
     *  passed by reference.
     * @param string $selected (optional)
     *    the current validator selection if there is one. defaults to null if there
     *    isn't.
     * @param string $name (optional)
     *    the form element name of this field. this defaults to "fields[validator]".
     * @param string $type (optional)
     *    the type of input for the validation to apply to. this defaults to 'input'
     *    but also accepts 'upload'.
     * @param array $errors (optional)
     *    an associative array of errors
     * @throws InvalidArgumentException
     */
    public function buildValidationSelect(
        XMLElement &$wrapper,
        string $selected = null,
        string $name = 'fields[validator]',
        string $type = 'input',
        array $errors = null
    ): void
    {
        include TOOLKIT . '/util.validators.php';

        $rules = ($type == 'upload') ? $upload : $validators;

        $label = Widget::Label(__('Validation Rule'));
        $label->setAttribute('class', 'column');
        $label->appendElement(new XMLElement('i', __('Optional')));
        $label->appendElement(Widget::Input($name, $selected));

        $ul = new XMLElement('ul', null, ['class' => 'tags singular', 'data-interactive' => 'data-interactive']);
        foreach ($rules as $name => $rule) {
            $ul->appendElement(new XMLElement('li', $name, ['class' => $rule]));
        }

        if (isset($errors['validator'])) {
            $div = new XMLElement('div');
            $div->appendElement($label);
            $div->appendElement($ul);

            $wrapper->appendElement(Widget::Error($div, $errors['validator']));
        } else {
            $wrapper->appendElement($label);
            $wrapper->appendElement($ul);
        }
    }

    /**
     * Append the html widget for selecting an association interface and editor
     * for this field.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the association interface selection to,
     *    if either interfaces or editors are provided to the system.
     * @since Symphony 2.5.0
     */
    public function appendAssociationInterfaceSelect(XMLElement &$wrapper): void
    {
        $wrapper->setAttribute('data-condition', 'associative');

        $interfaces = App::ExtensionManager()->getProvidersOf(iProvider::ASSOCIATION_UI);
        $editors = App::ExtensionManager()->getProvidersOf(iProvider::ASSOCIATION_EDITOR);

        if (!empty($interfaces) || !empty($editors)) {
            $association_context = $this->getAssociationContext();

            $group = new XMLElement('div');
            if (!empty($interfaces) && !empty($editors)) {
                $group->setAttribute('class', 'two columns');
            }

            // Create interface select
            if (!empty($interfaces)) {
                $label = Widget::Label(__('Association Interface'), null, 'column');
                $label->appendElement(new XMLElement('i', __('Optional')));

                $options = [
                    [null, false, __('None')]
                ];
                foreach ($interfaces as $id => $name) {
                    $options[] = [$id, ($association_context['interface'] === $id), $name];
                }

                $select = Widget::Select('fields[' . $this->get('sortorder') . '][association_ui]', $options);
                $label->appendElement($select);
                $group->appendElement($label);
            }

            // Create editor select
            if (!empty($editors)) {
                $label = Widget::Label(__('Association Editor'), null, 'column');
                $label->appendElement(new XMLElement('i', __('Optional')));

                $options = [
                    [null, false, __('None')]
                ];
                foreach ($editors as $id => $name) {
                    $options[] = [$id, ($association_context['editor'] === $id), $name];
                }

                $select = Widget::Select('fields[' . $this->get('sortorder') . '][association_editor]', $options);
                $label->appendElement($select);
                $group->appendElement($label);
            }

            $wrapper->appendElement($group);
        }
    }

    /**
     * Get association data of the current field from the page context.
     *
     * @since Symphony 2.5.0
     * @return array
     */
    public function getAssociationContext(): array
    {
        $context = App::Engine()->Page->getContext();
        $associations = $context['associations']['parent'];
        $field_association = [];
        $count = 0;

        if (!empty($associations)) {
            $associationsCount = count($associations);
            for ($i = 0; $i < $associationsCount; $i++) {
                if ($associations[$i]['child_section_field_id'] == $this->get('id')) {
                    if ($count === 0) {
                        $field_association = $associations[$i];
                        $count++;
                    } else {
                        $field_association['parent_section_id'] .= '|' . $associations[$i]['parent_section_id'];
                        $field_association['parent_section_field_id'] .= '|' . $associations[$i]['parent_section_field_id'];
                    }
                }
            }
        }

        return $field_association;
    }

    /**
     * Set association data for the current field.
     *
     * @since Symphony 2.5.0
     * @param XMLElement $wrapper
     */
    public function setAssociationContext(XMLElement &$wrapper): void
    {
        $association_context = $this->getAssociationContext();

        if (!empty($association_context)) {
            $wrapper->setAttributeArray(array(
                'data-parent-section-id' => $association_context['parent_section_id'],
                'data-parent-section-field-id' => $association_context['parent_section_field_id'],
                'data-child-section-id' => $association_context['child_section_id'],
                'data-child-section-field-id' => $association_context['child_section_field_id'],
                'data-interface' => $association_context['interface'],
                'data-editor' => $association_context['editor']
            ));
        }
    }

    /**
     * Append and set a labeled html checkbox to the input XML element if this
     * field is set as a required field.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the constructed html checkbox to if
     *    necessary.
     * @throws InvalidArgumentException
     */
    public function appendRequiredCheckbox(XMLElement &$wrapper): void
    {
        if (!$this->required) {
            return;
        }

        $this->createCheckboxSetting($wrapper, 'required', __('Make this a required field'));
    }

    /**
     * Append the show column html widget to the input parent XML element. This
     * displays a column in the entries table or not.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the checkbox to.
     * @throws InvalidArgumentException
     */
    public function appendShowColumnCheckbox(XMLElement &$wrapper): void
    {
        if (!$this->showcolumn) {
            return;
        }

        $this->createCheckboxSetting($wrapper, 'show_column', __('Display in entries table'));
    }

    /**
     * Append the show association html widget to the input parent XML element. This
     * widget allows fields that provide linking to hide or show the column in the linked
     * section, similar to how the Show Column functionality works, but for the linked
     * section.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the checkbox to.
     * @param string $help (optional)
     *    a help message to show below the checkbox.
     * @throws InvalidArgumentException
     */
    public function appendShowAssociationCheckbox(
        XMLElement &$wrapper,
        string $help = null
    ): void
    {
        if (!$this->showassociation) {
            return;
        }

        $label = $this->createCheckboxSetting($wrapper, 'show_association', __('Display associations in entries table'), $help);
        $label->setAttribute('data-condition', 'associative');
    }

    /**
     * Given the setting name and the label, this helper method will add
     * the required markup for a checkbox to the given `$wrapper`.
     *
     * @since Symphony 2.5.2
     * @param XMLElement $wrapper
     *  Passed by reference, this will have the resulting markup appended to it
     * @param string $setting
     *  This will be used with $this->get() to get the existing value
     * @param string $label_description
     *  This will be localisable and displayed after the checkbox when
     *  generated.
     * @param string $help (optional)
     *    A help message to show below the checkbox.
     * @return XMLElement
     *  The Label and Checkbox that was just added to the `$wrapper`.
     */
    public function createCheckboxSetting(
        XMLElement &$wrapper,
        string $setting,
        string $label_description,
        string $help = null
    ): XMLElement
    {
        $order = $this->get('sortorder');
        $name = "fields[$order][$setting]";

        $label = Widget::Checkbox($name, $this->get($setting), $label_description, $wrapper, $help);
        $label->addClass('column');

        return $label;
    }

    /**
     * Append the default status footer to the field settings panel.
     * Displays the required and show column checkboxes.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the checkbox to.
     * @throws InvalidArgumentException
     */
    public function appendStatusFooter(XMLElement &$wrapper): void
    {
        $fieldset = new XMLElement('fieldset');
        $div = new XMLElement('div', null, ['class' => 'two columns']);

        $this->appendRequiredCheckbox($div);
        $this->appendShowColumnCheckbox($div);

        $fieldset->appendElement($div);
        $wrapper->appendElement($fieldset);
    }

    /**
     * Check the field's settings to ensure they are valid on the section
     * editor
     *
     * @param array $errors
     *  the array to populate with the errors found.
     * @param boolean $checkForDuplicates (optional)
     *  if set to true, duplicate Field name's in the same section will be flagged
     *  as errors. Defaults to true.
     * @return integer
     *  returns the status of the checking. if errors has been populated with
     *  any errors `self::__ERROR__`, `self::__OK__` otherwise.
     */
    public function checkFields(
        array &$errors,
        bool $checkForDuplicates = true
    )
    {
        $parent_section = $this->get('parent_section');
        $label = $this->get('label');
        $element_name = $this->get('element_name');

        if ($label === '') {
            $errors['label'] = __('This is a required field.');
        } elseif (strtolower($label) === 'id') {
            $errors['label'] = __('%s is a reserved name used by the system and is not allowed for a field handle. Try using %s instead.', ['<code>ID</code>', '<code>UID</code>']);
        // Check label starts with a letter
        } elseif (!preg_match('/^\p{L}/u', $label)) {
            $errors['label'] = __('The label of the field must begin with a letter.');
        }

        if ($element_name === '') {
            $errors['element_name'] = __('This is a required field.');
        } elseif ($element_name === 'id') {
            $errors['element_name'] = __('%s is a reserved name used by the system and is not allowed for a field handle. Try using %s instead.', ['<code>id</code>', '<code>uid</code>']);
        } elseif (!preg_match('/^[a-z]/i', $element_name)) {
            $errors['element_name'] = __('Invalid element name. Must be valid %s.', ['<code>QName</code>']);
        } elseif ($checkForDuplicates) {
            if (FieldManager::fetchFieldIDFromElementName($element_name, $parent_section) !== $this->get('id')) {
                $errors['element_name'] = __('A field with that element name already exists. Please choose another.');
            }
        }

        // Check that if the validator is provided that it's a valid regular expression
        if (!is_null($this->get('validator')) && $this->get('validator') !== '') {
            if (@preg_match($this->get('validator'), 'teststring') === false) {
                $errors['validator'] = __('Validation rule is not a valid regular expression');
            }
        }

        return (!empty($errors) ? self::__ERROR__ : self::__OK__);
    }

    /**
     * Format this field value for display in the publish index tables.
     *
     * Since Symphony 2.5.0, this function will call `Field::prepareReadableValue`
     * in order to get the field's human readable value.
     *
     * @param array $data
     *  an associative array of data for this string. At minimum this requires a
     *  key of 'value'.
     * @param XMLElement $link (optional)
     *  an XML link structure to append the content of this to provided it is not
     *  null. it defaults to null.
     * @param integer $entry_id (optional)
     *  An option entry ID for more intelligent processing. defaults to null
     * @return string
     *  the formatted string summary of the values of this field instance.
     */
    public function prepareTableValue(
        array $data,
        XMLElement $link = null,
        int $entry_id = null
    ): string
    {
        $value = $this->prepareReadableValue($data, $entry_id, true, __('None'));

        if ($link) {
            $link->setValue($value);

            return $link->generate();
        }

        return $value;
    }

    /**
     * Format this field value for display as readable  text value. By default, it
     * will call `Field::prepareTextValue` to get the raw text value of this field.
     *
     * If $truncate is set to true, Symphony will truncate the value to the
     * configuration setting `cell_truncation_length`.
     *
     * @since Symphony 2.5.0
     * @param array $data
     *  an associative array of data for this string. At minimum this requires a
     *  key of 'value'.
     * @param integer $entry_id (optional)
     *  An option entry ID for more intelligent processing. Defaults to null.
     * @param string $defaultValue (optional)
     *  The value to use when no plain text representation of the field's data
     *  can be made. Defaults to null.
     * @return string
     *  the readable text summary of the values of this field instance.
     */
    public function prepareReadableValue(
        array $data,
        string $entry_id = null,
        bool $truncate = false,
        string $defaultValue = null
    ): string
    {
        $value = $this->prepareTextValue($data, $entry_id);

        if ($truncate) {
            $max_length = App::Configuration()->get('cell_truncation_length', 'symphony');
            $max_length = ($max_length ? $max_length : 75);

            $value = (General::strlen($value) <= $max_length ? $value : General::substr($value, 0, $max_length) . '…');
        }

        if (General::strlen($value) == 0 && $defaultValue != null) {
            $value = $defaultValue;
        }

        return $value;
    }

    /**
     * Format this field value for complete display as text (string). By default,
     * it looks for the 'value' key in the $data array and strip tags from it.
     *
     * @since Symphony 2.5.0
     * @param array $data
     *  an associative array of data for this string. At minimum this requires a
     *  key of 'value'.
     * @param integer $entry_id (optional)
     *  An option entry ID for more intelligent processing. defaults to null
     * @return string
     *  the complete text representation of the values of this field instance.
     */
    public function prepareTextValue(
        array $data,
        int $entry_id = null
    ): ?string
    {
        return isset($data['value']) ? strip_tags($data['value']) : null;
    }

    /**
     * This is general purpose factory method that makes it easier to create the
     * markup needed in order to create an Associations Drawer XMLElement.
     *
     * @since Symphony 2.5.0
     *
     * @param string $value
     *   The value to display in the link
     * @param Entry $e
     *   The associated entry
     * @param array $parent_association
     *   An array containing information about the association
     * @param string $prepopulate
     *   A string containing prepopulate parameter to append to the association url
     *
     * @return XMLElement
     *   The XMLElement must be a li node, since it will be added an ul node.
     */
    public static function createAssociationsDrawerXMLElement(
        string $value,
        Entry $e,
        array $parent_association,
        string $prepopulate = ''
    ): XMLElement
    {
        $li = new XMLElement('li');
        $a = new XMLElement('a', $value);
        $a->setAttribute('href', SYMPHONY_URL . '/publish/' . $parent_association['handle'] . '/edit/' . $e->get('id') . '/' . $prepopulate);
        $li->appendElement($a);
        return $li;
    }

    /**
     * Format this field value for display in the Associations Drawer publish index.
     * By default, Symphony will use the return value of the `prepareReadableValue` function.
     *
     * @since Symphony 2.4
     * @since Symphony 2.5.0 The prepopulate parameter was added.
     *
     * @param Entry $e
     *   The associated entry
     * @param array $parent_association
     *   An array containing information about the association
     * @param string $prepopulate
     *   A string containing prepopulate parameter to append to the association url
     *
     * @return XMLElement
     *   The XMLElement must be a li node, since it will be added an ul node.
     */
    public function prepareAssociationsDrawerXMLElement(
        Entry $e,
        array $parent_association,
        string $prepopulate = ''
    ): XMLElement
    {
        $value = $this->prepareReadableValue($e->getData($this->get('id')), $e->get('id'));

        // Fallback for compatibility since the default
        // `preparePlainTextValue` is not compatible with all fields.
        // This should be removed in Symphony 3.0
        if (empty($value)) {
            $value = strip_tags($this->prepareTableValue($e->getData($this->get('id')), null, $e->get('id')));
        }

        // use our factory method to create the html
        $li = self::createAssociationsDrawerXMLElement($value, $e, $parent_association, $prepopulate);

        $li->setAttribute('class', 'field-' . $this->get('type'));

        return $li;
    }

    /**
     * Display the publish panel for this field. The display panel is the
     * interface shown to Authors that allow them to input data into this
     * field for an `Entry`.
     *
     * @param XMLElement $wrapper
     *  the XML element to append the html defined user interface to this
     *  field.
     * @param array $data (optional)
     *  any existing data that has been supplied for this field instance.
     *  this is encoded as an array of columns, each column maps to an
     *  array of row indexes to the contents of that column. this defaults
     *  to null.
     * @param mixed $flagWithError (optional)
     *  flag with error defaults to null.
     * @param string $fieldnamePrefix (optional)
     *  the string to be prepended to the display of the name of this field.
     *  this defaults to null.
     * @param string $fieldnamePostfix (optional)
     *  the string to be appended to the display of the name of this field.
     *  this defaults to null.
     * @param integer $entry_id (optional)
     *  the entry id of this field. this defaults to null.
     */
    public function displayPublishPanel(
        XMLElement &$wrapper,
        array $data = null,
        $flagWithError = null,
        string $fieldnamePrefix = null,
        string $fieldnamePostfix = null,
        int $entry_id = null
    ): void
    {
    }

    public function outputPublishXML(XMLElement $wrapper, $value = null)
    {
        $x_field = $wrapper->appendElement(
            'field', null, ['class' => static::class]
        );
        $x_field->appendElement('location', $this['location']);
        $x_field->appendElement('name', $this['name']);
        $x_field->appendElement('handle', $this['handle']);
        if (isset($this['required'])) {
            $x_field->appendElement('required', $this['required']);
        }
    }

    /**
     * Check the field data that has been posted from a form. This will set the
     * input message to the error message or to null if there is none. Any existing
     * message value will be overwritten.
     *
     * @param array $data
     *  the input data to check.
     * @param string $message
     *  the place to set any generated error message. any previous value for
     *  this variable will be overwritten.
     * @param integer $entry_id (optional)
     *  the optional id of this field entry instance. this defaults to null.
     * @return integer
     *  `self::__MISSING_FIELDS__` if there are any missing required fields,
     *  `self::__OK__` otherwise.
     */
    public function checkPostFieldData(
        array $data,
        string &$message,
        int $entry_id = null
    ): int
    {
        $message = null;

        $has_no_value = is_array($data) ? empty($data) : strlen(trim($data)) == 0;

        if ($this->get('required') === 'yes' && $has_no_value) {
            $message = __('‘%s’ is a required field.', [$this->get('label')]);

            return self::__MISSING_FIELDS__;
        }

        return self::__OK__;
    }

    /**
     * Process the raw field data.
     *
     * @param mixed $data
     *  post data from the entry form
     * @param integer $status
     *  the status code resultant from processing the data.
     * @param string $message
     *  the place to set any generated error message. any previous value for
     *  this variable will be overwritten.
     * @param boolean $simulate (optional)
     *  true if this will tell the CF's to simulate data creation, false
     *  otherwise. this defaults to false. this is important if clients
     *  will be deleting or adding data outside of the main entry object
     *  commit function.
     * @param mixed $entry_id (optional)
     *  the current entry. defaults to null.
     * @return array
     *  the processed field data.
     */
    public function processRawFieldData(
        array|string $data,
        int &$status,
        string &$message = null,
        bool $simulate = false,
        int $entry_id = null
    ): array
    {
        $status = self::__OK__;

        return [
            'value' => $data,
        ];
    }

    /**
     * Returns the keywords that this field supports for filtering. Note
     * that no filter will do a simple 'straight' match on the value.
     *
     * @since Symphony 2.6.0
     * @return array
     */
    public function fetchFilterableOperators(): array
    {
        return [
            [
                'title' => 'is',
                'filter' => ' ',
                'help' => __('Find values that are an exact match for the given string.')
            ],
            [
                'filter' => 'sql: NOT NULL',
                'title' => 'is not empty',
                'help' => __('Find entries with a non-empty value.')
            ],
            [
                'filter' => 'sql: NULL',
                'title' => 'is empty',
                'help' => __('Find entries with an empty value.')
            ],
            [
                'title' => 'contains',
                'filter' => 'regexp: ',
                'help' => __('Find values that match the given <a href="%s">MySQL regular expressions</a>.', [
                    'https://dev.mysql.com/doc/mysql/en/regexp.html'
                ])
            ],
            [
                'title' => 'does not contain',
                'filter' => 'not-regexp: ',
                'help' => __('Find values that do not match the given <a href="%s">MySQL regular expressions</a>.', [
                    'https://dev.mysql.com/doc/mysql/en/regexp.html'
                ])
            ],
        ];
    }

    /**
     * Returns the types of filter suggestion this field supports.
     * The array may contain the following values:
     *
     * - `entry` for searching entries in the current section
     * - `association` for searching entries in associated sections
     * - `static` for searching static values
     * - `date` for searching in a calendar
     * - `parameters` for searching in parameters
     *
     * If the date type is set, only the calendar will be shown in the suggestion dropdown.
     *
     * @since Symphony 2.6.0
     * @return array
     */
    public function fetchSuggestionTypes(): array
    {
        return ['entry'];
    }

    /**
     * Display the default data source filter panel.
     *
     * @param XMLElement $wrapper
     *    the input XMLElement to which the display of this will be appended.
     * @param mixed $data (optional)
     *    the input data. this defaults to null.
     * @param null $errors
     *  the input error collection. this defaults to null.
     * @param string $fieldnamePrefix
     *  the prefix to apply to the display of this.
     * @param string $fieldnamePostfix
     *  the suffix to apply to the display of this.
     * @throws InvalidArgumentException
     */
    public function displayDatasourceFilterPanel(
        XMLElement &$wrapper,
        $data = null,
        $errors = null,
        string $fieldnamePrefix = null,
        string $fieldnamePostfix = null
    ): void
    {
        $wrapper->appendElement(new XMLElement('header', '<h4>' . $this->get('label') . '</h4> <span>' . $this->name() . '</span>', [
            'data-name' => $this->get('label') . ' (' . $this->name() . ')'
        ]));

        $label = Widget::Label(__('Value'));
        $input = Widget::Input('fields[filter]'.($fieldnamePrefix ? '['.$fieldnamePrefix.']' : '').'['.$this->get('id').']'.($fieldnamePostfix ? '['.$fieldnamePostfix.']' : ''), ($data ? General::sanitize($data) : null));
        $input->setAttribute('autocomplete', 'off');
        $input->setAttribute('data-search-types', 'parameters');
        $input->setAttribute('data-trigger', '{$');
        $label->appendElement($input);
        $wrapper->appendElement($label);

        $this->displayFilteringOptions($wrapper);
    }

    /**
     * Inserts tags at the bottom of the filter panel
     *
     * @since Symphony 2.6.0
     * @param XMLElement $wrapper
     */
    public function displayFilteringOptions(XMLElement &$wrapper): void
    {
        // Add filter tags
        $filterTags = new XMLElement('ul');
        $filterTags->setAttribute('class', 'tags singular');
        $filterTags->setAttribute('data-interactive', 'data-interactive');

        $filters = $this->fetchFilterableOperators();
        foreach ($filters as $value) {
            $item = new XMLElement('li', $value['title']);
            $item->setAttribute('data-value', $value['filter']);

            if (isset($value['help'])) {
                $item->setAttribute('data-help', General::sanitize($value['help']));
            }

            $filterTags->appendElement($item);
        }
        $wrapper->appendElement($filterTags);

        $help = new XMLElement('p');
        $help->setAttribute('class', 'help');
        $first = array_shift($filters);
        $help->setValue($first['help']);
        $wrapper->appendElement($help);
    }

    /**
     * Default accessor for the includable elements of this field. This array
     * will populate the `Datasource` included elements. Fields that have
     * different modes will override this and add new items to the array.
     * The Symphony convention is element_name : mode. Modes allow Fields to
     * output different XML in datasources.
     *
     * @return array
     *  the array of includable elements from this field.
     */
    public function fetchIncludableElements(): array
    {
        return [$this->get('element_name')];
    }

    /**
     * Builds a basic REGEXP statement given a `$filter`. This function supports
     * `regexp:` or `not-regexp:`. Users should keep in mind this function
     * uses MySQL patterns, not the usual PHP patterns, the syntax between these
     * flavours differs at times.
     *
     *  Use EntryQueryFieldAdapter::createFilterRegexp() instead
     */

    /**
     * Test whether the input string is a NULL/NOT NULL SQL clause, by searching
     * for the prefix of `sql:` in the given `$string`, followed by `(NOT )? NULL`
     *
     *  Use EntryQueryFieldAdapter::isFilterSQL() instead
     */

    /**
     * Builds a basic NULL/NOT NULL SQL statement given a `$filter`.
     *  This function supports `sql: NULL` or `sql: NOT NULL`.
     *
     *  Use EntryQueryFieldAdapter::createFilterSQL() instead
     */

    /**
     * Construct the SQL statement fragments to use to retrieve the data of this
     * field when utilized as a data source.
     *
     *  Use EntryQueryFieldAdapter::filter() instead
     */

    /**
     * Determine if the requested $order is random or not.
     *
     *  Use EntryQueryFieldAdapter::isRandomOrder() instead
     */

    /**
     * Build the SQL command to append to the default query to enable
     * sorting of this field. By default this will sort the results by
     * the entry id in ascending order.
     *
     *  Use EntryQueryFieldAdapter::sort() instead
     */

    /**
     * Build the needed SQL clause command to make `buildSortingSQL()` work on
     * MySQL 5.7 in strict mode, which requires all columns in the ORDER BY
     * clause to be included in the SELECT's projection.
     *
     *  Use EntryQueryFieldAdapter::sort() instead
     */

    /**
     * Default implementation of record grouping. This default implementation
     * will throw an `Exception`. Thus, clients must overload this method
     * for grouping to be successful.
     *
     * @throws Exception
     * @param array $records
     *  the records to group.
     */
    public function groupRecords(?array $records): ?array
    {
        throw new Exception(
            __('Data source output grouping is not supported by the %s field', ['<code>' . $this->get('label') . '</code>'])
        );
    }

    /**
     * Function to format this field if it chosen in a data source to be
     * output as a parameter in the XML.
     *
     * Since Symphony 2.5.0, it will defaults to `prepareReadableValue` return value.
     *
     * @param array $data
     *  The data for this field from it's `tbl_entry_data_{id}` table
     * @param integer $entry_id
     *  The optional id of this field entry instance
     * @return string|array
     *  The formatted value to be used as the parameter. Note that this can be
     *  an array or a string. When returning multiple values use array, otherwise
     *  use string.
     */
    public function getParameterPoolValue(
        array $data,
        int $entry_id = null
    ): string|array
    {
        return $this->prepareReadableValue($data, $entry_id);
    }

    /**
     * Append the formatted XML output of this field as utilized as a data source.
     *
     * Since Symphony 2.5.0, it will defaults to `prepareReadableValue` return value.
     *
     * @param XMLElement $wrapper
     *  the XML element to append the XML representation of this to.
     * @param array $data
     *  the current set of values for this field. the values are structured as
     *  for displayPublishPanel.
     * @param boolean $encode (optional)
     *  flag as to whether this should be html encoded prior to output. this
     *  defaults to false.
     * @param string $mode
     *   A field can provide ways to output this field's data. For instance a mode
     *  could be 'items' or 'full' and then the function would display the data
     *  in a different way depending on what was selected in the datasource
     *  included elements.
     * @param integer $entry_id (optional)
     *  the identifier of this field entry instance. defaults to null.
     */
    public function appendFormattedElement(
        XMLElement &$wrapper,
        array $data,
        bool $encode = false,
        string $mode = null,
        int $entry_id = null
    ): void
    {
        $wrapper->appendElement(
            $this->get('element_name'),
            $encode ?
                General::sanitize($this->prepareReadableValue($data, $entry_id)) :
                $this->prepareReadableValue($data, $entry_id)
        );
    }

    /**
     * Commit the settings of this field from the section editor to
     * create an instance of this field in a section.
     *
     * @return boolean
     *  true if the commit was successful, false otherwise.
     */
    public function commit(): bool
    {
        $fields = [];

        $fields['label'] = General::sanitize($this->get('label'));
        $fields['element_name'] = ($this->get('element_name') ? $this->get('element_name') : Lang::createHandle($this->get('label')));
        $fields['parent_section'] = $this->get('parent_section');
        $fields['placement'] = $this->get('placement');
        $fields['required'] = $this->get('required');
        $fields['type'] = $this->handle;
        $fields['show_column'] = $this->get('show_column');
        $fields['sortorder'] = (string)$this->get('sortorder');

        if ($id = $this->get('id')) {
            return FieldManager::edit($id, $fields);
        } elseif ($id = FieldManager::add($fields)) {
            $this->set('id', $id);
            if ($this->requiresTable()) {
                return $this->createTable();
            }
            return true;
        }

        return false;
    }

    /**
     * The default field table construction method. This constructs the bare
     * minimum set of columns for a valid field table. Subclasses are expected
     * to overload this method to create a table structure that contains
     * additional columns to store the specific data created by the field.
     *
     * @throws DatabaseException
     * @see Field::requiresTable()
     * @return boolean
     */
    /*public function createTable(
        string $section_handle,
        string $field_handle,
        string $index_type = null
    ): bool
    {
        return App::Database()
            ->create('tbl_entries_data_' . General::intval($this->get('id')))
            ->ifNotExists()
            ->fields([
                'id' => [
                    'type' => 'int(11)',
                    'auto' => true,
                ],
                'entry_id' => 'int(11)',
                'value' => [
                    'type' => 'varchar(255)',
                    'null' => true,
                ],
            ])
            ->keys([
                'id' => 'primary',
                'entry_id' => 'key',
                'value' => 'key',
            ])
            ->execute()
            ->success();
    }*/

    /**
     * Tells Symphony that this field needs a table in order to store
     * data for each of its entries. Used when adding/deleting this field in a section
     * or entries are edited/added, data as a performance optimization.
     * It defaults to true, which force table creation.
     *
     * Developers are encouraged to update their null create table implementation
     * with this method.
     *
     * @since Symphony 2.7.0
     * @see Field::createTable()
     * @return boolean
     *  true if Symphony should call `createTable()`
     */
    public function requiresTable(): bool
    {
        return true;
    }

    /**
     * Checks that we are working with a valid field handle and
     * that the setting table exists.
     *
     * @since Symphony 2.7.0
     * @return boolean
     *   true if the table exists, false otherwise
     */
    public static function tableExists(): bool
    {
        $prefix = App::Configuration()->get('prefix', 'database');
        $result = App::Database()
            ->query("SHOW TABLES LIKE '{$prefix}field_" . static::HANDLE . "'")->fetchAll();
        return !empty($result);
    }

    /**
     * Checks that we are working with a valid field handle and field id, and
     * checks that the field record exists in the settings table.
     *
     * @since Symphony 2.7.1 It does check if the settings table only contains
     *   default columns and assume those fields do not require a record in the settings table.
     *   When this situation is detected the field is considered as valid even if no records were
     *   found in the settings table.
     *
     * @since Symphony 2.7.0
     * @see Field::tableExists()
     * @return boolean
     *   true if the field id exists in the table, false otherwise
     */
    public function exists(): bool
    {
        if (!$this->get('id') || !$this->handle) {
            return false;
        }
        $row = App::Database()
            ->select(['id'])
            ->from('tbl_fields_' . $this->handle)
            ->where(['field_id' => $this->get('id')])
            ->execute()
            ->rows();

        if (empty($row)) {
            // Some fields do not create any records in their settings table because they do not
            // implement a proper `Field::commit()` method.
            // The base implementation of the commit function only deals with the "core"
            // `tbl_fields` table.
            // The problem with this approach is that it can lead to data corruption when
            // saving a field that got deleted by another user.
            // The only way a field can live without a commit method is if it does not store any
            // settings at all.
            // But current version of Symphony assume that the `tbl_fields_$handle` table exists
            // with at least a `id` and `field_id` column, so field are required to at least create
            // the table to make their field work without SQL errors from the core.
            $columns = App::Database()
                ->describe('tbl_fields_' . $this->handle)
                ->execute()
                ->column('Field');

            // The table only has the two required columns, tolerate the missing record
            $isDefault = count($columns) === 2 &&
                in_array('id', $columns) &&
                in_array('field_id', $columns);
            if ($isDefault) {
                App::Log()->pushDeprecateWarningToLog($this->handle, get_class($this), [
                    'message-format' => __('The field `%1$s` does not create settings records in the `tbl_fields_%1$s`.'),
                    'alternative-format' => __('Please implement the commit function in class `%s`.'),
                    'removal-format' => __('The compatibility check will will be removed in Symphony %s.'),
                    'removal-version' => '4.0.0',
                ]);
            }
            return $isDefault;
        }
        return true;
    }

    /**
     * Remove the entry data of this field from the database.
     *
     * @param integer|array $entry_id
     *    the ID of the entry, or an array of entry ID's to delete.
     * @param array $data (optional)
     *    The entry data provided for fields to do additional cleanup
     *  This is an optional argument and defaults to null.
     * @throws DatabaseException
     * @return boolean
     *    Returns true after the cleanup has been completed
     */
    public function entryDataCleanup(
        int|array $entry_id,
        array $data = null
    ): bool
    {
        if (!is_array($entry_id)) {
            $entry_id = [$entry_id];
        }
        return App::Database()
            ->delete('tbl_entries_data_' . $this->get('id'))
            ->where(['entry_id' => ['in' => $entry_id]])
            ->execute()
            ->success();
    }

    /**
     * Accessor to the associated entry search value for this field
     * instance. This default implementation simply returns `$data`
     *
     * @param array $data
     *  the data from which to construct the associated search entry value, this is usually
     *  Entry with the `$parent_entry_id` value's data.
     * @param integer $field_id (optional)
     *  the ID of the field that is the parent in the relationship
     * @param integer $parent_entry_id (optional)
     *  the ID of the entry from the parent section in the relationship
     * @return array|string
     *  Defaults to returning `$data`, but overriding implementation should return
     *  a string
     */
    public function fetchAssociatedEntrySearchValue(
        array $data,
        int $field_id = null,
        int $parent_entry_id = null
    ): array|string
    {
        return $data;
    }

    /**
     * Fetch the count of the associated entries given a `$value`.
     *
     * @see toolkit.Field#fetchAssociatedEntrySearchValue()
     * @param mixed $value
     *  the value to find the associated entry count for, this usually comes from
     *  the `fetchAssociatedEntrySearchValue` function.
     * @return void|integer
     *  this default implementation returns void. overriding implementations should
     *  return an integer.
     */
    public function fetchAssociatedEntryCount($value)
    {
    }

    /**
     * Find related entries from a linking field's data table. Default implementation uses
     * column names `entry_id` and `relation_id` as with the Select Box Link
     *
     * @since Symphony 2.5.0
     *
     * @param  integer $entry_id
     * @param  integer $parent_field_id
     * @return array
     */
    public function findRelatedEntries(
        int $entry_id,
        int $parent_field_id
    ): array
    {
        try {
            $ids = App::Database()
                ->select(['entry_id'])
                ->from('tbl_entries_data_' . $this->get('id'))
                ->where(['relation_id' => General::intval($entry_id)])
                ->where(['entry_id' => ['!=' => null]])
                ->execute()
                ->column('entry_id');
        } catch (Exception $e) {
            return [];
        }

        return $ids;
    }

    /**
     * Find related entries for the current field. Default implementation uses
     * column names `entry_id` and `relation_id` as with the Select Box Link
     *
     * @since Symphony 2.5.0
     *
     * @param  integer $parent_field_id
     * @param  integer $entry_id
     * @return array
     */
    public function findParentRelatedEntries(
        int $parent_field_id,
        int $entry_id
    ): array
    {
        try {
            $ids = App::Database()
                ->select(['relation_id'])
                ->from('tbl_entries_data_' . $this->get('id'))
                ->where(['entry_id' => General::intval($entry_id)])
                ->where(['relation_id' => ['!=' => null]])
                ->execute()
                ->column('relation_id');
        } catch (Exception $e) {
            return [];
        }

        return $ids;
    }

    /**
     * Converts all $values into proper ones to use with prepopulate and filtering.
     * The default implementation simply returns the $values array.
     *
     * @param array $values
     *  The original filter values
     * @return array
     *  The proper filter values to use in query strings
     */
    public function convertValuesToFilters(array $values): array
    {
        return $values;
    }

    /*public function outputXML(XMLElement $wrapper): void
    {
        print_r($this->get('handle')); die;
        $field = $wrapper->appendElement('field');
        $field->setAttribute('class', get_class($this));
        $field->setAttribute('current_handle', $this->get('handle'));
        foreach ($this->settings as $key => $value) {
            if ($key == 'current_handle') continue;
            $field->{$key} = $value;
        }
    }*/

    public function validate(array &$handles): bool
    {
        if (empty($this['name'])) {
            $this->setError('name', __('This is a required field'));
        } else {
            if (empty($this['handle'])) {
                $this['handle'] = Lang::createHandle($this['name']);
            }
        }
        if (!empty($this['handle'])) {
            if (in_array($this['handle'], $handles)) {
                $this->setError('handle', __('Handle already used.'));
            }
            $handles[] = $this['handle'];
        }
        return !empty($this->errors);
    }

    protected function setError($key, $message)
    {
        $this->errors[$key] = $message;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
