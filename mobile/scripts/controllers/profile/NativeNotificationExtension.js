/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {

    const emailFields = [
        'emailExpire',
        'emailRewardsActivity',
        'emailNewPlans',
        'emailPlanChanges',
        'emailCheckins',
        'emailBookingMessages',
        'emailProductUpdates',
        'emailOffers',
        'emailNewBlogPosts',
        'emailInviteeReg',
        'emailConnected',
        'emailNotConnected'
    ];

    const pushFields = [
        'mpExpire',
        'mpRewardsActivity',
        'mpRetailCards',
        'mpNewPlans',
        'mpPlanChanges',
        'mpCheckins',
        'mpBookingMessages',
        'mpProductUpdates',
        'mpOffers',
        'mpNewBlogPosts',
        'mpInviteeReg',
        'mpConnected',
        'mpNotConnected'
    ];

    const methods = {
        disableFields: function(form, fields, disable) {
            fields.forEach(function(name) {
                form.disableField(name, disable);
            });
        }
    };

    extension.onFieldChange = function (form, fieldName) {
        const checked = form.getValue(fieldName);

        if (fieldName === 'emailDisableAll') {
            methods.disableFields(form, emailFields, checked);
        } else if (fieldName === 'mpDisableAll') {
            methods.disableFields(form, pushFields, checked);
        }
    };

}