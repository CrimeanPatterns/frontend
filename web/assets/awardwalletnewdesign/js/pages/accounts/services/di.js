define([
	'angular'
], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	function DI() {
		var services = {};
		var self = {
			set: function(serviceId, service) {
				services[serviceId] = service;
			},
			has: function(serviceId) {
				return Object.prototype.hasOwnProperty.call(services, serviceId);
			},
			get: function(serviceId) {
				if (Object.prototype.hasOwnProperty.call(services, serviceId)) {
					return services[serviceId];
				} else {
					throw new Error('Required service ' + serviceId);
				}
			}
		};
		return self;
	}

	var service = angular.module('diService', []);

	service.provider('DI',
		function () {
			return {
				$get: [
					function () {
						return new DI();
					}
				]
			};
		})

});