<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use Symnext\Core\App;

/**
 * The `SectionManager` is responsible for managing all Sections in a Symphony
 * installation by exposing basic CRUD operations. Sections are stored in the
 * database in `tbl_sections`.
 */

class SectionManager
{
    /**
     * An array of all the objects that the Manager is responsible for.
     *
     * @var array
     *   Defaults to an empty array.
     */
    protected static $pool = [];

    protected static function getSectionXML(string $handle)
    {
        if (!in_array($handle, self::$pool)) {
            $pool[$handle] = new Section;
        }
        return self::$pool[$handle];
    }

    /**
     * Takes an associative array of Section settings and creates a new
     * entry in the `tbl_sections` table, returning the ID of the Section.
     * The ID of the section is generated using auto_increment and returned
     * as the Section ID.
     *
     * @param array $settings
     *    An associative of settings for a section with the key being
     *    a column name from `tbl_sections`
     * @throws DatabaseException
     * @return integer
     *    The newly created Section's ID on success, 0 otherwise
     */
    public static function add(array $settings)
    {
        $defaults = [];
        $defaults['creation_date'] = $defaults['modification_date'] = DateTimeObj::get('Y-m-d H:i:s');
        $defaults['creation_date_gmt'] = $defaults['modification_date_gmt'] = DateTimeObj::getGMT('Y-m-d H:i:s');
        $defaults['author_id'] = 1;
        $defaults['modification_author_id'] = 1;
        $settings = array_replace($defaults, $settings);
        $inserted = App::Database()
            ->insert('tbl_sections')
            ->values($settings)
            ->execute()
            ->success();

        return $inserted ? App::Database()->getInsertID() : 0;
    }

    /**
     * @param string $handle
     *  Table handle
     */
    public function modify(string $handle)
    {
        $x_section = self::getSectionXML($handle);
    }

    /**
     * Updates an existing Section given it's ID and an associative
     * array of settings. The array does not have to contain all the
     * settings for the Section as there is no deletion of settings
     * prior to updating the Section
     *
     * @param integer $section_id
     *    The ID of the Section to edit
     * @param array $settings
     *    An associative of settings for a section with the key being
     *    a column name from `tbl_sections`
     * @throws DatabaseException
     * @return boolean
     */
    public static function edit(int $section_id, array $settings)
    {
        $defaults = [];
        $defaults['modification_date'] = DateTimeObj::get('Y-m-d H:i:s');
        $defaults['modification_date_gmt'] = DateTimeObj::getGMT('Y-m-d H:i:s');
        $defaults['author_id'] = 1;
        $defaults['modification_author_id'] = 1;
        $settings = array_replace($defaults, $settings);
    }

    /**
     * Deletes a Section by Section ID, removing all entries, fields, the
     * Section and any Section Associations in that order
     *
     * @param integer $section_id
     *    The ID of the Section to delete
     * @throws DatabaseException
     * @throws Exception
     * @return boolean
     *    Returns true when completed
     */
    public static function delete(string $section_id): bool
    {
        $tables = self::getTables($section_id);
        if (!empty($tables)) {
            foreach ($tables as $table) {
                App::Database()->delete($table)->execute();
            }
        }
        return true;
    }

    /**
     * Returns a new Section object, using the SectionManager
     * as the Section's $parent.
     *
     * @return Section
     */
    public static function create()
    {
        $obj = new Section;
        return $obj;
    }

    /**
     * Create an association between a section and a field.
     *
     * @since Symphony 2.3
     * @param integer $parent_section_id
     *    The linked section id.
     * @param integer $child_field_id
     *    The field ID of the field that is creating the association
     * @param integer $parent_field_id (optional)
     *    The field ID of the linked field in the linked section
     * @param boolean $show_association (optional)
     *    Whether of not the link should be shown on the entries table of the
     *    linked section. This defaults to true.
     * @throws DatabaseException
     * @throws Exception
     * @return boolean
     *    true if the association was successfully made, false otherwise.
     */
    public static function createSectionAssociation(
        $parent_section_id = null,
        $child_field_id = null,
        $parent_field_id = null,
        $show_association = true,
        $interface = null,
        $editor = null)
    {
        if (is_null($parent_section_id) && (is_null($parent_field_id) || !$parent_field_id)) {
            return false;
        }

        if (is_null($parent_section_id)) {
            $parent_field = (new FieldManager)
                ->select()
                ->field($parent_field_id)
                ->execute()
                ->next();
            $parent_section_id = $parent_field->get('parent_section');
        }

        $child_field = (new FieldManager)
            ->select()
            ->field($child_field_id)
            ->execute()
            ->next();
        $child_section_id = $child_field->get('parent_section');

        $fields = [
            'parent_section_id' => $parent_section_id,
            'parent_section_field_id' => $parent_field_id,
            'child_section_id' => $child_section_id,
            'child_section_field_id' => $child_field_id,
            'hide_association' => ($show_association ? 'no' : 'yes'),
            'interface' => $interface,
            'editor' => $editor
        ];

        return App::Database()
            ->insert('tbl_sections_association')
            ->values($fields)
            ->execute()
            ->success();
    }

