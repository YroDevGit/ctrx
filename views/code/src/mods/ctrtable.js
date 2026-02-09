class CtrTableClass {
    constructor(selector) {
        this.table = document.querySelector(selector);
        if (!this.table) throw new Error(`CtrTable: element ${selector} not found`);

        this.thead = this.table.querySelector("thead");
        this.tbody = this.table.querySelector("tbody");
        if (!this.tbody) {
            this.tbody = document.createElement("tbody");
            this.table.appendChild(this.tbody);
        }

        this.data = [];
        this.filtered = [];
        this.currentPage = 1;
        this.perPage = 10;
        this.searchColumn = "all";

        this._injectCSS();
        this._mapHeaders();
        this._wrapTable();
        this._createTopControls();
        this._createBottomControls();
        this._render();
    }

    clear() {
        this.data = [];
        this.filtered = [...this.data];
        this._render(true);
    }

    static init(selector) {
        return new CtrTableClass(selector);
    }

    paginate(totalPages = null, callback = () => {}, autotrigger = true) {
        console.warn("CtrTable.paginate() is only available in .plain() mode");
    }    

    static plain(selector, addons = undefined, triggerSearch = true) {
        const table = new CtrTableClass(selector);
        table._destroyControls();
    
        if (!document.getElementById("ctrplain-style")) {
            const style = document.createElement("style");
            style.id = "ctrplain-style";
            style.textContent = `
            .ctrplain-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .ctrplain-bottom {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                padding: 8px 0px;
                gap: 10px;
            }            
    
            .ctrplain-left,
            .ctrplain-right {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }
    
            .ctrplain-search {
                display: flex;
                align-items: center;
                gap: 6px;
            }
    
            .ctrplain-search-input {
                padding: 6px 10px;
                border: 1px solid #ccc;
                border-radius: 6px;
                min-width: 180px;
                font-size: 14px;
                outline: none;
            }
    
            .ctrplain-search-btn {
                padding: 6px 12px;
                background: #0d6efd;
                color: #fff;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
                transition: 0.2s;
            }
    
            .ctrplain-search-btn:hover {
                background: #0b5ed7;
            }
    
            .ctrplain-paginate select {
                padding: 6px 8px;
                border: 1px solid #ced4da;
                border-radius: 6px;
                background: #f8f9fa;
                font-size: 14px;
                outline: none;
            }
            `;
            document.head.appendChild(style);
        }
    
        const top = document.createElement("div");
        top.className = "ctrplain-top";
    
        const left = document.createElement("div");
        left.className = "ctrplain-left";
    
        const right = document.createElement("div");
        right.className = "ctrplain-right";
    
        top.appendChild(left);
        top.appendChild(right);
    
        const bottom = document.createElement("div");
        bottom.className = "ctrplain-bottom";
    
        table.container.parentNode.insertBefore(top, table.container);
        table.container.after(bottom);
    
        let hasSearch = undefined;
        let hasExport = undefined;
    
        if (addons) {
            if (typeof addons === "function") {
                hasSearch = addons;
            } else {
                hasSearch = addons.search ?? undefined;
                hasExport = addons.export ?? undefined;
            }
        }
      
        if (Array.isArray(hasExport)) {
            hasExport.forEach(type => {
                const btn = document.createElement("button");
                btn.textContent = type.toUpperCase();
                btn.className = "ctrplain-search-btn";
                btn.addEventListener("click", () => table._export(type));
                left.appendChild(btn);
            });
        }
    
        if (hasSearch) {
            const searchDiv = document.createElement("div");
            searchDiv.className = "ctrplain-search";
    
            const input = document.createElement("input");
            input.type = "search";
            input.placeholder = "Search...";
            input.className = "ctrplain-search-input";
    
            const btn = document.createElement("button");
            btn.textContent = "Search";
            btn.className = "ctrplain-search-btn";
    
            const handleSearch = () => {
                const value = input.value.trim();
                hasSearch(value, table);
            };
    
            input.addEventListener("input", () => {
                if (input.value === "") handleSearch();
            });
    
            btn.addEventListener("click", handleSearch);
            input.addEventListener("keypress", e => {
                if (e.key === "Enter") handleSearch();
            });
    
            if (triggerSearch) handleSearch();
    
            searchDiv.appendChild(input);
            searchDiv.appendChild(btn);
            right.appendChild(searchDiv);
        }
    
        /* -------- PAGINATION -------- */
    
        const pagDiv = document.createElement("div");
        pagDiv.className = "ctrplain-paginate";
        bottom.appendChild(pagDiv);
    
        table.paginate = (totalPages = null, callback = null, autotrigger = true) => {
            pagDiv.innerHTML = "";
    
            let defPages = table.getAttr("ctr-pages") || 1;
            totalPages = totalPages || defPages || 1;
    
            const select = document.createElement("select");
    
            for (let i = 1; i <= totalPages; i++) {
                const opt = document.createElement("option");
                opt.value = i;
                opt.textContent = `Page ${i}`;
                select.appendChild(opt);
            }
    
            if (autotrigger && typeof callback === "function") {
                callback.call(table, 1, table);
            }
    
            select.addEventListener("change", e => {
                const page = parseInt(e.target.value);
                if (typeof callback === "function") {
                    callback.call(table, page, table);
                }
            });
    
            pagDiv.appendChild(select);
        };
    
        return table;
    }
    

    _destroyControls() {
        const top = this.container.previousElementSibling;
        if (top && top.classList.contains("ctrtable-top")) top.remove();
        const bottom = this.container.nextElementSibling;
        if (bottom && bottom.classList.contains("ctrtable-bottom")) bottom.remove();
        this.perPage = this.data.length || 999999;
        this._render();
    }

    getAttr(attr) {
        return this.table.getAttribute(attr);
    }

    _mapHeaders() {
        this.headers = [];
        this.includeMap = [];

        this.thead?.querySelectorAll("th").forEach(th => {
            const key = th.getAttribute("key") || th.getAttribute("col") || th.innerText.trim();
            const include = th.getAttribute("ctr-include");
            const isIncluded = include === null || include.toLowerCase() !== "false";

            this.headers.push(key);
            this.includeMap.push(isIncluded);
        });
    }

    _injectCSS() {
        if (document.getElementById("ctrtable-style")) return;
        const style = document.createElement("style");
        style.id = "ctrtable-style";
        style.textContent = `
        .ctrtable-container {
            width: 100%;
            display: block;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            padding: 10px;
            box-sizing: border-box;
        }
    
        .ctrtable-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
    
        .ctrtable-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
    
        .ctrtable-select select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            background: #f8f9fa;
        }
    
        .ctrtable-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
    
        .ctrtable-buttons button {
            background: #0d6efd;
            border: none;
            color: #fff;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 13px;
            transition: 0.2s;
        }
        .ctrtable-buttons button:hover {
            background: #0b5ed7;
        }
    
        .ctrtable-search {
            display: flex;
            align-items: center;
            gap: 6px;
        }
    
        .ctrtable-search select {
            padding: 6px 8px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            background: #f8f9fa;
        }
    
        .ctrtable-search input {
            padding: 6px 10px;
            outline: none;
            border: 1px solid #ced4da;
            border-radius: 6px;
            width: 200px;
        }
    
        table.ctrtable {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 600px; 
            box-sizing: border-box;
        }
    
        table.ctrtable th,
        table.ctrtable td {
            border: 1px solid #dee2e6;
            padding: 10px 14px;
            text-align: left;
            white-space: nowrap;
        }
    
        table.ctrtable th {
            background: #f8f9fa;
            color: #212529;
            font-weight: 600;
        }
    
        table.ctrtable tr:nth-child(even) {
            background: #fcfcfc;
        }
    
        table.ctrtable tr:hover {
            background: #f1f3f5;
        }
    
        .ctrtable-nodata {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 30px 12px;
            background: #fff;
        }
    
        .ctrtable-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #555;
        }
    
        .ctrtable-pagination {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
    
        .ctrtable-pagination button {
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 5px 10px;
            cursor: pointer;
            transition: 0.2s;
        }
    
        .ctrtable-pagination button.active {
            background: #0b5ed7;
        }
    
        @media (max-width: 600px) {
            .ctrtable-container {
                width: 100%;
                padding: 10px; 
                overflow-x: auto; 
            }
    
            table.ctrtable {
                min-width: 500px; 
            }
    
            table.ctrtable th,
            table.ctrtable td {
                padding: 10px; 
                white-space: nowrap;
            }
    
            .ctrtable-top,
            .ctrtable-search {
                flex-direction: row;
                align-items: stretch;
                width: 100%;
            }
    
            .ctrtable-search select,
            .ctrtable-search input {
                width: 100%;
            }
    
            .ctrtable-left {
                width: 100%;
                justify-content: space-between;
            }
        }`;
        document.head.appendChild(style);
        this.table.classList.add("ctrtable");
    }


    _wrapTable() {
        const container = document.createElement("div");
        container.className = "ctrtable-container";
        this.table.parentNode.insertBefore(container, this.table);
        container.appendChild(this.table);
        this.container = container;
    }

    _createTopControls() {
        const top = document.createElement("div");
        top.className = "ctrtable-top";

        const left = document.createElement("div");
        left.className = "ctrtable-left";

        const selectDiv = document.createElement("div");
        selectDiv.className = "ctrtable-select";
        const select = document.createElement("select");
        [10, 25, 50, 100].forEach(num => {
            const opt = document.createElement("option");
            opt.value = num;
            opt.textContent = `Show ${num}`;
            if (num === this.perPage) opt.selected = true;
            select.appendChild(opt);
        });
        select.addEventListener("change", e => {
            this.perPage = parseInt(e.target.value);
            this.currentPage = 1;
            this._render(false);
        });
        selectDiv.appendChild(select);
        left.appendChild(selectDiv);

        this.buttonWrap = document.createElement("div");
        this.buttonWrap.className = "ctrtable-buttons";
        left.appendChild(this.buttonWrap);

        const searchDiv = document.createElement("div");
        searchDiv.className = "ctrtable-search";

        const combo = document.createElement("select");
        combo.innerHTML = `<option value="all">All Columns</option>`;
        this.headers.forEach(h => {
            const opt = document.createElement("option");
            opt.value = h;
            opt.textContent = h;
            combo.appendChild(opt);
        });
        combo.addEventListener("change", e => {
            this.searchColumn = e.target.value;
            this._filter(this.searchInput.value);
        });

        const input = document.createElement("input");
        input.placeholder = "Search...";
        input.addEventListener("input", (e) => this._filter(e.target.value));
        this.searchInput = input;

        searchDiv.appendChild(combo);
        searchDiv.appendChild(input);

        top.appendChild(left);
        top.appendChild(searchDiv);
        this.container.parentNode.insertBefore(top, this.container);
    }

    _createBottomControls() {
        const bottom = document.createElement("div");
        bottom.className = "ctrtable-bottom";
        this.infoDiv = document.createElement("div");
        this.infoDiv.className = "ctrtable-info";
        this.paginationDiv = document.createElement("div");
        this.paginationDiv.className = "ctrtable-pagination";
        bottom.appendChild(this.infoDiv);
        bottom.appendChild(this.paginationDiv);
        this.container.after(bottom);
    }

    add_row(data, id = 0) {
        let obj = {};
        if (Array.isArray(data)) {
            this.headers.forEach((h, i) => obj[h] = data[i] ?? "");
        } else if (typeof data === "object") {
            obj = data;
        } else return;

        if (id) obj._ctr_id = id;

        this.data.push(obj);
        this.filtered = [...this.data];
        this._render(true);
    }

    _filter(query) {
        query = query.toLowerCase();
        if (!query) this.filtered = [...this.data];
        else {
            this.filtered = this.data.filter(row => {
                if (this.searchColumn === "all")
                    return Object.values(row).some(v => String(v).toLowerCase().includes(query));
                else
                    return String(row[this.searchColumn] ?? "").toLowerCase().includes(query);
            });
        }
        this.currentPage = 1;
        this._render(false);
    }

    _paginate() {
        const total = this.filtered.length;
        const pages = Math.ceil(total / this.perPage) || 1;
        const start = (this.currentPage - 1) * this.perPage;
        const end = Math.min(start + this.perPage, total);
        const rows = this.filtered.slice(start, end);

        this.tbody.innerHTML = "";

        if (!rows.length) {
            const tr = document.createElement("tr");
            const td = document.createElement("td");
            td.colSpan = this.headers.length;
            td.className = "ctrtable-nodata";
            td.style.textAlign = "center";
            td.textContent = "No data available";
            tr.appendChild(td);
            this.tbody.appendChild(tr);
        } else {
            rows.forEach(r => {
                const tr = document.createElement("tr");
                if (r._ctr_id) {
                    tr.setAttribute("type", "ctrtr");
                    tr.setAttribute("ctrtr_id", r._ctr_id);
                }
                this.headers.forEach(k => {
                    const td = document.createElement("td");
                    let cont = r[k] ?? "";
                    if (Array.isArray(cont)) {
                        cont.forEach(item => {
                            if (item instanceof HTMLElement) {
                                td.appendChild(item);
                            } else {
                                td.insertAdjacentHTML('beforeend', item);
                            }
                        });
                    }
                    else if (cont instanceof HTMLElement) {
                        td.appendChild(cont);
                    }
                    else {
                        td.innerHTML = cont;
                    }
                    td.setAttribute("data-label", k);
                    tr.appendChild(td);
                });
                this.tbody.appendChild(tr);
            });
        }

        this.paginationDiv.innerHTML = "";

        const createBtn = (text, disabled, clickFn, active = false) => {
            const btn = document.createElement("button");
            btn.textContent = text;
            if (disabled) btn.disabled = true;
            if (active) btn.classList.add("active");
            btn.addEventListener("click", clickFn);
            return btn;
        };

        const prev = createBtn("‹", this.currentPage === 1, () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this._render(false);
            }
        });
        this.paginationDiv.appendChild(prev);

        const maxVisible = 3;
        let startPage = Math.max(1, this.currentPage - 1);
        let endPage = Math.min(pages, startPage + maxVisible - 1);
        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const btn = createBtn(
                i,
                false,
                () => {
                    this.currentPage = i;
                    this._render(false);
                },
                i === this.currentPage
            );
            this.paginationDiv.appendChild(btn);
        }

        const next = createBtn("›", this.currentPage === pages, () => {
            if (this.currentPage < pages) {
                this.currentPage++;
                this._render(false);
            }
        });
        this.paginationDiv.appendChild(next);

        const showFrom = total ? start + 1 : 0;
        const showTo = end;
        this.infoDiv.textContent = `Showing ${showFrom} to ${showTo} of ${total} entries`;
    }

    _render(resetFilter = true) {
        if (resetFilter && !this.filtered.length) this.filtered = [...this.data];
        this._paginate();
    }

    buttons(list = []) {
        this.buttonWrap.innerHTML = "";
        list.forEach(name => {
            const btn = document.createElement("button");
            btn.textContent = name.toUpperCase();
            btn.addEventListener("click", () => this._export(name));
            this.buttonWrap.appendChild(btn);
        });
    }

    get_data(ctr_id) {
        if (ctr_id === undefined) {
            return this.data.map(({ _ctr_id, ...rest }) => ({ ...rest }));
        }

        if (Array.isArray(ctr_id)) {
            return this.data
                .filter(r => ctr_id.includes(r._ctr_id))
                .map(({ _ctr_id, ...rest }) => ({ ...rest }));
        }

        const found = this.data.find(r => r._ctr_id == ctr_id);
        if (!found) return null;
        const { _ctr_id, ...data } = found;
        return data;
    }

    _export(type) {
        const rows = [];
        const ths = Array.from(this.thead.querySelectorAll("th"))
            .filter((th, i) => this.includeMap[i])
            .map(th => th.innerText.trim());
        rows.push(ths);

        this.filtered.forEach(r => {
            const row = this.headers
                .map((k, i) => (this.includeMap[i] ? r[k] : null))
                .filter(v => v !== null);
            rows.push(row);
        });

        if (type === "csv") this._downloadCSV(rows);
        else alert(type.toUpperCase() + " export coming soon");
    }

    _downloadCSV(rows) {
        const csv = rows.map(r => r.join(",")).join("\n");
        const blob = new Blob([csv], { type: "text/csv" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        let thename = this.table.getAttribute("ctr-name") || "table";
        a.download = thename + ".csv";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}

const CtrTable = CtrTableClass;
if (typeof window !== "undefined") window.CtrTable = CtrTable;
if (typeof module !== "undefined" && module.exports) module.exports = CtrTable;
export default CtrTable;
