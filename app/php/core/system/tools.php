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

ctrx_force_save_previous_pages(previous_page());

$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

function exportAsCSV($table, $columns, $data)
{
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, [$table]);
    fputcsv($output, $columns);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

function exportAsExcel($table, $columns, $data)
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', $table);
    $colIndex = 'A';
    foreach ($columns as $column) {
        $sheet->setCellValue($colIndex . '2', $column);
        $colIndex++;
    }
    $rowIndex = 3;
    foreach ($data as $row) {
        $colIndex = 'A';
        foreach ($columns as $column) {
            $value = $row[$column] ?? '';
            $sheet->setCellValue($colIndex . $rowIndex, $value);
            $colIndex++;
        }
        $rowIndex++;
    }
    foreach (range('A', $colIndex) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    $content = ob_get_clean();
    echo $content;
}

function readCSV($filePath)
{
    $data = [];
    if (($handle = fopen($filePath, 'r')) !== false) {
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $tableNameRow = fgetcsv($handle);
        $table = $tableNameRow ? $tableNameRow[0] : null;
        $headers = fgetcsv($handle);
        if ($headers) {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) == count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
        }
        fclose($handle);
        return ['table' => $table, 'data' => $data];
    }
    return ['table' => null, 'data' => []];
}

function readExcel($filePath)
{
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    if (empty($rows)) return ['table' => null, 'data' => []];
    $table = $rows[0][0] ?? null;
    $headers = isset($rows[1]) ? $rows[1] : [];
    $data = [];
    for ($i = 2; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (count($row) == count($headers)) {
            $data[] = array_combine($headers, $row);
        }
    }
    return ['table' => $table, 'data' => $data];
}

