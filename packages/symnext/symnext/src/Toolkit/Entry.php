<?php

/**
 * @package toolkit
 */
/**
 * An entry is a combination of data that is stored in several Fields typically
 * contained in one Section. Entries are created by the Authors of Symnext and
 * hold all the content for the website.
 * Entries are typically created from the Symnext backend, but can also be
 * created using Events from the Frontend.
 *
 * @since Symnext 3.0.0 it implements the ArrayAccess interface.
 */
class Entry implements ArrayAccess
{
    /**
     * The constant for when an Entry is ok, that is, no errors have
     * been raised by any of it's Fields.
     * @var integer
     */
    const __ENTRY_OK__ = 0;

    /**
     * The constant for an Entry if there is an error is raised by any of
     * it's Fields.
     * @var integer
     */
    const __ENTRY_FIELD_ERROR__ = 100;

    /**
     * An associative array of basic metadata/settings for this Entry
     * @var array
     */
    protected $_fields = [];

    /**
     * An associative array of the data for each of the Fields that make up
     * this Entry. The key is the Field ID, and the value is typically an array
     * @var array
     */
    protected $_data = [];

    /**
     * Entries have some basic metadata settings such as the Entry ID, the Author ID
     * who created it and the Section that the Entry sits in. This function will set a
     * setting to a value overwriting any existing value for this setting
     *
     * @param string $setting
     *  the setting key.
     * @param mixed $value
     *  the value of the setting.
     */
    public function set(string $setting, $value)
    {
        $this->_fields[$setting] = $value;
    }

    /**
     * Accessor to the a setting by name. If no setting is provided all the
     * settings of this Entry instance are returned.
     *
     * @param string $setting (optional)
     *  the name of the setting to access the value for. This is optional and
     *  defaults to null in which case all settings are returned.
     * @return null|mixed|array
     *  the value of the setting if there is one, all settings if the input setting
     * was omitted or null if the setting was supplied but there is no value
     * for that setting.
     */
    public function get(string $setting = null)
    {
        if (is_null($setting)) {
            return $this->_fields;
        }

        if (!isset($this->_fields[$setting])) {
            return null;
        }

        return $this->_fields[$setting];
    }

    /**
     * Implementation of ArrayAccess::offsetExists()
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->_fields[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->_fields[$offset];
    }

    /**
     * Implementation of ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->_fields[$offset] = $value;
    }

    /**
     * Implementation of ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->_fields[$offset]);
    }

    /**
     * Creates the initial entry row in tbl_entries and returns the resulting
     * Entry ID using `getInsertID()`.
     *
     * @see toolkit.Database#getInsertID()
     * @throws DatabaseException
     * @return integer
     */
    public function assignEntryId(): int
    {
        $fields = $this->get();
        $fields['creation_date'] = $fields['modification_date'] = DateTimeObj::get('Y-m-d H:i:s');
        $fields['creation_date_gmt'] = $fields['modification_date_gmt'] = DateTimeObj::getGMT('Y-m-d H:i:s');
        $fields['author_id'] = is_null($this->get('author_id')) ? 1 : (int)$this->get('author_id'); // Author_id cannot be null
        $fields['modification_author_id'] = is_null($this->get('modification_author_id')) ? $fields['author_id'] : (int)$this->get('modification_author_id');

        $inserted = App::Database()
            ->insert('tbl_entries')
            ->values($fields)
            ->execute()
            ->success();

        if ($inserted && !$entry_id = App::Database()->getInsertID()) {
            return 0;
        }

        $this->set('id', $entry_id);

        return $entry_id;
    }

    /**
     * Set the data for a Field in this Entry, given the Field ID and it's data
     *
     * @param integer $field_id
     *  The ID of the Field this data is for
     * @param mixed $data
     *  Often an array
     */
    public function setData(int $field_id, $data): void
    {
        $this->_data[$field_id] = $data;
    }

