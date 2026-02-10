angular.module('AwardWalletMobile').controller('MenuController', [
    '$scope',
    '$rootScope',
    '$state',
    'Booking',
    'Database',
    function ($scope, $rootScope, $state, Booking, Database) {

        var translations = [ /* Uses in native application */
            Translator.trans(/** @Desc("Merchant Lookup") */'merchant.lookup', {}, 'mobile')
        ];
        var profile = Database.getProperty('profile');

        $scope.menu = {
            open: function (stateName) {
                if ($state.current.name === stateName)
                    $rootScope.$broadcast('swipemenu:toggle');
                $state.go(stateName);
            },
            accounts: {
                active: function () {
                    return $state.current.name.indexOf('account') > 0;
                }
            },
            timeline: {
                active: function () {
                    return $state.includes('index.timeline');
                }
            },
            booking: {
                active: function () {
                    return $state.includes('index.booking') || $state.includes('index.booking-request');
                },
                hasUnread: function () {
                    return typeof Booking.getUnread() !== 'undefined';
                }
            },
            merchants: {
                active: function () {
                    return $state.includes('index.merchants');
                }
            },
            miles: {
                active: function () {
                    return $state.includes('index.miles');
                }
            },
            spendAnalysis: {
                active: function () {
                    return $state.current.name.indexOf('index.spend-analysis') === 0;
                },
                isEnabled: function () {
                    return profile.hasOwnProperty('spentAnalysis') && profile.spentAnalysis;
                }
            },
            profile: {
                active: function () {
                    return $state.current.name.indexOf('.profile') > 0;
                }
            },
            members: {
                open: function () {
                    var url = BaseUrl + '/user/connections';
                    if (platform.cordova)
                        url += '?fromapp=1&KeepDesktop=1';
                    window.open(url, '_blank');
                }
            }
        };
    }
]);