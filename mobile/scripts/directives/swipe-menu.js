angular.module('AwardWalletMobile').service('SideMenu', function () {
    var props = {
        enabled: false,
        visible: false
    };
    return {
        enabled: function () {
            return props.enabled;
        },
        getProps: function () {
            return props;
        },
        setProperty: function (prop, value) {
            return props[prop] = value;
        },
        getProperty: function (prop) {
            return props[prop];
        },
        toggle: function () {
            props.visible = !props.visible;
        },
        destroy: function () {
            props.enabled = false;
            props.visible = false;
        },
        properties: props
    };
});
angular.module('AwardWalletMobile').directive('swipeMenu', ['Refresher', 'SideMenu', 'PageContent', function (Refresher, SideMenu, PageContent) {
    return {
        restrict: 'EA',
        scope: {},
        link: function (scope, element, attrs, ctrl) {
            var TRANSITION_END = 'webkitTransitionEnd transitionend msTransitionEnd oTransitionEnd',
                BROWSER_TRANSFORMS = [
                    'webkitTransform',
                    'mozTransform',
                    'msTransform',
                    'oTransform',
                    'transform'
                ];

            var currentX = 0,
                startX = 0,
                MAX = 280,
                behindPage = element[0],
                $behindPage = angular.element(behindPage),
                abovePage = document.querySelector('page'),
                $abovePage = angular.element(abovePage);

            var settings = {
                cssProps: {
                    userSelect: true
                }
            };

            if (platform.ios || platform.android) {
                settings.inputClass = Hammer.TouchInput;
            }

            var hElement = new Hammer(abovePage, settings);

            var isInsideIgnoredElement = function (el) {
                do {
                    if (el.getAttribute && el.getAttribute('sliding-menu-ignore') === 'true') {
                        return true;
                    }
                    el = el.parentNode;
                } while (el);
                return false;
            };

            var direction = 1,
                DIRECTION_LEFT = 2,
                DIRECTION_RIGHT = 4;

            var handleEvent = function (ev) {
                if (
                    isInsideIgnoredElement(ev.target)
                ) {
                    hElement.stop();
                    return;
                }
                var deltaX = ev.deltaX;
                switch (ev.type) {
                    case 'panleft':
                    case 'panright':
                        if (!Refresher.enabled()) {
                            currentX = startX + deltaX;
                            direction = ev.direction;
                            if (currentX >= 0) {
                                translate(currentX);
                            }
                        }
                        break;
                    case 'panend':
                    case 'pancancel':
                        var delta = Math.abs(deltaX);
                        if ([DIRECTION_LEFT, DIRECTION_RIGHT].indexOf(direction) > -1) {
                            if (direction === DIRECTION_RIGHT && delta >= MAX * .3) {
                                if (startX === MAX) {
                                    close();
                                } else {
                                    open();
                                }
                            } else if (direction === DIRECTION_LEFT && delta >= MAX * .2) {
                                close();
                            } else {
                                revert();
                            }
                        } else {
                            revert();
                        }
                        break;
                }
                SideMenu.setProperty('visible', currentX > 0);
            };

            var onTransitionEnd = function () {
                $abovePage.removeClass('transition');
                $behindPage.removeClass('transition');
            };

            var isClosed = function () {
                return startX === 0;
            };

            var close = function () {
                startX = 0;
                if (currentX !== 0) {
                    $abovePage.addClass('transition');
                    $behindPage.addClass('transition');
                    translate(0);
                }

                SideMenu.setProperty('visible', false);
            };

            var open = function () {
                startX = MAX;
                if (currentX !== MAX) {
                    $abovePage.addClass('transition');
                    $behindPage.addClass('transition');
                    translate(MAX);
                }
            };

            var revert = function () {
                $abovePage.addClass('transition');
                $behindPage.addClass('transition');
                translate(startX);
            };

            var toggle = function () {
                if (startX === 0) {
                    open();
                } else {
                    close();
                }
            };

            var transformFn = function (x) {
                return 'translate3d(' + x + ',0,0)';
            };

            var translate = function (x) {
                var aboveTransform = transformFn(x + 'px');
                var behind = (x - MAX) / MAX * 10;
                if (startX === 0 && x < 0) return;
                if (behind > 0) {
                    behind = 0;
                }
                var behindTransform = transformFn(behind + '%');

                var property;
                for (var i = 0; i < BROWSER_TRANSFORMS.length; i++) {
                    property = BROWSER_TRANSFORMS[i];
                    abovePage.style[property] = aboveTransform;
                    behindPage.style[property] = behindTransform;
                }
                currentX = x;
            };

            hElement.get('pan').set({direction: Hammer.DIRECTION_HORIZONTAL});
            hElement.on('pan panleft panright panend pancancel', handleEvent);

            $abovePage.bind(TRANSITION_END, onTransitionEnd.bind(this));

            scope.$on('swipemenu:toggle', toggle);

            scope.$on('$destroy', function () {
                hElement.off('pan panleft panright panend', handleEvent);
                SideMenu.destroy();
            });

            scope.$on('$stateChangeStart', function () {
                close();
            });
        }
    }
}]);

angular.module('AwardWalletMobile').directive('swipeMenuToggle', ['$rootScope', function ($rootScope) {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            function toggle() {
                $rootScope.$broadcast('swipemenu:toggle');
            }

            element[0].addEventListener('click', toggle);
            scope.$on('$destroy', function () {
                element[0].removeEventListener('click', toggle);
            });
        }
    }
}]);