InputStyle = (function () {

    function checkBoxFire(event) {
        //event.stopPropagation();
        if (this.checked) {
            if (this.type == 'radio') {
                $('input[name="' + this.name + '"]').each(function () {
                    $(this.label).removeClass('checked');
                });
            }
            $(this.label).addClass('checked');
        }
        else {
            if (this.type != 'radio') {
                $(this.label).removeClass('checked');
            }
        }
    }

    return {
        styleSelects: true,

        select: function (e) {
            if (!InputStyle.styleSelects)
                return;
            var s = $(e);
            e = s.get(0);
            var r = s.prev('span');
            if (r.length < 1) {
                s.before('<span class="select-text"></span>');
                r = s.prev('span');
            }
            var sel = $('option:selected', s);
            if (s.outerWidth() > 34) {
                r.text(sel.text()).width(s.outerWidth() - 12);
                r.attr('style', sel.attr('style'));
            }
            if (s.hasClass('disabled')) {
                r.addClass('disabled');
            }
            if (!e.inputStyled) {
                s.on('change resize', function () {
                    var s = $(this),
                        r = $(this).prev('span');
                    r.text($('option:selected', s).text());
                    r.attr('style', $('option:selected', s).attr('style'));

                    if (s.outerWidth() > 34)
                        r.width(s.outerWidth() - 12);
                });
                e.inputStyled = true;
            }
        },

        select2: function (e) {
            var options = $(e).attr('data-select2-opt'),
                data = $(e).attr('data-init-data'),
                defaults = {};
            if ($.isArray(options))
                options = $.extend(defaults, (typeof options == 'undefined') ? {} : eval("(" + options + ")"));
            else
                options = eval("(" + options + ")");
            if (data != null)
                options['initSelection'] = function (element, callback) {
                    callback(eval("(" + data + ")"));
                };
            $(e).select2(options);
            $(e).addClass('select2-styled');
        },

        checks: function (e) {
            var c = $(e);
            e = c.get(0);
            var l = c.next('span');
            if (!l.length) {
                c.after('<span></span>');
                l = c.next('span').attr('tabindex', 0);
            }
            c.css('display', 'none');
            e.label = l.get(0);
            e.label.controlCheck = e;
            if (!e.inputStyled) {
                l.on('click', function () {
                    this.controlCheck.click();
                    return false;
                }).on('focus', function () {
                    $(this).addClass('focused');
                }).on('focusout', function () {
                    $(this).removeClass('focused');
                }).on('keyup', function (e) {
                    var el = $(this);
                    if (el.hasClass('focused') && e.keyCode == 32)
                        el.trigger('click');
                });

                c.click(checkBoxFire).on('change', checkBoxFire);
                e.inputStyled = true;
            }
            l.toggleClass('checked', e.checked);
            l.toggleClass('disabled', e.disabled);
        },

        date: function (e) {
            var id = $(e).attr('id').split('_datepicker')[0];
            var input = $('#' + id)[0];
            var defaultOptions = {
                altField: '#' + id,
                altFormat: 'yy-mm-dd',
                yearRange: "1910:c",
                changeYear: true,
                changeMonth: true,
                showButtonPanel: true,
                buttonImage: '/assets/awardwalletmain/images/calendar.png',
                showOn: 'both',
                beforeShow: function (el, obj) {
                    $(el).closest('.row').addClass('datePickerFocus');
                },
                onClose: function () {
                    $(this).closest('.row').removeClass('datePickerFocus');
                }
            };
            var options = $(e).attr('data-dp-opt');
            var isValidDate = function (inst) {
                var valid = true;
                try {
                    $.datepicker.parseDate($.datepicker._get(inst, "dateFormat"), (inst.input ? inst.input.val() : null), $.datepicker._getFormatConfig(inst));
                } catch (error) {
                    valid = false;
                }
                return valid;
            };
            options = $.extend(defaultOptions, (typeof options == 'undefined') ? {} : eval("(" + options + ")"));
            $(e).datepicker(options);
            $(e).keyup(function (event) {
                var inst = $.datepicker._getInst(event.target);
                if (!isValidDate(inst)) {
                    $($(this).datepicker('option', 'altField')).val($(this).val());
                    if (event.stopPropagation)
                        event.stopPropagation();
                    else
                        event.cancelBubble = true;
                }
            });
            try {
                var _date = $.datepicker.parseDate(options.altFormat, input.value);
                $(e).datepicker('setDate', _date);
            } catch (err) {
                $(e).val(input.value).trigger('keyup');
            }
        },

        hover: function (over, apply) {
            over = $(over);
            over.get(0).goesToItem = (apply ? $(apply) : over).eq(0);
            over.on('mouseover', function () {
                this.goesToItem.addClass('hover');
            }).on('mouseout', function () {
                this.goesToItem.removeClass('hover');
            });
        },

        tabs: function (e, allTabs) {
            var a = $(e);
            e = a.get(0);
            var t = $('#' + a.attr('data-id') + '.tab');
            if (t.length) {
                if (a.hasClass('active')) {
                    t.css('display', '');
                } else {
                    t.css('display', 'none');
                }
                e.tabDiv = t;
                a.on('click', function () {
                    $(allTabs).each(function () {
                        if (this.tabDiv) {
                            this.tabDiv.css('display', 'none');
                            $(this).removeClass('active');
                        }
                    });
                    if (this.tabDiv) {
                        this.tabDiv.css('display', '');
                        $(this).addClass('active');
                        return false;
                    }
                });
            }
        },

        twoChoices: function (e) {
            var d = $(e),
                opt = eval('(' + (d.attr('data-widget-opt') || '{}') + ')'),
                e = d.get(0);

            var r = $('input[type="radio"]', e),
                hide = function (e) {
                    var cont = $('.two_choices_or_text_widget_container', e),
                        txt = cont.find('textarea, input[type="text"]');
                    cont.hide();
                    if (opt['yes_without_help']) {
                        d.nextAll('span.info').hide();
                    }
                    txt.val('').removeAttr('required');
                },
                show = function (e) {
                    var cont = $('.two_choices_or_text_widget_container', e),
                        txt = cont.find('textarea, input[type=text]:not(input[style])');
                    cont.show();
                    if (opt['yes_without_help']) {
                        d.nextAll('span.info').show();
                    }
                    if (opt['default_text']) {
                        if (!txt.val())
                            txt.val(opt['default_text']);
                    }
                    txt.attr('required', true);
                };
            r.each(function () {
                this.twoChoices = e;
                if (this.checked && !$(this).is(':last-of-type')) {
                    hide(e);
                }
            });
            if (!r.filter(":checked").length)
                hide(e);
            if (!e.inputStyled) {
                r.click(function () {
                    var e = this.twoChoices;
                    var d = $(e);
                    if (this.checked && !$(this).is(':last-of-type')) {
                        hide(e);
                    } else {
                        show(e);
                    }
                });
                e.inputStyled = true;
            }
            /*
             var c = function(){
             var container = $(this).closest('.two-choices');
             var options = container.attr('data-widget-opt');
             options = (typeof options == 'undefined')?{}:eval("("+options+")");
             if (container.find(":radio").last().is(':checked')) {
             container.find('[type=text]').attr('required', true).show();
             if (typeof options['yes_without_help'] != 'undefined' && options['yes_without_help'])
             container.nextAll('span.info').show();
             } else {
             container.find('[type=text]').removeAttr('required').hide();
             if (typeof options['yes_without_help'] != 'undefined' && options['yes_without_help'])
             container.nextAll('span.info').hide();
             }
             };
             */
            //$(document.body).off('click', ".two-choices :radio", c).on('click', ".two-choices :radio", c);
            //$(".two-choices :radio").trigger('click');
        },

        init: function (context, options) {
            context = (context == null) ? $('body') : context;
            var defaults = {datepicker: true};
            var settings = $.extend({}, defaults, options);
            var browser = browserDetectNav();
            if (browser[0] == 'MSIE' && browser[1] == 7)
                InputStyle.styleSelects = false;




            context.find('select:not(.select2)').each(function () {
                if ($(this).data('custom')) return;
                InputStyle.select(this);
            });

            context.find('input.select2:not(.select2-styled), select.select2:not(.select2-styled)').each(function () {
                InputStyle.select2(this);
            });

            context.find('input[type="checkbox"]').each(function () {
                InputStyle.checks(this);
            });
            context.find('input[type="radio"]').each(function () {
                InputStyle.checks(this);
            });

            if (settings.datepicker) {
                context.find("input[type='text'].date").each(function () {
                    InputStyle.date(this);
                });
            }
            // init two choices or text
            context.find('.radio.two-choices').each(function () {
                    InputStyle.twoChoices(this);
                }
            );
            $(document).on('focus', '.row input[type="text"], .row input[type="email"], .row input[type="password"], .row textarea', null, function () {
                $(this).closest('.row').addClass('focused');
                return false;
            });
            $(document).on('blur', '.row input[type="text"], .row input[type="email"], .row input[type="password"], row textarea', null, function () {
                $(this).closest('.row').removeClass('focused');
                return false;
            });
        }
    };
})();

