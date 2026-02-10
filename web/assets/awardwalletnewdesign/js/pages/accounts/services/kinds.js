define(['angular', 'routing'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('kindsService', []);

	function Kinds($http, $q, $filter) {
		var kinds = {};

		var self = {
			/**
			 *
			 * @param {KindsData} data
			 */
			setKinds: function (data) {
				kinds = data;
			},
			/**
			 *
			 * @returns {KindsData}
			 */
			getKinds: function () {
				return kinds;
			},
			/**
			 *
			 * @returns {KindsData}
			 */
			getOrderedKinds: function () {
				var ret = [];
				angular.forEach(self.getKinds(), function (kind) {
					ret.push(kind);
				});
				return $filter('orderBy')(ret, 'order');
			},
			/**
			 *
			 * @param id
			 * @param {KindData} data
			 */
			setKind: function (id, data) {
				if (!self.getKind(id)) {
					kinds[id] = data;
				} else {
					angular.extend(kinds[id], data);
				}
			},
			/**
			 *
			 * @param id
			 * @returns {KindData}
			 */
			getKind: function (id) {
				if (Object.prototype.hasOwnProperty.call(kinds, id)) {
					return kinds[id];
				} else {
					return null;
				}
			},

			getItems: function(id, number) {
				number = number || 0;
				if (self.getKind(id)) {
					return /** @Ignore */ Translator.transChoice(self.getKind(id).items, number);
				} else {
					return '';
				}
			},

			/**
			 * @param {KindsData} data
			 */
			setFromLoader: function (data) {
				angular.forEach(data, function (d) {
					self.setKind(d.ID, d);
				});
			}
		};

		return self;
	}

	service.provider('Kinds',
		function () {
			var kinds;

			return {
				setKinds: function(data) {
					kinds = data;
				},
				$get: ['$http', '$q', '$filter',
					/**
					 * @lends Kinds
					 * @param $http
					 * @param $q
					 * @param $filter
					 */
					function ($http, $q, $filter) {
						var ret = new Kinds($http, $q, $filter);
						if (kinds) ret.setFromLoader(kinds);
						return ret;
					}
				]
			};
		})
});

/**
 * @typedef {Object} KindData
 * @property {(number|string)} ID
 * @property {(number|string)} order
 * @property {string} name
 * @property {string} items
 */

/**
 * @typedef {KindData[]|Object.<string, KindData>} KindsData
 */