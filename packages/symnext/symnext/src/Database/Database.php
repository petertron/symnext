<?php

/**
 * @package Database
 */

namespace Symnext\Database;

use PDO, PDOException;
use DateTime, DateTimeZone;
use Symnext\Core\App;
use Symnext\Toolkit\General;

/**
 * The Database class acts as a wrapper for connecting to the Database in
 * Symnext.
 *
 * It provides many methods that maps directly to their PDO equivalent.
 * It also provides many factory methods to help developers creates instances
 * of `DatabaseStatement` and their specialized child classes.
 *
 * Symnext uses a prefix to namespace it's tables in a database, allowing it
 * play nice with other applications installed on the database.
 *
 * An error that occur during a query throw a `DatabaseException`.
 * By default, Symnext logs all queries to be used for Profiling and Debug
 * devkit extensions when a Developer is logged in. When a developer is not
 * logged in, all queries and errors are made available with delegates.
 */
class Database
{
    /**
     * An instance of the current PDO object
     *
     * @var PDO
     */
    private $conn = null;

    /**
     * The array of log messages
     *
     * @var array
     */
    private $log = [];

    /**
     * The number of queries this class has executed, defaults to 0.
     *
     * @var int
     */
    private $queryCount = 0;

    /**
     * The default configuration values
     *
     * @var array
     */
    private $config = [
        'host' => null,
        'port' => null,
        'user' => null,
        'password' => null,
        'database' => null,
        'driver' => null,
        'charset' => null,
        'collate' => null,
        'engine' => null,
        'table_prefix' => null,
        'query_caching' => null,
        'query_logging' => null,
        'options' => [],
    ];

    /**
     * The DatabaseCache instance
     *
     * @var DatabaseCache
     */
    private $cache;

    /**
     * The last executed query
     * @var string;
     */
    private $lastQuery;

    /**
     * The md5 hash of the last executed query
     * @var string;
     */
    private $lastQueryHash;

    /**
     * The values used with the last executed query
     * @var array
     */
    private $lastQueryValues;

    /**
     * The unsafe mode of the last executed query
     * @var bool
     */
    private $lastQuerySafe;

    /**
     * The version of the SQL server
     * @var string
     */
    private $version;

    /**
     * Creates a new Database object given an associative array of configuration
     * parameters in `$config`, which should include
     * `driver`, `host`, `port`, `user`, `password`, `db` and an optional
     * array of PDO options in `options`.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->cache = new DatabaseCache;
    }

    /**
     * Magic function that will flush the logs and close the underlying database
     * connection when the Database class is destroyed.
     *
     * @link http://php.net/manual/en/language.oop5.decon.php
     */
    public function __destruct()
    {
        unset($this->conn);
        $this->flush();
    }

    /**
     * Getter for all the log entries.
     *
     * @return array
     */
    public function getLogs(): array
    {
        return $this->log;
    }

    /**
     * Resets `$this->lastQuery`, `$this->lastQueryHash`, `$this->lastQueryValues` and
     * `$this->lastQuerySafe` to their empty values.
     * Called on each query and when the class is destroyed.
     *
     * @return Database
     *  The current instance.
     */
    public function flush(): Database
    {
        $this->lastQuery = null;
        $this->lastQueryHash = null;
        $this->lastQueryValues = null;
        $this->lastQuerySafe = null;
        $this->_lastResult = null; // deprecated
        return $this;
    }

    /**
     * Based on the configuration values set in the constructor,
     * this method will properly format the values to get a valid DSN
     * connection string.
     *
     * @return string
     *  The generated DNS connection string
     */
    public function getDSN(): string
    {
        $config = &$this->config;
        if ($config['host'] === 'unix_socket') {
           return sprintf(
                '%s:unix_socket=%s;dbname=%s;charset=%s',
                $config['driver'],
                General::intval($config['port']) === -1? $config['port'] : '',
                $config['database'],
                $config['charset']
            );
        }
        return sprintf(
            '%s:dbname=%s;host=%s;port=%d;charset=%s',
            $config['driver'],
            $config['database'],
            $config['host'],
            General::intval($config['port']),
            $config['charset']
        );
    }

