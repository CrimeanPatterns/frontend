var CollectionManager = function(name, containerSelector, prototype, addSelector, removeSelector) {
    return {
        name: name,
        containerSelector: containerSelector,
        prototype: prototype,
        addSelector: addSelector,
        removeSelector: removeSelector,
        placeholder: '__name__',
        beforeAddEventName: 'before_added',
        afterAddEventName: 'after_added',
        removeEventName: 'removed',
        animationShow: {
            type: 'slideDown',
            duration: 400
        },
        animationHide: {
            type: 'slideUp',
            duration: 400
        },
        beforeAdd: null,
        beforeRemove: null,
        _notFireEvent: false,
        _restoreForm: false,
		form: null,
        prototypeTag: null,

        init: function() {
            var context = this;
            this.prototypeTag = $($.trim(this.prototype)).prop("tagName").toLowerCase();
            this.getSelector(this.addSelector).click(function(event) {
                event.preventDefault();
                if (typeof(context.beforeAdd) != 'function' || context.beforeAdd(this))
                    $.proxy(context.addItem, context)(event);
            });
            this.getSelector(this.containerSelector).on('click', this.removeSelector, null, function(event) {
                event.preventDefault();
                if (typeof(context.beforeRemove) != 'function' || context.beforeRemove(this))
                    $.proxy(context.removeItem, context)(event);
                return false;
            });
			form = this.getSelector(this.containerSelector).closest('form');
            form
                .on('check_form', this.onCheckForm.bind(this))
                .on('save_form', this.onSaveForm.bind(this))
                .on('restore_form', this.onRestoreForm.bind(this));
        },

        addItem: function(event, success, name) {
            var number = (typeof(name) != "undefined") ? name : new Date().getTime(),
                html = $.trim(this.prototype.replace(new RegExp(this.placeholder, 'g'), number));
            var newItem = $(html).hide();
            this.getSelector(this.containerSelector).append(newItem);
            // animation appearance
            this.showCallback(newItem, this.animationShow, success);
            $(document).trigger('dom_added');
			if(event)
				event.preventDefault();
        },

        removeItem: function(event, success, name) {
            var prototype = $($.trim(this.prototype)),
                tag = prototype.first().prop("tagName").toLowerCase(),
                className = prototype.first().attr('class'),
                id = prototype.first().attr('id'),
                parentSelector = '',
                target = (typeof(event) != 'undefined' && event != null &&
                    typeof(event['target']) != 'undefined') ?
                    $(event.target)
                    : this.getItems().filter(':last');
            if (typeof(name) != "undefined") target = this.getItems().filter('[data-key="'+name+'"]');
            if (typeof(tag) != "undefined") parentSelector = tag;
            if (typeof(className) != "undefined") parentSelector = parentSelector+'.'+(className.split(" ")[0]);

            if (parentSelector != '')
                this.hideCallback(target.closest(parentSelector), this.animationHide, success);
            return false;
        },

        showCallback: function(row, animate, success) {
            var context = this,
                notificationSent = false,
                container = this.getSelector(this.containerSelector);
            row[animate.type]({
                duration: animate.duration,
                progress: function(animation, progress, remainingMs){
                    // send notification when element is already visible, to allow correct resizing of selects
                    if(progress > 0 && !notificationSent){
                        notificationSent = true;
                        if (!context._notFireEvent)
                            container.trigger(context.beforeAddEventName, {obj: context, row: row});
                    }
                },
                complete: function(){
                    if(!notificationSent)
                        if (!context._notFireEvent)
                            container.trigger(context.beforeAddEventName, {obj: context, row: row});
                    row.find('input[type="text"]').first().focus();
                    InputStyle.init(row);
                    if (!context._notFireEvent){
                        container.trigger(context.afterAddEventName, {obj: context, row: row});
                        form.trigger('form_change', this);
					}
                    if (typeof(success) == 'function')
                        success();
                }
            });
        },

        hideCallback: function(row, animate, success) {
            var context = this,
                container = this.getSelector(this.containerSelector);

            row[animate.type]({
                duration: animate.duration,
                complete: function(){
                    row.remove();
                    if (!context._notFireEvent) {
                        container.trigger(context.removeEventName, row);
						form.trigger('form_change', this);
                    }
                    if (typeof(success) == 'function')
                        success();
                }
            });
        },

        onCheckForm: function(event, data) {
            var context = this,
                container = this.getSelector(this.containerSelector);
            window.console && console.log("checking collection " + this.name + ", rows: " + context.getItems().length + ', will remove empty rows');
            for(var n = context.getItems().length - 1; n >= 0; n--){
                var row = context.getItems().eq(n);
                var inputs = row.find('input, select, textarea, checkbox');
                var emptyInputs = inputs.filter(function() {
                    switch(this.type){
                        case 'radio':
                            return form.find('input[name="' + context.name + '"]:checked').length == 0;
                        case 'checkbox':
                            return !this.checked;
                        case 'select':
                        case 'select-one':
                            // select without empty option: <option value="">Bla bla</option> will be considered as empty
                            // @TODO: collection with only one select will not function
                            var emptyOptions = $(this).find('option').filter(function(){
                                return $.trim(this.value) == '';
                            });
                            if(emptyOptions.length == 0)
                                return true;
                            return $.trim($(this).val()) == '';
                        default:
                            return $.trim($(this).val()) == '';
                    }
                });
                if(inputs.length == emptyInputs.length){
                    window.console && console.log('discarding row ' + n + ' as empty');
                    row.remove();
                }
            }
        },

        onSaveForm: function(event, data) {
            window.console && console.log("saving collection " + this.name + ", rows: " + this.getItems().length);
            var idx = [];
            this.getItems().each(function(){
                idx.push($(this).attr('data-key'));
            });
            data['minor'][this.name] = idx;
        },

        onRestoreForm: function(event, data) {
            var items = this.getItems(),
                prevShowAnimate = this.animationShow,
                prevHideAnimate = this.animationHide,
                context = this;
            window.console && console.log("restore fired for " + this.name);
            this._restoreForm = true;
            if(typeof(data['minor'][this.name]) != "undefined" && $.isArray(data['minor'][this.name])){
                window.console && console.log("restoring collection " + this.name + ', rows: ' + JSON.stringify(data['minor'][this.name]));
                this.animationShow = {
                    type: 'show',
                    duration: 0
                };
                this.animationHide = {
                    type: 'hide',
                    duration: 0
                };
                var exists = [];
                var stored = data['minor'][this.name];
                items.each(function(){
                    exists.push($(this).attr('data-key'));
                });

                $.each(stored, function(index, value) {
                    if ($.inArray(value, exists) === -1) {
                        context.addItem(null, null, value);
                        exists.push(value);
                    }
                });
                $.each(exists, function(index, value) {
                    if ($.inArray(value, stored) === -1) {
                        context.removeItem(null, null, value);
                    }
                });

                this.animationShow = prevShowAnimate;
                this.animationHide = prevHideAnimate;
            }
            this._restoreForm = false;
            window.console && console.log("end restore for " + this.name);
        },

        getSelector: function(s) {
            if(typeof(s)=='string')return $(s);
            return s;
        },

        getItems: function() {
            var container = this.getSelector(this.containerSelector);
            if (this.prototypeTag != 'tr')
                return container.children();

            return container.children('tbody').children();
        }
    };
};
