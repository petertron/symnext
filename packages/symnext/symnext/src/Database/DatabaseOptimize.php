<?php

/**
 * @package Database
 */

namespace Symnext\Database;

/**
 * This DatabaseStatement specialization class allows creation of OPTIMIZE TABLE statements.
 */
final class DatabaseOptimize extends DatabaseStatement
{
    /**
     * Creates a new DatabaseOptimize statement on table $table.
     *
     * @see Database::optimize()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, string $table)
    {
        parent::__construct($db, 'OPTIMIZE TABLE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    /**
     * Returns the parts statement structure for this specialized statement.
     *
     * @see DatabaseStatement::getStatementStructure()
     * @return array
     */
    protected function getStatementStructure(): array
    {
        return [
            'statement',
            'table',
        ];
    }
}
