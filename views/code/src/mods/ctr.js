class CtrClass {

    constructor(rootpath = "") {
        this.global_root = rootpath;
        this.frontend = "?page=";
        this.backend = "?be=";
        this.func = "?funcpage=";
    }

    page($page = "", params = {}) {
        if (!$page || $page == "/") {
            return "/";
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

    scroll_to_top(top = 0, behavior = "smooth"){
        window.scrollTo({
            top: top,
            behavior: behavior
        });
    }

    scroll_to_bottom(reduce = 0, behavior = "smooth"){
        window.scrollTo({
            top: document.body.scrollHeight - reduce,
            behavior: behavior
        });
    }

    reverse_object(object){
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