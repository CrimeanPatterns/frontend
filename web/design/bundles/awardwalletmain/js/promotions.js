var preRoute = "";
var isLocation = "home";
if(window.location.href.indexOf('region') != -1){
    preRoute = "../../";
    isLocation = "region";
}



var promo = {
    processing: [],
    toogleItem: function(promoID) {
        var headElem = this.getElem(promoID, 'promo', 'Head');
        var bodyElem = this.getElem(promoID, 'promo', 'Body');
        if (this.isOpenItem(promoID)) {
            promo.processing['item'] = true;
            bodyElem.slideUp(300, function() {
                headElem.addClass('listItemClose').removeClass('listItemOpen');
                promo.processing['item'] = false;
            });
        } else {
            if(typeof(markMsg[promoID]) == 'undefined' || (typeof(markMsg[promoID]) != 'undefined' && markMsg[promoID] == 0)){
                markAsRead(promoID, 0);
            }
            promo.processing['item'] = true;
            headElem.removeClass('listItemClose').addClass('listItemOpen');
            bodyElem.slideDown(300, function() {
                promo.processing['item'] = false;
            });
        }
    },

    toogleProvider: function(ID) {
        /*if(isProvider == 'undefined')
         isProvider = false;   */

        var providerID = ID;
        /*if(isProvider)
         providerID = ID;
         else
         providerID = $('#dealHead_'+ID).parent().replace('providerBody_','');*/

        var headElem = this.getElem(ID, 'provider', 'Head');
        var bodyElem = this.getElem(ID, 'provider', 'Body');
        if (!this.isOpenProvider(providerID)) {
            headElem.removeClass('blueHeadClose').addClass('blueHeadOpen');
            promo.processing['provider'] = true;
            bodyElem.slideDown(300, function() {
                promo.processing['provider'] = false;
            });
        } else {
            promo.processing['provider'] = true;
            bodyElem.slideUp(300, function() {
                promo.processing['provider'] = false;
                headElem.addClass('blueHeadClose').removeClass('blueHeadOpen');
            });
        }
    },

    toogleHead: function(ID) {
        var headElem = this.getElem(ID, 'head', 'Head');
        var bodyElem = this.getElem(ID, 'head', 'Body');
        if (headElem.hasClass('redHeadClose')) {
            headElem.removeClass('redHeadClose').addClass('redHeadOpen');
            promo.processing['head'] = true;
            bodyElem.slideDown(300, function() {
                promo.processing['head'] = false;
            });
        } else {
            promo.processing['head'] = true;
            bodyElem.slideUp(300, function() {
                headElem.addClass('redHeadClose').removeClass('redHeadOpen');
                promo.processing['head'] = false;
            });
        }
    },

    getElem: function(ID, kind, half) {
        switch (kind) {
            case 'promo':
                return $('#deal' + half + '_' + ID);
                break;
            case 'provider':
                return $('#provider' + half + '_' + ID);
                break;
            case 'head':
                return $('#' + ID + 'Promo' + half + '');
                break;
        }
    },

    isOpenItem: function(ID) {
        if (this.getElem(ID, 'promo', 'Head').hasClass('listItemOpen'))
            return true;
        return false;
    },

    isOpenProvider: function(ID) {
        if (this.getElem(ID, 'provider', 'Head').hasClass('blueHeadOpen'))
            return true;
        return false;
    },

    isOpenHead: function(ID) {
        if (this.getElem(ID, 'head', 'Head').hasClass('redHeadOpen'))
            return true;
        return false;
    },

    headElemByPromoID: function(ID) {
        var promoElem = this.getElem(ID, 'promo', 'Head');
        var headID = promoElem.closest(".redBody").attr('id').replace(/PromoBody/, '');
        return this.getElem(headID, 'head', 'Head');
    },

    providerElemByPromoID: function(ID) {
        var promoElem = this.getElem(ID, 'promo', 'Head');
        var providerID = promoElem.closest("div[id ^= providerBody_]").attr('id').replace(/providerBody_/, '');
        return this.getElem(providerID, 'provider', 'Head');
    }
}

$(function() {
    $('.redHeadOpener').click(function() {
        var id = $(this).attr('id').replace(/PromoHead/, '');
        promo.toogleHead(id);
    })

    $('.blueHeadOpener > .title').click(function() {
        var id = $(this).parent().attr('id').replace(/providerHead_/, '');
        promo.toogleProvider(id);
    })

    $('.listItem > .title').click(function() {
        var id = $(this).parent().attr('id').replace(/dealHead_/, '');
        promo.toogleItem(id);
    });

    leadToPromo();
});

