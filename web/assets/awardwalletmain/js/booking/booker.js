$(function () {
    $(document)
        .on('click', '#respond-block .booker-nav-blk a:not(#add-trip-btn)', function (e, focus) {
            if (typeof(focus) == "undefined") focus = true;
            e.preventDefault();
            var button = $(this),
                isCancelButton = (button.attr('data-cancel-btn') !== undefined),
                active = $("#respond-block > div[id]:visible").attr('id'),
                target = button.data('target'),
                all_btns = $("#respond-block .booker-nav-blk li"),
                cancel_btn = $("#respond-block .booker-nav-blk a[data-cancel-btn]")
                    .closest('li');
            if (active != target) {
                $("#respond-block > div[id]:visible:not([id^=darkfader])").hide().promise().always(function () {
                    $("#respond-block > #" + target).show().promise().always(function () {
                        if (focus) {
                            $.scrollTo('#' + target, 800, {onAfter: function () {
                                $("#" + target).find("input[type=text],textarea").filter(":visible").first().focus();
                            }, offset: {top: -50}});
                        }
                    });
                });
            }
            // show/hide buttons

            if (isCancelButton) {
                all_btns.show();
                cancel_btn.hide();
            } else {
                all_btns.hide();
                cancel_btn.show();
            }
            // title
            $('#respond-block .booker-title span:first').text(button.data('title'));

            return false;
        })

        .on('click', '#aw-email-link', function (e) {
            e.preventDefault();
            var container = $('<div/>'),
                email = $(e.target).data('email');

            container.html('<p>In order to add travel reservations to this users\'s AwardWallet profile please forward them to:</p><br/><a href="mailto:' + email + '">' + email + '</a>');
            $('body').append(container);
            container.dialog({
                title: 'AwardWallet Personal Mailbox',
                width: 400,
                modal: true,
                resizable: false,
                buttons: [
                    {
                        text: 'Close',
                        "class": 'btn-blue',
                        click: function(){
                            container.dialog('close');
                        }
                    }
                ],
                close: function() {
                    container.remove();
                }
            });
        })
});