<?php

/**
 * @package SectionFields
 */

namespace Symnext\SectionFields;

use Symnext\Toolkit\Field;
use Symnext\Interface\ImportableField;
use Symnext\Interface\ExportableField;
use Symnext\Database\EntryQueryCheckboxAdapter;
use Symnext\Toolkit\XMLElement;

/**
 * Checkbox field simulates a HTML checkbox field, in that it represents a
 * simple yes/no field.
 */
class FieldCheckbox extends Field implements ExportableField, ImportableField
{
    const DEFAULT_LOCATION = 'sidebar';

    public function __construct()
    {
        parent::__construct();
        $this->name = __('Checkbox');
        $this->_required = true;
        $this->entryQueryFieldAdapter = new EntryQueryCheckboxAdapter($this);
        $this->initialiseSettings([
            'default_state' => [
                'type' => 'string',
                'values_allowed' => ['on', 'off'],
                'default_value' => 'off',
            ],
            'show_column' => [
                'type' => 'string',
                'values_allowed' => ['yes', 'no'],
                'default_value' => 'yes'
            ]
        ]);
    }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

    public function canToggle(): bool
    {
        return true;
    }

    public function getToggleStates(): array
    {
        return [
            'yes' => __('Yes'),
            'no' => __('No')
        ];
    }

    public function toggleFieldData(
        array $data,
        string $newState,
        int $entry_id = null
    ): array
    {
        $data['value'] = $newState;
        return $data;
    }

    public function canFilter(): bool
    {
        return true;
    }

    public function isSortable(): bool
    {
        return true;
    }

    public function allowDatasourceOutputGrouping(): bool
    {
        return true;
    }

    public function allowDatasourceParamOutput(): bool
    {
        return true;
    }

    public function fetchFilterableOperators(): array
    {
        return [
            [
                'title' => 'is',
                'filter' => ' ',
                'help' => __('Find values that are an exact match for the given string.')
            ]
        ];
    }

    public function fetchSuggestionTypes(): array
    {
        return ['static'];
    }

