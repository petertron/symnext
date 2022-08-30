<?php

/**
 * @package SectionFields
 */

namespace Symnext\SectionFields;

use Symnext\Toolkit\Field;
use Symnext\Toolkit\XMLElement;
use Symnext\Interface\ImportableField;
use Symnext\Interface\ExportableField;
use Symnext\Database\EntryQueryTextareaAdapter;

/**
 * A simple Textarea field that essentially maps to HTML's `<textarea/>`.
 */
class FieldTextArea extends Field implements ExportableField, ImportableField
{
    const HANDLE = 'textarea';
    const DEFAULT_LOCATION = 'main';

    const DEFAULT_SETTINGS = [
        'default_num_rows' => '15',
        'required' => 'no',
        'show_column' => 'no'
    ];

    static $table_columns = [
        'value' => [
            'MEDIUMTEXT',
            'NULL',
        ],
        'value_formatted' => [
            'MEDIUMTEXT',
            'NULL',
        ],
        "FULLTEXT (<value>)"
    ];

    public function __construct()
    {
        parent::__construct();
        $this->name = __('Text Area');
        $this->_required = true;
        $this->entryQueryFieldAdapter = new EntryQueryTextareaAdapter($this);
        $this->initialiseSettings([
            'default_num_rows' => [
                'type' => 'string',
                'default_value' => '15'
            ],
            'required' => [
                'type' => 'string',
                'values_allowed' => ['yes', 'no'],
                'default_value' => 'no'
            ],
            'show_column' => [
                'type' => 'string',
                'values_allowed' => ['yes', 'no'],
                'default_value' => 'no'
            ]
        ]);
    }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

    public function canFilter(): bool
    {
        return true;
    }

    public function canPrePopulate(): bool
    {
        return true;
    }

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/

    protected function __applyFormatting(
        $data, bool $validate = false, &$errors = null
    ): string
    {
        $result = '';

        if ($this->get('formatter')) {
            $formatter = TextformatterManager::create($this->get('formatter'));
            $result = $formatter->run($data);
        }

        if ($validate === true) {
            if (!General::validateXML($result, $errors, false, new XSLTProcess)) {
                $result = html_entity_decode($result, ENT_QUOTES, 'UTF-8');
                $result = $this->__replaceAmpersands($result);

                if (!General::validateXML($result, $errors, false, new XSLTProcess)) {
                    return false;
                }
            }
        }

        return $result;
    }

