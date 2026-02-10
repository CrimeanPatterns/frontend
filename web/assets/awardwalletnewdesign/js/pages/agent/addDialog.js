define(['jquery-boot', 'lib/dialog', 'translator-boot', 'routing'], function($, dialog){

	var dialogElement;

	// Add persons popup
	if(typeof(dialogElement) == 'undefined') {
		dialogElement = $('<div />').appendTo('body').html(
				Translator.trans(/** @Desc("You have two options, you can connect with another person on AwardWallet, or you can just create another name to better organize your rewards.") */'agents.popup.content')
		);
		dialog.createNamed('persons-menu', dialogElement, {
			width: '600',
			autoOpen: false,
			modal: true,
			title: Translator.trans(/** @Desc("Select connection type") */ 'agents.popup.header'),
			buttons: [
				{
					'text': Translator.trans(/** @Desc("Connect with another person") */'agents.popup.connect.btn'),
					'class': 'btn-blue spinnerable',
					'click': function () {
						window.location.href = Routing.generate('aw_create_connection')
					}
				},
				{
					'text': Translator.trans(/** @Desc("Just add a new name") */'agents.popup.add.btn'),
					'class': 'btn-blue spinnerable',
					'click': function () {
						window.location.href = Routing.generate('aw_add_agent')
					}
				}
			],
			open: function () {
				// Remove bottons focus
				$('.ui-dialog :button').blur();
                history.pushState(null, null, '?add-new-person=true');

            },
            close: function() {
                history.back();
            }
		});
	}

	var clickHandler = function() {
		dialogElement.dialog('open');
	};

	return clickHandler;
});
