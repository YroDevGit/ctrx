(function () {
    const state = {
        pages: [],
        activePath: null,
        modalMode: "create",
        modalTarget: null,
        previewTab: "view"
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
        modalConfirm: document.getElementById("modalConfirm"),
        tabCode: document.getElementById("tabCode"),
        tabView: document.getElementById("tabView"),
        previewCodeView: document.getElementById("previewCodeView"),
        previewFrameView: document.getElementById("previewFrameView"),
        pageFrame: document.getElementById("pageFrame")
    };

    const API = "/ctrx/pagemgt";

    function normalizePath(input) {
        let path = String(input).trim().replace(/\\/g, "/").replace(/^\/+/, "");
        if (path.endsWith(".php")) path = path.slice(0, -4);
        if (path.endsWith(".js")) path = path.slice(0, -3);
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

    function highlightCode(code) {
    if (!code) return "";

    const tokens = [];
    let i = 0;
    const len = code.length;

    const keywords = new Set([
        "abstract","and","array","as","break","callable","case","catch","class","clone","const","continue",
        "declare","default","do","echo","else","elseif","empty","enddeclare","endfor","endforeach","endif",
        "endswitch","endwhile","extends","final","finally","fn","for","foreach","function","global","goto",
        "if","implements","include","include_once","instanceof","insteadof","interface","isset","list","match",
        "namespace","new","or","print","private","protected","public","readonly","require","require_once",
        "return","static","switch","throw","trait","try","unset","use","var","while","xor","yield",
        "true","false","null","let","typeof","void","async","await","this","super","export","import","from","of","in"
    ]);

    const push = (cls, text) => {
        if (text) tokens.push({ cls, text });
    };

    while (i < len) {
        if (code.startsWith("<?php", i)) { push("tok-php", "<?php"); i += 5; continue; }
        if (code.startsWith("<?=", i))   { push("tok-php", "<?=");  i += 3; continue; }
        if (code.startsWith("?>", i))    { push("tok-php", "?>");   i += 2; continue; }

        if (code.startsWith("<!--", i)) {
            const end = code.indexOf("-->", i + 4);
            const stop = end === -1 ? len : end + 3;
            push("tok-comment", code.slice(i, stop));
            i = stop;
            continue;
        }

        if (code.startsWith("/*", i)) {
            const end = code.indexOf("*/", i + 2);
            const stop = end === -1 ? len : end + 2;
            push("tok-comment", code.slice(i, stop));
            i = stop;
            continue;
        }

        if (code.startsWith("//", i) || code[i] === "#") {
            const end = code.indexOf("\n", i);
            const stop = end === -1 ? len : end;
            push("tok-comment", code.slice(i, stop));
            i = stop;
            continue;
        }

        if (code[i] === '"' || code[i] === "'" || code[i] === "`") {
            const quote = code[i];
            let j = i + 1;
            while (j < len) {
                if (code[j] === "\\") { j += 2; continue; }
                if (code[j] === quote) { j++; break; }
                if (code[j] === "\n" && quote !== "`") { break; }
                j++;
            }
            push("tok-string", code.slice(i, j));
            i = j;
            continue;
        }

        if (code[i] === "<" && /[a-zA-Z\/!]/.test(code[i + 1] || "")) {
            let j = i + 1;
            if (code[j] === "/") j++;
            while (j < len && /[a-zA-Z0-9\-]/.test(code[j])) j++;
            push("tok-tag", code.slice(i, j));

            while (j < len && code[j] !== ">") {
                if (code[j] === "/" && code[j + 1] === ">") break;

                if (/[a-zA-Z_:]/.test(code[j])) {
                    let aStart = j;
                    while (j < len && /[a-zA-Z0-9_:.\-]/.test(code[j])) j++;
                    push("tok-attr", code.slice(aStart, j));

                    while (j < len && /\s/.test(code[j])) j++;
                    if (code[j] === "=") {
                        push("tok-op", "=");
                        j++;
                        while (j < len && /\s/.test(code[j])) j++;
                        if (code[j] === '"' || code[j] === "'") {
                            const q = code[j];
                            let vStart = j;
                            j++;
                            while (j < len && code[j] !== q) {
                                if (code[j] === "\\") j++;
                                j++;
                            }
                            j++;
                            push("tok-string", code.slice(vStart, j));
                        }
                    }
                    continue;
                }

                push("tok-text", code[j]);
                j++;
            }

            if (code[j] === "/") { push("tok-tag", "/>"); j += 2; }
            else if (code[j] === ">") { push("tok-tag", ">"); j++; }

            i = j;
            continue;
        }

        if (code[i] === "$" && /[a-zA-Z_]/.test(code[i + 1] || "")) {
            let j = i + 1;
            while (j < len && /[a-zA-Z0-9_]/.test(code[j])) j++;
            push("tok-var", code.slice(i, j));
            i = j;
            continue;
        }

        if (/[0-9]/.test(code[i]) && !/[a-zA-Z_]/.test(code[i - 1] || "")) {
            let j = i;
            while (j < len && /[0-9._]/.test(code[j])) j++;
            push("tok-num", code.slice(i, j));
            i = j;
            continue;
        }

        if (/[a-zA-Z_]/.test(code[i])) {
            let j = i;
            while (j < len && /[a-zA-Z0-9_]/.test(code[j])) j++;
            const word = code.slice(i, j);

            if (keywords.has(word.toLowerCase())) {
                push("tok-kw", word);
            } else if (code[j] === "(") {
                push("tok-fn", word);
            } else {
                push("tok-id", word);
            }
            i = j;
            continue;
        }

        push("tok-text", code[i]);
        i++;
    }

    return tokens.map(t => {
        const safe = t.text.replace(/[&<>"']/g, c => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        }[c]));
        return t.cls ? `<span class="${t.cls}">${safe}</span>` : safe;
    }).join("");
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
        el.pagePreview.innerHTML = highlightCode(content);
        el.previewLines.textContent = content.split("\n").length + " lines";

        el.pageFrame.src = "/" + path + "?t=" + Date.now();
        setPreviewTab("view");
    }

    function clearDetails() {
        state.activePath = null;
        el.emptyState.style.display = "flex";
        el.pageDetails.style.display = "none";
        el.detailPath.textContent = "—";
        el.pagePreview.textContent = "";
        el.pageFrame.src = "about:blank";
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
        return (bytes / (1024 * 1024)).toFixed(1) + " MB";
    }

    function setPreviewTab(tab) {
        state.previewTab = tab;

        el.tabCode.classList.toggle("active", tab === "code");
        el.tabView.classList.toggle("active", tab === "view");

        el.previewCodeView.classList.toggle("active", tab === "code");
        el.previewFrameView.classList.toggle("active", tab === "view");

        if (tab === "view" && state.activePath) {
            el.pageFrame.src = "/" + state.activePath + "?t=" + Date.now();
        }
    }

    el.tabCode.addEventListener("click", function () {
        setPreviewTab("code");
    });

    el.tabView.addEventListener("click", function () {
        setPreviewTab("view");
    });

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