    private function __replaceAmpersands(string $value): string
    {
        return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public static function addValuesToXMLDoc(
        XMLElement $x_parent,
        array $values
    ): void
    {
        $x_parent->appendElement(
            'default_num_rows', $values['default_num_rows'] ?? '15'
        );
        $x_parent->appendElement('required', $values['required'] ?? 'no');
        $x_parent->appendElement('show_column', $values['show_column'] ?? 'no');
    }

    public function findDefaults(array &$settings)
    {
        if (!isset($settings['size'])) {
            $settings['size'] = 15;
        }
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

        if ($this->get('formatter') != 'none') {
            $fields['formatter'] = $this->get('formatter');
        }

        $fields['size'] = $this->get('size');

        return FieldManager::saveSettings($id, $fields);
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(
        XMLElement &$wrapper,
        array $data = null,
        $flagWithError = null,
        string $fieldnamePrefix = null,
        string $fieldnamePostfix = null,
        int $entry_id = null
    ): void
    {
        $label = Widget::Label($this->get('label'));

        if ($this->get('required') !== 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        $value = isset($data['value']) ? $data['value'] : null;
        $textarea = Widget::Textarea('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (int)$this->get('size'), 50, (strlen($value) != 0 ? General::sanitizeDouble($value) : null));

        if ($this->get('formatter') != 'none') {
            $textarea->setAttribute('class', $this->get('formatter'));
        }

        /**
         * Allows developers modify the textarea before it is rendered in the publish forms
         *
         * @delegate ModifyTextareaFieldPublishWidget
         * @param string $context
         * '/backend/'
         * @param Field $field
         * @param Widget $label
         * @param Widget $textarea
         */
        Symphony::ExtensionManager()->notifyMembers(
            'ModifyTextareaFieldPublishWidget',
            '/backend/',
            [
                'field' => &$this,
                'label' => &$label,
                'textarea' => &$textarea
            ]
        );

        $label->appendChild($textarea);

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
    }

    public function checkPostFieldData(
        $data, string &$message, int $entry_id = null
    ): int
    {
        $message = null;

        if ($this->get('required') === 'yes' && strlen(trim($data)) == 0) {
            $message = __('‘%s’ is a required field.', [$this->get('label')]);
            return self::__MISSING_FIELDS__;
        }

        if ($this->__applyFormatting($data, true, $errors) === false) {
            $message = __('‘%s’ contains invalid XML.', [$this->get('label')]) . ' ' . __('The following error was returned:') . ' <code>' . $errors[0]['message'] . '</code>';
            return self::__INVALID_FIELDS__;
        }

        return self::__OK__;
    }

    public function processRawFieldData(
        array|string $data,
        int &$status,
        string &$message = null,
        bool $simulate = false,
        int $entry_id = null
    ): array
    {
        $status = self::__OK__;

        if (strlen(trim($data)) == 0) {
            return [];
        }

        $result = [
            'value' => $data
        ];

        $result['value_formatted'] = $this->__applyFormatting($data, true, $errors);

        if ($result['value_formatted'] === false) {
            // Run the formatter again, but this time do not validate. We will sanitize the output
            $result['value_formatted'] = General::sanitize($this->__applyFormatting($data));
        }

        return $result;
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function fetchIncludableElements(): array
    {
        if ($this->get('formatter')) {
            return [
                $this->get('element_name') . ': formatted',
                $this->get('element_name') . ': unformatted'
            ];
        } else {
            return [
                $this->get('element_name')
            ];
        }
    }

    public function appendFormattedElement(
        XMLElement &$wrapper,
        $data,
        bool $encode = false,
        string $mode = null,
        int $entry_id = null
    ): void
    {
        $attributes = [];

        if (!is_null($mode)) {
            $attributes['mode'] = $mode;
        }

        if ($mode == 'formatted') {
            if ($this->get('formatter') && isset($data['value_formatted'])) {
                $value = $data['value_formatted'];
            } else {
                $value = $this->__replaceAmpersands($data['value']);
            }

            $wrapper->appendChild(
                new XMLElement(
                    $this->get('element_name'),
                    ($encode ? General::sanitize($value) : $value),
                    $attributes
                )
            );
        } elseif ($mode == null || $mode == 'unformatted') {
            $value = !empty($data['value'])
                ? sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $data['value']))
                : $data['value'];

            $wrapper->appendChild(
                new XMLElement($this->get('element_name'), $value, $attributes)
            );
        }
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
        $message = $status = null;
        $modes = (object)$this->getImportModes();

        if ($mode === $modes->getValue) {
            return $data;
        } elseif ($mode === $modes->getPostdata) {
            return $this->processRawFieldData($data, $status, $message, true, $entry_id);
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
            'getHandle' =>      ExportableField::HANDLE,
            'getFormatted' =>   ExportableField::FORMATTED,
            'getUnformatted' => ExportableField::UNFORMATTED,
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
     * @return string|null
     */
    public function prepareExportValue(
        $data,
        int $mode,
        int $entry_id = null
    ): string|null
    {
        $modes = (object)$this->getExportModes();

        // Export handles:
        if ($mode === $modes->getHandle) {
            if (isset($data['handle'])) {
                return $data['handle'];
            } elseif (isset($data['value'])) {
                return Lang::createHandle($data['value']);
            }

            // Export unformatted:
        } elseif ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
            return isset($data['value'])
                ? $data['value']
                : null;

            // Export formatted:
        } elseif ($mode === $modes->getFormatted) {
            if (isset($data['value_formatted'])) {
                return $data['value_formatted'];
            } elseif (isset($data['value'])) {
                return General::sanitize($data['value']);
            }
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    /**
     * @deprecated @since Symphony 3.0.0
     * @see Field::buildDSRetrievalSQL()
     */
    public function buildDSRetrievalSQL(
        $data, &$joins, &$where, $andOperation = false): bool
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                get_called_class() . '::buildDSRetrievalSQL()',
                'EntryQueryFieldAdapter::filter()'
            );
        }
        $field_id = $this->get('id');

        if (self::isFilterRegex($data[0])) {
            $this->buildRegexSQL($data[0], ['value'], $joins, $where);
        } elseif (self::isFilterSQL($data[0])) {
            $this->buildFilterSQL($data[0], ['value'], $joins, $where);
        } else {
            if (is_array($data)) {
                $data = $data[0];
            }

            $this->_key++;
            $data = $this->cleanValue($data);
            $joins .= "
                LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
            ";
            $where .= "
                AND MATCH (t{$field_id}_{$this->_key}.value) AGAINST ('{$data}' IN BOOLEAN MODE)
            ";
        }

        return true;
    }

    /**
     * Write XML for publish panel.
     */
    public function outputPublishXML(XMLElement $tree, $value = null)
    {
        parent::outputPublishXML($tree);
    }

    /*-------------------------------------------------------------------------
        Events:
    -------------------------------------------------------------------------*/

    public function getExampleFormMarkup(): XMLElement
    {
        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Textarea('fields['.$this->get('element_name').']', (int)$this->get('size'), 50));

        return $label;
    }
}
