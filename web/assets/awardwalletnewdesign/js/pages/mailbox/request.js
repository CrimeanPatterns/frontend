define(['jquery-boot'], function ($) {
    var Request =
        function () {
            function Request() {
                this.container = null;
                this.busy = false;
                this.busyTimer = null;
                this.faderId = 'userMailboxFader';
                this.fader = $('<div style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: white; -ms-filter: \'progid:DXImageTransform.Microsoft.Alpha(Opacity=80)\'; filter: alpha(opacity=80); opacity: 0.8; z-index: 100;" id="' + this.faderId + '"></div>');
            }

            var _proto = Request.prototype;

            _proto.setContainer = function setContainer(container) {
                this.container = container;
            };

            _proto.showButtonProgress = function showButtonProgress(button) {
                $(button).find('input').attr('disabled', 'disabled');
            };

            _proto.hideButtonProgress = function hideButtonProgress(button) {
                $(button).find('input').removeAttr('disabled');
            };

            _proto.lock = function lock() {
                if (this.busy) {
                    return;
                }

                if (this.container) {
                    this.fader.clone().appendTo(this.container).css({
                        opacity: 0
                    }).height($(document).height()).show().stop().animate({
                        opacity: 0.5
                    }, 2000);
                }

                this.busy = true;
            };

            _proto.unlock = function unlock() {
                if (this.container) {
                    this.container.find('#' + this.faderId).stop().animate({
                        opacity: 0
                    }, {
                        duration: 600,
                        complete: function complete() {
                            $(this).remove();
                        }
                    });
                }

                this.busy = false;
            };

            _proto.request = function request(url, method, data, settings) {
                var _this = this;

                if (this.busy) {
                    return;
                }

                var defaults = {
                    timeout: 30000,
                    before: function before() {},
                    complete: function complete() {},
                    success: function success() {},
                    error: function error() {},
                    button: null
                };
                settings = $.extend({}, defaults, settings);
                $.ajax({
                    url: url,
                    dataType: 'json',
                    type: method,
                    data: data,
                    timeout: settings.timeout,
                    beforeSend: function beforeSend() {
                        if (!_this.busy) {
                            clearTimeout(_this.busyTimer);
                            _this.busyTimer = setTimeout(function () {
                                _this.unlock();
                            }, settings.timeout + 1000);
                        }

                        _this.lock();

                        if (typeof(settings.button) === 'object' && settings.button !== null) {
                            _this.showButtonProgress(settings.button);
                        }

                        settings.before();
                    },
                    complete: function complete() {
                        settings.complete();
                    },
                    // todo deprecated
                    success: function success(json) {
                        if (settings.success(json)) {
                            _this.unlock();

                            if (typeof(settings.button) === 'object') {
                                _this.hideButtonProgress(settings.button);
                            }
                        }
                    },
                    error: function error(jqXHR, status, _error) {
                        _this.unlock();

                        if (typeof(settings.button) === 'object') {
                            _this.hideButtonProgress(settings.button);
                        }

                        settings.error(jqXHR, status, _error);
                    }
                });
            };

            return Request;
        }();

    return new Request();
});