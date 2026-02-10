/**
 *
 * @param formId - form element id
 * @param askRestore boolean show restore dialog, if there are saved data
 * @param exclude
 * @param autoRestore
 * @returns {{id: *, method: *, form: (*|jQuery|HTMLElement), formInputs: string, notice: *, question: *, exclude: *, storeCollections: storeCollections, restoreCollections: restoreCollections, store: store, restore: restore, formChanged: formChanged, recoverClicked: recoverClicked, clearClicked: clearClicked, clear: clear, bindEvents: bindEvents, init: init}}
 * @constructor
 */

var FormKeeper = function (formId, askRestore, exclude, autoRestore) {

	var self = {

		id: formId,
		askRestore: askRestore,
		form: $('#' + formId),
		formInputs: 'input[type!="hidden"],select,textarea',
		exclude: exclude,
		state: {
            major: {},
            minor: {}
        },

        getDefaultState: function(){
            return {
                major: {},
                minor: {}
            };
        },

		storeCollections: function(){
			self.form.trigger('save_form', [self.state]);
			localStorage.setItem(self.id, JSON.stringify(self.state));
			console.log('stored collections: ' + JSON.stringify(self.state));
		},

		restoreCollections: function(){
			console.log('restoring collections: ' + JSON.stringify(self.state));
			self.form.trigger('restore_form', [self.state]);
		},

        getInputNameAndValue: function(element) {
            var
                name = element.name || ('#' + element.id),
                tag = element.tagName.toLowerCase(),
                type = (element.type || '').toLowerCase(),
                value;

            if (tag == 'input' && type=='checkbox'){
                value = element.checked;
            } else {
                if ($(element).hasClass('date')){
                    var id = $(element).attr('id').split('_datepicker')[0];
                    value = $('#'+id).get(0).value;
                } else {
                    value = $(element).val();
                }
            }

            return {'name': name, 'value': value};
        },

		store: function(element) {
            if(self.exclude && $(element).is(exclude))
                return;
			var field = self.getInputNameAndValue(element);

			console.log('stored ' + field.name + ': ' + field.value);
			self.state['major'][field.name] = field.value;
			localStorage.setItem(self.id, JSON.stringify(self.state));
		},

		restore: function(element) {
			var
				name = element.name || ('#' + element.id),
				tag = element.tagName.toLowerCase(),
				type= (element.type || '').toLowerCase(),
				value = self.state['major'][name];
			if(value == null) {
                if (typeof(self.state['minor']['defaults']) != 'undefined') {
                    if (self.state['minor']['defaults'][name]) {
                        value = self.state['minor']['defaults'][name];
                    }
                }
            }
            if (value == null)
                return;
			if(self.exclude && $(element).is(exclude)) {
				return;
			}
			console.log('restoring ' + name + ': ' + value);
			if(tag == 'input' && type == 'checkbox'){
				element.checked = value;
				if(value){
                    $(element).next().addClass('checked')
				}
			}else{
				if(tag == 'select'){
					$(element).val(value);
					InputStyle.select(element);
				}else{
					if(tag == 'input' && type=='radio'){
						if(value == element.value){
							if(!element.checked){
								console.log('restoring radio ' + name + ': ' + value);
//								self.form.find('input[name="' + name + '"]').each(function(index, el){
//									if(el.value != value){
//										console.log('unchecking radio: ' + el.value);
//										el.checked = false;
//									}
//								});
								self.form.find('input[name="' + name + '"]').each(function(index, el){
									if(el.value != value){
										console.log('unchecking radio: ' + el.value);
										el.checked = false;
										$(el.label).removeClass('checked');
									}
								});
								element.click();
							}
						}
//						else{
//							element.checked = false;
//						}
					}else{
						if(typeof value != 'undefined'){
							if($(element).hasClass('date')){
                                try {
                                    var date=$.datepicker.parseDate('yy-mm-dd', value);
                                    $(element).datepicker('setDate', date);
                                    $(element).trigger("click");
                                } catch(err) {
                                    $(element).val(value).trigger('keyup');
                                }
							}else{
								$(element).val(value);
								$(element).trigger('change');
							}
						}

					}
				}
			}
		},

		formChanged: function(){
			self.storeCollections();
		},

        fieldsRemoved: function(event, row){
            self.removeByElement($(row));
        },

        removeByElement: function(element){
            var name;
            element.find(self.formInputs).each(function(){
                name = this.name || ('#' + this.id);
                delete self.state['major'][name];
            });
        },

		recoverClicked: function() {
			self.restoreCollections();
			self.form.find(self.formInputs).each(
				function () {
					self.restore(this);
				}
			);
			console.log('post_restore: ' + JSON.stringify(self.state));
			Segments.processSegments();
			self.form.trigger('post_restore', [self.state]);
			self.bindEvents();
		},

		clearClicked: function(event) {
			event.preventDefault();
			self.clear();
			self.bindEvents();
		},

		clear: function(){
            console.log('FormKeeper memory cleaning');
			localStorage.removeItem(self.id);
            self.state = self.getDefaultState();
		},

		bindEvents: function(){
			console.log('FormKeeper events bound');
            window['formKeeperTimer'] = null;
            self.form
                .on('change', self.formInputs, null, function(){
                    if (typeof(self.state['minor']['defaults']) == 'undefined')
                        self.saveDefaults();
                    self.store(this);
                })
                .on('keyup', self.formInputs, null, function(){
                    var context = $(this);
                    if (window['formKeeperTimer'] != null)
                        clearTimeout(window['formKeeperTimer']);
                    window['formKeeperTimer'] = setTimeout(function() {
                        window['formKeeperTimer'] = null;
                        context.trigger('change');
                    }, 2000);
                })
                .on('form_change', self.formChanged)
                .on('removed', self.fieldsRemoved);
		},

        saveDefaults: function(){
            var input, type;
            console.log('save defaults');
            self.state['minor']['defaults'] = {};
            self.form.find(self.formInputs).each(function(){
                input = self.getInputNameAndValue(this);
                type = (this.type || '').toLowerCase();

                if (type == 'checkbox' || type == 'radio') {
                    if (this.checked)
                        self.state['minor']['defaults'][input.name] = input.value;
                } else if (input.value != "") {
                    self.state['minor']['defaults'][input.name] = input.value;
                }
            });
            console.log('defaults: '+JSON.stringify(self.state['minor']['defaults']));
            localStorage.setItem(self.id, JSON.stringify(self.state));
        },

		// do we have non-empty saved stated?
		hasState: function(){
			var result = false;
			for (var key in self.state.major) {
			   if (self.state.major.hasOwnProperty(key) && self.state.major[key]) {
			       result = true;
				   break;
			   }
			}
			return result;
		},

		init: function(){
			self.state = JSON.parse(localStorage.getItem(self.id));
			if(!self.state
                || typeof(self.state) != 'object'
                || typeof(self.state.length) == 'number'
                || !self.state['major']
                || !self.state['minor']
            ) // discard state from old versions
                self.state = self.getDefaultState();
			if(self.hasState()){
				if(autoRestore){
					if(window.history != undefined && window.history.replaceState != undefined){
                        window.history.replaceState(null, null, window.location.pathname);
					}else{
                        window.parent.location = window.parent.location.pathname;
					}
                    self.recoverClicked();
                // Show restore popup here
				}else if (askRestore){
                    var buttons = [
						{
							"text": Translator.trans('booking.restoreform.button.dont', {}, 'booking'),
							"click": function (e) {
								self.clearClicked(e);
								$(this).dialog("close");

							},
							"class": "btn-silver"
						},
						{
							"text": Translator.trans('login.button.recovery', {}, 'messages'),
							"click": function (e) {
                                e.preventDefault();
								self.recoverClicked();
								$(this).dialog("close");
							},
							"class": "btn-blue"
						}
					];
                    $('#restorePopup').dialog({

                        title: Translator.trans('booking.restoreform.botton', {}, 'booking'),
                        width: 550,
                        modal: true,
                        buttons: buttons,
                        open: function(){
                            var elem = this;
                            $(elem).parents('.ui-dialog').find('.ui-dialog-titlebar-close').hide();
                            $(window).bind('scroll', function() {
                                $(elem).dialog("option", "position", "center");
                            });
                        },
                        close: function(){
                            $(window).unbind('scroll');
                        }
                    });
                }
			}
			else{
				self.bindEvents();
			}
		}
	};

	self.init();
	return self;

};