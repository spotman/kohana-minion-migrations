<?php

namespace BetaKiller\Migration;

use Database;
use DB;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class AbstractMigration implements MigrationInterface
{
    const ACTION_NONE        = 'NO ACTION';
    const ACTION_RESTRICT    = 'RESTRICT';
    const ACTION_SET_NULL    = 'SET NULL';
    const ACTION_SET_DEFAULT = 'SET DEFAULT';
    const ACTION_CASCADE     = 'CASCADE';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * BetaKiller\Migration\Migration constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function beforeUp(): void
    {
        // Nothing by default
    }

    /**
     * @inheritDoc
     */
    public function beforeDown(): void
    {
        // Nothing by default
    }

    /**
     * @inheritDoc
     */
    public function afterUp(): void
    {
        // Nothing by default
    }

    /**
     * @inheritDoc
     */
    public function afterDown(): void
    {
        // Nothing by default
    }

    /**
     * @param string      $sql
     * @param int|null    $type
     * @param null|string $db
     */
    protected function runSql(string $sql, ?int $type = null, ?string $db = null): void
    {
        DB::query($type ?? Database::UPDATE, $sql)->execute($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);
    }

    /**
     * @param string      $tableName
     * @param null|string $db
     *
     * @return bool
     */
    protected function tableExists(string $tableName, ?string $db = null): bool
    {
        return $this->tableHasColumn($tableName, '*', $db);
    }

    /**
     * @param string      $tableName
     * @param string      $columnName
     * @param null|string $db
     *
     * @return bool
     */
    protected function tableHasColumn(string $tableName, string $columnName, ?string $db = null): bool
    {
        try {
            DB::select($columnName)->from($tableName)->limit(1)->execute($db)->as_array();

            // Query completed => table and column exists
            return true;
        } /** @noinspection BadExceptionsProcessingInspection */
        catch (Throwable $ignore) {
            // Query failed => table or column is absent
            return false;
        }
    }

    /**
     * @param string      $tableName
     * @param string      $columnName
     * @param             $value
     * @param null|string $db
     *
     * @return bool
     */
    protected function tableHasColumnValue(string $tableName, string $columnName, $value, ?string $db = null): bool
    {
        if (!$this->tableHasColumn($tableName, $columnName)) {
            return false;
        }

        $query = DB::select($columnName)
            ->from($tableName)
            ->where($columnName, '=', $value)
            ->limit(1);

        return (bool)$query->execute($db)->get($columnName);
    }

    /**
     * @param string      $tableNameCurrent
     * @param string      $tableNameNew
     * @param null|string $db
     *
     * @return bool
     */
    protected function renameTable(string $tableNameCurrent, string $tableNameNew, ?string $db = null): bool
    {
        $tableNameCurrentEsc = $this->escapeSqlName($tableNameCurrent);
        $tableNameNewEsc     = $this->escapeSqlName($tableNameNew);

        $query = 'RENAME TABLE '.$tableNameCurrentEsc.' TO '.$tableNameNewEsc;

        $sql = DB::query(null, $query)->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string      $tableName
     * @param string      $columnName
     * @param null|string $db
     *
     * @return bool
     */
    protected function dropTableColumn(string $tableName, string $columnName, ?string $db = null): bool
    {
        $tableNameEsc  = $this->escapeSqlName($tableName);
        $columnNameEsc = $this->escapeSqlName($columnName);

        $query = 'ALTER TABLE '.$tableNameEsc.' DROP COLUMN '.$columnNameEsc;

        $sql = DB::query(null, $query)->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string      $tableName
     * @param string      $columnName
     * @param string      $columnProperty
     * @param null|string $comment
     * @param null|string $db
     *
     * @return bool
     */
    protected function addTableColumn(
        string $tableName,
        string $columnName,
        string $columnProperty,
        ?string $comment = null,
        ?string $db = null
    ): bool {
        $tableNameEsc  = $this->escapeSqlName($tableName);
        $columnNameEsc = $this->escapeSqlName($columnName);

        $query = 'ALTER TABLE '.$tableNameEsc.' ADD COLUMN '.$columnNameEsc.' '.$columnProperty;
        if ($comment) {
            $query .= ' COMMENT :comment';
        }

        $sql = DB::query(null, $query);
        if ($comment) {
            $sql->param(':comment', $comment);
        }
        $sql = $sql->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string      $tableName
     * @param string      $columnName
     * @param string      $columnProperty
     * @param null|string $comment
     * @param null|string $db
     *
     * @return bool
     */
    protected function changeTableColumn(
        string $tableName,
        string $columnName,
        string $columnProperty,
        ?string $comment = null,
        ?string $db = null
    ): bool {
        $tableNameEsc  = $this->escapeSqlName($tableName);
        $columnNameEsc = $this->escapeSqlName($columnName);

        $query = 'ALTER TABLE '.$tableNameEsc.' CHANGE COLUMN '.$columnNameEsc.' '.$columnNameEsc.' '.$columnProperty;
        if ($comment) {
            $query .= ' COMMENT '.$comment;
        }

        $sql = DB::query(null, $query)->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string      $tableName
     * @param string      $indexName
     * @param null|string $db
     *
     * @return bool
     */
    protected function hasTableIndex(string $tableName, string $indexName, ?string $db = null): bool
    {
        $tableNameEsc = $this->escapeSqlName($tableName);

        $query = 'SHOW INDEX FROM '.$tableNameEsc.' WHERE `key_name` = :index';

        $sql = DB::query(null, $query)
            ->param(':index', $indexName)
            ->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string      $indexName
     * @param null|string $db
     *
     * @return bool
     */
    protected function hasTableIndexForeign(string $indexName, ?string $db = null): bool
    {
        $query = 'SELECT * FROM `information_schema`.`TABLE_CONSTRAINTS` WHERE `CONSTRAINT_NAME` = :index';

        $sql = DB::query(null, $query)
            ->param(':index', $indexName)
            ->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string      $tableName
     * @param string      $indexName
     * @param bool        $isForeignKey
     * @param null|string $db
     *
     * @return bool
     */
    private function dropTableIndexAny(string $tableName, string $indexName, $isForeignKey, ?string $db = null): bool
    {
        $tableNameEsc = $this->escapeSqlName($tableName);
        $indexNameEsc = $this->escapeSqlName($indexName);

        $queryAction = 'INDEX';
        if ($isForeignKey) {
            $queryAction = 'FOREIGN KEY';
        }
        $query = 'ALTER TABLE '.$tableNameEsc.' DROP '.$queryAction.' '.$indexNameEsc;

        $sql = DB::query(null, $query)->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string      $tableName
     * @param string      $indexName
     * @param null|string $db
     *
     * @return bool
     */
    protected function dropTableIndex(string $tableName, string $indexName, ?string $db = null): bool
    {
        return $this->dropTableIndexAny($tableName, $indexName, false, $db);
    }

    /**
     * @param string      $tableName
     * @param string      $indexName
     * @param null|string $db
     *
     * @return bool
     */
    protected function dropTableIndexForeign(string $tableName, string $indexName, ?string $db = null): bool
    {
        return $this->dropTableIndexAny($tableName, $indexName, true, $db);
    }

    /**
     * @param string      $tableName
     * @param string      $indexName
     * @param array       $indexFields
     * @param bool|null   $isUnique
     * @param null|string $db
     *
     * @return bool
     */
    private function addTableIndexAny(
        string $tableName,
        string $indexName,
        array $indexFields,
        bool $isUnique = null,
        ?string $db = null
    ): bool {
        $tableNameEsc   = $this->escapeSqlName($tableName);
        $indexNameEsc   = $this->escapeSqlName($indexName);
        $indexFieldsEsc = $this->escapeSqlNames($indexFields);
        $indexFieldsEsc = implode(',', $indexFieldsEsc);

        $queryUnique = '';
        if ($isUnique) {
            $queryUnique = 'UNIQUE';
        }
        $query = 'ALTER TABLE '.$tableNameEsc.' ADD '.$queryUnique.' INDEX '.$indexNameEsc.' ('.$indexFieldsEsc.')';

        $sql = DB::query(null, $query)
            ->param(':index', $indexName)
            ->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string      $tableName
     * @param string      $indexName
     * @param array       $indexFields
     * @param null|string $db
     *
     * @return bool
     */
    protected function addTableIndex(
        string $tableName,
        string $indexName,
        array $indexFields,
        ?string $db = null
    ): bool {
        return $this->addTableIndexAny($tableName, $indexName, $indexFields, false, $db);
    }

    /**
     * @param string      $tableName
     * @param string      $indexName
     * @param array       $indexFields
     * @param null|string $db
     *
     * @return bool
     */
    protected function addTableIndexUnique(
        string $tableName,
        string $indexName,
        array $indexFields,
        ?string $db = null
    ): bool {
        return $this->addTableIndexAny($tableName, $indexName, $indexFields, true, $db);
    }

    /**
     * @param string      $tableName
     * @param string      $indexName
     * @param string[]    $indexFields
     * @param string      $refTableName
     * @param string[]    $refFields
     * @param string      $actionUpdate
     * @param string      $actionDelete
     * @param null|string $db
     *
     * @return bool
     */
    protected function addTableIndexForeign(
        string $tableName,
        string $indexName,
        array $indexFields,
        string $refTableName,
        array $refFields,
        string $actionUpdate,
        string $actionDelete,
        ?string $db = null
    ): bool {
        $tableNameEsc    = $this->escapeSqlName($tableName);
        $indexNameEsc    = $this->escapeSqlName($indexName);
        $indexFieldsEsc  = $this->escapeSqlNames($indexFields);
        $indexFieldsEsc  = implode(',', $indexFieldsEsc);
        $refTableNameEsc = $this->escapeSqlName($refTableName);
        $refFieldsEsc    = $this->escapeSqlNames($refFields);
        $refFieldsEsc    = implode(',', $refFieldsEsc);

        $query = 'ALTER TABLE '.$tableNameEsc
            .' ADD CONSTRAINT '.$indexNameEsc
            .' FOREIGN KEY ('.$indexFieldsEsc.')'
            .' REFERENCES '.$refTableNameEsc.' ('.$refFieldsEsc.')'
            .' ON UPDATE '.$actionUpdate
            .' ON DELETE '.$actionDelete;

        $sql = DB::query(null, $query)->compile($db);

        $this->logger->debug('SQL done: :query', [':query' => $sql]);

        return (bool)DB::query(null, $sql)->execute($db);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function escapeSqlName(string $name): string
    {
        return sprintf('`%s`', $name);
    }

    /**
     * @param string[] $names
     *
     * @return string[]
     */
    public function escapeSqlNames(array $names): array
    {
        $self = $this;
        array_walk($names, function ($name) use ($self) {
            return $self->escapeSqlName($name);
        });

        return $names;
    }

    protected function importDump(string $filename): void
    {
        $sql = file_get_contents($filename);

        if (!$sql) {
            throw new \LogicException(sprintf('Missing dump file %s', $filename));
        }

        $this->runSql($sql);
    }
}
