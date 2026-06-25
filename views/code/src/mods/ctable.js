class CTable {

    static opt = {
        columns: undefined,
        pagination: undefined,
        id: undefined,
        attibutes: undefined,
        search: undefined,
        title: undefined,
    }

    static init(selector, options = {...this.opt}) {
        return new CTable(selector, options);
    }

    static stylesInjected = false;

    static injectStyles() {

        if (this.stylesInjected) return;

        const style = document.createElement("style");

        style.innerHTML = `
    
        .ctrtable-container{
            width:100%;
            background:#fff;
            border-radius:12px;
            box-shadow:0 2px 10px rgba(0,0,0,.08);
            padding:16px;
            box-sizing:border-box;
            overflow:hidden;
        }

        .ctrtable-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
    
        .ctrtable-toolbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ctrtable-toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
    
        .ctrtable-search{
            width:100%;
            max-width:300px;
            padding:10px;
            border:1px solid #ddd;
            border-radius:8px;
            outline:none;
        }
    
        .ctrtable-wrapper{
            overflow-x:auto;
        }
    
        .ctrtable{
            width:100%;
            border-collapse:collapse;
        }
    
        .ctrtable th{
            background:#f8fafc;
            padding:12px;
            text-align:left;
            border-bottom:2px solid #e5e7eb;
        }
    
        .ctrtable td{
            padding:12px;
            border-bottom:1px solid #eee;
        }
    
        .ctrtable tr:hover{
            background:#fafafa;
        }
    
        .ctrtable-button{
            border:none;
            background:#2563eb;
            color:white;
            padding:8px 12px;
            border-radius:6px;
            cursor:pointer;
        }
    
        .ctrtable-span{
            cursor:pointer;
            color:#2563eb;
        }
    
        .ctrtable-action{
            border:none;
            background:#f3f4f6;
            padding:6px 10px;
            border-radius:6px;
            cursor:pointer;
            font-size: 18px;
            line-height: 1;
        }
    
        .ctrtable-pagination{
            display:flex;
            gap:5px;
            justify-content:flex-end;
            margin-top:15px;
            flex-wrap: wrap;
        }
    
        .ctrtable-page{
            border:1px solid #ddd;
            background:white;
            padding:6px 12px;
            cursor:pointer;
            border-radius:6px;
        }
    
        .ctrtable-page.active{
            background:#2563eb;
            color:white;
        }
    
        .ctrtable-modal{
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:rgba(0,0,0,.4);
            display:none;
            justify-content:center;
            align-items:center;
            z-index:9999;
        }
    
        .ctrtable-modal-content{
            background:white;
            width:400px;
            max-width:95%;
            border-radius:12px;
            padding:20px;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
        }
    
        .ctrtable-action-item{
            width:100%;
            text-align:center;
            border:none;
            background:#f8fafc;
            padding:12px 16px;
            margin-bottom:5px;
            border-radius:6px;
            cursor:pointer;
            font-weight: bold;
            transition: background 0.2s;
        }
    
        .ctrtable-action-item:hover {
            background: #e5e7eb;
        }

        .ctrtable-no-data {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            font-size: 16px;
            background: #f9fafb;
            border-radius: 8px;
        }
    
        @media(max-width:768px){
    
            .ctrtable thead{
                display:none;
            }
    
            .ctrtable,
            .ctrtable tbody,
            .ctrtable tr,
            .ctrtable td{
                display:block;
                width:100%;
            }
    
            .ctrtable tr{
                margin-bottom:15px;
                border:1px solid #eee;
                border-radius:10px;
            }
    
            .ctrtable td{
                text-align:right;
                padding-left:50%;
                position:relative;
            }
    
            .ctrtable td::before{
                content:attr(data-label);
                position:absolute;
                left:10px;
                font-weight:bold;
            }
        }
        `;

        document.head.appendChild(style);

        this.stylesInjected = true;
    }

    constructor(selector, options = {}) {

        CTable.injectStyles();

        this.parent = document.querySelector(selector);

        this.options = options;

        this.columns = options.columns || [];

        this.pageSize = options.pagination || 25;

        this.title = options.title || '';

        // Check if search is enabled
        this.searchColumns = [];
        this.enableSearch = false;
        
        if (options.search === true) {
            this.enableSearch = true;
            this.searchColumns = [];
        } else if (Array.isArray(options.search) && options.search.length > 0) {
            this.enableSearch = true;
            this.searchColumns = options.search;
        }

        this.rows = [];

        this.filteredRows = [];

        this.currentPage = 1;

        // Track if search has been initiated
        this.searchInitiated = false;

        this.render();

        // Render initial empty state
        this.renderRows();
    }

    render() {

        // Build toolbar HTML
        let toolbarHTML = '';
        
        if (this.title || this.enableSearch) {
            toolbarHTML = `<div class="ctrtable-toolbar">`;
            
            if (this.title || this.enableSearch) {
                toolbarHTML += `<div class="ctrtable-toolbar-left">`;
                
                if (this.title) {
                    if(typeof this.title == "string"|| typeof this.title == "number"){
                        toolbarHTML += `<div class="ctrtable-title">${this.title}</div>`;
                    }else{
                        
                        let tClass = this.title?.class ?? undefined;
                        let tText = this.title?.text ?? this.title?.label ?? undefined;
                        if(tText){
                            if(tClass){
                                toolbarHTML += `<div class="${tClass}">${tText}</div>`;
                            }else{
                                toolbarHTML += `<div class="">${tText}</div>`;
                            }
                        }
                    }
                }
                
                if (this.enableSearch) {
                    toolbarHTML += `<input type="text" class="ctrtable-search" placeholder="Search...">`;
                }
                
                toolbarHTML += `</div>`;
            }
            
            toolbarHTML += `</div>`;
        }

        this.parent.innerHTML = `
            <div class="ctrtable-container">
    
                ${toolbarHTML}
    
                <div class="ctrtable-wrapper">
    
                    <table
                        class="ctrtable ${this.options.class || ''}"
                        id="${this.options.id || ''}"
                    >
    
                        <thead>
                            <tr>
                                ${this.columns.map(col =>
            `<th>${col}</th>`
        ).join("")}
                            </tr>
                        </thead>
    
                        <tbody></tbody>
    
                    </table>
    
                </div>
    
                <div class="ctrtable-pagination"></div>
    
            </div>
    
            <div class="ctrtable-modal">
                <div class="ctrtable-modal-content">
                    <div class="ctrtable-action-list"></div>
                </div>
            </div>
        `;

        this.tbody =
            this.parent.querySelector("tbody");

        this.paginationDiv =
            this.parent.querySelector(".ctrtable-pagination");

        if (this.enableSearch) {
            this.searchInput =
                this.parent.querySelector(".ctrtable-search");
            this.bindSearch();
        }

        this.modal =
            this.parent.querySelector(".ctrtable-modal");

        this.actionList =
            this.parent.querySelector(".ctrtable-action-list");
    }

    addRow(row) {

        this.rows.push(row);

        this.filteredRows = [...this.rows];

        this.renderRows();
    }

    addRows(rows) {

        rows.forEach(row => {
            this.rows.push(row);
        });

        this.filteredRows = [...this.rows];

        this.renderRows();
    }

    renderRows() {

        this.tbody.innerHTML = "";

        const start =
            (this.currentPage - 1) * this.pageSize;

        const end =
            start + this.pageSize;

        const rows =
            this.filteredRows.slice(start, end);

        // Check if no data to display
        if (rows.length === 0) {
            const tr = document.createElement("tr");
            const td = document.createElement("td");
            td.colSpan = this.columns.length || 1;
            td.className = "ctrtable-no-data";
            td.textContent = "No data to display";
            tr.appendChild(td);
            this.tbody.appendChild(tr);
            this.paginationDiv.innerHTML = "";
            return;
        }

        rows.forEach(row => {

            const tr =
                document.createElement("tr");

            row.forEach((cell, index) => {

                const td =
                    document.createElement("td");

                td.setAttribute(
                    "data-label",
                    this.columns[index]
                );

                if (
                    typeof cell === "string" ||
                    typeof cell === "number"
                ) {

                    td.textContent = cell;
                }
                else {

                    td.appendChild(
                        this.createElement(cell)
                    );
                }

                tr.appendChild(td);
            });

            this.tbody.appendChild(tr);
        });

        this.renderPagination();
    }

    createElement(config) {

        if (config.type === "button") {

            const btn =
                document.createElement("button");

            btn.className =
                "ctrtable-button";

            btn.textContent =
                config.text ||
                config.label ||
                config.value;

            btn.onclick = () =>
                config.click?.(btn);

            return btn;
        }

        if (config.type === "span") {

            const span =
                document.createElement("span");

            span.className =
                "ctrtable-span";

            span.textContent =
                config.text ||
                config.label ||
                config.value;

            span.onclick = () =>
                config.click?.(span);

            return span;
        }

        if (config.type === "action") {

            const btn =
                document.createElement("button");

            btn.className =
                "ctrtable-action";

            btn.innerHTML = "⋮";

            btn.onclick = () => {

                // Convert object to array if needed
                let actionOptions = [];
                if (Array.isArray(config.options)) {
                    actionOptions = config.options;
                } else if (typeof config.options === 'object') {
                    // Convert object to array of action objects
                    actionOptions = Object.keys(config.options).map(key => {
                        return {
                            text: key,
                            click: config.options[key]
                        };
                    });
                }

                this.openActions(actionOptions);
            };

            return btn;
        }

        return document.createTextNode("");
    }

    openActions(actions) {

        this.modal.style.display = "flex";

        this.actionList.innerHTML = "";

        actions.forEach(action => {

            const item =
                document.createElement("button");

            item.className =
                "ctrtable-action-item";

            item.textContent =
                action.text ||
                action.label;

            item.onclick = () => {

                action.click?.();

                this.modal.style.display =
                    "none";
            };

            this.actionList.appendChild(item);
        });

        this.modal.onclick = (e) => {

            if (e.target === this.modal) {

                this.modal.style.display =
                    "none";
            }
        };
    }

    bindSearch() {

        this.searchInput.addEventListener(
            "keyup",
            e => {

                const keyword =
                    e.target.value.toLowerCase().trim();

                // Check if search is empty or not initiated
                if (keyword === "" || keyword.length === 0) {
                    this.searchInitiated = false;
                    // Reset to original rows when search is cleared
                    this.filteredRows = [...this.rows];
                } else {
                    this.searchInitiated = true;
                    
                    // If searchColumns is empty, search all columns
                    // If searchColumns has values, only search those columns
                    this.filteredRows = this.rows.filter(row => {
                        // If searchColumns is empty, search all columns
                        if (this.searchColumns.length === 0) {
                            return row.some(cell =>
                                String(
                                    typeof cell === "object"
                                        ? cell.text || cell.label || cell.value || ""
                                        : cell
                                )
                                    .toLowerCase()
                                    .includes(keyword)
                            );
                        } else {
                            // Search only specific columns
                            return this.searchColumns.some(colName => {
                                const colIndex = this.columns.indexOf(colName);
                                if (colIndex === -1) return false;
                                const cell = row[colIndex];
                                return String(
                                    typeof cell === "object"
                                        ? cell.text || cell.label || cell.value || ""
                                        : cell
                                )
                                    .toLowerCase()
                                    .includes(keyword);
                            });
                        }
                    });
                }

                this.currentPage = 1;

                this.renderRows();
            }
        );
    }

    renderPagination() {

        const pages =
            Math.ceil(
                this.filteredRows.length /
                this.pageSize
            );

        this.paginationDiv.innerHTML = "";

        for (let i = 1; i <= pages; i++) {

            const btn =
                document.createElement("button");

            btn.className =
                "ctrtable-page";

            if (i === this.currentPage) {
                btn.classList.add("active");
            }

            btn.textContent = i;

            btn.onclick = () => {

                this.currentPage = i;

                this.renderRows();
            };

            this.paginationDiv.appendChild(btn);
        }
    }

}

export default CTable;