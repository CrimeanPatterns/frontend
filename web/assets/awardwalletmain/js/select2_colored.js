$(function () {
    function coloring(state) {
        var style = $(state.element).attr('style');
        return '<div style="' + style + '">' + state.text + '</div>'
    }

    $('select[data-type="select2"].colored').select2({
        minimumResultsForSearch: -1,
        width: 'element',
        formatResult: coloring,
        formatSelection: coloring,
        escapeMarkup: function(m) { return m; },
        dropdownCssClass: 'select2-dropdown-colored',
        containerCssClass: 'select2-container-colored'
    });
});