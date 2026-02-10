angular.module('AwardWalletMobile').directive('captcha', ['ReCaptcha', '$document', '$timeout', function (ReCaptcha, $document, $timeout) {
    return {
        restrict: 'A',
        scope: {
            key: '=',
            vendor: '=',
            url: '=',
            theme: '=',
            lang: '=',
            size: '=',
            onCreate: '&',
            onSuccess: '&',
            onExpire: '&',
            onError: '&'
        },
        link: function (scope, element, attrs) {
            scope.widgetId = null;

            function expired(){
                $timeout(function () {
                    scope.onExpire({ widgetId: scope.widgetId });
                });
            }

            function error() {
                var args = arguments;
                $timeout(function () {
                    scope.onError({ widgetId: scope.widgetId, arguments: args });
                });
            }

            function destroy() {
                ReCaptcha.remove(scope.widgetId);
            }

            var removeCreationListener = scope.$watch('key', function (key) {
                var callback = function (recaptchaResponse) {
                    $timeout(function () {
                        scope.onSuccess({response: ReCaptcha.isValidResponse(recaptchaResponse, scope.vendor) ? recaptchaResponse : undefined, widgetId: scope.widgetId});
                    });
                };

                ReCaptcha.create(element[0], scope.url, scope.vendor, {
                    callback: callback,
                    sitekey: key,
                    theme: scope.theme,
                    size: scope.size,
                    lang: scope.lang,
                    'expired-callback': expired,
                    'error-callback': attrs.onError ? error : undefined
                }).then(function (widgetId) {
                    scope.widgetId = widgetId;
                    scope.onCreate({widgetId: widgetId});
                    scope.$on('$destroy', destroy);
                });

                removeCreationListener();
            });
        }
    };
}]);