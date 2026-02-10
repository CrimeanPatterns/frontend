angular.module("ui.router.history", [
    "ui.router"
]).service("$history", function ($state) {

    var history = [];

    function removeEmpty(obj) {
        Object.keys(obj).forEach(function (key) {
            if (obj[key] === null) {
                delete obj[key]
            }
        });
        return obj;
    }

    angular.extend(this, {
        push: function (state, params) {
            history.push({state: state, params: params});
        },
        all: function () {
            return history;
        },
        previous: function () {
            history.splice(-1);
            return history.pop();
        },
        back: function (params, options) {
            if (history.length > 1) {
                var prev = this.previous();
                return $state.go(prev.state, angular.extend(prev.params, params), options);
            }
        },
        clear: function () {
            history = [];
        }

    });

}).run(function ($history, $rootScope) {
    $rootScope.$on("$stateChangeStart", function (event, to, toParams, from, fromParams, options) {
        if (!to.abstract && !options.skipHistory) {
            $history.push(to.name, toParams);
        }
    });
    $rootScope.$on("$stateChangeError", function () {
        $history.previous();
    });
});