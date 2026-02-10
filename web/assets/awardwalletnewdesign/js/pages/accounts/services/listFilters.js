define([
	'angular'
], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	function ListFilters() {
		var share = {
			filters: {
				program: undefined,
				owner: undefined,
				account: undefined,
				status: undefined,
				balance: undefined,
				// cashequivalent: undefined,
				expire: undefined,
				lastupdate: undefined,
                sharedWith: undefined,
			}
		};
		var store = {
			program: undefined,
			owner: undefined,
			account: undefined,
			status: undefined,
			balance: undefined,
			// cashequivalent: undefined,
			expire: undefined,
			lastupdate: undefined,
            sharedWith: undefined,
		};

		var self = {
			clear: function () {
				share.filters.program = undefined;
				share.filters.owner = undefined;
				share.filters.account = undefined;
				share.filters.status = undefined;
				share.filters.balance = undefined;
				// share.filters.cashequivalent = undefined;
				share.filters.expire = undefined;
				share.filters.lastupdate = undefined;
				share.filters.sharedWith = undefined;
			},
			store: function () {
				store.program		= share.filters.program;
				store.owner			= share.filters.owner;
				store.account		= share.filters.account;
				store.status		= share.filters.status;
				store.balance		= share.filters.balance;
				// store.cashequivalent = share.filters.cashequivalent;
				store.expire		= share.filters.expire;
				store.lastupdate	= share.filters.lastupdate;
				store.sharedWith	= share.filters.sharedWith;
			},
			restore: function () {
				share.filters.program		= store.program;
				share.filters.owner			= store.owner;
				share.filters.account		= store.account;
				share.filters.status		= store.status;
				share.filters.balance		= store.balance;
				// share.filters.cashequivalent = store.cashequivalent;
				share.filters.expire		= store.expire;
				share.filters.lastupdate	= store.lastupdate;
				share.filters.sharedWith	= store.sharedWith;
			},
			getFilters: function() {
				return share.filters;
			}
		};
		return self;
	}

	var service = angular.module('listFiltersService', []);

	service.provider('ListFilters',
		function () {
			return {
				$get: [
					function () {
						return new ListFilters();
					}
				]
			};
		});


});