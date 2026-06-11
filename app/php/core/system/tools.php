<?php
include_once "app/php/core/partials/envloader.php";

$dbname = getenv("database");
if (!$dbname) {
    die("❌ No Database found @ .env");
}

include_once "app/php/core/partials/be.php";
include_once "app/php/core/partials/backend.php";

$pdo = pdo($dbname);

$message = "";

if (isset($_POST['export_table'])) {
    $table = $_POST['table'] ?? "";

    if ($table == "") {
        $message = "❌ Please input table name.";
    } else {

        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->query("SELECT * FROM `$table`");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $json = [
            "table" => $table,
            "columns" => $columns,
            "data" => $data,
        ];

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="'.$table.'.json"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (isset($_POST['import_table'])) {

    if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] != 0) {
        $message = "❌ Please upload a valid JSON file.";
    } else {

        $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($jsonContent, true);

        if (!$data || !isset($data['table'], $data['data'])) {
            $message = "❌ Invalid JSON format.";
        } else {

            $table = $data['table'];
            $rows = $data['data'];

            $replaceAll = isset($_POST['replace_all']);

            if ($replaceAll) {
                $pdo->exec("TRUNCATE TABLE `$table`");
            }

            if (count($rows) > 0) {

                foreach ($rows as $row) {

                    $columns = array_keys($row);
                    $placeholders = ":" . implode(", :", $columns);

                    $sql = "INSERT INTO `$table` (`" . implode("`,`", $columns) . "`)
                            VALUES ($placeholders)";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($row);
                }

                $message = $replaceAll
                    ? "✅ Table replaced successfully: {$table}"
                    : "✅ Data appended successfully to {$table}";
            } else {
                $message = "⚠️ No data found in JSON.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CTRX Import / Export</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 600px;
            margin: auto;
        }

        .tabs {
            display: flex;
            margin-bottom: 15px;
        }

        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            background: #ddd;
        }

        .tab.active {
            background: #333;
            color: white;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        input, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }

        button {
            background: #333;
            color: white;
            border: none;
            cursor: pointer;
        }

        .msg {
            margin-bottom: 10px;
            padding: 10px;
            background: #eee;
        }

        .checkbox {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox input {
            width: auto;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>CTRX Database Tool</h2>

    <?php if ($message): ?>
        <div class="msg"><?= $message ?></div>
    <?php endif; ?>

    <div class="tabs">
        <div class="tab active" onclick="switchTab(0)">Export</div>
        <div class="tab" onclick="switchTab(1)">Import</div>
    </div>

    <div class="section active">
        <form method="POST">
            <label>Table Name</label>
            <input type="text" name="table" placeholder="tbl_user" required>
            <button name="export_table">Export Table</button>
        </form>
    </div>

    <div class="section">
        <form method="POST" enctype="multipart/form-data">
            <label>JSON File</label>
            <input type="file" name="json_file" required>

            <div class="checkbox">
                <input type="checkbox" name="replace_all" id="replace_all">
                <label for="replace_all">Replace all data (truncate table first)</label>
            </div>

            <button name="import_table">Import Table</button>
        </form>
    </div>

</div>

<script>
function switchTab(i) {
    let tabs = document.querySelectorAll(".tab");
    let sections = document.querySelectorAll(".section");

    tabs.forEach(t => t.classList.remove("active"));
    sections.forEach(s => s.classList.remove("active"));

    tabs[i].classList.add("active");
    sections[i].classList.add("active");
}
</script>

</body>