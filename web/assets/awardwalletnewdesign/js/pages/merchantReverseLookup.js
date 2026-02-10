define([
    'angular-boot',
    'jquery-boot',
    'lib/customizer',
    'lib/design',
    'filters/unsafe'
], function(angular, $, customizer){
    angular = angular && angular.__esModule ? angular.default : angular;

    var InitData;

    var app = angular.module("merchantReverseLookupApp", [
        'appConfig', 'unsafe-mod'
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

    app.controller('mainCtrl', ['$scope', '$http', '$timeout', function ($scope, $http, $timeout) {

        this.state = {
            loaded: false
        };

        this.offer = {
            loading: false,
            loaded: false,
            content: null
        };

        this.cardsList = [];
        this.currentCard = {};
        this.currentGroup = {};

        this.currentCardId = null;
        this.currentGroupId = null;
        this.currentMultiplierId = null;

        var main = this;

        if ((typeof (InitData) == 'object') && (InitData != null)) {
            this.cardsList = angular.copy(InitData);
            this.state.loaded = true;
        }

        this.selectCard = function () {
            var existed = false;
            main.offer.loaded = false;

            for (var i in this.cardsList) {
                if (this.cardsList[i].cardId !== parseInt(this.currentCardId)) {
                    continue;
                }

                existed = true;
                this.currentCard = angular.copy(this.cardsList[i]);
                break;
            }

            if (!existed) {
                this.currentCard = {};
            }

            this.currentGroupId = null;
        };

        this.selectGroup = function () {
            if (this.currentGroupId === undefined) {
                return;
            }

            var existed = false;

            for (var i in this.currentCard.multipliers) {
                if (this.currentCard.multipliers[i].groupId !== parseInt(this.currentGroupId)) {
                    continue;
                }

                existed = true;
                this.currentGroup = angular.copy(this.currentCard.multipliers[i]);
                this.currentMultiplierId = this.currentCard.multipliers[i].id;
                break;
            }

            if (!existed) {
                this.currentGroup = {};
                return;
            }

            this.offer.loading = true;
            $http.get(
                Routing.generate('aw_merchant_reverse_lookup_offer', {
                    id: main.currentMultiplierId,
                    noBody: ''
                })
            ).then(function(res) {
                var data = res.data;
                main.offer.loading = false;
                main.offer.loaded = true;
                main.offer.content = data;
            }).catch(function(e) {
                main.offer.loading = false;
            });


        };

    }]);

});
