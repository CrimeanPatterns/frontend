angular.module('AwardWalletMobile').directive('timeAgo', ['$window', 'DateTimeDiff', function ($window, DateTimeDiff) {
    return {
        restrict: 'EA',
        scope: {
            timeAgo: '=',
            fromDate: '=',
            withoutSuffix: '=',
            shortFormat: '=',
            localDate: '='
        },
        link: function (scope, element, attrs) {
            var activeTimeout = null,
                isBindOnce = (attrs.timeAgo.indexOf('::') === 0),
                fromDate = scope.fromDate ? new Date(scope.fromDate) : null,
                withoutSuffix = !!scope.withoutSuffix,
                shortFormat = !!scope.shortFormat,
                unwatchChanges;

            function cancelTimer() {
                if (activeTimeout) {
                    clearTimeout(activeTimeout);
                    activeTimeout = null;
                }
            }

            function updateTime(updateDate, localDate) {
                var now = new Date(), from = fromDate || now;
                var diff = now.getTime() - updateDate.getTime();
                diff = Math.abs(diff);

                if (diff >= 86400000/* 1 day */ && !fromDate) {
                    if (localDate) {
                        element.text(DateTimeDiff.longFormatViaDates(from, localDate));
                    } else {
                        element.text(DateTimeDiff.longFormatViaDates(from, updateDate));
                    }
                } else {
                    if (shortFormat) {
                        element.text(DateTimeDiff.shortFormatViaDateTimes(from, updateDate, !withoutSuffix));
                    } else {
                        element.text(DateTimeDiff.longFormatViaDateTimes(from, updateDate, !withoutSuffix));
                    }
                }

                if (!isBindOnce) {
                    activeTimeout = setTimeout(function () {
                        updateTime(updateDate, localDate);
                    }, 60 * 1000);
                }
            }

            function updateMoment(date, localDate) {
                cancelTimer();
                if (date) {
                    updateTime(date, localDate);
                }
            }

            unwatchChanges = scope.$watchGroup(['timeAgo', 'localDate'], function (values) {
                var date = values[0];
                var localDate = values[1];

                if (date && !(date instanceof Date)) {
                    date = new Date(date);
                }

                if (localDate && !(localDate instanceof Date)) {
                    localDate = new Date(localDate);
                }

                if ((typeof date === 'undefined') || (date === null) || (date === '')) {
                    cancelTimer();
                    element.text('');
                    return;
                }

                updateMoment(date, localDate);

                if (date !== undefined && isBindOnce) {
                    unwatchChanges();
                }
            });

            scope.$on('$destroy', function () {
                cancelTimer();
            });
        }
    }
}]);