class Validator {
    /**
     * Tyrone Validator JS
     * inspired by Express Validator
     */

    static _errors = {};
    static ers = {};
    static failedState = false;
    static collect = {};
    static dataSource = {};
    static errorList = [];

    constructor(field) {
        this.field = field;
        this.labelName = field;
        this.rules = [];
        this.errorList = {};
    }

    static set_data(data = {}) {
        if (data instanceof FormData) {
            data = Object.fromEntries(data.entries());
        }
        this.reset();
        this.dataSource = data;
        return this;
    }

    static input(field) {
        return new Validator(field);
    }

    static body(field) {
        return new Validator(field);
    }

    static post(field) {
        return new Validator(field);
    }

    label(label) {
        this.labelName = label;
        return this;
    }

    required(message = null) {
        if (message) {
            this.errorList.required = message;
        }
        this.rules.push({ name: 'required' });
        return this;
    }

    email(message = null) {
        if (message) {
            this.errorList.email = message;
        }
        this.rules.push({ name: 'email' });
        return this;
    }

    number(message = null) {
        if (message) {
            this.errorList.number = message;
        }
        this.rules.push({ name: 'number' });
        return this;
    }

    string(message = null) {
        if (message) {
            this.errorList.string = message;
        }
        this.rules.push({ name: 'string' });
        return this;
    }

    min(val, message = null) {
        if (message) {
            this.errorList.min = message;
        }
        this.rules.push({ name: 'min', value: val });
        return this;
    }

    max(val, message = null) {
        if (message) {
            this.errorList.max = message;
        }
        this.rules.push({ name: 'max', value: val });
        return this;
    }

    minChars(val, message = null) {
        if (message) {
            this.errorList.minChars = message;
        }
        this.rules.push({ name: 'minChars', value: val });
        return this;
    }

    maxChars(val, message = null) {
        if (message) {
            this.errorList.maxChars = message;
        }
        this.rules.push({ name: 'maxChars', value: val });
        return this;
    }

    equal(val, message = null) {
        if (message) {
            this.errorList.equal = message;
        }
        this.rules.push({ name: 'equal', value: val });
        return this;
    }

    regex(pattern, message = null) {
        if (message) {
            this.errorList.regex = message;
        }
        this.rules.push({ name: 'regex', value: pattern });
        return this;
    }

    contain(val, error = null) {
        this.rules.push({ name: 'contain', value: val });
        if (error) {
            this.errorList.contain = error;
        }
        return this;
    }

    exclude(val, error = null) {
        this.rules.push({ name: 'exclude', value: val });
        if (error) {
            this.errorList.exclude = error;
        }
        return this;
    }

    in(options = [], error = null) {
        this.rules.push({ name: 'in', value: options });
        if (error) {
            this.errorList.in = error;
        }
        return this;
    }

    notIn(options = [], error = null) {
        this.rules.push({ name: 'notIn', value: options });
        if (error) {
            this.errorList.notIn = error;
        }
        return this;
    }

    alpha(message = null) {
        if (message) {
            this.errorList.alpha = message;
        }
        this.rules.push({ name: 'alpha' });
        return this;
    }

    alphaStrict(message = null) {
        if (message) {
            this.errorList.alphaStrict = message;
        }
        this.rules.push({ name: 'alphaStrict' });
        return this;
    }

    alphanumeric(message = null) {
        if (message) {
            this.errorList.alphanumeric = message;
        }
        this.rules.push({ name: 'alphanumeric' });
        return this;
    }

    boolean(message = null) {
        if (message) {
            this.errorList.boolean = message;
        }
        this.rules.push({ name: 'boolean' });
        return this;
    }

    url(message = null) {
        if (message) {
            this.errorList.url = message;
        }
        this.rules.push({ name: 'url' });
        return this;
    }

    ip(message = null) {
        if (message) {
            this.errorList.ip = message;
        }
        this.rules.push({ name: 'ip' });
        return this;
    }

