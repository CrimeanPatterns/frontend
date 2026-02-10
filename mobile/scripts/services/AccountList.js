angular.module('AwardWalletMobile').service('AccountList', [
    '$filter',
    'SessionService',
    '$rootScope',
    function ($filter, SessionService, $rootScope) {
        var accountList = [],
            searchAccountList = [],
            accounts = {},
            providerKinds = {},
            notifications = {};
        var counters = {
            errors: 0,
            totals: 0,
            accounts: 0
        };
        var defaultCounters = angular.copy(counters);

        function object2array(obj) {
            var out = [];
            for (var i in obj) {
                if (obj.hasOwnProperty(i)) {
                    var item = angular.extend({}, obj[i]);
                    item.KEY = i;
                    out.push(item);
                }
            }
            return out;
        }

        function getList() {
            if (accountList.length < 1) {
                accountList = orderAccounts(accounts);
            }
            return accountList;
        }

        function getSearchList() {
            if (searchAccountList.length < 1) {
                searchAccountList = orderAccounts(accounts, true);
            }
            return searchAccountList;
        }

        function getAccounts() {
            return angular.extend({}, accounts);
        }

        function setAccounts(newAccounts) {
            if (angular.isObject(newAccounts)) {
                accounts = newAccounts;
                accountList = [];
                searchAccountList = [];
                attachCoupons();
                buildNotifications();
                $rootScope.$broadcast('accountList:update');
            } else {
                throw new TypeError('AccountList.setAccounts called on non-object');
            }
        }

        function attachCoupons() {
            for (var accountKey in accounts) {
                if (accounts.hasOwnProperty(accountKey)) {
                    if (accounts[accountKey].TableName === 'Coupon') {
                        attachCoupon(accounts[accountKey]);
                    }
                }
            }
        }

        function detachCoupon(coupon) {
            if (coupon.TableName === 'Coupon') {
                var prevAccount = accounts['c' + coupon.ID];

                if (prevAccount && prevAccount.ParentAccount) {
                    var parentPrevAccount = accounts['a' + prevAccount.ParentAccount];
                    if (parentPrevAccount) {
                        for (var i in parentPrevAccount['SubAccountsArray']) {
                            if (parentPrevAccount['SubAccountsArray'].hasOwnProperty(i)) {
                                if (parentPrevAccount['SubAccountsArray'][i].ID === coupon.ID) {
                                    parentPrevAccount['SubAccountsArray'].splice(i, 1);
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
        }

        function attachCoupon(coupon) {
            if (coupon.TableName === 'Coupon') {
                detachCoupon(coupon);
                if (coupon.ParentAccount) {
                    var parentAccount = accounts['a' + coupon.ParentAccount], linked = false;
                    if (parentAccount) {
                        parentAccount['SubAccountsArray'] = parentAccount['SubAccountsArray'] || [];
                        for (var i in parentAccount['SubAccountsArray']) {
                            if (parentAccount['SubAccountsArray'].hasOwnProperty(i)) {
                                if (
                                    parentAccount['SubAccountsArray'][i].ParentAccount &&
                                    parentAccount['SubAccountsArray'][i].ID === coupon.ID
                                ) {
                                    parentAccount['SubAccountsArray'][i] = coupon;
                                    linked = true;
                                    continue;
                                }
                            }
                        }
                        if (!linked)
                            parentAccount['SubAccountsArray'].push(coupon);
                        //setAccount(parentAccount);
                    }
                }
            }
        }

        function BarcodeNotification(properties, location) {
            var excludedTypes = {
                ios: ['DATA_MATRIX'],
                android: []
            };
            var barcode = properties.barcode,
                account = properties.account,
                subAccount = properties.subAccount;

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

            if (
                !platform.cordova ||
                (
                    platform.cordova &&
                    excludedTypes[device.platform.toLowerCase()].indexOf(barcode.BarCodeType) < 0
                )
            ) {
                var accountKey = account.TableName.toLowerCase()[0] + account.ID;
                if (subAccount) {
                    accountKey += '.' + subAccount.SubAccountID;
                }
                var regexDisplayName = /^(.+?)(\((?:[^\)]+)\))?$/,
                    displayName = regexDisplayName.exec(account.DisplayName);

                return {
                    notification: {
                        body: Translator.trans(/** @Desc("Here is your %programName% card") */'notifications.barcode.body', {programName: displayName[1].trim()}, 'mobile'),
                        categoryIdentifier: platform.android || barcode.BarCodeType !== 'QR_CODE' ? 'barcode' : 'qrcode',
                        userInfo: {
                            a: accountKey,
                            displayName: displayName[1].trim(),
                            barCodeData: BarcodeUtils.compute(barcode.BarCodeType, barcode.BarCodeData),
                            barCodeType: barcode.BarCodeType,
                            userName: account.UserName,
                            providerFontColor: '#' + (account.FontColor || 'FFFFFF'),
                            providerBgColor: '#' + (account.BackgroundColor || '515766'),
                            providerLogo: account.ProviderCode || "",
                            properties: getAccountProperties(subAccount || account)
                        }
                    },
                    trigger: {
                        type: 'geofence',
                        geofence: {
                            lat: location.Lat,
                            lng: location.Lng,
                            radius: location.Radius,
                            locationId: location.LocationID
                        }
                    }
                }
            }
        }

        function getAccountBarcodeNotifications(account) {
            var notifications = [];

            function createNotifications(properties) {
                var account = properties.account,
                    subAccount = properties.subAccount,
                    barcode = properties.barcode;
                var notifications = [], locations = subAccount ? subAccount.Locations : account.Locations, notification;
                for (var i = 0, l = locations.length; i < l; i++) {
                    notification = BarcodeNotification(properties, locations[i]);
                    if (notification) {
                        notifications.push(notification);
                    }
                }
                return notifications;
            }

            if (account) {
                var barcode;
                if (account.BarCodeCustom && account.BarCodeCustom.BarCodeData)
                    barcode = account.BarCodeCustom;
                else if (account.BarCodeParsed && account.BarCodeParsed.BarCodeData)
                    barcode = account.BarCodeParsed;
                if (account.Locations && barcode)
                    notifications = createNotifications({account: account, barcode: barcode});
                if (account.SubAccountsArray) {
                    for (var j = 0, k = account.SubAccountsArray.length, subAccount, subAccountBarcode; j < k; j++) {
                        subAccount = account.SubAccountsArray[j];
                        if (subAccount.BarCodeCustom && subAccount.BarCodeCustom.BarCodeData)
                            subAccountBarcode = subAccount.BarCodeCustom;
                        else if (subAccount.BarCodeParsed && subAccount.BarCodeParsed.BarCodeData)
                            subAccountBarcode = subAccount.BarCodeParsed;
                        if (subAccount.Locations && subAccountBarcode) {
                            notifications = notifications.concat(createNotifications({
                                account: account,
                                subAccount: subAccount,
                                barcode: subAccountBarcode
                            }));
                        }
                    }
                }
            }

            return notifications;
        }

        function buildNotifications() {
            notifications = {};
            if (accounts) {
                for (var key in accounts) {
                    if (accounts.hasOwnProperty(key)) {
                        notifications[key] = getAccountBarcodeNotifications(accounts[key]);
                    }
                }
            }
        }

        function setProviderKinds(kinds) {
            if (typeof providerKinds === 'object') {
                providerKinds = kinds;
            } else {
                throw new TypeError('AccountList.setProviderKinds called on non-object');
            }
        }

        function getProviderKinds() {
            return providerKinds;
        }

        function orderAccounts(accounts, search) {
            var orderedAccounts;
            var user = SessionService.getProperty('userId');
            var userIdOrder = function (item) {
                var userID = parseInt(item['UserID']),
                    userAgentID = item['UserAgentID'] === null ? null : parseInt(item['UserAgentID']),
                    ownerID = parseInt(user);

                if (userAgentID === null && userID === ownerID)
                    return -1;
                else if (item['FamilyName'])
                    return item['FamilyName'].toLowerCase();
                else
                    return item['UserName'].toLowerCase();
            };

            orderedAccounts = object2array(accounts);
            if (!search) {
                orderedAccounts = $filter('orderBy')(orderedAccounts, [function (item) {
                    if (providerKinds.hasOwnProperty(item['Kind']))
                        return parseInt(providerKinds[item['Kind']]['index']);
                    return 0;
                }, userIdOrder, 'DisplayName']);
            } else {
                orderedAccounts = $filter('orderBy')(orderedAccounts, [userIdOrder, 'DisplayName']);
            }

            return orderedAccounts;
        }

        function getCounters() {
            counters = angular.copy(defaultCounters);
            for (var id in accounts) {
                if (accounts.hasOwnProperty(id)) {
                    if (!accounts[id].ParentAccount){
                        counters.accounts++;
                    }
                    if (accounts[id].hasOwnProperty('TotalBalance')){
                        counters.totals += parseFloat(accounts[id]['TotalBalance']) || 0;
                    }
                    if (accounts[id].Error){
                        counters.errors++;
                    }
                }
            }
            return counters;
        }

        function deleteAccount(accountId) {
            if (typeof accountId === 'string' && accounts.hasOwnProperty(accountId)) {
                if(accounts[accountId].TableName === 'Coupon')
                    detachCoupon(accounts[accountId]);
                delete accounts[accountId];
                if (notifications[accountId])
                    delete notifications[accountId];
                accountList = [];
                searchAccountList = [];
                $rootScope.$broadcast('accountList:update');
            } else {
                return false;
            }
        }

        function setAccount(account, subAccount) {
            if (
                angular.isObject(account) &&
                account.hasOwnProperty('ID') &&
                accounts.hasOwnProperty(account.TableName[0].toLowerCase() + account.ID)
            ) {
                if (angular.isObject(subAccount)) {
                    for (var i in account['SubAccountsArray']) {
                        if (account['SubAccountsArray'].hasOwnProperty(i)) {
                            if (
                                account['SubAccountsArray'][i] &&
                                account['SubAccountsArray'][i].SubAccountID === subAccount.SubAccountID
                            ) {
                                account['SubAccountsArray'][i] = subAccount;
                            }
                        }
                    }
                }
                addAccount(account);
                return accounts[account.TableName[0].toLowerCase() + account.ID];
            }
        }

        function addAccount(account) {
            if (angular.isObject(account) && account.hasOwnProperty('ID')) {
                var key = account.TableName[0].toLowerCase() + account.ID;
                if (account.TableName === 'Coupon')
                    attachCoupon(account);
                accounts[key] = account;
                notifications[key] = getAccountBarcodeNotifications(account);
                accountList = [];
                searchAccountList = [];
                $rootScope.$broadcast('accountList:update');
                return true;
            } else {
                return false;
            }
        }

        function getAccount(accountId) {
            if (typeof accountId === 'string' && accounts.hasOwnProperty(accountId)) {
                return accounts[accountId];
            } else {
                return null;
            }
        }

        function getLength() {
            getCounters();
            return counters.accounts;
        }

        var _this = {
            deleteAccount: deleteAccount,
            setAccount: setAccount,
            getAccount: getAccount,
            addAccount: addAccount,
            filter: function (account, property, value, unique) {
                if (unique) {
                    return $filter('filter')(account[property] || [], value, true)[0]
                }
                return $filter('filter')(account[property] || [], value);
            },
            getAccounts: getAccounts,
            setAccounts: setAccounts,
            getAccountsNotifications: function () {
                return [].concat.apply([], Object.keys(notifications).map(function (k) {
                    return notifications[k];
                }));
            },
            setProviderKinds: setProviderKinds,
            getProviderKinds: getProviderKinds,
            getList: getList,
            getSearchList: getSearchList,
            getLength: getLength,
            getCounters: getCounters,
            destroy: function () {
                accountList = [];
                searchAccountList = [];
                accounts = {};
                providerKinds = {};
                notifications = {};
            }
        };

        $rootScope.$on('app:storage:destroy', _this.destroy);

        return _this;
    }
])
;