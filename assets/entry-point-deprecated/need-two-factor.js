import $ from 'jquery-boot';
import dialog from 'lib/dialog';

var popup = $('#needTwoFactorAuthentication');
const dialogButtons = [];

if (window.needTwoFactorPopupIsTwoFactorAllowed) {
    const buttonSetTwoFactor = {
        text: window.needTwoFactorPopupSetTwoFactorButtonText,
        class: 'btn-blue btn-wide',
        click: function () {
            var route;
            if (window.needTwoFactorPopupIsBusiness) {
                route = window.needTwoFactorPopupSetTwoFactorBusinessPath;
            } else {
                route = window.needTwoFactorPopupSetTwoFactorUserPath;
            }
            window.location = route;
        },
    };

    dialogButtons.push(buttonSetTwoFactor);
} else {
    const buttonSetPassword = {
        text: window.needTwoFactorPopupSetPasswordButtonText,
        class: 'btn-blue btn-wide',
        click: function () {
            if (window.needTwoFactorPopupIsBusiness) {
                window.location = window.needTwoFactorPopupSetPasswordBusinessPath;
            } else {
                window.location = window.needTwoFactorPopupSetPasswordUserPath;
            }
        },
    };
    dialogButtons.push(buttonSetPassword);
}

var d = dialog.createNamed('needTwoFactorAuthentication', popup, {
    modal: true,
    minWidth: 590,
    autoOpen: false,
    closeOnEscape: false,
    keyboard: false,
    draggable: true,
    resizable: false,
    open: function (event) {
        if (window.needTwoFactorPopupIsUser) {
            $(event.target).parent().find('.ui-dialog-titlebar-close').hide();
        }
        $('.ui-widget-overlay').click(function (e) {
            e.preventDefault();
            return false;
        });
    },
    buttons: dialogButtons,
});

if (window.needTwoFactorPopupShouldOpenDialog) {
    setTimeout(function () {
        d.open();
    }, 2000);
}