    /**
     * Getter for the version of the SQL server.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Creates a PDO connection to the desired database given the current config.
     * This will also set the error mode to be exceptions,
     * which are handled by this class.
     *
     * @link http://www.php.net/manual/en/pdo.drivers.php
     * @param array $options
     * @return Database
     *  The current instance if connection was successful.
     * @throws DatabaseException
     */
    public function connect(): self
    {
        try {
            $config = $this->config;
            $this->conn = new PDO(
                $this->getDSN(),
                $config['user'],
                $config['password'],
                is_array($config['options']) ? $config['options'] : []
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $this->version = $this->select(['VERSION()'])->execute()->string(0);
        } catch (PDOException $ex) {
            $this->throwDatabaseError($ex);
        }

        return $this;
    }

    /**
     * Checks if the connection was already made successfully.
     *
     * @return boolean
     *  true if the connection was made, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->conn and $this->conn instanceof PDO;
    }

    /**
     * Issues a call to `connect()` if the current instance is not already
     * connected. Does nothing if already connected.
     *
     * @see isConnected()
     * @return Database
     *  The current instance.
     * @throws DatabaseException
     */
    private function autoConnect(): self
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this;
    }

    /**
     * Returns the number of queries that has been executed since
     * the creation of the object.
     *
     * @return int
     *  The total number of query executed.
     */
    public function queryCount(): int
    {
        return $this->queryCount;
    }

    /**
     * Returns boolean if query caching is enabled or not.
     *
     * @deprecated The query cache is deprecated as of MySQL 5.7.20,
     * and is removed in MySQL 8.0.
     * @link https://dev.mysql.com/doc/refman/5.7/en/query-cache-in-select.html
     * @return boolean
     */
    public function isCachingEnabled(): bool
    {
        return in_array($this->config['query_caching'], ['on', true], true);
    }

    /**
     * @internal Returns the DatabaseCache instance tied to this Database instance.
     *
     * @return DatabaseCache
     */
    public function getCache(): DatabaseCache
    {
        return $this->cache;
    }

    /**
     * Symnext uses a prefix for all it's database tables so it can live peacefully
     * on the same database as other applications. By default this is sym_, but it
     * can be changed when Symnext is installed.
     *
     * @param string $prefix
     *  The table prefix for Symnext, by default this is sym_
     * @return Database
     *  The current instance
     */
    public function setPrefix(string $prefix): Database
    {
        $this->config['table_prefix'] = $prefix;
        return $this;
    }

    /**
     * Returns the prefix used by Symnext for this Database instance.
     *
     * @see __construct()
     * @since Symnext 2.4
     * @return string
     */
    public function getPrefix(): string|null
    {
        return $this->config['table_prefix'];
    }

    /**
     * Sets query logging to true.
     *
     * @return Database
     *  The current instance
     */
    public function enableLogging(): Database
    {
        $this->config['query_logging'] = true;
        return $this;
    }

    /**
     * Sets query logging to false.
     *
     * @return Database
     *  The current instance
     */
    public function disableLogging(): self
    {
        $this->config['query_logging'] = false;
        return $this;
    }

    /**
     * Returns true if logging of queries is enabled.
     *
     * @return boolean
     */
    public function isLoggingEnabled(): bool
    {
        return in_array($this->config['query_logging'], ['on', true], true);
    }

