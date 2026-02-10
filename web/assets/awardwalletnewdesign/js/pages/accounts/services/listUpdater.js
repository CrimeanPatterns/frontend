define(['angular'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('listUpdaterService', []);

	/**
	 * Состояния: start -> done|fail|stop -> end
	 *
	 * @param di
	 * @lends ListUpdater
	 */
	function ListUpdater(di) {

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
			di.get('manager').updateAccounts(accounts);
		}

		/**
		 * @class ListUpdater
		 */
		var self = {
			start: function (ids) {
				if (di.get('updater').isDone()) {
					shared.active = true;
					shared.updating = true;
					di.get('manager').setUpdate(ids);
					di.get('manager').build();
					di.get('updater').startGroupOrTrips(ids, toState, changedAccounts);
					di.get('updater-advertise').start(ids);
				}
			},
			stop: function () {
				if (shared.updating && shared.active) {
					shared.updating = false;
					di.get('updater').stop();
					di.get('updater-advertise').stop();
				}
			},
			end: function () {
				if (!shared.updating && shared.active) {
					shared.updating = false;
					shared.active = false;
					di.get('checker').uncheck(di.get('updater-elements').getUnchecking());
					di.get('manager').updateAccounts(di.get('updater').getAccounts(), true);
					di.get('manager').setUpdate(false);
					di.get('manager').build();
					di.get('updater').end();
				}
			},
			getState: function () {
				return shared;
			},
			elementDone: function (id) {
				if (shared.active) {
					di.get('updater-elements').bind(id).setDone();
					if (!shared.updating && di.get('updater-elements').isAllDone()) {
						self.end();
					}
				}
			}
		};

		return self;
	}

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
			di.get('manager').updateAccounts(accounts);
		}

		/**
		 * @class ElementUpdater
		 */
		var self = {
			start: function (id) {
				if (di.get('updater').isDone()) {
					shared.active = true;
					shared.updating = true;
					di.get('updater').startOne([id], toState, changedAccounts);
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
					di.get('manager').updateAccounts(di.get('updater').getAccounts(), true);
					di.get('updater').end();
				}
			},
			getState: function () {
				return shared;
			},
			elementDone: function (id) {
				if (shared.active) {
					di.get('updater-elements').bind(id).setDone();
					if (!shared.updating && di.get('updater-elements').isAllDone()) {
						self.end();
					}
				}
			}
		};

		return self;
	}

	service.provider('ListUpdater',
		function () {
			//var trips = false;

			return {
				//setTrips: function(data) {
				//	trips = data;
				//},
				$get: [
					'DI',
					function (di) {
						return new ListUpdater(di);
					}
				]
			};
		});

	service.provider('ElementUpdater',
		function () {
			//var trips = false;

			return {
				//setTrips: function(data) {
				//	trips = data;
				//},
				$get: [
					'DI',
					function (di) {
						return new ElementUpdater(di);
					}
				]
			};
		})
});

