define(['angular-boot', 'jquery-boot', 'angular-scroll'], function () {
	'use strict';

	angular.module('faqPage-dir', ['duScroll'])
		.directive('faqToggle', function () {
			return {
				restrict: 'A',
				link: function (scope, element) {
					element.find('.question').on('click', function (e) {
						e.preventDefault();

						document.location.hash = element.data('id');

						$(element).find('.answer').slideToggle(300, function () {
							$(element).toggleClass('active');
						});
					});
				}
			};
		});
});