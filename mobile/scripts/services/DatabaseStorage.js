angular.module('AwardWalletMobile').service('DatabaseStorage', [
    '$angularCacheFactory',
    '$rootScope',
    'StorageSettings',
    function ($angularCacheFactory, $rootScope, StorageSettings) {
        return $angularCacheFactory('DatabaseStorage', angular.extend({}, StorageSettings, {
            onExpire: function (key, value) {
                if (key == '/data') {
                    $rootScope.$broadcast('database:expire');
                }
            }
        }));
    }
]);