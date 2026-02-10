(function (window, document, angular, React) {

    angular.module('AwardWalletMobile').service('AccountHistoryDateTitle', [function() {
        return class extends React.Component {
            render() {
                const {title} = this.props;

                return (
                    <div className="user-block">
                        <div className="item">
                            <div className="user-item">
                                <h3>{title.toUpperCase()}</h3>
                            </div>
                        </div>
                    </div>
                );
            }
        };
    }]);

    angular.module('AwardWalletMobile').service('AccountHistoryRowTitle', [function() {
        return class extends React.Component {
            render() {
                const {name, value, positive, last} = this.props;

                return (
                    <div className="flex">
                        <div>
                            <span className="bold">{name}</span>
                        </div>
                        {
                            angular.isString(value) && <div>
                                <span className="bold">{value}</span>
                            </div>
                        }
                    </div>
                )
            }
        };
    }]);

    angular.module('AwardWalletMobile').service('AccountHistoryRowBalance', [function() {
        return class extends React.Component {
            render() {
                const {name, value, multiplier, positive, last} = this.props;

                return (
                    <div className="flex">
                        <div>
                            <span className="transaction__caption">{name}</span>
                        </div>
                        <div>
                            <span className="bold">{value}</span>
                            {
                                angular.isString(multiplier) &&
                                <span className="up-counter">{multiplier}</span>
                            }
                        </div>
                    </div>
                )
            }
        };
    }]);

    angular.module('AwardWalletMobile').service('AccountHistoryRowString', [function() {
        return class extends React.Component {
            render() {
                const {name, value, multiplier, positive, last} = this.props;

                return (
                    <div className="flex">
                        <div>
                            <span className="transaction__caption">{name}</span>
                        </div>
                        <div>
                            {value}
                        </div>
                    </div>
                )
            }
        };
    }]);

    angular.module('AwardWalletMobile').service('AccountHistoryRowEarningPotential', ['$state', function($state) {
        return class extends React.Component {

            constructor(props) {
                super(props);

                this.openHistoryOffer = this.openHistoryOffer.bind(this);
            }

            openHistoryOffer() {
                const {routeName = 'index.accounts.account-history-offer', uuid, extraData, offerFilterIds} = this.props;

                $state.go(routeName, {
                    uuid,
                    extraData: {...extraData, offerFilterIds},
                });
            }

            render() {
                const {type, name, value, multiplier, uuid} = this.props;
                let {color = 'silver'} = this.props;

                if (_.isNull(color)) color = 'silver';

                if (type === 'offer') {
                    const clickable = children => {
                        if (_.isString(uuid)) {
                            return <a className={`flex ${color}`} href="#" onClick={this.openHistoryOffer}>
                                {children}
                            </a>;
                        }

                        return <div className={`flex ${color}`}>
                            {children}
                        </div>;
                    };

                    return clickable(
                        [
                            <div>
                                <span className="transaction__caption">{name}</span>
                            </div>,
                            <div>
                                <span className="bold value">{value}</span>
                                {
                                    angular.isString(multiplier) &&
                                    <span className="up-counter">{multiplier}</span>
                                }
                            </div>
                        ]
                    );
                }

                return <div className="flex silver">
                    {
                        angular.isString(name) &&
                        <div>
                            <span className="transaction__caption">{name}</span>
                        </div>
                    }
                    <div>
                        <i className="icon-like"></i>
                    </div>
                </div>
            }
        };
    }]);

    angular.module('AwardWalletMobile').service('AccountHistorySectionRow', [
        'AccountHistoryRowTitle',
        'AccountHistoryRowBalance',
        'AccountHistoryRowString',
        'AccountHistoryRowEarningPotential',
        function(AccountHistoryRowTitle, AccountHistoryRowBalance, AccountHistoryRowString, AccountHistoryRowEarningPotential) {
            return class extends React.Component {

                components = {
                    title: AccountHistoryRowTitle,
                    balance: AccountHistoryRowBalance,
                    string: AccountHistoryRowString,
                    earning_potential: AccountHistoryRowEarningPotential
                };

                render () {
                    const {blocks, style, date, arrow, extraData: parentExtraData, ...props} = this.props;
                    const {d, m} = date;
                    const key = `${d}-${m}`;
                    const className = classNames({
                        'transaction': true,
                        'increased': style === 'positive',
                        'decreased': style === 'negative'
                    });

                    return (
                        <div className={className}>
                            <div className="transaction__date">
                                <span>{d}</span>
                                {m.toUpperCase()}
                            </div>
                            <div className="transaction__details">
                                {
                                    blocks.map((row, index) => {
                                        const Component = this.components[row.kind];
                                        const {extraData, ...rest} = row;

                                        return <Component
                                            {...rest}
                                            key={`${key}-section-row-${index}`}
                                            style={style}
                                            last={index === blocks.length - 1}
                                            {...props}
                                            extraData={{...extraData, ...parentExtraData}}
                                        />;
                                    })
                                }
                            </div>
                        </div>
                    )
                }
            };
        }
    ]);

    angular.module('AwardWalletMobile').service('AccountHistoryList', [
        'AccountHistorySectionRow',
        'AccountHistoryDateTitle',
        function (AccountHistorySectionRow, AccountHistoryDateTitle) {

            class AccountHistorySearchBar extends React.Component {

                constructor(props) {
                    super(props);

                    this.handleChange = this.handleChange.bind(this);
                    this.focusInput = this.focusInput.bind(this);
                }

                handleChange() {
                    this.props.onInput(this.refs.search.getDOMNode().value);
                }

                focusInput() {
                    this.props.onInput('');
                    this.refs.search.getDOMNode().focus();
                }

                render() {
                    const placeholder = Translator.trans('search');

                    return (
                        <div className="search">
                            <input
                                ref="search"
                                type="text"
                                placeholder={placeholder}
                                value={this.props.text}
                                onChange={this.handleChange}
                            />

                            {
                                this.props.text.length < 1 &&
                                <button type="submit" onClick={function () {
                                    return false;
                                }}>{placeholder}</button>
                            }
                            {
                                this.props.text.length > 0 &&
                                <a onClick={this.focusInput} className="clear"><i className="icon-clear-d"></i></a>
                            }
                        </div>
                    );
                }
            }

            class AccountHistorySearchNoResults extends React.Component {

                render() {
                    const message = Translator.trans('account.history.not-exist', {}, 'messages');

                    return (
                        <div className="not-found">
                            <i className="icon-warning"></i>
                            <p>{message}</p>
                        </div>
                    );
                }

            }

            return class extends React.Component {

                initialHistory = null;
                mounted = false;

                static defaultProps = {
                    loadHistory: function () {
                    },
                    limit: 25
                };

                constructor(props) {
                    super(props);

                    this.state = {
                        history: null,
                        search: '',
                        nextPageToken: null,
                        loading: false,
                        showSpinner: false,
                        hasMore: false
                    };

                    this.onSearchChange = this.onSearchChange.bind(this);
                    this.handleLoadMore = this.handleLoadMore.bind(this);
                }

                componentDidMount() {
                    this.loadHistory();
                }

                loadHistory(data, loadMore) {
                    const {loadHistory} = this.props;

                    this.setState({
                        loading: true,
                        hasMore: false
                    });

                    loadHistory(data).then((response) => {
                        if (angular.isObject(response)) {
                            const {history} = this.state;
                            const {rows, nextPageToken} = response;

                            if (angular.isArray(rows)) {
                                let data;

                                if (this.initialHistory === null) {
                                    this.initialHistory = rows;
                                }

                                if (loadMore && angular.isArray(history)) {
                                    data = history.concat(rows);
                                } else {
                                    data = rows;
                                }

                                this.setState({
                                    history: data,
                                    nextPageToken,
                                    loading: false,
                                    hasMore: nextPageToken !== null
                                });
                            }
                        }
                    });
                }

                handleLoadMore() {
                    const {search, nextPageToken} = this.state;

                    if (nextPageToken) {
                        this.loadHistory({
                            descriptionFilter: search,
                            nextPage: nextPageToken
                        }, true);
                    }
                }

                search() {
                    const {search} = this.state;

                    if (search && search.length >= 3) {
                        this.loadHistory({
                            descriptionFilter: search
                        });
                    }
                }

                onSearchChange(search) {
                    if (angular.isArray(this.initialHistory)) {
                        if (search && search.length > 0) {
                            this.setState({search}, () => {
                                this.search();
                            });
                        } else {
                            this.setState({
                                search,
                                history: this.initialHistory,
                            });
                        }
                    }
                }

                renderList(list) {
                    const rows = [];

                    if (!list && !list.length) {
                        return rows;
                    }

                    for (let i = 0, l = list.length, row; i < l, row = list[i]; i++) {
                        if (row.kind === 'row') {
                            const hasArrow = list[i - 1] && list[i - 1].kind === 'date';

                            rows.push(<AccountHistorySectionRow {...row} arrow={hasArrow} key={`section-row-${i}`}/>);
                        } else {
                            rows.push(<AccountHistoryDateTitle title={row.value} key={`date-title-${i}`}/>);
                        }
                    }

                    return rows;
                }

                renderLoading() {
                    const {loading} = this.state;
                    const Spinner = React.addons.Spinner;

                    return loading && (
                        <div className="spinner-container">
                            <Spinner/>
                        </div>
                    )
                }

                render() {
                    const {history, search, hasMore} = this.state;
                    const InfiniteScroll = React.addons.InfiniteScroll;

                    return (
                        <div>
                            {
                                history !== null &&
                                <AccountHistorySearchBar
                                    text={search}
                                    onInput={this.onSearchChange}
                                />
                            }
                            {
                                history && history.length < 1 &&
                                search && search.length > 0 &&
                                <AccountHistorySearchNoResults/>
                            }
                            <div className="transaction-list">
                                {
                                    history &&
                                    history.length > 0 &&
                                    <InfiniteScroll
                                        hasMore={hasMore}
                                        loadMore={this.handleLoadMore}
                                        key="infinite-scroll"
                                        parent=".content">
                                        {this.renderList(history)}
                                    </InfiniteScroll>
                                }
                                {
                                    this.renderLoading()
                                }
                            </div>
                        </div>
                    );
                }

            };
        }
    ]);

    angular.module('AwardWalletMobile').directive('accountHistoryList', ['$q', 'Account', 'AccountHistoryList', function ($q, Account, AccountHistoryList) {

        function loadHistory(data, accountId, subId) {
            var deferred = $q.defer();

            Account.getResource().post({
                type: 'account',
                action: 'history',
                subAction: accountId,
                accountId: subId
            }, data, function (response) {
                deferred.resolve(response);
            }, function () {
                deferred.reject();
            });

            return deferred.promise;
        }

        return {
            restrict: 'AE',
            scope: {
                accountId: '=',
                subId: '='
            },
            link: function (scope, element, attrs) {
                function loadAccountHistory(data) {
                    return loadHistory(data, scope.accountId, scope.subId);
                }

                React.render(React.createElement(AccountHistoryList, {
                    loadHistory: loadAccountHistory
                }), element[0]);

                scope.$on('$destroy', function () {
                    React.unmountComponentAtNode(element[0]);
                });
            }
        };
    }]);
})(window, document, angular, React);