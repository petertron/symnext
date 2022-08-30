<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

/**
 * The `EntryManager` is responsible for all `Entry` objects in Symphony.
 * Entries are stored in the database in a cluster of tables. There is a
 * parent entry row stored in `tbl_entries` and then each field's data is
 * stored in a separate table, `tbl_entries_data_{field_id}`. Where Field ID
 * is generated when the Section is saved. This Manager provides basic add,
 * edit, delete and fetching methods for Entries.
 */
class EntryManager
{
    /**
     * Executes the SQL queries need to save a field's data for the specified
     * entry id.
     *
     * It first locks the table for writes, it then deletes existing data and then
     * it inserts a new row for the data. Errors are discarded and the lock is
     * released, if it was acquired.
     *
     * @param int $entry_id
     *  The entry id to save the data for
     * @param int $field_id
     *  The field id to save the data for
     * @param array $field
     *  The field data to save
     */
    protected static function saveFieldData(
        int $entry_id,
        int $field_id,
        array $field
    ): void
    {
        // Check that we have a field id
        if (empty($field_id)) {
            return;
        }

        // Ignore parameter when not an array
        if (!is_array($field)) {
            $field = [];
        }

        // Check if table exists
        $table_name = 'tbl_entries_data_' . General::intval($field_id);
        if (!App::Database()->tableExists($table_name)) {
            return;
        }

        // Delete old data
        App::Database()
            ->delete($table_name)
            ->where(['entry_id' => $entry_id])
            ->execute();

        // Insert new data
        $data = [
            'entry_id' => $entry_id
        ];

        $fields = [];

        foreach ($field as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $ii => $v) {
                    $fields[$ii][$key] = $v;
                }
            } else {
                $fields[max(0, count($fields) - 1)][$key] = $value;
            }
        }

        foreach ($fields as $index => $field_data) {
            $fields[$index] = array_merge($data, $field_data);
        }

        // Insert only if we have field data
        if (!empty($fields)) {
            foreach ($fields as $f) {
                App::Database()
                    ->insert($table_name)
                    ->values($f)
                    ->execute();
            }
        }
    }

    /**
     * Given an Entry object, iterate over all of the fields in that object
     * an insert them into their relevant entry tables.
     *
     * @see EntryManager::saveFieldData()
     * @param Entry $entry
     *  An Entry object to insert into the database
     * @throws DatabaseException
     * @return boolean
     */
    public static function add(Entry $entry)
    {
        return App::Database()->transaction(function (Database $db) use ($entry) {
            $fields = $entry->get();
            $inserted = $db
                ->insert('tbl_entries')
                ->values($fields)
                ->execute()
                ->success();

            if (!$inserted || !$entry_id = $db->getInsertID()) {
                throw new DatabaseException('Could not insert in the entries table.');
            }

            // Iterate over all data for this entry
            foreach ($entry->getData() as $field_id => $field) {
                // Write data
                static::saveFieldData($entry_id, $field_id, $field);
            }

            $entry->set('id', $entry_id);
        })->execute()->success();
    }

    /**
     * Update an existing Entry object given an Entry object
     *
     * @see EntryManager::saveFieldData()
     * @param Entry $entry
     *  An Entry object
     * @throws DatabaseException
     * @return boolean
     */
    public static function edit(Entry $entry): bool
    {
        return App::Database()->transaction(function (Database $db) use ($entry) {
            // Update modification date and modification author.
            $updated = $db
                ->update('tbl_entries')
                ->set([
                    'modification_author_id' => $entry->get('modification_author_id'),
                    'modification_date' => $entry->get('modification_date'),
                    'modification_date_gmt' => $entry->get('modification_date_gmt')
                ])
                ->where(['id' => $entry->get('id')])
                ->execute()
                ->success();

            if (!$updated) {
                throw new DatabaseException('Could not update the entries table.');
            }

            // Iterate over all data for this entry
            foreach ($entry->getData() as $field_id => $field) {
                // Write data
                static::saveFieldData($entry->get('id'), $field_id, $field);
            }
        })->execute()->success();
    }

    /**
     * Given an Entry ID, or an array of Entry ID's, delete all
     * data associated with this Entry using a Field's `entryDataCleanup()`
     * function, and then remove this Entry from `tbl_entries`. If the `$entries`
     * all belong to the same section, passing `$section_id` will improve
     * performance
     *
     * @param array|integer $entries
     *  An entry_id, or an array of entry id's to delete
     * @param integer $section_id (optional)
     *  If possible, the `$section_id` of the the `$entries`. This parameter
     *  should be left as null if the `$entries` array contains entry_id's for
     *  multiple sections.
     * @throws DatabaseException
     * @throws Exception
     * @return boolean
     */
    public static function delete(
        array|int $entries,
        int $section_id = null
    ): bool
    {
        $needs_data = true;

        if (!is_array($entries)) {
            $entries = array($entries);
        }

        // Get the section's schema
        if (!is_null($section_id)) {
            $section = (new SectionManager)->select()->section($section_id)->execute()->next();

            if ($section instanceof Section) {
                $fields = $section->fetchFields();
                $data = array();

                foreach ($fields as $field) {
                    $reflection = new ReflectionClass($field);
                    // This field overrides the default implementation, so pass it data.
                    $data[$field->get('element_name')] = $reflection->getMethod('entryDataCleanup')->class == 'Field' ? false : true;
                }

                $data = array_filter($data);

                if (empty($data)) {
                    $needs_data = false;
                }
            }
        }

        // We'll split $entries into blocks of 2500 (random number)
        // and process the deletion in chunks.
        $chunks = array_chunk($entries, 2500);

        foreach ($chunks as $chunk) {
            // If we weren't given a `section_id` we'll have to process individually
            // If we don't need data for any field, we can process the whole chunk
            // without building Entry objects, otherwise we'll need to build
            // Entry objects with data
            if (is_null($section_id) || !$needs_data) {
                $entries = $chunk;
            } elseif ($needs_data) {
                $entries = (new EntryManager)
                    ->select()
                    ->entries($chunk)
                    ->section($section_id)
                    ->includeAllFields()
                    ->disableDefaultSort()
                    ->execute()
                    ->rows();
            }

            if ($needs_data) {
                foreach ($entries as $id) {
                    // Handles the case where `section_id` was not provided
                    if (is_null($section_id)) {
                        $e = (new EntryManager)->select()->entry($id)->execute()->next();

                        if (!$e) {
                            continue;
                        }

                        $e = (new EntryManager)
                            ->select()
                            ->entry($id)
                            ->section($e->get('section_id'))
                            ->includeAllFields()
                            ->disableDefaultSort()
                            ->execute()
                            ->next();

                        // If we needed data, whole Entry objects will exist
                    } elseif ($needs_data) {
                        $e = $id;
                        $id = $e->get('id');
                    }

                    // Time to loop over it and send it to the fields.
                    // Note we can't rely on the `$fields` array as we may
                    // also be dealing with the case where `section_id` hasn't
                    // been provided
                    $entry_data = $e->getData();

                    foreach ($entry_data as $field_id => $data) {
                        $field = (new FieldManager)
                            ->select()
                            ->field($field_id)
                            ->execute()
                            ->next();
                        $field->entryDataCleanup($id, $data);
                    }
                }
            } else {
                foreach ($fields as $field) {
                    $field->entryDataCleanup($chunk);
                }
            }

            App::Database()
                ->delete('tbl_entries')
                ->where(['id' => ['in' => $chunk]])
                ->execute();
        }

        return true;
    }

    /**
     * Given an Entry ID, return the Section ID that it belongs to
     *
     * @param integer $entry_id
     *  The ID of the Entry to return it's section
     * @return integer
     *  The Section ID for this Entry's section
     */
    public static function fetchEntrySectionID(int $entry_id): int
    {
        return (new EntryManager)
            ->select()
            ->entry($entry_id)
            ->limit(1)
            ->execute()
            ->integer('section_id');
    }

    /**
     * Creates a new Entry object using this class as the parent.
     *
     * @return Entry
     */
    public static function create(): Entry
    {
        return new Entry;
    }

    /**
     * Factory method that creates a new EntryQuery.
     *
     * @since Symphony 3.0.0
     * @param array $values
     *  The fields to select. By default it's none of them, so the query
     *  only populates the object with its data.
     * @return EntryQuery
     */
    public function select(array $schema = []): EntryQuery
    {
        return new EntryQuery(App::Database(), $schema);
    }
}
