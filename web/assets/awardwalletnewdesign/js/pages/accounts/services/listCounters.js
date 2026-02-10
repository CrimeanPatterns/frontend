define([
	'angular'
], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	function ListCounters() {
		var share = {
			counters: {
				accounts: 0,
				actives: 0,
				archives: 0,
				coupons: 0
			},
			owner: {},
			totals: {
				viewTotal: 0,
				viewBalance: 0,
				byKind: {},
				byOwner: {},
				total: 0,
				balance: 0
			}
		};

		var self = {
			setAccounts: function(data) {
				share.counters.accounts = data;
			},
			setActives: function(data) {
				share.counters.actives = data;
			},
			setArchives: function(data) {
				share.counters.archives = data;
			},
			setCoupons: function(data) {
				share.counters.coupons = data;
			},
			setOwners: function(data) {
				angular.forEach(share.owner, function (d, id) {
					share.owner[id] = 0;
				});
				angular.extend(share.owner, data);
			},
			setTotals: function(data) {
				share.totals = angular.extend(share.totals, data);
			},
			getTotals: function() {
				return share.totals;
			},
			getOwnerCounters: function() {
				return share.owner;
			},
			getCounters: function() {
				return share.counters;
			}
		};
		return self;
	}

	var service = angular.module('listCountersService', []);

	service.provider('ListCounters',
		function () {
			return {
				$get: [
					function () {
						return new ListCounters();
					}
				]
			};
		});


});
