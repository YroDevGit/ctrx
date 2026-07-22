<?php
// Author: Your Name

$base_path = 'logs';

$current_path = isset($_GET['path']) ? $_GET['path'] : '';
$full_path = $base_path . $current_path;

$real_base = realpath($base_path);
$real_full = realpath($full_path);

if ($real_full === false || strpos($real_full, $real_base) !== 0) {
    //die('Access denied!');
}

// Handle file viewing
if (isset($_GET['view'])) {
    $view_file = $real_full;
    if (is_file($view_file) && is_readable($view_file)) {
        header('Content-Type: text/plain');
        readfile($view_file);
        exit;
    }
}

// Handle file deletion
if (isset($_GET['delete']) && isset($_GET['file'])) {
    $delete_path = $base_path . '/' . ltrim($_GET['file'], '/');
    if (is_file($delete_path) && is_writable($delete_path)) {
        if (unlink($delete_path)) {
            $message = "File deleted successfully!";
        } else {
            $message = "Failed to delete file.";
        }
    } elseif (is_dir($delete_path)) {
        // Delete folder recursively
        function deleteFolder($dir)
        {
            if (!file_exists($dir)) return true;
            if (!is_dir($dir)) return unlink($dir);

            $items = array_diff(scandir($dir), array('.', '..'));
            foreach ($items as $item) {
                $path = $dir . '/' . $item;
                if (is_dir($path)) {
                    deleteFolder($path);
                } else {
                    unlink($path);
                }
            }
            return rmdir($dir);
        }

        if (deleteFolder($delete_path)) {
            $message = "Folder deleted successfully!";
        } else {
            $message = "Failed to delete folder.";
        }
    }
    // Redirect to remove delete parameter
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?path=' . urlencode($current_path) . '&msg=' . urlencode($message));
    exit;
}

