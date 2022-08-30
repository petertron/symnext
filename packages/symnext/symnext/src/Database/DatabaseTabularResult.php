<?php

/**
 * @package Database
 */

namespace Symnext\Database;

use Symnext\Toolkit\ArrayReducer;
use PDO, PDOStatement, Traversable, IteratorAggregate;

/**
 * This class hold the data created by the execution of a specialized DatabaseStatement classes
 * that returns tabular data.
 * It implements the IteratorAggregate interface but also provides its own API with more control
 * built in.
 */
class DatabaseTabularResult
    extends DatabaseStatementResult implements IteratorAggregate
{
    /**
     * The read offset.
     * @var int
     */
    private $offset = 0;

    /**
     * The type of variable that should be returned.
     * @var int
     */
    private $type = PDO::FETCH_ASSOC;

    /**
     * The orientation of the offset.
     * @var int
     */
    private $orientation = PDO::FETCH_ORI_NEXT;

    /**
     * If the cursor reached the end
     * @var boolean
     */
    private $eof = false;

    /**
     * The current cursor position
     * @var integer
     */
    private $position = -1;

    /**
     * Implements the IteratorAggregate getIterator function by delegating it to
     * the PDOStatement.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return $this->statement();
    }

    /**
     * Sets the offset value
     *
     * @param int $offset
     *  A positive number by which to limit the number of results
     * @return DatabaseTabularResult
     *  The current instance
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Sets the type of the returned structure value.
     *
     * @param int $type
     *  The type to use
     *  Either PDO::FETCH_ASSOC or PDO::FETCH_OBJ
     * @throws DatabaseStatementException
     * @return DatabaseTabularResult
     *  The current instance
     */
    public function type(int $type): self
    {
        if ($type !== PDO::FETCH_ASSOC && $type !== PDO::FETCH_OBJ) {
            throw new DatabaseStatementException('Invalid fetch type');
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Sets the orientation value, which controls the way the offset is applied.
     *
     * @param int $orientation
     *  The orientation value to use.
     *  Either PDO::FETCH_ORI_NEXT or PDO::FETCH_ORI_ABS
     * @throws DatabaseStatementException
     * @return DatabaseTabularResult
     *  The current instance
     */
    public function orientation(int $orientation): self
    {
        if (
            $orientation !== PDO::FETCH_ORI_NEXT && $orientation !== PDO::FETCH_ORI_ABS) {
            throw new DatabaseStatementException('Invalid orientation type');
        }
        $this->orientation = $orientation;
        return $this;
    }

    /**
     * Retrieves the record at the current offset, if available.
     * It also advances the current offset in the specified orientation.
     * The record will be either an array or an object depending on the specified type.
     *
     * @see type()
     * @see orientation()
     * @see offset()
     * @see process()
     * @throws DatabaseStatementException
     * @return array|object
     *  The next available record.
     *  null if there are no more available record or an error happened.
     *  After the first null return, the cursor is marked as EOF.
     *  Subsequent calls will throw an exception.
     */
    public function next()#: array|object|null
    {
        if ($this->eof) {
            throw new DatabaseStatementException('Can not call next() after the cursor reached the end');
        }
        $next = $this->statement()->fetch(
            $this->type,
            $this->orientation,
            $this->offset
        );
        $this->position++;
        if ($next === false) {
            $this->eof = true;
            return null;
        }
        return $this->process($next);
    }

    /**
     * Processes the value coming from the database and allows sub-classes
     * to return a different value for each row coming out of the database.
     * The default implementation simply returns the $entry without any modification.
     *
     * @param object|array $entry
     *  The array or object returned from the database
     * @return object|array
     */
    protected function process(object|array $entry): object|array
    {
        return $entry;
    }

    /**
     * Retrieves all remaining rows.
     *
     * @uses next()
     * @see rows()
     * @see type()
     * @see orientation()
     * @see offset()
     * @throws DatabaseStatementException
     * @return array
     *  An array of objects or arrays
     */
    public function remainingRows(): array
    {
        $rows = [];
        while ($row = $this->next()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Retrieves all rows, by making sure no records were read prior to this call.
     *
     * @see remainingRows()
     * @see type()
     * @see orientation()
     * @see offset()
     * @throws DatabaseStatementException
     * @return array
     *  An array of objects or arrays
     */
    public function rows(): array
    {
        if ($this->position !== -1) {
            $consumed = $this->position + 1;
            throw new DatabaseStatementException("Can not retrieve all rows, $consumed were already consumed");
        }
        return $this->remainingRows();
    }

    /**
     * Retrieves the number of available columns in each record.
     *
     * @return int
     *  The number of available columns
     */
    public function columnCount(): int
    {
        $this->statement()->columnCount();
    }

    /**
     * Creates a new reducer for all remaining rows.
     *
     * @uses remainingRows()
     * @throws DatabaseStatementException
     * @return ArrayReducer
     *  A newly created ArrayReducer object
     */
    public function reducer(): ArrayReducer
    {
        return new ArrayReducer(
            $this->remainingRows(),
            $this->type === PDO::FETCH_ASSOC
        );
    }

    /**
     * Retrieves all values for all rows for the specified column.
     *
     * @uses ArrayReducer::column
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return array
     *  An array containing all the values for the specified column
     */
    public function column(string|int $col): array
    {
        return $this->reducer()->column($col);
    }

    /**
     * Retrieves all available rows, indexed with the values of the
     * specified column. The value of the column must be unique.
     *
     * @uses ArrayReducer::rowsIndexedByColumn
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return array
     *  An array of rows containing all the values indexed by the specified column
     */
    public function rowsIndexedByColumn(string|int $col): array
    {
        return $this->reducer()->rowsIndexedByColumn($col);
    }

    /**
     * Retrieves all available rows, grouped with the values of the
     * specified column.
     *
     * @uses ArrayReducer::rowsGroupedByColumn
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return array
     *  An array of arrays containing all the values grouped by the specified column
     */
    public function rowsGroupedByColumn(string|int $col): array
    {
        return $this->reducer()->rowsGroupedByColumn($col);
    }

    /**
     * Retrieve the value of the specified column in the next available record.
     * Note: this method can return null even if there are more records.
     *
     * @uses ArrayReducer::variable
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return mixed
     *  The value of the column
     */
    public function variable(string|int $col)
    {
        return $this->reducer()->variable($col);
    }

    /**
     * int returning version of variable()
     *
     * @uses ArrayReducer::integer
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return int
     *  The value of the column
     */
    public function integer(string|int $col): int
    {
        return $this->reducer()->integer($col);
    }

    /**
     * float returning version of variable()
     *
     * @uses ArrayReducer::float
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return float
     *  The value of the column
     */
    public function float(string|int $col): float
    {
        return $this->reducer()->float($col);
    }

    /**
     * float returning version of variable()
     * If it is a bool, returns it as is.
     * If is is a string, checks for 'yes', 'true' and '1'.
     * If it is an int, returns true is it is not equal to 0.
     * Otherwise, returns false.
     *
     * @uses ArrayReducer::boolean
     * @see variable()
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return bool
     *  The value of the column
     */
    public function boolean(string|int $col): bool
    {
        return $this->reducer()->boolean($col);
    }

    /**
     * string returning version of variable()
     *
     * @uses ArrayReducer::string
     * @see variable()
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return string
     *  The value of the column
     */
    public function string(string|int $col): string
    {
        return $this->reducer()->string($col);
    }
}
