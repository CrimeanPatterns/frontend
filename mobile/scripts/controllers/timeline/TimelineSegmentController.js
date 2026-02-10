angular.module('AwardWalletMobile').controller('TimelineSegmentController',
    ['travelerId', 'Segment', 'SubId', '$scope', '$state', 'Timeline', 'AutoLogin', 'AutoLoginLocalPasswordPopup', '$cordovaLaunchNavigator', 'AccountList', 'btfModal', '$rootScope',
        function (travelerId, Segment, SubId, $scope, $state, Timeline, AutoLogin, AutoLoginLocalPasswordPopup, $cordovaLaunchNavigator, AccountList, btfModal, $rootScope) {
            var segment = Timeline.getSegment(Segment + '.' + SubId), numbersPopup = btfModal({
                controller: angular.noop,
                templateUrl: 'templates/directives/popups/popup-accounts-number.html'
            }), hasExtension = false, account;
            if (segment) {
                segment.menu = segment.menu || {};
                $scope.segment = segment;
                $scope.params = {travelerId: travelerId, Segment: Segment, SubId: SubId};

                if (segment.menu.hasOwnProperty('accountId')) {
                    account = AccountList.getAccount('a' + segment.menu.accountId);
                    hasExtension = account && account.Autologin && (account.Autologin.desktopExtension || account.Autologin.mobileExtension);
                    $scope.account = account;
                }

                $scope.usermenu = {
                    states: {
                        autologin: false
                    },
                    showBoardingPass: function () {
                        if (segment.menu.hasOwnProperty('boardingPassUrl')) {
                            window.open(segment.menu.boardingPassUrl, '_blank');
                        }
                    },
                    getDirection: function () {
                        var query, application;
                        if (segment.menu.hasOwnProperty('direction')) {
                            query = segment.menu.direction.address;
                            if (segment.menu.direction.hasOwnProperty('lat') && segment.menu.direction.hasOwnProperty('lng')) {
                                query = [segment.menu.direction.lat, segment.menu.direction.lng];
                            }

                            launchnavigator.isAppAvailable(launchnavigator.APP.GOOGLE_MAPS, function (isAvailable) {
                                application = platform.ios ? launchnavigator.APP.APPLE_MAPS : launchnavigator.APP.USER_SELECT;
                                if (isAvailable) {
                                    application = launchnavigator.APP.GOOGLE_MAPS;
                                }
                                launchnavigator.navigate(query, {
                                    app: application
                                });
                            });
                        }
                    },
                    autologin: function () {
                        if (!$scope.usermenu.states.autologin) {
                            $scope.usermenu.states.autologin = true;
                            AutoLogin.startExtensionAutologin(account).then(function (response) {
                                $scope.usermenu.states.autologin = false;
                                if (angular.isObject(response) && response.hasOwnProperty('localPassword') && response.localPassword) {
                                    AutoLoginLocalPasswordPopup.open({
                                        accountId: account.ID,
                                        callback: function () {
                                            AutoLoginLocalPasswordPopup.close();
                                            $scope.usermenu.autologin();
                                        },
                                        close: function () {
                                            AutoLoginLocalPasswordPopup.close();
                                        }
                                    });
                                }
                            });
                        }
                    },
                    showFlightStatus: function () {
                        AutoLogin.showFlightStatus(segment.menu.flightStatus.provider, segment.menu.flightStatus);
                    },
                    showNumberPopup: function () {
                        numbersPopup.open({
                            accounts: AccountList.getAccounts(),
                            itinerarieAccounts: segment.menu.accountNumbers,
                            hideModal: function () {
                                return numbersPopup.close();
                            }
                        });
                    },
                    findParking: function () {
                        if (segment.menu.hasOwnProperty('parkingUrl')) {
                            window.open(segment.menu.parkingUrl, '_blank');
                        }
                    },
                    confirmChanges: function () {
                        Timeline.confirmChanged($scope.params.Segment, $scope.params.SubId);
                        $scope.usermenu.access.confirmChanges = false;
                        segment.menu.allowConfirmChanges = false;
                        segment.changed = false;
                        if (segment.hasOwnProperty("startDate") && segment.startDate.hasOwnProperty("old")) {
                            delete segment.startDate.old;
                        }

                        var confirm = function (blocks) {
                            angular.forEach(blocks, function (block) {
                                if (block.hasOwnProperty("old")) {
                                    block.old = null;
                                }
                                if (block.hasOwnProperty("kind") && block.kind == "showmore" && angular.isArray(block.val)) {
                                    confirm(block.val);
                                }
                            });
                        };
                        confirm($scope.segment.blocks);

                        $rootScope.$broadcast('timeline:update');
                    }
                };

                function setUsermenuAccess() {
                    $scope.usermenu.access = {
                        boardingPass: !!segment.menu.boardingPassUrl && platform.cordova,
                        phones: !!segment.menu.phones,
                        direction: !!segment.menu.direction && platform.cordova,
                        accountNumber: !!(segment.menu.accountNumbers && account),
                        autologin: {
                            active: account && account.Access && account.Access.autologin,
                            gotosite: (!platform.cordova || !hasExtension) && (account && account.Autologin && account.Autologin.loginUrl),
                            autologin: platform.cordova && hasExtension
                        },
                        altflights: !!segment.menu.alternativeFlights && platform.cordova,
                        flightstatus: !!segment.menu.flightStatus && platform.cordova,
                        parking: !!segment.menu.parkingUrl && platform.cordova,
                        confirmChanges: !!segment.menu.allowConfirmChanges
                    };
                }

                setUsermenuAccess();

                $scope.$on('timeline:update', function () {
                    var updatedSegment = Timeline.getSegment(Segment + '.' + SubId);
                    if (updatedSegment) {
                        $scope.segment = updatedSegment;
                        segment = updatedSegment;
                        setUsermenuAccess();
                    }
                    updatedSegment = null;
                });
            }
            else {
                $state.go('index.timeline.list', {Id: travelerId});
            }
        }
    ])
;