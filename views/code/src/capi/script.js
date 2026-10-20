import Tyrax from "../tyrux/main.js";
import Twal from "../mods/twal.js";

const HISTORY_COOKIE = "_ctrx_saved_testrequest";
const HISTORY_LIMIT = 30;

function tokenizeCurl(input) {
    const tokens = [];
    let current = "";
    let quote = null;
    let i = 0;

    while (i < input.length) {
        const ch = input[i];
        const next = input[i + 1];

        if (quote === null && ch === "#") {
            while (i < input.length && input[i] !== "\n") i++;
            continue;
        }

        if (quote === null && ch === "\\" && next === "\n") {
            i += 2;
            continue;
        }

        if (quote === null && ch === "\\" && next === "\r" && input[i + 2] === "\n") {
            i += 3;
            continue;
        }

        if (quote === null && ch === "\\") {
            if (next !== undefined) {
                current += next;
                i += 2;
                continue;
            }
        }

        if (quote === "'") {
            if (ch === "'") {
                quote = null;
                i++;
                continue;
            }

            if (ch === "'" && next === '"' && input[i + 2] === "'" && input[i + 3] === '"' && input[i + 4] === "'") {
                current += "'";
                i += 5;
                continue;
            }

            current += ch;
            i++;
            continue;
        }

        if (quote === '"') {
            if (ch === '"') {
                quote = null;
                i++;
                continue;
            }

            if (ch === "\\" && (next === '"' || next === "\\" || next === "$" || next === "`")) {
                current += next;
                i += 2;
                continue;
            }

            current += ch;
            i++;
            continue;
        }

        if (ch === "'" || ch === '"') {
            quote = ch;
            i++;
            continue;
        }

        if (/\s/.test(ch)) {
            if (current.length) {
                tokens.push(current);
                current = "";
            }
            i++;
            continue;
        }

        current += ch;
        i++;
    }

    if (current.length) tokens.push(current);

    return tokens;
}

function parseCurl(curlString) {
    const tokens = tokenizeCurl(curlString);

    if (!tokens.length || tokens[0].toLowerCase() !== "curl") {
        Twal.err("Not a cURL command");
        throw new Error("Not a cURL command");
    }

    const request = {
        method: "GET",
        url: "",
        headers: {},
        body: null,
        bodyMode: "json",
        params: {}
    };

    const headerFlags = ["-H", "--header"];
    const methodFlags = ["-X", "--request"];
    const urlFlags = ["--url"];
    const dataFlags = ["-d", "--data", "--data-raw", "--data-binary", "--data-ascii"];
    const dataUrlencodeFlags = ["--data-urlencode"];
    const formFlags = ["-F", "--form"];
    const ignoreFlags = [
        "-s", "--silent",
        "-k", "--insecure",
        "-L", "--location",
        "-i", "--include",
        "-v", "--verbose",
        "-f", "--fail",
        "-S", "--show-error",
        "-g", "--globoff",
        "--compressed"
    ];

    let hasBody = false;
    let bodyParts = [];

    for (let i = 1; i < tokens.length; i++) {
        const token = tokens[i];

        if (methodFlags.includes(token)) {
            request.method = (tokens[++i] || "GET").toUpperCase();
            continue;
        }

        if (urlFlags.includes(token)) {
            request.url = tokens[++i] || "";
            continue;
        }

        if (headerFlags.includes(token)) {
            const raw = tokens[++i] || "";
            const colon = raw.indexOf(":");
            if (colon > 0) {
                const name = raw.slice(0, colon).trim();
                const value = raw.slice(colon + 1).trim();
                request.headers[name] = value;
            }
            continue;
        }

        if (dataFlags.includes(token)) {
            bodyParts.push(tokens[++i] || "");
            hasBody = true;
            continue;
        }

        if (dataUrlencodeFlags.includes(token)) {
            const raw = tokens[++i] || "";
            const eq = raw.indexOf("=");
            if (eq > 0) {
                const key = raw.slice(0, eq);
                const value = raw.slice(eq + 1);
                request.params[decodeURIComponent(key)] = decodeURIComponent(value);
            }
            hasBody = true;
            continue;
        }

        if (formFlags.includes(token)) {
            const raw = tokens[++i] || "";
            const eq = raw.indexOf("=");
            if (eq > 0) {
                const key = raw.slice(0, eq);
                const value = raw.slice(eq + 1);
                request.params[decodeURIComponent(key)] = decodeURIComponent(value);
            }
            request.bodyMode = "form";
            hasBody = true;
            continue;
        }

        if (ignoreFlags.includes(token)) {
            continue;
        }

        if (token.startsWith("-")) {
            const next = tokens[i + 1];
            if (next !== undefined && !next.startsWith("-")) {
                i++;
            }
            continue;
        }

        if (!request.url) {
            request.url = token;
        }
    }

    if (hasBody && bodyParts.length) {
        request.body = bodyParts.join("&");
        try {
            JSON.parse(request.body);
            request.bodyMode = "json";
        } catch (e) {
            if (request.body.includes("=")) {
                request.bodyMode = "form";
            }
        }
    }

    try {
        const parsed = new URL(request.url);
        parsed.searchParams.forEach((value, key) => {
            request.params[key] = value;
        });
        request.url = parsed.origin + parsed.pathname;
    } catch (e) { }

    if (request.method === "GET" && request.body) {
        request.method = "POST";
    }

    return request;
}

