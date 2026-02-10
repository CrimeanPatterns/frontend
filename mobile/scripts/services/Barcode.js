angular.module('AwardWalletMobile').service('Barcode', [
    '$q',
    '$http',
    'AccountList',
    function ($q, $http, AccountList) {
        var formats = [
            'CODABAR',
            'CODE_39',
            'CODE_93',
            'CODE_128',
            'DATA_MATRIX',
            'EAN_8',
            'EAN_13',
            'ITF',
            'PDF417',
            'QR_CODE',
            'UPC_A',
            'UPC_E'
        ];

        function setProperties(accountId, subAccountId, properties) {
            var account = AccountList.getAccount(accountId), subAccount;
            if (subAccountId)
                subAccount = AccountList.filter(account, 'SubAccountsArray', {SubAccountID: subAccountId}, true);
            for (var key in properties) {
                if (properties.hasOwnProperty(key)) {
                    if (subAccount) {
                        subAccount[key] = properties[key];
                    } else {
                        account[key] = properties[key];
                    }
                }
            }
            return AccountList.setAccount(account, subAccount);
        }

        return {
            scan: function (config) {
                var q = $q.defer();
                config = config || {preferFrontCamera: false, disableSuccessBeep: true};
                if (!config.formats)
                    config.formats = formats.join();
                cordova.plugins.barcodeScanner.scan(function (response) {
                    if (response.cancelled) {
                        q.reject();
                        return;
                    }
                    q.resolve(response);
                }, function () {
                    q.reject();
                }, config);
                return q.promise;
            },
            save: function (accountId, properties, action) {
                action = action || 'put';
                var url, account, subAccountId;

                if (typeof accountId == 'object') {
                    subAccountId = accountId['subId'];
                    accountId = accountId['Id'];
                }

                account = setProperties(accountId, subAccountId, {
                    'BarCodeCustom': properties
                });

                url = ['', 'customLoyaltyProperty', account.TableName.toLowerCase(), account.ID];

                if (subAccountId)
                    url.push(subAccountId);

                return $http({
                    url: url.join('/'),
                    method: action,
                    timeout: 30000,
                    globalError: false,
                    data: properties,
                    retries: 5
                });
            },
            remove: function (accountId, properties) {
                return this.save(accountId, properties, 'delete')
            },
            formats: formats
        }
    }
]);