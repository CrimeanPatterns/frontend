define([
    'jquery-boot',
    'translator-boot'
], function($) {
    'use strict';

    const $headEmailPreview = $('#headEmailPreview');
    $(document).ready(function() {
        $('#user_gift_awplus_payType').change(function() {
            if (1 == $(this).val()) {
                $headEmailPreview
                    .find('.js-yearly-title').attr('hidden', 'hidden').end()
                    .find('.js-once-title').removeAttr('hidden');
            } else {
                $headEmailPreview
                    .find('.js-yearly-title').removeAttr('hidden').end()
                    .find('.js-once-title').attr('hidden', 'hidden');
            }
        }).trigger('change');

        const $previewFrame = $('#previewFrame');
        $('#user_gift_awplus_message').keyup(function() {
            let message = String($(this).val()).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'),
                height = 800;
            if ('' !== message) {
                message = message.replace(/^\r+|\n+$/g, '');
                height += 100 + (message.split("\n").length * 15);
            }

            if (undefined !== $previewFrame.get(0).contentWindow.render) {
                $previewFrame.css('min-height', height).get(0).contentWindow.render({
                    message : message.replace(/\n/g, '<br>'),
                });
            }
        }).trigger('keyup');

        function followingEmailUp() {
            let $block = $('.js-once-title>p', $headEmailPreview);
            let email = $('#user_gift_awplus_email').val();
            if ('' === email || !/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/.test(email)) {
                email = Translator.trans('error.email-required', {}, 'validators');
            }
            $block.text(Translator.trans('following-email-sent-to', {'email' : email}) + ':');
        }
        $('#user_gift_awplus_email').keyup(followingEmailUp);
        followingEmailUp();
    });
});