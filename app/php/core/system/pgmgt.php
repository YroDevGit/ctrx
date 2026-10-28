<?php

use Classes\Request;
use Classes\Response;

$action = Request::get("__action");

if ($action) {
    header("Content-Type: application/json");

    $pagesDir = "views/pages";
    $jsDir = "views/js";

    $safePath = function ($path) {
        $path = str_replace("\\", "/", trim((string) $path, "/"));
        if ($path === "" || str_contains($path, "..") || str_starts_with($path, "_")) {
            return null;
        }
        if (! preg_match('/^[a-zA-Z0-9_\-\/]+$/', $path)) {
            return null;
        }
        return $path;
    };

    try {
        if ($action === "list") {
            if (! is_dir($pagesDir)) {
                echo json_encode(["code" => 200, "message" => "OK", "data" => [], "errors" => null]);
                exit;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pagesDir, FilesystemIterator::SKIP_DOTS)
            );

            $pages = [];

            foreach ($iterator as $file) {
                if (! $file->isFile() || strtolower($file->getExtension()) !== "php") {
                    continue;
                }

                $relative = str_replace("\\", "/", substr($file->getPathname(), strlen($pagesDir) + 1));
                $path = substr($relative, 0, -4);

                if (str_starts_with($path, "_") || str_contains($path, "/_")) {
                    continue;
                }

                $pages[] = [
                    "path" => $path,
                    "hasJs" => file_exists($jsDir . "/" . $path . ".js")
                ];
            }

            usort($pages, fn($a, $b) => strcmp($a["path"], $b["path"]));

            echo json_encode(["code" => 200, "message" => "OK", "data" => $pages, "errors" => null]);
            exit;
        }

        if ($action === "read") {
            $path = $safePath(Request::get("path"));

            if (! $path) {
                echo json_encode(["code" => 401, "message" => "Invalid path", "data" => null, "errors" => null]);
                exit;
            }

            $file = $pagesDir . "/" . $path . ".php";
            $jsFile = $jsDir . "/" . $path . ".js";

            if (! file_exists($file)) {
                echo json_encode(["code" => 404, "message" => "Page not found", "data" => null, "errors" => null]);
                exit;
            }

            echo json_encode([
                "code" => 200,
                "message" => "OK",
                "data" => [
                    "path" => $path,
                    "content" => file_get_contents($file),
                    "size" => filesize($file),
                    "modified" => date("Y-m-d H:i", filemtime($file)),
                    "hasJs" => file_exists($jsFile)
                ],
                "errors" => null
            ]);
            exit;
        }

        if ($action === "create") {
            $body = json_decode(file_get_contents("php://input"), true) ?? [];

            $path = $safePath($body["path"] ?? "");
            $createJs = ! empty($body["createJs"]);
            $rawJsPath = (string) ($body["jsPath"] ?? $path);
            $rawJsPath = preg_replace('/\.js$/i', '', $rawJsPath);
            $jsPath = $safePath($rawJsPath);

            if (! $path) {
                echo json_encode(["code" => 422, "message" => "Invalid path", "data" => null, "errors" => ["path" => "Invalid path"]]);
                exit;
            }

            $file = $pagesDir . "/" . $path . ".php";

            if (file_exists($file)) {
                echo json_encode(["code" => 422, "message" => "Page already exists", "data" => null, "errors" => ["path" => "Page already exists"]]);
                exit;
            }

            $dir = dirname($file);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $template = "<?php\n// page: /" . $path . " \n?>\n\n<!DOCTYPE html>\n<html>\n<head>\n    <title>$path</title>\n</head>\n<body>\n<h1>This is $path page</h1>\n</body>\n</html>\n";

            if (file_put_contents($file, $template) === false) {
                echo json_encode(["code" => 500, "message" => "Failed to create page", "data" => null, "errors" => null]);
                exit;
            }

            if ($createJs && $jsPath) {
                $jsFile = $jsDir . "/" . $jsPath . ".js";
                $jsDirPath = dirname($jsFile);

                if (! is_dir($jsDirPath)) {
                    mkdir($jsDirPath, 0777, true);
                }

                if (! file_exists($jsFile)) {
                    file_put_contents($jsFile, "// JS file for " . $path . "\n");
                }
            }

            echo json_encode(["code" => 200, "message" => "Page created", "data" => null, "errors" => null]);
            exit;
        }

        if ($action === "rename") {
            $body = json_decode(file_get_contents("php://input"), true) ?? [];

            $oldPath = $safePath(Request::get("path"));
            $newPath = $safePath($body["newPath"] ?? "");

            if (! $oldPath || ! $newPath) {
                echo json_encode(["code" => 401, "message" => "Invalid path", "data" => null, "errors" => null]);
                exit;
            }

            $oldFile = $pagesDir . "/" . $oldPath . ".php";
            $newFile = $pagesDir . "/" . $newPath . ".php";

            if (! file_exists($oldFile)) {
                echo json_encode(["code" => 404, "message" => "Page not found", "data" => null, "errors" => null]);
                exit;
            }

            if (file_exists($newFile)) {
                echo json_encode(["code" => 422, "message" => "Target already exists", "data" => null, "errors" => ["newPath" => "Target already exists"]]);
                exit;
            }

            $newDir = dirname($newFile);
            if (! is_dir($newDir)) {
                mkdir($newDir, 0777, true);
            }

            if (! rename($oldFile, $newFile)) {
                echo json_encode(["code" => 500, "message" => "Failed to rename page", "data" => null, "errors" => null]);
                exit;
            }

            $oldJs = $jsDir . "/" . $oldPath . ".js";
            $newJs = $jsDir . "/" . $newPath . ".js";

            if (file_exists($oldJs)) {
                $newJsDir = dirname($newJs);
                if (! is_dir($newJsDir)) {
                    mkdir($newJsDir, 0777, true);
                }
                rename($oldJs, $newJs);
            }

            echo json_encode(["code" => 200, "message" => "Page renamed", "data" => null, "errors" => null]);
            exit;
        }

        if ($action === "delete") {
            $path = $safePath(Request::get("path"));

            if (! $path) {
                echo json_encode(["code" => 401, "message" => "Invalid path", "data" => null, "errors" => null]);
                exit;
            }

            $file = $pagesDir . "/" . $path . ".php";
            $jsFile = $jsDir . "/" . $path . ".js";

            if (! file_exists($file)) {
                echo json_encode(["code" => 404, "message" => "Page not found", "data" => null, "errors" => null]);
                exit;
            }

            unlink($file);

            if (file_exists($jsFile)) {
                unlink($jsFile);
            }

            $dir = dirname($file);
            while ($dir !== $pagesDir && is_dir($dir) && count(scandir($dir)) === 2) {
                rmdir($dir);
                $dir = dirname($dir);
            }

            echo json_encode(["code" => 200, "message" => "Page deleted", "data" => null, "errors" => null]);
            exit;
        }

        echo json_encode(["code" => 400, "message" => "Unknown action", "data" => null, "errors" => null]);
        exit;
    } catch (\Throwable $e) {
        echo json_encode(["code" => 500, "message" => $e->getMessage(), "data" => null, "errors" => null]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Page Manager</title>
<link rel="stylesheet" href="/views/code/src/style/pgmgt.css">
</head>
<body>

<header class="pm-header">
    <div class="pm-header-inner">
        <span class="pm-logo">Page<span>Manager</span></span>
        <div class="pm-actions">
            <button id="backBtn" class="pm-btn">Back</button>
            <button id="refreshBtn" class="pm-btn">Refresh</button>
            <button id="addPageBtn" class="pm-btn pm-btn-primary">+ New Page</button>
        </div>
    </div>
</header>

<main class="pm-main">

    <section class="pm-sidebar">
        <div class="pm-sidebar-header">
            <span>Pages</span>
            <span id="pageCount" class="pm-count">0</span>
        </div>
        <div id="pageTree" class="pm-tree">
            <p class="pm-empty">Loading...</p>
        </div>
    </section>

    <section class="pm-content">
        <div id="emptyState" class="pm-empty-state">
            <h2>No page selected</h2>
            <p>Pick a page from the sidebar, or click <strong>+ New Page</strong> to create one.</p>
        </div>

        <div id="pageDetails" class="pm-details" style="display:none;">
            <div class="pm-details-header">
                <div class="pm-details-title">
                    <h2 id="detailPath">—</h2>
                    <span id="detailUrl" class="pm-url"></span>
                </div>
                <div class="pm-details-actions">
                    <button id="openPageBtn" class="pm-btn pm-btn-small">Open</button>
                    <button id="renamePageBtn" class="pm-btn pm-btn-small">Rename</button>
                    <button id="deletePageBtn" class="pm-btn pm-btn-small pm-btn-danger">Delete</button>
                </div>
            </div>

            <div class="pm-meta">
                <div class="pm-meta-row">
                    <span class="pm-meta-label">Size</span>
                    <span id="detailSize" class="pm-meta-value">—</span>
                </div>
                <div class="pm-meta-row">
                    <span class="pm-meta-label">Modified</span>
                    <span id="detailModified" class="pm-meta-value">—</span>
                </div>
                <div class="pm-meta-row">
                    <span class="pm-meta-label">Paired JS</span>
                    <span id="detailJs" class="pm-meta-value">—</span>
                </div>
            </div>

            <div class="pm-preview-header">
                <div class="pm-preview-tabs">
                    <button id="tabCode" class="pm-preview-tab active" data-tab="code">Code</button>
                    <button id="tabView" class="pm-preview-tab" data-tab="view">View</button>
                </div>
                <span id="previewLines" class="pm-preview-lines"></span>
            </div>

            <div class="pm-preview-body">
                <div id="previewCodeView" class="pm-preview-view active">
                    <pre id="pagePreview" class="pm-preview-code"></pre>
                </div>
                <div id="previewFrameView" class="pm-preview-view">
                    <iframe id="pageFrame" title="Page preview"></iframe>
                </div>
            </div>
        </div>
    </section>

</main>

<aside id="modalOverlay" class="pm-overlay">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h3 id="modalTitle">New Page</h3>
            <button id="modalClose" class="pm-btn pm-btn-small">×</button>
        </div>

        <div class="pm-modal-body">
            <label class="pm-field">
                <span class="pm-field-label">Page path</span>
                <input id="modalPath" class="pm-input" type="text" placeholder="user/orders" spellcheck="false" />
                <span class="pm-field-hint">Enter <code>user</code>, <code>user.php</code>, <code>user/orders</code>, or <code>user/orders.php</code></span>
            </label>

            <label class="pm-checkbox">
                <input id="modalCreateJs" type="checkbox" checked />
                Create paired JS file
            </label>

            <div id="jsFieldWrap" class="pm-field">
                <span class="pm-field-label">JS path</span>
                <input id="modalJsPath" class="pm-input" type="text" spellcheck="false" />
                <span class="pm-field-hint">Auto-generated from the page path. You can change it.</span>
            </div>

            <div id="modalError" class="pm-error" style="display:none;"></div>
        </div>

        <div class="pm-modal-footer">
            <button id="modalCancel" class="pm-btn">Cancel</button>
            <button id="modalConfirm" class="pm-btn pm-btn-primary">Create</button>
        </div>
    </div>
</aside>
<script src="/views/code/src/mods/internal/pgmgt.js"></script>

</body>
</html>