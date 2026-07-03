// FormValidator.js
import Validator from "./validator";

/**
 * Usage:
 * 
    // data is data from form
    // #signupForm is the id of Form

    let rules = {
        fullname: {required:true, maxChars:10}
    }
    
    let res = FormValidator.validate(data,rules, "#signupForm");
    if(res.failed){
        return;   
    }
    //Proceed
 */

    /**
     Raw use:
     FormValidator.displayErrors(errors, "#formId");
     */

class FormValidator {
    
    /**
     * Get form element from various input types
     * @param {string|HTMLElement} form - Form element, ID, or selector
     * @returns {HTMLElement|null}
     */
    static _getFormElement(form) {
        if (!form) return null;
        
        // If it's already an element
        if (form instanceof HTMLElement) {
            return form;
        }
        
        // If it's a string
        if (typeof form === 'string') {
            // If it starts with # or ., use querySelector
            if (form.startsWith('#') || form.startsWith('.')) {
                return document.querySelector(form);
            }
            // Otherwise treat as ID
            return document.getElementById(form);
        }
        
        return null;
    }
    
    /**
     * Clear error for a specific field
     * @param {HTMLElement} formElement - Form element
     * @param {string} fieldName - Field name
     */
    static _clearFieldError(formElement, fieldName) {
        if (!formElement) return;
        
        // Remove error class from input
        const input = formElement.querySelector(`[name="${fieldName}"]`);
        if (input) {
            input.classList.remove('err-field');
        }
        
        // Clear error message
        const errorEl = formElement.querySelector(`#err_${fieldName}`);
        if (errorEl) {
            errorEl.textContent = '';
        }
    }
    
    /**
     * Validate form data against rules
     * @param {Object|FormData} data - Data to validate
     * @param {Object} rules - Validation rules
     * @param {string|HTMLElement} form - Form element, ID, or selector (optional)
     * @returns {Object} { failed: boolean, errors: Object, data: Object }
     */
    static validate(data, rules, form = null) {
        // Reset validator
        Validator.reset();
        
        // Get form element if provided
        const formElement = form ? this._getFormElement(form) : null;
        
        // Convert FormData to object if needed
        if (data instanceof FormData) {
            data = Object.fromEntries(data.entries());
        }
        
        Validator.set_data(data);
        
        let failed = false;
        const errors = {};
        const validatedData = {};
        
        // First pass: validate all fields
        Object.keys(rules).forEach(fieldName => {
            const rule = rules[fieldName];
            const label = rule.label || fieldName.charAt(0).toUpperCase() + fieldName.slice(1);
            
            // Build validator
            let validator = Validator.input(fieldName).label(label);
            
            // Check if optional
            const isOptional = rule.optional || false;
            const value = data[fieldName];
            
            // If optional and empty, skip validation
            if (isOptional && (value === undefined || value === null || value === '')) {
                return;
            }
            
            // Apply rules
            if (rule.required) validator.required();
            if (rule.email) validator.email();
            if (rule.number) validator.number();
            if (rule.string) validator.string();
            if (rule.alpha) validator.alpha();
            if (rule.alphanumeric) validator.alphanumeric();
            if (rule.boolean) validator.boolean();
            if (rule.url) validator.url();
            if (rule.ip) validator.ip();
            if (rule.trim) validator.trim();
            
            if (rule.min !== undefined) validator.min(rule.min);
            if (rule.max !== undefined) validator.max(rule.max);
            if (rule.minChars !== undefined) validator.minChars(rule.minChars);
            if (rule.maxChars !== undefined) validator.maxChars(rule.maxChars);
            if (rule.length !== undefined) validator.length(rule.length);
            if (rule.equal !== undefined) validator.equal(rule.equal);
            if (rule.regex) validator.regex(rule.regex);
            if (rule.startsWith) validator.startsWith(rule.startsWith);
            if (rule.endsWith) validator.endsWith(rule.endsWith);
            if (rule.contain) validator.contain(rule.contain);
            if (rule.exclude) validator.exclude(rule.exclude);
            if (rule.in) validator.in(rule.in);
            if (rule.notIn) validator.notIn(rule.notIn);
            
            // Run validation
            validator.validate();
            
            if (Validator.failed()) {
                failed = true;
                errors[fieldName] = Validator.field_error(fieldName);
            } else {
                validatedData[fieldName] = data[fieldName];
            }
        });
        
        // Handle form errors display
        if (formElement) {
            // Clear ALL errors first
            this.clearErrors(form);
            
            // If there are errors, display them
            if (failed) {
                this.displayErrors(errors, form, false);
            }
        }
        
        return {
            failed: failed,
            errors: errors,
            data: validatedData
        };
    }
    
    /**
     * Display errors on form fields
     * @param {Object} errors - Error object from validate()
     * @param {string|HTMLElement} form - Form element, ID, or selector
     */
    static displayErrors(errors, form, autoReset = true) {
        const formElement = this._getFormElement(form);
        if (!formElement) {
            console.error('Form not found for displaying errors');
            return;
        }

        if(autoReset){
            this.clearErrors(form);
        }
        
        Object.keys(errors).forEach(fieldName => {
            const errorMsg = errors[fieldName];
            if (!errorMsg) return;
            
            // Find input
            const input = formElement.querySelector(`[name="${fieldName}"]`);
            if (input) {
                input.classList.add('err-field');
            }else{
                let fid = formElement.getAttribute("id");
                console.error(`INPUT named: '${fieldName}' not found.! @${fid}`);
            }
            
            // Find error display
            const errorEl = formElement.querySelector(`#err_${fieldName}`);
            if (errorEl) {
                errorEl.textContent = errorMsg;
            }else{
                let fid = formElement.getAttribute("id");
                console.error(`error_text: '${fieldName}' not found.! @${fid}`);
            }
        });
    }
    
    /**
     * Clear all errors from form
     * @param {string|HTMLElement} form - Form element, ID, or selector
     */
    static clearErrors(form) {
        const formElement = this._getFormElement(form);
        if (!formElement) return;
        
        // Remove error class from all inputs
        formElement.querySelectorAll('.tmodal-input.error, .tmodal-textarea.error, .tmodal-select.error, input.error, textarea.error, select.error')
            .forEach(el => el.classList.remove('error'));
        
        // Clear all error messages
        formElement.querySelectorAll('.error_text')
            .forEach(el => {
                el.textContent = '';
                el.classList.remove('show');
            });
    }
    
    /**
     * Clear error for a specific field
     * @param {string|HTMLElement} form - Form element, ID, or selector
     * @param {string} fieldName - Field name
     */
    static clearFieldError(form, fieldName) {
        const formElement = this._getFormElement(form);
        if (!formElement) return;
        this._clearFieldError(formElement, fieldName);
    }

    static reset(form){
        this.clearErrors(form);
    }
    
    /**
     * Get validation errors as string
     * @param {Object} errors - Error object
     * @param {string} separator - Separator between errors
     * @returns {string}
     */
    static errorsToString(errors, separator = '\n') {
        return Object.values(errors).join(separator);
    }
    
    /**
     * Check if there are any errors
     * @param {Object} errors - Error object
     * @returns {boolean}
     */
    static hasErrors(errors) {
        return Object.keys(errors).length > 0;
    }
}

export default FormValidator;