    /**
     * Permanently remove a section association for this field in the database.
     *
     * @since Symphony 2.3
     * @param integer $field_id
     *    the field ID of the linked section's linked field.
     * @throws DatabaseException
     * @return boolean
     */
    public static function removeSectionAssociation($field_id)
    {
        return App::Database()
            ->delete('tbl_sections_association')
            ->where(['or' => [
                'child_section_field_id' => $field_id,
                'parent_section_field_id' => $field_id
            ]])
            ->execute()
            ->success();
    }

    /**
     * Returns the association settings for the given field id. This is to be used
     * when configuring the field so we can correctly show the association setting
     * the UI.
     *
     * @since Symphony 2.6.0
     * @param integer $field_id
     * @return string
     *  Either 'yes' or 'no', 'yes' meaning display the section.
     */
    public static function getSectionAssociationSetting($field_id)
    {
        $value = App::Database()
            ->select(['hide_association'])
            ->from('tbl_sections_association')
            ->where(['child_section_field_id' => $field_id])
            ->execute()
            ->string('hide_association');

        // We must inverse the setting. The database stores 'hide', whereas the UI
        // refers to 'show'. Hence if the database says 'yes', it really means, hide
        // the association. In the UI, this needs to be flipped to 'no' so the checkbox
        // won't be checked.
        return $value == 'no' ? 'yes' : 'no';
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
     * @param integer $section_id
     *    The ID of the section
     * @param boolean $respect_visibility
     *    Whether to return all the section associations regardless of if they
     *    are deemed visible or not. Defaults to false, which will return all
     *    associations.
     * @throws DatabaseException
     * @return array
     */
    public static function fetchChildAssociations(
        $section_id,
        $respect_visibility = false
    )
    {
        $sql = App::Database()
            ->select([
                's.*',
                'sa.parent_section_id',
                'sa.parent_section_field_id',
                'sa.child_section_id',
                'sa.child_section_field_id',
                'sa.hide_association',
                'sa.interface',
                'sa.editor',
            ])
            ->distinct()
            ->from('tbl_sections_association', 'sa')
            ->join('tbl_sections', 's')
            ->on(['s.id' => '$sa.child_section_id'])
            ->where(['sa.parent_section_id' => $section_id])
            ->orderBy(['s.sortorder' => 'ASC']);

        if ($respect_visibility) {
            $sql->where(['sa.hide_association' => 'no']);
        }

        return $sql->execute()->rows();
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
     * @param integer $section_id
     *    The ID of the section
     * @param boolean $respect_visibility
     *    Whether to return all the section associations regardless of if they
     *    are deemed visible or not. Defaults to false, which will return all
     *    associations.
     * @throws DatabaseException
     * @return array
     */
    public static function fetchParentAssociations(
        int $section_id,
        bool $respect_visibility = false
    ): array
    {
        $sql = App::Database()
            ->select([
                's.*',
                'sa.parent_section_id',
                'sa.parent_section_field_id',
                'sa.child_section_id',
                'sa.child_section_field_id',
                'sa.hide_association',
                'sa.interface',
                'sa.editor',
            ])
            ->distinct()
            ->from('tbl_sections_association', 'sa')
            ->join('tbl_sections', 's')
            ->on(['s.id' => '$sa.parent_section_id'])
            ->where(['sa.child_section_id' => $section_id])
            ->orderBy(['s.sortorder' => 'ASC']);

        if ($respect_visibility) {
            $sql->where(['sa.hide_association' => 'no']);
        }

        return $sql->execute()->rows();
    }

    /**
     * Factory method that creates a new SectionQuery.
     *
     * @since Symphony 3.0.0
     * @param array $projection
     *  The projection to select.
     *  If no projection gets added, it defaults to `SectionQuery::getDefaultProjection()`.
     * @return SectionQuery
     */
    public function select(array $projection = []): SectionQuery
    {
        return new SectionQuery(App::Database(), $projection);
    }

    /**
     * Get a list tables belonging to the specified section.
     *
     * @return Array of section table names.
     */
    public static function getTables(string $section_id): array
    {
        return App::Database()
            ->show()->like("tbl_section:$section_id%")->execute()->column(0);
    }

    public function getFileList()
    {
        $files = glob(\SECTIONS . '/section.*.xml');
    }

    public static function sectionExists(string $handle)
    {
        return is_file(SECTIONS . '/section.' . $handle . '.xml');
    }
}
