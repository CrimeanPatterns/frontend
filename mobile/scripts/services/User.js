angular.module('AwardWalletMobile').service('UserService', [
    '$http',
    '$q',
    'SessionService',
    'Database',
    function ($http, $q, SessionService, Database) {
        var xScripted;
        return {
            loginStatus: function () {
                return $http.get('/login_status', {timeout: 30000});
            },
            isLoginIn: function () {
                var q = $q.defer();
                if(SessionService.getProperty('authorized') != null) {
                    q.resolve(SessionService.getProperty('authorized'));
                }else{
                    this.loginStatus().then(function (response) {
                        response = response.data;
                        if (response && angular.isObject(response) && response.authorized) {
                            SessionService.setProperty('authorized', response.authorized);
                            q.resolve(true);
                        } else {
                            SessionService.setProperty('authorized', false);
                            q.reject(false);
                        }
                    }, function () {
                        SessionService.setProperty('authorized', false);
                        q.reject(false);
                    });
                }
                return q.promise;
            },
            login: function (data) {
                return this.loginStatus().then(function (response) {
                    xScripted = eval(response.headers('X-Scripted'));
                    if (response.hasOwnProperty('data') && angular.isObject(response.data)) {
                        return $http({
                            url: '/login_check',
                            method: 'post',
                            data: angular.extend(data || {}, {_remember_me: 1}),
                            timeout: 30000,
                            headers: {
                                'X-Scripted': xScripted
                            }
                        });
                    }
                });
            },
            logout: function () {
                var defer = $q.defer();
                SessionService.setProperty('authorized', false);
                $http({
                    method: 'get',
                    url: '/logout',
                    timeout: 30000
                }).then(defer.resolve, defer.resolve);
                return defer.promise;
            }
        };
    }
]);