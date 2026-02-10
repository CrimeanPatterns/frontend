import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: '#reservation',
        template: '#segmentTemplate',
        autocompleteField: 'address',
        blockContainer: '#addedSegments',
        block: '.segment',
    }).init();

    $('body').trigger('init_places', [
        {
            selector: 'input[id*="_hotelName"]',
            options: {
                source: function (request, response) {
                    var self = this;
                    $.get(
                        Routing.generate('google_hotels', { query: encodeURIComponent(request.term) }),
                        function (data) {
                            $(self.element).removeClass('loading-input');
                            if (!data) return;
                            response(
                                data.map(function (item) {
                                    return {
                                        label: item.name,
                                        address: item.formatted_address,
                                        place_id: item.place_id,
                                    };
                                }),
                            );
                        },
                    );
                },
                select: function (event, ui) {
                    event.preventDefault();
                    $(event.target).val(ui.item.label);
                    $('#reservation_address').val(ui.item.address).trigger('change');
                    $.get(Routing.generate('google_place_details', { placeId: ui.item.place_id }), function (data) {
                        $('#reservation_phone').val(data.formatted_phone_number);
                    });
                },
                create: function () {
                    $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                        var regex = new RegExp('(' + this.element.val() + ')', 'gi');
                        var itemLabel = item.label.replace(regex, '<b>$1</b>');
                        var itemAddress = item.address.replace(regex, '<b>$1</b>');
                        return $('<li></li>')
                            .data('item.autocomplete', item)
                            .append(
                                $('<a></a>').html(
                                    itemLabel + '<br/><span style="font-size: smaller;">' + itemAddress + '</span>',
                                ),
                            )
                            .appendTo(ul);
                    };
                },
            },
        },
    ]);
});
