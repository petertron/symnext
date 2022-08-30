<?php

/**
 * @package SectionFields
 */

namespace Symnext\SectionFields;

use Symnext\Core\App;
use Symnext\Toolkit\Field;
use Symnext\Interface\ImportableField;
use Symnext\Interface\ExportableField;
use Symnext\Database\EntryQueryInputAdapter;
use Symnext\Toolkit\XMLElement;

/**
 * A simple Input field that essentially maps to HTML's `<input type='text'/>`.
 */
class FieldInput extends Field implements ExportableField, ImportableField
{
    const HANDLE = 'input';
    const CAN_FILTER = true;
    const CAN_PREPOPULATE = true;
    const IS_SORTABLE = true;
    const ALLOWDATASOURCE_OUTPUT_GROUPING = true;
    const ALLOW_DATASOURCE_PARAM_OUTPUT = true;

    const DEFAULT_LOCATION = 'main';

    const DEFAULT_SETTINGS = [
        'validation' => null,
        'required' => 'no',
        'show_column' => 'no'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->name = __('Text Input');
        $this->entryQueryFieldAdapter = new EntryQueryInputAdapter($this);
        $this->initialiseSettings([
            'unique_value' => [
                'type' => 'string',
                'values_allowed' => ['yes', 'no'],
                'default_value' => 'no',
            ],
            'validation' => ['type' => 'string'],
            'required' => [
                'type' => 'string',
                'values_allowed' => ['yes', 'no'],
                'default_value' => 'no'
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


    /*public function canFilter(): bool
    {
        return true;
    }

    public function canPrePopulate(): bool
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
    }*/

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    static $table_columns = [
        'handle' => [
            'VARCHAR(255)',
            'NULL',
        ],
        'value' => [
            'VARCHAR(255)',
            'NULL',
        ],
        "KEY (<handle>)",
        "KEY (<value>)"
    ];

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/

    private function applyValidationRules($data): bool
    {
        $rule = $this->get('validator');

        return ($rule ? General::validateString($data, $rule) : true);
    }

    private function replaceAmpersands(string $value)
    {
        return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    /*public function setFromArray(array $settings = []): void
    {
        parent::setFromArray($settings);

        if ($this->get('validator') == '') {
            $this->remove('validator');
        }
    }*/

    /*public static function addValuesToXMLDoc(
        XMLElement $x_parent,
        array $values
    ): void
    {
        $x_parent->appendElement('validation', $values['validation'] ?? '');
        $x_parent->appendElement('required', $values['required'] ?? 'no');
        $x_parent->appendElement('show_column', $values['show_column'] ?? 'no');
    }*/

    public function commit(): bool
    {
        if (!parent::commit()) {
            return false;
        }

        $id = $this->get('id');

        if ($id === false) {
            return false;
        }

        $fields = ['validator' => null];

        $fields['validator'] = ($fields['validator'] == 'custom') ? null : $this->get('validator');

        return FieldManager::saveSettings($id, $fields);
    }

    public function checkPostFieldData(
        $data,
        ?string &$message,
        int $entry_id = null
    ): int
    {
        $message = null;

        if (is_array($data) && isset($data['value'])) {
            $data = $data['value'];
        }

        if ($this->get('required') === 'yes' && strlen(trim($data)) == 0) {
            $message = __('‘%s’ is a required field.', [$this->get('label')]);
            return self::__MISSING_FIELDS__;
        }

        if (!$this->__applyValidationRules($data)) {
            $message = __('‘%s’ contains invalid data. Please check the contents.', [$this->get('label')]);
            return self::__INVALID_FIELDS__;
        }

        return self::__OK__;
    }

    public function processRawFieldData(
        array|string $data,
        int &$status = null,
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
            'value' => General::substr($data, 0, 255)
        ];

        $result['handle'] = Lang::createHandle($result['value']);

        return $result;
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function appendFormattedElement(
        XMLElement &$wrapper,
        array $data,
        bool $encode = false,
        string $mode = null,
        int $entry_id = null
    ): void
    {
        $value = $data['value'];

        if ($encode === true) {
            $value = General::sanitize($value);
        } else {
            if (!General::validateXML($data['value'], $errors, false, new XSLTProcess)) {
                $value = html_entity_decode($data['value'], ENT_QUOTES, 'UTF-8');
                $value = $this->__replaceAmpersands($value);

                if (!General::validateXML($value, $errors, false, new XSLTProcess)) {
                    $value = General::sanitize($data['value']);
                }
            }
        }

        $wrapper->appendChild(
            new XMLElement($this->get('element_name'), $value, ['handle' => $data['handle']])
        );
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
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Grouping:
    -------------------------------------------------------------------------*/

    public function groupRecords(array $records = null): ?array
    {
        if (!is_array($records) || empty($records)) {
            return null;
        }

        $groups = [$this->get('element_name') => []];

        foreach ($records as $r) {
            $data = $r->getData($this->get('id'));
            $value = $data['value'] ?? null;
            if (!$value) continue;
            $value = General::sanitize($data['value']);


            if (!isset($groups[$this->get('element_name')][$data['handle']])) {
                $groups[$this->get('element_name')][$data['handle']] = [
                    'attr' => ['handle' => $data['handle'], 'value' => $value],
                    'records' => [],
                    'groups' => []
                ];
            }

            $groups[$this->get('element_name')][$data['handle']]['records'][] = $r;
        }

        return $groups;
    }

    public function additionalXML(XMLElement $wrapper)
    {
        $x_validators = $wrapper->addChild('validators');
        $x_v = $x_validators->addchild('validator', '/^-?(?:\d+(?:\.\d+)?|\.\d+)$/i');
        $x_v->addAttribute('for', 'number');
    }
        /*'email">
            <xsl:text disable-output-escaping="yes">/^\w(?:\.?[\w%+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i</xsl:text>
        </xsl:variable>
        <xsl:variable name="regex-uri">
            <xsl:text disable-output-escaping="yes">/^[^\s:\/?#]+:(?:\/{2,3})?[^\s.\/?#]+(?:\.[^\s.\/?#]+)*(?:\/?[^\s?#]*\??[^\s?#]*(#[^\s#]*)?)?$/</xsl:text>
        </xsl:variable>*/
    public function writeValues(XMLWriter $doc)
    {
        $settings = $this->settings;
        $doc->writeElement('validation', $settings['validation']);
        $doc->writeElement('required', $settings['required']);
        $doc->writeElement('show_column', $settings['show_column']);
    }
}
