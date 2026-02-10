/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    extension.onFieldChange = function (form, fieldName) {
        if (fieldName === 'trackexpiration') {
            var checked = form.getValue('trackexpiration');

            form.disableField('expirationdate', !checked)
        }
    };

    extension.onFormReady = function (form) {
    };
}
