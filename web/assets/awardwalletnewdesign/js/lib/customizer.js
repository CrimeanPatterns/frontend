define(['jquery-boot', 'dateTimeDiff', 'jqueryui'], function ($, dateTimeDiff) {
    const customizer = {
        autoReinit: true,
        autoReinitInterval: 200,
        defaultArea: "body",
        debug: true,
        locale: ($('a[data-target="select-language"]').data('language') || $('html').attr('lang').substr(0, 2))
    };
    customizer.locale = customizer.locale.replace('_', '-');
    customizer.region = $('a.language[data-target="select-language"]').data('region') || 'us';
    var _locale = $('a.language[data-target="select-language"]').data('locale') || null;
    customizer.locales = function() {
        if (null !== _locale)
            return _locale.replace('_', '-');
        return customizer.locale.substr(0, 2) + '-' + customizer.region;
    };
    var dp = {
        init: false, // global datepicker init
        selector: '[data-role="datepicker"]',
        initialize: function (locale, callback) {
            if (this.init)
                return;

            callback = (callback == null) ? function () {
            } : callback;

            $.datepicker._updateDatepicker_original = $.datepicker._updateDatepicker;
            $.datepicker._updateDatepicker = function (inst) {
                $.datepicker._updateDatepicker_original(inst);
                var afterShow = this._get(inst, 'afterShow');
                if (afterShow)
                    afterShow.apply((inst.input ? inst.input[0] : null));
            };

            $.datepicker.setDefaults($.datepicker.regional[locale[0]]);

            $.datepicker._gotoToday = function(id) {
                $(id).datepicker('setDate', new Date()).datepicker('hide').blur();
                $.datepicker._selectDate.apply(this, [id]);
            };

            $('body').on('keyup', "." + $.datepicker.markerClassName, function (event) {
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
                if (date == null) {
                    var id = $(this).attr('id').split('_datepicker')[0];
                    if (id) $('#' + id).val("");
                }
            });
            this.init = true;
            callback();
        },
        initializeFields: function (fields) {
            fields.each(function () {
                dp.initializeField($(this));
            });
        },
        initializeField: function (e) {
            var id = $(e).attr('id').split('_datepicker')[0];
            var input = $('#' + id)[0];
            var defaultOptions = {
                altField: '#' + id,
                altFormat: 'yy-mm-dd',
                yearRange: "1910:c",
                changeYear: true,
                changeMonth: true,
                showButtonPanel: true, // @see #11652
                // buttonImage: '/assets/awardwalletnewdesign/img/datepicker.jpg',
                // buttonImageOnly: true,
                showOn: 'both',
                buttonText: '',
                beforeShow: function (el, obj) {
                    $(el).closest('.row').addClass('datePickerFocus');
                },
                afterShow: function() {
                    if (!legacy) {
                        $('.ui-datepicker select').wrap('<div class="styled-select"></div>');
                    }
                },
                onSelect: function() {
                    $(e).trigger("change");
                    $(input).trigger("change");
                },
                onClose: function () {
                    $(this).closest('.row').removeClass('datePickerFocus');
                }
            };
            var options = $(e).attr('data-dp-opt');
            var legacy = $(e).data('legacy');
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
            $(e).removeAttr('data-role');
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
        getLocale: function () {
            if (typeof window['datePickerI18n'] != 'undefined'
                && $.isArray(window['datePickerI18n'])
                && window['datePickerI18n'][1] != '') {
                return window['datePickerI18n'];
            }
            var region = $('a.language[data-region]', 'aside.user-blk').data('region'), lang = null;
            if (region && region.length) {
                lang = customizer.getLocaleByCountry(region, {mode: 'jqDatepicker'});
            }
            if (null === lang)
                return ['', ''];
            return [$('html').attr('lang').replace('_', '-'), 'jqueryui-ui/i18n/datepicker-' + lang];
        },
        loadDatepickerLocale: function (locale, callback) {
            var modulePath = locale[1];

            var region = $('a.language[data-region]', 'aside.user-blk').data('region')
            var lang = customizer.getLocaleByCountry(region, {mode: 'jqDatepicker'});

            if (typeof require === 'function' && require.toString().indexOf('require') !== -1) {
                require([modulePath], function (module) {
                    callback(locale);
                });
            }else if (typeof __webpack_require__ === 'function') {
                try {
                    import(
                        /* webpackChunkName: `[request]` */
                        `jquery-ui/ui/i18n/datepicker-${lang}`
                    ).then((module) => {
                        callback(locale);
                    });
                } catch (error) {
                    console.error('Failed to load module:', modulePath, error);
                }
            } else {
                console.error('Unknown module loader.');
            }

        }
    };

    customizer.initDatepickers = function (area, callback) {
        var pickers,
            locale = dp.getLocale();
        if (area != undefined)
            pickers = $(area).find(dp.selector).addBack(dp.selector);
        else
            pickers = $(dp.selector);
        if (pickers.length > 0) {
            if (!dp.init) {
                if (locale[1] !== "") {
                    dp.loadDatepickerLocale(locale, function (locale) {
                        dp.initialize(locale, function () {
                            dp.initializeFields(pickers);
                            if(callback) callback();
                        });
                    });
                } else {
                    dp.initialize(locale, function () {
                        dp.initializeFields(pickers);
                        if(callback) callback();
                    });
                }
            } else {
                dp.initializeFields(pickers);
            }
        }
    };

    

    customizer.initSelects2 = function (area) {
        var select,
            selector = '[data-role="select2"]',
            options, defaults = {}, data;
        if (area != undefined)
            select = $(area).find(selector).addBack(selector);
        else
            select = $(selector);
        select.each(function () {
            $(this).removeAttr('data-role');
            options = $(this).attr('data-select2-opt');
            options = eval("(" + options + ")") || {};
            data = $(this).attr('data-init-data');
            if (data != null)
                options['initSelection'] = function (element, callback) {
                    callback(eval("(" + data + ")"));
                };
            $(this).select2(
                $.extend({minimumResultsForSearch: -1}, options)
            )
                .on('select2-focus', function () {
                    $(this).parent('form').find('input').blur();
                })
                .on('select2-close', function () {
                    $(this).prev('.select2-container').removeClass('');
                })
        });
    };

    customizer.initTooltips = function (area, options = {}) {
        var tooltip,
            selector = '[data-role=tooltip]';
        if (area != undefined)
            tooltip = $(area).find(selector).addBack(selector);
        else
            tooltip = $(selector);
        tooltip.tooltip({
            tooltipClass: "custom-tooltip-styling",
            position: {
                my: "center bottom-11",
                at: "center top",
                collision: "flipfit flip",
                using: function (position, feedback) {
                    $(this).css(position);
                    $("<div>")
                        .addClass("arrow")
                        .addClass(feedback.vertical)
                        //.addClass(feedback.horizontal)
                        .css({marginLeft: feedback.target.left - feedback.element.left - 6 - 7 + feedback.target.width / 2, width: 0})
                        .appendTo(this);
                },
                ...options
            }
        }).removeAttr('data-role').off('focusin focusout').prop('tooltip-initialized', true);
    };

    var navHeight = 70;
    var scrollToError = function() {
        var invalid_el = $('.row.error').first().offset().top - navHeight;

        if ( invalid_el > (window.pageYOffset - navHeight) && invalid_el < (window.pageYOffset + window.innerHeight - navHeight) ) {
            return;
        } else {
            if (!$('html, body').is(':animated')) {
                $('html, body').animate({scrollTop: invalid_el}, 100);
            }
        }
    };
    var showErrorMessages = function(form) {
        var rowElement, errorType,
            errors = 0;
        form.find(":invalid").filter(':visible').each(function(index, node) {
            rowElement = $(this).closest('div.row');
            errorType = null;

            $(rowElement).parents('.row').addClass('error');

            if (rowElement.hasClass('error')) {
                errors++;
                return true;
            }
            if (typeof(node.validity) === 'object') {
                if (node.validity.patternMismatch) {
                    errorType = 'patternMismatch';
                } else if (node.validity.valueMissing) {
                    errorType = 'valueMissing';
                } else if (node.validity.typeMismatch) {
                    errorType = 'typeMismatch';
                } else if (node.validity.tooShort) {
                    errorType = 'tooShort';
                } else if (node.validity.tooLong) {
                    errorType = 'tooLong';
                }
            }
            if (errorType) {
                var mes = rowElement.children('div[class="error-message"][data-type='+errorType+']');
                if (mes.length > 0) {
                    mes.css("display", "table-row");
                    rowElement.addClass('error');
                    errors++;
                } else {
                    mes = rowElement.children('div[class="error-message"]').first();
                    if (mes.length > 0) {
                        mes.css("display", "table-row");
                        rowElement.addClass('error');
                        errors++;
                    }
                }
            } else {
                mes = rowElement.children('div[class="error-message"]').first();
                if (mes.length > 0) {
                    mes.css("display", "table-row");
                    rowElement.addClass('error');
                    errors++;
                }
            }

            if($(rowElement).parent().hasClass('row'))
                $(rowElement).parent().addClass('error');
        });
        if (errors > 0) {
            form.trigger('submit_cancelled');
            scrollToError();
            return false;
        }
        return true;
    };

    customizer.initHtml5Inputs = function (area) {
        var formErrorHandler = function() {
            var form = $(this);
            form.on("submit", function(event) {
                if (this.checkValidity && !this.checkValidity()) {
                    event.preventDefault();
                    return showErrorMessages($(this));
                }
            }).on("click", "input[type=submit], button:not([type=button])", function(){
                return showErrorMessages($(this).closest('form'));
            });
        };
        $("form", area).each(formErrorHandler);
        $(document).on('change keyup paste click', 'input, select, textarea', function (event) {
            var rowElement = $(event.target).parents('div[class^="row"]:not(".row-block"):not(".row-body")');
            if (rowElement.hasClass('error') && !$(event.target).hasClass('ng-invalid')) {
                rowElement.removeClass('error').find('.error-message').hide();
                rowElement.children('div[class="error-message"]').hide();
            }

            return true;
        }).on('invalid', function (event) {
            event.preventDefault();
        });
    };

    customizer.initDropdowns = function (area, options) {
        options = options || {};
        var dropdown,
            selector = '[data-role="dropdown"]';
        if (area != undefined)
            dropdown = $(area).find(selector).addBack(selector);
        else
            dropdown = $(selector);
        const ofParentSelector = options.ofParent || 'li';

        dropdown.each(function (id, el) {
            $(el)
                .removeAttr('data-role')
                .menu()
                .hide()
                .on('menu.hide', function (e) {
                    $(e.target).hide(200);
                });
            $('[data-target=' + $(el).data('id') + ']').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $('.ui-menu:visible').not('[data-id="' + $(this).data('target') + '"]').trigger('menu.hide');
                $(el).toggle(0, function () {
                    $(el).position({
                        my: options?.position?.my || "left top",
                        at: "left bottom",
                        of: $(e.target).parents(ofParentSelector).find('.rel-this'),
                        collision: "fit"
                    });
                });
            });
        });
        $(document).on('click', function (e) {
            $('.ui-menu:visible').trigger('menu.hide');
        });
    };

    customizer.initAll = function (area) {
        require(['dateTimeDiff'], function() {
            customizer.dateUtc();
        });
        customizer.initDatepickers(area);
        customizer.initTooltips(area);
        customizer.initHtml5Inputs(area);
        customizer.btnHoverEffect();
        $(document)
            .trigger('dom_change')
            .ready(customizer.tip);

        setTimeout(function() {
            customizer.tabIndex();
        }, 2500);
        //console.log('Customizer trigger!');
    };

    customizer.getLocaleByCountry = function(countryCode, options) {
        options = options || {};
        var mode = undefined !== options.mode ? options.mode : 'jqDatepicker';
        if ('jqDatepicker' == mode) {
            // country => jQueryUI datepicker i18n file [vendors/jquery-ui/ui/i18n/*.js]
            var jqDatepickerI18n = {
                kw : 'ar',
                az : 'az',
                cn : 'zh-CN',
                vn : 'vi',
                ua : 'uk',
                tr : 'tr',
                th : 'th',
                se : 'sv',
                rs : 'sr',
                al : 'sq',
                si : 'sl',
                sk : 'sk',
                ru : 'ru',
                ro : 'ro',
                br : 'pt-BR',
                pt : 'pt',
                pl : 'pl',
                no : 'no',
                be : 'nl',
                my : 'ms',
                mk : 'mk',
                lv : 'lv',
                lt : 'lt',
                lu : 'lb',
                kg : 'ky',
                kr : 'ko',
                kz : 'kk',
                ge : 'ka',
                jp : 'ja',
                it : 'it',
                is : 'is',
                id : 'id',
                am : 'hy',
                hu : 'hu',
                hr : 'hr',
                in : 'hi',
                fr : 'fr',
                gf : 'fr',
                pf : 'fr',
                fo : 'fo',
                fi : 'fi',
                ee : 'et',
                es : 'es',
                nz : 'en-NZ',
                gb : 'en-GB',
                au : 'en-AU',
                gr : 'el',
                de : 'de',
                dk : 'da',
                cz : 'cs',
                ba : 'bs',
                bg : 'bg',
                by : 'be'
            };

            if (undefined !== jqDatepickerI18n[countryCode])
                return jqDatepickerI18n[countryCode];
        }

        return null;
    };

    customizer.dateUtc = function($context) {
        $context || ($context = false);
        var date, dateFormat = new Intl.DateTimeFormat(customizer.locales(), {weekday : 'long', day : 'numeric', month : 'long', year : 'numeric', hour : 'numeric', minute : 'numeric'});
        $('.js-date-utc--title', $context).each(function() {
            date = $(this).data('date');
            if ('string' === typeof date && -1 !== date.indexOf('[['))
                return;

            if ('number' === typeof date)
                date = 1000 * date;
            $(this).attr('title', dateFormat.format(new Date(date)));
        });
        $('.js-date-utc', $context).each(function() {
            var format = $(this).data('format'),
                date   = $(this).data('date');
            if ('string' === typeof date && -1 !== date.indexOf('[['))
                return;
            if ('number' === typeof date)
                date = 1000 * date;

            if ('undefined' !== typeof format) {
                switch (format) {
                    case 'diffTimeAgo':
                        return dateTimeDiff.longFormatViaDateTimes(new Date(), new Date(date));
                }
            }

            $(this).text(dateFormat.format(new Date(date)));
        });
    };

    customizer.btnHoverEffect = function() {
        $(document).keydown(function(e) {
            var keyCode = e.keyCode || e.which;
            if (32 === keyCode || 13 === keyCode) {
                var $elFocused = $(':focus');
                if (32 === keyCode && !$elFocused.is('button'))
                    return;
                if (!$elFocused.hasClass('pressed') && ($elFocused.hasClass('btn-blue') || $elFocused.hasClass('btn-silver') || $elFocused.hasClass('btn-light-silver'))) {
                    $elFocused.addClass('pressed');
                    setTimeout(function() {
                        $elFocused.removeClass('pressed');
                    }, 1500);
                }
            }
        });
    };

    customizer.tabIndex = function() {
        $('.dropdown-submenu').removeAttr('tabindex');
        var tabindex = 970;
        $('.user-menu > li > a', '#headerSite').each(function() {
            $(this).attr('tabindex', ++tabindex);
        });
        tabindex = 850;
        $('li a[data-id]', '.js-persons-menu').each(function() {
            $(this).attr('tabindex', ++tabindex).prev().attr('tabindex', '-1');
        });

        tabindex = 1000;
        $('.account-row').each(function() {
            $('input.checkbox,.show-details,a.title,a[data-ng-click*=".showPopupExpiration()"],a[data-ng-click*=".update()"],a[data-ng-if*=".edit"]', $(this)).attr('tabindex', ++tabindex);
        });

        tabindex = 5000;
        $('.footer-menu a').each(function() {
            $(this).attr('tabindex', ++tabindex);
        });
    };

    customizer.tip = function() {
        var $tipFound = $('*[data-intro]');
        if ($tipFound.length || typeof window.tipJsList !== 'undefined') {
            require(['tipjs'], function(tipjs) {
                if ($('#showTips').length)
                    return;

                var tipJsCountWithoutInit = 0;
                if (typeof window.tipJsList !== 'undefined') {
                    for (var i in window.tipJsList) {
                        if ('undefined' === typeof window.tipJsList[i].init) {
                            ++tipJsCountWithoutInit;
                        }
                    }
                }
                if ($tipFound.length || tipJsCountWithoutInit > 0) {
                    $('ul.user-menu').prepend('<li class="header-menu-0"><a id="showTips" href="#tips" class="btn-light-silver" data-role="tooltip" title="' + Translator.trans(/** @Desc("Show all tips") */'tip.button.show-all') + '">?</a></li>');
                }

                function tipMarkRead(tipId, eventType) {
                    if (tipId === undefined /*|| $('[data-tipid="' + tipId + '"]').not('#tipjsTooltip').attr('data-show') === undefined*/)
                        return false;

                    return $.ajax({
                        type           : 'POST',
                        suppressErrors : true,
                        url            : Routing.generate('aw_tip_mark', {tipId : tipId}),
                        data           : {
                            event : eventType
                        },
                        dataType       : 'json',
                        success        : function(data) {
                        }
                    });
                }

                function tipjsList(init) {
                    init || (init = false);
                    if (typeof window.tipJsList !== 'undefined' && window.tipJsList.length > 0) {
                        for (var i in window.tipJsList) {
                            var $obj = $(window.tipJsList[i].selector);
                            if ($obj.length) {
                                for (var j in window.tipJsList[i].attr)
                                    $obj.attr('data-' + j, window.tipJsList[i].attr[j]);
                            } else if (!init && 'function' === typeof window.tipJsList[i].init) {
                                window.tipJsList[i].init(tipStart, window.tipJsList[i].selector);
                            }
                        }
                    }

                    $tipFound = $('*[data-intro]');
                    $tipFound
                        .off('click.markRead')
                        .one('click.markRead', function(e) {
                            var $element = $(this);
                            if ($element.hasClass('introjs-showElement') /*&& $element.attr('data-show') !== undefined*/) {
                                e.preventDefault();
                                var deferred = tipMarkRead($element.attr('data-tipid'), 'click');
                                if (deferred === false)
                                    return true;

                                $.when(deferred).then(function() {
                                    tipjs().exit();
                                    $element.off('click.markRead');
                                    $element[0].click();
                                });
                                return false;
                            }
                            return true;
                        });
                }

                function tipStart(element, exactly) {
                    exactly || (exactly = false);
                    tipjsList(exactly);

                    tipjs(element)
                        .setOptions({
                            'tooltipPosition' : 'auto',
                            'showStepNumbers' : false,
                            'showButtons'     : undefined === element,
                            'showBullets'     : undefined === element,
                            'nextLabel'       : Translator.trans(/** @Desc("Next &#187;") */'tip.button.next'),
                            'prevLabel'       : Translator.trans(/** @Desc("&#171; Back") */'tip.button.prev'),
                            'skipLabel'       : Translator.trans('skip', {}, 'mobile'),
                            'doneLabel'       : Translator.trans('done'),
                            'buttonClass'     : 'btn-tipjs'
                        })
                        .onexit(function() {
                            $('#tipjsOverlay').hide();
                            $tipFound.off('click.markRead');
                            $(document.body).removeClass('tip-show');
                            $('.search-row[autofocus]').focus();
                        })
                        .onbeforechange(function($element) {
                            var $overlay = $('#tipjsOverlay');
                            if ($overlay.length) {
                                $($element).after($overlay);
                            }
                            tipMarkRead($($element).attr('data-tipid'), 'show');
                        })
                        .onafterchange(function($element) {
                            var $toolTip = $('#tipjsTooltip');
                            customizer.dateUtc($toolTip);
                            tipMarkRead($toolTip.attr('data-tipid'), 'close');
                            $toolTip.attr('data-tipid', $($element).attr('data-tipid'));
                        })
                        .onskip(function() {
                            tipMarkRead($('#tipjsTooltip').attr('data-tipid'), 'close');
                        })
                        .onclose(function() {
                            $('#tipjsOverlay').hide();
                            $tipFound.off('click.markRead');
                            tipMarkRead($('#tipjsTooltip').attr('data-tipid'), 'close');
                            tipjs().exit();
                            return false;
                        })
                        .start();

                    $(document.body).addClass('tip-show');
                }

                $('#showTips').click(function(e) {
                    e.preventDefault();
                    tipStart();
                });

                function tip() {
                    if (this.attempts === undefined)
                        this.attempts = 0;
                    if (window.$httpLoading !== undefined && window.$httpLoading === true && this.attempts++ < 30) {
                        return setTimeout(tip, 500);
                    }

                    tipjsList();
                    if ($('*[data-intro][data-show]').length) {
                        tipStart(document.querySelector('div.main-body'));
                    }
                }

                setTimeout(function() {
                    var $popup = $('.ui-dialog:visible');
                    if (!$popup.length)
                        return tip();
                    $popup.on('dialogclose', tip);
                }, 500);
            });
        }
        return;
    };

    customizer.isMobile = function() {
        var check = false;
        (function(a) {
            if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) {
                check = true;
            }
        })(navigator.userAgent || navigator.vendor || window.opera);
        return check;
    };

    customizer.navMobile = function() {
        if (!customizer.isMobile()) {
            $('.nav-buttons-mobile a.btn-light-silver').each(function() {
                if ('/m/' == $(this).attr('href').substr(0, 3)) {
                    let href = $(this).attr('href').substr(2);
                    if ('/registration' == href) {
                        href = '/register';
                    }
                    $(this).attr('href', href);
                }
            });
        }
    };

    customizer.getQueryParameter = function(name) {
        return decodeURI((RegExp(name + '=' + '(.+?)(&|$)').exec(location.search) || ['', null])[1]);
    };

    $(function () {
        customizer.initAll(customizer.defaultArea);

        //if (customizer.autoReinit) {
        //    var originalDOM = $('body').html(),
        //        actualDOM;

            //setInterval(function () {
            //    actualDOM = $('body').html();
            //
            //    if (originalDOM !== actualDOM) {
            //        originalDOM = actualDOM;
            //
            //        customizer.initAll(customizer.defaultArea);
            //    }
            //}, customizer.autoReinitInterval);
        //}
    });

    customizer.showErrors = showErrorMessages;

    customizer.dp = dp;

    customizer.isGtmLoaded = function () {
        if ('undefined' === typeof gtag) {
            return false;
        }
        window.dataLayer = window.dataLayer || [];
        let gtmStartedEvent = window.dataLayer.find((element) => element['gtm.start']);

        if (!gtmStartedEvent || !gtmStartedEvent['gtm.uniqueEventId']) {
            return false;
        }

        return true;
    };

    return customizer;
});
