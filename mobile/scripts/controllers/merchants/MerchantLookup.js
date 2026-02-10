angular.module('AwardWalletMobile').controller('MerchantLookupController', [
    'Translator',
    '$scope',
    '$http',
    '$stateParams',
    '$state',
    function (Translator, $scope, $http, $stateParams, $state) {
        const search = (query) => {
            if (query && query.length >= 3) {
                $scope.view.loading = true;

                $http({
                    url: '/account/merchants/data',
                    method: 'POST',
                    timeout: 30000,
                    data: {
                        query: query
                    }
                }).then(function (response) {
                    response = response.data;
                    $scope.view.loading = false;
                    if (angular.isArray(response)) {
                        $scope.view.results = response;
                    }
                }, function (response) {
                    $scope.view.loading = false;
                });
            }else{
                $scope.view.results = null;
            }
        };

        $scope.view = {
            query: '',
            results: null,
            clear: function () {
                $scope.view.query = '';
                $scope.view.results = null;
            },
            search,
            loading: false
        };
    }
]);
