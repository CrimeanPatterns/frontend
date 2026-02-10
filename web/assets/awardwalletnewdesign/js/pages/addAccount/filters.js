define(['angular-boot'], function () {
    'use strict';

    angular
        .module('accountAddPage-filter', [])
        .filter('displayName', function () {
            return function (text, enable) {
                if (!enable)
                    return text;

                return text.replace(new RegExp('\\([^\\(\\)]+\\)', 'gi'), '<span class="silver">$&</span>');
            }
        })
});