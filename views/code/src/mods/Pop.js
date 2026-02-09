class Pop {
    static id = "ctr-pop";

    static ensureStyle() {
        if (document.getElementById("ctr-pop-style")) return;

        const style = document.createElement("style");
        style.id = "ctr-pop-style";
        style.textContent = `
        #${this.id}-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            opacity: 0;
            transition: opacity .25s ease;
            font-family: Consolas, Monaco, monospace;
        }
        #${this.id}-overlay.show { opacity: 1; }

        #${this.id} {
            background: #1e1e1e;
            color: #d4d4d4;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,.5);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: pop-in .25s ease forwards;
        }

        @keyframes pop-in {
            from { transform: scale(.9); opacity: 0 }
            to   { transform: scale(1); opacity: 1 }
        }

        #${this.id} header {
            padding: 10px 14px;
            background: #252526;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        #${this.id} header button {
            background: none;
            border: none;
            color: #aaa;
            font-size: 20px;
            cursor: pointer;
        }

        #${this.id} .content {
            padding: 10px 14px;
            overflow: auto;
            font-size: 13px;
            line-height: 1.6;
        }

        .json-line { white-space: nowrap; }
        .json-toggle {
            cursor: pointer;
            color: #aaa;
            margin-left: 4px;
        }
        .json-key { color: #9cdcfe; }
        .json-string { color: #ce9178; }
        .json-number { color: #b5cea8; }
        .json-boolean { color: #569cd6; }
        .json-null { color: #569cd6; }
        .json-brace { color: #d4d4d4; }

        .json-indent {
            margin-left: 18px;
        }

        .json-value {
            margin-left: 36px;
        }
        `;
        document.head.appendChild(style);
    }

    static show(data, title = "CodeTazeR Pop Display") {
        this.ensureStyle();

        const old = document.getElementById(this.id + "-overlay");
        if (old) old.remove();

        const overlay = document.createElement("div");
        overlay.id = this.id + "-overlay";

        const modal = document.createElement("div");
        modal.id = this.id;

        const header = document.createElement("header");
        header.innerHTML = `<span>${title}</span>`;
        const close = document.createElement("button");
        close.innerHTML = "&times;";
        close.onclick = () => overlay.remove();
        header.appendChild(close);

        const content = document.createElement("div");
        content.className = "content";

        if (typeof data === "object" && data !== null) {
            content.appendChild(this.renderJSON(data));
        } else {
            content.textContent = String(data);
        }

        modal.appendChild(header);
        modal.appendChild(content);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        overlay.addEventListener("click", e => {
            if (e.target === overlay) overlay.remove();
        });

        setTimeout(() => overlay.classList.add("show"), 10);
    }

    static renderJSON(obj) {
        const container = document.createElement("div");
        const isArray = Array.isArray(obj);

        const open = document.createElement("div");
        open.innerHTML = `<span class="json-brace">${isArray ? "[" : "{"}</span>`;
        container.appendChild(open);

        Object.keys(obj).forEach(key => {
            const value = obj[key];
            const hasChildren = typeof value === "object" && value !== null;

            /* KEY LINE */
            const line = document.createElement("div");
            line.className = "json-line json-indent";

            const keySpan = document.createElement("span");
            keySpan.className = "json-key";
            keySpan.textContent = isArray ? key : `"${key}"`;
            line.appendChild(keySpan);

            line.appendChild(document.createTextNode(":"));

            if (hasChildren) {
                const toggle = document.createElement("span");
                toggle.className = "json-toggle";
                toggle.textContent = " ▶";
                line.appendChild(toggle);

                const summary = document.createElement("span");
                summary.className = "json-brace";
                summary.textContent = Array.isArray(value) ? " [ … ]" : " { … }";
                line.appendChild(summary);

                /* VALUE BLOCK (separate column) */
                const valueWrap = document.createElement("div");
                valueWrap.className = "json-value";
                valueWrap.style.display = "none";
                valueWrap.appendChild(this.renderJSON(value));

                let expanded = false;
                const toggleFn = () => {
                    expanded = !expanded;
                    toggle.textContent = expanded ? " ▼" : " ▶";
                    summary.style.display = expanded ? "none" : "inline";
                    valueWrap.style.display = expanded ? "block" : "none";
                };

                toggle.onclick = toggleFn;
                line.onclick = (e) => {
                    if (e.target === toggle) return;
                    toggleFn();
                };

                container.appendChild(line);
                container.appendChild(valueWrap);
            } else {
                line.appendChild(document.createTextNode(" "));
                line.appendChild(this.renderValue(value));
                container.appendChild(line);
            }
        });

        const close = document.createElement("div");
        close.innerHTML = `<span class="json-brace">${isArray ? "]" : "}"}</span>`;
        container.appendChild(close);

        return container;
    }

    static renderValue(val) {
        const span = document.createElement("span");

        if (typeof val === "string") {
            span.className = "json-string";
            span.textContent = `"${val}"`;
        } else if (typeof val === "number") {
            span.className = "json-number";
            span.textContent = val;
        } else if (typeof val === "boolean") {
            span.className = "json-boolean";
            span.textContent = val;
        } else if (val === null) {
            span.className = "json-null";
            span.textContent = "null";
        }

        return span;
    }
}

if (typeof window !== "undefined") {
    window.Pop = Pop;
}

export default Pop;
