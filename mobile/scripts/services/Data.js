angular.module('AwardWalletMobile').factory('Data', [
    '$http',
    '$q',
    'SessionService',
    function ($http, $q, SessionService) {

        function createResourceInstance(aborter) {
            return {
                query: function (silent) {
                    var defer = $q.defer();
                    $http({
                        method: 'GET',
                        url: '/data',
                        timeout: aborter.promise,
                        globalError: !silent,
                        headers: {'If-None-Match': SessionService.getProperty('etag')}
                    }).then(function (response) {
                        aborter.resolve();
                        defer.resolve(response);
                    }, function (reject) {
                        aborter.resolve();
                        defer.reject(reject);
                    });
                    return defer.promise;
                }
            };
        }

        function resourceHelper() {
            var aborter,
                resource = createResourceInstance(aborter);
            return {
                getResource: function () {
                    aborter = $q.defer();
                    resource = createResourceInstance(aborter);
                    aborter.promise.timeout(30000 * 4/*debugMode ? 60000 : 30000*/, function (defer) {
                        defer.resolve();
                    });
                    return resource;
                },
                abort: function () {
                    if (aborter && aborter.hasOwnProperty('resolve')) {
                        aborter.resolve();
                    }
                }
            };
        }

        return (resourceHelper());
    }
]);