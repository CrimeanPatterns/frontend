/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    var methods = {
        getScope: function(form, inputName) {
            return angular.element(form.getInput(inputName).get(0)).scope();
        },
        'get': function(serviceName) {
            return angular.element(document.body).injector().get(serviceName);
        },
        disableEmailFields: function(form, disable) {
            form.getForm().find('input, select')
                .filter('[name^=email]:visible')
                .not('[name=emaildisableall]')
                .each(function(){
                    form.disableField($(this).attr('name'), disable);
                });
        },
        disablePushFields: function(form, disable) {
            form.getForm().find('input')
                .filter('[name^=mp]:visible')
                .not('[name=mpdisableall]')
                .each(function(){
                    form.disableField($(this).attr('name'), disable);
                });
        },
        applySettings: function() {
            var newSettings = {};
            angular.forEach(Object.keys(userSettings.getDefaultSettings()), function (optionName) {
                if (typeof appSettings[optionName] != "undefined") {
                    newSettings[optionName] = appSettings[optionName];
                }
            });
            userSettings.extend(newSettings);
            appSettings = {};
        }
    };
    var $rootScope = methods.get('$rootScope'),
        $timeout = methods.get('$timeout'),
        userSettings = methods.get('UserSettings'),
        appSettings = {},
        timeout;

    extension.onFieldChange = function (form, fieldName) {
        var checked = form.getInput(fieldName).is(":checked");
        if (fieldName == 'emaildisableall') {
            methods.disableEmailFields(form, checked);
        } else if (fieldName == 'mpdisableall') {
            methods.disablePushFields(form, checked);
        }

        if (['sound', 'vibrate'].indexOf(fieldName) !== -1 || fieldName.substr(0, 2) == 'mp') {
            appSettings[methods.getScope(form, fieldName).field.name] = !!checked;
        }
        if (timeout) $timeout.cancel(timeout);
        timeout = $timeout(function() {
            methods.applySettings();
            $rootScope.$broadcast('profile:silent:submit');
        }, 750);
    };

    extension.onFormReady = function (form, fieldName) {
        var scope;
        if (form.getInput("sound").length > 0) {
            scope = methods.getScope(form, "sound");
            if (typeof scope.$apply == 'function') {
                scope.$apply(function () {
                    scope.field.value = userSettings.get('sound');
                });
            }
        }
        if (form.getInput("vibrate").length > 0) {
            scope = methods.getScope(form, "vibrate");
            if (typeof scope.$apply == 'function') {
                scope.$apply(function () {
                    scope.field.value = userSettings.get('vibrate');
                });
            }
        }
    };

}