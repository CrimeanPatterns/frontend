angular.module('AwardWalletMobile').directive('pageForm', ['$timeout', 'Translator', 'btfModal', function ($timeout, Translator, btfModal) {
    return {
        restrict: 'E',
        transclude: true,
        replace: true,
        scope: {
            fields: '=fields',
            errors: '=formErrors',
            showErrors: '=',
            success: '=',
            onSubmit: '&',
            autoSubmit: '=',
            autoSubmitDelay: '=',
            formInterface: '=',
            formExtension: '=',
            recaptcha: '='
        },
        templateUrl: 'templates/directives/form/form.html',
        controller: ['$scope', function ($scope) {
            var q = [];
            return {
                form: {},
                onFormInit: function () {
                },
                onFormSubmit: function () {
                    q.push(arguments[0]);
                },
                submit: function (canSumbit, submitFn) {
                    if (canSumbit && typeof submitFn === 'function') {
                        $scope.success = false;
                        return submitFn();
                    }
                    for (var i = 0; i < q.length; i++) {
                        q[i]();
                    }
                },
                success: function (message) {
                    $scope.success = message;
                },
                recaptcha: $scope.recaptcha
            };
        }],
        link: {
            post: function (scope, element, attrs, pageForm) {
                var $element = angular.element(element),
                    $form = $(element[0]);
                var form = scope[attrs.name],
                    formInterface = scope.formInterface,
                    formExtension = scope.formExtension,
                    formExtensions = [],
                    delayedSubmit;

                if (formInterface && formExtension) {
                    pageForm.onFormInit = function () {
                        var extensions = [];
                        try {
                            eval(formInterface);

                            if (typeof formExtension === 'string') {
                                formExtensions.push(formExtension);
                            } else {
                                formExtensions = formExtension;
                            }

                            angular.forEach(formExtensions, function (extension) {
                                if (typeof extension === 'string') {
                                    eval(extension);
                                    if (typeof addFormExtension !== 'undefined') {
                                        extensions.push(addFormExtension);
                                    }
                                } else if (typeof extension === 'function') {
                                    extensions.push(extension);
                                }
                            });

                            var mobileForm = new MobileForm($form, extensions);
                        } catch (e) {
                            console.log(e);
                        }
                    };
                }

                var trySubmit = function() {
                    $form.find('.ng-pristine').removeClass('ng-pristine');
                    scope.$apply(function () {
                        angular.forEach(form, function (formElement, fieldName) {
                            if (fieldName[0] === '$') return;
                            formElement.$pristine = false;
                            formElement.$setDirty();
                            if (formElement.$invalid && formElement.$error.required) {
                                formElement.$setValidity('required', false);
                            }
                        });
                    });
                    pageForm.submit(!form.$invalid, function () {
                        scope.onSubmit(/** fields */);
                        form.$submitted = false;
                    });
                    if (form.$invalid) {
                        $timeout(function () {
                            $form.find('.errored input').first().focus();
                        });
                    }
                };

                $form.on('submit', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    trySubmit();

                    return false;
                });

                if (scope.autoSubmit) {
                    delayedSubmit = _.debounce(trySubmit, scope.autoSubmitDelay || 750);

                    $form.on('change', 'input[type=text], input[type=checkbox], input[type=hidden], select', null, function (event) {
                        delayedSubmit();
                    });
                }

                pageForm.form = form;
                pageForm.fields = scope.fields;
                scope.$watch(function () {
                    return scope.fields;
                }, function (oldFields, newFields) {
                    if (newFields && oldFields !== newFields) {
                        $timeout(function () {
                            $form.find('input').blur();
                            $form.find('.errored input').first().focus();
                        }, 500);
                    }
                });
            }
        }
    };
}]);

