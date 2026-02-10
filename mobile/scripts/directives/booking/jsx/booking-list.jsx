/** @jsx React.DOM */

var BookingListRequest = React.createClass({
    render: function () {
        var props = this.props, request = props.request;
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
        if(request.statusCode == -1) {
            requestLink = $state.href('index.booking.request.not-verified', {Id: request.id});
        }
        var TimeAgo = React.addons.TimeAgo;
        return (
            <a href={requestLink} className={classesLink}>
                <table>
                    <tr>
                        <td>
                            <h3>{request.listTitle}</h3>
                            <div className="info">
                                <div className="status">
                                    <i className={icons.status}></i>
                                    <p>{request.status}<span className="silver">{'#' + request.id}</span></p>
                                </div>
                                <div className="flight">
                                    <i className={icons.trip}></i>
                                    <p>
                                        {dates.start ? <span className="date">{$filter('date')(dates.start, 'd')}</span> : <span className="date">{request.startDate.fmt.d}</span>}
                                        {dates.start ? $filter('date')(dates.start, 'MMM') : request.startDate.fmt.m}
                                    </p>
                                </div>
                                {
                                    request.newMessage == false ?
                                        (<div className="action">
                                        <i className="icon-read-message"></i>
                                        <span className="silver bold uppercase">{translations['last-update'] + ':'}</span>
                                            {typeof request.lastUpdateDate.fmt == 'string' ?
                                            <p className="uppercase" dangerouslySetInnerHTML={{__html: request.lastUpdateDate.fmt}}></p> :
                                        <p className="uppercase">{$filter('date')(request.lastUpdateDate.ts * 1000, 'shortDateTime')}</p>}
                                        </div>) : (<div className="action">
                                        <i className="icon-new-message"></i>
                                        <span className="orange bold uppercase">{translations['new-message']}</span>
                                        <p className="bold">
                                            <TimeAgo date={request.lastUpdateDate.ts * 1000}></TimeAgo>
                                        </p>
                                    </div>)
                                }
                            </div>
                        </td>
                        <td className="readmore">
                            <i className="icon-next-arrow"></i>
                        </td>
                    </tr>
                </table>
            </a>
        );
    }
});
var BookingList = React.createClass({
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
        var props = this.props, data = props.data, requests = data.requests, sort = data.sort;
        this.bookingList = this.displayList(sort, requests);
        if (props.infinite) {
            return {
                more: this.bookingList.length > props.limit,
                items: this.bookingList.slice(0, props.limit),
                limit: this.props.limit
            }
        } else {
            return {
                items: this.bookingList
            }
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
                props: {request: request, key: 'booking-request_' + request.id}
            });
        }

        return rows;
    },
    render: function () {
        var InfiniteScroll = React.addons.InfiniteScroll, page = [];
        var props = this.props;
        if (props.infinite) {
            page.push(<InfiniteScroll loadMore={this.loadMore} hasMore={this.state.more} key="booking-infinite-scroll"
                                      parent=".content">{this.renderList(this.state.items)}</InfiniteScroll>);
        } else {
            page.push(this.renderList(this.displayList(props.data.sort, props.data.requests)));
        }
        return (<div className="booking-list">{page}</div>);
    }
});