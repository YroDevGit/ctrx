<?php
include_once "app/php/core/partials/envloader.php";

/**
 * This is CTR-X database management page
 * Where you can manage your entire database
 * Made by CodeYro
 * Modified date: July 1 2026
 */
$dbname = env("database");
if (!$dbname) {
    die("❌ No Database found @ .env");
}
define('DB_HOST', env('dbhost'));
define('DB_NAME', $dbname);
define('DB_USER', env('dbuser'));
define('DB_PASS', env('dbpass'));
define('DB_CHARSET', env('dbcharset'));

function getDBConnection()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
    }
}

function executeQuery($pdo, $sql, $params = [])
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return ['success' => true, 'data' => $stmt];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getTables($pdo)
{
    $result = executeQuery($pdo, "SHOW TABLES");
    if (!$result['success']) return $result;
    $tables = [];
    while ($row = $result['data']->fetch()) {
        $tables[] = reset($row);
    }
    return ['success' => true, 'data' => $tables];
}

function getTableInfo($pdo, $table)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $result = executeQuery($pdo, "SHOW FULL COLUMNS FROM `$table`");
    if (!$result['success']) return $result;
    $columns = [];
    while ($row = $result['data']->fetch()) {
        $columns[] = $row;
    }

    $keyResult = executeQuery($pdo, "SHOW INDEXES FROM `$table`");
    $keys = ['PRIMARY' => [], 'UNIQUE' => []];
    if ($keyResult['success']) {
        while ($row = $keyResult['data']->fetch()) {
            if ($row['Key_name'] == 'PRIMARY') {
                $keys['PRIMARY'][] = $row['Column_name'];
            } elseif ($row['Non_unique'] == 0) {
                $keys['UNIQUE'][$row['Key_name']][] = $row['Column_name'];
            }
        }
    }

    return ['success' => true, 'data' => ['columns' => $columns, 'keys' => $keys]];
}

function getTableData($pdo, $table, $limit = 100)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $result = executeQuery($pdo, "SELECT * FROM `$table` LIMIT $limit");
    if (!$result['success']) return $result;
    $data = [];
    while ($row = $result['data']->fetch()) {
        $data[] = $row;
    }
    return ['success' => true, 'data' => $data];
}

function createTable($pdo, $tableName, $columnDefs)
{
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    $sql = "CREATE TABLE `$tableName` ($columnDefs)";
    $result = executeQuery($pdo, $sql);
    return $result;
}

function dropTable($pdo, $table)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $result = executeQuery($pdo, "DROP TABLE `$table`");
    return $result;
}

function renameTable($pdo, $oldName, $newName)
{
    $oldName = preg_replace('/[^a-zA-Z0-9_]/', '', $oldName);
    $newName = preg_replace('/[^a-zA-Z0-9_]/', '', $newName);
    $result = executeQuery($pdo, "RENAME TABLE `$oldName` TO `$newName`");
    return $result;
}

function addColumn($pdo, $table, $columnDef)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $result = executeQuery($pdo, "ALTER TABLE `$table` ADD COLUMN $columnDef");
    return $result;
}

function removeColumn($pdo, $table, $columnName)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $columnName = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
    $result = executeQuery($pdo, "ALTER TABLE `$table` DROP COLUMN `$columnName`");
    return $result;
}

function renameColumn($pdo, $table, $oldName, $newName)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $oldName = preg_replace('/[^a-zA-Z0-9_]/', '', $oldName);
    $newName = preg_replace('/[^a-zA-Z0-9_]/', '', $newName);

    $info = getTableInfo($pdo, $table);
    if (!$info['success']) return $info;
    $col = null;
    foreach ($info['data']['columns'] as $c) {
        if ($c['Field'] == $oldName) {
            $col = $c;
            break;
        }
    }
    if (!$col) {
        return ['success' => false, 'message' => 'Column not found'];
    }
    $type = $col['Type'];
    $null = $col['Null'] == 'YES' ? '' : 'NOT NULL';
    $default = $col['Default'] !== null ? "DEFAULT '" . addslashes($col['Default']) . "'" : '';
    $extra = $col['Extra'] ? $col['Extra'] : '';
    $sql = "ALTER TABLE `$table` CHANGE `$oldName` `$newName` $type $null $default $extra";
    $result = executeQuery($pdo, $sql);
    return $result;
}

function modifyColumn($pdo, $table, $columnName, $newDef)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $columnName = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
    $result = executeQuery($pdo, "ALTER TABLE `$table` MODIFY `$columnName` $newDef");
    return $result;
}

function setPrimaryKey($pdo, $table, $column)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $result = executeQuery($pdo, "ALTER TABLE `$table` ADD PRIMARY KEY (`$column`)");
    return $result;
}

function setUniqueKey($pdo, $table, $column)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $result = executeQuery($pdo, "ALTER TABLE `$table` ADD UNIQUE (`$column`)");
    return $result;
}

function dropKey($pdo, $table, $keyName)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if (strtoupper($keyName) == 'PRIMARY') {
        $result = executeQuery($pdo, "ALTER TABLE `$table` DROP PRIMARY KEY");
    } else {
        $keyName = preg_replace('/[^a-zA-Z0-9_]/', '', $keyName);
        $result = executeQuery($pdo, "ALTER TABLE `$table` DROP INDEX `$keyName`");
    }
    return $result;
}

function insertRow($pdo, $table, $data)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    $result = executeQuery($pdo, $sql, array_values($data));
    return $result;
}

function updateRow($pdo, $table, $data, $where, $whereValue)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $where = preg_replace('/[^a-zA-Z0-9_]/', '', $where);
    $sets = [];
    $params = [];
    foreach ($data as $col => $val) {
        $col = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        $sets[] = "`$col` = ?";
        $params[] = $val;
    }
    $params[] = $whereValue;
    $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `$where` = ?";
    $result = executeQuery($pdo, $sql, $params);
    return $result;
}

