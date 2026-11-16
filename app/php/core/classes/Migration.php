<?php

namespace Classes;

use Classes\DB;
use PDOException;

include "app/php/core/partials/bin/migration_const.php";

class Migration
{
    /**
     * @author Tyrone Limen Malocon
     * @codeyro
     */
    private static $lastQuery = "";
    private static $varcharDefaultLenght = 50;
    private static $intDefaultLenght = 11;

    public static function setVarcharLength(int $length)
    {
        self::$varcharDefaultLenght = $length;
    }

    public static function setIntLength(int $length)
    {
        self::$intDefaultLenght = $length;
    }

    public static function setLastQuery(string $query): void
    {
        self::$lastQuery = $query;
    }

    public static function getLastQuery(): string
    {
        return self::$lastQuery;
    }

    private static function driver(): string
    {
        $d = strtolower(env("dbdriver") ?? "mysql");
        if ($d === 'mariadb') return 'mysql';
        return $d;
    }

    private static function isPg(): bool
    {
        $d = self::driver();
        return $d === 'pgsql' || $d === 'postgres' || $d === 'postgresql';
    }

    private static function isSqlite(): bool
    {
        return self::driver() === 'sqlite';
    }

    private static function quoteId(string $name): string
    {
        $name = trim($name, '`"');
        if (self::isPg()) {
            return '"' . str_replace('"', '""', $name) . '"';
        }
        return '`' . $name . '`';
    }

    private static function pgType(string $type): string
    {
        $t = strtolower(trim($type));
        if (preg_match('/^int(eger)?(\(\d+\))?$/', $t)) return 'integer';
        if (preg_match('/^bigint(\(\d+\))?$/', $t)) return 'bigint';
        if (preg_match('/^smallint(\(\d+\))?$/', $t)) return 'smallint';
        if (preg_match('/^tinyint(\(\d+\))?$/', $t)) return 'smallint';
        if (preg_match('/^mediumint(\(\d+\))?$/', $t)) return 'integer';
        if (preg_match('/^varchar\((\d+)\)$/', $t, $m)) return 'varchar(' . $m[1] . ')';
        if ($t === 'varchar') return 'varchar(' . self::$varcharDefaultLenght . ')';
        if (preg_match('/^char\((\d+)\)$/', $t, $m)) return 'char(' . $m[1] . ')';
        if (in_array($t, ['text', 'tinytext', 'mediumtext', 'longtext'])) return 'text';
        if (preg_match('/^(decimal|numeric)\((\d+),(\d+)\)$/', $t, $m)) return 'numeric(' . $m[2] . ',' . $m[3] . ')';
        if (preg_match('/^(decimal|numeric)\((\d+)\)$/', $t, $m)) return 'numeric(' . $m[2] . ')';
        if ($t === 'decimal' || $t === 'numeric') return 'numeric';
        if ($t === 'float') return 'real';
        if ($t === 'double' || $t === 'double precision') return 'double precision';
        if ($t === 'datetime' || $t === 'timestamp') return 'timestamp';
        if ($t === 'date') return 'date';
        if ($t === 'time') return 'time';
        if ($t === 'year') return 'integer';
        if ($t === 'tinyint(1)' || $t === 'bool' || $t === 'boolean') return 'boolean';
        if (in_array($t, ['blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary'])) return 'bytea';
        if ($t === 'json') return 'jsonb';
        if ($t === 'enum' || strpos($t, 'enum(') === 0) return 'varchar(255)';
        if ($t === 'set' || strpos($t, 'set(') === 0) return 'text';
        if ($t === 'uuid') return 'uuid';
        if ($t === 'jsonb') return 'jsonb';
        if ($t === 'bytea') return 'bytea';
        if ($t === 'serial' || $t === 'bigserial' || $t === 'smallserial') return $t;
        return $type;
    }

    private static function pgSerialFor(string $type): ?string
    {
        $t = strtolower(trim($type));
        if ($t === 'integer' || $t === 'int' || $t === 'int4') return 'serial';
        if ($t === 'bigint' || $t === 'int8') return 'bigserial';
        if ($t === 'smallint' || $t === 'int2') return 'smallserial';
        return null;
    }

