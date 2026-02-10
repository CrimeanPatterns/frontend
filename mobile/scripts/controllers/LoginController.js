angular.module('AwardWalletMobile').controller('LoginController', [
    '$scope',
    '$state',
    '$stateParams',
    '$timeout',
    'SessionService',
    'UserService',
    'Database',
    'Splashscreen',
    'GlobalError',
    '$cordovaListener',
    '$cordovaClipboard',
    'ReCaptcha',
    function ($scope, $state, $stateParams, $timeout, SessionService, UserService, Database, Splashscreen, GlobalError, $cordovaListener, $cordovaClipboard, ReCaptcha) {

        function initialRedirect() {
            if ($stateParams.BackTo) {
                // return to desktop version
                if ($stateParams.BackTo.indexOf('/user/') === 0) {
                    var origin = document.location.origin;
                    var url = new URL(origin + $stateParams.BackTo);
                    url.searchParams.append('KeepDesktop', '1');
                    document.location.href = url.toString();
                    return;
                }
            }
            if ($stateParams.toState) {
                $state.go($stateParams.toState, $stateParams.toParams);
            } else {
                $state.go('index.accounts.list');
            }
        }

        if (SessionService.getProperty('authorized')) {
            Splashscreen.open();
            initialRedirect()
        }

        var errors_count = 0, max_errors = 3;
        var getData = function () {
            $scope.form.loading = true;
            Database.query().then(function (response) {
                $scope.form.loading = false;
                if (response && response.data && angular.isObject(response.data)) {
                    Database.save(response.data);
                    initialRedirect();
                }
            }, function () {
                errors_count++;
                $scope.form.loading = false;
                if (errors_count >= max_errors) {
                    errors_count = 0;
                    $scope.form.error = GlobalError.getHttpError('default');
                } else {
                    getData();
                }
            });
        };

        $scope.actions = {
            registration: function () {
                $state.go('unauth.registration', $stateParams);
            }
        };

        $scope.recaptcha = {
            autoSubmit: false,
            widgetId: null,
            response: null,
            key: '',
            url: null,
            vendor: null,
            isInitialized: function() {
                return $scope.recaptcha.widgetId != null;
            },
            reset: function() {
                if ($scope.recaptcha.isInitialized() && typeof($scope.recaptcha.response) === 'string') {
                    $scope.recaptcha.response = null;
                    ReCaptcha.reload();
                    // console.log('recaptcha, reset');
                }
            },
            setResponse: function (response) {
                // console.log('recaptcha, setResponse');
                $scope.recaptcha.response = response;
                if ($scope.recaptcha.autoSubmit) {
                    $scope.form.submit();
                }
            },
            onCreate: function (widgetId) {
                $scope.recaptcha.widgetId = widgetId;
                $scope.recaptcha.requestCaptcha();
                // console.log('recaptcha, onCreate', widgetId)
            },
            cbExpiration: function() {
                // console.log('recaptcha, expired');
                $scope.recaptcha.response = null;
            },
            requestCaptcha: function () {
                if ($scope.recaptcha.isInitialized()) {
                    // console.log('recaptcha, requestCaptcha');
                    $scope.form.loading = true;
                    try {
                        $scope.recaptcha.response = ReCaptcha.getResponse($scope.recaptcha.widgetId);
                    } catch (err) {
                        console.log(err);
                    }

                    if (typeof($scope.recaptcha.response) !== 'string') {
                        $scope.recaptcha.autoSubmit = true;
                        //ReCaptcha.execute($scope.recaptcha.widgetId);
                    } else {
                        $scope.recaptcha.autoSubmit = false;
                    }
                }
            }
        };

        $scope.form = {
            fields: {
                login: {
                    value: ''
                },
                password: {
                    value: ''
                },
                _otc: {
                    value: ''
                }
            },
            submit: function () {
                $scope.form.error = null;
                //$scope.form.message = null;
                $scope.form.loading = true;

                $scope.form.fields.password.blur();
                $scope.form.fields.login.blur();
                $scope.form.fields._otc.blur();

                var request = {
                    recaptcha: $scope.recaptcha.response
                };

                if ($scope.form.fields.login.value && $scope.form.fields.password.value) {
                    request.login_password = {
                        login: $scope.form.fields.login.value,
                        pass: $scope.form.fields.password.value
                    };
                }

                if ($scope.form.otcField && $scope.form.fields._otc.value) {
                    request[$scope.form.otcField] = $scope.form.fields._otc.value;
                }

                UserService.login(request).then(function (response) {
                    $scope.form.loading = false;

                    if (angular.isObject(response) && response.hasOwnProperty('data')) {
                        response = response.data;

                        if (response.success) {
                            SessionService.setProperty('authorized', true);
                            getData();
                        } else {
                            $scope.form.otcRequired = false;
                            $scope.form.label = null;
                            $scope.form.hint = null;
                            $scope.form.message = null;

                            const {recaptcha} = response;

                            if (angular.isObject(recaptcha)) {
                                const {required, error, key, url, vendor} = recaptcha;

                                $scope.recaptcha.reset();

                                if (required) {
                                    $scope.recaptcha.requestCaptcha();
                                }

                                if (angular.isString(key)) {
                                    $scope.recaptcha.key = key;
                                    $scope.recaptcha.url = url;
                                    $scope.recaptcha.vendor = vendor;
                                }

                                // console.log(recaptcha);
                            }

                            if (angular.isObject(response.login_password)) {
                                if (response.login_password.error) {
                                    $scope.form.error = response.login_password.error;
                                }
                                $scope.form.fields.password.value = '';
                                $scope.form.fields.password.focus();
                            }

                            if (
                                angular.isObject(response.one_time_code_by_app) ||
                                angular.isObject(response.one_time_code_by_email)
                            ) {
                                var property = 'one_time_code_by_app';

                                if (angular.isObject(response.one_time_code_by_email)) {
                                    property = 'one_time_code_by_email';
                                }

                                if (response[property].error) {
                                    $scope.form.error = response[property].error;
                                }

                                $scope.form.label = response[property].label;
                                $scope.form.hint = response[property].hint;
                                $scope.form.message = response[property].notice;

                                $scope.form.otcField = property;
                                $scope.form.otcRequired = true;
                                $scope.form.fields._otc.value = '';
                                $scope.form.fields._otc.focus();
                            }

                            if (angular.isObject(response.security_question)) {
                                return $state.go('unauth.security-questions', {
                                    request: request,
                                    securityQuestions: response.security_question
                                });
                            }

                            if (response.error) {
                                $scope.form.error = response.error;
                            }
                        }
                    }
                }, function (response) {
                    $scope.form.error = GlobalError.getHttpError(response.status);
                    $scope.form.loading = false;
                    $scope.form.otcField = null;
                    $scope.form.otcRequired = false;
                    $scope.form.label = null;
                    $scope.form.hint = null;
                    $scope.recaptcha.reset();
                });
            },
            back: function () {
                $scope.form.error = null;
                $scope.form.loading = false;
                $scope.form.otcField = null;
                $scope.form.otcRequired = false;
                $scope.form.label = null;
                $scope.form.hint = null;
                $scope.form.fields._otc.value = '';
                $scope.form.message = null;
            },
            loading: false,
            error: null,
            otcRequired: false,
            otcField: null,
            label: null,
            hint: null
        };
    }
]);