    static $table_columns = [
        'id' => [
            'INT',
            'NOT NULL',
            'AUTO_INCREMENT',
            'PRIMARY KEY'
        ],
        'entry_id' => [
            'INT',
            'UNIQUE KEY'
        ],
        'value' => [
            "ENUM('yes', 'no')",
            #"DEFAULT " . ($this->get('default_state') == 'on' ? "'yes'" : "'no'")
        ],
        'KEY (<value>)'
    ];

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    public function getTableColumns()
    {
        return [
            'columns' => [
                $this->defColumn('value', 'enum', [
                    'values' => ['yes', 'no'],
                    #'default' => $this['default_state'] == 'on' ? 'yes' : 'no',
                ])
            ],
            'keys' => [
                'value' => 'key',
            ]
        ];
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public static function addValuesToXMLDoc(XMLElement $x_parent, array $values): void
    {
        $x_parent->appendElement('default_state', $values['default_state'] ?? 'off');
        $x_parent->appendElement('show_column', $values['show_column'] ?? 'no');
    }

    public function commit(): bool
    {
        if (!parent::commit()) {
            return false;
        }

        $id = $this->get('id');

        if ($id === false) {
            return false;
        }

        $fields = [];

        $fields['default_state'] = ($this->get('default_state') ? $this->get('default_state') : 'off');

        return FieldManager::saveSettings($id, $fields);
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(
        XMLElement &$wrapper,
        $data = null,
        $flagWithError = null,
        string $fieldnamePrefix = null,
        string $fieldnamePostfix = null,
        int $entry_id = null
    ): void
    {
        if (!$data) {
            // TODO: Don't rely on $_POST
            if (isset($_POST) && !empty($_POST)) {
                $value = 'no';
            } elseif ($this->get('default_state') == 'on') {
                $value = 'yes';
            } else {
                $value = 'no';
            }
        } else {
            $value = ($data['value'] === 'yes' ? 'yes' : 'no');
        }

        $label = Widget::Label();

        if ($this->get('required') !== 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        $input = Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, 'yes', 'checkbox', ($value === 'yes' ? ['checked' => 'checked'] : null));

        $label->setValue($input->generate(false) . ' ' . $this->get('label'));

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
    }

    public function checkPostFieldData(
        $data,
        string &$message,
        int $entry_id = null
    ): int
    {
        $message = null;

        // Check if any value was passed
        $has_no_value = is_array($data) ? empty($data) : strlen(trim($data)) == 0;
        // Check that the value passed was 'on' or 'yes', if it's not
        // then the field has 'no value' in the context of being required. RE: #1569
        $has_no_value = ($has_no_value === false) ? !in_array(strtolower($data), ['on', 'yes']) : true;

        if ($this->get('required') === 'yes' && $has_no_value) {
            $message = __('‘%s’ is a required field.', [$this->get('label')]);

            return self::__MISSING_FIELDS__;
        }

        return self::__OK__;
    }

    public function processRawFieldData(
        $data,
        int &$status,
        string &$message = null,
        bool $simulate = false,
        int $entry_id = null
    ): array
    {
        $status = self::__OK__;

        return [
            'value' => (strtolower($data) == 'yes' or strtolower($data) == 'on' or $data === true ? 'yes' : 'no')
        ];
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function appendFormattedElement(
        XMLElement &$wrapper,
        array $data,
        bool $encode = false, string $mode = null,
        int $entry_id = null
    ): void
    {
        $value = ($data['value'] === 'yes' ? 'Yes' : 'No');

        $wrapper->appendChild(new XMLElement($this->get('element_name'), ($encode ? General::sanitize($value) : $value)));
    }

    public function prepareTextValue(
        array $data,
        int $entry_id = null
    ): ?string
    {
        return $this->prepareExportValue($data, ExportableField::VALUE, $entry_id);
    }

    public function getParameterPoolValue(array $data, int $entry_id = null): array|string
    {
        return $this->prepareExportValue($data, ExportableField::POSTDATA, $entry_id);
    }

    /*-------------------------------------------------------------------------
        Import:
    -------------------------------------------------------------------------*/

    public function getImportModes(): array
    {
        return [
            'getValue' =>       ImportableField::STRING_VALUE,
            'getPostdata' =>    ImportableField::ARRAY_VALUE
        ];
    }

    public function prepareImportValue(
        $data,
        int $mode,
        int $entry_id = null
    )
    {
        $status = $message = null;
        $modes = (object)$this->getImportModes();
        $value = $this->processRawFieldData($data, $status, $message, true, $entry_id);

        if ($mode === $modes->getValue) {
            return $value['value'];
        } elseif ($mode === $modes->getPostdata) {
            return $value;
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Export:
    -------------------------------------------------------------------------*/

    /**
     * Return a list of supported export modes for use with `prepareExportValue`.
     *
     * @return array
     */
    public function getExportModes(): array
    {
        return [
            'getBoolean' =>     ExportableField::BOOLEAN,
            'getValue' =>       ExportableField::VALUE,
            'getPostdata' =>    ExportableField::POSTDATA
        ];
    }

    /**
     * Give the field some data and ask it to return a value using one of many
     * possible modes.
     *
     * @param mixed $data
     * @param integer $mode
     * @param integer $entry_id
     * @return string|boolean|null
     */
    public function prepareExportValue(
        $data,
        int $mode,
        int $entry_id = null
    ): string|bool|null
    {
        $modes = (object)$this->getExportModes();
        $value = $data['value'] ?? null;

        if ($mode === $modes->getPostdata) {
            // Export unformatted:
            return isset($value) ? ($value == 'yes' ? 'yes' : 'no') : null;
        } elseif ($mode === $modes->getValue) {
            // Export formatted:
            return  isset($value) ? ($value === 'yes' ? __('Yes') : __('No')) : __('None');
        } elseif ($mode === $modes->getBoolean) {
            // Export boolean:
            return (
                isset($data['value'])
                && $data['value'] === 'yes'
            );
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    public function displayFilteringOptions(XMLElement &$wrapper): void
    {
        $existing_options = ['yes', 'no'];

        if (is_array($existing_options) && !empty($existing_options)) {
            $optionlist = new XMLElement('ul');
            $optionlist->setAttribute('class', 'tags');
            $optionlist->setAttribute('data-interactive', 'data-interactive');

            foreach ($existing_options as $option) {
                $optionlist->appendChild(new XMLElement('li', $option));
            }

            $wrapper->appendChild($optionlist);
        }
    }

    /**
     * @deprecated @since Symphony 3.0.0
     * @see Field::buildDSRetrievalSQL()
     */

    /**
     * @deprecated @since Symphony 3.0.0
     * @see Field::buildSortingSQL()
     */

    /**
     * @deprecated @since Symphony 3.0.0
     * @see Field::buildSortingSelectSQL()
     */

    /*-------------------------------------------------------------------------
        Grouping:
    -------------------------------------------------------------------------*/

    public function groupRecords(array $records = null): ?array
    {
        if (empty($records)) {
            return null;
        }

        $groups = [$this->get('element_name') => []];

        foreach ($records as $r) {
            $data = $r->getData($this->get('id'));

            $value = $data['value'];

            if (!isset($groups[$this->get('element_name')][$value])) {
                $groups[$this->get('element_name')][$value] = [
                    'attr' => ['value' => $value],
                    'records' => [],
                    'groups' => []
                ];
            }

            $groups[$this->get('element_name')][$value]['records'][] = $r;
        }

        return $groups;
    }

    /*-------------------------------------------------------------------------
        Events:
    -------------------------------------------------------------------------*/

    public function getExampleFormMarkup(): XMLElement
    {
        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Input('fields['.$this->get('element_name').']', 'yes', 'checkbox', ($this->get('default_state') == 'on' ? ['checked' => 'checked'] : null)));

        return $label;
    }
}