    public static function query($query)
    {
        $db = env("database");
        if (empty($db)) {
            self::setLastQuery("No database found in (.env)");
            echo "❌ No Database found in .env\n";
            return false;
        }
        $pdo = pdo($db);
        if (!self::isPg() && !self::isSqlite()) {
            $pdo->exec("USE $db");
        }
        echo "\nRunning Migration query....";
        echo "\n\n";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->rowCount() > 0;
        if ($result) {
            echo "✔️ Query has been executed.\n";
            return true;
        } else {
            return false;
        }
    }

    public static function table(string $tablename, array $columns, bool $timestamp = false, $active = false): bool
    {
        $db = env("database");
        if (empty($db)) {
            self::setLastQuery("No database found in (.env)");
            echo "❌ No Database found in .env\n";
            return false;
        }

        if ($timestamp) {
            if (self::isPg()) {
                $columns['created_at'] = "TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                $columns['updated_at'] = "TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            } else {
                $columns['created_at'] = "DATETIME DEFAULT CURRENT_TIMESTAMP";
                $columns['updated_at'] = "DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
            }
        }
        if ($active) {
            $columns['active'] = ["int" => 1, "default" => 1];
        }

        $pdo = pdo($db);
        if (!self::isPg() && !self::isSqlite()) {
            $pdo->exec("USE $db");
        }

        $tableExists = self::tableExists($pdo, $tablename);

        if (!$tableExists) {
            $colDefs = [];
            $primaryKeys = [];

            foreach ($columns as $colName => $definition) {
                if ($definition == "@primary" || $definition == "@primarykey" || $definition == "@main" || $definition == "@pk") {
                    $definition = ["int" => 20, "primary key", "auto_increment", "NOT NULL"];
                }
                if (is_array($definition)) {
                    $newDefinition = [];
                    foreach ($definition as $key => $value) {
                        if (is_numeric($key) && strtolower($value) === 'primary key') {
                            $primaryKeys[] = $colName;
                        } else {
                            $newDefinition[$key] = $value;
                        }
                    }
                    $colDefs[] = self::buildColumnDefinition($colName, $newDefinition);
                } else {
                    $colDefs[] = self::buildColumnDefinition($colName, $definition);
                }
            }

            if (!empty($primaryKeys)) {
                $quoted = array_map([self::class, 'quoteId'], $primaryKeys);
                $colDefs[] = "PRIMARY KEY (" . implode(", ", $quoted) . ")";
            }

            $quotedTable = self::quoteId($tablename);
            $query = "CREATE TABLE IF NOT EXISTS $quotedTable (\n    " . implode(",\n    ", $colDefs) . "\n);";
            self::setLastQuery($query);

            try {
                $pdo->exec($query);
                $pdo = null;
                echo "✔️ Table `$tablename` created successfully\n";
                return true;
            } catch (PDOException $e) {
                echo "❌ " . $e->getMessage() . "\n";
                return false;
            }
        } else {
            $existingCols = self::getExistingColumns($pdo, $db, $tablename);

            foreach ($columns as $colName => $definition) {
                if ($definition == "@primary" || $definition == "@primarykey" || $definition == "@main" || $definition == "@pk") {
                    $definition = ["int" => 20, "primary key", "auto_increment"];
                }
                if (is_array($definition)) {
                    $newDefinition = [];
                    foreach ($definition as $key => $value) {
                        if (!(is_numeric($key) && strtolower($value) === 'primary key')) {
                            $newDefinition[$key] = $value;
                        }
                    }
                } else {
                    $newDefinition = $definition;
                }

                $newDefStr = self::buildColumnDefinition($colName, $newDefinition, false);
                $quotedTable = self::quoteId($tablename);

                if (!isset($existingCols[$colName])) {
                    $alterQuery = "ALTER TABLE $quotedTable ADD COLUMN $newDefStr";
                    self::setLastQuery($alterQuery);
                    try {
                        $pdo->exec($alterQuery);
                    } catch (PDOException $e) {
                        echo "❌ " . $e->getMessage() . "\n";
                    }
                } else {
                    if (!self::isColumnEqual($existingCols[$colName], $newDefinition)) {
                        if (self::isPg()) {
                            $pgType = self::pgType(self::extractTypeString($newDefinition));
                            $alterQuery = "ALTER TABLE $quotedTable ALTER COLUMN " . self::quoteId($colName) . " TYPE $pgType";
                            self::setLastQuery($alterQuery);
                            try {
                                $pdo->exec($alterQuery);
                            } catch (PDOException $e) {
                                echo "❌ " . $e->getMessage() . "\n";
                            }
                        } else {
                            $alterQuery = "ALTER TABLE $quotedTable MODIFY COLUMN $newDefStr";
                            self::setLastQuery($alterQuery);
                            try {
                                $pdo->exec($alterQuery);
                            } catch (PDOException $e) {
                                echo "❌ " . $e->getMessage() . "\n";
                            }
                        }
                    }
                }
            }
            echo "✔️ Table '$tablename' created.\n";
            $pdo = null;
            return true;
        }
    }

