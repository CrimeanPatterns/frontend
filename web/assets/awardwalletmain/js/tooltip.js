$(document).ready(function () {
    $('.seg-tooltip').tooltip({
        items: 'td',
        content: '<div class="loader"></div>',
        position:{
            my:'left-100 top',
            at:'left bottom',
            collision:'none'
        },
        open: function () {
            var elem = $(this);
            $.ajax($(elem).data('tooltip')).done(function (data) {
                elem.tooltip('option', 'content', data);
            });
        }
    })
})
