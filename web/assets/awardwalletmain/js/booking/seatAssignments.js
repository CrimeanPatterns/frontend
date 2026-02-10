var seatAssignments = {
    minLength: 5,
    formSubmitted: false,
    timeout: null,

    init: function() {
        seatAssignments.initForm();
        var timeout, ajax;
        $('#seat-assignments')
            .on('input change paste', '.cp-autocomplete', null, function(event) {
                window.clearTimeout(timeout);
                timeout = window.setTimeout(function(){
                    if (ajax != null && typeof(ajax['abort']) == 'function') ajax.abort();
                    var target = $(event.target);
                    var text = $.trim(target.val());
                    var container = target.closest('tr').find('div.CustomProgram_Phone');
                    var input = container.find('input[name], select');
                    var setPhones = function(phones) {
                        var name = input.attr('name');
                        var val = input.val();
                        container.empty();
                        if (phones.length > 0) {
                            seatAssignments.createPhoneSelect(container, phones);
                            if (val != null && val != "") {
                                container.find('select option').each(function() {
                                    if($(this).val() == val) {
                                        $(this).prop("selected", true);
                                        return false;
                                    }
                                });
                            }
                        } else {
                            seatAssignments.createPhoneInput(container);
                            container.find('input').val(val);
                        }
                        container.find('input, select').attr('data-provider', text).attr('name', name);
                    };

                    if(input.attr('data-provider') != text){
                        if (text == '')
                            setPhones([]);
                        else
                        if (text.length >= seatAssignments.minLength) {
                            ajax = $.ajax({
                                url: Routing.generate('aw_booking_json_getphones', {provider: text}),
                                dataType: "json",
                                success: setPhones
                            });
                        }
                    }
                }, 500);
            })
            .on('submit', 'form', function(event) {
                event.preventDefault();
                if (seatAssignments.formSubmitted)
                    return false;
                seatAssignments.disableSubmit();

                var form = $(this).closest('form'),
                    id = $('#requestView').data('id');
                $.ajax({
                    url: Routing.generate('aw_booking_message_seatassignments', {id: id}),
                    data: form.serialize(),
                    type: 'POST',
                    dataType: 'json',
                    success: function (data) {
                        if(data.status == 'success'){
                            $('#seat-assignments').html(data.form);
                            seatAssignments.initForm();
                            $('#commonMessages').find('.message-body').load(Routing.generate('aw_booking_message_getmessages', {id: id, internal: 'common'}), function(){
                                $('[data-cancel-btn]').trigger('click', false);
                                $.scrollTo('#commonMessages .message-body > div:last-child');
                            });
                        } else {
                            seatAssignments.showErrors(data.errors);
                        }
                        seatAssignments.enableSubmit();
                    }
                }).always(function() {
                    seatAssignments.enableSubmit();
                });
                return false;
            })
            .on('change keyup paste', 'input, select', function(){
                $(this).closest('.row').removeClass('error');
            });
    },
    initForm: function() {
        var context = this;
        this.toggleRemoveButton();
        $('#seat-assignments-list').on('after_added removed', function(event, data) {
            context.toggleRemoveButton();
        });
        var manager = new CollectionManager(
            'PhoneNumbers',
            '#seat-assignments-list',
            $('#seat-assignments-list').attr('data-prototype'),
            '#seat-assignments .add-block',
            '.delete-miles-btn'
        );
        manager.animationShow = {
            type: 'fadeIn',
            duration: 200
        };
        manager.animationHide = {
            type: 'fadeOut',
            duration: 200
        };
        manager.init();
    },
    enableSubmit: function() {
        $('#respond-block').darkfader('hide', function(){
            $('#seat-assignments .submitButton').removeClass('loader');
            seatAssignments.formSubmitted = false;
            window.clearTimeout(seatAssignments.timeout);
        });
    },
    disableSubmit: function() {
        $('#seat-assignments .submitButton').addClass('loader');
        seatAssignments.formSubmitted = true;
        seatAssignments.timeout = window.setTimeout(seatAssignments.enableSubmit, 10000);
        $('#respond-block').darkfader('show', {
            'opacity': 0
        });
    },
    showErrors: function(errors) {
        $('#seat-assignments form div.row').removeClass('error').find('div.message').empty().hide();
        $.each(errors, function(idx, error){
            seatAssignments.addError($('#seat-assignments [name="'+error.name+'"]'), error.errorText);
        });
        // focus
        $('#seat-assignments form div.error:first').find('input, select').focus();
    },
    addError: function(field, error) {
        field.closest('div.row').addClass('error').find('div.message').text(error).show();
    },
    createPhoneSelect: function(container, phones) {
        container.append('<select></select>');
        var select = container.find('select'),
            label;
        $.each(phones, function(idx, phone){
            label = (phone[1] == null) ? phone[0] : phone[1] + " " + phone[0];
            select.append("<option value='" + phone[0] + "'>" + label + "</option>");
        });
        InputStyle.select(container.children().get(0));
        select.trigger('change');
        select.prev('span').css('width', '92%'); // <span> has padding 10px and border 4px, could not set it's wdith to 100%
    },
    createPhoneInput: function(container) {
        container.append('<input type="text"/>');
    },
    toggleRemoveButton: function() {
        var items = $("#seat-assignments-list tr");
        if (items.length == 1) {
            items.find('.delete-miles-btn').hide();
        } else {
            items.find('.delete-miles-btn').show();
        }
    }
};

$(document).ready(function () {
    seatAssignments.init();
});
