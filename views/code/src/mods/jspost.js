
/**
 * jspost is a javascript post, get and put function created by CodeYro team
 * Author: Tyrone L. Malocon
 * 2025/01/06
 * @param {*}  
 * @param {*}  
 * @param {*}  
 * @returns 
 */
const jserrorcode = -1;
const jssuccesscode = 200;
const jsbaseurl = "http://localhost/basixs";

async function mypost(url, data, headers = { 'Content-Type': 'application/json' }) {
    return await jspost("?be=" + url, data, headers);
}

async function myget(url, headers = { 'Content-Type': 'application/json' }) {
    return await jsget("?be=" + url, headers);
}

async function myput(url, data, where = {}, headers = { 'Content-Type': 'application/json' }) {
    return jsput("?be=" + url, data, where, headers);
}


async function jspost(url, data, headers = { 'Content-Type': 'application/json' }) {
    let ret = { code: -1, status: 'error', message: 'error message' };

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(data),
        });

        if (response.ok) {
            const result = await response.json();
            ret = {
                code: result.code ?? jssuccesscode,
                backendcode: result.code ?? 0,
                status: 'ok',
                message: result.message ?? 'Okay',
                data: result.data ?? [],
                first_row: result.first_row ?? [],
                result: result,
                backend: result,
                body: data,
                headers: headers,
                url: url
            };
        } else {
            ret = {
                code: jserrorcode,
                backendcode: 404,
                status: 'error',
                message: `HTTP error! status: ${response.status}`,
                error: `HTTP error! status: ${response.status}`,
                body: data,
                headers: headers,
                url: url
            };
            console.error(ret);
        }
    } catch (error) {
        ret = {
            code: jserrorcode,
            status: 'error',
            backendcode: 404,
            message: error.message || 'Error',
            body: data,
            error: error.message || error,
            allerror: error,
            headers: headers,
            url: url
        };
        console.error(ret);
    }

    return ret;
}

async function jspost_plain(url, data, headers = { 'Content-Type': 'application/json' }) {
    let ret = null;

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(data),
        });

        if (response.ok) {
            const result = await response.json();
            ret = result;
        } else {
            ret = {
                code: jserrorcode,
                status: 'error',
                message: `HTTP error! status: ${response.status}`,
                error: `HTTP error! status: ${response.status}`,
            };
            console.error(`HTTP error! status: ${response.status}`);
        }
    } catch (error) {
        ret = {
            code: jserrorcode,
            status: 'error',
            message: 'Error',
            data: JSON.stringify(data),
            error: error.message || error,
            allerror: error
        };
        console.error(ret);
    }

    return ret;
}

async function jsget(url, headers = { 'Content-Type': 'application/json' }) {
    let ret = { code: -1, status: 'error', message: 'error message' };

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: headers,
        });

        if (response.ok) {
            const result = await response.json();
            ret = {
                code: jssuccesscode,
                backendcode: result.code || 0,
                status: 'ok',
                message: result.message || 'Okay',
                data: result.data || [],
                result: result,
                backend: result,
                headers: headers,
                url: url
            };
        } else {
            ret = {
                code: jserrorcode,
                backendcode: 404,
                status: 'error',
                message: `HTTP error! status: ${response.status}`,
                error: `HTTP error! status: ${response.status}`,
                headers: headers,
                url: url
            };
            console.error(ret);
        }
    } catch (error) {
        ret = {
            code: jserrorcode,
            backendcode: 404,
            status: 'error',
            allerror: error,
            message: error.message || error,
            error: error.message || error,
            headers: headers,
            url: url
        };
        console.error(ret);
    }

    return ret;
}

function jschange_effect(effect, dependencies) {
    //effect is a callable
    //dependencies is an array
    let previousDeps = myUseEffect.previousDeps || [];

    const hasChanged = dependencies.some((dep, i) => dep !== previousDeps[i]);

    if (hasChanged) {
        if (typeof myUseEffect.cleanup === "function") {
            myUseEffect.cleanup();
        }
        myUseEffect.cleanup = effect();
        myUseEffect.previousDeps = dependencies;
    }
}

