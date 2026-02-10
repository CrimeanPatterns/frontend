angular.module('AwardWalletMobile').controller('TimelineSharedSegmentController',
    ['SharedKey', 'Segment', 'SubId', '$scope', '$state', 'TimelineShared', 'AutoLogin', '$cordovaLaunchNavigator',
        function (SharedKey, Segment, SubId, $scope, $state, TimelineShared, AutoLogin, $cordovaLaunchNavigator) {
            var segment = TimelineShared.getSegment(Segment + '.' + SubId);
            if (segment) {
                segment.menu = segment.menu || {};
                $scope.params = {SharedKey: SharedKey, Segment: Segment, SubId: SubId};
                $scope.segment = segment;
                $scope.usermenu = {
                    access: {
                        phones: segment.menu.hasOwnProperty('phones'),
                        direction: segment.menu.hasOwnProperty('direction') && isCordova,
                        altflights: segment.menu.hasOwnProperty('alternativeFlights') && isCordova,
                        flightstatus: segment.menu.hasOwnProperty('flightStatus') && isCordova
                    },
                    getDirection: function () {
                        var query = '';
                        if (segment.menu.hasOwnProperty('direction')) {
                            if (segment.menu['direction'].hasOwnProperty('lat') && segment.menu['direction'].hasOwnProperty('lng')) {
                                query = segment.menu['direction']['lat'] + ',' + segment.menu['direction']['lng'];
                            } else {
                                query = segment.menu['direction']['address'];
                            }
                            $cordovaLaunchNavigator.navigate(query, null,
                                {
                                    navigationMode: "turn-by-turn",
                                    disableAutoGeolocation: true,
                                    preferGoogleMaps: true
                                });
                        }
                    },
                    showFlightStatus: function () {
                        AutoLogin.showFlightStatus(segment.menu.flightStatus.provider, segment.menu.flightStatus);
                    }
                };
            }
            else {
                $state.go('shared.timeline', {SharedKey: SharedKey});
            }
        }
    ]);