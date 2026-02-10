define(['angular-boot', 'pages/faq/controllers', 'pages/faq/directives'], function () {
    'use strict';

    angular.module("faqPage", ['appConfig', 'faqPage-ctrl', 'faqPage-dir']);

	$(document).ready(function() {
		angular.bootstrap(document, ['faqPage']);
	});

});