angular.module('AwardWalletMobile').directive('infiniteScroll', ['$timeout', function ($timeout) {
    return function (scope, element, attr) {
        var
            lengthThreshold = attr.scrollThreshold || 50,
            timeThreshold = attr.timeThreshold || 400,
            handler = scope.$eval(attr.infiniteScroll),
            container = attr.container || null,
            promise = null,
            lastRemaining = 9999,
            element = container ? angular.element(document.querySelector(container)) : element;

        lengthThreshold = parseInt(lengthThreshold, 10);
        timeThreshold = parseInt(timeThreshold, 10);

        if (!handler || !angular.isFunction(handler)) {
            handler = angular.noop;
        }

        element.bind('scroll', function () {
            var remaining = element[0].scrollHeight - (element[0].clientHeight + element[0].scrollTop);
            if (remaining < lengthThreshold && (remaining - lastRemaining) < 0) {
                if (promise !== null) {
                    $timeout.cancel(promise);
                }
                promise = $timeout(function () {
                    handler();
                    promise = null;
                }, timeThreshold);
            }
            lastRemaining = remaining;
        });
    };
}]);