function deleteRow($pdo, $table, $where, $value)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $where = preg_replace('/[^a-zA-Z0-9_]/', '', $where);
    $result = executeQuery($pdo, "DELETE FROM `$table` WHERE `$where` = ?", [$value]);
    return $result;
}

function truncateTable($pdo, $table)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $result = executeQuery($pdo, "TRUNCATE TABLE `$table`");
    return $result;
}

function exportDatabaseSQL($pdo, $tablesWithData = [])
{
    $tablesResult = getTables($pdo);
    if (!$tablesResult['success']) {
        return ['success' => false, 'message' => 'Failed to get tables'];
    }
    
    $allTables = $tablesResult['data'];
    $sql = "-- ============================================\n";
    $sql .= "-- Database Export By CTR-X\n";
    $sql .= "-- Database: " . DB_NAME . "\n";
    $sql .= "-- Export Date: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- CodeYro ". date("Y-m-d") ."\n";
    $sql .= "-- ============================================\n\n";
    $sql .= "-- CREATE DATABASE `".DB_NAME."`;\n";
    $sql .= "-- USE `".DB_NAME."`;\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($allTables as $table) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $createResult = executeQuery($pdo, "SHOW CREATE TABLE `$table`");
        if ($createResult['success']) {
            $row = $createResult['data']->fetch();
            $sql .= "-- Table structure for `$table`\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $row['Create Table'] . ";\n\n";
        }
        
        $includeData = in_array($table, $tablesWithData);
        
        if ($includeData) {
            $dataResult = executeQuery($pdo, "SELECT * FROM `$table`");
            if ($dataResult['success']) {
                $rows = $dataResult['data']->fetchAll();
                if (count($rows) > 0) {
                    $columns = array_keys($rows[0]);
                    $escapedColumns = array_map(function($col) {
                        return "`" . str_replace('`', '``', $col) . "`";
                    }, $columns);
                    $columnList = implode(', ', $escapedColumns);
                    
                    $sql .= "-- Dumping data for table `$table`\n";
                    $sql .= "INSERT INTO `$table` ($columnList) VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $escapedValues = array_map(function($value) use ($pdo) {
                            if ($value === null) {
                                return 'NULL';
                            }
                            return $pdo->quote($value);
                        }, array_values($row));
                        $values[] = "(" . implode(', ', $escapedValues) . ")";
                    }
                    $sql .= implode(",\n", $values) . ";\n\n";
                } else {
                    $sql .= "-- No data for table `$table`\n\n";
                }
            }
        } else {
            $sql .= "-- Skipping data for table `$table` (structure only)\n\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    return ['success' => true, 'sql' => $sql];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $pdo = getDBConnection();
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];

    try {
        switch ($action) {
            case 'getTables':
                $response = getTables($pdo);
                break;
            case 'getTableInfo':
                $response = getTableInfo($pdo, $_POST['table'] ?? '');
                break;
            case 'getTableData':
                $response = getTableData($pdo, $_POST['table'] ?? '');
                break;
            case 'createTable':
                $response = createTable($pdo, $_POST['tableName'] ?? '', $_POST['columns'] ?? '');
                break;
            case 'dropTable':
                $response = dropTable($pdo, $_POST['table'] ?? '');
                break;
            case 'renameTable':
                $response = renameTable($pdo, $_POST['oldName'] ?? '', $_POST['newName'] ?? '');
                break;
            case 'addColumn':
                $response = addColumn($pdo, $_POST['table'] ?? '', $_POST['columnDef'] ?? '');
                break;
            case 'removeColumn':
                $response = removeColumn($pdo, $_POST['table'] ?? '', $_POST['columnName'] ?? '');
                break;
            case 'renameColumn':
                $response = renameColumn($pdo, $_POST['table'] ?? '', $_POST['oldName'] ?? '', $_POST['newName'] ?? '');
                break;
            case 'modifyColumn':
                $response = modifyColumn($pdo, $_POST['table'] ?? '', $_POST['columnName'] ?? '', $_POST['newDef'] ?? '');
                break;
            case 'setPrimaryKey':
                $response = setPrimaryKey($pdo, $_POST['table'] ?? '', $_POST['column'] ?? '');
                break;
            case 'setUniqueKey':
                $response = setUniqueKey($pdo, $_POST['table'] ?? '', $_POST['column'] ?? '');
                break;
            case 'dropKey':
                $response = dropKey($pdo, $_POST['table'] ?? '', $_POST['keyName'] ?? '');
                break;
            case 'insertRow':
                $data = [];
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'col_') === 0) {
                        $colName = substr($key, 4);
                        $data[$colName] = $value;
                    }
                }
                $response = insertRow($pdo, $_POST['table'] ?? '', $data);
                break;
            case 'updateRow':
                $data = [];
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'col_') === 0) {
                        $colName = substr($key, 4);
                        $data[$colName] = $value;
                    }
                }
                $response = updateRow($pdo, $_POST['table'] ?? '', $data, $_POST['whereCol'] ?? '', $_POST['whereVal'] ?? '');
                break;
            case 'deleteRow':
                $response = deleteRow($pdo, $_POST['table'] ?? '', $_POST['whereCol'] ?? '', $_POST['whereVal'] ?? '');
                break;
            case 'truncateTable':
                $response = truncateTable($pdo, $_POST['table'] ?? '');
                break;
            case 'exportSQL':
                $tablesWithData = isset($_POST['tables_with_data']) ? json_decode($_POST['tables_with_data'], true) : [];
                $response = exportDatabaseSQL($pdo, $tablesWithData);
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    if ($action === 'exportSQL' && $response['success']) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . DB_NAME . '_backup_' . date('Y-m-d_H-i-s') . '.sql"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $response['sql'];
        exit;
    }

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager - <?=$dbname?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
            color: #333;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .col-md-3 {
            flex: 0 0 calc(25% - 15px);
            min-width: 250px;
        }

        .col-md-9 {
            flex: 1;
            min-width: 300px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .db-header {
            border-left: 4px solid #0d6efd;
            padding-left: 15px;
        }

        .db-header h2 {
            font-size: 24px;
            font-weight: 700;
        }

        .db-header h2 .text-primary {
            color: #0d6efd;
        }

        .db-header small {
            color: #6c757d;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            border: 1px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s;
            line-height: 1.5;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .btn-primary {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        .btn-primary:hover {
            background: #0b5ed7;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #198754;
            color: white;
            border-color: #198754;
        }

        .btn-success:hover {
            background: #157347;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background: #bb2d3b;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
            border-color: #ffc107;
        }

        .btn-warning:hover {
            background: #ffca2c;
        }

        .btn-info {
            background: #0dcaf0;
            color: #212529;
            border-color: #0dcaf0;
        }

        .btn-info:hover {
            background: #31d2f2;
        }

        .btn-outline-primary {
            background: transparent;
            color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-outline-primary:hover {
            background: #0d6efd;
            color: white;
        }

        .btn-outline-secondary {
            background: transparent;
            color: #6c757d;
            border-color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
        }

        .btn-outline-danger {
            background: transparent;
            color: #dc3545;
            border-color: #dc3545;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }

        .btn-outline-warning {
            background: transparent;
            color: #ffc107;
            border-color: #ffc107;
        }

        .btn-outline-warning:hover {
            background: #ffc107;
            color: #212529;
        }

        .btn-outline-info {
            background: transparent;
            color: #0dcaf0;
            border-color: #0dcaf0;
        }

        .btn-outline-info:hover {
            background: #0dcaf0;
            color: #212529;
        }

        .btn-outline-primary {
            background: transparent;
            color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-outline-primary:hover {
            background: #0d6efd;
            color: white;
        }

        .w-100 {
            width: 100%;
        }

        .mt-2 {
            margin-top: 10px;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .mb-3 {
            margin-bottom: 15px;
        }

        .mb-4 {
            margin-bottom: 20px;
        }

        .mt-3 {
            margin-top: 15px;
        }

        .mt-4 {
            margin-top: 20px;
        }

        .me-2 {
            margin-right: 10px;
        }

        .me-1 {
            margin-right: 5px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-header h5 {
            font-size: 16px;
            font-weight: 600;
        }

        .sidebar {
            min-height: 500px;
        }

        .sidebar .card-header {
            border-bottom: none;
            padding-bottom: 0;
        }

        .table-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .table-list-item {
            display: block;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .table-list-item:hover {
            background: #f0f2f5;
        }

        .table-list-item.active {
            background: #0d6efd;
            color: white;
        }

        .table-list-item .badge {
            float: right;
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .table-list-item.active .badge {
            background: rgba(255, 255, 255, 0.3);
        }

        .table-responsive {
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-weight: 600;
        }

        table td {
            padding: 6px 10px;
            border: 1px solid #dee2e6;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        table tbody tr:nth-child(even):hover {
            background: #e9ecef;
        }

        .badge-key {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 3px;
            margin-left: 4px;
        }

        .badge-key.bg-primary {
            background: #0d6efd;
            color: white;
        }

        .badge-key.bg-info {
            background: #0dcaf0;
            color: #212529;
        }

        .badge-key.bg-secondary {
            background: #6c757d;
            color: white;
        }

        .text-muted {
            color: #6c757d;
        }

        .text-center {
            text-align: center;
        }

        .text-danger {
            color: #dc3545;
        }

        .py-3 {
            padding-top: 15px;
            padding-bottom: 15px;
        }

        .py-5 {
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .display-4 {
            font-size: 48px;
            font-weight: 300;
        }

        .d-block {
            display: block;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }

        .alert-danger {
            background: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }

        .alert-warning {
            background: #fff3cd;
            border-color: #ffecb5;
            color: #664d03;
        }

        .alert-info {
            background: #cff4fc;
            border-color: #b6effb;
            color: #055160;
        }

        .alert-dismissible {
            position: relative;
            padding-right: 40px;
        }

        .alert-dismissible .btn-close {
            position: absolute;
            top: 8px;
            right: 12px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
        }

        .alert-dismissible .btn-close:hover {
            opacity: 1;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 8px;
            max-width: 700px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-lg {
            max-width: 800px;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }

        .modal-header h5 {
            font-size: 18px;
            font-weight: 600;
        }

        .modal-header .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            opacity: 0.6;
            padding: 0 8px;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            position: sticky;
            bottom: 0;
            background: white;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color 0.15s;
        }

        .form-control:focus {
            border-color: #0d6efd;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        textarea.form-control {
            min-height: 100px;
            font-family: monospace;
            resize: vertical;
        }

        .input-group {
            display: flex;
            gap: 5px;
        }

        .input-group .form-control {
            flex: 1;
        }

        .input-group .btn {
            flex-shrink: 0;
        }

        .checkbox-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 8px;
            margin: 10px 0 15px 0;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 4px;
            transition: background 0.15s;
            cursor: pointer;
        }

        .checkbox-item:hover {
            background: #e9ecef;
        }

        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #0d6efd;
        }

        .checkbox-item label {
            cursor: pointer;
            font-size: 13px;
            color: #333;
            margin: 0;
            flex: 1;
        }

        .checkbox-item .table-count {
            font-size: 11px;
            color: #6c757d;
        }

        .select-all-container {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: #e9ecef;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .select-all-container input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #0d6efd;
        }

        .select-all-container label {
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            margin: 0;
            color: #333;
        }

        .select-all-container .hint {
            font-size: 12px;
            color: #6c757d;
            margin-left: auto;
        }

        .spinner-border {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner 0.75s linear infinite;
        }

        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .btn-group .btn {
            border-radius: 4px;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }

        .gap-2 {
            gap: 8px;
        }

        .icon {
            display: inline-block;
            width: 16px;
            text-align: center;
            margin-right: 4px;
        }

        .export-sql-btn {
            margin-left: 10px;
        }

        @media (max-width: 768px) {

            .col-md-3,
            .col-md-9 {
                flex: 0 0 100%;
                min-width: 0;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .btn-group .btn {
                font-size: 11px;
                padding: 3px 6px;
            }

            table {
                font-size: 12px;
            }

            table th,
            table td {
                padding: 4px 6px;
            }

            .export-sql-btn {
                margin-left: 0;
            }

            .checkbox-list {
                grid-template-columns: 1fr;
                max-height: 200px;
            }
        }
    </style>
</head>

<body>

    <div class="container" id="app">
        <div class="header">
            <div class="db-header">
                <h2><span class="icon"></span>Database: <span class="text-primary"><?=$dbname?></span></h2>
                <small>CTRX database management system</small>
            </div>
            <div>
                <button class="btn btn-success btn-sm export-sql-btn" onclick="showExportModal()">
                    <span class="icon">💾</span> Export SQL
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="refreshAll()">
                    <span class="icon">🔄</span> Refresh
                </button>
                <a href="<?=$backpage?>">
                    <button class="btn btn-outline-secondary btn-sm">
                        <span class="icon">🔙</span> Back
                    </button>
                </a>
            </div>
        </div>

        <div id="alertContainer"></div>

        <div class="row">
            <div class="col-md-3">
                <div class="card sidebar">
                    <div class="card-header">
                        <h5><span class="icon">📋</span>Tables</h5>
                        <button class="btn btn-primary btn-sm" onclick="showCreateTableModal()">
                            <span class="icon">➕</span> New
                        </button>
                    </div>
                    <div id="tableList">
                        <div class="text-center text-muted py-3">Loading tables...</div>
                    </div>
                    <hr style="margin: 15px 0;">
                    <div>
                        <button class="btn btn-outline-danger btn-sm w-100" onclick="dropTable()">
                            <span class="icon">🗑️</span> Drop Table
                        </button>
                        <button class="btn btn-outline-secondary btn-sm w-100 mt-2" onclick="showRenameTableModal()">
                            <span class="icon">✏️</span> Rename Table
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card main-content">
                    <div id="tableContent">
                        <div class="text-center text-muted py-5">
                            <div style="font-size: 48px; margin-bottom: 15px;">📊</div>
                            <p>Select a table from the left to manage it</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="exportModal">
        <div class="modal">
            <div class="modal-header">
                <h5><span class="icon">💾</span>Export Database as SQL</h5>
                <button class="btn-close" onclick="closeModal('exportModal')">×</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 12px; color: #6c757d; font-size: 14px;">
                    Select which tables should include data in the export. 
                    <strong>Unchecked tables will export structure only</strong> (no data).
                </p>
                
                <div class="select-all-container">
                    <input type="checkbox" id="selectAllTables" onchange="toggleAllTables()">
                    <label for="selectAllTables">Select All Tables</label>
                    <span class="hint">Include data for all tables</span>
                </div>
                
                <div id="tableCheckboxList" class="checkbox-list">
                </div>
                
                <div style="margin-top: 10px; font-size: 13px; color: #6c757d;">
                    <span id="selectedCount">0</span> tables selected to include data
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('exportModal')">Cancel</button>
                <button class="btn btn-success" onclick="exportSQL()">
                    <span class="icon">💾</span> Export SQL
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="createTableModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h5><span class="icon">➕</span>Create Table</h5>
                <button class="btn-close" onclick="closeModal('createTableModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Table Name</label>
                    <input id="newTableName" class="form-control" placeholder="e.g., products">
                </div>
                <div class="mb-3">
                    <label class="form-label">Column Definitions</label>
                    <textarea id="newTableColumns" class="form-control" rows="6" placeholder="id INT PRIMARY KEY AUTO_INCREMENT,&#10;name VARCHAR(100) NOT NULL,&#10;price DECIMAL(10,2),&#10;created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"></textarea>
                    <small class="text-muted">Separate columns with commas. Use standard MySQL column definitions.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('createTableModal')">Cancel</button>
                <button class="btn btn-primary" onclick="createTable()">Create Table</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="addColumnModal">
        <div class="modal">
            <div class="modal-header">
                <h5><span class="icon">➕</span>Add Column</h5>
                <button class="btn-close" onclick="closeModal('addColumnModal')">×</button>
            </div>
            <div class="modal-body">
                <label class="form-label">Column Definition</label>
                <input id="addColumnDef" class="form-control" placeholder="email VARCHAR(100) NOT NULL">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addColumnModal')">Cancel</button>
                <button class="btn btn-primary" onclick="addColumn()">Add Column</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="removeColumnModal">
        <div class="modal">
            <div class="modal-header">
                <h5><span class="icon">🗑️</span>Remove Column</h5>
                <button class="btn-close" onclick="closeModal('removeColumnModal')">×</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 12px; color: #6c757d; font-size: 14px;">
                    Select a column to remove from table <strong id="removeColumnTableName"></strong>.
                    <span style="color: #dc3545;">⚠️ This action cannot be undone!</span>
                </p>
                
                <label class="form-label">Column Name</label>
                <select id="removeColumnSelect" class="form-control">
                    <option value="">— Select a column —</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('removeColumnModal')">Cancel</button>
                <button class="btn btn-danger" onclick="removeColumn()">
                    <span class="icon">🗑️</span> Remove Column
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="renameColumnModal">
        <div class="modal">
            <div class="modal-header">
                <h5><span class="icon">✏️</span>Rename Column</h5>
                <button class="btn-close" onclick="closeModal('renameColumnModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Current Column Name</label>
                    <input id="renameOldCol" class="form-control" placeholder="old_column_name">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Column Name</label>
                    <input id="renameNewCol" class="form-control" placeholder="new_column_name">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('renameColumnModal')">Cancel</button>
                <button class="btn btn-warning" onclick="renameColumn()">Rename</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modifyColumnModal">
        <div class="modal">
            <div class="modal-header">
                <h5><span class="icon">⚙️</span>Modify Column</h5>
                <button class="btn-close" onclick="closeModal('modifyColumnModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Column Name</label>
                    <input id="modifyColName" class="form-control" placeholder="column_name">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Definition</label>
                    <input id="modifyColDef" class="form-control" placeholder="VARCHAR(255) NOT NULL">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modifyColumnModal')">Cancel</button>
                <button class="btn btn-warning" onclick="modifyColumn()">Modify</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="keyModal">
        <div class="modal">
            <div class="modal-header">
                <h5><span class="icon">🔑</span>Manage Keys</h5>
                <button class="btn-close" onclick="closeModal('keyModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Set PRIMARY KEY</label>
                    <div class="input-group">
                        <input id="pkColumn" class="form-control" placeholder="column_name">
                        <button class="btn btn-primary" onclick="setPrimaryKey()">Set</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Set UNIQUE KEY</label>
                    <div class="input-group">
                        <input id="uniqueColumn" class="form-control" placeholder="column_name">
                        <button class="btn btn-info" onclick="setUniqueKey()">Set</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Drop Key</label>
                    <div class="input-group">
                        <input id="dropKeyName" class="form-control" placeholder="key_name (or PRIMARY)">
                        <button class="btn btn-danger" onclick="dropKey()">Drop</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="renameTableModal">
        <div class="modal">
            <div class="modal-header">
                <h5><span class="icon">✏️</span>Rename Table</h5>
                <button class="btn-close" onclick="closeModal('renameTableModal')">×</button>
            </div>
            <div class="modal-body">
                <label class="form-label">New Table Name</label>
                <input id="renameTableName" class="form-control" placeholder="new_table_name">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('renameTableModal')">Cancel</button>
                <button class="btn btn-warning" onclick="renameTable()">Rename</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="insertRowModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h5><span class="icon">➕</span>Insert Row</h5>
                <button class="btn-close" onclick="closeModal('insertRowModal')">×</button>
            </div>
            <div class="modal-body" id="insertRowFields">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('insertRowModal')">Cancel</button>
                <button class="btn btn-success" onclick="insertRow()">Insert</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="editRowModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h5><span class="icon">✏️</span>Edit Row</h5>
                <button class="btn-close" onclick="closeModal('editRowModal')">×</button>
            </div>
            <div class="modal-body" id="editRowFields">

            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('editRowModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateRow()">Update</button>
            </div>
        </div>
    </div>

    <script>
        let currentTable = null;
        let currentColumns = [];
        let currentKeys = [];
        let tableData = [];
        let allTableNames = [];

        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('show');
            }
        });

        function showAlert(message, type = 'info') {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible`;
            alert.innerHTML = `
        ${message}
        <button class="btn-close" onclick="this.parentElement.remove()">×</button>
    `;
            container.appendChild(alert);
            setTimeout(() => {
                if (alert.parentElement) alert.remove();
            }, 5000);
        }

        function showLoading(element) {
            if (!element) return;
            element.classList.add('loading');
            element.innerHTML = '<div class="text-center py-3"><span class="spinner-border"></span> Loading...</div>';
        }

        function hideLoading(element) {
            if (!element) return;
            element.classList.remove('loading');
        }

        async function apiRequest(action, data = {}) {
            data.action = action;
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const contentType = response.headers.get('Content-Type');
                if (contentType && contentType.includes('application/sql')) {
                    const blob = await response.blob();
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = '<?=$dbname?>_backup_' + new Date().toISOString().slice(0, 19).replace(/[:-]/g, '_') + '.sql';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                    return { success: true, message: 'SQL export started' };
                }
                
                const result = await response.json();
                if (!result.success) {
                    showAlert(result.message || 'Operation failed', 'danger');
                }
                return result;
            } catch (error) {
                showAlert('Network error: ' + error.message, 'danger');
                return {
                    success: false,
                    message: error.message
                };
            }
        }

        async function showExportModal() {
            if (allTableNames.length === 0) {
                const result = await apiRequest('getTables');
                if (result.success && result.data) {
                    allTableNames = result.data;
                } else {
                    showAlert('Failed to load tables', 'danger');
                    return;
                }
            }
            
            const container = document.getElementById('tableCheckboxList');
            if (allTableNames.length === 0) {
                container.innerHTML = '<div class="text-muted text-center">No tables found</div>';
            } else {
                container.innerHTML = allTableNames.map(table => `
                    <div class="checkbox-item">
                        <input type="checkbox" id="table_${table}" value="${table}" onchange="updateSelectedCount()">
                        <label for="table_${table}">${table}</label>
                    </div>
                `).join('');
            }
            
            updateSelectedCount();
            
            openModal('exportModal');
        }

        function toggleAllTables() {
            const checked = document.getElementById('selectAllTables').checked;
            document.querySelectorAll('#tableCheckboxList input[type="checkbox"]').forEach(cb => {
                cb.checked = checked;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checked = document.querySelectorAll('#tableCheckboxList input[type="checkbox"]:checked').length;
            document.getElementById('selectedCount').textContent = checked;
        }

        async function exportSQL() {
            const selectedTables = [];
            document.querySelectorAll('#tableCheckboxList input[type="checkbox"]:checked').forEach(cb => {
                selectedTables.push(cb.value);
            });
            
            if (selectedTables.length === 0) {
                showAlert('Please select at least one table to include data', 'warning');
                return;
            }
            
            closeModal('exportModal');
            
            const btn = document.querySelector('.export-sql-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Exporting...';
            btn.disabled = true;
            
            try {
                const result = await apiRequest('exportSQL', {
                    tables_with_data: JSON.stringify(selectedTables)
                });
                if (result.success) {
                    showAlert('Database exported successfully!', 'success');
                }
            } catch (error) {
                showAlert('Export failed: ' + error.message, 'danger');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function showRemoveColumnModal() {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            
            if (!currentColumns.length) {
                showAlert('No columns found in this table', 'warning');
                return;
            }
            
            document.getElementById('removeColumnTableName').textContent = currentTable;
            
            const select = document.getElementById('removeColumnSelect');
            select.innerHTML = '<option value="">— Select a column —</option>';
            
            currentColumns.forEach(col => {
                const option = document.createElement('option');
                option.value = col.Field;
                option.textContent = col.Field + ' (' + col.Type + ')';
                select.appendChild(option);
            });
            
            openModal('removeColumnModal');
        }

        async function removeColumn() {
            const select = document.getElementById('removeColumnSelect');
            const columnName = select.value.trim();
            
            if (!columnName) {
                showAlert('Please select a column to remove', 'warning');
                return;
            }
            
            if (!confirm(`Are you sure you want to remove column "${columnName}" from table "${currentTable}"? This cannot be undone!`)) {
                return;
            }
            
            const result = await apiRequest('removeColumn', {
                table: currentTable,
                columnName: columnName
            });
            
            if (result.success) {
                showAlert(`Column "${columnName}" removed successfully`, 'success');
                closeModal('removeColumnModal');
                await loadTableInfo();
                await loadTableData();
            }
        }

        async function loadTables() {
            const container = document.getElementById('tableList');
            if (!container) return;
            showLoading(container);
            const result = await apiRequest('getTables');
            hideLoading(container);
            if (result.success && result.data) {
                allTableNames = result.data;
                if (result.data.length === 0) {
                    container.innerHTML = '<div class="text-muted text-center py-3">No tables found</div>';
                } else {
                    container.innerHTML = result.data.map(table => `
                <div class="table-list-item ${currentTable === table ? 'active' : ''}" 
                     onclick="selectTable('${table}')">
                    <span>📊 ${table}</span>
                </div>
            `).join('');
                }
            } else {
                container.innerHTML = '<div class="text-danger text-center py-3">Failed to load tables</div>';
            }
        }

        async function selectTable(table) {
            currentTable = table;
            await loadTables();
            await loadTableInfo();
            await loadTableData();
        }

        async function loadTableInfo() {
            if (!currentTable) return;
            const result = await apiRequest('getTableInfo', {
                table: currentTable
            });
            if (result.success && result.data) {
                currentColumns = result.data.columns || [];
                currentKeys = result.data.keys || {};
                renderTableInfo();
            }
        }

        function renderTableInfo() {
            const container = document.getElementById('tableContent');
            if (!container) return;
            if (!currentColumns.length) {
                container.innerHTML = '<div class="text-center text-muted py-5">No columns found</div>';
                return;
            }

            let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 8px;">
            <h5 style="font-size: 16px; font-weight: 600;">
                <span class="icon">📋</span>Table: <strong>${currentTable}</strong>
            </h5>
            <div class="btn-group">
                <button class="btn btn-outline-primary btn-sm" onclick="showAddColumnModal()">
                    <span class="icon">➕</span> Column
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="showRemoveColumnModal()">
                    <span class="icon">🗑️</span> Remove
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="showRenameColumnModal()">
                    <span class="icon">✏️</span> Rename
                </button>
                <button class="btn btn-outline-warning btn-sm" onclick="showModifyColumnModal()">
                    <span class="icon">⚙️</span> Modify
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="showKeyModal()">
                    <span class="icon">🔑</span> Keys
                </button>
                <button class="btn btn-success btn-sm" onclick="showInsertRowModal()">
                    <span class="icon">➕</span> Insert
                </button>
                <button class="btn btn-danger btn-sm" onclick="truncateTable()">
                    <span class="icon">🗑️</span> Truncate
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                </thead>
                <tbody>
    `;

            currentColumns.forEach(col => {
                let keyBadges = '';
                if (col.Key === 'PRI') keyBadges = '<span class="badge-key bg-primary">PRI</span>';
                else if (col.Key === 'UNI') keyBadges = '<span class="badge-key bg-info">UNI</span>';
                else if (col.Key === 'MUL') keyBadges = '<span class="badge-key bg-secondary">MUL</span>';

                html += `
            <tr>
                <td><strong>${col.Field}</strong></td>
                <td>${col.Type}</td>
                <td>${col.Null}</td>
                <td>${keyBadges || '—'}</td>
                <td>${col.Default !== null && col.Default !== undefined ? col.Default : 'NULL'}</td>
                <td>${col.Extra || ''}</td>
            </tr>
        `;
            });

            html += `
                </tbody>
            </table>
        </div>
        <div class="mt-4" id="tableDataContainer">
            <div class="text-center text-muted py-3">Loading data...</div>
        </div>
    `;

            container.innerHTML = html;
        }

        async function loadTableData() {
            if (!currentTable) return;
            const container = document.getElementById('tableDataContainer');
            if (!container) return;

            showLoading(container);
            const result = await apiRequest('getTableData', {
                table: currentTable
            });
            hideLoading(container);

            if (result.success && result.data) {
                tableData = result.data;
                renderTableData();
            } else {
                container.innerHTML = '<div class="text-center text-danger py-3">Failed to load data</div>';
            }
        }

        function renderTableData() {
            const container = document.getElementById('tableDataContainer');
            if (!container) return;

            if (!tableData.length) {
                container.innerHTML = '<div class="text-center text-muted py-3">No rows found</div>';
                return;
            }

            const columns = Object.keys(tableData[0]);
            const primaryKey = currentColumns.find(c => c.Key === 'PRI')?.Field || columns[0];

            let html = `
        <h6 style="margin-bottom: 10px;"><span class="icon">📊</span>Data (${tableData.length} rows)</h6>
        <div class="table-responsive" style="max-height: 400px;">
            <table>
                <thead>
                    <tr>
                        ${columns.map(col => `<th>${col}</th>`).join('')}
                        <th style="min-width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;

            tableData.forEach(row => {
                html += '<tr>';
                columns.forEach(col => {
                    html += `<td>${row[col] !== null ? row[col] : '<span class="text-muted">NULL</span>'}</td>`;
                });
                const pkValue = row[primaryKey];
                html += `
            <td>
                <button class="btn btn-outline-primary btn-sm" onclick="showEditRowModal('${pkValue}')" style="margin: 2px;">
                    ✏️
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="deleteRow('${pkValue}')" style="margin: 2px;">
                    🗑️
                </button>
            </td>
        </tr>
        `;
            });

            html += `
                </tbody>
            </table>
        </div>
    `;

            container.innerHTML = html;
        }

        async function createTable() {
            const tableName = document.getElementById('newTableName').value.trim();
            const columns = document.getElementById('newTableColumns').value.trim();

            if (!tableName || !columns) {
                showAlert('Table name and columns are required', 'warning');
                return;
            }

            const result = await apiRequest('createTable', {
                tableName,
                columns
            });
            if (result.success) {
                showAlert(`Table "${tableName}" created successfully`, 'success');
                document.getElementById('newTableName').value = '';
                document.getElementById('newTableColumns').value = '';
                closeModal('createTableModal');
                await loadTables();
                currentTable = tableName;
                await selectTable(tableName);
            }
        }

        async function dropTable() {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            if (!confirm(`Are you sure you want to drop table "${currentTable}"? This cannot be undone!`)) return;

            const result = await apiRequest('dropTable', {
                table: currentTable
            });
            if (result.success) {
                showAlert(`Table "${currentTable}" dropped`, 'warning');
                currentTable = null;
                document.getElementById('tableContent').innerHTML = `
            <div class="text-center text-muted py-5">
                <div style="font-size: 48px; margin-bottom: 15px;">📊</div>
                <p>Select a table from the left to manage it</p>
            </div>
        `;
                await loadTables();
            }
        }

        function showRenameTableModal() {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            document.getElementById('renameTableName').value = currentTable;
            openModal('renameTableModal');
        }

        async function renameTable() {
            const newName = document.getElementById('renameTableName').value.trim();
            if (!newName) {
                showAlert('New table name is required', 'warning');
                return;
            }
            if (newName === currentTable) {
                closeModal('renameTableModal');
                return;
            }

            const result = await apiRequest('renameTable', {
                oldName: currentTable,
                newName
            });
            if (result.success) {
                showAlert(`Table renamed to "${newName}"`, 'success');
                closeModal('renameTableModal');
                currentTable = newName;
                await loadTables();
                await selectTable(newName);
            }
        }

        async function truncateTable() {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            if (!confirm(`Truncate all data from "${currentTable}"?`)) return;

            const result = await apiRequest('truncateTable', {
                table: currentTable
            });
            if (result.success) {
                showAlert(`Table "${currentTable}" truncated`, 'warning');
                await loadTableData();
            }
        }

        function showAddColumnModal() {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            document.getElementById('addColumnDef').value = '';
            openModal('addColumnModal');
        }

        async function addColumn() {
            const columnDef = document.getElementById('addColumnDef').value.trim();
            if (!columnDef) {
                showAlert('Column definition is required', 'warning');
                return;
            }

            const result = await apiRequest('addColumn', {
                table: currentTable,
                columnDef
            });
            if (result.success) {
                showAlert('Column added successfully', 'success');
                closeModal('addColumnModal');
                await loadTableInfo();
                await loadTableData();
            }
        }

        function showRenameColumnModal() {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            document.getElementById('renameOldCol').value = '';
            document.getElementById('renameNewCol').value = '';
            openModal('renameColumnModal');
        }

        async function renameColumn() {
            const oldName = document.getElementById('renameOldCol').value.trim();
            const newName = document.getElementById('renameNewCol').value.trim();
            if (!oldName || !newName) {
                showAlert('Both column names are required', 'warning');
                return;
            }

            const result = await apiRequest('renameColumn', {
                table: currentTable,
                oldName,
                newName
            });
            if (result.success) {
                showAlert(`Column renamed from "${oldName}" to "${newName}"`, 'success');
                closeModal('renameColumnModal');
                await loadTableInfo();
                await loadTableData();
            }
        }

        function showModifyColumnModal() {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            document.getElementById('modifyColName').value = '';
            document.getElementById('modifyColDef').value = '';
            openModal('modifyColumnModal');
        }

        async function modifyColumn() {
            const columnName = document.getElementById('modifyColName').value.trim();
            const newDef = document.getElementById('modifyColDef').value.trim();
            if (!columnName || !newDef) {
                showAlert('Column name and new definition are required', 'warning');
                return;
            }

            const result = await apiRequest('modifyColumn', {
                table: currentTable,
                columnName,
                newDef
            });
            if (result.success) {
                showAlert(`Column "${columnName}" modified`, 'success');
                closeModal('modifyColumnModal');
                await loadTableInfo();
                await loadTableData();
            }
        }

        function showKeyModal() {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            document.getElementById('pkColumn').value = '';
            document.getElementById('uniqueColumn').value = '';
            document.getElementById('dropKeyName').value = '';
            openModal('keyModal');
        }

        async function setPrimaryKey() {
            const column = document.getElementById('pkColumn').value.trim();
            if (!column) {
                showAlert('Column name is required', 'warning');
                return;
            }
            const result = await apiRequest('setPrimaryKey', {
                table: currentTable,
                column
            });
            if (result.success) {
                showAlert(`PRIMARY KEY set on "${column}"`, 'success');
                document.getElementById('pkColumn').value = '';
                await loadTableInfo();
            }
        }

        async function setUniqueKey() {
            const column = document.getElementById('uniqueColumn').value.trim();
            if (!column) {
                showAlert('Column name is required', 'warning');
                return;
            }
            const result = await apiRequest('setUniqueKey', {
                table: currentTable,
                column
            });
            if (result.success) {
                showAlert(`UNIQUE KEY set on "${column}"`, 'success');
                document.getElementById('uniqueColumn').value = '';
                await loadTableInfo();
            }
        }

        async function dropKey() {
            const keyName = document.getElementById('dropKeyName').value.trim();
            if (!keyName) {
                showAlert('Key name is required', 'warning');
                return;
            }
            const result = await apiRequest('dropKey', {
                table: currentTable,
                keyName
            });
            if (result.success) {
                showAlert(`Key "${keyName}" dropped`, 'info');
                document.getElementById('dropKeyName').value = '';
                await loadTableInfo();
            }
        }

        function showInsertRowModal() {
            if (!currentTable || !currentColumns.length) {
                showAlert('Please select a valid table', 'warning');
                return;
            }

            const container = document.getElementById('insertRowFields');
            let html = `<input type="hidden" name="table" value="${currentTable}">`;
            currentColumns.forEach(col => {
                if (col.Extra && col.Extra.includes('auto_increment')) {
                    html += `<div class="mb-2 text-muted"><small>${col.Field} (auto-increment, will be generated)</small></div>`;
                } else {
                    html += `
                <div class="mb-2">
                    <label class="form-label">${col.Field} <small class="text-muted">(${col.Type})</small></label>
                    <input class="form-control" name="col_${col.Field}" placeholder="Enter value for ${col.Field}">
                </div>
            `;
                }
            });
            container.innerHTML = html;
            openModal('insertRowModal');
        }

        async function insertRow() {
            const form = document.getElementById('insertRowFields');
            const inputs = form.querySelectorAll('input');
            const data = {
                table: currentTable
            };

            inputs.forEach(input => {
                if (input.name.startsWith('col_')) {
                    const colName = input.name.substring(4);
                    data[`col_${colName}`] = input.value;
                }
            });

            const result = await apiRequest('insertRow', data);
            if (result.success) {
                showAlert('Row inserted successfully', 'success');
                closeModal('insertRowModal');
                await loadTableData();
            }
        }

        function showEditRowModal(primaryKeyValue) {
            if (!currentTable || !currentColumns.length) {
                showAlert('Please select a valid table', 'warning');
                return;
            }

            const primaryKey = currentColumns.find(c => c.Key === 'PRI')?.Field || 'id';
            const row = tableData.find(r => String(r[primaryKey]) === String(primaryKeyValue));
            if (!row) {
                showAlert('Row not found', 'danger');
                return;
            }

            const container = document.getElementById('editRowFields');
            let html = `
        <input type="hidden" name="table" value="${currentTable}">
        <input type="hidden" name="whereCol" value="${primaryKey}">
        <input type="hidden" name="whereVal" value="${primaryKeyValue}">
    `;

            currentColumns.forEach(col => {
                const value = row[col.Field] !== null ? row[col.Field] : '';
                html += `
            <div class="mb-2">
                <label class="form-label">${col.Field} <small class="text-muted">(${col.Type})</small></label>
                <input class="form-control" name="col_${col.Field}" value="${value}">
            </div>
        `;
            });
            container.innerHTML = html;
            openModal('editRowModal');
        }

        async function updateRow() {
            const form = document.getElementById('editRowFields');
            const inputs = form.querySelectorAll('input');
            const data = {
                table: currentTable
            };

            inputs.forEach(input => {
                if (input.name.startsWith('col_')) {
                    const colName = input.name.substring(4);
                    data[`col_${colName}`] = input.value;
                } else if (input.name === 'whereCol') {
                    data.whereCol = input.value;
                } else if (input.name === 'whereVal') {
                    data.whereVal = input.value;
                }
            });

            const result = await apiRequest('updateRow', data);
            if (result.success) {
                showAlert('Row updated successfully', 'success');
                closeModal('editRowModal');
                await loadTableData();
            }
        }

        async function deleteRow(primaryKeyValue) {
            if (!currentTable) {
                showAlert('Please select a table first', 'warning');
                return;
            }
            if (!confirm('Delete this row?')) return;

            const primaryKey = currentColumns.find(c => c.Key === 'PRI')?.Field || 'id';
            const result = await apiRequest('deleteRow', {
                table: currentTable,
                whereCol: primaryKey,
                whereVal: primaryKeyValue
            });
            if (result.success) {
                showAlert('Row deleted', 'danger');
                await loadTableData();
            }
        }

        async function refreshAll() {
            await loadTables();
            if (currentTable) {
                await loadTableInfo();
                await loadTableData();
            }
            showAlert('Refreshed', 'info');
        }

        function showCreateTableModal() {
            document.getElementById('newTableName').value = '';
            document.getElementById('newTableColumns').value = 'id INT PRIMARY KEY AUTO_INCREMENT,\nname VARCHAR(100) NOT NULL';
            openModal('createTableModal');
        }

        document.addEventListener('DOMContentLoaded', async function() {
            try {
                const result = await apiRequest('getTables');
                if (result.success) {
                    await loadTables();
                    if (result.data && result.data.length > 0) {
                        currentTable = result.data[0];
                        await selectTable(currentTable);
                    }
                }
            } catch (error) {
                showAlert('Failed to connect to database: ' + error.message, 'danger');
            }
        });
    </script>
</body>

</html>