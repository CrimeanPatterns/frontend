angular.module('AwardWalletMobile').controller('AccountHistoryOfferController', [
    '$scope',
    '$stateParams',
    '$http',
    function ($scope, $stateParams, $http) {
        if ($stateParams.uuid) {
            $scope.loading = true;

            $http({
                url: '/account/spent-analysis/transaction-offer',
                data: {
                    uuid: $stateParams.uuid,
                    source: 'transaction-history&mid=mobile',
                    ...$stateParams.extraData
                },
                method: 'POST',
                timeout: 30000
            }).then(function (response) {
                if (angular.isString(response.data)) {
                    $scope.content = response.data;
                }
            });
        } else {
            $scope.back('index.accounts.list');
        }

    }
]);