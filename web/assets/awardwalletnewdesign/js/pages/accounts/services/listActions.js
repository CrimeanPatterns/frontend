define(['angular'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('listActionsService', []);

	function ListActions(di) {
		/**
		 * Action handlers
		 */
		var actions = {};

		/**
		 * Action test functions
		 */
		var tests = {};

		/**
		 * Action items sets
		 */
		var items = {};

		/**
		 * Action properties
		 */
		var props = {};

		var shared = {
			state: {
				dropdown: false
			},
			actions: {}
		};

		/**
		 * @class actionProvider
		 */
		var self = {
			/**
			 * Add/set action handler and test function
			 * @param {string} action
			 * @param {function} handler
			 * @param {function=} testFunc
			 * @param {Object=} properties
			 */
			setAction: function (action, handler, testFunc, properties) {
				testFunc = testFunc || false;
				actions[action] = handler;
				items[action] = [];
				props[action] = angular.merge({icon: '', text: '', group: 'dropdown'}, properties || {});
				shared.actions[action] = {};
				if (testFunc !== false) tests[action] = testFunc;
			},
			/**
			 * Remove action
			 * @param action
			 */
			removeAction: function (action) {
				if (self.hasAction(action)) {
					delete actions[action];
					delete items[action];
					delete props[action];
					delete shared.actions[action];
					if (Object.prototype.hasOwnProperty.call(tests, action)) {
						delete tests[action];
					}
				}
			},
			/**
			 * Is action exists
			 * @param action
			 * @returns {boolean}
			 */
			hasAction: function (action) {
				return Object.prototype.hasOwnProperty.call(actions, action);
			},
			/**
			 * Set test function
			 * @param action
			 * @param test
			 */
			setTest: function (action, test) {
				if (self.hasAction(action)) {
					tests[action] = test;
				}
			},
			/**
			 * Set actions items
			 * @param data
			 */
			setItems: function (data) {
				angular.forEach(actions, function(func, action) {
					if (Object.prototype.hasOwnProperty.call(data, action) && data[action] instanceof Array) {
						items[action] = data[action];
					} else {
						items[action] = [];
					}
				});
			},
			/**
			 * Test action on active state (is current checked elements in action elements?)
			 * @param action
			 * @param checked
			 * @returns {boolean}
			 */
			isActiveAction: function (action, checked) {
				if (angular.isArray(checked) && checked.length && self.hasAction(action) && items[action].length) {
					return checked.filter(function (id) {return items[action].indexOf(id) > -1;}).length > 0;
				}
				return false;
			},
			/**
			 * Get active flag for all actions
			 * @returns {{}}
			 */
			isActiveActions: function (checked) {
				var ret = {};
				angular.forEach(actions, function(func, action) {
					ret[action] = self.isActiveAction(action, checked);
				});
				return ret;
			},
			/**
			 * Set active flag for all actions
			 */
			setActiveActions: function (checked) {
				angular.forEach(shared.state, function (val, group) {
					shared.state[group] = false;
				});
				angular.forEach(self.isActiveActions(checked), function (val, action) {
					shared.actions[action] = {
						icon: props[action].icon,
						text: props[action].text,
						group: props[action].group,
						active: val
					};
					if (!Object.prototype.hasOwnProperty.call(shared.state, props[action].group)) {
						shared.state[props[action].group] = false;
					}
					shared.state[props[action].group] = shared.state[props[action].group] || val;
				});
			},
			/**
			 * Get checked elements, allowed in action
			 * @param action
			 * @param checked
			 * @returns {Array}
			 */
			getActionItems: function (action, checked) {
				var ret = [];
				if (self.isActiveAction(action, checked)) {
					ret = checked.filter(function (id) {return items[action].indexOf(id) > -1;})
				}
				return ret;
			},
			/**
			 * Do action
			 * @param action
			 * @param checked
			 * @param force
			 */
			go: function (action, checked, force) {
				checked = checked || false;
				if (!checked) {
					checked = di.get('checker').getChecked();
				}
				checked = force ? checked : self.getActionItems(action, checked);
				if (checked.length) {
					actions[action](checked);
				}
			},

			/**
			 * Get actions list
			 * @returns {Array}
			 */
			getActions: function () {
				return Object.keys(actions);
			},
			getState: function () {
				return shared.state;
			},
			getActionsState: function () {
				return shared.actions;
			},
			/**
			 * Test item data on allowed in action
			 * @param element
			 * @returns {{}}
			 */
			testElement: function (element) {
				var ret = {};
				angular.forEach(actions, function(func, action) {
					if (Object.prototype.hasOwnProperty.call(tests, action) && tests[action]) {
						ret[action] = !!(tests[action](element.fields));
					} else {
						ret[action] = true;
					}
				});
				return ret;
			}
		};

		return self;
	}

	service.provider('ListActions',
		function () {
			//var param;

			return {
				//setParam: function(data) {
				//	param = data;
				//},
				$get: [
					'DI',
					/**
					 * @lends ListActions
					 */
					function (di) {
						return new ListActions(di);
					}
				]
			};
		})

});