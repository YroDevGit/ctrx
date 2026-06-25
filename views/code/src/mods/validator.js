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

    required() {
        this.rules.push({ name: 'required' });
        return this;
    }

    email() {
        this.rules.push({ name: 'email' });
        return this;
    }

    number() {
        this.rules.push({ name: 'number' });
        return this;
    }

    string() {
        this.rules.push({ name: 'string' });
        return this;
    }

    min(val) {
        this.rules.push({ name: 'min', value: val });
        return this;
    }

    max(val) {
        this.rules.push({ name: 'max', value: val });
        return this;
    }

    minChars(val) {
        this.rules.push({ name: 'minChars', value: val });
        return this;
    }

    maxChars(val) {
        this.rules.push({ name: 'maxChars', value: val });
        return this;
    }

    equal(val) {
        this.rules.push({ name: 'equal', value: val });
        return this;
    }

    regex(pattern) {
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

    alpha() {
        this.rules.push({ name: 'alpha' });
        return this;
    }

    alphanumeric() {
        this.rules.push({ name: 'alphanumeric' });
        return this;
    }

    boolean() {
        this.rules.push({ name: 'boolean' });
        return this;
    }

    url() {
        this.rules.push({ name: 'url' });
        return this;
    }

    ip() {
        this.rules.push({ name: 'ip' });
        return this;
    }

    startsWith(val) {
        this.rules.push({ name: 'startsWith', value: val });
        return this;
    }

    endsWith(val) {
        this.rules.push({ name: 'endsWith', value: val });
        return this;
    }

    length(val) {
        this.rules.push({ name: 'length', value: val });
        return this;
    }

    trim() {
        this.rules.push({ name: 'trim' });
        return this;
    }

    validate() {
        let value = Validator.dataSource[this.field];

        if (this.rules.find(r => r.name === 'trim')) {
            value = String(value ?? '').trim();
        }

        for (const rule of this.rules) {
            switch (rule.name) {

                case 'required':
                    if (value === undefined || value === null || value === '') {
                        this.addError(`${this.labelName} is required.`);
                        return value;
                    }
                    break;

                case 'email':
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        this.addError(`${this.labelName} must be a valid email.`);
                        return value;
                    }
                    break;

                case 'number':
                    if (isNaN(value)) {
                        this.addError(`${this.labelName} must be a number.`);
                        return value;
                    }
                    break;

                case 'string':
                    if (typeof value !== 'string') {
                        this.addError(`${this.labelName} must be a string.`);
                        return value;
                    }
                    break;

                case 'min':
                    if (Number(value) < rule.value) {
                        this.addError(`${this.labelName} must be at least ${rule.value}.`);
                        return value;
                    }
                    break;

                case 'max':
                    if (Number(value) > rule.value) {
                        this.addError(`${this.labelName} must not exceed ${rule.value}.`);
                        return value;
                    }
                    break;

                case 'minChars':
                    if (String(value).length < rule.value) {
                        this.addError(`${this.labelName} must be at least ${rule.value} characters.`);
                        return value;
                    }
                    break;

                case 'maxChars':
                    if (String(value).length > rule.value) {
                        this.addError(`${this.labelName} must not exceed ${rule.value} characters.`);
                        return value;
                    }
                    break;

                case 'equal':
                    if (value !== rule.value) {
                        this.addError(`${this.labelName} has invalid value.`);
                        return value;
                    }
                    break;

                case 'regex':
                    if (!new RegExp(rule.value).test(value)) {
                        this.addError(`${this.labelName} format is invalid.`);
                        return value;
                    }
                    break;

                case 'contain':
                    if (!String(value).includes(rule.value)) {
                        this.addError(
                            this.errorList.contain ||
                            `${this.labelName} has invalid value.`
                        );
                        return value;
                    }
                    break;

                case 'exclude':
                    if (String(value).includes(rule.value)) {
                        this.addError(
                            this.errorList.exclude ||
                            `${this.labelName} value is not allowed.`
                        );
                        return value;
                    }
                    break;

                case 'in':
                    if (!rule.value.includes(value)) {
                        this.addError(
                            this.errorList.in ||
                            `${this.labelName} has invalid value.`
                        );
                        return value;
                    }
                    break;

                case 'notIn':
                    if (rule.value.includes(value)) {
                        this.addError(
                            this.errorList.notIn ||
                            `${this.labelName} value is not allowed.`
                        );
                        return value;
                    }
                    break;

                case 'alpha':
                    if (!/^[A-Za-z]+$/.test(value)) {
                        this.addError(`${this.labelName} must contain only letters.`);
                        return value;
                    }
                    break;

                case 'alphanumeric':
                    if (!/^[A-Za-z0-9]+$/.test(value)) {
                        this.addError(`${this.labelName} must contain only letters and numbers.`);
                        return value;
                    }
                    break;

                case 'boolean':
                    if (![true, false, 0, 1, '0', '1'].includes(value)) {
                        this.addError(`${this.labelName} must be boolean.`);
                        return value;
                    }
                    break;

                case 'url':
                    try {
                        new URL(value);
                    } catch {
                        this.addError(`${this.labelName} must be a valid URL.`);
                        return value;
                    }
                    break;

                case 'ip':
                    if (!/^(\d{1,3}\.){3}\d{1,3}$/.test(value)) {
                        this.addError(`${this.labelName} must be a valid IP.`);
                        return value;
                    }
                    break;

                case 'startsWith':
                    if (!String(value).startsWith(rule.value)) {
                        this.addError(`${this.labelName} must start with ${rule.value}.`);
                        return value;
                    }
                    break;

                case 'endsWith':
                    if (!String(value).endsWith(rule.value)) {
                        this.addError(`${this.labelName} must end with ${rule.value}.`);
                        return value;
                    }
                    break;

                case 'length':
                    if (String(value).length !== rule.value) {
                        this.addError(`${this.labelName} must be exactly ${rule.value} characters.`);
                        return value;
                    }
                    break;
            }
        }

        Validator.collect[this.field] = value;

        return value;
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