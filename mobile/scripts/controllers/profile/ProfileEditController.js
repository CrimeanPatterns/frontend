angular.module('AwardWalletMobile').controller('ProfileEditController', [
    '$scope',
    '$q',
    '$state',
    '$stateParams',
    '$http',
    '$location',
    'GlobalError',
    'UserSettings',
    'Refresher',
    'Form',
    function ($scope, $q, $state, $stateParams, $http, $location, GlobalError, UserSettings, Refresher, Form) {
        var formLink = $location.path(),
            needUpdateData = false;

        if (formLink) {
            function filterFormChildren(children) {
                var filtered = [];
                angular.forEach(children, function (field) {
                    if (
                        !(
                            (platform.ios && ["sound", "vibrate"].indexOf(field['name']) !== -1) ||
                            (field['name'] == "vibrate" && !UserSettings.isVibrationSupported())
                        )
                    ) this.push(field);
                }, filtered);
                return filtered;
            }

            $scope.form = {
                link: formLink,
                buttons: {
                    submit: {
                        text: Translator.trans('buttons.save', {}, 'mobile'),
                        display: true
                    }
                },
                "interface": null,
                "extension": null,
                fields: [],
                errors: [],
                title: $stateParams.formTitle,
                showBackButton: typeof $stateParams.formTitle != 'string' || $stateParams.formTitle.length === 0,
                submit: function () {
                    if (passwordComplexity && !passwordComplexity.passwordField) {
                        passwordComplexity.init($('input[type=password][name=first]'), function () {
                            return $('input[name=login]').val() || '';
                        }, function () {
                            return $('input[name=email]').val() || '';
                        });
                    }
                    var data = Form.parseData($scope.form.fields), errors = passwordComplexity && passwordComplexity.passwordField ? passwordComplexity.getErrors() : [];
                    if (errors && errors.length < 1) {
                        $scope.spinnerSubmitForm = true;
                        $http({url: formLink, method: 'PUT', timeout: 30000, data: data}).then(function (response) {
                            response = response.data;
                            $scope.spinnerSubmitForm = false;
                            if (response) {
                                if (response.hasOwnProperty('success')) {
                                    if (response.hasOwnProperty('next')) {
                                        $state.go(response.next.route, response.next.params);
                                    } else {
                                        if(response.language) {
                                            Translator.locale = response.language;
                                            UserSettings.set('language', response.language);
                                            $state.go('index.profile', response, {
                                                reload: true,
                                                inherit: false,
                                                notify: true
                                            });
                                        }else{
                                            $state.go('index.profile', response);
                                        }
                                    }
                                }
                                if (response.hasOwnProperty('submitLabel')) {
                                    if (response.submitLabel === false) {
                                        $scope.form.buttons.submit.display = false;
                                    } else {
                                        $scope.form.buttons.submit.text = response.submitLabel;
                                    }
                                }
                                if (response.hasOwnProperty('jsFormInterface')) {
                                    $scope.form.interface = response.jsFormInterface;
                                }
                                if (response.hasOwnProperty('jsProviderExtension')) {
                                    $scope.form.extension = response.jsProviderExtension;
                                }
                                if (response.hasOwnProperty('children')) {
                                    $scope.form.fields = filterFormChildren(response.children);
                                    $scope.form.errors = response.errors;
                                }
                                if (response.hasOwnProperty('error')) {
                                    $scope.form.errors = [response.error];
                                }
                            }
                        }, function (response) {
                            $scope.spinnerSubmitForm = false;
                        });
                    }
                }
            };
            $scope.spinnerLoadingPage = true;
            $http({url: formLink, method: 'GET', timeout: 30000}).then(function (response) {
                $scope.spinnerLoadingPage = false;
                response = response.data;
                if (response) {
                    if (!response.hasOwnProperty('children')) {
                        GlobalError.show(GlobalError.getHttpError('default'));
                    }
                    if(response.formTitle) {
                        $scope.form.title = response.formTitle;
                        $scope.form.showBackButton = !response.formTitle;
                    }
                    if (response.hasOwnProperty('submitLabel')) {
                        if (response.submitLabel === false) {
                            $scope.form.buttons.submit.display = false;
                        } else {
                            $scope.form.buttons.submit.text = response.submitLabel;
                        }
                    }
                    if (response.hasOwnProperty('jsFormInterface')) {
                        $scope.form.interface = response.jsFormInterface;
                    }
                    if (response.hasOwnProperty('jsProviderExtension')) {
                        $scope.form.extension = response.jsProviderExtension;
                    }
                    if (response.hasOwnProperty('children')) {
                        $scope.form.fields = filterFormChildren(response.children);
                        $scope.form.errors = response.errors;
                    }
                    if (response.hasOwnProperty('error')) {
                        $scope.form.errors = [response.error];
                    }

                    if ($stateParams.hasOwnProperty('needUpdate') && $stateParams['needUpdate'] == true){
                        Refresher.setProperty('needRefresh', true);
                        $stateParams['needUpdate'] = false;
                    }
                }
            }, function (response) {
                $scope.spinnerLoadingPage = false;
            });

            $scope.$on('$stateChangeStart', function (event, toState, toParams, fromState, fromParams, options) {
                if ($scope.form.buttons.submit.display) return;
                if (['index.profile', 'index.profile-edit'].indexOf(toState.name) !== -1) {
                    toParams.needUpdate = needUpdateData;
                }
            });

            var aborter = $q.defer();
            $scope.$on('profile:silent:submit', function () {
                needUpdateData = true;
                if (!aborter.promise.canceled) {
                    aborter.promise.canceled = true;
                    aborter.resolve();
                }
                aborter = $q.defer();
                aborter.promise.canceled = false;
                $scope.spinnerSubmitForm = true;
                $http({
                    url: formLink,
                    method: 'PUT',
                    timeout: aborter.promise,
                    data: Form.parseData($scope.form.fields)
                }).then(function (response) {
                    $scope.spinnerSubmitForm = false;
                    response = response.data;
                    if (response && !response.hasOwnProperty('success')) {
                        GlobalError.show(GlobalError.getHttpError('default'));
                    }
                }, function (response) {
                    $scope.spinnerSubmitForm = false;
                });
            });

        } else {
            $state.go('index.profile');
        }
    }
]);