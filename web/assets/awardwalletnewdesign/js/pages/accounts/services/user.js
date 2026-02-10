define(['angular', 'routing'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('userService', []);

	function User() {
		var userId = null;
		var awPlus = false;
		var disabledExtension = false;

		/**
		 *
		 * @class User
		 */
		var self = {
			setAwPlus: function (plus) {
				awPlus = !!plus;
			},
			getAwPlus: function () {
				return awPlus;
			},
			isAwPlus: function () {
				return self.getAwPlus();
			},
			setDisabledExtension: function (disabled) {
				disabledExtension = !!disabled;
			},
			getDisabledExtension: function () {
				return disabledExtension;
			},
			isDisabledExtension: function () {
				return self.getDisabledExtension();
			},
			getUserId:function() {
				return userId;
			},
			/**
			 * @param {UserData} data
			 */
			setFromLoader: function (data) {
				if (Object.prototype.hasOwnProperty.call(data, 'awPlus'))
					self.setAwPlus(data.awPlus);
				if (Object.prototype.hasOwnProperty.call(data, 'disabledExtension'))
					self.setDisabledExtension(data.disabledExtension);
				if (Object.prototype.hasOwnProperty.call(data, 'ID'))
					userId = data.ID;
			}
		};

		return self;
	}

	service.provider('User',
		function () {
			var awPlus;

			return {
				setAwPlus: function(data) {
					awPlus = data;
				},
				$get: [
					/**
					 * @lends User
					 */
					function () {
						var ret = new User();
						if (awPlus) ret.setAwPlus(awPlus);
						return ret;
					}
				]
			};
		})
});

/**
 * @typedef {Object} UserData
 * @property {(number|string)} ID
 * @property {string} name
 * @property {boolean} awPlus
 * @property {boolean} disabledExtension
 */
