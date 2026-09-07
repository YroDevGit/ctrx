/**
 * TextEditor - A native rich text editor with table support, mentions, file attachments, and more
 * 
 * Usage:
 * const editor = new TextEditor({
 *     element: '#comment-input',
 *     users: ['Alice Johnson', 'Bob Smith', 'Carol Davis']
 * });
 */
class TextEditor {
    constructor(config = {}) {
        this.config = {
            element: null,
            selector: null,
            users: ['Alice Johnson', 'Bob Smith', 'Carol Davis', 'Dave Wilson', 'Eve Brown'],
            placeholder: 'Write something...',
            ...config
        };

        this.editor = null;
        this.dropdown = null;
        this.fileInput = null;
        this.imageInput = null;
        this.mentionStartIndex = -1;
        this.selectedMentionIndex = 0;
        this.isMentioning = false;
        this.mentionTextNode = null;
        this.highlightedRowElement = null;
        this.highlightedColIndex = -1;
        this.highlightedTableElement = null;
        this.undoStack = [];
        this.redoStack = [];
        this.MAX_STACK = 50;
        this.mentionTimeout = null;
        this.wrapper = null;
        this.toolbar = null;

        this.init();
    }

    init() {
        let container = this.config.element || 
                       (this.config.selector ? document.querySelector(this.config.selector) : null);
        
        if (typeof container === 'string') {
            container = document.querySelector(container);
        }
        
        if (!container) {
            throw new Error('TextEditor: No element or selector provided');
        }

        // Create wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'ctrxeditor-wrapper';
        container.parentNode.insertBefore(this.wrapper, container);
        this.wrapper.appendChild(container);

        // Setup editor
        this.editor = container;
        this.editor.contentEditable = 'true';
        this.editor.className = 'ctrxeditor-content';
        this.editor.setAttribute('placeholder', this.config.placeholder);

        // Create toolbar
        this.createToolbar();
        this.createDropdown();
        this.createFileInputs();
        this.bindEvents();
        this.addStyles();
        this.saveState();
        this.updateToolbar();
        this.addTableControls();
    }