async function jsget_plain(url, headers = { 'Content-Type': 'application/json' }) {
    let ret = { code: -1, status: 'error', message: 'error message' };

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: headers,
        });

        if (response.ok) {
            const result = await response.json();
            ret = result;
        } else {
            ret = {
                code: jserrorcode,
                status: 'error',
                message: `HTTP error! status: ${response.status}`,
                error: `HTTP error! status: ${response.status}`,
            };
            console.error(`HTTP error! status: ${response.status}`);
        }
    } catch (error) {
        ret = {
            code: jserrorcode,
            status: 'error',
            message: 'Error',
            error: error.message || error,
        };
        console.error(ret);
    }

    return ret;
}

async function jsput(url, data, where = {}, headers = { 'Content-Type': 'application/json' }) {
    let ret = { code: -1, status: 'error', message: 'error message' };
    const queryParams = new URLSearchParams(where).toString();
    const fullUrl = queryParams ? `${url}?${queryParams}` : url;

    try {
        const response = await fetch(fullUrl, {
            method: 'PUT',
            headers: headers,
            body: JSON.stringify(data),
        });

        if (response.ok) {
            const result = await response.json();
            ret = {
                code: jssuccesscode,
                backendcode: result.code || 0,
                status: 'ok',
                message: result.message || 'Okay',
                data: result.data || [],
                result: result,
                backend: result,
                body: data,
                headers: headers,
                url: url
            };
        } else {
            ret = {
                code: jserrorcode,
                backendcode: 404,
                status: 'error',
                message: `HTTP error! status: ${response.status}`,
                error: `HTTP error! status: ${response.status}`,
                body: data,
                headers: headers,
                url: url
            };
            console.error(ret);
        }
    } catch (error) {
        ret = {
            code: jserrorcode,
            backendcode: 404,
            status: 'error',
            allerror: error,
            message: error.message || 'Error',
            error: error.message || error,
            body: data,
            headers: headers,
            url: url
        };
        console.error(ret);
    }

    return ret;
}

async function jsput_plain(url, data, where = {}, headers = { 'Content-Type': 'application/json' }) {
    let ret = { code: -1, status: 'error', message: 'error message' };
    const queryParams = new URLSearchParams(where).toString();
    const fullUrl = queryParams ? `${url}?${queryParams}` : url;

    try {
        const response = await fetch(fullUrl, {
            method: 'PUT',
            headers: headers,
            body: JSON.stringify(data),
        });

        if (response.ok) {
            const result = await response.json();
            ret = result;
        } else {
            ret = {
                code: jserrorcode,
                status: 'error',
                message: `HTTP error! status: ${response.status}`,
                error: `HTTP error! status: ${response.status}`,
            };
            console.error(`HTTP error! status: ${response.status}`);
        }
    } catch (error) {
        ret = {
            code: jserrorcode,
            status: 'error',
            message: 'Error',
            error: error.message || error,
        };
        console.error(ret);
    }

    return ret;
}


