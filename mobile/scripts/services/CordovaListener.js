angular.module('AwardWalletMobile').service('$cordovaListener', [function () {
    var events = ['deviceready',
        'pause',
        'resume',
        'backbutton',
        'menubutton',
        'searchbutton',
        'startcallbutton',
        'endcallbutton',
        'volumedownbutton',
        'volumeupbutton'];
    return {
        bind: function (event, callback) {
            if (events.indexOf(event) < 0) {
                return Error(event + ' not allowed');
            }
            document.addEventListener(event, callback);
        },
        unbind: document.removeEventListener
    };
}]);