$(function () {
    // Hack for internal statuses color
    $('#booking_request_properties_InternalStatus').find('option').each(function (id, el) {
        var div = $($(el).text());
        if (div && div.attr('style'))
            $(el).attr('style', div.attr('style'));
        $(el).text(div.text());
    });


    // datepicker i18n
    var initDatePicker = function (locale) {
        $.datepicker.setDefaults($.datepicker.regional[locale]);
        $('body').on('keyup', "input[type='text']." + $.datepicker.markerClassName, function (event) {
            var date;
            $(this).data('changed', true);
            try {
                date = $.datepicker.parseDate(
                    $(this).datepicker("option", "dateFormat"),
                    $(this).val(),
                    $.datepicker._getFormatConfig($.datepicker._getInst(this))
                );
            }
            catch (err) {
                date = null;
            }
            if (date == null)
                $(this).closest('.input').find('input[type=hidden]').val("");
        });
        $("input[type='text'].date").each(function () {
            InputStyle.date(this);
        });
    };
    if (typeof window['datePickerI18n'] != 'undefined'
        && $.isArray(window['datePickerI18n'])
        && window['datePickerI18n'][1] != '') {
        $.ajax({
            async: false,
            url: window['datePickerI18n'][1],
            dataType: "script"
        }).done(function () {
            initDatePicker(window['datePickerI18n'][0]);
        }).fail(function () {
            initDatePicker("");
        });
    } else
        initDatePicker("");

    // init forms
    if(!window.NDbooking){
        InputStyle.init(null, {datepicker: false});
    }
    var tabs = $('ul.form-tabs>li a');
    if (!tabs.hasClass('active').length) {
        tabs.eq(0).addClass('active');
    }
    tabs.each(function () {
        InputStyle.tabs(this, tabs);
    });
    $(document).on('mouseover', 'div.row', null, function () {
        $(this).addClass('hover');
        return false;
    });
    $(document).on('mouseout', 'div.row', null, function () {
        $(this).removeClass('hover');
        return false;
    });
//    $('.row>.input').each(function () {
//        var p = $(this).parent();
//        if (p.parent('td').length)
//            p = p.parents('tr').eq(0);
//        InputStyle.hover(this, p)
//        $(this).find('input,label,select').each(function () {
//            InputStyle.hover(this, p);
//        });
//    });
    $('form.login').on('submit', function (ev) {
        ev.preventDefault();

    });

});