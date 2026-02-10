import 'jquery-boot';
import 'jqueryui';

// Invite via email
$('form[name=inviteByEmail]')
    .on('submit', function (e) {
        e.preventDefault();

        require(['pages/invites/inviteByEmailHandler'], function (clickHandler) {
            clickHandler(e, '{{ csrfToken }}');
        });
    })
    .find('input')
    .on('keyup paste', function (e) {
        var btn = $(e.target).next();
        if (e.target.validity.valid) {
            btn.show(300);
        } else {
            btn.hide(300);
        }
    });
