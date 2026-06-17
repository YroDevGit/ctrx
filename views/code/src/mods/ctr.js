class CtrClass {

    constructor(rootpath = "") {
        this.global_root = rootpath;
        this.frontend = "";
        this.backend = "";
        this.func = "";
    }

    page($page = "", params = {}) {
        if (!$page || $page == "/") {
            return "/";
        }
        if (!$page.startsWith("/")) {
            $page = "/" + $page;
        }
        let url = this.frontend + $page;
        if (typeof params === "object" && Object.keys(params).length > 0) {
            const query = Object.entries(params)
                .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
                .join("&");
            url += "?" + query;
        }
        return url;
    }

    generateRandom() {
        const now = new Date();

        const date =
            now.getFullYear().toString().slice(-2) +
            String(now.getMonth() + 1).padStart(2, '0') +
            String(now.getDate()).padStart(2, '0') +
            String(now.getHours()).padStart(2, '0') +
            String(now.getMinutes()).padStart(2, '0') +
            String(now.getSeconds()).padStart(2, '0') +
            String(now.getMilliseconds()).padStart(3, '0');

        const random = Math.random().toString(36).substring(2, 8);

        return `CTR${date}${random}`;
    }

    async generateHash(max = 16) {
        const id = this.generateRandom();
        const buffer = await crypto.subtle.digest(
            'SHA-256',
            new TextEncoder().encode(id)
        );
        const hash = Array.from(new Uint8Array(buffer))
            .map(b => b.toString(max).padStart(2, '0'))
            .join('');

        return hash;
    }

    async shortHash(max = 16) {
        const id = this.generateRandom();

        const buffer = await crypto.subtle.digest(
            'SHA-256',
            new TextEncoder().encode(id)
        );

        return Array.from(new Uint8Array(buffer))
            .map(b => b.toString(max).padStart(2, '0'))
            .join('')
            .substring(0, max)
            .toUpperCase();
    }

    generateUnique() {
        const now = Date.now();
        const uuid = crypto.randomUUID().replace(/-/g, '');

        return `CTR${now}${uuid}`;
    }

    backend($be = "", params = {}) {
        let url = this.backend + $be;
        if (typeof params === "object" && Object.keys(params).length > 0) {
            const query = Object.entries(params)
                .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
                .join("&");
            url += (url.includes("?") ? "&" : "&") + query;
        }
        return url;
    }

    redirect(page = "", params = {}) {
        if (!page.startsWith("/")) {
            page = "/" + page;
        }
        window.location.href = this.page(page, params);
    }

    reload(hardRefresh = false) {
        if (hardRefresh) {
            caches.keys().then(names => {
                for (let name of names) caches.delete(name);
            }).then(() => {
                location.reload(true);
            });
        }
        else {
            window.location.reload();
        }
    }

    funcpage($page = "", params = {}) {
        let url = this.func + $page;
        if (typeof params === "object" && Object.keys(params).length > 0) {
            const query = Object.entries(params)
                .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
                .join("&");
            url += (url.includes("?") ? "&" : "&") + query;
        }
        return url;
    }

    dom_loaded(callable) {
        window.addEventListener("DOMContentLoaded", callable());
    }

    set_html(selector, strhtml) {
        let elements = [];

        if (typeof selector == "string") {
            if (selector.charAt(0) === "#") {
                const element = document.getElementById(selector.substring(1));
                if (element) {
                    elements.push(element);
                }
            } else if (selector.charAt(0) === ".") {
                elements = Array.from(document.querySelectorAll(selector));
            } else {
                const element = document.getElementById(selector);
                if (element) {
                    elements.push(element);
                }
            }
        } else if (selector instanceof HTMLElement) {
            elements.push(selector);
        } else if (Array.isArray(selector)) {
            selector.forEach(sel => {
                elements.push(sel);
            });
        }

        if (elements.length > 0) {
            elements.forEach(element => {
                if (strhtml instanceof HTMLElement) {
                    element.innerHTML = "";
                    element.appendChild(strhtml);
                } else {
                    element.innerHTML = strhtml;
                }
            });
        } else {
            console.warn(`No elements found for selector: "${selector}"`);
        }
    }

    add_html(selector, strhtml) {
        let elements = [];

        if (typeof selector == "string") {
            if (selector.charAt(0) === "#") {
                const element = document.getElementById(selector.substring(1));
                if (element) {
                    elements.push(element);
                }
            } else if (selector.charAt(0) === ".") {
                elements = Array.from(document.querySelectorAll(selector));
            } else {
                const element = document.getElementById(selector);
                if (element) {
                    elements.push(element);
                }
            }
        } else if (selector instanceof HTMLElement) {
            elements.push(selector);
        } else if (Array.isArray(selector)) {
            selector.forEach(sel => {
                elements.push(sel);
            });
        }

        if (elements.length > 0) {
            elements.forEach(element => {
                if (strhtml instanceof HTMLElement) {
                    element.appendChild(strhtml);
                } else {
                    element.insertAdjacentHTML('beforeend', strhtml);
                }
            });
        } else {
            console.warn(`No elements found for selector: "${selector}"`);
        }
    }

    loaded(callable) {
        window.addEventListener("DOMContentLoaded", function () {
            callable();
        });
    }

    click(selector, callable) {
        if (typeof selector == "string") {
            let form = null;
            if (selector.charAt(0) === "#" || selector.charAt(0) === ".") {
                form = document.querySelectorAll(selector);
                form.forEach(element => {
                    const attrs = {};
                    for (let attr of element.attributes) {
                        attrs[attr.name] = attr.value;
                    }
                    element.addEventListener("click", function () {
                        callable(element, attrs);
                    });
                });
            } else {
                form = document.getElementById(selector);
                const attrs = {};
                for (let attr of form.attributes) {
                    attrs[attr.name] = attr.value;
                }
                form.addEventListener("click", function () {
                    callable(attrs);
                });
            }
        } else if (selector instanceof HTMLElement) {
            selector.addEventListener("click", () => {
                const attrs = {};
                for (let attr of selector.attributes) {
                    attrs[attr.name] = attr.value;
                }
                callable(attrs);
            })
        }
    }

    to_object(data) {
        if (data instanceof FormData) {
            return Object.fromEntries(data.entries());
        }
        return data;
    }

    scroll_to_element(selector, offset = 0) {
        let elements = [];

        if (typeof selector === "string") {
            elements = Array.from(document.querySelectorAll(selector));
        } else if (selector instanceof HTMLElement) {
            elements = [selector];
        } else if (Array.isArray(selector)) {
            elements = selector.filter(el => el instanceof HTMLElement);
        }

        let el = document.querySelector(selector);
        if (!el) return;

        const y = el.getBoundingClientRect().top + window.pageYOffset - offset;

        window.scrollTo({
            top: y,
            behavior: "smooth"
        });
    }

    scroll_to_top(top = 0, behavior = "smooth") {
        window.scrollTo({
            top: top,
            behavior: behavior
        });
    }

    scroll_to_bottom(reduce = 0, behavior = "smooth") {
        window.scrollTo({
            top: document.body.scrollHeight - reduce,
            behavior: behavior
        });
    }

    reverse_object(object) {
        let reversed = Object.fromEntries(
            Object.entries(object).reverse()
        );
        return reversed;
    }

    submit(selector, callable, clean = true) {
        let elements = [];

        if (typeof selector === "string") {
            elements = Array.from(document.querySelectorAll(selector));
        } else if (selector instanceof HTMLElement) {
            elements = [selector];
        } else if (Array.isArray(selector)) {
            elements = selector.filter(el => el instanceof HTMLElement);
        }

        const handleSubmit = (element) => (event) => {
            event.preventDefault();
            const formData = new FormData(element);
            const dataObject = Object.fromEntries(formData.entries());
            callable(formData, dataObject, event);
        };

        elements.forEach(element => {
            if (element.tagName !== "FORM") return;

            if (clean && element._submitHandler) {
                element.removeEventListener('submit', element._submitHandler);
            }
            const boundHandler = handleSubmit(element);
            element._submitHandler = boundHandler;
            element.addEventListener('submit', boundHandler);
        });
    }

    apply(...callable) {
        callable.forEach(call => {
            if (typeof call !== "function") {
                return;
            }
            call();
        });
    }

    setOptions(selector, options = [], config = { value: "value", label: "label", onChange: undefined, index: "Select item" }) {
        let val = config.value ?? "value";
        let lab = config.label ?? "label";
        let elements = document.querySelectorAll(selector);
        if (config.onChange && typeof config.onChange == "function") {
            elements.forEach(element => {
                element.addEventListener("change", () => {
                    config.onChange(element);
                });
            });
        }

        if (Array.isArray(options)) {
            if (typeof config.index != "boolean") {
                config.index = config.index ?? "Select item";
                this.set_html(selector, `<option value=''>${config.index}</option>` ?? "<option value=''>Select item</option>");
            }
            for (let op in options) {
                let row = options[op];
                this.add_html(selector, `<option value='${row[val]}'>${row[lab]}</option>`)
            }
        }
    }

    base_url(path = null) {
        if (path) {
            return path.startsWith("/") ? window.location.origin + path : window.location.origin + "/" + path;
        }
        return window.location.origin;
    }

    form_data(selector) {
        let form = null;
        if (selector.charAt(0) === "#" || selector.charAt(0) === ".") {
            form = document.querySelector(selector);
        } else {
            form = document.querySelector(`#${selector}`);
        }

        if (!form) return null;

        const formData = new FormData(form);

        const dataObject = {};
        formData.forEach((value, key) => {
            dataObject[key] = value;
        });

        return dataObject;
    }

    open_window(url, target = null){
        if(! target){
            window.location.href = url;
        }else{
            if(typeof target == "string"){
                window.open(url, target);
            }
        }
        
    }

    get_selected(seletor, type = null) {
        const select = document.querySelector(seletor);
        const value = select.value ?? null;
        const label = select?.options[select.selectedIndex]?.text ?? null;
        if(type == null){
            return { value: value, label: label };
        }
        if(typeof type == "string"){
            if(type == "value"){
                return value;
            }
            if(type == "label"){
                return label;
            }
            return null;
        }
    }

    form_object(selector) {
        const element = document.querySelector(selector);
        const formdata = new FormData(element);
        return formdata;
    }

    storage_set(name, item) {
        localStorage.setItem(name, item);
    }

    storage_get(name) {
        localStorage.getItem(name);
    }

    storage_clear() {
        localStorage.clear();
    }

    storage_remove(name) {
        localStorage.removeItem(name);
    }
    $(selector) {
        return document.querySelector(selector);
    }
    $all(selector) {
        return document.querySelectorAll(selector);
    }
    load(callable) {
        window.addEventListener("DOMContentLoaded", function () {
            callable();
        });
    }

    value(selector) {
        return document.querySelector(selector).value;
    }

    child(selector) {
        return document.querySelector(selector).innerHTML;
    }

    parentAndChild(selector) {
        return document.querySelector(selector).outerHTML;
    }

    errStr(str = null, errorString = "err_") {
        if (!str) return errorString;
        if (typeof str == "string") {
            return `${errorString}${str}`;
        }
    }
    errStrId(str = null, errorString = "err_") {
        if (!str) return `#${errorString}`;
        if (typeof str == "string") {
            return `#${errorString}${str}`;
        }
    }

    errStrSet(str = null, value, errorString = "err_") {
        if (str && typeof str == "string") {
            if (str.startsWith("#")) {
                document.querySelector(`${str}`).innerHTML = value;
            } else {
                document.querySelector(`#${errorString}${str}`).innerHTML = value;
            }

        }
    }

    resetErrorStr(errorClass = "error_text") {
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
}

const CTR = new CtrClass();
const Ctr = CTR;

if (typeof window !== "undefined") {
    window.Ctr = CTR;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = Ctr;
}

export default Ctr;