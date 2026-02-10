(function($){
    var methods = {
        show: function(options) {
            if (typeof(options) == 'function')
                options = {complete: options};
            var settings = $.extend(true, {
                faderCss: {
                    'display': 'none',
                    'position': 'absolute',
                    'top': 0,
                    'left': 0,
                    'z-index': 101,
                    'height': '100%',
                    'width': '100%',
                    'background-color': 'white'
                },
                duration: 2000,
                opacity: 0.5,
                complete: function(){}
            }, options);

            var fader = $('<div>&nbsp;</div>').css(settings.faderCss);
            if (typeof(this.attr('data-darkfader')) != "undefined")
                return;
            var id = 'darkfader_' + new Date().getTime();
            this.attr('data-darkfader', id).css({'position': 'relative'});
            fader.clone().attr('id', id).appendTo(this).css({opacity: 0}).show().stop().animate({
                opacity: settings.opacity
            }, {
                duration: settings.duration,
                complete: settings.complete
            });
        },
        hide: function(options) {
            if (typeof(options) == 'function')
                options = {complete: options};
            var settings = $.extend( {
                duration: 600,
                opacity: 0,
                complete: function(){}
            }, options);
            var faderId = this.attr('data-darkfader');
            if (typeof(faderId) == "undefined")
                return;
            this.removeAttr('data-darkfader');
            var fader = $('#'+faderId);
            if (fader.length == 0)
                return;

            fader.stop().animate({
                opacity: settings.opacity
            }, {
                duration: settings.duration,
                complete: function() {
                    $(this).remove();
                    settings.complete();
                }
            });
        }
    };

    $.fn.darkfader = function(method) {
        var args = Array.prototype.slice.call(arguments, 1);
        return this.each(function() {
            var $this = $(this);
            if (methods[method]) {
                return methods[method].apply($this, args);
            }
        });

    };

})(jQuery);