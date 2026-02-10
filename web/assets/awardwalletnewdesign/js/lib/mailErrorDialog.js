define(['lib/dialog', 'translator-boot'], function (dialog) {

	return function (mailErrors) {
		const title = Translator.trans('error.server.other.title');

		mailErrors = JSON.parse(mailErrors);
		const message = mailErrors.join("<br/><br/>");

		const dlg = dialog.fastCreate(
			title,
			message,
			true,
			true,
			[
				{
					text: Translator.trans('button.ok'),
					'class': 'btn-blue',
					click: function () {
						dlg.close();
					}
				}
			],
			500,
			null,
			'error'
		);
	}

});