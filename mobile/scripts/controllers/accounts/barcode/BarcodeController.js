angular.module('AwardWalletMobile').controller('BarcodeController', [
    '$scope',
    '$state',
    '$stateParams',
    '$cordovaDialogs',
    'AccountList',
    'Barcode',
    'PushNotification',
    function ($scope, $state, $stateParams, $cordovaDialogs, AccountList, Barcode, PushNotification) {
        var translations = {
            'tooltip': Translator.trans(/** @Desc("Hold your card in front of the\ncamera to scan the barcode") */'camera.scan.barcode', {}, 'mobile'),
            'title': Translator.trans(/** @Desc("Please confirm you want to delete this barcode") */'delete.barcode', {}, 'mobile'),
            'confirm': Translator.trans('alerts.text.confirm', {}, 'messages'),
            'delete': Translator.trans('button.delete', {}, 'messages'),
            'cancel': Translator.trans('cancel', {}, 'messages')
        };
        var params = {
                Id: $stateParams.Id,
                subId: $stateParams.subId
            },
            isCustom = $stateParams.type === 'custom',
            barcodeProperty = isCustom ? 'BarCodeCustom' : 'BarCodeParsed',
            account = AccountList.getAccount(params.Id);

        if (account) {

            var access = account['Access'];

            if (params.subId)
                account = AccountList.filter(account, 'SubAccountsArray', {SubAccountID: params.subId}, true);

            $scope.barcode = {
                access: {
                    scan: platform.cordova && access['edit'],
                    remove: isCustom,
                    displayNumber: true,
                    edit: access['edit']
                },
                params: params,
                displayName: account.DisplayName,
                data: null,
                type: null,
                scan: function () {
                    Barcode.scan().then(function (response) {
                        Barcode.save(params, {
                            'BarCodeData': response.text,
                            'BarCodeType': response.format
                        });
                        $state.go('index.accounts.account-barcode', angular.extend({type: 'custom'}, params));
                    });
                },
                remove: function () {
                    $cordovaDialogs.confirm(
                        translations.title,
                        translations.confirm,
                        [
                            translations.delete,
                            translations.cancel
                        ]
                    ).then(function (button) {
                        if (button === 1) {
                            Barcode.remove(params, {
                                'BarCodeData': null,
                                'BarCodeType': null
                            });
                            $state.go('index.accounts.account-details', params);
                        }
                    });
                },
                notification: function () {//for demo
                    /**
                     * @return {boolean}
                     */
                    function LastChangeDate(date) {
                        var date1 = new Date(date), date2 = new Date();
                        return (Math.abs(date2.getTime() - date1.getTime()) / 3600000) < 24;
                    }

                    function getAccountProperties(account) {
                        var properties = [], property;
                        
                        if (account.hasOwnProperty('BalancePush')) {
                            property = {
                                type: 'balance',
                                name: Translator.trans('award.account.balance', {}, 'messages'),
                                value: account.BalancePush
                            };
                            if (LastChangeDate(account.LastChangeDate * 1000)) {
                                property.change = account.LastChange;
                            }
                            properties.push(property);
                        }


                        if (account.hasOwnProperty('EliteStatus') && account.EliteStatus.Name)
                            properties.push(
                                {
                                    name: Translator.trans('award.account.list.column.status', {}, 'messages'),
                                    value: account.EliteStatus.Name
                                }
                            );

                        if (account.hasOwnProperty('ExpirationDatePush') && typeof account.ExpirationDatePush === 'string')
                            properties.push(
                                {
                                    name: Translator.trans('account.label.expiration', {}, 'messages'),
                                    value: account.ExpirationDatePush
                                }
                            );

                        return properties;
                    }

                    var accountKey = account.TableName.toLowerCase()[0] + account.ID;
                    var regexDisplayName = /^(.+?)(\((?:[^\)]+)\))?$/,
                        displayName = regexDisplayName.exec(account.DisplayName);
                    PushNotification.createNotification({
                        id: platform.android ? account.ID : accountKey,
                        body: Translator.trans(/** @Desc("Here is your %programName% card") */'notifications.barcode.body', {programName: displayName[1].trim()}, 'mobile'),
                        categoryIdentifier: platform.android || $scope.barcode.type !== 'QR_CODE' ? 'barcode' : 'qrcode',
                        userInfo: {
                            a: accountKey,
                            displayName: displayName[1].trim(),
                            barCodeData: BarcodeUtils.compute($scope.barcode.type, $scope.barcode.data),
                            barCodeType: $scope.barcode.type,
                            userName: account.UserName,
                            providerFontColor: '#' + (account.FontColor || 'FFFFFF'),
                            providerBgColor: '#' + (account.BackgroundColor || '515766'),
                            providerLogo: account.ProviderCode || "",
                            properties: getAccountProperties(account)
                        }
                    }, {
                        type: 'timeInterval',
                        timeInterval: {
                            interval: 15,
                            repeats: false
                        }
                    });
                }
            };

            $scope.$watchCollection(function () {
                if (AccountList.getAccount(params.Id)) {
                    var account = AccountList.getAccount(params.Id);
                    if (account) {
                        if (params.subId)
                            account = AccountList.filter(account, 'SubAccountsArray', {SubAccountID: params.subId}, true);
                        return account[barcodeProperty];
                    }
                }
            }, function (barcode) {
                if (
                    barcode &&
                    barcode.BarCodeData &&
                    barcode.BarCodeType
                ) {
                    $scope.barcode.data = barcode.BarCodeData;
                    $scope.barcode.type = barcode.BarCodeType;
                    $scope.barcode.access.displayNumber = ['QR_CODE', 'DATA_MATRIX'].indexOf(barcode.BarCodeType) === -1;
                } else {
                    $scope.barcode.data = account.Number || account.Login;
                }
            });

        } else {
            $state.go('index.accounts.list');
        }
    }
]);