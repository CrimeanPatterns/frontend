/*
 * @license
 * angular-modal v0.2.0
 * (c) 2013 Brian Ford http://briantford.com
 * License: MIT
 */

angular.module('btford.modal', []).
    factory('btfModal', function ($compile, $rootScope, $controller, $q, $http, $templateCache) {
        return function modalFactory(config) {

            if ((+!!config.template) + (+!!config.templateUrl) !== 1) {
                throw new Error('Expected modal to have exacly one of either `template` or `templateUrl`');
            }

            var template = config.template,
                controller = config.controller || angular.noop,
                controllerAs = config.controllerAs,
                container = angular.element(config.container || document.body),
                element = null,
                html, scope, prefix='ngmodal_', uid = config.uid;

            var deferred = $q.defer();
            if (config.template) {
                deferred.resolve(config.template);
                html = deferred.promise;
            } else {
                var templateCache = $templateCache.get(config.templateUrl); //get template from Cache
                if (templateCache) {
                    deferred.resolve(templateCache);
                    html = deferred.promise;
                } else {
                    html = $http.get(config.templateUrl, {
                        cache: $templateCache
                    }).
                        then(function (response) {
                            return response.data;
                        });
                }
            }

            function activate(locals) {
                html.then(function (html) {
                    if (document.getElementById(prefix+uid) == null) {
                        attach(html, locals);
                    }
                });
            }

            function attach(html, locals) {
                element = angular.element('<div class="ng-modal" id="'+prefix+uid+'"></div>');
                element = element.html(html);
                document.body.classList.add('overflow');
                angular.element(document.body).append(element);
                scope = $rootScope.$new();
                if (locals) {
                    for (var prop in locals) {
                        scope[prop] = locals[prop];
                    }
                }
                var ctrl = $controller(controller, { $scope: scope });
                if (controllerAs) {
                    scope[controllerAs] = ctrl;
                }
                $compile(element)(scope);
            }

            function deactivate() {
                if (element) {
                    element.remove();
                    document.body.classList.remove('overflow');
                    scope.$destroy(); //add destroy method
                    element = null;
                }
            }

            function active() {
                return !!element;
            }

            return {
                open: activate,
                close: deactivate,
                active: active
            };
        };
    });
