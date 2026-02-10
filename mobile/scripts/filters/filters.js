angular.module('AwardWalletMobile').filter('keys', function () {
    return function (input) {
        if (!input) {
            return [];
        }
        return Object.keys(input);
    }
});
angular.module('AwardWalletMobile').filter('text2p', function () {
    return function (str) {
        var text = String(str).trim(),
            formatText = text.split('.');
        formatText.pop();
        formatText.map(function (text) {
            return String(text).trim();
        });
        if (formatText.length > 0)
            return (text.length > 0 ? '<p>' + formatText.join('.</p><p>') + '</p>' : null);
        return text;
    }
});
angular.module('AwardWalletMobile').filter('objectToArray', [
    function () {
        return function (object) {
            var array = [];
            angular.forEach(object, function (element) {
                array.push(element);
            });

            return array;
        };
    }
]);
angular.module('AwardWalletMobile').filter('mark', ['$sce', function ($sce) {
    return function (input, word) {
        return input.replace(word, ['<mark>', word, '</mark>'].join());
    }
}]);
angular.module('AwardWalletMobile').filter('desc', function () {
    return function (input) {
        return input;
    }
});
angular.module('AwardWalletMobile').filter('lowercase', function () {
    return function (input) {
        return input.toLowerCase();
    }
});
angular.module('AwardWalletMobile').filter('fmt', [
    function () {
        return function (fmt) {
            return new Date(fmt.y, fmt.m, fmt.d, fmt.h, fmt.i).getTime();
        };
    }
]);
angular.module('AwardWalletMobile').filter('IntlNumber', [
    '$filter',
    function ($filter) {
        return function (number, region) {
            if (window.hasOwnProperty('Intl') && window.Intl.hasOwnProperty('NumberFormat')) {
                return new window.Intl.NumberFormat(region).format(number)
            }
            return $filter('number')(number);
        };
    }
]);
angular.module('AwardWalletMobile').filter('capitalize', () => {
    return str => {
        if (typeof str !== 'string') {
            return '';
        }

        return str.charAt(0).toUpperCase() + str.slice(1);
    }
});
