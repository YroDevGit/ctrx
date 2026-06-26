<?php
include_once "app/php/core/partials/envloader.php";

$dbname = env("database");
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
            background: #f4f6f9;
            min-height: 100vh;
            padding: 2rem 1.5rem;
            position: relative;
            overflow-x: hidden;
            color: #1e293b;
        }

        .container {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 1.5rem;
            padding: 2rem 2rem 2.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9edf2;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .maintitle{
            margin-bottom: 10px;
        }

        h2 small {
            font-size: 1rem;
            font-weight: 400;
            color: #475569;
            margin-left: 8px;
        }

        .msg {
            color: #0f172a;
            margin-bottom: 1.4rem;
            padding: 0.9rem 1.4rem;
            border-radius: 0.75rem;
            font-weight: 500;
            background: #f1f5f9;
            border-left: 4px solid #94a3b8;
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
                transform: translateX(-8px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .tabs {
            display: flex;
            margin-bottom: 2rem;
            gap: 0.5rem;
            background: #f1f5f9;
            padding: 0.4rem;
            border-radius: 0.75rem;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 0.7rem 0;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            letter-spacing: 0.3px;
            background: transparent;
            color: #475569;
        }

        .tab.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.5);
            color: #0f172a;
        }

        .section {
            display: none;
            animation: fadeSlide 0.3s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeSlide {
            from {
                opacity: 0;
                transform: translateY(6px);
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
            color: #334155;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        input,
        select,
        button,
        .file-label {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            color: #0f172a;
            transition: all 0.2s;
            outline: none;
            font-weight: 500;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>");
            background-repeat: no-repeat;
            background-position: right 1rem center;
        }

        select option {
            background: #ffffff;
            color: #0f172a;
        }

        input:focus,
        select:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.2);
            background: #ffffff;
        }

        input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        button {
            background: #0f172a;
            border: none;
            margin-top: 1.8rem;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.2s;
            color: #ffffff;
            padding: 0.85rem 1rem;
        }

        button:hover {
            background: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 1rem;
            background: #f8fafc;
            padding: 0.7rem 1rem;
            border-radius: 0.75rem;
        }

        .checkbox input {
            width: 1.1rem;
            height: 1.1rem;
            margin-top: 0;
            accent-color: #0f172a;
            box-shadow: none;
            border-radius: 0.25rem;
        }

        .checkbox label {
            margin: 0;
            text-transform: none;
            font-weight: 500;
            font-size: 0.9rem;
            color: #334155;
        }

        .format-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .format-option {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            cursor: pointer;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s;
        }

        .format-option.selected {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .format-option input {
            width: auto;
            margin: 0;
            transform: scale(1.1);
            accent-color: #0f172a;
        }

        .format-option label {
            margin: 0;
            text-transform: none;
            font-size: 0.85rem;
            cursor: pointer;
            color: #334155;
            font-weight: 500;
        }

        hr {
            margin: 1.6rem 0 0.5rem;
            border-color: #e2e8f0;
        }

        .icon-badge {
            display: inline-block;
            font-size: 1.1rem;
            margin-right: 6px;
        }

        input[type="file"] {
            padding: 0.6rem;
            cursor: pointer;
            background: #f8fafc;
            color: #334155;
        }

        input[type="file"]::file-selector-button {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 6px 14px;
            color: #0f172a;
            margin-right: 12px;
            cursor: pointer;
            transition: 0.2s;
            font-weight: 500;
        }

        input[type="file"]::file-selector-button:hover {
            background: #e2e8f0;
        }

        .inline-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.5rem;
            text-align: center;
        }

        .table-count {
            font-size: 0.75rem;
            color: #64748b;
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
                font-size: 0.85rem;
                padding: 0.5rem 0;
            }

            .format-group {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #94a3b8;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        a {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
        }

        a:hover {
            color: #0f172a;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="maintitle">
        <h2>
            ⚡CTRX LIGHTNING CORE 
        </h2>
        <div><small>DATABASE PULSE | IMPORT / EXPORT</small></div>
        </div>

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
                    <div class="inline-hint" style="color: #b91c1c;">⚠️ No tables found in database</div>
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
            <div class="inline-hint">➤ Select a table and download in your chosen format</div>
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

        <div style="margin-top: 1.5rem; font-size: 0.95rem; text-align: center;">
            <a href="<?= $backpage ?? '/' ?>">← I'm done</a>
        </div>

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

            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length) {
                        const fileName = e.target.files[0].name;
                        const oldMsg = fileInput.parentNode.querySelector('.file-feedback');
                        if (oldMsg) oldMsg.remove();
                        const span = document.createElement('div');
                        span.className = 'file-feedback';
                        span.innerText = `📎 File ready: ${fileName}`;
                        span.style.fontSize = '0.75rem';
                        span.style.marginTop = '6px';
                        span.style.color = '#475569';
                        fileInput.insertAdjacentElement('afterend', span);
                        setTimeout(() => span.remove(), 3000);
                    }
                });
            }

            const selectElement = document.querySelector('select[name="table"]');
            if (selectElement) {
                selectElement.addEventListener('focus', () => {
                    selectElement.style.borderColor = '#94a3b8';
                });
                selectElement.addEventListener('blur', () => {
                    selectElement.style.borderColor = '#e2e8f0';
                });
            }
        })();
    </script>
</body>

</html>