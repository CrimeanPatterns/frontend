(function (window, document, angular, React) {

    angular.module('AwardWalletMobile').service('SpendAnalysisRowTitle', ['$state', function($state) {
        return class extends React.Component {

            constructor(props) {
                super(props);

                this.openDetails = this.openDetails.bind(this);
            }

            openDetails() {
                const {name, merchant, formData} = this.props;

                $state.go('index.spend-analysis.details', {title: name, merchant, formData});
            }

            render() {
                const {name, value, style, merchant} = this.props;
                const touchable = children => {
                    if (_.isString(merchant)) {
                        return (
                            <a className="flex" href="#" onClick={this.openDetails}>
                                {children}
                            </a>
                        );
                    } else {
                        return <div className="flex">{children}</div>;
                    }
                };

                return touchable([
                        <div>
                            <span className="bold">{name}</span>
                        </div>,

                        _.isString(value)
                            ? <div><span className="bold">{value}</span></div>
                            : null
                ]);
            }
        };
    }]);

    angular.module('AwardWalletMobile').service('SpendAnalysisSectionRow', [
        'SpendAnalysisRowTitle',
        'AccountHistoryRowBalance',
        'AccountHistoryRowString',
        'AccountHistoryRowEarningPotential',
        function(SpendAnalysisRowTitle, AccountHistoryRowBalance, AccountHistoryRowString, AccountHistoryRowEarningPotential) {
            return class extends React.Component {
                components = {
                    title: SpendAnalysisRowTitle,
                    balance: AccountHistoryRowBalance,
                    string: AccountHistoryRowString,
                    earning_potential: AccountHistoryRowEarningPotential
                };

                render() {
                    const {index: sectionIndex, blocks, style, extraData: parentExtraData, ...props} = this.props;
                    const className = classNames({
                        'transaction': true,
                        'increased': style === 'positive',
                        'decreased': style === 'negative'
                    });

                    return (
                        <div className={className}>
                            <div className="transaction__details">
                                {
                                    blocks.map((row, index) => {
                                        const Component = this.components[row.kind];
                                        const {extraData, ...rest} = row;

                                        if (row.kind === 'earning_potential') {
                                            rest.routeName = 'index.spend-analysis.account-history-offer';
                                        }

                                        return <Component
                                            {...rest}
                                            key={`section-${sectionIndex}-row-${index}`}
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

    angular.module('AwardWalletMobile').service('SpendAnalysisOverview', [
        'AccountHistoryDateTitle',
        'SpendAnalysisSectionRow',
        '$filter',
        '$state',
        'UserSettings',
        'Chart',
        function(AccountHistoryDateTitle, SpendAnalysisSectionRow, $filter, $state, UserSettings, Chart) {

            return class extends React.Component {

                constructor(props) {
                    super(props);

                    this.openMerchantsLookup = this.openMerchantsLookup.bind(this);
                }

                openMerchantsLookup() {
                    $state.go('unauth.merchants.lookup');
                }

                renderTitle() {
                    const {title, subTitle} = this.props;

                    if (!_.isString(title) || !_.isString(subTitle)) return null;

                    return (
                        <div className="block-title account-name">
                            <div className="prev"><i className="icon-spend-analysis"/></div>
                            <div className="title">
                                <h3>{title}</h3>
                            </div>
                            <div className="block-title__item">
                                <div className="prev"><i className="icon-warning"/></div>
                                <div className="title">
                                    <p>{subTitle}</p>
                                </div>
                            </div>
                        </div>
                    );
                }

                renderChart() {
                    const {charts} = this.props;
                    let gridMax = Math.max(...charts.map(chart => {
                        return Math.max(parseInt(chart.value), parseInt(chart.potentialValue));
                    }));
                    gridMax = Math.round(gridMax + gridMax / 2);

                    return charts.map(chart => {
                        const {name, potentialValue: earningPotential, value: pointsEarned} = chart;

                        return (
                            <div className="chart">
                                <div className="chartLabel">
                                    {name}
                                </div>
                                <div style={{position: 'relative', height: '64px'}}>
                                    <Chart
                                        data={{
                                            datasets: [
                                                {
                                                    backgroundColor: '#4684c4',
                                                    borderWidth: 0,
                                                    hoverBorderWidth: 0,
                                                    data: [parseInt(pointsEarned)],
                                                    datalabels: {
                                                        color: '#4684c4'
                                                    }
                                                },
                                                {
                                                    backgroundColor: '#4dbfa2',
                                                    borderWidth: 0,
                                                    hoverBorderWidth: 0,
                                                    data: [parseInt(earningPotential)],
                                                    datalabels: {
                                                        color: '#4dbfa2'
                                                    }
                                                }
                                            ]
                                        }}
                                        options={{
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            layout: {
                                                padding: {
                                                    left: 0,
                                                    right: 0,
                                                    top: 0,
                                                    bottom: 0
                                                }
                                            },
                                            animation: {
                                                duration: 500,
                                            },
                                            legend: {
                                                display: false,
                                            },
                                            title: {
                                                display: false,
                                            },
                                            tooltips: {
                                                enabled: false,
                                            },
                                            hover: {mode: null},
                                            scales:{
                                                xAxes: [{
                                                    ticks: {
                                                        display: false,
                                                        min: 0,
                                                        max: gridMax,
                                                    },
                                                    gridLines: {
                                                        color: 'rgb(243, 244, 245)',
                                                        tickMarkLength: 1,
                                                    },
                                                }],
                                                yAxes: [{
                                                    stacked: false,
                                                    barPercentage: 0.9,
                                                    categoryPercentage: 1.0,
                                                    ticks: {
                                                        fontSize: 0,
                                                    },
                                                }],
                                            },
                                            plugins: {
                                                datalabels: {
                                                    anchor: 'end',
                                                    align: 'end',
                                                    formatter(value) {
                                                        return $filter('IntlNumber')(value, UserSettings.get("locale"));
                                                    },
                                                    font: {
                                                        weight: 'bold'
                                                    }
                                                }
                                            }
                                        }}
                                        type='horizontalBar'
                                    />
                                </div>
                            </div>
                        );
                    });
                }

                renderHeader() {
                    return [
                        this.renderTitle(),
                        <div className="chart-info">
                            <div className="chart-info__block blue">Points Earned</div>
                            <div className="chart-info__block green">Earning Potential</div>
                        </div>,
                        this.renderChart(),
                    ];
                }

                renderFooter() {
                    const {notice, rows} = this.props;

                    if (!(_.isArray(rows) && rows.length > 0) && !_.isString(notice)) return null;

                    const className = classNames({
                        'bottom-link': true,
                        'end-list': !this.isEmptyList()
                    });

                    return (
                        <a href="#" onClick={this.openMerchantsLookup} className={className}>
                            Trying to figure out which credit card to use with a specific merchant to maximize your rewards? Try our new merchant category lookup tool!
                        </a>
                    );
                }

                renderEmptyList() {
                    const {notice} = this.props;

                    if (!this.isEmptyList()) return null;

                    return (
                        <div className="not-found">
                            <i className="icon-warning"/>
                            <p>{notice}</p>
                        </div>
                    );
                }

                isEmptyList() {
                    const {notice, rows} = this.props;

                    return (!_.isArray(rows) || rows.length === 0) && _.isString(notice);
                }

                keyExtractor(index, row) {
                    if (_.isObject(row) && row.date) {
                        const {d, m} = row.date;

                        return `${d}-${m}-${index}`;
                    }

                    return String(index);
                }

                renderRow(index, row) {
                    const {formData, offerFilterIds, rows} = this.props;
                    const {kind, value} = row;
                    const key = this.keyExtractor(index, row);

                    switch (kind) {
                        case 'row':
                            const hasArrow = rows[index - 1] && rows[index - 1].kind === 'date';

                            return (
                                <SpendAnalysisSectionRow
                                    {...row}
                                    formData={formData}
                                    offerFilterIds={offerFilterIds}
                                    index={index}
                                    arrow={hasArrow}
                                    extraData={{source: 'spend-analysis&mid=mobile'}}
                                    key={key} />
                            );
                        case 'date':
                            return (
                                <AccountHistoryDateTitle title={value} key={key} />
                            );
                    }
                }

                render() {
                    const {charts, rows} = this.props;

                    return (
                        <div className="content-item">
                            <div className="content-item__wrap">
                                {
                                    Array.isArray(charts) && charts.length > 0 && this.renderHeader()
                                }

                                {
                                    rows.map((row, i) => this.renderRow(i, row))
                                }

                                {this.renderEmptyList()}
                            </div>
                            {this.renderFooter()}
                        </div>
                    );
                }
            };
    }]);

    angular.module('AwardWalletMobile').directive('spendAnalysisOverview', ['SpendAnalysisOverview', function(SpendAnalysisOverview) {
        return {
            restrict: 'AE',
            scope: {
                formData: '=',
                data: '='
            },
            link: function (scope, element, attrs) {
                React.render(
                    <SpendAnalysisOverview {...{formData: scope.formData, ...scope.data}}></SpendAnalysisOverview>,
                    element[0]
                );

                scope.$on('$destroy', function () {
                    React.unmountComponentAtNode(element[0]);
                });
            }
        };
    }]);

    angular.module('AwardWalletMobile').service('SpendAnalysisDetails', [
        'AccountHistoryDateTitle',
        'AccountHistorySectionRow',
        function(AccountHistoryDateTitle, AccountHistorySectionRow) {
            return class extends React.Component {

                keyExtractor(index, row) {
                    if (_.isObject(row) && row.date) {
                        const {d, m} = row.date;

                        return `${d}-${m}-${index}`;
                    }

                    return String(index);
                }

                renderRow(index, row) {
                    const {rows} = this.props;
                    const {kind, value} = row;
                    const key = this.keyExtractor(index, row);

                    switch (kind) {
                        case 'row':
                            const hasArrow = rows[index - 1] && rows[index - 1].kind === 'date';

                            return (
                                <AccountHistorySectionRow
                                    {...row}
                                    index={index}
                                    arrow={hasArrow}
                                    extraData={{source: 'spend-analysis&mid=mobile'}}
                                    routeName={'index.spend-analysis.account-history-offer'}
                                    key={key} />
                            );
                        case 'date':
                            return (
                                <AccountHistoryDateTitle title={value} key={key} />
                            );
                    }
                }

                render() {
                    const {rows} = this.props;

                    return (
                        <div className="content-item">
                            {
                                rows.map((row, i) => this.renderRow(i, row))
                            }
                        </div>
                    );
                }
            };
    }]);

    angular.module('AwardWalletMobile').directive('spendAnalysisDetails', ['SpendAnalysisDetails', function(SpendAnalysisDetails) {
        return {
            restrict: 'AE',
            scope: {
                data: '='
            },
            link: function (scope, element, attrs) {
                React.render(
                    <SpendAnalysisDetails rows={scope.data}></SpendAnalysisDetails>,
                    element[0]
                );

                scope.$on('$destroy', function () {
                    React.unmountComponentAtNode(element[0]);
                });
            }
        };
    }]);

})(window, document, angular, React);