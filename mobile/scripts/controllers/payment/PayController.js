angular.module('AwardWalletMobile').controller('PayController', [
    '$scope',
    'inAppPurchase',
    '$stateParams',
    'Database',
    'btfModal',
    function ($scope, inAppPurchase, $stateParams, Database, btfModal) {
        if (
            platform.cordova &&
            $scope.user &&
            $scope.user.Free
        ) {
            var productId,
                initiated,
                splashScreen = btfModal({
                    templateUrl: 'templates/directives/popups/popup-splashscreen.html',
                    uid: 'splashScreen'
                });

            function onReject() {
                $scope.spinnerLoadingPage = false;
                $scope.errorUpgrade = Translator.trans(/** @Desc("Unfortunately you appear to be offline, thus we are not able to process this transaction.") */ 'alerts.upgradeOffline', {}, 'messages');
            }

            $scope.spinnerLoadingPage = true;

            inAppPurchase.init().then(function () {
                inAppPurchase.getAvailableProduct().then(function (response) {
                    response = response.data;
                    productId = response.productId;
                    $scope.product = inAppPurchase.getProduct(productId);
                    $scope.spinnerLoadingPage = false;
                }, onReject);
            }, onReject);

            $scope.buy = function () {
                $scope.errorUpgrade = false;
                $scope.alreadyUpgraded = false;
                $scope.purchaseLoading = true;
                inAppPurchase.purchase(productId, {developerPayload: {UserID: $scope.user.UserID}});
            };

            $scope.$on('store:initiated', function () {
                initiated = true;
            });

            $scope.$on('store:cancelled', function () {
                if (initiated) {
                    $scope.spinnerLoadingPage = false;
                    $scope.errorUpgrade = false;
                    $scope.alreadyUpgraded = false;
                    $scope.purchaseLoading = false;
                    initiated = false;
                }
            });

            $scope.$on('store:error', function (event, data) {
                if (initiated && data) {
                    if ($scope.purchaseLoading && data.code !== 6777010) {
                        $scope.errorUpgrade = data.message || Translator.trans(/** @Desc("Unfortunately the upgrade didn’t go through. Please verify that you are connected to the internet and try again.") */ 'alerts.errorUpgrade', {}, 'messages');
                    }
                    $scope.spinnerLoadingPage = false;
                    $scope.purchaseLoading = false;
                    initiated = false;
                }
            });

            $scope.$on('store:finished', function (event, data) {
                if (initiated) {
                    $scope.purchaseLoading = false;
                    splashScreen.open();
                    Database.destroy();
                    Database.update().then(function () {
                        $scope.alreadyUpgraded = true;
                        splashScreen.close();
                    });
                }
            });

        } else {
            window.history.back();
        }
    }
]);