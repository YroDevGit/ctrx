class CtrElement {
    constructor(element, attribute = {}) {
        this.elem = document.createElement(element);
        let txt = "";
        if (attribute) {
            if (typeof attribute == "string") {
                txt = attribute;
            } else {
                txt = attribute.text ?? attribute.html ?? element;
                if (attribute) {
                    for (let a in attribute) {
                        if (a == 'text') {
                            continue;
                        }
                        this.elem.setAttribute(a, attribute[a]);
                    }
                }
            }
        }
        if (element == "input" || element == "textarea") {
            if (txt instanceof HTMLElement) return;
            this.elem.value = txt;
        } else {
            if (txt instanceof HTMLElement) {
                this.elem.appendChild(txt);
            } else {
                this.elem.innerHTML = txt;
            }
        }
        this._injectCSS();
        return this.elem;
    }
    static make(element, attribute = {}) {
        return new CtrElement(element, attribute);
    }

    static _button(attribute, actions) {
        return new CtrElement("button", attribute);
    }

    _injectCSS() {
        if (document.getElementById("CtrElement-style")) return;
        const style = document.createElement("style");
        style.id = "CtrElement-style";
        style.textContent = `
        .ctr-element-btn {
            display: inline-block;
            font-size: 0.95rem;
            padding: 0.3rem 0.8rem;
            border-radius: 0.3rem;
            border: none;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            user-select: none;
            text-decoration: none;
          }
          .ctr-element-btn-default {
            background: #e0e0e0;
            color: #333;
          }
          .ctr-element-btn-default:hover {
            background: #d5d5d5;
          }

          .ctr-element-btn-primary {
            background: #007bff;
            color: white;
          }
          .ctr-element-btn-primary:hover {
            background: #0069d9;
          }
          
          .ctr-element-btn-success {
            background: #28a745;
            color: white;
          }
          .ctr-element-btn-success:hover {
            background: #218838;
          }
          
          .ctr-element-btn-warning {
            background: #ffc107;
            color: #212529;
          }
          .ctr-element-btn-warning:hover {
            background: #e0a800;
          }
          
          .ctr-element-btn-danger {
            background: red;
            color: white;
          }
          .ctr-element-btn-danger:hover {
            background: #c82333;
          }
          
          .ctr-element-btn-info {
            background: #17a2b8;
            color: white;
          }
          .ctr-element-btn-info:hover {
            background: #138496;
          }
          
          .ctr-element-btn-dark {
            background: #343a40;
            color: white;
          }
          .ctr-element-btn-dark:hover {
            background: #23272b;
          }
          
          .ctr-element-btn-light {
            background: #f8f9fa;
            color: #212529;
            border: 1px solid #ced4da;
          }
          .ctr-element-btn-light:hover {
            background: #e2e6ea;
          }
          
          .ctr-element-btn-outline-primary {
            background: transparent;
            color: #007bff;
            border: 1px solid #007bff;
          }
          .ctr-element-btn-outline-primary:hover {
            background: #007bff;
            color: white;
          }
          
          .ctr-element-btn:disabled,
          .ctr-element-btn.disabled {
            opacity: 0.65;
            cursor: not-allowed;
            pointer-events: none;
          }
          
          .ctr-element-btn:hover {
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
          }
        `;
        document.head.appendChild(style);
    }


    static button(attribute, actions = {}) {
        let attr = {};
        let hasClicked = false;
        let clickbtn = undefined;
        if (typeof attribute == "string") {
            attr = { ...attr, text: attribute };
        } else {
            attr = attribute;
        }
        if (attr.click) {
            clickbtn = attr.click;
            delete attr.click;
        }
        let btn = new CtrElement("button", attr);
        if (attr.color) {
            btn.style.color = attr.color;
        }
        if (!attr.class) {
            btn.className = "ctr-element-btn";
        }
        if (attr.bg) {
            let bg = attr.bg;
            if (bg == "primary" || bg == "info" || bg == "warning" || bg == "success" || bg == "danger" || bg == "dark") {
                btn.classList.add(`ctr-element-btn-${bg}`);
            } else {
                btn.style.background = bg;
            }
        }
        if (clickbtn) {
            if (typeof clickbtn !== "function") return;
            hasClicked = true;
            btn.addEventListener("click", () => {
                clickbtn();
            });
            delete attr.ckick;
        }
        if (actions) {
            if (typeof actions == "function") {
                btn.addEventListener("click", () => {
                    if (hasClicked) return;
                    actions();
                });
            } else if (typeof actions == "object") {
                for (let a in actions) {
                    let call = actions[a];
                    if (typeof call !== "function") {
                        continue;
                    }
                    btn.addEventListener(a, () => {
                        call();
                    });
                }
            } else {
                console.error("ctr button actions should only be object or function");
                return;
            }
        }
        return btn;
    }

    static dropdown(attribute, items = []) {
        if (!CtrElement._dropdowns) CtrElement._dropdowns = [];

        if (!CtrElement._dropdownCSSInjected) {
            const style = document.createElement("style");
            style.textContent = `
                .ctr-dropdown-toggle.ctr-dropdown-active {
                    font-weight:bold;
                    color:red;
                }
            `;
            document.head.appendChild(style);
            CtrElement._dropdownCSSInjected = true;
        }

        const wrapper = document.createElement("div");
        wrapper.classList.add("ctr-dropdown");
        wrapper.style.position = "relative";
        wrapper.style.display = "inline-block";
        let attr = {};
        if (typeof attribute == "string") {
            attr.text = attribute;
        } else if (attribute instanceof HTMLElement) {
            attr.text = attribute.outerHTML;
        } else {
            attr = attribute;
        }
        const btn = document.createElement("span");
        btn.classList.add("ctr-dropdown-toggle");
        btn.innerHTML = attr.text || "⋮";
        btn.style.cursor = "pointer";
        Object.entries(attr).forEach(([k, v]) => {
            if (k !== "text") btn.setAttribute(k, v);
        });
        wrapper.appendChild(btn);

        const menu = document.createElement("div");
        menu.classList.add("ctr-dropdown-menu");
        Object.assign(menu.style, {
            position: "absolute",
            top: "0",
            left: "0",
            background: "#fff",
            boxShadow: "0 2px 6px rgba(0,0,0,0.15)",
            borderRadius: "6px",
            display: "none",
            zIndex: 9999,
            minWidth: "90px",
            overflow: "hidden"
        });

        items.forEach(item => {
            const menuItem = document.createElement("div");
            menuItem.classList.add("ctr-dropdown-item");
            menuItem.innerHTML = item.text;
            if (item.color) {
                menuItem.style.color = item.color;
            }
            Object.assign(menuItem.style, {
                padding: "8px 12px",
                cursor: "pointer",
                userSelect: "none"
            });
            menuItem.addEventListener("click", e => {
                e.stopPropagation();
                menu.style.display = "none";
                btn.classList.remove("ctr-dropdown-active");
                let action = item.action ?? item.click ?? undefined;
                if (typeof action === "function") action();
            });
            menuItem.addEventListener("mouseover", () => menuItem.style.background = "#f2f2f2");
            menuItem.addEventListener("mouseout", () => menuItem.style.background = "");
            menu.appendChild(menuItem);
        });

        document.body.appendChild(menu);
        CtrElement._dropdowns.push({ menu, btn });

        btn.addEventListener("click", e => {
            e.stopPropagation();
            CtrElement._dropdowns.forEach(({ menu: m, btn: b }) => {
                if (m !== menu) {
                    m.style.display = "none";
                    b.classList.remove("ctr-dropdown-active");
                }
            });

            const isVisible = menu.style.display === "block";
            menu.style.display = isVisible ? "none" : "block";
            btn.classList.toggle("ctr-dropdown-active", !isVisible);

            const rect = btn.getBoundingClientRect();
            const menuHeight = menu.offsetHeight;
            const menuWidth = menu.offsetWidth;
            const viewportHeight = window.innerHeight;

            let left = rect.right - menuWidth;
            if (left < 0) left = 0;
            menu.style.left = left + "px";

            if (rect.bottom + menuHeight > viewportHeight) {
                menu.style.top = (rect.top - menuHeight) + "px";
            } else {
                menu.style.top = rect.bottom + "px";
            }
        });

        document.addEventListener("click", () => {
            menu.style.display = "none";
            btn.classList.remove("ctr-dropdown-active");
        });

        return wrapper;
    }

    static menu(attribute, items = []) {
        if (!CtrElement._menuCSSInjected) {
            const style = document.createElement("style");
            style.textContent = `
                .ctr-menu-modal {
                    position: fixed;
                    top: 0; left: 0;
                    width: 100%; height: 100%;
                    background: rgba(0,0,0,0.4);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 10000;
                }
                .ctr-menu-content {
                    background: #fff;
                    border-radius: 8px;
                    min-width: 150px;
                    max-width: 90%;
                    max-height: 80%;
                    overflow-y: auto;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                    position: relative;
                }
                .ctr-menu-close {
                    display: block;
                    width: 100%;
                    text-align: right;
                    padding: 10px 16px;
                    font-weight: bold;
                    font-size: 18px;
                    cursor: pointer;
                    color: #0d6efd; /* primary light blue */
                    border-bottom: 1px solid #ddd;
                    box-sizing: border-box;
                }
                .ctr-menu-close:hover {
                    color: #084298;
                }
                .ctr-menu-item {
                    padding: 10px 16px;
                    cursor: pointer;
                    user-select: none;
                    color:#0e8297;
                }
                .ctr-menu-item-center {
                    padding: 10px 16px;
                    cursor: pointer;
                    user-select: none;
                    text-align:center;
                    color:#0e8297;
                }
                .ctr-menu-item:hover {
                    background: #f2f2f2;
                }
                .ctr-menu-separator {
                    height: 1px;
                    width: 100%;
                    background: #ddd;
                    margin: 0;
                }
                .ctr-menu-toggle-active {
                    font-weight: bold;
                    color: red;
                }
            `;
            document.head.appendChild(style);
            CtrElement._menuCSSInjected = true;
        }

        const wrapper = document.createElement("div");
        wrapper.classList.add("ctr-menu-wrapper");
        wrapper.style.display = "inline-block";

        let attr = {};
        if (typeof attribute == "string") {
            attr.text = attribute;
        } else if (attribute instanceof HTMLElement) {
            attr.text = attribute.outerHTML;
        } else {
            attr = attribute;
        }
        const btn = document.createElement("span");
        btn.classList.add("ctr-menu-toggle");
        btn.innerHTML = attr.text || "⋮";
        btn.style.cursor = "pointer";
        Object.entries(attr).forEach(([k, v]) => {
            if (k !== "text") btn.setAttribute(k, v);
        });
        wrapper.appendChild(btn);

        btn.addEventListener("click", e => {
            e.stopPropagation();
            let align = btn.getAttribute("menu-align") ?? null;
            let itemclass = "ctr-menu-item";
            if (align && align == "center") {
                itemclass = "ctr-menu-item-center";
            }
            btn.classList.add("ctr-menu-toggle-active");

            const modal = document.createElement("div");
            modal.classList.add("ctr-menu-modal");

            const content = document.createElement("div");
            content.classList.add("ctr-menu-content");

            const closeBtn = document.createElement("div");
            closeBtn.classList.add("ctr-menu-close");
            closeBtn.innerHTML = "&times;";
            closeBtn.addEventListener("click", () => {
                document.body.removeChild(modal);
                btn.classList.remove("ctr-menu-toggle-active");
            });
            content.appendChild(closeBtn);

            items.forEach((item, index) => {
                if (index > 0) {
                    const sep = document.createElement("div");
                    sep.classList.add("ctr-menu-separator");
                    content.appendChild(sep);
                }

                const menuItem = document.createElement("div");
                menuItem.classList.add(itemclass);
                menuItem.innerHTML = item.text;
                if (item.color) {
                    menuItem.style.color = item.color;
                }
                menuItem.addEventListener("click", ev => {
                    ev.stopPropagation();
                    let action = item.action ?? item.click ?? undefined;
                    if (typeof action === "function") action();
                    document.body.removeChild(modal);
                    btn.classList.remove("ctr-menu-toggle-active");
                });
                content.appendChild(menuItem);
            });

            modal.appendChild(content);
            document.body.appendChild(modal);

            modal.addEventListener("click", ev => {
                if (ev.target === modal) {
                    document.body.removeChild(modal);
                    btn.classList.remove("ctr-menu-toggle-active");
                }
            });
        });

        return wrapper;
    }

    static image_picker(selector, clickAction = undefined) {
        const input = typeof selector === "string"
            ? document.querySelector(selector)
            : selector;

        if (!input) return console.error("image_picker: input not found");
        if (input.type !== "file") {
            console.error("image_picker: element must be <input type='file'>");
            return;
        }

        let ctrname = input.getAttribute("name") ?? null;

        if (ctrname) {
            if (ctrname.includes("[]")) {
                input.setAttribute("multiple", true);
            } else {
                input.removeAttribute("multiple");
            }
        }

        const isMultiple = input.hasAttribute("multiple");
        input.setAttribute("accept", "image/*");
        input.style.display = "none";
        let selectedFiles = [];
        let alignment = {};
        if (!isMultiple) {
            alignment = { justifyContent: "center" };
        }
        const wd = input.getAttribute("ctr-width") || "100%";
        const bg = input.getAttribute("ctr-bg") || "white";
        const col = input.getAttribute("ctr-color") || "black";
        const container = document.createElement("div");
        container.classList.add("ctr-image-picker-container");
        Object.assign(container.style, {
            border: "2px solid #e0e0e0",
            borderRadius: "12px",
            padding: "15px",
            background: bg,
            color: col,
            boxShadow: "0 2px 8px rgba(0,0,0,0.05)",
            maxWidth: wd,
        });

        const titleText = input.getAttribute("ctr-title") || "";
        const align = input.getAttribute("ctr-align") || "center";

        if (titleText) {
            const title = document.createElement("div");
            title.textContent = titleText;
            Object.assign(title.style, {
                fontWeight: "600",
                fontSize: "15px",
                marginBottom: "10px",
                textAlign: align
            });
            container.appendChild(title);
        }

        const wrapper = document.createElement("div");
        wrapper.classList.add("ctr-image-picker");
        wrapper.innerHTML = `<p style="margin:0;color:black;">📁 Drop image${isMultiple ? "s" : ""} here or click to browse</p>`;
        Object.assign(wrapper.style, {
            border: "2px dashed #ccc",
            borderRadius: "10px",
            padding: "20px",
            textAlign: "center",
            cursor: "pointer",
            transition: "0.2s ease",
            background: "#fafafa",
        });

        const preview = document.createElement("div");
        preview.classList.add("ctr-image-preview");
        Object.assign(preview.style, {
            display: "flex",
            flexWrap: "nowrap",
            gap: "12px",
            overflowX: isMultiple ? "auto" : "hidden",
            marginTop: "15px",
            paddingBottom: "5px",
            scrollbarWidth: "thin",
            scrollbarColor: "#ccc transparent",
            ...alignment
        });

        container.appendChild(wrapper);
        container.appendChild(preview);
        input.parentNode.insertBefore(container, input);
        input.parentNode.insertBefore(input, container.nextSibling);

        wrapper.addEventListener("dragover", e => {
            e.preventDefault();
            wrapper.style.borderColor = "#007bff";
            wrapper.style.background = "#f0f8ff";
        });
        wrapper.addEventListener("dragleave", e => {
            e.preventDefault();
            wrapper.style.borderColor = "#ccc";
            wrapper.style.background = "#fafafa";
        });
        wrapper.addEventListener("drop", e => {
            e.preventDefault();
            wrapper.style.borderColor = "#ccc";
            wrapper.style.background = "#fafafa";
            handleFiles(e.dataTransfer.files);
        });

        wrapper.addEventListener("click", () => input.click());
        input.addEventListener("change", () => handleFiles(input.files));

        function handleFiles(files) {
            const newFiles = Array.from(files).filter(f => f.type.startsWith("image/"));

            if (!isMultiple) {
                selectedFiles = newFiles.slice(0, 1);
                preview.innerHTML = "";
                renderImage(selectedFiles[0]);
            } else {
                newFiles.forEach(file => {
                    const exists = selectedFiles.some(
                        f => f.name === file.name && f.size === file.size
                    );
                    if (!exists) {
                        selectedFiles.unshift(file);
                        renderImage(file, true);
                    }
                });
            }
            updateInputFiles();
        }

        function renderImage(file, prepend = false) {
            const reader = new FileReader();
            reader.onload = e => {
                const item = document.createElement("div");
                item.classList.add("ctr-image-item");
                Object.assign(item.style, {
                    position: "relative",
                    minWidth: "100px",
                    height: "100px",
                    flex: "0 0 auto",
                    borderRadius: "10px",
                    overflow: "hidden",
                    boxShadow: "0 2px 5px rgba(0,0,0,0.1)",
                    border: "1px solid #eee",
                });

                const img = document.createElement("img");
                img.src = e.target.result;
                Object.assign(img.style, {
                    width: "100%",
                    height: "100%",
                    objectFit: "cover",
                });

                let details = {
                    name: file.name,
                    lastModified: file.lastModified,
                    type: file.type,
                    size: file.size,
                    lastModifiedDate: file.lastModifiedDate,
                    src: img.src
                }
                if (clickAction) {
                    img.addEventListener("click", () => {
                        clickAction(details);
                    });
                }
                const close = document.createElement("span");
                close.innerHTML = "&times;";
                Object.assign(close.style, {
                    position: "absolute",
                    top: "4px",
                    left: "4px",
                    background: "#ff4d4d",
                    color: "white",
                    borderRadius: "50%",
                    width: "20px",
                    height: "20px",
                    fontSize: "16px",
                    lineHeight: "20px",
                    textAlign: "center",
                    cursor: "pointer",
                    boxShadow: "0 0 4px rgba(0,0,0,0.3)"
                });

                close.addEventListener("click", () => {
                    selectedFiles = selectedFiles.filter(f => f !== file);
                    item.remove();
                    updateInputFiles();
                });
                let flname = document.createElement("span");
                flname.style.display = "absolute";
                Object.assign(flname.style, {
                    position: "absolute",
                    bottom: "0",
                    zIndex: "9999",
                    left: "0",
                    textOverflow: "ellipsis",
                    overflow: "hidden",
                    whiteSpace: "nowrap",
                    maxWidth: "100%",
                    background: "white",
                    opacity: "0.5"
                });
                flname.textContent = file.name || "No name";

                item.appendChild(img);
                item.appendChild(close);
                item.appendChild(flname);

                if (prepend) {
                    preview.insertBefore(item, preview.firstChild);
                } else {
                    preview.appendChild(item);
                }
            };
            reader.readAsDataURL(file);
        }

        function updateInputFiles() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(f => dataTransfer.items.add(f));
            input.files = dataTransfer.files;
            //input.dispatchEvent(new Event("change", { bubbles: true }));
        }
    }

    static popup(options = {}) {
        const {
            icon = null,
            title = "Notification",
            text = "",
            imgWidth = 100,
            imgHeight = 100,
        } = options;

        const overlay = document.createElement("div");
        overlay.className = "popup-overlay";
        Object.assign(overlay.style, {
            position: "fixed",
            top: 0,
            left: 0,
            width: "100%",
            height: "100%",
            background: "rgba(0, 0, 0, 0.4)",
            display: "flex",
            justifyContent: "center",
            alignItems: "center",
            zIndex: "99999",
            padding: "15px",
            boxSizing: "border-box",
        });

        const box = document.createElement("div");
        box.className = "popup-box";
        Object.assign(box.style, {
            background: "#fff",
            borderRadius: "12px",
            boxShadow: "0 4px 15px rgba(0,0,0,0.2)",
            padding: "25px 30px",
            textAlign: "center",
            maxWidth: "90%",
            width: "350px",
            animation: "popupFadeIn 0.25s ease",
        });

        if (icon) {
            const img = new Image();
            let src = icon;
            if (icon.startsWith("@")) {
                src = "_frontend/assets/" + icon.replace("@", "");
            }
            img.src = src;
            img.width = imgWidth;
            img.height = imgHeight;
            img.style.objectFit = "contain";
            img.style.marginBottom = "10px";
            box.appendChild(img);
        }

        if (title) {
            const h3 = document.createElement("h3");
            h3.textContent = title;
            Object.assign(h3.style, {
                margin: "5px 0 10px",
                fontSize: "1.2rem",
                color: "#333",
                fontWeight: "600",
            });
            box.appendChild(h3);
        }

        if (text) {
            const msg = document.createElement("div");
            msg.innerHTML = text;
            Object.assign(msg.style, {
                fontSize: "0.95rem",
                color: "#555",
                marginBottom: "20px",
            });
            box.appendChild(msg);
        }

        const okBtn = CtrElement.button({
            text: "OKAY",
            bg: "primary",
            style: "padding:8px 20px;border-radius:6px;font-weight:500;"
        }, () => {
            overlay.remove();
        });
        box.appendChild(okBtn);

        overlay.appendChild(box);
        document.body.appendChild(overlay);

        if (!document.getElementById("popup-style")) {
            const style = document.createElement("style");
            style.id = "popup-style";
            style.textContent = `
            @keyframes popupFadeIn {
              from { opacity: 0; transform: scale(0.9); }
              to { opacity: 1; transform: scale(1); }
            }
            @media (max-width: 480px) {
              .popup-box {
                width: 90% !important;
                padding: 20px !important;
              }
            }
          `;
            document.head.appendChild(style);
        }
    }


    static card_section(parent = null, options = {}, cards = [], loadmore = undefined) {
        let {
            bg = "#fff",
            color = "#000",
            rowCards = 4,
            maxCards = 8,
            attributes = {},
            title = "",
            description = "",
        } = options;

        const section = document.createElement("section");
        Object.assign(section.style, {
            background: bg,
            color: color,
            padding: "30px 20px",
            boxSizing: "border-box",
        });

        for (const [key, val] of Object.entries(attributes)) {
            section.setAttribute(key, val);
        }

        if (title || description) {
            const header = document.createElement("div");
            Object.assign(header.style, {
                textAlign: "center",
                marginBottom: "25px",
            });

            if (title) {
                const h2 = document.createElement("h2");
                h2.textContent = title;
                Object.assign(h2.style, {
                    fontSize: "1.8rem",
                    margin: "0 0 10px",
                    color: color,
                });
                header.appendChild(h2);
            }

            if (description) {
                const p = document.createElement("p");
                p.textContent = description;
                Object.assign(p.style, {
                    fontSize: "1rem",
                    color: "#555",
                    margin: 0,
                });
                header.appendChild(p);
            }

            section.appendChild(header);
        }

        const grid = document.createElement("div");
        rowCards++;
        Object.assign(grid.style, {
            display: "grid",
            gridTemplateColumns: `repeat(auto-fill, minmax(${100 / rowCards}%, 1fr))`,
            gap: "20px",
        });
        section.appendChild(grid);

        let allCards = cards || [];
        let visibleCount = 0;

        const createCard = (cardData) => {
            let { title, text, html, content, image, onClick, click, action, align } = cardData;
            onClick = onClick ?? click ?? action ?? undefined;
            text = text ?? html ?? content ?? undefined;
            const card = document.createElement("div");
            card.className = "card";
            let drc = {};
            if (image) {
                drc = { flexDirection: "column" };
            }
            Object.assign(card.style, {
                background: "#fff",
                color: "#333",
                borderRadius: "10px",
                boxShadow: "0 3px 10px rgba(0,0,0,0.1)",
                overflow: "hidden",
                display: "flex",
                justifyContent: "space-between",
                transition: "transform 0.2s ease, box-shadow 0.2s ease",
                cursor: onClick ? "pointer" : "default",
                padding: "10px 0px",
                ...drc
            });

            card.addEventListener("mouseenter", () => {
                card.style.transform = "translateY(-5px)";
                card.style.boxShadow = "0 5px 15px rgba(0,0,0,0.2)";
            });
            card.addEventListener("mouseleave", () => {
                card.style.transform = "";
                card.style.boxShadow = "0 3px 10px rgba(0,0,0,0.1)";
            });

            if (onClick) card.addEventListener("click", onClick);

            if (image) {
                const img = new Image();
                img.src = image.startsWith("@")
                    ? "_frontend/assets/" + image.replace("@", "")
                    : image;
                Object.assign(img.style, {
                    width: "100%",
                    height: "180px",
                    objectFit: "cover",
                    flexShrink: "0",
                });
                card.appendChild(img);
            } else {
                const spacer = document.createElement("div");
                spacer.style.height = "180px";
                card.appendChild(spacer);
            }
            const body = document.createElement("div");
            Object.assign(body.style, {
                padding: "15px",
                flexGrow: "1",
                display: "flex",
                flexDirection: "column",
                justifyContent: "space-between",
            });

            if (title) {
                const h4 = document.createElement("h4");
                h4.textContent = title;
                Object.assign(h4.style, {
                    fontSize: "1.1rem",
                    margin: "0 0 10px",
                    color: color,
                });
                body.appendChild(h4);
            }

            if (text) {
                const content = document.createElement("div");
                if (/<[a-z][\s\S]*>/i.test(text)) content.innerHTML = text;
                else content.textContent = text;
                Object.assign(content.style, {
                    fontSize: "0.95rem",
                    color: "#555",
                });
                body.appendChild(content);
            }
            let alg = align ?? "center";
            if (alg) {
                body.setAttribute("align", alg);
            }

            card.appendChild(body);
            return card;
        };

        let temp = document.createElement("div");
        let btncontainer = document.createElement("div");
        const renderCards = () => {
            grid.innerHTML = "";
            const showCount = Math.min(visibleCount + maxCards, allCards.length);
            if(showCount == 0){
                temp.setAttribute("align", "center");
                Object.assign(temp.style, {
                    padding: "20px",
                    display: "block"
                });
                let tx = document.createElement("span");
                tx.textContent = "No data available";
                btncontainer.style.display = "none";
                temp.appendChild(tx);
                section.appendChild(temp);
            }else{
                temp.style.display = "none";
                btncontainer.style.display = "block";
            }
            
            for (let i = 0; i < showCount; i++) {
                const card = createCard(allCards[i]);
                grid.appendChild(card);
            }
            visibleCount = showCount;

            if (visibleCount < allCards.length) {
                const showMoreBtn = document.createElement("button");
                showMoreBtn.textContent = "Show More";
                showMoreBtn.style.display = "none";
                Object.assign(showMoreBtn.style, {
                    gridColumn: "1 / -1",
                    justifySelf: "center",
                    padding: "10px 20px",
                    border: "none",
                    borderRadius: "6px",
                    background: "#007bff",
                    color: "#fff",
                    cursor: "pointer",
                    fontSize: "1rem",
                    marginTop: "10px",
                });
                showMoreBtn.addEventListener("click", renderCards);
                grid.appendChild(showMoreBtn);
            }
        };

        renderCards();

        if (!document.getElementById("card-section-style")) {
            const style = document.createElement("style");
            style.id = "card-section-style";
            style.textContent = `
            @media (max-width: 768px) {
              section div[style*="grid"] {
                grid-template-columns: 1fr !important;
              }
            }
          `;
            document.head.appendChild(style);
        }

        section.add_card = (cardData) => {
            allCards.push(cardData);
            renderCards();
        };

        if (parent) {
            const parentEl =
                typeof parent === "string" ? document.querySelector(parent) : parent;
            if (parentEl) parentEl.appendChild(section);
        }

        if (loadmore) {
            if (typeof loadmore == "function") {
                makeLoadMore(loadmore);
            } else if (typeof loadmore == "array") {
                if (loadmore.action && typeof loadmore.action == "function") {
                    let txt = loadmore.text || "Load more";
                    let bg = loadmore.bg || "#007bff";
                    let col = loadmore.color || "white";
                    makeLoadMore(loadmore.action, txt, bg, col);
                }
            }

            function makeLoadMore(action, text = "Load more", bg = "#007bff", color = "white") {
                btncontainer.setAttribute("align", "center");
                Object.assign(btncontainer.style, {
                    padding: "20px 0px",
                });
                let btn = document.createElement("button");
                Object.assign(btn.style, {
                    border: "none",
                    padding: "0.3rem 0.8rem",
                    borderRadius: "4px",
                    fontSize: "0.9rem",
                    cursor: "pointer",
                    background: bg,
                    color: color
                });
                btn.textContent = text;
                if (typeof action == "function") {
                    btn.addEventListener("click", () => {
                        action(section, btn);
                    });
                }
                btncontainer.append(btn);
                section.append(btncontainer);
            }
        }

        return section;
    }

    set_attribute(array) {
        if (array) {
            for (let a in array) {
                this.elem.setAttribute(a, array[a]);
            }
        }
    }
}

export default CtrElement;