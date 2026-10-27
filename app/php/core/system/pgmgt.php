<?php

use Classes\Ctrx;
use Classes\Request;
use Classes\Response;
use Classes\Validator;

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
            $jsPath = $safePath($body["jsPath"] ?? $path);

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
                <span>Preview</span>
                <span id="previewLines" class="pm-preview-lines"></span>
            </div>
            <pre id="pagePreview" class="pm-preview"></pre>
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

<script>
(function () {
    const state = {
        pages: [],
        activePath: null,
        modalMode: "create",
        modalTarget: null
    };

    document.querySelector("#backBtn").addEventListener("click", ()=>{
        location.href = "/ctrx";
    });

    const el = {
        pageTree: document.getElementById("pageTree"),
        pageCount: document.getElementById("pageCount"),
        refreshBtn: document.getElementById("refreshBtn"),
        addPageBtn: document.getElementById("addPageBtn"),
        emptyState: document.getElementById("emptyState"),
        pageDetails: document.getElementById("pageDetails"),
        detailPath: document.getElementById("detailPath"),
        detailUrl: document.getElementById("detailUrl"),
        detailSize: document.getElementById("detailSize"),
        detailModified: document.getElementById("detailModified"),
        detailJs: document.getElementById("detailJs"),
        pagePreview: document.getElementById("pagePreview"),
        previewLines: document.getElementById("previewLines"),
        openPageBtn: document.getElementById("openPageBtn"),
        renamePageBtn: document.getElementById("renamePageBtn"),
        deletePageBtn: document.getElementById("deletePageBtn"),
        modalOverlay: document.getElementById("modalOverlay"),
        modalTitle: document.getElementById("modalTitle"),
        modalPath: document.getElementById("modalPath"),
        modalCreateJs: document.getElementById("modalCreateJs"),
        modalJsPath: document.getElementById("modalJsPath"),
        jsFieldWrap: document.getElementById("jsFieldWrap"),
        modalError: document.getElementById("modalError"),
        modalClose: document.getElementById("modalClose"),
        modalCancel: document.getElementById("modalCancel"),
        modalConfirm: document.getElementById("modalConfirm")
    };

    const API = "/ctrx/pagemgt";

    function normalizePath(input) {
        let path = String(input).trim().replace(/\\/g, "/").replace(/^\/+/, "");
        if (path.endsWith(".php")) path = path.slice(0, -4);
        return path;
    }

    function pathToJsPath(pagePath) {
        return pagePath + ".js";
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (c) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
        });
    }

    async function api(method, action, params, body) {
        let url = API + "?__action=" + encodeURIComponent(action);
        if (params) {
            Object.keys(params).forEach(function (k) {
                url += "&" + encodeURIComponent(k) + "=" + encodeURIComponent(params[k]);
            });
        }

        const options = {
            method: method,
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            }
        };

        if (body !== undefined && body !== null) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);
        const text = await response.text();

        let parsed;
        try {
            parsed = JSON.parse(text);
        } catch (e) {
            parsed = { code: response.status, message: text || response.statusText, data: null };
        }

        return parsed;
    }

    function buildTree(pages) {
        const root = { name: "", folders: {}, files: [] };

        pages.forEach(function (page) {
            const parts = page.path.split("/");
            const fileName = parts.pop();
            let node = root;

            parts.forEach(function (part) {
                if (!node.folders[part]) {
                    node.folders[part] = { name: part, folders: {}, files: [] };
                }
                node = node.folders[part];
            });

            node.files.push({
                name: fileName,
                fullPath: page.path,
                hasJs: page.hasJs
            });
        });

        return root;
    }

    function renderTree(node, container) {
        const folderNames = Object.keys(node.folders).sort();

        folderNames.forEach(function (name) {
            const folder = node.folders[name];
            const folderDiv = document.createElement("div");
            folderDiv.className = "pm-folder";

            const toggle = document.createElement("div");
            toggle.className = "pm-folder-toggle";
            toggle.innerHTML = '<span class="pm-caret">▼</span><span>' + escapeHtml(name) + "</span>";

            const children = document.createElement("div");
            children.className = "pm-folder-children";

            toggle.addEventListener("click", function () {
                toggle.classList.toggle("collapsed");
                children.classList.toggle("collapsed");
            });

            renderTree(folder, children);

            folderDiv.appendChild(toggle);
            folderDiv.appendChild(children);
            container.appendChild(folderDiv);
        });

        node.files.sort(function (a, b) { return a.name.localeCompare(b.name); }).forEach(function (file) {
            const item = document.createElement("div");
            item.className = "pm-page-item";
            item.dataset.path = file.fullPath;

            if (file.fullPath === state.activePath) {
                item.classList.add("active");
            }

            item.innerHTML =
                '<span class="pm-page-icon">📄</span>' +
                '<span class="pm-page-name">' + escapeHtml(file.name) + "</span>" +
                (file.hasJs ? '<span class="pm-page-js">JS</span>' : "");

            item.addEventListener("click", function () {
                selectPage(file.fullPath);
            });

            container.appendChild(item);
        });
    }

    function renderPages() {
        el.pageCount.textContent = state.pages.length;

        if (!state.pages.length) {
            el.pageTree.innerHTML = '<p class="pm-empty">No pages yet.<br>Click + New Page to create one.</p>';
            return;
        }

        el.pageTree.innerHTML = "";
        const tree = buildTree(state.pages);
        renderTree(tree, el.pageTree);
    }

    async function loadPages() {
        const data = await api("GET", "list");

        if (data.code !== 200) {
            alert(data.message || "Failed to load pages");
            return;
        }

        state.pages = Array.isArray(data.data) ? data.data : [];
        renderPages();

        if (state.activePath && !state.pages.find(function (p) { return p.path === state.activePath; })) {
            clearDetails();
        }
    }

    function selectPage(path) {
        state.activePath = path;

        document.querySelectorAll(".pm-page-item").forEach(function (item) {
            item.classList.toggle("active", item.dataset.path === path);
        });

        loadPageDetails(path);
    }

    async function loadPageDetails(path) {
        const data = await api("GET", "read", { path: path });

        if (data.code !== 200) {
            alert(data.message || "Failed to read page");
            clearDetails();
            return;
        }

        el.emptyState.style.display = "none";
        el.pageDetails.style.display = "flex";

        el.detailPath.textContent = path + ".php";
        el.detailUrl.textContent = "/" + path;
        el.detailSize.textContent = formatBytes(data.data.size);
        el.detailModified.textContent = data.data.modified || "—";
        el.detailJs.textContent = data.data.hasJs ? path + ".js" : "None";

        const content = data.data.content || "";
        el.pagePreview.textContent = content;
        el.previewLines.textContent = content.split("\n").length + " lines";
    }

    function clearDetails() {
        state.activePath = null;
        el.emptyState.style.display = "flex";
        el.pageDetails.style.display = "none";
        el.detailPath.textContent = "—";
        el.pagePreview.textContent = "";
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
        return (bytes / (1024 * 1024)).toFixed(1) + " MB";
    }

    function openModal(mode, target) {
        state.modalMode = mode;
        state.modalTarget = target || null;

        el.modalError.style.display = "none";
        el.modalError.textContent = "";

        if (mode === "create") {
            el.modalTitle.textContent = "New Page";
            el.modalPath.value = "";
            el.modalPath.disabled = false;
            el.modalCreateJs.checked = true;
            el.modalCreateJs.disabled = false;
            el.modalJsPath.value = "";
            el.jsFieldWrap.style.display = "flex";
            el.modalConfirm.textContent = "Create";
        } else if (mode === "rename") {
            el.modalTitle.textContent = "Rename Page";
            el.modalPath.value = target;
            el.modalPath.disabled = false;
            el.modalCreateJs.checked = false;
            el.modalCreateJs.disabled = true;
            el.jsFieldWrap.style.display = "none";
            el.modalConfirm.textContent = "Rename";
        }

        el.modalOverlay.classList.add("open");
        setTimeout(function () { el.modalPath.focus(); }, 50);
    }

    function closeModal() {
        el.modalOverlay.classList.remove("open");
        el.modalCreateJs.disabled = false;
    }

    function updateJsPath() {
        if (state.modalMode !== "create") return;

        if (el.modalCreateJs.checked) {
            el.jsFieldWrap.style.display = "flex";
            const pagePath = normalizePath(el.modalPath.value);
            el.modalJsPath.value = pagePath ? pathToJsPath(pagePath) : "";
        } else {
            el.jsFieldWrap.style.display = "none";
        }
    }

    el.modalPath.addEventListener("input", updateJsPath);
    el.modalCreateJs.addEventListener("change", updateJsPath);

    el.modalClose.addEventListener("click", closeModal);
    el.modalCancel.addEventListener("click", closeModal);

    el.modalOverlay.addEventListener("click", function (e) {
        if (e.target === el.modalOverlay) closeModal();
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && el.modalOverlay.classList.contains("open")) {
            closeModal();
        }
    });

    el.refreshBtn.addEventListener("click", loadPages);
    el.addPageBtn.addEventListener("click", function () { openModal("create"); });

    el.renamePageBtn.addEventListener("click", function () {
        if (state.activePath) openModal("rename", state.activePath);
    });

    el.openPageBtn.addEventListener("click", function () {
        if (state.activePath) {
            window.open("/" + state.activePath, "_blank");
        }
    });

    el.deletePageBtn.addEventListener("click", async function () {
        if (!state.activePath) return;

        const target = state.activePath;

        if (!confirm("Delete page " + target + ".php?")) return;

        const data = await api("DELETE", "delete", { path: target });

        if (data.code === 200) {
            alert("Page deleted");
            clearDetails();
            loadPages();
        } else {
            alert(data.message || "Delete failed");
        }
    });

    el.modalConfirm.addEventListener("click", async function () {
        const pagePath = normalizePath(el.modalPath.value);

        if (!pagePath) {
            showModalError("Enter a page path");
            return;
        }

        if (!/^[a-zA-Z0-9_\-\/]+$/.test(pagePath)) {
            showModalError("Only letters, numbers, underscore, dash, and slash are allowed");
            return;
        }

        if (state.modalMode === "create") {
            const createJs = el.modalCreateJs.checked;
            const jsPath = createJs ? normalizePath(el.modalJsPath.value || pathToJsPath(pagePath)) : null;

            const data = await api("POST", "create", null, {
                path: pagePath,
                createJs: createJs,
                jsPath: jsPath
            });

            if (data.code === 200) {
                closeModal();
                alert("Page created");
                await loadPages();
                setTimeout(function () { selectPage(pagePath); }, 300);
            } else {
                showModalError(data.message || "Create failed");
            }
        } else if (state.modalMode === "rename") {
            const oldPath = state.modalTarget;

            if (pagePath === oldPath) {
                closeModal();
                return;
            }

            const data = await api("PUT", "rename", { path: oldPath }, { newPath: pagePath });

            if (data.code === 200) {
                closeModal();
                alert("Page renamed");
                clearDetails();
                loadPages();
            } else {
                showModalError(data.message || "Rename failed");
            }
        }
    });

    function showModalError(msg) {
        el.modalError.textContent = msg;
        el.modalError.style.display = "block";
    }

    el.modalPath.addEventListener("keydown", function (e) {
        if (e.key === "Enter") el.modalConfirm.click();
    });

    loadPages();
})();
</script>

</body>
</html>