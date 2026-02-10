import $ from 'jquery-boot';
import dialog from 'lib/dialog';
import 'jqueryui';
import 'translator-boot';

$('.bookingAdv a').on('click', function (event) {
    dialog.fastCreate(
        window.booking_adv_title,
        window.booking_adv_body,
        true,
        false,
        [
            {
                text: window.booking_adv_button,
                click: function () {
                    $(this).dialog('close');
                },
                class: 'btn-blue',
            },
        ],
        500,
    );
});
