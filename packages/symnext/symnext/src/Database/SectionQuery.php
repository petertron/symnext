<?php

/**
 * @package Database
 */

namespace Symnext\Database;

/**
 * Specialized DatabaseQuery that facilitate creation of queries on the
 * sections table.
 */
class SectionQuery extends DatabaseQuery
{
    /**
     * Flag to indicate if the statement needs to add the default ORDER BY
     * clause.
     * @var boolean
     */
    private $addDefaultSort = true;

    /**
     * Creates a new SectionQuery statement on table `tbl_sections` with an
     * optional projection.
     * The table is aliased to `s`.
     *
     * @see SectionManager::select()
     * @param Database $db
     *  The underlying database connection
     * @param array $projection
     *  The columns names for include in the projection.
     *  Defaults to an empty projection.
     */
    public function __construct(Database $db, array $projection = [])
    {
        parent::__construct($db, $projection);
        $this->from('tbl_sections')->alias('s');
    }

    /**
     * Disables the default sort
     * @return SectionQuery
     *  The current instance
     */
    public function disableDefaultSort(): self
    {
        $this->addDefaultSort = false;
        return $this;
    }

    /**
     * Gets the default projection to use if no projection is added.
     *
     * @see DatabaseQuery::getDefaultProjection()
     * @return array
     */
    public function getDefaultProjection(): array
    {
        return ['s.*'];
    }

    /**
     * Adds a WHERE clause on the section id.
     *
     * @param int $section_id
     *  The section id to fetch
     * @return SectionQuery
     *  The current instance
     */
    public function section(int $section_id): self
    {
        return $this->where(['s.id' => General::intval($section_id)]);
    }

    /**
     * Adds a WHERE clause on the section id.
     *
     * @param array $section_ids
     *  The section id to fetch
     * @return SectionQuery
     *  The current instance
     */
    public function sections(array $section_ids): self
    {
        return $this->where(['s.id' => ['in' => array_map(['General', 'intval'], $section_ids)]]);
    }

    /**
     * Appends a ORDER BY clause using the $field parameter.
     *
     * @param string $field
     *  The field to order by with
     * @param string $direction
     *  The default direction to use.
     *  Defaults to ASC.
     * @return SectionQuery
     *  The current instance
     */
    public function sort(string $field, string $direction = 'ASC'): self
    {
        return $this->orderBy(["s.$field" => $direction]);
    }

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * Appends any remaining part of the statement.
     * If no sort is specified, it will default to the sort order.
     *
     * @see DatabaseStatement::execute()
     * @return SectionQuery
     *  The current instance
     */
    public function finalize(): self
    {
        if ($this->addDefaultSort && !$this->containsSQLParts('order by')) {
            $this->sort('name');
        }
        return parent::finalize();
    }

    /**
     * Creates a specialized version of DatabaseQueryResult to hold
     * result from the current SectionQuery.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return SectionQueryResult
     *  The wrapped result
     */
    public function results(bool $success, PDOStatement $stm): SectionQueryResult
    {
        return new SectionQueryResult($success, $stm, $this, $this->page);
    }
}
