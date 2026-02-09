class OrbitLoader {
    constructor(theme = "blue") {
        this.theme = theme;
        this.loaderId = "orbit-loader-overlay";
        this.styleId = "orbit-loader-style";
        this.ensureStyle();
    }

    ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("style");
        style.id = this.styleId;
        style.innerHTML = `
            :root {
                --orbit-color-blue: #0066ff;
                --orbit-color-shadow: #00aeff;
                --orbit-bg-light: rgba(0, 0, 0, 0.1);
                --orbit-bg-dark: rgba(255, 255, 255, 0.1);
            }

            /* --- Overlay and Container --- */
            #${this.loaderId} {
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 0;
                background: rgba(0, 0, 0, 1); /* Dark semi-transparent background */
                transition: opacity 0.3s ease;
            }

            .loader {
                position: relative; /* Changed from absolute to relative for better encapsulation */
                width: 150px;
                height: 150px;
                background: transparent;
                border: 3px solid rgba(0, 102, 255, 0.1);
                border-radius: 50%;
                text-align: center;
                line-height: 150px;
                font-family: sans-serif;
                font-size: 20px;
                color: var(--orbit-color-blue);
                letter-spacing: 2px;
                text-transform: uppercase;
                text-shadow: 0 0 10px var(--orbit-color-blue);
                box-shadow: 0 0 20px rgba(0, 0, 0, .15);
            }

            /* --- Outer Spinning Ring (::before on .loader) --- */
            .loader::before {
                content: '';
                position: absolute;
                top: -3px;
                left: -3px;
                width: 100%;
                height: 100%;
                border: 3px solid transparent;
                border-top: 3px solid var(--orbit-color-blue);
                border-right: 3px solid var(--orbit-color-blue);
                border-radius: 50%;
                animation: animateC 2s linear infinite;
            }

            /* --- Inner Orbiting Arm (span) --- */
            .loader span {
                display: block;
                position: absolute;
                top: calc(50% - 2px);
                left: 50%;
                width: 50%;
                height: 4px;
                background: transparent;
                transform-origin: left;
                animation: animate 2s linear infinite;
            }

            /* --- Arm Tip/Orb (::before on .loader span) --- */
            .loader span::before {
                content: '';
                position: absolute;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background: var(--orbit-color-shadow);
                top: -6px;
                right: -8px;
                box-shadow: 0 0 20px 5px var(--orbit-color-blue);
            }

            /* --- Keyframes --- */
            @keyframes animateC {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            @keyframes animate {
                0% { transform: rotate(45deg); }
                100% { transform: rotate(405deg); }
            }
        `;
        document.head.appendChild(style);
    }

    // Creates the HTML structure for the loader
    createLoaderElement() {
        const loader = document.createElement("div");
        loader.className = "loader";
        loader.textContent = "Loading";

        const span = document.createElement("span");
        loader.appendChild(span);

        return loader;
    }

    loadss(text = "Loading") {
        if (document.getElementById(this.loaderId)) return;

        const overlay = document.createElement("div");
        overlay.id = this.loaderId;

        const loaderElement = this.createLoaderElement();
        loaderElement.textContent = text;
        
        overlay.appendChild(loaderElement);
        document.body.appendChild(overlay);

        requestAnimationFrame(() => overlay.style.opacity = "1");
    }

    load(bool = true, text = "Loading"){
        if(bool){
            this.loadss(text);
        }else{
            this.unload();
        }
    }

    unload() {
        const existing = document.getElementById(this.loaderId);
        if (existing) {
            existing.style.opacity = "0";

            setTimeout(() => existing.remove(), 300);
        }
    }
}

const OLOADING = new OrbitLoader("blue");
const OLoading = OLOADING;

if (typeof window !== "undefined") {
    window.OLOADING = OLOADING;
    window.OLoading = OLOADING;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = OLOADING;
    module.exports = OLoading;
}

export default OLoading;