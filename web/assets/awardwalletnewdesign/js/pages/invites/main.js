define([
        'angular-boot',
        'routing',
        'pages/invites/controllers',
        'filters/htmlencode'],
    function () {
        'use strict';

        angular
            .module("invitesPage", ['appConfig', 'invitesPage-ctrl', 'htmlencode-mod']);

		$(document).ready(function() {
			angular.bootstrap(document, ['invitesPage']);
		});

    });