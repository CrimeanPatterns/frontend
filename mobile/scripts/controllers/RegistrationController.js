angular.module('AwardWalletMobile').service('RegisterCache', [
    '$cacheFactory',
    function ($cacheFactory) {
        return $cacheFactory('registerPage');
    }
]);
angular.module('AwardWalletMobile').controller('RegistrationController', [
    '$scope',
    '$state',
    '$stateParams',
    '$http',
    'SessionService',
    'UserService',
    'RegisterCache',
    'GlobalError',
    '$injector',
    function ($scope, $state, $stateParams, $http, SessionService, UserService, RegisterCache, GlobalError, $injector) {
        $scope.view = {
            form: {
                "interface": null,
                "extension": null
            },
            back: function () {
                $state.go('unauth.login', $stateParams);
            }
        };

        $scope.recaptcha = {
            autoSubmit: false,
            show: !platform.cordova,
            widgetId: null,
            response: null,
            recaptchaService: function() {
                return $injector.get("ReCaptcha");
            },
            isShown: function() {
                return this.show && this.widgetId != null;
            },
            reset: function() {
                if (this.show) {
                    this.response = null;
                    if (this.widgetId != null) {
                        this.recaptchaService().reload();
                    }
                }
            },
            setResponse: function (response) {
                this.response = response;
                if (this.autoSubmit) {
                    $scope.submit();
                }
            },
            onCreate: function (widgetId) {
                this.widgetId = widgetId;
            },
            cbExpiration: function() {
                this.response = null;
            }
        };

        if (SessionService.getProperty('authorized')) {
            $state.go('index.accounts.list');
        } else {
            var parse = function parse(form) {
                var data = {};
                angular.forEach(form, function (field) {
                    if (field.children) {
                        data[field.name] = parse(field.children);
                    } else if (!field.ignore && !(field.hasOwnProperty("mapped") && field.mapped === false)) {
                        data[field.name] = field.value;
                    }
                });
                return data;
            };
            if (!RegisterCache.get('fields')) {
                $scope.spinnerLoadingPage = true;
                $http({url: '/register', method: 'GET', timeout: 30000}).then(function (response) {
                    $scope.spinnerLoadingPage = false;
                    if (response.data.hasOwnProperty('error')) {
                        $scope.view.form.errors = [response.data.error];
                    } else if (!response.data.hasOwnProperty('children')) {
                        GlobalError.show(GlobalError.getHttpError('default'));
                    } else {
                        $scope.view.form.fields = response.data.children;
                        $scope.view.form.errors = response.data.errors;
                        if (response.data.hasOwnProperty('jsFormInterface')) {
                            $scope.view.form.interface = response.data.jsFormInterface;
                        }
                        if (response.data.hasOwnProperty('jsProviderExtension')) {
                            $scope.view.form.extension = response.data.jsProviderExtension;
                        }
                    }
                }, function (response) {
                    $scope.spinnerLoadingPage = false;
                });
            } else {
                $scope.view.form.fields = RegisterCache.get('fields');
                $scope.recaptcha.reset();
            }

            $scope.submit = function () {
                if ($scope.recaptcha.isShown()) {
                    var recaptchaService = $scope.recaptcha.recaptchaService();
                    try {
                        $scope.recaptcha.response = recaptchaService.getResponse($scope.recaptcha.widgetId);
                    } catch (err) {console.log(err);}
                    if (typeof($scope.recaptcha.response) !== 'string') {
                        $scope.recaptcha.autoSubmit = true;
                        //recaptchaService.execute($scope.recaptcha.widgetId);
                        return;
                    } else {
                        $scope.recaptcha.autoSubmit = false;
                    }
                }
                if (passwordComplexity && !passwordComplexity.passwordField) {
                    passwordComplexity.init($('input[type=password][name=first]'), function () {
                        return $('input[name=login]').val() || '';
                    }, function () {
                        return $('input[name=email]').val() || '';
                    });
                }
                var data = parse($scope.view.form.fields), errors = passwordComplexity && passwordComplexity.passwordField ? passwordComplexity.getErrors() : [];
                if (errors && errors.length < 1) {
                    $scope.spinnerSubmitForm = true;
                    $scope.view.error = '';
                    if ($scope.recaptcha.isShown()) {
                        data = angular.extend(data, {recaptcha_response: $scope.recaptcha.response});
                    }
                    $http({url: '/register', data: data, method: 'POST', timeout: 30000}).then(function (response) {
                        if (response.data.hasOwnProperty('userId')) {
                            SessionService.setProperty('authorized', true);
                            if ($stateParams.toState) {
                                $state.go($stateParams.toState, $stateParams.toParams);
                            } else {
                                $state.go('index.accounts.list');
                            }
                            return;
                        } else if (response.data.hasOwnProperty('error')) {
                            $scope.view.form.errors = [response.data.error];
                            $scope.recaptcha.reset();
                        } else if (!response.data.hasOwnProperty('children')) {
                            GlobalError.show(GlobalError.getHttpError('default'));
                            $scope.recaptcha.reset();
                        } else {
                            $scope.view.form.fields = response.data.children;
                            if (response.data.hasOwnProperty('errors'))
                                $scope.view.form.errors = response.data.errors;
                            $scope.recaptcha.reset();
                        }
                        $scope.spinnerSubmitForm = false;
                    }, function (response) {
                        $scope.spinnerSubmitForm = false;
                        $scope.recaptcha.reset();
                    });
                } else {
                    $('input[type=password][name=first]').focus();
                }
            };

            var unbind = $scope.$on('$stateChangeStart', function (event, toState, toParams, fromState, fromParams) {
                if (toState.name === 'unauth.terms') {
                    RegisterCache.put('fields', $scope.view.form.fields);
                } else {
                    RegisterCache.remove('fields');
                }
                unbind();
            });
        }
    }
]);
