<?php
include_once "app/php/core/partials/envloader.php";
$dbname = env("database");
if (!$dbname) {
    die("❌ No Database found @ .env");
}
$host = env('dbhost') ?: 'localhost';
define('DB_TYPE', env("dbdriver") ?? "mysql");
define('DB_HOST', $host);
define('DB_NAME', $dbname);
define('DB_USER', env('dbuser') ?: 'root');
define('DB_PASS', env('dbpass') ?: '');
define('DB_CHARSET', env('dbcharset') ?: 'utf8mb4');
define('DB_PORT', env('dbport') ?: ($dbdriver === 'pgsql' ? '5432' : '3306'));

$activation_key = 'role_management_activated';
$activation_requested = isset($_GET['activate']) && $_GET['activate'] === 'true';
$activation_confirmed = isset($_POST['confirm_activate']) && $_POST['confirm_activate'] === 'yes';
$deactivation_confirmed = isset($_POST['confirm_deactivate']) && $_POST['confirm_deactivate'] === 'yes';
$tables_created = [];

class Database
{
    private static $instance = null;
    private $pdo;
    private $dbType;
    private $lastError = null;
    private function __construct()
    {
        $this->dbType = DB_TYPE;
        $this->connect();
    }
    private function connect()
    {
        try {
            switch ($this->dbType) {
                case 'mysql':
                case 'mariadb':
                    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                    $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
                    break;
                case 'pgsql':
                    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
                    $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
                    break;
                case 'sqlite':
                    if (!defined('DB_PATH')) {
                        define('DB_PATH', __DIR__ . '/database.sqlite');
                    }
                    $this->pdo = new PDO("sqlite:" . DB_PATH);
                    $this->pdo->exec("PRAGMA foreign_keys = ON");
                    break;
                default:
                    throw new Exception("Unsupported database type: " . $this->dbType);
            }
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            if ($this->dbType === 'pgsql') {
                $this->pdo->exec("SET NAMES '" . DB_CHARSET . "'");
            }
            $this->lastError = null;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            if (($this->dbType === 'mysql' || $this->dbType === 'mariadb') &&
                strpos($e->getMessage(), 'Unknown database') !== false
            ) {
                $this->createDatabase();
                $this->connect();
            } else {
                throw $e;
            }
        }
    }
    private function createDatabase()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbName = DB_NAME;
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET " . DB_CHARSET);
            $pdo = null;
        } catch (PDOException $e) {
            throw new Exception("Failed to create database: " . $e->getMessage());
        }
    }
    public static function getInstance()
    {
        if (self::$instance === null) {
            try {
                self::$instance = new self();
            } catch (Exception $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
    public function getPDO()
    {
        return $this->pdo;
    }
    public function getDbType()
    {
        return $this->dbType;
    }
    public function getLastError()
    {
        return $this->lastError;
    }
    public function testConnection()
    {
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}

try {
    $db = Database::getInstance();
    $pdo = $db->getPDO();
    $dbType = $db->getDbType();
} catch (Exception $e) {
    die("❌ Database connection error: " . $e->getMessage());
}

class SchemaManager
{
    private $pdo;
    private $dbType;
    public function __construct($pdo, $dbType)
    {
        $this->pdo = $pdo;
        $this->dbType = $dbType;
    }
    public function checkTablesExist()
    {
        return $this->tableExists('ctrx_roles') && $this->tableExists('ctrx_roles_access');
    }
    public function createTables()
    {
        if (!$this->tableExists('ctrx_roles')) {
            $this->createRolesTable();
            $this->insertDefaultRoles();
        }
        if (!$this->tableExists('ctrx_roles_access')) {
            $this->createRolesAccessTable();
        }
    }
    public function setupPublicRoleAccess(string $role = 'public')
    {
        $routeScanner = new RouteScanner('views/pages/');
        $rootRoutes = $routeScanner->getRoutesByDirectory('root');
        $stmt = $this->pdo->prepare("SELECT id FROM ctrx_roles WHERE role_name = ?");
        $stmt->execute([$role]);
        $publicRole = $stmt->fetch();
        if ($publicRole) {
            $insert_stmt = $this->pdo->prepare("INSERT INTO ctrx_roles_access (role_id, route, has_access) VALUES (?, ?, ?)");
            foreach ($rootRoutes as $route) {
                $insert_stmt->execute([$publicRole['id'], $route['route'], 1]);
            }
        }
    }
    public function dropTables()
    {
        if ($this->tableExists('ctrx_roles_access')) {
            $this->dropTable('ctrx_roles_access');
        }
        if ($this->tableExists('ctrx_roles')) {
            $this->dropTable('ctrx_roles');
        }
    }
    private function dropTable($tableName)
    {
        switch ($this->dbType) {
            case 'mysql':
            case 'mariadb':
                $sql = "DROP TABLE IF EXISTS `{$tableName}`";
                break;
            case 'pgsql':
                $sql = "DROP TABLE IF EXISTS {$tableName} CASCADE";
                break;
            case 'sqlite':
                $sql = "DROP TABLE IF EXISTS {$tableName}";
                break;
            default:
                return;
        }
        $this->pdo->exec($sql);
    }
    private function tableExists($tableName)
    {
        try {
            switch ($this->dbType) {
                case 'mysql':
                case 'mariadb':
                    $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tableName}'");
                    return $stmt->rowCount() > 0;
                case 'pgsql':
                    $stmt = $this->pdo->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)");
                    $stmt->execute([$tableName]);
                    return $stmt->fetchColumn() === 't' || $stmt->fetchColumn() === true;
                case 'sqlite':
                    $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
                    $stmt->execute([$tableName]);
                    return $stmt->fetch() !== false;
                default:
                    return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    private function createRolesTable()
    {
        switch ($this->dbType) {
            case 'mysql':
            case 'mariadb':
                $sql = "CREATE TABLE `ctrx_roles` (`id` int(11) NOT NULL AUTO_INCREMENT,`role_name` varchar(50) NOT NULL,`description` text,`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY (`id`),UNIQUE KEY `unique_role_name` (`role_name`)) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET;
                break;
            case 'pgsql':
                $sql = "CREATE TABLE ctrx_roles (id SERIAL PRIMARY KEY,role_name VARCHAR(50) NOT NULL UNIQUE,description TEXT,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)";
                break;
            case 'sqlite':
                $sql = "CREATE TABLE ctrx_roles (id INTEGER PRIMARY KEY AUTOINCREMENT,role_name VARCHAR(50) NOT NULL UNIQUE,description TEXT,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)";
                break;
        }
        $this->pdo->exec($sql);
        if ($this->dbType === 'pgsql') {
            $this->pdo->exec("CREATE OR REPLACE FUNCTION update_updated_at_column() RETURNS TRIGGER AS $$ BEGIN NEW.updated_at = CURRENT_TIMESTAMP; RETURN NEW; END; $$ language 'plpgsql'; CREATE TRIGGER update_ctrx_roles_updated_at BEFORE UPDATE ON ctrx_roles FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();");
        }
    }
    private function createRolesAccessTable()
    {
        switch ($this->dbType) {
            case 'mysql':
            case 'mariadb':
                $sql = "CREATE TABLE `ctrx_roles_access` (`id` int(11) NOT NULL AUTO_INCREMENT,`role_id` int(11) NOT NULL,`route` varchar(255) NOT NULL,`has_access` tinyint(1) DEFAULT 0,`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY (`id`),UNIQUE KEY `unique_role_route` (`role_id`, `route`),KEY `role_id` (`role_id`),CONSTRAINT `ctrx_roles_access_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ctrx_roles` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET;
                break;
            case 'pgsql':
                $sql = "CREATE TABLE ctrx_roles_access (id SERIAL PRIMARY KEY,role_id INTEGER NOT NULL REFERENCES ctrx_roles(id) ON DELETE CASCADE,route VARCHAR(255) NOT NULL,has_access BOOLEAN DEFAULT FALSE,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(role_id, route))";
                break;
            case 'sqlite':
                $sql = "CREATE TABLE ctrx_roles_access (id INTEGER PRIMARY KEY AUTOINCREMENT,role_id INTEGER NOT NULL,route VARCHAR(255) NOT NULL,has_access INTEGER DEFAULT 0,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(role_id, route),FOREIGN KEY (role_id) REFERENCES ctrx_roles(id) ON DELETE CASCADE)";
                break;
        }
        $this->pdo->exec($sql);
        if ($this->dbType === 'pgsql') {
            $this->pdo->exec("CREATE TRIGGER update_ctrx_roles_access_updated_at BEFORE UPDATE ON ctrx_roles_access FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();");
        }
    }
    private function insertDefaultRoles()
    {
        $roles = [
            ['public', 'Can access public pages'],
        ];
        $stmt = $this->pdo->prepare("INSERT INTO ctrx_roles (role_name, description) VALUES (?, ?)");
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
    }
}

class RouteScanner
{
    private $basePath;
    public $dirColors = [];
    private $colorPalette = [
        '#2196F3',
        '#9C27B0',
        '#3F51B5',
        '#009688',
        '#FF5722',
        '#795548',
        '#607D8B',
        '#E91E63',
        '#00BCD4',
        '#8BC34A',
        '#673AB7',
        '#FF9800',
    ];

    public function __construct($basePath = 'views/pages/')
    {
        $this->basePath = rtrim($basePath, '/') . '/';
        $this->dirColors['root'] = '#4CAF50';
    }

    private function getDirectoryColor($dirPath)
    {
        if (isset($this->dirColors[$dirPath])) {
            return $this->dirColors[$dirPath];
        }

        if ($dirPath === 'root' || $dirPath === '') {
            $color = $this->dirColors['root'];
        } else {
            $parts = explode('/', $dirPath);
            $firstDir = $parts[0];

            if (isset($this->dirColors[$firstDir])) {
                $color = $this->dirColors[$firstDir];
            } else {
                $colorIndex = count($this->dirColors) % count($this->colorPalette);
                $color = $this->colorPalette[$colorIndex];
                $this->dirColors[$firstDir] = $color;
            }
        }

        $this->dirColors[$dirPath] = $color;
        return $color;
    }

    public function getDirectories()
    {
        $dirs = ['all', 'root'];
        $this->scanDirectories($this->basePath, '', $dirs);
        return $dirs;
    }
    private function scanDirectories($dir, $prefix, &$dirs)
    {
        if (!is_dir($dir)) return;
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . '/' . $file;
            if (is_dir($fullPath)) {
                $dirs[] = $prefix . $file;
                $this->scanDirectories($fullPath, $prefix . $file . '/', $dirs);
            }
        }
    }
    public function getRoutesByDirectory($directory = '')
    {
        $routes = [];
        if ($directory === 'all') {
            $this->scanDirectory($this->basePath, '', $routes);
        } else if ($directory === 'root') {
            $this->scanDirectoryRoot($this->basePath, $routes);
        } else {
            $searchPath = $this->basePath . rtrim($directory, '/') . '/';
            if (is_dir($searchPath)) {
                $this->scanDirectory($searchPath, $directory . '/', $routes);
            }
        }
        return $routes;
    }
    private function scanDirectoryRoot($dir, &$routes)
    {
        if (!is_dir($dir)) return;
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . '/' . $file;
            if (!is_dir($fullPath) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $routeName = pathinfo($file, PATHINFO_FILENAME);
                $routes[] = [
                    'route' => $routeName,
                    'file' => $fullPath,
                    'display_name' => $this->formatRouteName($routeName),
                    'color' => $this->dirColors['root']
                ];
            }
        }
    }
    private function scanDirectory($dir, $prefix, &$routes)
    {
        if (!is_dir($dir)) return;
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . '/' . $file;
            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $prefix . $file . '/', $routes);
            } else if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $routeName = $prefix . pathinfo($file, PATHINFO_FILENAME);
                $dirPath = rtrim($prefix, '/');
                $color = $this->getDirectoryColor($dirPath);
                $routes[] = [
                    'route' => $routeName,
                    'file' => $fullPath,
                    'display_name' => $this->formatRouteName($routeName),
                    'color' => $color
                ];
            }
        }
    }
    private function formatRouteName($route)
    {
        $parts = explode('/', $route);
        $lastPart = end($parts);
        return ucwords(str_replace(['-', '_'], ' ', $lastPart));
    }
    public function getAllRoutes()
    {
        $routes = [];
        $this->scanDirectory($this->basePath, '', $routes);
        return $routes;
    }
    public function getDirColors()
    {
        return $this->dirColors;
    }
}

