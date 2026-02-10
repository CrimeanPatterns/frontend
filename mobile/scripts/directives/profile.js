angular.module('AwardWalletMobile').directive('profile', [function () {
    return {
        restrict: 'E',
        scope: {
            fields: '=fields'
        },
        templateUrl: 'templates/directives/profile.html',
        controller: [
            '$scope',
            'Pincode',
            function ($scope, Pincode) {
                $scope.pincode = {
                    setup: Pincode.setup,
                    remove: Pincode.remove,
                    get: Pincode.get,
                    translations: {
                        title: Translator.trans('pincode.setup.title', {}, 'mobile')
                    }
                };

                if (platform.cordova && platform.ios) {
                    var model = parseFloat(device.model.toLowerCase().replace('iphone', '').replace(',', '.'));
                    if (model > 6) {
                        $scope.pincode.translations.title = Translator.trans(/** @Desc("PIN code & Touch ID set up")*/'pincode.setup.title-ios', {}, 'mobile');
                        if (
                            device &&
                            (
                                model === 10.3 ||
                                model >= 10.6
                            )//https://www.theiphonewiki.com/wiki/Models
                        ) {
                            $scope.pincode.translations.title = Translator.trans(/** @Desc("PIN code & Face ID set up")*/'faceid.title', {}, 'mobile')
                        }
                    }
                }
            }]
    }
}]);

angular.module('AwardWalletMobile').directive('profileField', [
    '$state',
    '$http',
    '$templateCache',
    '$q',
    '$compile',
    '$parse',
    'UserSettings',
    'SessionService',
    'UpgradeAccountLevelPopup',
    function ($state, $http, $templateCache, $q, $compile, $parse, UserSettings, SessionService, UpgradeAccountLevelPopup) {
        return {
            restrict: 'E',
            scope: true,
            require: '^profile',
            compile: function (element, attrs) {
                function getTemplate(templateUrl) {
                    var deferred = $q.defer();
                    var template = $templateCache.get(templateUrl);
                    if (template) {
                        deferred.resolve(template);
                    } else {
                        deferred.reject();
                    }
                    return deferred.promise;
                }

                function getTemplateUrl(type) {
                    return 'templates/directives/form/' + type + '.html';
                }

                return {
                    pre: function (scope, element, attrs) {
                        var templateUrl;

                        scope.isCordova = platform.cordova;
                        scope.type = $parse(attrs.type)(scope);
                        scope.field = $parse(attrs.field)(scope);

                        if (scope.field.formLink && scope.field.formLink.indexOf('#') === 0)
                            scope.type = scope.field.formLink.slice(1);

                        templateUrl = getTemplateUrl(scope.type);

                        if (['textProperty', 'upgrade', 'cancelSubscription'].indexOf(scope.type) > -1) {
                            scope.field.action = function () {
                                if (scope.type === 'upgrade') {
                                    $state.go('index.pay', {start: 'start'});
                                    return;
                                }
                                if (scope.type === 'cancelSubscription') {
                                    if (scope.field.attrs && [8, 9].indexOf(scope.field.attrs.kind) === -1) {
                                        var url = BaseUrl + '/user/profile?KeepDesktop=1';
                                        window.open(url, '_blank');
                                    } else {
                                        $state.go('index.subscription', {
                                            action: 'cancel',
                                            platform: {
                                                8: 'ios',
                                                9: 'android'
                                            }[scope.field.attrs.kind]
                                        });
                                    }
                                } else {
                                    $state.go('index.profile-edit', {
                                        formLink: scope.field.formLink,
                                        formTitle: scope.field.formTitle
                                    });
                                }
                            }
                        }
                        if (scope.type === 'flashMessage') {
                            scope.field.action = function () {
                                scope.field.message = scope.field.notice;
                                scope.field.status = 'success';
                                return $http({
                                    method: scope.field.method,
                                    url: scope.field.link
                                });
                            }
                        }

                        if (['textProperty', 'formLink', 'checklistItem'].indexOf(scope.type) > -1 && scope.field.formLink) {
                            scope.field.path = scope.field.formLink.replace('/profile/', '');
                            scope.field.action = function () {
                                $state.go('index.profile-edit', {
                                    formLink: scope.field.formLink,
                                    formTitle: scope.field.formTitle,
                                    action: scope.field.path
                                });
                            };
                        }

                        if (scope.type === 'checklistItem') {
                            if (scope.field.hasOwnProperty('attrs') && scope.field.attrs.hasOwnProperty('setting') && UserSettings.has(scope.field.attrs.setting)) {
                                scope.field.checked = UserSettings.get(scope.field.attrs.setting);
                            }
                        }

                        if (['balanceCredits', 'upgradeAccount'].indexOf(scope.type) > -1) {
                            scope.field.buy = function () {
                                if (!scope.field.attrs) return;

                                var attrs = scope.field.attrs;
                                if (scope.type === 'balanceCredits' && attrs.needUpgrade === true) {
                                    UpgradeAccountLevelPopup.open({
                                        message: Translator.trans('account.balancewatch.awplus-upgrade')
                                    });
                                } else {
                                    window.open(attrs.url, '_blank');
                                }
                            };
                        }

                        if (
                            scope.field.formLink === '/profile/location/list' &&
                            !(SessionService.getProperty('locations-total') > 0)
                        ) {
                            return;
                        }

                        getTemplate(templateUrl).then(function (html) {
                            element.append(html);
                            $compile(element.contents())(scope);
                        });
                    }
                }
            }
        }
    }]);
