class TModal {

    static styleId = "tmodal-style";

    static ensureStyle() {

        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("style");

        style.id = this.styleId;

        style.textContent = `
        .tmodal-overlay{
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            opacity: 0;
            transition: .25s ease;
        }

        .tmodal-overlay.show{
            display: flex;
            opacity: 1;
        }

        .error_text{color:red}
        .tmodal{
            width: 95%;
            max-width: 550px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,.25);
            animation: tmodalIn .2s ease;
            font-family: Arial, sans-serif;
        }

        @keyframes tmodalIn{
            from{
                transform: scale(.95);
                opacity: 0;
            }
            to{
                transform: scale(1);
                opacity: 1;
            }
        }

        .tmodal-header{
            padding: 14px 18px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
            font-weight: bold;
        }

        .tmodal-close{
            border: none;
            background: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .tmodal-body{
            padding: 18px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .tmodal-group{
            margin-bottom: 14px;
        }

        .tmodal-label{
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .tmodal-input,
        .tmodal-textarea,
        .tmodal-select{
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .tmodal-textarea{
            resize: vertical;
        }

        .tmodal-footer{
            padding-top: 14px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .tmodal-btn{
            padding: 10px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .tmodal-btn-close{
            background: #ddd;
        }

        .tmodal-btn-submit{
            background: #111;
            color: #fff;
        }
        `;

        document.head.appendChild(style);
    }

    static errStr(str = null, errorString = "err_"){
        if(! str) return errorString;
        if(typeof str == "string"){
            return `${errorString}${str}`;
        }
    }

    static errStrId(str = null, errorString = "err_"){
        if(! str) return `#${errorString}`;
        if(typeof str == "string"){
            return `#${errorString}${str}`;
        }
    }

    static resetErrorStr(errorClass = "error_text"){
        let elm = undefined;
        if(errorClass.startsWith(".")){
            elm = document.querySelectorAll(errorClass);
        }else{
            elm = document.querySelectorAll(`.${errorClass}`);
        }
        elm.forEach(element => {
            element.innerHTML = "";
        });
    }

