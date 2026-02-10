 /**
 * use this as base class for account edit form extensions (/engine/<providerCode>/form.js)
 * see FormInterface
 */
function AbstractFormExtension() {}

/**
 * will be called on field change, field
 * @param {FormInterface} form
 * @param {string} fieldName in lower case
 */
AbstractFormExtension.prototype.onFieldChange = function (form, fieldName){

};

/**
 * will be called when form loaded and ready
 * @param {FormInterface} form
 * @param {string} fieldName in lower case
 */
AbstractFormExtension.prototype.onFormReady = function (form, fieldName){

};

