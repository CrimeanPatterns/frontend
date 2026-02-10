require(['jquery-boot', 'lib/reauthenticator'], function($, reauth) {
    $(() => {
        const form = $('form[data-reauth-action]');
        const action = form.data('reauth-action');
        let submit = false;

        if (typeof action !== 'string' || typeof reauth['get' + action] !== 'function') {
            return;
        }

        form.submit(e => {
            if (submit) {
                return true;
            }
            e.preventDefault();
            const cb = () => {
                form.find('[type="submit"].loader').removeClass('loader').prop('disabled', false);
            };
            reauth.reauthenticate(
                reauth['get' + action](),
                () => {
                    submit = true;
                    cb();
                    form.submit();
                },
                () => cb()
            );
        });
    });
});