define(['angular-boot', 'jquery-boot', 'directives/dialog', 'directives/tabs'], function () {
	'use strict';
	angular.module('faqPage-ctrl', ['dialog-directive', 'tabs-directive'])
		.controller('indexCtrl', function (dialogService, $window, $document, $timeout) {
			$timeout(function () {
				$($window).bind('hashchange', function (e) {
					e.preventDefault();
					if (document.location.hash) {
						var element = $('.faq-blk[data-id="' + document.location.hash + '"]');
						if (element.length)
							$document.scrollToElement(element, 55, 600);
					}
				}).trigger('hashchange');
			}, 1);

            $('img[src*=\'OneCardSmall.png\']').parent().on('click', function (e) {
                e.preventDefault();
                dialogService.get('oneCardSampleImage').open();
            })
        });
});
