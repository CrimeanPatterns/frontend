define(['angular-boot', 'lib/customizer', 'dateTimeDiff', 'jqueryui'], function (angular, customizer, dateTimeDiff) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('customizer-directive', [])
        .directive('tip', function () {
            return {
                restrict: 'A',
                priority: -1000,
                scope: {
                    title: "@"
                },
                link: function (scope, element, attr) {
                    if (element.attr('title')) {
                        $(element).off('mouseenter').on('mouseenter', function () {
                            element.attr('data-role', 'tooltip');
                            customizer.initTooltips(element);

                            if (element.data('tip') === 'fixed') {
                                element.tooltip('open').off('mouseenter mouseleave');
                            } else {
                                element.tooltip('open').off('mouseleave').on('mouseleave', function() {
                                    if (element.data('ui-tooltip'))
                                        element.tooltip('close').tooltip('destroy');
                                });
                            }
                        });
                    }
                }
            }
        })
        .directive('date', function () {
            return {
                restrict: 'A',
                priority: -1000,
                link: function (scope, element, attr) {
                    if (!Object.prototype.hasOwnProperty.call(attr, 'calc')) return;

                    const dateFormat = new Intl.DateTimeFormat(
                        customizer.locales(),
                        {
                            weekday: 'long',
                            month: 'long',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: 'numeric'
                        }
                    );

                    return scope.$watch(attr.date, function(val) {
                        let date = new Date(1000 * val);
                        element.text(dateTimeDiff.longFormatViaDateTimes(new Date(), date));
                        element.prop('title', dateFormat.format(date));
                    });
                }
            };
        })
        .directive('datepicker', function () {
            return {
                restrict: 'A',
                priority: -1000,
                link: function (scope, element, attr) {
                    element.attr('data-role', 'datepicker');
                    customizer.initDatepickers(element);
                }
            }
        })
        .directive('menu', function () {
            return {
                restrict: 'A',
                priority: -1000,
                link: function (scope, element, attr) {
                    element.attr('data-role', 'dropdown');
                    customizer.initDropdowns(element);
                }
            }
        })
        .directive('choice2', ['$timeout', function ($timeout) {
            return {
                restrict: 'A',
                priority: -1000,
                link: function (scope, element, attr) {
                    $timeout(function () {
                        element.attr('data-role', 'select2');
                        customizer.initSelects2(element);
                    }, 0);
                }
            }
        }]);
});
