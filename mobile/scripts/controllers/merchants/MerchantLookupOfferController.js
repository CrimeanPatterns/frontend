angular.module('AwardWalletMobile').controller('MerchantLookupOfferController', [
    '$scope',
    '$stateParams',
    '$http',
    'vcRecaptchaService',
    function ($scope, $stateParams, $http, vcRecaptchaService) {
        const {merchantName} = $stateParams;

        $scope.loading = true;

        $scope.recaptcha = {
            _retries: 0,
            key: null,
            setKey: function (key) {
                this.key = key;
            },
            getKey: function () {
                return this.key;
            },
            widgetId: null,
            response: null,
            getResponse: function () {
                return this.response;
            },
            reset: function () {
                this.response = null;
                if (this.widgetId != null && this._retries < 5) {
                    vcRecaptchaService.reload(this.widgetId);
                    this.onCreate(this.widgetId);
                    this._retries++;
                }
            },
            setResponse: function (response) {
                this.response = response;
                loadOffer(merchantName);
            },
            onCreate: function (widgetId) {
                this.widgetId = widgetId;
                try {
                    var response = vcRecaptchaService.getResponse(this.widgetId);
                } catch (e) {
                    console.log(e);
                } finally {
                    if (!response) {
                        vcRecaptchaService.execute(this.widgetId);
                    } else {
                        this.setResponse(response);
                    }
                }
            },
            cbExpiration: function () {
                this.response = null;
            }
        };

        function loadOffer(merchantName) {
            return $http({
                url: '/account/merchants/offer-name/' + merchantName,
                method: 'GET',
                timeout: 30000,
                headers: {
                    'X-RECAPTCHA': $scope.recaptcha.getResponse()
                }
            }).then(function (response) {
                if (angular.isString(response.data)) {
                    $scope.content = response.data;
                }
            }, function (response) {
                var recaptchaFailed = response.headers('X-RECAPTCHA-FAILED');

                if (recaptchaFailed) {
                    if ($scope.recaptcha.getKey() !== null) {
                        $scope.recaptcha.reset();
                    } else {
                        $scope.recaptcha.setKey(response.headers('X-RECAPTCHA-KEY'));
                    }
                }
            });
        }

        if (merchantName) {
            loadOffer(merchantName);
        } else {
            $scope.back('index.accounts.list');
        }
    }
]);
