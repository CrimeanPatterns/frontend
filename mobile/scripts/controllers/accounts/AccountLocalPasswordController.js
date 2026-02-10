angular.module('AwardWalletMobile').controller('AccountLocalPasswordController', [
    '$scope',
    'Updater',
    'UpdateLocalPasswordPopup',
    function ($scope, Updater, UpdateLocalPasswordPopup) {
        var accountId = $scope.accountId;
        $scope.password = '';
        $scope.submit = function () {
            if ($scope.password && $scope.password.length > 0) {
                UpdateLocalPasswordPopup.close();
                Updater.donePassword(accountId, $scope.password);
                Updater.nextPopup();
            }
        };

        $scope.close = function () {
            UpdateLocalPasswordPopup.close();
            Updater.cancelPassword(accountId);
            Updater.nextPopup();
        };
    }
]);

angular.module('AwardWalletMobile').controller('AutoLoginLocalPasswordController', [
    '$scope',
    'AccountLocalPassword',
    function ($scope, AccountLocalPassword) {
        $scope.form = {};
        if ($scope.accountId) {
            $scope.spinnerSubmitForm = false;
            AccountLocalPassword.getResource().getLocalPassword({accountId: $scope.accountId}, function (response) {
                $scope.title = response['DisplayName'];
                $scope.form.fields = response['formData']['children'];
                $scope.form.errors = response['formData']['errors'];
                $scope.submit = function () {
                    var data = {};
                    angular.forEach($scope.form.fields, function (field) {
                        if (field['type'] == 'choice') {
                            data[field['name']] = field.selectedOption['value'];
                        } else {
                            data[field['name']] = field['value'];
                        }
                    });
                    $scope.spinnerSubmitForm = true;
                    AccountLocalPassword.getResource().saveLocalPassword({
                        accountId: $scope.accountId
                    }, data, function (response) {
                        $scope.spinnerSubmitForm = false;
                        if (response.hasOwnProperty('formData')) {
                            $scope.form.fields = response['formData']['children'];
                            $scope.form.errors = response['formData']['errors'];
                        } else {
                            if (angular.isFunction($scope.callback)) {
                                $scope.callback(true);
                            }
                        }
                    }, function () {
                        $scope.spinnerSubmitForm = false;
                        if (angular.isFunction($scope.callback)) {
                            $scope.callback(false);
                        }
                    });
                };
            });
        }
        $scope.$on('$destroy', function(){
            AccountLocalPassword.abort();
        });
    }
]);