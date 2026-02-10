angular.module('AwardWalletMobile').service('Refresher', function () {
    var properties = {
        enabled: false,
        canMove: false,
        distance: 0,
        loading: false,
        offsetTop: 0,
        needRefresh: false
    };
    return {
        properties: properties,
        setProperties: function () {
            if (arguments[0] && typeof arguments[0] == 'object')
                angular.extend(properties, arguments[0]);
        },
        enabled: function () {
            return properties.enabled;
        },
        getProperties: function () {
            return properties;
        },
        setProperty: function (property, value) {
            if (property && properties.hasOwnProperty(property)) {
                properties[property] = value;
            }
        },
        destroy: function () {
            properties.enabled = false;
            properties.canMove = false;
            properties.distance = 0;
            properties.loading = false;
            properties.offsetTop = 0;
            properties.needRefresh = false;
        }
    };
});
angular.module('AwardWalletMobile').directive('pullToRefresh', ['Refresher', 'SideMenu', 'PageContent', 'SessionService', function (Refresher, SideMenu, PageContent, SessionService) {
    return {
        restrict: 'EA',
        require: '^pageContent',
        templateUrl: 'templates/directives/pull-to-refresh.html',
        scope: {
            distance: '=?',
            onRefresh: '&?'
        },
        link: function (scope, $element, $attrs, pageContent) {
            scope.time = parseInt(SessionService.getProperty('timestamp')) * 1000;
            var content = $element.parent(),
                scrollElement = content.parent()[0],
                $content = $(content[0]),
                parent = $element.children(),
                pan = Refresher.getProperties(),
                timeoutId;

            var options = {
                distance: 80,
                onRefresh: null
            };

            angular.extend(options, scope);

            var _touchStart = function (e) {
                var transform, translate3d;
                if (scrollElement.scrollTop === 0 && !SideMenu.getProperty('visible')) {
                    pan.canMove = true;
                    transform = $content.css('-webkit-transform');
                    if (transform && transform !== 'none') {
                        translate3d = transform.match(/matrix(?:(3d)\(-{0,1}\d+(?:, -{0,1}\d+)*(?:, (-{0,1}\d+))(?:, (-{0,1}\d+))(?:, (-{0,1}\d+)), -{0,1}\d+\)|\(-{0,1}\d+(?:, -{0,1}\d+)*(?:, (-{0,1}\d+))(?:, (-{0,1}\d+))\))/);
                        if (translate3d && translate3d.length) {
                            pan.offsetTop = parseInt(translate3d[translate3d.length - 1]);
                        }
                    }
                }
            };

            var _touchDown = function (e) {
                if (!pan.canMove) {
                    return;
                }
                e.preventDefault();
                pan.enabled = true;
                pan.distance = e.deltaY / 2;
                if (!pan.loading)
                    _doRelease(pan.distance > options.distance);
                _setContentPan();
            };

            var _touchUp = function (e) {
                if (!pan.canMove || pan.distance === 0) {
                    return;
                }
                e.preventDefault();
                pan.enabled = true;
                pan.distance = e.deltaY / 2;
                if (!pan.loading)
                    _doRelease(pan.distance > options.distance);
                _setContentPan();
            };

            var _touchEnd = function (e) {
                if (!pan.enabled) {
                    pan.canMove = false;
                    return;
                }

                if (pan.loading) {
                    _doLoading();
                } else {
                    if (pan.distance > options.distance) {
                        _doLoading();
                    } else {
                        _doReset();
                    }

                }

                pan.distance = 0;
                pan.enabled = false;
                pan.canMove = false;
            };

            var _setContentPan = function () {
                $content.css({
                    '-webkit-transform': 'translate3d(0, ' + (pan.offsetTop + pan.distance) + 'px, 0)'
                });
            };

            var _doReset = function () {
                pan.offsetTop = 0;
                pan.loading = false;
                parent[0].classList.remove('active');
                parent[0].classList.remove('complete');

                $content.css({
                    '-webkit-transform': 'translate3d(0px, 0px, 0px)',
                    '-webkit-transition': '-webkit-transform 300ms ease'
                });

                function transitionEnd() {
                    $content.css({
                        '-webkit-transform': '',
                        '-webkit-transition': ''
                    });
                    $content.off('transitionend.reset', transitionEnd);
                }

                $content.on('transitionend.reset', transitionEnd);
            };

            var _doLoading = function () {
                $content.css({
                    '-webkit-transform': 'translate3d(0px, 50px, 0px)',
                    '-webkit-transition': '-webkit-transform 300ms ease'
                });

                function transitionEnd() {
                    $content.css({
                        '-webkit-transition': ''
                    });
                    $content.off('transitionend.loading', transitionEnd);
                }

                $content.on('transitionend.loading', transitionEnd);

                parent[0].classList.remove('touch');
                parent[0].classList.add('active');

                if (!pan.loading) {
                    if (!options.onRefresh) {
                        return _doReset();
                    }

                    // The loading function should return a promise
                    var loadingPromise = options.onRefresh();

                    loadingPromise.then(function () {
                        _doComplete();
                        timeoutId = setTimeout(_doReset, 5000);
                    });
                    pan.loading = true;
                }
            };

            var _doRelease = function (release) {
                if (release) {
                    parent[0].classList.add('touch');
                } else {
                    parent[0].classList.remove('touch');
                }
            };

            var _doComplete = function (release) {
                parent[0].classList.add('complete');
            };

            var settings = {
                touchAction: 'auto',
                cssProps: {
                    userSelect: true
                }
            };

            if (platform.android) {
                settings.inputClass = Hammer.TouchInput;
            }

            var h = new Hammer(content[0], settings);

            h.get('pan').set({direction: Hammer.DIRECTION_VERTICAL});

            h.on('panstart', _touchStart);
            h.on('panup', _touchUp);
            h.on('pandown', _touchDown);
            h.on('panend', _touchEnd);

            pageContent.props.$hasRefresher = true;

            scope.$watch(function () {
                return SessionService.properties.timestamp;
            }, function (ts, old) {
                if (ts != old) {
                    var time = new Date(ts * 1000), now = new Date();
                    if (time > now) {
                        time = now;
                    }
                    scope.time = time;
                }
            });

            scope.$watch(function () {
                return Refresher.properties.needRefresh != false;
            }, function (needRefresh) {
                if (needRefresh) {
                    _doLoading();
                    Refresher.setProperty('needRefresh', false);
                }
            });

            scope.$on('$destroy', function () {
                content.unbind('touchmove');
                content.unbind('touchstart');
                content.unbind('touchend');
                Refresher.destroy();
            });

            scope.$on('$stateChangeStart', function () {
                $content.css({
                    '-webkit-transition': '',
                    '-webkit-transofrm': ''
                });
                Refresher.destroy();
                clearTimeout(timeoutId);
            });
        }
    }
}]);