    /**
     * When an entry is saved from a form (either Frontend/Backend) this
     * function will find all the fields in this set and loop over them, setting
     * the data to each of the fields for processing. If any errors occur during
     * this, `_ENTRY_FIELD_ERROR_` is returned, and an array is available with
     * the errors.
     *
     * @param array $data
     *  An associative array of the data for this entry where they key is the
     *  Field's handle for this Section and the value is the data from the form
     * @param array $errors
     *  An associative array of errors, by reference. The key is the `field_id`, the value
     *  is the message text. Defaults to an empty array
     * @param boolean $simulate
     *  If $simulate is given as true, a dry run of this function will occur, where
     *  regardless of errors, an Entry will not be saved in the database. Defaults to
     *  false
     * @param boolean $ignore_missing_fields
     *  This parameter allows Entries to be updated, rather than replaced. This is
     *  useful if the input form only contains a couple of the fields for this Entry.
     *  Defaults to false, which will set Fields to their default values if they are not
     *  provided in the $data
     * @throws DatabaseException
     * @throws Exception
     * @return integer
     *  Either `Entry::__ENTRY_OK__` or `Entry::__ENTRY_FIELD_ERROR__`
     */
    public function setDataFromPost(
        array $data,
        array &$errors = null,
        bool $simulate = false,
        bool $ignore_missing_fields = false
    ): int
    {
        $status = Entry::__ENTRY_OK__;

        // Entry has no ID, create it:
        if (!$this->get('id') && $simulate === false) {
            $entry_id = $this->assignEntryId();

            if (is_null($entry_id)) {
                return Entry::__ENTRY_FIELD_ERROR__;
            }
        }

        $section = (new SectionManager)->select()->section($this->get('section_id'))->execute()->next();
        $schema = $section->fetchFieldsSchema();

        foreach ($schema as $info) {
            $message = null;
            $field = (new FieldManager)->select()->field($info['id'])->execute()->next();

            if ($ignore_missing_fields && !isset($data[$field->get('element_name')])) {
                continue;
            }

            $result = $field->processRawFieldData((isset($data[$info['element_name']]) ? $data[$info['element_name']] : null), $s, $message, $simulate, $this->get('id'));

            if ($s !== Field::__OK__) {
                $status = Entry::__ENTRY_FIELD_ERROR__;
                if (!isset($errors[$info['id']])) {
                    $errors[$info['id']] = $message;
                }
            }

            $this->setData($info['id'], $result);
        }

        // Failed to create entry, cleanup
        if ($status !== Entry::__ENTRY_OK__ && !is_null($entry_id)) {
            App::Database()
                ->delete('tbl_entries')
                ->where(['id' => $entry_id])
                ->execute();
        }

        return $status;
    }

    /**
     * Accessor function to return data from this Entry for a particular
     * field. Optional parameter to return this data as an object instead
     * of an array. If a Field is not provided, an associative array of all data
     * assigned to this Entry will be returned.
     *
     * @param integer $field_id
     *  The ID of the Field whose data you want
     * @param boolean $asObject
     *  If true, the data will be returned as an object instead of an
     *  array. Defaults to false. Note that if a `$field_id` is not provided
     *  the result will always be an array.
     * @return array|object
     *  Depending on the value of `$asObject`, return the field's data
     *  as either an array or an object. If no data exists, null will be
     *  returned.
     */
    public function getData(
        int $field_id = null,
        bool $asObject = false
    ): array|object
    {
        if (!$field_id) {
            return $this->_data;
        }
        $fieldData = $this->_data[$field_id] ?? [];

        return $asObject ? (object)$fieldData : $fieldData;
    }

    /**
     * Given a array of data from a form, this function will iterate over all the fields
     * in this Entry's Section and call their `checkPostFieldData()` function.
     *
     * @param array $data
     *  An associative array of the data for this entry where they key is the
     *  Field's handle for this Section and the value is the data from the form
     * @param null|array $errors
     *  An array of errors, by reference. Defaults to empty*  An array of errors, by reference.
     *  Defaults to empty
     * @param boolean $ignore_missing_fields
     *  This parameter allows Entries to be updated, rather than replaced. This is
     *  useful if the input form only contains a couple of the fields for this Entry.
     *  Defaults to false, which will check all Fields even if they are not
     *  provided in the $data
     * @throws Exception
     * @return integer
     *  Either `Entry::__ENTRY_OK__` or `Entry::__ENTRY_FIELD_ERROR__`
     */
    public function checkPostData(
        array $data,
        array &$errors = null,
        bool $ignore_missing_fields = false
    ): int
    {
        $status = Entry::__ENTRY_OK__;
        $section = (new SectionManager)->select()->section($this->get('section_id'))->execute()->next();
        $schema = $section->fetchFieldsSchema();

        foreach ($schema as $info) {
            $message = null;
            $field = (new FieldManager)
                ->select()->field($info['id'])->execute()->next();

            /**
             * Prior to checking a field's post data.
             *
             * @delegate EntryPreCheckPostFieldData
             * @since Symnext 2.7.0
             * @param string $context
             * '/backend/' resp. '/frontend/'
             * @param object $section
             *  The section of the field
             * @param object $field
             *  The field, passed by reference
             * @param array $post_data
             *  All post data, passed by reference
             * @param array $errors
             *  The errors (of fields already checked), passed by reference
             */
            App::ExtensionManager()->notifyMembers(
                'EntryPreCheckPostFieldData',
                App::getEngineNamespace(),
                [
                    'section' => $section,
                    'field' => &$field,
                    'post_data' => &$data,
                    'errors' => &$errors,
                ]
            );

            if ($ignore_missing_fields && !isset($data[$field->get('element_name')])) {
                continue;
            }

            if (Field::__OK__ !== $field->checkPostFieldData((isset($data[$info['element_name']]) ? $data[$info['element_name']] : null), $message, $this->get('id'))) {
                $status = Entry::__ENTRY_FIELD_ERROR__;
                $errors[$info['id']] = $message;
            }
        }

        return $status;
    }