    date(message = null) {
        if (message) {
            this.errorList.date = message;
        }
        this.rules.push({ name: 'date' });
        return this;
    }

    startsWith(val, message = null) {
        if (message) {
            this.errorList.startsWith = message;
        }
        this.rules.push({ name: 'startsWith', value: val });
        return this;
    }

    endsWith(val, message = null) {
        if (message) {
            this.errorList.endsWith = message;
        }
        this.rules.push({ name: 'endsWith', value: val });
        return this;
    }

    length(val, message = null) {
        if (message) {
            this.errorList.length = message;
        }
        this.rules.push({ name: 'length', value: val });
        return this;
    }

    trim() {
        this.rules.push({ name: 'trim' });
        return this;
    }

    column(columnName) {
        this.rules.push({ name: 'collect', value: columnName });
        return this;
    }

    collect(key) {
        return this.column(key);
    }

    decrypt() {
        this.rules.push({ name: 'decrypt' });
        return this;
    }

    getErrorMsg(rule, label, errorMessages, defaultMsg) {
        const err = errorMessages[rule] || null;
        if (!err) {
            return defaultMsg;
        }
        return err.replace(/:?/g, label);
    }

    validate() {
        let value = Validator.dataSource[this.field];
        const org = Validator.dataSource[this.field] ?? null;
        let collected = false;

        const hasDecrypt = this.rules.find(r => r.name === 'decrypt');
        if (hasDecrypt && org) {
            value = org;
        }

        const hasTrim = this.rules.find(r => r.name === 'trim');
        if (hasTrim) {
            value = String(value ?? '').trim();
        }

        const hasRequired = this.rules.find(r => r.name === 'required');
        const isOptional = !hasRequired;

        if (!hasRequired && (value === undefined || value === null || value === '')) {
            const collectRule = this.rules.find(r => r.name === 'collect');
            if (collectRule) {
                collected = true;
                Validator.collect[collectRule.value] = value;
            }
            if (!collectRule) {
                Validator.collect[this.field] = value;
            }
            return org;
        }

        for (const rule of this.rules) {
            if (rule.name === 'collect' || rule.name === 'column') {
                collected = true;
                Validator.collect[rule.value] = value;
                continue;
            }

            if (rule.name === 'decrypt' || rule.name === 'trim') {
                continue;
            }

            switch (rule.name) {

                case 'required':
                    if (value === undefined || value === null || value === '') {
                        const msg = this.getErrorMsg('required', this.labelName, this.errorList, `${this.labelName} is required.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'email':
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        const msg = this.getErrorMsg('email', this.labelName, this.errorList, `${this.labelName} must be a valid email.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'number':
                    if (isNaN(value) || value === '') {
                        const msg = this.getErrorMsg('number', this.labelName, this.errorList, `${this.labelName} must be a number.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'string':
                    if (typeof value !== 'string') {
                        const msg = this.getErrorMsg('string', this.labelName, this.errorList, `${this.labelName} must be a string.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'min':
                    if (isNaN(value) || Number(value) < rule.value) {
                        const msg = this.getErrorMsg('min', this.labelName, this.errorList, `${this.labelName} must be at least ${rule.value}.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'max':
                    if (isNaN(value) || Number(value) > rule.value) {
                        const msg = this.getErrorMsg('max', this.labelName, this.errorList, `${this.labelName} must not exceed ${rule.value}.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'minChars':
                    if (String(value).length < rule.value) {
                        const msg = this.getErrorMsg('minChars', this.labelName, this.errorList, `${this.labelName} must be at least ${rule.value} characters.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'maxChars':
                    if (String(value).length > rule.value) {
                        const msg = this.getErrorMsg('maxChars', this.labelName, this.errorList, `${this.labelName} must not exceed ${rule.value} characters.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'equal':
                    if (value !== rule.value) {
                        const msg = this.getErrorMsg('equal', this.labelName, this.errorList, `${this.labelName} has invalid value.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'regex':
                    if (!new RegExp(rule.value).test(value)) {
                        const msg = this.getErrorMsg('regex', this.labelName, this.errorList, `${this.labelName} format is invalid.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'contain':
                    if (!String(value).includes(rule.value)) {
                        const msg = this.errorList.contain || `${this.labelName} has invalid value.`;
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'exclude':
                    if (String(value).includes(rule.value)) {
                        const msg = this.errorList.exclude || `${this.labelName} value is not allowed.`;
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'in':
                    if (!rule.value.includes(value)) {
                        const msg = this.errorList.in || `${this.labelName} has invalid value.`;
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'notIn':
                    if (rule.value.includes(value)) {
                        const msg = this.errorList.notIn || `${this.labelName} value is not allowed.`;
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'alpha':
                    if (!/^[A-Za-z]+$/.test(value)) {
                        const msg = this.getErrorMsg('alpha', this.labelName, this.errorList, `${this.labelName} must contain only letters.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'alphaStrict':
                    if (!/^[A-Za-z]+$/.test(value)) {
                        const msg = this.getErrorMsg('alphaStrict', this.labelName, this.errorList, `${this.labelName} must contain only letters.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'alphanumeric':
                    if (!/^[A-Za-z0-9]+$/.test(value)) {
                        const msg = this.getErrorMsg('alphanumeric', this.labelName, this.errorList, `${this.labelName} must contain only letters and numbers.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'boolean':
                    if (![true, false, 0, 1, '0', '1'].includes(value)) {
                        const msg = this.getErrorMsg('boolean', this.labelName, this.errorList, `${this.labelName} must be boolean.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'url':
                    try {
                        new URL(value);
                    } catch {
                        const msg = this.getErrorMsg('url', this.labelName, this.errorList, `${this.labelName} must be a valid URL.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'ip':
                    if (!/^(\d{1,3}\.){3}\d{1,3}$/.test(value)) {
                        const msg = this.getErrorMsg('ip', this.labelName, this.errorList, `${this.labelName} must be a valid IP.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'date':
                    if (isNaN(Date.parse(value))) {
                        const msg = this.getErrorMsg('date', this.labelName, this.errorList, `${this.labelName} must be a valid date.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'startsWith':
                    if (!String(value).startsWith(rule.value)) {
                        const msg = this.getErrorMsg('startsWith', this.labelName, this.errorList, `${this.labelName} must start with ${rule.value}.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'endsWith':
                    if (!String(value).endsWith(rule.value)) {
                        const msg = this.getErrorMsg('endsWith', this.labelName, this.errorList, `${this.labelName} must end with ${rule.value}.`);
                        this.addError(msg);
                        return org;
                    }
                    break;

                case 'length':
                    if (String(value).length !== rule.value) {
                        const msg = this.getErrorMsg('length', this.labelName, this.errorList, `${this.labelName} must be exactly ${rule.value} characters.`);
                        this.addError(msg);
                        return org;
                    }
                    break;
            }
        }

        if (!collected) {
            Validator.collect[this.field] = value;
        }

        return org;
    }

    run() {
        return this.validate();
    }

    exec() {
        return this.validate();
    }

    go() {
        return this.validate();
    }

    X() {
        return this.validate();
    }

    addError(message) {
        Validator._errors[this.field] = message;
        Validator.ers[this.field] = message;
        Validator.failedState = true;
    }

    static failed() {
        return this.failedState;
    }

    static get isFailed() {
        return this.failedState;
    }

    static errorsList() {
        return this._errors;
    }

    static errors() {
        return this._errors;
    }

    static get hasErrors() {
        if (this.failed) {
            return this._errors;
        }
        return false;
    }

    static field_error(field = null) {
        if (!field) {
            return Object.values(this._errors)[0] || null;
        }
        return this._errors[field] || null;
    }

    static post_error(field = null) {
        return this.field_error(field);
    }

    static reset() {
        this._errors = {};
        this.ers = {};
        this.failedState = false;
        this.collect = {};
    }

    static data() {
        return this.collect;
    }
}

export default Validator;