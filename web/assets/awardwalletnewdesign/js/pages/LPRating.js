define([
    'angular-boot',
    'jquery-boot',
    'lib/customizer',
    'angular-ui-router',
    'filters/unsafe',
    'filters/highlight',
    'translator-boot',
    'directives/customizer'
], function(angular, $, customizer) {
    angular = angular && angular.__esModule ? angular.default : angular;

    var app = angular.module("LPRatingApp", [
        'appConfig', 'unsafe-mod', 'highlight-mod', 'ui.router', 'customizer-directive'
    ]);

    app.config([
        '$injector', '$locationProvider',
        function($injector, $locationProvider) {
            $locationProvider.html5Mode({
                enabled : true,
                rewriteLinks : true
            });
        }
    ]);

    app.controller('LPRatingCtrl', [
        '$scope', '$http', '$timeout', 'initialData', 'transferTimesTo', 'transferTimesFrom', 'mileValue',
        function($scope, $http, $timeout, initialData, transferTimesTo, transferTimesFrom, mileValue) {
            $scope.main.status = {};
            $scope.main.status.loading = false;

            $scope.transferTimesTo = transferTimesTo;
            $scope.transferTimesFrom = transferTimesFrom;

            function isValuesExists(key, item) {
                return {
                    isUserSetExists : 'undefined' !== typeof item.user && 'undefined' !== typeof item.user[key],
                    isManualExists : 'undefined' !== typeof item.manual && 'undefined' !== typeof item.manual[key],
                    isAutoExists : 'undefined' !== typeof item.auto && 'undefined' !== typeof item.auto[key],
                    isShowExists : 'undefined' !== typeof item.show && 'undefined' !== typeof item.show[key],
                };
            }

            if ('undefined' !== typeof mileValue._mileValue) {
                const numberCostFormat = new Intl.NumberFormat(customizer.locale, {
                    style : 'decimal',
                    useGrouping : true,
                    minimumFractionDigits : 2,
                    maximumFractionDigits : 2
                });
                const numberFormat = new Intl.NumberFormat(customizer.locale);

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
                    if (isShowExists) {
                        if (null !== item.CertifyDate) {
                            const dateFormatDay = new Intl.DateTimeFormat(customizer.locale, {month : 'short', day : 'numeric', year : 'numeric'});
                            let date = new Date(item.CertifyDate);
                            certifyDate = ', ' + Translator.trans('as-of-date', {'date' : dateFormatDay.format(date)});
                        }
                        if ('undefined' !== typeof item.show[key + '_count']) {
                            return Translator.trans('based-on-last-bookings', {
                                'number' : numberFormat.format(item.show[key + '_count']),
                                'as-of-date' : certifyDate,
                            });
                        }

                        const numb = parseFloat(item.show[key + '_number']);
                        if (isNaN(numb) || 0.0000 >= numb) {
                            return '';
                        }
                    }

                    return Translator.trans('manually_set_by_aw') + certifyDate;
                };

                $scope.getValue = function(key, item, isMain) {
                    if ('undefined' === typeof isMain) {
                        isMain = true;
                    }

                    const {isUserSetExists, isManualExists, isAutoExists, isShowExists} = isValuesExists(key, item);
                    if (isMain && isUserSetExists) {
                        return '<span class="mp-value"><strong>' + numberCostFormat.format(parseFloat(item.user[key]).toFixed(2)) + '</strong> ' + item.user[key + '_currency'] + '</span>';
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


                for (let i in mileValue._mileValue.data) {
                    mileValue._mileValue.data[i].edit = false;
                }
            }
            $scope.mileValue = mileValue;

            this.page_loaded = false;
            this.review = {
                saved : false,
                success : false
            };

            $scope.starsNumber = [1, 2, 3, 4, 5];
            $scope.userReview = initialData.userReview;
            $scope.reviewsList = initialData.reviewsList;
            $scope.fieldNames = initialData.fieldNames;
            $scope.yourCurrentRate = [];
            if ($scope.userReview !== undefined) {
                for (var field of $scope.fieldNames) {
                    $scope.yourCurrentRate[field] = $scope.userReview[field];
                }
            }

            $scope.sortedFields = [
                {name : 'totalRating', descending : true, label : Translator.trans(/** @Desc("Highest Rating") */'sort.highest_rating')},
                {name : 'totalRating', descending : false, label : Translator.trans(/** @Desc("Lowest Rating") */'sort.lowest_rating')},
                {name : '_usefulCount', descending : false, label : Translator.trans(/** @Desc("Least Useful") */'sort.least_useful')},
                {name : '_updatedate', descending : true, label : Translator.trans(/** @Desc("Newest") */'sort.newest')},
                {name : '_updatedate', descending : false, label : Translator.trans(/** @Desc("Oldest") */'sort.oldest')},
                {name : '_usefulCount', descending : true, label : Translator.trans(/** @Desc("Most Useful") */'sort.more_useful')}
            ];

            $scope.selectedFields = $scope.sortedFields[3];

            var main = this;
            main.page_loaded = true;

            $scope.setRate = function(number, key) {
                if ($scope.userReview[key] == number) {
                    $scope.userReview[key] = 0;
                    $scope.yourCurrentRate[key] = 0;
                } else {
                    $scope.userReview[key] = number;
                    $scope.yourCurrentRate[key] = number;
                }
                $scope.form.fReview.$setDirty();
            }

            $scope.currentRate = function(number, key) {
                $scope.yourCurrentRate[key] = number;
            }
            $scope.currentRateUnset = function(key) {
                $scope.yourCurrentRate[key] = $scope.userReview[key];
            }

            $scope.setReviewVote = function(review, vote, e) {
                $(e.target).addClass('loader');
                $.post(Routing.generate('aw_rating_useful_vote'), {reviewId : review.ReviewID, providerId : review.ProviderID}, function(response) {
                    $scope.$apply(() => $scope.reviewsList = response.reviewsList);
                    $(e.target).removeClass('loader');
                }, 'json');
            };

            $scope.submit = function() {
                let data = {
                    ReviewID : $scope.userReview.ReviewID ?? 0,
                    ProviderID : initialData.provider.providerid,
                    Review : $scope.userReview.Review
                };
                for (let field of $scope.fieldNames) {
                    data[field] = $scope.userReview[field];
                }
                $('button.btn-blue').addClass('loader');
                $.post(Routing.generate('aw_rating_review_update'), {review : data}, function(response) {
                    $scope.$apply(function() {
                        main.review.saved = true;
                        main.review.success = true;
                        $scope.userReview.ReviewID = response.ReviewID;
                        $scope.reviewsList = response.reviewsList;
                    });

                    $('button.btn-blue').removeClass('loader');
                    $timeout(function() {
                        main.review.saved = false;
                    }, 8000);

                }, 'json');
            };

            $scope.helpfulTrans = function(usefulCount) {
                if (usefulCount > 0) {
                    return Translator.transChoice('found_review_useful', usefulCount, {'votes' : usefulCount});
                }
                return '';
            };

            $scope.ratingLabelText = function(name) {
                return Translator.trans('rating.' + name.toLowerCase());
            };

            $scope.ratingTipText = function(name) {
                return Translator.trans('rating.' + name.toLowerCase() + '.desc');
            };

            $scope.ratingSum = function() {
                let $sumRating = 0;
                for (var name of $scope.fieldNames) {
                    $sumRating += Number($scope.userReview[name]);
                }
                return $sumRating;
            };

            $scope.main.status.loaded = true;
        }]);
});