angular.module('AwardWalletMobile').controller('ApiAccessController', [
    '$scope',
    '$http',
    '$stateParams',
    'connectionInfo',
    function ($scope, $http, $stateParams, connectionInfo) {
        $scope.view = {
            close: function () {
                window.close();
            }
        };

        $scope.view = connectionInfo.data;

        if (connectionInfo && connectionInfo.data && connectionInfo.data.code) {
            var url = '/connections/approve/' + $stateParams.Hash + '/' + $stateParams.AccessLevel;

            if ($stateParams.AuthKey) {
                url += '/' + $stateParams.AuthKey;
            }

            $scope.view.loading = false;
            $scope.view.accept = function () {
                $scope.view.loading = true;
                $http({
                    method: 'post',
                    url: url,
                    timeout: 30000
                }).then(function (response) {
                    response = response.data;
                    if (response.success) {
                        if (window.opener) {
                            window.opener.location = response.callbackUrl;
                            window.close();
                        } else {
                            window.location = response.callbackUrl;
                        }
                    }
                    $scope.view.loading = false;
                });
            };
            $scope.view.deny = function () {
                if (window.opener) {
                    window.opener.location = connectionInfo.data.denyUrl;
                    window.close();
                } else {
                    window.location = connectionInfo.data.denyUrl;
                }
            };
        }
    }
]);