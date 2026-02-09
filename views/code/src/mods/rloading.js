class CtrRLoading {
    constructor() {
        this.loaderId = "rinnegan-loader-overlay";
        this.styleId = "rinnegan-style";
        this.ensureStyle();
    }

    ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("style");
        style.id = this.styleId;
        style.innerHTML = `
            #${this.loaderId} {
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(0,0,0,0.7);
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .rinnegan-loader {
                width: 120px;
                aspect-ratio: 1;
                border-radius: 50%;
                background: radial-gradient(circle at center, #7d3fc4 20%, #4a2078 60%, #1c082c 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow: hidden;
                animation: spin 6s linear infinite;
                box-shadow: 0 0 20px #7d3fc4, inset 0 0 20px #7d3fc4;
            }

            /* outer glowing rings */
            .rinnegan-loader::before,
            .rinnegan-loader::after {
                content: "";
                position: absolute;
                border: 2px solid rgba(255,255,255,0.4);
                border-radius: 50%;
                inset: 15%;
            }
            .rinnegan-loader::after {
                inset: 30%;
            }

            /* inner rings */
            .rinnegan-ring {
                position: absolute;
                border: 5px solid rgba(5, 5, 5);
                border-radius: 50%;
            }
            .rinnegan-ring.r1 { inset: 45%; }
            .rinnegan-ring.r2 { inset: 60%; font}
            .rinnegan-ring.r3 { inset: 75%; }

            @keyframes spin {
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

            const loader = document.createElement("div");
            loader.className = "rinnegan-loader";

            // inner rings
            ["r1", "r2", "r3"].forEach(r => {
                const ring = document.createElement("div");
                ring.className = `rinnegan-ring ${r}`;
                loader.appendChild(ring);
            });

            overlay.appendChild(loader);
            document.body.appendChild(overlay);

            requestAnimationFrame(() => overlay.style.opacity = "1");

        } else if (existing) {
            existing.style.opacity = "0";
            setTimeout(() => existing.remove(), 300);
        }
    }
}

const RLOADING = new CtrRLoading();
const RLoading = RLOADING;

if (typeof window !== "undefined") {
    window.RLOADING = RLOADING;
    window.RLoading = RLOADING;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = RLOADING;
    module.exports = RLoading;
}

export default RLoading;