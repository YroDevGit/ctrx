class CtrBLoading {
    constructor(theme = "dark") {
        this.theme = theme;
        this.loaderId = "ctr-loader-overlay";
        this.styleId = "ctr-loader-style";
        this.ensureStyle();
    }

    ensureStyle() {
        if (document.getElementById(this.styleId)) return;

        const DOTS = 12;
        const DUR = 2;
        const Z_INDICES = [5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6];

        const dotStyles = Array.from({length: DOTS}).map((_, i) => {
            const angle = 360 / DOTS * i;
            const delay = DUR * (-(i) / DOTS);
            const zIndex = Z_INDICES[i];
            
            return `
                .pl__dot:nth-child(${i + 1}) { 
                    transform: rotate(-${angle}deg) translateX(5em) rotate(${angle}deg);
                    z-index: ${zIndex}; /* From the hardcoded array */
                    animation-delay: ${delay}s;
                }
                .pl__dot:nth-child(${i + 1}):before,
                .pl__dot:nth-child(${i + 1}):after {
                    animation-delay: ${delay}s;
                }
            `;
        }).join("\n");

        const style = document.createElement("style");
        style.id = this.styleId;
        style.innerHTML = `
            :root {
                --bg-dark: hsl(223, 10%, 15%);
                --fg-dark: hsl(223, 10%, 90%);
                --bg-light: hsl(223, 20%, 95%);
                --fg-light: hsl(223, 15%, 20%);
                --fg-t: hsla(223, 10%, 90%, 0.5);
                --primary1: hsl(223, 90%, 55%);
                --primary2: hsl(223, 90%, 65%);
                --trans-dur: 0.3s;
                --hue: 223; /* Added for background calculation */
            }

            #${this.loaderId} {
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .pl {
                box-shadow: 2em 0 2em hsla(0,0%,0%,0.2) inset, -2em 0 2em hsla(0,0%,100%,0.1) inset;
                display: flex;
                justify-content: center;
                align-items: center;
                position: relative;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                /* Keeps the 3D perspective, no continuous rotation */
                transform: rotateX(30deg) rotateZ(45deg);
                width: 15em;
                height: 15em;
                border-radius: 50%;
            }

            .pl__dot {
                animation-name: shadow;
                animation-duration: ${DUR}s;
                animation-iteration-count: infinite;
                box-shadow: 0.1em 0.1em 0 0.1em hsl(0,0%,0%), 0.3em 0 0.3em hsla(0,0%,0%,0.5);
                top: calc(50% - 0.75em);
                left: calc(50% - 0.75em);
                width: 1.5em;
                height: 1.5em;
                border-radius: 50%;
                position: absolute;
            }
            .pl__dot::before,
            .pl__dot::after {
                content: "";
                display: block;
                position: absolute;
                left: 0;
                width: inherit;
                height: inherit;
                border-radius: inherit;
                animation-duration: ${DUR}s;
                animation-iteration-count: infinite;
                transition: background-color var(--trans-dur);
            }
            .pl__dot::before {
                animation-name: pushInOut1;
                background-color: var(--bg-current);
                box-shadow: 0.05em 0 0.1em hsla(0,0%,100%,0.2) inset;
                height: inherit;
                z-index: 1;
            }
            .pl__dot::after {
                animation-name: pushInOut2;
                background-color: var(--primary1);
                border-radius: 0.75em;
                /* Converted SASS color to use CSS var */
                box-shadow: 0.1em 0.3em 0.2em hsla(0,0%,100%,0.4) inset,
                            0 (-0.4em) 0.2em hsl(var(--hue),10%,20%) inset,
                            0 (-1em) 0.25em hsla(0,0%,0%,0.3) inset;
                bottom: 0;
                clip-path: polygon(0 75%, 100% 75%, 100% 100%, 0 100%);
                height: 3em;
                transform: rotate(-45deg);
                transform-origin: 50% 2.25em;
            }
            
            /* Position dots (12 total) with staggered delay and z-index */
            ${dotStyles}

            .pl__text {
                font-size: 0.75em;
                max-width: 5rem;
                position: relative;
                text-shadow: 0 0 0.1em var(--fg-t);
                transform: rotateZ(-45deg);
            }

            /* Animations for the dot bounce/shadow */
            @keyframes shadow {
                from {
                    animation-timing-function: ease-in;
                    box-shadow: 0.1em 0.1em 0 0.1em hsl(0,0%,0%), 0.3em 0 0.3em hsla(0,0%,0%,0.3);
                }
                25% {
                    animation-timing-function: ease-out;
                    box-shadow: 0.1em 0.1em 0 0.1em hsl(0,0%,0%), 0.8em 0 0.8em hsla(0,0%,0%,0.5);
                }
                50%, to {
                    box-shadow: 0.1em 0.1em 0 0.1em hsl(0,0%,0%), 0.3em 0 0.3em hsla(0,0%,0%,0.3);
                }
            }
            @keyframes pushInOut1 {
                from {
                    animation-timing-function: ease-in;
                    background-color: var(--bg-current); /* Matches the JS 'var(--bg-current)' */
                    transform: translate(0,0);
                }
                25% {
                    animation-timing-function: ease-out;
                    background-color: var(--primary2);
                    transform: translate(-71%,-71%);
                }
                50%, to {
                    background-color: var(--bg-current); /* Matches the JS 'var(--bg-current)' */
                    transform: translate(0,0);
                }
            }
            @keyframes pushInOut2 {
                from {
                    animation-timing-function: ease-in;
                    background-color: var(--bg-current); /* Matches the JS 'var(--bg-current)' */
                    clip-path: polygon(0 75%, 100% 75%, 100% 100%, 0 100%);
                }
                25% {
                    animation-timing-function: ease-out;
                    background-color: var(--primary1);
                    clip-path: polygon(0 25%, 100% 25%, 100% 100%, 0 100%);
                }
                50%, to {
                    background-color: var(--bg-current); /* Matches the JS 'var(--bg-current)' */
                    clip-path: polygon(0 75%, 100% 75%, 100% 100%, 0 100%);
                }
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

            if (this.theme === "dark") {
                overlay.style.background = "var(--bg-dark)";
                overlay.style.color = "var(--fg-dark)";
                overlay.style.setProperty("--bg-current", "var(--bg-dark)");
            } else {
                overlay.style.background = "var(--bg-light)";
                overlay.style.color = "var(--fg-light)";
                overlay.style.setProperty("--bg-current", "var(--bg-light)");
            }

            const loader = document.createElement("div");
            loader.className = "pl";

            for (let i = 0; i < 12; i++) {
                const dot = document.createElement("div");
                dot.className = "pl__dot";
                loader.appendChild(dot);
            }

            // Loading text
            const text = document.createElement("div");
            text.className = "pl__text";
            text.innerText = "Loading…";
            loader.appendChild(text);

            overlay.appendChild(loader);
            document.body.appendChild(overlay);

            requestAnimationFrame(() => overlay.style.opacity = "1");

        } else if (existing) {
            existing.style.opacity = "0";
            setTimeout(() => existing.remove(), 300);
        }
    }
}

const BLOADING = new CtrBLoading("dark");
const BLoading = BLOADING;

if (typeof window !== "undefined") {
    window.BLOADING = BLOADING;
    window.BLoading = BLOADING;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = BLOADING;
    module.exports = BLoading;
}

export default BLoading;