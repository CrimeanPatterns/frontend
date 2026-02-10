define(['jquery-boot', 'lib/customizer', 'jqueryui', 'routing', 'common/alerts', 'translator-boot'], function ($, customizer) {
    $(function () {
        $(document).on('click', 'a.delete', function (e) {
            e.preventDefault();

            jConfirm(Translator.trans(/**@Desc("Are you sure want to delete this pending account?.")*/'pending.confirm-delete'), function () {
                var id = $(e.target).closest('form').data('id');
                $(e.target).addClass('loader');

                var data = [
                    {
                        id: id,
                        isCoupon: false,
                        useragentid: 'my'
                    }
                ];

                $.ajax({
                    type: "POST",
                    url: Routing.generate('aw_account_json_remove'),
                    data: JSON.stringify(data),
                    success: function (resp) {
                        if (resp.success) {
                            $.ajax({
                                url: location.href,
                                type: 'GET',
                                success: function (html) {
                                    $(e.target).removeClass('loader');
                                    var page = location.href.match(/page=(\d+)$/);
                                    if (page && parseInt(page[1]) > 1 && /no-result/.test(html)) {
                                        location.href = Routing.generate('aw_account_pending_list') + '?page=' + (parseInt(page[1]) - 1);
                                    } else {
                                        $(e.target).closest('.main-blk').slideUp(300, function () {
                                            $('.content').replaceWith(html);
                                            var counter = $('a[href*="pending"] .count');
                                            counter.text(parseInt(counter.text()) - 1);
                                        });
                                    }
                                }
                            });
                        }
                    },
                    dataType: 'json'
                });
            });
        });

        $(document).on('submit', '.main-blk form', function (e) {
            e.preventDefault();
            var blk = $(e.target).closest('.main-blk');
            var btn = blk.find('button[type="submit"]').addClass('loader');

            $.ajax({
                type: 'POST',
                url: Routing.generate('aw_account_pending_save', {'accountId': $(e.target).data('id')}),
                data: $(e.target).serialize(),
                success: function (resp) {
                    btn.removeClass('loader');
                    if (resp.success) {
                        window.location = Routing.generate('aw_account_edit', {
                            accountId: $(e.target).data('id'),
                            autosubmit: true
                        });
                    } else {
                        if (resp.form) {
                            $(e.target).replaceWith(resp.form);
                            customizer.initAll($(e.target));
                        }
                    }
                }
            })
        });
    })
});
