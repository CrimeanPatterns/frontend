angular.module('AwardWalletMobile').factory('Account', [
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

            return $resource('/:type/:action/:subAction/:accountId/:key/:requestData', {}, {
                query: {
                    method: 'GET',
                    params: {type: 'account', accountId: '', action: null, key: null, subAction: null, requestData: ''},
                    timeout: aborter.promise,
                    interceptor: interceptors
                },
                save: {
                    method: 'PUT',
                    params: {
                        type: 'account',
                        accountId: '',
                        action: null,
                        key: null,
                        subAction: null,
                        requestData: null
                    },
                    timeout: aborter.promise,
                    interceptor: interceptors
                },
                remove: {
                    method: 'DELETE',
                    params: {
                        type: 'account',
                        accountId: '',
                        action: null,
                        key: null,
                        subAction: null,
                        requestData: null
                    },
                    timeout: aborter.promise,
                    interceptor: interceptors
                },
                post: {
                    method: 'POST',
                    params: {
                        type: 'account',
                        accountId: '',
                        action: null,
                        key: null,
                        subAction: null,
                        requestData: null
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
angular.module('AwardWalletMobile').factory('AccountLocalPassword', [
    '$resource',
    '$q',
    function ($resource, $q) {

        function createResourceInstance(aborter) {
            return $resource('/account/:action/:accountId', {}, {
                saveLocalPassword: {
                    method: 'POST',
                    params: {accountId: '', action: 'localpassword'},
                    timeout: aborter.promise
                },
                getLocalPassword: {
                    method: 'GET',
                    params: {accountId: '', action: 'localpassword'},
                    timeout: aborter.promise
                }
            });
        }

        function resourceHelper() {
            var aborter = $q.defer(),
                resource = createResourceInstance(aborter);
            aborter.canceled = false;
            return {
                getResource: function () {
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
angular.module('AwardWalletMobile').factory('AccountSecurityQuestion', [
    '$resource',
    '$q',
    function ($resource, $q) {

        function createResourceInstance(aborter) {
            return $resource('/account/:action/:subAction/:accountId/:key', {}, {
                saveQuestion: {
                    method: 'POST',
                    params: {accountId: '', action: 'update', key: '', subAction: 'question'},
                    timeout: aborter.promise
                },
                getQuestion: {
                    method: 'GET',
                    params: {accountId: '', action: 'update', key: '', subAction: 'question'},
                    timeout: aborter.promise
                },
                cancelQuestion: {
                    method: 'DELETE',
                    params: {accountId: '', action: 'update', key: '', subAction: 'question'},
                    timeout: aborter.promise
                }
            });
        }

        function resourceHelper() {
            var aborter = $q.defer(),
                resource = createResourceInstance(aborter);
            return {
                getResource: function () {
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

angular.module('AwardWalletMobile').factory('Provider', [
    '$resource',
    function ($resource) {
        return $resource('/provider/:providerId/:requestData', {}, {
            query: {method: 'GET', params: {providerId: '', requestData: ''}, timeout: 30000},
            add: {method: 'POST', params: {providerId: '', requestData: null}, timeout: 30000},
            search: {method: 'POST', timeout: 30000}
        });
    }
]);

angular.module('AwardWalletMobile').factory('Providers', [
    '$resource',
    '$q',
    function ($resource, $q) {
        function createResourceInstance(aborter) {
            return angular.copy($resource('/providers/:providerId/:scope', {}, {
                query: {
                    method: 'GET',
                    params: {providerId: null, scope: null},
                    timeout: 30000
                },
                search: {
                    method: 'POST',
                    params: {providerId: null, scope: null},
                    timeout: aborter.promise
                }
            }));
        }

        function resourceHelper() {
            var aborter = $q.defer(),
                resource = createResourceInstance(aborter);
            return {
                getResource: function () {
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