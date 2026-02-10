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

    angular.module('AwardWalletMobile').service('BookingDetails', ['$state', '$filter', function ($state, $filter) {

        var TableRows = {
            Text: React.createClass({
                displayName: 'BookingDetailsTableText',
                getDefaultProps: function () {
                    return {
                        field: {}
                    };
                },
                render: function () {
                    return React.createElement(
                        'div',
                        {className: 'info'},
                        this.props.field.value
                    );
                }
            }),
            TimeAgo: React.createClass({
                displayName: 'BookingDetailsTableTimeAgo',
                getDefaultProps: function () {
                    return {
                        field: {}
                    };
                },
                render: function () {
                    var TimeAgo = React.addons.TimeAgo;
                    return React.createElement(
                        'div',
                        {className: 'info'},
                        React.createElement(TimeAgo, {date: this.props.field.value.ts * 1000})
                    );
                }
            })
        };
        var BookingDetailsFields = {
            Header: React.createClass({
                displayName: 'BookingDetailsHeader',
                getDefaultProps: function () {
                    return {
                        field: {}
                    };
                },
                render: function () {
                    return React.createElement(
                        "div",
                        { className: "block-title" },
                        React.createElement("i", { className: "icon-arrow-down" }),
                        React.createElement("h3", { dangerouslySetInnerHTML: { __html: this.props.field.name } })
                    );
                }
            }),
            SubHeader: React.createClass({
                displayName: 'BookingDetailsSubHeader',
                getDefaultProps: function () {
                    return {
                        field: {}
                    };
                },
                render: function () {
                    return React.createElement(
                        "div",
                        { className: "user-block" },
                        React.createElement(
                            "div",
                            { className: "item" },
                            React.createElement(
                                "div",
                                { className: "user-item" },
                                this.props.field.icon ? React.createElement("i", { className: this.props.field.icon }) : null,
                                React.createElement(
                                    "h3",
                                    null,
                                    this.props.field.name
                                )
                            )
                        )
                    );
                }
            }),
            Field: React.createClass({
                displayName: 'BookingDetailsField',
                getDefaultProps: function () {
                    return {
                        field: {}
                    };
                },
                render: function () {
                    return React.createElement(
                        "div",
                        { className: "flex-row" },
                        React.createElement(
                            "div",
                            { className: "title" },
                            this.props.field.name
                        ),
                        React.createElement(
                            "div",
                            { className: "info" },
                            this.props.field.value
                        )
                    );
                }
            }),
            Note: React.createClass({
                displayName: 'BookingDetailsNote',
                getDefaultProps: function () {
                    return {
                        field: {}
                    };
                },
                render: function () {
                    var classes = classNames({
                        'flex-row': true,
                        'full-size': this.props.field.value.length > 50
                    });
                    return React.createElement(
                        "div",
                        { className: classes },
                        React.createElement(
                            "div",
                            { className: "title" },
                            this.props.field.name
                        ),
                        React.createElement("div", { className: "info", dangerouslySetInnerHTML: { __html: this.props.field.value } })
                    );
                }
            }),
            TimeAgo: React.createClass({
                displayName: 'BookingDetailsTimeAgo',
                getDefaultProps: function () {
                    return {
                        field: {}
                    };
                },
                render: function () {
                    var TimeAgo = React.addons.TimeAgo;
                    return React.createElement(
                        "div",
                        { className: "flex-row" },
                        React.createElement(
                            "div",
                            { className: "title" },
                            this.props.field.name
                        ),
                        React.createElement(
                            "div",
                            { className: "info" },
                            React.createElement(TimeAgo, { date: this.props.field.value.ts * 1000 })
                        )
                    );
                }
            }),
            Table: React.createClass({
                displayName: 'BookingDetailsTable',
                getDefaultProps: function () {
                    return {
                        field: {}
                    };
                },
                rows: {
                    text: TableRows.Text,
                    timeAgo: TableRows.TimeAgo,
                    empty: React.createElement("div", { className: "info" })
                },
                render: function () {
                    var rows = this.rows,
                        count = this.props.count,
                        field = this.props.field;
                    return React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "div",
                            { className: "block" },
                            field.headers.map(function (column, i) {
                                return React.createElement(
                                    "div",
                                    { className: "title", key: ['booking-details-table-', count, '-column_', i].join('') },
                                    column
                                );
                            })
                        ),
                        field.rows.map(function (row, i) {
                            return React.createElement(
                                "div",
                                { className: "block", key: ['booking-details-table-', count, '-row_', i].join('') },
                                row.map(function (field, j) {
                                    if (field && field.type && rows.hasOwnProperty(field.type)) {
                                        return React.createElement(rows[field.type], {
                                            field: field,
                                            key: ['booking-details-table-', count, '-row_', i, '-column_', j].join('')
                                        });
                                    } else {
                                        return React.createElement("div", { className: "info", key: ['booking-details-table-', count, '-row_', i, '-column_', j, '_empty'].join('') });
                                    }
                                })
                            );
                        })
                    );
                }
            })
        };

        return React.createClass({
            displayName: 'BookingDetails',
            getDefaultProps: function () {
                return {
                    fields: []
                };
            },
            components: {
                field: BookingDetailsFields.Field,
                header: BookingDetailsFields.Header,
                subheader: BookingDetailsFields.SubHeader,
                note: BookingDetailsFields.Note,
                timeAgo: BookingDetailsFields.TimeAgo,
                toggle: true,
                table: BookingDetailsFields.Table
            },
            render: function () {
                var components = this.components,
                    props = this.props;
                var rows = [];
                props.fields.map(function (field, j) {
                    if (components.hasOwnProperty(field.type)) {
                        if (j == 0 && field.type == 'header' && props.showMore) {
                            rows.push(React.createElement(components[field.type], {
                                field: field,
                                key: 'booking-details-field_' + j,
                                count: j
                            }));
                        } else {
                            if (field.type != 'toggle') {
                                rows.push(React.createElement(components[field.type], {
                                    field: field,
                                    key: 'booking-details-field_' + j,
                                    count: j
                                }));
                            } else {
                                if (field.hasOwnProperty('phone')) {
                                    rows.push(React.createElement(
                                        'div',
                                        { className: 'row-container', key: 'booking-details-field_phone_' + j },
                                        field.phone.map(function (f, k) {
                                            return React.createElement(components[f.type], {
                                                field: f,
                                                key: 'booking-details-field_' + j + '_phone_' + k,
                                                count: j + (k + 1)
                                            });
                                        })
                                    ));
                                }
                                if (field.hasOwnProperty('tablet')) {
                                    rows.push(React.createElement(
                                        'div',
                                        { className: 'table-container', key: 'booking-details-field_table_' + j },
                                        field.tablet.map(function (f, k) {
                                            if (components.hasOwnProperty(f.type)) {
                                                return React.createElement(components[f.type], {
                                                    field: f,
                                                    key: 'booking-details-field_' + j + '_table_' + k,
                                                    count: j + (k + 1)
                                                });
                                            }
                                        })
                                    ));
                                }
                            }
                        }
                    }
                });
                return React.createElement(
                    'div',
                    { className: 'booker-info' },
                    rows
                );
            }
        });
    }]);

    angular.module('AwardWalletMobile').directive('bookingDetails', ['BookingDetails', 'Booking', function (BookingDetails, Booking) {
        return {
            restrict: 'E',
            scope: {
                requestId: '='
            },
            link: function (scope, element, attrs) {
                var request = Booking.getRequest(scope.requestId);
                if (request && request.details)
                    React.render(React.createElement(BookingDetails, {
                        fields: request.details
                    }), element[0]);
                scope.$on('$destroy', function () {
                    request = null;
                    React.unmountComponentAtNode(element[0]);
                });
                scope.$on('booking:update', function () {
                    request = Booking.getRequest(scope.requestId);
                    if (request && request.details)
                        React.render(React.createElement(BookingDetails, {
                            fields: request.details
                        }), element[0]);
                });
            }
        };
    }]);

})(window, document, angular, React);