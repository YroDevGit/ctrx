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

                $importMode = $_POST['import_mode'] ?? 'replace_all';

                if ($importMode === 'replace_all') {
                    $hasMixedLanguages = false;
                    $languagesInFile = [];
                    foreach ($importedData as $row) {
                        $lang = $row['lang'] ?? $langCode;
                        if ($lang) {
                            $languagesInFile[$lang] = true;
                        }
                    }
                    
                    if ($langCode && count($languagesInFile) <= 1) {
                        $pdo->prepare("DELETE FROM `$tableName` WHERE `lang` = ?")->execute([$langCode]);
                        $message = "🗑️ Existing data for language '{$langCode}' cleared. ";
                    } else {
                        $pdo->exec("TRUNCATE TABLE `$tableName`");
                        $message = "🗑️ All existing data cleared. ";
                    }
                }

                $inserted = 0;
                $updated = 0;
                $skipped = 0;
                $errors = [];

                foreach ($importedData as $rowIndex => $row) {
                    $currentLang = $row['lang'] ?? $langCode;
                    $currentLangName = $row['name'] ?? $langName;

                    if (empty($currentLang) || empty($row['en']) || !isset($row['str'])) {
                        $errors[] = "Row " . ($rowIndex + 1) . " missing required lang, en, or str field";
                        continue;
                    }

                    $active = isset($row['active']) ? (int)$row['active'] : 1;

                    if ($importMode === 'skip') {
                        if (!empty($row['id']) && is_numeric($row['id'])) {
                            $checkStmt = $pdo->prepare("SELECT id FROM `$tableName` WHERE id = ?");
                            $checkStmt->execute([$row['id']]);
                            if ($checkStmt->fetch()) {
                                $skipped++;
                                continue;
                            }
                        }
                        $checkStmt = $pdo->prepare("SELECT id FROM `$tableName` WHERE `lang` = ? AND `en` = ?");
                        $checkStmt->execute([$currentLang, $row['en']]);
                        if ($checkStmt->fetch()) {
                            $skipped++;
                            continue;
                        }
                    }

                    if ($importMode === 'update') {
                        $updatedRecord = false;
                        
                        if (!empty($row['id']) && is_numeric($row['id'])) {
                            $checkStmt = $pdo->prepare("SELECT id FROM `$tableName` WHERE id = ?");
                            $checkStmt->execute([$row['id']]);
                            if ($checkStmt->fetch()) {
                                $updateStmt = $pdo->prepare("UPDATE `$tableName` SET `lang` = ?, `name` = ?, `en` = ?, `str` = ?, `active` = ? WHERE `id` = ?");
                                $updateStmt->execute([$currentLang, $currentLangName, $row['en'], $row['str'], $active, $row['id']]);
                                $updated++;
                                $updatedRecord = true;
                            }
                        }
                        
                        if (!$updatedRecord) {
                            $checkStmt = $pdo->prepare("SELECT id FROM `$tableName` WHERE `lang` = ? AND `en` = ?");
                            $checkStmt->execute([$currentLang, $row['en']]);
                            $existing = $checkStmt->fetch();
                            if ($existing) {
                                $updateStmt = $pdo->prepare("UPDATE `$tableName` SET `name` = ?, `str` = ?, `active` = ? WHERE `lang` = ? AND `en` = ?");
                                $updateStmt->execute([$currentLangName, $row['str'], $active, $currentLang, $row['en']]);
                                $updated++;
                                $updatedRecord = true;
                            }
                        }
                        
                        if ($updatedRecord) {
                            continue;
                        }
                    }

                    $insertStmt = $pdo->prepare("INSERT INTO `$tableName` (`lang`, `name`, `en`, `str`, `active`) VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$currentLang, $currentLangName, $row['en'], $row['str'], $active]);
                    $inserted++;
                }

                if (!empty($errors)) {
                    $message .= "⚠️ " . implode(", ", $errors) . ". ";
                }
                
                $modeText = [
                    'replace_all' => 'replaced all',
                    'update' => 'updated (merge)',
                    'skip' => 'skipped duplicates'
                ][$importMode] ?? 'processed';
                
                $message .= "✅ {$inserted} inserted, {$updated} updated, {$skipped} skipped successfully in '{$tableName}' ({$modeText})";

                $langStmt = $pdo->query("SELECT DISTINCT `lang`, `name` FROM `$tableName` ORDER BY `lang`");
                $availableLanguages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            $message = "❌ " . $e->getMessage();
        }
    }

    if (isset($_POST['delete_record'])) {
        try {
            $id = (int)$_POST['delete_id'];
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM `$tableName` WHERE `id` = ?");
                $stmt->execute([$id]);
                $message = "✅ Record ID {$id} deleted successfully.";
            }
        } catch (Throwable $e) {
            $message = "❌ Failed to delete: " . $e->getMessage();
        }
    }

    if (isset($_POST['edit_record'])) {
        try {
            $id = (int)$_POST['edit_id'];
            $en = $_POST['edit_en'];
            $str = $_POST['edit_str'];
            $lang = $_POST['edit_lang'];
            $name = $_POST['edit_name'];
            
            if ($id > 0 && !empty($en) && !empty($str) && !empty($lang)) {
                $stmt = $pdo->prepare("UPDATE `$tableName` SET `lang` = ?, `name` = ?, `en` = ?, `str` = ? WHERE `id` = ?");
                $stmt->execute([$lang, $name, $en, $str, $id]);
                $message = "✅ Record ID {$id} updated successfully.";
            } else {
                $message = "❌ Please fill all required fields.";
            }
        } catch (Throwable $e) {
            $message = "❌ Failed to update: " . $e->getMessage();
        }
    }

    $langStmt = $pdo->query("SELECT DISTINCT `lang`, `name` FROM `$tableName` ORDER BY `lang`");
    $availableLanguages = $langStmt->fetchAll(PDO::FETCH_ASSOC);

    $searchLang = isset($_POST['search_lang']) ? $_POST['search_lang'] : 'all';
    $searchEn = isset($_POST['search_en']) ? trim($_POST['search_en']) : '';
    $searchStr = isset($_POST['search_str']) ? trim($_POST['search_str']) : '';

    $sql = "SELECT `id`, `lang`, `name`, `en`, `str`, `active` FROM `$tableName` WHERE 1=1";
    $params = [];

    if ($searchLang !== 'all' && !empty($searchLang)) {
        $sql .= " AND `lang` = ?";
        $params[] = $searchLang;
    }

    if (!empty($searchEn)) {
        $sql .= " AND `en` LIKE ?";
        $params[] = "%" . $searchEn . "%";
    }

    if (!empty($searchStr)) {
        $sql .= " AND `str` LIKE ?";
        $params[] = "%" . $searchStr . "%";
    }

    $sql .= " ORDER BY `lang`, `en` LIMIT 500";

    $previewStmt = $pdo->prepare($sql);
    $previewStmt->execute($params);
    $previewData = $previewStmt->fetchAll(PDO::FETCH_ASSOC);

    $totalRecords = $pdo->query("SELECT COUNT(*) as total FROM `$tableName`")->fetch(PDO::FETCH_ASSOC)['total'];

    $editRecord = null;
    if (isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        if ($editId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM `$tableName` WHERE `id` = ?");
            $stmt->execute([$editId]);
            $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    $activeTab = isset($_POST['active_tab']) ? (int)$_POST['active_tab'] : (isset($_GET['tab']) ? (int)$_GET['tab'] : 0);
    if ($activeTab < 0 || $activeTab > 2) $activeTab = 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Translation Manager</title>
    <style>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

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

        .btn-info {
            background: #2196f3;
            color: #fff;
        }

        .btn-info:hover {
            background: #1976d2;
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

        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
        }

        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 10px 14px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e8e8e8;
            margin-top: 4px;
        }

        .radio-group .radio-option {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .radio-group .radio-option input[type="radio"] {
            width: auto;
            margin: 0;
            flex-shrink: 0;
        }

        .radio-group .radio-option label {
            margin: 0;
            font-weight: 400;
            font-size: 13px;
            color: #333;
            cursor: pointer;
        }

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

        .hint {
            font-size: 13px;
            color: #888;
            margin-top: 8px;
            line-height: 1.5;
        }

        .preview-table {
            overflow-x: auto;
            max-height: 550px;
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

        .search-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e8e8e8;
            margin-bottom: 15px;
            align-items: flex-end;
        }

        .search-bar .form-group {
            margin-bottom: 0;
            flex: 1;
            min-width: 150px;
        }

        .search-bar .form-group label {
            font-size: 12px;
            margin-bottom: 3px;
        }

        .search-bar .form-group input,
        .search-bar .form-group select {
            padding: 6px 10px;
            font-size: 13px;
        }

        .search-bar .search-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding-bottom: 2px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            overflow: auto;
            padding: 20px;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e8e8e8;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 20px;
            color: #1a1a1a;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #888;
            padding: 0 8px;
            line-height: 1;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal .form-group {
            margin-bottom: 14px;
        }

        .modal .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        .modal .form-actions button {
            flex: 1;
        }

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

        .footer {
            text-align: center;
            margin-top: 25px;
            color: #aaa;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

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

            .radio-group {
                flex-direction: column;
                gap: 6px;
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

            .search-bar {
                flex-direction: column;
                gap: 10px;
            }

            .search-bar .form-group {
                min-width: 100%;
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

            .modal-content {
                padding: 20px;
                margin: 10px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header">
            <h1>
                📋 Translation Manager
                <small>Multilingual dictionary management</small>
            </h1>
        </div>

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

            <div class="tabs">
                <div class="tab <?= $activeTab == 0 ? 'active' : '' ?>" data-tab="0">📤 Export</div>
                <div class="tab <?= $activeTab == 1 ? 'active' : '' ?>" data-tab="1">📥 Import</div>
                <div class="tab <?= $activeTab == 2 ? 'active' : '' ?>" data-tab="2">👁️ Preview</div>
            </div>

            <div class="section <?= $activeTab == 0 ? 'active' : '' ?>" id="exportSection">
                <form method="POST">
                    <input type="hidden" name="active_tab" value="0">
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

            <div class="section <?= $activeTab == 1 ? 'active' : '' ?>" id="importSection">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="active_tab" value="1">
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

                    <div class="form-group">
                        <label>⚙️ IMPORT MODE</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="import_mode" value="update" id="mode_update" checked>
                                <label for="mode_update">UPDATE (merge existing)</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="import_mode" value="skip" id="mode_skip">
                                <label for="mode_skip">SKIP duplicates</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="import_mode" value="replace_all" id="mode_replace_all">
                                <label for="mode_replace_all">REPLACE ALL (clear existing + insert all)</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="import_table" class="btn-warning btn-block">
                        🔥 IMPORT TRANSLATIONS
                    </button>
                </form>
                <div class="hint">
                    💡 REPLACE ALL: Clears existing data for the language(s) in the file, then inserts all records.<br>
                    💡 UPDATE: Updates existing records (by ID or lang+en), inserts new ones.<br>
                    💡 SKIP: Skips records that already exist (by ID or lang+en), inserts new ones.
                </div>
            </div>

            <div class="section <?= $activeTab == 2 ? 'active' : '' ?>" id="previewSection">
                <form method="POST" class="search-bar">
                    <input type="hidden" name="active_tab" value="2">
                    <div class="form-group">
                        <label>🌐 Language</label>
                        <select name="search_lang">
                            <option value="all" <?= $searchLang === 'all' ? 'selected' : '' ?>>All Languages</option>
                            <?php foreach ($availableLanguages as $lang): ?>
                                <option value="<?= htmlspecialchars($lang['lang']) ?>" <?= $searchLang === $lang['lang'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lang['lang']) ?> - <?= htmlspecialchars($lang['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>🔍 Default</label>
                        <input type="text" name="search_en" placeholder="Search Default..." value="<?= htmlspecialchars($searchEn) ?>">
                    </div>
                    <div class="form-group">
                        <label>🔍 Translation</label>
                        <input type="text" name="search_str" placeholder="Search Translation..." value="<?= htmlspecialchars($searchStr) ?>">
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="btn-primary">🔍 Filter</button>
                        <a href="?tab=2&clear=1" class="btn-default" style="padding: 9px 20px; text-decoration: none; display: inline-block;">Clear</a>
                    </div>
                </form>

                <div class="preview-table">
                    <?php if (empty($previewData)): ?>
                        <div style="padding: 30px; text-align: center; color: #888;">
                            📭 No translations found matching your criteria.
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 80px;">Code</th>
                                    <th>Language</th>
                                    <th>Default</th>
                                    <th>Translation</th>
                                    <th style="width: 120px;">Actions</th>
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
                                            <a href="?tab=2&edit=<?= $row['id'] ?>" class="btn-info btn-sm" style="text-decoration: none; display: inline-block; color: #fff;">✏️</a>
                                            <button type="button" class="btn-danger btn-sm" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['en']) ?>')">🗑️</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($previewData) >= 500): ?>
                            <div style="padding: 8px 12px; background: #f8f9fa; font-size: 13px; color: #888;">
                                📊 Showing first 500 records. Refine your search for more specific results.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

        <div class="back-link">
            <a href="<?= $backpage ?? '/' ?>">← Back to Dashboard</a>
        </div>

        <div class="footer">
            Translation Manager • Multilingual Data Flow
        </div>

    </div>

    <?php if ($editRecord): ?>
    <div class="modal active" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Translation #<?= $editRecord['id'] ?></h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="active_tab" value="2">
                <input type="hidden" name="edit_id" value="<?= $editRecord['id'] ?>">
                <div class="form-group">
                    <label>Default</label>
                    <input type="text" name="edit_en" value="<?= htmlspecialchars($editRecord['en']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Translation</label>
                    <input type="text" name="edit_str" value="<?= htmlspecialchars($editRecord['str']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Language Code</label>
                    <input type="text" name="edit_lang" style="background: #f7f7f7;" value="<?= htmlspecialchars($editRecord['lang']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Language Name</label>
                    <input type="text" name="edit_name" style="background: #f7f7f7;" value="<?= htmlspecialchars($editRecord['name']) ?>" readonly>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-default" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_record" class="btn-success">💾 Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="active_tab" value="2">
        <input type="hidden" name="delete_id" id="deleteId">
        <input type="hidden" name="delete_record" value="1">
    </form>

    <script>
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
                tab.addEventListener('click', () => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', idx);
                    window.history.pushState({}, '', url);
                    switchTab(idx);
                });
            });

            <?php if ($activeTab == 2): ?>
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 2);
            window.history.replaceState({}, '', url);
            <?php endif; ?>

            document.querySelectorAll('.format-option').forEach(opt => {
                const radio = opt.querySelector('input');
                if (radio) {
                    radio.addEventListener('change', () => {
                        const group = opt.closest('.format-group');
                        group.querySelectorAll('.format-option').forEach(o => o.classList.remove('selected'));
                        if (radio.checked) opt.classList.add('selected');
                    });
                    if (radio.checked) opt.classList.add('selected');
                }
            });

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

            document.querySelectorAll('#importTypeGroup .format-option input').forEach(radio => {
                radio.addEventListener('change', function() {
                    const fileInput = document.querySelector('input[name="import_file"]');
                    if (this.value === 'json') fileInput.setAttribute('accept', '.json');
                    else if (this.value === 'csv') fileInput.setAttribute('accept', '.csv');
                    else if (this.value === 'excel') fileInput.setAttribute('accept', '.xlsx,.xls');
                });
            });

            function confirmDelete(id, en) {
                if (confirm('Are you sure you want to delete translation "' + en + '" (ID: ' + id + ')?')) {
                    document.getElementById('deleteId').value = id;
                    document.getElementById('deleteForm').submit();
                }
            }

            function closeEditModal() {
                const modal = document.getElementById('editModal');
                if (modal) {
                    modal.classList.remove('active');
                    const url = new URL(window.location.href);
                    url.searchParams.delete('edit');
                    window.history.pushState({}, '', url);
                    setTimeout(() => {
                        window.location.href = url.toString();
                    }, 100);
                }
            }

            <?php if ($editRecord): ?>
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeEditModal();
                }
            });
            <?php endif; ?>
        <?php endif; ?>
    </script>

</body>

</html>