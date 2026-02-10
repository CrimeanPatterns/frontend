angular.module('AwardWalletMobile').directive('timerCountdown', [function () {
    return {
        restrict: 'EA',
        scope: {
            timerCountdown: '='
        },
        link: function (scope, element, attrs) {
            var activeTimeout = null,
                unwatchChanges;

            function cancelTimer() {
                if (activeTimeout) {
                    clearTimeout(activeTimeout);
                    activeTimeout = null;
                }
            }

            function getTimeRemaining(dateEnd) {
                const diff = dateEnd - Date.now();

                function zero(value) {
                    return Math.max(value, 0);
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24)),
                    hours = Math.floor((diff / (1000 * 60 * 60)) % 24),
                    minutes = Math.floor((diff / 1000 / 60) % 60),
                    seconds = Math.floor((diff / 1000) % 60);

                return [diff, days, hours, minutes, seconds].map(zero);
            }

            function formatTime(hours, minutes, seconds) {
                return [hours, minutes, seconds].map(function(value) {
                    return ('0' + value).slice(-2);
                }).join(':');
            }

            function updateTime(date) {
                var remaining = getTimeRemaining(new Date(date * 1000)),
                    total = remaining[0],
                    days = remaining[1],
                    hours = remaining[2],
                    minutes = remaining[3],
                    seconds = remaining[4],
                    time = [];

                if (total !== 0 && days > 0) {
                    time.push(Translator.transChoice('interval_short.days', days, {count: days}, 'messages'));
                    time.push(Translator.trans('and.text', 'messages'));
                }

                time.push(formatTime(hours, minutes, seconds));

                element.text(time.join(' '));

                activeTimeout = setTimeout(function () {
                    updateTime(date);
                }, 1000);
            }

            function updateMoment(date) {
                cancelTimer();
                if (date) {
                    updateTime(date);
                }
            }

            unwatchChanges = scope.$watch('timerCountdown', function (newValue, oldValue) {
                if (typeof newValue === 'undefined' || newValue === null || newValue === '') {
                    cancelTimer();
                    element.text('');
                    return;
                }

                updateMoment(newValue);
            });

            scope.$on('$destroy', function () {
                cancelTimer();
            });
        }
    };
}]);