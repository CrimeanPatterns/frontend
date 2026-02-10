if (platform.cordova && platform.ios) {
    angular.module('AwardWalletMobile').service('inAppPurchase', ['$q', '$http', '$rootScope', '$cordovaDialogs',
        function ($q, $http, $rootScope, $cordovaDialogs) {

            var initializing = false, restored = false, _products = {}, constants = {
                products: [
                    {id: '10', type: store.PAID_SUBSCRIPTION},
                    {id: '11', type: store.PAID_SUBSCRIPTION},
                    {id: '12', type: store.PAID_SUBSCRIPTION}
                ]
            };

            var broadcast = function () {
                var args = arguments;
                $rootScope.$evalAsync(function () {
                    $rootScope.$broadcast.apply($rootScope, args);
                });
            };

            var _validator = function (transaction, callback) {
                console.log('validator', JSON.stringify(transaction));

                $http({
                    method: 'post',
                    url: '/inAppPurchase/confirm',
                    timeout: 60000,
                    data: transaction,
                    globalError: false,
                    retries: 5
                }).then(function () {
                    callback(transaction.id, true, transaction);
                }, function () {
                    callback(transaction.id, false, {
                        code: store.CONNECTION_FAILED
                    });
                });
            };

            function _error(errorCode, errorText, options) {
                if (!options) options = {};
                console.log('storekit error ' + errorCode + ': ' + errorText + ' - ' + JSON.stringify(options));
                if (errorCode === storekit.ERR_PAYMENT_CANCELLED) {
                    broadcast('store:cancelled', {
                        productId: options.productId,
                        code: errorCode,
                        message: errorText
                    });
                    return;
                }
                broadcast('store:error', {
                    code: errorCode,
                    message: errorText
                });
            }

            function _finish(transactionId, complete, data) {
                if (complete) {
                    storekit.finish(transactionId);
                    broadcast('store:finished', data);
                    return;
                }
                broadcast('store:error', data);
            }

            function _purchase(transactionId, productId) {
                var transaction;
                console.log('storekit purchase: transaction: ' + transactionId + ', product: ' + productId);
                storekit.loadReceipts(function (receipts) {
                    transaction = {
                        type: 'ios-appstore',
                        id: transactionId,
                        appStoreReceipt: storekit.appStoreReceipt,
                        transactionReceipt: receipts.forTransaction(transactionId)
                    };
                    _validator(transaction, _finish);
                });
            }

            function _purchasing(productId) {
                console.log('storekit purchasing', arguments);
                broadcast('store:initiated', productId);
            }

            function _restore(transactionId) {
                console.log('_restore', arguments);
                storekit.finish(transactionId)
            }

            function _restoreCompleted(canceled) {
                console.log('_restoreCompleted', arguments);
                var data = {
                    appStoreReceipt: storekit.appStoreReceipt,
                    receiptForTransaction: storekit.receiptForTransaction
                };
                if (canceled) {
                    data.canceled = canceled
                }
                //temporary fix
                $http({
                    method: 'post',
                    url: '/inAppPurchase/restore',
                    timeout: 60000,
                    data: data,
                    globalError: false,
                    retries: 5
                });
            }

            function _restoreFailed() {
                console.log('_restoreFailed', arguments);
            }

            function _loadProducts(validProducts, invalidProductIds) {
                console.log('storekit products loaded', arguments);
                for (var i = 0; i < validProducts.length; ++i) {
                    _products[validProducts[i].id].price = validProducts[i].price;
                    if (!_products[validProducts[i].id].hasOwnProperty('description') && validProducts[i].description) {
                        _products[validProducts[i].id].description = validProducts[i].description;
                    }
                }
            }

            function _ready(products) {
                console.log('storekit ready', arguments);
                initializing = true;
                storekit.load(products, _loadProducts);
            }

            function _failed() {
                console.log('storekit init fail', arguments);
            }

            return {
                init: function () {
                    var q = $q.defer();
                    if (!initializing) {
                        storekit.init({
                            debug: !!window.debugMode,
                            noAutoFinish: true,
                            error: _error,
                            purchase: _purchase,
                            purchasing: _purchasing,
                            restore: _restore,
                            restoreCompleted: _restoreCompleted,
                            restoreFailed: _restoreFailed
                        }, function () {
                            q.resolve();
                            _ready(Object.keys(_products));
                        }, function () {
                            _failed();
                            q.reject();
                        });
                    } else {
                        q.resolve();
                    }
                    return q.promise;
                },
                register: function (products) {
                    products = products || constants.products;
                    for (var i = 0; i < products.length; ++i) {
                        if (_products[products[i].id]) {
                            _products[products[i].id].description = products[i].description;
                        } else {
                            _products[products[i].id] = products[i];
                        }
                    }
                    this.init();
                },
                restore: function () {
                    if (
                        initializing &&
                        !restored
                    ) {
                        restored = true;
                        $cordovaDialogs.confirm(
                            'We would like to verify if you have an active AwardWallet Plus subscription with Apple. For that, you will need to enter your Apple ID password when prompted (will be sent to Apple not to AwardWallet). Please note that you are not being charged anything during this process.',
                            'Subscription Verification',
                            [
                                'Verify',
                                Translator.trans('cancel', {}, 'messages')
                            ]
                        ).then(function (button) {
                            if (button === 1) {
                                storekit.refreshReceipts(function () {
                                    storekit.restore();
                                });
                            } else {
                                _restoreCompleted(true);
                            }
                        });
                    }
                },
                getProducts: function () {
                    return _products;
                },
                getProduct: function (id) {
                    return _products[id];
                },
                purchase: function (id) {
                    storekit.purchase('' + id);
                },
                getAvailableProduct: function () {
                    return $http({
                        method: 'get',
                        url: '/inAppPurchase/product',
                        timeout: 30000,
                        globalError: false
                    });
                }
            };
        }
    ]);
}