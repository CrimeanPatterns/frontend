angular.module('AwardWalletMobile').controller('RateController', [
    '$scope',
    '$rootScope',
    'BaseUrl',
    'Translator',
    'AccountList',
    '$localStorage',
    '$http',
    function ($scope, $rootScope, BaseUrl, Translator, AccountList, $localStorage, $http) {
        var ACTION_SKIP = 1,
            ACTION_CONTACTUS = 2,
            ACTION_RATE = 3;
        var counters = AccountList.getCounters(),
            current = {
                action: 0,
                date: new Date
            },
            url = {
                ios: 'itms-apps://itunes.apple.com/app/id388442727?action=write-review',
                android: 'market://details?id=com.itlogy.awardwallet&reviewId=0'
            };

        $scope.user = $scope.user || {};
        $scope.user.feedbacks = $scope.user.feedbacks || [];
        var lastRate = angular.fromJson($localStorage.getItem('RateApp')) || ($scope.user.feedbacks.length > 0 ? $scope.user.feedbacks[$scope.user.feedbacks.length - 1] : null);

        function save(action) {
            $scope.rate.show = false;
            current.action = action;
            current.date = Math.floor(new Date().getTime() / 1000);
            $http({
                method: 'post',
                url: '/feedback/add',
                timeout: 30000,
                data: {action: current.action}
            });
        }

        var step = 0;
        $scope.rate = {
            question: Translator.trans(/** @Desc("Do you like AwardWallet?") */'rateApp.question-like', {}, 'mobile'),
            show: /**(counters.errors == 0) */ $scope.user.Logons > 50 && ((counters.errors * 100) / counters.accounts < 20) && (counters.accounts > 8) && !lastRate,
            discard: function () {
                if (step == 1) {
                    $scope.rate.skip();
                }
                if (step == 0 || step == 2) {
                    $scope.rate.question = Translator.trans(/** @Desc("OK, would you tell us what's wrong?") */'rateApp.whats-wrong', {}, 'mobile');
                    step = 1;
                    current.action = ACTION_SKIP;
                }
            },
            agree: function () {
                if (step == 1) {
                    $scope.rate.contactUs();
                }
                if (step == 2) {
                    $scope.rate.open();
                }
                if (step == 0) {
                    $scope.rate.question = Translator.trans(/** @Desc("Awesome, would you Rate our app?") */'rateApp.awesome', {}, 'mobile');
                    step = 2;
                    current.action = ACTION_RATE;
                    $scope.rate.requestReview();
                }
            },
            skip: function () {
                save(ACTION_SKIP);
            },
            requestReview: function () {
                if (platform.ios && window.inappreview && window.inappreview.requestReview) {
                    window.inappreview.requestReview(angular.noop, angular.noop);
                }
            },
            open: function (notrack) {
                if (!notrack) {
                    save(ACTION_RATE);
                }

                if (platform.ios) {
                    window.open(url.ios, '_system');
                } else if (platform.android) {
                    window.open(url.android, '_system');
                }
            },
            contactUs: function () {
                save(ACTION_CONTACTUS);
                var url = BaseUrl + '/contact.php';
                if (platform.cordova)
                    url += '?fromapp=1&KeepDesktop=1';
                window.open(url, '_blank');
            }
        };
        $scope.$on('$destroy', function () {
            if (current.action > 0) {
                $localStorage.setItem('RateApp', angular.toJson(current));
            }
            current = null;
        });
    }
]);