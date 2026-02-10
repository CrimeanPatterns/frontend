define(['jquery-boot', 'routing', 'jqueryui', 'translator-boot'], function ($) {
	var insufficient_balance_popup = $('#insufficient_balance_popup');
	if (insufficient_balance_popup.length) {
		insufficient_balance_popup.dialog({
			title: Translator.trans(/** @Desc("Insufficient balance") */ 'insufficient-balance.popup.title'),
			width: 600,
			autoOpen: true,
			modal: true,
			draggable: true,
			closeOnEscape: false,
			dialogClass: 'no-close',
			beforeClose: function( event, ui ) {
				return false;
			},
			buttons: [
				{
					text: Translator.trans(/** @Desc("Fill up balance") */ 'insufficient-balance.popup.proceed'),
					//style: "width: 300px;",
					"class": "btn-blue",
					click: function () {
						window.location.href = Routing.generate('aw_business_pay');
					}
				}
			]
		});
		$('.ui-widget-overlay').css({zIndex: 989});
		//not_verified_dialog.dialog().open();
	}
});