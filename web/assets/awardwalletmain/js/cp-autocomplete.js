//Autocomplete highlighting
$.ui.autocomplete.prototype._renderItem = function (ul, item) {
    var regex = new RegExp("(" + this.element.val().replace(/[^A-Za-z0-9А-Яа-я]+/g, '') + ")", "gi"),
        html = $('<div/>').text(item.label).html().replace(regex, "<b>$1</b>");
    return $("<li></li>")
        .data("item.autocomplete", item)
        .append($("<a></a>").html(html))
        .appendTo(ul);
};

$(function () {
    // Custom programm autocomplete
    $(document).on('focus', '.cp-autocomplete:not(.ui-autocomplete-input)', function (e) {
        $(this).autocomplete({
            source: function (request, response) {
                var term = request.term.replace(/[^A-Za-z0-9А-Яа-я ]+/g, '');
                    $.ajax({
                        url: Routing.generate('aw_booking_json_getallprogs', { query: term }),
                        dataType: "json",
                        success: function (data) {
                            response(data);
                        }
                    });
            },
            select: function (event, ui) {
                $(event.target).val(ui.item.value);
                $(event.target).trigger('change');
            },
            minLength: 2
        })
    });
});