const state = {
    method: "GET",
    url: "",
    bodyMode: "json",
    bodyList: [],
    params: [],
    headers: [],
    history: [],
    lastResponse: null,
    curlTab: "raw"
};

const el = {
    method: document.getElementById("method"),
    url: document.getElementById("url"),
    sendBtn: document.getElementById("sendBtn"),
    bodyInput: document.getElementById("bodyInput"),
    formatJson: document.getElementById("formatJson"),
    paramsList: document.getElementById("paramsList"),
    bodyList: document.getElementById("bodyList"),
    headersList: document.getElementById("headersList"),
    addParam: document.getElementById("addParam"),
    addBodyList: document.getElementById("addField"),
    addHeader: document.getElementById("addHeader"),
    statusCode: document.getElementById("statusCode"),
    statusTime: document.getElementById("statusTime"),
    statusSize: document.getElementById("statusSize"),
    responsePretty: document.getElementById("responsePretty"),
    responseRaw: document.getElementById("responseRaw"),
    optReload: document.getElementById("optReload"),
    optLoading: document.getElementById("optLoading"),
    historyToggle: document.getElementById("historyToggle"),
    historyPanel: document.getElementById("historyPanel"),
    historyClose: document.getElementById("historyClose"),
    historyList: document.getElementById("historyList"),
    clearBtn: document.getElementById("clearBtn"),
    curlBtn: document.getElementById("curlBtn"),
    curlPanel: document.getElementById("curlPanel"),
    curlClose: document.getElementById("curlClose"),
    curlRaw: document.getElementById("curlRaw"),
    curlTyrax: document.getElementById("curlTyrax"),
    curlForm: document.getElementById("curlForm"),
    curlTable: document.getElementById("curlTable"),
    curlCopy: document.getElementById("curlCopy")
};

function setCookie(name, value, days = 30) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = name + "=" + encodeURIComponent(value) + "; expires=" + expires + "; path=/; SameSite=Lax";
}

function getCookie(name) {
    const match = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + "=([^;]*)"));
    return match ? decodeURIComponent(match[1]) : null;
}

function loadHistory() {
    const raw = getCookie(HISTORY_COOKIE);
    if (!raw) return [];

    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
}
function getPath(url) {
    return new URL(url).pathname.replace(/^\/+/, "");
}

