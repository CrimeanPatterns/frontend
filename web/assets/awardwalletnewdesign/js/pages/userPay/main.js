define([
        'jquery-boot',
        'routing',
        'pages/userPay/controllers'],
    function () {
        'use strict';

        angular
            .module("userPayPage", ['appConfig', 'userPayPage-ctrl']);

		$(document).ready(function() {
			angular.bootstrap(document, ['userPayPage']);
		});

    });