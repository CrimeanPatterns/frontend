var ProgressBar = function ProgressBar(element) {

    if (!(element instanceof Element)) {
        throw new Error(element + ' is not instanceof DOMNodeElement');
    }

    var obj = {};

    obj.element = element;

    var percents = obj.element.querySelector('.progress-percents'), bar = obj.element.querySelector('.progress-bar');
    var $bar = $(bar);

    var toBarPercents = function (num) {
        return num * 100;
    };

    obj.inc = function (num, duration, callback) {
        var options = {
            step: function (now) {
                $(percents).text(Math.round(now) + '%');
            },
            easing: 'linear',
            duration: duration,
            complete: callback
        };
        $bar.stop(true).animate({width: toBarPercents(num) + '%'}, options);
    };


    obj.done = function (callback) {
        obj.inc(1, 1000, callback);
    };

    obj.stop = function () {
        $bar.stop(true);
    };

    return obj;
};

angular.module('AwardWalletMobile').directive('progressBar', ['$timeout', function ($timeout) {
    return {
        restrict: 'E',
        scope: {
            state: '=',
            duration: '=',
            to: '='
        },
        replace: true,
        link: function (scope, element, attrs) {
            var progressBar = new ProgressBar(element[0]), progressState = 'waiting';

            scope.$watch('to', function (to) {
                if (progressState != 'waiting')
                    progressBar.inc(to, scope.duration * 1000);
            });

            scope.$watch('state', function (state) {
                progressState = state;
                switch (state) {
                    case 'checking':
                        progressBar.inc(scope.to, scope.duration * 1000);
                        break;
                    case 'waiting':
                    case 'progress':
                        break;
                    default:
                        progressBar.done(function () {
                            $timeout(function () {
                                scope.$emit('progressBar:done', true);
                            });
                        });
                        break;
                }
            });

            scope.$on('$destroy', function () {
                progressBar.stop();
                progressBar = null;
            });
        },
        template: '<div class="progress"><p class="progress-percents"></p><span class="progress-bar"></span></div>'
    };
}]);