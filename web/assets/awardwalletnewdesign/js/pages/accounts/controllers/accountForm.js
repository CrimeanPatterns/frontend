define([
	'angular-boot', 'jquery-boot',
	'lib/customizer',
	'lib/design',
	'browserext', /*'angular-hotkeys',*/ 'translator-boot',
	'filters/unsafe',
	'directives/customizer', 'directives/dialog',

	'pages/accounts/controllers/accountUpdater',
	'pages/accounts/services/accounts',
	'pages/accounts/services/user',
	'pages/accounts/services/kinds',
	'pages/accounts/services/agents',
	'pages/accounts/services/di',
	'pages/accounts/services/updater',
	'pages/accounts/services/updaterElements',
	'pages/accounts/services/updaterDecorator'
], function (angular, $, customizer) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var AccountData = {};
	var AccountID = null;

	var ListServices = {
		updater: 'Updater',
		'updater-elements': 'UpdaterElements',
		'updater-element-decorator': 'UpdaterElementDecorator',
		'updater-results': 'UpdaterResults',
		kinds: 'Kinds',
		agents: 'Agents',
		user: 'User',
		accounts: 'Accounts'
	};

	var app = angular.module("accountFormApp", [
		'appConfig', /*'cfp.hotkeys',*/ 'customizer-directive', 'dialog-directive', 'unsafe-mod',
		'diService',
		'accountsService',
		'agentsService',
		'kindsService',
		'userService',
		'updaterService',
		'updaterElementsService',
		'updaterDecoratorService',
		'accountUpdaterModule'
	]);

	app.config([
		'$injector', 'AccountsProvider',
		function ($injector, AccountsProvider) {
			if ($injector.has('AccountData')) {
				AccountData = $.extend(AccountData, $injector.get('AccountData'));
				AccountsProvider.setAccounts([AccountData]);
				AccountID = AccountData.FID;
			}
		}
	]);

	app.run([
		'$injector', 'DI',
		function ($injector, di) {
			var keys = Object.keys(ListServices);
			keys.reverse();
			angular.forEach(keys, function (serviceId) {
				var service = ListServices[serviceId];
				if ($injector.has(service)) {
					di.set(serviceId, $injector.get(service));
				} else if (service !== undefined) {
					di.set(serviceId, service);
				}
			});
			di.set('manager', $injector.get('ElementUpdater'));
		}
	]);

	app.controller('formUpdaterCtrl', [
		'$scope', '$timeout', 'DI',
		function ($scope, $timeout, di) {
			var element = this;

			/**
			 * @type {integer}
			 */
			element.id = AccountID;
			if (!element.id) return;

			/**
			 *
			 * @type {AccountData}
			 */
			element.account = di.get('accounts').getAccount(element.id);

			element.updating = di.get('updater-elements').bind(element.id, element.account);
			element.updating = di.get('updater-element-decorator').decorate(element.updating);

			element.state = di.get('manager').getState();

			var LastBalanceRaw = element.account.BalanceRaw || 0;
			element.LastBalance = LastBalanceRaw > 0 ? element.account.Balance : 0;

			var notices = [];
			if (1 == element.account.SavePassword && 'undefined' !== typeof element.account.PwnedTimes && element.account.PwnedTimes|0 > 0) {
				notices.push({
					'type'    : 'warning',
                    'message' : Translator.transChoice('checked-hacked-passwords', element.account.PwnedTimes, {'count_formatted' : (new Intl.NumberFormat(customizer.locales()).format(element.account.PwnedTimes)), 'bold_on' : '<b>', 'bold_off' : '</b>'})
				});
			}
			element.notices = notices;

			$scope.$watch(function () {
				return di.get('updater').getState();
			}, function (data) {
				if (data == 'fail') {
					$('.js-update-overlay').hide();
				} else if (data == 'done' && (element.updating.state == 'error' || element.updating.state == 'failed' || element.updating.state == 'disabled')) {
					$('.js-update-overlay').hide();
					if(element.updating.state == 'disabled'){
						$('#account_disabled').attr('checked', 'checked');
					}
				} else {
					$('.js-update-overlay').show();
				}
			});

			$scope.$on('accountUpdater.extensionRequired', function (event, data) {
                //document.location.href = '/extension/?BackTo=' + encodeURIComponent(Routing.generate('aw_account_edit', {accountId: element.account.ID, check: 1}));
                var params = new URLSearchParams();
                if (typeof data === 'object' && Object.prototype.hasOwnProperty.call(data, 'version') && data.version === 3) {
                    params.append('v3', true);
                }
                params.append('BackTo', Routing.generate('aw_account_edit', {accountId: element.account.ID, check: 1}))
                document.location.href = '/extension-install?' + params;
            });

			$timeout(function () {
				$("[data-ng-cloak2]").removeAttr('data-ng-cloak2');
				di.get('manager').start(element.id);
			}, 500);
		}])
	;

	/**
	 * @param di
	 * @lends ElementUpdater
	 */
	function ElementUpdater(di) {

		var shared = {
			updating: false,
			active: false
		};

		function toState(state) {
			// state = done|fail|stop
			if (shared.updating && shared.active) {
				shared.updating = false;
			}
		}

		function changedAccounts(accounts) {
			di.get('accounts').setAccounts(accounts, true);
		}

		/**
		 * @class ElementUpdater
		 */
		var self = {
			start: function (id) {
				if (!di.get('updater').isUpdating()) {
					shared.active = true;
					shared.updating = true;
					di.get('updater').startEdit([id], toState, changedAccounts);
				}
			},
			stop: function () {
				if (shared.updating && shared.active) {
					shared.updating = false;
					di.get('updater').stop();
				}
			},
			end: function () {
				if (!shared.updating && shared.active) {
					shared.updating = false;
					shared.active = false;
					di.get('accounts').setAccounts(di.get('updater').getAccounts(), true);
					di.get('updater').end();
				}
			},
			getState: function () {
				return shared;
			},
			elementDone: function (id) {
				di.get('updater-elements').bind(id).setDone();
				if (!shared.updating && shared.active && di.get('updater-elements').isAllDone()) {
					self.end();
				}
			}
		};

		return self;
	}

	app.provider('ElementUpdater',
		function () {
			return {
				$get: [
					'DI',
					function (di) {
						return new ElementUpdater(di);
					}
				]
			};
		})

});
