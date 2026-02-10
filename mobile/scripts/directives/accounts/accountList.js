(function (window, document, angular, React) {
    function classNames() {

        var classes = '';

        for (var i = 0; i < arguments.length; i++) {
            var arg = arguments[i];
            if (!arg) continue;

            var argType = typeof arg;

            if ('string' === argType || 'number' === argType) {
                classes += ' ' + arg;

            } else if (Array.isArray(arg)) {
                classes += ' ' + classNames.apply(null, arg);

            } else if ('object' === argType) {
                for (var key in arg) {
                    if (arg.hasOwnProperty(key) && arg[key]) {
                        classes += ' ' + key;
                    }
                }
            }
        }

        return classes.substr(1);
    }

    /**
     * @return {boolean}
     */
    function LastChangeDate(date, duration) {
        var date1 = new Date(date), date2 = new Date();
        return (Math.abs(date2.getTime() - date1.getTime()) / 3600000) < (duration || 24);
    }

    angular.module('AwardWalletMobile').service('RssFeed', [function () {
        return React.createClass({
            displayName: "RssFeed",

            render: function () {
                var href = this.props.link;
                if (platform.cordova) {
                    if (href.indexOf('?') > 0) {
                        href += '&fromapp=1';
                    } else {
                        href += '?fromapp=1';
                    }
                }
                return React.createElement(
                    "div",
                    {className: "rss-feed"},
                    React.createElement(
                        "a",
                        {href: href, target: "_system"},
                        this.props.title
                    )
                );
            }
        });
    }]);

    angular.module('AwardWalletMobile').service('SearchAccountList', ['$state', '$filter', 'RssFeed', 'UserSettings', function ($state, $filter, RssFeed, UserSettings) {
        /** @jsx React.DOM */

        var Title = React.createClass({
            displayName: "Title",

            render: function () {
                return React.createElement(
                    "div",
                    {className: "block-title"},
                    React.createElement("i", {className: "icon-arrow-down"}),
                    React.createElement(
                        "h3",
                        null,
                        this.props.name
                    ),
                    React.createElement(
                        "div",
                        {className: "prev"},
                        React.createElement("i", {className: this.props.icon})
                    )
                );
            }
        });
        var Header = React.createClass({
            displayName: 'Header',

            render: function () {
                var header = {
                    program: Translator.trans('award.account.list.column.program'),
                    account: Translator.trans('award.account.list.column.account'),
                    status: Translator.trans('award.account.list.column.status'),
                    expire: Translator.trans('award.account.list.column.expire'),
                    balance: Translator.trans('award.account.list.column.balance')
                };
                return React.createElement(
                    'div',
                    {className: 'title-list'},
                    React.createElement(
                        'div',
                        {className: "flex"},
                        React.createElement(
                            'div',
                            {className: 'title'},
                            header.program
                        ),
                        React.createElement(
                            'div',
                            {className: 'account-row'},
                            header.account
                        ),
                        React.createElement(
                            'div',
                            {className: 'status-row'},
                            header.status
                        ),
                        React.createElement(
                            'div',
                            {className: 'expiration-row'},
                            header.expire
                        ),
                        React.createElement(
                            'div',
                            {className: 'balance'},
                            header.balance
                        )
                    )
                );
            }
        });
        var UserBlock = React.createClass({
            displayName: 'UserBlock',

            render: function () {
                return React.createElement(
                    "div",
                    {className: "user-block"},
                    React.createElement(
                        "div",
                        {className: "item"},
                        React.createElement(
                            "div",
                            {className: "user-item"},
                            React.createElement("i", {className: "icon-user"}),
                            this.props.familyName ? React.createElement(
                                "h3",
                                null,
                                this.props.familyName,
                                React.createElement(
                                    "span",
                                    {className: "silver"},
                                    "(",
                                    this.props.userName,
                                    ")"
                                )
                            ) : React.createElement(
                                "h3",
                                null,
                                this.props.userName
                            )
                        )
                    )
                );
            }
        });
        var CardOffer = React.createClass({
            displayName: 'CardOffer',

            handleClick: function (event) {
                event.preventDefault();
                var findA = function (elem) {
                        if (elem.nodeName == 'A') {
                            return elem;
                        }
                        if (elem.parentElement) {
                            if (elem.parentElement.nodeName == 'A') {
                                return elem.parentElement;
                            } else {
                                return findA(elem.parentElement);
                            }
                        }
                        return null;
                    },
                    element = findA(event.target);
                if (element != null && element.nodeName == 'A' && element.href) {
                    window.open(element.href, element.target || '_blank');
                }
            },
            render: function () {
                return React.createElement('div', {
                    className: 'card-offer',
                    onClick: this.handleClick,
                    dangerouslySetInnerHTML: {__html: this.props.content}
                });
            }
        });
        var TimerCountdown = React.createClass({
            displayName: "TimerCountdown",
            getInitialState: function getInitialState() {
                return {
                    time: null
                };
            },
            throttle: function throttle(callback, limit) {
                var wait = false;
                return function() {
                    if (!wait) {
                        callback.call();
                        wait = true;
                        setTimeout(function() {
                            wait = false;
                        }, limit);
                    }
                };
            },
            getTimeRemaining: function getTimeRemaining(dateEnd) {
                var diff = dateEnd - Date.now();

                function zero(value) {
                    return Math.max(value, 0);
                }

                var days = Math.floor(diff / (1000 * 60 * 60 * 24)),
                    hours = Math.floor((diff / (1000 * 60 * 60)) % 24),
                    minutes = Math.floor((diff / 1000 / 60) % 60),
                    seconds = Math.floor((diff / 1000) % 60);
                return [diff, days, hours, minutes, seconds].map(zero);
            },
            componentDidMount: function componentDidMount() {
                this._mounted = true;
                this._tick = this.throttle(this._tick, 1000);
                this.tick();
            },
            componentWillUnmount: function componentWillUnmount() {
                this._mounted = false;
                cancelAnimationFrame(this.tick);
            },
            safeSetState: function safeSetState() {
                if (this._mounted) {
                    this.setState.apply(this, arguments);
                }
            },
            formatTime: function formatTime(hours, minutes, seconds) {
                return [hours, minutes, seconds]
                .map(function(value) {
                    return ("0" + value).slice(-2);
                })
                .join(":");
            },
            tick: function tick() {
                this._tick();

                requestAnimationFrame(this.tick);
            },
            _tick: function _tick() {
                var remaining = this.getTimeRemaining(new Date(this.props.date * 1000)),
                    total = remaining[0],
                    days = remaining[1],
                    hours = remaining[2],
                    minutes = remaining[3],
                    seconds = remaining[4],
                    time = [];

                if (total !== 0 && days > 0) {
                    time.push(
                        Translator.transChoice(
                            "interval_short.days",
                            days,
                            {
                                count: days
                            },
                            "messages"
                        )
                    );
                    time.push(Translator.trans("and.text", "messages"));
                }

                time.push(this.formatTime(hours, minutes, seconds));
                this.safeSetState({
                    time: time.join(" ")
                });
            },
            render: function render() {
                return React.createElement("strong", null, this.state.time);
            }
        });
        var Account = React.createClass({
            displayName: "Account",
            render: function render() {
                var TimeAgo = React.addons.TimeAgo;
                var account = this.props.account,
                    href = $state.href("index.accounts.account-details", {
                        Id: account.KEY
                    }),
                    lastChange = LastChangeDate(account.LastChangeDate * 1000);
                var classes = classNames({
                    account: true,
                    up: lastChange && account.LastChangeRaw > 0 && !account.Disabled,
                    down: lastChange && account.LastChangeRaw < 0 && !account.Disabled,
                    error: account.Error && !account.Disabled,
                    disable: account.Disabled,
                    monitored: typeof account.BalanceWatchEndDate === "number"
                });
                var color = {
                        true: "green",
                        false: "blue"
                    }[account.LastChangeRaw > 0],
                    icon = {
                        true: "icon-green-up-s",
                        false: "icon-blue-down-s"
                    }[account.LastChangeRaw > 0],
                    expiration = {
                        expired: "icon-red-error-s",
                        soon: "icon-warning",
                        far: "icon-green-check-s"
                    }[account.ExpirationState],
                    expirationIconSmall = {
                        expired: "icon-small-error",
                        soon: "icon-small-warning",
                        far: "icon-small-check"
                    }[account.ExpirationState];

                var pay = function pay() {
                        $state.go("index.pay", {
                            start: "start"
                        });
                    },
                    range = function range(size) {
                        return Array.apply(null, Array(Math.abs(size)));
                    };

                return React.createElement(
                    "a",
                    {
                        href: href,
                        className: classes
                    },
                    React.createElement(
                        "div",
                        {
                            className: "flex"
                        },
                        React.createElement(
                            "div",
                            {
                                className: "title"
                            },
                            React.createElement("h3", {
                                dangerouslySetInnerHTML: {
                                    __html: this.props.account.DisplayName
                                }
                            }),
                            React.createElement(
                                "p",
                                {
                                    className: "email"
                                },
                                this.props.account.Login
                            )
                        ),
                        React.createElement(
                            "div",
                            {
                                className: "account-row"
                            },
                            this.props.account.Login
                        ),
                        React.createElement(
                            "div",
                            {
                                className: "status-row"
                            },
                            account.EliteStatus
                                ? React.createElement(
                                "div",
                                {
                                    className: "status-blk"
                                },
                                React.createElement("span", {
                                    dangerouslySetInnerHTML: {
                                        __html: account.EliteStatus.Name
                                    }
                                }),
                                React.createElement(
                                    "div",
                                    {
                                        className: "progress-row"
                                    },
                                    React.createElement(
                                        "ul",
                                        null,
                                        range(account.EliteStatus.LevelsCount).map(function(x, i) {
                                            return React.createElement(
                                                "li",
                                                {
                                                    key: i
                                                },
                                                React.createElement("span", {
                                                    className:
                                                        i < account.EliteStatus.Rank ? "blue" : "silver"
                                                })
                                            );
                                        })
                                    )
                                )
                                )
                                : null
                        ),
                        account.ExpirationUpgrade
                            ? React.createElement(
                            "div",
                            {
                                className: "expiration-row"
                            },
                            account.ExpirationDate
                                ? platform.cordova
                                ? React.createElement("p", {
                                    dangerouslySetInnerHTML: {
                                        __html: account.ExpirationDate.fmt
                                    },
                                    onClick: pay
                                })
                                : React.createElement("p", {
                                    dangerouslySetInnerHTML: {
                                        __html: account.ExpirationDate.fmt
                                    }
                                })
                                : null
                            )
                            : React.createElement(
                            "div",
                            {
                                className: "expiration-row"
                            },
                            React.createElement("i", {
                                className: expiration
                            }),
                            account.ExpirationDate
                                ? account.ExpirationState !== "expired" &&
                                account.ExpirationDate.ts
                                ? React.createElement(TimeAgo, {
                                    date: account.ExpirationDate.ts * 1000,
                                    shortDate: true,
                                    locale: UserSettings.get("locale")
                                })
                                : React.createElement("p", {
                                    dangerouslySetInnerHTML: {
                                        __html: account.ExpirationDate.fmt
                                    }
                                })
                                : null
                            ),
                        React.createElement(
                            "div",
                            {
                                className: "balance"
                            },
                            account.Disabled
                                ? React.createElement("i", {
                                    className: "icon-warning"
                                })
                                : null,
                            React.createElement("div", {
                                className: "balance-row",
                                dangerouslySetInnerHTML: {
                                    __html: this.props.account.Balance
                                }
                            }),
                            account.LastChangeRaw && lastChange
                                ? React.createElement(
                                "p",
                                {
                                    className: color
                                },
                                React.createElement("span", null, account.LastChange),
                                React.createElement("i", {
                                    className: icon
                                })
                                )
                                : null,
                            React.createElement(
                                "div",
                                {
                                    className: "expire-row"
                                },
                                React.createElement("i", {
                                    className: expirationIconSmall
                                }),
                                account.ExpirationDate
                                    ? account.ExpirationState !== "expired" &&
                                    account.ExpirationDate.ts
                                    ? React.createElement(TimeAgo, {
                                        date: account.ExpirationDate.ts * 1000,
                                        shortDate: true,
                                        locale: UserSettings.get("locale")
                                    })
                                    : React.createElement("span", {
                                        dangerouslySetInnerHTML: {
                                            __html: account.ExpirationDate.fmt
                                        }
                                    })
                                    : null
                            )
                        ),
                        React.createElement(
                            "div",
                            {
                                className: "readmore"
                            },
                            React.createElement("i", {
                                className: "icon-next-arrow"
                            })
                        )
                    ),
                    typeof account.BalanceWatchEndDate === "number"
                        ? React.createElement(
                        "div",
                        {
                            className: "monitoring"
                        },
                        React.createElement(
                            "div",
                            null,
                            Translator.trans(
                                "account.list.balancewatch.monitored-changes",
                                {},
                                "messages"
                            )
                        ),
                        React.createElement(
                            "div",
                            null,
                            Translator.trans("remaining-time-col", {}, "messages"),
                            " ",
                            React.createElement(TimerCountdown, {
                                date: account.BalanceWatchEndDate
                            })
                        )
                        )
                        : null
                );
            }
        });
        var SubAccount = React.createClass({
            render: function render() {
                var TimeAgo = React.addons.TimeAgo;
                var parent = this.props.parent,
                    account = this.props.account,
                    href = $state.href('index.accounts.account-details', {Id: parent.KEY, subId: account.SubAccountID}),
                    lastChange = LastChangeDate(account.LastChangeDate * 1000);
                var classes = classNames({
                        'sub-account': true,
                        'up': lastChange && account.LastChangeRaw > 0 && !account.Disabled,
                        'down': lastChange && account.LastChangeRaw < 0 && !account.Disabled,
                        'error': account.Error && !account.Disabled,
                        'disable': account.Disabled
                    }),
                    color = {true: 'green', false: 'blue'}[account.LastChangeRaw > 0],
                    icon = {true: 'icon-green-up-s', false: 'icon-blue-down-s'}[account.LastChangeRaw > 0],
                    expiration = {
                        'expired': 'icon-red-error-s',
                        'soon': 'icon-warning',
                        'far': 'icon-green-check-s'
                    }[account.ExpirationState],
                    expirationIconSmall = {
                        'expired': 'icon-small-error',
                        'soon': 'icon-small-warning',
                        'far': 'icon-small-check'
                    }[account.ExpirationState];
                var displayName = account.DisplayName;

                if (account.ParentAccount) {
                    displayName = account.CouponType;
                    href = $state.href('index.accounts.account-details', {Id: 'c' + account.ID});
                }

                return React.createElement(
                    'a',
                    {href: href, className: classes},
                    React.createElement(
                        'div',
                        {className: 'item'},
                        React.createElement(
                            'div',
                            {className: 'flex'},
                            React.createElement(
                                'div',
                                {className: 'title'},
                                React.createElement('h3', {dangerouslySetInnerHTML: {__html: displayName}})
                            ),
                            React.createElement(
                                'div',
                                {className: 'expiration-row'},
                                React.createElement('i', {className: expiration}),
                                account.ExpirationDate ? account.ExpirationState !== 'expired' && account.ExpirationDate.ts ? React.createElement(TimeAgo, {
                                    date: account.ExpirationDate.ts * 1000,
                                    shortDate: true,
                                    locale: UserSettings.get('locale')
                                }) : React.createElement('span', {dangerouslySetInnerHTML: {__html: account.ExpirationDate.fmt}}) : null
                            ),
                            React.createElement(
                                'div',
                                {className: 'balance'},
                                account.Disabled ? React.createElement('i', {className: 'icon-warning'}) : null,
                                account.Balance ? React.createElement('div', {
                                    className: 'balance-row',
                                    dangerouslySetInnerHTML: {__html: account.Balance}
                                }) : null,
                                account.LastChangeRaw && LastChangeDate(account.LastChangeDate * 1000, 7 * 24) ? React.createElement(
                                    'p',
                                    {className: color},
                                    React.createElement(
                                        'span',
                                        null,
                                        account.LastChange
                                    ),
                                    React.createElement('i', {className: icon})
                                ) : null,
                                React.createElement(
                                    'div',
                                    {className: 'expire-row'},
                                    React.createElement('i', {className: expirationIconSmall}),
                                    account.ExpirationDate ? account.ExpirationState !== 'expired' && account.ExpirationDate.ts ? React.createElement(TimeAgo, {
                                        date: account.ExpirationDate.ts * 1000, shortDate: true,
                                        locale: UserSettings.get('locale')
                                    }) : React.createElement('span', {
                                        dangerouslySetInnerHTML: {__html: account.ExpirationDate.fmt}
                                    }) : null
                                )
                            ),
                            React.createElement(
                                'div',
                                {className: 'readmore'},
                                React.createElement('i', {className: 'icon-next-arrow'})
                            )
                        )
                    )
                );
            }
        });
        var SearchBar = React.createClass({
            displayName: "SearchBar",
            handleChange: function () {
                this.props.onInput(this.refs.search.getDOMNode().value);
            },
            focusInput: function () {
                this.props.onInput('');
                this.refs.search.getDOMNode().focus();
            },
            render: function () {
                var placeholder = Translator.trans('award.account.list.search.placeholder');
                return (
                    React.createElement("form", {className: "search", autoComplete: "off"},
                        React.createElement("input", {
                            ref: "search",
                            type: "text",
                            placeholder: placeholder,
                            value: this.props.text,
                            onChange: this.handleChange,
                            autoComplete: "disabled"
                        }),
                        this.props.text.length < 1 ? React.createElement("button", {
                            type: "submit",
                            onClick: function () {
                                return false;
                            }
                        }, placeholder) : React.createElement("a", {
                            onClick: this.focusInput,
                            className: "clear"
                        }, React.createElement("i", {
                            className: "icon-clear-d"
                        }))
                    )
                );
            }
        });
        var SearchNoResults = React.createClass({
            displayName: "SearchNoResults",
            render: function () {
                var message = Translator.trans('award.account.list.search.not-found');
                return (
                    React.createElement("div", {className: "not-found"},
                        React.createElement("i", {className: "icon-warning"}),
                        React.createElement("p", null, message)
                    )
                );
            }
        });
        var AccountsList = React.createClass({
            displayName: 'AccountsList',
            accountList: [],
            getDefaultProps: function () {
                return {
                    infinite: true,
                    accounts: [],
                    searchList: [],
                    counters: {},
                    limit: 25
                };
            },
            getInitialState: function () {
                var props = this.props;
                this.accountList = this.displayList(props.accounts, props.rawAccounts, props.providerKinds, props.providerIcons);
                if (props.infinite) {
                    return {
                        more: this.accountList.length > props.limit,
                        items: this.accountList.slice(0, props.limit),
                        limit: props.limit
                    }
                } else {
                    return {
                        items: this.accountList
                    }
                }
            },
            componentWillReceiveProps: function (props) {
                var search = props.search.trim().toLowerCase();
                if (search.length > 0) {
                    this.accountList = this.displayList(props.searchList, props.rawAccounts, props.providerKinds, props.providerIcons, search);
                } else {
                    this.accountList = this.displayList(props.accounts, props.rawAccounts, props.providerKinds, props.providerIcons);
                }
                var items = this.accountList.slice(0, props.limit);
                this.setState({
                    items: items,
                    more: items.length < this.accountList.length,
                    limit: props.limit
                });
            },
            loadMore: function () {
                this.setState({
                    items: this.accountList.slice(0, this.state.limit + this.props.limit),
                    more: this.state.items.length < this.accountList.length,
                    limit: this.state.limit + this.props.limit
                });
            },
            renderList: function (list) {
                if (!list && !list.length) {
                    return [];
                }
                var rows = [];
                for (var i = 0, l = list.length, row; i < l, row = list[i]; i++) {
                    rows.push(React.createElement(row.component, row.props));
                }
                return rows;
            },
            search: function (accounts, search) {
                var self = this;

                return accounts.filter(function (account) {
                    var additionalResult = false;

                    if (account.hasOwnProperty('SubAccountsArray') && account.SubAccountsArray.length > 0) {
                        var result = self.search(account.SubAccountsArray, search);

                        additionalResult = result.length > 0;
                    }

                    return (account.DisplayName || '').toLowerCase().indexOf(search.toLowerCase()) > -1 || additionalResult;
                });
            },
            displayList: function (accounts, rawAccounts, providerKinds, providerIcons, search) {
                accounts = accounts.filter(function (account) {
                    return !account.ParentAccount
                });
                accounts = search ? this.search(accounts, search) : accounts;

                if (!accounts && !accounts.length) {
                    return [];
                }
                var rows = [];

                for (var i = 0, l = accounts.length, account; i < l, account = accounts[i]; i++) {

                    if (i === 0 || i > 0 && account.Kind !== accounts[i - 1].Kind) {
                        if (!search && providerKinds[account.Kind]) {
                            if (providerKinds[account.Kind].ad) {
                                rows.push({
                                    component: CardOffer,
                                    props: {
                                        content: providerKinds[account.Kind].ad,
                                        key: account.ID + '.cardoffer'
                                    }
                                });
                            }
                            rows.push({
                                component: Title,
                                props: {
                                    name: providerKinds[account.Kind].Name,
                                    icon: providerIcons[account.Kind],
                                    key: account.ID + '.title',
                                    search: account.DisplayName
                                }
                            });
                        }
                        if (i === 0 || (search && rows.length < 1)) {
                            rows.push({
                                component: Header,
                                props: {key: account.ID + '.header', search: account.DisplayName}
                            });
                        }
                    }

                    if (
                        (i === 0 || (search && rows.length < 2)) ||
                        (accounts[i - 1] && (account.UserAgentID || account.UserID) !== (accounts[i - 1].UserAgentID || accounts[i - 1].UserID)) ||
                        (!search && account.Kind !== accounts[i - 1].Kind)
                    ) {
                        rows.push({
                            component: UserBlock,
                            props: {
                                userName: account.UserName,
                                familyName: account.FamilyName,
                                key: account.ID + '.user',
                                search: account.DisplayName
                            }
                        });
                    }

                    if (!account.ParentAccount)
                        rows.push({
                            component: Account,
                            props: {
                                account: account,
                                key: account.ID + '.account',
                                search: account.DisplayName
                            }
                        });

                    if (account.hasOwnProperty('SubAccountsArray') && account.SubAccountsArray.length > 0) {
                        for (var k = 0, j = account.SubAccountsArray.length, subAccount; k < j, subAccount = account.SubAccountsArray[k]; k++) {
                            rows.push({
                                component: SubAccount,
                                props: {
                                    account: subAccount,
                                    parent: account,
                                    key: account.ID + '.subaccount' + k
                                }
                            });
                        }
                    }
                }
                return rows;
            },
            render: function () {
                var InfiniteScroll = React.addons.InfiniteScroll;
                var props = this.props, page = [];
                if (this.state.items.length < 1 && this.props.search.length > 0) {
                    return (React.createElement(SearchNoResults, null));
                }
                if (props.infinite) {
                    page.push(React.createElement(InfiniteScroll, {
                        loadMore: this.loadMore,
                        hasMore: this.state.more,
                        key: "infinite-scroll",
                        parent: '.content'
                    }, this.renderList(this.state.items)));
                } else {
                    page.push(this.renderList(this.state.items));
                }
                if (this.state.items.length == this.accountList.length && this.props.search.length < 1) {
                    var button = Translator.trans('account.buttons.add'), href = $state.href('index.accounts.add');
                    var totals = Translator.trans('award.account.list.totals'),
                        hrefTotals = $state.href('index.accounts.totals');
                    var balance = window.hasOwnProperty('Intl') && window.Intl.hasOwnProperty('NumberFormat') ? $filter('IntlNumber')(Math.round(props.counters.totals), props.locale) : $filter('number')(Math.round(props.counters.totals));
                    page.push(React.createElement("a", {
                        key: "total-accounts",
                        href: hrefTotals,
                        className: "total-accounts"
                    }, React.createElement("div", {className: "item"}, React.createElement("div", {className: "flex"}, React.createElement("div", {className: "title"}, totals), React.createElement("div", {className: "balance"}, balance), React.createElement("div", {className: "readmore"}, React.createElement("i", {className: "icon-next-arrow"}))))));
                    page.push(React.createElement("a", {
                        key: "button-add",
                        href: href,
                        className: "add-account"
                    }, React.createElement("i", {className: "icon-add"}), React.createElement("span", null, button)));
                }
                return (React.createElement("div", null, page));
            }
        });
        var AdvertiserDisclosure = React.createClass({
            displayName: "AdvertiserDisclosure",

            render: function () {
                var title = Translator.trans(/** @Desc("Advertiser Disclosure") */'advertiser.disclosure');
                return React.createElement(
                    "div",
                    {className: "advertise-disclosure"},
                    React.createElement(
                        "a",
                        {className: "silver-link", href: this.props.link, target: "_system"},
                        title
                    )
                );
            }
        });
        return React.createClass({
            displayName: "SearchAccountList",
            getInitialState: function () {
                return {
                    search: ''
                };
            },
            handleSearch: function (text) {
                this.setState({search: text});
            },
            render: function () {
                var search = this.state.search;
                return React.createElement(
                    "div",
                    null,
                    this.props.blog ? React.createElement(RssFeed, this.props.blog) : null,
                    React.createElement(SearchBar, {
                        text: this.state.search,
                        onInput: this.handleSearch
                    }),
                    this.props.disclosureLink && !search ? React.createElement(AdvertiserDisclosure, {link: this.props.disclosureLink}) : null,
                    React.createElement(AccountsList, React.__spread({}, this.props, {
                        search: search
                    }))
                );
            }
        });
    }]);

    angular.module('AwardWalletMobile').directive('rssFeed', ['RssFeed', function (RssFeed) {
        return {
            restrict: 'AE',
            scope: {
                data: '='
            },
            link: function (scope, element, attrs) {
                React.render(React.createElement(RssFeed, scope.data), element[0]);
                scope.$on('$destroy', function () {
                    React.unmountComponentAtNode(element[0]);
                });
                scope.$on('database:updated', function () {
                    React.render(React.createElement(RssFeed, scope.data), element[0]);
                });
            }
        };
    }]);

    angular.module('AwardWalletMobile').directive('accountsList', ['AccountList', 'SearchAccountList', function (AccountList, SearchAccountList) {
        return {
            restrict: 'AE',
            scope: {
                infinite: '@',
                providerIcons: '=',
                blog: '=',
                disclosure: '=',
                locale: '='
            },
            link: function (scope, element, attrs) {
                React.render(React.createElement(SearchAccountList, {
                    infinite: scope.infinite === String(true),
                    rawAccounts: AccountList.getAccounts(),
                    accounts: AccountList.getList(),
                    searchList: AccountList.getSearchList(),
                    counters: AccountList.getCounters(),
                    providerKinds: AccountList.getProviderKinds(),
                    providerIcons: scope.providerIcons,
                    blog: scope.blog,
                    disclosureLink: scope.disclosure,
                    locale: scope.locale
                }), element[0]);
                scope.$on('$destroy', function () {
                    React.unmountComponentAtNode(element[0]);
                });
                scope.$on('accountList:update', function () {
                    React.render(React.createElement(SearchAccountList, {
                        infinite: scope.infinite === String(true),
                        rawAccounts: AccountList.getAccounts(),
                        accounts: AccountList.getList(),
                        searchList: AccountList.getSearchList(),
                        counters: AccountList.getCounters(),
                        providerKinds: AccountList.getProviderKinds(),
                        providerIcons: scope.providerIcons,
                        blog: scope.blog,
                        disclosureLink: scope.disclosure,
                        locale: scope.locale
                    }), element[0]);
                });
            }
        };
    }]);
})(window, document, angular, React);
