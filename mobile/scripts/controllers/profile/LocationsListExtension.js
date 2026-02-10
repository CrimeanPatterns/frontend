/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    var methods = {
        'get': function(serviceName) {
            return angular.element(document.body).injector().get(serviceName);
        },
        disableFields: function(form, disable) {
            form.getForm().find('input:visible:not(":checked")')
                .each(function(){
                    form.disableField($(this).attr('name'), disable);
                });
        }
    };
    var maxTracking = methods.get('Database').getProperty('constants').maxTracking,
        $rootScope = methods.get('$rootScope'),
        $timeout = methods.get('$timeout'),
        timeout;

    extension.onFieldChange = function (form, fieldName) {
        var checked = form.getInput(fieldName).is(":checked");
        var totalChecked = form.getForm().find('input:visible:checked').length;
        methods.disableFields(form, totalChecked >= maxTracking);

        if (timeout) $timeout.cancel(timeout);
        timeout = $timeout(function() {
            $rootScope.$broadcast('profile:silent:submit');
        }, 750);
    };

}