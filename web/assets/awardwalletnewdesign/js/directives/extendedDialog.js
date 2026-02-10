define(['angular', 'jquery-boot', 'jqueryui'], function (angular, $) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('extendedDialog', [])
        .directive('dialogHeader', function () {
            return {
                restrict: 'AE',
                template: '<span class="ui-dialog-title" ng-transclude></span>',
                transclude: true,
                replace: true,
                link: function (scope, element) {
                    scope.$on('renderParts', function (event, el) {
                        el.find('.ui-dialog-titlebar .ui-dialog-title').replaceWith(element);
                    });
                }
            }
        })
        .directive('dialogFooter', function () {
            return {
                restrict: 'AE',
                template: '<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix"><div class="ui-dialog-buttonset" ng-transclude></div></div>',
                transclude: true,
                link: function (scope, element) {
                    scope.$on('renderParts', function (event, el) {
                        element.appendTo(el);
                    });

                    scope.dialogClose = function () {
                        scope.$emit('dialogClose');
                    }
                }
            }
        })
        .directive('extDialog', ['$timeout', '$window', function ($timeout, $window) {
            return {
                restrict: 'AE',
                template: '<div ng-transclude style="display: none"></div>',
                transclude: true,
                replace: true,
                scope: {
                    onclose: '&'
                },
                link: function (scope, element, attr) {
                    $timeout(function () {
                        var options = $.extend(attr, {
                            hide: 'fade',
                            show: 'fade',
                            appendTo: element.parent(),
                            autoOpen: true,
                            close: function () {
                                $($window).off('resize.dialog');
                                $timeout(function () {
                                    scope.$apply(function () {
                                        scope.onclose();
                                    })
                                }, 100);
                            },
                            create: function () {
                                scope.$broadcast('renderParts', element.parent());
                            },
                            open: function (event) {
                                $($window).off('resize.dialog').on('resize.dialog', function () {
                                    $(event.target).dialog("option", "position", {
                                        my: "center",
                                        at: "center",
                                        of: $window
                                    });
                                });

                                $('body').one('click','.ui-widget-overlay', function() {
                                    $('.ui-dialog').filter(function () {
                                        return $(this).css("display") === "block";
                                    }).find('.ui-dialog-content').dialog('close');
                                });
                            }
                        });
                        $(element).dialog(options);

                        scope.$on('dialogClose', function () {
                            $(element).dialog('close');
                        });
                    }, 1);
                }
            }
        }])
});