document.querySelector("#jsongen").addEventListener("click", () => {
    document.querySelector("#curlClose").click();
    let curbody = document.querySelector("#bodyInput").value;
    if (!curbody) return;
    let parsedCurl = parseCurl(curbody);
    console.log(parsedCurl);
    if (parsedCurl.body) {
        parsedCurl.body = JSON.parse(parsedCurl.body);
        loadBodyData(parsedCurl.body);
    }
    if (parsedCurl.params) {
        loadParamsData(parsedCurl.params);
    }

    if (parsedCurl.headers) {
        loadHeadersData(parsedCurl.headers);
    }

    if (parsedCurl.url) {
        el.url.value = getPath(parsedCurl.url);
    }
});

function saveHistory() {
    const trimmed = state.history.slice(0, HISTORY_LIMIT);
    setCookie(HISTORY_COOKIE, JSON.stringify(trimmed), 30);
}

function switchTab(target) {
    document.querySelectorAll(".tester-tab").forEach((t) => t.classList.toggle("active", t.dataset.tab === target));
    document.querySelectorAll(".tester-panel").forEach((p) => p.classList.toggle("active", p.dataset.panel === target));
}

function switchResponseTab(target) {
    document.querySelectorAll(".tester-rtab").forEach((t) => t.classList.toggle("active", t.dataset.rtab === target));
    document.querySelectorAll(".tester-response-panel").forEach((p) => p.classList.toggle("active", p.id === (target === "pretty" ? "responsePretty" : "responseRaw")));
}

function switchCurlTab(target) {
    state.curlTab = target;
    document.querySelectorAll(".tester-ctab").forEach((t) => t.classList.toggle("active", t.dataset.ctab === target));

    const map = { raw: el.curlRaw, tyrax: el.curlTyrax, form: el.curlForm, tabletab: el.curlTable };
    Object.entries(map).forEach(([key, node]) => {
        node.classList.toggle("active", key === target);
    });
}

document.querySelectorAll(".tester-tab").forEach((tab) => {
    tab.addEventListener("click", () => switchTab(tab.dataset.tab));
});

document.querySelectorAll(".tester-rtab").forEach((tab) => {
    tab.addEventListener("click", () => switchResponseTab(tab.dataset.rtab));
});

document.querySelectorAll(".tester-ctab").forEach((tab) => {
    tab.addEventListener("click", () => switchCurlTab(tab.dataset.ctab));
});

document.querySelectorAll('input[name="bodyMode"]').forEach((radio) => {
    radio.addEventListener("change", () => {
        state.bodyMode = radio.value;
        el.bodyInput.placeholder = state.bodyMode === "json"
            ? '{ "title": "The Pragmatic Programmer" }'
            : "title=The+Pragmatic+Programmer&author=Andrew+Hunt";
    });
});

el.method.addEventListener("change", () => {
    state.method = el.method.value;
});

el.url.addEventListener("input", () => {
    state.url = el.url.value.trim();
});

function addKvRow(container, store, key = "", value = "") {
    const row = document.createElement("div");
    row.className = "tester-kv-row";

    const keyInput = document.createElement("input");
    keyInput.placeholder = "key";
    keyInput.value = key;

    const valInput = document.createElement("input");
    valInput.placeholder = "value";
    valInput.value = value;

    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.textContent = "×";

    const entry = { key: keyInput, value: valInput };

    keyInput.addEventListener("input", () => {
        entry.key = keyInput.value.trim();
    });

    valInput.addEventListener("input", () => {
        entry.value = valInput.value;
    });

    removeBtn.addEventListener("click", () => {
        row.remove();
        const idx = store.indexOf(entry);
        if (idx >= 0) store.splice(idx, 1);
    });

    row.appendChild(keyInput);
    row.appendChild(valInput);
    row.appendChild(removeBtn);
    container.appendChild(row);
    store.push(entry);
}

el.addParam.addEventListener("click", () => addKvRow(el.paramsList, state.params));
el.addBodyList.addEventListener("click", () => addKvRow(el.bodyList, state.bodyList));
el.addHeader.addEventListener("click", () => addKvRow(el.headersList, state.headers));

