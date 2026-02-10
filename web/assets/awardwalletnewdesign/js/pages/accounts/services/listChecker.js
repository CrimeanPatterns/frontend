define(['angular'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('listCheckerService', []);

	function ListChecker(di, persistent) {
		var Checker = di.get('checker'),
			Actions = di.get('actions-manager'),
			index = {},
			tests = {},
			props = {},
			lastCheck = false,
			shared = {
				state: {
					checked: 0,
					mark: false,
					reset: false
				},
				checks: {}
			};

		function update () {
			shared.state.checked = self.getChecked().length;
			shared.state.mark = (shared.checks.all.cnt == shared.state.checked) && shared.checks.all.cnt > 0;
			shared.state.reset = !shared.state.mark && (shared.state.checked > 0);
			Actions.setActiveActions(self.getChecked());
		}

		var self = {
			setCheck: function (type, testFunc, properties) {
				testFunc = testFunc || false;
				index[type] = [];
				props[type] = angular.merge({icon: '', text: ''}, properties || {});
				if (testFunc !== false) tests[type] = testFunc;
			},
			testElement: function (element) {
				var ret = {};
				angular.forEach(index, function(func, type) {
					if (Object.prototype.hasOwnProperty.call(tests, type) && tests[type]) {
						ret[type] = !!(tests[type](element.fields));
					} else {
						ret[type] = true;
					}
				});
				return ret;
			},
			setView: function (type, items) {
				if (Object.prototype.hasOwnProperty.call(index, type)) {
					index[type] = items;
					shared.checks[type] = {
						icon: props[type].icon,
						text: props[type].text,
						cnt: items.length
					};
					if (type === 'all' && items.length) {
						update();
					}
				}
			},
			setIndex: function (data) {
				angular.forEach(index, function (items, type) {
					self.setView(type, []);
				});
				angular.forEach(data, function (items, type) {
					self.setView(type, items);
				})
			},
			getChecked: function () {
				var checked = Checker.getChecked();
				return index.all.filter(function (id) {return checked.indexOf(id) !== -1;});
			},
			select: function (type) {
				if (Object.prototype.hasOwnProperty.call(index, type)) {
					Checker.uncheckAll();
					Checker.check(index[type]);
					update();
				}
			},
			checkAll: function () {
				Checker.check(index.all);
				update();
			},
			resetAll: function () {
				Checker.uncheckAll();
				update();
			},
			checkOne: function (id, event) {
				if (lastCheck && id != lastCheck) {
					var state = Checker.isChecked(lastCheck);
					if (event && event.shiftKey) {
						var checking = false, reverse = false;
						angular.forEach(index.all, function (currentId) {
							if (!checking) {
								if (currentId == id) {
									checking = true;
									reverse = true;
								} else if (currentId == lastCheck) {
									checking = true;
								}
							}
							if (checking) {
								if (state) {
									Checker.check(currentId);
								} else {
									Checker.uncheck(currentId);
								}

								if ((currentId == id && !reverse) || (currentId == lastCheck && reverse)) {
									checking = false;
								}
							}
						});
					}
				}
				update();
				lastCheck = id;
			},
			cleanLastCheck: function () {
				lastCheck = false;
			},
			getState: function () {
				return shared.state;
			},
			getCheckState: function () {
				return shared.checks;
			},
			resetService: function () {
				if (!persistent) {
					Checker.reset();
				}
			}
		};

		self.setCheck('all',
			false,
			{icon: 'icon-checkbox', text: Translator.trans('award.account.list.menu.select.all')}
		);

		return self;
	}

	service.provider('ListChecker',
		function () {
			var persistent = true;

			return {
				setPersistent: function(data) {
					persistent = data;
				},
				$get: [
					'DI',
					/**
					 * @lends ListChecker
					 */
					function (di) {
						return new ListChecker(di, persistent);
					}
				]
			};
		})
});