    public static function table_ts(string $tablename, array $columns, bool $activeColumn = false)
    {
        return self::table($tablename, $columns, true, $activeColumn);
    }

    private static function tableExists($pdo, string $tablename): bool
    {
        if (self::isPg()) {
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
            $stmt->execute([$tablename]);
        } elseif (self::isSqlite()) {
            $stmt = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name = ?");
            $stmt->execute([$tablename]);
        } else {
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
            $stmt->execute([env("database"), $tablename]);
        }
        return $stmt->rowCount() > 0;
    }

    private static function getExistingColumns($pdo, string $db, string $tablename): array
    {
        $existingCols = [];
        if (self::isPg()) {
            $colStmt = $pdo->prepare("
                SELECT column_name             AS \"COLUMN_NAME\",
                       data_type               AS \"COLUMN_TYPE\",
                       is_nullable             AS \"IS_NULLABLE\",
                       column_default          AS \"COLUMN_DEFAULT\",
                       ''                      AS \"EXTRA\",
                       character_maximum_length,
                       numeric_precision,
                       numeric_scale,
                       udt_name
                FROM information_schema.columns
                WHERE table_schema = 'public' AND table_name = ?
                ORDER BY ordinal_position
            ");
            $colStmt->execute([$tablename]);
            while ($row = $colStmt->fetch(\PDO::FETCH_ASSOC)) {
                $existingCols[$row['COLUMN_NAME']] = $row;
            }
        } else {
            $colStmt = $pdo->prepare("
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ");
            $colStmt->execute([$db, $tablename]);
            while ($row = $colStmt->fetch(\PDO::FETCH_ASSOC)) {
                $existingCols[$row['COLUMN_NAME']] = $row;
            }
        }
        return $existingCols;
    }

    private static function extractTypeString($definition): string
    {
        if (is_string($definition)) {
            $def = trim($definition);
            $tokens = preg_split('/\s+/', $def);
            $first = strtolower($tokens[0] ?? '');
            if ($first === 'varchar') return 'varchar(' . self::$varcharDefaultLenght . ')';
            if ($first === 'int' || $first === 'integer') return 'integer';
            if ($first === 'datetime' || $first === 'timestamp') return 'timestamp';
            if ($first === 'auto_increment') return 'serial';
            if (preg_match('/^[a-z]+(\([^)]*\))?$/i', $tokens[0] ?? '')) {
                return $tokens[0];
            }
            return $tokens[0] ?? 'text';
        }
        if (is_array($definition)) {
            foreach ($definition as $key => $value) {
                if (is_string($key) && strtolower($key) !== 'default') {
                    return strtolower($key) . "($value)";
                }
                if (is_numeric($key) && is_string($value)) {
                    $v = strtolower($value);
                    if (in_array($v, ['varchar', 'int', 'integer', 'bigint', 'smallint', 'text', 'boolean', 'datetime', 'timestamp', 'date'])) {
                        return $v;
                    }
                }
            }
        }
        return 'text';
    }

    private static function buildColumnDefinition(string $colName, $definition, bool $includeNullDefault = true): string
    {
        $quotedCol = self::quoteId($colName);

        if (is_string($definition)) {
            $def = trim($definition);

            if (self::isPg()) {
                $lower = strtolower($def);
                if ($lower === 'varchar') {
                    return $quotedCol . " varchar(" . self::$varcharDefaultLenght . ")";
                }
                if ($lower === 'int') {
                    return $quotedCol . " integer";
                }
                if ($lower === 'auto_increment') {
                    return $quotedCol . " serial";
                }
                $translated = self::pgType($def);
                return $quotedCol . " " . $translated;
            }

            if (strtolower($def) == "varchar") {
                $length = self::$varcharDefaultLenght;
                return "$quotedCol $def($length)";
            }
            if (strtolower($def) == "int") {
                $length = self::$intDefaultLenght;
                return "$quotedCol $def($length)";
            }
            return "$quotedCol $def";
        }

        $typeParts = [];
        $extras = [];
        $nullSpecified = false;
        $autoIncrement = false;

        foreach ($definition as $key => $value) {
            if (is_string($key)) {
                $keyLower = strtolower($key);
                if ($keyLower === 'default') {
                    $extras[] = "DEFAULT " . (is_numeric($value) ? $value : "'$value'");
                } else {
                    $typeParts[] = strtoupper($key) . "($value)";
                }
            } elseif (is_numeric($key) && is_string($value)) {
                $valLower = strtolower($value);
                if ($valLower === 'null' || $valLower === 'not null') {
                    $extras[] = strtoupper($value);
                    $nullSpecified = true;
                } elseif ($valLower === 'auto_increment') {
                    $autoIncrement = true;
                } else {
                    $extras[] = strtoupper($value);
                }
            }
        }

        if ($includeNullDefault && !$nullSpecified) {
            $extras[] = "NULL";
        }

        if (empty($typeParts) && isset($definition[0])) {
            $baseType = strtolower($definition[0]);
            if ($baseType === "varchar") {
                $length = self::$varcharDefaultLenght;
                $typeParts[] = "VARCHAR($length)";
            } elseif ($baseType === "int") {
                $length = self::$intDefaultLenght;
                $typeParts[] = "INT($length)";
            } else {
                $typeParts[] = strtoupper($definition[0]);
            }
        }

        $typeStr = implode(" ", $typeParts);

        if (self::isPg()) {
            $pgType = self::pgType($typeStr);
            if ($autoIncrement) {
                $serial = self::pgSerialFor($pgType);
                if ($serial) {
                    $out = "$quotedCol $serial";
                    if ($includeNullDefault && !$nullSpecified) {
                        $out .= " NULL";
                    } elseif ($nullSpecified) {
                        foreach ($extras as $ex) {
                            if ($ex === 'NOT NULL' || $ex === 'NULL') {
                                $out .= " $ex";
                                break;
                            }
                        }
                    }
                    return $out;
                }
            }
            return "$quotedCol " . implode(" ", array_merge([$pgType], $extras));
        }

        return "$quotedCol " . implode(" ", array_merge($typeParts, $extras));
    }

    private static function isColumnEqual(array $existingCol, $newDefinition): bool
    {
        if (self::isPg()) {
            return self::isColumnEqualPg($existingCol, $newDefinition);
        }

        $existingType = strtolower($existingCol['COLUMN_TYPE']);
        $existingNullable = $existingCol['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
        $existingDefault = $existingCol['COLUMN_DEFAULT'];

        $expectedType = '';
        $expectedNullable = 'NULL';
        $expectedDefault = null;

        if (is_string($newDefinition)) {
            $expectedType = strtolower($newDefinition);
        } elseif (is_array($newDefinition)) {
            foreach ($newDefinition as $key => $value) {
                if (is_string($key)) {
                    if (strtolower($key) === 'default') {
                        $expectedDefault = (string)$value;
                    } else {
                        $expectedType = strtolower($key) . "($value)";
                    }
                } elseif (is_numeric($key) && is_string($value)) {
                    if (strtolower($value) === 'not null') {
                        $expectedNullable = 'NOT NULL';
                    } elseif (strtolower($value) === 'null') {
                        $expectedNullable = 'NULL';
                    }
                }
            }
        }

        if (strpos($existingType, $expectedType) === false) {
            return false;
        }

        if ($existingNullable !== $expectedNullable) {
            return false;
        }

        if ($existingDefault !== $expectedDefault) {
            return false;
        }

        return true;
    }

    private static function isColumnEqualPg(array $existingCol, $newDefinition): bool
    {
        $existingRawType = strtolower((string)$existingCol['COLUMN_TYPE']);
        $udt = strtolower((string)($existingCol['udt_name'] ?? ''));

        $existingType = $existingRawType;
        if ($existingRawType === 'character varying') {
            $existingType = 'varchar(' . ($existingCol['character_maximum_length'] ?? self::$varcharDefaultLenght) . ')';
        } elseif ($existingRawType === 'character') {
            $existingType = 'char(' . ($existingCol['character_maximum_length'] ?? 1) . ')';
        } elseif ($existingRawType === 'numeric' || $existingRawType === 'decimal') {
            $p = $existingCol['numeric_precision'];
            $s = $existingCol['numeric_scale'];
            if ($p !== null) {
                $existingType = $s !== null ? "numeric($p,$s)" : "numeric($p)";
            } else {
                $existingType = 'numeric';
            }
        } elseif ($existingRawType === 'integer' || $udt === 'int4') {
            $existingType = 'integer';
        } elseif ($existingRawType === 'bigint' || $udt === 'int8') {
            $existingType = 'bigint';
        } elseif ($existingRawType === 'smallint' || $udt === 'int2') {
            $existingType = 'smallint';
        } elseif ($existingRawType === 'boolean' || $udt === 'bool') {
            $existingType = 'boolean';
        } elseif ($existingRawType === 'timestamp without time zone') {
            $existingType = 'timestamp';
        } elseif ($existingRawType === 'timestamp with time zone') {
            $existingType = 'timestamptz';
        }

        $existingNullable = $existingCol['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
        $existingDefault = $existingCol['COLUMN_DEFAULT'];
        if ($existingDefault !== null && stripos((string)$existingDefault, 'nextval(') === 0) {
            $existingDefault = '__AUTO__';
            $existingType = 'serial';
        }

        $expectedType = '';
        $expectedNullable = 'NULL';
        $expectedDefault = null;
        $expectedAuto = false;

        if (is_string($newDefinition)) {
            $expectedType = strtolower(self::pgType($newDefinition));
        } elseif (is_array($newDefinition)) {
            foreach ($newDefinition as $key => $value) {
                if (is_string($key)) {
                    if (strtolower($key) === 'default') {
                        $expectedDefault = (string)$value;
                    } else {
                        $expectedType = strtolower(self::pgType(strtolower($key) . "($value)"));
                    }
                } elseif (is_numeric($key) && is_string($value)) {
                    $v = strtolower($value);
                    if ($v === 'not null') {
                        $expectedNullable = 'NOT NULL';
                    } elseif ($v === 'null') {
                        $expectedNullable = 'NULL';
                    } elseif ($v === 'auto_increment') {
                        $expectedAuto = true;
                    }
                }
            }
            if ($expectedType === '' && isset($newDefinition[0]) && is_string($newDefinition[0])) {
                $expectedType = strtolower(self::pgType($newDefinition[0]));
            }
        }

        if ($expectedAuto) {
            $serial = self::pgSerialFor($expectedType);
            if ($serial) {
                $expectedType = $serial;
                $expectedDefault = '__AUTO__';
            }
        }

        if ($existingType !== $expectedType) {
            return false;
        }

        if ($existingNullable !== $expectedNullable) {
            return false;
        }

        if ((string)$existingDefault !== (string)$expectedDefault) {
            return false;
        }

        return true;
    }
}