function get_form_data(selector) {
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


function on_submit(selector, callable) {
    let form = null;
    if (selector.charAt(0) === "#" || selector.charAt(0) === ".") {
        form = document.querySelector(selector);
    } else {
        form = document.getElementById(selector);
    }
    if (!form) {
        console.error(`Form with id '${selector}' not found.`);
        return;
    }
    if (typeof callable !== "function") {
        console.error("Callable is not a function.");
        return;
    }
    form.onsubmit = function (event) {
        callable(event);
    };
}

function on_click(selector, callable) {
    let form = null;
    if (selector.charAt(0) === "#" || selector.charAt(0) === ".") {
        form = document.querySelector(selector);
    } else {
        form = document.getElementById(selector);
    }
    if (!form) {
        console.error(`Tag with id '${selector}' not found.`);
        return;
    }
    if (typeof callable !== "function") {
        console.error("Callable is not a function.");
        return;
    }
    form.onclick = function (event) {
        callable(event);
    };
}

function format_string(str, separator, format) {
    let parts = str.split(separator);
    let result = "";

    for (let e of parts) {
        let formatted = format.replace("?", e.trim());
        result += formatted;
    }

    return result;
}


function filter_json_array(data, column, contains, wildcard = "%") {
    let filteredData;

    if (wildcard === "%") {
        filteredData = data.filter(item => item[column].includes(contains));
    } else {
        filteredData = data.filter(item => item[column] === contains);
    }

    return filteredData;
}

function direct_post(id, url, data = null, headers = { 'Content-Type': 'application/json' }) {
    const formdata = get_form_data(id);
    if (data == null || data.length === 0) {
        data = formdata;
    }
    return jspost(url, data, headers);
}


function direct_get(url, data = null, headers = { 'Content-Type': 'application/json' }) {
    if (data == null || Object.keys(data).length === 0) {
        return jsget(url, headers);
    } else {
        const queryParams = new URLSearchParams(data).toString();
        url = `${url}?${queryParams}`;
        return jsget(url, headers);
    }
}

function url_param(url, param = null) {
    if (param == null || Object.keys(param).length === 0) {
        return url;
    } else {
        const queryParams = new URLSearchParams(param).toString();
        return `${url}?${queryParams}`;
    }
}


function window_loaded(callable) {
    window.addEventListener("load", callable());
}

function on_load(callable) {
    window.addEventListener("load", callable());
}

function dom_loaded(callable) {
    window.addEventListener("DOMContentLoaded", callable());
}

function reload() {
    window.location.reload();
}

function log(text) {
    console.log(text);
}

function set_value(name, value, by = "id") {
    let element = null;
    if (by == "name") {
        element = document.getElementsByName(name);
    } else {
        element = document.getElementById(name);
    }
    element.value = value;
}

function set_input_value(selector, value) {
    document.querySelector(selector).value = value;
}

function set_values(array, by = "id") {
    let element = null;
    for (let key in array) {
        if (by == "name") {
            element = document.getElementsByName(key);
        } else {
            element = document.getElementById(key);
        }
        element.value = array[key];
    }
}


function set_form_values(form, array, by = "name") {
    let fform = `#${form}`;
    if (form.charAt(0) === "#" || form.charAt(0) === ".") {
        fform = `${form}`;
    }
    let element;
    for (let key in array) {
        if (by == "name") {
            element = document.querySelector(`${fform} *[name="${key}"]`);
        } else {
            element = document.querySelector(`${fform} *[id="${key}"]`);
        }
        if (element) {
            element.value = array[key];
        } else {
            console.warn(`No input found for ${key} in form ${form}`);
        }
    }
}

function set_form_data(form, array, by = "name") {
    return set_form_values(form, array, by);
}


function get_form_value(form, name, by = "name") {
    let fform = `#${form}`;
    if (form.charAt(0) === "#" || form.charAt(0) === ".") {
        fform = `${form}`;
    }
    let element;
    if (by == "name") {
        element = document.querySelector(`${fform} *[name="${name}"]`);
    } else {
        element = document.querySelector(`${fform} *[id="${name}"]`);
    }
    if (element) {

    } else {
        console.warn(`No input found for ${name} in form ${form}`);
    }

    return element.value;
}

function get_form_values(id) {
    return get_form_data(id)
}

function get_value(selector) {
    let element = null;

    if (selector.charAt(0) === "#" || selector.charAt(0) === ".") {
        element = document.querySelector(selector);
    } else {
        element = document.getElementById(selector);
    }

    return element ? element.value : null; // Return null if element is not found
}


function get_element(selector) {
    if (selector.charAt(0) === "#" || selector.charAt(0) === ".") {
        return document.querySelector(selector);
    } else {
        return document.getElementById(selector);
    }
}

function get_attribute(selector, attr) {
    let element = null;

    if (selector.charAt(0) === "#" || selector.charAt(0) === ".") {
        element = document.querySelector(selector);
    } else {
        element = document.getElementById(selector);
    }

    return element ? element.getAttribute(attr) : null;
}



function set_attribute(selector, attributes) { // attributes should be an object, not an array
    let el = null;

    if (selector.charAt(0) === "#" || selector.charAt(0) === ".") {
        el = document.querySelector(selector);
    } else {
        el = document.getElementById(selector);
    }

    if (!el) return; // Prevent errors if element is not found

    for (let key in attributes) {
        if (Object.prototype.hasOwnProperty.call(attributes, key)) {
            el.setAttribute(key, attributes[key]);
        }
    }
}


function js_tostring(array) {
    return JSON.stringify(array);
}

function js_stringfy(array) {
    return js_tostring(array);
}

function js_toarray(string) {
    return JSON.parse(string);
}

function json_stringfy(array) {
    return js_tostring(array);
}

function json_parse(string) {
    return js_toarray(string);
}

function set_html(selector, strhtml) {
    let elements = [];

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
    if (elements.length > 0) {
        elements.forEach(element => {
            element.innerHTML = strhtml;
        });
    } else {
        console.warn(`No elements found for selector: "${selector}"`);
    }
}


function add_html(selector, strhtml) {
    let elements = [];

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

    if (elements.length > 0) {
        elements.forEach(element => {
            element.insertAdjacentHTML('beforeend', strhtml);
        });
    } else {
        console.warn(`No elements found for selector: "${selector}"`);
    }
}

function js_href(url, target = "this") {
    if (target == "this") {
        window.location.href = url;
    } else {
        window.open(url, target);
    }
}

function js_selector(selector) {
    return document.querySelector(selector);
}

function js_selector_all(selector) {
    const elements = document.querySelectorAll(selector);
    return elements;
}

function get_selector_value(selector) {
    return document.querySelector(selector).value;
}

function js_timer(timer, callable) {
    setTimeout(callable, timer);
}

function js_interval(timmer, callable) {
    setInterval(callable, timmer);
}
function set_session(key, value) {
    sessionStorage.setItem(key, value);
}

function get_session(key) {
    return sessionStorage.getItem(key);
}

function remove_session(key) {
    sessionStorage.removeItem(key);
}

function clear_session() {
    sessionStorage.clear();
}

const jsform_validate = (formData, rules) => { // usage: rules = {"fname=>Firstname": "required"}
    const errors = {};

    for (const [key, validation] of Object.entries(rules)) {
        let fieldName, label;

        if (key.includes("=>")) {
            [fieldName, label] = key.split("=>");
            label = label || fieldName;
        } else {
            fieldName = key;
            label = fieldName;
        }

        const value = formData.get(fieldName) || "";
        const fieldRules = validation.split("|");

        for (const rule of fieldRules) {
            const [ruleName, ruleParam] = rule.includes(":") ? rule.split(":") : [rule, null];

            switch (ruleName) {
                case "required":
                    if (!value.trim()) {
                        errors[fieldName] = `${label} is required.`;
                        break;
                    }
                    break;

                case "number":
                case "numeric":
                    if (isNaN(value)) {
                        errors[fieldName] = `${label} should be a number.`;
                        break;
                    }
                    break;

                case "alphabet":
                    if (!/^[a-zA-Z]+$/.test(value)) {
                        errors[fieldName] = `${label} should contain only alphabets.`;
                        break;
                    }
                    break;

                case "modern-password":
                    if (!/(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}/.test(value)) {
                        errors[fieldName] = `${label} should contain letters, numbers, and symbols.`;
                        break;
                    }
                    break;

                case "max":
                    if (Number(value) > Number(ruleParam)) {
                        errors[fieldName] = `${label} should not exceed ${ruleParam}.`;
                        break;
                    }
                    break;

                case "min":
                    if (Number(value) < Number(ruleParam)) {
                        errors[fieldName] = `${label} should not be less than ${ruleParam}.`;
                        break;
                    }
                    break;

                case "size":
                    if (value.length !== Number(ruleParam)) {
                        errors[fieldName] = `${label} should have exactly ${ruleParam} character(s).`;
                        break;
                    }
                    break;

                case "length":
                    if (value.length > Number(ruleParam)) {
                        errors[fieldName] = `${label} should not exceed ${ruleParam} characters.`;
                        break;
                    }
                    break;

                default:
                    break;
            }

            if (errors[fieldName]) break;
        }
    }

    return errors;
};

function jsinput_to_base64(selector) {
    return new Promise((resolve, reject) => {
        const fileInput = document.querySelector(selector);

        if (fileInput.files.length === 0) {
            resolve(null);
            return;
        }

        const file = fileInput.files[0];
        const reader = new FileReader();

        reader.onload = function (e) {
            const base64Encoded = e.target.result.split(',')[1];
            const mimeType = file.type;
            const fullDataUrl = `data:${mimeType};base64,${base64Encoded}`;
            resolve(fullDataUrl);
        };
        reader.onerror = function () {
            reject('Error reading the file.');
        };

        reader.readAsDataURL(file);
    });
}



async function jsfile_to_base64(filePath) {
    try {
        const response = await fetch(filePath);
        const blob = await response.blob();
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    } catch (error) {
        console.error('Error fetching the file:', error);
        throw error;
    }
}


function jsdownload_base64(base64Data, fileName) {
    const [mimeTypePart, base64Content] = base64Data.split(',');
    const mimeType = mimeTypePart.match(/:(.*?);/)[1];

    const mimeToExtension = {
        "image/png": "png",
        "image/jpeg": "jpg",
        "image/gif": "gif",
        "text/plain": "txt",
        "application/pdf": "pdf",
        "application/msword": "doc",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document": "docx",
        "application/vnd.ms-excel": "xls",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet": "xlsx",
        "application/vnd.ms-powerpoint": "ppt",
        "application/vnd.openxmlformats-officedocument.presentationml.presentation": "pptx",
        "audio/mpeg": "mp3",
        "video/mp4": "mp4",
        "application/zip": "zip",
        "application/x-rar-compressed": "rar"
    };

    const extension = mimeToExtension[mimeType] || "bin";

    if (!fileName.includes('.')) {
        fileName = `${fileName}.${extension}`;
    }

    const binaryString = atob(base64Content);
    const binaryLength = binaryString.length;
    const bytes = new Uint8Array(binaryLength);
    for (let i = 0; i < binaryLength; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }

    const blob = new Blob([bytes], { type: mimeType });

    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}


function jsimg_base64(imgselector, base64) {
    document.querySelector(imgselector).src = base64;
}

function jsscroll(selector, attr) {
    document.querySelector(selector).scrollTo(attr);
}

function array_key_exist(needle, array) {//check if the array contains a key needle
    return array.hasOwnProperty(needle);
}

function array_element_exist(needle, array) {//check if the array has element needle
    return array.includes(needle);
}

function array_value_exist(needle, array) { // check the array if it has a value needle
    return Object.entries(array).find(([key, value]) => value === needle);
}

function array_value_contains(needle, array) { // check the array contains value needle
    return Object.values(array).some(value => value.includes(needle));
}

function array_filter(filter, array) { // Filter the array
    const filteredarray = Object.fromEntries(
        Object.entries(array).filter(([key, value]) => value.includes(filter))
    );
    return filteredarray;
}

function is_empty(arr) {//check if array is empty
    if (arr == null) {
        return true;
    }
    if (Array.isArray(arr) && arr.length === 0) {
        return true;
    }

    if (typeof arr === 'object' && Object.keys(arr).length === 0) {
        return true;
    }

    return false;
}


function image_capture(callable) { // ussage image_capture((blob)=>{document.querySelector('#id').src = blob});
    const modal = document.createElement("div");
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8); display: flex; flex-direction: column;
        align-items: center; justify-content: center; color: white; z-index: 9999;
    `;

    const video = document.createElement("video");
    video.style.cssText = "width: 80%; max-width: 40%; border: 2px solid white;";

    const canvas = document.createElement("canvas");
    canvas.style.display = "none";

    const btnContainer = document.createElement("div");
    btnContainer.style.cssText = "margin-top: 10px; display: flex; gap: 10px;";

    const captureBtn = document.createElement("button");
    captureBtn.innerText = "Capture";

    const retakeBtn = document.createElement("button");
    retakeBtn.innerText = "Retake";
    retakeBtn.style.display = "none";

    const confirmBtn = document.createElement("button");
    confirmBtn.innerText = "Confirm";
    confirmBtn.style.display = "none";

    const closeBtn = document.createElement("button");
    closeBtn.innerText = "Close";

    btnContainer.append(captureBtn, retakeBtn, confirmBtn, closeBtn);
    modal.append(video, canvas, btnContainer);
    document.body.appendChild(modal);

    let stream = null, imageBlob = null;

    navigator.mediaDevices.getUserMedia({ video: true }).then(mediaStream => {
        stream = mediaStream;
        video.srcObject = stream;
        video.play();
    }).catch(err => alert("Camera access denied!"));

    captureBtn.onclick = () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext("2d").drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.style.display = "block";
        video.style.display = "none";
        captureBtn.style.display = "none";
        retakeBtn.style.display = "inline-block";
        confirmBtn.style.display = "inline-block";

        canvas.toBlob(blob => { imageBlob = blob; }, "image/jpeg");
    };

    retakeBtn.onclick = () => {
        video.style.display = "block";
        canvas.style.display = "none";
        captureBtn.style.display = "inline-block";
        retakeBtn.style.display = "none";
        confirmBtn.style.display = "none";
        imageBlob = null;
    };

    confirmBtn.onclick = () => {
        if (imageBlob) {
            const reader = new FileReader();
            reader.readAsDataURL(imageBlob);
            reader.onloadend = () => {
                if (callable && typeof callable === "function") {
                    callable(reader.result);
                }
            };
        }
        closeModal();
    };

    closeBtn.onclick = closeModal;
    function closeModal() {
        if (stream) stream.getTracks().forEach(track => track.stop());
        modal.remove();
    }
}

function jsimage_show(imagePathOrBlob) { //display image popup //show_image // image_show
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = 0;
    overlay.style.left = 0;
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
    overlay.style.zIndex = 9999;
    overlay.style.display = 'flex';
    overlay.style.justifyContent = 'center';
    overlay.style.alignItems = 'center';
    overlay.style.cursor = 'pointer';

    const img = document.createElement('img');
    img.style.maxHeight = '90vh';
    img.style.maxWidth = '90vw';
    img.style.objectFit = 'contain';
    img.style.borderRadius = '8px';
    img.style.cursor = 'default';

    if (imagePathOrBlob instanceof Blob) {
        img.src = URL.createObjectURL(imagePathOrBlob);
    } else {
        img.src = imagePathOrBlob;
    }

    const closeButton = document.createElement('button');
    closeButton.textContent = 'X';
    closeButton.style.position = 'absolute';
    closeButton.style.top = '20px';
    closeButton.style.right = '20px';
    closeButton.style.fontSize = '20px';
    closeButton.style.padding = '10px';
    closeButton.style.color = '#fff';
    closeButton.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    closeButton.style.border = 'none';
    closeButton.style.cursor = 'pointer';

    overlay.onclick = function (event) {
        if (event.target === overlay) {
            document.body.removeChild(overlay);
        }
    };

    closeButton.onclick = function () {
        document.body.removeChild(overlay);
    };

    overlay.appendChild(img);
    overlay.appendChild(closeButton);

    document.body.appendChild(overlay);
}

function jsqrcode(selector = "qrcode", text, height = 300, width = 300) {//needs to import qrcode (REQUIRED)
    //Libraries: https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js
    // yros: php yros import jsqrcode
    if (!text) {
        alert("Please enter text to generate a QR code.");
        return;
    }

    // Clear previous QR code
    document.querySelector(selector).innerHTML = "";

    // Generate new QR code
    new QRCode(document.querySelector(selector), {
        text: text,
        width: width,
        height: height,
        colorDark: "#000000", // Black color for QR code
        colorLight: "#ffffff", // White background
        correctLevel: QRCode.CorrectLevel.H // High error correction
    });
}


function jsqrscanner(callable, height = 500, width = 500) {
    // Public app/library: https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js
    // Import via Yros: php yros import jsqrscanner
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = 0;
    overlay.style.left = 0;
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
    overlay.style.zIndex = 9999;
    overlay.style.display = 'flex';
    overlay.style.justifyContent = 'center';
    overlay.style.alignItems = 'center';
    overlay.style.flexDirection = 'column';

    const scannerDiv = document.createElement('div');
    scannerDiv.id = 'reader';
    scannerDiv.style.width = width + 'px';
    scannerDiv.style.height = height + 'px';
    scannerDiv.style.background = '#fff';
    scannerDiv.style.borderRadius = '8px';

    const closeButton = document.createElement('button');
    closeButton.textContent = 'X';
    closeButton.style.position = 'absolute';
    closeButton.style.top = '20px';
    closeButton.style.right = '20px';
    closeButton.style.fontSize = '20px';
    closeButton.style.padding = '10px';
    closeButton.style.color = '#fff';
    closeButton.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    closeButton.style.border = 'none';
    closeButton.style.cursor = 'pointer';

    overlay.appendChild(scannerDiv);
    overlay.appendChild(closeButton);
    document.body.appendChild(overlay);

    const scanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
    scanner.render(decodedText => {
        scanner.clear();
        document.body.removeChild(overlay);

        if (typeof callable === "function") {
            callable(decodedText);
        }
    });

    closeButton.onclick = function () {
        scanner.clear();
        document.body.removeChild(overlay);
    };
}

function jsDataTable(selector) {
    return new DataTable(selector);
}

function jspost_validation(postdata, rules) {
    let isValid = true;

    // Clear previous error messages
    rules.forEach(rule => {
        document.getElementById(rule[2]).innerText = ''; // Clear previous error message
    });

    // Loop through all the validation rules
    rules.forEach(rule => {
        const inputName = rule[0]; // The name of the input field
        const errorLabelId = rule[2]; // The ID of the label/span to show the error
        const fieldLabel = rule[1]; // The label to show in the error message
        const ruleString = rule[3]; // The validation rules as a string (e.g., "required|email")

        const inputElement = postdata[inputName];

        // Split the rule string into individual rules
        let ruleArray = ruleString.split('|');
        ruleArray = ruleArray.reverse();

        // Loop through each rule and apply the corresponding validation
        ruleArray.forEach(validationRule => {
            const ruleParam = validationRule.includes(':') ? validationRule.split(':')[1] : null;
            const ruleName = validationRule.split(':')[0];

            // Required validation
            if (ruleName === 'required' && (inputElement === '' || inputElement === null)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} is required.`;
                isValid = false;
            }

            // Min length validation
            if (ruleName === 'min' && inputElement.length < parseInt(ruleParam)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be at least ${ruleParam} characters.`;
                isValid = false;
            }

            // Max length validation
            if (ruleName === 'max' && inputElement.length > parseInt(ruleParam)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must not exceed ${ruleParam} characters.`;
                isValid = false;
            }

            // Email validation
            if (ruleName === 'email' && !filterEmail(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be a valid email address.`;
                isValid = false;
            }

            // Numeric validation
            if (ruleName === 'numeric' && isNaN(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be a number.`;
                isValid = false;
            }

            // Alpha validation
            if (ruleName === 'alpha' && !/^[a-zA-Z]+$/.test(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must contain only letters.`;
                isValid = false;
            }

            // Alphanumeric validation
            if (ruleName === 'alphanumeric' && !/^[a-zA-Z0-9]+$/.test(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must contain only letters and numbers.`;
                isValid = false;
            }

            // Regex validation
            if (ruleName === 'regex' && ruleParam && !new RegExp(ruleParam).test(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} format is invalid.`;
                isValid = false;
            }

            // Match validation
            if (ruleName === 'match' && ruleParam && inputElement !== postdata[ruleParam]) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must match ${ruleParam.replace('_', ' ')}.`;
                isValid = false;
            }

            // In validation (check if value is one of the provided options)
            if (ruleName === 'in' && ruleParam && !ruleParam.split(',').includes(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be one of: ${ruleParam}.`;
                isValid = false;
            }

            // Not in validation (check if value is NOT one of the provided options)
            if (ruleName === 'not_in' && ruleParam && ruleParam.split(',').includes(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must not be one of: ${ruleParam}.`;
                isValid = false;
            }

            // Date validation
            if (ruleName === 'date' && !isValidDate(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be a valid date.`;
                isValid = false;
            }

            // URL validation
            if (ruleName === 'url' && !isValidURL(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be a valid URL.`;
                isValid = false;
            }

            // IP validation
            if (ruleName === 'ip' && !isValidIP(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be a valid IP address.`;
                isValid = false;
            }

            // Boolean validation
            if (ruleName === 'boolean' && !['0', '1', 0, 1, true, false].includes(inputElement)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be true or false.`;
                isValid = false;
            }

            // Exact length validation
            if (ruleName === 'length' && inputElement.length !== parseInt(ruleParam)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must be exactly ${ruleParam} characters.`;
                isValid = false;
            }

            // Starts with validation
            if (ruleName === 'starts_with' && !inputElement.startsWith(ruleParam)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must start with '${ruleParam}'.`;
                isValid = false;
            }

            // Ends with validation
            if (ruleName === 'ends_with' && !inputElement.endsWith(ruleParam)) {
                document.getElementById(errorLabelId).innerText = `${fieldLabel} must end with '${ruleParam}'.`;
                isValid = false;
            }
        });
    });

    return isValid;
}