function popupDeepLinkDeal(providerID, dealID, act) {
    buttonAjax($('#register_' + dealID), preRoute+'promos/getPopupFrame.json', {providerID:providerID, dealID:dealID, action: act}, 'json', 'POST', function(data) {
        if (data.error != '') {
            alert(data.error);
        } else {
            $('#popupDeepLinkDealFrame').html(data.content);
            showPopupWindow(document.getElementById('popupDeepLinkDeal'), true);
        }
    });
}
;

var markMsg = [];
function markAsRead(dealID, status) {
    if (typeof(markMsg[dealID]) != 'undefined')
        status = markMsg[dealID];
    var arr = ['isArray', $('#mark_' + dealID), $('#markSmall_' + dealID)];
    buttonAjax(arr, preRoute+"promos/mark.json", {dealID: dealID, status:status}, 'json', 'POST', function(data) {
        if (data.content != 'OK') {
            alert(data.error);
        } else {
            var providerID = $('#dealHead_' + dealID).closest('div[id ^= providerBody]').attr('id').replace(/providerBody_/, '');
            var partID = $('#dealHead_' + dealID).closest('div[id $= PromoBody]').attr('id').replace(/PromoBody/, '');
            var providerUnread = parseInt($('#providerHead_' + providerID + ' .countBlock span[rel=unread]').html());
            var providerTotal = parseInt($('#providerHead_' + providerID + ' .countBlock span[rel=total]').html());
            var partUnread = parseInt($('#' + partID + 'PromoHead .countBlock span[rel=unread]').html());
            if (status == 1) {
                $('#dealHead_' + dealID).removeClass('listItemRead');
                $('#mark_' + dealID + ' input').val(varMarkAsRead);
                $('#markSmall_' + dealID + ' input').val(varMarkAsRead);
                markMsg[dealID] = 0;
                providerUnread++;
                partUnread++;
                if (providerUnread > 0) {
                    $('#markProvider_' + providerID + ' input').val(varMarkAsReadProvider);
                    markMsgProvider[providerID] = 0;
                }

            } else {
                $('#dealHead_' + dealID).addClass("listItemRead");
                $('#mark_' + dealID + ' input').val(varMarkAsUnread);
                $('#markSmall_' + dealID + ' input').val(varMarkAsUnread);
                markMsg[dealID] = 1;
                providerUnread--;
                partUnread--;
                if (providerUnread == 0) {
                    $('#markProvider_' + providerID + ' input').val(varMarkAsUnreadProvider);
                    markMsgProvider[providerID] = 1;
                }
            }
            $('#providerHead_' + providerID + ' .countBlock span[rel=unread]').html(providerUnread);
            $('#' + partID + 'PromoHead .countBlock span[rel=unread]').html(partUnread);
        }
    });
}
;

var markMsgProvider = [];
function markReadProvider(providerID, status) {
    if (typeof(markMsgProvider[providerID]) != 'undefined')
        status = markMsgProvider[providerID];

    var dealIDs = null;
    if(isLocation == 'region'){
        dealHeadIDs = $('#providerBody_' + providerID + ' > .listItem');
        console.log(dealHeadIDs.length);
        var dealIDs = '';
        for(i = 0; i < dealHeadIDs.length; i++){
            dealIDs += $(dealHeadIDs[i]).attr('id').replace(/dealHead_/,'') + ",";
        }
        dealIDs = dealIDs.substr(0, dealIDs.length-1);
    }

    var allButtonsHead = $('#providerBody_' + providerID + ' > .listItem > div[id ^= markSmall_] input');
    var allButtonsBody = $('#providerBody_' + providerID + ' > .listItemBody  div[id ^= mark_] input');
    var thisObj = this;

    this.allButtonsChange = function(buttons, status, elem) {
        var dealID;
        for (i = 0; i < buttons.length; i++) {
            if (elem == 'head')
                var dealID = $(buttons[i]).parent().attr('id').replace(/markSmall_|mark_/, '');
            else
                var dealID = $(buttons[i]).closest('div.overallButton').attr('id').replace(/markSmall_|mark_/, '');
            if (status == 1) {
                markMsg[dealID] = 0;
                $('#mark_' + dealID + ' input, #markSmall_' + dealID + ' input').val(varMarkAsRead);
            } else {
                markMsg[dealID] = 1;
                $('#mark_' + dealID + ' input, #markSmall_' + dealID + ' input').val(varMarkAsUnread);
            }
        }
    }

    allButtonsHead.attr('disabled', 'disabled');
    allButtonsBody.attr('disabled', 'disabled');

    buttonAjax($('#markProvider_' + providerID), preRoute+"promos/markProviderAll.json", {providerID: providerID, status:status, dealIDs:dealIDs}, 'json', 'POST', function(data) {
        if (data.content != 'OK') {
            alert(data.error);
        } else {
            var partID = $('#providerHead_' + providerID).closest('div[id $= PromoBody]').attr('id').replace(/PromoBody/, '');
            var providerUnread = parseInt($('#providerHead_' + providerID + ' .countBlock span[rel=unread]').html());
            var providerTotal = parseInt($('#providerHead_' + providerID + ' .countBlock span[rel=total]').html());
            var partUnread = parseInt($('#' + partID + 'PromoHead .countBlock span[rel=unread]').html());
            if (status == 1) {
                $('#providerBody_' + providerID + ' .listItem').removeClass('listItemRead');
                $('#markProvider_' + providerID + ' input').val(varMarkAsReadProvider);
                markMsgProvider[providerID] = 0;
                partUnread = partUnread + providerTotal - providerUnread
                providerUnread = providerTotal;
            } else {
                $('#providerBody_' + providerID + ' .listItem').addClass('listItemRead');
                $('#markProvider_' + providerID + ' input').val(varMarkAsUnreadProvider);
                markMsgProvider[providerID] = 1;
                partUnread = partUnread - providerUnread
                providerUnread = 0;
            }
            $('#providerHead_' + providerID + ' .countBlock span[rel=unread]').html(providerUnread);
            $('#' + partID + 'PromoHead .countBlock span[rel=unread]').html(partUnread);
            allButtonsHead.removeAttr('disabled');
            allButtonsBody.removeAttr('disabled');
            thisObj.allButtonsChange(allButtonsHead, status, 'head');
            thisObj.allButtonsChange(allButtonsBody, status, 'body');

        }
    });
}

