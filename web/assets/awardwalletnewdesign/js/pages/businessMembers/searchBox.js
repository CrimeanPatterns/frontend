define(['jquery-boot', 'jqueryui', 'routing'], function ($) {

    return function(dataRoute, add, onGetUrl) {
        $.ui.autocomplete.prototype._renderItem = function (ul, item) {
            var regex = new RegExp("(" + this.element.val().replace(/[^A-Za-z0-9А-Яа-я ]+/g, '') + ")", "gi"),
                    html = item.label;
            if (item.email && !item.extra)
                html += ", <b>" + item.email + "</b>";
            html += " (" + item.trips + ")";
            if (item.extra)
                html += " (" + Translator.trans(/** @Desc("sub-account of")*/'sub-account_of') + " " + item.extra + (item.email ? ", <b>" + item.email + "</b>" : '') + ")";

            html = html.replace(regex, "<b>$1</b>");
            return $("<li></li>")
                    .data("item.autocomplete", item)
                    .append($("<a></a>").html(item.label === NoResultsLabel ? item.label : html))
                    .appendTo(ul);
        };

        var traveler = $('#traveler'),
                NoResultsLabel = '<i class="icon-warning-small"></i>&nbsp; No members found',
                defaultValue = traveler.val(),
                lastResponse;

        $('form.search-form a.reset').click(function (e) {
            e.preventDefault();
            traveler.autocomplete('close').val('').trigger('autocompletechange').focus();
        });

        traveler.autocomplete({
            minLength: 2,
            delay: 300,
            source: function (request, response) {
                var element = $(this.element).attr('class', 'loading-input');
                lastResponse = $.ajax({
                    url: Routing.generate(dataRoute, {q: request.term, add: add}),
                    method: 'POST',
                    success: function (data, status, xhr) {
                        if ($.isEmptyObject(data)) {
                            data = {
                                label: NoResultsLabel
                            };
                        }
                        if (lastResponse === xhr) {
                            response(data);
                            element.attr('class', 'clear-input');
                        }
                    }
                })
            },
            select: function (event, ui) {
                if (ui.item.label === NoResultsLabel) {
                    return false;
                }

                $(event.target).val(ui.item.label);
                if ('my' === ui.item.value)
                    ui.item.value = '';

                const onGetUrlResult = onGetUrl(ui.item.value);
                if (onGetUrlResult) {
                    document.location.href = onGetUrlResult;
                } else {
                    defaultValue = ui.item.label;
                    traveler.val(ui.item.label);
                }
                return false;
            },
            focus: function (event, ui) {
                if (ui.item.label === NoResultsLabel) {
                    return false;
                }
                if (event.keyCode == 40 || event.keyCode == 38)
                    $(event.target).val(ui.item.label);
                return false;
            }

        }).click(function () {
            $(this).select();
        }).focus(function () {
            $(this).autocomplete("search");
        }).on("autocompletechange keyup", function (event, ui) {
            var input = $(event.target);
            if (event.keyCode == 27) {
                $(this).val(defaultValue);
            }
            if (input.val() === '') {
                input.attr('class', 'search-input');
            } else {
                input.attr('class', 'clear-input');
            }
        }).on('blur', function (e) {
            $(this).val(defaultValue).trigger('autocompletechange');
        });

        const $searchBox = $(traveler).closest(".search-box[data-position]");
        if ($searchBox.length) {
            traveler.autocomplete('option', 'position', $searchBox.data('position'));
        }

        if (defaultValue) {
            traveler.trigger('autocompletechange');
        }
    };

});
