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

    angular.module('AwardWalletMobile').service('BookingList', ['$state', '$filter', 'Booking', function ($state, $filter, Booking) {
        var BookingListRequest = React.createClass({
            displayName: 'BookingListRequest',

            render: function render() {
                var props = this.props,
                    request = props.request;
                var requestLink = $state.href('index.booking.request.details', {Id: request.id}),
                    classesLink = classNames({'not-active': request.active != true, 'booking-request': true}),
                    icons = {
                        status: 'icon-' + request.statusIcon,
                        trip: request.kindIcon
                    },
                    dates = {
                        start: typeof request.startDate.fmt != 'string' ? $filter('fmt')(request.startDate.fmt) : null
                    },
                    translations = {
                        'last-update': Translator.trans('booking.table.headers.last-update', {}, 'booking'),
                        'new-message': Translator.trans(/** @Desc("new message") */'new.message', {}, 'booking')
                    };
                if (request.statusCode == -1) {
                    requestLink = $state.href('index.booking.request.not-verified', {Id: request.id});
                }
                var TimeAgo = React.addons.TimeAgo;
                return React.createElement(
                    'a',
                    {href: requestLink, className: classesLink},
                    React.createElement(
                        'table',
                        null,
                        React.createElement(
                            'tr',
                            null,
                            React.createElement(
                                'td',
                                null,
                                React.createElement(
                                    'h3',
                                    null,
                                    request.listTitle
                                ),
                                React.createElement(
                                    'div',
                                    {className: 'info'},
                                    React.createElement(
                                        'div',
                                        {className: 'status'},
                                        React.createElement('i', {className: icons.status}),
                                        React.createElement(
                                            'p',
                                            null,
                                            request.status,
                                            React.createElement(
                                                'span',
                                                {className: 'silver'},
                                                '#' + request.id
                                            )
                                        )
                                    ),
                                    React.createElement(
                                        'div',
                                        {className: 'flight'},
                                        React.createElement('i', {className: icons.trip}),
                                        React.createElement(
                                            'p',
                                            null,
                                            dates.start ? React.createElement(
                                                    'span',
                                                    {className: 'date'},
                                                    $filter('date')(dates.start, 'd')
                                                ) : React.createElement(
                                                    'span',
                                                    {className: 'date'},
                                                    request.startDate.fmt.d
                                                ),
                                            dates.start ? $filter('date')(dates.start, 'MMM') : request.startDate.fmt.m
                                        )
                                    ),
                                    request.newMessage == false ? React.createElement(
                                            'div',
                                            {className: 'action'},
                                            React.createElement('i', {className: 'icon-read-message'}),
                                            React.createElement(
                                                'span',
                                                {className: 'silver bold uppercase'},
                                                translations['last-update'] + ':'
                                            ),
                                            typeof request.lastUpdateDate.fmt == 'string' ? React.createElement('p', {
                                                    className: 'uppercase',
                                                    dangerouslySetInnerHTML: {__html: request.lastUpdateDate.fmt}
                                                }) : React.createElement(
                                                    'p',
                                                    {className: 'uppercase'},
                                                    $filter('date')(request.lastUpdateDate.ts * 1000, 'shortDateTime')
                                                )
                                        ) : React.createElement(
                                            'div',
                                            {className: 'action'},
                                            React.createElement('i', {className: 'icon-new-message'}),
                                            React.createElement(
                                                'span',
                                                {className: 'orange bold uppercase'},
                                                translations['new-message']
                                            ),
                                            React.createElement(
                                                'p',
                                                {className: 'bold'},
                                                React.createElement(TimeAgo, {date: request.lastUpdateDate.ts * 1000})
                                            )
                                        )
                                )
                            ),
                            React.createElement(
                                'td',
                                {className: 'readmore'},
                                React.createElement('i', {className: 'icon-next-arrow'})
                            )
                        )
                    )
                );
            }
        });

        return React.createClass({
            displayName: 'BookingList',
            bookingList: [],
            getDefaultProps: function () {
                return {
                    infinite: true,
                    data: {},
                    limit: 25
                };
            },
            getInitialState: function () {
                var props = this.props,
                    data = props.data,
                    requests = data.requests,
                    sort = data.sort;
                this.bookingList = this.displayList(sort, requests);
                if (props.infinite) {
                    return {
                        more: this.bookingList.length > props.limit,
                        items: this.bookingList.slice(0, props.limit),
                        limit: this.props.limit
                    };
                } else {
                    return {
                        items: this.bookingList
                    };
                }
            },
            componentWillReceiveProps: function (props) {
                this.bookingList = this.displayList(props.data.sort, props.data.requests);
                var items = this.bookingList.slice(0, props.limit);
                this.setState({
                    items: items,
                    more: items.length < this.bookingList.length,
                    limit: props.limit
                });
            },
            loadMore: function () {
                this.setState({
                    items: this.bookingList.slice(0, this.state.limit + this.props.limit),
                    more: this.state.items.length < this.bookingList.length,
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
            displayList: function (sort, requests) {
                if (!sort && !sort.length) {
                    return [];
                }

                var rows = [];

                for (var i = 0, l = sort.length, request; i < l, request = requests[sort[i]]; i++) {
                    rows.push({
                        component: BookingListRequest,
                        props: { request: request, key: 'booking-request_' + request.id }
                    });
                }

                return rows;
            },
            render: function () {
                var InfiniteScroll = React.addons.InfiniteScroll,
                    page = [];
                var props = this.props;
                if (props.infinite) {
                    page.push(React.createElement(
                        InfiniteScroll,
                        { loadMore: this.loadMore, hasMore: this.state.more, key: 'booking-infinite-scroll',
                            parent: '.content' },
                        this.renderList(this.state.items)
                    ));
                } else {
                    page.push(this.renderList(this.displayList(props.data.sort, props.data.requests)));
                }
                return React.createElement(
                    'div',
                    { className: 'booking-list' },
                    page
                );
            }
        });
    }]);

    angular.module('AwardWalletMobile').directive('bookingList', ['BookingList', function (BookingList) {
        return {
            restrict: 'E',
            scope: {
                requests: '='
            },
            link: function (scope, element, attrs) {
                var unbind = scope.$watch(function(){
                    return scope.requests;
                }, function(data){
                    if(data && data.sort && data.sort.length > 0){
                        React.render(React.createElement(BookingList, {
                            data: data
                        }), element[0]);
                    }
                });
                scope.$on('$destroy', function () {
                    unbind();
                    React.unmountComponentAtNode(element[0]);
                });
            }
        };
    }]);

})(window, document, angular, React);