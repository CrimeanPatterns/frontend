define([
	'angular-boot', 'jquery-boot', 'lib/customizer',
    'lib/design', 'filters/unsafe', 'angular-ui-router',
    'directives/customizer', 'directives/dialog',
    'ng-infinite-scroll',
    'pages/spentAnalysis/ServiceSpentAnalysis'

], function (angular, $, customizer) {
    angular = angular && angular.__esModule ? angular.default : angular;

    var InitData;

    var app = angular.module("accountHistoryApp", [
        'appConfig', 'ui.router', 'infinite-scroll', 'unsafe-mod',
        'customizer-directive', 'dialog-directive', 'SpentAnalysisService'
    ]);

    app.config([
        '$injector',
        function ($injector) {
            if ($injector.has('InitData')) {
                var data = $injector.get('InitData');
                if ((typeof (data) == 'object') && (data != null)) {
                    InitData = $.extend(InitData, $injector.get('InitData'));
                }
            }
        }
    ]);

    app.controller('mainCtrl', [
        '$scope', '$http', '$timeout', 'dialogService', 'SpentAnalysis',
        function ($scope, $http, $timeout, dialogService, SpentAnalysis) {

    	this.historyRows = [];
    	this.historyColumns = [];
        this.offerCardsFilter = [];
        this.offerDialogContent = "";
        this.descriptionFilter = "";

        this.state = {
            loading: false,
            reloading: false,
            offerLoading: false,
            loaded: false,
            accountId: null,
            subAccountId: null,
            nextPageToken: null,
            isLastPageLoaded: false,
            cardsFilter: {}
        };

        var main = this;

        if ((typeof (InitData) == 'object') && (InitData != null)) {
            this.historyRows = angular.copy(InitData.historyRows);
            this.historyColumns = angular.copy(InitData.historyColumns);
            this.offerCardsFilter = angular.copy(InitData.offerCardsFilter);
            this.state.accountId = angular.copy(InitData.accountId);
            this.state.subAccountId = angular.copy(InitData.subAccountId);
            this.state.nextPageToken = angular.copy(InitData.nextPageToken);

            angular.forEach(this.offerCardsFilter, function (value) {
                angular.forEach(value.cardsList, function (value) {
                    main.state.cardsFilter[value.creditCardId] = true;
                });
            });

            this.state.loaded = true;
        }

        this.buildCardsFilterToRequest = function() {
            var result = [];
            angular.forEach(this.state.cardsFilter, function (value, key) {
                if (value) {
                    result.push(key);
                }
            });

            return result;
        };

        this.reloadRows = function() {
            if (this.state.reloading) {
                return;
            }

            this.state.reloading = true;
            var request = {
                accountId: this.state.accountId,
                descriptionFilter: this.descriptionFilter,
                offerFilterIds: this.buildCardsFilterToRequest()
            };

            if (this.state.subAccountId !== null) {
                request.subAccountId = this.state.subAccountId;
            }

            $http.get(
                Routing.generate('aw_subaccount_history_data', request)
            ).then(
                res => {
                    const data = res.data;
                    main.historyRows = data.historyRows;
                    main.state.nextPageToken = data.nextPageToken;
                    main.state.isLastPageLoaded = false;
                }
            ).finally(
                () => main.state.reloading = false
            );

        };

        this.resetSearch = function(e) {
            e.preventDefault();
            this.descriptionFilter = "";
            this.reloadRows();
        };

        this.loadMore = function() {
            if (this.state.loading) {
                return;
            }

            if (this.state.isLastPageLoaded) {
                window.console.log('Last page Loaded.');
                return;
            }

            var request = {
                accountId: this.state.accountId,
                descriptionFilter: this.descriptionFilter,
                nextPage: this.state.nextPageToken
            };
            var route = 'aw_account_history_data';

            if (this.state.subAccountId != null) {
                request.subAccountId = this.state.subAccountId;
                request.offerFilterIds = this.buildCardsFilterToRequest();
                route = 'aw_subaccount_history_data';
            }

            window.console.log('Loading next page...');
            this.state.loading = true;
            $http.get(
                Routing.generate(route, request)
            ).then(
                res => {
                    const data = res.data;
                    if (data.nextPageToken == null) {
                        main.state.isLastPageLoaded = true;
                    }
                    for (var i=0;i<data.historyRows.length;i++) {
                        main.historyRows.push(data.historyRows[i]);
                    }
                    main.state.nextPageToken = data.nextPageToken;
                }
            ).finally(
                () => main.state.loading = false
            );
        };

        this.showOfferPopup = function(uuid, merchant) {
            window.console.log('Loading offer dialog...');

            var dialog = dialogService.get('credit-card-offer-popup');
            dialog.element.parent().find('.ui-dialog-title').html(SpentAnalysis.getOfferTitle(merchant));

            dialog.setOption('buttons', [
                {
                    text: 'OK',
                    click: function () {
                        dialog.close();
                    },
                    'class': 'btn-blue'
                }
            ]);

            main.state.offerLoading = true;
            $http.post(
                Routing.generate('aw_spent_analysis_transaction_offer'),
                $.param({
                    source: "transaction-history&mid=web",
                    uuid: uuid,
                    offerFilterIds: this.buildCardsFilterToRequest()
                }),
                {headers: {'Content-Type': 'application/x-www-form-urlencoded'}}
            ).then(
                res => {
                    main.state.offerLoading = false;
                    main.offerDialogContent = res.data;

                    setTimeout(() => {
                        $(window).trigger('resize.dialog');
                        customizer.initTooltips($(dialog.element));
                    }, 100);
                }
            ).catch(
                () => {
                    main.state.offerLoading = false;
                    dialog.close();
                }
            );

            dialog.open();
            setTimeout(() => {
                $(dialog.element).closest('.ui-dialog').css('width', '96%');
                $(window).trigger('resize.dialog');
            }, 100);
        };

        this.uploadHistory = {
            file: null,
            success: null,
            upload : function () {
                $('#uploadHistory').trigger('click');
            }
        };

        this.tripLink = tripId => {
            return Routing.generate('aw_timeline_show_trip', {'tripId': tripId});
        };

        $scope.$watch('main.uploadHistory.file.name', (oV, nV) => {
            console.log("call: main.uploadHistory.file.name");
            if(oV === nV || !main.uploadHistory.file) return;

            var file = main.uploadHistory.file;
            var fd = new FormData();
            var fileName = main.uploadHistory.file.name;

            fd.append('historyFile', file);

            $('.transaction-info.provider').fadeOut({
                complete: function () {
                    var progressBar = $('.progress-bar');
                    progressBar.fadeIn();
                    progressBar.find('.progress-bar-row p').text(fileName);
                    progressBar.find('.progress-bar-row span').animate({
                        width:'100%'
                    },{
                        easing: 'linear',
                        duration: 2000,
                        complete: function () {
                            progressBar.fadeOut();
                        }
                    });
                }
            });

            $http.post('/account/history/upload/' + main.state.accountId, fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .then(
                    res => {
                        const data = res.data;
                        main.uploadHistory.success = data.success;
                        if(!data.success){
                            $timeout(() => main.uploadHistory.success = null, 3000);
                        }
                    }
                )
                .catch(
                    () => {
                        main.uploadHistory.success = false;
                        $timeout(() => main.uploadHistory.success = null, 3000);
                        dialog.close();
                    }
                );
        });

        $scope.$watch('main.uploadHistory.success', (oV, nV) => {
            console.log("call: main.uploadHistory.success");

            if(oV == nV) return;
            if(main.uploadHistory.success === null)
                $('.transaction-info.error').fadeOut({
                    complete: function () {
                        $('.transaction-info.provider').fadeIn();
                    }
                });
            else
                $('.progress-bar').fadeOut({
                    complete: function () {
                        if(main.uploadHistory.success)
                            $('.transaction-info.success').fadeIn();
                        else
                            $('.transaction-info.error').fadeIn();
                    }
                });
        });

    }]);

    app.directive('fileModel', ['$parse', '$timeout', function ($parse, $timeout) {
        return {
            restrict: 'A',
            link: function(scope, element, attrs) {
                var model = $parse(attrs.fileModel);
                var modelSetter = model.assign;

                element.bind('change', function(){
                    $timeout(function() {
                        scope.$apply(function(){
                            modelSetter(scope, element[0].files[0]);
                        });
                    });
                });
            }
        };
    }]);

});
