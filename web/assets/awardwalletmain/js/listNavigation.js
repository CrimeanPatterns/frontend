// allows navigation in any list that can be represented by jquery node list
// using 'up' and 'down' keys
// allows binding additional keydown handlers (space, enter, etc.)

ListNavigator = function(list) {
	if (typeof list == 'string')
		list = $(list);
	this.list = list;
	this.current = null;
	this.focus = null;
	this.blur = null;

	this.Blur = function() {
		if (this.current != null && typeof(this.blur) == 'function' && typeof(this.list[this.current]) != "undefined")
			this.blur(this.list[this.current]);
	};

	this.Focus = function() {
		if (this.current != null && typeof(this.focus) == 'function' && typeof(this.list[this.current]) != "undefined")
			this.focus(this.list[this.current]);
	};

	this.Adjust = function() {
		if (this.current != null && typeof(this.list[this.current]) != "undefined") {
			var _screen = {
				top: window.scrollY,
				bot: window.scrollY + $(window).height()
			};
			var element = $(this.list[this.current]);
			var _element = {
				top: element.offset().top,
				bot: element.offset().top + element.height()
			};
			if (_element.top < _screen.top || _element.bot > _screen.bot) {
				window.scrollTo(0, _element.top - $(window).height() / 2)
			}
		}
	};

	this.Up = function(element, self) {
		if (self.current != null && self.current > 0) {
			self.Blur();
			self.current--;
			self.Focus();
			self.Adjust();
		}
	};

	this.Down = function(element, self) {
		if (self.current == null || self.current < (self.list.length - 1)) {
			if (self.current == null)
				self.current = 0;
			else {
				self.Blur();
				self.current++;
			}
			self.Focus();
			self.Adjust();
		}
	};

	this.handlers = {
		38: this.Up,
		40: this.Down
	};

	$(document).keydown(this, function(event) {
		var navigator = event.data;
		if (typeof(navigator.handlers[event.keyCode]) == "function" && event.target === $('body').get(0)) {
			event.preventDefault();
			var element = null;
			if (typeof(navigator.current) != "undefined" && navigator.current >= 0 && navigator.current < navigator.list.length) {
				element = navigator.list[navigator.current];
			}
			navigator.handlers[event.keyCode](element, navigator);
		}
	});

	this.setHandler = function(code, handler) {
		this.handlers[code] = handler;
	};

	this.onSpace = function(handler) {
		this.handlers[32] = handler;
	};

	this.onEnter = function(handler) {
		this.handlers[13] = handler;
	}
};
