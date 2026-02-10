define(['jquery-boot', 'lib/dialog', 'jqueryui', 'routing', 'translator-boot'], function($, dialog) {
    let cacheResult = null;

    function showDialog(content) {
        dialog.fastCreate(
            '<div style="overflow-y: auto;">' + content + '</div>',
            {
                title : Translator.trans('menu.invite.coupon.available', {}, 'menu'),
                width : 600,
                maxHeight : 'auto',
                autoOpen : true,
                modal : true,
                buttons : [
                    {
                        'text' : Translator.trans('button.ok'),
                        'class' : 'btn-blue',
                        'click' : function() {
                            $(this).dialog('close');
                        }
                    }
                ]
            }
        );
    }

    return function(countCodes) {
        if (0 === parseInt(countCodes)) {
            return showDialog(Translator.trans('upgrade.message.text1'));
        }
        if (null !== cacheResult) {
            return showDialog(cacheResult);
        }

        $.get(Routing.generate('aw_coupon_get_available'), function(response) {
            let content = response.content;
            if ('undefined' === typeof response.coupons || 0 === response.coupons.length) {
                return showDialog(content);
            }

            let links = [];
            response.coupons.forEach(function(coupon) {
                links.push(coupon.Code + ' &mdash; <a href="' + Routing.generate('aw_users_usecoupon', { Code : coupon.Code }) + '">' + Translator.trans('use.coupon.link') + '</a>');
            });
            content += '<p>' + links.join('<br>') + '</p>';
            cacheResult = content;
            showDialog(content);
        }, 'json');

    };
});
