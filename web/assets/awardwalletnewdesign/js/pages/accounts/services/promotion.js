define(['angular', 'routing'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('PromotionService', []);

	function Promotion() {
		var ads = {};

		/**
		 *
		 * @class Promotion
		 */
		var self = {
			setData: function (data) {
                ads = data;
			},
            setFromLoader: function (data) {
                ads = data;
            },
            getAds: function () {
                return ads;
            },
            getAdByKind: function (kindId) {
				return ads[kindId];
            }
		};

		return self;
	}

	service.provider('Promotion',
		function () {
			var ads;

			return {
                setData: function(data) {
					ads = data;
				},
				$get: [
					/**
					 * @lends Promotion
					 */
					function () {
                        var ret = new Promotion();
                        if (ads) ret.setFromLoader(ads);
                        return ret;
					}
				]
			};
		})
});

