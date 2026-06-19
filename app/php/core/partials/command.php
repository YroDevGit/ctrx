<?php


if (PHP_SAPI !== 'cli') {
    echo "This script should only be run from the command line.";
    exit(1);
}

function display_result(string $message, $end = false)
{
    echo $message;
    echo "\n";
    if ($end) {
        exit;
    }
}

function AddAllBaseTable($dbname)
{
    $pdo = pdo($dbname);
    $stmnt = $pdo->prepare("SHOW TABLES");
    $stmnt->execute();
    $tables = $stmnt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $filename) {
        $newname  = ucfirst($filename);
        $phpFile = "app/base/" . ucfirst($newname) . ".php";

        $phpContent = <<<EOT
    <?php 
    namespace Tables;
    use Classes\BaseTable;

    class $newname extends BaseTable {
        
        protected \$table = "$filename";

        protected \$fillable = [];

        protected \$guarded = [];

        protected \$hidden = [];

        protected \$timestamps = false;
    }
    ?>
    EOT;

        if (file_exists($phpFile)) {
            echo "✔️ Base file already created: $phpFile\n\n";
            continue;
        } else {
            if (file_put_contents($phpFile, $phpContent) !== false) {
                echo "✔️ Base file created successfully: $phpFile\n\n";
            } else {
                continue;
            }
        }
    }
}

$arguments = $argv;
$route = isset($arguments[1]) ? strtolower($arguments[1]) : '';
$filename = isset($arguments[2]) ? $arguments[2] : '';
$extra = isset($arguments[3]) ? $arguments[3] : '';
$exxr = isset($arguments[4]) ? $arguments[4] : '';

