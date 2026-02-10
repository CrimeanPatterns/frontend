require(['jquery'], function($) {

    function updateEditLink(a, input) {
        a.attr('href', 'edit.php?Schema=' + a.data('schema') + '&ID=' + input.val());
        a.toggle(input.val() != '');
    }

    $('a.schema-lookup-edit').each(function() {
        var a = $(this);
        var input = $('#fld' + a.data('field'));
        updateEditLink(a, input);
        input.on('change', function() {
            updateEditLink(a, input);
        })
    })
})