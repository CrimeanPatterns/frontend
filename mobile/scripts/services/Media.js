angular.module('AwardWalletMobile').service('Media', [
    '$cordovaMedia',
    '$cordovaVibration',
    'UserSettings',
    function ($cordovaMedia, $cordovaVibration, UserSettings) {
        var vibrate = navigator.vibrate || navigator.webkitVibrate || navigator.mozVibrate || navigator.msVibrate;
        return {
            play: function(src) {
                if (!UserSettings.isSoundEnabled()) return;
                if (platform.cordova) {
                    if (platform.android && !(new RegExp("^(/android_asset|https?:\/\/)")).test(src)) {
                        src = '/android_asset/www/' + src;
                    }
                    $cordovaMedia.newMedia(src).play();
                } else {
                    (new Audio(src)).play();
                }
            },
            vibrate: function (times) {
                if (!UserSettings.isVibrationSupported()) return;
                if (platform.cordova) {
                    $cordovaVibration.vibrate(times);
                } else {
                    if (vibrate) {
                        vibrate(times);
                    }
                }
            }
        };
    }
]);