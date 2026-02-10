define([
		'angular-boot',
		'jquery-boot',
		'pages/addAccount/controllers', 'pages/addAccount/filters'],
	function (angular, $) {
		angular = angular && angular.__esModule ? angular.default : angular;

		angular
			.module("accountAddPage", ['appConfig', 'accountAddPage-ctrl', 'accountAddPage-filter']);

		$(function () {
			angular.bootstrap(document, ['accountAddPage']);
		});
	});