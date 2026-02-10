angular.module('AwardWalletMobile').service('Database', [
    '$http',
    '$q',
    'DatabaseStorage',
    'SessionService',
    'Data',
    '$rootScope',
    function ($http, $q, DatabaseStorage, SessionService, Data, $rootScope) {
        var data = DatabaseStorage.get('/data');

        function getVersion() {
            var version = DatabaseStorage.get('version');
            return version || app.version;
        }

        var obj = {
            version: getVersion,
            get: function () {
                var _this = this, deferred = $q.defer(),
                    dataVersion = (_this.version()).split('.'), appVersion = app.version.split('.');
                if ((appVersion[0] > dataVersion[0] && DatabaseStorage.get('/data')) || !DatabaseStorage.get('/data')) {
                    _this.query().then(function (response) {
                        if (response && angular.isObject(response.data)) {
                            _this.save(response.data);
                            deferred.resolve();
                        } else {
                            deferred.reject();
                        }
                    }, function () {
                        deferred.reject();
                    });
                } else {
                    deferred.resolve();
                }
                return deferred.promise;
            },
            query: function (silent) {
                var defer = $q.defer(), _this = this;
                Data.getResource().query(silent).then(function (response) {
                    var etag = response.headers('ETag');
                    var date = response.headers('Date');
                    if (date) {
                        SessionService.setProperty('timestamp', (new Date(date).getTime()) / 1000);
                    }
                    if (response.status == 304 && (_this.version() !== app.version || !data)) {
                        SessionService.setProperty('etag', '');
                        defer.resolve();
                        _this.update(false);
                    }
                    if (etag) {
                        SessionService.setProperty('etag', etag);
                    }
                    defer.resolve(response)
                }, function (reject) {
                    defer.reject(reject);
                });
                return defer.promise;
            },
            abortQuery: function () {
                return Data.abort();
            },
            setProperty: function (property, value) {
                data[property] = value;
                this.save(data, true);
            },
            getProperty: function (property) {
                if (angular.isObject(data) && data.hasOwnProperty(property)) {
                    return data[property];
                } else {
                    return false;
                }
            },
            getData: function () {
                return data;
            },
            save: function (obj, skipEvent) {
                if (angular.isObject(obj)) {
                    data = angular.extend({}, obj);
                    DatabaseStorage.put('/data', obj);
                    DatabaseStorage.put('version', app.version);
                    if (!skipEvent) $rootScope.$broadcast('database:updated', skipEvent);
                }
            },
            update: function (silent, showError) {
                var _this = this, defer = $q.defer();
                _this.query(showError).then(function (response) {
                    if (response) {
                        _this.save(response.data, silent);
                    }
                    defer.resolve();
                }, function () {
                    defer.resolve();
                });
                return defer.promise;
            },
            destroy: function () {
                data = {};
                Data.abort();
                DatabaseStorage.remove('/data');
            }
        };

        $rootScope.$on('app:storage:destroy', obj.destroy);

        return obj;
    }
]);