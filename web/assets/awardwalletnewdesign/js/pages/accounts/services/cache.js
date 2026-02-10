define(['angular-boot'], function (angular) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('cacheService', []).service('accountInfoCache', [
        '$cacheFactory',
        function ($cacheFactory) {
            var cache = $cacheFactory('accountInfo');
            return {
                get: function () {
                    return cache;
                },
                clear: function (account) {
                    cache.remove(account.FID);
                    if (typeof account.SubAccountsArray != 'undefined' && angular.isArray(account.SubAccountsArray)) {
                        angular.forEach(account.SubAccountsArray, function (value) {
                            cache.remove(account.FID + '-' + value.SubAccountID);
                        });
                    }
                },
            };
        },
    ]);
});