    /**
     * Sets the Database connection to use this timezone instead of the default
     * Database server timezone.
     *
     * @throws DatabaseException
     * @link https://dev.mysql.com/doc/refman/5.6/en/time-zone-support.html
     * @link https://github.com/symphonycms/symphonycms/issues/1726
     * @since Symnext 2.3.3
     * @param string $timezone
     *  PHP's Human readable timezone, such as Australia/Brisbane.
     * @return boolean
     */
    public function setTimeZone(string $timezone = null): bool
    {
        // This should throw, default value should be removed
        if (!$timezone) {
            return true;
        }

        // What is the time now in the install timezone
        $symnext_date = new DateTime('now', new DateTimeZone($timezone));

        // MySQL wants the offset to be in the format +/-H:I, getOffset returns offset in seconds
        $utc = new DateTime('now ' . $symnext_date->getOffset() . ' seconds', new DateTimeZone("UTC"));

        // Get the difference between the symphony install timezone and UTC
        $offset = $symnext_date->diff($utc)->format('%R%H:%I');

        return $this->set('time_zone')
            ->value((string)$offset)
            ->execute()
            ->success();
    }

    /**
     * This function takes `$table` and `$field` names and returns true
     * if the `$table` contains a column named `$field`.
     *
     * @since Symnext 2.3
     * @see describe
     * @link  https://dev.mysql.com/doc/refman/en/describe.html
     * @param string $table
     *  The table name
     * @param string $field
     *  The field name
     * @throws DatabaseException
     * @return boolean
     *  true if `$table` contains `$field`, false otherwise
     */
    public function tableContainsField(
        string $table,
        string $field
    ): bool
    {
        return $this->describe($table)
            ->field($field)
            ->execute()
            ->next() !== null;
}

    /**
     * This function takes `$table` and returns boolean
     * if it exists or not.
     *
     * @since Symnext 2.3.4
     * @see show
     * @link  https://dev.mysql.com/doc/refman/en/show-tables.html
     * @param string $table
     *  The table name
     * @throws DatabaseException
     * @return boolean
     *  true if `$table` exists, false otherwise
     */
    public function tableExists(string $table): bool
    {
        return $this->show()
            ->like($table)
            ->execute()
            ->next() !== null;
    }

    /**
     * Factory method that creates a new, empty statement.
     *
     * @param string $action
     *  The SQL clause name. Default to empty string.
     * @return DatabaseStatement
     */
    public function statement(?string $action = ''): DatabaseStatement
    {
        return new DatabaseStatement($this, $action);
    }

    /**
     * Factory method that creates a new `SELECT ...` statement.
     *
     * @param array $projection
     *  The columns to select.
     *  If no projection gets added, it defaults to `DatabaseQuery::getDefaultProjection()`.
     * @return DatabaseQuery
     */
    public function select(array $projection = []): DatabaseQuery
    {
        return new DatabaseQuery($this, $projection);
    }

    /**
     * Factory method that creates a new `SHOW TABLES` statement.
     *
     * @return DatabaseShow
     */
    public function show(): DatabaseShow
    {
        return new DatabaseShow($this);
    }

    /**
     * Factory method that creates a new `SHOW COLUMNS` statement.
     *
     * @return DatabaseShow
     */
    public function showColumns(): DatabaseShow
    {
        return new DatabaseShow($this, 'COLUMNS');
    }

    /**
     * Factory method that creates a new `SHOW FULL COLUMNS` statement.
     *
     * @return DatabaseShow
     */
    public function showFullColumns(): DatabaseShow
    {
        return new DatabaseShow($this, 'COLUMNS', 'FULL');
    }

    /**
     * Factory method that creates a new `SHOW INDEX` statement.
     *
     * @return DatabaseShow
     */
    public function showIndex(): DatabaseShow
    {
        return new DatabaseShow($this, 'INDEX');
    }

    /**
     * Factory method that creates a new `RENAME TABLE` statement.
     *
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be
     * changed to the Database table prefix.
     * @return DatabaseRename
     */
    public function rename(string $table): DatabaseRename
    {
        return new DatabaseRename($this, $table);
    }

    /**
     * Factory method that creates a new `INSERT` statement.
     *
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be
     * changed to the Database table prefix.
     * @return DatabaseInsert
     */
    public function insert(string $table, array $what): DatabaseInsert
    {
        $stm = (new DatabaseInsert($this, $table))->values($what['values']);
        if (($what['on_duplicate_key_update'] ?? false) === true) {
            $stm->updateOnDuplicateKey();
        }
        return $stm->execute()->success();
    }

