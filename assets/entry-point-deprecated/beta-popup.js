import $ from 'jquery-boot';
import dialog from 'lib/dialog';

var popup = $('#betaWelcome');

var d = dialog.createNamed('betaWelcome', popup, {
    autoOpen: true,
    modal: true,
    minWidth: 590,
    buttons: [
        {
            text: Translator.trans('button.ok'),
            class: 'btn-blue',
            click: function () {
                d.close();
            },
        },
    ],
});
setTimeout(function () {
    d.open();
}, 2000);