    createToolbar() {
        this.toolbar = document.createElement('div');
        this.toolbar.className = 'ctrxeditor-toolbar';
        
        this.toolbar.innerHTML = `
            <button data-command="bold" title="Bold (Ctrl+B)"><b>B</b></button>
            <button data-command="italic" title="Italic (Ctrl+I)"><i>I</i></button>
            <button data-command="underline" title="Underline (Ctrl+U)"><u>U</u></button>
            
            <span class="ctrxeditor-separator"></span>
            
            <button data-command="insertParagraph">¶ Paragraph</button>
            <button data-command="insertHeading">H1 Heading</button>
            
            <span class="ctrxeditor-separator"></span>
            
            <button data-command="insertTable">📊 Table</button>
            <button data-command="insertRow">⬇ Add Row</button>
            <button data-command="insertCol">➡ Add Col</button>
            
            <span class="ctrxeditor-separator"></span>
            
            <button data-command="createLink">🔗 Link</button>
            <button data-command="unlink">🔗 Unlink</button>
            
            <span class="ctrxeditor-separator"></span>
            
            <button data-command="mention">@ Mention</button>
            <button data-command="attachFile">📎 File</button>
            <button data-command="attachImage">🖼 Image</button>
            
            <span class="ctrxeditor-separator"></span>
            
            <button data-command="clear">🗑 Clear</button>
        `;

        this.wrapper.insertBefore(this.toolbar, this.editor);

        // Bind toolbar buttons
        this.toolbar.querySelectorAll('[data-command]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const cmd = btn.dataset.command;
                this.handleCommand(cmd);
            });
        });
    }

    handleCommand(cmd) {
        switch(cmd) {
            case 'bold':
            case 'italic':
            case 'underline':
                this.exec(cmd);
                break;
            case 'insertParagraph':
                this.insertParagraph();
                break;
            case 'insertHeading':
                this.insertHeading();
                break;
            case 'insertTable':
                this.insertTable();
                break;
            case 'insertRow':
                this.insertRow();
                break;
            case 'insertCol':
                this.insertCol();
                break;
            case 'createLink':
                this.createLink();
                break;
            case 'unlink':
                this.unlink();
                break;
            case 'mention':
                this.insertMentionTrigger();
                break;
            case 'attachFile':
                this.attachFile();
                break;
            case 'attachImage':
                this.attachImage();
                break;
            case 'undo':
                this.undo();
                break;
            case 'redo':
                this.redo();
                break;
            case 'clear':
                this.clear();
                break;
        }
    }

    createDropdown() {
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'ctrxeditor-mention-dropdown';
        document.body.appendChild(this.dropdown);
    }

    createFileInputs() {
        this.fileInput = document.createElement('input');
        this.fileInput.type = 'file';
        this.fileInput.className = 'ctrxeditor-file-input-hidden';
        this.fileInput.multiple = true;
        document.body.appendChild(this.fileInput);

        this.imageInput = document.createElement('input');
        this.imageInput.type = 'file';
        this.imageInput.className = 'ctrxeditor-file-input-hidden';
        this.imageInput.accept = 'image/*';
        this.imageInput.multiple = true;
        document.body.appendChild(this.imageInput);

        this.bindFileInputs();
    }

    bindFileInputs() {
        this.fileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            for (let file of files) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.saveState();
                    const url = event.target.result;
                    const fileName = file.name;
                    const fileSize = (file.size / 1024).toFixed(1);
                    
                    const link = document.createElement('a');
                    link.className = 'ctrxeditor-file-attachment';
                    link.href = url;
                    link.download = fileName;
                    link.innerHTML = `<span class="ctrxeditor-file-icon">📎</span> ${fileName} (${fileSize} KB)`;
                    
                    this.insertNodeAtCursor(link);
                    this.editor.focus();
                };
                reader.readAsDataURL(file);
            }
            e.target.value = '';
        });

        this.imageInput.addEventListener('change', (e) => {
            const files = e.target.files;
            for (let file of files) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.saveState();
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.alt = file.name;
                    img.title = file.name;
                    img.className = 'ctrxeditor-image';
                    
                    this.insertNodeAtCursor(img);
                    this.editor.focus();
                };
                reader.readAsDataURL(file);
            }
            e.target.value = '';
        });
    }

    insertNodeAtCursor(node) {
        const sel = window.getSelection();
        if (sel.rangeCount) {
            const range = sel.getRangeAt(0);
            range.insertNode(node);
            const space = document.createTextNode(' ');
            range.setStartAfter(node);
            range.insertNode(space);
            const newRange = document.createRange();
            newRange.setStartAfter(space);
            sel.removeAllRanges();
            sel.addRange(newRange);
        }
    }

    bindEvents() {
        // Save state on input
        this.editor.addEventListener('input', () => {
            this.saveState();
        });

        // Handle mention input with debounce
        this.editor.addEventListener('input', (e) => {
            clearTimeout(this.mentionTimeout);
            this.mentionTimeout = setTimeout(() => {
                this.handleMentionInput(e);
            }, 50);
        });

        this.editor.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'z') {
                e.preventDefault();
                this.undo();
            }
            if (e.ctrlKey && e.key === 'y') {
                e.preventDefault();
                this.redo();
            }
            this.handleMentionKeydown(e);
        });

        this.editor.addEventListener('mouseup', () => this.updateToolbar());
        this.editor.addEventListener('keyup', () => this.updateToolbar());

        document.addEventListener('click', (e) => {
            if (!this.dropdown.contains(e.target) && e.target !== this.editor) {
                this.dropdown.style.display = 'none';
                this.isMentioning = false;
                this.mentionTextNode = null;
            }
        });

        this.editor.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            if (e.target.closest('td, th')) {
                const cell = e.target.closest('td, th');
                const tr = cell.closest('tr');
                this.highlightRow(tr);
                this.highlightColumn(cell);
            }
        });
    }

    addStyles() {

        let styleId="ctrxeditor_yro_styles";
        if (document.getElementById(styleId)) return;

        const style = document.createElement("link");
        style.id = styleId;
        style.setAttribute("rel", "stylesheet");
        style.setAttribute("href", "/views/code/src/style/teditor.css");
        document.head.appendChild(style);
    }

    saveState() {
        this.undoStack.push(this.editor.innerHTML);
        if (this.undoStack.length > this.MAX_STACK) this.undoStack.shift();
        this.redoStack = [];
    }

    undo() {
        if (this.undoStack.length === 0) return;
        this.redoStack.push(this.editor.innerHTML);
        this.editor.innerHTML = this.undoStack.pop();
        this.editor.focus();
        this.refreshAllTableControls();
        this.highlightedRowElement = null;
        this.highlightedColIndex = -1;
        this.highlightedTableElement = null;
    }

    redo() {
        if (this.redoStack.length === 0) return;
        this.undoStack.push(this.editor.innerHTML);
        this.editor.innerHTML = this.redoStack.pop();
        this.editor.focus();
        this.refreshAllTableControls();
        this.highlightedRowElement = null;
        this.highlightedColIndex = -1;
        this.highlightedTableElement = null;
    }

    get value() {
        const val = this.editor.innerHTML;
        if (val === '<p><br></p>' || val === '') return '';
        return val;
    }

    get getText() {
        return this.editor.textContent;
    }

    setValue(html) {
        this.saveState();
        this.editor.innerHTML = html || '';
        this.refreshAllTableControls();
    }

    get clear() {
        this.saveState();
        this.editor.innerHTML = '';
        this.highlightedRowElement = null;
        this.highlightedColIndex = -1;
        this.highlightedTableElement = null;
    }

    enable(enable = true) {
        this.editor.contentEditable = enable;
    }

    disable() {
        this.editor.contentEditable = false;
    }

    destroy() {
        this.dropdown.remove();
        this.fileInput.remove();
        this.imageInput.remove();
        this.toolbar.remove();
        this.editor.contentEditable = false;
        this.editor.className = '';
        this.wrapper.parentNode.insertBefore(this.editor, this.wrapper);
        this.wrapper.remove();
    }

    exec(cmd, value = null) {
        this.saveState();
        document.execCommand(cmd, false, value);
        this.editor.focus();
        this.updateToolbar();
        this.refreshAllTableControls();
    }

    getCurrentCell() {
        const sel = window.getSelection();
        if (!sel.rangeCount) return null;
        let node = sel.rangeAt(0).startContainer;
        if (node.nodeType === Node.TEXT_NODE) {
            node = node.parentElement;
        }
        return node.closest?.('td, th') || null;
    }

    getCurrentRow() {
        const cell = this.getCurrentCell();
        return cell ? cell.closest('tr') : null;
    }

    getCurrentTable() {
        const row = this.getCurrentRow();
        return row ? row.closest('table') : null;
    }

    getColumnIndex(cell) {
        const row = cell.closest('tr');
        return Array.from(row.children).indexOf(cell);
    }

    getColumnCount(table) {
        const firstRow = table.querySelector('tr');
        if (!firstRow) return 0;
        return firstRow.children.length;
    }

    clearHighlights(table) {
        if (!table) return;
        table.querySelectorAll('tr').forEach(tr => {
            tr.classList.remove('highlighted-row');
        });
        table.querySelectorAll('td, th').forEach(cell => {
            cell.classList.remove('highlighted-col');
        });
    }

    highlightRow(tr) {
        const table = tr.closest('table');
        this.clearHighlights(table);
        tr.classList.add('highlighted-row');
        this.highlightedRowElement = tr;
        this.highlightedTableElement = table;
    }

    highlightColumn(cell) {
        const table = cell.closest('table');
        const colIndex = this.getColumnIndex(cell);
        this.clearHighlights(table);
        table.querySelectorAll('tr').forEach(row => {
            const children = row.children;
            if (children[colIndex]) {
                children[colIndex].classList.add('highlighted-col');
            }
        });
        this.highlightedColIndex = colIndex;
        this.highlightedTableElement = table;
    }

    refreshAllTableControls() {
        const tables = this.editor.querySelectorAll('table');
        tables.forEach(table => {
            table.dataset.hasControls = 'false';
        });
        this.addTableControls();
    }

    addTableControls() {
        const tables = this.editor.querySelectorAll('table');
        
        tables.forEach(table => {
            if (table.dataset.hasControls === 'true') return;
            table.dataset.hasControls = 'true';
            
            table.querySelectorAll('tr').forEach(tr => {
                if (tr.querySelector('.row-delete-btn')) return;
                const btn = document.createElement('button');
                btn.className = 'row-delete-btn';
                btn.innerHTML = '×';
                btn.title = 'Delete this row';
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    e.preventDefault();
                    this.deleteRow(tr);
                });
                tr.style.position = 'relative';
                tr.appendChild(btn);
            });
            
            const headerRow = table.querySelector('thead tr');
            if (headerRow) {
                headerRow.querySelectorAll('th').forEach(th => {
                    if (th.querySelector('.col-delete-btn')) return;
                    const btn = document.createElement('button');
                    btn.className = 'col-delete-btn';
                    btn.innerHTML = '×';
                    btn.title = 'Delete this column';
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        e.preventDefault();
                        this.deleteColumn(th);
                    });
                    th.style.position = 'relative';
                    th.appendChild(btn);
                });
            } else {
                const firstRow = table.querySelector('tbody tr');
                if (firstRow) {
                    firstRow.querySelectorAll('td').forEach(td => {
                        if (td.querySelector('.col-delete-btn')) return;
                        const btn = document.createElement('button');
                        btn.className = 'col-delete-btn';
                        btn.innerHTML = '×';
                        btn.title = 'Delete this column';
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            e.preventDefault();
                            this.deleteColumn(td);
                        });
                        td.style.position = 'relative';
                        td.appendChild(btn);
                    });
                }
            }
        });
    }

    deleteRow(tr) {
        this.saveState();
        const tbody = tr.closest('tbody');
        if (!tbody) {
            alert('Cannot delete header row');
            return;
        }
        if (tbody.children.length <= 1) {
            alert('Cannot delete the last row. Delete the table instead.');
            return;
        }
        tr.remove();
        this.highlightedRowElement = null;
        this.editor.focus();
        this.refreshAllTableControls();
    }

    deleteColumn(cell) {
        this.saveState();
        const table = cell.closest('table');
        const colIndex = this.getColumnIndex(cell);
        const firstRow = table.querySelector('tr');
        if (firstRow && firstRow.children.length <= 1) {
            alert('Cannot delete the last column. Delete the table instead.');
            return;
        }
        table.querySelectorAll('tr').forEach(row => {
            const children = row.children;
            if (children[colIndex]) {
                children[colIndex].remove();
            }
        });
        this.highlightedColIndex = -1;
        this.editor.focus();
        this.refreshAllTableControls();
    }

    insertRow() {
        this.saveState();
        let targetRow = null;
        
        if (this.highlightedRowElement && document.body.contains(this.highlightedRowElement)) {
            targetRow = this.highlightedRowElement;
        } else {
            targetRow = this.getCurrentRow();
            if (targetRow) {
                this.highlightRow(targetRow);
            }
        }
        
        if (!targetRow) {
            alert('Please click a cell to select a row first.');
            return;
        }
        
        const tbody = targetRow.closest('tbody');
        if (!tbody) {
            alert('Cannot add row to header section.');
            return;
        }
        
        const table = targetRow.closest('table');
        const colCount = this.getColumnCount(table);
        const newRow = document.createElement('tr');
        
        for (let i = 0; i < colCount; i++) {
            const newCell = document.createElement('td');
            newCell.contentEditable = 'true';
            newCell.textContent = 'New cell';
            newRow.appendChild(newCell);
        }
        
        targetRow.parentNode.insertBefore(newRow, targetRow.nextSibling);
        this.highlightRow(newRow);
        this.editor.focus();
        this.refreshAllTableControls();
        this.saveState();
    }

    insertCol() {
        this.saveState();
        let targetCell = null;
        let targetColIdx = -1;
        let table = null;
        
        if (this.highlightedColIndex !== -1 && this.highlightedTableElement && 
            document.body.contains(this.highlightedTableElement)) {
            table = this.highlightedTableElement;
            targetColIdx = this.highlightedColIndex;
            const firstRow = table.querySelector('tr');
            if (firstRow && firstRow.children[targetColIdx]) {
                targetCell = firstRow.children[targetColIdx];
            }
        }
        
        if (!targetCell) {
            targetCell = this.getCurrentCell();
            if (targetCell) {
                table = targetCell.closest('table');
                targetColIdx = this.getColumnIndex(targetCell);
                this.highlightColumn(targetCell);
            }
        }
        
        if (!targetCell || !table) {
            alert('Please click a cell to select a column first.');
            return;
        }
        
        const insertPosition = targetColIdx + 1;
        table.querySelectorAll('tr').forEach(row => {
            const newCell = document.createElement(row.closest('thead') ? 'th' : 'td');
            newCell.contentEditable = 'true';
            newCell.textContent = 'New';
            const cells = row.children;
            if (insertPosition < cells.length) {
                row.insertBefore(newCell, cells[insertPosition]);
            } else {
                row.appendChild(newCell);
            }
        });
        
        this.highlightedColIndex = insertPosition;
        table.querySelectorAll('tr').forEach(row => {
            const children = row.children;
            if (children[this.highlightedColIndex]) {
                children[this.highlightedColIndex].classList.add('highlighted-col');
            }
        });
        
        this.editor.focus();
        this.refreshAllTableControls();
        this.saveState();
    }

    insertTable(rows = 3, cols = 3) {
        this.saveState();
        const table = document.createElement('table');
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        for (let i = 0; i < cols; i++) {
            const th = document.createElement('th');
            th.textContent = `Header ${i+1}`;
            headerRow.appendChild(th);
        }
        thead.appendChild(headerRow);
        table.appendChild(thead);
        
        const tbody = document.createElement('tbody');
        for (let r = 0; r < rows; r++) {
            const tr = document.createElement('tr');
            for (let c = 0; c < cols; c++) {
                const td = document.createElement('td');
                td.textContent = `Cell ${r+1},${c+1}`;
                td.contentEditable = 'true';
                tr.appendChild(td);
            }
            tbody.appendChild(tr);
        }
        table.appendChild(tbody);
        
        this.insertNodeAtCursor(table);
        this.editor.focus();
        this.refreshAllTableControls();
    }

    insertParagraph() {
        this.saveState();
        const sel = window.getSelection();
        if (!sel.rangeCount) return;
        
        const range = sel.getRangeAt(0);
        const parent = range.startContainer.parentElement;
        
        // If we're in a paragraph, just insert a new one
        if (parent && parent.tagName === 'P') {
            document.execCommand('insertParagraph', false, null);
            this.editor.focus();
            return;
        }
        
        // Otherwise create a new paragraph at cursor position
        const p = document.createElement('p');
        p.textContent = '';
        
        // Insert at cursor
        range.insertNode(p);
        
        // Move cursor into the new paragraph
        const newRange = document.createRange();
        newRange.setStart(p, 0);
        sel.removeAllRanges();
        sel.addRange(newRange);
        
        this.editor.focus();
        this.refreshAllTableControls();
    }

    insertHeading() {
        this.saveState();
        const sel = window.getSelection();
        if (!sel.rangeCount) return;
        
        const range = sel.getRangeAt(0);
        const parent = range.startContainer.parentElement;
        
        // Check if we're in a table cell
        if (parent && parent.closest('td, th')) {
            alert('Cannot add heading inside a table cell.');
            return;
        }
        
        // Create heading
        const h1 = document.createElement('h1');
        h1.textContent = 'Heading';
        
        // Insert at cursor
        range.insertNode(h1);
        
        // Move cursor into the heading
        const newRange = document.createRange();
        newRange.setStart(h1, 0);
        sel.removeAllRanges();
        sel.addRange(newRange);
        
        this.editor.focus();
        this.refreshAllTableControls();
    }

    insertMentionTrigger() {
        this.saveState();
        document.execCommand('insertText', false, '@');
        this.editor.focus();
    }

    attachFile() {
        this.fileInput.click();
    }

    attachImage() {
        this.imageInput.click();
    }

    createLink() {
        const url = prompt('Enter URL:', 'https://');
        if (url) this.exec('createLink', url);
    }

    unlink() {
        this.exec('unlink');
    }

    handleMentionInput(e) {
        const sel = window.getSelection();
        if (!sel.rangeCount) return;
        
        const range = sel.getRangeAt(0);
        const node = range.startContainer;
        
        if (node.nodeType !== Node.TEXT_NODE) return;
        
        const text = node.textContent || '';
        const cursorPos = range.startOffset;
        const beforeCursor = text.substring(0, cursorPos);
        const atIndex = beforeCursor.lastIndexOf('@');
        
        if (atIndex !== -1) {
            const query = beforeCursor.substring(atIndex + 1);
            
            if (query.length <= 30) {
                const rect = range.getClientRects()[0];
                
                if (rect) {
                    this.dropdown.style.display = 'block';
                    this.dropdown.style.left = rect.left + 'px';
                    this.dropdown.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                    
                    const filtered = this.config.users.filter(u => 
                        u.toLowerCase().includes(query.toLowerCase())
                    );
                    
                    this.dropdown.innerHTML = filtered.length > 0 
                        ? filtered.map((user, i) => 
                            `<div class="item ${i === this.selectedMentionIndex ? 'selected' : ''}" 
                                  data-user="${user}">${user}</div>`
                          ).join('')
                        : '<div class="item" style="color:#999;">No users found</div>';
                    
                    this.dropdown.querySelectorAll('.item[data-user]').forEach(el => {
                        el.onclick = () => {
                            this.insertMention(el.dataset.user, node, atIndex);
                        };
                    });
                    
                    this.isMentioning = true;
                    this.mentionStartIndex = atIndex;
                    this.mentionTextNode = node;
                    this.selectedMentionIndex = 0;
                }
            } else {
                this.dropdown.style.display = 'none';
                this.isMentioning = false;
            }
        } else {
            this.dropdown.style.display = 'none';
            this.isMentioning = false;
        }
    }

    handleMentionKeydown(e) {
        if (!this.isMentioning) return;
        
        const items = this.dropdown.querySelectorAll('.item[data-user]');
        if (items.length === 0) return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.selectedMentionIndex = (this.selectedMentionIndex + 1) % items.length;
            this.updateMentionSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.selectedMentionIndex = (this.selectedMentionIndex - 1 + items.length) % items.length;
            this.updateMentionSelection(items);
        } else if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            const selected = items[this.selectedMentionIndex];
            if (selected && this.mentionTextNode) {
                this.insertMention(selected.dataset.user, this.mentionTextNode, this.mentionStartIndex);
            }
        } else if (e.key === 'Escape') {
            this.dropdown.style.display = 'none';
            this.isMentioning = false;
            this.mentionTextNode = null;
        }
    }

    updateMentionSelection(items) {
        items.forEach((el, i) => {
            el.classList.toggle('selected', i === this.selectedMentionIndex);
        });
    }

    insertMention(username, textNode, startIndex) {
        this.saveState();
        
        const sel = window.getSelection();
        if (!sel.rangeCount) return;
        
        const range = sel.getRangeAt(0);
        const fullText = textNode.textContent;
        const cursorPos = range.startOffset;
        
        const before = fullText.substring(0, startIndex);
        const after = fullText.substring(cursorPos);
        
        const mentionSpan = document.createElement('span');
        mentionSpan.className = 'mention';
        mentionSpan.contentEditable = 'false';
        mentionSpan.textContent = username;
        
        const space = document.createTextNode(' ');
        
        textNode.textContent = before;
        
        const parent = textNode.parentNode;
        const fragment = document.createDocumentFragment();
        fragment.appendChild(mentionSpan);
        fragment.appendChild(space);
        
        if (textNode.nextSibling) {
            parent.insertBefore(fragment, textNode.nextSibling);
        } else {
            parent.appendChild(fragment);
        }
        
        const newRange = document.createRange();
        newRange.setStartAfter(space);
        sel.removeAllRanges();
        sel.addRange(newRange);
        
        this.dropdown.style.display = 'none';
        this.isMentioning = false;
        this.mentionTextNode = null;
        
        this.editor.focus();
        this.updateToolbar();
    }

    updateToolbar() {
        this.toolbar.querySelectorAll('[data-command]').forEach(btn => {
            const cmd = btn.dataset.command;
            if (['bold', 'italic', 'underline'].includes(cmd)) {
                try {
                    btn.classList.toggle('active', document.queryCommandState(cmd));
                } catch(e) {}
            }
        });
    }
}

export default TextEditor;