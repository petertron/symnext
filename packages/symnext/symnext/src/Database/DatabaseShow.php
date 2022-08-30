<?php

/**
 * @package Database
 */

namespace Symnext\Database;

use PDOStatement;

/**
 * This DatabaseStatement specialization class allows creation of SHOW TABLES/COLUMNS statements.
 */
final class DatabaseShow extends DatabaseStatement
{
    use DatabaseWhereDefinition;
    use DatabaseCacheableExecutionDefinition;

    /**
     * Creates a new DatabaseSet statement on table $table.
     *
     * @see Database::show()
     * @param Database $db
     *  The underlying database connection.
     * @param string $show
     *  Configure what to show, either TABLES, COLUMNS or INDEX. Defaults to TABLES.
     * @param string $modifier
     *  Configure the statement to output either FULL or EXTENDED information.
     */
    public function __construct(
        Database $db,
        string $show = 'TABLES',
        string $modifier = null
    )
    {
        if (!in_array($show, ['TABLES', 'COLUMNS', 'INDEX'])) {
            throw new DatabaseStatementException('Can only show TABLES, COLUMNS or INDEX');
        }
        if ($modifier) {
            if (!in_array($modifier, ['FULL', 'EXTENDED'])) {
                throw new DatabaseStatementException('Can modify with FULL or EXTENDED');
            } /*else {
                $show = "$modifier $show";
            }*/
        }
        parent::__construct($db, "SHOW $modifier $show");
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
            'like',
            'where',
        ];
    }

    /**
     * Gets the proper separator string for the given $type SQL part type, when
     * generating a formatted SQL statement.
     *
     * @see DatabaseStatement::getSeparatorForPartType()
     * @param string $type
     *  The SQL part type.
     * @return string
     *  The string to use to separate the formatted SQL parts.
     */
    public function getSeparatorForPartType(string $type): string
    {
        if (in_array($type, ['like', 'where'])) {
            return self::FORMATTED_PART_DELIMITER;
        }
        return self::STATEMENTS_DELIMITER;
    }

    /**
     * Appends a FROM `table` clause.
     * Can only be called once in the lifetime of the object.
     *
     * @see alias()
     * @throws DatabaseStatementException
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @return DatabaseShow
     *  The current instance
     */
    public function from(string $table): self
    {
        if ($this->containsSQLParts('table')) {
            throw new DatabaseStatementException('DatabaseShow can not hold more than one table clause');
        }
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', "FROM $table");
        return $this;
    }

    /**
     * Appends a LIKE clause.
     * This clause will likely be a table name, so it calls replaceTablePrefix().
     * Can only be called once in the lifetime of the object.
     * Uses the named parameter 'like' to hold the value when not using placeholders.
     *
     * @see replaceTablePrefix()
     * @throws DatabaseStatementException
     * @param string $value
     *  The LIKE search pattern to look for
     * @return DatabaseShow
     *  The current instance
     */
    public function like(string $value): self
    {
        if ($this->containsSQLParts('like')) {
            throw new DatabaseStatementException('DatabaseShow can not hold more than one like clause');
        }
        $value = $this->replaceTablePrefix($value);
        if ($this->isUsingPlaceholders()) {
            $this->appendValues([$value]);
            $this->unsafeAppendSQLPart('like', 'LIKE ?');
        } else {
            $this->appendValues(['like' => $value]);
            $this->unsafeAppendSQLPart('like', 'LIKE :like');
        }
        return $this;
    }

    /**
     * Appends one or multiple WHERE clauses.
     * Calling this method multiple times will join the WHERE clauses with a AND.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseShow
     *  The current instance
     */
    public function where(array $conditions): self
    {
        $op = $this->containsSQLParts('where') ? 'AND' : 'WHERE';
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "$op $where");
        return $this;
    }

    /**
     * Creates a specialized version of DatabaseStatementResult to hold
     * result from the current statement.
     *
     * @see DatabaseStatement::execute()
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return DatabaseTabularResult
     *  The wrapped result
     */
    public function results(
        bool $success,
        PDOStatement $stm
    ): DatabaseTabularResult
    {
        return new DatabaseTabularResult($success, $stm);
    }
}
