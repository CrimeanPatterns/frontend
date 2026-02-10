(function (React) {
    if (React.addons && React.addons.TimeAgo) {
        return React.addons.TimeAgo;
    }
    React.addons = React.addons || {};
    var TimeAgo = React.addons.TimeAgo = React.createClass({
        displayName: 'TimeAgo',
        getDefaultProps: function () {
            return {
                date: new Date(),
                fromDate: null,
                live: true,
                withoutSuffix: false,
                shortTime: false,
                shortDate: false,
                locale: null
            };
        },
        getInitialState: function () {
            return {
                timeAgo: null
            }
        },
        isStillMounted: false,
        timeoutId: 0,
        tick: function () {
            if (!this.isStillMounted) {
                return;
            }
            var updateDate = new Date(this.props.date), now = new Date(), from = this.props.fromDate || now;
            var diff = Math.floor((now.getTime() - updateDate.getTime()) / 1000);
            var mins = Math.floor(Math.abs(diff) / 60);
            var timeAgo;

            if (this.props.shortDate) {
                timeAgo = dateTimeDiff.shortFormatViaDateTimes(from, updateDate, !this.props.withoutSuffix, false, this.props.locale);
            } else {
                timeAgo = dateTimeDiff.longFormatViaDateTimes(from, updateDate, !this.props.withoutSuffix, false, this.props.locale);
            }
            this.setState({timeAgo: timeAgo});

            if (this.props.live) {
                var secondsUntilUpdate = 3600;
                if (mins < 1) {
                    secondsUntilUpdate = 1;
                } else if (mins < 60) {
                    secondsUntilUpdate = 30;
                } else if (mins < 180) {
                    secondsUntilUpdate = 300;
                }
                this.timeoutId = setTimeout(this.tick, secondsUntilUpdate * 1000);
            }
        },
        componentDidMount: function () {
            this.isStillMounted = true;
            this.tick();
        },
        componentDidUpdate: function(lastProps) {
            if (this.props.live !== lastProps.live || this.props.date !== lastProps.date) {
                if (!this.props.live && this.timeoutId) {
                    clearTimeout(this.timeoutId);
                }
                this.tick();
            }
        },
        componentWillUnmount: function() {
            this.isStillMounted = false;
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
                this.timeoutId = 0;
            }
        },
        render: function () {
            return React.createElement(
                'span',
                null,
                this.state.timeAgo
            );
        }
    });
    TimeAgo.setDefaultLoader = function (loader) {
        TimeAgo._defaultLoader = loader;
    };
    return TimeAgo;
})(React);