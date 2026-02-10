/** @jsx React.DOM */

var Title = React.createClass({
    render: function() {
        return (<div className="block-title">
            <i className="icon-arrow-down"></i>
            <h3>{this.props.name}</h3>
            <div className="prev"><i className={this.props.icon}></i></div>
        </div>);
    }
});
var Header = React.createClass({
    render: function() {
        var header = {
            program: Translator.trans('award.account.list.column.program'),
            account: Translator.trans('award.account.list.column.account'),
            status: Translator.trans('award.account.list.column.status'),
            expire: Translator.trans('award.account.list.column.expire'),
            balance: Translator.trans('award.account.list.column.balance')
        };
        return (<div className="title-list">
            <div className="flex">
                <div className="title">{header.program}</div>
                <div className="account-row">{header.account}</div>
                <div className="status-row">{header.status}</div>
                <div className="expiration-row">{header.expire}</div>
                <div className="balance">{header.balance}</div>
            </div>
        </div>);
    }
});
var UserBlock = React.createClass({
    render: function() {
        return (<div className="user-block">
            <div className="item">
                <div className="user-item">
                    <i className="icon-user"></i>
                    {
                        this.props.familyName ?
                            <h3>{this.props.familyName} <span className="silver">({this.props.userName})</span></h3> :
                            <h3>{this.props.userName}</h3>
                    }
                </div>
            </div>
        </div>);
    }
});
var CardOffer = React.createClass({
    handleClick: function(event){
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
        }, element = findA(event.target);
        if (element != null && element.nodeName == 'A' && element.href) {
            window.open(element.href, element.target || '_blank');
        }
    },
    render: function() {
        return (<div className="card-offer" onClick={this.handleClick} dangerouslySetInnerHTML={{__html:this.props.content}}></div>);
    }
});
var TimerCountdown = React.createClass({
    getInitialState: function() {
        return {
            time: null
        };
    },
    throttle: function(callback, limit) {
        var wait = false;
        return function () {
            if (!wait) {
                callback.call();
                wait = true;
                setTimeout(function () {
                    wait = false;
                }, limit);
            }
        }
    },
    getTimeRemaining: function(dateEnd) {
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
    componentDidMount: function() {
        this._mounted = true;
        this._tick = this.throttle(this._tick, 1000);
        this.tick();
    },
    componentWillUnmount: function() {
        this._mounted = false;
        cancelAnimationFrame(this.tick);
    },
    safeSetState: function() {
        if (this._mounted) {
            this.setState.apply(this, arguments);
        }
    },
    formatTime: function(hours, minutes, seconds) {
        return [hours, minutes, seconds].map(function(value) {
            return ('0' + value).slice(-2);
        }).join(':');
    },
    tick: function() {
        this._tick();
        requestAnimationFrame(this.tick);
    },
    _tick: function() {
        var remaining = this.getTimeRemaining(new Date(this.props.date * 1000)),
            total = remaining[0],
            days = remaining[1],
            hours = remaining[2],
            minutes = remaining[3],
            seconds = remaining[4],
            time = [];

        if (total !== 0 && days > 0) {
            time.push(Translator.transChoice('interval_short.days', days, {count: days}, 'messages'));
            time.push(Translator.trans('and.text', 'messages'));
        }

        time.push(this.formatTime(hours, minutes, seconds));

        this.safeSetState({
            time: time.join(' ')
        });
    },
    render() {
        return (<strong>{this.state.time}</strong>);
    }
});
var Account = React.createClass({
    render: function() {
        var TimeAgo = React.addons.TimeAgo;
        var account = this.props.account,
            href = $state.href('index.accounts.account-details', {Id: account.KEY}),
            lastChange = LastChangeDate(account.LastChangeDate * 1000);
        var classes = classNames({
            'account': true,
            'up': lastChange && account.LastChangeRaw > 0 && !account.Disabled,
            'down': lastChange && account.LastChangeRaw < 0 && !account.Disabled,
            'error': account.Error && !account.Disabled,
            'disable': account.Disabled,
            'monitored': typeof account.BalanceWatchEndDate === 'number',
        });
        var color = ({true: 'green', false: 'blue'})[account.LastChangeRaw > 0],
            icon = ({true: 'icon-green-up-s', false: 'icon-blue-down-s'})[account.LastChangeRaw > 0],
            expiration = ({
                'expired': 'icon-red-error-s',
                'soon': 'icon-warning',
                'far': 'icon-green-check-s'
            })[account.ExpirationState],
            expirationIconSmall = ({
                'expired': 'icon-small-error',
                'soon': 'icon-small-warning',
                'far': 'icon-small-check'
            })[account.ExpirationState];
        var pay = function () {
                $state.go('index.pay', {start: 'start'})
            },
            range = function (size) {
                return Array.apply(null, Array(Math.abs(size)));
            };
        return (
            <a href={href} className={classes}>
                <div className="flex">
                    <div className="title">
                        <h3 dangerouslySetInnerHTML={{__html: this.props.account.DisplayName}}/>
                        <p className="email">{this.props.account.Login}</p>
                    </div>
                    <div className="account-row">{this.props.account.Login}</div>
                    <div className="status-row">
                        {
                            account.EliteStatus ?
                                (
                                    <div className="status-blk">
                                        <span dangerouslySetInnerHTML={{__html: account.EliteStatus.Name}}/>
                                        <div className="progress-row">
                                            <ul>
                                                {
                                                    range(account.EliteStatus.LevelsCount).map(function (x, i) {
                                                        return (<li key={i}><span
                                                            className={i < account.EliteStatus.Rank ? 'blue' : 'silver'}/>
                                                        </li>)
                                                    })
                                                }
                                            </ul>
                                        </div>
                                    </div>
                                ) : null
                        }
                    </div>
                    {
                        account.ExpirationUpgrade ?
                            (
                                <div className="expiration-row">
                                    {
                                        account.ExpirationDate ?
                                            (
                                                platform.cordova ?
                                                (
                                                    <p dangerouslySetInnerHTML={{__html: account.ExpirationDate.fmt}}
                                                       onClick={pay}/>
                                                ) : (
                                                    <p dangerouslySetInnerHTML={{__html: account.ExpirationDate.fmt}}/>
                                                )
                                            ) : null
                                    }
                                </div>
                            ) : (
                                <div className="expiration-row">
                                    <i className={expiration}/>
                                    {
                                        account.ExpirationDate ?
                                            account.ExpirationState !== 'expired' && account.ExpirationDate.ts ?
                                                <TimeAgo date={account.ExpirationDate.ts * 1000} shortDate={true} locale={UserSettings.get('locale')}/> :
                                                <p dangerouslySetInnerHTML={{__html: account.ExpirationDate.fmt}}/>
                                            : null
                                    }
                                </div>
                            )
                    }
                    <div className="balance">
                        {
                            account.Disabled ? (<i className="icon-warning"/>) : null
                        }
                        <div className="balance-row" dangerouslySetInnerHTML={{__html: this.props.account.Balance}}/>
                        {
                            account.LastChangeRaw && lastChange ?
                                (
                                    <p className={color}>
                                        <span>{account.LastChange}</span>
                                        <i className={icon}/>
                                    </p>
                                ) : null
                        }
                        {
                            <div className="expire-row">
                                <i className={expirationIconSmall}/>
                                {
                                    account.ExpirationDate ?
                                        account.ExpirationState !== 'expired' && account.ExpirationDate.ts ?
                                            <TimeAgo date={account.ExpirationDate.ts * 1000} shortDate={true} locale={UserSettings.get('locale')}/> :
                                            <span dangerouslySetInnerHTML={{__html: account.ExpirationDate.fmt}}/>
                                        : null
                                }
                            </div>
                        }
                    </div>
                    <div className="readmore"><i className="icon-next-arrow"/></div>
                </div>
                {
                    typeof account.BalanceWatchEndDate === 'number' ?
                        (<div className="monitoring">
                            <div>{Translator.trans('account.list.balancewatch.monitored-changes', {}, 'messages')}</div>
                            <div>{Translator.trans('remaining-time-col', {}, 'messages')} <TimerCountdown date={account.BalanceWatchEndDate} /></div>
                        </div>)
                        : null
                }
            </a>
        );
    }
});
var SubAccount = React.createClass({
    render: function() {
        var TimeAgo = React.addons.TimeAgo;
        var parent = this.props.parent,
            account = this.props.account,
            href = $state.href('index.accounts.account-details', {Id:parent.KEY, subId:account.SubAccountID}),
            lastChange = LastChangeDate(account.LastChangeDate * 1000);
        var classes = classNames({
                'sub-account': true,
                'up': lastChange && account.LastChangeRaw > 0 && !account.Disabled,
                'down': lastChange && account.LastChangeRaw < 0 && !account.Disabled,
                'error': account.Error && !account.Disabled,
                'disable': account.Disabled
            }),
            color = ({true:'green', false:'blue'})[account.LastChangeRaw > 0],
            icon = ({true:'icon-green-up-s', false:'icon-blue-down-s'})[account.LastChangeRaw > 0],
            expiration = ({
                'expired': 'icon-red-error-s',
                'soon': 'icon-warning',
                'far': 'icon-green-check-s'
            })[account.ExpirationState],
            expirationIconSmall = ({
                'expired': 'icon-small-error',
                'soon': 'icon-small-warning',
                'far': 'icon-small-check'
            })[account.ExpirationState];
        var displayName = account.DisplayName;

        if (account.ParentAccount) {
            displayName = account.CouponType;
            href = $state.href('index.accounts.account-details', {Id: 'c' + account.ID});
        }

        return (
            <a href={href} className={classes}>
                <div className="item">
                    <div className="flex">
                        <div className="title">
                            <h3 dangerouslySetInnerHTML={{__html: displayName}}/>
                        </div>
                        <div className="expiration-row">
                            <i className={expiration}/>
                            {
                                account.ExpirationDate ?
                                    account.ExpirationState !== 'expired' && account.ExpirationDate.ts ?
                                        <TimeAgo date={account.ExpirationDate.ts * 1000} shortDate={true} locale={UserSettings.get('locale')}/> :
                                        <span dangerouslySetInnerHTML={{__html: account.ExpirationDate.fmt}}/>
                                    : null
                            }
                        </div>
                        {
                            <div className="balance">
                                {
                                    account.Disabled ? (<i className="icon-warning"/>) : null
                                }
                                {
                                    account.Balance ?
                                        <div className="balance-row"
                                             dangerouslySetInnerHTML={{__html: account.Balance}}/>
                                        :
                                        null
                                }
                                {
                                    account.LastChangeRaw && LastChangeDate(account.LastChangeDate * 1000, 7 * 24) ?
                                        (
                                            <p className={color}>
                                                <span>{account.LastChange}</span>
                                                <i className={icon}/>
                                            </p>
                                        ) : null
                                }
                                <div className="expire-row">
                                    <i className={expirationIconSmall}/>
                                    {
                                        account.ExpirationDate ?
                                            account.ExpirationState !== 'expired' && account.ExpirationDate.ts ?
                                                <TimeAgo date={account.ExpirationDate.ts * 1000} shortDate={true}
                                                         locale={UserSettings.get('locale')}/> :
                                                <span
                                                    dangerouslySetInnerHTML={{__html: account.ExpirationDate.fmt}}/>
                                            : null
                                    }
                                </div>
                            </div>
                        }
                        <div className="readmore"><i className="icon-next-arrow"/></div>
                    </div>
                </div>
            </a>
        );
    }
});
var SearchBar = React.createClass({
    handleChange: function() {
        this.props.onInput(this.refs.search.getDOMNode().value);
    },
    focusInput: function() {
        this.props.onInput('');
        this.refs.search.getDOMNode().focus();
    },
    render: function() {
        var placeholder = Translator.trans('award.account.list.search.placeholder');
        return (
            <form autoComplete={"off"} className="search">
                <input autoComplete="disabled" ref="search" type="text" placeholder={placeholder} value={this.props.text} onChange={this.handleChange}/>
                    {this.props.text.length < 1 ? <button type="submit" onClick={function(){return false;}}>{placeholder}</button> : <a onClick={this.focusInput} className="clear"><i
                    className="icon-clear-d"></i></a>}
            </form>
        );
    }
});
var SearchNoResults = React.createClass({
    render: function() {
        var message = Translator.trans('award.account.list.search.not-found');
        return (
            <div className="not-found">
                <i className="icon-warning"></i>
                <p>{message}</p>
            </div>
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
        accounts = accounts.filter(function(account){ return !account.ParentAccount});
        accounts = search ? this.search(accounts, search) : accounts;

        if (!accounts && !accounts.length) {
            return [];
        }
        var rows = [];

        for (var i = 0, l = accounts.length, account; i < l, account = accounts[i]; i++) {

            if (i === 0 || i > 0 && account.Kind !== accounts[i - 1].Kind) {
                if (!search) {
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
    render: function() {
        var InfiniteScroll = React.addons.InfiniteScroll;
        var props = this.props,  page = [];
        if(this.state.items.length < 1 && this.props.search.length > 0){
            return (<SearchNoResults/>);
        }
        if(props.infinite){
            page.push(<InfiniteScroll loadMore={this.loadMore} hasMore={this.state.more} key="infinite-scroll" parent=".content" >{this.renderList(this.state.items)}</InfiniteScroll>);
        }else{
            page.push(this.renderList(this.state.items));
        }
        if(this.state.items.length == this.accountList.length && this.props.search.length < 1) {
            var button = Translator.trans('account.buttons.add'), href = $state.href('index.accounts.add');
            var totals = Translator.trans('award.account.list.totals'), hrefTotals = $state.href('index.accounts.totals');
            var balance = window.hasOwnProperty('Intl') && window.Intl.hasOwnProperty('NumberFormat') ? $filter('IntlNumber')(Math.round(props.counters.totals), props.locale) : $filter('number')(Math.round(props.counters.totals));
            page.push(<a key="total-accounts" href={hrefTotals} className="total-accounts"><div className="item"><div className="flex"><div className="title">{totals}</div><div className="balance">{balance}</div><div className="readmore"><i className="icon-next-arrow"></i></div></div></div></a>);
            page.push(<a key="button-add" href={href} className="add-account"><i className="icon-add"></i><span>{button}</span></a>);
        }
        return (<div>{page}</div>);
    }
});
var RssFeed = React.createClass({
    render: function() {
        var href = this.props.link;
        if(platform.cordova) {
            if(href.indexOf('?') > 0) {
                href += '&fromapp=1';
            }else{
                href += '?fromapp=1';
            }
        }
        return (
            <div className="rss-feed">
                <a href={href} target="_system">{this.props.title}</a>
            </div>
        );
    }
});
var AdvertiserDisclosure = React.createClass({
    render: function() {
        var title = Translator.trans(/** @Desc("Advertiser Disclosure") */ 'advertiser.disclosure');
        return (
            <div className="advertise-disclosure">
                <a className="silver-link" href={this.props.link} target="_system">{title}</a>
            </div>
        );
    }
});
var SearchAccountList = React.createClass({
    getInitialState: function() {
        return {
            search: ''
        };
    },
    handleSearch: function(text){
        this.setState({search:text});
    },
    render: function() {
        var search = this.state.search;
        return (
            <div>
                {this.props.blog ? <RssFeed {...this.props.blog}/> : null}
                <SearchBar
                text={this.state.search}
                onInput={this.handleSearch}
                />
                {this.props.disclosureLink && !search ? <AdvertiserDisclosure link={this.props.disclosureLink}></AdvertiserDisclosure> : null}
                <AccountsList
                            {...this.props}
                search={search}
                />
            </div>
        );
    }
});
