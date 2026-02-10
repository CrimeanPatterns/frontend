var Miles = (function(InputStyle){
    return {
        init: function(){
            var manager;
            if (AddForm.byBooker() && AddForm.createdByBooker()) {
                manager = new CollectionManager(
                    'Accounts',
                    '#booking-account-list > tbody',
                    $('#booking-account-list').attr('data-prototype'),
                    '#add-account',
                    '.delete a'
                );
                manager.init();
            } else {
                manager = new CollectionManager(
                    'CustomPrograms',
                    '#booking_request_CustomPrograms > div.inner-block',
                    $('#booking_request_CustomPrograms').attr('data-prototype'),
                    '#add-custom-button',
                    'div.delete'
                );
                manager.init();
            }

            Miles.event();
        },
        event: function(){
            if (AddForm.byBooker() && AddForm.createdByBooker()) {
                $('#tab4').on('select2-selecting', 'input.select2[name$="[UserAgentID]"]', function(event) {
                    var row = $(event.target).closest('tr'),
                        status = row.children('td:eq(3)'),
                        balance = row.children('td:eq(4)'),
                        ua = event.object.id;

                    $.ajax(Routing.generate('aw_booking_json_searchaccounts', {}, false), {
                        data: {
                            ua: ua
                        },
                        dataType: "json"
                    }).done(function(data) {
                        var acc = row.children('td:eq(2)').find('input.select2');
                        acc.select2({data: data}).select2("enable", true);
                        status.text("");
                        balance.text("");
                        acc.trigger('change');
                    });
                });
                $('#tab4').on('select2-selecting', 'input.select2[name$="[AccountID]"]', function(event) {
                    var row = $(event.target).closest('tr'),
                        item = event.object,
                        status = row.children('td:eq(3)'),
                        balance = row.children('td:eq(4)'),
                        exist = false,
                        val;
                    row.closest('table').find('input.select2[name$="[AccountID]"]').not(this).each(function(){
                        val = $(this).select2("val");
                        if (val == item.id) {
                            exist = true;
                            return false;
                        }
                    });
                    if (exist) {
                        event.preventDefault();
                        return;
                    }
                    status.text(item.status);
                    balance.text(item.balance);
                });
                $('#booking-account-list > tbody').on('after_added removed', function(event, data) {
                    var accounts = $(this).children('[data-key]').length;
                    if (accounts >= 50)
                        $('#add-account').parent().hide();
                    else
                        $('#add-account').parent().show();
                });
            } else {
                $('#tab4').on('selected', Miles.tab4selected);
                $('#booking_request_CustomPrograms > div.inner-block').on('before_added', Miles.customAdded);
                $('#booking_request_CustomPrograms').on('input change paste', '.cp-autocomplete', null, Miles.customNameChanged);
                var $accountSelector = $("#account-selector");
                var accountSelectAllState = function() {
                    var $unchecked      = $('td.check-cell input[type="checkbox"]:not(:checked)', $accountSelector);
                    var $accountsChecks = $('#booking_request_Accounts_checks');
                    $accountsChecks.prop('checked', ($unchecked.length ? false : true));
                    touchAccount($accountsChecks);
                };
                var touchAccount = function(elem){
                    var $el = $(elem);
                    $el.next().toggleClass('checked', $el.is(':checked')).closest('tr').toggleClass('focused', $el.is(':checked'));
                    //$(elem).closest("tr").toggleClass("focused", $(elem).is(":checked"));
                };
                var touchAllAccounts = function(){
                    $("#account-selector input[type=checkbox]").each(function(){
                        touchAccount(this);
                    });
                    accountSelectAllState();
                };
                touchAllAccounts();
                AddForm.form.on('post_restore', function(event, data) {
                    touchAllAccounts();
                });
                $accountSelector.on("change", "input[name^='booking_request[Accounts]']", function() {
                    touchAccount(this);
                    accountSelectAllState();
                });
                $('#booking_request_Accounts_checks').click(function() {
                    $('.check-cell input[type="checkbox"]', $accountSelector).prop('checked', $(this).prop('checked')).trigger('change');
                    touchAllAccounts();
                });
            }
        },

        tab4selected: function(){
            $('#booking-custom-programs > fieldset > div.inner-block > div.row').each(function(idx, el){
                Miles.skinCustomRow($(el));
            });
            if($('#account-selector > tbody > tr').length < 3 && !$('.CustomProgram_Name').length){
                console.log('adding custom program');
                $('#add-custom-button').click();
            }
        },
        skinCustomRow: function(row, restore){
            var options = [];
            if (!restore)
                $('#passenger-list fieldset').each(function(idx, el){
                    var name = $(el).find('input[name *= "FirstName"]').val();
                    name += ' ' + $(el).find('input[name *= "LastName"]').val();
                    name = $.trim(name);
                    if(name != '')
                        options.push(name);
                });
            options.sort();
            row.find('input[name*="Owner"]').autocomplete({
                source: options
            });
        },
        customAdded: function(event, data){
            Miles.skinCustomRow($(data['row']), data['obj']._restoreForm); // why we lost jquery object? it was send as jquery from CollectionManager
        },
        customNameChanged: function(event){
            var target = $(event.target);
            var text = $.trim(target.val());
            var div = target.closest('table').find('div.CustomProgram_EliteStatus');
            var input = div.find('input[name], select');

            if(input.attr('data-provider') != text){

                var setLevels = function (levels) {
                    var name = input.attr('name');
                    var val = input.val();
                    console.log('setting levels for '  + name + ', value: ' + val);
                    div.empty();
                    if (levels.length > 0) {
                        Miles.createLevelSelect(div, levels);
                        if (val != null && val != "") {
                            div.find('select option').each(function() {
                                if($(this).val() == val) {
                                    $(this).prop("selected", true);
                                    return false;
                                }
                            });
                            div.find('select').trigger('change');
                        }
                    } else {
                        Miles.createLevelInput(div);
                        div.find('input').val(val).trigger('change');
                    }
                    div.find('input, select').attr('data-provider', text).attr('name', name);
                    AddForm.form.trigger('form_change');
                };

                if(text == '')
                    setLevels([]);
                else
                if (text.length > 5) {
                    $.ajax({
                        url: Routing.generate('aw_booking_json_getelitelevels'),
                        data: {provider: text},
                        dataType: "json",
                        success: setLevels
                    });
                }
            }
        },
        createLevelSelect: function(div, levels){
            div.append('<select></select>');
            var select = div.find('select');
            $.each(levels, function(idx, level){
                select.append("<option value='" + level + "'>" + level + "</option>");
            });
            InputStyle.select(div.children().get(0));
            select.prev('span').css('width', '92%'); // <span> has padding 10px and border 4px, could not set it's wdith to 100%
        },
        createLevelInput: function(div){
            div.append('<input type="text"/>');
        },
        check: function(){
            $('#select_programs_message').hide();

            var accountsChecks   = $('input[name]:checked', '#account-selector');
            var customPrograms   = $('div.CustomProgram_Name');
            var $requiredPayment = $('#requiredPayment');
            $requiredPayment.addClass('hidden');
            if (0 === accountsChecks.length && 0 === customPrograms.length && !AddForm.byBooker() && !AddForm.createdByBooker()) {
                var $bookerPayCash = $('#booking_request_paymentCash');
                if (0 === $bookerPayCash.length || ($bookerPayCash.length && false === $bookerPayCash.is(':checked'))) {
                    AddForm.selectTab('tab4');
                    formValidator.focus($requiredPayment.removeClass('hidden').addClass('row error'));
                    return false;
                }
            }

            return true;
        },

        userAgentsSelect2Options: {
            ajax: {
                url: Routing.generate('aw_booking_json_searchmembers', {}, false),
                dataType: 'json',
                data: function (term, page) {
                    term = term.replace(/[^A-Za-z0-9А-Яа-я ]+/g, '');
                    return {
                        query: term, // search term
                        step: 3
                    };
                },
                results: function(data, page) {
                    return {results: data};
                }
            },
            width: 'copy'
        },
        accountsSelect2Options: {
            width: 'copy'
        }
    };
})(InputStyle);