class CtrTOAST {
    constructor() {
        this.containerId = "ctr-toast-container";
        this.ensureContainer();
        this.injectResponsiveStyle();
    }

    ensureContainer() {
        if (!document.getElementById(this.containerId)) {
            const container = document.createElement("div");
            container.id = this.containerId;
            container.style.position = "fixed";
            container.style.top = "20px";
            container.style.left = "50%";
            container.style.transform = "translateX(-50%)";
            container.style.zIndex = "9999";
            container.style.display = "flex";
            container.style.flexDirection = "column";
            container.style.alignItems = "center";
            container.style.gap = "10px";
            document.body.appendChild(container);
        }
    }

    injectResponsiveStyle() {
        if (document.getElementById("ctr-toast-style")) return;
        const style = document.createElement("style");
        style.id = "ctr-toast-style";
        style.innerHTML = `
            .ctr-toast {
                width: 100%;
                max-width:820px;
            }

            @media (max-width: 1024px) {
                .ctr-toast {
                    max-width: 360px;
                }
            }

            @media (max-width: 768px) {
                .ctr-toast {
                    max-width: 100%;
                }
            }

            @media (max-width: 480px) {
                .ctr-toast {
                    max-width: 100%; 
                }
            }
        `;
        document.head.appendChild(style);
    }

    fire({ text = "", bg = "#333", color = "#fff", icon = "", duration = 3000, effect = "top", click }) {
        const toast = document.createElement("div");
        toast.className = "ctr-toast";
        toast.style.minWidth = "240px";
        toast.style.padding = "12px 16px";
        toast.style.background = bg;
        toast.style.color = color;
        toast.style.borderRadius = "8px";
        toast.style.boxShadow = "0 4px 8px rgba(0,0,0,0.25)";
        toast.style.display = "flex";
        toast.style.alignItems = "center";
        toast.style.justifyContent = "space-between";
        toast.style.fontFamily = "sans-serif";
        toast.style.opacity = "0";
        toast.style.transition = "all 0.5s ease";

        if (click) {
            if (! typeof click == "function") {
                console.err("Toast 'click' should be a function");
                return;
            }
            toast.addEventListener("click", () => {
                click(toast);
            });
        }

        switch (effect) {
            case "bottom": toast.style.transform = "translateY(30px)"; break;
            case "left": toast.style.transform = "translateX(-50px)"; break;
            case "right": toast.style.transform = "translateX(50px)"; break;
            case "bounce": toast.style.transform = "scale(0.5)"; break;
            case "flip": toast.style.transform = "rotateY(90deg)"; break;
            default: toast.style.transform = "translateY(-20px)";
        }

        const contentWrap = document.createElement("div");
        contentWrap.style.display = "flex";
        contentWrap.style.alignItems = "center";
        contentWrap.style.gap = "8px";

        if (icon) {
            if (icon.startsWith("fa")) {
                const iconElem = document.createElement("i");
                iconElem.className = icon;
                contentWrap.appendChild(iconElem);
            } else if (icon.startsWith("&#")) {
                const iconSpan = document.createElement("span");
                iconSpan.innerHTML = icon;
                iconSpan.style.fontSize = "18px";
                contentWrap.appendChild(iconSpan);
            } else {
                const iconSpan = document.createElement("span");
                iconSpan.textContent = icon;
                iconSpan.style.fontSize = "18px";
                contentWrap.appendChild(iconSpan);
            }
        }

        const textNode = document.createElement("span");
        textNode.innerHTML = text;
        contentWrap.appendChild(textNode);

        const closeBtn = document.createElement("span");
        closeBtn.textContent = "×";
        closeBtn.style.cursor = "pointer";
        closeBtn.style.marginLeft = "12px";
        closeBtn.style.fontWeight = "bold";
        closeBtn.onclick = (e) => {
            e.stopPropagation();
            this.removeToast(toast, effect)
        };

        toast.appendChild(contentWrap);
        toast.appendChild(closeBtn);

        document.getElementById(this.containerId).appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = "1";
            toast.style.transform = "translateY(0) translateX(0) scale(1) rotateY(0)";
        }, 50);

        if (duration > 0) {
            setTimeout(() => this.removeToast(toast, effect), duration);
        }
    }

    reset() {
        let allToast = document.querySelectorAll(".ctr-toast");
        allToast.forEach(tst => {
            tst.style.display = 'none';
        });
    }

    removeToast(toast, effect) {
        if (!toast) return;
        toast.style.opacity = "0";

        switch (effect) {
            case "bottom": toast.style.transform = "translateY(30px)"; break;
            case "left": toast.style.transform = "translateX(-50px)"; break;
            case "right": toast.style.transform = "translateX(50px)"; break;
            case "bounce": toast.style.transform = "scale(0.5)"; break;
            case "flip": toast.style.transform = "rotateY(90deg)"; break;
            default: toast.style.transform = "translateY(-20px)";
        }

        setTimeout(() => toast.remove(), 500);
    }

    success(input) {
        const defaults = { text: "", bg: "#28a745", icon: "&#10004;", duration: 6000, effect: "top" };
        const opts = typeof input === "string" ? { ...defaults, text: input } : { ...defaults, ...input };
        this.fire(opts);
    }

    ok(input) {
        this.success(input);
    }

    error(input) {
        const defaults = { text: "", bg: "#dc3545", icon: "&#10060;", duration: 6000, effect: "top" };
        const opts = typeof input === "string" ? { ...defaults, text: input } : { ...defaults, ...input };
        this.fire(opts);
    }

    err(input) {
        this.error(input);
    }

    warning(input) {
        const defaults = { text: "", bg: "#fd7e14", icon: "&#9888;", duration: 6000, effect: "top" };
        const opts = typeof input === "string" ? { ...defaults, text: input } : { ...defaults, ...input };
        this.fire(opts);
    }

    warn(input) {
        this.warning(input);
    }

    info(input) {
        const defaults = { text: "", bg: "#0d6efd", icon: "&#128712;", duration: 6000, effect: "top" };
        const opts = typeof input === "string" ? { ...defaults, text: input } : { ...defaults, ...input };
        this.fire(opts);
    }

    dark(input) {
        const defaults = { text: "", bg: "#343a40", icon: "&#128640;", duration: 6000, effect: "top" };
        const opts = typeof input === "string" ? { ...defaults, text: input } : { ...defaults, ...input };
        this.fire(opts);
    }
}

const TOAST = new CtrTOAST();
const Toast = TOAST;

if (typeof window !== "undefined") {
    window.TOAST = TOAST;
    window.Toast = TOAST;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = TOAST;
    module.exports = Toast;
}

export default Toast;
