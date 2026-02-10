define(['jquery-boot', 'routing', 'translator-boot'], function ($) {
    var pollTimeout = null;
    var methods = {
        getCartId: function() {
            return $('[data-cart-id]').data('cart-id');
        },
        poll: function(){
            $.ajax({
                url: Routing.generate('aw_cart_bitcoin_status', {id: methods.getCartId()}),
                complete: function(response){
                    if(response.responseText === 'complete') {
                        window.location = Routing.generate('aw_cart_common_complete', {id: methods.getCartId()});
                    } else {
                        methods.listen();
                    }
                }
            });
        },
        listen: function() {
            if(pollTimeout)
                clearTimeout(pollTimeout);
            pollTimeout = setTimeout(function(){
                methods.poll();
            }, 3000);
        }
    };
    $('input[value=10]').on('click', function(e) {
        // todo fail
        $.ajax({
            url: Routing.generate('aw_cart_bitcoin_prepare'),
            success: function(){
                methods.listen();
            }
        });
    });
});