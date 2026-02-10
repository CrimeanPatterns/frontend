define(['jquery-boot', 'lib/dialog', 'vendor/jquery.scrollTo/jquery.scrollTo.min', 'translator-boot'], function ($, dialog) {
    var preRoute = "";
    var isLocation = "home";
    if (window.location.href.indexOf('region') != -1){
        preRoute = "../../";
        isLocation = "region";
    }

    var buttonAjax = function(obj, url, params, requestType, method, success) {
        if(!params)      params = {};
        if(!requestType) requestType = 'json';
        if(!method)      method = 'POST';
        const id = [];
        const addLoader = function(obj) {
            $(obj).attr('disabled', 'disabled');
            $(obj).addClass('loader');
        };
        const removeLoader = function(obj) {
            $(obj).removeAttr('disabled', '');
            $(obj).removeClass('loader');
        };
        if (obj){
            if(obj[0] === 'isArray'){
                for(let i in obj){
                    if(i != 0)
                        addLoader(obj[i]);
                }
            } else {
                addLoader(obj);
            }
        }
        // todo fail!
        $.ajax({
            url:        url,
            dataType:   requestType,
            type:       method,
            data:       params,
            timeout:    30000,
            success:    function(json, textStatus){
                if (typeof success === "function")
                    success(json);
                if (obj){
                    if(obj[0] === 'isArray'){
                        for(let i in obj){
                            if(i != 0)
                                removeLoader(obj[i]);
                        }
                    } else {
                        removeLoader(obj);
                    }
                }
            }
        });
    };
    var markMsg = [],
        markMsgProvider = [],
        markDealMsg = {
            'Apply': [],
            'Follow': []
        };

    var promo = {
        processing: [],

        toogleItem: function(block, duration) {
            const list = block.find('.promotion-list-description');
            const promoID = block.data('deal-id');
            duration = duration || 400;

            if (!block.hasClass('active')) {
                if (typeof(markMsg[promoID]) == 'undefined' || (typeof(markMsg[promoID]) != 'undefined' && markMsg[promoID] == 0)){
                    promo.markAsRead(promoID, 0);
                }
                promo.processing['item'] = true;
                list.slideDown(duration, function() {
                    block.addClass('active');
                    promo.processing['item'] = false;
                });
            } else {
                promo.processing['item'] = true;
                list.slideUp(duration, function() {
                    block.removeClass('active');
                    promo.processing['item'] = false;
                });
            }
        },

        toogleProvider: function(block, duration) {
            const list = block.find('.promotion-list');
            duration = duration || 400;

            if (!block.hasClass('active')) {
                promo.processing['provider'] = true;
                list.slideDown(duration, function() {
                    block.addClass('active');
                    promo.processing['provider'] = false;
                });
            } else {
                promo.processing['provider'] = true;
                list.slideUp(duration, function() {
                    block.removeClass('active');
                    promo.processing['provider'] = false;
                });
            }
        },

        toogleHead: function(block, duration) {
            const list = block.find('.list-of-providers');
            duration = duration || 400;

            if (!block.hasClass('active')) {
                block.addClass('active');
                promo.processing['head'] = true;
                list.slideDown(duration, function() {
                    promo.processing['head'] = false;
                });
            } else {
                promo.processing['head'] = true;
                list.slideUp(duration, function() {
                    block.removeClass('active');
                    promo.processing['head'] = false;
                });
            }
        },

        leadToPromo: function() {
            var promoID = location.hash.replace(/^\s+|\/|#|\s+$/gm,''),
                promoElem, providerElem, headElem;
            if (promoID == '') return;

            promoElem = $('[data-deal-id="'+promoID+'"]');
            if (promoElem && !promoElem.hasClass('active')) {
                promo.toogleItem(promoElem);
            }
            providerElem = promoElem.closest('[data-provider-id]');
            if (providerElem && !providerElem.hasClass('active')) {
                promo.toogleProvider(providerElem);
            }
            headElem = providerElem.closest('.promotions');
            if (headElem && !headElem.hasClass('active')) {
                promo.toogleHead(headElem);
            }

            var isReady = true;
            var timer = setInterval(function() {
                isReady = true;
                for (let i in promo.processing) {
                    if (promo.processing[i] == true)
                        isReady = false;
                }
                if (isReady) {
                    $('html, body').animate({ scrollTop: promoElem.offset().top - 50 }, 1000);
                    clearInterval(timer);
                }
            }, 100);
        },

        markAsRead: function(dealID, status) {
            if (typeof(markMsg[dealID]) != 'undefined')
                status = markMsg[dealID];
            buttonAjax($('#markSmall_' + dealID), preRoute+"promos/mark.json", {dealID: dealID, status:status}, 'json', 'POST', function(data) {
                if (data.content != 'OK') {
                    alert(data.error);
                } else {
                    var deal = $('[data-deal-id="'+dealID+'"]'),
                        provider = deal.closest('[data-provider-id]'),
                        providerID = provider.data('provider-id'),
                        providerUnread = parseInt(provider.find('.promotion-title span[rel=unread]').html()),
                        partUnread = parseInt(provider.closest('.promotions').find('.title-note span[rel=unread]').html());
                    if (status == 1) {
                        deal.removeClass('read');
                        $('#markSmall_' + dealID + '').text(Translator.trans('promotion.mark-as-read', {}, 'promotions'));
                        markMsg[dealID] = 0;
                        providerUnread++;
                        partUnread++;
                        if (providerUnread > 0) {
                            $('#markProvider_' + providerID + '').text(Translator.trans('promotion.mark-read-all', {}, 'promotions'));
                            markMsgProvider[providerID] = 0;
                        }

                    } else {
                        deal.addClass('read');
                        $('#markSmall_' + dealID + '').text(Translator.trans('promotion.mark-as-unread', {}, 'promotions'));
                        markMsg[dealID] = 1;
                        if (providerUnread > 0) providerUnread--;
                        if (partUnread > 0) partUnread--;
                        if (providerUnread == 0) {
                            $('#markProvider_' + providerID + '').text(Translator.trans('promotion.mark-unread-all', {}, 'promotions'));
                            markMsgProvider[providerID] = 1;
                        }
                    }
                    provider.find('.promotion-title span[rel=unread]').html(providerUnread);
                    provider.closest('.promotions').find('.title-note span[rel=unread]').html(partUnread);
                }
            });
        },

        dealClicked: function(dealID) {
            $.post(preRoute+"promos/click.json", {dealID: dealID}, function(data) {
                if (data.content != 'OK') {
                    // todo what?
                    alert(data.error);
                }
            });
        },

        markReadProvider: function(providerID, status) {
            if (typeof(markMsgProvider[providerID]) != 'undefined')
                status = markMsgProvider[providerID];

            var dealIDs = [];
            if(isLocation == 'region'){
                $('[data-provider-id=1] [data-deal-id]').each(function(k, v){
                    dealIDs.push($(v).attr('data-deal-id'));
                });
                dealIDs = dealIDs.join(",");
            }

            var allButtons = $('[data-provider-id='+providerID+'] [data-deal-id] .action [id ^= "markSmall_"]');
            var thisObj = this;

            this.allButtonsChange = function(buttons, status) {
                var dealID;
                for (let i = 0; i < buttons.length; i++) {
                    dealID = $(buttons[i]).closest('[data-deal-id]').attr('data-deal-id');
                    if (status == 1) {
                        markMsg[dealID] = 0;
                        $(buttons[i]).text(Translator.trans('promotion.mark-as-read', {}, 'promotions'));
                    } else {
                        markMsg[dealID] = 1;
                        $(buttons[i]).text(Translator.trans('promotion.mark-as-unread', {}, 'promotions'));
                    }
                }
            };

            allButtons.attr('disabled', 'disabled');

            buttonAjax($('#markProvider_' + providerID), preRoute+"promos/markProviderAll.json", {providerID: providerID, status: status, dealIDs: dealIDs}, 'json', 'POST', function(data) {
                if (data.content != 'OK') {
                    alert(data.error);
                } else {
                    var provider = $('[data-provider-id='+providerID+']'),
                        providerUnread = parseInt(provider.find('.promotion-title span[rel=unread]').html()),
                        providerTotal = parseInt(provider.find('.promotion-title span[rel=total]').html()),
                        partUnread = parseInt(provider.closest('.promotions').find('.title-note span[rel=unread]').html());
                    if (status == 1) {
                        $('[data-provider-id='+providerID+'] [data-deal-id]').removeClass('read');
                        $('#markProvider_' + providerID + '').text(Translator.trans('promotion.mark-read-all', {}, 'promotions'));
                        markMsgProvider[providerID] = 0;
                        partUnread = partUnread + providerTotal - providerUnread;
                        providerUnread = providerTotal;
                    } else {
                        $('[data-provider-id='+providerID+'] [data-deal-id]').addClass('read');
                        $('#markProvider_' + providerID + '').text(Translator.trans('promotion.mark-unread-all', {}, 'promotions'));
                        markMsgProvider[providerID] = 1;
                        partUnread = partUnread - providerUnread;
                        providerUnread = 0;
                    }

                    provider.find('.promotion-title span[rel=unread]').html(providerUnread);
                    provider.closest('.promotions').find('.title-note span[rel=unread]').html(partUnread);
                    allButtons.removeAttr('disabled');
                    thisObj.allButtonsChange(allButtons, status);
                }
            });
        },

        markDeal: function(dealID, status, action) {
            if (typeof(markDealMsg[action][dealID]) != 'undefined')
                status = markDealMsg[action][dealID];
            var varMarkAsAction,
                varMarkAsUnaction;
            switch(action){
                case 'Apply':
                    varMarkAsAction = Translator.trans('promotion.mark-as-applied', {}, 'promotions');
                    varMarkAsUnaction = Translator.trans('promotion.unmark-as-applied', {}, 'promotions');
                    break;
                case 'Follow':
                    varMarkAsAction = Translator.trans('promotion.follow-up', {}, 'promotions');
                    varMarkAsUnaction = Translator.trans('promotion.undo-follow-up', {}, 'promotions');
                    break;
            }
            buttonAjax($('#mark' + action + '_' + dealID), preRoute+"promos/mark" + action + ".json", {dealID: dealID, status: status}, 'json', 'POST', function(data) {
                if (data.content != 'OK') {
                    alert(data.error);
                } else {
                    var deal = $('[data-deal-id='+dealID+']');
                    var className = (action == 'Apply') ? 'applied' : 'follow';
                    if (status == 1) {
                        $('#mark' + action + '_' + dealID)
                            .removeClass('off')
                            .addClass('on')
                            .find('span').text(varMarkAsAction)
                            .closest('[data-deal-id]').removeClass(className);
                        markDealMsg[action][dealID] = 0;
                    } else {
                        $('#mark' + action + '_' + dealID)
                            .addClass('off')
                            .removeClass('on')
                            .find('span').text(varMarkAsUnaction)
                            .closest('[data-deal-id]').addClass(className);
                        markDealMsg[action][dealID] = 1;
                    }
                }
            });
        },

        popupDeepLinkDeal: function(providerID, dealID, act) {
            buttonAjax($('#register_' + dealID), preRoute+'promos/getPopupFrame.json', {providerID: providerID, dealID: dealID, action: act}, 'json', 'POST', function(data) {
                if (data.error != '') {
                    alert(data.error);
                } else {
                    var elem = $('<div>'+data.content+'</div>');
                    dialog.createNamed(
                        'promo-select-person', elem, {
                            autoOpen: true,
                            modal: true,
                            width: 700,
                            title: Translator.trans('promotion.select-person', {}, 'promotions'),
                            close: function() {
                                dialog.get('promo-select-person').destroy();
                            }
                        }
                    );
                }
            });
        }
    };

    $(function() {
        $('.styled-select.inline').each(function(){
            const width = $(this).width();
            $(this).css({"width":width + 30});
            $(this).find('select').css({
                "width": width + 60
            });
        });
        $('.promotion-title .title').each(function(){
            $(this).html($(this).text().replace(new RegExp('\\([^\\(\\)]+\\)', 'gi'), '<span class="silver">$&</span>'));
        });
        $('[id$="PromoHead"]').click(function() {
            promo.toogleHead($(this).parent());
        });
        $('.promotions-blk > .promotion-title').click(function() {
            promo.toogleProvider($(this).parent());
        });
        $('.promotions-blk .promotion-list-title').click(function() {
            promo.toogleItem($(this).parent());
        });
        $(document).on('click', '#promotions-choose-person a', function(){
            dialog.get('promo-select-person').close();
        });
        promo.leadToPromo();
    });

    return promo;
});



