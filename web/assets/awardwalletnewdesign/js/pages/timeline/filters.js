define([
    'angular-boot',
    'pages/timeline/main'
], angular => {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('app')
        .filter('capitalize', () => {
            return str => {
                if (typeof str !== 'string') {
                    return '';
                }

                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        });
});