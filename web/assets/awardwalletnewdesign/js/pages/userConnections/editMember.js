define([
    'jquery-boot',
    'lib/dialog'
], function($, dialog) {
    'use strict';
    $(document).ready(function() {

        var $avatar            = $('.js-avatar'),
            $avatarField       = $('#family_member_avatar'),
            $avatarImage       = $avatar.find('img'),
            $avatarDeleteField = $('#family_member_avatarRemove');

        $('.js-avatar-delete')
            .on('click', function(e) {
                e.preventDefault();
                $avatarImage.attr('src', '/assets/awardwalletnewdesign/img/no-avatar.gif');
                $avatarDeleteField.val(1);
            });
        $avatarField.on('change', function(e) {
            var src;
            if ('files' in this) {
                var obj = this.files[0];
                src     = window.URL ?
                    window.URL.createObjectURL(obj) :
                    window.webkitURL.createObjectURL(obj);
            } else {
                if (/fake/.test(this.value)) {
                    // ie security fixed
                } else {
                    src = this.value;
                }
            }
            $avatarImage.attr('src', src);
            $avatarDeleteField.val(0);
        });

        $avatarImage.on('load', function() {
            var css, ratio = $(this).width() / $(this).height();
            if (ratio >= 1) css = {width : 'auto', height : '100%'};
            else css = {width : '100%', height : 'auto'};
            $(this).css(css);
        });

    });
});