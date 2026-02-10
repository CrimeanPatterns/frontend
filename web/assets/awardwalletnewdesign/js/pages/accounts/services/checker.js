define(['angular'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('checkerService', []);

	function Check(id, checker) {
		return {
			checked: false,
			check: function() {
				checker.check(id);
			},
			uncheck: function() {
				checker.uncheck(id);
			},
			toggle: function() {
				checker.toggle(id);
			}
		}
	}

	function Checker(persistent) {
		var checks = {};

		var self = {
			reset: function () {
				checks = {};
			},
			bind: function (id) {
				if (!Object.prototype.hasOwnProperty.call(checks, id)) {
					checks[id] = new Check(id, self);
				}
				return checks[id];
			},
			isChecked: function (id) {
				return self.bind(id).checked;
			},
			check: function (ids) {
				if (angular.isArray(ids)) {
					angular.forEach(ids, function (id) {
						self.bind(id).checked = true;
					})
				} else {
					self.bind(ids).checked = true;
				}
			},
			uncheck: function (ids) {
				if (angular.isArray(ids)) {
					angular.forEach(ids, function (id) {
						self.bind(id).checked = false;
					})
				} else {
					self.bind(ids).checked = false;
				}
			},
			toggle: function (ids) {
				if (angular.isArray(ids)) {
					angular.forEach(ids, function (id) {
						self.bind(id).checked = !self.bind(id).checked;
					})
				} else {
					self.bind(ids).checked = !self.bind(ids).checked;
				}
			},
			checkAll: function() {
				angular.forEach(checks, function (check, id) {
					check.checked = true;
				});
			},
			uncheckAll: function() {
				angular.forEach(checks, function (check, id) {
					check.checked = false;
				});
			},
			getChecked: function () {
				var ret = [];
				angular.forEach(checks, function (check, id) {
					if (check.checked) {
						ret.push(id);
					}
				});
				return ret;
			}
		};

		return self;
	}

	service.provider('Checker',
		function () {
			var persistent = true;

			return {
				setPersistent: function(data) {
					persistent = data;
				},
				$get: [
					/**
					 * @lends Checker
					 */
					function () {
						return new Checker(persistent);
					}
				]
			};
		})
});

