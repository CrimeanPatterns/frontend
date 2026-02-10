define([
    'angular-boot',
    'jquery-boot',
    'pages/status/controllers'
], function(angular, $) {
    'use strict';
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('statusPage', ['appConfig', 'statusPage-ctrl']);

    $(document).ready(function() {
        $('a', '#t_cannotAdd').attr('target', '_blank');
        //
        (function sortTableColumn() {
            function comparer(index) {
                return function(a, b) {
                    var valA = getCellValue(a, index), valB = getCellValue(b, index);
                    return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.localeCompare(valB);
                };
            }

            function getCellValue(row, index) {
                var $t = $(row).children('td').eq(index);
                if ($t.data('date'))
                    return $t.data('date');
                return $t.text();
            }

            $('th.js-sort').click(function() {
                var $table = $(this).closest('table');
                var rows   = $table.find('tbody tr').toArray().sort(comparer($(this).index()));
                this.asc   = (undefined === this.asc ? false : !this.asc);
                $table.find('thead th.js-sort i[class*="icon-"]').addClass('hidden');
                $('.js' + $table.attr('id')).find('thead th.js-sort i[class*="icon-"]').addClass('hidden');
                if (!this.asc) {
                    rows = rows.reverse();
                    $(this).find('i[class*="icon-"]').toggleClass('icon-arrow-up-silver icon-arrow-down-silver').removeClass('hidden');
                    $('.js' + $table.attr('id')).find('thead th:eq(' + (1 + $(this).index()) + ') i[class*="icon-"]').toggleClass('icon-arrow-up-silver icon-arrow-down-silver').removeClass('hidden');
                } else {
                    $(this).find('i[class*="icon-"]').toggleClass('icon-arrow-down-silver icon-arrow-up-silver').removeClass('hidden');
                    $('.js' + $table.attr('id')).find('thead th:eq(' + (1 + $(this).index()) + ') i[class*="icon-"]').toggleClass('icon-arrow-down-silver icon-arrow-up-silver').removeClass('hidden');
                }
                for (var i = 0; i < rows.length; i++)
                    $table.append(rows[i]);
            });
        })();

        // 
        (function fixTableCaption() {
            $(window).each(function() {
                if ($(window).width() < 1024) {
                    $('.main-body').addClass('small-desktop hide-menu');
                } else {
                    $('.main-body').removeClass('small-desktop hide-menu');
                }
            });
            $(window).resize(function() {
                if ($('.main-body').hasClass('manual-hidden'))
                    return;
                var sizeWindow = $('body').width();
                if (sizeWindow < 1024) {
                    $('.main-body').addClass('small-desktop hide-menu');
                } else {
                    $('.main-body').removeClass('small-desktop hide-menu');
                }
            });

            $.fn.fixMe = function() {
                return this.each(function() {
                    var $this = $(this),
                        $t_fixed;

                    function init() {
                        $this.wrap('<div class="container" />');
                        $t_fixed = $this.clone();
                        $t_fixed.addClass('js' + $t_fixed.attr('id')).removeAttr('id').find("tbody").remove().end().addClass("fixed-table").insertBefore($this);
                        $t_fixed.find('th.js-sort').click(function() {
                            $(this).closest('table').next().find('th:eq(' + (1 + $(this).index()) + ')').click();
                        });
                        resizeFixed();
                    }

                    function resizeFixed() {
                        $t_fixed.find("th").each(function(index) {
                            $(this).css("width", $this.find("th").eq(index).outerWidth() + "px");
                        });
                    }

                    function scrollFixed() {
                        if ($t_fixed.hasClass('hidden'))
                            return;
                        var offset            = $(this).scrollTop(),
                            scrollLeft        = $(this).scrollLeft(),
                            tableOffsetTop    = $this.offset().top,
                            tableOffsetBottom = tableOffsetTop + $this.height() - $this.find("thead").height();
                        $t_fixed.css('left', -scrollLeft + $this.offset().left);
                        if (offset < tableOffsetTop || offset > tableOffsetBottom)
                            $t_fixed.hide();
                        else if (offset >= tableOffsetTop && offset <= tableOffsetBottom && $t_fixed.is(":hidden"))
                            $t_fixed.show();
                    }

                    $(window).resize(resizeFixed);
                    $(window).scroll(scrollFixed);
                    init();

                    $(window).on('resize', function() {
                        setTimeout(resizeFixed, 400); // after hide-menu (css:transition 300ms)
                    });
                });
            };

            $("table.fixed-header").fixMe();
        })();

        angular.bootstrap(document, ['statusPage']);
    });

});