if ($route == "run" || $route == "server") {
    include "envloader.php";
    $host = env("rootpath");
    $exp = explode("//", $host);
    $runner = $exp[1];

    $portExp = explode(":", $runner);
    $h = $portExp[0];
    $p = $portExp[1];
    if (file_exists("exec.php")) {
        include "exec.php";
    }

    if ($host == null) {
        echo "❌ ERROR: 'rootpath' has no value (.env file)\n";
        echo "💡 You can use: http://localhost:9999 as rootpath\n\n";
        exit;
    }

    if ($filename == "mobile" || $filename == "public") {
        $host = env("rootpath");
        $root = realpath(__DIR__ . '/../../');
        $cmd = "php -S 0.0.0.0:$p -t $root";
        echo "\n⚡⚡ CodeTazer Framework by CodeYRO⚡⚡\n\n";
        echo "🌐 public ip @ " . "\033[41m$host\033[0m\n" . "\n";
        echo "--You can stop server by CTRL+C on your console\n\n";
        passthru($cmd);
        exit;
    }

    $dir = env("main_dir");
    $dir_msg = "";
    $dir_comm = "";
    if ($dir != null) {
        $dir = realpath(__DIR__ . '/../../');

        if (!is_dir($dir)) {
            echo "❌ Directory $dir does not exist.\n";
            exit(1);
        }
        $dir_comm = " -t \"$dir\"";
        $dir_msg = "📁 Document root: $dir\n";
    }

    $fwork = "CodeTazer Framework by CodeYRO";
    echo "\n⚡⚡ \033[1m$fwork\033[0m⚡⚡\n\n";
    echo "🔄 Serving at " . "\033[1;33m$host\033[0m\n" . "\n";
    echo "🔗 Front-End:  " . "\033[32m$host\033[0m\n";
    echo "🔗 Back-End:  " . "\033[32m$host/api/\033[0m\n";
    echo $dir_msg;
    echo "--You can stop server by CTRL+C on your console\n\n";


    $phpPath = PHP_BINARY;

    $cmd = "php -S $runner index.php" . $dir_comm;
    passthru($cmd);
    exit;
} else if ($route == "tables") {
    if (! $filename) {
        echo "❌ Invalid tables command.!";
        exit;
    }
    if ($filename == "sync") {
        include "app/php/core/partials/envloader.php";
        include "app/php/core/partials/be.php";
        $db = env("database");
        if (! $db) {
            echo "❌ Database not set @env.!";
            exit;
        }
        AddAllBaseTable($db);
    }
} else if ($route == "+class") {
    if ($filename == "") {
        echo "❌ Please provide a filename for the class.\n";
        exit(1);
    }
    $newname = ucfirst($filename);
    $phpFile = "app/php/core/classes/" . ucfirst($newname) . ".php";

    $phpContent = <<<EOT
    <?php
    namespace Classes;

    class $newname{

        //create a function here...

    }
    ?>
    EOT;

    if (file_exists($phpFile)) {
        echo "❌ File already exists. Please choose a different name.\n";
        exit(1);
    } else {
        if (file_put_contents($phpFile, $phpContent) !== false) {
            echo "✔️ Class file created successfully: $phpFile\n\n";
            exit(0);
        } else {
            echo "❌ Failed to create Class file.\n";
            exit(1);
        }
    }
} else if ($route == "+controller" || $route == "+ctrl" || $route == "+c") {
    if ($filename == "") {
        echo "❌ Please provide a filename for the controller.\n";
        exit(1);
    }
    if (! str_contains($filename, "/")) {
        echo "❌ Please provide a valid route name, Example: admin/add.\n";
        exit(1);
    }

    $apipath = "";
    if ($extra && str_starts_with($extra, "--")) {
        $extra = substr($extra, 2);
        //$extra = $explode[1] ?? null;
        if (! $extra) {
            echo "❌ unable add route format.\n";
            exit(1);
        }
        $apipath = "$extra/";
    }
    $newname = ucfirst($filename);
    $exp = explode("/", $filename);
    $fdr = $exp[0];
    $fls = $exp[sizeof($exp) - 1];
    unset($exp[sizeof($exp) - 1]);
    if (!$fdr || !$fls) {
        echo "❌ Invalid file format";
        exit;
    }

    $fpath = implode("/", $exp);
    $exp1 = explode(",", $fls);
    $filename = "";
    $counter = 0;
    echo "\n";
    foreach ($exp1 as $ee) {
        $filename = $apipath . $fpath . "/" . $ee;
        $phpFile = "app/_controller/" . $filename . ".php";

        $phpContent = <<<EOT
    <?php //route: $filename

    //Add codes here...
    
    
    EOT;

        if (file_exists($phpFile)) {
            echo "✔️ Controller file [$filename] already created @ $phpFile.\n\n";
        } else {
            $directory = dirname($phpFile);

            if (!is_dir($directory)) {
                if (!mkdir($directory, 0777, true)) {
                    echo "Failed to create directories: $directory";
                    exit;
                }
            }

            if (file_put_contents($phpFile, $phpContent) !== false) {
                echo "✔️ Controller file [$filename] created successfully @ $phpFile.\n\n";
            } else {
                echo "❌ Failed to create Route file.\n";
            }
        }
        $counter += 1;
    }
    echo "\n";
    exit;
} else if ($route == "+model") {
    if ($filename == "") {
        echo "❌ Please provide a filename for the model.\n";
        exit(1);
    }
    $newname = ucfirst($filename);
    $phpFile = "app/model/" . ucfirst($newname) . ".php";

    $phpContent = <<<EOT
    <?php 
    namespace Models;
    
    class $newname{
        
        public function __construct() {
            // Constructor code here
            // You can initialize properties or perform setup tasks
        }

        static function test(){
            return "Hello CodeTazer user. This is model file";
        }

    

    }
    EOT;

    if (file_exists($phpFile)) {
        echo "❌ File already exists. Please choose a different name.\n";
        exit(1);
    } else {
        if (file_put_contents($phpFile, $phpContent) !== false) {
            echo "\n✔️ Model file created successfully: $phpFile\n\n";
            exit(0);
        } else {
            echo "❌ Failed to create model file.\n\n";
            exit(1);
        }
    }
} else if ($route == "routes" || $route == "get_routes") {
    echo "\n";
    $projectRoot = realpath(__DIR__ . "/../..");
    $path = $projectRoot . "/app/controller";
    if (!is_dir($path)) {
        die("❌ Directory not found: " . $path);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path)
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $bee = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $bee = str_replace('\\', "/", $bee);
            if (substr($bee, -4) == '.php') {
                $bax = substr($bee, 0, -4);
                echo $bax . "\n";
                continue;
            }
            echo $bee . "\n";
        }
    }
    echo "\n";
    exit;
}
if ($route == "update") {
    if ($filename == "classes") {
        function deleteFolder($dir)
        {
            if (!file_exists($dir)) return true;
            if (!is_dir($dir)) return unlink($dir);

            foreach (scandir($dir) as $item) {
                if ($item == '.' || $item == '..') continue;
                if (!deleteFolder($dir . DIRECTORY_SEPARATOR . $item)) return false;
            }
            return rmdir($dir);
        }

        function downloadFolder($apiUrl, $targetDir)
        {
            $opts = [
                "http" => [
                    "header" => "User-Agent: PHP\r\n"
                ]
            ];
            $context = stream_context_create($opts);
            $response = file_get_contents($apiUrl, false, $context);

            if ($response === FALSE) {
                echo "❌ Error fetching $apiUrl";
                exit;
            }

            $items = json_decode($response, true);
            if (!is_array($items)) {
                echo "❌ Invalid API response";
                exit;
            }

            @mkdir($targetDir, 0777, true);

            echo "\n\CTRX\n\n";
            echo "Updating classes....\n\n";
            $sz = sizeof($items);
            $ct = 1;
            foreach ($items as $item) {
                $localPath = $targetDir . "/" . $item['name'];
                $p = $ct / $sz;
                $p = intval($p * 100);


                if ($item['type'] === 'file') {
                    echo "⬇️  $p%  Downloading file: {$item['path']}\n";
                    $content = file_get_contents($item['download_url']);
                    file_put_contents($localPath, $content);
                } elseif ($item['type'] === 'dir') {
                    downloadFolder($item['url'], $localPath);
                }
                $ct += 1;
            }
        }

        $root = realpath(__DIR__ . '/../../');
        $targetDir = $root . "/app/php/core/classes";
        $apiUrl = "https://api.github.com/repos/YroDevGit/CodeTazer/contents/_backend/core/partials/classes?ref=main";

        deleteFolder($targetDir);

        downloadFolder($apiUrl, $targetDir);

        echo "\n";
        echo "🎉 CodeTazer App Classes updated!\n\n";
        exit;
    } else if ($filename == "file") {
        $updt = "";
        if ($extra == "") {
            echo "❌ Please enter the file relative path to update\n";
            exit;
        }

        $root = realpath(__DIR__ . '/../../');
        $targetFile = $root . DIRECTORY_SEPARATOR . $extra;
        $rawUrl = "https://raw.githubusercontent.com/YroDevGit/CodeTazer/main/" . str_replace('\\', '/', $extra);

        if ($extra == "index.php" || $extra == "index") {
            $targetFile = $root . DIRECTORY_SEPARATOR . "index.php";
            $rawUrl = "https://raw.githubusercontent.com/YroDevGit/CodeTazer/main/index.php";
        }
        if ($extra == "command") {
            $targetFile = $root . DIRECTORY_SEPARATOR . "_backend\core\command";
            $rawUrl = "https://raw.githubusercontent.com/YroDevGit/CodeTazer/main/_backend/core/command";
        }

        if ($ext == "--main") {
            $targetFile = $root . DIRECTORY_SEPARATOR . "_backend\core\command";
            $rawUrl = "https://raw.githubusercontent.com/YroDevGit/CodeTazer/main/_frontend/pages/main.php";
        }

        if (!file_exists($targetFile)) {
            if ($exxr === "--mkdir") {
                $updt = "✅ $targetFile is created!\n\n";
                @mkdir(dirname($targetFile), 0777, true);
                if (file_put_contents($targetFile, "...") === false) {
                    echo "❌ Failed to create $targetFile\n";
                    exit;
                }
                echo "📂 Directory created and placeholder file added: $targetFile\n";
            } else {
                echo "❌ File $targetFile does not exist (use --mkdir to create it)\n";
                exit;
            }
        } else {
            if (!is_file($targetFile)) {
                echo "❌ $targetFile is a directory, not a file\n";
                exit;
            }
            echo "🔃 Updating $targetFile.....\n";
        }

        echo "🔃 Fetching new content from CodeTazer.....\n";

        $content = file_get_contents($rawUrl);
        if ($content === false) {
            echo "❌ Failed to fetch content from $rawUrl\n";
            exit;
        }

        echo "🔃 Almost done.....\n";
        @mkdir(dirname($targetFile), 0777, true);

        if (file_put_contents($targetFile, $content) === false) {
            echo "❌ Failed to write $targetFile\n";
            exit;
        }

        if ($updt) {
            echo $updt;
            exit;
        }
        echo "✅ $targetFile is now updated!\n\n";
        exit;
    } else {
        echo "❌ Invalid update parameter.";
        exit;
    }
} else if ($route == "--secret") {
    $realpath = realpath(__DIR__ . "/../..");
    $dir = $realpath . "/views/code/src/mods/";
    if (! is_dir($dir)) {
        $creating = @mkdir($dir, 0777, true);
    }
    $secret = ! $filename ? "codetazer_yroez" : $filename;
    try {
        $fl = "env.json";
        $fullpath = $dir . $fl;
        if (file_exists($fullpath)) {
            unlink($fullpath);
        }
        $content = <<<EOT
            {  
                "appname": "codetazer",
                "author": "tyronemalocon",
                "secret": "$secret",
                "key": "codetazerapp",
                "fe": "frontend",
                "be": "backend"
            }
            EOT;

        if (file_put_contents($fullpath, $content)) {
            echo "✔️ Secret has been generated\n\n";
            exit;
        } else {
            echo "❌ Error generating secret\n\n";
            exit;
        }
    } catch (Exception $e) {
        $a = 1;
    }
} else if ($route == "+test") {
    if ($filename == "") {
        echo "❌ Please provide a filename for the test script.\n";
        exit(1);
    }
    $phpFile = $filename;

    if (!is_dir("test/")) {
        if (!mkdir("test/", 0777, true)) {
            echo "Failed to create directories: $directory";
            exit;
        }
    }
    $bname = ucfirst($phpFile);

    $phpContent = <<<EOT
    <?php
    use Classes\CtrTest as Test;
    require_once 'vendor/autoload.php';

    class Arg{
        public function __construct() {
            //
        }

        //create a function here...
        function main(){
            Test::write("Test1", function(){
                //Write test here
                
            });
        }

    }
    EOT;

    $phpFile = "test/" . $phpFile;

    if (file_exists($phpFile)) {
        echo "❌ File already exists. Please choose a different name.\n\n";
        exit(1);
    } else {
        if (file_put_contents($phpFile, $phpContent) !== false) {
            echo "✔️ Library file created successfully: $phpFile\n\n";
            exit(0);
        } else {
            echo "❌ Failed to create Library file.\n\n";
            exit(1);
        }
    }
} else if ($route == "+middleware") {
    if ($filename == "") {
        echo "❌ Please provide a filename for the library.\n";
        exit(1);
    }
    $newname = $filename;
    $phpFile = "app/middleware/" . $newname . ".php";

    $phpContent = <<<EOT
    <?php
    
    //Middleware: $newname



    EOT;

    if (file_exists($phpFile)) {
        echo "❌ File already exists. Please choose a different name.\n";
        exit(1);
    } else {
        if (file_put_contents($phpFile, $phpContent) !== false) {
            echo "✔️ Middleware file created successfully: $phpFile\n\n";
            exit(0);
        } else {
            echo "❌ Failed to create Middleware file.\n";
            exit(1);
        }
    }
}
else if($route == "author"){
    echo "\n";
    $tyronename = "CTRX by CodeYRO";
    echo "\033[1;33m$tyronename\033[0m\n";
    echo "Light, Fast, Easy and Full of features\n";
    echo "Made by Filipino brilliance\n\n";
    $tyroneEmzname = "Tyrone Limen Malocon 2025";
    echo "\033[32m$tyroneEmzname\033[0m\n";
    exit;
}
else if ($route == "download:table") {
    include "app/php/core/partials/envloader.php";
    $dbname = env("database");
    if (!$dbname) {
        echo "❌ No Database found @ .env\n\n";
        exit;
    }

    if ($filename == "") {
        echo "❌ Please input table name.\n\n";
        exit(1);
    }

    $table = $filename;

    include_once "app/php/core/partials/be.php";
    include "app/php/core/partials/backend.php";
    $pdo = pdo($dbname);

    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT * FROM `$table`");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $json = [
        "table"   => $table,
        "columns" => $columns,
        "data"    => $data,
    ];

    $filename = $table . "_ctrx.json";

    if (file_exists($filename)) {
        if (!unlink($filename)) {
            echo "❌ Failed to delete existing file: {$filename}\n";
            exit(1);
        }
    }

    if (file_put_contents(
        $filename,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) === false) {
        echo "❌ Failed to export table.\n";
        exit(1);
    }

    echo "✅ Exported to {$filename}\n";
} else if ($route == "db:import" || $route == "db:migrate") {
    include "app/php/core/partials/envloader.php";
    $dbname = env("database");
    if (! $dbname) {
        echo "❌ No Database found @ .env\n\n";
        exit;
    }

    if ($filename == "") {
        echo "❌ Please input json filename for migration.\n\n";
        exit(1);
    }

    if (! file_exists("app/php/db/" . $filename . ".php")) {
        echo "❌ Invalid migration name\n";
        exit;
    }

    include_once "app/php/core/partials/be.php";
    include "app/php/core/partials/backend.php";
    $pdo = pdo($dbname, true);
    $stmnt = $pdo->prepare("SHOW DATABASES LIKE '" . $dbname . "'");
    $stmnt->execute();
    $rowcount = $stmnt->rowCount();
    if (! $rowcount) {
        $stmnt = $pdo->prepare("Create database `$dbname`;");
        $stmnt->execute();
        $rowcount = $stmnt->rowCount();
        echo "✔️ Database $dbname created\n\n";
    }

    include "app/php/core/classes/Migration.php";

    $jsonfile = "app/php/db/" . $filename . ".php";

    $jsonfile = str_ends_with($jsonfile, ".php") ? $jsonfile : $jsonfile . ".php";

    include $jsonfile;

    echo "Auto sync base table, please wait....";
    echo "\n";
    AddAllBaseTable($dbname);
    echo "-------\n";
    echo "✔️ Done\n";

    exit;
} elseif ($route == "dbload" || $route == "db:load") {
    include "app/php/core/partials/envloader.php";
    $dbname = env("database");
    if ($dbname == null || $dbname == "") {
        echo "❌ database not found @ .env file\n";
        exit;
    }
    include "app/php/core/partials/be.php";
    try {
        $pdo = pdo($dbname, true);
        $stmnt = $pdo->prepare("Create database `$dbname`;");
        $stmnt->execute();
        $rowcount = $stmnt->rowCount();
        echo "✔️ Database $dbname created\n\n";
        exit;
    } catch (PDOException $e) {
        echo "❌ " . $e->getMessage();
        exit;
    }
} else if ($route == "sync:tables" || $route == "db:sync") {
    include "app/php/core/partials/envloader.php";
    $dbname = env("database");
    if (! $dbname) {
        echo "❌ No Database found @ .env\n\n";
        exit;
    }

    include "app/php/core/partials/be.php";
    include "app/php/core/classes/Migration.php";

    echo "Auto sync base table, please wait....";
    echo "\n";
    AddAllBaseTable($dbname);
    echo "-------\n";
    echo "✔️ Done\n";
} else if ($route == "+library") {
    if ($filename == "") {
        echo "❌ Please provide a filename for Library.\n";
        exit(1);
    }
    $phpFile = $filename;

    if (!is_dir("app/library/")) {
        if (!mkdir("app/library/", 0777, true)) {
            echo "Failed to create directories: $directory";
            exit;
        }
    }
    $bname = ucfirst($phpFile);

    $phpContent = <<<EOT
    <?php
    namespace Classes;

    class $bname{
        public function __construct() {
            //
        }

        //create a function here...
        


    }
    EOT;

    $phpFile = "app/library/" . $phpFile;

    if (file_exists($phpFile)) {
        echo "❌ File already exists. Please choose a different name.\n\n";
        exit(1);
    } else {
        if (file_put_contents($phpFile, $phpContent) !== false) {
            echo "✔️ Library file created successfully: $phpFile\n\n";
            exit(0);
        } else {
            echo "❌ Failed to create Library file.\n\n";
            exit(1);
        }
    }
} else {
    echo "\n❌ Invalid command.\n\n";
    exit(1);
}