if (isset($_POST['export_table'])) {
    try {
        $table = $_POST['table'] ?? "";
        $export_format = $_POST['export_format'] ?? 'json';
        if ($table == "") {
            $message = "❌ Please select a table.";
        } else {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            switch ($export_format) {
                case 'csv':
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $table . '.csv"');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    exportAsCSV($table, $columns, $data);
                    break;
                case 'excel':
                    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                        throw new Exception("PhpSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet");
                    }
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment; filename="' . $table . '.xlsx"');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    exportAsExcel($table, $columns, $data);
                    break;
                case 'json':
                default:
                    $json = [
                        "table" => $table,
                        "columns" => $columns,
                        "data" => $data,
                    ];
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="' . $table . '.json"');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    break;
            }
            exit;
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

if (isset($_POST['import_table'])) {
    try {
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] != 0) {
            $message = "❌ Please upload a valid file.";
        } else {
            $fileType = $_POST['file_type'] ?? 'json';
            $fileTmpPath = $_FILES['import_file']['tmp_name'];
            $fileName = $_FILES['import_file']['name'];
            $importedData = null;
            $table = null;
            switch ($fileType) {
                case 'csv':
                    $result = readCSV($fileTmpPath);
                    if (empty($result['data'])) {
                        throw new Exception("CSV file is empty or invalid.");
                    }
                    $table = $result['table'];
                    $importedData = $result['data'];
                    break;
                case 'excel':
                    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                        throw new Exception("PhpSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet");
                    }
                    $result = readExcel($fileTmpPath);
                    if (empty($result['data'])) {
                        throw new Exception("Excel file is empty or invalid.");
                    }
                    $table = $result['table'];
                    $importedData = $result['data'];
                    break;
                case 'json':
                default:
                    $jsonContent = file_get_contents($fileTmpPath);
                    $data = json_decode($jsonContent, true);
                    if (!$data || !isset($data['data'])) {
                        throw new Exception("Invalid JSON format. Expected { table: 'name', data: [...] }");
                    }
                    $table = $data['table'] ?? null;
                    $importedData = $data['data'];
                    break;
            }
            if ($importedData === null || empty($importedData)) {
                throw new Exception("No data found in file.");
            }
            if (empty($table)) {
                $table = $_POST['table_name'] ?? pathinfo($fileName, PATHINFO_FILENAME);
            }
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            if (empty($table)) {
                throw new Exception("Invalid table name.");
            }
            $replaceAll = isset($_POST['replace_all']);
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $tableExists = $stmt->rowCount() > 0;
            if (!$tableExists) {
                $firstRow = $importedData[0];
                $columns = array_keys($firstRow);
                $columnDefs = [];
                foreach ($columns as $col) {
                    $colSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
                    $columnDefs[] = "`$colSafe` TEXT";
                }
                $sql = "CREATE TABLE `$table` (" . implode(", ", $columnDefs) . ")";
                $pdo->exec($sql);
                $message = "✅ Table '{$table}' created automatically. ";
            }
            if ($replaceAll && $tableExists) {
                $pdo->exec("TRUNCATE TABLE `$table`");
            }
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
            $dbColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $inserted = 0;
            $updated = 0;
            foreach ($importedData as $row) {
                if (isset($row['id']) && $row['id'] !== '' && $row['id'] !== null) {
                    $setClauses = [];
                    $params = ['id' => $row['id']];

                    foreach ($dbColumns as $col) {
                        if ($col === 'id') continue;
                        if (isset($row[$col]) && $row[$col] !== '' && $row[$col] !== null) {
                            $setClauses[] = "`$col` = :$col";
                            $params[$col] = $row[$col];
                        }
                    }

                    if (!empty($setClauses)) {
                        $sql = "UPDATE `$table` SET " . implode(", ", $setClauses) . " WHERE `id` = :id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $updated++;
                    }
                } else {
                    $filteredRow = [];
                    foreach ($dbColumns as $col) {
                        if ($col === 'id') continue;
                        $kaw = $row[$col] == "" || $row[$col] == null ? null : $row[$col];
                        if (! $kaw) continue;
                        $filteredRow[$col] = $kaw;
                    }
                    $columns = array_keys($filteredRow);
                    if (!empty($columns)) {
                        $placeholders = ":" . implode(", :", $columns);
                        $sql = "INSERT INTO `$table` (`" . implode("`,`", $columns) . "`)
                                VALUES ($placeholders)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($filteredRow);
                        $inserted++;
                    }
                }
            }
            $action = $replaceAll ? "replaced" : "appended";
            $message .= "✅ {$inserted} new records inserted, {$updated} records updated successfully in '{$table}'";
        }
    } catch (Throwable $e) {
        $message = "❌ " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CTRX Lightning | Database Pulse Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, 'Poppins', sans-serif;
            background: radial-gradient(circle at 20% 30%, #0a0f1e, #03050b);
            min-height: 100vh;
            padding: 2rem 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(45deg,
                    rgba(255, 215, 0, 0.02) 0px,
                    rgba(255, 215, 0, 0.02) 2px,
                    transparent 2px,
                    transparent 8px);
            pointer-events: none;
            z-index: 0;
        }

        .lightning-streak {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: 0.3;
        }

        .lightning-streak::after {
            content: '';
            position: absolute;
            top: -10%;
            left: 20%;
            width: 4px;
            height: 120%;
            background: linear-gradient(180deg, transparent, #ffea80, #ffc107, #ffb347, transparent);
            filter: blur(3px);
            animation: lightningFlash 3s infinite ease-in-out;
            box-shadow: 0 0 20px #ffd966;
        }

        .lightning-streak::before {
            content: '';
            position: absolute;
            top: -5%;
            right: 35%;
            width: 2px;
            height: 110%;
            background: linear-gradient(180deg, transparent, #ffe69b, #ffaa33, transparent);
            filter: blur(5px);
            animation: lightningFlash 4.2s infinite ease-in-out 1s;
        }

        @keyframes lightningFlash {

            0%,
            90%,
            100% {
                opacity: 0;
                transform: scaleY(0.8);
            }

            92% {
                opacity: 1;
                transform: scaleY(1);
            }

            94% {
                opacity: 0.4;
            }

            96% {
                opacity: 1;
            }

            98% {
                opacity: 0;
            }
        }

        .container {
            max-width: 820px;
            margin: 0 auto;
            background: rgba(12, 18, 28, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 2rem;
            padding: 2rem 2rem 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 0 2px rgba(255, 200, 50, 0.2), 0 0 0 5px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 200, 70, 0.5);
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .container::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #00ccff, #ffaa22, #4aadc5, #00ccff);
            border-radius: 2rem;
            z-index: -1;
            opacity: 0.2;
            filter: blur(18px);
            animation: borderPulse 2.5s infinite alternate;
        }

        @keyframes borderPulse {
            0% {
                opacity: 0.2;
                filter: blur(12px);
            }

            100% {
                opacity: 0.6;
                filter: blur(20px);
            }
        }

        h2 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFF3C9, #00ccff, #FDBB17);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 0 8px rgba(255, 200, 0, 0.3);
            margin-bottom: 1.2rem;
        }

        .msg {
            color: yellowgreen;
            margin-bottom: 1.4rem;
            padding: 0.9rem 1.4rem;
            border-radius: 60px;
            font-weight: 500;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(4px);
            border-left: 6px solid;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: flickerMsg 0.4s ease;
        }

        @keyframes flickerMsg {
            0% {
                opacity: 0;
                transform: translateX(-12px);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .tabs {
            display: flex;
            margin-bottom: 2rem;
            gap: 0.8rem;
            background: rgba(0, 0, 0, 0.5);
            padding: 0.5rem;
            border-radius: 80px;
            backdrop-filter: blur(8px);
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 0.8rem 0;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            border-radius: 60px;
            transition: all 0.25s ease;
            letter-spacing: 1px;
            background: rgba(20, 28, 40, 0.7);
            color: #b9c7d9;
            border: 1px solid rgba(255, 200, 80, 0.2);
            backdrop-filter: blur(4px);
        }

        .tab.active {
            background: linear-gradient(95deg, #FFD966, #FFB347);
            color: #0a0a1a;
            box-shadow: 0 0 12px #ffcc44, 0 4px 12px rgba(0, 0, 0, 0.3);
            text-shadow: 0 0 1px rgba(0, 0, 0, 0.2);
            border-color: #FFE484;
        }

        .tab:hover:not(.active) {
            background: rgba(255, 205, 70, 0.25);
            color: #ffe6aa;
            border-color: #ffcc66;
            transform: scale(0.98);
        }

        .section {
            display: none;
            animation: fadeSlide 0.4s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeSlide {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        label {
            display: block;
            margin-top: 1.2rem;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #FFE5A3;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }

        input,
        select,
        button,
        .file-label {
            width: 100%;
            padding: 0.85rem 1rem;
            background: rgba(5, 10, 20, 0.7);
            border: 1.5px solid rgba(255, 200, 80, 0.5);
            border-radius: 1.2rem;
            font-size: 0.95rem;
            color: #F0F3FA;
            transition: all 0.2s;
            outline: none;
            font-weight: 500;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23FFD966' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>");
            background-repeat: no-repeat;
            background-position: right 1rem center;
        }

        select option {
            background: #0a0f1e;
            color: #F0F3FA;
        }

        input:focus,
        select:focus {
            border-color: #FFD966;
            box-shadow: 0 0 15px rgba(255, 210, 70, 0.6);
            background: rgba(8, 14, 24, 0.9);
        }

        input::placeholder {
            color: #6c7a8e;
            font-weight: 400;
        }

        button {
            background: linear-gradient(95deg, #2b2f3f, #1a1e2c);
            border: 1px solid #ffcd7e;
            margin-top: 1.8rem;
            font-weight: bold;
            font-size: 1rem;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
            color: #FFE9B6;
        }

        button:hover {
            background: linear-gradient(95deg, #FFC857, #FFA82E);
            color: #0f0f1a;
            border-color: #FFE484;
            box-shadow: 0 0 18px #ffbb44, 0 4px 12px black;
            transform: translateY(-2px);
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 1rem;
            background: rgba(0, 0, 0, 0.4);
            padding: 0.7rem 1rem;
            border-radius: 2rem;
            backdrop-filter: blur(4px);
        }

        .checkbox input {
            width: 1.3rem;
            height: 1.3rem;
            margin-top: 0;
            accent-color: #ffcc44;
            box-shadow: none;
            border-radius: 0.3rem;
        }

        .checkbox label {
            margin: 0;
            text-transform: none;
            font-weight: 500;
            font-size: 0.9rem;
            color: #ffeaC0;
        }

        .format-group {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .format-option {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 1rem;
            cursor: pointer;
            border: 1px solid rgba(255, 200, 80, 0.3);
            transition: all 0.2s;
        }

        .format-option.selected {
            background: rgba(255, 200, 80, 0.2);
            border-color: #FFD966;
            box-shadow: 0 0 8px rgba(255, 200, 80, 0.3);
        }

        .format-option input {
            width: auto;
            margin: 0;
            transform: scale(1.2);
            accent-color: #ffcc44;
        }

        .format-option label {
            margin: 0;
            text-transform: none;
            font-size: 0.85rem;
            cursor: pointer;
        }

        hr {
            margin: 1.6rem 0 0.5rem;
            border-color: rgba(255, 200, 100, 0.3);
        }

        .icon-badge {
            display: inline-block;
            font-size: 1.1rem;
            margin-right: 6px;
        }

        input[type="file"] {
            padding: 0.7rem;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.6);
            color: #ffdfaa;
        }

        input[type="file"]::file-selector-button {
            background: #2a2f3f;
            border: 1px solid #ffcc66;
            border-radius: 30px;
            padding: 6px 14px;
            color: #FFF2CC;
            margin-right: 12px;
            cursor: pointer;
            transition: 0.2s;
        }

        input[type="file"]::file-selector-button:hover {
            background: #ffcc44;
            color: #0f111c;
        }

        .inline-hint {
            font-size: 0.7rem;
            color: #8f9bb3;
            margin-top: 0.3rem;
            text-align: center;
        }

        .table-count {
            font-size: 0.7rem;
            color: #ffdb8e;
            text-align: center;
            margin-top: 0.5rem;
        }

        @media (max-width: 550px) {
            body {
                padding: 1rem;
            }

            .container {
                padding: 1.5rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            .tab {
                font-size: 0.9rem;
                padding: 0.6rem 0;
            }

            .format-group {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        .spark {
            position: fixed;
            width: 3px;
            height: 3px;
            background: #FFDD88;
            border-radius: 50%;
            opacity: 0;
            pointer-events: none;
            z-index: 999;
            filter: blur(1px);
            animation: sparkFloat 1.8s ease-out forwards;
        }

        @keyframes sparkFloat {
            0% {
                opacity: 0.8;
                transform: translateY(0) scale(1);
            }

            100% {
                opacity: 0;
                transform: translateY(-80px) scale(0.5);
            }
        }

        footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c9a;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.7;
        }
    </style>
</head>

<body>

    <div class="lightning-streak"></div>
    <div class="lightning-streak" style="transform: rotate(10deg); opacity:0.2;"></div>

    <div class="container">
        <h2>⚡ CTRX LIGHTNING CORE</h2>
        <h2 style="font-size: 1.2rem; margin-top: -15px; margin-bottom: 20px;">DATABASE PULSE | IMPORT / EXPORT</h2>

        <?php if (!empty($message)): ?>
            <div class="msg">
                <span class="icon-badge">⚡</span>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" data-tab="0">📤 EXPORT</div>
            <div class="tab" data-tab="1">📥 IMPORT / SURGE</div>
        </div>

        <div class="section active" id="exportSection">
            <form method="POST" id="exportForm">
                <label>🗄️ SELECT TABLE TO EXPORT</label>
                <select name="table" required>
                    <option value="" disabled selected>— Select a table —</option>
                    <?php foreach ($allTables as $tbl): ?>
                        <option value="<?= htmlspecialchars($tbl) ?>"><?= htmlspecialchars($tbl) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($allTables)): ?>
                    <div class="inline-hint" style="color: #ffaa66;">⚠️ No tables found in database</div>
                <?php else: ?>
                    <div class="table-count">📊 <?= count($allTables) ?> table(s) available</div>
                <?php endif; ?>

                <label>📁 EXPORT FORMAT</label>
                <div class="format-group" id="exportFormatGroup">
                    <div class="format-option" data-format="json">
                        <input type="radio" name="export_format" value="json" id="export_json" checked>
                        <label for="export_json">JSON</label>
                    </div>
                    <div class="format-option" data-format="csv">
                        <input type="radio" name="export_format" value="csv" id="export_csv">
                        <label for="export_csv">CSV</label>
                    </div>
                    <div class="format-option" data-format="excel">
                        <input type="radio" name="export_format" value="excel" id="export_excel">
                        <label for="export_excel">Excel (XLSX)</label>
                    </div>
                </div>

                <button name="export_table" type="submit">
                    <span>⚡ EXPORT DATA</span>
                </button>
            </form>
            <div class="inline-hint">➤ Lightning export • Select a table and download in your chosen format</div>
        </div>

        <div class="section" id="importSection">
            <form method="POST" enctype="multipart/form-data" id="importForm">
                <label>📂 FILE TYPE</label>
                <div class="format-group" id="importTypeGroup">
                    <div class="format-option" data-format="json">
                        <input type="radio" name="file_type" value="json" id="import_json" checked>
                        <label for="import_json">JSON</label>
                    </div>
                    <div class="format-option" data-format="csv">
                        <input type="radio" name="file_type" value="csv" id="import_csv">
                        <label for="import_csv">CSV</label>
                    </div>
                    <div class="format-option" data-format="excel">
                        <input type="radio" name="file_type" value="excel" id="import_excel">
                        <label for="import_excel">Excel (XLSX)</label>
                    </div>
                </div>

                <label>📂 SELECT FILE</label>
                <input type="file" name="import_file" accept=".json,.csv,.xlsx" required>

                <label>🏷️ TABLE NAME (optional if file contains table name in row 1 column A)</label>
                <input type="text" name="table_name" placeholder="Leave empty to auto-detect from file">

                <div class="checkbox">
                    <input type="checkbox" name="replace_all" id="replace_all">
                    <label for="replace_all">⚡ REPLACE MODE – Truncate table before import</label>
                </div>

                <button name="import_table" type="submit">
                    <span>🔥 IMPORT & STRIKE</span>
                </button>
            </form>
            <div class="inline-hint">CSV/Excel: Row1 ColA = table name, Row2 = headers, then data. JSON: requires 'table' and 'data' fields.</div>
        </div>
        <div style="margin-top: 1rem; font-size:16; text-align:center;"><a style="text-decoration:none;color:#ccb27c;" href="<?= $backpage ?? '/' ?>">← I'm done</a></div>
        <footer>⚡ CTRX THUNDER EDGE • DATABASE FLOW</footer>
    </div>

    <script>
        (function() {
            const tabs = document.querySelectorAll('.tab');
            const sections = {
                0: document.getElementById('exportSection'),
                1: document.getElementById('importSection')
            };

            function switchTab(index) {
                tabs.forEach((tab, i) => {
                    if (i === index) {
                        tab.classList.add('active');
                    } else {
                        tab.classList.remove('active');
                    }
                });
                if (sections[0]) sections[0].classList.remove('active');
                if (sections[1]) sections[1].classList.remove('active');
                if (index === 0 && sections[0]) sections[0].classList.add('active');
                if (index === 1 && sections[1]) sections[1].classList.add('active');
            }

            tabs.forEach((tab, idx) => {
                tab.addEventListener('click', () => {
                    switchTab(idx);
                });
            });

            const exportOptions = document.querySelectorAll('#exportFormatGroup .format-option');
            exportOptions.forEach(opt => {
                const radio = opt.querySelector('input');
                radio.addEventListener('change', () => {
                    exportOptions.forEach(o => o.classList.remove('selected'));
                    if (radio.checked) opt.classList.add('selected');
                });
                if (radio.checked) opt.classList.add('selected');
            });

            const importOptions = document.querySelectorAll('#importTypeGroup .format-option');
            importOptions.forEach(opt => {
                const radio = opt.querySelector('input');
                radio.addEventListener('change', () => {
                    importOptions.forEach(o => o.classList.remove('selected'));
                    if (radio.checked) opt.classList.add('selected');

                    const fileInput = document.querySelector('input[name="import_file"]');
                    if (radio.value === 'json') fileInput.setAttribute('accept', '.json');
                    else if (radio.value === 'csv') fileInput.setAttribute('accept', '.csv');
                    else if (radio.value === 'excel') fileInput.setAttribute('accept', '.xlsx,.xls');
                });
                if (radio.checked) opt.classList.add('selected');
            });

            function createSpark(event, element) {
                const rect = element.getBoundingClientRect();
                const x = event.clientX || rect.left + rect.width / 2;
                const y = event.clientY || rect.top + rect.height / 2;
                for (let i = 0; i < 12; i++) {
                    const spark = document.createElement('div');
                    spark.classList.add('spark');
                    const angle = Math.random() * Math.PI * 2;
                    const vx = (Math.cos(angle) * (Math.random() * 40 + 10)) * (Math.random() > 0.5 ? 1 : -1);
                    const vy = (Math.sin(angle) * (Math.random() * 30 + 15)) * -1 - 10;
                    spark.style.left = x + 'px';
                    spark.style.top = y + 'px';
                    spark.style.transform = `translate(${vx}px, ${vy}px)`;
                    spark.style.width = Math.random() * 6 + 2 + 'px';
                    spark.style.height = spark.style.width;
                    spark.style.background = `hsl(${50 + Math.random() * 20}, 100%, 65%)`;
                    spark.style.boxShadow = '0 0 6px #ffcc44';
                    document.body.appendChild(spark);
                    setTimeout(() => {
                        spark.remove();
                    }, 800);
                }
            }

            function attachSparkToButtons() {
                const btns = document.querySelectorAll('button');
                btns.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        createSpark(e, btn);
                    });
                });
            }

            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"], button');
                    if (submitBtn) {
                        const fakeEvent = {
                            clientX: submitBtn.getBoundingClientRect().left + submitBtn.offsetWidth / 2,
                            clientY: submitBtn.getBoundingClientRect().top + submitBtn.offsetHeight / 2
                        };
                        for (let s = 0; s < 20; s++) createSpark(fakeEvent, submitBtn);
                    }
                    const flashDiv = document.createElement('div');
                    flashDiv.style.position = 'fixed';
                    flashDiv.style.top = '0';
                    flashDiv.style.left = '0';
                    flashDiv.style.width = '100%';
                    flashDiv.style.height = '100%';
                    flashDiv.style.backgroundColor = 'rgba(255, 215, 0, 0.25)';
                    flashDiv.style.pointerEvents = 'none';
                    flashDiv.style.zIndex = '9999';
                    flashDiv.style.animation = 'fadeOutFlash 0.25s ease-out forwards';
                    document.body.appendChild(flashDiv);
                    setTimeout(() => flashDiv.remove(), 300);
                });
            });

            const styleSheet = document.createElement("style");
            styleSheet.textContent = `
            @keyframes fadeOutFlash {
                0% { opacity: 0.7; background-color: rgba(255, 210, 70, 0.5);}
                100% { opacity: 0; background-color: rgba(255, 210, 70, 0);}
            }
        `;
            document.head.appendChild(styleSheet);

            attachSparkToButtons();

            let trailTimeout;
            document.body.addEventListener('mousemove', (e) => {
                if (trailTimeout) return;
                trailTimeout = setTimeout(() => {
                    const miniSpark = document.createElement('div');
                    miniSpark.style.position = 'fixed';
                    miniSpark.style.left = e.clientX - 2 + 'px';
                    miniSpark.style.top = e.clientY - 2 + 'px';
                    miniSpark.style.width = '4px';
                    miniSpark.style.height = '4px';
                    miniSpark.style.background = 'radial-gradient(circle, #ffcc55, #ffaa22)';
                    miniSpark.style.borderRadius = '50%';
                    miniSpark.style.pointerEvents = 'none';
                    miniSpark.style.zIndex = '99999';
                    miniSpark.style.filter = 'blur(1px)';
                    miniSpark.style.opacity = '0.7';
                    document.body.appendChild(miniSpark);
                    setTimeout(() => miniSpark.remove(), 250);
                    trailTimeout = null;
                }, 25);
            });

            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length) {
                        const fileName = e.target.files[0].name;
                        const oldMsg = fileInput.parentNode.querySelector('.file-feedback');
                        if (oldMsg) oldMsg.remove();
                        const span = document.createElement('div');
                        span.className = 'file-feedback';
                        span.innerText = `⚡ File ready: ${fileName}`;
                        span.style.fontSize = '0.7rem';
                        span.style.marginTop = '8px';
                        span.style.color = '#ffe0a3';
                        fileInput.insertAdjacentElement('afterend', span);
                        setTimeout(() => span.remove(), 2000);
                    }
                });
            }

            const selectElement = document.querySelector('select[name="table"]');
            if (selectElement) {
                selectElement.addEventListener('focus', () => {
                    selectElement.style.boxShadow = '0 0 15px #ffaa33';
                });
                selectElement.addEventListener('blur', () => {
                    selectElement.style.boxShadow = 'none';
                });
            }
        })();
    </script>
</body>

</html>