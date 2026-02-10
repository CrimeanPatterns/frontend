var invoice = {
    itemsList: null,
    milesList: null,
    totals: null,
    form: null,
    curSymbol: null,
    thousand: null,
    decimal: null,

    init: function() {
        var context = this;
        this.itemsList = $('#items-list');
        this.milesList = $('#miles-list');
        this.form = this.itemsList.closest('form');
        this.curSymbol = this.form.data('setting-currency-sign');
        this.thousand = this.form.data('setting-ts');
        this.decimal = this.form.data('setting-dp');
        this.totals = $('#calc-totals .red');

        this.toggleRemoveButton();
        var manager = new CollectionManager(
            'InviceItems',
            '#items-list',
            this.itemsList.attr('data-prototype'),
            '#add-item-link',
            '.delete-miles-btn'
        );
        manager.init();

        var manager2 = new CollectionManager(
            'InviceMiles',
            '#miles-list',
            this.milesList.attr('data-prototype'),
            '#add-miles-link',
            '.delete-miles-btn'
        );
        manager2.init();

        this.itemsList.find('tr[data-key]').each(function(){
            context.calculateTotalsRow($(this));
        });
        context.calculateTotals();
        context.form.trigger('update_tips');
    },

    events: function() {
        var context = this;
        $(document)
            .on('click', 'a#invoice_mark_as_paid', function (e) {
                e.preventDefault();
                var link = $(e.target);
                link.addClass('loader');
                $.ajax({
                    url: Routing.generate('aw_booking_mark_invoice_as_paid', {id: link.data('id')}),
                    data: {paid: link.data('paid')},
                    type: 'POST',
                    success: function(){
                        var messageId = link.closest('.message-block').data('id');
                        document.location.href = document.location.href.replace(/[\?#].*|$/, '?_=' + Date.now() + '#message_'+messageId);
                    }
                });
            })
            .on('keyup change', '#items-list input:not([name$="[description]"])', function(e) {
                context.calculateTotalsRow($(this).closest('tr[data-key]'));
                context.calculateTotals();
            })
            // Submiting invoice form
            .on('submit', '#create-invoice form', function (e) {
                e.preventDefault();
                var sbm_btn = $(this).find('.submitButton');
                if (sbm_btn.hasClass('loader'))
                    return;

                var form = $(this).closest('form'),
                    id = $('#requestView').data('id');
                $.ajax({
                    url: Routing.generate('aw_booking_message_createinvoice', {id: id}),
                    data: form.serialize(),
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function () {
                        sbm_btn.addClass('loader');
                    },
                    success: function (data) {
                        if (data.status == 'success') {
                            $('#commonMessages').find('.message-body').load(Routing.generate('aw_booking_message_getmessages', {id: id, internal: 'common'}), function () {
                                $('[data-cancel-btn]').trigger('click', false);
                                $.scrollTo('#commonMessages .message-body > div:last-child');
                            })
                        }
                        $('#create-invoice').html(data.content).find('select').each(function (id, el) {
                            InputStyle.select(el);
                        });
                        if ($('#items-list').length > 0) {
                            invoice.init();
                        }
                    }
                }).always(function () {
                    sbm_btn.removeClass('loader');
                });
            })
            .on('input change paste', '#items-list .desc-autocomplete', function (e) {
                $(this).autocomplete({
                    source: context.itemsList.data('descriptions'),
                    select: function (event, ui) {
                        event.preventDefault();
                        $(event.target).val(ui.item.label);
                        $(event.target).trigger('change');

                        var quantityInput = $(event.target).closest('tr').find('.CustomProgram_quantity input');
                        if(!quantityInput.val()){
                            quantityInput.val(1);
                        }

                        if(ui.item.value){
                            var priceInput = $(event.target).closest('tr').find('.CustomProgram_price input');
                            priceInput.val(ui.item.value);
                            priceInput.trigger('change');
                        }
                    },
                    minLength: 2
                })
            })
            .on('after_added', '#items-list, #miles-list', function(event, data) {
                data.row.trigger('update_tips');
            })
            .on('after_added', '#items-list', function(event, data) {
                context.calculateTotalsRow(data.row);
            })
            .on('after_added removed', '#items-list', function(event, data) {
                context.toggleRemoveButton();
                context.calculateTotals();
            });
    },

    toggleRemoveButton: function() {
        var items = this.itemsList.find(".delete-miles-btn");
        if (items.length == 1) {
            items.hide();
        } else {
            items.show();
        }
    },

    formatCurrency: function(number, places) {
        number = number || 0;
        thousand = this.thousand;
        decimal = this.decimal;
        symbol = this.curSymbol;
        places = !isNaN(places = Math.abs(places)) ? places : 2;
        var negative = number < 0 ? "-" : "",
            i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
            j = (j = i.length) > 3 ? j % 3 : 0;
        if (parseFloat(number) == parseInt(i)) {
            places = 0;
        }
        return negative + symbol + (j ? i.substr(0, j) + thousand : "")
            + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand)
            + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
    },

    calculateTotals: function() {
        var t = 0;
        this.itemsList.find('tr[data-key]').each(function(){
            if (typeof($(this).data('total')) != 'undefined') {
                t += parseFloat($(this).data('total'));
            }
        });
        this.totals.text(this.formatCurrency(t));
    },

    calculateTotalsRow: function(row) {
        var quantity = row.find('[name$="[quantity]"]').val(),
            discount = this.getDiscount(row),
            price = row.find('[name$="[price]"]').val();

        var price_el = row.find('.price-item'),
            discount_el = row.find('.discount-item'),
            totals_el = row.find('.total-item');

        var total = price * quantity,
            totald = total - (total / 100 * discount);

        if (total) {
            price_el.text(this.formatCurrency(total)).parent().show();
        } else {
            price_el.parent().hide();
            price_el.text('');
        }
        if (total && discount > 0) {
            discount_el.text('-' + discount + '%');
            totals_el.text(this.formatCurrency(totald));
            discount_el.closest('tr').show();
        } else {
            discount_el.closest('tr').hide();
            discount_el.text('');
            totals_el.text('');
        }
        row.data('total', totald);
    },

    getDiscount: function(row) {
        var discount = row.find('[name$="[discount]"]').val() || 0;
        if (/^[0-9]+$/.test(discount) && discount >= 0 && discount <= 100)
            return discount;

        return 0;
    }
};


$(function () {
    if ($('#items-list').length > 0) {
        invoice.init();
        invoice.events();
    }
});
