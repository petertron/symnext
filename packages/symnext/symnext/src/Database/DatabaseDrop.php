<?php

/**
 * @package Database
 */

namespace Symnext\Database;

/**
 * This DatabaseStatement specialization class allows creation of DROP TABLE statements.
 */
final class DatabaseDrop extends DatabaseStatement
{
    /**
     * Creates a new DatabaseDrop statement on table $table, with an optional
     * optimizer value.
     *
     * @see Database::drop()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, string $table)
    {
        parent::__construct($db, 'DROP TABLE');
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
            'optimizer',
            'table',
        ];
    }

    /**
     * Currently, only IF EXISTS is supported->ifExists()
     *
     * @return DatabaseDrop
     *  The current instance
     */
    public function ifExists(): self
    {
        return $this->unsafeAppendSQLPart('optimizer', 'IF EXISTS');
    }

    /**
     * Appends the name of the table to drop.
     *
     * @param string $table
     * @return DatabaseDrop
     *  The current instance
     */
    public function table(string $table): self
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', self::LIST_DELIMITER . $table);
        return $this;
    }
}
