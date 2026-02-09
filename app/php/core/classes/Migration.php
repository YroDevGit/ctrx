<?php

namespace Classes;

use Classes\DB;
use PDOException;

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

    public static function query($query){
        $pdo = pdo($db);
        echo "\nRunning Migration query....";
        echo "\n\n";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->rowCount() > 0;
        if($result){
            echo "✔️ Query has been executed.\n";
            return true;
        }else{
            return false;
        }
    }

    public static function table(string $tablename, array $columns, bool $timestamp = false, $active = false): bool
    {
        $db = getenv("database");
        if (empty($db)) {
            self::setLastQuery("No database found in (.env)");
            echo "❌ No Database found in .env\n";
            return false;
        }

        if ($timestamp) {
            $columns['created_at'] = "datetime";
            $columns['updated_at'] = "datetime";
        }
        if($active){
            $columns['active'] = ["int"=>1, "default"=>1];
        }

        $pdo = pdo($db);

        $stmt = $pdo->prepare("SHOW TABLES LIKE '$tablename'");
        $stmt->execute();
        $tableExists = $stmt->rowCount() > 0;

        if (!$tableExists) {
            $colDefs = [];
            $primaryKeys = [];

            foreach ($columns as $colName => $definition) {
                if ($definition == "@primary" || $definition == "@primarykey" || $definition == "@main" || $definition == "@pk") {
                    $definition = ["int" => 20, "primary key", "auto_increment"];
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
                $colDefs[] = "PRIMARY KEY (`" . implode("`, `", $primaryKeys) . "`)";
            }

            $query = "CREATE TABLE IF NOT EXISTS `$tablename` (\n    " . implode(",\n    ", $colDefs) . "\n);";
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
            $existingCols = [];
            $colStmt = $pdo->prepare("
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ");
            $colStmt->execute([$db, $tablename]);
            while ($row = $colStmt->fetch(\PDO::FETCH_ASSOC)) {
                $existingCols[$row['COLUMN_NAME']] = $row;
            }

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

                if (!isset($existingCols[$colName])) {
                    $alterQuery = "ALTER TABLE `$tablename` ADD COLUMN $newDefStr";
                    self::setLastQuery($alterQuery);
                    try {
                        $pdo->exec($alterQuery);
                    } catch (PDOException $e) {
                        echo "❌ " . $e->getMessage() . "\n";
                    }
                } else {
                    if (!self::isColumnEqual($existingCols[$colName], $newDefinition)) {
                        $alterQuery = "ALTER TABLE `$tablename` MODIFY COLUMN $newDefStr";
                        self::setLastQuery($alterQuery);
                        try {
                            $pdo->exec($alterQuery);
                        } catch (PDOException $e) {
                            echo "❌ " . $e->getMessage() . "\n";
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

    private static function buildColumnDefinition(string $colName, $definition, bool $includeNullDefault = true): string
    {
        if (is_string($definition)) {
            if (strtolower($definition) == "varchar") {
                $length = self::$varcharDefaultLenght;
                return "`$colName` $definition($length)";
            }
            if (strtolower($definition) == "int") {
                $length = self::$intDefaultLenght;
                return "`$colName` $definition($length)";
            }
            return "`$colName` $definition";
        }

        $typeParts = [];
        $extras = [];
        $nullSpecified = false;

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


        return "`$colName` " . implode(" ", array_merge($typeParts, $extras));
    }

    private static function isColumnEqual(array $existingCol, $newDefinition): bool
    {
        $existingType = strtolower($existingCol['COLUMN_TYPE']);
        $existingNullable = $existingCol['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
        $existingDefault = $existingCol['COLUMN_DEFAULT'];
        $existingExtra = strtolower($existingCol['EXTRA']);

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
}
