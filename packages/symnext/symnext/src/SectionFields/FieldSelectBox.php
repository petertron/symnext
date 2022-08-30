<?php

/**
 * @package SectionFields
 */

namespace Symnext\SectionFields;

use Symnext\Toolkit\Field;
use Symnext\Toolkit\XMLElement;
use Symnext\Interface\ImportableField;
use Symnext\Interface\ExportableField;
use Symnext\Database\EntryQueryListAdapter;

/**
 * A simple Select field that essentially maps to HTML's `<select/>`. The
 * options for this field can be static, or feed from another field.
 */
class FieldSelectBox extends FieldTagList implements ExportableField, ImportableField
{
    const DEFAULT_LOCATION = 'sidebar';

    const DEFAULT_SETTINGS = [
        'placement' => 'sidebar',
        'validation' => null,
        'required' => 'no',
        'show_column' => 'yes'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->name = __('Select Box');
        $this->_required = true;
        $this->_showassociation = true;
        $this->entryQueryFieldAdapter = new EntryQueryListAdapter($this);
        $this->initialiseSettings([
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

    public function canToggle(): bool
    {
        return ($this->get('allow_multiple_selection') === 'yes') ? false : true;
    }

    public function getToggleStates(): array
    {
        $values = preg_split('/,\s*/i', $this->get('static_options'), -1, PREG_SPLIT_NO_EMPTY);

        if ($this->get('dynamic_options') != '') {
            $this->findAndAddDynamicOptions($values);
        }

        $values = array_map('trim', $values);
        // Fixes issues on PHP5.3. RE: #1773 ^BA
        if (empty($values)) {
            return $values;
        }

        $states = array_combine($values, $values);

        if ($this->get('sort_options') === 'yes') {
            natsort($states);
        }

        return $states;
    }

    public function toggleFieldData(
        Array $data,
        string $newState,
        int $entry_id = null
    ): Array
    {
        $data['value'] = $newState;
        $data['handle'] = Lang::createHandle($newState);

        return $data;
    }

    public function canFilter(): bool
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
        // Grouping follows the same rule as toggling.
        return $this->canToggle();
    }

    public function allowDatasourceParamOutput(): bool
    {
        return true;
    }

    public function requiresSQLGrouping(): bool
    {
        // SQL grouping follows the opposite rule as toggling.
        return !$this->canToggle();
    }

    public function fetchSuggestionTypes(): array
    {
        return ['association', 'static'];
    }

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/

    public function findAndAddDynamicOptions(?array &$values = []): void
    {
        if (!is_array($values)) {
            $values = [];
        }

        $results = null;

        // Fixes #1802
        if (!Symphony::Database()->tableExists('tbl_entries_data_' . General::intval($this->get('dynamic_options')))) {
            return;
        }

        // Ensure that the table has a 'value' column
        if (count(Symphony::Database()
            ->showColumns()
            ->from('tbl_entries_data_' . $this->get('dynamic_options'))
            ->like('value')
            ->execute()
            ->rows()) === 1
        ) {
            $results = Symphony::Database()
                ->select(['value'])
                ->distinct()
                ->from('tbl_entries_data_' . $this->get('dynamic_options'))
                ->orderBy(['value' => 'ASC'])
                ->execute()
                ->column('value');
        }

        // In the case of a Upload field, use 'file' instead of 'value'
        if (!$results && count(Symphony::Database()
            ->showColumns()
            ->from('tbl_entries_data_' . $this->get('dynamic_options'))
            ->like('file')
            ->execute()
            ->rows()) === 1
        ) {
            $results = Symphony::Database()
                ->select(['value'])
                ->distinct()
                ->from('tbl_entries_data_' . $this->get('dynamic_options'))
                ->orderBy(['file' => 'ASC'])
                ->execute()
                ->column('file');
        }

        if ($results) {
            if ($this->get('sort_options') == 'no') {
                natsort($results);
            }

            $values = array_merge($values, $results);
        }
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public static function addValuesToXMLDoc(
        XMLElement $x_parent,
        array $values
    ): void
    {
        $x_parent->appendElement('required', $values['required'] ?? 'no');
        $x_parent->appendElement('show_column', $values['show_column'] ?? 'no');
    }

    public function findDefaults(array &$settings): void
    {
        if (!isset($settings['allow_multiple_selection'])) {
            $settings['allow_multiple_selection'] = 'no';
        }

        if (!isset($settings['show_association'])) {
            $settings['show_association'] = 'no';
        }

        if (!isset($settings['sort_options'])) {
            $settings['sort_options'] = 'no';
        }
    }
/*
    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null): void
    {
        Field::displaySettingsPanel($wrapper, $errors);

        $div = new XMLElement('div', null, ['class' => 'two columns']);

        // Predefined Values
        $label = Widget::Label(__('Static Values'));
        $label->setAttribute('class', 'column');
        $label->appendChild(new XMLElement('i', __('Optional')));
        $input = Widget::Input('fields['.$this->get('sortorder').'][static_options]', General::sanitize($this->get('static_options')));
        $label->appendChild($input);
        $div->appendChild($label);

        // Dynamic Values
        // Only append selected ids, load full section information asynchronously
        $label = Widget::Label(__('Dynamic Values'));
        $label->setAttribute('class', 'column');
        $label->appendChild(new XMLElement('i', __('Optional')));

        $options = [
            ['', false, __('None')]
        ];

        if ($this->get('dynamic_options')) {
            $options[] = [$this->get('dynamic_options')];
        }

        $label->appendChild(
            Widget::Select('fields['.$this->get('sortorder').'][dynamic_options]', $options, [
                'class' => 'js-fetch-sections'
            ])
        );

        if (isset($errors['dynamic_options'])) {
            $div->appendChild(Widget::Error($label, $errors['dynamic_options']));
        } else {
            $div->appendChild($label);
        }

        $wrapper->appendChild($div);

        // Other settings
        $div = new XMLElement('div', null, ['class' => 'two columns']);

        // Allow selection of multiple items
        $this->createCheckboxSetting($div, 'allow_multiple_selection', __('Allow selection of multiple options'));

        // Sort options?
        $this->createCheckboxSetting($div, 'sort_options', __('Sort all options alphabetically'));

        $wrapper->appendChild($div);

        // Associations
        $fieldset = new XMLElement('fieldset');
        $this->appendAssociationInterfaceSelect($fieldset);
        $this->appendShowAssociationCheckbox($fieldset);
        $wrapper->appendChild($fieldset);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }*/

    public function checkFields(array &$errors, bool $checkForDuplicates = true)
    {
        if (!is_array($errors)) {
            $errors = [];
        }

        if ($this->get('static_options') == '' && ($this->get('dynamic_options') == '' || $this->get('dynamic_options') == 'none')) {
            $errors['dynamic_options'] = __('At least one source must be specified, dynamic or static.');
        }

        Field::checkFields($errors, $checkForDuplicates);
    }

    public function commit(): bool
    {
        if (!Field::commit()) {
            return false;
        }

        $id = $this->get('id');

        if ($id === false) {
            return false;
        }

        $fields = [];

        if ($this->get('static_options') != '') {
            $fields['static_options'] = $this->get('static_options');
        }

        if ($this->get('dynamic_options') != '') {
            $fields['dynamic_options'] = $this->get('dynamic_options');
        }

        $fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');
        $fields['sort_options'] = $this->get('sort_options') === 'yes' ? 'yes' : 'no';

        if (!FieldManager::saveSettings($id, $fields)) {
            return false;
        }

        SectionManager::removeSectionAssociation($id);

        // Dynamic Options isn't an array like in Select Box Link
        $field_id = $this->get('dynamic_options');

        if (!is_null($field_id) && is_numeric($field_id)) {
            SectionManager::createSectionAssociation(null, $id, (int)$field_id, $this->get('show_association') === 'yes' ? true : false, $this->get('association_ui'), $this->get('association_editor'));
        }

        return true;
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
        $states = $this->getToggleStates();
        $value = isset($data['value']) ? $data['value'] : null;

        if (!is_array($value)) {
            $value = [$value];
        }

        $options = [];
        if ($this->get('required') !== 'yes') {
            $options[] = [null, false, null];
        }

        foreach ($states as $handle => $v) {
            $options[] = [General::sanitize($v), in_array($v, $value), General::sanitize($v)];
        }

        $fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;

        if ($this->get('allow_multiple_selection') === 'yes') {
            $fieldname .= '[]';
        }

        $label = Widget::Label($this->get('label'));

        if ($this->get('required') !== 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        $label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') === 'yes' ? ['multiple' => 'multiple', 'size' => count($options)] : null)));

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
    }

    public function checkPostFieldData(
        array $data,
        string &$message,
        int $entry_id = null
    ): int
    {
        return Field::checkPostFieldData($data, $message, $entry_id);
    }

    public function processRawFieldData(
        $data,
        int &$status,
        string &$message = null,
        bool $simulate = false,
        $entry_id = null
    ): array
    {
        $status = self::__OK__;

        if (!is_array($data)) {
            return [
                'value' => $data,
                'handle' => Lang::createHandle($data)
            ];
        }

        if (empty($data)) {
            return null;
        }

        $result = [
            'value' => [],
            'handle' => []
        ];

        foreach ($data as $value) {
            $result['value'][] = $value;
            $result['handle'][] = Lang::createHandle($value);
        }

        return $result;
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function prepareTextValue($data, int $entry_id = null): string
    {
        $value = $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::VALUE, $entry_id);

        return implode(', ', $value);
    }

    /*-------------------------------------------------------------------------
        Import:
    -------------------------------------------------------------------------*/

    public function prepareImportValue($data, $mode, int $entry_id = null)
    {
        $message = $status = null;
        $modes = (object)$this->getImportModes();

        if (!is_array($data)) {
            $data = [$data];
        }

        if ($mode === $modes->getValue) {
            if ($this->get('allow_multiple_selection') === 'no') {
                $data = [implode('', $data)];
            }

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
            'listHandle' =>         ExportableField::LIST_OF
                                    + ExportableField::HANDLE,
            'listValue' =>          ExportableField::LIST_OF
                                    + ExportableField::VALUE,
            'listHandleToValue' =>  ExportableField::LIST_OF
                                    + ExportableField::HANDLE
                                    + ExportableField::VALUE,
            'getPostdata' =>        ExportableField::POSTDATA
        ];
    }

    /**
     * Give the field some data and ask it to return a value using one of many
     * possible modes.
     *
     * @param mixed $data
     * @param integer $mode
     * @param integer $entry_id
     * @return array
     */
    public function prepareExportValue(
        $data,
        int $mode,
        int $entry_id = null
    ): array
    {
        $modes = (object)$this->getExportModes();

        if (isset($data['handle']) && is_array($data['handle']) === false) {
            $data['handle'] = [
                $data['handle']
            ];
        }

        if (isset($data['value']) && is_array($data['value']) === false) {
            $data['value'] = [
                $data['value']
            ];
        }

        // Handle => Value pairs:
        if ($mode === $modes->listHandleToValue) {
            return isset($data['handle'], $data['value'])
                ? array_combine($data['handle'], $data['value'])
                : [];

            // Array of handles:
        } elseif ($mode === $modes->listHandle) {
            return isset($data['handle'])
                ? $data['handle']
                : [];

            // Array of values:
        } elseif ($mode === $modes->listValue || $mode === $modes->getPostdata) {
            return isset($data['value'])
                ? $data['value']
                : [];
        }
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    public function displayFilteringOptions(XMLElement &$wrapper): void
    {
        $existing_options = $this->getToggleStates();

        if (is_array($existing_options) && !empty($existing_options)) {
            $optionlist = new XMLElement('ul');
            $optionlist->setAttribute('class', 'tags');
            $optionlist->setAttribute('data-interactive', 'data-interactive');

            foreach ($existing_options as $option) {
                $optionlist->appendChild(
                    new XMLElement('li', General::sanitize($option))
                );
            };

            $wrapper->appendChild($optionlist);
        }
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

    /*-------------------------------------------------------------------------
        Events:
    -------------------------------------------------------------------------*/

    public function getExampleFormMarkup(): XMLElement
    {
        $states = $this->getToggleStates();

        $options = [];

        foreach ($states as $handle => $v) {
            $options[] = [$v, null, $v];
        }

        $fieldname = 'fields['.$this->get('element_name').']';

        if ($this->get('allow_multiple_selection') === 'yes') {
            $fieldname .= '[]';
        }

        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') === 'yes' ? ['multiple' => 'multiple'] : null)));

        return $label;
    }
}
