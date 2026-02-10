angular.module('AwardWalletMobile').service('EventEmitter', [function() {
    var events = {};

    function subscribe(event, callback) {
        if (!events[event]) events[event] = [];
        events[event].push(callback);
    }
    function unsubscribe(event, callback) {
        if (!events[event]) return;
        var i = events[event].length;
        while (i--) {
            if (events[event][i] === callback) {
                events[event].splice(i, 1);
            }
        }
    }
    function dispatch(event, data) {
        if (!events[event]) return;
        for (var i = 0; i < events[event].length; i++) {
            events[event][i](data);
        }
    }

    return {
        subscribe: subscribe,
        unsubscribe: unsubscribe,
        dispatch: dispatch
    };
}]);