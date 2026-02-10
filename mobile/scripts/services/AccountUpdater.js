angular.module('AwardWalletMobile').factory('AccountUpdater', [
    '$resource',
    '$q',
    '$timeout',
    function ($resource, $q, $timeout) {

        function createResourceInstance(aborter) {

            var interceptors = {
                response: function (data) {
                    $timeout.cancel(aborter.timeout);
                    return data.resource;
                },
                responseError: function (data) {
                    $timeout.cancel(aborter.timeout);
                    return data;
                }
            };

            return $resource('/account/update2/:key/:eventIndex', {}, {
                start: {
                    method: 'POST',
                    params: {key: null, eventIndex: null},
                    timeout: aborter.promise,
                    interceptor: interceptors,
                    globalError: false
                },
                getEvents: {
                    method: 'POST',
                    params: {key: null, eventIndex: null},
                    timeout: aborter.promise,
                    interceptor: interceptors,
                    globalError: false
                }
            });
        }

        function resourceHelper() {
            var aborter = $q.defer(), resource;
            resource = createResourceInstance(aborter);
            return {
                getResource: function () {
                    var _this = this;
                    aborter.timeout = $timeout(function () {
                        if (aborter.promise.$$state.status == 0) {
                            aborter.resolve();
                            _this.renew();
                        }
                    }, 30000);
                    return resource;
                },
                renew: function () {
                    aborter = $q.defer();
                    aborter.promise.canceled = false;
                    resource = createResourceInstance(aborter);
                },
                abort: function () {
                    aborter.promise.canceled = true;
                    aborter.resolve();
                    this.renew();
                }
            };
        }

        return (resourceHelper());
    }
]);