function dealClicked(dealID) {
    $.post(preRoute+"promos/click.json", {dealID: dealID}, function(data) {
        if (data.content != 'OK') {
            alert(data.error);
        }
    });
}
;

function leadToPromo() {
    var promoID = window.location.hash.replace(/#/, '');
    if (promoID != '') {
        if (!promo.isOpenItem(promoID)) {
            promo.toogleItem(promoID);
        }
        var proniderElem = promo.providerElemByPromoID(promoID);
        var providerID = proniderElem.attr('id').replace(/providerHead_/, '');
        if (!promo.isOpenProvider(providerID)) {
            promo.toogleProvider(providerID);
        }
        var headElem = promo.headElemByPromoID(promoID);
        var headID = headElem.attr('id').replace(/PromoHead/, '');
        if (!promo.isOpenHead(headID)) {
            promo.toogleHead(headID);
        }

        var isReady = true;
        var timer = setInterval(function() {
            isReady = true;
            for (i in promo.processing) {
                if (promo.processing[i] == true)
                    isReady = false;
            }
            if (isReady) {
                $.scrollTo('#dealHead_' + promoID, 400);
                clearInterval(timer);
            }

        }, 100);

    }
}

var markDealMsg;
markDealMsg = {
    'Apply': [],
    'Follow': []
};
function markDeal(dealID, status, action) {
    if (typeof(markDealMsg[action][dealID]) != 'undefined')
        status = markDealMsg[action][dealID];
    var varMarkAsAction;
    var varMarkAsUnaction;
    switch(action){
        case 'Apply': varMarkAsAction = varMarkAsApply; varMarkAsUnaction = varMarkAsUnapply; break;
        case 'Follow': varMarkAsAction = varMarkAsFollow; varMarkAsUnaction = varMarkAsUnfollow; break;
    }
    buttonAjax($('#mark' + action + '_' + dealID), preRoute+"promos/mark" + action + ".json", {dealID: dealID, status:status}, 'json', 'POST', function(data) {
        if (data.content != 'OK') {
            alert(data.error);
        } else {
            if (status == 1) {
                $('#dealHead_' + dealID).removeClass('listItem' + action).removeClass("listItemDouble");
                $('#mark' + action + '_' + dealID).removeClass('mark' + action + 'Off').addClass('mark' + action + 'On');
                $('#mark' + action + '_' + dealID + ' input').val(varMarkAsAction);
                markDealMsg[action][dealID] = 0;
            } else {
                $('#dealHead_' + dealID).addClass('listItem' + action);
                if($('#dealHead_' + dealID).hasClass('listItemApply') && $('#dealHead_' + dealID).hasClass('listItemFollow'))
                    $('#dealHead_' + dealID).addClass('listItemDouble');
                $('#mark' + action + '_' + dealID).addClass('mark' + action + 'Off').removeClass('mark' + action + 'On');
                $('#mark' + action + '_' + dealID + ' input').val(varMarkAsUnaction);
                markDealMsg[action][dealID] = 1;
            }
        }
    });
}

function markManual(dealID){
    buttonAjax($('#markManual_' + dealID), preRoute+"promos/markManual.json", {dealID: dealID, status: 0}, 'json', 'POST', function(data) {
        if (data.content != 'OK') {
            alert(data.error);
        } else {
            $('#markManual_' + dealID).fadeOut(100);
        }
    });
}



