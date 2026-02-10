var manualParser = function() {
	this.data = null;
	this.showBody = false;
	this.submitUrl = null;
	this.propertiesUrl = null;
	this.rejectUrl = null;
	this.dateUrl = null;
	this.listUrl = null;
	this.key = null;
	this.popupAction = null;
	this.defaultData = function() {
		return {
			Properties: {},
			Itineraries: [],
			check: null,
			errors: [],
			warnings: []
		};
	};
	this.typewatch = (function() {
		var timer = 0;
		return function (callback, ms) {
			clearTimeout(timer);
			timer = setTimeout(callback, ms);
		}
	})();
	this.clickBody = function() {
		var d = $('div#email_source');
		if (this.showBody) {
			d.hide();
			$('#click_body').val('Show email source');
		}
		else {
			d.show();
			$('#click_body').val('Hide');
		}
		this.showBody = !this.showBody;
	};
	this.checkValue = function(input) {
		var type = input.attr('data-type');
		var val = $.trim(input.val());
		if (val.length == 0)
			return null;
		switch (type) {
			case 'number':
				if (/^[\d\.]+$/.exec(val))
					var result = val;
				break;
			case 'date':
				if (input.attr('data-timestamp'))
					var result = input.attr('data-timestamp');
				break;
			default:
				var result = val;
		}
		if (typeof(result) == 'undefined')
			return false;
		else
			return result;
	};
	this.checkFormValues = function(form, kind) {
		if (!kind)
			kind = 'property';
		else
			kind = kind + '_property';
		var fields = form.find('*[data-kind="' + kind + '"]');
		var result = {};
		for (var k = 0; k < fields.length; k++) {
			var input = $(fields[k]);
			var value = this.checkValue(input);
			var name = input.attr('data-property');
			if (value === null) {
				switch (input.attr('data-required')) {
					case 'required':
						this.data.errors.push('Itinerary field ' + name + ' is required');
						break;
					case 'expected':
						this.data.warnings.push('Itinerary field ' + name + ' is expected');
						break;
				}
				continue;
			}
			if (value === false)
				this.data.errors.push('Itinerary field ' + input.attr('data-property') + ' is invalid');
			else {
				if (name == 'Passengers' || name == 'GuestNames')
					result[name] = value.split('\n');
				else
					result[name] = value;
			}
		}
		return result;
	};
	this.checkItineraries = function() {
		var kinds = ['trip', 'rental', 'hotel', 'diner'];
		for (var i in kinds) {
			var kind = kinds[i];
			var its = $('div.data.' + kind);
			for (var j = 0; j < its.length; j++) {
				var it = this.checkFormValues($(its[j]).find('form.it_' + kind), kind);
				if (kind == 'trip') {
					var segments = $(its[j]).find('form.it_trip_segment');
					it.TripSegments = [];
					for (var k = 0; k < segments.length; k++)
						it.TripSegments.push(this.checkFormValues($(segments[k]), 'segment'));
					if (it.TripSegments.length == 0)
						this.data.errors.push('Trip segments are required');
				}
				this.data.Itineraries.push(it);
			}
		}
	};
	this.checkProperties = function() {
		var properties = $('tr.property');
		for (var i = 0; i < properties.length; i++) {
			var input = $(properties[i]).find('input[data-kind="property"]');
			var value = this.checkValue(input);
			if (value === null)
				continue;
			var name = input.attr('data-property');
			if (value !== false) {
				if (typeof(this.data.Properties[name]) != 'undefined')
					this.data.errors.push("Property " + name + " is duplicated");
				else
					this.data.Properties[name] = value;
			}
			else
				this.data.errors.push("Property " + name + " is invalid");
		}
		var c = 0;
		for (i in this.data.Properties)
			c++;
		if (c > 0 && (typeof(this.data.Properties.Balance) == 'undefined'))
			this.data.warnings.push('Account properties without balance');
	};

	this.confirmSubmit = function() {
		if (this.data.errors.length > 0) {
			this.cancelPopup();
			return;
		}
		this.showPopup(false, 'Submitting...');

		var send = {
			Properties: this.data.Properties,
			Itineraries: this.data.Itineraries,
			providerCode: this.data.providerCode,
			key: this.key
		};

		var data = JSON.stringify(send);
		$.ajax({
			url: this.submitUrl,
			type: 'POST',
			data: data,
			context: this,
			success: function(response){
				this.popupAction = this.returnToList;
				this.showPopup(false, response, 'OK', 'Return to list');
			},
			error: function(){
				this.popupAction = null;
				this.showPopup(false, 'ajax error', 'OK');
			}
		});
	};

	this.submitForm = function() {
		this.data = this.defaultData();
		var type = $.trim($('#email_type').val());
		if (type.length == 0)
			this.data.errors.push('Email type is required');
		this.checkProperties();
		this.checkItineraries();
		this.data.providerCode = $('select#providerCode').val();
		if (!this.data.providerCode)
			this.data.errors.push('Provider is required');
		if (this.data.Itineraries.length == 0 && typeof(this.data.Properties.Balance) == 'undefined')
			this.data.errors.push('There should be at least 1 Itinerary or Balance');
		this.popupAction = this.confirmSubmit;
		this.showPopup(true);
	};

	this.showPopup = function(submit, text, cancel, ok) {
		var popup = $('div#popup');
		popup.find('ul').html("");
		popup.find('div').show();
		if (submit) {
			var html = '';
			for (var i in this.data.errors)
				html += "<li>" + this.data.errors[i] + "</li>";
			popup.find('#errors ul').html(html);
			html = '';
			for (i in this.data.warnings)
				html += "<li>" + this.data.warnings[i] + "</li>";
			popup.find('#warnings ul').html(html);
			popup.find('#text').text('Submit this data?');
			if (this.data.warnings.length > 0)
				popup.find('#text').text('Submit this data nonetheless?');
			else
				popup.find('#warnings').hide();
			if (this.data.errors.length > 0) {
				popup.find('#text').hide();
				popup.find('#popup_submit').hide();
			}
			else
				popup.find('#errors').hide();
			popup.find('#popup_cancel').text('Cancel');
			popup.find('#popup_submit').text('Submit');
		}
		else {
			popup.find('#text').text(text);
			popup.find('#errors').hide();
			popup.find('#warnings').hide();
			if (ok)
				popup.find('#popup_submit').text(ok);
			else
				popup.find('#popup_submit').hide();
			if (cancel)
				popup.find('#popup_cancel').text(cancel);
			else
				popup.find('#popup_cancel').hide();
		}
		popup.show();
		window.scrollTo(0,0);
	};

	this.setDateWatchers = function(form) {
		var parser = this;
		var url = this.dateUrl;
		form.find('tr.watch_date').on('keyup', 'input[data-type="date"]', function() {
			var ths = $(this);
			var display = ths.parents('tr.watch_date').next('tr.date_result').find('td');
			var date = $.trim(ths.val());
			if (!date) {
				display.text('');
				return;
			}
			parser.typewatch(function() {
				$.ajax({
					url: url,
					type: 'POST',
					data: {"date" : date},
					success: function(response){
						switch (response.result) {
							case 'fail':
								display.css('color', 'red');
								display.text('INVALID DATE');
								ths.attr('data-timestamp', "");
								break;
							case 'success':
								display.css('color', 'green');
								display.text(response.date);
								ths.attr('data-timestamp', response.unix);
								break;
							case 'warning':
								display.css('color', 'orange');
								display.text(response.date);
								ths.attr('data-timestamp', response.unix);
								break;
						}
					},
					error: function(){
						display.css('font-color', 'red');
						display.text('ajax error');
						ths.attr('data-invalid', 'true');
					}
				});
			}, 1000);
		});

	};

	this.addItinerary = function(kind) {
		if ($('#email_type').val().length == 0)
			$('#email_type').val('Reservation');
		var template = $('div.templates div.' + kind);
		if (!template.length)
			return false;
		var _new = template.clone().appendTo('#inputs').addClass('data');
		_new.one('click', 'div.it_header_'  + kind + ' input[type="button"]', function() {
			_new.remove();
		});
		this.setDateWatchers(_new.find('form'));
		var ths = this;
		if (kind == 'trip') {
			_new.on('click', 'input.add_segment', function() {ths.addSegment(_new);});
		}
		return false;
	};

	this.addSegment = function(trip) {
		var _new = $('div.templates div.segment').clone().appendTo(trip).addClass('data');
		_new.on('click', 'div.it_header_segment input[type="button"]', function() {
			_new.remove();
		});
		this.setDateWatchers(_new.find('form'));
	};

	this.loadProperties = function(code) {
		if (!code || !this.propertiesUrl)
			return false;
		$('div.templates div.segment input[data-property="AirlineName"]').val($('option[value="' + code + '"]').attr('data-short'));
		$.ajax(
			this.propertiesUrl,
		{
			type: 'POST',
			data: {code: code},
			context: this,
			success: function(response) {
				this.setProperties(response);
			},
			error: function() {
				alert('Error occurred');
			}
		}
		);
		return false;
	};

	this.setProperties = function(properties) {
		$('form[name="properties"]').show();
		$('div.it_buttons').show();
		$('tr.add_property').show();
		$('input#submit').show();
		$('select#add_property option[value != ""]').remove();
		$('table.properties tr.ext_property').remove();
		var row = $('select#add_property option[value=""]');
		properties.push({"Name": "Custom", "Code": "-1"});
		for (var property in properties) {
			var _new = row.clone();
			_new.val(properties[property].Code);
			_new.text(properties[property].Name);
			_new.appendTo($('select#add_property'));
		}
	};

	this.addProperty = function() {
		var select = $('select#add_property');
		var val = select.val();
		if (!val)
			return false;
		if (val == -1)
			return this.addCustomProperty();
		else {
			$('input#add_property_button').hide();
			var name = select.find('option[value=' + val + ']').text();
			var _new = $('table.properties tr.balance').clone();
			_new.removeClass('balance').addClass('property').addClass('ext_property').appendTo($('table.properties'));
			_new.find('td.name').text(name);
			_new.find('input').attr('data-property', val).removeAttr('data-type').removeAttr('id').val('');
			$('select#add_property option[value="' + val + '"]').remove();
		}
		return false;
	};

	this.addCustomProperty = function() {
		var _new = $('table.properties tr.custom_template').clone();
		_new.addClass('property').addClass('ext_property').removeClass('custom_template').appendTo($('table.properties')).show();
		_new.one('click', "input[type='button']", function(){
			var name = $.trim(_new.find('td.name input').val()).replace(/\s/g, '');
			if (!name.length)
				_new.remove();
			else {
				_new.find('td.name input').remove();
				_new.find('td.name').text(name);
				_new.find('td.value input').attr('data-kind', 'property').attr('data-property', name);
				$(this).val('-');
				$(this).one('click', function(){
					_new.remove();
				});
			}
		});
		return false;
	};

	this.cancelPopup = function() {
		$('div#popup').hide();
		this.popupAction = null;
	};

	this.junkMail = function() {
		this.popupAction = this.rejectMessage;
		this.showPopup(false, "Reject this message?", 'Cancel', 'Reject');
	};

	this.rejectMessage = function() {
		this.showPopup(false, 'Loading...');
		$.ajax({
			url: this.rejectUrl,
			type: "POST",
			context: this,
			success: function(data) {
				this.popupAction = this.returnToList;
				this.showPopup(false, data, 'OK', 'Return to list');
			},
			error: function() {
				this.showPopup(false, 'AJAX error', 'OK');
				this.popupAction = null;
			}
		});
	};

	this.returnToList = function() {
		document.location.href = this.listUrl;
	}
};
