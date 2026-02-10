require(['jquery-boot', 'lib/reauthenticator', 'jqueryui', 'translator-boot', 'common/alerts'], function ($, reauth) {
    $(function () {
        $('#have_business_dialog').dialog({
            autoOpen: true,
            modal: true,
            closeOnEscape: false,
            closeText: false,
            width: 460,
            open: function (event, ui) {
                $(".ui-dialog-titlebar-close", event.target.offsetParent).hide();
                $(".ui-dialog-title", event.target.offsetParent).html('<i class="icon-warning-small"></i>Warning!');
            },
            buttons: [
                {
                    text: Translator.trans('button.cancel'),
                    'class': 'btn btn-blue',
                    click: function () {
                        location.href = '/';
                    }
                }

            ]
        });

        var submit = false;

        $('#delete-account-form').submit(function(e) {
            var form = $(this);
            if (submit) {
                return true;
            }
            e.preventDefault();
            var cb = function() {
                form.find('[type="submit"].loader').removeClass('loader').prop('disabled', false);
            };
            reauth.reauthenticate(
                reauth.getDeleteAccountAction(),
                function() {
                    submit = true;
                    cb();
                    form.submit();
                },
                function() {
                    cb();
                }
            );
        });

        $('input[type="submit"]').on('click', function (e) {
            e.preventDefault();

            setTimeout(function () {
                if (!$('#delete-account-form').find('.error').length)
                    jConfirm(Translator.trans(/**@Desc("Are you sure you want to delete this account and all the data associated with this account?")*/ 'user.delete.confirm-text'), function () {
                        $('#delete-account-form').submit();
                    });
            }, 20);
        })
    });
});
