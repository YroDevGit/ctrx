class CtrBANNER {
    constructor() {
        this.id = "ctr-banner";
        this.ensureStyle();
        this.assets = "_frontend/assets";
    }

    ensureStyle() {
        if (document.getElementById("ctr-banner-style")) return;
        const style = document.createElement("style");
        style.id = "ctr-banner-style";
        style.textContent = `
            #${this.id}-overlay {
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                background: rgba(0,0,0,0.6);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 99999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            #${this.id}-overlay.show { opacity: 1; }
            #${this.id} {
                position: relative;
                border-radius: 10px;
                max-width: 600px;
                width: 90%;
                height: 350px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                background-size: cover;
                background-position: center;
                animation: banner-in 0.3s ease forwards;
            }
            @keyframes banner-in {
                from { transform: scale(0.8); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            #${this.id} .banner-overlay-content {
                position: absolute;
                top: 0; left: 0;
                width: 100%; height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                padding: 20px;
                color: #fff;
                z-index: 2;
            }
            #${this.id} .banner-title {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            #${this.id} .banner-text {
                margin-bottom: 20px;
                font-size: 16px;
                line-height: 1.4;
            }
            #${this.id} .banner-buttons {
                display: flex;
                justify-content: center;
                gap: 10px;
                flex-wrap: wrap;
            }
            #${this.id} .banner-buttons button {
                padding: 8px 16px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
                transition: background 0.2s;
            }
            #${this.id} .banner-close {
                position: absolute;
                top: 10px;
                right: 12px;
                font-size: 18px;
                cursor: pointer;
                background: none;
                border: none;
                color: #fff;
                z-index: 3;
            }
        `;
        document.head.appendChild(style);
    }

    fire({ title = "", text = "", image = "", className = "", style = "", buttons = {}, click, close }) {
        const old = document.getElementById(this.id + "-overlay");
        if (old) old.remove();

        const overlay = document.createElement("div");
        overlay.id = this.id + "-overlay";

        const banner = document.createElement("div");
        banner.id = this.id;

        if (className) banner.classList.add(className);
        if (style) banner.setAttribute("style", banner.getAttribute("style") + ";" + style);

        if (image) {
            banner.style.backgroundImage = `url('${this.assets}/${image}')`;
        }

        const content = document.createElement("div");
        content.className = "banner-overlay-content";

        if (title) {
            const t = document.createElement("div");
            t.className = "banner-title";
            t.innerHTML = title;
            content.appendChild(t);
        }

        if (text) {
            const txt = document.createElement("div");
            txt.className = "banner-text";
            txt.innerHTML = text;
            content.appendChild(txt);
        }
       
        if (buttons && Object.keys(buttons).length > 0) {
            const btnWrap = document.createElement("div");
            btnWrap.className = "banner-buttons";

            for (let key in buttons) {
                const cfg = buttons[key];
                const btn = document.createElement("button");
                btn.innerText = cfg.text || key;

                if (cfg.class) btn.className = cfg.class;

                if (cfg.attributes) {
                    for (let attr in cfg.attributes) {
                        btn.setAttribute(attr, cfg.attributes[attr]);
                    }
                }

                btn.addEventListener("click", (e) => {
                    if (cfg.click) cfg.click(e);
                    overlay.remove();
                });

                btnWrap.appendChild(btn);
            }

            content.appendChild(btnWrap);
        }

        if(click){
            if(! typeof click == "function"){
                console.error("BANNER click should be a function");
                return;
            }
            overlay.addEventListener("click", ()=>{
                click(overlay);
            })
        }

        banner.appendChild(content);

        const closeBtn = document.createElement("button");
        closeBtn.className = "banner-close";
        closeBtn.innerHTML = "&times;";
        closeBtn.style.fontSize = "25px";
        if(close){
            if(close.style){
                closeBtn.setAttribute(closeBtn.getAttribute("style")+";"+close.style)
            }
        }
        closeBtn.onclick = (e) => {
            e.stopPropagation();
            overlay.remove();
        }
        banner.appendChild(closeBtn);

        overlay.appendChild(banner);
        document.body.appendChild(overlay);

        setTimeout(() => overlay.classList.add("show"), 10);
    }
}

const BANNER = new CtrBANNER();
const Banner = BANNER;

if (typeof window !== "undefined") {
    window.BANNER = BANNER;
    window.Banner = BANNER;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = BANNER;
    module.exports = Banner;
}
/**
 * CodeTazer Banner
 */
export default Banner;
