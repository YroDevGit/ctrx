/**
 * Ussage:
 * 
 * let editor = await TextEditor.init({
    element: "#comment-input"
});
 */

class TextEditor {
    static styleId = "ctrx-editor-style-ctrx";
    static scriptId = "ctrx-editor-script-ctrx";

    static ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const link = document.createElement("link");
        link.id = this.styleId;
        link.rel = "stylesheet";
        link.href = "/views/code/src/style/quil/quil.css";
        document.head.appendChild(link);
    }

    static ensureScript() {
        const existing = document.getElementById(this.scriptId);

        if (existing) {
            return new Promise((resolve) => {
                if (window.Quill) {
                    resolve();
                    return;
                }

                existing.addEventListener("load", resolve, { once: true });
            });
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.id = this.scriptId;
            script.type = "module";
            script.src = "/views/code/src/style/quil/quil.js";

            script.addEventListener("load", resolve, { once: true });
            script.addEventListener("error", reject, { once: true });

            document.head.appendChild(script);
        });
    }

    static async init(config = {}) {
        this.ensureStyle();
        await this.ensureScript();

        const {
            theme = "snow",
            element,
            selector
        } = config;

        const container = element || (selector ? document.querySelector(selector) : null);
        
        if (!container) {
            throw new Error("CtrxEditor: No element or selector provided");
        }

        const quill = new Quill(container, {
            theme,
            modules: {
                toolbar: [
                    ["bold", "italic", "underline"],
                    ["link", "image"],
                    [
                        { list: "ordered" },
                        { list: "bullet" }
                    ]
                ]
            }
        });

        return {
            quill,
            
            get value(){
                return quill.root.innerHTML;
            },

            getValue() {
                return quill.root.innerHTML;
            },
            
            get clear() {
                quill.root.innerHTML = '';
            },

            clearValue(){
                quill.root.innerHTML = '';
            },
            
            setValue(value) {
                quill.root.innerHTML = value;
            },
            
            get getText() {
                return quill.getText();
            },
            
            get getLength() {
                return quill.getLength();
            },
            
            enable(enable = true) {
                return quill.enable(enable);
            },
            
            get disable() {
                return quill.disable();
            },
            
            get destroy() {
                return quill.destroy();
            }
        };
    }
}

export default TextEditor;