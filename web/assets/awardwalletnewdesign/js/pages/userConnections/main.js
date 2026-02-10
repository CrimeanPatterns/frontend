define([
        'angular-boot',
        'routing',
        'pages/userConnections/controllers'],
    function () {
        'use strict';

        angular
            .module("userConnectionsPage", ['appConfig', 'userConnectionsPage-ctrl']);

		$(document).ready(function() {
			angular.bootstrap(document, ['userConnectionsPage']);
		});

    });