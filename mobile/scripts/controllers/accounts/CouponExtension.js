/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {

    var timeoutId = 0;

    function setAttachedAccounts(form) {
        var field = form.getField('programname'),
            owner = form.getValue('owner'),
            attachAccounts = form.getOptions('account');

        if (field) {
            if (field.row) {
                var additionalData = field.row.additionalData;
                form.setValue('kind', field.row.kind);
                form.showField('kind', false);
                if (
                    additionalData &&
                    additionalData.attachAccounts &&
                    additionalData.attachAccounts[owner]
                ) {
                    form.setOptions('account', attachAccounts.slice(0, 1).concat(additionalData.attachAccounts[owner]));
                    return;
                }
            } else {
                if (!field.value) {
                    form.showField('kind', true);
                    form.setValue('kind', '');
                }
                if (!field.changed) {
                    if (
                        field.attachedAccounts &&
                        field.attachedAccounts[owner]
                    ) {
                        form.setOptions('account', attachAccounts.slice(0, 1).concat(field.attachedAccounts[owner]));
                        return;
                    }
                }
            }
            form.setOptions('account', attachAccounts.slice(0, 1));
        }
    }

    /**
     * will be called on field change
     * @param {FormInterface} form
     * @param {string} fieldName in lower case
     */
    extension.onFieldChange = function (form, fieldName) {
        var owner = form.getValue('owner');

        if (fieldName === 'programname') {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(setAttachedAccounts.bind(this, form), 250);
        }

        if (fieldName === 'owner' && owner !== 'new_family_member') {
            setAttachedAccounts(form);
        }
    };

    /**
     * will be called when form loaded and ready
     * @param {FormInterface} form
     */
    extension.onFormReady = function (form) {
        var programName = form.getField('programname');

        if (programName && programName.providerKind)
            form.showField('kind', false);
    };

}