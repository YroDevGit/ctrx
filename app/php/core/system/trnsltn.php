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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CTRX Lightning | Translation Manager</title>
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
            max-width: 1400px;
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

        .stats-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            border-radius: 1rem;
            padding: 0.8rem 1.5rem;
            border: 1px solid rgba(255, 200, 80, 0.3);
            flex: 1;
            min-width: 150px;
        }

        .stat-card .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #ffdb8e;
            letter-spacing: 1px;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #FFE5A3;
        }

        .language-badge {
            display: inline-block;
            background: rgba(255, 200, 80, 0.2);
            border: 1px solid #ffcc66;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            margin: 0.2rem;
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

        .activation-screen {
            text-align: center;
            padding: 3rem 2rem;
        }

        .activation-screen p {
            color: #ffdb8e;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .activation-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .activation-buttons button {
            width: auto;
            min-width: 180px;
            padding: 0.9rem 2rem;
            font-size: 1.1rem;
            margin-top: 0;
        }

        .btn-cancel {
            background: linear-gradient(95deg, #3a2a2a, #2a1a1a);
            border-color: #ff8866;
            color: #ffaa88;
        }

        .btn-cancel:hover {
            background: linear-gradient(95deg, #cc5533, #aa4422);
            color: white;
            border-color: #ffaa88;
        }

        .btn-activate {
            background: linear-gradient(95deg, #FFD966, #FFB347);
            color: #0a0a1a;
            font-weight: bold;
        }

        .btn-activate:hover {
            background: linear-gradient(95deg, #FFE484, #FFC857);
            box-shadow: 0 0 25px #ffcc44;
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

        .inline-hint {
            font-size: 0.7rem;
            color: #8f9bb3;
            margin-top: 0.3rem;
            text-align: center;
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

        .preview-table {
            margin-top: 2rem;
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }

        .preview-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .preview-table th {
            background: rgba(255, 200, 80, 0.2);
            padding: 0.8rem;
            text-align: left;
            color: #FFE5A3;
            border-bottom: 2px solid #ffcc66;
            position: sticky;
            top: 0;
            backdrop-filter: blur(8px);
        }

        .preview-table td {
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid rgba(255, 200, 80, 0.2);
            color: #d4dcec;
        }

        .preview-table tr:hover {
            background: rgba(255, 200, 80, 0.1);
        }

        .active-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .active-yes {
            background: rgba(76, 175, 80, 0.3);
            color: #81c784;
            border: 1px solid #4caf50;
        }

        .active-no {
            background: rgba(244, 67, 54, 0.3);
            color: #ef9a9a;
            border: 1px solid #f44336;
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
            margin-top: 2rem;
            color: #7f8c9a;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
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

            .stats-bar {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="lightning-streak"></div>
    <div class="lightning-streak" style="transform: rotate(10deg); opacity:0.2;"></div>

    <div class="container">
        <h2>🌍 CTRX TRANSLATION HUB</h2>
        <h2 style="font-size: 1rem; margin-top: -15px; margin-bottom: 20px;">MULTILINGUAL DICTIONARY MANAGER</h2>

        <?php if (!empty($message) && $tableExists): ?>
            <div class="msg">
                <span class="icon-badge">⚡</span>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$tableExists): ?>
            <div class="activation-screen">
                <p>⚡ The translation table is not yet activated. Click Activate to create the required database structure.</p>
                <div class="activation-buttons">
                    <a href="<?= $backpage ?? '/' ?>">
                        <button type="button" class="btn-cancel" style="text-decoration: none;">← Back / Cancel</button>
                    </a>
                    <button type="button" class="btn-activate" id="activateTableBtn">⚡ Activate Translation System</button>
                </div>
            </div>
        <?php else: ?>
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="label">📚 Total Translations</div>
                    <div class="value"><?= $totalRecords ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">🌐 Languages</div>
                    <div class="value"><?= count($availableLanguages) ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">🎯 Active Languages</div>
                    <div class="value">
                        <?php foreach ($availableLanguages as $lang): ?>
                            <span class="language-badge"><?= htmlspecialchars($lang['lang']) ?> - <?= htmlspecialchars($lang['name']) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($availableLanguages)) echo "—"; ?>
                    </div>
                </div>
            </div>

            <div class="tabs">
                <div class="tab active" data-tab="0">📤 EXPORT</div>
                <div class="tab" data-tab="1">📥 IMPORT</div>
                <div class="tab" data-tab="2">👁️ PREVIEW</div>
            </div>

            <div class="section active" id="exportSection">
                <form method="POST" id="exportForm">
                    <label>🌐 SELECT LANGUAGE TO EXPORT</label>
                    <select name="selected_lang" required>
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
                        <span>⚡ EXPORT TRANSLATIONS</span>
                    </button>
                </form>
                <div class="inline-hint">
                    📋 CSV/Excel format: Row1: language code, language name | Row2: id, en, str, active | Then data rows<br>
                    📋 JSON: Structured format with all fields
                </div>
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

                    <div class="checkbox">
                        <input type="checkbox" name="replace_all" id="replace_all">
                        <label for="replace_all">⚡ REPLACE MODE – Delete all existing data for this language before import (keeps existing IDs)</label>
                    </div>

                    <button name="import_table" type="submit">
                        <span>🔥 IMPORT TRANSLATIONS</span>
                    </button>
                </form>
                <div class="inline-hint">
                    💡 Import rules:<br>
                    • If ID exists and matches a record → UPDATE that record, keeping the same ID<br>
                    • If no ID but (lang + en) exists → UPDATE that record, keeping the same ID<br>
                    • If no match → INSERT new record (auto-assign new ID)<br>
                    • Replace mode: ONLY deletes records for the language being imported that are NOT in the import file (keeps IDs from import)
                </div>
            </div>

            <div class="section" id="previewSection">
                <div class="preview-table">
                    <?php if (empty($previewData)): ?>
                        <div class="inline-hint">📭 No translations yet. Import some data to see preview.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>🌐 Language Code</th>
                                    <th>🏷️ Language Name</th>
                                    <th>📖 English Word</th>
                                    <th>🔄 Translation</th>
                                    <th>⚡ Active</th>
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
                            <div class="inline-hint">📊 Showing first 100 of <?= $totalRecords ?> records</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top: 2rem; font-size:16px; text-align:center;">
            <a style="text-decoration:none;color:#ccb27c;" href="<?= $backpage ?? '/' ?>">← Back to Dashboard</a>
        </div>
        <footer>⚡ CTRX TRANSLATION ENGINE • MULTILINGUAL DATA FLOW</footer>
    </div>

    <script>
        (function() {
            <?php if (!$tableExists): ?>
                const activateBtn = document.getElementById('activateTableBtn');
                if (activateBtn) {
                    activateBtn.addEventListener('click', async function() {
                        const originalText = activateBtn.innerHTML;
                        activateBtn.innerHTML = '⏳ Activating...';
                        activateBtn.disabled = true;

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
                                msgDiv.className = 'msg';
                                msgDiv.innerHTML = '<span class="icon-badge">⚡</span> ' + result.message;
                                const container = document.querySelector('.container');
                                const activationScreen = document.querySelector('.activation-screen');
                                if (activationScreen) {
                                    activationScreen.insertAdjacentElement('beforebegin', msgDiv);
                                }

                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                activateBtn.innerHTML = originalText;
                                activateBtn.disabled = false;
                                alert('Error: ' + result.message);
                            }
                        } catch (error) {
                            activateBtn.innerHTML = originalText;
                            activateBtn.disabled = false;
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
                        if (i === index) {
                            tab.classList.add('active');
                        } else {
                            tab.classList.remove('active');
                        }
                    });
                    for (let i = 0; i <= 2; i++) {
                        if (sections[i]) sections[i].classList.remove('active');
                    }
                    if (sections[index]) sections[index].classList.add('active');
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

                const langSelect = document.querySelector('select[name="selected_lang"]');
                const exportFormatRadios = document.querySelectorAll('input[name="export_format"]');

                function updateExportFormatOptions() {
                    const selectedLang = langSelect ? langSelect.value : '';
                    const isAllSelected = selectedLang === 'all';
                    exportFormatRadios.forEach(radio => {
                        const optionDiv = radio.closest('.format-option');
                        if (radio.value === 'excel' || radio.value === 'csv') {
                            if (isAllSelected && optionDiv) {
                                optionDiv.style.opacity = '0.5';
                                optionDiv.style.pointerEvents = 'none';
                                if (radio.checked && radio.value !== 'json') {
                                    document.getElementById('export_json').checked = true;
                                    document.querySelector('#exportFormatGroup .format-option[data-format="json"]').classList.add('selected');
                                }
                            } else if (optionDiv) {
                                optionDiv.style.opacity = '1';
                                optionDiv.style.pointerEvents = 'auto';
                            }
                        }
                    });
                }

                if (langSelect) {
                    langSelect.addEventListener('change', updateExportFormatOptions);
                    updateExportFormatOptions();
                }
            <?php endif; ?>
        })();
    </script>
</body>

</html>