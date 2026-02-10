angular.module('AwardWalletMobile').service('UserSettingsStorage', [
    '$angularCacheFactory',
    'StorageSettings',
    function ($angularCacheFactory, StorageSettings) {
        return $angularCacheFactory('UserSettingsStorage', angular.extend({}, StorageSettings, {
            maxAge: Number.MAX_VALUE
        }));
    }
]);

angular.module('AwardWalletMobile').service('UserSettings', [
    '$rootScope',
    'UserSettingsStorage',
    'SessionService',
    function ($rootScope, UserSettingsStorage, SessionService) {
        var defaultSettings = {
                sound: true,
                vibrate: true,
                mpDisableAll: false,
                mpBookingMessages: true,
                mpCheckins: true,
                mpRetailCards: true,
                language: SessionService.getProperty('language'),
                locale: null
            },
            settings = angular.copy(defaultSettings),
            currentUserId = 0,
            cache;

        var _this = {
            has: function (name) {
                return typeof settings[name] !== 'undefined';
            },
            get: function (name) {
                return settings[name];
            },
            set: function (name, value) {
                var s = {};
                s[name] = value;
                _this.extend(s);
            },
            extend: function (s) {
                var changed = {}, event = false;
                angular.forEach(Object.keys(settings), function (field) {
                    changed[field] = false;
                    if (typeof s[field] !== 'undefined' && settings[field] !== s[field]) {
                        changed[field] = true;
                        event = true;
                    }
                });
                angular.extend(settings, s);
                UserSettingsStorage.put('data/' + currentUserId, settings);
                if (event) {
                    $rootScope.$broadcast('userSettings:update', changed);
                }
            },
            isVibrationSupported: function () {
                return !(platform && platform.cordova && platform.ipad);
            },
            isMpDisableAll: function () {
                return settings.mpDisableAll;
            },
            isMpEnabled: function (name) {
                return !settings.mpDisableAll && (typeof settings[name] !== 'undefined' && settings[name]);
            },
            isSoundEnabled: function () {
                return !platform.cordova || settings.sound;
            },
            loadSettings: function (userId) {
                currentUserId = !userId ? 0 : userId;
                cache = UserSettingsStorage.get('data/' + currentUserId);
                settings = angular.copy(defaultSettings);
                if (!cache) {
                    UserSettingsStorage.put('data/' + currentUserId, settings);
                } else {
                    UserSettingsStorage.put('data/' + currentUserId, angular.extend(settings, cache));
                }
            },
            getDefaultSettings: function () {
                return defaultSettings;
            }
        };

        _this.loadSettings();

        $rootScope.$on('app:storage:destroy', function () {
            _this.loadSettings();
        });

        return _this;
    }
]);