    /**
     * Returns the last insert ID from the previous query. This is
     * the value from an auto_increment field.
     * If the lastInsertId is empty or not a valid integer, -1 is returned.
     *
     * @return int
     *  The last interested row's ID
     */
    public function getInsertID(): int
    {
        return General::intval($this->conn->lastInsertId());
    }

    /**
     * Factory method that creates a new `UPDATE` statement.
     *
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be
     * changed to the Database table prefix.
     * @return DatabaseUpdate
     */
    public function update(string $table): DatabaseUpdate
    {
        return new DatabaseUpdate($this, $table);
    }

    /**
     * Factory method that creates a new `DELETE` statement.
     *
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be
     * changed to the Database table prefix.
     * @return DatabaseDelete
     */
    public function delete(string $table,): DatabaseDelete
    {
        return new DatabaseDelete($this, $table);
    }

    /**
     * Factory method that creates a new `DROP` statement.
     *
     * @param string $table
     * @return DatabaseDrop
     */
    public function drop(string $table): DatabaseDrop
    {
        return new DatabaseDrop($this, $table);
    }

    /**
     * Factory method that creates a new `DESCRIBE` statement.
     *
     * @param string $table
     * @return DatabaseDescribe
     */
    public function describe(string $table): DatabaseDescribe
    {
        return new DatabaseDescribe($this, $table);
    }

    /**
     * Factory method that creates a new `CREATE TABLE` statement.
     * Also sets the charset, collate and engine values using the
     * instance configuration.
     *
     * @param string $table
     * @return DatabaseCreate
     */
    public function create(string $table, array $structure): Bool #DatabaseCreate
    {
        return (new DatabaseCreate($this, $table))
            ->fields($structure['fields'] ?? [])
            ->keys($structure['keys'] ?? [])
            ->charset($this->config['charset'])
            ->collate($this->config['collate'])
            ->engine($this->config['engine'])
            ->execute()
            ->success();
    }

    /**
     * Factory method that creates a new `ALTER TABLE` statement.
     * Also sets the collate value using the instance configuration.
     *
     * @param string $table
     * @return DatabaseAlter
     */
    public function alter(string $table): DatabaseAlter
    {
        return (new DatabaseAlter($this, $table))
            ->charset($this->config['charset'])
            ->collate($this->config['collate']);
    }

    /**
     * Factory method that creates a new `OPTIMIZE TABLE` statement.
     *
     * @param string $table
     * @return DatabaseOptimize
     */
    public function optimize(string $table): DatabaseOptimize
    {
        return new DatabaseOptimize($this, $table);
    }

    /**
     * Factory method that creates a new `TRUNCATE TABLE` statement.
     *
     * @param string $table
     * @return DatabaseTruncate
     */
    public function truncate(string $table): DatabaseTruncate
    {
        return new DatabaseTruncate($this, $table);
    }

    /**
     * Factory method that creates a new `SET` statement.
     *
     * @param string $variable
     * @return DatabaseSet
     */
    public function set(string $variable): DatabaseSet
    {
        return new DatabaseSet($this, $variable);
    }

    /**
     * Begins a new transaction.
     * This method calls `autoConnect()` before forwarding the call to PDO.
     *
     * @return boolean
     */
    public function beginTransaction(): bool
    {
        $this->autoConnect();
        return $this->conn->beginTransaction();
    }

    /**
     * Commits the lastly created transaction.
     * This method calls `autoConnect()` before forwarding the call to PDO.
     *
     * @return boolean
     */
    /*public function commit()
    {
        $this->autoConnect();
        return $this->conn->commit();
    }*/

    public function commit(): bool
    {
        $this->autoConnect();
        try {
            return $this->conn->commit();
        } catch (PDOException $ex) {
            return false;
        }
    }