el.formatJson.addEventListener("click", () => {
    try {
        const parsed = JSON.parse(el.bodyInput.value || "{}");
        document.querySelector("#formatJson").click();
        el.bodyInput.value = JSON.stringify(parsed, null, 2);
    } catch (e) {
        Twal.err("Invalid JSON: " + e.message);
    }
});

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    }[c]));
}

function syntaxHighlight(json) {
    if (typeof json !== "string") {
        json = JSON.stringify(json, null, 2);
    }

    json = escapeHtml(json);

    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, (match) => {
        let cls = "json-number";

        if (/^"/.test(match)) {
            cls = /:$/.test(match) ? "json-key" : "json-string";
        } else if (/true|false/.test(match)) {
            cls = "json-bool";
        } else if (/null/.test(match)) {
            cls = "json-null";
        }

        return '<span class="' + cls + '">' + match + "</span>";
    });
}

function setStatus(code, time, size) {
    el.statusCode.textContent = code || "—";
    el.statusCode.className = "tester-status-code";

    if (code >= 200 && code < 300) el.statusCode.classList.add("ok");
    else if (code >= 400 && code < 500) el.statusCode.classList.add("warn");
    else if (code >= 500) el.statusCode.classList.add("err");

    el.statusTime.textContent = time ? time + " ms" : "";
    el.statusSize.textContent = size ? formatBytes(size) : "";
}

function formatBytes(bytes) {
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}

function collectParams() {
    const out = {};
    state.params.forEach((p) => {
        let key = p.key;
        let val = null;
        if (key instanceof HTMLInputElement) {
            key = p.key.value ? p.key.value.trim() : "";
            val = p.value ? p.value.value : "";
        } else {
            key = p.key ? p.key : "";
            val = p.value ? p.value : "";
        }

        if (key) out[key] = val;
    });
    return out;
}

function collectBodyList() {
    let out = {};
    state.bodyList.forEach((p) => {
        console.log(p);
        let key = p.key ? p.key : "";
        let val = p.value ? p.value : "";
        if (key instanceof HTMLInputElement) {
            key = key.value;
            val = val.value;
        }
        if (key) out[key] = val;
    });
    return out;
}

function collectHeaders() {
    const out = {
        "Content-Type": "application/json"
    };
    state.headers.forEach((h) => {
        let key = h.key
        let val = null;
        if (key instanceof HTMLInputElement) {
            key = h.key.value ? h.key.value.trim() : "";
            val = h.value ? h.value.value : "";
        } else {
            key = h.key ? h.key : "";
            val = h.value ? h.value : "";
        }
        if (key) out[key] = val;
    });
    return out;
}

function parseBody() {
    const raw = el.bodyInput.value.trim();
    if (!raw) return null;

    if (state.bodyMode === "json") {
        try {
            return JSON.parse(raw);
        } catch (e) {
            throw new Error("Invalid JSON body: " + e.message);
        }
    }

    const out = {};
    raw.split("&").forEach((pair) => {
        const [k, v] = pair.split("=");
        if (k) out[decodeURIComponent(k)] = decodeURIComponent(v || "");
    });
    return out;
}

function parseFormBody() {
    const raw = el.bodyInput.value.trim();
    if (!raw) return {};
    const out = {};
    raw.split("&").forEach((pair) => {
        const [k, v] = pair.split("=");
        if (k) out[decodeURIComponent(k)] = decodeURIComponent(v || "");
    });
    return out;
}

function addToHistory(entry) {
    state.history.unshift(entry);
    if (state.history.length > HISTORY_LIMIT) state.history = state.history.slice(0, HISTORY_LIMIT);
    saveHistory();
    renderHistory();
}

function removeHistoryAt(index) {
    state.history.splice(index, 1);
    saveHistory();
    renderHistory();
}


function loadBodyData(data) {
    el.bodyInput.value = "";
    el.bodyList.innerHTML = "";
    if (!data) return;
    el.bodyInput.value = JSON.stringify(data);
    document.querySelector("#formatJson").click();
    let ibody = data;
    for (let x in ibody) {
        addKvRow(el.bodyList, state.bodyList, x, ibody[x]);
    }
}

