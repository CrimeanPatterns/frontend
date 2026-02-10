define([
        'angular-boot',
        'routing',
        'pages/businessMembers/controllers', 'filters/htmlencode'],
    function () {
        'use strict';

        angular
            .module("businessMembersPage", ['appConfig', 'businessMembersPage-ctrl', 'htmlencode-mod']);

		$(document).ready(function() {
			angular.bootstrap(document, ['businessMembersPage']);
		});

    });