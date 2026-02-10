(function (window, document, angular, React) {
    let stateParams;

    function getAnswer(type) {
        const matches = document.cookie.match(new RegExp(
            "(?:^|; )" + `${type}_mb_answer`.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));

        return matches ? decodeURIComponent(matches[1]) : undefined;
    }

    function saveAnswer(type, answer) {
        const d = new Date();
        d.setTime(d.getTime()+(15*365*24*60*60*1000));

        document.cookie = `${type}_mb_answer=${escape(answer)}; expires=${d.toGMTString()}`;
    }

    function getQueryParams() {
        const result = {};

        if (stateParams.toPath) {
            result.BackTo = stateParams.toPath;
        }

        if (window.location.search) {
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

        }

        return result;
    }

    function redirect(type, action, mailboxAccess) {
        document.location.href = '/oauth/start/' + type
            + '?' + new URLSearchParams(Object.assign(getQueryParams(), {
                'action': action,
                'mailboxAccess': mailboxAccess,
                'rememberMe': true
            })).toString();
    }

    class OauthButtons extends React.Component {
        static propTypes = {
            action: React.PropTypes.string.isRequired,
            popup: React.PropTypes.object
        }

        renderButton(type, mailboxSupport = true) {
            const {action, popup} = this.props;

            return (
                <OauthButton type={type} action={action} mailboxSupport={mailboxSupport} popup={popup}/>
            );
        }
        render() {
            return (
                <div className="oauth">
                    <div className="oauth__or">{Translator.trans('or')}</div>
                    {this.renderButton('google')}
                    {this.renderButton('microsoft')}
                    {this.renderButton('yahoo')}
                    {this.renderButton('apple', false)}
                </div>
            );
        }
    }

    class OauthButton extends React.Component {
        static propTypes = {
            action: React.PropTypes.string.isRequired,
            type: React.PropTypes.string.isRequired,
            popup: React.PropTypes.object.isRequired,
            mailboxSupport: React.PropTypes.bool,
        }

        static defaultProps = {
            mailboxSupport: true
        }

        constructor(props) {
            super(props);

            this.onClick = this.onClick.bind(this);
        }

        getCaption(type, signUp) {
            switch(type) {
                case 'google':
                    return signUp ? Translator.trans('sign-up-btn.google') : Translator.trans('award.mailbox.google');
                case 'microsoft':
                    return signUp ? Translator.trans('sign-up-btn.microsoft') : Translator.trans('award.mailbox.misrosoft');
                case 'yahoo':
                    return signUp ? Translator.trans('sign-up-btn.yahoo') : Translator.trans('award.mailbox.yahoo');
                case 'aol':
                    return signUp ? Translator.trans('sign-up-btn.aol') : Translator.trans('award.mailbox.aol');
                case 'apple':
                    return signUp ? Translator.trans('sign-up-btn.apple') : Translator.trans('sign-in-btn.apple');
            }
        }

        onClick() {
            const {action, type, mailboxSupport, popup} = this.props;

            if (!mailboxSupport) {
                return redirect(type, action, mailboxSupport);
            }

            const answer = getAnswer(type);

            if (typeof answer !== 'undefined') {
                return redirect(type, action, answer);
            }

            const cb = answer => {
                saveAnswer(type, answer);
                popup.close();
                redirect(type, action, answer);
            };
            popup.open({
                yes: () => cb(true),
                no: () => cb(false)
            });
        }

        render() {
            const {action, type} = this.props;
            const signUp = action === 'register';

            return (
                <a href="javascript:void(0);" onClick={this.onClick} className="oauth__button">
                    <span className="oauth__button-icon">
                        <i className={'icon-' + type} />
                    </span>
                    <span>{this.getCaption(type, signUp)}</span>
                </a>
            );
        }
    }

    angular.module('AwardWalletMobile')
        .directive('oauthButtons', ['ReactDirective', 'ScanMailboxQuestionPopup', '$stateParams', (ReactDirective, ScanMailboxQuestionPopup, $stateParams) => {
            stateParams = $stateParams;

            return ReactDirective(OauthButtons, undefined, undefined, {}, {
                popup: ScanMailboxQuestionPopup
            });
        }])
        .directive('oauthAgreement', ['ReactDirective', '$state', (ReactDirective, $state) => {
            return ReactDirective(class OauthAgreement extends React.Component {
                static propTypes = {
                    action: React.PropTypes.string.isRequired
                }

                getText() {
                    const {action} = this.props;

                    if (action === 'register') {
                        return Translator.trans('sign-up.agreement', {
                            'link1_on': '<a href="" ui-sref="unauth.terms" class="silver-link bold">',
                            'link1_off': '</a>',
                            'link2_on': '<a href="" ui-sref="unauth.privacy" class="silver-link bold">',
                            'link2_off': '</a>'
                        });
                    }

                    return Translator.trans('sign-in.agreement', {
                        'link1_on': '<a href="" ui-sref="unauth.terms" class="silver-link bold">',
                        'link1_off': '</a>',
                        'link2_on': '<a href="" ui-sref="unauth.privacy" class="silver-link bold">',
                        'link2_off': '</a>'
                    });
                }

                render() {
                    return (
                        <p id="oauth-agreement" className="small-text" dangerouslySetInnerHTML={{__html: this.getText()}} />
                    );
                }
            }, undefined, undefined, {
                onLink: elem => $(elem).on('click', 'a[ui-sref]', e => $state.go(e.target.getAttribute('ui-sref'))),
                onScopeDestroy: elem => $(elem).off('click')
            });
        }]);
})(window, document, angular, React);