class RoleManager
{
    private $pdo;
    private $dbType;
    public function __construct($pdo, $dbType)
    {
        $this->pdo = $pdo;
        $this->dbType = $dbType;
    }
    public function getRoles()
    {
        $stmt = $this->pdo->query("SELECT * FROM ctrx_roles ORDER BY id");
        return $stmt->fetchAll();
    }
    public function getRole($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ctrx_roles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function createRole($role_name, $description)
    {
        $stmt = $this->pdo->prepare("INSERT INTO ctrx_roles (role_name, description) VALUES (?, ?)");
        return $stmt->execute([$role_name, $description]);
    }
    public function updateRole($id, $role_name, $description)
    {
        $stmt = $this->pdo->prepare("UPDATE ctrx_roles SET role_name = ?, description = ? WHERE id = ?");
        return $stmt->execute([$role_name, $description, $id]);
    }
    public function deleteRole($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM ctrx_roles WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public function getRoleAccess($role_id)
    {
        $stmt = $this->pdo->prepare("SELECT route, has_access FROM ctrx_roles_access WHERE role_id = ?");
        $stmt->execute([$role_id]);
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['route']] = $row['has_access'];
        }
        return $result;
    }

    public function saveRoleAccess($role_id, $access_data)
    {
        $this->pdo->beginTransaction();
        try {
            $routeScanner = new RouteScanner('views/pages/');
            $all_routes = $routeScanner->getAllRoutes();
            $all_route_names = array_column($all_routes, 'route');

            $current_access = $this->getRoleAccess($role_id);

            foreach ($access_data as $route => $has_access) {
                $new_value = 1;
                $current_value = isset($current_access[$route]) ? $current_access[$route] : 0;

                if ($current_value != $new_value) {
                    $check_stmt = $this->pdo->prepare("SELECT id FROM ctrx_roles_access WHERE role_id = ? AND route = ?");
                    $check_stmt->execute([$role_id, $route]);
                    if ($check_stmt->fetch()) {
                        $update_stmt = $this->pdo->prepare("UPDATE ctrx_roles_access SET has_access = ? WHERE role_id = ? AND route = ?");
                        $update_stmt->execute([$new_value, $role_id, $route]);
                    } else {
                        $insert_stmt = $this->pdo->prepare("INSERT INTO ctrx_roles_access (role_id, route, has_access) VALUES (?, ?, ?)");
                        $insert_stmt->execute([$role_id, $route, $new_value]);
                    }
                }
            }
            $posted_routes = array_keys($access_data);
            foreach ($current_access as $route => $current_value) {
                if (!in_array($route, $posted_routes)) {
                    $selected_directory = isset($_GET['dir']) ? $_GET['dir'] : 'root';
                    $current_view_routes = $routeScanner->getRoutesByDirectory($selected_directory);
                    $current_view_route_names = array_column($current_view_routes, 'route');
                    if (in_array($route, $current_view_route_names)) {
                        $update_stmt = $this->pdo->prepare("UPDATE ctrx_roles_access SET has_access = 0 WHERE role_id = ? AND route = ?");
                        $update_stmt->execute([$role_id, $route]);
                    }
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

$schemaManager = new SchemaManager($pdo, $dbType);
$tablesExist = $schemaManager->checkTablesExist();
$is_activated = $tablesExist;

if ($activation_confirmed) {
    try {
        $schemaManager->createTables();
        $schemaManager->setupPublicRoleAccess();
        $_SESSION[$activation_key] = true;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } catch (Exception $e) {
        die("❌ Failed to activate: " . $e->getMessage());
    }
}

if ($deactivation_confirmed) {
    try {
        $schemaManager->dropTables();
        unset($_SESSION[$activation_key]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } catch (Exception $e) {
        die("❌ Failed to deactivate: " . $e->getMessage());
    }
}

if (isset($_GET['deactivate'])) {
    $show_deactivate_confirm = true;
} else {
    $show_deactivate_confirm = false;
}

$roleManager = new RoleManager($pdo, $dbType);
$routeScanner = new RouteScanner('views/pages/');
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_activated) {
        try {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'create_role':
                        if (!empty($_POST['role_name'])) {
                            if ($_POST['role_name'] == "admin") {
                                throw new Exception("Unable to add admin role.");
                            }
                            $roleManager->createRole($_POST['role_name'], $_POST['description'] ?? '');
                            $schemaManager->setupPublicRoleAccess($_POST['role_name']);
                            $message = 'Role created successfully!';
                            $message_type = 'success';
                        }
                        break;
                    case 'update_role':
                        if (!empty($_POST['role_id']) && !empty($_POST['role_name'])) {
                            $roleManager->updateRole($_POST['role_id'], $_POST['role_name'], $_POST['description'] ?? '');
                            $message = 'Role updated successfully!';
                            $message_type = 'success';
                        }
                        break;
                    case 'delete_role':
                        if (!empty($_POST['role_id'])) {
                            $roleManager->deleteRole($_POST['role_id']);
                            $message = 'Role deleted successfully!';
                            $message_type = 'success';
                        }
                        break;
                    case 'save_access':
                        if (!empty($_POST['role_id'])) {
                            $access_data = $_POST['access'] ?? [];
                            $roleManager->saveRoleAccess($_POST['role_id'], $access_data);
                            $message = 'Access permissions saved successfully!';
                            $message_type = 'success';
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if ($is_activated) {
    $roles = $roleManager->getRoles();
    $directories = $routeScanner->getDirectories();
    $selected_directory = isset($_GET['dir']) ? $_GET['dir'] : 'root';
    $all_routes = $routeScanner->getRoutesByDirectory($selected_directory);
    $selected_role_id = isset($_GET['role_id']) ? (int)$_GET['role_id'] : (isset($roles[0]) ? $roles[0]['id'] : 0);
    $selected_role = $selected_role_id ? $roleManager->getRole($selected_role_id) : null;
    $role_access = $selected_role_id ? $roleManager->getRoleAccess($selected_role_id) : [];
    $display_name = '';
    if ($selected_directory === 'all') {
        $display_name = 'All Routes (All Directories)';
    } else if ($selected_directory === 'root') {
        $display_name = 'Root Pages (Independent)';
    } else {
        $display_name = ucwords(str_replace(['/', '_', '-'], ' ', $selected_directory));
    }
} else {
    $roles = [];
    $directories = [];
    $all_routes = [];
    $selected_role = null;
    $role_access = [];
    $selected_directory = 'root';
    $display_name = '';
    $selected_role_id = 0;
}

$prev_page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Framework Roles Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa
        }

        .container-fluid {
            display: flex;
            min-height: 100vh
        }

        .sidebar {
            width: 280px;
            background: #fff;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            flex-shrink: 0
        }

        .main-content {
            flex: 1;
            padding: 20px 30px;
            overflow-x: hidden
        }

        .sidebar-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6
        }

        .sidebar-header h5 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px
        }

        .role-list {
            flex: 1;
            overflow-y: auto
        }

        .role-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .role-item:hover {
            background-color: #f8f9fa
        }

        .role-item.active {
            background-color: #e3f2fd;
            border-left: 3px solid #1976d2
        }

        .role-item .role-name {
            font-weight: 500
        }

        .role-item .role-desc {
            font-size: 12px;
            color: #6c757d;
            display: block
        }

        .role-item .btn-group {
            display: flex;
            gap: 4px
        }

        .form-group {
            margin-bottom: 10px
        }

        .form-control {
            width: 100%;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.5;
            color: #495057;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color 0.15s
        }

        .form-control:focus {
            border-color: #1976d2;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25)
        }

        .form-control-sm {
            padding: 4px 8px;
            font-size: 12px
        }

        .input-group {
            display: flex;
            gap: 4px
        }

        .input-group .form-control {
            flex: 1
        }

        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.5;
            border-radius: 4px;
            border: 1px solid transparent;
            transition: all 0.15s;
            text-decoration: none
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px
        }

        .btn-primary {
            color: #fff;
            background: #1976d2;
            border-color: #1976d2
        }

        .btn-primary:hover {
            background: #1565c0;
            border-color: #1565c0
        }

        .btn-success {
            color: #fff;
            background: #28a745;
            border-color: #28a745
        }

        .btn-success:hover {
            background: #218838;
            border-color: #218838
        }

        .btn-danger {
            color: #fff;
            background: #dc3545;
            border-color: #dc3545
        }

        .btn-danger:hover {
            background: #c82333;
            border-color: #c82333
        }

        .btn-outline-primary {
            color: #1976d2;
            background: transparent;
            border-color: #1976d2
        }

        .btn-outline-primary:hover {
            color: #fff;
            background: #1976d2
        }

        .btn-outline-danger {
            color: #dc3545;
            background: transparent;
            border-color: #dc3545
        }

        .btn-outline-danger:hover {
            color: #fff;
            background: #dc3545
        }

        .btn-outline-secondary {
            color: #6c757d;
            background: transparent;
            border-color: #6c757d
        }

        .btn-outline-secondary:hover {
            color: #fff;
            background: #6c757d
        }

        .btn-group {
            display: flex;
            gap: 4px
        }

        .card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 20px
        }

        .card-header {
            padding: 12px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-weight: 500
        }

        .card-body {
            padding: 0
        }

        .card-footer {
            padding: 12px 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6
        }

        .route-item {
            padding: 10px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s
        }

        .route-item:hover {
            background-color: #f8f9fa
        }

        .route-item:last-child {
            border-bottom: none
        }

        .route-item .row {
            display: flex;
            align-items: center;
            gap: 15px
        }

        .route-item .col-route {
            flex: 1
        }

        .route-item .col-access {
            width: 120px;
            text-align: center
        }

        .route-item .col-file {
            width: 120px;
            text-align: center
        }

        .route-path {
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            display: inline-block;
            color: #fff
        }

        .route-name {
            font-size: 12px;
            color: #6c757d
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            transition: .3s;
            border-radius: 24px
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: .3s;
            border-radius: 50%
        }

        .switch input:checked+.slider {
            background: #28a745
        }

        .switch input:checked+.slider:before {
            transform: translateX(20px)
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 12px;
            color: #fff;
            min-width: 36px;
            text-align: center
        }

        .badge-success {
            background: #28a745
        }

        .badge-secondary {
            background: #6c757d
        }

        .badge-info {
            background: #17a2b8
        }

        .badge-primary {
            background: #1976d2
        }

        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid transparent
        }

        .alert-success {
            color: #155724;
            background: #d4edda;
            border-color: #c3e6cb
        }

        .alert-danger {
            color: #721c24;
            background: #f8d7da;
            border-color: #f5c6cb
        }

        .alert .btn-close {
            float: right;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit
        }

        .table-created {
            background: #d4edda;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #155724;
            border: 1px solid #c3e6cb
        }

        .mt-1 {
            margin-top: 5px
        }

        .mt-3 {
            margin-top: 15px
        }

        .mt-4 {
            margin-top: 20px
        }

        .mb-3 {
            margin-bottom: 15px
        }

        .mb-4 {
            margin-bottom: 20px
        }

        .p-3 {
            padding: 15px
        }

        .p-4 {
            padding: 20px
        }

        .p-0 {
            padding: 0
        }

        .py-5 {
            padding-top: 40px;
            padding-bottom: 40px
        }

        .d-flex {
            display: flex
        }

        .justify-content-between {
            justify-content: space-between
        }

        .align-items-center {
            align-items: center
        }

        .text-center {
            text-align: center
        }

        .text-muted {
            color: #6c757d
        }

        .text-danger {
            color: #dc3545
        }

        .display-1 {
            font-size: 60px
        }

        .gap-2 {
            gap: 8px
        }

        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center
        }

