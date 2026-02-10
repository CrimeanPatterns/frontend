define(['angular'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	angular.module('appConfig', [])
		.config(['$interpolateProvider', '$httpProvider', '$provide', function ($interpolateProvider, $httpProvider, $provide) {
			$interpolateProvider.startSymbol('[[');
			$interpolateProvider.endSymbol(']]');

            $httpProvider.defaults.xsrfCookieName = 'none';
            $httpProvider.defaults.xsrfHeaderName = 'none';
			$httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

			$httpProvider.interceptors.push(['$q', '$injector', function ($q, $injector) {
				return {
					'request': function(config) {
						config.headers = config.headers || {};
                        if (window.csrf_token === null) {
							window.csrf_token = document.head.querySelector('meta[name="csrf-token"]').content;
                        }
						if (window.csrf_token) {
							config.headers['X-XSRF-TOKEN'] = window.csrf_token;
						}

					    return config;
					},

                    'response': function(response) {
                        if (response.headers('X-XSRF-TOKEN')) {
							window.csrf_token = response.headers('X-XSRF-TOKEN');
                        }

                        return response;
                    },

					'responseError': function (error, $http) {
						const canCsrfRetry = typeof(error.csrfRetry) === 'undefined';
						const csrfCode = error.headers('X-XSRF-TOKEN');
						const csrfFailed = error.headers("X-XSRF-FAILED") === "true";

						if (csrfCode) {
							window.csrf_token = csrfCode;
						}

						if (error.status === 403 && csrfFailed && canCsrfRetry) {
							error.config.csrfRetry = true;
							console.log('retrying with fresh CSRF, should receive on in cookies');
							return $injector.get('$http')(error.config);
						}

						require(['lib/errorDialog'], function (showErrorDialog) {
							showErrorDialog(error, (typeof(error.config.disableErrorDialog) != 'undefined' && error.config.disableErrorDialog));
						});
						return $q.reject(error);
					}
				}
			}]);

			$provide.decorator("$exceptionHandler", ['$delegate', '$injector', function ($delegate, $injector) {
				return function (exception, cause) {
					if (typeof (window.onerrorHandler) != 'undefined') {
						window.onerrorHandler(exception);
					}
					$delegate(exception, cause);
				};
			}]);

		}
	])
	.directive('ngRepeat', ['$timeout', function ($timeout) {
		var emit = false;
		return {
			priority: 1000,
			link: function (scope, element, attr) {
				if (scope.$last !== true) return;

				$timeout(function () {
					if (emit) return;
					if (!emit)
						emit = true;
					scope.$emit('viewContentFullyLoaded');
				});
			}
		};
	}])
	.directive('showElement', [function () {
		return {
			restrict: 'A',
			multiElement: true,
			link: function (scope, element, attr) {
				scope.$watch(attr.showElement, function (value) {
					if (value) {
						element.show().removeClass('ng-hide');
					} else {
						element.hide().addClass('ng-hide');
					}
					if (attr.afterShowCallback != null && element.is(':visible'))
						scope.$eval(attr.afterShowCallback);
				});
			}
		};
	}])
	.directive('bindHtmlCompile', ['$compile', '$parse', function ($compile, $parse) {
		return {
			restrict: 'A',
			link: function (scope, element, attrs) {
				var bindHtmlWatch = $parse(attrs.bindHtmlCompile, function (value) {
					return (value || '').toString();
				});
				scope.$watch(bindHtmlWatch, function (value) {
					element.html(value);
					$compile(element.contents())(scope);
				});
			}
		};
	}]);
});