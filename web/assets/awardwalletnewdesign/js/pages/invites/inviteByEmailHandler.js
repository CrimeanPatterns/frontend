define(['jquery-boot', 'jqueryui', 'translator-boot', 'common/alerts'], function ($) {
    return function (e, csrf) {
        var email = $(e.target).find("input");
        email.next().hide();
        email.addClass('loading-input');
        // todo fail!
        $.ajax({
            url: "/invites/send",
            method: "POST",
            suppressErrors: true,
            data: {
                inviteEmail: email.val(),
                requestType: "json",
                CSRF: csrf
            },
            success: function (response) {
                email.removeClass('loading-input');
                let jsonResponse = (typeof response !== 'object' && response !== null) ? JSON.parse(response) : response;

                if (jsonResponse && jsonResponse.success) {
                    $("<div>" + Translator.trans("invation.sent.to") + " " + email.val() + "</div>").appendTo("body")
                        .dialog({
                            width: 400,
                            title: Translator.trans("thank.you"),
                            modal: true,
                            buttons: [
                                {
                                    'text': Translator.trans("button.ok"),
                                    'class': 'btn-blue',
                                    'click': function () {
                                        $(this).dialog('close');
                                        email.val("");
                                    }
                                }
                            ]
                        })
                } else {
                    var error = jsonResponse ?
                        jsonResponse.error :
                        $(response).find('.errorFrm').text();
                    jAlert({content: error, type: 'error'});
                }
            }
        })

    }
});