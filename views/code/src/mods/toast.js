class CtrTOAST {
    constructor() {
        this.containerId = "ctr-toast-container";
        this.isFirstToast = true;
        this.ensureContainer();
        this.injectResponsiveStyle();
    }

    ensureContainer() {
        if (!document.getElementById(this.containerId)) {
            const container = document.createElement("div");
            container.id = this.containerId;

            container.style.position = "fixed";
            container.style.left = "50%";
            container.style.transform = "translate(-50%, -50%)";
            container.style.top = "50%";

            container.style.zIndex = "99999999";
            container.style.display = "flex";
            container.style.flexDirection = "column";
            container.style.alignItems = "center";
            container.style.gap = "10px";

            // IMPORTANT: smooth movement when switching center -> top
            container.style.transition = "top 2.7s ease, transform 0.6s ease";

            document.body.appendChild(container);
        }
    }

    moveToTop() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        container.style.top = "20px";
        container.style.transform = "translateX(-50%)";
    }

    injectResponsiveStyle() {
        if (document.getElementById("ctr-toast-style")) return;

        const style = document.createElement("style");
        style.id = "ctr-toast-style";
        style.innerHTML = `
            .ctr-toast {
                width: 100%;
                max-width: 820px;
                transform-origin: top center;
            }

            @media (max-width: 1024px) {
                .ctr-toast { max-width: 360px; }
            }

            @media (max-width: 768px) {
                .ctr-toast { max-width: 100%; }
            }
        `;
        document.head.appendChild(style);
    }

    fire({ text = "", bg = "#333", color = "#fff", icon = "", duration = 3000, effect = "top", click }) {
        const container = document.getElementById(this.containerId);

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
        toast.style.transition = "all 0.45s ease";

        // FIXED click check
        if (typeof click === "function") {
            toast.addEventListener("click", () => click(toast));
        }

        // entrance effect
        switch (effect) {
            case "bottom": toast.style.transform = "translateY(30px)"; break;
            case "left": toast.style.transform = "translateX(-50px)"; break;
            case "right": toast.style.transform = "translateX(50px)"; break;
            case "bounce": toast.style.transform = "scale(0.6)"; break;
            case "flip": toast.style.transform = "rotateX(90deg)"; break;
            default: toast.style.transform = "translateY(-20px)";
        }

        const contentWrap = document.createElement("div");
        contentWrap.style.display = "flex";
        contentWrap.style.alignItems = "center";
        contentWrap.style.gap = "8px";

        if (icon) {
            const iconElem = document.createElement("span");
            iconElem.innerHTML = icon.startsWith("&#") ? icon : icon;
            iconElem.style.fontSize = "18px";
            contentWrap.appendChild(iconElem);
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
            this.removeToast(toast, effect);
        };

        toast.appendChild(contentWrap);
        toast.appendChild(closeBtn);

        container.appendChild(toast);

        // FIRST TOAST CENTER EFFECT
        if (this.isFirstToast) {
            this.isFirstToast = false;

            setTimeout(() => {
                this.moveToTop();
            }, 300); // small delay so user sees center pop
        }

        // animate in
        requestAnimationFrame(() => {
            toast.style.opacity = "1";
            toast.style.transform = "translate(0,0) scale(1) rotate(0)";
        });

        if (duration > 0) {
            setTimeout(() => this.removeToast(toast, effect), duration);
        }
    }

    removeToast(toast, effect) {
        if (!toast) return;

        toast.style.opacity = "0";

        switch (effect) {
            case "bottom": toast.style.transform = "translateY(30px)"; break;
            case "left": toast.style.transform = "translateX(-50px)"; break;
            case "right": toast.style.transform = "translateX(50px)"; break;
            case "bounce": toast.style.transform = "scale(0.6)"; break;
            case "flip": toast.style.transform = "rotateX(90deg)"; break;
            default: toast.style.transform = "translateY(-20px)";
        }

        setTimeout(() => toast.remove(), 450);
    }

    reset() {
        document.querySelectorAll(".ctr-toast").forEach(t => {
            t.style.display = "none";
        });
    }

    success(input) {
        const defaults = {
            text: "",
            bg: "#28a745",
            icon: "&#10004;",
            duration: 6000,
            effect: "top"
        };

        const opts = typeof input === "string"
            ? { ...defaults, text: input }
            : { ...defaults, ...input };

        this.fire(opts);
    }

    ok(input) { this.success(input); }

    error(input) {
        const defaults = {
            text: "",
            bg: "#dc3545",
            icon: "&#10060;",
            duration: 6000,
            effect: "top"
        };

        const opts = typeof input === "string"
            ? { ...defaults, text: input }
            : { ...defaults, ...input };

        this.fire(opts);
    }

    err(input) { this.error(input); }

    warning(input) {
        const defaults = {
            text: "",
            bg: "#fd7e14",
            icon: "&#9888;",
            duration: 6000,
            effect: "top"
        };

        const opts = typeof input === "string"
            ? { ...defaults, text: input }
            : { ...defaults, ...input };

        this.fire(opts);
    }

    warn(input) { this.warning(input); }

    info(input) {
        const defaults = {
            text: "",
            bg: "#0d6efd",
            icon: "&#8505;",
            duration: 6000,
            effect: "top"
        };

        const opts = typeof input === "string"
            ? { ...defaults, text: input }
            : { ...defaults, ...input };

        this.fire(opts);
    }

    dark(input) {
        const defaults = {
            text: "",
            bg: "#343a40",
            icon: "&#128640;",
            duration: 6000,
            effect: "top"
        };

        const opts = typeof input === "string"
            ? { ...defaults, text: input }
            : { ...defaults, ...input };

        this.fire(opts);
    }
}

const TOAST = new CtrTOAST();
const Toast = TOAST;

if (typeof window !== "undefined") {
    window.TOAST = TOAST;
    window.Toast = TOAST;
}

if (typeof module !== "undefined" && module.exports) {
    module.exports = TOAST;
}

export default Toast;