define(['angular', 'routing'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('agentsService', []);

	function Agents($filter) {
		/**
		 *
		 * @type {AgentsData}
		 */
		var agents = {};

		function getAgents(getAll) {
			var ret = [];
			for (var n in agents) {
				if (getAll) {
					ret.push(agents[n]);
				} else {
					if (!agents[n].hidden) {
						ret.push(agents[n]);
					}
				}
			}
			return $filter('orderBy')(ret, 'order');
		}

		/**
		 * @class userProvider
		 */
		var self = {
			/**
			 *
			 * @param {AgentsData} data
			 */
			setAgents: function (data) {
				agents = data;
			},
			/**
			 *
			 * @return {AgentsData}
			 */
			getAgents: function (getAll) {
				getAll = getAll || false;
				return getAgents(getAll);
			},
			/**
			 *
			 * @param {string|number} id
			 * @param {AgentData} data
			 */
			setAgent: function (id, data) {
				if (Object.prototype.hasOwnProperty.call(agents, id)) {
					agents[id] = angular.extend(agents[id], data);
				} else {
					agents[id] = data;
				}
			},
			/**
			 *
			 * @param {string|number} id
			 * @return {AgentData}
			 */
			getAgent: function (id) {
				if (Object.prototype.hasOwnProperty.call(agents, id)) {
					return agents[id];
				} else {
					return null;
				}
			},
			/**
			 *
			 * @return {AgentsData}
			 */
			getPossibleOwners: function () {
				/** @type {AgentsData} */
				var agentsArray = self.getAgents(true);
				/** @type {AgentsData} */
				var ret = [];
				for (var n in agentsArray) {
					if (self.isPossibleOwner(agentsArray[n].ID)) {
						ret.push(agentsArray[n]);
					}
				}
				return $filter('orderBy')(ret, 'name');
			},
			/**
			 *
			 * @return {AgentsData}
			 */
			getPossibleShares: function () {
				/** @type {AgentsData} */
				var agentsArray = self.getAgents(true);
				/** @type {AgentsData} */
				var ret = [];
				for (var n in agentsArray) {
					if (self.isPossibleShare(agentsArray[n].ID)) {
						ret.push(agentsArray[n]);
					}
				}
				return $filter('orderBy')(ret, 'name');
			},
			/**
			 *
			 * @param {string|number} id
			 * @param {boolean} flag
			 */
			setPossibleOwner: function (id, flag) {
				if (Object.prototype.hasOwnProperty.call(agents, id)) {
					agents[id].owner = !!flag;
				}
			},
			/**
			 *
			 * @param {string|number} id
			 * @return {boolean}
			 */
			isPossibleOwner: function (id) {
				if (Object.prototype.hasOwnProperty.call(agents, id)) {
					return agents[id].owner || id === 'my';
				}
				return false;
			},
			/**
			 *
			 * @param {string|number} id
			 * @return {boolean}
			 */
			isPossibleShare: function (id) {
				if (Object.prototype.hasOwnProperty.call(agents, id)) {
					return agents[id].shareable;
				}
				return false;
			},

			setFromLoader: function (data) {
				angular.forEach(data, function (d) {
					self.setAgent(d.ID, d);
				});
			}
		};

		return self;

	}

	service.provider('Agents',
		function () {
			var agents, owners;

			return {
				setUsers: function(data) {
					agents = data;
				},
				$get: ['$filter',
					/**
					 * @lends Agents
					 */
					function ($filter) {
						var ret = new Agents($filter);
						//if (agents) ret.setRaw(agents);
						//if (owners) ret.setOwnersRaw(owners);
						return ret;
					}
				]
			};
		})
});

/**
 * @typedef {Object} AgentData
 * @property {(number|string)} ID
 * @property {string} name
 * @property {boolean} owner
 * @property {boolean} shareable
 * @property {string} shareableNotice
 * @property {boolean} hidden
 */

/**
 * @typedef {AgentData[]|Object.<string, AgentData>} AgentsData
 */

