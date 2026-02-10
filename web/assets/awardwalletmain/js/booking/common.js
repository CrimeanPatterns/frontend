$(function () {
    var addTooltip = function(context) {
        context.find('.tipped').tooltip({
            position: {
                my: "center bottom-15",
                at: "center top",
                using: function (position, feedback) {
                    $(this).css(position);
                    $("<div>")
                        .addClass("arrow")
                        .addClass(feedback.vertical)
                        .addClass(feedback.horizontal)
                        .appendTo(this);
                }
            }
        });
    };

    // jQuery UI Tooltips on title attr
    $(document)
        .on('after_added', function(event, data){
            var context = (typeof(data['row']) == 'undefined') ? $(this) : data['row'];
            addTooltip(context);
        })
        .on('update_tips', function(){
            addTooltip($(this));
        });
    addTooltip($(document));

});