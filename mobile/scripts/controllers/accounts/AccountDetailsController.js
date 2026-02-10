angular.module('AwardWalletMobile').controller('AccountDetailsController', [
    '$scope',
    '$stateParams',
    '$state',
    'Account',
    'btfModal',
    'UpdateAccountPopup',
    'AutoLogin',
    'AutoLoginLocalPasswordPopup',
    'AccountList',
    'CardStorage',
    'Barcode',
    function ($scope, $stateParams, $state, Account, btfModal, UpdateAccountPopup, AutoLogin, AutoLoginLocalPasswordPopup, AccountList, CardStorage, Barcode) {
        $scope.accountId = $stateParams.Id;
        $scope.account = AccountList.getAccount($stateParams.Id);
        $scope.subAccount = null;

        if ($scope.account) {

            if ($stateParams.subId) {
                $scope.subAccount = AccountList.filter($scope.account, 'SubAccountsArray', {SubAccountID: $stateParams.subId}, true);
                if ($scope.subAccount)
                    $scope.subAccount['Access'] = $scope.account['Access'];
            }

            var popupDeleteAccount = btfModal({
                controller: function ($scope) {
                    $scope.confirmDelete = function () {
                        var card;
                        if ($scope.account['Access']['delete']) {
                            AccountList.deleteAccount($scope.account.TableName[0].toLowerCase() + $scope.account.ID);
                            Account.getResource().remove({
                                type: $scope.account.TableName.toLowerCase(),
                                accountId: $scope.account.ID
                            });
                            if (platform.cordova) {
                                card = CardStorage.get({
                                    accountId: $stateParams.Id,
                                    subAccountId: $stateParams.subId
                                });
                                if (card)
                                    CardStorage.remove(card);
                            }
                            $state.go('index.accounts.list');
                            return popupDeleteAccount.close();
                        }
                    };
                },
                templateUrl: 'templates/directives/popups/popup-confirm.html',
                uid: 'popupDelete'
            });

            $scope.barcode = {
                parsed: !!(($scope.subAccount && $scope.subAccount['BarCodeParsed']) ||
                    ($scope.account && $scope.account['BarCodeParsed'])),
                scanned: !!(($scope.subAccount && $scope.subAccount['BarCodeCustom']) ||
                    ($scope.account && $scope.account['BarCodeCustom']) || {})['BarCodeData'],
                scan: function () {
                    Barcode.scan().then(function (response) {
                        $scope.$applyAsync(function () {
                            Barcode.save($stateParams, {
                                'BarCodeData': response.text,
                                'BarCodeType': response.format
                            });
                            $scope.barcode.scanned = true;
                            $scope.view.menu.showBarcode = true;
                            $scope.view.menu.scanBarcode = false;
                        });
                    });
                }
            };

            $scope.isExtenstion = $scope.account['Autologin'] &&
                ($scope.account['Autologin']['desktopExtension'] ||
                    $scope.account['Autologin']['mobileExtension']);

            $scope.hasHistory = false;

            if ($scope.subAccount) {
                $scope.hasHistory = $scope.subAccount['HasHistory'] || false;
            }else{
                $scope.hasHistory = $scope.account['HasHistory'];
            }

            $scope.view = {
                stateParams: {
                    Id: $scope.accountId,
                    subId: $scope.subAccount ? $scope.subAccount.SubAccountID : null
                },
                links: {
                    edit: $state.href('index.accounts.account-edit', {
                        Id: $scope.accountId
                    })
                },
                call: function (phone) {
                    window.open('tel:' + phone.replace(/[^\d.]/g, ''), '_system');
                },
                showBarcode: function (custom) {
                    var params = {
                        Id: $stateParams.Id,
                        subId: $stateParams.subId,
                        type: custom ? 'custom' : 'parsed'
                    };
                    $state.go('index.accounts.account-barcode', params);
                },
                showDeletePopup: function () {
                    return popupDeleteAccount.open({
                        account: $scope.account,
                        accounts: $scope.accounts,
                        hideModal: function () {
                            return popupDeleteAccount.close();
                        }
                    });
                },
                showUpdatePopup: function () {
                    UpdateAccountPopup.open({
                        editLink: $scope.view.links.edit,
                        isCordova: platform.cordova,
                        user: $scope.user,
                        account: $scope.account,
                        hideModal: function (account, databaseExpire) {
                            if (account) {
                                $scope.account = account;
                            }
                            if (databaseExpire) {
                                $scope.$emit('database:expire');
                            }
                            return UpdateAccountPopup.close();
                        }
                    });
                },
                autoLoginStarted: false,
                autoLogin: function () {
                    if (!$scope.view.autoLoginStarted) {
                        $scope.view.autoLoginStarted = true;
                        AutoLogin.startExtensionAutologin($scope.account).then(function (response) {
                            $scope.view.autoLoginStarted = false;
                            if (
                                angular.isObject(response) &&
                                response.hasOwnProperty('localPassword') &&
                                response['localPassword']
                            ) {
                                AutoLoginLocalPasswordPopup.open({
                                    accountId: $scope.account.ID,
                                    callback: function () {
                                        AutoLoginLocalPasswordPopup.close();
                                        $scope.view.autoLogin();
                                    },
                                    close: function () {
                                        AutoLoginLocalPasswordPopup.close();
                                    }
                                });
                            }
                        });
                    }
                },
                menu: {
                    showBarcode: $scope.barcode.parsed || $scope.barcode.scanned,
                    scanBarcode: !($scope.barcode.parsed || $scope.barcode.scanned) && $scope.account['Access']['edit'] && platform.cordova,
                    history: $scope.hasHistory,
                    phones: $scope.account['Phones'] && $scope.account['Phones'].length > 0,
                    delete: $scope.account['Access']['delete'] && !$scope.subAccount,
                    edit: $scope.account['Access']['edit'] && !$scope.subAccount,
                    logIn: {
                        active: $scope.account['Access']['autologin'] && !$scope.subAccount,
                        gotosite: (!platform.cordova || !$scope.isExtenstion) && ($scope.account['Autologin'] && $scope.account['Autologin']['loginUrl']),
                        autologin: platform.cordova && $scope.isExtenstion
                    }

                },
                pay: function () {
                    $state.go('index.pay', {start: 'start'});
                },
                isCordova: platform.cordova
            };


            $scope.$on('accountList:update', function () {
                var account = AccountList.getAccount($stateParams.Id);
                if (account) {
                    $scope.account = account;
                    if ($stateParams.subId) {
                        var subAccount = AccountList.filter($scope.account, 'SubAccountsArray', {SubAccountID: $stateParams.subId}, true);
                        if (subAccount) {
                            $scope.subAccount = subAccount;
                            $scope.subAccount['Access'] = account['Access'];
                        }
                    }
                }
                account = null;
                subAccount = null;
            });

            $scope.$on('$destroy', function () {
                popupDeleteAccount.close();
                UpdateAccountPopup.close();
                AutoLoginLocalPasswordPopup.close();
            });

        } else {
            $state.go('index.accounts.list');
        }
    }
]);