angular.module('AwardWalletMobile').controller('RecoveryPasswordController', [
    '$scope',
    '$state',
    '$http',
    'SessionService',
    '$stateParams',
    'GlobalError',
    function ($scope, $state, $http, SessionService, $stateParams, GlobalError) {
        $scope.view = {
            form: {}
        };
        if (SessionService.getProperty('authorized')) {
            $state.go('logout', {toState: 'unauth.password-recovery.recovery', toParams: $stateParams});
        } else {
            var parse = function parse(form) {
                var data = {};
                angular.forEach(form, function (field) {
                    if (field.children) {
                        data[field.name] = parse(field.children);
                    } else {
                        data[field.name] = field.value;
                    }
                });
                return data;
            };
            $scope.view.spinnerLoadingPage = true;
            $scope.setNewPassword = $stateParams.UserId && $stateParams.Hash;
            var params = ($scope.setNewPassword ? '/change/' + $stateParams.UserId + '/' + $stateParams.Hash : '');
            $http({url: '/recover' + params, method: 'GET', timeout: 30000}).then(function (response) {
                $scope.view.spinnerLoadingPage = false;
                if (response.data.hasOwnProperty('error')) {
                    $scope.view.form.errors = [response.data.error];
                } else if (!response.data.hasOwnProperty('form')) {
                    GlobalError.show(GlobalError.getHttpError('default'));
                } else {
                    $scope.view.form.fields = response.data.form.children;
                    $scope.view.form.errors = response.data.form.errors;
                }
            }, function (response) {
                $scope.view.spinnerLoadingPage = false;
            });

            $scope.submit = function () {
                var data = parse($scope.view.form.fields), errors = passwordComplexity && passwordComplexity.passwordField ? passwordComplexity.getErrors() : [];
                if (!$scope.setNewPassword || ($scope.setNewPassword && errors && errors.length < 1)) {
                    $scope.view.spinnerSubmitForm = true;
                    $scope.view.form.errors = '';
                    $scope.view.form.success = '';
                    $http({
                        url: '/recover' + params,
                        method: 'POST',
                        data: data,
                        timeout: 30000
                    }).then(function (response) {
                        $scope.view.spinnerSubmitForm = false;
                        if (response.data.hasOwnProperty('success') && response.data.success) {
                            $scope.view.form.success = response.data.message;
                            $scope.view.hideError = true;
                        } else if (response.data.hasOwnProperty('error')) {
                            $scope.view.form.errors = [response.data.error];
                        } else if (!response.data.hasOwnProperty('form')) {
                            GlobalError.show(GlobalError.getHttpError('default'));
                        } else {
                            $scope.view.form.fields = response.data.form.children;
                            if (response.data.hasOwnProperty('errors'))
                                $scope.view.form.errors = response.data.form.errors;
                        }

                    }, function (response) {
                        $scope.view.spinnerSubmitForm = false;
                    });
                }else {
                    $('input[type=password][name=first]').focus();
                }
            };
        }
    }
]);