class CtrNLoading {
    constructor() {
        this.bodyColor = "#f3f3f3";
        this.spinnerColor = "#3498db";
        this.zindex = "9999";
        this.loaderId = "custom-loader-overlay";
        this.styleId = "custom-loader-style";
        this.percentId = "custom-loader-percent";
        this.ensureStyle();
    }

    ensureStyle() {
        if (!document.getElementById(this.styleId)) {
            const style = document.createElement("style");
            style.id = this.styleId;
            style.innerHTML = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                #${this.loaderId} {
                    transition: opacity 0.3s ease;
                }
                #${this.percentId} {
                    position: absolute;
                    top: 70px; /* below spinner */
                    font-size: 18px;
                    color: white;
                    font-weight: bold;
                    text-align: center;
                    width: 100%;
                }
            `;
            document.head.appendChild(style);
        }
    }

    load(show = true) {
        const existingOverlay = document.getElementById(this.loaderId);

        if (show) {
            if (existingOverlay) return;

            const overlay = document.createElement("div");
            overlay.id = this.loaderId;
            overlay.style.position = "fixed";
            overlay.style.top = "0";
            overlay.style.left = "0";
            overlay.style.width = "100%";
            overlay.style.height = "100%";
            overlay.style.background = "rgba(0,0,0,0.5)";
            overlay.style.display = "flex";
            overlay.style.alignItems = "center";
            overlay.style.justifyContent = "center";
            overlay.style.zIndex = this.zindex;
            overlay.style.opacity = "0";

            const spinnerWrapper = document.createElement("div");
            spinnerWrapper.style.position = "relative";

            const spinner = document.createElement("div");
            spinner.style.width = "60px";
            spinner.style.height = "60px";
            spinner.style.border = "6px solid " + this.bodyColor;
            spinner.style.borderTop = "6px solid " + this.spinnerColor;
            spinner.style.borderRadius = "50%";
            spinner.style.animation = "spin 1s linear infinite";

            const percentText = document.createElement("div");
            percentText.id = this.percentId;
            percentText.innerText = "";

            spinnerWrapper.appendChild(spinner);
            spinnerWrapper.appendChild(percentText);

            overlay.appendChild(spinnerWrapper);
            document.body.appendChild(overlay);

            requestAnimationFrame(() => {
                overlay.style.opacity = "1";
            });

        } else {
            if (existingOverlay) {
                const percentText = document.getElementById(this.percentId);
                percentText.innerText = "";
                existingOverlay.style.opacity = "0";
                setTimeout(() => existingOverlay.remove(), 300);
            }
        }
    }

    text(value) {
        const overlay = document.getElementById(this.loaderId);
        const percentText = document.getElementById(this.percentId);
        if (overlay && percentText && overlay.style.opacity !== "0") {
            percentText.innerText = value;
        }
    }
}

const NLOADING = new CtrNLoading();
const NLoading = NLOADING;

if (typeof window !== "undefined") {
    window.NLOADING = NLOADING;
    window.NLoading = NLOADING;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = NLOADING;
    module.exports = NLoading;
}

export default NLoading;
