class Popmodal {
    /**
     * Usage:
     * 
     * PHP: (class popmodal default showing, remove if preview)
      <div id="pmodal" class="popmodal">
        //html here
      </div>

       JS:
       let modal = Popmodal.init("#pmodal");
       modal.show();
       modal.hide();
     */
    static styleId = "popmodal-style-ctrx";
    static instances = {};

    static ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("link");
        style.id = this.styleId;
        style.setAttribute("rel", "stylesheet");
        style.setAttribute("href", "/views/code/src/style/popmodal.css");

        document.head.appendChild(style);
    }

    static init(selector, options = {}) {
        if (!selector) {
            console.error("Popmodal: selector is required");
            return null;
        }

        let id = selector;
        if (selector.startsWith("#")) {
            id = selector.substring(1);
        }

        if (this.instances[id]) {
            return this.instances[id];
        }

        let element = document.getElementById(id);
        if (!element) {
            element = document.querySelector(selector);
            if (!element) {
                console.error(`Popmodal: element "${selector}" not found`);
                return null;
            }
        }

        let elWidth = element.offsetWidth;

        if (element.classList && element.classList == "popmodal") {
            options.autoOpen = false;
        }

        let existingOverlay = element.closest('.popmodal-overlay');
        if (existingOverlay) {
            return this.instances[id];
        }

        this.ensureStyle();

        const overlay = document.createElement("div");
        overlay.className = "popmodal-overlay";

        const modal = document.createElement("div");
        modal.className = `popmodal ${options.class || ''}`;
        modal.id = id;

        const header = document.createElement("div");
        header.className = "popmodal-header";

        const title = document.createElement("span");
        title.className = "popmodal-title";
        title.innerText = options.title || "CTR-X";

        const closeBtn = document.createElement("button");
        closeBtn.className = "popmodal-close";
        closeBtn.innerHTML = "×";
        closeBtn.setAttribute("aria-label", "Close modal");

        const titleContainer = document.createElement("div");

        if (options.icon) {
            const ic = document.createElement("span");
            ic.className = options.icon;
            ic.style.paddingRight = "5px";
            titleContainer.appendChild(ic);
        }

        titleContainer.appendChild(title);

        header.appendChild(titleContainer);
        header.appendChild(closeBtn);

        const body = document.createElement("div");
        body.className = "popmodal-body";

        const originalContent = element.innerHTML;
        body.innerHTML = originalContent;
        let applyWidth = options.width || options.applyWidth || undefined;
        if(options.bg){
            body.style.background = options.bg;
        }
        if(options.padding && typeof options.padding == "string"){
            body.style.padding = options.padding;
        }
        if (applyWidth == undefined || applyWidth == true) {
            if(elWidth){
                modal.style.width = `${elWidth}px`;
            }else{
                modal.style.width = "unset";
            }
        } else {
            if (applyWidth && typeof applyWidth == "number") {
                modal.style.width = `${options.applyWidth}%`;
            }
            if (applyWidth && typeof applyWidth == "string") {
                modal.style.width = applyWidth;
            }
            if (applyWidth && (typeof applyWidth == "boolean" && applyWidth == false)) {
                modal.style.width = "95%";
            }
        }

        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }

        const footer = document.createElement("div");
        footer.className = "popmodal-footer";

        const footerText = document.createElement("span");
        footerText.className = "popmodal-footer-text";
        footerText.innerHTML = options.footer || options.footerText || "CTRX MODAL";

        footer.appendChild(footerText);

        modal.appendChild(header);
        modal.appendChild(body);
        modal.appendChild(footer);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const instance = {
            element: element,
            overlay: overlay,
            modal: modal,
            body: body,
            header: header,
            title: title,
            closeBtn: closeBtn,
            footer: footer,
            footerText: footerText,
            id: id,
            _onOpenCallback: null,
            _onCloseCallback: null,
            _onCancelCallback: null,

            show() {
                overlay.style.display = 'flex';
                overlay.offsetHeight;
                overlay.classList.add("show");
                document.body.style.overflow = 'hidden';

                if (typeof this._onOpenCallback === 'function') {
                    this._onOpenCallback(this);
                }
                return this;
            },

            hide() {
                overlay.classList.remove("show");
                setTimeout(() => {
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                    if (typeof this._onCloseCallback === 'function') {
                        this._onCloseCallback(this);
                    }
                }, 300);
                return this;
            },

            close() {
                return this.hide();
            },

            toggle() {
                if (overlay.classList.contains("show")) {
                    this.hide();
                } else {
                    this.show();
                }
                return this;
            },

            destroy() {
                this.hide();
                if (overlay.parentNode) {
                    overlay.remove();
                }
                delete Popmodal.instances[this.id];
                return this;
            },

            isVisible() {
                return overlay.classList.contains("show");
            },

            onOpen(callback) {
                if (typeof callback === "function") {
                    this._onOpenCallback = callback;
                }
                return this;
            },

            onClose(callback) {
                if (typeof callback === "function") {
                    this._onCloseCallback = callback;
                }
                return this;
            },

            onCancel(callback) {
                if (typeof callback === "function") {
                    this._onCancelCallback = callback;
                }
                return this;
            },

            setContent(html) {
                this.body.innerHTML = html;
                return this;
            },

            setTitle(text) {
                this.title.innerText = text;
                return this;
            },

            setFooterText(text) {
                this.footerText.innerHTML = text;
                return this;
            },

            showFooter(show = true) {
                this.footer.style.display = show ? 'flex' : 'none';
                return this;
            }
        };

        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            instance.hide();
            if (typeof instance._onCancelCallback === 'function') {
                instance._onCancelCallback(instance);
            }
        });

        if (options.closeOnOverlayClick !== false) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    instance.hide();
                    if (typeof instance._onCancelCallback === 'function') {
                        instance._onCancelCallback(instance);
                    }
                }
            });
        }

        if (options.closeOnEscape !== false) {
            const escapeHandler = (e) => {
                if (e.key === 'Escape' && instance.isVisible()) {
                    instance.hide();
                    if (typeof instance._onCancelCallback === 'function') {
                        instance._onCancelCallback(instance);
                    }
                }
            };
            document.addEventListener('keydown', escapeHandler);
            instance._escapeHandler = escapeHandler;
        }

        if (options.autoOpen !== false) {
            instance.show();
        }

        Popmodal.instances[id] = instance;
        return instance;
    }

    static get(selector) {
        let id = selector;
        if (selector.startsWith("#")) {
            id = selector.substring(1);
        }
        return this.instances[id] || null;
    }

    static closeAll() {
        Object.keys(this.instances).forEach(key => {
            this.instances[key].hide();
        });
        return this;
    }

    static destroyAll() {
        Object.keys(this.instances).forEach(key => {
            this.instances[key].destroy();
        });
        this.instances = {};
        return this;
    }
}

if (typeof module !== "undefined" && module.exports) {
    module.exports = Popmodal;
}

export default Popmodal;