    /**
     * Rollbacks the lastly created transaction.
     * This method calls `autoConnect()` before forwarding the call to PDO.
     *
     * @return boolean
     */
    /*public function rollBack()
    {
        $this->autoConnect();
        return $this->conn->rollBack();
    }*/

    public function rollBack(): bool
    {
        $this->autoConnect();
        try {
            return $this->conn->rollBack();
        } catch (PDOException $ex) {
            return false;
        }
    }

    /**
     * Check if we are currently in a transaction.
     * This method calls `autoConnect()` before forwarding the call to PDO.
     *
     * @return boolean
     */
    public function inTransaction(): bool
    {
        $this->autoConnect();
        return $this->conn->inTransaction();
    }

    /**
     * Factory method that creates a new DatabaseTransaction object.
     * $tx will be called with a single parameter: the instance of the current Database object.
     *
     * @param callable $tx
     *  The code to execute in the transaction
     * @return DatabaseTransaction
     */
    public function transaction($tx): DatabaseTransaction
    {
        return new DatabaseTransaction($this, $tx);
    }

    /**
     * @internal
     * Finds the best possible PDO::PARAM_* value to bind with, based on the PHP type.
     *
     * @param mixed $value
     *  The value on which to deduce its PDO type
     * @return int
     *  Either PDO::PARAM_NULL, PDO::PARAM_INT, PDO::PARAM_BOOL or PDO::PARAM_STR
     */
    public function deducePDOParamType($value): int
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        } elseif (is_string($value)) {
            return PDO::PARAM_STR;
        } elseif (is_numeric($value) and floatval(intval($value)) === floatval($value)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }
        return PDO::PARAM_STR;
    }

    /**
     * Given a DatabaseStatement, it will execute it and return
     * its result, by calling `DatabaseStatement::result()`.
     * Any error will throw a DatabaseException.
     *
     * Developers are encouraged to call `DatabaseStatement::execute()` instead,
     * because it will make sure to set required state properly.
     *
     * @see validateSQLQuery()
     * @see DatabaseStatement::execute()
     * @see DatabaseStatement::result()
     * @param string $query
     * @return DatabaseStatementResult
     * @throws DatabaseException
     */
    public function execute(DatabaseStatement $stm): DatabaseStatementResult|null
    {
        $this->autoConnect();

        if ($this->isLoggingEnabled()) {
            $start = precision_timer();
        }

        $query = $stm->generateSQL();
        $values = $stm->getValues();
        $result = null;

        // Cleanup from last time, set some logging parameters
        $this->flush();
        $this->lastQuery = $stm->generateFormattedSQL();
        $this->lastQueryHash = $stm->computeHash();
        $this->lastQueryValues = $values;
        $this->lastQuerySafe = $stm->isSafe();

        try {
            // Validate the query
            $this->validateSQLQuery($query, $stm->isSafe());
            // Prepare the query
            $pstm = $this->conn->prepare($query);
            // Bind all values
            foreach ($values as $param => $value) {
                if (General::intval($param) !== -1) {
                    $param = $param + 1;
                } else {
                    $param = ":$param";
                }
                $pstm->bindValue($param, $value, $this->deducePDOParamType($value));
            }
            // Execute it
            #var_dump($pstm); die;
            $result = $pstm->execute();
            $this->queryCount++;
        } catch (PDOException $ex) {
            $this->throwDatabaseError($ex);
            return null;
        }

        // Check for errors
        if ($this->conn->errorCode() !== PDO::ERR_NONE) {
            $this->throwDatabaseError();
            return null;
        }

        // Log the query
        if ($this->isLoggingEnabled()) {
            $this->logLastQuery(precision_timer('stop', $start));
        }

        return $stm->results($result, $pstm);
    }

    /**
     * @internal
     * This method checks for common pattern of SQL injection, like `--`, `'`, `"`, and `;`.
     *
     * @see execute()
     * @param string $query
     *  The query to test.
     * @param boolean $strict
     *  Perform extra validation, true by default.
     *  When false, only common patterns like `';--` are checked
     * @return void
     * @throws DatabaseStatementException
     */
    final public function validateSQLQuery(
        string $query,
        bool $strict = true
    ): void
    {
        if (
            strpos($query, '\'--') !== false
            or strpos($query, '\';--') !== false
            or strpos($query, '\' --') !== false
            or strpos($query, '\'/*') !== false
        ) {
            throw (new DatabaseStatementException(
                'Query contains SQL injection.'
            ))->sql($query);
        } elseif ($strict and strpos($query, '--') !== false) {
            throw (new DatabaseStatementException(
                'Query contains illegal characters: `--`.'
            ))->sql($query);
        } elseif ($strict and strpos($query, '\'') !== false) {
            throw (new DatabaseStatementException(
                'Query contains illegal character: `\'`.'
            ))->sql($query);
        } elseif ($strict and strpos($query, '"') !== false) {
            throw (new DatabaseStatementException(
                'Query contains illegal character: `"`.'
            ))->sql($query);
        } elseif ($strict and strpos($query, '#') !== false) {
            throw (new DatabaseStatementException(
                'Query contains illegal character: `#`.'
            ))->sql($query);
        } elseif ($strict and strpos($query, '/*') !== false) {
            throw (new DatabaseStatementException(
                'Query contains illegal character: `/*`.'
            ))->sql($query);
        } elseif ($strict and strpos($query, '*/') !== false) {
            throw (new DatabaseStatementException(
                'Query contains illegal character: `*/`.'
            ))->sql($query);
        } elseif ($strict and strpos($query, ';') !== false) {
            throw (new DatabaseStatementException(
                'Query contains illegal character: `;`.'
            ))->sql($query);
        }
    }

    /**
     * Convenience function to allow you to execute multiple SQL queries at
     * once by providing a string with the queries delimited with a `;`
     *
     * @throws DatabaseException
     * @throws Exception
     * @param string $sql
     *  A string containing SQL queries delimited by `;`
     * @return boolean
     *  If one of the queries fails, false will be returned and no further
     * queries will be executed, otherwise true will be returned.
     */
    public function import(string $sql): bool
    {
        $queries = preg_split('/;[\\r\\n]+/', $sql, -1, PREG_SPLIT_NO_EMPTY);

        if (!is_array($queries) or empty($queries) or count($queries) <= 0) {
            throw new Exception('The SQL string contains no queries.');
        }

        return $this->transaction(function (Database $db) use ($queries) {
            foreach ($queries as $sql) {
                if (trim($sql) !== '') {
                    $stm = $db->statement();
                    $sql = $stm->replaceTablePrefix($sql);
                    $stm->unsafe()->unsafeAppendSQLPart('statement', $sql);
                    if (!$stm->execute()->success()) {
                        throw new DatabaseException(
                            'Failed to execute import statement'
                        );
                    }
                }
            }
        })->execute()->success();
    }

    /**
     * Given an Exception, or called when an error occurs, this function will
     * fire the `QueryExecutionError` delegate and then raise a `DatabaseException`
     *
     * @uses QueryExecutionError
     * @throws DatabaseException
     * @param Exception $ex
     *  The exception thrown while doing something with the Database
     */
    #private function throwDatabaseError(Exception $ex = null)
    private function throwDatabaseError(PDOException $ex = null)
    {
        if (isset($ex) and $ex) {
            $msg = $ex->getMessage();
            $errornum = (int)$ex->getCode();
        } else {
            $error = $this->conn->errorInfo();
            $msg = $error[2];
            $errornum = $error[0];
        }
#echo $msg . PHP_EOL . PHP_EOL;
debug_print_backtrace();
die;
        /**
         * After a query execution has failed this delegate will provide the query,
         * query hash, error message and the error number.
         *
         * Note that this function only starts logging once the `ExtensionManager`
         * is available, which means it will not fire for the first couple of
         * queries that set the character set.
         *
         * @since Symnext 2.3
         * @delegate QueryExecutionError
         * @param string $context
         * '/frontend/' or '/backend/'
         * @param string $query
         *  The query that has just been executed
         * @param string $query_hash
         *  The hash used by Symnext to uniquely identify this query
         * @param string $msg
         *  The error message provided by MySQL which includes information on why the execution failed
         * @param int $num
         *  The error number that corresponds with the MySQL error message
         * @param Exception $exception
         *  @since Symnext 3.0.0
         *  The raised exception, if any
         */
        if (App::ExtensionManager() instanceof \Symnext\Toolkit\ExtensionManager) {
            App::ExtensionManager()->notifyMembers(
                'QueryExecutionError',
                App::getEngineNamespace(),
                [
                    'query' => $this->lastQuery,
                    'query_hash' => $this->lastQueryHash,
                    'msg' => $msg,
                    'num' => $errornum,
                    'exception' => $ex,
                ]
            );
        }

        throw new DatabaseException(
            __(
                'Database Error (%1$s): %2$s in query:%4$s%3$s',
                [$errornum, $msg, $this->lastQuery, PHP_EOL]
            ),
            [
                'msg' => $msg,
                'num' => $errornum,
                'query' => $this->lastQuery
            ],
            $ex
        );
    }

    /**
     * Function is called every time a query is executed to log it for
     * basic profiling/debugging purposes
     *
     * @uses PostQueryExecution
     * @param int $stop
     */
    private function logLastQuery(int $stop)
    {
        /**
         * After a query has successfully executed, that is it was considered
         * valid SQL, this delegate will provide the query, the query_hash and
         * the execution time of the query.
         *
         * Note that this function only starts logging once the ExtensionManager
         * is available, which means it will not fire for the first couple of
         * queries that set the character set.
         *
         * @since Symnext 2.3
         * @delegate PostQueryExecution
         * @param string $context
         * '/frontend/' or '/backend/'
         * @param string $query
         *  The query that has just been executed
         * @param string $query_hash
         *  The hash used by Symnext to uniquely identify this query
         * @param array $query_values
         *  @since Symnext 3.0.0
         *  The values passed by Symnext to the database
         * @param bool $query_safe
         *  @since Symnext 3.0.0
         *  If the query was using the unsafe mode
         * @param float $execution_time
         *  The time that it took to run `$query`
         */
        if (App::ExtensionManager() instanceof ExtensionManager) {
            // TODO: Log unlogged queries
            App::ExtensionManager()->notifyMembers(
                'PostQueryExecution',
                App::getEngineNamespace(),
                [
                    'query' => $this->lastQuery, // TODO: Format
                    'query_hash' => $this->lastQueryHash,
                    'query_values' => $this->lastQueryValues,
                    'query_safe' => $this->lastQuerySafe,
                    'execution_time' => $stop
                ]
            );
        }

        // Keep internal log for easy debugging
        $this->log[] = [
            'query' => $this->lastQuery, // TODO: Format
            'query_hash' => $this->lastQueryHash,
            'query_values' => $this->lastQueryValues,
            'query_safe' => $this->lastQuerySafe,
            'execution_time' => $stop
        ];
    }

    /**
     * Returns some basic statistics from the Database class about the
     * number of queries, the time it took to query and any slow queries.
     * A slow query is defined as one that took longer than 0.0999 seconds
     * This function is used by the Profile devkit
     *
     * @return array
     *  An associative array with the number of queries, an array of slow
     *  queries and the total query time.
     */
    public function getStatistics(): array
    {
        $stats = [];
        $query_timer = 0.0;
        $slow_queries = [];

        foreach ($this->log as $key => $val) {
            $query_timer += $val['execution_time'];
            if ($val['execution_time'] > 0.0999) {
                $slow_queries[] = $val;
            }
        }

        return [
            'queries' => $this->queryCount(),
            'slow-queries' => $slow_queries,
            'total-query-time' => number_format($query_timer, 5, '.', '')
        ];
    }
}
