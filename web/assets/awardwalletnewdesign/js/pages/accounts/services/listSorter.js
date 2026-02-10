define([
	'angular'
], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	function ListSorter(groupMode, sorter) {
		var params = {
			ungroup: true,
			column: 'Name',
			reverse: false,
			search: false,
			owner: false
		};
		var changed = false;
		var self = {
			setUngroup: function(ungroup) {
				if (!groupMode) return;
				ungroup = ungroup || false;
				if (params.ungroup != ungroup) {
					params.ungroup = ungroup;
					changed = true;
				}
			},
			setSearch: function(search) {
				if (!groupMode) return;
				search = search || false;
				if (params.search != search) {
					params.search = search;
					changed = true;
				}
			},
			setOwner: function(owner) {
				if (!groupMode) return;
				owner = owner || false;
				if (params.owner != owner) {
					params.owner = owner;
					changed = true;
				}
			},
			setOrder: function(column, reverse) {
				if (params.column != column) {
					params.column = column;
					changed = true;
				}
				if (reverse !== undefined) {
					self.setReverse(reverse);
				}
			},
			setColumn: function(column) {
				if (params.column != column) {
					params.column = column;
					changed = true;
				}
			},
			setReverse: function(reverse) {
				if (params.reverse != reverse) {
					params.reverse = reverse;
					changed = true;
				}
			},
			getUngroup: function() {
				return params.ungroup;
			},
			getColumn: function() {
				return params.column;
			},
			getReverse: function() {
				return params.reverse;
			},
			getOrder: function() {
				return params;
			},
			/**
			 *
			 * @param {Object} data Массив данных для сортировки
			 * @param {Array} index Индекс массива данных
			 * @param {boolean} required Требуется ли пересортировка
             * @param {boolean} forceUngroup Разгруппировать ли данные
			 * @returns {Array}
			 */
			sort: function(data, index, required, forceUngroup) {
				required = required || changed;
                forceUngroup = forceUngroup || false;
				if (!required) return index;
				changed = false;

                const localParams = Object.assign({}, params);

                if (forceUngroup) {
                    localParams.ungroup = true;
                }

				return sorter.go(data, index, localParams);
			}
		};
		return self;
	}

	function Sorter($filter) {
		return {
			go: function (data, index, params) {
				var orderBy = [], indexOrderBy = {}, sorterIndex = [];

				if (!params.ungroup) {
					if (params.search || params.owner) {
						indexOrderBy.group = function (a) {
							return data[a]._preorder.kind;
						};
					} else {
						indexOrderBy.group = function (a) {
							return data[a]._preorder.kindUser;
						};
					}
					orderBy.push('group');
				} else {
					// flat
				}
				indexOrderBy.column = function (a) {
					return data[a]._order[params.column];
				};
				if (params.reverse) {
					if (params.column == 'ExpirationDateTs' || params.column == 'LastUpdatedDateTs') {
						indexOrderBy.column = function (a) {
							return data[a]._order[params.column + 'Reverse'];
						};
					}
					orderBy.push('-column');
				} else {
					orderBy.push('column');
				}
				indexOrderBy.post = function (a) {
					return data[a].ID;
				};
				orderBy.push('post');

				angular.forEach(index, function (id) {
					var idx = {
						id: id,
						column: indexOrderBy.column(id),
						post: indexOrderBy.column(id)
					};
					if (Object.prototype.hasOwnProperty.call(indexOrderBy, 'group')) {
						idx.group = indexOrderBy.group(id);
					}
					sorterIndex.push(idx);
				});
				sorterIndex = $filter('orderBy')(sorterIndex, orderBy);
				index = sorterIndex.map(function (idx) {return idx.id});

				return index;
			}
		};
	}

	function DummySorter() {
		return {
			go: function (data, index, params) {
				return index;
			}
		};
	}


	var service = angular.module('listSorterService', []);

	service.provider('ListSorter',
		function () {
			// Режим работы сервиса:
			// true -- с и без группировки по Kinds
			// false -- только без группировки по Kinds
			var groupMode = true;

			return {
				setGroupMode: function(mode) {
					groupMode = mode;
				},
				$get: [
					'$filter',
					/**
					 * @param $filter
					 */
					function ($filter) {
						return new ListSorter(groupMode, new Sorter($filter));
					}
				]
			};
		});

	service.provider('DummyListSorter',
		function () {
			return {
				$get: [
					function () {
						return new ListSorter(false, new DummySorter());
					}
				]
			};
		})

});