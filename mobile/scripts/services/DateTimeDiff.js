angular.module('AwardWalletMobile').service('DateTimeDiff', [() => {
    const DTDiff = window['date-time-diff'];

    return window.dateTimeDiff = new DTDiff.default(Translator, (number) => {
        return Intl.NumberFormat().format(number);
    });
}]);