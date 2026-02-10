define(['angular', 'routing'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('loaderService', []);

	function Queue($http, $q, $timeout, route) {
		var queue = [],
			id = 1,
			errorCount = 0,
			loading = false;

		function load() {
			if (!loading) {
				var req = {}, go = false;
				angular.forEach(queue, function (q) {
					if (!q.resolved) {
						q.attempt++;
						if (q.attempt > 5) {
							q.defer.reject('fail');
						} else {
							req[q.id] = q.request;
							q.defer.notify('attempt');
							go = true;
						}
					}
				});
				var cacheTS = new Date().getTime() / 1000 - 60 * 1; // 1 minute cache
				queue = queue.filter(function (q) {return !q.resolved || q.resolvedTS > cacheTS;});
				if (go) {
					loading = true;
					// todo fail!
					$http.post(route, req).then(
						function (response) {
							angular.forEach(response.data, function (d) {
								if (Object.prototype.hasOwnProperty.call(d, 'id')) {
									angular.forEach(queue, function (q) {
										if (!q.resolved && q.id == d.id) {
											q.resolved = true;
											q.resolvedTS =  new Date().getTime() / 1000;
											if (Object.prototype.hasOwnProperty.call(d, 'result')) {
												d.dataset = q.request.dataset;
												q.defer.resolve(d);
											} else {
												q.defer.reject(d);
											}
										}
									});
								}
							});
							errorCount = 0;
							loading = false;
							$timeout(function () {load();}, 0);
						}, function (reason) {
							errorCount ++;
							if (errorCount > 3) {
								angular.forEach(queue, function (q) {
									if (!q.resolved) {
										q.resolved = true;
										q.defer.reject(reason);
									}
								});
							} else {
								errorCount = 0;
								loading = false;
								$timeout(function () {load();}, 0);
							}
						}
					)
				}
			}
		}

		var self = {
			push: function (request, go) {
				go = go || false;
				var hash = angular.toJson(request), ret;
				angular.forEach(queue, function (q) {
					if (q.hash == hash) {
						ret = q.defer.promise;
					}
				});
				if (ret) return ret;

				var defer = $q.defer();
				queue.push({
					id: id++,
					request: request,
					hash: hash,
					attempt: 0,
					resolved: false,
					resolvedTS: 0,
					defer: defer
				});
				if (go) {
					$timeout(function () {load();}, 0);
				}
				return defer.promise;
			},
			go: function () {
				$timeout(function () {load();}, 0);
			},
			clear: function () {
				queue = queue.filter(function (q) {return !q.resolved;});
			}
		};
		return self;
	}

	function Loader($q, di, queue, resolvers, preloadedData) {
		var services = {};

		// Инициализация резолверов
		angular.forEach(resolvers, function (resolverServiceId) {
			var service = di.get(resolverServiceId);
			if (!service || !Object.prototype.hasOwnProperty.call(service, 'setFromLoader')) {
				throw new Error('Loader resolver ' + resolverServiceId + ' must have setter');
			}
			services[resolverServiceId] = {
				resolver: service,
				required: Object.prototype.hasOwnProperty.call(service, 'requiredFromLoader') ? service.requiredFromLoader() : [],
				sessionPersistent: Object.prototype.hasOwnProperty.call(service, 'isPersistent') ? service.isPersistent('session') : false,
				lifetimePersistent: Object.prototype.hasOwnProperty.call(service, 'isPersistent') ? service.isPersistent('lifetime') : false,
				data: null,
				options: null
			};
		});

		// данные, загруженные на странице
		angular.forEach(preloadedData, function (data, serviceId) {
			if (Object.prototype.hasOwnProperty.call(services, serviceId)) {
				var service = services[serviceId];
				service.data = data;
				service.resolver.setFromLoader(service.data);
				if (service.lifetimePersistent) {
					localStorage.setItem('loader.' + serviceId, service.data);
				}
			}
		});

		// кешированные данные
		angular.forEach(services, function (service, serviceId) {
			if (service.lifetimePersistent && service.data == null) {
				var data = localStorage.getItem('loader.' + serviceId);
				if (data) {
					service.data = data;
					service.resolver.setFromLoader(service.data);
				}
			}
		});


		/**
		 * @class accountProvider
		 */
		var self = {
			setOptions: function (dataset, options) {
				options = options || {};
				services[dataset].options = options;
			},
			resetOptions: function (dataset) {
				services[dataset].options = {};
			},
			load: function (datasets) {
				var loader = $q.defer();
				var q = [];
				angular.forEach(datasets, function (serviceId) {
					if (Object.prototype.hasOwnProperty.call(services, serviceId)) {
						var service = services[serviceId];
						q.push(queue.push({
							dataset: serviceId,
							options: service.options
						}));
						angular.forEach(service.required, function (sId) {
							if (services[sId].data == null) {
								q.push(queue.push({
									dataset: sId,
									options: services[sId].options
								}));
							}
						})
					}
				});
				queue.go();
				$q.all(q).then(function (data) {
					angular.forEach(data, function (d) {
						var serviceId = d.dataset;
						services[serviceId].data = d.result;
						services[serviceId].resolver.setFromLoader(d.result);
						if (serviceId == 'accounts') {
							di.get('counters').setTotals({viewTotal: parseInt(d.total)});
						}
					});
					loader.resolve();
				}, function (reason) {
					loader.reject(reason);
				});
				return loader.promise;
			},
			get: function (dataset) {
				var getter = $q.defer();
				var service = services[dataset];
				if (service.data == null) {
					self.load([dataset]).then(function () {
						getter.resolve(service.data);
					}, function (reason) {
						getter.reject(reason)
					})
				} else {
					var q = [];
					angular.forEach(service.required, function (sId) {
						if (services[sId].data == null) {
							q.push(sId);
						}
					});
					if (q) {
						self.load(q).then(function () {
							getter.resolve(services[dataset].data);
						}, function (reason) {
							getter.reject(reason)
						})
					} else {
						getter.resolve(services[dataset].data);
					}
				}
				return getter.promise;
			},
			clear: function () {
				queue.clear();
			}
		};

		return self;
	}

	service.provider('Loader',
		function () {
			var preloadData = {},
				partial = false,
				business = false,
				resolvers = [];

			return {
				setData: function(data) {
					preloadData = data;
				},
				setPartialMode: function(mode) {
					partial = mode;
				},
				setBusinessMode: function(mode) {
					business = mode;
				},
				setResolvers: function(r) {
					resolvers = r;
				},
				$get: [
					'$http', '$q', '$timeout', 'DI',
					function ($http, $q, $timeout, di) {
						var route;
						if (business) {
							route = Routing.generate('aw_business_account_data');
						} else {
							route = Routing.generate('aw_account_data');
						}
						var q = new Queue($http, $q, $timeout, route);
						return Loader($q, di, q, resolvers, preloadData);
					}
				]
			};
		})
});

