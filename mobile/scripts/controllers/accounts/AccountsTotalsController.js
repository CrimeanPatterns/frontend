angular.module('AwardWalletMobile').controller('AccountsTotalsController', [
    '$scope',
    'AccountList',
    function ($scope, AccountList) {
        var providerKinds = angular.copy($scope.providerKinds);
        var users = [];
        var accounts = AccountList.getList();
        for(var i = 0, account; i < accounts.length, account = accounts[i]; i++){
            if (providerKinds[account['Kind']] && !account['ParentAccount']) {
                if (!Object.prototype.hasOwnProperty.call(providerKinds[account['Kind']], 'totals')) {
                    providerKinds[account['Kind']].totals = 0;
                    providerKinds[account['Kind']].accounts = 0;
                }
                if (Object.prototype.hasOwnProperty.call(account, 'TotalBalance')) {
                    providerKinds[account['Kind']].totals += parseFloat(account['TotalBalance']);
                }
                if (users.indexOf(account['UserName']) < 0) {
                    users.push(account['UserName']);
                }
                providerKinds[account['Kind']].accounts++;
            }
        }
        for (let kind in providerKinds) {
            if (Object.prototype.hasOwnProperty.call(providerKinds, kind)) {
                providerKinds[kind].totals = Math.round(providerKinds[kind].totals);
            }
        }
        $scope.users = users;
        $scope.totals = providerKinds;
        $scope.filterHidden = (item) => {
            return !item.hidden;
        }
    }
]);