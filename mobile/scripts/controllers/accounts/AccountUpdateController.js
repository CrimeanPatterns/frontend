angular.module('AwardWalletMobile').controller('AccountUpdateController', [
    '$timeout',
    '$scope',
    'Updater',
    'UpdateSecurityQuestionPopup',
    'UpdateLocalPasswordPopup',
    'AccountList',
    function ($timeout, $scope, Updater, UpdateSecurityQuestionPopup, UpdateLocalPasswordPopup, AccountList) {
        var account = null, accountKey, databaseExpire = false;
        if ($scope.account) {

            accountKey = 'a'+$scope.account['ID'];

            var shared = {
                updating: false,
                active: false
            };

            function toState(state) {
                // state = done|fail
                if (shared.updating && shared.active) {
                    shared.updating = false;
                }
            }

            var self = {
                start: function (id) {
                    if (!Updater.isUpdating()) {
                        shared.active = true;
                        shared.updating = true;
                        Updater.start([id], toState);
                    }
                },
                stop: function () {
                    if (shared.updating && shared.active) {
                        shared.updating = false;
                        Updater.stop();
                    }
                },
                end: function () {
                    if (!shared.updating && shared.active) {
                        shared.updating = false;
                        shared.active = false;
                        account = Updater.getAccount(accountKey);
                        if (account) {
                            AccountList.setAccount(account);
                        }
                        Updater.end();
                    }
                },
                getState: function () {
                    return shared;
                }
            };

            Updater.setQuestionAction(function (id, data) {
                UpdateSecurityQuestionPopup.open({
                    accountId: id,
                    data: data
                });
            }, function () {
                UpdateSecurityQuestionPopup.close();
            });

            Updater.setPasswordAction(function (id, data) {
                UpdateLocalPasswordPopup.open({
                    accountId: id,
                    data: data
                });
            }, function () {
                UpdateLocalPasswordPopup.close();
            });

            $scope.view = angular.extend($scope.view || {}, {
                state: 'waiting',
                updating: {
                    result: [],
                    state: '',
                    duration: 0
                }
            });


            self.start(accountKey);

            var accountState = Updater.getCollection().getElement(accountKey);

            accountState.setChecking = function (duration) {
                accountState.progressDuration = duration;
                accountState.state = 'checking';
                $scope.view.state = accountState.state;
                $scope.view.updating.duration = duration;
            };
            
            var unbindWatchState = $scope.$watch(function () {
                return Updater.getState();
            }, function (state) {
                if (!self.getState().active) return;
                if (['done', 'fail'].indexOf(state) > -1) {
                    $timeout(function () {
                        $scope.view.state = 'complete';
                        $scope.view.updating.result = accountState.result;
                        $scope.view.updating.state = accountState.state;
                        if (Updater.getTrips().length > 0) {
                            databaseExpire = true;
                        }
                        unbindWatchState();
                    });
                }
            });

            $scope.$on('progressBar:done', function (event, data) {
                $scope.complete = true;
            });
        }

        $scope.stop = function () {
            self.stop();
            self.end();
            $scope.hideModal();
        };

        $scope.end = function () {
            self.end();
            $scope.hideModal(account, databaseExpire);
        };

        $scope.$on('$destroy', function(){
            self.stop();
            self.end();
        });
    }
]);