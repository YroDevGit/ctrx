class SHLOADING {
    
    constructor(theme = "light") {
        this.theme = theme; 
        this.loaderId = "shloader-overlay";
        this.styleId = "shloader-style";
        this.ensureStyle();
    }

    ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("style");
        style.id = this.styleId;
        style.innerHTML = `
            .shloader-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                transition: opacity 0.3s ease;
                opacity: 0;
                background: rgb(0,0,0,0.5);
            }
            .shloader {
                width: 80px;
                aspect-ratio: 1;
                display: grid;
                place-items: center;
                border-radius: 50%;
                position: relative;
                --spinner-color: #d70000ff;
                --bg-light: #f3f3f3;
                --bg-dark: #1e1e1e;
                background: var(--bg-light);
            }
            .shloader.dark {
                background: var(--bg-dark);
            }

            .shloader::before {
                content: "";
                position: absolute;
                inset: 0;
                background: var(--spinner-color);
                clip-path: polygon(
                    100% 50%,64.14% 64.14%,50% 100%,35.86% 64.14%,
                    0 50%,35.86% 35.86%,50% 0,64.14% 35.86%
                );
                -webkit-mask: radial-gradient(circle 8px, transparent 92%, #000);
                animation: shspin 1.5s linear infinite;
                border-radius: 50%;
            }

            .shloader::after {
                content: "";
                position: absolute;
                width: 4px;
                height: 40%;
                background: var(--spinner-color);
                border-radius: 4px;
                box-shadow: 0 0 10px var(--spinner-color);
            }

            @keyframes shspin {
                to { transform: rotate(1turn); }
            }
        `;
        document.head.appendChild(style);
    }

    load(show = true) {
        const existing = document.getElementById(this.loaderId);

        if (show) {
            if (existing) return;

            const overlay = document.createElement("div");
            overlay.id = this.loaderId;
            overlay.className = "shloader-overlay";

            const loader = document.createElement("div");
            loader.className = "shloader";
            if (this.theme === "dark") {
                loader.classList.add("dark");
            }

            overlay.appendChild(loader);
            document.body.appendChild(overlay);

            requestAnimationFrame(() => {
                overlay.style.opacity = "1";
            });

        } else {
            if (existing) {
                existing.style.opacity = "0";
                setTimeout(() => existing.remove(), 300);
            }
        }
    }

    setTheme(theme) {
        this.theme = theme;
        const loader = document.querySelector(`#${this.loaderId} .shloader`);
        if (loader) {
            if (theme === "dark") {
                loader.classList.add("dark");
            } else {
                loader.classList.remove("dark");
            }
        }
    }
}

const SHULOADING = new SHLOADING();
const SHULoading = SHLOADING;

if (typeof window !== "undefined") {
    window.SHULOADING = SHULOADING;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = SHULOADING;
}

export default SHULoading;
