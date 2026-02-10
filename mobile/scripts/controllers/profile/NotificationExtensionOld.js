/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {

    var getScope = function(form, inputName) {
        return angular.element(form.getInput(inputName).get(0)).scope();
    }, getPushService = function() {
        return angular.element(document.body).injector().get('PushNotification');
    }, disableFields = function(form, disable) {
        form.getForm().find('input, select')
            .filter(':visible')
            .not('[name=emaildisableall], [name=push]')
            .each(function(){
                form.disableField($(this).attr('name'), disable);
            });
    };

    extension.onFieldChange = function (form, fieldName) {
        if (fieldName == 'emaildisableall') {
            disableFields(form, form.getInput(fieldName).is(":checked"));
        }
    };

    extension.onFormReady = function (form, fieldName) {
        var push = getPushService(), scope;
        if (form.getInput("push").length > 0) {
            scope = getScope(form, "push");
            if (typeof scope.$apply == 'function') {
                scope.$apply(function () {
                    scope.field.value = !push.disabled();
                });
            }
        }
        if (form.getInput("emaildisableall").length > 0) {
            getScope(form, "emaildisableall").field.mapped = true;
        }
    };

}