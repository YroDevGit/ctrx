import Validator from "./validator";

/**
 //Use:
const modal = TModal.init({
        title: "Register here",
        id: "modex", 
        form_id: "regForm",
        form: {
            email: {type: "text", label: "Enter email here:", validation:{email:true, maxChar: 50, label: "Email"}},
            //add more fields
        }
    });

modal.form_submit((data, array, form, instance)=>{

});
 */

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
            background: rgba(0,0,0,.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            opacity: 0;
            transition: opacity .3s ease;
        }

        .tmodal-overlay.show{
            display: flex;
            opacity: 1;
        }

        .error_text{
            color: #dc3545;
            font-size: 12px;
            display: block;
            font-weight: 500;
            min-height: 18px;
        }

        .error_text.show{
            display: block;
        }

        .tmodal{
            width: 95%;
            max-width: 550px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            animation: tmodalIn .3s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            transform: scale(0.95);
            opacity: 0;
        }

        .tmodal-overlay.show .tmodal{
            transform: scale(1);
            opacity: 1;
        }

        @keyframes tmodalIn{
            from{
                transform: scale(.95) translateY(10px);
                opacity: 0;
            }
            to{
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .tmodal-header{
            padding: 20px 24px;
            background: #ffffff;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .tmodal-close{
            border: none;
            background: #f5f5f5;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all .2s ease;
            line-height: 1;
        }

        .tmodal-close:hover{
            background: #e8e8e8;
            transform: rotate(90deg);
            color: #1a1a1a;
        }

        .tmodal-body{
            padding: 15px;
            max-height: 70vh;
            overflow-y: auto;
            background: #fafafa;
        }

        .tmodal-body::-webkit-scrollbar{
            width: 6px;
        }

        .tmodal-body::-webkit-scrollbar-track{
            background: #f1f1f1;
            border-radius: 10px;
        }

        .tmodal-body::-webkit-scrollbar-thumb{
            background: #d0d0d0;
            border-radius: 10px;
        }

        .tmodal-body::-webkit-scrollbar-thumb:hover{
            background: #b0b0b0;
        }

        .tmodal-group{
            margin-bottom: 2px;
        }

        .tmodal-label{
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .tmodal-input,
        .tmodal-textarea,
        .tmodal-select{
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all .2s ease;
            background: #ffffff;
            font-family: inherit;
            color: #1a1a1a;
        }

        .tmodal-input.error,
        .tmodal-textarea.error,
        .tmodal-select.error{
            border-color: #dc3545;
            background: #fff5f5;
        }

        .tmodal-input:focus,
        .tmodal-textarea:focus,
        .tmodal-select:focus{
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0,102,204,.1);
        }

        .tmodal-input.error:focus,
        .tmodal-textarea.error:focus,
        .tmodal-select.error:focus{
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220,53,69,.1);
        }

        .tmodal-input:hover,
        .tmodal-textarea:hover,
        .tmodal-select:hover{
            border-color: #bbb;
        }

        .tmodal-textarea{
            resize: vertical;
            min-height: 80px;
        }

        .tmodal-footer{
            padding: 16px 24px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #ffffff;
            border-top: 1px solid #f0f0f0;
        }

        .tmodal-btn{
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all .2s ease;
            font-family: inherit;
        }

        .tmodal-btn-close{
            background: #f5f5f5;
            color: #333;
            border: 1px solid #e0e0e0;
            display:none;
        }

        .tmodal-btn-close:hover{
            background: #e8e8e8;
            transform: translateY(-1px);
        }

        .tmodal-btn-close:active{
            transform: translateY(0);
        }

        .tmodal-btn-cancel{
            background: #dc3545;
            color: #fff;
        }

        .tmodal-btn-cancel:hover{
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220,53,69,.3);
        }

        .tmodal-btn-cancel:active{
            transform: translateY(0);
        }

        .tmodal-btn-submit{
            background: #0066cc;
            color: #fff;
        }

        .tmodal-btn-submit:hover{
            background: #0052a3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,102,204,.3);
        }

        .tmodal-btn-submit:active{
            transform: translateY(0);
        }

        .tmodal-btn-submit:disabled{
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .tmodal{
                width: 98%;
                border-radius: 10px;
            }

            .tmodal-header{
                padding: 16px 18px;
                font-size: 16px;
            }

            .tmodal-body{
                padding: 12px;
            }

            .tmodal-group{
                margin-bottom: 2px;
            }

            .tmodal-footer{
                padding: 16px 18px;
                flex-direction: column-reverse;
            }

            .tmodal-btn{
                width: 100%;
                justify-content: center;
                padding: 12px;
            }
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
            element.classList.remove("show");
        });
    }

    static clearFieldErrors(){
        document.querySelectorAll('.tmodal-input.error, .tmodal-textarea.error, .tmodal-select.error').forEach(el => {
            el.classList.remove('error');
        });
    }

    static buildValidationRules(fieldConfig) {
        const rules = [];

        // Handle array validation
        if (Array.isArray(fieldConfig.validation)) {
            fieldConfig.validation.forEach(rule => {
                if (typeof rule === 'string') {
                    rules.push(rule);
                } else if (Array.isArray(rule)) {
                    rules.push({name: rule[0], value: rule[1]});
                } else if (typeof rule === 'object') {
                    rules.push(rule);
                }
            });
            return rules;
        }

        // Handle object validation
        if (fieldConfig.validation) {
            const validation = fieldConfig.validation;

            if (validation.required) rules.push('required');
            if (validation.email) rules.push('email');
            if (validation.number) rules.push('number');
            if (validation.string) rules.push('string');
            if (validation.alpha) rules.push('alpha');
            if (validation.alphanumeric) rules.push('alphanumeric');
            if (validation.boolean) rules.push('boolean');
            if (validation.url) rules.push('url');
            if (validation.ip) rules.push('ip');
            if (validation.trim) rules.push('trim');
            if (validation.optional) rules.push('optional');
            
            if (validation.min) rules.push({name: 'min', value: validation.min});
            if (validation.label) rules.push({name: "label", value: validation.label});
            if (validation.max) rules.push({name: 'max', value: validation.max});
            if (validation.minChars) rules.push({name: 'minChars', value: validation.minChars});
            if (validation.maxChars) rules.push({name: 'maxChars', value: validation.maxChars});
            if (validation.length) rules.push({name: 'length', value: validation.length});
            if (validation.equal) rules.push({name: 'equal', value: validation.equal});
            if (validation.regex) rules.push({name: 'regex', value: validation.regex});
            if (validation.startsWith) rules.push({name: 'startsWith', value: validation.startsWith});
            if (validation.endsWith) rules.push({name: 'endsWith', value: validation.endsWith});
            if (validation.contain) rules.push({name: 'contain', value: validation.contain});
            if (validation.exclude) rules.push({name: 'exclude', value: validation.exclude});
            if (validation.in) rules.push({name: 'in', value: validation.in});
            if (validation.notIn) rules.push({name: 'notIn', value: validation.notIn});
        }

        return rules;
    }

    static validateForm(formData, formConfig) {
        Validator.reset();
        Validator.set_data(formData);

        let isValid = true;
        const errors = {};

        Object.keys(formConfig).forEach(key => {
            const field = formConfig[key];
            
            if (field.validation) {
                const rules = TModal.buildValidationRules(field);
                const label = field.label || key.charAt(0).toUpperCase() + key.slice(1);
                
                const isOptional = rules.some(r => {
                    if (typeof r === 'string') return r === 'optional';
                    if (typeof r === 'object') return r.name === 'optional';
                    return false;
                });
                
                const value = formData[key];
                
                if (isOptional && (value === undefined || value === null || value === '')) {
                    return;
                }
                
                let validator = Validator.input(key).label(label);

                rules.forEach(rule => {
                    if (typeof rule === 'string') {
                        if (rule !== 'optional') {
                            validator[rule]();
                        }
                    } else if (typeof rule === 'object' && rule.name !== 'optional') {
                        validator[rule.name](rule.value);
                    }
                });

                const result = validator.validate();
                
                if (Validator.failed()) {
                    isValid = false;
                    errors[key] = Validator.field_error(key);
                }
            }
        });

        return { isValid, errors };
    }

    static displayErrors(errors) {
        TModal.clearFieldErrors();

        Object.keys(errors).forEach(key => {
            const errorMsg = errors[key];
            if (errorMsg) {
                // Show error on input only
                const input = document.getElementById(key);
                if (input) {
                    input.classList.add('error');
                }

                // Show error text
                const errorEl = document.getElementById(`err_${key}`);
                if (errorEl) {
                    errorEl.textContent = errorMsg;
                    errorEl.classList.add('show');
                }
            }
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
            _cancelCallback: null,

            show() {
                overlay.classList.add("show");
            },

            hide() {
                overlay.classList.remove("show");
                TModal.resetErrorStr();
                TModal.clearFieldErrors();
                Validator.reset();
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

            onCancel(callback) {
                if (typeof callback === "function") {
                    this._cancelCallback = callback;
                }
                return this;
            },

            overlay,
            modal,
            form: null,
            config: config
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
            if(field.required && field.required == true){
                input.setAttribute("required","");
            }
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

        /* cancel button (red) */
        const cancelBtn = document.createElement("button");

        cancelBtn.type = "button";

        cancelBtn.className =
            "tmodal-btn tmodal-btn-cancel";

        cancelBtn.innerText = "Cancel";

        cancelBtn.onclick = () => {
            if (typeof instance._cancelCallback === "function") {
                instance._cancelCallback(instance);
            }
            instance.hide();
        };

        /* submit button (blue) */
        const submitBtn = document.createElement("button");

        submitBtn.type = "submit";

        submitBtn.className =
            "tmodal-btn tmodal-btn-submit";

        submitBtn.innerText =
            config.submitText || "Submit";

        footer.appendChild(closeFooterBtn);
        footer.appendChild(cancelBtn);
        footer.appendChild(submitBtn);

        /* append footer INSIDE form */
        form.appendChild(footer);

        /* submit */
        form.onsubmit = (e) => {

            e.preventDefault();

            TModal.resetErrorStr();
            TModal.clearFieldErrors();
            Validator.reset();

            const data = {};
            let formData = new FormData(form);
            formData.forEach((value, key) => {
                data[key] = value;
            });

            if (config.form) {
                const validationResult = TModal.validateForm(data, config.form);
                
                if (!validationResult.isValid) {
                    TModal.displayErrors(validationResult.errors);
                    return;
                }
            }

            if (typeof config.submit === "function") {
                config.submit(data, form, instance);
            }

            if (typeof instance._submitCallback === "function") {
                instance._submitCallback(formData, data, form, instance);
            }
        };

        body.appendChild(form);

        modal.appendChild(header);
        modal.appendChild(body);

        overlay.appendChild(modal);

        document.body.appendChild(overlay);

        overlay.addEventListener("click", (e) => {

            if (e.target === overlay) {
                //instance.hide();
            }
        });

        return instance;
    }
}

if (typeof window !== "undefined") {
    window.TModal = TModal;
}

export default TModal;