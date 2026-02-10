/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {

    function updateField(form) {
        var question = form.getField('question');
        var selectedValue = form.getValue('question');

        if (selectedValue)
            form.setFieldType('answer', question.attr.type_map[selectedValue]);
    }

    /**
     * will be called on field change
     * @param {FormInterface} form
     * @param {string} fieldName in lower case
     */
    extension.onFieldChange = function (form, fieldName) {
        updateField(form);
    };

    /**
     * will be called when form loaded and ready
     * @param {FormInterface} form
     */
    extension.onFormReady = function (form) {
        updateField(form);
    };

}