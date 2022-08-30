<?php

/**
 * @package SectionFields
 */

namespace Symnext\SectionFields;

use Symnext\Toolkit\Field;
use Symnext\Interface\ExportableField;
use Symnext\Database\EntryQueryAuthorAdapter;
use Symnext\Toolkit\XMLElement;

/**
 * The Author field allows Symphony Authors to be selected in your entries.
 * It is a read only field, new Authors cannot be added from the Frontend using
 * events.
 *
 * The Author field allows filtering by Author ID or Username.
 * Sorting is done based on the Author's first name and last name.
 */
class FieldAuthor extends Field implements ExportableField
{
    const DEFAULT_LOCATION = 'main';

    const DEFAULT_SETTINGS = [
        'placement' => 'main',
        'validation' => null,
        'required' => 'no',
        'show_column' => 'yes'
    ];

    /*protected $settings = [
        'id' => null,
        'name' => null,
        'handle' => null,
        'placement' => 'main',
        'validation' => null,
        'required' => 'no',
        'show_column' => 'yes'
    ];*/

    public function __construct()
    {
        parent::__construct();
        $this->name = __('Author');
        $this->_required = true;
        $this->entryQueryFieldAdapter = new EntryQueryAuthorAdapter($this);

        #$this->set('author_types', []);
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

    public function canToggle(): bool
    {
        return ($this->get('allow_multiple_selection') === 'yes') ? false : true;
    }

    public function getToggleStates(): array
    {
        $authors = (new AuthorManager)->select()->execute()->rows();

        $states = [];
        foreach ($authors as $a) {
            $states[$a->get('id')] = $a->getFullName();
        }

        return $states;
    }

    public function toggleFieldData(
        array $data, $newState, $entry_id = null
    ): array
    {
        $data['author_id'] = $newState;
        return $data;
    }

    public function canFilter(): bool
    {
        return true;
    }

    public function isSortable(): bool
    {
        return $this->canToggle();
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

    public function fetchSuggestionTypes(): array
    {
        return ['static'];
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    static $table_columns = [
        'author_id' => [
            'INT',
            'NULL',
            'UNIQUE'
        ]
    ];

    public function getTableColumns()
    {
        return [
            'columns' => [
                $this->defColumn('author_id', 'int(11)', ['null' => true])
            ],
            'keys' => [
                'author' => [
                    'type' => 'unique',
                    'cols' => ['author_id'],
                ],
                'author_id' => 'key',
            ]
        ];
    }

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/

    public function set(string $field, $value): void
    {
        if ($field === 'author_types' && !is_array($value)) {
            $value = explode(',', $value);
        }

        $this->_settings[$field] = $value;
    }

    /**
     * Determines based on the input value whether we want to filter the Author
     * field by ID or by the Author's Username
     *
     * @since Symphony 2.2
     * @deprecated @since Symphony 3.0.0
     * @param string $value
     * @return string
     *  Either `author_id` or `username`
     */
    private static function __parseFilter(string $value): string
    {
        return is_numeric($value) ? 'author_id' : 'username';
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public static function addValuesToXMLDoc(XMLElement $x_parent, array $values): void
    {
        $x_parent->appendElement('validation', $values['validation'] ?? '');
        $x_parent->appendElement('required', $values['required'] ?? 'no');
        $x_parent->appendElement('show_column', $values['show_column'] ?? 'no');
    }

    public function findDefaults(array &$settings)
    {
        if (!isset($settings['allow_multiple_selection'])) {
            $settings['allow_multiple_selection'] = 'no';
        }

        if (!isset($settings['author_types'])) {
            $settings['author_types'] = ['developer', 'manager', 'author'];
        }
    }

    /*public function displaySettingsPanel(XMLElement &$wrapper, $errors = null): void
    {
        parent::displaySettingsPanel($wrapper, $errors);

        // Author types
        $label = Widget::Label(__('Author types'));
        $types = $this->get('author_types');
        $options = [
            ['author', empty($types) ? true : in_array('author', $types), __('Author')],
            ['manager', empty($types) ? true : in_array('manager', $types), __('Manager')],
            ['developer', empty($types) ? true : in_array('developer', $types), __('Developer')]
        ];
        $label->appendChild(
            Widget::Select('fields['.$this->get('sortorder').'][author_types][]', $options, [
                'multiple' => 'multiple'
            ])
        );

        if (isset($errors['author_types'])) {
            $wrapper->appendChild(Widget::Error($label, $errors['author_types']));
        } else {
            $wrapper->appendChild($label);
        }

        // Options
        $div = new XMLElement('div', null, ['class' => 'two columns']);

        // Allow multiple selection
        $this->createCheckboxSetting($div, 'allow_multiple_selection', __('Allow selection of multiple authors'));

        // Default to current logged in user
        $this->createCheckboxSetting($div, 'default_to_current_user', __('Select current user by default'));

        // Requirements and table display
        $wrapper->appendChild($div);
        $this->appendStatusFooter($wrapper);
    }*/

    public function checkFields(array &$errors, bool $checkForDuplicates = true): int
    {
        parent::checkFields($errors, $checkForDuplicates);

        $types = $this->get('author_types');

        if (empty($types)) {
            $errors['author_types'] = __('This is a required field.');
        }

        return (is_array($errors) && !empty($errors) ? self::__ERROR__ : self::__OK__);
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

        $fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');
        $fields['default_to_current_user'] = ($this->get('default_to_current_user') ? $this->get('default_to_current_user') : 'no');

        if ($this->get('author_types') != '') {
            $fields['author_types'] = implode(',', $this->get('author_types'));
        }

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
        $value = isset($data['author_id']) ? $data['author_id'] : null;

        if ($this->get('default_to_current_user') === 'yes' && empty($data) && empty($_POST)) {
            $value = [Symphony::Author()->get('id')];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $options = [];

        if ($this->get('required') !== 'yes') {
            $options[] = [null, false, null];
        }

        $authorQuery = (new AuthorManager)
            ->select()
            ->sort('id');

        // Custom where to only show Authors based off the Author Types setting
        $types = $this->get('author_types');

        if (!empty($types)) {
            $authorQuery->where(['user_type' => ['in' => $types]]);
        }

        $authors = $authorQuery->execute()->rows();
        $found = false;

        foreach ($authors as $a) {
            if (in_array($a->get('id'), $value)) {
                $found = true;
            }

            $options[] = [$a->get('id'), in_array($a->get('id'), $value), $a->getFullName()];
        }

        // Ensure the selected Author is included in the options (incase
        // the settings change after the original entry was created)
        if (!$found && !is_null($value)) {
            $authors = AuthorManager::fetchByID($value);

            foreach ($authors as $a) {
                $options[] = [$a->get('id'), in_array($a->get('id'), $value), $a->getFullName()];
            }
        }

        $fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;

        if ($this->get('allow_multiple_selection') === 'yes') {
            $fieldname .= '[]';
        }

        $label = Widget::Label($this->get('label'));

        if ($this->get('required') !== 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        $label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') === 'yes' ? ['multiple' => 'multiple'] : null)));

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
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

        if (!is_array($data) && !empty($data)) {
            return ['author_id' => $data];
        }

        if (empty($data)) {
            return null;
        }

        $result = [];

        foreach ($data as $id) {
            $result['author_id'][] = $id;
        }

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
        if (!is_array($data['author_id'])) {
            $data['author_id'] = [$data['author_id']];
        }

        $list = new XMLElement($this->get('element_name'));
        $authors = AuthorManager::fetchByID($data['author_id']);

        foreach ($authors as $author) {
            if (is_null($author)) {
                continue;
            }

            $list->appendChild(new XMLElement(
                'item',
                $author->getFullName(),
                [
                    'id' => (string)$author->get('id'),
                    'handle' => Lang::createHandle($author->getFullName()),
                    'username' => General::sanitize($author->get('username'))
                ]
            ));
        }

        $wrapper->appendChild($list);
    }

    public function prepareTextValue(array $data, int $entry_id = null): ?string
    {
        $value = $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::VALUE, $entry_id);
        return General::sanitize(implode(', ', $value));
    }

    public function getParameterPoolValue(array $data, int $entry_id = null): array|string
    {
        return $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::AUTHOR, $entry_id);
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
            'listAuthor' =>         ExportableField::LIST_OF
                                    + ExportableField::AUTHOR,
            'listAuthorObject' =>   ExportableField::LIST_OF
                                    + ExportableField::AUTHOR
                                    + ExportableField::OBJECT,
            'listAuthorToValue' =>  ExportableField::LIST_OF
                                    + ExportableField::AUTHOR
                                    + ExportableField::VALUE,
            'listValue' =>          ExportableField::LIST_OF
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
     * @return array|null
     */
    public function prepareExportValue($data, int $mode, int $entry_id = null): ?array
    {
        $modes = (object)$this->getExportModes();

        // Make sure we have an array to work with:
        if (isset($data['author_id']) && is_array($data['author_id']) === false) {
            $data['author_id'] = [$data['author_id']];
        }

        // Return the author IDs:
        if ($mode === $modes->listAuthor || $mode === $modes->getPostdata) {
            return $data['author_id'] ?? [];
        }

        // All other modes require full data:
        $authors = isset($data['author_id']) ? AuthorManager::fetchByID($data['author_id']) : [];
        $items = [];

        foreach ($authors as $author) {
            if (is_null($author)) {
                continue;
            }

            if ($mode === $modes->listAuthorObject) {
                $items[] = $author;
            } elseif ($mode === $modes->listValue) {
                $items[] = $author->getFullName();
            } elseif ($mode === $modes->listAuthorToValue) {
                $items[$data['author_id']] = $author->getFullName();
            }
        }

        return $items;
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    /**
     * @deprecated @since Symphony 3.0.0
     * @see Field::buildDSRetrievalSQL()
     */
    public function buildDSRetrievalSQL(
        $data, string &$joins, string &$where, bool $andOperation = false
    ): bool
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                get_called_class() . '::buildDSRetrievalSQL()',
                'EntryQueryFieldAdapter::filter()'
            );
        }
        $field_id = $this->get('id');

        if (self::isFilterRegex($data[0])) {
            $this->_key++;

            if (preg_match('/^regexp:/i', $data[0])) {
                $pattern = preg_replace('/^regexp:\s*/i', null, $this->cleanValue($data[0]));
                $regex = 'REGEXP';
            } else {
                $pattern = preg_replace('/^not-?regexp:\s*/i', null, $this->cleanValue($data[0]));
                $regex = 'NOT REGEXP';
            }

            if (strlen($pattern) == 0) {
                return false;
            }

            $joins .= "
                LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                JOIN
                    `tbl_authors` AS t{$field_id}_{$this->_key}_authors
                    ON (t{$field_id}_{$this->_key}.author_id = t{$field_id}_{$this->_key}_authors.id)
            ";
            $where .= "
                AND (
                    t{$field_id}_{$this->_key}.author_id {$regex} '{$pattern}'
                    OR t{$field_id}_{$this->_key}_authors.username {$regex} '{$pattern}'
                    OR CONCAT_WS(' ',
                        t{$field_id}_{$this->_key}_authors.first_name,
                        t{$field_id}_{$this->_key}_authors.last_name
                    ) {$regex} '{$pattern}'
                )
            ";
        } elseif (self::isFilterSQL($data[0])) {
            $this->buildFilterSQL($data[0], ['username', 'first_name', 'last_name'], $joins, $where);
        } elseif ($andOperation) {
            foreach ($data as $value) {
                $this->_key++;
                $value = $this->cleanValue($value);

                if (self::__parseFilter($value) == "author_id") {
                    $where .= "
                        AND t{$field_id}_{$this->_key}.author_id = '{$value}'
                    ";
                    $joins .= "
                        LEFT JOIN
                            `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                            ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                    ";
                } else {
                    $joins .= "
                        LEFT JOIN
                            `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                            ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                        JOIN
                            `tbl_authors` AS t{$field_id}_{$this->_key}_authors
                            ON (t{$field_id}_{$this->_key}.author_id = t{$field_id}_{$this->_key}_authors.id)
                    ";
                    $where .= "
                        AND (
                            t{$field_id}_{$this->_key}_authors.username = '{$value}'
                            OR CONCAT_WS(' ',
                                t{$field_id}_{$this->_key}_authors.first_name,
                                t{$field_id}_{$this->_key}_authors.last_name
                            ) = '{$value}'
                        )
                    ";
                }
            }
        } else {
            if (!is_array($data)) {
                $data = [$data];
            }

            foreach ($data as &$value) {
                $value = $this->cleanValue($value);
            }

            $this->_key++;
            $data = implode("', '", $data);
            $joins .= "
                LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                JOIN
                    `tbl_authors` AS t{$field_id}_{$this->_key}_authors
                    ON (t{$field_id}_{$this->_key}.author_id = t{$field_id}_{$this->_key}_authors.id)
            ";
            $where .= "
                AND (
                    t{$field_id}_{$this->_key}.author_id IN ('{$data}')
                    OR
                    t{$field_id}_{$this->_key}_authors.username IN ('{$data}')
                    OR CONCAT_WS(' ',
                        t{$field_id}_{$this->_key}_authors.first_name,
                        t{$field_id}_{$this->_key}_authors.last_name
                    ) IN ('{$data}')
                )
            ";
        }

        return true;
    }

    /*-------------------------------------------------------------------------
        Sorting:
    -------------------------------------------------------------------------*/

    /**
     * @deprecated @since Symphony 3.0.0
     * @see Field::buildSortingSQL()
     */
    public function buildSortingSQL(
        string &$joins,
        string &$where,
        string &$sort,
        string $order = 'ASC'
    ): void
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                get_called_class() . '::buildSortingSQL()',
                'EntryQueryFieldAdapter::sort()'
            );
        }
        if ($this->isRandomOrder($order)) {
            $sort = 'ORDER BY RAND()';
        } else {
            $joins .= "
                LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`)
                LEFT OUTER JOIN `tbl_authors` AS `a` ON (ed.author_id = a.id)
            ";
            $sort = sprintf('ORDER BY `a`.`first_name` %1$s, `a`.`last_name` %1$s, `e`.`id` %1$s', $order);
        }
    }

    /**
     * @deprecated @since Symphony 3.0.0
     * @see Field::buildSortingSelectSQL()
     */
    public function buildSortingSelectSQL(
        string $sort,
        string $order = 'ASC'
    ): ?string
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                get_called_class() . '::buildSortingSelectSQL()',
                'EntryQueryFieldAdapter::sort()'
            );
        }
        if ($this->isRandomOrder($order)) {
            return null;
        }
        return '`a`.`first_name`, `a`.`last_name`';
    }

    /*-------------------------------------------------------------------------
        Events:
    -------------------------------------------------------------------------*/

    public function getExampleFormMarkup(): XMLElement
    {
        $authors = (new AuthorManager)->select()->execute()->rows();
        $options = [];

        foreach ($authors as $a) {
            $options[] = [$a->get('id'), null, $a->getFullName()];
        }

        $fieldname = 'fields['.$this->get('element_name').']';

        if ($this->get('allow_multiple_selection') === 'yes') {
            $fieldname .= '[]';
        }

        $attr = [];

        if ($this->get('allow_multiple_selection') === 'yes') {
            $attr['multiple'] = 'multiple';
        }

        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Select($fieldname, $options, $attr));

        return $label;
    }

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
            $author_id = $data['author_id'] ?? 0;

            if (!isset($groups[$this->get('element_name')][$author_id])) {
                $author = AuthorManager::fetchByID($author_id);
                // If there is an author, use those values, otherwise just blank it.
                if ($author instanceof Author) {
                    $username = $author->get('username');
                    $full_name = $author->getFullName();
                } else {
                    $username = '';
                    $full_name = '';
                }

                $groups[$this->get('element_name')][$author_id] = [
                    'attr' => ['author-id' => $author_id, 'username' => $username, 'full-name' => $full_name],
                    'records' => [],
                    'groups' => []
                ];
            }

            $groups[$this->get('element_name')][$author_id]['records'][] = $r;
        }

        return $groups;
    }
}
