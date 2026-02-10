define([
	'angular'
], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	function ListExtender(di, extenders) {
		var services = {};

		angular.forEach(extenders, function (extenderServiceId) {
			var service = di.get(extenderServiceId);
			if (!service || !Object.prototype.hasOwnProperty.call(service, 'getPropertyName')) {
				throw new Error('Extender service ' + extenderServiceId + ' must have property');
			}
			services[extenderServiceId] = service;
		});
		var self = {
			getService: function(serviceId) {
				if (!Object.prototype.hasOwnProperty.call(services, serviceId)) {
					throw new Error('Required extender service ' + serviceId);
				}
				return services[serviceId];
			},
			/**
			 *
			 * @param {Object} data Строка данных для расширения
			 * @param {string} type Тип строки
			 * @returns {*}
			 */
			extend: function(data, type) {
				if (['account', 'subaccount', 'coupon'].indexOf(type) === -1) return;
				angular.forEach(services, function (service) {
					var action = 'extend' + type.charAt(0).toUpperCase() + type.slice(1),
						propertyName = '_' + service.getPropertyName();
					if (!Object.prototype.hasOwnProperty.call(data, propertyName)) {
						data[propertyName] = {};
					}
					if (Object.prototype.hasOwnProperty.call(service, action)) {
						angular.extend(data[propertyName], service[action](data.fields));
					} else if (Object.prototype.hasOwnProperty.call(service, 'extend')) {
						angular.extend(data[propertyName], service.extend(data.fields));
					}
				});
			}
		};
		return self;
	}


	var service = angular.module('listExtenderService', []);

	service.provider('ListExtender',
		function () {
			// Массив расширителей
			var extenders = [];

			return {
				setExtenders: function(e) {
					extenders = e;
				},
				$get: [
					'DI',
					/**
					 * @param di
					 */
					function (di) {
						return new ListExtender(di, extenders);
					}
				]
			};
		});

	service.factory('ListExtenderForSearch', [
		'DI',
		function (di) {
			var propertyName = 'search';

			return {
				getPropertyName: function() {return propertyName;},
				extendAccount: function (item) {
					return [
						item.DisplayName, item.LoginFieldFirst,
						(item.LoginFieldLast || '').substring(0, 50), item.AccountStatus
					];
				},
				extendSubaccount: function (item) {
					return []
				},
				extendCoupon: function (item) {
					return []
				}
			};
		}]);

	service.factory('ListExtenderDecorator', [
		'DI', '$sce',
		function (di, $sce) {
			var propertyName = 'decorate';

			return {
				getPropertyName: function() {return propertyName;},
				extend: function (/** AccountData */ item) {
					var ret = {};

					if (['inc', 'dec'].indexOf(item.StateBar) !== -1) {
						ret.barShow = true;
						ret.barClass = item.StateBar == "inc" ? 'icon-increased' : 'icon-decreased';
					} else {
						ret.barShow = false;
					}
					ret.accountFID = 'account_' + item.FID;
					if(item.ExpirationMode === 'info'){
                        ret.expirationStateClass = 'icon-info-small';
					}else if (item.ExpirationState === 'far') {
						ret.expirationStateClass = 'icon-green-check';
					} else if (item.ExpirationState === 'soon') {
						ret.expirationStateClass = 'icon-warning-small';
					} else if (item.ExpirationState === 'expired') {
						ret.expirationStateClass = 'icon-red-error';
					} else {
						ret.expirationStateClass = false;
					}

					if (item.ExpirationMode == 'calc') {
						ret.expirationModeClass = 'icon-small-green-expiration';
					} else if (item.ExpirationMode == 'pen') {
						ret.expirationModeClass = 'icon-small-green-edit-expiration';
					} else if (item.ExpirationMode == 'warn') {
						ret.expirationModeClass = 'icon-small-warning';
					} else {
						ret.expirationModeClass = false;
					}

					var user = di.get('agents').getAgent(item.AccountOwner);
					ret.userName = user ? user.name : '';

                    if(item.isDocument) return ret;

                    item.LoginFieldFirst = item.LoginFieldFirst || '';
					ret.LoginFirstLong = item.LoginFieldFirst.length > 20;
					ret.LoginLastLong = Object.prototype.hasOwnProperty.call(item, 'LoginFieldLast') && item.LoginFieldLast && item.LoginFieldLast.length > 20;

					var eliteLevels = '',
                        status;
					for (var i = 1; i <= item.EliteLevelsCount; i++) {
                        status = item.EliteStatuses[i - 1];
						if (i <= item.StatusIndicators) {
							eliteLevels += '<span data-tip title="'+status+'" class="blue"></span>';
						} else {
							eliteLevels += '<span data-tip title="'+status+'" class="silver"></span>';
						}
					}
					ret.eliteLevels =  $sce.trustAsHtml(eliteLevels);

					var goalLevels = '';
					for (i = 1; i <= 10; i++) {
						if (i <= item.GoalIndicators) {
							goalLevels += '<span class="blue"></span>';
						} else {
							goalLevels += '<span class="silver"></span>';
						}
					}
					ret.goalLevels = $sce.trustAsHtml(goalLevels);

					return ret;
				}
			};
		}]);
});