    /**
     * Iterates over all the Fields in this Entry calling their
     * `processRawFieldData()` function to set default values for this Entry.
     *
     * @see toolkit.Field#processRawFieldData()
     */
    public function findDefaultData(): void
    {
        $section = (new SectionManager)->select()->section($this->get('section_id'))->execute()->next();
        $schema = $section->fetchFields();

        foreach ($schema as $field) {
            $field_id = $field->get('field_id');
            if (empty($field_id) || isset($this->_data[$field_id])) {
                continue;
            }
            $status = null;
            $message = null;
            $result = $field->processRawFieldData(null, $status, $message, false, $this->get('id'));
            $this->setData($field_id, $result);
        }

        $this->set('modification_date', DateTimeObj::get('Y-m-d H:i:s'));
        $this->set('modification_date_gmt', DateTimeObj::getGMT('Y-m-d H:i:s'));

        if (!$this->get('creation_date')) {
            $this->set('creation_date', $this->get('modification_date'));
        }

        if (!$this->get('creation_date_gmt')) {
            $this->set('creation_date_gmt', $this->get('modification_date_gmt'));
        }

        if (!$this->get('author_id')) {
            $this->set('author_id', 1);
        }

        if (!$this->get('modification_author_id')) {
            $this->set('modification_author_id', $this->get('author_id'));
        }
    }

    /**
     * Commits this Entry's data to the database, by first finding the default
     * data for this `Entry` and then utilising the `EntryManager`'s
     * add or edit function. The `EntryManager::edit` function is used if
     * the current `Entry` object has an ID, otherwise `EntryManager::add`
     * is used.
     *
     * @see toolkit.Entry#findDefaultData()
     * @throws Exception
     * @return boolean
     *  true if the commit was successful, false otherwise.
     */
    public function commit(): bool
    {
        $this->findDefaultData();

        return $this->get('id')
            ? EntryManager::edit($this) : EntryManager::add($this);
    }

    /**
     * Entries may link to other Entries through fields. This function will return the
     * number of entries that are associated with the current entry as an associative
     * array. If there are no associated entries, null will be returned.
     *
     * @param array $associated_sections
     *  An associative array of sections to return the Entry counts from. Defaults to
     *  null, which will fetch all the associations of this Entry.
     * @throws Exception
     * @return array
     *  An associative array with the key being the associated Section's ID and the
     *  value being the number of entries associated with this Entry.
     */
    public function fetchAllAssociatedEntryCounts(
        array $associated_sections = null
    ): array
    {
        if (is_null($this->get('section_id'))) {
            return null;
        }

        if (is_null($associated_sections)) {
            $section = (new SectionManager)->select()->section($this->get('section_id'))->execute()->next();
            $associated_sections = $section->fetchChildAssociations();
        }

        if (!is_array($associated_sections) || empty($associated_sections)) {
            return null;
        }

        $counts = [];

        foreach ($associated_sections as $as) {
            $field = (new FieldManager)->select()->field($as['child_section_field_id'])->execute()->next();
            $parent_section_field_id = $as['parent_section_field_id'];

            if (!is_null($parent_section_field_id)) {
                $search_value = $field->fetchAssociatedEntrySearchValue(
                    $this->getData($as['parent_section_field_id']),
                    $as['parent_section_field_id'],
                    $this->get('id')
                );
            } else {
                $search_value = $this->get('id');
            }

            $counts[$as['child_section_id']][$as['child_section_field_id']]
                = $field->fetchAssociatedEntryCount($search_value);
        }

        return $counts;
    }
}