function loadParamsData(data) {
    el.paramsList.innerHTML = "";
    state.params = [];
    Object.entries(data).forEach(([k, v]) => addKvRow(el.paramsList, state.params, k, v));
}

function loadHeadersData(data) {
    el.headersList.innerHTML = "";
    state.headers = [];
    Object.entries(data).forEach(([k, v]) => addKvRow(el.headersList, state.headers, k, v));
}

function renderHistory() {
    el.historyList.innerHTML = "";

    if (!state.history.length) {
        el.historyList.innerHTML = '<p style="color:var(--text-muted);font-size:0.85rem;padding:0.5rem;">No history yet.</p>';
        return;
    }

    state.history.forEach((item, index) => {
        const div = document.createElement("div");
        div.className = "tester-history-item";

        div.innerHTML =
            '<button class="hi-delete" title="Remove">×</button>' +
            '<span class="hi-method">' + escapeHtml(item.method) + "</span>" +
            '<span class="hi-url">' + escapeHtml(item.url) + "</span>" +
            '<span class="hi-time">' + escapeHtml(item.time) + "</span>";

        div.addEventListener("click", (e) => {
            if (e.target.classList.contains("hi-delete")) return;

            el.method.value = item.method;
            el.url.value = item.url;
            state.method = item.method;
            state.url = item.url;

            console.log(item);

            if (item.body) {
                loadBodyData(item.body);
            }

            if (item.params) {
                loadParamsData(item.params);
            }

            if (item.headers) {
                loadHeadersData(item.headers);
            }

            el.historyPanel.classList.remove("open");
        });

        div.querySelector(".hi-delete").addEventListener("click", (e) => {
            e.stopPropagation();
            removeHistoryAt(index);
        });

        el.historyList.appendChild(div);
    });
}

async function sendRequest() {
    const url = el.url.value.trim();
    document.querySelector("#curlClose").click();
    if (!url) {
        Twal.err("Enter a URL first");
        return;
    }

    let body;
    try {
        body = parseBody();
    } catch (e) {
        Twal.err(e.message);
        return;
    }

    const params = collectParams();

    const headers = collectHeaders();
    const start = performance.now();

    if (document.querySelector("#bnraw").classList.contains("active")) {
        body = collectBodyList();
    }

    el.sendBtn.disabled = true;
    el.sendBtn.textContent = "Sending";
    setStatus(null, null, null);

    const payload = {
        url: url,
        params: params,
        req: body,
        res: (send, code, message, data, errors) => {
            const time = Math.round(performance.now() - start);
            const raw = JSON.stringify(send, null, 2);
            const size = new Blob([raw]).size;

            setStatus(code, time, size);

            el.responsePretty.innerHTML = syntaxHighlight(raw);
            el.responseRaw.textContent = raw;

            console.log(params)
            state.lastResponse = send;

            loadBodyData(body);
            addToHistory({
                method: state.method,
                url: url,
                time: new Date().toLocaleString(),
                body: body,
                bodyMode: state.bodyMode,
                params: Object.keys(params).length ? params : null,
                headers: Object.keys(headers).length ? headers : null
            });
        }
    };

    if (Object.keys(params).length) payload.params = params;
    if (Object.keys(headers).length) payload.headers = headers;

    if (state.method === "GET") {
        Tyrax.get(payload);
    } else if (state.method === "POST") {
        payload.params = params;
        Tyrax.post(payload);
    } else if (state.method === "PUT") {
        Tyrax.put(payload);
    } else if (state.method === "DELETE") {
        Tyrax.delete(payload);
    }

    setTimeout(() => {
        el.sendBtn.disabled = false;
        el.sendBtn.textContent = "Send";
    }, 200);
}

el.sendBtn.addEventListener("click", sendRequest);

el.url.addEventListener("keydown", (e) => {
    if (e.key === "Enter") sendRequest();
});