angular.module('AwardWalletMobile').directive('pageFormField', [
    '$state',
    '$stateParams',
    '$http',
    '$templateCache',
    '$q',
    '$compile',
    '$filter',
    'Popup',
    '$timeout',
    function ($state, $stateParams, $http, $templateCache, $q, $compile, $filter, Popup, $timeout) {
        return {
            restrict: 'EA',
            scope: {
                type: '=',
                field: '=',
                showError: '=',
                formInit: '='
            },
            require: '^pageForm',
            compile: function (element, attrs) {
                var getTemplate = function (templateUrl) {
                    var deferred = $q.defer();
                    var template = $templateCache.get(templateUrl);
                    if (template) {
                        deferred.resolve(template);
                    } else {
                        deferred.reject();
                    }
                    return deferred.promise;
                };
                return {
                    pre: function (scope, element, attrs, pageForm) {
                        var formCtrl = pageForm.form;

                        scope.isCordova = platform.cordova;
                        scope.field = scope.field || {};
                        scope.field.showError = scope.showError;

                        function getTemplateUrl(type, field) {
                            if (['passwordMask', 'password'].indexOf(type) > -1 && field && field.attr && field.attr.policy) {
                                return 'templates/directives/form/passwordPrivacy.html';
                            }
                            if (type === 'choice' && field && field.multiple) {
                                return 'templates/directives/form/multipleChoice.html';
                            }
                            return 'templates/directives/form/' + (type === 'password' ? 'passwordMask' : type) + '.html';
                        }

                        var templateUrl = getTemplateUrl(scope.type, scope.field);

                        if (angular.isArray(scope.field.children)) {
                            var field = '<page-form-field ng-repeat="children in field.children" show-error="field.showError" type="children.type" field="children" form-init="false"></page-form-field>';
                            element.append($compile(field)(scope));
                            return;
                        }

                        scope.field.onChange = function (trigger) {
                            trigger = trigger || false;
                            scope.field.changed = true;
                            scope.field.showError = false;
                            if (trigger) {
                                $timeout(function () {
                                    $('input[name="' + scope.field.name + '"]').trigger('change');
                                });
                            }
                        };

                        scope.field.goTo = function () {
                            if (
                                scope.field.attr &&
                                scope.field.attr.route
                            ) {
                                if ($state.get(scope.field.attr.route.name))
                                    $state.go(scope.field.attr.route.name, scope.field.attr.route.params);
                            }
                        };

                        if (scope.type === 'hidden') {
                            scope.$watch('field.value', function (newValue, oldValue) {
                                if (newValue !== oldValue) {
                                    scope.field.onChange(true);
                                }
                            });
                        }

                        if (scope.type === 'text_completion') {
                            scope.field.completions = [];
                            scope.field.onChange = function () {
                                scope.field.changed = true;
                                scope.field.showError = false;
                                scope.field.row = null;
                                if (scope.field.value && scope.field.value.length >= 2) {
                                    $http({
                                        method: 'post',
                                        url: scope.field.completionLink,
                                        data: {queryString: scope.field.value},
                                        timeout: 30000
                                    }).then(function (response) {
                                        response = response.data;
                                        if (response && response.queryString === scope.field.value) {
                                            scope.field.completions = response.completions.map(function (row) {
                                                var regex = new RegExp('(' + scope.field.value.replace(/[^A-Za-z0-9А-Яа-я\s]+/g, '') + ')', 'gi');
                                                row.label = row.label.replace(regex, '<mark>$1</mark>');
                                                return row;
                                            });
                                        }
                                    });
                                } else {
                                    scope.field.close();
                                }
                            };
                            scope.field.input = function (row) {
                                if (row) {
                                    scope.field.row = row;
                                    scope.field.value = row.value;
                                    scope.field.close();
                                }
                            };
                            scope.field.close = function () {
                                if (scope.field.completions.length > 0) {
                                    scope.field.completions = [];
                                    $timeout(function () {
                                        $('input[name="' + scope.field.name + '"]').trigger('change');//hack for cordova app
                                    });
                                }
                            };
                            scope.field.blur = function () {
                                $timeout(function () {
                                    scope.field.close();
                                }, 300);
                            };
                        } else if (scope.type === 'recaptcha') {
                            scope.recaptcha = pageForm.recaptcha;
                        }

                        if (scope.type === 'choice') {
                            if (scope.field.multiple === true) {
                                scope.field.valueMap = {};
                                if (!angular.isArray(scope.field.value)) {
                                    scope.field.value = [];
                                }
                                angular.forEach(scope.field.choices, function (choice) {
                                    scope.field.valueMap[choice.name] = scope.field.value.indexOf(choice.name) > -1;
                                });

                                scope.toggleCheckbox = function (choice) {
                                    scope.field.onChange();
                                    $timeout(function () {
                                        $('input[name="' + scope.field.name + '_' + choice.name + '"]').trigger('change');
                                    });
                                    scope.field.valueMap[choice.name] = !scope.field.valueMap[choice.name];

                                    var i = scope.field.value.indexOf(choice.name);
                                    if (scope.field.valueMap[choice.name]) {
                                        if (i === -1) scope.field.value.push(choice.name);
                                    } else {
                                        if (i > -1) scope.field.value.splice(i, 1);
                                    }
                                };
                            } else {
                                function isEmpty(data) {
                                    const type = typeof(data);

                                    if (type === 'number' || type === 'boolean') {
                                        return false;
                                    }

                                    if (type === 'undefined' || data === null) {
                                        return true;
                                    }

                                    if (typeof(data.length) !== 'undefined') {
                                        return data.length === 0;
                                    }

                                    return false;
                                }

                                var isEmptyValue = isEmpty(scope.field.value),
                                    foundSimilar = false;

                                angular.forEach(scope.field.choices, function (field) {
                                    if (
                                        field.selected === true
                                        || ( !foundSimilar && (field.value === scope.field.value || (isEmptyValue && isEmpty(field.value))) )
                                    ) {
                                        scope.field['selectedOption'] = field;
                                        foundSimilar = true;
                                    }
                                });

                                if (
                                    !scope.field.hasOwnProperty('selectedOption') &&
                                    angular.isArray(scope.field.choices) &&
                                    scope.field.choices.length > 0
                                ) {
                                    scope.field['selectedOption'] = scope.field.choices[0];
                                }

                                scope.field.onChange = function () {
                                    var selected = scope.field['selectedOption'];

                                    scope.field.changed = true;
                                    scope.field.showError = false;

                                    if (
                                        scope.field.hasOwnProperty('formLinks') &&
                                        scope.field.formLinks.hasOwnProperty(selected.value)
                                    ) {
                                        $state.go('index.accounts.agents-add', {
                                            formLink: scope.field.formLinks[selected.value].formLink,
                                            formTitle: scope.field.formLinks[selected.value].formTitle,
                                            formData: pageForm.fields
                                        });
                                        return;
                                    }

                                    if (
                                        scope.field.hasOwnProperty('alerts') &&
                                        scope.field.alerts.hasOwnProperty(selected.value)
                                    ) {
                                        Popup.open({
                                            Message: $filter('text2p')(scope.field.alerts[selected.value]),
                                            hideModal: Popup.close
                                        });
                                    }
                                };
                            }
                        }

                        if (scope.type === 'date') {
                            if (scope.field.value) {
                                scope.field.date = new Date(scope.field.value);
                            }
                            scope.field.onChange = function () {
                                scope.field.value = $filter('date')(scope.field.date, 'yyyy-MM-dd');
                            };
                        }

                        if (scope.type === 'action') {
                            scope.field.action = function () {
                                pageForm.success(scope.field.notice);
                                return $http({
                                    method: scope.field.method,
                                    url: scope.field.link
                                });
                            };
                        }

                        if (['textProperty', 'formLink', 'form'].indexOf(scope.type) > -1 && scope.field.formLink) {
                            scope.field.path = scope.field.formLink.replace('/profile/', '');
                            scope.field.action = function () {
                                $state.go('index.profile-edit', {
                                    formLink: scope.field.formLink,
                                    formTitle: scope.field.formTitle,
                                    action: scope.field.path
                                });
                            };
                        }

                        pageForm.onFormSubmit(function () {
                            scope.$apply(function () {
                                if (scope.field.changed) {
                                    scope.field.errors = [];
                                }
                                scope.field.showError = true;
                            });
                        });

                        if (
                            ['passwordMask', 'password'].indexOf(scope.type) > -1 &&
                            scope.field.attr &&
                            scope.field.attr.policy
                        ) {
                            scope.field.passwordComplexity = false;
                            scope.field.onFocus = function () {
                                var fieldName = scope.field.name;
                                if (scope.field.passwordComplexity === false) {
                                    var inputField = $('input[type=password][name="' + fieldName + '"]');
                                    passwordComplexity.init(inputField, function () {
                                        return $('input[name=login]').val() || '';
                                    }, function () {
                                        return $('input[name=email]').val() || '';
                                    });
                                    scope.field.passwordComplexity = true;
                                }
                            };
                            scope.field.close = function () {
                                $('input[type=password][name="' + scope.field.name + '"]').blur();
                            };
                        }

                        if (
                            ['checkbox', 'switcher'].indexOf(scope.type) !== -1 &&
                            scope.field.attr && scope.field.attr.passwordAccess &&
                            scope.field.attr.passwordAccess.route &&
                            scope.field.attr.passwordAccess.trigger_value
                        ) {
                            scope.field.triggered = scope.field.value === scope.field.attr.passwordAccess.trigger_value;
                            scope.$watch('field.value', function (newValue, oldValue) {
                                if (
                                    newValue !== oldValue &&
                                    scope.field.triggered &&
                                    oldValue === scope.field.attr.passwordAccess.trigger_value
                                ) {
                                    scope.field.value = oldValue;
                                    $http({
                                        method: 'post',
                                        url: scope.field.attr.passwordAccess.route,
                                        timeout: 30000
                                    }).then(function (response) {
                                        response = response.data;
                                        if (response && response.success) {
                                            scope.field.triggered = false;
                                            scope.field.value = newValue;
                                        }
                                    });
                                }
                            });
                        }

                        if (scope.type === 'passwordEdit') {
                            scope.field.onFocus = function () {
                                if (!scope.field.changed && scope.field.value) {
                                    scope.field.oldValue = scope.field.value;
                                    scope.field.value = '';
                                    scope.field.notValidate = true;
                                }
                            };
                            scope.field.onBlur = function () {
                                if (scope.field.value === '' && !scope.field.changed) {
                                    scope.field.value = scope.field.oldValue;
                                    scope.field.oldValue = '';
                                }
                                scope.field.notValidate = false;
                            };
                        }

                        if (scope.type === 'oauth') {
                            var state = {
                                name: $state.current.name,
                                params: $state.params,
                                props: {notify: false}
                            }, data = btoa(JSON.stringify({
                                state: state,
                                href: $state.href(state.name, state.params, state.props)
                            }));
                            scope.openOauth = function () {
                                var url = scope.field.callbackUrl + '/' + data;
                                var h = 650;
                                var w = 450;
                                var y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
                                var x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);

                                if (!platform.cordova) {
                                    app.window = window.open(url, '', 'toolbar=no, location=0, directories=no, status=no, menubar=no, scrollbars=no, resizable=0, copyhistory=no, width=' + w + ', height=' + h + ', top=' + y + ', left=' + x);
                                } else {
                                    app.window = window.open(url, '_system');
                                }
                            };
                            scope.revoke = function () {
                                scope.field.onChange();
                                scope.field.value = '';
                            };
                            scope.$watch(function () {
                                return $state.params.oauthKey;
                            }, function (key) {
                                if (key) {
                                    scope.field.value = key;
                                }
                            });
                        }

                        if (['checkbox', 'switcher'].indexOf(scope.type) !== -1) {
                            scope.toggleCheckbox = function () {
                                if (scope.field.disabled === true) return;
                                scope.field.onChange(true);
                                scope.field.value = !scope.field.value;
                            };
                        }

                        scope.field.isRequired = function () {
                            var fieldName = scope.field.name;
                            if (
                                formCtrl &&
                                formCtrl[fieldName] &&
                                scope.field.required &&
                                formCtrl.$submitted &&
                                !scope.field.notValidate
                            ) {
                                return formCtrl[fieldName].$error.required;
                            } else {
                                return false;
                            }
                        };

                        getTemplate(templateUrl).then(function (html) {
                            element.append(html);
                            if (['html', 'checkbox', 'warningLink'].indexOf(scope.type) !== -1) {
                                element.on('click', function (e) {
                                    e.preventDefault();
                                    var findA = function (elem) {
                                        if (elem.nodeName === 'A') {
                                            return elem;
                                        }
                                        if (elem.parentElement) {
                                            if (elem.parentElement.nodeName === 'A') {
                                                return elem.parentElement;
                                            } else {
                                                return findA(elem.parentElement);
                                            }
                                        }
                                        return null;
                                    }, element = findA(e.target);
                                    if (element !== null && element.nodeName === 'A' && element.href) {
                                        var ref = window.open(element.href, '_blank');
                                        ref.opener = null;
                                        return;
                                    }
                                    if (['checkbox'].indexOf(scope.type) !== -1) {
                                        if (e.target.classList.contains('checkbox-label')) {
                                            scope.$apply(function () {
                                                scope.toggleCheckbox();
                                            });
                                        }
                                    }
                                });
                            }
                            $compile(element.contents())(scope);
                        });

                        scope.$on('$destroy', function () {
                            passwordComplexity.destroy();
                            element.off();
                        });
                    },
                    post: function (scope, element, attrs, pageForm) {
                        if (scope.formInit) {
                            if (typeof pageForm.onFormInit === 'function') {
                                $timeout(function () {
                                    pageForm.onFormInit();
                                });
                            }
                        }
                    }
                };
            }
        };
    }]);