        .modal-overlay.show {
            display: flex
        }

        .modal {
            background: #fff;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3)
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .modal-header h5 {
            font-size: 18px;
            margin: 0
        }

        .modal-body {
            padding: 20px
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 8px
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #000;
            opacity: 0.5;
            padding: 0 5px
        }

        .btn-close:hover {
            opacity: 0.8
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            align-items: center;
            flex-wrap: wrap
        }

        .search-box label {
            font-weight: 500;
            margin-right: 5px;
            display: flex;
            align-items: center
        }

        .search-box .form-control {
            max-width: 350px;
            flex: 1
        }

        .search-box .btn {
            margin-left: 5px
        }

        .activation-box {
            max-width: 600px;
            margin: 40px auto;
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center
        }

        .activation-box h2 {
            color: #1976d2;
            margin-bottom: 20px
        }

        .activation-box p {
            margin-bottom: 20px;
            color: #6c757d
        }

        .activation-box .btn-group {
            margin-top: 20px;
            justify-content: center
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6
        }

        .top-bar .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #1976d2;
            text-decoration: none;
            font-weight: 500
        }

        .top-bar .back-btn:hover {
            text-decoration: underline
        }

        .top-bar .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724
        }

        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24
        }

        .deactivate-btn {
            color: #dc3545;
            background: transparent;
            border: 1px solid #dc3545;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.15s;
            display: inline-block
        }

        .deactivate-btn:hover {
            color: #fff;
            background: #dc3545
        }

        .role-count {
            font-size: 12px;
            color: #6c757d;
            margin-left: 10px
        }

        .color-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle
        }

        .legend {
            margin-bottom: 15px;
            padding: 10px 15px;
            background: #fff;
            border-radius: 4px;
            border: 1px solid #dee2e6
        }

        .legend-item {
            display: inline-block;
            margin-right: 15px;
            font-size: 13px
        }

        .legend-color {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 4px;
            margin-right: 5px;
            vertical-align: middle
        }

        @media (max-width:768px) {
            .container-fluid {
                flex-direction: column
            }

            .card-footer {
                display: grid;
                gap: 5px;
            }

            .sidebar {
                width: 100%;
                max-height: 400px;
                border-right: none;
                border-bottom: 1px solid #dee2e6
            }

            .main-content {
                padding: 15px
            }

            .route-item .row {
                flex-wrap: wrap;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
            }

            .route-item .col-route,
            .route-item .col-access,
            .route-item .col-file {
                width: 100%;
            }

            .route-item .col-access {
                text-align: right;
            }

            .route-item .col-file {
                display: none;
            }

            .card-header-file {
                display: none;
            }

            .search-box .form-control {
                max-width: 100%
            }

            .search-box label {
                display: none;
            }

            .top-bar {
                position: absolute;
                top: 0;
                left: 0;
                flex-wrap: wrap;
                gap: 10px;
                width: 100%;
                padding: 10px;
                background-color: #dee2e6;
            }

            .sidebar-header {
                margin-top: 40px;
            }

            .role-list {
                max-height: 300px;
            }

            .father-of-title {
                display: grid;
                align-items: center;
                justify-content: center;
            }

            .deactivate-btn {
                font-size: 0px;
            }

            .deactivate-btn::before {
                content: "Deactivate";
                font-size: 9px;
            }

            .legend-item {
                display: block;
            }

            .legend-colors {
                max-height: 100px;
                overflow-y: scroll;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <?php if ($is_activated): ?>
            <div class="sidebar">
                <div class="sidebar-header">
                    <h5>🔒 Roles <span class="role-count">(<?= count($roles) ?>)</span></h5>
                    <form method="POST" id="addRoleForm">
                        <input type="hidden" name="action" value="create_role">
                        <div class="input-group">
                            <input type="text" name="role_name" class="form-control form-control-sm" placeholder="New role name" required>
                            <button class="btn btn-primary btn-sm" type="submit">+</button>
                        </div>
                        <input type="text" name="description" class="form-control form-control-sm mt-1" placeholder="Description (optional)">
                    </form>
                </div>
                <div class="role-list">
                    <?php foreach ($roles as $role): ?>
                        <div class="role-item <?= $role['id'] == $selected_role_id ? 'active' : '' ?>" onclick="window.location.href='?role_id=<?= $role['id'] ?>&dir=<?= urlencode($selected_directory) ?>'">
                            <div>
                                <span class="role-name"><?= htmlspecialchars($role['role_name']) ?></span>
                                <span class="role-desc"><?= htmlspecialchars($role['description'] ?? '') ?></span>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary btn-sm" <?= $role['role_name'] == 'public' ? 'disabled' : '' ?> onclick="event.stopPropagation(); editRole(<?= $role['id'] ?>, '<?= htmlspecialchars($role['role_name']) ?>', '<?= htmlspecialchars($role['description'] ?? '') ?>')">✎</button>
                                <button class="btn btn-outline-danger btn-sm" <?= $role['role_name'] == 'public' ? 'disabled' : '' ?> onclick="event.stopPropagation(); deleteRole(<?= $role['id'] ?>, '<?= htmlspecialchars($role['role_name']) ?>')">✕</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="main-content">
            <div class="top-bar">
                <a href="<?= htmlspecialchars(prev_page) ?>" class="back-btn">← Go back</a>
                <?php if ($is_activated): ?>
                    <span class="status-badge active">✓ Activated</span>
                <?php else: ?>
                    <span class="status-badge inactive">⚠ Inactive</span>
                <?php endif; ?>
            </div>

            <?php if (!$is_activated && !$activation_requested && !$show_deactivate_confirm): ?>
                <div class="activation-box">
                    <h2>🔐 Role Management</h2>
                    <p>This system uses role-based access control to manage user permissions.</p>
                    <p><strong>Do you want to activate the Role Management System?</strong></p>
                    <p style="font-size:13px;color:#6c757d;">This will create the necessary tables (ctrx_roles, ctrx_roles_access) and a default 'public' role with access to all root pages.</p>
                    <div class="btn-group">
                        <a href="?activate=true" class="btn btn-primary">Yes, Activate</a>
                        <a href="<?= htmlspecialchars($prev_page) ?>" class="btn btn-outline-secondary">No, Skip</a>
                    </div>
                </div>
            <?php elseif ($activation_requested && !$is_activated): ?>
                <div class="activation-box">
                    <h2>⚠ Confirm Activation</h2>
                    <p>You are about to activate the Role Management System.</p>
                    <p><strong>This will create the following tables:</strong></p>
                    <ul style="text-align:left;display:inline-block;margin:10px 0;">
                        <li>📊 ctrx_roles - Stores role definitions</li>
                        <li>📊 ctrx_roles_access - Stores route permissions per role</li>
                    </ul>
                    <p style="font-size:13px;color:#6c757d;">A default 'public' role will be created with access to all root pages.</p>
                    <div class="btn-group">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="confirm_activate" value="yes">
                            <button type="submit" class="btn btn-success">✅ Confirm & Activate</button>
                        </form>
                        <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            <?php elseif ($show_deactivate_confirm && $is_activated): ?>
                <div class="activation-box">
                    <h2>⚠ Confirm Deactivation</h2>
                    <p>You are about to deactivate the Role Management System.</p>
                    <p><strong>This will permanently delete the following tables:</strong></p>
                    <ul style="text-align:left;display:inline-block;margin:10px 0;color:#dc3545;">
                        <li>🗑️ ctrx_roles</li>
                        <li>🗑️ ctrx_roles_access</li>
                    </ul>
                    <p style="font-size:13px;color:#dc3545;"><strong>All role data and permissions will be permanently lost!</strong></p>
                    <div class="btn-group">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="confirm_deactivate" value="yes">
                            <button type="submit" class="btn btn-danger">🗑️ Confirm Deactivation</button>
                        </form>
                        <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            <?php elseif ($is_activated): ?>
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?>">
                        <?= htmlspecialchars($message) ?>
                        <button class="btn-close" onclick="this.parentElement.style.display='none'">×</button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($tables_created)): ?>
                    <div class="table-created">✓ Tables created: <?= implode(', ', $tables_created) ?></div>
                <?php endif; ?>

                <div style="margin-bottom:15px;" align='right'>
                    <a href="?deactivate=true" class="deactivate-btn" onclick="return confirm('Are you sure you want to deactivate? This will delete all role tables and data.')">🗑️ Deactivate (Delete Tables)</a>
                </div>

                <?php if ($selected_role): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4 father-of-title">
                        <h2>🔑 Access Permissions: <?= htmlspecialchars($selected_role['role_name']) ?></h2>
                        <span class="badge badge-info"><?= count($all_routes) ?> routes found</span>
                    </div>

                    <?php
                    $legend_colors = [];
                    foreach ($all_routes as $route) {
                        if (!in_array($route['color'], $legend_colors)) {
                            $legend_colors[] = $route['color'];
                        }
                    }
                    $dirColors = $routeScanner->getDirColors();
                    ?>
                    <?php if (count($legend_colors) > 0): ?>
                        <div class="legend">
                            <strong>📌 Directory Colors:</strong>
                            <div class="legend-colors">
                                <?php foreach ($legend_colors as $color): ?>
                                    <span class="legend-item">
                                        <span class="legend-color" style="background-color:<?= $color ?>;"></span>
                                        <?php
                                        $dir_name = 'Sub Directory';
                                        foreach ($dirColors as $dir => $c) {
                                            if ($c === $color && $dir !== 'root') {
                                                $dir_name = ucwords(str_replace(['/', '_', '-'], ' ', $dir));
                                                break;
                                            }
                                        }
                                        if ($color === '#4CAF50') $dir_name = 'Root Pages';
                                        echo htmlspecialchars($dir_name);
                                        ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="search-box">
                        <label for="directory_filter">📁 Select Directory:</label>
                        <select id="directory_filter" class="form-control" onchange="window.location.href='?role_id=<?= $selected_role_id ?>&dir='+encodeURIComponent(this.value)">
                            <option value="all" <?= $selected_directory === 'all' ? 'selected' : '' ?>>🌐 ALL (All Directories)</option>
                            <option value="root" <?= $selected_directory === 'root' ? 'selected' : '' ?>>📄 Root Pages (Independent)</option>
                            <?php foreach ($directories as $dir): if ($dir === 'all' || $dir === 'root') continue;
                                $display_name_dir = ucwords(str_replace(['/', '_', '-'], ' ', $dir));
                                $indent = substr_count($dir, '/') * 2;
                                $indent_str = str_repeat('&nbsp;', $indent); ?>
                                <option value="<?= htmlspecialchars($dir) ?>" <?= $selected_directory === $dir ? 'selected' : '' ?>><?= $indent_str ?>📁 <?= htmlspecialchars($display_name_dir) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" onclick="window.location.href='?role_id=<?= $selected_role_id ?>&dir='+encodeURIComponent(document.getElementById('directory_filter').value)">🔍 Go</button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_access">
                        <input type="hidden" name="role_id" value="<?= $selected_role_id ?>">
                        <div class="card">
                            <div class="card-header">
                                <div style="display:flex;">
                                    <div style="flex:1;"><strong>Route</strong></div>
                                    <div style="width:120px;text-align:center;" class="card-header-access"><strong>Access</strong></div>
                                    <div style="width:120px;text-align:center;" class="card-header-file"><strong>File</strong></div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($all_routes)): ?>
                                    <div style="padding:20px;text-align:center;color:#6c757d;">📂 No routes found in this directory.</div>
                                <?php else: ?>
                                    <?php foreach ($all_routes as $route): ?>
                                        <?php $has_access = isset($role_access[$route['route']]) ? $role_access[$route['route']] : 0;
                                        if (isset($route['route']) && (! str_contains($route['route'], "/") && ! isset($role_access[$route['route']]))) {
                                            $routeN = $route['route'];
                                            $roleId = $_GET['role_id'] ?? 1;
                                            $stm = $pdo->prepare("INSERT INTO ctrx_roles_access (role_id, route, has_access) VALUES('$roleId', '$routeN', 1)");
                                            $stm->execute();
                                            $has_access = 1;
                                        }
                                        $routeName = $route['route'];
                                        $routeNameQ = "'" . $route['route'] . "'";
                                        $roleNameQ = "'" . $selected_role['role_name'] . "'";  ?>
                                        <div class="route-item">
                                            <div class="row">
                                                <div class="col-route">
                                                    <span class="route-path" style="color:<?= $route['color'] ?>;">
                                                        <?= htmlspecialchars($route['route']) ?>
                                                    </span>
                                                    <div class="route-name"><?= htmlspecialchars($route['display_name']) ?></div>
                                                </div>
                                                <div class="col-access">
                                                    <label class="switch">
                                                        <input type="checkbox" name="access[<?= htmlspecialchars($route['route']) ?>]" value="1" <?= $has_access ? 'checked' : '' ?> onchange="updateBadge(this<?= !str_contains($routeName, '/') ? ', true, ' . $routeNameQ . ', ' . $roleNameQ : '' ?>)">
                                                        <span class="slider"></span>
                                                    </label>
                                                    <span class="badge badge-<?= $has_access ? 'success' : 'secondary' ?>" id="badge_<?= md5($route['route']) ?>"><?= $has_access ? 'Yes' : 'No' ?></span>
                                                </div>
                                                <div class="col-file">
                                                    <small class="text-muted"><?= $route['route'] . ".php" ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success">💾 Save Permissions</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAll(true)">✓ Select All</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAll(false)">✕ Unselect All</button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div style="font-size:60px;color:#ccc;">🔒</div>
                        <h4 class="mt-3">No role selected</h4>
                        <p class="text-muted">Please select a role from the sidebar to manage its permissions.</p>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <small class="text-muted">
                        🗄️ Database: <?= strtoupper($dbType) ?> |
                        Host: <?= DB_HOST ?>:<?= DB_PORT ?> |
                        Tables: ctrx_roles, ctrx_roles_access |
                        Directory: <?= htmlspecialchars($display_name) ?> |
                        Routes: <?= count($all_routes) ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="modal-overlay" id="editRoleModal">
        <div class="modal">
            <form method="POST">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="role_id" id="edit_role_id">
                <div class="modal-header">
                    <h5>Edit Role</h5>
                    <button type="button" class="btn-close" onclick="closeModal('editRoleModal')">×</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_role_name" style="display:block;margin-bottom:5px;font-weight:500;">Role Name</label>
                        <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description" style="display:block;margin-bottom:5px;font-weight:500;">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="closeModal('editRoleModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-overlay" id="deleteRoleModal">
        <div class="modal">
            <form method="POST">
                <input type="hidden" name="action" value="delete_role">
                <input type="hidden" name="role_id" id="delete_role_id">
                <div class="modal-header">
                    <h5>Delete Role</h5>
                    <button type="button" class="btn-close" onclick="closeModal('deleteRoleModal')">×</button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the role <strong id="delete_role_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone and will remove all access permissions for this role.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="closeModal('deleteRoleModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Role</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function editRole(id, name, description) {
            document.getElementById('edit_role_id').value = id;
            document.getElementById('edit_role_name').value = name;
            document.getElementById('edit_description').value = description || '';
            document.getElementById('editRoleModal').classList.add('show');
        }

        function deleteRole(id, name) {
            document.getElementById('delete_role_id').value = id;
            document.getElementById('delete_role_name').textContent = name;
            document.getElementById('deleteRoleModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function selectAll(checked) {
            document.querySelectorAll('input[name^="access["]').forEach(function(checkbox) {
                checkbox.checked = checked;
                updateBadge(checkbox);
            });
        }

        function updateBadge(checkbox, askFirst = false, route = "", role = "") {
            if (askFirst && !checkbox.checked) {
                let conf = confirm(`Do you want to disable access of '${role}' in page: '${route}'?`);
                if (!conf) {
                    checkbox.checked = true;
                    return;
                };
            }
            const routeItem = checkbox.closest('.route-item');
            if (!routeItem) return;
            const badge = routeItem.querySelector('.badge');
            if (checkbox.checked) {
                badge.textContent = 'Yes';
                badge.className = 'badge badge-success';
            } else {
                badge.textContent = 'No';
                badge.className = 'badge badge-secondary';
            }
        }
        document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>