el.historyToggle.addEventListener("click", () => {
    el.historyPanel.classList.toggle("open");
});

el.historyClose.addEventListener("click", () => {
    el.historyPanel.classList.remove("open");
});

el.clearBtn.addEventListener("click", () => {
    el.url.value = "";
    el.bodyInput.value = "";
    el.paramsList.innerHTML = "";
    el.headersList.innerHTML = "";
    state.params = [];
    state.headers = [];
    state.url = "";
    setStatus(null, null, null);
    el.responsePretty.innerHTML = "";
    el.responseRaw.textContent = "";
    Twal.ok("Cleared");
});

function buildFullUrl(withParams = true) {
    const base = location.origin;
    const path = el.url.value.trim();
    if (!path) return base;

    let full = path.startsWith("http") ? path : base + (path.startsWith("/") ? path : "/" + path);

    if (withParams) {
        const params = collectParams();
        if (Object.keys(params).length) {
            const qs = new URLSearchParams(params).toString();
            full += (full.includes("?") ? "&" : "?") + qs;
        }
    }

    return full;
}

function buildBodyForCurl() {
    const raw = el.bodyInput.value.trim();
    return raw || "";
}

function buildHeadersForCurl() {
    const out = collectHeaders();
    const headers = [];

    const hasBody = el.bodyInput.value.trim() && (state.method === "POST" || state.method === "PUT" || state.method === "DELETE");

    if (hasBody) {
        if (state.bodyMode === "json") {
            headers.push(["Content-Type", "application/json"]);
        } else {
            headers.push(["Content-Type", "application/x-www-form-urlencoded"]);
        }
    }

    Object.entries(out).forEach(([k, v]) => {
        if (!headers.find(([hk]) => hk.toLowerCase() === k.toLowerCase())) {
            headers.push([k, v]);
        }
    });

    return headers;
}

