/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    var methods = {
        'get': function(serviceName) {
            return angular.element(document.body).injector().get(serviceName);
        }
    };
    var $rootScope = methods.get('$rootScope'),
        $timeout = methods.get('$timeout'),
        timeout;

    extension.onFieldChange = function (form, fieldName) {
        if (timeout) $timeout.cancel(timeout);
        timeout = $timeout(function() {
            $rootScope.$broadcast('profile:silent:submit');
        }, 750);
    };
}