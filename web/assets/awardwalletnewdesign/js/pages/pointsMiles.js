define([
    'jquery-boot',
    'angular-boot',
    'lib/customizer',
    'lib/utils',
    'lib/dialog',
    'translator-boot',
    'routing',
    'directives/customizer',
    'filters/unsafe',
    'filters/highlight',
    'lib/couponAutocomplete',
], function($, angular, customizer, utils, dialog) {
    angular = angular && angular.__esModule ? angular.default : angular;

    const user = {
        isAuth : null,
        unAuthInputTitle : ''
    };

    const app = angular
        .module('pointValuesApp', ['appConfig', 'unsafe-mod', 'highlight-mod', 'customizer-directive']);

    app.controller('pointMileCtrl', [
        '$scope', '$injector', '$http', 'orderByFilter', '$timeout',
        'datas', 'isAuth',
        function($scope, $injector, $http, $orderBy, $timeout,
                 datas, isAuth) {

            user.isAuth = isAuth;
            $scope.user = user;
            $scope.loading = true;
            const numberCostFormat = new Intl.NumberFormat(customizer.locale, {
                style : 'decimal',
                useGrouping : true,
                minimumFractionDigits : 2,
                maximumFractionDigits : 2
            });
            const numberFormat = new Intl.NumberFormat(customizer.locale);
            const $customProgram = $('#customProgramName');

            function isValuesExists(key, item) {
                return {
                    isUserSetExists : 'undefined' !== typeof item.user && 'undefined' !== typeof item.user[key],
                    isManualExists : 'undefined' !== typeof item.manual && 'undefined' !== typeof item.manual[key],
                    isAutoExists : 'undefined' !== typeof item.auto && 'undefined' !== typeof item.auto[key],
                    isShowExists : 'undefined' !== typeof item.show && 'undefined' !== typeof item.show[key],
                };
            }

            $scope.getTitle = function(key, item, isMain) {
                const {isUserSetExists, isManualExists, isShowExists} = isValuesExists(key, item);
                if (isMain && isUserSetExists) {
                    return Translator.trans('personally-set-average');
                }

                if (isShowExists && 'undefined' !== typeof item.show['_transfer']) {
                    return '';
                }

                if (!isManualExists && isShowExists && ~~item.show[key + '_count'] < 5) {
                    return '';
                }

                let certifyDate = '';
                if (!isManualExists && isShowExists) {
                    if (null !== item.CertifyDate) {
                        const dateFormatDay = new Intl.DateTimeFormat(customizer.locales(), {month : 'short', day : 'numeric', year : 'numeric'});
                        let date = new Date(item.CertifyDate.replace(' ', 'T'));
                        certifyDate = ', ' + Translator.trans('as-of-date', {'date' : dateFormatDay.format(date)});
                    }
                    if ('undefined' !== typeof item.show[key + '_count']) {
                        return Translator.trans('based-on-last-bookings', {
                            'number' : numberFormat.format(item.show[key + '_count']),
                            'as-of-date' : certifyDate,
                        });
                    }

                    const numb = parseFloat(item.show[key]);
                    if (isNaN(numb) || 0.0000 >= numb) {
                        return '';
                    }
                }

                if (isManualExists) {
                    return Translator.trans('manually_set_by_aw') + certifyDate;
                }

                return '';
            };

            $scope.getHead = function (item) {
                if (Object.hasOwn(item, 'titleTranslateId')) {
                    return Translator.trans(item.titleTranslateId);
                }

                return item.title;
            };

            $scope.getValue = function(key, item, isMain) {
                if ('undefined' === typeof isMain) {
                    isMain = true;
                }

                const {isUserSetExists, isManualExists, isAutoExists, isShowExists} = isValuesExists(key, item);
                if (isMain && isUserSetExists) {
                    return '<span class="mp-value"><strong>' + numberCostFormat.format(item.user[key]) + '</strong> ' + item.user[key + '_currency'] + '</span>';
                } else if (!isMain && !isUserSetExists) {
                    return '';
                }

                if (!isManualExists && !isShowExists) {
                    return '<strong></strong>';
                }
                if (!isManualExists && isShowExists && ~~item.show[key + '_count'] < 5) {
                    return '<strong title="' + Translator.trans('not-enough-data') + '" data-tip></strong>';
                }

                if (isManualExists) {
                    return '<span class="mp-value"><strong>' + numberCostFormat.format(parseFloat(item.manual[key]).toFixed(2)) + '</strong> ' + item.manual[key + '_currency'] + '</span>';
                }
                if (isAutoExists) {
                    return '<span class="mp-value"><strong>' + numberCostFormat.format(parseFloat(item.auto[key]).toFixed(2)) + '</strong> ' + item.auto[key + '_currency'] + '</span>';
                }

                return '<span class="mp-value"><strong>' + numberCostFormat.format(parseFloat(item.show[key]).toFixed(2)) + '</strong> ' + item.show[key + '_currency'] + '</span>';
            };

            const route = Routing.generate('aw_points_miles_values_provider');
            $scope.filterVal = {
                providerName : decodeURI(decodeURIComponent(location.pathname
                    .substr(location.pathname.indexOf(route) + route.length)
                    //.replace(/^\/(hotels|airlines)+/, '')
                    .replace(/^\/+|\/+$/g, '')
                    .replace(/\+/g, ' '))
                    .replace(/%2F/g, '/')
                ),
                transfer : '',
            };

            $scope.filters = {
                providerName : () => urlSet(),
                applyFilter : () => fetchData(),
                clear : () => urlSet($scope.filterVal.providerName = ''),
            };

            $scope.sort = {
                reverse : false,
                fieldName : 'DisplayName',
                fieldSet : '+DisplayName',
                do : function(field) {
                    $scope.sort.reverse = $scope.sort.fieldName === field ? !$scope.sort.reverse : false;
                    $scope.sort.fieldSet = $scope.sort.reverse ? [`-${field}`, '-DisplayName'] : [`+${field}`, '-DisplayName'];
                    $scope.sort.fieldName = field;
                }
            };

            /*
            $scope.$watch('filterVal.providerName', function(val) {
                $timeout(function() {
                    if ('' === val) {
                        $('.point-values-subbrand-filter-on').removeClass('point-values-subbrand-filter-on');
                        $('.point-values-filtered').removeClass('point-values-filtered');
                    } else {
                        $('.point-values-subbrand').each(function() {
                            let found = $(this).find('span.green');
                            $(this).parent().addClass('point-values-filtered');
                            $(this).removeClass('point-values-subbrand-filter-on');
                            if (found.length) {
                                $(this).addClass('point-values-subbrand-filter-on');
                            }
                        });
                    }
                });
            });
            */

            function urlSet() {
                const route = Routing.generate('aw_points_miles_values'),
                    provider = $scope.filterVal.providerName
                        .replace(/ /g, '+')
                        .replace(/\//g, '%252F');
                history.pushState({}, '', `${route}/${provider}`);
                $timeout(() => $('#notFound')[$('.point-mile-values__block:not(.notFound)', '#pointMiles').length ? 'hide' : 'show']());
            }

            function fetchData(providerName) {
                $scope.loading = true;
                $http
                    .post(Routing.generate('aw_points_miles_values_data', {providerName : (providerName || $scope.filterVal.providerName)}), $scope.filterVal)
                    .then((result) => $scope.datas = result.data)
                    .finally(() => $scope.loading = false);
            }

            function convertComma(value) {
                if (-1 === value.indexOf('.') && -1 !== value.indexOf(',')) {
                    value = value.replace(',', '.');
                }

                return value;
            }

            $scope.anotherProgram = {
                name : '',
                value : '',
                isDisabled : false,
                disabled : function() {
                    return (0 === this.name.length || 0 === this.value.length || true === this.isDisabled);
                },
                setReadOnly : function(isReadOnly) {
                    $('input,button', '#anotherProgramAdd').prop('readonly', isReadOnly);
                },
                setUserValue : function($event) {
                    this.setReadOnly(true);
                    let value = utils.reverseFormatNumber(convertComma(this.value), customizer.locale);
                    value = parseFloat(value);
                    if (isNaN(value) || 0 >= value || value >= 100000) {
                        this.setReadOnly(false);
                        return $('#customAverageValue').parent().addClass('error');
                    }
                    const providerId = $customProgram.data('providerId');
                    if ('undefined' === typeof providerId || null === providerId) {
                        this.setReadOnly(false);
                        return $('#customProgramName').parent().addClass('error');
                    }

                    updateValue(providerId, 0, value)
                        .finally(() => {
                            $scope.anotherProgram.isDisabled = false;
                            $('input', '#anotherProgramAdd').val('');
                            this.name = this.value = '';
                            $customProgram.data('providerId', null);
                            this.setReadOnly(false);
                        });
                },
            };

            $scope.getEditTitle = function(item) {
                return 'undefined' !== typeof item.user ? Translator.trans('button.edit') : Translator.trans('override');
            };

            $scope.changeValue = function($event, key, item) {
                $event.preventDefault();
                const {isUserSetExists, isManualExists} = isValuesExists(key, item);
                const $field = $($event.target).closest('td').find('input');
                if (isUserSetExists) {
                    $field.val(item.user[key]);
                } else if (isManualExists) {
                    $field.val(item.manual[key]);
                } else if (undefined !== item.show) {
                    $field.val(item.show[key]);
                } else {
                    $field.val(0);
                }

                item.edit = true;
            };

            function updateValue(providerId, accountId, value) {
                return $http.post(Routing.generate('aw_points_miles_userset', {
                    'providerId' : providerId,
                    'accountId' : accountId
                }), {'value' : value})
                    .then((response) => {
                        if (response.data.success) {
                            processSetData(response.data.datas);
                        } else if ('string' === typeof response.data.error) {
                            dialog.fastCreate(Translator.trans('status.error-occurred'), response.data.error, false, true, [], 500);
                            $scope.anotherProgram.setReadOnly(false);
                        }
                    });
            }

            $scope.updateValue = function($event, key, item) {
                $event.preventDefault();
                const $row = $($event.target).closest('tr');
                const providerId = $row.data('id');
                const accountId = $row.data('aid');
                const $field = $($event.target).parent().find('input');
                let value = utils.reverseFormatNumber(convertComma($field.val()), customizer.locale);
                if (null !== value) {
                    value = parseFloat(value).toFixed(2);
                }

                $field.prop('readonly', true);
                updateValue(providerId, accountId, value)
                    .finally(() => {
                        item.edit = false;
                        $field.prop('readonly', false);
                    });
            };

            $scope.removeValue = function($event, item) {
                $event.preventDefault();
                delete item.user;
                const $row = $($event.target).closest('tr');
                updateValue($row.data('id'), $row.data('aid'), null);
            };

            processSetData(datas);

            function processSetData(datas) {
                for (let type in datas) {
                    for (let i in datas[type].data) {
                        datas[type].data[i].edit = false;
                    }
                }

                $scope.datas = datas;
            }

            $scope.digitFilter = ($event, item) => {
                if (13 === $event.keyCode) {
                    if (undefined === item) {
                        return $scope.anotherProgram.setUserValue($event);
                    }
                    return $scope.updateValue($event, 'AvgPointValue', item);
                }
                if (isNaN(String.fromCharCode($event.keyCode)) && (
                    '.' !== String.fromCharCode($event.keyCode)
                    && ',' !== String.fromCharCode($event.keyCode)
                )) {
                    $event.preventDefault();
                }
            };

            $('#customAverageValue,#customProgramName').on('focus', function() {
                if ($(this).parent().hasClass('error')) {
                    $(this).parent().removeClass('error');
                }
            });
            $('#customProgramName').on('focus', function() {
                $(this).autocomplete('option', 'position', {my : 'left top+32', at : 'left top'});
            });
            $scope.loading = false;

            $(document).ready(function(){

                $('.point-values').on('click', '.parent-brand>span', function() {
                    let $row = $(this).closest('tr');
                    $row.toggleClass('point-values-brand--expanded');

                });

            });


        }
    ]);


});
