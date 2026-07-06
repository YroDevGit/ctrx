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
    static styleId = "popmodal-style";
    static instances = {};

    static ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("style");
        style.id = this.styleId;
        style.textContent = `
            .popmodal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.6);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                opacity: 0;
                transition: opacity 0.3s ease;
                padding: 20px;
            }

            .popmodal-overlay.show {
                display: flex;
                opacity: 1;
            }

            .popmodal {
                width: 95%;
                max-width: 550px;
                max-height: 90vh;
                background: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0,0,0,0.25);
                transform: scale(0.95);
                opacity: 0;
                transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                display: flex;
                flex-direction: column;
            }

            .popmodal-overlay.show .popmodal {
                transform: scale(1);
                opacity: 1;
            }

            .popmodal-header {
                padding: 20px 24px;
                background: #ffffff;
                border-bottom: 2px solid #f0f0f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-shrink: 0;
                min-height: 60px;
            }

            .popmodal-title {
                font-size: 18px;
                font-weight: 600;
                color: #1a1a1a;
                margin: 0;
                flex: 1;
            }

            .popmodal-close {
                border: none;
                background: transparent;
                border-radius: 50%;
                width: 36px;
                height: 36px;
                font-size: 24px;
                cursor: pointer;
                color: #999;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.25s ease;
                line-height: 1;
                flex-shrink: 0;
                margin-left: 12px;
                padding: 0;
                position: relative;
            }

            .popmodal-close::before {
                content: '';
                position: absolute;
                inset: 0;
                border-radius: 50%;
                background: #f5f5f5;
                transform: scale(0);
                transition: transform 0.25s ease;
                z-index: -1;
            }

            .popmodal-close:hover {
                color: #1a1a1a;
                transform: rotate(90deg);
            }

            .popmodal-close:hover::before {
                transform: scale(1);
            }

            .popmodal-close:active {
                transform: rotate(90deg) scale(0.9);
            }

            .popmodal-body {
                padding: 24px;
                overflow-y: auto;
                flex: 1;
                background: #fafafa;
                max-height: 60vh;
            }

            .popmodal-body::-webkit-scrollbar {
                width: 6px;
            }

            .popmodal-body::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }

            .popmodal-body::-webkit-scrollbar-thumb {
                background: #d0d0d0;
                border-radius: 10px;
            }

            .popmodal-body::-webkit-scrollbar-thumb:hover {
                background: #b0b0b0;
            }

            .popmodal-footer {
                padding: 12px 24px;
                background: #ffffff;
                border-top: 1px solid #f0f0f0;
                flex-shrink: 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                min-height: 50px;
            }

            .popmodal-footer-text {
                font-size: 12px;
                color: #999;
                font-weight: 400;
                letter-spacing: 0.3px;
            }

            .popmodal-footer-text span {
                color: #666;
                font-weight: 500;
            }

            @media (max-width: 640px) {
                .popmodal-overlay {
                    padding: 12px;
                }

                .popmodal {
                    width: 98%;
                    border-radius: 10px;
                    max-height: 95vh;
                }

                .popmodal-header {
                    padding: 16px 18px;
                    min-height: 50px;
                }

                .popmodal-title {
                    font-size: 16px;
                }

                .popmodal-body {
                    padding: 16px;
                    max-height: 50vh;
                    min-height: 80px;
                }

                .popmodal-close {
                    width: 32px;
                    height: 32px;
                    font-size: 20px;
                }

                .popmodal-footer {
                    padding: 10px 16px;
                    flex-direction: column;
                    gap: 6px;
                    text-align: center;
                }

                .popmodal-footer-text {
                    font-size: 11px;
                }
            }

            @media (max-width: 480px) {
                .popmodal-overlay {
                    padding: 8px;
                }

                .popmodal {
                    border-radius: 8px;
                }

                .popmodal-body {
                    padding: 12px;
                    max-height: 40vh;
                    min-height: 60px;
                }

                .popmodal-header {
                    padding: 12px 14px;
                    min-height: 44px;
                }

                .popmodal-title {
                    font-size: 15px;
                }
            }
        `;

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

        if(element.classList && element.classList =="popmodal"){
            options.autoOpen = false;
        }

        // Check if already wrapped
        let existingOverlay = element.closest('.popmodal-overlay');
        if (existingOverlay) {
            return this.instances[id];
        }

        this.ensureStyle();

        // Create overlay
        const overlay = document.createElement("div");
        overlay.className = "popmodal-overlay";

        // Create modal container
        const modal = document.createElement("div");
        modal.className = `popmodal ${options.class || ''}`;
        modal.id = id;

        // Create header
        const header = document.createElement("div");
        header.className = "popmodal-header";

        const title = document.createElement("span");
        title.className = "popmodal-title";
        title.innerText = options.title || "CTR-X";

        const closeBtn = document.createElement("button");
        closeBtn.className = "popmodal-close";
        closeBtn.innerHTML = "×";
        closeBtn.setAttribute("aria-label", "Close modal");

        header.appendChild(title);
        header.appendChild(closeBtn);

        // Create body
        const body = document.createElement("div");
        body.className = "popmodal-body";

        // Move the content from the original element to the body
        const originalContent = element.innerHTML;
        body.innerHTML = originalContent;

        // Remove the original element from DOM
        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }

        // Create footer
        const footer = document.createElement("div");
        footer.className = "popmodal-footer";

        const footerText = document.createElement("span");
        footerText.className = "popmodal-footer-text";
        footerText.innerHTML = options.footerText || 'Powered by <span>CTR-X Popmodal</span>';

        footer.appendChild(footerText);

        // Assemble modal
        modal.appendChild(header);
        modal.appendChild(body);
        modal.appendChild(footer);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Instance object
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
                overlay.offsetHeight; // Trigger reflow
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

        // Close button handler
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            instance.hide();
            if (typeof instance._onCancelCallback === 'function') {
                instance._onCancelCallback(instance);
            }
        });

        // Overlay click handler
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

        // Escape key handler
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

        // Auto open
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

// Browser global
if (typeof window !== "undefined") {
    window.Popmodal = Popmodal;
}

// Node.js export
if (typeof module !== "undefined" && module.exports) {
    module.exports = Popmodal;
}

export default Popmodal;