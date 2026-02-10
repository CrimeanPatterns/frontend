define(['angular'], function() {
    angular.module('settings', []).factory('settings', function() {
        var params = {};
        if (localStorage.getItem('aw_settings')) params = angular.fromJson(localStorage.getItem('aw_settings'));
        angular.forEach(params, function(param) {
            var nowTime = new Date().getTime();
            if (param._toTime && param._toTime < nowTime) delete params[param];
        });
        return {
            setParam: function(name, value) {
                params[name] = value;
                localStorage.setItem('aw_settings', angular.toJson(params));
                return this;
            },
            getParam: function(name) {
                var param = params[name];
                if (param) {
                    if (param['_untilTime'] != undefined && !param._untilTime < new Date.getTime()) return undefined;
                    if (param['_toTime'] != undefined || param['_untilTime'] != undefined) return param.value;
                    return param;
                }
            },
            setParamТоTime: function(name, value, time) {
                var param = {
                    value: value,
                    _toTime: time
                };
                this.setParam(name, param);
            },
            setParamUntilTime: function(name, value, time) {
                var param = {
                    value: value,
                    _untilTime: time
                };
                this.setParam(name, param);
            }
        }
    });
});