function generateRawCurl() {
    const url = buildFullUrl(true);
    const body = buildBodyForCurl();
    const headers = buildHeadersForCurl();
    const hasBody = body;

    let curl = "curl -X " + state.method + " '" + url + "'";

    headers.forEach(([k, v]) => {
        curl += " \\\n  -H '" + k + ": " + v + "'";
    });

    if (hasBody) {
        if (state.bodyMode === "json") {
            curl += " \\\n  -d '" + body.replace(/'/g, "'\\''") + "'";
        } else {
            const parsed = parseFormBody();
            Object.entries(parsed).forEach(([k, v]) => {
                curl += " \\\n  --data-urlencode '" + k + "=" + String(v).replace(/'/g, "'\\''") + "'";
            });
        }
    }

    return curl;
}

function generateTyraxCurl() {
    const path = el.url.value.trim();
    const body = buildBodyForCurl();
    const params = collectParams();
    const headers = collectHeaders();
    const hasBody = body && (state.method === "POST" || state.method === "PUT");

    let code = '// Import the TYRAX library here\n\n';

    if (state.method === "GET") {
        code += "Tyrax.get({\n";
    } else if (state.method === "POST") {
        code += "Tyrax.post({\n";
    } else if (state.method === "PUT") {
        code += "Tyrax.put({\n";
    } else {
        code += "Tyrax.delete({\n";
    }

    code += '    url: "' + path + '",\n';

    if (Object.keys(params).length) {
        code += "    params: " + JSON.stringify(params, null, 8).replace(/\n/g, "\n    ") + ",\n";
    }

    if (hasBody) {
        const key = state.method === "PUT" ? "data" : "req";
        if (state.bodyMode === "json") {
            try {
                const parsed = JSON.parse(body);
                code += "    " + key + ": " + JSON.stringify(parsed, null, 8).replace(/\n/g, "\n    ") + ",\n";
            } catch (e) {
                code += "    " + key + ": " + body + ",\n";
            }
        } else {
            code += "    " + key + ": " + JSON.stringify(parseFormBody(), null, 8).replace(/\n/g, "\n    ") + ",\n";
        }
    }

    if (Object.keys(headers).length) {
        code += "    headers: " + JSON.stringify(headers, null, 8).replace(/\n/g, "\n    ") + ",\n";
    }

    code += "    res: (send, code, message, data, errors) => {\n";
    code += "        if (code === 200) Twal.ok(message);\n";
    code += "        if (code === 422) console.log(errors);\n";
    code += "        console.log(send);\n";
    code += "    }\n";
    code += "});\n";

    return code;
}

function generateFormCurl() {
    const url = buildFullUrl(true);
    const body = buildBodyForCurl();
    const headers = buildHeadersForCurl().filter(([k]) => k.toLowerCase() !== "content-type");
    const hasBody = body && (state.method === "POST" || state.method === "PUT" || state.method === "DELETE");

    let curl = "curl -X " + state.method + " '" + url + "'";

    headers.forEach(([k, v]) => {
        curl += " \\\n  -H '" + k + ": " + v + "'";
    });

    if (hasBody) {
        let parsed;
        if (state.bodyMode === "json") {
            try {
                parsed = JSON.parse(body);
            } catch (e) {
                parsed = {};
            }
        } else {
            parsed = parseFormBody();
        }

        Object.entries(parsed).forEach(([k, v]) => {
            const val = typeof v === "object" ? JSON.stringify(v) : String(v);
            curl += " \\\n  --data-urlencode '" + k + "=" + val.replace(/'/g, "'\\''") + "'";
        });
    }

    return curl;
}

function renderCurlPanel() {
    el.curlRaw.textContent = generateRawCurl();
    el.curlTyrax.textContent = generateTyraxCurl();
    el.curlForm.textContent = generateFormCurl();
    let curlTableraw = parseCurl(el.curlRaw.textContent);
    el.curlTable.innerHTML = renderCurlTables(curlTableraw);
}

function renderCurlTables(data) {
    if (!data || typeof data !== 'object') return '';
    let html = '';
    html += buildTable('URL', [
        ['url', data.url]
    ]);

    html += buildTable('Method', [
        ['method', data.method]
    ]);

    html += buildTable('Headers',
        data.headers && typeof data.headers === 'object'
            ? Object.entries(data.headers)
            : []
    );


    if (typeof data.body == "string") {
        data.body = JSON.parse(data.body);
    }
    html += buildTable('Body',
        data.body && typeof data.body === 'object'
            ? Object.entries(data.body)
            : []
    );

    html += buildTable('Params',
        data.params && typeof data.params === 'object'
            ? Object.entries(data.params)
            : []
    );
    return html;
}

function buildTable(title, entries) {
    if (!entries.length) return '';

    let html = `<div class="curl-section">`;
    html += `<span>${escapeHtml(title)}: </span>`;
    html += `<table border='1' class="curl-table">`;
    html += `<thead><tr><th>Key</th><th>Value</th></tr></thead><tbody>`;

    for (const [key, value] of entries) {
        let display;
        if (value === null || value === undefined) {
            display = '<em>null</em>';
        } else if (typeof value === 'object') {
            display = `<pre>${escapeHtml(JSON.stringify(value, null, 2))}</pre>`;
        } else {
            display = escapeHtml(String(value));
        }
        html += `<tr><td class="key">${escapeHtml(key)}</td><td class="value">${display}</td></tr>`;
    }

    html += `</tbody></table></div>`;
    return html;
}

el.curlBtn.addEventListener("click", () => {
    renderCurlPanel();
    switchCurlTab(state.curlTab);
    el.curlPanel.classList.add("open");
});

el.curlClose.addEventListener("click", () => {
    el.curlPanel.classList.remove("open");
});

el.curlCopy.addEventListener("click", () => {
    const active = document.querySelector(".tester-curl-panel.active");
    if (!active) return;

    navigator.clipboard.writeText(active.textContent).then(() => {
        Twal.ok("Copied to clipboard");
    }).catch(() => {
        Twal.err("Copy failed");
    });
});

state.history = loadHistory();
renderHistory();