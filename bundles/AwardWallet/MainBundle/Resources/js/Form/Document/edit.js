/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    let countriesStates = null;
    let ownersList = null;
    let certificateIssuedChangeLocked = false;
    let isNewRecord = null;

    function correctionDate(dateValue) {
        return (new Date(dateValue + 'T14:00:00'));
    }

    extension.onFieldChange = function(form, fieldName) {

        switch (fieldName) {
            case 'owner':
                if (!isNewRecord) {
                    break;
                }
                const ownerId = form.getValue(fieldName);
                if (null === ownersList) {
                    ownersList = JSON.parse(form.getValue('ownersList'));
                }

                if (null !== ownersList && ownersList.hasOwnProperty(ownerId)) {
                    const selectedName = ownersList[ownerId].name;
                    form.setValue('fullName', selectedName);
                    form.setValue('nameOnCard', selectedName);
                    form.setValue('vaccinePassportName', selectedName);

                    if (null !== form.getInput('vaccinePassportNumber')) {
                        const passport = ownersList[ownerId].hasOwnProperty('passportNumber') ? ownersList[ownerId].passportNumber : '';
                        form.setValue('vaccinePassportNumber', passport);
                    }
                }
                break;

            case 'country':
                const state = form.getInput('state');
                if (0 === state.length) {
                    return null;
                }

                if (null === countriesStates) {
                    countriesStates = JSON.parse(form.getValue('countriesStates'));
                }

                const countryId = form.getValue('country');
                if (countriesStates.hasOwnProperty(countryId) && countriesStates[countryId].hasOwnProperty('states')) {
                    let options = [];
                    const states = countriesStates[countryId]['states'];
                    for (let i in states) {
                        options.push({ value: states[i].StateID, label: states[i].Name });
                    }
                    form.setOptions('state', options);
                    form.showField('state', true);
                } else {
                    form.showField('state', false);
                    form.setOptions('state', [{ label: '', value: '' }]);
                }
                break;
        }

        if (!isNewRecord) {
            return;
        }

        switch (fieldName) {
            case 'certificateIssued_datepicker':
                extension.onFieldChange(form, 'certificateIssued');
                break;
            case 'certificateIssued':
                certificateIssuedChangeLocked = true;
                break;

            case 'firstDoseDate_datepicker':
                extension.onFieldChange(form, 'firstDoseDate');
                break;
            case 'firstDoseDate':
                if ('' !== form.getValue('certificateIssued')) {
                    return;
                }
                form.setValue('certificateIssued', correctionDate(form.getValue(fieldName)));
                break;

            case 'secondDoseDate_datepicker':
                extension.onFieldChange(form, 'secondDoseDate');
                break;
            case 'secondDoseDate':
                if (certificateIssuedChangeLocked) {
                    return;
                }
                form.setValue('certificateIssued', correctionDate(form.getValue(fieldName)));
                break;

            case 'issueDate_datepicker':
                extension.onFieldChange(form, 'issueDate');
                break;
            case 'issueDate':
                if ('' !== form.getValue('validFrom')) {
                    return;
                }
                form.setValue('validFrom', correctionDate(form.getValue(fieldName)));
                break;
        }
    };

    extension.onFormReady = function(form) {
        isNewRecord = '1' == form.getValue('isNewRecord');
        extension.onFieldChange(form, 'owner');
        if ('' == form.getValue('state')) {
            extension.onFieldChange(form, 'country');
        }
    };

}