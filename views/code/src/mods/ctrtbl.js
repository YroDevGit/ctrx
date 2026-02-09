import CtrTable from "./ctrtable.js";

class Ctrtbl {

    static init(selector, addons = undefined, triggerSearch = true) {
        const table = new CtrTable(selector);
        table._destroyControls();

        this._injectCSS();
        this._buildLayout(table, addons, triggerSearch);

        return table;
    }

    static _injectCSS() {
        if (document.getElementById("ctrplain-style")) return;

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
            justify-content: space-between;
            align-items: center;
            padding: 8px 0px;
            gap: 10px;
            flex-wrap: wrap;
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
        }`;
        document.head.appendChild(style);
    }

    static _buildLayout(table, addons, triggerSearch) {
        const top = document.createElement("div");
        top.className = "ctrplain-top";

        const topLeft = document.createElement("div");
        topLeft.className = "ctrplain-left";

        const topRight = document.createElement("div");
        topRight.className = "ctrplain-right";

        top.appendChild(topLeft);
        top.appendChild(topRight);

        const bottom = document.createElement("div");
        bottom.className = "ctrplain-bottom";

        const bottomLeft = document.createElement("div");
        bottomLeft.className = "ctrplain-left";

        const bottomRight = document.createElement("div");
        bottomRight.className = "ctrplain-right";

        bottom.appendChild(bottomLeft);
        bottom.appendChild(bottomRight);

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
                bottomLeft.appendChild(btn);
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
                if (!input.value) handleSearch();
            });

            btn.addEventListener("click", handleSearch);
            input.addEventListener("keypress", e => {
                if (e.key === "Enter") handleSearch();
            });

            if (triggerSearch) handleSearch();

            searchDiv.appendChild(input);
            searchDiv.appendChild(btn);
            topRight.appendChild(searchDiv);
        }

        this._injectPagination(table, bottomRight);
    }

    static _injectPagination(table, container) {
        const pagDiv = document.createElement("div");
        pagDiv.className = "ctrplain-paginate";
        container.appendChild(pagDiv);

        table.paginate = (totalPages = null, callback = null, autotrigger = true) => {
            pagDiv.innerHTML = "";
            let defPages = table.getAttr("ctr-pages");
            totalPages = defPages ?? totalPages ?? 1;

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
    }
}

if (typeof window !== "undefined") window.Ctrtbl = Ctrtbl;
export default Ctrtbl;
