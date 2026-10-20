<?php
if(env('environment') == "prod" || env('environment') == "production"){
    die("This page is not available.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CAPI - CTRX API Tester</title>
    <link rel="stylesheet" href="/views/code/src/capi/ctrx-capi-style.css" />
</head>

<body>

    <header class="tester-header">
        <div class="tester-header-inner">
            <span class="tester-logo">c<span>API</span></span>
            <div class="tester-actions">
                <button id="curlBtn" class="tester-btn-icon" title="Generate cURL">cURL</button>
                <button id="historyToggle" class="tester-btn-icon" title="History">History</button>
                <button id="clearBtn" class="tester-btn-icon" title="Clear">Clear</button>
            </div>
        </div>
    </header>

    <main class="tester-main">
        <section class="tester-request">
            <div class="tester-row">
                <select id="method" class="tester-method">
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="DELETE">DELETE</option>
                </select>
                <input id="url" class="tester-url" type="text" placeholder="" spellcheck="false" />
                <button id="sendBtn" class="tester-send">Send</button>
            </div>

            <div class="tester-tabs">
                <button class="tester-tab active" data-tab="body">Body raw</button>
                <button id="bnraw" class="tester-tab active" data-tab="bodyform">Body</button>
                <button class="tester-tab" data-tab="params">Params</button>
                <button class="tester-tab" data-tab="headers">Headers</button>
                <button class="tester-tab" data-tab="options">Options</button>
            </div>

            <div class="tester-panel active" data-panel="body">
                <div class="tester-body-mode">
                    <label><input type="radio" name="bodyMode" value="json" checked /> JSON</label>
                    <label style="display: none;"><input type="radio" name="bodyMode" value="form" /> Form</label>
                </div>
                <textarea id="bodyInput" class="tester-textarea" spellcheck="false" placeholder='{ "title": "The Pragmatic Programmer" }'></textarea>
                <div style="display: flex;gap:10px;">
                    <button id="formatJson" class="tester-btn-small">Format JSON</button>
                    <button id="jsongen" class="tester-btn-small">Generate Curl</button>
                </div>
            </div>

            <div class="tester-panel" data-panel="bodyform">
                <div id="bodyList" class="tester-kv-list"></div>
                <button id="addField" class="tester-btn-small">+ Add Field</button>
            </div>

            <div class="tester-panel" data-panel="params">
                <div id="paramsList" class="tester-kv-list"></div>
                <button id="addParam" class="tester-btn-small">+ Add Param</button>
            </div>

            <div class="tester-panel" data-panel="headers">
                <div id="headersList" class="tester-kv-list"></div>
                <button id="addHeader" class="tester-btn-small">+ Add Header</button>
            </div>

            <div class="tester-panel" data-panel="options">
                <label class="tester-checkbox">
                    <input id="optLoading" type="checkbox" checked />
                    Show loading spinner
                </label>
            </div>
        </section>

        <section class="tester-response">
            <div class="tester-response-header">
                <div class="tester-status">
                    <span id="statusCode" class="tester-status-code">—</span>
                    <span id="statusTime" class="tester-status-time"></span>
                    <span id="statusSize" class="tester-status-size"></span>
                </div>
                <div class="tester-response-tabs">
                    <button class="tester-rtab active" data-rtab="pretty">Pretty</button>
                    <button class="tester-rtab" data-rtab="raw">Raw</button>
                </div>
            </div>
            <div id="responsePretty" class="tester-response-panel active"></div>
            <pre id="responseRaw" class="tester-response-panel"></pre>
        </section>

    </main>

    <aside id="historyPanel" class="tester-history">
        <div class="tester-history-header">
            <span>History</span>
            <button id="historyClose" class="tester-btn-icon">Close</button>
        </div>
        <div id="historyList" class="tester-history-list"></div>
    </aside>

    <aside id="curlPanel" class="tester-history">
        <div class="tester-history-header">
            <span>Generate cURL</span>
            <button id="curlClose" class="tester-btn-icon">Close</button>
        </div>
        <div class="tester-curl-body">
            <div class="tester-curl-tabs">
                <button class="tester-ctab active" data-ctab="raw">Raw cURL</button>
                <button class="tester-ctab" data-ctab="tyrax">TYRAX</button>
                <button class="tester-ctab" data-ctab="form">Form</button>
                <button class="tester-ctab" data-ctab="tabletab">Table</button>
            </div>
            <div id="curlRaw" class="tester-curl-panel active"></div>
            <div id="curlTyrax" class="tester-curl-panel"></div>
            <div id="curlForm" class="tester-curl-panel"></div>
            <div id="curlTable" class="tester-curl-panel"></div>
            <button id="curlCopy" class="tester-btn-small">Copy to clipboard</button>
        </div>
    </aside>

    <script type="module" src="/views/code/src/capi/ctrx-capi-script.js"></script>
</body>
</html>