angular.module('AwardWalletMobile').factory('AccountStoreLocation', [
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

            return $resource('/location/:type/:action/:id/:subId/:subAction', {}, {
                get: {
                    method: 'GET',
                    params: {
                        type: 'account',
                        action: null,
                        id: null,
                        subId: null,
                        subAction: 'add'
                    },
                    timeout: aborter.promise,
                    interceptor: interceptors
                },
                add: {
                    method: 'PUT',
                    params: {
                        type: 'account',
                        action: null,
                        id: null,
                        subId: null,
                        subAction: 'add'
                    },
                    timeout: aborter.promise,
                    interceptor: interceptors
                },
                save: {
                    method: 'POST',
                    params: {
                        type: 'edit',
                        action: null,
                        id: null,
                        subId: null,
                        subAction: null
                    },
                    timeout: aborter.promise,
                    interceptor: interceptors
                },
                remove: {
                    method: 'DELETE',
                    params: {
                        type: null,
                        action: 'delete',
                        id: null,
                        subId: null,
                        subAction: null
                    },
                    timeout: aborter.promise,
                    interceptor: interceptors
                },
                list: {
                    method: 'GET',
                    params: {
                        type: null,
                        action: 'list',
                        id: null,
                        subId: null,
                        subAction: null
                    },
                    timeout: aborter.promise,
                    interceptor: interceptors
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
                        if (aborter.promise.$$state.status === 0) {
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