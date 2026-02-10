var AddForm = (function(InputStyle){
    var loginDialog, termsPopup;
    var formatSelection = function(o) {
        if (o.Connected == "0")
            return "<span class='select-icon'><i class='icon-member'></i></span> " + o.text;
        return "<span class='select-icon'><i class='icon-user'></i></span> " + o.text;
    };
    return {
        form: null,
        editMode: false,
        showErrors: false,
		passwordComplexity: null,

        init: function(passwordComplexity){
			AddForm.passwordComplexity = passwordComplexity;
			if(AddForm.passwordComplexity)
				passwordComplexity.init($('#booking_request_User_pass_Password'), function(){ return $('#booking_request_User_login').val() }, function(){ return $('#booking_request_User_email_Email').val() } );
            AddForm.form = $('#booking-form-tabs').closest('form');
            AddForm.handleButtons();
            // Release unique error
            $("div[data-error-type]").each(function (id, el) {
                if ($(el).data("error-type").indexOf("equal") != -1) {
                    var nextRow = $(el).parents(".row").next(".row");
                    nextRow.addClass("error");
                    var error = nextRow.find("div.message");
                    error.addClass('error').text($(el).text());
                }
            });

            loginDialog = $('#loginPopup').dialog({
                title: Translator.trans(/** @Desc("Please login") */ 'booking.title.login'),
                modal: true,
                autoOpen: false,
                resizable: false,
                width: 650,
                close: function() {
                    $(this).find('input').val('').trigger('change');
                }
            });

            termsPopup = $('#termsPopup');
            termsPopup.dialog({
                title: Translator.trans(/** @Desc("Terms of use") */ 'terms'),
                modal: true,
                autoOpen: false,
                resizable: false,
                width: 750,
	            height: 400,
                open: function(){
	                termsPopup.css('overflow', 'auto');
	                termsPopup.css('margin-bottom', '10px');
                    termsPopup.closest('.ui-dialog').find('button[role="button"]').blur();
                }
            });
            $('#booking_request_Terms').parent().addClass("prompt");
            $('#booking_request_RememberMe').parent().addClass("prompt");

            // session prolongation
            setInterval(function() {
                AddForm.ping();
            }, 1000*60*7);

            var link = $('#public-link');
            if (link.length == 1) {
                var val = link.val(),
                    dummy = $('<div style="position:absolute;left: -1000%;font-size:'+link.css('font-size')+'">').hide();
                dummy.append(val).appendTo('body');
                link.width(dummy.width());
                dummy.remove();
            }

            AddForm.event();

            var openTab = window.location.hash.replace(/#/, '');
            if (openTab != '' && AddForm.form.find('div.row.error, .error').length == 0) {
                openTab = $('#'+openTab+'.tab');
                if (openTab.length > 0) {
                    AddForm.activateElementTab(openTab);
                    AddForm.focusFirst();
                }
            } else if (AddForm.form.find('div.row.error, .error').length > 0){
                AddForm.activateElementTab(AddForm.form.find('div.row.error').first());
            }

            Passengers.init();
            Segments.init();
            Miles.init();
            Info.init();
        },
        ping: function(){
            $.ajax({
                url: Routing.generate('aw_ping'),
                method: 'POST',
                global: false,
                data: AddForm.form.find('input[type!=password], textarea, select')
            });
        },
        event: function(){
            // tabs
            $('#booking-form-tabs li a').click(function(e) {
                e.preventDefault();
                var click_id = $(this).data('id');

                // Set first name and lastname in contact info page
                if (click_id == 'tab1') {
                    var fname = $('#booking_request_User_firstname'),
                        lname = $('#booking_request_User_lastname'),
                        fpass = $('#passenger-list').find('fieldset:first-child');

                    if (fname.val() == '')
                        fname.val(fpass.find("input[id*='FirstName']").val());

                    if (lname.val() == '')
                        lname.val(fpass.find("input[id*='LastName']").val());

                }

                if (click_id != $('#booking-form-tabs a.active').attr('id') ) {
                    $('#booking-form-tabs a').removeClass('active');
                    $(this).addClass('active');
                    $('#booking-form-tabs div').removeClass('active');
                    $('#con_' + click_id).addClass('active');
                    var activeTab = AddForm.getActiveTab();
                    activeTab.trigger('selected');
                    AddForm.handleButtons();
                    AddForm.form.trigger('form_change');

                    if(click_id == 'tab2'){
                        InputStyle.select('#booking_request_NumberPassengers');
                    }
                }
            });
            // init datepicker
            $('[id^=tab]').on('selected', function() {
                $("input[type='text'].date").each(function(){
                    InputStyle.date(this);
                });
            });
            AddForm.form.on('post_restore', AddForm.postRestore);
            // buttons
            $('#previousButton').click(AddForm.previousClick);
            $('#nextButton').click(AddForm.nextClick);
            $('#submitButton, #saveButton').click(AddForm.submitClick);
            $('#cancelButton').click(AddForm.cancelClick);

            $('.booking-form').on('change keyup paste', 'div.row input, div.row textarea, div.row checkbox', null, AddForm.fieldChanged);

            // error handling
            if(AddForm.showErrors)
                formValidator.displayErrors(AddForm.form);

            AddForm.form
                .on('restore_form', function(event, data) {
                    var item;
                    $('#passenger-list').find('input[type!="hidden"]').each(function(){
                        if (!$.proxy(AddForm.elementEmpty, this)()) {
                            AddForm.resetInput(this);
                        }
                    });
                    $('#booking_request_CustomPrograms > div.inner-block').find('input[type!="hidden"]').each(function(){
                        if (!$.proxy(AddForm.elementEmpty, this)()) {
                            AddForm.resetInput(this);
                        }
                    });
                });

            // Hide error on tab4 if select account
            $('.check-cell input[type=checkbox], #add-custom-button').click(function(){
                $('#select_programs_message').slideUp();
            });
            // Switch hash if click
            $('a[data-id*="tab"]').click(function(){
                var hash = $(this).data('id');
                AddForm.setHash(hash);
            });

            $('#show_login_popup').click(function (e) {
                e.preventDefault();
                document.location.href = Routing.generate('aw_login') + '?BackTo=' + encodeURIComponent(document.location.pathname + document.location.search);
            });
            var otcView = false;
            var form = loginDialog.find('form');
            form.submit(function (e) {
                e.preventDefault();
                // ie11 fix, see #10625
                var cookie = $.cookie();
                if (cookie.hasOwnProperty('XSRF-TOKEN')) {
                    cookie = cookie['XSRF-TOKEN'];
                } else {
                    cookie = $.cookie('XSRF-TOKEN');
                }
                var submitBtn = form.find('button[type="submit"]');
                if (!submitBtn.hasClass('loader')) {
					var payload = {
						login: form.find('input[name="login"]').val(),
						password: form.find('input[name="password"]').val(),
                        _otc: form.find('input[name="_otc"]').val(),
						_remember_me: $('#check').is(':checked'),
                        FormToken: cookie
					};
                    $.ajax({
                        url: Routing.generate('aw_login_client_check'),
                        method: 'POST',
                        success: function (response) {
                            Dn698tCQ = eval(response.expr);
                            payload._csrf_token = response.csrf_token;
                            $.ajax({
                                url: Routing.generate('aw_users_logincheck'),
                                method: 'POST',
                                data: payload,
                                headers: {
                                    'X-Scripted': Dn698tCQ || Math.random()
                                },
                                success: function (data) {
                                    if (data.success) {
                                        window.location.href = location.href.split(location.search||location.hash||/[?#]/)[0] + '?recover=true';
                                        // $("#tab1 fieldset:first").find('.inner-block').load(Routing.generate('aw_booking_add_getcontactinfo'), $("#tab1 fieldset:first").find('input').serialize(), function () {
                                            // $('.registerCredentials').detach();
                                            // loginDialog.dialog('close');
                                            //$('#submitButton').trigger('click');
                                        // });
                                    } else {
                                        if (!otcView && data.otcRequired) {
                                            form.find('.info-message' ).html(data.message).show();
                                            form.find('.mess').hide();
                                        } else {
                                            form.find('.mess').html(data.message).show();
                                            form.find('.info-message').hide();
                                        }

                                        if (data.otcRequired && !otcView) {
                                            form.find('#blockLogin, #blockPassword, #blockOTC, #blockRememberMe, #blockBackButton').toggle();
                                            otcView = true;
                                            form.find('#blockBackButton button').off().click(function() {
                                                form.find('#blockLogin, #blockPassword, #blockOTC, #blockRememberMe, #blockBackButton').toggle();
                                                form.find('input[name="_otc"]').val('');
                                                otcView = false;
                                                form.find('.mess, .info-message').hide();
                                            });
                                        }
                                        loginDialog.parents('.ui-dialog').effect('shake', {}, 500);
                                    }
                                },
                                beforeSend: function () {
                                    submitBtn.addClass('loader');
                                },
                                complete: function () {
                                    submitBtn.removeClass('loader');
                                }
                            });
                        }
                    });

                }
            })
                .find('input[name=login], input[name=password]').on('input change paste', function () {
                    form.find('.mess').fadeOut();
                });

            $('#termsBtn').click(function () {
                termsPopup.load($(this).attr("href"), function () {
                    termsPopup.dialog('open');
                });
                return false;
            });
            $(document).on('click', '#public-link', function(){
                $(this).select();
            });
        },

        handleButtons: function() {
            var edit = AddForm.editMode,
                activeTab = AddForm.getActiveTab();
            $('#previousButton').toggle(!edit && activeTab.attr('id') != 'tab1');
            $('#nextButton').toggle(!edit && activeTab.attr('id') != 'tab4');
            $('#submitButton').toggle(!edit && activeTab.attr('id') == 'tab4');
            $('#saveButton').toggle(edit);
            $('#cancelButton').toggle(edit);
        },
        activateElementTab: function(element){
            var tab = element.closest('div.tab').attr('id');
            AddForm.selectTab(tab);
        },
        selectTab: function(tab){
            if(tab != AddForm.getActiveTab().attr('id'))
                $('#booking-form-tabs ul.form-tabs a[data-id="' + tab + '"]').trigger('click');
        },
        getActiveTab: function(){
            return $('#booking-form-tabs div.tab:visible');
        },
        previousClick: function(event){
            $('#booking-form-tabs ul.form-tabs li:has(a.active)').prev().find('a').trigger('click');
            event.preventDefault();
            AddForm.focusFirst();
        },
        nextClick: function(event){
            event.preventDefault();
            $('#nextButton').focus(); // remove focus from date pickers, to save value

            var activeTab = AddForm.getActiveTab().attr('id');
            if (activeTab == 'tab1' && !Info.check()){
                if(
                    formValidator.checkRequiredFields($('#booking-form-tabs div.tab#tab1')) &&
                    AddForm.passwordComplexityCheck() &&
                    Info.confirmFieldsCheck()
                ){
                    $(event.target).addClass('loader');
                    AddForm.doSubmit();
                }
                return;
            }
            if (activeTab == 'tab2' && !Passengers.check()) return;
            if (activeTab == 'tab3' && !Segments.check()) return;
            if (activeTab == 'tab4' && !Miles.check()) return;

            if (activeTab != 'tab3' && !formValidator.checkRequiredFields(AddForm.getActiveTab())) return;

            $('#booking-form-tabs ul.form-tabs li:has(a.active)').next().find('a').trigger('click');
            AddForm.focusFirst();
        },
        submitClick: function (event) {
            event.preventDefault();
            console.warn('submit form');
            if (AddForm.submitCheck() && AddForm.passwordComplexityCheck()) {
                $(event.target).addClass('loader');
                $('.booking-form').darkfader('show', {
                    'opacity': 0,
                    'complete': function() {
                        AddForm.form.on('submit_cancelled', function(){
                            $(event.target).removeClass('loader');
                            $('*[data-darkfader]').darkfader('hide');
                        });
                        console.warn("form do submit");
                        AddForm.doSubmit();
                    }
                });
            }

        },
		passwordComplexityCheck: function(){
			if($('#booking_request_User_pass_Password').length == 0 || typeof(AddForm.passwordComplexity) != "object")
				return true;
			var errors = AddForm.passwordComplexity.getErrors();
			if(errors.length > 0){
				AddForm.selectTab('tab1');
				$('#booking_request_User_pass_Password').focus();
				return false;
			}
			else
				return true;
		},
        cancelClick: function(event){
            event.preventDefault();
            window.location.href = Routing.generate('aw_booking_view_index', {id: $('.booking-form').data('request')});
        },
        doSubmit: function () {
            if(typeof(whenRecaptchaLoaded) === 'function')
                setTimeout(function(){ whenRecaptchaLoaded(function(){
                    renderRecaptcha();
                    whenRecaptchaSolved(function(recaptcha_code){
                        AddForm.form[0]['recaptcha'].value = recaptcha_code;
                        AddForm.form[0].submit();
                    });
                }); }, 100);
            else
                AddForm.form[0].submit();
        },
        submitCheck: function(){
            AddForm.form.trigger('check_form');
            console.log(formValidator.checkRequiredFields(AddForm.form));
            return formValidator.checkRequiredFields(AddForm.form)
                && Passengers.check()
                && Segments.check()
                && Miles.check()
                && Info.check();
        },
        focusFirst: function(){
            var top = -20,
                header = $('header');
            if (header && header.css('position') == 'fixed') {
                top -= header.outerHeight();
            }
            $('html, body').animate({
                scrollTop: AddForm.form.offset().top + top
            }, {
                complete: function(){ AddForm.getActiveTab().find('input, select, checkbox, textarea, radio').first().focus() }
            });
        },
        notHidden: function(){
            return $(this).closest('fieldset.hidden').length == 0;
        },
        elementEmpty: function(){
            if(this.type == 'radio'){
                var row = $(this).closest('.row');
                return (!row.hasClass('has-slider') || (row.hasClass('has-slider') && row.is(':visible'))) &&
                    AddForm.form.find('input[name="' + this.name + '"]:checked').length == 0;
            }
            else
            if(this.type == 'checkbox')
                return !this.checked;
            else
                return $.trim($(this).val()) == '';
        },
        /**
         * this event will be fired after FormKeeper restore entire form, to set values to Owner select in custom programs
         * we need this, because we can't set select value with missing options
         * @param event
         * @param data
         */
        postRestore: function(event, data){
            Miles.tab4selected(); //add autocomplete to custom account owner field
            var form = AddForm.form.get(0);
            // anonymous -> logged in: different contact phone field names
            if(data['major']['booking_request[User][phone1]'] && !form['booking_request[User][phone1]'] && form['booking_request[ContactPhone]'])
                form['booking_request[ContactPhone]'].value = data['major']['booking_request[User][phone1]'];
            // logged in -> anonymous: different contact phone field names
            if(data['major']['booking_request[ContactPhone]'] && !form['booking_request[ContactPhone]'] && form['booking_request[User][phone1]'])
                form['booking_request[User][phone1]'].value = data['major']['booking_request[ContactPhone]'];

            AddForm.ping();
        },
        fieldChanged: function(event){
            if(!AddForm.elementEmpty.call(this, event))
                $(this).closest('div.row').removeClass('error required').find('div.message').hide();
            $(this).closest('.error-able.required').removeClass('error required');
        },
        resetInput: function(input) {
            var type = input.type;
            var tag = input.tagName.toLowerCase();
            if (type == 'text' || type == 'password' || tag == 'textarea')
                input.value = "";
            else if (type == 'checkbox' || type == 'radio') {
                input.checked = false;
                $(input).next('span').removeClass('checked');
            } else if (tag == 'select')
                input.selectedIndex = -1;
        },
        setHash: function(hash) {
            var el = $('#' + hash);
            el.removeAttr('id');
            location.hash = hash;
            el.attr('id', hash);
        },
        byBooker: function() {
            return $('[data-booker="true"]').length > 0;
        },
        createdByBooker: function() {
            return $('[data-by-booker="true"]').length > 0;
        },

        select2hiddenOptions: {
            minimumInputLength: 2,
            width: 'element',
            formatResult: formatSelection,
            formatSelection: formatSelection,
            escapeMarkup: function(m) { return m; },
            formatNoMatches: Translator.trans(/** @Desc("No matches") */ 'booking.request.add.form.traveler.search.nomatches', {}, 'booking'),
            formatSearching: Translator.trans(/** @Desc("Searching...") */ 'booking.request.add.form.traveler.search.searching', {}, 'booking'),
            formatInputTooShort: null,
            formatInputTooLong: Translator.trans(/** @Desc("Search input too long") */ 'booking.request.add.form.traveler.search.toolong', {}, 'booking')
        },
        select2choiceOptions: {
            width: 'element',
            formatNoMatches: Translator.trans(/** @Desc("No matches") */ 'booking.request.add.form.traveler.search.nomatches', {}, 'booking'),
            formatSearching: Translator.trans(/** @Desc("Searching...") */ 'booking.request.add.form.traveler.search.searching', {}, 'booking'),
            formatInputTooShort: null,
            formatInputTooLong: Translator.trans(/** @Desc("Search input too long") */ 'booking.request.add.form.traveler.search.toolong', {}, 'booking')
        }
    };
})(InputStyle);
