define(['angular-boot', 'pages/contactUs/controllers', 'pages/faq/directives'], function () {
    'use strict';

    angular
        .module("contactUsPage", ['appConfig', 'contactUsPage-ctrl', 'faqPage-dir'])
        .config(["$locationProvider", function($locationProvider) {
            $locationProvider.html5Mode({
                enabled: true,
                requireBase: false,
                rewriteLinks: false
            });
        }]);

	$(document).ready(function() {
		angular.bootstrap(document, ['contactUsPage']);
	});

});