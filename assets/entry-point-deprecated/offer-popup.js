import $ from 'jquery-boot';
import dialog from 'lib/dialog';
import onReady from '../bem/ts/service/on-ready';

onReady(() => {
    window.dialog = dialog;

    var popup = $('#offerPopup');
    dialog.createNamed('offerPopup', popup, {
        autoOpen: true,
        modal: true,
        minWidth: 910,
        width: 'auto',
        position: {
            my: 'center',
            at: 'center',
            of: window,
        },
        open: function (event, ui) {
            $('.ui-dialog-titlebar', ui.dialog | ui).hide();
        },
    });
});