function filterEmail(value) {
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    return emailPattern.test(value);
}

function isValidDate(value) {
    return !isNaN(Date.parse(value));
}

function isValidURL(value) {
    const urlPattern = /^(https?|ftp):\/\/[^\s/$.?#].[^\s]*$/i;
    return urlPattern.test(value);
}

function isValidIP(value) {
    const ipPattern = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    return ipPattern.test(value);
}

function selected_item($selector) {
    const select = document.querySelector($selector);
    const selectedText = select.options[select.selectedIndex].text;
    return selectedText;
}

function selected_value($selector) {
    const select = document.querySelector($selector);
    const selectedText = select.value;
    return selectedText;
}

function selected_index($selector) {
    const select = document.querySelector($selector);
    const selectedIndex = select.selectedIndex;
    return selectedIndex;
}

function set_selected_item($selector, $item) {
    const select = document.querySelector($selector);
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].text === $item) {
            select.selectedIndex = i;
            break;
        }
    }
}

function set_selected_index($selector, $index) {
    document.querySelector($selector).selectedIndex = $index;
}

function set_selected_value($selector, $value) {
    document.querySelector($selector).value = $value;
}

function dom_loaded(callable) {
    document.addEventListener('DOMContentLoaded', callable);
}

function swal_success(message, title = "Success") {
    return Swal.fire({
        title: title,
        text: message,
        icon: "success"
    });
}

function swal_error(message, title = "Error") {
    return Swal.fire({
        title: title,
        text: message,
        icon: "error"
    });
}

function swal_warning(message, title = "Warning") {
    return Swal.fire({
        title: title,
        text: message,
        icon: "warning"
    });
}

function swal_info(message, title = "Information") {
    return Swal.fire({
        title: title,
        text: message,
        icon: "info"
    });
}


