/** @jsx React.DOM */

var TableRows = {
    Text: React.createClass({
        displayName: 'BookingDetailsTableText',
        getDefaultProps: function () {
            return {
                field: {}
            };
        },
        render: function () {
            return (<div className="info">
                {this.props.field.value}
            </div>);
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
            return (<div className="info">
                <TimeAgo date={this.props.field.value.ts * 1000}></TimeAgo>
            </div>);
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
            return (<div className="block-title">
                <i className="icon-arrow-down"></i>
                <h3 dangerouslySetInnerHTML={{__html:this.props.field.name}}></h3>
            </div>);
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
            return (<div className="user-block">
                <div className="item">
                    <div className="user-item">
                        {this.props.field.icon ? <i className={this.props.field.icon}></i> : null}
                        <h3>{this.props.field.name}</h3>
                    </div>
                </div>
            </div>);
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
            return (<div className="flex-row">
                <div className="title">{this.props.field.name}</div>
                <div className="info">{this.props.field.value}</div>
            </div>);
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
            return (<div className={classes}>
                <div className="title">{this.props.field.name}</div>
                <div className="info" dangerouslySetInnerHTML={{__html:this.props.field.value}}></div>
            </div>);
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
            return (<div className="flex-row">
                <div className="title">{this.props.field.name}</div>
                <div className="info"><TimeAgo date={this.props.field.value.ts * 1000}></TimeAgo></div>
            </div>);
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
            empty: (<div className="info"></div>)
        },
        render: function () {
            var rows = this.rows, count = this.props.count, field = this.props.field;
            return (<div>
                <div className="block">
                    {field.headers.map(function (column, i) {
                        return (<div className="title" key={['booking-details-table-', count, '-column_', i].join('')}>{column}</div>);
                    })}
                </div>
                {field.rows.map(function (row, i) {
                    return (<div className="block" key={['booking-details-table-', count, '-row_', i].join('')}>{
                        row.map(function (field, j) {
                            if (field && field.type && rows.hasOwnProperty(field.type)) {
                                return React.createElement(rows[field.type], {
                                    field: field,
                                    key: ['booking-details-table-', count, '-row_', i, '-column_', j].join('')
                                });
                            } else {
                                return (<div className="info" key={['booking-details-table-', count, '-row_', i, '-column_', j, '_empty'].join('')}></div>);
                            }
                        })
                    }</div>)
                })}
            </div>);
        }
    })
};
var BookingDetails = React.createClass({
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
        var components = this.components, props = this.props;
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
                            rows.push((<div className='row-container' key={'booking-details-field_phone_' + j}>{
                                field.phone.map(function (f, k) {
                                    return React.createElement(components[f.type], {
                                        field: f,
                                        key: 'booking-details-field_' + j + '_phone_' + k,
                                        count: j + (k + 1)
                                    });
                                })
                            }</div>));
                        }
                        if (field.hasOwnProperty('tablet')) {
                            rows.push((<div className='table-container' key={'booking-details-field_table_' + j}>{
                                field.tablet.map(function (f, k) {
                                    if (components.hasOwnProperty(f.type)) {
                                        return React.createElement(components[f.type], {
                                            field: f,
                                            key: 'booking-details-field_' + j + '_table_' + k,
                                            count: j + (k + 1)
                                        });
                                    }
                                })
                            }</div>));
                        }
                    }
                }
            }
        });
        return (<div className="booker-info">{rows}</div>);
    }
});