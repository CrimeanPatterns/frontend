import $ from 'jquery-boot';
import 'pages/itinerary/form';

console.log('address load');
$('body').on('init_address', function (e, payload) {
    console.log('address init');
    var options = {
        delay: 500,
        minLength: 2,
        search: function (event, ui) {
            if ($(event.target).val().length >= 2) $(event.target).addClass('loading-input');
            else $(event.target).removeClass('loading-input');
        },
        open: function (event, ui) {
            $(event.target).removeClass('loading-input');
        },
    };
    $(payload.selector + ':not(.ui-autocomplete-input)')
        .off('keydown')
        .on('keydown', function (e) {
            if (!$.trim($(e.target).val()) && (e.keyCode === 0 || e.keyCode === 32)) e.preventDefault();
        })
        .autocomplete($.extend({}, options, payload.options));
});
