angular.module('AwardWalletMobile').controller('AccountHistoryController', [
    '$scope',
    '$stateParams',
    '$state',
    'AccountList',
    function ($scope, $stateParams, $state, AccountList) {
        var account = AccountList.getAccount($stateParams.Id);
        var subAccount = null;

        var translations = [ /* Uses in native application */
            Translator.trans(/** @Desc("History") */'history', {}, 'mobile'),
            Translator.trans(/** @Desc("No merchants found") */'merchant_lookup.noresult', {}, 'messages'),
            Translator.trans(/** @Desc("Search") */'search', {}, 'messages')
        ];

        if (account) {

            if ($stateParams.subId) {
                subAccount = AccountList.filter(account, 'SubAccountsArray', {SubAccountID: $stateParams.subId}, true);
            }

            $scope.view = {
                providerName: subAccount ? subAccount['DisplayName'] : account['DisplayName'],
                accountNumber: account['Login'],
                userName: account['UserName'],
                accountId: account.ID,
                subId: subAccount ? subAccount.SubAccountID : null,
                stateParams: $stateParams
            };

        } else {
            $state.go('index.accounts.list');
        }
    }
]);