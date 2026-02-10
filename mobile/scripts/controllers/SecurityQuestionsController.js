angular.module('AwardWalletMobile').controller('SecurityQuestionsController', [
    '$scope',
    '$state',
    '$stateParams',
    '$timeout',
    'SessionService',
    'UserService',
    'Database',
    'Splashscreen',
    'GlobalError',
    function ($scope, $state, $stateParams, $timeout, SessionService, UserService, Database, Splashscreen, GlobalError) {
        var errors_count = 0, max_errors = 3;

        if (!angular.isObject($stateParams.securityQuestions)) {
            return $state.go('unauth.login');
        }

        var getData = function () {
            $scope.form.loading = true;
            Database.query().then(function (response) {
                $scope.form.loading = false;
                if (response && response.data && angular.isObject(response.data)) {
                    Database.save(response.data);
                    if ($stateParams.toState) {
                        $state.go($stateParams.toState, $stateParams.toParams);
                    } else {
                        $state.go('index.accounts.list');
                    }
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

        function parse(form) {
            var data = {};
            angular.forEach(form, function (field) {
                if (field.children) {
                    data[field.name] = parse(field.children);
                } else if (field['type'] === 'choice') {
                    data[field['name']] = field.selectedOption['value'];
                } else if (!field.ignore && !(field.hasOwnProperty("mapped") && field.mapped === false)) {
                    data[field.name] = field.value;
                }
            });
            return data;
        }

        $scope.form = {
            fields: $stateParams.securityQuestions.form.children,
            title: $stateParams.securityQuestions.notice,
            interface: $stateParams.securityQuestions.form.jsFormInterface,
            extension: $stateParams.securityQuestions.form.jsProviderExtension,
            submit: function () {
                var request = angular.extend({}, $stateParams.request, {security_question: parse($scope.form.fields)});

                $scope.spinnerSubmitForm = true;

                UserService.login(request).then(function (response) {
                    if (response && angular.isObject(response.data)) {
                        response = response.data;

                        if (response.success) {
                            SessionService.setProperty('authorized', true);
                            getData();
                        } else {
                            $scope.spinnerSubmitForm = false;

                            if (response.error) {
                                $scope.form.errors = [response.error];
                            } else {
                                $scope.form.fields = response.security_question.form.children;
                                if (response.security_question.error) {
                                    $scope.form.errors = [response.security_question.error];
                                }
                                if (response.security_question.form.hasOwnProperty('jsFormInterface')) {
                                    $scope.form.interface = response.security_question.form.jsFormInterface;
                                }
                                if (response.security_question.form.hasOwnProperty('jsProviderExtension')) {
                                    $scope.form.extension = response.security_question.form.jsProviderExtension;
                                }
                            }
                        }
                    }
                }, function (response) {
                    $scope.spinnerSubmitForm = false;
                    $scope.form.error = GlobalError.getHttpError(response.status);
                });
            }
        };
    }
]);