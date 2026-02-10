define(['jquery-boot', 'lib/utils', 'lib/dialog', 'routing'], function($, utils) {
    return function(onShowQuestion, onHideQuestion) {

        function getKeyTypeName(type) {
            return `${type}_mb_answer`;
        }

        function getAnswer(type) {
            let answer = localStorage.getItem(getKeyTypeName(type));
            if (null === answer) {
                answer = utils.getCookie(getKeyTypeName(type));
                if ('undefined' !== typeof answer) {
                    return answer;
                }
            }
            return null;
        }

        function saveAnswer(type, answer) {
            localStorage.setItem(getKeyTypeName(type), answer);
        }

        function getQueryParams() {
            if (!window.location.search) {
                return {};
            }
            const result = {};
            var query = window.location.search.substring(1);
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                var name = decodeURIComponent(pair[0]);
                if (name === 'error') {
                    continue;
                }
                result[name] = decodeURIComponent(pair[1]);
            }
            return result;
        }

        function redirect(type, action, mailboxAccess) {
            document.location.href = Routing.generate(
                'aw_usermailbox_oauth',
                Object.assign(getQueryParams(), {
                    'type': type,
                    'action': action,
                    'mailboxAccess': mailboxAccess,
                    'rememberMe': $('#remember_me').is(':checked') && action === 'login'
                })
            );
        }

        function noop() {}

        const questionElem = $('#scan-mailbox-question');

        $('.oauth-buttons-list a').on('click', function (event) {
            event.preventDefault();

            const link = $(this);
            const type = link.data('type');
            const action = link.data('action');

            if (link.data('mailbox-support') !== 'off') {
                const answer = getAnswer(type);

                if (null !== answer || /^business/.test(window.location.hostname)) {
                    redirect(type, action, answer || false);
                } else {
                    questionElem.data('type', type);
                    questionElem.data('action', action);
                    questionElem.show();
                    (onShowQuestion || noop)();
                }

                return;
            }

            redirect(type, action, false);
        });


        questionElem.find('button').on('click', function(event) {
            event.preventDefault();

            const answer = $(this).data('mailbox-access');
            const type = questionElem.data('type');
            const action = questionElem.data('action');

            saveAnswer(type, answer);
            questionElem.hide();
            (onHideQuestion || noop)();

            redirect(type, action, answer);
        });

    };
});
