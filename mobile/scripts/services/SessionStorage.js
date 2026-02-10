angular.module('AwardWalletMobile').service('SessionStorage', [
    '$angularCacheFactory',
    'StorageSettings',
    function ($angularCacheFactory, StorageSettings) {
        return $angularCacheFactory('SessionStorage', angular.extend({}, StorageSettings));
    }
]);