    static init(config = {}) {

        this.ensureStyle();

        const old = document.getElementById(config.id);

        if (old) {
            old.parentElement.remove();
        }

        /* overlay */
        const overlay = document.createElement("div");

        overlay.className = "tmodal-overlay";

        /* modal */
        const modal = document.createElement("div");

        modal.className = `tmodal ${config.class || ""}`;
        modal.id = config.id || "tmodal";

        /* instance */
        const instance = {

            _submitCallback: null,

            show() {
                overlay.classList.add("show");
            },

            hide() {
                overlay.classList.remove("show");
            },

            remove() {
                overlay.remove();
            },

            form_submit(callback) {

                if (typeof callback === "function") {
                    this._submitCallback = callback;
                }

                return this;
            },

            overlay,
            modal,
            form: null
        };

        /* header */
        const header = document.createElement("div");

        header.className = "tmodal-header";

        const title = document.createElement("span");

        title.innerText = config.title || "CTRX MODAL";

        const closeBtn = document.createElement("button");

        closeBtn.className = "tmodal-close";
        closeBtn.innerHTML = "&times;";

        closeBtn.onclick = () => instance.hide();

        header.appendChild(title);
        header.appendChild(closeBtn);

        /* body */
        const body = document.createElement("div");

        body.className = "tmodal-body";

        /* form */
        const form = document.createElement("form");

        form.id = config.form_id || "";

        instance.form = form;

        const formData = config.form || {};

        Object.keys(formData).forEach(async(key) => {

            let field = formData[key];

            /* shortcut string */
            if (typeof field === "string") {

                field = {
                    type: field
                };
            }

            /* hidden shortcut */
            if (field.hidden) {
                field.type = "hidden";
            }

            const wrapper = document.createElement("div");

            wrapper.className = "tmodal-group";

            const tag = field.tag || "input";

            /* label */
            if (
                field.label !== false &&
                field.type !== "hidden"
            ) {

                const label = document.createElement("label");

                label.className = "tmodal-label";

                label.setAttribute("for", key);

                label.innerText =
                    field.label ||
                    key.charAt(0).toUpperCase() + key.slice(1);

                wrapper.appendChild(label);
            }

            /* element */
            const input = document.createElement(tag);

            input.name = key;
            input.id = key;

            /* input */
            if (tag === "input") {

                input.type = field.type || "text";

                input.className =
                    "tmodal-input " + (field.class || "");
            }

            /* textarea */
            if (tag === "textarea") {

                input.className =
                    "tmodal-textarea " + (field.class || "");
            }

            /* select */
            if (tag === "select") {

                input.className =
                    "tmodal-select " + (field.class || "");
                    
                if (Array.isArray(field.options)) {

                    if(field.config){
                        let conf = field.config;
                        let value = conf.value ?? "value";
                        let label = conf.label ?? "label";
                        let spl = [];
                        let opt = field.options;
                        for(let op in opt){
                            let separator = conf.separator ?? "";
                            let lbl = "";
                            let lblarr = [];
                            let labl = opt[op][label];
                            if(Array.isArray(label)){
                                for(let l in label){
                                    lblarr = [...lblarr, opt[op][label[l]]];
                                }
                                lbl = lblarr.join(separator);
                            }else{
                                lbl = labl;
                            }
                            spl[op] = { value: opt[op][value], label: lbl };
                        }
                        if(typeof field?.config.index && field?.config.index == false){
                            field.options = spl;
                        }else{
                            field.options = [{value: "", label: `${field?.config?.index ?? "Select Item"}`},...spl];
                        }
                        
                    }

                    field.options.forEach((opt) => {

                        const option = document.createElement("option");
                        if (typeof opt === "object") {

                            option.value = opt.value;
                            option.textContent = opt.label;

                        } else {

                            option.value = opt;
                            option.textContent = opt;
                        }

                        input.appendChild(option);
                    });
                }
            }

            /* attributes */
            if (field.attributes) {

                Object.keys(field.attributes).forEach((attr) => {

                    input.setAttribute(
                        attr,
                        field.attributes[attr]
                    );
                });
            }

            /* value */
            if (field.value !== undefined) {
                input.value = field.value;
            }

            let err = document.createElement("div");
            err.className ="error_text";
            err.setAttribute("id", `err_${input.id}`);
            wrapper.appendChild(input);
            wrapper.appendChild(err);
            
            form.appendChild(wrapper);
        });

        /* footer */
        const footer = document.createElement("div");

        footer.className = "tmodal-footer";

        /* close button */
        const closeFooterBtn = document.createElement("button");

        closeFooterBtn.type = "button";

        closeFooterBtn.className =
            "tmodal-btn tmodal-btn-close";

        closeFooterBtn.innerText = "Close";

        closeFooterBtn.onclick = () => instance.hide();

        /* submit button */
        const submitBtn = document.createElement("button");

        submitBtn.type = "submit";

        submitBtn.className =
            "tmodal-btn tmodal-btn-submit";

        submitBtn.innerText =
            config.submitText || "Submit";

        footer.appendChild(closeFooterBtn);
        footer.appendChild(submitBtn);

        /* append footer INSIDE form */
        form.appendChild(footer);

        /* submit */
        form.onsubmit = (e) => {

            e.preventDefault();

            const data = {};

            new FormData(form).forEach((value, key) => {
                data[key] = value;
            });

            /* init submit */
            if (typeof config.submit === "function") {
                config.submit(data, form, instance);
            }

            /* dynamic submit */
            if (typeof instance._submitCallback === "function") {
                instance._submitCallback(data, form, instance);
            }
        };

        body.appendChild(form);

        modal.appendChild(header);
        modal.appendChild(body);

        overlay.appendChild(modal);

        document.body.appendChild(overlay);

        /* click outside */
        overlay.addEventListener("click", (e) => {

            if (e.target === overlay) {
                instance.hide();
            }
        });

        return instance;
    }
}

if (typeof window !== "undefined") {
    window.TModal = TModal;
}

export default TModal;