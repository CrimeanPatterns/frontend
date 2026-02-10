define(['jquery-boot', 'lib/dialog', 'translator-boot'], function($, dialog){

	return {
		showPopup: function () {
			var counter = $('#account-btn-counter');
			var myAccounts = Math.round(counter.text());
			if (counter.data('overlimits') == '1' || counter.data('onlimits') == '1' || document.location.href.indexOf('testBizPopup') > 0) {
				// @TODO: extractor do not grab this text
				var message = 'AwardWallet.com is a personal interface for managing loyalty programs and is not intended for business use.'
				+'<br/><br/>'
 				+'There is another business interface that we offer: <a href="https://business.AwardWallet.com" target="_blank">https://business.AwardWallet.com</a>.'
 				+'<br/><br/>'
				+'Your account looks like a business account based on the fact that you have added a total of '+myAccounts+' loyalty programs.';
				var button;
				button = {
					text: Translator.trans('agents.add.convert.btn'),
					'class': 'btn-blue',
					click: function () {
						document.location.href = '/agent/convertToBusiness.php?Start=1';
					}
				};
				// @TODO: other version of button - to go to business interface, implement after switch to business
				dialog.fastCreate(
					Translator.trans('personal.account.limit'),
					message,
					true,
					true,
					[button],
					500,
                    null,
                    'info'
				);
				return true;
			}
			else
				return false;
		}
	}

});
