if (platform.cordova && platform.android) {
    angular.module('AwardWalletMobile').service('inAppPurchase', ['$q', '$http', '$rootScope',
        function ($q, $http, $rootScope) {

            var initializing = false, _products = {}, constants = {
                products: [
                    {id: 'awardwallet_plus_managed', type: store.CONSUMABLE},
                    {id: 'plus_subscription_1year', type: store.PAID_SUBSCRIPTION},
                    {id: 'plus_subscription_1year_discounted', type: store.PAID_SUBSCRIPTION}
                ]
            };

            var broadcast = function () {
                var args = arguments;
                $rootScope.$evalAsync(function () {
                    $rootScope.$broadcast.apply($rootScope, args);
                });
            };

            var _validator = function (transaction, callback, productId) {
                console.log('validator', JSON.stringify(transaction));

                $http({
                    method: 'post',
                    url: '/inAppPurchase/confirm',
                    timeout: 30000,
                    data: transaction,
                    globalError: false,
                    retries: 3
                }).then(function () {
                    callback(productId, true);
                }, function () {
                    callback(productId, false, {
                        code: store.CONNECTION_FAILED
                    });
                });
            };

            function _refund(transaction) {
                console.log('refund', JSON.stringify(transaction));

                $http({
                    method: 'post',
                    url: '/inAppPurchase/refund',
                    timeout: 30000,
                    data: transaction,
                    globalError: false,
                    retries: 3
                });
            }

            function _consumePurchase (productId) {
                inappbilling.consumePurchase(angular.noop, angular.noop, productId);
            }

            function _finish(productId, complete, data) {
                if (complete) {
                    if(_products[productId] && _products[productId].type === store.CONSUMABLE) {
                        _consumePurchase(productId)
                    }
                    broadcast('store:finished', data);
                    return;
                }
                broadcast('store:error', data);
            }

            function _purchase(productId, additionalData) {
                var method = 'buy', transaction;
                if (_products[productId].type == 'subs') {
                    method = 'subscribe';
                }
                console.log('storekit purchasing', productId);
                broadcast('store:initiated', productId);
                inappbilling[method](function(data) {
                    transaction = {
                        type: 'android-playstore',
                        id: data.orderId,
                        purchaseToken: data.purchaseToken,
                        developerPayload: data.developerPayload,
                        receipt: data.receipt,
                        signature: data.signature
                    };
                    _validator(transaction, _finish, productId);
                }, function(err, code) {
                    if (code === store.ERR_PAYMENT_CANCELLED) {
                        broadcast('store:cancelled', {
                            productId: productId,
                            code: code,
                            message: err
                        });
                        return;
                    }
                    broadcast('store:error', {
                        code: code,
                        message: err
                    });
                }, productId, additionalData);
            }


            function _getPurchases (){
                inappbilling.getPurchases(function (purchases) {
                    if (purchases && purchases.length) {
                        for (var i = 0; i < purchases.length; ++i) {
                            if(purchases[i].purchaseState == 2 ) {
                                _refund(purchases[i]);
                            }else{
                                _validator(purchases[i], _finish, purchases[i].productId);
                            }
                        }
                    }
                    console.log('_getPurchases', purchases);
                }, angular.noop);
            }

            function _loadProducts(validProducts) {
                initializing = true;
                console.log('storekit products loaded', arguments);
                for (var i = 0; i < validProducts.length; ++i) {
                    var description = validProducts[i].description;
                    if (_products[validProducts[i].productId] && _products[validProducts[i].productId].description) {
                        description = _products[validProducts[i].productId].description + '';
                    }
                    _products[validProducts[i].productId] = validProducts[i];
                    _products[validProducts[i].productId].description = description;
                }
                _getPurchases();
            }

            function _ready() {
                console.log('storekit ready', arguments);
                inappbilling.getAvailableProducts(_loadProducts, angular.noop);
            }

            function _failed() {
                console.log('storekit init fail', arguments);
            }

            return {
                init: function(){
                    var q = $q.defer();
                    if(!initializing){
                        inappbilling.init(function () {
                                q.resolve();
                                _ready();
                            }, function () {
                                q.reject();
                                _failed();
                            }, {
                                showLog: !!window.debugMode
                            }, Object.keys(_products)
                        );
                    }else{
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
                    this.init()
                },
                getProducts: function () {
                    return _products;
                },
                getProduct: function (id) {
                    return _products[id];
                },
                purchase: function (id, additionalData) {
                    _purchase('' + id, additionalData);
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