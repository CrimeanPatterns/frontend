angular.module('AwardWalletMobile').service('SessionService', [
    'SessionStorage',
    '$rootScope',
    function (SessionStorage, $rootScope) {
        var properties = {
            authorized: null,
            etag: '',
            timestamp: '',
            pause: null,
            pincode: null,
            'pincode-skipped': false,
            developer: false
        };
        var cache = SessionStorage.get('current');

        if (!cache) {
            SessionStorage.put('current', properties);
        } else {
            angular.extend(properties, cache);
        }

        var _this = {
            properties: properties,
            getProperty: function (name) {
                return properties[name];
            },
            setProperty: function (name, value) {
                properties[name] = value;
                SessionStorage.put('current', properties);
                return properties[name];
            },
            destroy: function () {
                properties.authorized = false;
                properties.etag = '';
                properties.timestamp = '';
                properties.pause = null;
                properties.pincode = null;
                properties.developer = false;
                properties['locations-total'] = 0;
                properties['locations-tracked'] = 0;
                SessionStorage.remove('current');
            }
        };

        $rootScope.$on('app:storage:destroy', _this.destroy);

        return _this;
    }
]);