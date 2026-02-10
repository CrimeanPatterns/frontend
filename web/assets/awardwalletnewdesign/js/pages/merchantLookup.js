/*
global
whenRecaptchaLoaded,
renderRecaptcha,
whenRecaptchaSolved
hideRecaptcha
showRecaptcha
*/

define([
    'lib/customizer',
    'angular-boot',
    'jquery-boot',
    'lib/utils',
    'directives/dialog',
    'filters/unsafe'
], function(customizer, angular, $, utils){
    angular = angular && angular.__esModule ? angular.default : angular;

    let IsAuth;
    let merchantOffer;
    let showPercent;

    const app = angular.module("merchantLookupApp", [
        'appConfig', 'unsafe-mod', 'dialog-directive'
    ]);

    app.config([
        '$injector',
        function ($injector) {
            if ($injector.has('IsAuth')) {
                IsAuth = $injector.get('IsAuth');
            }
            if ($injector.has('merchantOffer')) {
                merchantOffer = $injector.get('merchantOffer');
            }
            if ($injector.has('showPercent')) {
                showPercent = $injector.get('showPercent');
            }
        }
    ]);

    app.controller('mainCtrl', [
        '$scope', '$http', '$timeout', 'dialogService', '$window', function ($scope, $http, $timeout, dialogService, $window) {

            window.addEventListener('popstate',() => {
                $window.location.reload();
            });
            this.offer = {
                loading: false,
                loaded: false,
                content: null
            };
            this.currentMerchant = {
                id: null,
                label: null,
                nameToUrl: null,
                category: null,
                url: null,
            };
            this.searchInput = {
                data: null,
                loading: false
            };
            this.noResultsLabel = 'No merchants found';

            const main = this;
            const baseRoute = Routing.generate('aw_merchant_lookup');

            if(merchantOffer){
                this.offer.loaded = true;
                this.offer.content = $("<div/>").html(merchantOffer).text();
                setTimeout(() => customizer.initTooltips($('div.modal')), 500);
            }

            this.initState = function () {
                this.offer.loading = false;
                this.offer.loaded = false;
                this.offer.content = null;

                this.currentMerchant.id = null;
                this.currentMerchant.label = null;
                this.currentMerchant.nameToUrl = null;
                this.currentMerchant.category = null;
                this.currentMerchant.url = null;
                this.searchInput.data = null;
                this.searchInput.loading = false;
            };

            this.merchantSelect = function(event, ui) {
                if (ui.item.label === this.noResultsLabel) {
                    return false;
                }

                this.currentMerchant.id = ui.item.id;
                this.currentMerchant.label = ui.item.label;
                this.currentMerchant.nameToUrl = ui.item.nameToUrl;
                this.currentMerchant.category = ui.item.category;
                this.currentMerchant.url = ui.item.url;

                this.offer.loading = true;

                if (IsAuth) {
                    this.loadOffer(ui.item.nameToUrl, '');
                } else {
                    this.loadOfferRecaptcha(ui.item.nameToUrl);
                }

                if(history){
                    let newUrl = `${baseRoute}/${this.currentMerchant.nameToUrl}`;

                    if (showPercent) newUrl += '?showPercent';
                    history.pushState({}, '', newUrl);
                }

                return false;
            };

            this.loadOffer = function (name, captcha_key) {
                window.console.log('loadOffer');
                let url = decodeURIComponent(Routing.generate('aw_merchant_by_name_lookup_offer', {merchantName: name}));
                if (showPercent) url += '?showPercent'
                window.console.log(url);

                $http.get(
                    url,
                    {
                        headers: {
                            'recaptcha': captcha_key
                        }
                    }
                ).then(({ data }) => {
                    main.offer.loading = false;
                    main.offer.loaded = true;
                    main.offer.content = data;
                    setTimeout(() => {
                        setTimeout(() => customizer.initTooltips($('div.modal')), 500);
                    }, 50);
                }).catch(function(e) {
                    console.log(e);
                    main.createErrorDialog();
                    if(history) {
                        history.pushState({}, '', `${baseRoute}`);
                    }
                    main.offer.loading = false;
                });
            };

            this.loadOfferRecaptcha = function (name) {
                window.console.log('loadOfferRecaptcha');

                whenRecaptchaLoaded(function(){
                    showRecaptcha();
                    renderRecaptcha();
                });
                $timeout(function () {
                    whenRecaptchaSolved(function(captcha_key){
                        hideRecaptcha();
                        main.loadOffer(name, captcha_key);
                    });
                }, 0);
            };

            this.autocompleteSource = function(request, response) {
                this.searchInput.loading = true;

                $http.post(
                    Routing.generate('aw_merchant_lookup_data'),
                    $.param({
                        query: request.term
                    }),
                    { headers: {'Content-Type': 'application/x-www-form-urlencoded'} }
                ).then(function({ data }) {
                    let result = [];
                    if ($.isEmptyObject(data)) {
                        result = [{
                            label: main.noResultsLabel,
                            value: ""
                        }];
                    } else {
                        result = data;
                    }
                    response(result);
                    main.searchInput.loading = false;
                }).catch(function(e) {
                    main.createErrorDialog();
                    main.searchInput.loading = false;
                });
            };

            this.createErrorDialog = function() {
                dialogService.fastCreate(
                    "Error",
                    "Currently we are limiting our users to send no more than 100 lookup requests per 5 minutes. You have reached your limit please come back in 5 minutes if you wish to continue searching.",
                    true,
                    true,
                    [
                        {
                            text: Translator.trans('button.ok'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            'class': 'btn-blue'
                        }
                    ],
                    500
                );
            };

            this.resetSearch = function() {
                window.console.log('reset search');
                this.initState();
                $('#merchant').autocomplete('close').trigger('autocompletechange').focus();
            };

            const merchantInput = $('#merchant');

            merchantInput.autocomplete({
                minLength: 3,
                delay: 500,
                source: function(request, response) {
                    main.autocompleteSource(request, response);
                },
                select: function(event, ui) {
                    merchantInput.blur();
                    main.merchantSelect(event, ui);
                },
                create: function () {
                    $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                        const element = $('<a></a>').append($("<span></span>").html(`${item.label}&nbsp;`));
                        if ( item.category )
                            element.append($("<span></span>").addClass("blue").html(`(${item.category})`));

                        return $('<li></li>')
                            .data("item.autocomplete", item)
                            .append(element)
                            .appendTo(ul);
                    };
                }
            }).off('blur').on('blur', function() {
                if (document.hasFocus()) {
                    $('ul.ui-autocomplete').hide();
                }
            }).on('focus', function(event) {
                const $widget = merchantInput.autocomplete('widget');
                if ($widget.find('>.ui-menu-item').length) {
                    const pos = merchantInput.offset();
                    $widget.css({'top': pos.top + 33, 'left': pos.left}).show();
                }
            });
            $(window).resize(function(){
                const $widget = merchantInput.autocomplete('widget');
                if ($widget.is(':visible')) {
                    const pos = merchantInput.offset();
                    $widget.css({'top': pos.top + 33, 'left': pos.left});
                }
            });
        }
    ]);

});
