define(['jquery-boot', 'jqueryui', 'pages/agent/addDialog'], function ($) {
    $(function () {
        var top = $('.header-site').length ? $('.header-site').offset().top - parseFloat($('.header-site').css('margin-top').replace(/auto/, 0)) : 0;
        $('.menu-close').click(function () {
            $('.main-body').toggleClass('hide-menu').addClass('manual-hidden');
            $(window).trigger('resize');
        });
        if ($('.menu-button').length) {
            $('.menu-button').click(function(){
                $(this).toggleClass('active');
                $('.header-site,.fixed-header').toggleClass('active');
                $('body').toggleClass('overflow');
            });
        }
        $('.api-nav__has-submenu > a').click(function(e){
            e.preventDefault();
            $(this).parent().toggleClass('active');
        });
        $('.list-apis a, .about__tags a').click(function(e){
            e.preventDefault();
            var hash = $(this).attr('href'),
                headerHeight = $('html').hasClass('mobile-device') ? $('.fixed-header').innerHeight() : $('.header-site').innerHeight();
            $('body,html')
                .animate({
                    scrollTop: $(hash).offset().top - headerHeight
                }, 500);
            if(history.pushState) {
                history.pushState(null, '', hash);
            }
            else {
                location.hash = hash;
            }
        });
        $('.main-form .styled-select select').focus(function(){
            $(this).closest('.styled-select').addClass('focus');
        }).blur(function(){
            $(this).closest('.styled-select').removeClass('focus');
        });
        $(window).each(function () {
            var body = $('.main-body');
            if ($(window).width() < 1024) {
                body.addClass('small-desktop');
            }
            else {
                body.removeClass('small-desktop');
            }
            if (body.hasClass('manual-hidden')) return;
            if ($(window).width() < 1024) {
                body.addClass('hide-menu');
            }
            else {
                body.removeClass('hide-menu');
            }
        });
        $(window).on('scroll', function () {
            var nav = $('.nav-row');
            var last = $('.last-update');

            if ($('div.fixed-header').length){
                if ($(this).scrollTop() > 120){
                    $('div.fixed-header').fadeIn();
                } else{
                    $('div.fixed-header').fadeOut();
                }
            }

            if ($(this).scrollTop() > 0) {
                nav.addClass('scrolled');
                last.addClass('scrolled');
            } else {
                nav.removeClass('scrolled');
                last.removeClass('scrolled');
            }
            if ($(this).scrollTop() > 65) {
                nav.addClass('active');
                nav.offset({
                    left: 0
                });
            } else {
                nav.removeClass('active');
                nav.css({
                    left: 0
                });
            }
        });

        var liActive,
            leftMenu = $('.user-blk'),
            content = $('div.content'),
            liClass = 'beyond';
        var liActiveHandler = function () {
            liActive = leftMenu.find('li.active');
            if (liActive.length != 1) return;
            if (liActive.offset().top + liActive.outerHeight() > content.offset().top + content.outerHeight()) {
                if (!liActive.hasClass(liClass)) liActive.addClass(liClass);
            } else {
                if (liActive.hasClass(liClass)) liActive.removeClass(liClass);
            }
        };
        if (leftMenu.length == 1 && content.length == 1) {
            liActiveHandler();
            setInterval(function () {
                liActiveHandler();
            }, 700);
        }

        var oldRight = 0;

        $(window).on('resize scroll', function () {
            var header = $('.header-site');
            if ($(window).width() < 1000) {
                if ($(window).scrollLeft() == 0) {
                    if (header.css('left') != '0px') { header.css('left', 0); }
                } else {
                    if (header.css('left') != '-' + $(window).scrollLeft() + 'px') { header.css('left', -$(window).scrollLeft()); }
                }
            } else {
                if (header.css('left') != '0px') { header.css('left', 0); }
            }
        }).trigger('resize');

        $(window).resize(function () {
            var sizeWindow = $('body').width();
            if (sizeWindow < 1024) {
                $('.main-body').addClass('small-desktop');
            } else {
                $('.main-body').removeClass('small-desktop');
            }
            if ($('.main-body').hasClass('manual-hidden')) return;
            if (sizeWindow < 1024) {
                $('.main-body').addClass('hide-menu');
            } else {
                $('.main-body').removeClass('hide-menu');
            }
        });

        $(document)
            .on('change keyup paste', '.row.error input:visible, .row.error textarea:visible, .row.error checkbox:visible', function () {
                var inputItem = $(this).closest('.input-item');
                if (inputItem.length == 0 || !$(this).hasClass('ng-invalid')) {
                    $(this).closest('.error').removeClass('error');
                }
            })
            .on('change', '.styled-file input[type=file]', function () {
                var fullPath = $(this).val();
                if (fullPath) {
                    var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
                    var filename = fullPath.substring(startIndex);
                    if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                        filename = filename.substring(1);
                    }
                    $('.file-name').text(filename);
                }
            })
            .on('click', '.spinnerable:not(form)', function () {
                $(this).addClass('loader');
            })
            .on('submit', 'form.spinnerable', function () {
                var button = $(this).find('[type="submit"]').first();

                if (!button.hasClass('loader')) {
                    button.addClass('loader').attr('disabled', 'disabled');
                }
            });

        $(document).on('click', '.js-add-new-person, #add-person-btn, .js-persons-menu a[href="/user/connections"].add', function (e) {
            e.preventDefault();

            require(['pages/agent/addDialog'], function (clickHandler) {
                clickHandler();
            });
        });

        var $addNewPerson = $('<option>' + Translator.trans(/** @Desc("Add new person") */ 'add.new.person') + '</option>');
        var $prevSelected;
        if (!$('.main-body.business')) {
            $('.js-useragent-select').append($addNewPerson).on('change', function (el) {
                if ($(el.target).find('option:selected')[0].text === $addNewPerson[0].text) {
                    $prevSelected.prop('selected', true);
                    $('.js-add-new-person').trigger('click');
                } else {
                    $prevSelected = $(el.target).find('option:selected');
                }
            }).trigger('change');
        }

        // Open person add popup if param addNewPerson is present
        if (document.location.href.match(/add-new-person=/))
            $('#add-person-btn').trigger('click');
    });
});
