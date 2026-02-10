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

    var toBarPercents = function (num) {
        return num * 100;
    };

    var FlightProgress = React.createClass({
        displayName: 'FlightProgress',

        isStillMounted: false,
        timeoutId: 0,
        getDefaultProps: function () {
            return {
                arrival: '',
                depart: '',
                startDate: new Date(),
                endDate: new Date()
            };
        },
        getInitialState: function () {
            return this.getProgress();
        },
        getProgress: function () {
            var props = this.props,
                now = new Date(),
                nowTime = now.getTime(),
                progress = 0,
                startDate = props.startDate.getTime(),
                endDate = props.endDate.getTime();
            if (nowTime > startDate) {
                progress = 1 - (endDate - nowTime) / (endDate - startDate);
                if (nowTime >= endDate) {
                    progress = 1;
                }
            }

            return {
                progress: progress,
                time: {
                    fromStart: {
                        toNow: [
                            dateTimeDiff.formatDurationInHours(now, props.startDate),
                            props.startDate - nowTime
                        ],
                        toEnd: [
                            dateTimeDiff.formatDurationInHours(props.endDate, props.startDate),
                            props.startDate - props.endDate
                        ]
                    },
                    fromNow: {
                        toEnd: [
                            dateTimeDiff.formatDurationInHours(
                                props.endDate,
                                (props.endDate - nowTime < 1000 * 60) ? new Date(now.setSeconds(0, 0)) : now
                            ),
                            props.endDate - nowTime
                        ]
                    }
                }
            };
        },
        tick: function () {
            if (!this.isStillMounted) {
                return;
            }
            var props = this.props,
                endDate = new Date(props.endDate),
                startDate = new Date(props.startDate),
                now = Date.now(),
                secondsUntilUpdate = 30,
                _this = this,
                diff,
                mins;

            if (now < startDate.getTime()) {
                diff = Math.floor((startDate.getTime() - now) / 1000);
            } else {
                diff = Math.floor((endDate.getTime() - now) / 1000);
            }

            mins = Math.floor(Math.abs(diff) / 60);

            if (mins <= 1) {
                secondsUntilUpdate = 1;
            }

            clearTimeout(_this.timeoutId);

            if (Date.now() <= endDate.getTime()) {
                this.setState(this.getProgress(), function () {
                    _this.timeoutId = setTimeout(this.tick, secondsUntilUpdate * 1000);
                });
            } else {
                this.setState(this.getProgress());
            }
        },
        componentDidMount: function () {
            this.isStillMounted = true;
            this.tick();
            if(platform.cordova)
                document.addEventListener('resume', this.tick);
        },
        componentWillUnmount: function () {
            this.isStillMounted = false;
            clearTimeout(this.timeoutId);
            this.timeoutId = 0;
            if (platform.cordova)
                document.removeEventListener('resume', this.tick);
        },
        render: function () {
            var props = this.props,
                state = this.state,
                progress = toBarPercents(state.progress);
            var flightContainer = classNames({
                'flight-container': true,
                'waiting': progress == 0,
                'started': progress > 0 && progress < 100,
                'finished': progress == 100
            });
            var flightProgress = classNames({
                'flight-progress': true,
                'start': progress <= 10,
                'end': progress >= 90
            });
            var time = {
                gone: progress > 99 ? state.time.fromStart.toEnd[0] : state.time.fromStart.toNow[0],
                left: progress < 1 ? state.time.fromStart.toEnd[0] : state.time.fromNow.toEnd[0]
            };
            return React.createElement(
                'div',
                {className: flightContainer},
                React.createElement(
                    'div',
                    {className: 'flight-point start'},
                    React.createElement(
                        'span',
                        null,
                        props.depart
                    )
                ),
                React.createElement(
                    'div',
                    {className: 'flight-details'},
                    React.createElement(
                        'div',
                        {className: flightProgress},
                        React.createElement(
                            'span',
                            {className: 'time-gone', style: {'width': progress + '%'}},
                            Math.abs(state.time.fromStart.toNow[1]) < 1000 * 60 ? null : React.createElement(
                                'span',
                                {className: 'flight-time'},
                                time.gone
                            )
                        ),
                        React.createElement(
                            'div',
                            {className: 'plane-container'},
                            React.createElement('div', {className: 'plane', style: {'left': progress + '%'}})
                        ),
                        React.createElement(
                            'span',
                            {className: 'time-left', style: {'width': 100 - progress + '%'}},
                            Math.abs(state.time.fromNow.toEnd[1]) < 1000 * 60 ? null : React.createElement(
                                'span',
                                {className: 'flight-time'},
                                time.left
                            )
                        )
                    )
                ),
                React.createElement(
                    'div',
                    {className: 'flight-point end'},
                    React.createElement(
                        'span',
                        null,
                        props.arrival
                    )
                )
            );
        }
    });
    angular.module('AwardWalletMobile').directive('flightProgress', [function () {
        return {
            restrict: 'E',
            scope: {
                arr: '=',
                dept: '=',
                endDate: '=',
                startDate: '='
            },
            link: function (scope, element, attrs) {
                React.render(React.createElement(FlightProgress, {
                    startDate: new Date(scope.startDate * 1000),
                    endDate: new Date(scope.endDate * 1000),
                    arrival: scope.arr,
                    depart: scope.dept
                }), element[0]);
                scope.$on('$destroy', function () {
                    React.unmountComponentAtNode(element[0]);
                });
            }
        };
    }]);
})(window, document, angular, React);
