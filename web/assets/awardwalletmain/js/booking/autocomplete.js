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
    $(document)
        .on('focus', '.cp-autocomplete:not(.ui-autocomplete-input)', function (e) {
            var param = $(this).data('param');
            if (param == null)
                param = '';
            $(this).autocomplete({
                source: function (request, response) {
                    var term = request.term.replace(/[^A-Za-z0-9А-Яа-я ]+/g, '');
                        $.ajax({
                            url: Routing.generate('aw_booking_json_getallprogs', { query: term, param: param }),
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
        })
        .on('keydown', '.airport-autocomplete:not(.ui-autocomplete-input)', function (e) {
            if (
                !$.trim($(e.target).val()) &&
                (e.keyCode === 0 || e.keyCode === 32)
            ) {
                e.preventDefault();
            }
        })
        .on('focus', '.airport-autocomplete:not(.ui-autocomplete-input)', function (e) {
            $(this).autocomplete({
                delay: 0,
                minLength: 3,
                source: function (request, response) {
                    if (!request.term || request.term.length < 3) {
                        return;
                    }

                    var self = this;

                    $.get(Routing.generate("find_airport", {query: request.term}), function (data) {
                        response(data.map(function (item) {
                            return {label: item.airname, value: item.aircode, city: item.cityname, country: item.countryname};
                        }));
                    })
                },
                create: function () {
                    $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                        var regex = new RegExp("(" + this.element.val() + ")", "gi");
                        var itemLabel = item.label.replace(regex, "<b>$1</b>");
                        var city = item.city.replace(regex, "<b>$1</b>");
                        var itemValue = item.value.replace(regex, "<b>$1</b>");
                        var html = '<span class="silver">' + itemValue + '</span>' + itemLabel + '<span>' + city + ', ' + item.country + '</span>';
                        return $('<li></li>')
                            .data("item.autocomplete", item)
                            .append($('<a class="address-location"></a>').html(html))
                            .appendTo(ul);
                    };
                }
            });
        });
});
