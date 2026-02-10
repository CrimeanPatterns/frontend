define(['angular-boot', 'jquery-boot'], function (angular, $) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('pendingService', [])
        .service('pendingService', function ($http, $q, $filter) {
            return {
                get: function () {
                    var defer = $q.defer();
                    if(window.pendingAccounts)
                        defer.resolve(window.pendingAccounts);
                    return defer.promise;
                },
                storePendingId: function (id) {
                    var ids = localStorage.getItem('pendingIds') ? angular.fromJson(localStorage.getItem('pendingIds')) : [];
                    ids.push({
                        id: id,
                        date: new Date().getTime() + 1000 * 1800 // 30 min skip
                    });
                    localStorage.setItem('pendingIds', angular.toJson(ids));
                },
                checkIdsTimout: function () {
                    var ids = angular.fromJson(localStorage.getItem('pendingIds'));
                    if (ids) {
                        ids = $filter('filter')(ids, function (el) {
                            return el.date > new Date().getTime();
                        });
                        localStorage.setItem('pendingIds', angular.toJson(ids));
                    }
                },
                isStoredId: function (id) {
                    var ids = angular.fromJson(localStorage.getItem('pendingIds'));
                    if (ids) {
                        return $filter('filter')(ids, function (el) {
                            return el.id == id;
                        }).length > 0;
                    }
                },
                save: function (data, id) {
                    return $.post(Routing.generate('aw_account_pending_save', {accountId: id}), data);
                },
                remove: function (accounts) {
                    //console.log(accounts);
                    var post = [];
                    angular.forEach(accounts, function (account) {
                        post.push({
                            id: account.id,
                            isCoupon: false,
                            useragentid: 'my'
                        })
                    });
                    return $http.post(Routing.generate('aw_account_json_remove'), post).then(res => res.data);
                }
            };
        });
});