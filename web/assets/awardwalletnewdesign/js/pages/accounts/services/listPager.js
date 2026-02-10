define(['angular'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	function ListPager() {
		var bounds = 5;
		var share = {
			page: 1,
			pages: 0,
			pagesIndex: []
		};

		var self = {
			setPages: function(data) {
				share.pages = data;
				if (share.pages < 0) share.pages = 0;
				if (share.page > share.pages && share.pages > 0) share.page = share.pages;
				if (share.page < 1) share.page = 1;
				self.indexer();
			},
			setPage: function(data) {
				share.page = data;
				if (share.page > share.pages) share.page = share.pages;
				if (share.page < 1) share.page = 1;
				self.indexer();
			},
			getPages: function() {
				return share.pages;
			},
			getPage: function() {
				return share.page;
			},
			indexer: function() {
				var start = share.page - bounds, end = share.page + bounds;
				if (start < 1) start = 1;
				if (end > share.pages) end = share.pages;
				var index = [];
				for (var k = start; k <= end; k++) {
					index.push(k);
				}
				share.pagesIndex = index;
			},
			getPager: function() {
				return share;
			}
		};
		return self;
	}

	var service = angular.module('listPagerService', []);

	service.provider('ListPager',
		function () {
			return {
				$get: [
					function () {
						return new ListPager();
					}
				]
			};
		});


});