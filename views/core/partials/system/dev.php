<style>
  #ctrx-floatingLogWidget {
    position: fixed;
    z-index: 2147483647;
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    bottom: 2rem;
    right: 2rem;
    pointer-events: none;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
  }

  .ctrx-footermb{
    color:gray;
  }
  .ctrx-footerpr{
    padding-top: 5px;
  }

  #ctrx-logCircleBtn {
    width: 35px;
    height: 35px;
    background: linear-gradient(145deg, #2d3b4f, #1a2332);
    border-radius: 50%;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8), 0 0 0 2px rgba(255, 210, 80, 0.25), 0 0 20px #ffc85730;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    pointer-events: auto;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(255, 215, 100, 0.4);
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    position: relative;
  }

  #ctrx-logCircleBtn:hover {
    transform: scale(1.04);
    box-shadow: 0 0 30px #ffc85760, 0 0 0 2px #ffd966;
  }

  #ctrx-logBadgeCount {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ff6b4a;
    color: #0b0f1a;
    font-size: 0.6rem;
    font-weight: 800;
    width: 22px;
    height: 22px;
    border-radius: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #0b0f1a;
    box-shadow: 0 0 8px #ff5e3a;
    pointer-events: none;
  }

  #ctrx-logExpandedPanel {
    position: absolute;
    bottom: 52px;
    right: 0;
    width: 520px;
    max-height: 650px;
    background: rgba(14, 20, 32, 0.97);
    backdrop-filter: blur(18px);
    border-radius: 28px;
    border: 1px solid rgba(255, 200, 80, 0.3);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.9), 0 0 0 1px #ffd96640, 0 0 40px #ffb82e20;
    padding: 1rem 0.6rem 0.6rem 0.6rem;
    display: none;
    flex-direction: column;
    pointer-events: auto;
    transform-origin: bottom right;
    transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.2, 0.9, 0.3, 1.1);
    opacity: 0;
    transform: scale(0.92) translateY(8px);
    overflow: hidden;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
  }

  #ctrx-logExpandedPanel.ctrx-open {
    display: flex;
    opacity: 1;
    transform: scale(1) translateY(0);
  }

  #ctrx-logPanelHeader {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 0.5rem 0.6rem 0.5rem;
    border-bottom: 1px solid rgba(255, 215, 100, 0.12);
    flex-shrink: 0;
  }

  #ctrx-logPanelHeader .ctrx-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fae2b3;
    font-weight: 600;
    font-size: 0.9rem;
  }

  #ctrx-logPanelHeader .ctrx-title svg {
    filter: drop-shadow(0 0 4px #ffbb33);
  }

  #ctrx-consoleShortcutHint {
    font-size: 0.65rem;
    color: #7a8aa3;
    padding: 4px 12px;
    background: rgba(255, 215, 100, 0.06);
    border-radius: 20px;
    border: 1px solid rgba(255, 215, 100, 0.08);
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: default;
    white-space: nowrap;
  }

  #ctrx-consoleShortcutHint svg {
    width: 12px;
    height: 12px;
    fill: #f9b84a;
  }

  #ctrx-closeLogPanelBtn {
    background: rgba(255, 255, 255, 0.06);
    border: none;
    color: #b6c9e0;
    font-size: 1.2rem;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.15s;
    border: 1px solid transparent;
    touch-action: none;
  }

  #ctrx-closeLogPanelBtn:hover {
    background: #ff5e5e20;
    color: #ffbaba;
    border-color: #ff7a7a60;
  }

  #ctrx-closeLogPanelBtn svg {
    width: 18px;
    height: 18px;
    fill: #b6c9e0;
    pointer-events: none;
  }

  #ctrx-logListContainer {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0.4rem 0.2rem 0.2rem 0.2rem;
    max-height: 380px;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  #ctrx-logListContainer::-webkit-scrollbar {
    width: 4px;
    height: 4px;
  }
  #ctrx-logListContainer::-webkit-scrollbar-track {
    background: transparent;
  }
  #ctrx-logListContainer::-webkit-scrollbar-thumb {
    background: #ffc85780;
    border-radius: 12px;
  }
  #ctrx-logListContainer {
    scrollbar-width: thin;
    scrollbar-color: #ffc85780 transparent;
  }

  .ctrx-log-accordion {
    border-radius: 1rem;
    background: rgba(255, 215, 100, 0.03);
    border-left: 3px solid transparent;
    overflow: hidden;
    transition: background 0.15s;
    touch-action: none;
    user-select: none;
  }

  .ctrx-log-accordion:hover {
    background: rgba(255, 215, 100, 0.07);
  }

  .ctrx-log-accordion-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0.6rem 0.8rem;
    cursor: pointer;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
  }

  .ctrx-log-accordion-header .ctrx-log-icon {
    width: 22px;
    text-align: center;
    font-size: 0.9rem;
    flex-shrink: 0;
  }

  .ctrx-log-accordion-header .ctrx-log-icon svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
  }

  .ctrx-log-accordion-header .ctrx-log-summary {
    flex: 1;
    color: #d6e2f5;
    font-size: 0.85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
    min-width: 0;
  }

  .ctrx-log-accordion-header .ctrx-log-time {
    color: #7a8aa3;
    font-size: 0.65rem;
    white-space: nowrap;
    flex-shrink: 0;
    padding-left: 8px;
  }

  .ctrx-log-accordion-header .ctrx-log-expand-icon {
    color: #7a8aa3;
    font-size: 0.7rem;
    transition: transform 0.25s ease;
    flex-shrink: 0;
    width: 18px;
    text-align: center;
  }

  .ctrx-log-accordion-header .ctrx-log-expand-icon svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
    transition: transform 0.25s ease;
  }

  .ctrx-log-accordion.ctrx-expanded .ctrx-log-expand-icon svg {
    transform: rotate(180deg);
  }

  .ctrx-log-accordion-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1), padding 0.2s ease;
    padding: 0 0.8rem;
    background: rgba(0, 0, 0, 0.2);
  }

  .ctrx-log-accordion.ctrx-expanded .ctrx-log-accordion-body {
    max-height: 400px;
    padding: 0.6rem 0.8rem 0.8rem 0.8rem;
    overflow-y: auto;
    overflow-x: auto;
  }

  .ctrx-log-accordion-body .ctrx-log-detail {
    color: #b0c4db;
    font-size: 0.8rem;
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    line-height: 1.5;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: 0.5rem;
    margin-top: 0.2rem;
    white-space: pre-wrap;
    word-break: break-word;
  }

  .ctrx-log-accordion-body .ctrx-log-stack {
    color: #7a8aa3;
    font-size: 0.7rem;
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.4;
    margin-top: 0.3rem;
    padding: 0.3rem 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 0.4rem;
    max-height: 120px;
    overflow-y: auto;
    overflow-x: auto;
  }

  .ctrx-log-accordion.ctrx-log-info { border-left-color: #4a9eff; }
  .ctrx-log-accordion.ctrx-log-info .ctrx-log-icon { color: #4a9eff; }
  .ctrx-log-accordion.ctrx-log-success { border-left-color: #4caf84; }
  .ctrx-log-accordion.ctrx-log-success .ctrx-log-icon { color: #4caf84; }
  .ctrx-log-accordion.ctrx-log-warning { border-left-color: #f9b84a; }
  .ctrx-log-accordion.ctrx-log-warning .ctrx-log-icon { color: #f9b84a; }
  .ctrx-log-accordion.ctrx-log-error { border-left-color: #f25a5a; }
  .ctrx-log-accordion.ctrx-log-error .ctrx-log-icon { color: #f25a5a; }
  .ctrx-log-accordion.ctrx-log-debug { border-left-color: #a97bff; }
  .ctrx-log-accordion.ctrx-log-debug .ctrx-log-icon { color: #a97bff; }

  .ctrx-empty-logs {
    color: #6f7f98;
    text-align: center;
    padding: 2rem 0.5rem;
    font-size: 0.9rem;
    opacity: 0.7;
  }

  .ctrx-copy-btn {
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: #7a8aa3;
    font-size: 0.65rem;
    padding: 2px 10px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    margin-left: 8px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    touch-action: none;
    user-select: none;
  }

  .ctrx-copy-btn:hover {
    background: rgba(255, 215, 100, 0.15);
    color: #fae2b3;
  }

  .ctrx-copy-btn.copied {
    background: rgba(76, 175, 80, 0.2);
    color: #81c784;
  }

  .ctrx-copy-btn svg {
    width: 12px;
    height: 12px;
    fill: currentColor;
  }

  .ctrx-json-tree {
    padding-left: 0;
    margin: 0;
    list-style: none;
    white-space: pre-wrap;
    word-break: break-word;
  }

  .ctrx-json-tree-item {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .ctrx-json-tree-item .ctrx-tree-content {
    display: flex;
    align-items: baseline;
    padding: 2px 0;
    padding-left: 0;
    flex-wrap: wrap;
  }

  .ctrx-json-tree-item .ctrx-tree-toggle {
    cursor: pointer;
    display: inline-block;
    width: 20px;
    min-width: 20px;
    text-align: center;
    color: #7a8aa3;
    font-size: 0.7rem;
    user-select: none;
    transition: transform 0.2s ease;
    flex-shrink: 0;
    margin-right: 4px;
  }

  .ctrx-json-tree-item .ctrx-tree-toggle.ctrx-collapsed {
    display: inline-block;
  }

  .ctrx-json-tree-item .ctrx-tree-toggle.ctrx-expanded {
    display: inline-block;
    transform: rotate(90deg);
  }

  .ctrx-json-tree-item .ctrx-tree-toggle.ctrx-empty {
    display: none;
    width: 20px;
    min-width: 20px;
  }

  .ctrx-json-tree-item .ctrx-tree-key {
    color: #f9b84a;
    margin-right: 6px;
    flex-shrink: 0;
  }

  .ctrx-json-tree-item .ctrx-tree-value {
    color: #d6e2f5;
    word-break: break-word;
  }

  .ctrx-json-tree-item .ctrx-tree-value.ctrx-string {
    color: #a8d6a8;
  }
  .ctrx-string{
    font-size: 13px;
  }
  .ctrx-json-tree-item .ctrx-tree-value.ctrx-number {
    color: #7ec8e3;
  }
  .ctrx-json-tree-item .ctrx-tree-value.ctrx-boolean {
    color: #d4a0ff;
  }
  .ctrx-json-tree-item .ctrx-tree-value.ctrx-null {
    color: #ff7a7a;
  }
  .ctrx-json-tree-item .ctrx-tree-value.ctrx-undefined {
    color: #ff7a7a;
  }
  .ctrx-json-tree-item .ctrx-tree-bracket {
    color: #d6e2f5;
    margin-right: 2px;
  }

  .ctrx-json-tree-item .ctrx-tree-type-label {
    color: #f9b84a;
    font-style: italic;
    font-size: 0.90rem;
    margin-left: 6px;
  }

  .ctrx-json-tree-item .ctrx-tree-children {
    padding-left: 12px;
    overflow: hidden;
    transition: max-height 0.25s ease;
    max-height: 9999px;
    list-style: none;
    margin: 0;
    border-left: 1px solid rgba(255, 255, 255, 0.08);
  }

  .ctrx-json-tree-item .ctrx-tree-children.ctrx-collapsed {
    max-height: 0;
    border-left: none;
  }

  .ctrx-json-tree-item .ctrx-tree-preview {
    color: #7a8aa3;
    font-style: italic;
    margin-left: 6px;
    font-size: 0.75rem;
  }

  .ctrx-json-tree > .ctrx-json-tree-item > .ctrx-tree-content {
    padding-left: 6px;
  }

  .ctrx-log-accordion-body .ctrx-body-header {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 4px;
  }

  @media (max-width: 520px) {
    #ctrx-logExpandedPanel {
      width: 86vw;
      right: -8px;
      bottom: 62px;
      max-height: 420px;
    }
    #ctrx-floatingLogWidget {
      bottom: 1.2rem;
      right: 1.2rem;
    }
    #ctrx-logCircleBtn {
      width: 30px;
      height: 30px;
    }
    #ctrx-consoleShortcutHint {
      font-size: 0.55rem;
      padding: 3px 8px;
    }
    .ctrx-json-tree-item .ctrx-tree-children {
      padding-left: 20px;
    }
    .ctrx-json-tree-item .ctrx-tree-toggle {
      width: 16px;
      min-width: 16px;
    }
  }
</style>

<div id="ctrx-floatingLogWidget">
  <div id="ctrx-logCircleBtn" role="button" aria-label="Open logs">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 0 6px #ffbb33); pointer-events: none;">
      <path d="M13 2L4 14H12L11 22L20 10H12L13 2Z" fill="#FFE484" stroke="#FFD966" stroke-width="1.2" stroke-linejoin="round"/>
    </svg>
    <span id="ctrx-logBadgeCount">0</span>
  </div>

  <div id="ctrx-logExpandedPanel">
    <div id="ctrx-logPanelHeader">
      <div class="ctrx-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M13 2L4 14H12L11 22L20 10H12L13 2Z" fill="#FFE484" stroke="#FFD966" stroke-width="1.2" stroke-linejoin="round"/>
        </svg>
        <span>CTRX JS logs</span>
      </div>
      <div id="ctrx-consoleShortcutHint">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 5H4C2.9 5 2 5.9 2 7V17C2 18.1 2.9 19 4 19H20C21.1 19 22 18.1 22 17V7C22 5.9 21.1 5 20 5ZM20 17H4V7H20V17ZM9 15H7V11H9V15ZM13 13H17V15H13V13ZM17 9H13V11H17V9Z"/>
        </svg>
        <span id="ctrx-shortcutText">F12</span>
      </div>
      <button id="ctrx-closeLogPanelBtn" aria-label="Close logs">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41Z"/>
        </svg>
      </button>
    </div>
    <div id="ctrx-logListContainer">
      <div class="ctrx-empty-logs">⏳ waiting for logs…</div>
    </div>
    <footer>
      <div align='center' class="ctrx-footerpr">
        <small class="ctrx-footermb">CTRX by CodeYRO</small>
      </div>
    </footer>
  </div>
</div>

<script>
  (function() {
    var circleBtn = document.getElementById('ctrx-logCircleBtn');
    var panel = document.getElementById('ctrx-logExpandedPanel');
    var closeBtn = document.getElementById('ctrx-closeLogPanelBtn');
    var listContainer = document.getElementById('ctrx-logListContainer');
    var badge = document.getElementById('ctrx-logBadgeCount');
    var shortcutText = document.getElementById('ctrx-shortcutText');

    var isOpen = false;
    var logEntries = [];
    var uniqueId = 0;
    var currentlyExpanded = null;

    var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    shortcutText.textContent = isMac ? '⌘+⌥+I' : 'F12';

    function getIconForType(type) {
      var map = {
        'log': 'info',
        'info': 'info',
        'success': 'success',
        'warn': 'warning',
        'warning': 'warning',
        'error': 'error',
        'debug': 'debug'
      };
      return map[type] || 'info';
    }

    function getIconSVG(type) {
      var icons = {
        'info': '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
        'success': '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
        'warning': '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
        'error': '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
        'debug': '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 8h-2.81c-.45-.8-1.07-1.5-1.82-2.05L17 4.41 15.59 3l-2.17 2.17C12.96 5.06 12.49 5 12 5s-.96.06-1.41.17L8.41 3 7 4.41l1.62 1.62C7.88 6.5 7.26 7.2 6.81 8H4v2h2.09c-.05.33-.09.66-.09 1v1H4v2h2v1c0 .34.04.67.09 1H4v2h2.81c1.04 1.79 2.97 3 5.19 3s4.15-1.21 5.19-3H20v-2h-2.09c.05-.33.09-.67.09-1v-1h2v-2h-2v-1c0-.34-.04-.67-.09-1H20V8z"/></svg>'
      };
      return icons[getIconForType(type)] || icons.info;
    }

    function getTypeClass(type) {
      var map = {
        'log': 'ctrx-log-info',
        'info': 'ctrx-log-info',
        'success': 'ctrx-log-success',
        'warn': 'ctrx-log-warning',
        'warning': 'ctrx-log-warning',
        'error': 'ctrx-log-error',
        'debug': 'ctrx-log-debug'
      };
      return map[type] || 'ctrx-log-info';
    }

    function getBorderColor(type) {
      var map = {
        'log': '#4a9eff',
        'info': '#4a9eff',
        'success': '#4caf84',
        'warn': '#f9b84a',
        'warning': '#f9b84a',
        'error': '#f25a5a',
        'debug': '#a97bff'
      };
      return map[type] || '#4a9eff';
    }

    function getIconColor(type) {
      return getBorderColor(type);
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function formatArgForPreview(arg) {
      if (typeof arg === 'object' && arg !== null) {
        try {
          var str = JSON.stringify(arg);
          if (str.length > 80) str = str.slice(0, 80) + '…';
          return str;
        } catch {
          return '[Object]';
        }
      }
      return String(arg);
    }

    function getValueClass(val) {
      var t = typeof val;
      if (val === null) return 'ctrx-null';
      if (val === undefined) return 'ctrx-undefined';
      if (t === 'string') return 'ctrx-string';
      if (t === 'number') return 'ctrx-number';
      if (t === 'boolean') return 'ctrx-boolean';
      return '';
    }

    function getType(val) {
      if (val === null) return 'null';
      if (val === undefined) return 'undefined';
      var t = typeof val;
      if (t === 'object') {
        if (Array.isArray(val)) return 'array';
        return 'object';
      }
      return t;
    }

    function buildTree(obj) {
      var type = getType(obj);
      var isComplex = type === 'object' || type === 'array';
      var hasChildren = isComplex && Object.keys(obj).length > 0;
      var id = 'ctrx-tree-' + (++uniqueId);

      var html = '<li class="ctrx-json-tree-item">';
      html += '<div class="ctrx-tree-content">';

      if (isComplex) {
        var typeLabel = type === 'array' ? 'Array' : 'Object';
        var count = Object.keys(obj).length;

        if (hasChildren) {
          html += '<span class="ctrx-tree-toggle ctrx-collapsed" data-target="' + id + '">▶</span>';
          html += '<span class="ctrx-tree-bracket">' + (type === 'array' ? '[' : '{') + '</span>';
          html += '<span class="ctrx-tree-type-label">' + typeLabel + ' (' + count + ')</span>';
          html += '<span class="ctrx-tree-bracket">' + (type === 'array' ? ']' : '}') + '</span>';
        } else {
          html += '<span class="ctrx-tree-toggle ctrx-empty">▶</span>';
          html += '<span class="ctrx-tree-bracket">' + (type === 'array' ? '[]' : '{}') + '</span>';
          html += ' <span class="ctrx-tree-preview">empty</span>';
        }

        html += '</div>';

        if (hasChildren) {
          html += '<ul class="ctrx-tree-children ctrx-collapsed" id="' + id + '">';
          var keys = Object.keys(obj);
          for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            html += buildTreeItem(obj[k], k);
          }
          html += '</ul>';
        }
      } else {
        html += '<span class="ctrx-tree-toggle ctrx-empty">▶</span>';
        html += '<span class="ctrx-tree-value ' + getValueClass(obj) + '">' + escapeHtml(String(obj)) + '</span>';
        html += '</div>';
      }

      html += '</li>';
      return html;
    }

    function buildTreeItem(obj, key) {
      var type = getType(obj);
      var isComplex = type === 'object' || type === 'array';
      var hasChildren = isComplex && Object.keys(obj).length > 0;
      var id = 'ctrx-tree-' + (++uniqueId);

      var html = '<li class="ctrx-json-tree-item">';
      html += '<div class="ctrx-tree-content">';

      html += '<span class="ctrx-tree-key">' + escapeHtml(String(key)) + '</span>';
      html += '<span class="ctrx-tree-bracket">: </span>';

      if (isComplex) {
        var typeLabel = type === 'array' ? 'Array' : 'Object';
        var count = Object.keys(obj).length;

        if (hasChildren) {
          html += '<span class="ctrx-tree-toggle ctrx-collapsed" data-target="' + id + '">▶</span>';
          html += '<span class="ctrx-tree-bracket">' + (type === 'array' ? '[' : '{') + '</span>';
          html += '<span class="ctrx-tree-type-label">' + typeLabel + ' (' + count + ')</span>';
          html += '<span class="ctrx-tree-bracket">' + (type === 'array' ? ']' : '}') + '</span>';
        } else {
          html += '<span class="ctrx-tree-toggle ctrx-empty">▶</span>';
          html += '<span class="ctrx-tree-bracket">' + (type === 'array' ? '[]' : '{}') + '</span>';
          html += ' <span class="ctrx-tree-preview">empty</span>';
        }

        html += '</div>';

        if (hasChildren) {
          html += '<ul class="ctrx-tree-children ctrx-collapsed" id="' + id + '">';
          var keys = Object.keys(obj);
          for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            html += buildTreeItem(obj[k], k);
          }
          html += '</ul>';
        }
      } else {
        html += '<span class="ctrx-tree-toggle ctrx-empty">▶</span>';
        html += '<span class="ctrx-tree-value ' + getValueClass(obj) + '">' + escapeHtml(String(obj)) + '</span>';
        html += '</div>';
      }

      html += '</li>';
      return html;
    }

    function formatArgForDetail(arg) {
      if (typeof arg === 'object' && arg !== null) {
        return '<ul class="ctrx-json-tree">' + buildTree(arg) + '</ul>';
      }
      return '<span class="ctrx-tree-value ' + getValueClass(arg) + '">' + escapeHtml(String(arg)) + '</span>';
    }

    function getRawDetail(arg) {
      if (typeof arg === 'object' && arg !== null) {
        try {
          return JSON.stringify(arg, null, 2);
        } catch {
          return String(arg);
        }
      }
      return String(arg);
    }

    function copyToClipboard(text, btn) {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
          showCopiedFeedback(btn);
        }).catch(function() {
          fallbackCopy(text, btn);
        });
      } else {
        fallbackCopy(text, btn);
      }
    }

    function fallbackCopy(text, btn) {
      var textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.opacity = '0';
      textarea.style.left = '-9999px';
      textarea.style.top = '-9999px';
      document.body.appendChild(textarea);
      textarea.select();
      try {
        document.execCommand('copy');
        showCopiedFeedback(btn);
      } catch (e) {
        console.warn('Copy failed', e);
      }
      document.body.removeChild(textarea);
    }

    function showCopiedFeedback(btn) {
      var originalHtml = btn.innerHTML;
      btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg> Copied!';
      btn.classList.add('copied');
      setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.classList.remove('copied');
      }, 2000);
    }

    function closeAllOtherAccordions(exceptThis) {
      var allAccordions = listContainer.querySelectorAll('.ctrx-log-accordion');
      for (var i = 0; i < allAccordions.length; i++) {
        var acc = allAccordions[i];
        if (acc !== exceptThis && acc.classList.contains('ctrx-expanded')) {
          acc.classList.remove('ctrx-expanded');
        }
      }
    }

    function renderLogs() {
      if (!listContainer) return;
      if (logEntries.length === 0) {
        listContainer.innerHTML = '<div class="ctrx-empty-logs">⏳ waiting for logs…</div>';
        if (badge) { badge.textContent = '0'; badge.style.display = 'none'; }
        return;
      }

      var html = '';
      var entries = logEntries.slice().reverse();
      for (var i = 0; i < entries.length; i++) {
        var log = entries[i];
        var typeClass = getTypeClass(log.type);
        var msg = log.msg || '—';
        var time = log.time || '';
        var detail = log.detail || '';
        var stack = log.stack || '';
        var rawDetail = log.rawDetail || '';

        var safeMsg = escapeHtml(msg);
        var safeStack = escapeHtml(stack);

        var hasDetail = detail.length > 0 || safeStack.length > 0;

        var copyBtnHtml = '';
        if (hasDetail && rawDetail) {
          var escapedCopyText = escapeHtml(rawDetail);
          copyBtnHtml = '<button class="ctrx-copy-btn" data-copy-text="' + escapedCopyText + '">' +
            '<svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg> Copy</button>';
        }

        html += `
          <div class="ctrx-log-accordion ${typeClass}" data-index="${i}">
            <div class="ctrx-log-accordion-header">
              <div class="ctrx-log-icon" style="color: ${getIconColor(log.type)};">
                ${getIconSVG(log.type)}
              </div>
              <div class="ctrx-log-summary">${safeMsg}</div>
              ${time ? `<div class="ctrx-log-time">${time}</div>` : ''}
              <div class="ctrx-log-expand-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M7 10l5 5 5-5z"/>
                </svg>
              </div>
            </div>
            ${hasDetail ? `
              <div class="ctrx-log-accordion-body">
                <div class="ctrx-body-header">
                  ${copyBtnHtml}
                </div>
                ${detail ? `<div class="ctrx-log-detail">${detail}</div>` : ''}
                ${safeStack ? `<div class="ctrx-log-stack">${safeStack}</div>` : ''}
              </div>
            ` : ''}
          </div>
        `;
      }
      listContainer.innerHTML = html;
      if (badge) {
        badge.textContent = logEntries.length;
        badge.style.display = 'flex';
      }

      var accordions = listContainer.querySelectorAll('.ctrx-log-accordion');
      for (var j = 0; j < accordions.length; j++) {
        var acc = accordions[j];
        var header = acc.querySelector('.ctrx-log-accordion-header');
        if (header) {
          header.addEventListener('click', function(e) {
            e.stopPropagation();
            var accordion = this.closest('.ctrx-log-accordion');
            if (accordion) {
              var isExpanded = accordion.classList.contains('ctrx-expanded');
              closeAllOtherAccordions(accordion);
              if (isExpanded) {
                accordion.classList.remove('ctrx-expanded');
              } else {
                accordion.classList.add('ctrx-expanded');
              }
              bindTreeToggles(accordion);
            }
          });
        }
        var body = acc.querySelector('.ctrx-log-accordion-body');
        if (!body || body.innerHTML.trim() === '') {
          var expandIcon = acc.querySelector('.ctrx-log-expand-icon');
          if (expandIcon) expandIcon.style.opacity = '0.3';
          if (header) header.style.cursor = 'default';
        }
        bindTreeToggles(acc);

        var copyBtns = acc.querySelectorAll('.ctrx-copy-btn');
        for (var k = 0; k < copyBtns.length; k++) {
          var btn = copyBtns[k];
          btn.removeEventListener('click', copyHandler);
          btn.addEventListener('click', copyHandler);
        }
      }

      listContainer.scrollTop = 0;
    }

    function copyHandler(e) {
      e.stopPropagation();
      var btn = this;
      var text = btn.getAttribute('data-copy-text');
      if (text) {
        copyToClipboard(text, btn);
      }
    }

    function bindTreeToggles(container) {
      var toggles = container.querySelectorAll('.ctrx-tree-toggle:not(.ctrx-empty)');
      for (var i = 0; i < toggles.length; i++) {
        var toggle = toggles[i];
        toggle.removeEventListener('click', treeToggleHandler);
        toggle.addEventListener('click', treeToggleHandler);
      }
    }

    function treeToggleHandler(e) {
      e.stopPropagation();
      var toggle = this;
      var targetId = toggle.getAttribute('data-target');
      var children = document.getElementById(targetId);
      if (children) {
        if (children.classList.contains('ctrx-collapsed')) {
          children.classList.remove('ctrx-collapsed');
          toggle.classList.remove('ctrx-collapsed');
          toggle.classList.add('ctrx-expanded');
        } else {
          children.classList.add('ctrx-collapsed');
          toggle.classList.remove('ctrx-expanded');
          toggle.classList.add('ctrx-collapsed');
        }
      }
    }

    function addLog(type, args) {
      var previewParts = [];
      var detailParts = [];
      var rawDetailParts = [];
      for (var i = 0; i < args.length; i++) {
        var arg = args[i];
        previewParts.push(formatArgForPreview(arg));
        detailParts.push(formatArgForDetail(arg));
        rawDetailParts.push(getRawDetail(arg));
      }
      var msg = previewParts.join(' ');
      var detail = detailParts.join(' ');
      var rawDetail = rawDetailParts.join(' ');
      if (msg.length > 200) msg = msg.slice(0, 200) + '…';

      var stack = '';
      if (type === 'error' && args.length > 0 && args[0] instanceof Error) {
        stack = args[0].stack || '';
      } else {
        var err = new Error();
        stack = err.stack || '';
        var lines = stack.split('\n');
        var result = [];
        for (var j = 3; j < Math.min(lines.length, 15); j++) {
          result.push(lines[j].trim());
        }
        stack = result.join('\n');
      }

      var now = new Date();
      var timeStr = now.toTimeString().slice(0, 8);

      logEntries.push({
        type: type,
        msg: msg,
        time: timeStr,
        detail: detail,
        stack: stack,
        rawDetail: rawDetail
      });

      if (logEntries.length > 100) logEntries.shift();

      if (isOpen) {
        renderLogs();
      } else {
        if (badge) {
          badge.textContent = logEntries.length;
          badge.style.display = 'flex';
        }
      }
    }

    function openPanel() {
      if (isOpen) return;
      panel.classList.add('ctrx-open');
      isOpen = true;
      renderLogs();
    }

    function closePanel() {
      if (!isOpen) return;
      panel.classList.remove('ctrx-open');
      isOpen = false;
    }

    function togglePanel() {
      if (isOpen) closePanel();
      else openPanel();
    }

    var originalLog = console.log;
    var originalError = console.error;
    var originalWarn = console.warn;
    var originalInfo = console.info;
    var originalDebug = console.debug;

    console.log = function() {
      addLog('log', arguments);
      originalLog.apply(console, arguments);
    };

    console.error = function() {
      addLog('error', arguments);
      originalError.apply(console, arguments);
    };

    console.warn = function() {
      addLog('warning', arguments);
      originalWarn.apply(console, arguments);
    };

    console.info = function() {
      addLog('info', arguments);
      originalInfo.apply(console, arguments);
    };

    console.debug = function() {
      addLog('debug', arguments);
      originalDebug.apply(console, arguments);
    };

    window.addEventListener('error', function(e) {
      addLog('error', [e.message + ' (at ' + e.filename + ':' + e.lineno + ')']);
      return false;
    });

    window.addEventListener('unhandledrejection', function(e) {
      addLog('error', ['Unhandled Promise Rejection: ' + (e.reason || '')]);
    });

    circleBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      togglePanel();
    });

    closeBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      closePanel();
    });

    document.addEventListener('click', function(e) {
      var widget = document.getElementById('ctrx-floatingLogWidget');
      if (!widget) return;
      if (panel.classList.contains('ctrx-open')) {
        if (!widget.contains(e.target)) {
          closePanel();
        }
      }
    });

    var widget = document.getElementById('ctrx-floatingLogWidget');
    if (widget) {
      widget.addEventListener('dragstart', function(e) { e.preventDefault(); });
      widget.addEventListener('selectstart', function(e) { e.preventDefault(); });
    }

    console.log('Console interception active — click log items to expand');

    window.__ctrxLogEntries = logEntries;
  })();
</script>