<?php
namespace Classes;
use PDO;

class SQLite
{
    private static ?PDO $pdo = null;

    /**
     * Connect to SQLite database
     */
    public static function connect(
        string $database = "app/php/db/ctrx.db"
    ): PDO {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                'sqlite:' . $database
            );

            self::$pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            self::$pdo->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

            self::$pdo->exec(
                'PRAGMA foreign_keys = ON'
            );
        }

        return self::$pdo;
    }

    /**
     * Get PDO connection
     */
    public static function connection(): PDO
    {
        return self::connect();
    }

    /**
     * Execute prepared SQL query
     */
    public static function query(
        string $sql,
        array $params = []
    ) {
        $stmt = self::connect()->prepare($sql);

        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Get multiple rows
     */
    public static function get(
        string $sql,
        array $params = []
    ): array {
        return self::query(
            $sql,
            $params
        )->fetchAll();
    }

    /**
     * Get one row
     */
    public static function first(
        string $sql,
        array $params = []
    ): array|false {
        return self::query(
            $sql,
            $params
        )->fetch();
    }

    /**
     * Get a single value
     */
    public static function value(
        string $sql,
        array $params = []
    ): mixed {
        return self::query(
            $sql,
            $params
        )->fetchColumn();
    }

    /**
     * Insert row
     */
    public static function insert(
        string $table,
        array $data
    ): int {

        $columns = array_keys($data);

        $fields = implode(
            ', ',
            $columns
        );

        $placeholders = implode(
            ', ',
            array_map(
                fn($column) => ':' . $column,
                $columns
            )
        );

        $sql = "
            INSERT INTO {$table}
            ({$fields})
            VALUES
            ({$placeholders})
        ";

        self::query(
            $sql,
            $data
        );

        return (int) self::connect()
            ->lastInsertId();
    }

    /**
     * Update rows
     */
    public static function update(
        string $table,
        array $data,
        string $where,
        array $whereParams = []
    ): int {

        $set = [];

        foreach ($data as $column => $value) {
            $set[] = "{$column} = :set_{$column}";
        }

        $params = [];

        foreach ($data as $column => $value) {
            $params["set_{$column}"] = $value;
        }

        $params = array_merge(
            $params,
            $whereParams
        );

        $sql = "
            UPDATE {$table}
            SET " . implode(', ', $set) . "
            WHERE {$where}
        ";

        return self::query(
            $sql,
            $params
        )->rowCount();
    }

    /**
     * Delete rows
     */
    public static function delete(
        string $table,
        string $where,
        array $params = []
    ): int {

        $sql = "
            DELETE FROM {$table}
            WHERE {$where}
        ";

        return self::query(
            $sql,
            $params
        )->rowCount();
    }

    /**
     * Count rows
     */
    public static function count(
        string $table,
        string $where = '1 = 1',
        array $params = []
    ): int {

        return (int) self::value(
            "SELECT COUNT(*) FROM {$table} WHERE {$where}",
            $params
        );
    }

    /**
     * Create table
     */
    public static function createTable(
        string $table,
        string $definition
    ): bool {

        self::connect()->exec(
            "CREATE TABLE IF NOT EXISTS {$table} ({$definition})"
        );

        return true;
    }

    /**
     * Drop table
     */
    public static function dropTable(
        string $table
    ): bool {

        self::connect()->exec(
            "DROP TABLE IF EXISTS {$table}"
        );

        return true;
    }

    /**
     * Check if table exists
     */
    public static function tableExists(
        string $table
    ): bool {

        return (bool) self::value(
            "
            SELECT COUNT(*)
            FROM sqlite_master
            WHERE type = 'table'
            AND name = ?
            ",
            [$table]
        );
    }

    /**
     * Get all tables
     */
    public static function tables(): array
    {
        return self::get(
            "
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
            ORDER BY name
            "
        );
    }

    /**
     * Execute raw SQL
     */
    public static function exec(
        string $sql
    ): int|false {
        return self::connect()->exec($sql);
    }

    /**
     * Start transaction
     */
    public static function begin(): bool
    {
        return self::connect()
            ->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::connect()
            ->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::connect()
            ->rollBack();
    }

    /**
     * Get last inserted ID
     */
    public static function lastInsertId(): string
    {
        return self::connect()
            ->lastInsertId();
    }
}