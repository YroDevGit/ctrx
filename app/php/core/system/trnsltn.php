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
$tableName = "translations";

ctrx_force_save_previous_pages(previous_page());

$check = $pdo->query("SHOW TABLES LIKE 'translations'");
$tableExists = $check->rowCount() > 0;

$activationSuccess = false;
if (isset($_POST['activate_table']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' && isset($_POST['action']) && $_POST['action'] == 'activate_table')) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `translations` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `lang` TEXT,
            `name` VARCHAR(255),
            `en` TEXT,
            `str` TEXT,
            `active` INT DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        )");

        $pdo->exec("ALTER TABLE `translations` ADD COLUMN IF NOT EXISTS `active` INT DEFAULT 1");

        $activationSuccess = true;
        $tableExists = true;
        $message = "✅ Translation Vocabulary enabled, You can now import and export languages and word translations.";

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        }
    } catch (PDOException $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '❌ Failed to create table: ' . $e->getMessage()]);
            exit;
        }
        $message = "❌ Failed to create table: " . $e->getMessage();
    }
}

if ($tableExists) {
    $pdo->exec("ALTER TABLE `translations` ADD COLUMN IF NOT EXISTS `active` INT DEFAULT 1");

    function exportAsCSV($langCode, $langName, $data)
    {
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, [$langCode, $langName]);
        fputcsv($output, ['id', 'en', 'str', 'active']);
        foreach ($data as $row) {
            fputcsv($output, [$row['id'], $row['en'], $row['str'], $row['active']]);
        }
        fclose($output);
    }

    function exportAsExcel($langCode, $langName, $data)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', $langCode);
        $sheet->setCellValue('B1', $langName);
        $sheet->setCellValue('A2', 'id');
        $sheet->setCellValue('B2', 'en');
        $sheet->setCellValue('C2', 'str');
        $sheet->setCellValue('D2', 'active');
        $rowIndex = 3;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowIndex, $row['id']);
            $sheet->setCellValue('B' . $rowIndex, $row['en']);
            $sheet->setCellValue('C' . $rowIndex, $row['str']);
            $sheet->setCellValue('D' . $rowIndex, $row['active']);
            $rowIndex++;
        }
        foreach (range('A', 'D') as $col) {
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
            $langRow = fgetcsv($handle);
            $langCode = $langRow ? $langRow[0] : null;
            $langName = $langRow ? ($langRow[1] ?? null) : null;
            $headers = fgetcsv($handle);
            if ($headers) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) == count($headers)) {
                        $rowData = array_combine($headers, $row);
                        $data[] = [
                            'id' => isset($rowData['id']) && $rowData['id'] !== '' ? (int)$rowData['id'] : null,
                            'en' => $rowData['en'] ?? '',
                            'str' => $rowData['str'] ?? '',
                            'active' => isset($rowData['active']) && $rowData['active'] !== '' ? (int)$rowData['active'] : 1
                        ];
                    }
                }
            }
            fclose($handle);
            return ['langCode' => $langCode, 'langName' => $langName, 'data' => $data];
        }
        return ['langCode' => null, 'langName' => null, 'data' => []];
    }

    function readExcel($filePath)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        if (empty($rows)) return ['langCode' => null, 'langName' => null, 'data' => []];
        $langCode = $rows[0][0] ?? null;
        $langName = $rows[0][1] ?? null;
        $headers = isset($rows[1]) ? $rows[1] : [];
        $data = [];
        for ($i = 2; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (count($row) >= 4) {
                $data[] = [
                    'id' => isset($row[0]) && $row[0] !== '' ? (int)$row[0] : null,
                    'en' => $row[1] ?? '',
                    'str' => $row[2] ?? '',
                    'active' => isset($row[3]) && $row[3] !== '' ? (int)$row[3] : 1
                ];
            }
        }
        return ['langCode' => $langCode, 'langName' => $langName, 'data' => $data];
    }

    $langStmt = $pdo->query("SELECT DISTINCT `lang`, `name` FROM `$tableName` ORDER BY `lang`");
    $availableLanguages = $langStmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_POST['export_table'])) {
        try {
            $export_format = $_POST['export_format'] ?? 'json';
            $selected_lang = $_POST['selected_lang'] ?? '';

            if (empty($selected_lang)) {
                $message = "❌ Please select a language to export.";
            } else {
                if ($selected_lang === 'all') {
                    $stmt = $pdo->query("SELECT `id`, `lang`, `name`, `en`, `str`, `active` FROM `$tableName` ORDER BY `lang`, `en`");
                    $allData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $jsonData = [];
                    foreach ($allData as $item) {
                        $lang = $item['lang'];
                        if (!isset($jsonData[$lang])) {
                            $jsonData[$lang] = [
                                'lang' => $lang,
                                'name' => $item['name'],
                                'translations' => []
                            ];
                        }
                        $jsonData[$lang]['translations'][] = [
                            'id' => $item['id'],
                            'en' => $item['en'],
                            'str' => $item['str'],
                            'active' => $item['active']
                        ];
                    }
                    $json = [
                        "table" => $tableName,
                        "export_type" => "all_languages",
                        "data" => array_values($jsonData)
                    ];
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="translations_all.json"');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    exit;
                } else {
                    $stmt = $pdo->prepare("SELECT `id`, `lang`, `name`, `en`, `str`, `active` FROM `$tableName` WHERE `lang` = ? ORDER BY `en`");
                    $stmt->execute([$selected_lang]);
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $langName = !empty($data) ? $data[0]['name'] : $selected_lang;

                    switch ($export_format) {
                        case 'csv':
                            header('Content-Type: text/csv; charset=utf-8');
                            header('Content-Disposition: attachment; filename="translations_' . $selected_lang . '.csv"');
                            header('Pragma: no-cache');
                            header('Expires: 0');
                            exportAsCSV($selected_lang, $langName, $data);
                            break;
                        case 'excel':
                            if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                                throw new Exception("PhpSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet");
                            }
                            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                            header('Content-Disposition: attachment; filename="translations_' . $selected_lang . '.xlsx"');
                            header('Pragma: no-cache');
                            header('Expires: 0');
                            exportAsExcel($selected_lang, $langName, $data);
                            break;
                        case 'json':
                        default:
                            $json = [
                                "table" => $tableName,
                                "lang" => $selected_lang,
                                "name" => $langName,
                                "data" => $data
                            ];
                            header('Content-Type: application/json');
                            header('Content-Disposition: attachment; filename="translations_' . $selected_lang . '.json"');
                            header('Pragma: no-cache');
                            header('Expires: 0');
                            echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            break;
                    }
                    exit;
                }
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
                $importedData = null;
                $langCode = null;
                $langName = null;

                switch ($fileType) {
                    case 'csv':
                        $result = readCSV($fileTmpPath);
                        $importedData = $result['data'];
                        $langCode = $result['langCode'];
                        $langName = $result['langName'];
                        if (empty($importedData)) {
                            throw new Exception("CSV file is empty or invalid.");
                        }
                        break;
                    case 'excel':
                        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                            throw new Exception("PhpSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet");
                        }
                        $result = readExcel($fileTmpPath);
                        $importedData = $result['data'];
                        $langCode = $result['langCode'];
                        $langName = $result['langName'];
                        if (empty($importedData)) {
                            throw new Exception("Excel file is empty or invalid.");
                        }
                        break;
                    case 'json':
                    default:
                        $jsonContent = file_get_contents($fileTmpPath);
                        $data = json_decode($jsonContent, true);
                        if (!$data || !isset($data['data'])) {
                            throw new Exception("Invalid JSON format.");
                        }
                        if (isset($data['export_type']) && $data['export_type'] === 'all_languages') {
                            $importedData = [];
                            foreach ($data['data'] as $languageData) {
                                $currentLang = $languageData['lang'];
                                $currentLangName = $languageData['name'];
                                foreach ($languageData['translations'] as $trans) {
                                    $importedData[] = [
                                        'lang' => $currentLang,
                                        'name' => $currentLangName,
                                        'id' => $trans['id'] ?? null,
                                        'en' => $trans['en'],
                                        'str' => $trans['str'],
                                        'active' => $trans['active'] ?? 1
                                    ];
                                }
                            }
                        } else {
                            $langCode = $data['lang'] ?? null;
                            $langName = $data['name'] ?? null;
                            $importedData = $data['data'];
                        }
                        break;
                }

                if ($importedData === null || empty($importedData)) {
                    throw new Exception("No data found in file.");
                }

                $replaceAll = isset($_POST['replace_all']);

                if ($replaceAll && $langCode && $langCode !== 'all') {
                    $existingIdsStmt = $pdo->prepare("SELECT id, en FROM `$tableName` WHERE `lang` = ?");
                    $existingIdsStmt->execute([$langCode]);
                    $existingRecords = $existingIdsStmt->fetchAll(PDO::FETCH_ASSOC);
                    $existingMap = [];
                    foreach ($existingRecords as $rec) {
                        $existingMap[$rec['en']] = $rec['id'];
                    }

                    $idsToKeep = [];
                    foreach ($importedData as $row) {
                        if (!empty($row['id']) && is_numeric($row['id'])) {
                            $idsToKeep[] = $row['id'];
                        } elseif (isset($existingMap[$row['en']])) {
                            $idsToKeep[] = $existingMap[$row['en']];
                            $row['id'] = $existingMap[$row['en']];
                        }
                    }

                    if (!empty($idsToKeep)) {
                        $placeholders = implode(',', array_fill(0, count($idsToKeep), '?'));
                        $deleteStmt = $pdo->prepare("DELETE FROM `$tableName` WHERE `lang` = ? AND id NOT IN ($placeholders)");
                        $params = array_merge([$langCode], $idsToKeep);
                        $deleteStmt->execute($params);
                    } else {
                        $pdo->prepare("DELETE FROM `$tableName` WHERE `lang` = ?")->execute([$langCode]);
                    }
                    $message = "🗑️ Existing data for language '{$langCode}' cleared (keeping IDs from import). ";
                } elseif ($replaceAll) {
                    $pdo->exec("TRUNCATE TABLE `$tableName`");
                    $message = "🗑️ All existing data cleared. ";
                }

                $inserted = 0;
                $updated = 0;
                $errors = [];

                foreach ($importedData as $rowIndex => $row) {
                    $currentLang = $row['lang'] ?? $langCode;
                    $currentLangName = $row['name'] ?? $langName;

                    if (empty($currentLang) || empty($row['en']) || !isset($row['str'])) {
                        $errors[] = "Row " . ($rowIndex + 1) . " missing required lang, en, or str field";
                        continue;
                    }

                    $active = isset($row['active']) ? (int)$row['active'] : 1;

                    if (!empty($row['id']) && is_numeric($row['id'])) {
                        $checkStmt = $pdo->prepare("SELECT id FROM `$tableName` WHERE id = ?");
                        $checkStmt->execute([$row['id']]);
                        if ($checkStmt->fetch()) {
                            $updateStmt = $pdo->prepare("UPDATE `$tableName` SET `lang` = ?, `name` = ?, `en` = ?, `str` = ?, `active` = ? WHERE `id` = ?");
                            $updateStmt->execute([$currentLang, $currentLangName, $row['en'], $row['str'], $active, $row['id']]);
                            $updated++;
                            continue;
                        }
                    }

                    $checkStmt = $pdo->prepare("SELECT id FROM `$tableName` WHERE `lang` = ? AND `en` = ?");
                    $checkStmt->execute([$currentLang, $row['en']]);
                    $existing = $checkStmt->fetch();

                    if ($existing) {
                        $updateStmt = $pdo->prepare("UPDATE `$tableName` SET `name` = ?, `str` = ?, `active` = ? WHERE `lang` = ? AND `en` = ?");
                        $updateStmt->execute([$currentLangName, $row['str'], $active, $currentLang, $row['en']]);
                        $updated++;
                    } else {
                        $insertStmt = $pdo->prepare("INSERT INTO `$tableName` (`lang`, `name`, `en`, `str`, `active`) VALUES (?, ?, ?, ?, ?)");
                        $insertStmt->execute([$currentLang, $currentLangName, $row['en'], $row['str'], $active]);
                        $inserted++;
                    }
                }

                if (!empty($errors)) {
                    $message .= "⚠️ " . implode(", ", $errors) . ". ";
                }
                $message .= "✅ {$inserted} new records inserted, {$updated} records updated successfully in '{$tableName}' table";

                $langStmt = $pdo->query("SELECT DISTINCT `lang`, `name` FROM `$tableName` ORDER BY `lang`");
                $availableLanguages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            $message = "❌ " . $e->getMessage();
        }
    }

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tableName`");
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $previewStmt = $pdo->query("SELECT `id`, `lang`, `name`, `en`, `str`, `active` FROM `$tableName` ORDER BY `lang`, `en` LIMIT 100");
    $previewData = $previewStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Translation Manager</title>
    <style>
        /* ===== RESET ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
            padding: 20px;
            line-height: 1.6;
        }

        /* ===== CONTAINER ===== */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        /* ===== HEADER ===== */
        .header {
            border-bottom: 2px solid #e8e8e8;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 26px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .header h1 small {
            font-size: 14px;
            font-weight: 400;
            color: #888;
            display: block;
            margin-top: 4px;
        }

        /* ===== MESSAGES ===== */
        .message {
            padding: 12px 18px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .message-success {
            background: #e8f5e9;
            border-color: #4caf50;
            color: #2e7d32;
        }

        .message-error {
            background: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }

        .message-info {
            background: #e3f2fd;
            border-color: #2196f3;
            color: #0d47a1;
        }

        /* ===== STATS BAR ===== */
        .stats-bar {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #f8f9fa;
            border: 1px solid #e8e8e8;
            border-radius: 6px;
            padding: 12px 20px;
            flex: 1;
            min-width: 150px;
        }

        .stat-card .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            font-size: 22px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .lang-badge {
            display: inline-block;
            background: #e8e8e8;
            border-radius: 12px;
            padding: 2px 12px;
            font-size: 13px;
            margin: 2px 4px 2px 0;
        }

        /* ===== TABS ===== */
        .tabs {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid #e8e8e8;
            margin-bottom: 25px;
        }

        .tab {
            padding: 10px 24px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            color: #666;
            transition: all 0.2s;
        }

        .tab:hover {
            color: #1a1a1a;
        }

        .tab.active {
            color: #1a1a1a;
            border-bottom-color: #2196f3;
        }

        /* ===== SECTIONS ===== */
        .section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== FORMS ===== */
        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 14px;
            color: #444;
        }

        input,
        select,
        button {
            font-family: inherit;
            font-size: 14px;
        }

        input,
        select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            transition: border-color 0.2s;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2196f3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }

        select option {
            padding: 4px;
        }

        button {
            padding: 9px 28px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #2196f3;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1976d2;
        }

        .btn-success {
            background: #4caf50;
            color: #fff;
        }

        .btn-success:hover {
            background: #388e3c;
        }

        .btn-warning {
            background: #ff9800;
            color: #fff;
        }

        .btn-warning:hover {
            background: #f57c00;
        }

        .btn-danger {
            background: #f44336;
            color: #fff;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .btn-default {
            background: #e8e8e8;
            color: #333;
        }

        .btn-default:hover {
            background: #d5d5d5;
        }

        .btn-activate {
            background: #4caf50;
            color: #fff;
            padding: 12px 36px;
            font-size: 16px;
        }

        .btn-activate:hover {
            background: #388e3c;
        }

        .btn-cancel {
            background: #e8e8e8;
            color: #333;
            padding: 12px 36px;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cancel:hover {
            background: #d5d5d5;
        }

        .btn-block {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            margin-top: 8px;
        }

        /* ===== CHECKBOX ===== */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e8e8e8;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
            flex-shrink: 0;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 400;
            font-size: 14px;
            color: #444;
            cursor: pointer;
        }

        /* ===== FORMAT OPTIONS ===== */
        .format-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .format-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }

        .format-option:hover {
            border-color: #888;
        }

        .format-option.selected {
            border-color: #2196f3;
            background: #e3f2fd;
        }

        .format-option input[type="radio"] {
            width: auto;
            margin: 0;
            flex-shrink: 0;
        }

        .format-option label {
            margin: 0;
            font-weight: 400;
            font-size: 14px;
            cursor: pointer;
            color: #333;
        }

        /* ===== FILE INPUT ===== */
        input[type="file"] {
            padding: 8px;
            border: 1px dashed #ccc;
            background: #fafafa;
            cursor: pointer;
        }

        input[type="file"]:hover {
            border-color: #888;
            background: #f5f5f5;
        }

        input[type="file"]::file-selector-button {
            padding: 6px 16px;
            border: 1px solid #ccc;
            border-radius: 3px;
            background: #e8e8e8;
            cursor: pointer;
            margin-right: 12px;
            transition: 0.2s;
        }

        input[type="file"]::file-selector-button:hover {
            background: #d5d5d5;
        }

        /* ===== HINT TEXT ===== */
        .hint {
            font-size: 13px;
            color: #888;
            margin-top: 8px;
            line-height: 1.5;
        }

        /* ===== TABLE PREVIEW ===== */
        .preview-table {
            overflow-x: auto;
            max-height: 450px;
            overflow-y: auto;
            border: 1px solid #e8e8e8;
            border-radius: 4px;
        }

        .preview-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .preview-table th {
            background: #f8f9fa;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 2px solid #e8e8e8;
            position: sticky;
            top: 0;
            z-index: 10;
            font-weight: 600;
            color: #444;
        }

        .preview-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e8e8e8;
            color: #333;
        }

        .preview-table tr:hover {
            background: #f8f9fa;
        }

        .active-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
        }

        .active-yes {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .active-no {
            background: #ffebee;
            color: #c62828;
        }

        /* ===== ACTIVATION SCREEN ===== */
        .activation-screen {
            text-align: center;
            padding: 40px 20px;
        }

        .activation-screen p {
            font-size: 16px;
            color: #666;
            margin-bottom: 24px;
        }

        .activation-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ===== BACK LINK ===== */
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e8e8e8;
        }

        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            color: #333;
            text-decoration: underline;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            margin-top: 25px;
            color: #aaa;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 16px;
            }

            .header h1 {
                font-size: 20px;
            }

            .stats-bar {
                flex-direction: column;
                gap: 10px;
            }

            .format-group {
                flex-direction: column;
            }

            .tabs {
                overflow-x: auto;
                gap: 0;
            }

            .tab {
                padding: 8px 16px;
                font-size: 13px;
                white-space: nowrap;
            }

            .activation-buttons {
                flex-direction: column;
                align-items: center;
            }

            .activation-buttons button,
            .activation-buttons .btn-cancel {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <!-- HEADER -->
        <div class="header">
            <h1>
                📋 Translation Manager
                <small>Multilingual dictionary management</small>
            </h1>
        </div>

        <!-- MESSAGES -->
        <?php if (!empty($message)): ?>
            <?php if ($tableExists): ?>
                <div class="message message-success">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php else: ?>
                <div class="message message-error">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ACTIVATION SCREEN -->
        <?php if (!$tableExists): ?>
            <div class="activation-screen">
                <p>⚡ The translation table is not yet activated. Click Activate to create the required database structure.</p>
                <div class="activation-buttons">
                    <a href="<?= $backpage ?? '/' ?>">
                        <span class="btn-cancel">← Back / Cancel</span>
                    </a>
                    <button type="button" class="btn-activate" id="activateTableBtn">⚡ Activate Translation System</button>
                </div>
            </div>

        <?php else: ?>

            <!-- STATS -->
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="label">Total Translations</div>
                    <div class="value"><?= $totalRecords ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Languages</div>
                    <div class="value"><?= count($availableLanguages) ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Active Languages</div>
                    <div class="value">
                        <?php foreach ($availableLanguages as $lang): ?>
                            <span class="lang-badge"><?= htmlspecialchars($lang['lang']) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($availableLanguages)) echo "—"; ?>
                    </div>
                </div>
            </div>

            <!-- TABS -->
            <div class="tabs">
                <div class="tab active" data-tab="0">📤 Export</div>
                <div class="tab" data-tab="1">📥 Import</div>
                <div class="tab" data-tab="2">👁️ Preview</div>
            </div>

            <!-- EXPORT SECTION -->
            <div class="section active" id="exportSection">
                <form method="POST">
                    <div class="form-group">
                        <label for="selected_lang">🌐 Select Language to Export</label>
                        <select name="selected_lang" id="selected_lang" required>
                            <option value="" disabled selected>— Select a language —</option>
                            <option value="all">🌍 ALL LANGUAGES (JSON only)</option>
                            <?php foreach ($availableLanguages as $lang): ?>
                                <option value="<?= htmlspecialchars($lang['lang']) ?>">
                                    <?= htmlspecialchars($lang['lang']) ?> - <?= htmlspecialchars($lang['name']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (empty($availableLanguages)): ?>
                                <option disabled>— No languages found —</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>📁 Export Format</label>
                        <div class="format-group" id="exportFormatGroup">
                            <div class="format-option selected" data-format="json">
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
                    </div>

                    <button type="submit" name="export_table" class="btn-success btn-block">
                        ⚡ EXPORT TRANSLATIONS
                    </button>
                </form>
                <div class="hint">
                    📋 CSV/Excel: Row1: language code, language name | Row2: id, en, str, active | Then data rows
                </div>
            </div>

            <!-- IMPORT SECTION -->
            <div class="section" id="importSection">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>📂 File Type</label>
                        <div class="format-group" id="importTypeGroup">
                            <div class="format-option selected" data-format="json">
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
                    </div>

                    <div class="form-group">
                        <label>📂 Select File</label>
                        <input type="file" name="import_file" accept=".json,.csv,.xlsx" required>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="replace_all" id="replace_all">
                        <label for="replace_all">⚡ REPLACE MODE – Delete existing data for this language before import</label>
                    </div>

                    <button type="submit" name="import_table" class="btn-warning btn-block">
                        🔥 IMPORT TRANSLATIONS
                    </button>
                </form>
                <div class="hint">
                    💡 If ID exists → UPDATE | If (lang + en) exists → UPDATE | Otherwise → INSERT new record
                </div>
            </div>

            <!-- PREVIEW SECTION -->
            <div class="section" id="previewSection">
                <div class="preview-table">
                    <?php if (empty($previewData)): ?>
                        <div style="padding: 30px; text-align: center; color: #888;">
                            📭 No translations yet. Import some data to see preview.
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Language Code</th>
                                    <th>Language Name</th>
                                    <th>English Word</th>
                                    <th>Translation</th>
                                    <th>Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previewData as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['lang']) ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['en']) ?></td>
                                        <td><?= htmlspecialchars($row['str']) ?></td>
                                        <td>
                                            <span class="active-badge <?= $row['active'] == 1 ? 'active-yes' : 'active-no' ?>">
                                                <?= $row['active'] == 1 ? 'ACTIVE' : 'INACTIVE' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($totalRecords > 100): ?>
                            <div style="padding: 8px 12px; background: #f8f9fa; font-size: 13px; color: #888;">
                                📊 Showing first 100 of <?= $totalRecords ?> records
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

        <!-- BACK LINK -->
        <div class="back-link">
            <a href="<?= $backpage ?? '/' ?>">← Back to Dashboard</a>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            Translation Manager • Multilingual Data Flow
        </div>

    </div>

    <script>
        (function() {
            <?php if (!$tableExists): ?>
                const activateBtn = document.getElementById('activateTableBtn');
                if (activateBtn) {
                    activateBtn.addEventListener('click', async function() {
                        const originalText = this.innerHTML;
                        this.innerHTML = '⏳ Activating...';
                        this.disabled = true;

                        try {
                            const formData = new FormData();
                            formData.append('action', 'activate_table');

                            const response = await fetch(window.location.href, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                            const result = await response.json();

                            if (result.success) {
                                // Show message and reload
                                const msgDiv = document.createElement('div');
                                msgDiv.className = 'message message-success';
                                msgDiv.textContent = result.message;
                                const container = document.querySelector('.container');
                                const activationScreen = document.querySelector('.activation-screen');
                                if (activationScreen) {
                                    activationScreen.insertAdjacentElement('beforebegin', msgDiv);
                                }

                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                this.innerHTML = originalText;
                                this.disabled = false;
                                alert('Error: ' + result.message);
                            }
                        } catch (error) {
                            this.innerHTML = originalText;
                            this.disabled = false;
                            alert('Request failed: ' + error.message);
                        }
                    });
                }
            <?php else: ?>
                // Tab switching
                const tabs = document.querySelectorAll('.tab');
                const sections = {
                    0: document.getElementById('exportSection'),
                    1: document.getElementById('importSection'),
                    2: document.getElementById('previewSection')
                };

                function switchTab(index) {
                    tabs.forEach((tab, i) => {
                        tab.classList.toggle('active', i === index);
                    });
                    for (let i = 0; i <= 2; i++) {
                        if (sections[i]) sections[i].classList.toggle('active', i === index);
                    }
                }

                tabs.forEach((tab, idx) => {
                    tab.addEventListener('click', () => switchTab(idx));
                });

                // Format option selection
                document.querySelectorAll('.format-option').forEach(opt => {
                    const radio = opt.querySelector('input');
                    if (radio) {
                        radio.addEventListener('change', () => {
                            const group = opt.closest('.format-group');
                            group.querySelectorAll('.format-option').forEach(o => o.classList.remove(
                            'selected'));
                            if (radio.checked) opt.classList.add('selected');
                        });
                        if (radio.checked) opt.classList.add('selected');
                    }
                });

                // Language select - disable CSV/Excel for "all" option
                const langSelect = document.querySelector('select[name="selected_lang"]');
                if (langSelect) {
                    langSelect.addEventListener('change', function() {
                        const isAll = this.value === 'all';
                        document.querySelectorAll('#exportFormatGroup .format-option').forEach(opt => {
                            const radio = opt.querySelector('input');
                            if (radio && (radio.value === 'csv' || radio.value === 'excel')) {
                                opt.style.opacity = isAll ? '0.4' : '1';
                                opt.style.pointerEvents = isAll ? 'none' : 'auto';
                                if (isAll && radio.checked) {
                                    document.getElementById('export_json').checked = true;
                                    document.querySelector('#exportFormatGroup .format-option[data-format="json"]')
                                        .classList.add('selected');
                                }
                            }
                        });
                    });
                }

                // File input feedback
                const fileInput = document.querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.addEventListener('change', function() {
                        if (this.files.length) {
                            const span = document.createElement('div');
                            span.style.cssText = 'font-size: 13px; margin-top: 6px; color: #666;';
                            span.textContent = '📎 ' + this.files[0].name;
                            const old = this.parentNode.querySelector('.file-feedback');
                            if (old) old.remove();
                            span.className = 'file-feedback';
                            this.insertAdjacentElement('afterend', span);
                            setTimeout(() => span.remove(), 3000);
                        }
                    });
                }

                // Update accept attribute based on file type selection
                document.querySelectorAll('#importTypeGroup .format-option input').forEach(radio => {
                    radio.addEventListener('change', function() {
                        const fileInput = document.querySelector('input[name="import_file"]');
                        if (this.value === 'json') fileInput.setAttribute('accept', '.json');
                        else if (this.value === 'csv') fileInput.setAttribute('accept', '.csv');
                        else if (this.value === 'excel') fileInput.setAttribute('accept', '.xlsx,.xls');
                    });
                });
            <?php endif; ?>
        })();
    </script>

</body>

</html>