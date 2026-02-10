angular.module('AwardWalletMobile').controller('AccountsUpdateController', [
    '$scope',
    '$state',
    'Database',
    'AccountList',
    'Updater',
    'UpdateSecurityQuestionPopup',
    'UpdateLocalPasswordPopup',
    function ($scope, $state, Database, AccountList, Updater, UpdateSecurityQuestionPopup, UpdateLocalPasswordPopup) {

        var accounts = AccountList.getAccounts(), accountsKeys = [];

        for(var key in accounts) {
            if (accounts.hasOwnProperty(key) && accounts[key].Access && accounts[key].Access.update) {
                accountsKeys.push(key);
            }
        }

        var shared = {
            updating: false,
            active: false,
            aborted: false
        };

        function toState(state) {
            // state = done|fail
            if (shared.updating && shared.active) {
                shared.updating = false;
            }
        }


        function setAccounts(data) {
            if (!data) return;
            var accounts = AccountList.getAccounts();
            for (var id in data) {
                if (data.hasOwnProperty(id)) {
                    accounts[id] = data[id];
                }
            }
            AccountList.setAccounts(accounts);
        }

        var self = {
            start: function (ids) {
                if (!Updater.isUpdating()) {
                    shared.active = true;
                    shared.updating = true;
                    Updater.start(ids, toState, setAccounts);
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


        $scope.accounts = accounts;

        $scope.elements = {};

        $scope.updating = {
            complete: false,
            state: 'progress',
            progress: 0,
            updated: 0,
            updatedAccounts: Translator.transChoice('accounts.n', 0, {accounts: 0}),
            total: accountsKeys.length,
            totalAccounts: Translator.transChoice('accounts.n', accountsKeys.length, {accounts: accountsKeys.length}),
            stop: function () {
                $scope.stop();
                $scope.updating.state = 'complete';
            }
        };

        $scope.buy = function () {
            $state.go('index.pay', {start: 'start'});
        };

        $scope.$on('progressBar:done', function (event, data) {
            $scope.updating.complete = true;
        });

        Database.update().then(function () {
            if (shared.aborted) return;
            var temp = AccountList.getAccounts();

            accounts = angular.merge(accounts, temp);

            accountsKeys = [];

            for(var key in accounts) {
                if (accounts.hasOwnProperty(key) && accounts[key].Access && accounts[key].Access.update) {
                    accountsKeys.push(key);
                }
            }

            $scope.accounts = accounts;

            self.start(accountsKeys);

            temp = null;
        });

        var unbindWatchState = $scope.$watch(function () {
            return Updater.getState();
        }, function (state) {
            if (!self.getState().active) return;
            if (['done', 'fail'].indexOf(state) > -1) {
                $scope.updating.state = 'complete';
                if (Updater.getTrips().length > 0) {
                    $scope.$emit('database:expire');
                }
                unbindWatchState();
            }
        });

        var unbindWatchResults = $scope.$watch(function () {
            return Updater.getCounters();
        }, function (data) {
            if (data.all > 0) {
                var collection = Updater.getCollection();
                $scope.updating.progress = data.progress / 100;
                $scope.updating.total = data.all;
                $scope.updating.totalAccounts = Translator.transChoice('accounts.n', data.all, {accounts: data.all});
                $scope.updating.updated = data.updated;
                $scope.updating.updatedAccounts = Translator.transChoice('accounts.n', data.updated, {accounts: data.updated});
                if (data.progress == 100) {
                    unbindWatchResults();
                }
                if (collection.hasOwnProperty('length') && collection.length()) {
                    $scope.elements = collection.findUpdated();
                }
            }
        }, true);


        $scope.stop = function () {
            self.stop();
        };

        $scope.end = function () {
            self.end();
        };

        $scope.$on('$destroy', function () {
            Database.abortQuery();
            shared.aborted = true;
            self.stop();
            self.end();
        });
    }
]);