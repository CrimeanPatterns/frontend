angular.module('AwardWalletMobile').directive('timelineTime', [function () {
    return {
        templateUrl: 'templates/directives/timeline/time.html',
        link: function () {
        }
    };
}]);

angular.module('AwardWalletMobile').directive('timelineChain', ['$templateCache', '$q', '$compile', function ($templateCache, $q, $compile) {
    return {
        restrict: 'E',
        scope: {
            type: '=',
            chain: '=ngModel'
        },
        link: function (scope, element, attrs) {
            var templateUrl = 'templates/directives/timeline/list-view/' + scope.type + '.html';
            var getTemplate = function (templateUrl) {
                var deferred = $q.defer();
                var template = $templateCache.get(templateUrl);
                if (template) {
                    deferred.resolve(template);
                } else {
                    deferred.reject();
                }
                return deferred.promise;
            };
            if (scope.chain.hasOwnProperty('duration')) {
                var duration = scope.chain.duration;
                scope.duration = new Date(0, 0, 0, duration.h, duration.i).getTime();
                scope.fromDate = new Date(0, 0, 0, 0, 0, 0);
            }
            getTemplate(templateUrl).then(function (html) {
                element.html(html);
                $compile(element.contents())(scope);
            });
        }
    }
}]);
angular.module('AwardWalletMobile').directive('timelineRow', ['$templateCache', '$q', '$compile', '$state', '$filter', 'DateTimeDiff', function ($templateCache, $q, $compile, $state, $filter, DateTimeDiff) {
    return {
        restrict: 'E',
        scope: {
            type: '=',
            row: '=ngModel',
            shared: '='
        },
        link: function (scope, element, attrs) {
            var templateUrl = 'templates/directives/timeline/list/' + (['date', 'planStart', 'planEnd'].indexOf(scope.type) > -1 ? scope.type : 'trip') + '.html';
            var getTemplate = function (templateUrl) {
                var deferred = $q.defer();
                var template = $templateCache.get(templateUrl);
                if (template) {
                    deferred.resolve(template);
                } else {
                    deferred.reject();
                }
                return deferred.promise;
            };
            if (scope.row.hasOwnProperty('startDate')) {
                var startDate = scope.row.startDate.ts * 1000, localDate;

                localDate = startDate;

                if (scope.row.startDate.hasOwnProperty('fmtParts')) {
                    var fmt = scope.row.startDate.fmtParts;

                    localDate = $filter('fmt')(fmt);
                }

                localDate = new Date(localDate);

                scope.timeAgo = DateTimeDiff.longFormatViaDates(new Date(), localDate);
                scope.disabled = new Date(startDate).getTime() <= Date.now();
            }
            if (scope.row.blocks && scope.row.blocks.length > 0) {
                var id = scope.row.id.split('.');
                if (!scope.shared) {
                    scope.href = $state.href('index.timeline.segment.details', {Segment: id[0], SubId: id[1]});
                } else {
                    scope.href = $state.href('shared.segment.details', {Segment: id[0], SubId: id[1]});
                }
            }
            getTemplate(templateUrl).then(function (html) {
                element.html(html);
                $compile(element.contents())(scope);
            });
        }
    }
}]);
angular.module('AwardWalletMobile').directive('timelineDetailField', ['$templateCache', '$q', '$compile', '$filter', '$sce', function ($templateCache, $q, $compile, $filter, $sce) {
    return {
        restrict: 'EA',
        scope: {
            type: '=',
            field: '=ngModel'
        },
        link: function (scope, element, attrs) {
            var getTemplate = function (templateUrl) {
                var deferred = $q.defer();
                var template = $templateCache.get(templateUrl);
                if (template) {
                    deferred.resolve(template);
                } else {
                    deferred.reject();
                }
                return deferred.promise;
            };

            var templateUrl = 'templates/directives/timeline/details/' + scope.type + '.html';

            scope.isChanged = function (val, old) {
                return val !== old;
            };

            if (scope.field.val && scope.field.val.hasOwnProperty('date')) {
                scope.date = new Date(scope.field.val.date.ts * 1000);

                if (scope.field.val.date.hasOwnProperty('fmtParts')) {
                    scope.localDate = new Date($filter('fmt')(scope.field.val.date.fmtParts));
                }
            }

            if (scope.field.old && scope.field.old.hasOwnProperty('date')) {
                scope.oldDate = new Date(scope.field.old.date.ts * 1000);

                if (scope.field.old.date.hasOwnProperty('fmtParts')) {
                    scope.localOldDate = new Date($filter('fmt')(scope.field.old.date.fmtParts));
                }
            }

            if (scope.date) {
                scope.diff = Math.abs(scope.date - new Date());
            }

            scope.getPrefix = function (row) {
                var text = [], regex = /[^0-9\s]+/g;

                if (row.hasOwnProperty('nights') || row.hasOwnProperty('days')) {
                    text = Translator.trans(/** @Desc("on %date% for %nights%") */ 'check-in', {
                        date: 1,
                        nights: 1
                    }).match(regex);
                } else {
                    text = Translator.trans(/** @Desc("on %date%") */ 'check-in.short', {
                        date: 1
                    }).match(regex);
                }
                return text;
            };

            scope.getCheckInText = function (row, prefix) {
                return prefix + ' <span class="red-block">' + row.nights + '</span>' + Translator.transChoice(/** @Desc("night|nights") */ 'nights', row.nights);
            };

            scope.getPickUpText = function (row, prefix) {
                return prefix + ' <span class="red-block">' + row.days + '</span>' + Translator.transChoice(/** @Desc("day|days") */ 'days', row.days);
            };

            scope.$watch(function () {
                return scope.field;
            }, function (field) {
                if (field) {
                    if (field.val && field.val.hasOwnProperty('date')) {
                        scope.date = new Date(field.val.date.ts * 1000);

                        if (field.val.date.hasOwnProperty('fmtParts')) {
                            scope.localDate = new Date($filter('fmt')(field.val.date.fmtParts));
                        }
                    }

                    if (field.old && field.old.hasOwnProperty('date')) {
                        scope.oldDate = new Date(field.old.date.ts * 1000);

                        if (field.old.date.hasOwnProperty('fmtParts')) {
                            scope.localOldDate = new Date($filter('fmt')(field.old.date.fmtParts));
                        }
                    }

                    if (scope.date) {
                        scope.diff = Math.abs(scope.date - new Date());
                    }
                }
            });

            getTemplate(templateUrl).then(function (html) {
                element.html(html);
                $compile(element.contents())(scope);
            });
        }
    }
}
]);