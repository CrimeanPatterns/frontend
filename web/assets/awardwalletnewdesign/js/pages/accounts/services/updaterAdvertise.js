define(['angular'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('updaterAdvertiseService', []);

	/**
	 * @constructor
	 * @class UpdaterElement
	 */
	function UpdaterAdvertise($timeout, $http, di) {

		var rotateTimeout = 10 * 1000;

		var session = {
			working: false,
			ids: [],
			rotate: true,
			rotateTimer: null
		};

		var advertise = null;

		function rotate() {
			if (!session.working) return;
			if (!session.rotate) return;
			if (!session.ids.length) return;
			if (session.rotateTimer) return;

			$http.post(Routing.generate('aw_get_advertise'), {accounts: session.ids}).then(
				(res, status) => {
					const data = res.data;
					if (typeof(data) === 'object' && data.SocialAdID > 0) {
						advertise = data;
					} else {
						//advertise = null;
					}
				}
			).catch(function (data, status) {
				//advertise = null;
			});

			session.rotate = false;
			session.rotateTimer = $timeout(function () {
				session.rotateTimer = null;
				rotate();
			}, rotateTimeout);
		}

		/**
		 * @class UpdaterAdvertise
		 */
		var self = {
			start: function (ids) {
				advertise = null;
				session.working = true;
				session.ids = ids || [];
				session.rotate = true;
				session.rotateTimer = null;
				rotate();
			},
			tick: function (ids) {
				ids = ids || [];
				var newIds = [], oldIds = [];
				angular.forEach(ids, function (id) {
					if (session.ids.indexOf(id) == -1) {
						newIds.push(id);
					}
				});
				angular.forEach(session.ids, function (id) {
					if (ids.indexOf(id) == -1) {
						oldIds.push(id);
					}
				});
				if (oldIds.length || newIds.length) {
					session.ids = ids;
					session.rotate = true;
					rotate();
				}
			},
			stop: function () {
				advertise = null;
				session.working = false;
				session.ids = [];
				session.rotate = false;
				$timeout.cancel(session.rotateTimer);
			},
			getData: function () {
				if (!session.working) return null;
				return advertise;
			}
		};

		return self;
	}

	service.provider('UpdaterAdvertise',
		function () {
			return {
				$get: [
					'$timeout', '$http', 'DI',
					function ($timeout, $http, di) {
						return new UpdaterAdvertise($timeout, $http, di);
					}
				]
			};
		})
});

