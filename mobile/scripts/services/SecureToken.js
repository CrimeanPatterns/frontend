angular.module('AwardWalletMobile').factory('secureTokenInterceptor', [
    '$q',
    function ($q) {
        var token;

        function onResponseError(response) {
            if (
                response.status === 403 &&
                response.hasOwnProperty('headers') &&
                response.headers('X-AW-SECURE-TOKEN')
            ) {
                token = response.headers('X-AW-SECURE-TOKEN');
                response.config.retries = 3;
            }
            return $q.reject(response);
        }

        function onResponse(response) {
            if (
                response &&
                response.headers('X-AW-SECURE-TOKEN')
            ) {
                token = response.headers('X-AW-SECURE-TOKEN');
            }
            return response;
        }

        function onRequest(config) {
            var defer = $q.defer();
            if (typeof ApiSignature !== 'undefined' && token) {
                ApiSignature.createApiSignature(token, 'sha-256', function (signature) {
                    config.headers['X-AW-SECURE-TOKEN'] = signature;
                    config.headers['X-AW-SECURE-VALUE'] = token;
                    defer.resolve(config);
                    token = null;
                });
            } else {
                defer.resolve(config);
            }
            return defer.promise;
        }

        return {
            request: onRequest,
            response: onResponse,
            responseError: onResponseError
        };
    }
]);