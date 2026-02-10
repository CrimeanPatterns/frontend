import 'jquery-boot';
import dialog from '../bem/ts/service/dialog';
import Translator from '../bem/ts/service/translator';
import Router from '../bem/ts/service/router';

var popup = $('#mediaLogos');
var logo = $('.header-site .logo');

var d = dialog.createNamed('mediaLogos', popup, {
    autoOpen: false,
    modal: true,
    minWidth: 550,
    //position: { my: "left-80 top", at: "center bottom+15", of: logo },
    buttons: [
        {
            text: Translator.trans(/** @Desc("Close") */ 'button.close'),
            class: 'btn-silver',
            click: function () {
                d.close();
            },
        },
        {
            text: Translator.trans(/** @Desc("Proceed to this page") */ 'button.proceed_to_page'),
            class: 'btn-blue',
            click: function () {
                window.location = Router.generate('aw_media_logos');
            },
        },
    ],
});

logo.on('contextmenu', function (e) {
    e.preventDefault();
    d.open();
    //$(".ui-dialog-titlebar").hide();
});
