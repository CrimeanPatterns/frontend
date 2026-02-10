angular.module('AwardWalletMobile').directive('bindTranslateHtml', [function () {
    return {
        link: function (scope, element, attr) {
            element.html(attr.bindTranslateHtml);
        }
    }
}]);

angular.module('AwardWalletMobile').directive('autocomplete', [function () {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            // var autocomplete = scope.$eval(attrs.autocomplete);
            // if (autocomplete) {
            //     element[0].setAttribute('autocomplete', autocomplete);
            // } else {
            //     element[0].removeAttribute('autocomplete');
            // }
        }
    };
}]);

angular.module('AwardWalletMobile').directive('globalError', ['$timeout', function ($timeout) {
    return {
        restrict: 'E',
        scope: {},
        template: '<div class="error-block push-message" ng-class="{hide:!message, show:message&&message.length>0}">' +
            '<div class="error-message">' +
            '<p>{{message}}</p>' +
            '</div>' +
            '</div>',
        link: function (scope) {
            var timeoutId = null;
            scope.$on('globalError:show', function (event, message) {
                $timeout.cancel(timeoutId);
                scope.message = message;
                timeoutId = $timeout(function () {
                    scope.message = null;
                    $timeout.cancel(timeoutId);
                }, 5000);
            });
            scope.$on('globalError:hide', function () {
                $timeout.cancel(timeoutId);
                scope.message = null;
            });
            scope.$on('$destroy', function () {
                scope.message = null;
                $timeout.cancel(timeoutId);
            });
        }
    }
}]);

angular.module('AwardWalletMobile').directive('staticContent', ['$anchorScroll', function ($anchorScroll) {
    return {
        scope: {content: '='},
        link: function (scope, element) {
            var unbind = scope.$watch(function () {
                return scope.content;
            }, function (content) {
                if (content) {
                    $(element[0]).html(content);
                    $anchorScroll();

                    element.on('click', function (event) {
                        const href = event.target.getAttribute('href');
                        if (href) {
                            if (!/^\#/.test(href)) {
                                window.open(event.target.getAttribute('href'), '_blank');
                                event.preventDefault();
                            } else {
                                $anchorScroll(href.substring(1));
                                return false;
                            }
                        }
                    });
                    unbind();
                }
            });
        }
    }
}]);

angular.module('AwardWalletMobile').directive('anchorScroll', ['$timeout', '$stateParams', function ($timeout, $stateParams) {
    return function (scope, element, attrs) {
        var hash = $stateParams['to'];
        if (hash) {
            $timeout(function () {
                var el = document.getElementById(hash);
                if (el) {
                    element[0].scrollTop = el.offsetTop - 100;
                }
            });
        }
    };
}]);

angular.module('AwardWalletMobile').directive('header', [function () {
    return {
        restrict: 'A',
        scope: {
            header: '='
        },
        link: function (scope, element, attrs) {
            var regex = /^(.+?)(\((?:[^\)]+)\))?$/,
                names = regex.exec(scope.header),
                html = '';

            if (names && names.length > 0) {
                html += names[1];
                if (names[2])
                    html += ' <span class="silver">' + names[2] + '</span>';
            }

            element.html(html);
        }
    };
}]);

angular.module('AwardWalletMobile').directive('translationMode', ['$localStorage', function ($localStorage) {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            var enabled = scope.$eval(attrs.translationMode);
            if (!enabled)
                return;
            $localStorage.setItem('DEBUG_TRANSLATION', true);
            $.fn.ignore = function (sel) {
                return this.clone().find(sel || ">*").remove().end();
            };
            var hasTranslations = function (text, target) {
                if (app && app.translations) {
                    return app.translations[text] ||
                        app.translations[$.trim(target.html())] ||
                        app.translations[$.trim(target.ignore('span').text())] ||
                        app.translations[$.trim(target.parent().html())] ||
                        null;
                } else {
                    return null;
                }
            };
            var CMD_KEY = false;
            $(document).off('click.translation');
            $(document).off('keyup.translation keydown.translation');
            $(document).undelegate('*', 'mouseover.translation mouseout.translation');

            $(document).on('keyup.translation keydown.translation', function (event) {
                if (event.keyCode == 91 || event.keyCode == 17 || event.ctrlKey) {
                    if (event.type == 'keyup') {
                        CMD_KEY = false;
                    } else {
                        CMD_KEY = true;
                    }
                }
            });
            $(document).delegate('*', 'mouseover.translation mouseout.translation', function (event) {
                var target = $(event.target), text;
                $(event.target).removeClass('has-trans');
                if (CMD_KEY && event.type == 'mouseover') {
                    if (target.is('input')) {
                        text = target.attr('placeholder');
                    } else if (target.is('select')) {
                        text = target.find('option:selected').text();
                    } else {
                        text = $.trim(target.text());
                    }
                    var trans = hasTranslations(text, target);
                    if (trans) {
                        event.stopPropagation();
                        $(event.target).addClass('has-trans');
                    }
                }
                target = null;
            });

            $(document).on('click.translation', function (event) {
                var target = $(event.target), text;
                if (CMD_KEY) {
                    event.stopPropagation();
                    if (target.is('input')) {
                        text = target.attr('placeholder');
                    } else if (target.is('select')) {
                        text = target.find('option:selected').text();
                    } else {
                        text = $.trim(target.text());
                    }
                    var trans = hasTranslations(text, target);
                    if (trans) {
                        var texts = [];
                        texts.push('domain: ' + trans.domain + '\n\r' +
                            'key: ' + trans.key + '\n\r' +
                            'message: ' + trans.value);
                        if (trans.parameters) {
                            for (var key in trans.parameters) {
                                if (trans.parameters.hasOwnProperty(key)) {
                                    var value = trans.parameters[key],
                                        transPlaceholder = app.translations[$.trim(value)];
                                    if (transPlaceholder) {
                                        texts.push(
                                            'domain: ' + transPlaceholder.domain + '\n\r' +
                                            'key: ' + transPlaceholder.key + '\n\r' +
                                            'message: ' + transPlaceholder.value + '\n\r' +
                                            (transPlaceholder.number ? 'number: ' + transPlaceholder.number : '')
                                        );
                                    }
                                }
                            }
                        }
                        alert(texts.join('\n\r'));
                        CMD_KEY = false;
                    }
                    return false;
                }
            });
        }
    };
}]);

angular.module('AwardWalletMobile').directive('iframeDirective', [function () {
    return {
        restrict: 'E',
        scope: {
            html: '='
        },
        template: '<iframe width="100%" height="100%" frameborder="0"></iframe>',
        link: function (scope, element) {
            if (element[0].children && element[0].children[0].contentDocument) {
                var contentDocument = element[0].children[0].contentDocument;
                contentDocument.open();
                contentDocument.write(scope.html);
                contentDocument.close();
            }
        }
    }
}]);
