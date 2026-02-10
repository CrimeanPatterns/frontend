require(['jquery-boot'], function ($) {
    $(function () {
        var checboxes = $('.one-card-row input.user'),
            proceedBtn = $('#proceed'),
            errorLabel = $('#error'),
            counter = {
                total: parseInt($('#total').text()),
                used: parseInt($('#used').text()),
                left: parseInt($('#left').text())
            };

        checboxes.on('click change', function () {
            var checked = checboxes.filter(function (id, ui) {
                return $(ui).is(':checked');
            });

            if ((counter.left - checked.length) < 0) {
                errorLabel.slideDown();
            } else {
                errorLabel.slideUp();

                $('#left').text(counter.left - checked.length);
                $('#used').text(counter.used + checked.length);
            }
            proceedBtn.prop('disabled', ((counter.left - checked.length) < 0) || checked.length == 0);

        }).prop('checked', false);

        //$('#check_all').on('click', function (e) {
        //    checboxes.prop('checked', $(e.target).is(':checked')).trigger('change');
        //})
    })
});