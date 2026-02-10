angular.module('AwardWalletMobile').controller('AccountsAddController', [
    'Translator',
    '$scope',
    '$state',
    '$stateParams',
    '$filter',
    'Providers',
    'GlobalError',
    function (Translator, $scope, $state, $stateParams, $filter, Providers, GlobalError) {
        var providers;

        $scope.view = {
            showScan: !$stateParams.scanData,
            query: '',
            form: null,
            clear: function () {
                $scope.view.query = '';
                $scope.view.search();
            },
            open: function (stateParams) {
                var stateName,
                    params = {};
                stateName = 'index.accounts.account-add';
                if (stateParams.kindId) {
                    stateName = 'index.accounts.add';
                    if (['custom', 'coupon', 'passport', 'traveler-number'].indexOf(stateParams.kindId) > -1) {
                        params.providerId = stateParams.kindId;
                        stateName = 'index.accounts.account-add';
                    } else {
                        params.kindId = stateParams.kindId;
                    }
                } else {
                    params = stateParams;
                }
                params = angular.extend(params, {scanData: $stateParams.scanData, formData: $stateParams.formData});
                $state.go(stateName, params);
            },
            search: function (query) {
                var request = {
                    queryString: query
                };

                if ($stateParams.scanData) {
                    request.scope = 'all';
                }

                $scope.view['error-search'] = false;

                if (query && query.length > 0) {
                    if (providers && providers.length > 0) {
                        $scope.view.providers = $filter('filter')(providers, {DisplayName: query});
                    } else {
                        $scope.spinnerAddSpin = true;
                        Providers.abort();
                        Providers.getResource().search({
                            providerId: $stateParams.kindId
                        }, request, function (response) {
                            $scope.spinnerAddSpin = false;
                            if (typeof response === 'object') {
                                if (query === response.queryString) {
                                    $scope.view.providers = response.providers;
                                }
                                if (response.hasOwnProperty('error')) {
                                    $scope.view['error-search'] = response.error;
                                    $scope.view.providers = [];
                                }
                            }
                        }, function () {
                            $scope.spinnerAddSpin = false;
                        });
                    }
                } else {
                    $scope.view.providers = providers;
                }
            },
            filterHidden: (item) => {
                return !item.hidden;
            }
        };

        if ($stateParams.kindId) {
            $scope.spinnerAddSpin = true;
            Providers.getResource().query({
                providerId: $stateParams.kindId,
                scope: $stateParams.scanData ? 'all' : null
            }, function (response) {
                $scope.spinnerAddSpin = false;
                if (typeof response === 'object' && response.hasOwnProperty('providers')) {
                    providers = response.providers;
                    $scope.view.providers = providers;
                } else {
                    GlobalError.show(GlobalError.getHttpError('default'));
                }
            }, function () {
                $scope.spinnerAddSpin = false;
            });
        }
    }
]);