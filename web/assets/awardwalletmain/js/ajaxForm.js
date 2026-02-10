ajaxSendForm = {

    show_form_message: function (data, form) {
        var m = $('#form_message', form);
        var msg = data['message'] || false;
        if (!msg && data['errors'])
            msg = data['errors']['form_message'] || false;
        if (msg) {
            //var d=data['form_message'];
            if (m.length < 1) {
                $('div', form).eq(0).append('<div id="form_message" class="message"></div>');
                m = $('#form_message', form);
            }
            if (data.success) {
                $(m).addClass('success').removeClass('error');
            } else {
                $(m).removeClass('success').addClass('error');
            }
            m.html('<p>' + msg + '</p>');
        } else {
            if (m.length > 0) {
                m.remove();
            }
        }

    },
    onsuccess: null,
    onfailure: null,
    ondone: null,
    callback: function (data, form, custom) {
        if (typeof data == 'string')
            data = eval('(' + data + ')');
        if (custom) {
            if (false === custom(data))return false;
        }
        if (!data.success) {
            ajaxSendForm.show_form_message(data, form);
            if (ajaxSendForm.onfailure) {
                if (false === ajaxSendForm.onfailure()) return false;
            }
        } else {
            if (ajaxSendForm.onsuccess) {
                if (false === ajaxSendForm.onsuccess()) return false;
            }
        }
        if (ajaxSendForm.ondone) {
            if (false === ajaxSendForm.ondone(data)) return false;
        }
    },
    login_send: function (form) {
        var data = {};//,
        //form=$(this).get(0);
        $('input', form).each(function () {
            if (this.name.match(/\[Login\]$/))data['login'] = this.value;
            if (this.name.match(/\[Pass\]$/))data['password'] = this.value;
            if (this.name.match(/\[keep\]$/))data['remember_me'] = this.checked;
        });
        $.ajax({
            url: form.action,
            type: "POST",
            data: data
        }).done(function (data) {
                ajaxSendForm.callback(data, form);
            });
        return false;
    }

}