$items = [];
if (is_dir($full_path) && is_readable($full_path)) {
    $dir = opendir($full_path);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $item_path = $full_path . '/' . $file;
            $items[] = [
                'name' => $file,
                'is_dir' => is_dir($item_path),
                'size' => is_file($item_path) ? formatSize(filesize($item_path)) : '',
                'modified' => date('Y-m-d H:i:s', filemtime($item_path)),
                'path' => $current_path . '/' . $file,
                'full_path' => $item_path
            ];
        }
    }
    closedir($dir);

    usort($items, function ($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
}

function formatSize($bytes)
{
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

$parent_path = dirname($current_path);
if ($parent_path == '.') $parent_path = '';

// Show message if exists
$message = isset($_GET['msg']) ? $_GET['msg'] : '';

function folderSize($folder)
{
    $size = 0;

    if (!is_dir($folder)) {
        return 0;
    }

    foreach (scandir($folder) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $folder . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            $size += folderSize($path);
        } else {
            $size += filesize($path);
        }
    }

    return $size;
}

if (isset($_GET['clearlogs']) && $_GET['clearlogs'] == "yes") {
    function deleteFolder($folder)
    {
        if (!is_dir($folder)) {
            return false;
        }

        foreach (scandir($folder) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $folder . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                deleteFolder($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($folder);
    }
    deleteFolder("logs");
    reload_page(false);
}

$size = folderSize('logs');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log File Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        h1 {
            color: #0d6efd;
            font-weight: 600;
            letter-spacing: 1px;
            margin: 0;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
        }

        .top-back {
            background: #4a90e2;
            padding: 8px 16px;
            border-radius: 6px;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .top-back:hover {
            background: #357abd;
        }

        .exit-btn {
            background: #e94560;
            padding: 8px 20px;
            border-radius: 6px;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
        }

        .exit-btn:hover {
            background: #d63851;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(233, 69, 96, 0.3);
        }

        .breadcrumb {
            background: #f8f9fa;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            overflow-wrap: break-word;
            word-break: break-all;
            border: 1px solid #e9ecef;
        }

        .breadcrumb a {
            color: #0d6efd;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 6px;
            color: #333;
            font-size: 14px;
        }

        .search-box:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #e9ecef;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #0d6efd;
        }

        td {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        tr:hover td {
            background: #f8f9fa;
        }

        .folder-item {
            color: #4a90e2;
            cursor: pointer;
            font-weight: 500;
        }

        .folder-item:hover {
            text-decoration: underline;
        }

        .file-item {
            color: #28a745;
            cursor: pointer;
        }

        .file-item:hover {
            text-decoration: underline;
        }

        .file-link {
            color: #28a745;
            text-decoration: none;
            display: inline-block;
        }

        .file-link:hover {
            text-decoration: underline;
        }

        .icon {
            margin-right: 10px;
            font-size: 16px;
        }

        .file-size {
            color: #6c757d;
            font-size: 12px;
        }

        .modified-date {
            color: #6c757d;
            font-size: 12px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 4px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #4a90e2;
            color: white;
        }

        .btn-view:hover {
            background: #357abd;
        }

        .btn-delete {
            background: #e94560;
            color: white;
        }

        .btn-delete:hover {
            background: #d63851;
        }

        .btn-delete-folder {
            background: #dc3545;
            color: white;
        }

        .btn-delete-folder:hover {
            background: #c82333;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .file-content-wrapper {
            margin-top: 30px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }

        .file-content-header {
            background: #f8f9fa;
            padding: 12px 18px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .file-content-header h3 {
            color: #495057;
            margin: 0;
            font-size: 16px;
        }

        .file-content-header .file-info {
            color: #6c757d;
            font-size: 13px;
        }

        .file-content-scroll {
            height: 500px;
            max-height: 70vh;
            overflow: auto;
            background: #fafafa;
            position: relative;
        }

        .file-content {
            padding: 20px;
            white-space: pre;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            display: block;
            min-width: 100%;
            width: max-content;
        }

        .file-content-scroll::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        .file-content-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 0;
        }

        .file-content-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 6px;
        }

        .file-content-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .file-content-scroll {
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }

        .file-footer {
            padding: 12px 18px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .back-link {
            color: #4a90e2;
            text-decoration: none;
            padding: 8px 16px;
            background: #ffffff;
            border-radius: 6px;
            transition: all 0.3s;
            border: 1px solid #4a90e2;
            display: inline-block;
        }

        .back-link:hover {
            background: #4a90e2;
            color: #ffffff;
            text-decoration: none;
        }

        .line-count {
            color: #6c757d;
            font-size: 13px;
        }

        .stats {
            margin-top: 20px;
            padding: 10px 0;
            color: #6c757d;
            font-size: 13px;
            border-top: 1px solid #e9ecef;
        }

        .stats span {
            margin-right: 10px;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            th,
            td {
                padding: 8px 10px;
                font-size: 13px;
            }

            .actions {
                flex-direction: column;
                gap: 4px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-buttons {
                width: 100%;
            }

            .top-back,
            .exit-btn {
                flex: 1;
                justify-content: center;
            }

            .file-content-scroll {
                height: 300px;
                max-height: 50vh;
            }

            .file-content {
                padding: 15px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>📁 Log File Manager</h1>
            </div>
            <div class="header-buttons">
                <?php if ($current_path != ''): ?>
                    <a href="?path=" class="top-back">⬅ Back</a>
                <?php endif; ?>
                <a href="<?= $backpage ?>" class="exit-btn" onclick="return confirmExit();">🚪 Exit</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="breadcrumb">
            <strong>Path:</strong>
            <a href="?path=">root</a>
            <?php
            $parts = explode('/', trim($current_path, '/'));
            $accumulated = '';
            foreach ($parts as $part) {
                if (!empty($part)) {
                    $accumulated .= '/' . $part;
                    echo ' / <a href="?path=' . urlencode($accumulated) . '">' . htmlspecialchars($part) . '</a>';
                }
            }
            ?>
        </div>

        <div class="controls">
            <input type="text" class="search-box" placeholder="🔍 Filter files..." id="searchInput" onkeyup="filterTable()">
            <span style="align-self: center; color: #6c757d; font-size: 13px;">
                <?php echo count($items); ?> items
            </span>
        </div>

        <div class="table-container">
            <table id="fileTable">
                <thead>
                    <tr>
                        <th style="width: 40%;">Name</th>
                        <th style="width: 12%;">Size</th>
                        <th style="width: 18%;">Modified</th>
                        <th style="width: 30%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="empty-message">📂 This directory is empty</td>
                        </tr>
                    <?php else: ?>
                        <?php $items = array_reverse($items); ?>
                        <?php foreach ($items as $item): ?>
                            <?php if (isset($item['name']) && str_starts_with($item['name'], ".git")) continue; ?>
                            <tr data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>">
                                <td>
                                    <?php if ($item['is_dir']): ?>
                                        <span class="folder-item" onclick="navigate('<?php echo urlencode($item['path']); ?>')">
                                            <span class="icon">📁</span>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="file-item" onclick="viewFile('<?php echo urlencode($item['path']); ?>')">
                                            <span class="icon">📄</span>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="file-size"><?php echo $item['size']; ?></td>
                                <td class="modified-date"><?php echo $item['modified']; ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if (!$item['is_dir']): ?>
                                            <a href="?path=<?php echo urlencode($current_path); ?>&view=1&file=<?php echo urlencode($item['path']); ?>"
                                                class="btn btn-view" target="_blank">📖 View</a>
                                        <?php endif; ?>
                                        <a href="?path=<?php echo urlencode($current_path); ?>&delete=1&file=<?php echo urlencode($item['path']); ?>"
                                            class="btn <?php echo $item['is_dir'] ? 'btn-delete-folder' : 'btn-delete'; ?>"
                                            onclick="return confirm('Are you sure you want to delete this <?php echo $item['is_dir'] ? 'folder' : 'file'; ?>?')">
                                            🗑 Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="stats">
            <span>Total: <?php echo count($items); ?> items</span>
            <span>Size: <?php echo formatSize($size); ?> <a href="?clearlogs=yes" onclick="return confirm('Are you sure to clear all logs?')">(clear)</a></span>
        </div>

        <?php if (isset($_GET['view']) && isset($_GET['file'])):
            $view_path = $base_path . '/' . $_GET['file'];
            $file_size = is_file($view_path) ? formatSize(filesize($view_path)) : '0 B';
            $file_content = '';
            $line_count = 0;

            if (is_file($view_path) && is_readable($view_path)) {
                $file_content = file_get_contents($view_path);
                $line_count = substr_count($file_content, "\n") + 1;
            }
        ?>
            <div class="file-content-wrapper">
                <div class="file-content-header">
                    <h3>📄 <?php echo htmlspecialchars(basename($_GET['file'])); ?></h3>
                    <div class="file-info">
                        Size: <?php echo $file_size; ?> | Lines: <?php echo number_format($line_count); ?>
                    </div>
                </div>
                <div class="file-content-scroll">
                    <div class="file-content"><?php if (is_file($view_path) && is_readable($view_path)) {
                                                    echo htmlspecialchars($file_content);
                                                } else {
                                                    echo 'Unable to read file.';
                                                }
                                                ?>
                    </div>
                </div>
                <div class="file-footer">
                    <a href="?path=<?php echo urlencode($current_path); ?>" class="back-link">⬅ Back to file list</a>
                    <span class="line-count">Total lines: <?php echo number_format($line_count); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function navigate(path) {
            window.location.href = '?path=' + path;
        }

        function viewFile(path) {
            const currentPath = '<?php echo urlencode($current_path); ?>';
            window.location.href = '?path=' + currentPath + '&view=1&file=' + path;
        }

        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('fileTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const name = row.getAttribute('data-name') || '';
                if (name.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        function confirmExit() {
            return confirm('Are you sure you want to exit the Log File Manager?');
        }

        <?php if (isset($_GET['exit'])): ?>
            window.location.href = 'about:blank';
            window.close();
        <?php endif; ?>
    </script>
</body>

</html>