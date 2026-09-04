import CtrDATE from "../date";
import CImagePicker from "../picker/imagepicker";
import ImageSelector from "../picker/imageselector";
import Validator from "../validator";

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

    static styleId = "tmodal-style-ctrx";

    static ensureStyle() {

        if (document.getElementById(this.styleId)) return;

        const style = document.createElement("link");

        style.id = this.styleId;
        style.setAttribute("rel", "stylesheet");
        style.setAttribute("href", "/views/code/src/style/tmodal.css");

        document.head.appendChild(style);
    }

    static errStr(str = null, errorString = "err_t_") {
        if (!str) return errorString;
        if (typeof str == "string") {
            return `${errorString}${str}`;
        }
    }

    static errStrId(str = null, errorString = "err_t_") {
        if (!str) return `#${errorString}`;
        if (typeof str == "string") {
            return `#${errorString}${str}`;
        }
    }

    static resetErrorStr(errorClass = "tmodal_error_text") {
        let elm = undefined;
        if (errorClass.startsWith(".")) {
            elm = document.querySelectorAll(errorClass);
        } else {
            elm = document.querySelectorAll(`.${errorClass}`);
        }
        elm.forEach(element => {
            element.innerHTML = "";
        });
    }

    static clearFieldErrors() {
        document.querySelectorAll('.tmodal-input.tmodal-error, .tmodal-textarea.tmodal-error, .tmodal-select.tmodal-error').forEach(el => {
            el.classList.remove('tmodal-error');
        });
    }

    static buildValidationRules(fieldConfig) {
        const rules = [];
        if (Array.isArray(fieldConfig.validation)) {
            fieldConfig.validation.forEach(rule => {
                if (typeof rule === 'string') {
                    rules.push(rule);
                } else if (Array.isArray(rule)) {
                    rules.push({ name: rule[0], value: rule[1] });
                } else if (typeof rule === 'object') {
                    rules.push(rule);
                }
            });
            return rules;
        }

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

            if (validation.min) rules.push({ name: 'min', value: validation.min });
            if (validation.label) rules.push({ name: "label", value: validation.label });
            if (validation.max) rules.push({ name: 'max', value: validation.max });
            if (validation.minChars) rules.push({ name: 'minChars', value: validation.minChars });
            if (validation.maxChars) rules.push({ name: 'maxChars', value: validation.maxChars });
            if (validation.length) rules.push({ name: 'length', value: validation.length });
            if (validation.equal) rules.push({ name: 'equal', value: validation.equal });
            if (validation.regex) rules.push({ name: 'regex', value: validation.regex });
            if (validation.startsWith) rules.push({ name: 'startsWith', value: validation.startsWith });
            if (validation.endsWith) rules.push({ name: 'endsWith', value: validation.endsWith });
            if (validation.contain) rules.push({ name: 'contain', value: validation.contain });
            if (validation.exclude) rules.push({ name: 'exclude', value: validation.exclude });
            if (validation.in) rules.push({ name: 'in', value: validation.in });
            if (validation.notIn) rules.push({ name: 'notIn', value: validation.notIn });
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

        let firstErrorField = null;

        Object.keys(errors).forEach(key => {
            const errorMsg = errors[key];
            if (errorMsg) {
                const input = document.getElementById(key);
                if (input) {
                    input.classList.add('tmodal-error');
                    if (!firstErrorField) {
                        firstErrorField = input;
                    }
                }
                const errorEl = document.getElementById(`err_t_${key}`);
                if (errorEl) {
                    errorEl.textContent = errorMsg;
                }
            }
        });

        if (firstErrorField) {
            setTimeout(() => {
                const modalBody = firstErrorField.closest('.tmodal-body');
                if (modalBody) {
                    const scrollTop = firstErrorField.offsetTop - modalBody.offsetTop - (modalBody.clientHeight / 2) + (firstErrorField.offsetHeight / 2);
                    modalBody.scrollTo({
                        top: Math.max(0, scrollTop),
                        behavior: 'smooth'
                    });
                }
                firstErrorField.focus({
                    preventScroll: true
                });
            }, 100);
        }
    }

    static init(config = {}) {

        this.ensureStyle();

        const old = document.getElementById(config.id);

        if (old) {
            old.parentElement.remove();
        }

        const overlay = document.createElement("div");

        overlay.className = "tmodal-overlay";

        const modal = document.createElement("div");

        modal.className = `tmodal ${config.class || ""}`;
        modal.id = config.id || "tmodal";
        config.form_id = config.form_id ?? "tmodal-form";

        const instance = {
            _submitCallback: null,
            _cancelCallback: null,
            _type: null,
            _titleElement: null,
            _originalTitle: config.title || "CTRX MODAL",
            _currentTitle: null,

            setMeta(metaData) {
                if (typeof metaData == "string" || typeof metaData == "number" || typeof metaData == "boolean") {
                    this._type = metaData;
                    return this;
                }
                if (!metaData) return this;
                if (!this._type) {
                    this._type = {};
                }
                Object.keys(metaData).forEach(key => {
                    this._type[key] = metaData[key];
                });
                return this;
            },

            setTitle(title) {
                if (title) {
                    this._currentTitle = title;
                    if (this._titleElement) {
                        this._titleElement.innerHTML = title;
                    }
                }
                return this;
            },

            getTitle() {
                return this._currentTitle || this._originalTitle;
            },

            resetTitle() {
                this._currentTitle = null;
                if (this._titleElement) {
                    this._titleElement.innerHTML = this._originalTitle;
                }
                return this;
            },

            resetMeta() {
                this._type = null;
                return this;
            },

            getMeta(key = null) {
                if (!key) {
                    return this._type;
                }
                return this._type?.[key] ?? null;
            },

            get meta() {
                return this._type;
            },

            edit(data, meta, title = "Edit"){
                this.setTitle(title);
                this.setMeta(meta);
                this.show(data);
            },

            setValue(data) {
                const form = this.form;
                if (!form) return this;

                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if(input.type == "file"){
                        let subti = form.querySelector(`#${key}`);
                        if(subti){
                            subti.value = data[key] || "";
                            return;
                        }
                    }
                    if (input) {
                        input.value = data[key] || "";
                    }
                });
                return this;
            },

            resetForm(options = {}) {
                const form = this.form;
                if (!form) return this;

                const {
                    clearErrors = true,
                    clearMeta = false,
                    resetTitle = false
                } = options;

                form.reset();

                if (clearErrors) {
                    TModal.clearFieldErrors();
                    TModal.resetErrorStr();
                    Validator.reset();
                }

                if (clearMeta) {
                    this.resetMeta();
                }

                if (resetTitle) {
                    this.resetTitle();
                }

                return this;
            },

            displayErrors(errors, autoReset = true) {
                const formElement = this.form;

                if (!formElement) {
                    console.error('TModal: Form not found for displaying errors');
                    return;
                }

                if (autoReset) {
                    TModal.clearFieldErrors();
                    TModal.resetErrorStr();
                }

                let firstErrorField = null;

                Object.keys(errors).forEach(fieldName => {
                    const errorMsg = errors[fieldName];
                    if (!errorMsg) return;

                    const input = formElement.querySelector(`#${fieldName}`);
                    if (input) {
                        input.classList.add('tmodal-error');
                        if (!firstErrorField) {
                            firstErrorField = input;
                        }
                    } else {
                        console.error(`TModal: Input with ID '${fieldName}' not found`);
                    }

                    const errorEl = formElement.querySelector(`#err_t_${fieldName}`);
                    if (errorEl) {
                        errorEl.textContent = errorMsg;
                    } else {
                        console.error(`TModal: Error element with ID 'err_t_${fieldName}' not found`);
                    }
                });

                if (firstErrorField) {
                    setTimeout(() => {
                        const modalBody = firstErrorField.closest('.tmodal-body');
                        if (modalBody) {
                            const scrollTop = firstErrorField.offsetTop - modalBody.offsetTop - (modalBody.clientHeight / 2) + (firstErrorField.offsetHeight / 2);
                            modalBody.scrollTo({
                                top: Math.max(0, scrollTop),
                                behavior: 'smooth'
                            });
                        }
                        firstErrorField.focus({
                            preventScroll: true
                        });
                    }, 100);
                }
            },

            show(data = null, title = null) {
                if (title) {
                    this.setTitle(title);
                }

                if (data && typeof data === 'object' && !Array.isArray(data)) {
                    this.resetForm();
                    this.setValue(data);
                }

                if (!this._currentTitle) {
                    this.resetTitle();
                }

                overlay.classList.add("tmodal-show");
                return this;
            },

            get open() {
                this.show();
                return this;
            },

            get openNew() {
                this.resetForm();
                this.show();
                return this;
            },

            get close() {
                this.hide();
                return this;
            },

            hide(resetMeta = true, resetTitle = true, resetForm = false) {
                overlay.classList.remove("tmodal-show");

                if (resetMeta) {
                    this.resetMeta();
                }

                if (resetTitle) {
                    this.resetTitle();
                }

                if (resetForm) {
                    this.resetForm({ clearMeta: false, resetTitle: false });
                }

                TModal.resetErrorStr();
                TModal.clearFieldErrors();
                Validator.reset();
                return this;
            },

            remove() {
                overlay.remove();
                return this;
            },

            form_submit(callback) {
                if (typeof callback === "function") {
                    this._submitCallback = callback;
                }
                return this;
            },

            get form_id() {
                return config.form_id;
            },

            get form() {
                return document.getElementById(config.form_id);
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

        const header = document.createElement("div");

        header.className = "tmodal-header";

        const title = document.createElement("span");

        title.innerHTML = config.title || "CTRX MODAL";
        instance._titleElement = title;

        const closeBtn = document.createElement("button");

        closeBtn.className = "tmodal-close";
        closeBtn.innerHTML = "&times;";

        closeBtn.onclick = () => instance.hide();

        header.appendChild(title);
        header.appendChild(closeBtn);

        const body = document.createElement("div");

        body.className = "tmodal-body";

        const form = document.createElement("form");

        form.id = config.form_id || "";

        instance.form = form;

        const formData = config.form || {};

        Object.keys(formData).forEach(async (key) => {

            let field = formData[key];

            if (typeof field === "string") {

                field = {
                    type: field
                };
            }

            if (field.hidden) {
                field.type = "hidden";
            }

            const wrapper = document.createElement("div");

            wrapper.className = "tmodal-group";

            let tag = field.tag || "input";
            if (field.type == "textarea") {
                delete field.type;
                tag = "textarea";
            }
            if (field.type == "calendar") {
                delete field.type;
                tag = "calendar";
            }
            if (field.type == "select") {
                delete field.type;
                tag = "select";
            }
            if (field.type == "cimage" || field.type == "chooseimage" || field.type == "imageselector") {
                delete field.type;
                tag = "cimage";
            }
            if (field.type == "imagepicker") {
                delete field.type;
                tag = "imagepicker";
            }

            if (
                field.label !== false &&
                field.type !== "hidden"
            ) {

                const label = document.createElement("label");

                label.className = "tmodal-label";

                label.setAttribute("for", key);

                label.innerHTML =
                    field.label ||
                    key.charAt(0).toUpperCase() + key.slice(1);

                wrapper.appendChild(label);
            }

            const input = document.createElement(tag == "calendar" || tag == "datepicker" || tag == "cimage" || tag == "imagepicker" ? "input" : tag);
            let orgTag = tag;
            input.name = key;
            input.id = key;

            if (tag === "input") {

                input.type = field.type || "text";

                input.className =
                    "tmodal-input " + (field.class || "");
            }

            if (tag == "calendar" || tag == "datepicker") {
                tag = "input";
                field.tag = "input";
                input.type = "text";
                input.className =
                    "tmodal-input tmodal-calendar-input " + (field.class || "");
            }

            if (tag == "cimage") {
                tag = "input";
                field.tag = "input";
                input.type = "text";
                input.className =
                    "tmodal-input tmodal-cimage-input " + (field.class || "");
            }

            if (tag == "imagepicker") {
                tag = "input";
                field.tag = "input";
                input.type = "text";
                input.className =
                    "tmodal-input tmodal-imagepicker-input " + (field.class || "");
            }

            if (tag === "textarea") {

                input.className =
                    "tmodal-textarea " + (field.class || "");
            }

            if (tag == "div" || tag == "span" || tag == "section" || tag == "label") {
                input.className = field.class ?? "";
                if (field.innerHTML) {
                    input.innerHTML = field.innerHTML;
                }
            }

            if (tag === "select") {
                input.className =
                    "tmodal-select " + (field.class || "");
                let opts = field.options;
                if (Array.isArray(opts)) {
                    if (!field.config) {
                        field.config = { value: "value", label: "label" };
                    }
                    let conf = field.config;
                    let value = conf.value ?? "value";
                    let label = conf.label ?? "label";
                    let spl = [];
                    let optx = opts;
                    for (let op in optx) {
                        let separator = conf.separator ?? "";
                        let lbl = "";
                        let lblarr = [];
                        let labl = optx[op][label];
                        if (Array.isArray(label)) {
                            for (let l in label) {
                                lblarr = [...lblarr, optx[op][label[l]]];
                            }
                            lbl = lblarr.join(separator);
                        } else {
                            lbl = labl;
                        }
                        spl[op] = { value: optx[op][value], label: lbl };
                    }
                    if (typeof field?.config.index && field?.config.index == false) {
                        opts = spl;
                    } else {
                        if (field.multiple) {
                            opts = spl;
                        } else {
                            const ind = field?.config?.index ?? field?.index ?? undefined;
                            if (typeof ind == "string" || typeof ind == "number") {
                                opts = [{ value: "", label: `${ind ?? "Select Item"}` }, ...spl];
                            } else if (typeof ind == "object") {
                                opts = [ind, ...spl];
                            } else {
                                opts = [{ value: "", label: "Select Item" }, ...spl];
                            }
                        }
                    }

                    const onchange = field.onchange ?? field.onChange ?? undefined;
                    if (onchange && typeof onchange == "function") {
                        input.addEventListener("change", () => {
                            if (field.multiple) {
                                const selectedOptions = Array.from(input.selectedOptions);
                                const selectedValues = selectedOptions.map(option => ({
                                    label: option.text,
                                    value: option.value
                                }));

                                onchange(input, selectedValues);

                            } else {
                                const label = input?.options[input.selectedIndex]?.text ?? null;
                                let value = input.value ?? null;
                                let labelValue = { label: label, value: value };
                                onchange(input, labelValue);
                            }
                        });
                    }

                    opts.forEach((optk) => {
                        const option_c = document.createElement("option");
                        if (typeof optk === "object") {
                            option_c.value = optk.value || "";
                            option_c.innerHTML = optk.label;
                        } else {
                            option_c.value = optk || "";
                            option_c.innerHTML = optk;
                        }
                        input.appendChild(option_c);
                    });
                    let vval = field.value ?? field.default ?? undefined;
                    if (field.value) {
                        input.value = vval;
                    }
                    if (field.multiple) {
                        input.setAttribute("multiple", "");
                        input.value = "";
                    }
                }
            }

            if (field.required && field.required == true) {
                input.setAttribute("required", "");
            }
            if (field.id) {
                input.setAttribute("id", field.id);
            }
            if (field.attributes) {

                Object.keys(field.attributes).forEach((attr) => {

                    input.setAttribute(
                        attr,
                        field.attributes[attr]
                    );
                });
            }

            if (field.value !== undefined) {
                input.value = field.value;
            }

            let err = document.createElement("div");
            err.className = "tmodal_error_text";
            err.setAttribute("id", `err_t_${input.id}`);
            wrapper.appendChild(input);
            wrapper.appendChild(err);

            form.appendChild(wrapper);
            if (orgTag == "cimage") {
                ImageSelector.init(input, field.config ?? {});
            }
            if (orgTag == "imagepicker") {
                CImagePicker.init(input, field.config ?? {});
            }
        });

        const footer = document.createElement("div");

        footer.className = "tmodal-footer";

        const closeFooterBtn = document.createElement("button");

        closeFooterBtn.type = "button";

        closeFooterBtn.className =
            "tmodal-btn tmodal-btn-close";

        closeFooterBtn.innerText = "Close";

        closeFooterBtn.onclick = () => instance.hide();

        const cancelBtn = document.createElement("button");

        cancelBtn.type = "button";

        cancelBtn.className =
            "tmodal-btn tmodal-btn-cancel";

        cancelBtn.innerText = "Reset";
        cancelBtn.setAttribute("type", "reset");

        cancelBtn.onclick = () => {
            instance.resetForm();
        };

        const submitBtn = document.createElement("button");

        submitBtn.type = "submit";

        submitBtn.className =
            "tmodal-btn tmodal-btn-submit";

        submitBtn.innerText =
            config.submitText || "Submit";

        footer.appendChild(closeFooterBtn);
        footer.appendChild(cancelBtn);
        footer.appendChild(submitBtn);

        form.appendChild(footer);

        form.onsubmit = (e) => {
            e.preventDefault();

            TModal.resetErrorStr();
            TModal.clearFieldErrors();
            Validator.reset();

            const data = {};
            const formData = new FormData(form);

            const multipleSelects = form.querySelectorAll('select[multiple]');
            const multipleSelectNames = new Set();

            multipleSelects.forEach(select => {
                multipleSelectNames.add(select.name);
                data[select.name] = Array.from(select.selectedOptions).map(opt => opt.value);
            });

            formData.forEach((value, key) => {
                if (!multipleSelectNames.has(key)) {
                    if (data[key] !== undefined) {
                        if (!Array.isArray(data[key])) {
                            data[key] = [data[key]];
                        }
                        data[key].push(value);
                    } else {
                        data[key] = value;
                    }
                }
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

        let hasCalendarInpt = document.querySelectorAll(".tmodal-calendar-input");
        if (hasCalendarInpt.length && hasCalendarInpt.length > 0) {
            CtrDATE.datePicker(".tmodal-calendar-input");
        }
        return instance;
    }
}

if (typeof window !== "undefined") {
    window.TModal = TModal;
}

export default TModal;