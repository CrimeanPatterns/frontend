(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["web_assets_awardwalletnewdesign_js_lib_dialog_js"],{

/***/ "./web/assets/awardwalletnewdesign/js/lib/dialog.js":
/*!**********************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/lib/dialog.js ***!
  \**********************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
__webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
__webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
__webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
__webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! lib/utils */ "./web/assets/awardwalletnewdesign/js/lib/utils.js"), __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function ($, utils) {
  var Dialog = function Dialog(element) {
    var icon = element.dialog('option', 'type');
    if (icon) element.dialog('widget').find('.ui-dialog-title').prepend(icon);
    var cssClass = element.data('addclass');
    if (cssClass) {
      element.parent().addClass(cssClass);
    }
    var positionAt = element.data('position-at');
    if (positionAt) {
      $(element).dialog('option', 'position', {
        at: positionAt
      });
    }
    this.element = element;
  };
  Dialog.prototype = {
    isOpen: function isOpen() {
      return this.element.dialog('isOpen');
    },
    moveToTop: function moveToTop() {
      this.element.dialog('moveToTop');
    },
    open: function open() {
      this.element.dialog('open');
      if (this.getOption('modal')) {
        utils.documentScroll().lock();
      }
    },
    close: function close() {
      if (this.getOption('modal')) {
        utils.documentScroll().unlock();
      }
      this.element.dialog('close');
    },
    destroy: function destroy() {
      if (this.getOption && this.getOption('modal')) {
        utils.documentScroll().unlock();
      }
      this.element.dialog('destroy').remove();
    },
    getOption: function getOption(option) {
      if (typeof option == 'undefined') return this.element.dialog("option");
      return this.element.dialog("option", option);
    },
    setOption: function setOption(name, value) {
      if (name != null && _typeof(name) == 'object') return this.element.dialog("option", extendOptions(name));
      return this.element.dialog("option", name, extendOption(name, value));
    }
  };
  var extendOptions = function extendOptions(options) {
    options["open"] = options["open"] || null;
    options["close"] = options["close"] || null;
    $.each(options, function (key, value) {
      options[key] = extendOption(key, value);
    });
    return options;
  };
  var extendOption = function extendOption(key, option) {
    var o = option;
    switch (key) {
      case "open":
        option = function option(event, ui) {
          $('body').one('click', '.ui-widget-overlay', function () {
            $('.ui-dialog:visible .ui-dialog-content').each(function () {
              if ($(this).is(':data(uiDialog)') && $(this).dialog("isOpen")) {
                $(this).dialog("close");
              }
            });
          });
          $(window).off('resize.dialog').on('resize.dialog', function () {
            $(event.target).dialog("option", "position", {
              my: "center",
              at: "center",
              of: window
            });
          });
          (o || function () {})(event, ui);
        };
        break;
      case "close":
        option = function option(event, ui) {
          utils.documentScroll().unlock();
          $(window).off('resize.dialog');
          (o || function () {})(event, ui);
        };
        break;
      case "type":
        if (option && !(option instanceof Object)) {
          option = option ? '<i class="icon-' + option + '-small"></i>' : null;
        }
        break;
    }
    return option;
  };
  return {
    dialogs: {},
    createNamed: function createNamed(name, elem, options) {
      options = extendOptions(options);
      return this.dialogs[name] = new Dialog(elem.dialog(options));
    },
    has: function has(name) {
      return typeof this.dialogs[name] != 'undefined';
    },
    get: function get(name) {
      return this.dialogs[name];
    },
    remove: function remove(name) {
      if (!this.has(name)) return;
      this.get(name).destroy();
      delete this.dialogs[name];
    },
    fastCreate: function fastCreate(title, content, modal, autoOpen, buttons, width, height, type) {
      var element, options;
      if (content != null && _typeof(content) == 'object' && typeof title != 'undefined') {
        element = $('<div>' + title + '</div>');
        options = extendOptions(content);
        return new Dialog(element.dialog(options));
      }
      element = $('<div>' + (content || '') + '</div>');
      options = extendOptions({
        autoOpen: autoOpen || true,
        modal: modal || true,
        buttons: buttons || [],
        width: width || 300,
        height: height || 'auto',
        title: title || null,
        type: type || null,
        close: function close() {
          $(this).dialog('destroy').remove();
        }
      });
      var d = new Dialog(element.dialog(options));
      d.setOption("close", function () {
        d.destroy();
      });
      return d;
    },
    alert: function alert(text, title) {
      var element = $('<div>' + text + '</div>'),
        options = {
          autoOpen: true,
          modal: true,
          buttons: [{
            'text': Translator.trans('button.ok'),
            'click': function click() {
              $(this).dialog("close");
            },
            'class': 'btn-silver'
          }],
          closeOnEscape: true,
          draggable: true,
          resizable: false,
          width: 300,
          height: 'auto',
          title: title || '',
          close: function close() {
            $(this).dialog('destroy').remove();
          }
        };
      return new Dialog(element.dialog(options));
    },
    prompt: function prompt(text, title, nocallback, yescallback) {
      var element = $('<div>' + text + '</div>'),
        options = {
          autoOpen: true,
          modal: true,
          buttons: [{
            'text': Translator.trans('button.no'),
            'click': function click() {
              $(this).dialog("close");
              nocallback();
            },
            'class': 'btn-silver'
          }, {
            'text': Translator.trans('button.yes'),
            'click': function click() {
              $(this).dialog("close");
              yescallback();
            },
            'class': 'btn-blue'
          }],
          closeOnEscape: true,
          draggable: true,
          resizable: false,
          width: 600,
          height: 'auto',
          title: title || '',
          close: function close() {
            $(this).dialog('destroy').remove();
          }
        };
      return new Dialog(element.dialog(options));
    }
  };
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/lib/utils.js":
/*!*********************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/lib/utils.js ***!
  \*********************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
__webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
__webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
__webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
__webpack_require__(/*! core-js/modules/es.number.to-fixed.js */ "./node_modules/core-js/modules/es.number.to-fixed.js");
!(__WEBPACK_AMD_DEFINE_RESULT__ = (function () {
  var utils = {};
  utils.getUrlParam = function (name, url) {
    if (!url) {
      url = window.location.href;
    }
    if (url && !/^\/{1}[\w].+/.test(url)) {
      url = '/redirect_not_allowed';
      return url;
    }
    var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(url);
    if (!results) {
      return 0;
    }
    return results[1] || 0;
  };
  utils.setCookie = function setCookie(name, value, expires, path, domain, secure) {
    document.cookie = name + "=" + escape(value) + (expires ? "; expires=" + expires : "") + (path ? "; path=" + path : "") + (domain ? "; domain=" + domain : "") + (secure ? "; secure" : "");
  };
  utils.getCookie = function getCookie(name) {
    var matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
    return matches ? decodeURIComponent(matches[1]) : undefined;
  };
  utils.escape = function (text) {
    var entities = [['apos', '\''], ['amp', '&'], ['lt', '<'], ['gt', '>']];
    for (var i = 0, max = entities.length; i < max; ++i) text = text.replace(new RegExp('&' + entities[i][0] + ';', 'g'), entities[i][1]);
    return text;
  };
  utils.elementInViewport = function (el) {
    if (typeof jQuery === "function" && el instanceof jQuery) {
      el = el[0];
    }
    var rect = el.getBoundingClientRect();
    return rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
    rect.right <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */;
  };

  var timeout;
  utils.cancelDebounce = function () {
    if (timeout) {
      clearTimeout(timeout);
    }
  };
  utils.debounce = function (func, wait) {
    utils.cancelDebounce();
    timeout = setTimeout(func, wait);
  };
  utils.getNumberFormatter = function () {
    var selector = $('a[data-target="select-language"]');
    var locale = 'en';
    var region = selector.attr('data-region');
    var lang = selector.attr('data-language');
    if (!region && lang && lang.length === 5) locale = lang.replace('_', '-');else if (region && lang) {
      locale = region + '-' + lang.substring(0, 2);
    } else if (lang) {
      locale = lang.substring(0, 2);
    } else {
      // fallback
      locale = 'en';
    }
    var supportedLocales = Intl.NumberFormat.supportedLocalesOf(locale);
    var userLocale = supportedLocales.length ? supportedLocales[0] : null;
    return userLocale ? new Intl.NumberFormat(userLocale, {
      maximumFractionDigits: 0
    }) : new Intl.NumberFormat();
  };
  utils.ucfirst = function (str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  };
  utils.digitFilter = function (event) {
    if (isNaN(String.fromCharCode(event.keyCode)) && '.' !== String.fromCharCode(event.keyCode)) {
      event.preventDefault();
    }
  };
  utils.reverseFormatNumber = function (value, locale) {
    value = value.replace(/\D,/g, '');
    if (undefined === locale) {
      locale = $('a[data-target="select-language"]').data('language') || $('html').attr('lang').substr(0, 2);
    }
    var group = new Intl.NumberFormat(locale).format(1111).replace(/1/g, '');
    if ('' == group) {
      group = ',';
    }
    var decimal = new Intl.NumberFormat(locale).format(1.1).replace(/1/g, '');
    if ('' == decimal) {
      decimal = '.';
    }
    var num = value.replace(new RegExp('\\' + group, 'g'), '');
    num = num.replace(new RegExp('\\' + decimal, 'g'), '.');
    return !isNaN(parseFloat(num)) && isFinite(num) ? num : null;
  };
  utils.documentScroll = function () {
    var $body = $('body');
    function getScrollbarWidth() {
      var $scr = $('#scrollbarIdentify');
      if (!$scr.length) {
        $scr = $('<div id="scrollbarIdentify" style="position: absolute;top:-1000px;left:-1000px;width: 100px;height: 50px;box-sizing:border-box;overflow-y: scroll;"><div style="width: 100%;height: 200px;"></div></div>');
        $body.append($scr);
      }
      return $scr[0].offsetWidth - $scr[0].clientWidth;
    }
    return {
      lock: function lock() {
        var root = document.compatMode === 'BackCompat' ? document.body : document.documentElement;
        if (root.scrollHeight > root.clientHeight) {
          var scrWidth = getScrollbarWidth();
          $body.css({
            'overflow': 'hidden',
            'padding-right': scrWidth
          });
        }
      },
      unlock: function unlock() {
        $body.css({
          'overflow': 'auto',
          'padding-right': '0'
        });
      }
    };
  };
  utils.formatFileSize = function (bytes, dp) {
    dp = dp || 1;
    var thresh = 1024;
    if (Math.abs(bytes) < thresh) {
      return bytes + ' B';
    }
    var units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    var r = Math.pow(10, dp);
    var u = -1;
    do {
      bytes /= thresh;
      ++u;
    } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);
    return bytes.toFixed(dp) + ' ' + units[u];
  };
  utils.linkify = function (text) {
    var protocolPattern = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    text = text.replace(protocolPattern, '<a href="$1" target="_blank">$1</a>');
    var wwwPattern = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    text = text.replace(wwwPattern, '$1<a href="http://$2" target="_blank">$2</a>');
    var mailPattern = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
    text = text.replace(mailPattern, '<a href="mailto:$1">$1</a>');
    return text;
  };
  return utils;
}).call(exports, __webpack_require__, exports, module),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/common/js/jquery-boot.js":
/*!*********************************************!*\
  !*** ./web/assets/common/js/jquery-boot.js ***!
  \*********************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js"), __webpack_require__(/*! cookie */ "./web/assets/common/vendors/jquery.cookie/jquery.cookie.js"), __webpack_require__(/*! common/jquery-handlers */ "./web/assets/common/js/jquery-handlers.js"), __webpack_require__(/*! intl */ "./node_modules/intl/index.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
  return $;
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/common/js/jquery-handlers.js":
/*!*************************************************!*\
  !*** ./web/assets/common/js/jquery-handlers.js ***!
  \*************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
__webpack_require__(/*! core-js/modules/es.string.trim.js */ "./node_modules/core-js/modules/es.string.trim.js");
__webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
__webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
__webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
__webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
  $(function () {
    window.csrf_token = null;

    // show error dialogs on ajax errors
    $(document).ajaxError(function (event, jqXHR, ajaxSettings) {
      // retry with fresh CSRF token, if we got CSRF error from server
      var canCsrfRetry = typeof ajaxSettings.csrfRetry === 'undefined';
      var csrfCode = jqXHR.getResponseHeader('X-XSRF-TOKEN');
      var csrfFailed = jqXHR.getResponseHeader('X-XSRF-FAILED') === 'true';
      if (csrfCode) {
        window.csrf_token = csrfCode;
      }
      if (jqXHR.status === 403 && csrfFailed && canCsrfRetry) {
        console.log('retrying with fresh CSRF, should receive on in headers');
        // mark request as retry
        ajaxSettings.csrfRetry = true;
        $.ajax(ajaxSettings);
        return;
      }
      if (ajaxSettings.disableAwErrorHandler) {
        return;
      }
      __webpack_require__.e(/*! AMD require */ "web_assets_awardwalletnewdesign_js_lib_errorDialog_js").then(function() { var __WEBPACK_AMD_REQUIRE_ARRAY__ = [__webpack_require__(/*! lib/errorDialog */ "./web/assets/awardwalletnewdesign/js/lib/errorDialog.js")]; (function (showErrorDialog) {
        showErrorDialog({
          status: jqXHR.status,
          data: jqXHR.responseJSON ? jqXHR.responseJSON : jqXHR.responseText,
          config: {
            method: ajaxSettings.type,
            url: ajaxSettings.url,
            data: decodeURI(ajaxSettings.data)
          }
        }, typeof ajaxSettings.disableErrorDialog != 'undefined' && ajaxSettings.disableErrorDialog);
      }).apply(null, __WEBPACK_AMD_REQUIRE_ARRAY__);})['catch'](__webpack_require__.oe);
    });

    // add CSRF header to ajax POST requests
    $(document).ajaxSend(function (elm, xhr, s) {
      if (window.csrf_token === null) {
        window.csrf_token = document.head.querySelector('meta[name="csrf-token"]').content;
      }
      xhr.setRequestHeader('X-XSRF-TOKEN', window.csrf_token);
    });
    $(document).ajaxSuccess(function (event, jqXHR, settings) {
      var mailErrors = $.trim(jqXHR.getResponseHeader('x-aw-mail-failed'));
      var csrfCode = jqXHR.getResponseHeader('X-XSRF-TOKEN');
      if (mailErrors != '' && !settings.suppressErrors) {
        __webpack_require__.e(/*! AMD require */ "web_assets_awardwalletnewdesign_js_lib_mailErrorDialog_js").then(function() { var __WEBPACK_AMD_REQUIRE_ARRAY__ = [__webpack_require__(/*! lib/mailErrorDialog */ "./web/assets/awardwalletnewdesign/js/lib/mailErrorDialog.js")]; (function (showErrorDialog) {
          showErrorDialog(mailErrors);
        }).apply(null, __WEBPACK_AMD_REQUIRE_ARRAY__);})['catch'](__webpack_require__.oe);
      }
      if (csrfCode) {
        window.csrf_token = csrfCode;
      }
    });
    window.onerrorCounter = 0;
    window.onerrorHandler = function (e) {
      window.onerrorCounter++;
      if (window.onerrorCounter < 10) {
        $.post('/js_error', {
          error: e.message,
          file: e.fileName,
          line: e.lineNumber,
          column: e.columnNumber,
          stack: e.stack
        }, {
          disableErrorDialog: true
        });
        if (typeof e.fileName != 'undefined') {
          var a = document.createElement('a');
          a.href = e.fileName;
          if (a.href.indexOf('service-worker.js') >= 0 || a.hostname && a.hostname == window.location.hostname) {
            // exclude external scripts
            __webpack_require__.e(/*! AMD require */ "web_assets_awardwalletnewdesign_js_lib_errorDialog_js").then(function() { var __WEBPACK_AMD_REQUIRE_ARRAY__ = [__webpack_require__(/*! lib/errorDialog */ "./web/assets/awardwalletnewdesign/js/lib/errorDialog.js")]; (function (showErrorDialog) {
              showErrorDialog({
                status: 0,
                data: e.message + '<br\><br\>' + e.stack // only in debug mode
              });
            }).apply(null, __WEBPACK_AMD_REQUIRE_ARRAY__);})['catch'](__webpack_require__.oe);
          }
        }
      }
    };

    window.onerror = function (message, file, line, column, e) {
      if (e && _typeof(e) == 'object') {
        window.onerrorHandler(e);
      } else {
        window.onerrorHandler({
          message: message,
          fileName: file,
          lineNumber: line,
          columnNumber: column,
          stack: null
        });
      }
      return false;
    };
  });
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/common/vendors/jquery.cookie/jquery.cookie.js":
/*!******************************************************************!*\
  !*** ./web/assets/common/vendors/jquery.cookie/jquery.cookie.js ***!
  \******************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
__webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
__webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
__webpack_require__(/*! core-js/modules/es.array.join.js */ "./node_modules/core-js/modules/es.array.join.js");
__webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
__webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
__webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
__webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
/*!
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
  if (true) {
    // AMD
    !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js")], __WEBPACK_AMD_DEFINE_FACTORY__ = (factory),
		__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
		(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
  } else {}
})(function ($) {
  var pluses = /\+/g;
  function encode(s) {
    return config.raw ? s : encodeURIComponent(s);
  }
  function decode(s) {
    return config.raw ? s : decodeURIComponent(s);
  }
  function stringifyCookieValue(value) {
    return encode(config.json ? JSON.stringify(value) : String(value));
  }
  function parseCookieValue(s) {
    if (s.indexOf('"') === 0) {
      // This is a quoted cookie as according to RFC2068, unescape...
      s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
    }
    try {
      // Replace server-side written pluses with spaces.
      // If we can't decode the cookie, ignore it, it's unusable.
      // If we can't parse the cookie, ignore it, it's unusable.
      s = decodeURIComponent(s.replace(pluses, ' '));
      return config.json ? JSON.parse(s) : s;
    } catch (e) {}
  }
  function read(s, converter) {
    var value = config.raw ? s : parseCookieValue(s);
    return $.isFunction(converter) ? converter(value) : value;
  }
  var config = $.cookie = function (key, value, options) {
    // Write

    if (value !== undefined && !$.isFunction(value)) {
      options = $.extend({}, config.defaults, options);
      if (typeof options.expires === 'number') {
        var days = options.expires,
          t = options.expires = new Date();
        t.setTime(+t + days * 864e+5);
      }
      return document.cookie = [encode(key), '=', stringifyCookieValue(value), options.expires ? '; expires=' + options.expires.toUTCString() : '',
      // use expires attribute, max-age is not supported by IE
      options.path ? '; path=' + options.path : '', options.domain ? '; domain=' + options.domain : '', options.secure ? '; secure' : ''].join('');
    }

    // Read

    var result = key ? undefined : {};

    // To prevent the for loop in the first place assign an empty array
    // in case there are no cookies at all. Also prevents odd result when
    // calling $.cookie().
    var cookies = document.cookie ? document.cookie.split('; ') : [];
    for (var i = 0, l = cookies.length; i < l; i++) {
      var parts = cookies[i].split('=');
      var name = decode(parts.shift());
      var cookie = parts.join('=');
      if (key && key === name) {
        // If second argument (value) is a function it's a converter...
        result = read(cookie, value);
        break;
      }

      // Prevent storing a cookie that we couldn't decode.
      if (!key && (cookie = read(cookie)) !== undefined) {
        result[name] = cookie;
      }
    }
    return result;
  };
  config.defaults = {};
  $.removeCookie = function (key, options) {
    if ($.cookie(key) === undefined) {
      return false;
    }

    // Must not alter options, thus extending a fresh object...
    $.cookie(key, '', $.extend({}, options, {
      expires: -1
    }));
    return !$.cookie(key);
  };
});

/***/ }),

/***/ "?2dd4":
/*!*******************************************!*\
  !*** ./locale-data/complete.js (ignored) ***!
  \*******************************************/
/***/ (() => {

/* (ignored) */

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoid2ViX2Fzc2V0c19hd2FyZHdhbGxldG5ld2Rlc2lnbl9qc19saWJfZGlhbG9nX2pzLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBQUFBLGlDQUFPLENBQUMsK0VBQWEsRUFBRSx5RkFBVyxFQUFFLDZGQUFVLEVBQUUsbUZBQWlCLENBQUMsbUNBQUUsVUFBVUMsQ0FBQyxFQUFFQyxLQUFLLEVBQUU7RUFDcEYsSUFBSUMsTUFBTSxHQUFHLFNBQVRBLE1BQU1BLENBQWFDLE9BQU8sRUFBRTtJQUM1QixJQUFJQyxJQUFJLEdBQUdELE9BQU8sQ0FBQ0UsTUFBTSxDQUFDLFFBQVEsRUFBRSxNQUFNLENBQUM7SUFDM0MsSUFBR0QsSUFBSSxFQUNIRCxPQUFPLENBQUNFLE1BQU0sQ0FBQyxRQUFRLENBQUMsQ0FBQ0MsSUFBSSxDQUFDLGtCQUFrQixDQUFDLENBQUNDLE9BQU8sQ0FBQ0gsSUFBSSxDQUFDO0lBQ25FLElBQUlJLFFBQVEsR0FBR0wsT0FBTyxDQUFDTSxJQUFJLENBQUMsVUFBVSxDQUFDO0lBQ3ZDLElBQUlELFFBQVEsRUFBRTtNQUNWTCxPQUFPLENBQUNPLE1BQU0sQ0FBQyxDQUFDLENBQUNDLFFBQVEsQ0FBQ0gsUUFBUSxDQUFDO0lBQ3ZDO0lBQ0EsSUFBSUksVUFBVSxHQUFHVCxPQUFPLENBQUNNLElBQUksQ0FBQyxhQUFhLENBQUM7SUFDNUMsSUFBSUcsVUFBVSxFQUFFO01BQ1paLENBQUMsQ0FBQ0csT0FBTyxDQUFDLENBQUNFLE1BQU0sQ0FBQyxRQUFRLEVBQUUsVUFBVSxFQUFFO1FBQ3BDUSxFQUFFLEVBQUVEO01BQ1IsQ0FBQyxDQUFDO0lBQ047SUFDQSxJQUFJLENBQUNULE9BQU8sR0FBR0EsT0FBTztFQUMxQixDQUFDO0VBQ0RELE1BQU0sQ0FBQ1ksU0FBUyxHQUFHO0lBQ2ZDLE1BQU0sRUFBRSxTQUFBQSxPQUFBLEVBQVk7TUFDaEIsT0FBTyxJQUFJLENBQUNaLE9BQU8sQ0FBQ0UsTUFBTSxDQUFDLFFBQVEsQ0FBQztJQUN4QyxD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cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL2xpYi9kaWFsb2cuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL2xpYi91dGlscy5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL3dlYi9hc3NldHMvY29tbW9uL2pzL2pxdWVyeS1ib290LmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9jb21tb24vanMvanF1ZXJ5LWhhbmRsZXJzLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9jb21tb24vdmVuZG9ycy9qcXVlcnkuY29va2llL2pxdWVyeS5jb29raWUuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvaWdub3JlZHwvd3d3L2F3YXJkd2FsbGV0L25vZGVfbW9kdWxlcy9pbnRsfC4vbG9jYWxlLWRhdGEvY29tcGxldGUuanMiXSwic291cmNlc0NvbnRlbnQiOlsiZGVmaW5lKFsnanF1ZXJ5LWJvb3QnLCAnbGliL3V0aWxzJywgJ2pxdWVyeXVpJywgJ3RyYW5zbGF0b3ItYm9vdCddLCBmdW5jdGlvbiAoJCwgdXRpbHMpIHtcbiAgICB2YXIgRGlhbG9nID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIGljb24gPSBlbGVtZW50LmRpYWxvZygnb3B0aW9uJywgJ3R5cGUnKTtcbiAgICAgICAgaWYoaWNvbilcbiAgICAgICAgICAgIGVsZW1lbnQuZGlhbG9nKCd3aWRnZXQnKS5maW5kKCcudWktZGlhbG9nLXRpdGxlJykucHJlcGVuZChpY29uKTtcbiAgICAgICAgbGV0IGNzc0NsYXNzID0gZWxlbWVudC5kYXRhKCdhZGRjbGFzcycpO1xuICAgICAgICBpZiAoY3NzQ2xhc3MpIHtcbiAgICAgICAgICAgIGVsZW1lbnQucGFyZW50KCkuYWRkQ2xhc3MoY3NzQ2xhc3MpO1xuICAgICAgICB9XG4gICAgICAgIGxldCBwb3NpdGlvbkF0ID0gZWxlbWVudC5kYXRhKCdwb3NpdGlvbi1hdCcpO1xuICAgICAgICBpZiAocG9zaXRpb25BdCkge1xuICAgICAgICAgICAgJChlbGVtZW50KS5kaWFsb2coJ29wdGlvbicsICdwb3NpdGlvbicsIHtcbiAgICAgICAgICAgICAgICBhdDogcG9zaXRpb25BdFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICB9O1xuICAgIERpYWxvZy5wcm90b3R5cGUgPSB7XG4gICAgICAgIGlzT3BlbjogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5kaWFsb2coJ2lzT3BlbicpO1xuICAgICAgICB9LFxuICAgICAgICBtb3ZlVG9Ub3A6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHRoaXMuZWxlbWVudC5kaWFsb2coJ21vdmVUb1RvcCcpO1xuICAgICAgICB9LFxuICAgICAgICBvcGVuOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB0aGlzLmVsZW1lbnQuZGlhbG9nKCdvcGVuJyk7XG4gICAgICAgICAgICBpZiAodGhpcy5nZXRPcHRpb24oJ21vZGFsJykpIHtcbiAgICAgICAgICAgICAgICB1dGlscy5kb2N1bWVudFNjcm9sbCgpLmxvY2soKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgY2xvc2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLmdldE9wdGlvbignbW9kYWwnKSkge1xuICAgICAgICAgICAgICAgIHV0aWxzLmRvY3VtZW50U2Nyb2xsKCkudW5sb2NrKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aGlzLmVsZW1lbnQuZGlhbG9nKCdjbG9zZScpO1xuICAgICAgICB9LFxuICAgICAgICBkZXN0cm95OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICBpZiAodGhpcy5nZXRPcHRpb24gJiYgdGhpcy5nZXRPcHRpb24oJ21vZGFsJykpIHtcbiAgICAgICAgICAgICAgICB1dGlscy5kb2N1bWVudFNjcm9sbCgpLnVubG9jaygpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgdGhpcy5lbGVtZW50LmRpYWxvZygnZGVzdHJveScpLnJlbW92ZSgpO1xuICAgICAgICB9LFxuICAgICAgICBnZXRPcHRpb246IGZ1bmN0aW9uIChvcHRpb24pIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2Yob3B0aW9uKSA9PSAndW5kZWZpbmVkJylcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50LmRpYWxvZyhcIm9wdGlvblwiKTtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnQuZGlhbG9nKFwib3B0aW9uXCIsIG9wdGlvbik7XG4gICAgICAgIH0sXG4gICAgICAgIHNldE9wdGlvbjogZnVuY3Rpb24gKG5hbWUsIHZhbHVlKSB7XG4gICAgICAgICAgICBpZiAobmFtZSAhPSBudWxsICYmIHR5cGVvZiBuYW1lID09ICdvYmplY3QnKVxuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnQuZGlhbG9nKFwib3B0aW9uXCIsIGV4dGVuZE9wdGlvbnMobmFtZSkpO1xuXG4gICAgICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50LmRpYWxvZyhcIm9wdGlvblwiLCBuYW1lLCBleHRlbmRPcHRpb24obmFtZSwgdmFsdWUpKTtcbiAgICAgICAgfVxuICAgIH07XG5cbiAgICB2YXIgZXh0ZW5kT3B0aW9ucyA9IGZ1bmN0aW9uIChvcHRpb25zKSB7XG4gICAgICAgIG9wdGlvbnNbXCJvcGVuXCJdID0gb3B0aW9uc1tcIm9wZW5cIl0gfHwgbnVsbDtcbiAgICAgICAgb3B0aW9uc1tcImNsb3NlXCJdID0gb3B0aW9uc1tcImNsb3NlXCJdIHx8IG51bGw7XG4gICAgICAgICQuZWFjaChvcHRpb25zLCBmdW5jdGlvbiAoa2V5LCB2YWx1ZSkge1xuICAgICAgICAgICAgb3B0aW9uc1trZXldID0gZXh0ZW5kT3B0aW9uKGtleSwgdmFsdWUpO1xuICAgICAgICB9KTtcblxuICAgICAgICByZXR1cm4gb3B0aW9ucztcbiAgICB9O1xuICAgIHZhciBleHRlbmRPcHRpb24gPSBmdW5jdGlvbiAoa2V5LCBvcHRpb24pIHtcbiAgICAgICAgdmFyIG8gPSBvcHRpb247XG4gICAgICAgIHN3aXRjaCAoa2V5KSB7XG4gICAgICAgICAgICBjYXNlIFwib3BlblwiOlxuICAgICAgICAgICAgICAgIG9wdGlvbiA9IGZ1bmN0aW9uIChldmVudCwgdWkpIHtcbiAgICAgICAgICAgICAgICAgICAgJCgnYm9keScpLm9uZSgnY2xpY2snLCAnLnVpLXdpZGdldC1vdmVybGF5JywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgJCgnLnVpLWRpYWxvZzp2aXNpYmxlIC51aS1kaWFsb2ctY29udGVudCcpLmVhY2goZnVuY3Rpb24oKXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoJCh0aGlzKS5pcygnOmRhdGEodWlEaWFsb2cpJykgJiYgJCh0aGlzKS5kaWFsb2coXCJpc09wZW5cIikpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kaWFsb2coXCJjbG9zZVwiKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgJCh3aW5kb3cpLm9mZigncmVzaXplLmRpYWxvZycpLm9uKCdyZXNpemUuZGlhbG9nJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgJChldmVudC50YXJnZXQpLmRpYWxvZyhcIm9wdGlvblwiLCBcInBvc2l0aW9uXCIsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBteTogXCJjZW50ZXJcIixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBhdDogXCJjZW50ZXJcIixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBvZjogd2luZG93XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgIChvIHx8IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgfSkoZXZlbnQsIHVpKTtcbiAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgY2FzZSBcImNsb3NlXCI6XG4gICAgICAgICAgICAgICAgb3B0aW9uID0gZnVuY3Rpb24gKGV2ZW50LCB1aSkge1xuICAgICAgICAgICAgICAgICAgICB1dGlscy5kb2N1bWVudFNjcm9sbCgpLnVubG9jaygpO1xuICAgICAgICAgICAgICAgICAgICAkKHdpbmRvdykub2ZmKCdyZXNpemUuZGlhbG9nJyk7XG4gICAgICAgICAgICAgICAgICAgIChvIHx8IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgfSkoZXZlbnQsIHVpKTtcbiAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgY2FzZSBcInR5cGVcIjpcbiAgICAgICAgICAgICAgICBpZiAob3B0aW9uICYmICEob3B0aW9uIGluc3RhbmNlb2YgT2JqZWN0KSkge1xuICAgICAgICAgICAgICAgICAgICBvcHRpb24gPSBvcHRpb24gPyAnPGkgY2xhc3M9XCJpY29uLScgKyBvcHRpb24gKyAnLXNtYWxsXCI+PC9pPicgOiBudWxsO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gb3B0aW9uO1xuICAgIH07XG4gICAgcmV0dXJuIHtcbiAgICAgICAgZGlhbG9nczoge30sXG4gICAgICAgIGNyZWF0ZU5hbWVkOiBmdW5jdGlvbiAobmFtZSwgZWxlbSwgb3B0aW9ucykge1xuICAgICAgICAgICAgb3B0aW9ucyA9IGV4dGVuZE9wdGlvbnMob3B0aW9ucyk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5kaWFsb2dzW25hbWVdID0gbmV3IERpYWxvZyhcbiAgICAgICAgICAgICAgICBlbGVtLmRpYWxvZyhvcHRpb25zKVxuICAgICAgICAgICAgKTtcbiAgICAgICAgfSxcbiAgICAgICAgaGFzOiBmdW5jdGlvbiAobmFtZSkge1xuICAgICAgICAgICAgcmV0dXJuIHR5cGVvZih0aGlzLmRpYWxvZ3NbbmFtZV0pICE9ICd1bmRlZmluZWQnO1xuICAgICAgICB9LFxuICAgICAgICBnZXQ6IGZ1bmN0aW9uIChuYW1lKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5kaWFsb2dzW25hbWVdO1xuICAgICAgICB9LFxuICAgICAgICByZW1vdmU6IGZ1bmN0aW9uIChuYW1lKSB7XG4gICAgICAgICAgICBpZiAoIXRoaXMuaGFzKG5hbWUpKSByZXR1cm47XG4gICAgICAgICAgICB0aGlzLmdldChuYW1lKS5kZXN0cm95KCk7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5kaWFsb2dzW25hbWVdO1xuICAgICAgICB9LFxuICAgICAgICBmYXN0Q3JlYXRlOiBmdW5jdGlvbiAodGl0bGUsIGNvbnRlbnQsIG1vZGFsLCBhdXRvT3BlbiwgYnV0dG9ucywgd2lkdGgsIGhlaWdodCwgdHlwZSkge1xuICAgICAgICAgICAgdmFyIGVsZW1lbnQsIG9wdGlvbnM7XG4gICAgICAgICAgICBpZiAoY29udGVudCAhPSBudWxsICYmIHR5cGVvZiBjb250ZW50ID09ICdvYmplY3QnICYmIHR5cGVvZiB0aXRsZSAhPSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgICAgIGVsZW1lbnQgPSAkKCc8ZGl2PicgKyB0aXRsZSArICc8L2Rpdj4nKTtcbiAgICAgICAgICAgICAgICBvcHRpb25zID0gZXh0ZW5kT3B0aW9ucyhjb250ZW50KTtcbiAgICAgICAgICAgICAgICByZXR1cm4gbmV3IERpYWxvZyhlbGVtZW50LmRpYWxvZyhvcHRpb25zKSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbGVtZW50ID0gJCgnPGRpdj4nICsgKGNvbnRlbnQgfHwgJycpICsgJzwvZGl2PicpO1xuICAgICAgICAgICAgb3B0aW9ucyA9IGV4dGVuZE9wdGlvbnMoe1xuICAgICAgICAgICAgICAgIGF1dG9PcGVuOiBhdXRvT3BlbiB8fCB0cnVlLFxuICAgICAgICAgICAgICAgIG1vZGFsOiBtb2RhbCB8fCB0cnVlLFxuICAgICAgICAgICAgICAgIGJ1dHRvbnM6IGJ1dHRvbnMgfHwgW10sXG4gICAgICAgICAgICAgICAgd2lkdGg6IHdpZHRoIHx8IDMwMCxcbiAgICAgICAgICAgICAgICBoZWlnaHQ6IGhlaWdodCB8fCAnYXV0bycsXG4gICAgICAgICAgICAgICAgdGl0bGU6IHRpdGxlIHx8IG51bGwsXG4gICAgICAgICAgICAgICAgdHlwZTogdHlwZSB8fCBudWxsLFxuICAgICAgICAgICAgICAgIGNsb3NlOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICQodGhpcykuZGlhbG9nKCdkZXN0cm95JykucmVtb3ZlKCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB2YXIgZCA9IG5ldyBEaWFsb2coZWxlbWVudC5kaWFsb2cob3B0aW9ucykpO1xuICAgICAgICAgICAgZC5zZXRPcHRpb24oXCJjbG9zZVwiLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICBkLmRlc3Ryb3koKTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgcmV0dXJuIGQ7XG4gICAgICAgIH0sXG4gICAgICAgIGFsZXJ0OiBmdW5jdGlvbiAodGV4dCwgdGl0bGUpIHtcbiAgICAgICAgICAgIHZhciBlbGVtZW50ID0gJCgnPGRpdj4nICsgdGV4dCArICc8L2Rpdj4nKSxcbiAgICAgICAgICAgICAgICBvcHRpb25zID0ge1xuICAgICAgICAgICAgICAgICAgICBhdXRvT3BlbjogdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgbW9kYWw6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIGJ1dHRvbnM6IFt7XG4gICAgICAgICAgICAgICAgICAgICAgICAndGV4dCc6IFRyYW5zbGF0b3IudHJhbnMoJ2J1dHRvbi5vaycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgJ2NsaWNrJzogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQodGhpcykuZGlhbG9nKFwiY2xvc2VcIik7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgJ2NsYXNzJzogJ2J0bi1zaWx2ZXInXG4gICAgICAgICAgICAgICAgICAgIH1dLFxuICAgICAgICAgICAgICAgICAgICBjbG9zZU9uRXNjYXBlOiB0cnVlLFxuICAgICAgICAgICAgICAgICAgICBkcmFnZ2FibGU6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIHJlc2l6YWJsZTogZmFsc2UsXG4gICAgICAgICAgICAgICAgICAgIHdpZHRoOiAzMDAsXG4gICAgICAgICAgICAgICAgICAgIGhlaWdodDogJ2F1dG8nLFxuICAgICAgICAgICAgICAgICAgICB0aXRsZTogdGl0bGUgfHwgJycsXG4gICAgICAgICAgICAgICAgICAgIGNsb3NlOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmRpYWxvZygnZGVzdHJveScpLnJlbW92ZSgpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgcmV0dXJuIG5ldyBEaWFsb2coZWxlbWVudC5kaWFsb2cob3B0aW9ucykpO1xuICAgICAgICB9LFxuICAgICAgICBwcm9tcHQ6IGZ1bmN0aW9uICh0ZXh0LCB0aXRsZSwgbm9jYWxsYmFjaywgeWVzY2FsbGJhY2spIHtcbiAgICAgICAgICAgIHZhciBlbGVtZW50ID0gJCgnPGRpdj4nICsgdGV4dCArICc8L2Rpdj4nKSxcbiAgICAgICAgICAgICAgICBvcHRpb25zID0ge1xuICAgICAgICAgICAgICAgICAgICBhdXRvT3BlbjogdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgbW9kYWw6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIGJ1dHRvbnM6IFtcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAndGV4dCc6IFRyYW5zbGF0b3IudHJhbnMoJ2J1dHRvbi5ubycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGljayc6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kaWFsb2coXCJjbG9zZVwiKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbm9jYWxsYmFjaygpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2NsYXNzJzogJ2J0bi1zaWx2ZXInXG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICd0ZXh0JzogVHJhbnNsYXRvci50cmFucygnYnV0dG9uLnllcycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGljayc6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kaWFsb2coXCJjbG9zZVwiKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgeWVzY2FsbGJhY2soKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGFzcyc6ICdidG4tYmx1ZSdcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgXSxcbiAgICAgICAgICAgICAgICAgICAgY2xvc2VPbkVzY2FwZTogdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgZHJhZ2dhYmxlOiB0cnVlLFxuICAgICAgICAgICAgICAgICAgICByZXNpemFibGU6IGZhbHNlLFxuICAgICAgICAgICAgICAgICAgICB3aWR0aDogNjAwLFxuICAgICAgICAgICAgICAgICAgICBoZWlnaHQ6ICdhdXRvJyxcbiAgICAgICAgICAgICAgICAgICAgdGl0bGU6IHRpdGxlIHx8ICcnLFxuICAgICAgICAgICAgICAgICAgICBjbG9zZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kaWFsb2coJ2Rlc3Ryb3knKS5yZW1vdmUoKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIHJldHVybiBuZXcgRGlhbG9nKGVsZW1lbnQuZGlhbG9nKG9wdGlvbnMpKTtcbiAgICAgICAgfVxuICAgIH07XG59KTtcbiIsImRlZmluZShmdW5jdGlvbiAoKSB7XG4gICAgdmFyIHV0aWxzID0ge307XG5cbiAgICB1dGlscy5nZXRVcmxQYXJhbSA9IGZ1bmN0aW9uIChuYW1lLCB1cmwpIHtcbiAgICAgICAgaWYgKCF1cmwpIHtcbiAgICAgICAgICAgIHVybCA9IHdpbmRvdy5sb2NhdGlvbi5ocmVmO1xuICAgICAgICB9XG4gICAgICAgIGlmICh1cmwgJiYgISgvXlxcL3sxfVtcXHddLisvKS50ZXN0KHVybCkpIHtcbiAgICAgICAgICAgIHVybCA9ICcvcmVkaXJlY3Rfbm90X2FsbG93ZWQnO1xuICAgICAgICAgICAgcmV0dXJuIHVybDtcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciByZXN1bHRzID0gbmV3IFJlZ0V4cCgnW1xcXFw/Jl0nICsgbmFtZSArICc9KFteJiNdKiknKS5leGVjKHVybCk7XG4gICAgICAgIGlmICghcmVzdWx0cykge1xuICAgICAgICAgICAgcmV0dXJuIDA7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHJlc3VsdHNbMV0gfHwgMDtcbiAgICB9O1xuXG4gICAgdXRpbHMuc2V0Q29va2llID0gZnVuY3Rpb24gc2V0Q29va2llKG5hbWUsIHZhbHVlLCBleHBpcmVzLCBwYXRoLCBkb21haW4sIHNlY3VyZSkge1xuICAgICAgICBkb2N1bWVudC5jb29raWUgPSBuYW1lICsgXCI9XCIgKyBlc2NhcGUodmFsdWUpICtcbiAgICAgICAgICAgICgoZXhwaXJlcykgPyBcIjsgZXhwaXJlcz1cIiArIGV4cGlyZXMgOiBcIlwiKSArXG4gICAgICAgICAgICAoKHBhdGgpID8gXCI7IHBhdGg9XCIgKyBwYXRoIDogXCJcIikgK1xuICAgICAgICAgICAgKChkb21haW4pID8gXCI7IGRvbWFpbj1cIiArIGRvbWFpbiA6IFwiXCIpICtcbiAgICAgICAgICAgICgoc2VjdXJlKSA/IFwiOyBzZWN1cmVcIiA6IFwiXCIpO1xuICAgIH07XG5cbiAgICB1dGlscy5nZXRDb29raWUgPSBmdW5jdGlvbiBnZXRDb29raWUobmFtZSkge1xuICAgICAgICB2YXIgbWF0Y2hlcyA9IGRvY3VtZW50LmNvb2tpZS5tYXRjaChuZXcgUmVnRXhwKFxuICAgICAgICAgICAgXCIoPzpefDsgKVwiICsgbmFtZS5yZXBsYWNlKC8oW1xcLiQ/Knx7fVxcKFxcKVxcW1xcXVxcXFxcXC9cXCteXSkvZywgJ1xcXFwkMScpICsgXCI9KFteO10qKVwiXG4gICAgICAgICkpO1xuICAgICAgICByZXR1cm4gbWF0Y2hlcyA/IGRlY29kZVVSSUNvbXBvbmVudChtYXRjaGVzWzFdKSA6IHVuZGVmaW5lZDtcbiAgICB9O1xuXG4gICAgdXRpbHMuZXNjYXBlID0gZnVuY3Rpb24gKHRleHQpIHtcbiAgICAgICAgdmFyIGVudGl0aWVzID0gW1xuICAgICAgICAgICAgWydhcG9zJywgJ1xcJyddLFxuICAgICAgICAgICAgWydhbXAnLCAnJiddLFxuICAgICAgICAgICAgWydsdCcsICc8J10sXG4gICAgICAgICAgICBbJ2d0JywgJz4nXVxuICAgICAgICBdO1xuXG4gICAgICAgIGZvciAodmFyIGkgPSAwLCBtYXggPSBlbnRpdGllcy5sZW5ndGg7IGkgPCBtYXg7ICsraSlcbiAgICAgICAgICAgIHRleHQgPSB0ZXh0LnJlcGxhY2UobmV3IFJlZ0V4cCgnJicgKyBlbnRpdGllc1tpXVswXSArICc7JywgJ2cnKSwgZW50aXRpZXNbaV1bMV0pO1xuXG4gICAgICAgIHJldHVybiB0ZXh0O1xuICAgIH07XG5cbiAgICB1dGlscy5lbGVtZW50SW5WaWV3cG9ydCA9IGZ1bmN0aW9uIChlbCkge1xuICAgICAgICBpZiAodHlwZW9mIGpRdWVyeSA9PT0gXCJmdW5jdGlvblwiICYmIGVsIGluc3RhbmNlb2YgalF1ZXJ5KSB7XG4gICAgICAgICAgICBlbCA9IGVsWzBdO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIHJlY3QgPSBlbC5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTtcblxuICAgICAgICByZXR1cm4gKFxuICAgICAgICAgICAgcmVjdC50b3AgPj0gMCAmJlxuICAgICAgICAgICAgcmVjdC5sZWZ0ID49IDAgJiZcbiAgICAgICAgICAgIHJlY3QuYm90dG9tIDw9ICh3aW5kb3cuaW5uZXJIZWlnaHQgfHwgZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LmNsaWVudEhlaWdodCkgJiYgLypvciAkKHdpbmRvdykuaGVpZ2h0KCkgKi9cbiAgICAgICAgICAgIHJlY3QucmlnaHQgPD0gKHdpbmRvdy5pbm5lcldpZHRoIHx8IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5jbGllbnRXaWR0aCkgLypvciAkKHdpbmRvdykud2lkdGgoKSAqL1xuICAgICAgICApO1xuICAgIH07XG5cbiAgICB2YXIgdGltZW91dDtcblxuICAgIHV0aWxzLmNhbmNlbERlYm91bmNlID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAodGltZW91dCkge1xuICAgICAgICAgICAgY2xlYXJUaW1lb3V0KHRpbWVvdXQpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgdXRpbHMuZGVib3VuY2UgPSBmdW5jdGlvbiAoZnVuYywgd2FpdCkge1xuICAgICAgICB1dGlscy5jYW5jZWxEZWJvdW5jZSgpO1xuICAgICAgICB0aW1lb3V0ID0gc2V0VGltZW91dChmdW5jLCB3YWl0KVxuICAgIH07XG5cbiAgICB1dGlscy5nZXROdW1iZXJGb3JtYXR0ZXIgPSBmdW5jdGlvbigpe1xuICAgICAgICB2YXIgc2VsZWN0b3IgPSAkKCdhW2RhdGEtdGFyZ2V0PVwic2VsZWN0LWxhbmd1YWdlXCJdJyk7XG4gICAgICAgIHZhciBsb2NhbGUgPSAnZW4nO1xuICAgICAgICB2YXIgcmVnaW9uID0gc2VsZWN0b3IuYXR0cignZGF0YS1yZWdpb24nKTtcbiAgICAgICAgdmFyIGxhbmcgPSBzZWxlY3Rvci5hdHRyKCdkYXRhLWxhbmd1YWdlJyk7XG5cbiAgICAgICAgaWYoIXJlZ2lvbiAmJiBsYW5nICYmIGxhbmcubGVuZ3RoID09PSA1KVxuICAgICAgICAgICAgbG9jYWxlID0gbGFuZy5yZXBsYWNlKCdfJywgJy0nKTtcbiAgICAgICAgZWxzZSBpZihyZWdpb24gJiYgbGFuZyl7XG4gICAgICAgICAgICBsb2NhbGUgPSByZWdpb24gKyAnLScgKyBsYW5nLnN1YnN0cmluZygwLCAyKTtcbiAgICAgICAgfWVsc2UgaWYobGFuZyl7XG4gICAgICAgICAgICBsb2NhbGUgPSBsYW5nLnN1YnN0cmluZygwLCAyKTtcbiAgICAgICAgfWVsc2V7XG4gICAgICAgICAgICAvLyBmYWxsYmFja1xuICAgICAgICAgICAgbG9jYWxlID0gJ2VuJztcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciBzdXBwb3J0ZWRMb2NhbGVzID0gSW50bC5OdW1iZXJGb3JtYXQuc3VwcG9ydGVkTG9jYWxlc09mKGxvY2FsZSk7XG4gICAgICAgIHZhciB1c2VyTG9jYWxlID0gc3VwcG9ydGVkTG9jYWxlcy5sZW5ndGggPyBzdXBwb3J0ZWRMb2NhbGVzWzBdIDogbnVsbDtcblxuICAgICAgICByZXR1cm4gdXNlckxvY2FsZSA/XG4gICAgICAgICAgICBuZXcgSW50bC5OdW1iZXJGb3JtYXQodXNlckxvY2FsZSwge21heGltdW1GcmFjdGlvbkRpZ2l0czogMH0pIDpcbiAgICAgICAgICAgIG5ldyBJbnRsLk51bWJlckZvcm1hdCgpO1xuICAgIH07XG5cbiAgICB1dGlscy51Y2ZpcnN0ID0gZnVuY3Rpb24oc3RyKSB7XG4gICAgICAgIHJldHVybiBzdHIuY2hhckF0KDApLnRvVXBwZXJDYXNlKCkgKyBzdHIuc2xpY2UoMSk7XG4gICAgfTtcblxuICAgIHV0aWxzLmRpZ2l0RmlsdGVyID0gZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgaWYgKGlzTmFOKFN0cmluZy5mcm9tQ2hhckNvZGUoZXZlbnQua2V5Q29kZSkpICYmICcuJyAhPT0gU3RyaW5nLmZyb21DaGFyQ29kZShldmVudC5rZXlDb2RlKSkge1xuICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgfVxuICAgIH07XG5cbiAgICB1dGlscy5yZXZlcnNlRm9ybWF0TnVtYmVyID0gZnVuY3Rpb24odmFsdWUsIGxvY2FsZSkge1xuICAgICAgICB2YWx1ZSA9IHZhbHVlLnJlcGxhY2UoL1xcRCwvZywgJycpO1xuICAgICAgICBpZiAodW5kZWZpbmVkID09PSBsb2NhbGUpIHtcbiAgICAgICAgICAgIGxvY2FsZSA9ICQoJ2FbZGF0YS10YXJnZXQ9XCJzZWxlY3QtbGFuZ3VhZ2VcIl0nKS5kYXRhKCdsYW5ndWFnZScpIHx8ICQoJ2h0bWwnKS5hdHRyKCdsYW5nJykuc3Vic3RyKDAsIDIpO1xuICAgICAgICB9XG5cbiAgICAgICAgbGV0IGdyb3VwID0gbmV3IEludGwuTnVtYmVyRm9ybWF0KGxvY2FsZSkuZm9ybWF0KDExMTEpLnJlcGxhY2UoLzEvZywgJycpO1xuICAgICAgICBpZiAoJycgPT0gZ3JvdXApIHtcbiAgICAgICAgICAgIGdyb3VwID0gJywnO1xuICAgICAgICB9XG5cbiAgICAgICAgbGV0IGRlY2ltYWwgPSBuZXcgSW50bC5OdW1iZXJGb3JtYXQobG9jYWxlKS5mb3JtYXQoMS4xKS5yZXBsYWNlKC8xL2csICcnKTtcbiAgICAgICAgaWYgKCcnID09IGRlY2ltYWwpIHtcbiAgICAgICAgICAgIGRlY2ltYWwgPSAnLic7XG4gICAgICAgIH1cblxuICAgICAgICBsZXQgbnVtID0gdmFsdWUucmVwbGFjZShuZXcgUmVnRXhwKCdcXFxcJyArIGdyb3VwLCAnZycpLCAnJyk7XG4gICAgICAgIG51bSA9IG51bS5yZXBsYWNlKG5ldyBSZWdFeHAoJ1xcXFwnICsgZGVjaW1hbCwgJ2cnKSwgJy4nKTtcblxuICAgICAgICByZXR1cm4gIWlzTmFOKHBhcnNlRmxvYXQobnVtKSkgJiYgaXNGaW5pdGUobnVtKSA/IG51bSA6IG51bGw7XG4gICAgfTtcblxuICAgIHV0aWxzLmRvY3VtZW50U2Nyb2xsID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIGNvbnN0ICRib2R5ID0gJCgnYm9keScpO1xuXG4gICAgICAgIGZ1bmN0aW9uIGdldFNjcm9sbGJhcldpZHRoKCkge1xuICAgICAgICAgICAgbGV0ICRzY3IgPSAkKCcjc2Nyb2xsYmFySWRlbnRpZnknKTtcbiAgICAgICAgICAgIGlmICghJHNjci5sZW5ndGgpIHtcbiAgICAgICAgICAgICAgICAkc2NyID0gJCgnPGRpdiBpZD1cInNjcm9sbGJhcklkZW50aWZ5XCIgc3R5bGU9XCJwb3NpdGlvbjogYWJzb2x1dGU7dG9wOi0xMDAwcHg7bGVmdDotMTAwMHB4O3dpZHRoOiAxMDBweDtoZWlnaHQ6IDUwcHg7Ym94LXNpemluZzpib3JkZXItYm94O292ZXJmbG93LXk6IHNjcm9sbDtcIj48ZGl2IHN0eWxlPVwid2lkdGg6IDEwMCU7aGVpZ2h0OiAyMDBweDtcIj48L2Rpdj48L2Rpdj4nKTtcbiAgICAgICAgICAgICAgICAkYm9keS5hcHBlbmQoJHNjcik7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHJldHVybiAoJHNjclswXS5vZmZzZXRXaWR0aCAtICRzY3JbMF0uY2xpZW50V2lkdGgpO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgIGxvY2s6IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgIGNvbnN0IHJvb3QgPSBkb2N1bWVudC5jb21wYXRNb2RlID09PSAnQmFja0NvbXBhdCcgPyBkb2N1bWVudC5ib2R5IDogZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50O1xuICAgICAgICAgICAgICAgIGlmIChyb290LnNjcm9sbEhlaWdodCA+IHJvb3QuY2xpZW50SGVpZ2h0KSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0IHNjcldpZHRoID0gZ2V0U2Nyb2xsYmFyV2lkdGgoKTtcbiAgICAgICAgICAgICAgICAgICAgJGJvZHkuY3NzKHsnb3ZlcmZsb3cnIDogJ2hpZGRlbicsICdwYWRkaW5nLXJpZ2h0JyA6IHNjcldpZHRofSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIHVubG9jazogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgJGJvZHkuY3NzKHsnb3ZlcmZsb3cnIDogJ2F1dG8nLCAncGFkZGluZy1yaWdodCcgOiAnMCd9KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG5cbiAgICB1dGlscy5mb3JtYXRGaWxlU2l6ZSA9IGZ1bmN0aW9uKGJ5dGVzLCBkcCkge1xuICAgICAgICBkcCA9IGRwIHx8IDE7XG4gICAgICAgIGNvbnN0IHRocmVzaCA9IDEwMjQ7XG4gICAgICAgIGlmIChNYXRoLmFicyhieXRlcykgPCB0aHJlc2gpIHtcbiAgICAgICAgICAgIHJldHVybiBieXRlcyArICcgQic7XG4gICAgICAgIH1cblxuICAgICAgICBjb25zdCB1bml0cyA9IFsna0InLCAnTUInLCAnR0InLCAnVEInLCAnUEInLCAnRUInLCAnWkInLCAnWUInXTtcbiAgICAgICAgY29uc3QgciA9IDEwICoqIGRwO1xuICAgICAgICBsZXQgdSA9IC0xO1xuXG4gICAgICAgIGRvIHtcbiAgICAgICAgICAgIGJ5dGVzIC89IHRocmVzaDtcbiAgICAgICAgICAgICsrdTtcbiAgICAgICAgfSB3aGlsZSAoTWF0aC5yb3VuZChNYXRoLmFicyhieXRlcykgKiByKSAvIHIgPj0gdGhyZXNoICYmIHUgPCB1bml0cy5sZW5ndGggLSAxKTtcblxuXG4gICAgICAgIHJldHVybiBieXRlcy50b0ZpeGVkKGRwKSArICcgJyArIHVuaXRzW3VdO1xuICAgIH07XG5cbiAgICB1dGlscy5saW5raWZ5ID0gZnVuY3Rpb24odGV4dCkge1xuICAgICAgICBjb25zdCBwcm90b2NvbFBhdHRlcm4gPSAvKFxcYihodHRwcz98ZnRwKTpcXC9cXC9bLUEtWjAtOSsmQCNcXC8lPz1+X3whOiwuO10qWy1BLVowLTkrJkAjXFwvJT1+X3xdKS9naW07XG4gICAgICAgIHRleHQgPSB0ZXh0LnJlcGxhY2UocHJvdG9jb2xQYXR0ZXJuLCAnPGEgaHJlZj1cIiQxXCIgdGFyZ2V0PVwiX2JsYW5rXCI+JDE8L2E+Jyk7XG5cbiAgICAgICAgY29uc3Qgd3d3UGF0dGVybiA9IC8oXnxbXlxcL10pKHd3d1xcLltcXFNdKyhcXGJ8JCkpL2dpbTtcbiAgICAgICAgdGV4dCA9IHRleHQucmVwbGFjZSh3d3dQYXR0ZXJuLCAnJDE8YSBocmVmPVwiaHR0cDovLyQyXCIgdGFyZ2V0PVwiX2JsYW5rXCI+JDI8L2E+Jyk7XG5cbiAgICAgICAgY29uc3QgbWFpbFBhdHRlcm4gPSAvKChbYS16QS1aMC05XFwtXFxfXFwuXSkrQFthLXpBLVpcXF9dKz8oXFwuW2EtekEtWl17Miw2fSkrKS9naW07XG4gICAgICAgIHRleHQgPSB0ZXh0LnJlcGxhY2UobWFpbFBhdHRlcm4sICc8YSBocmVmPVwibWFpbHRvOiQxXCI+JDE8L2E+Jyk7XG5cbiAgICAgICAgcmV0dXJuIHRleHQ7XG4gICAgfTtcblxuICAgIHJldHVybiB1dGlscztcbn0pO1xuIiwiZGVmaW5lKCdqcXVlcnktYm9vdCcsIFsnanF1ZXJ5JywgJ2Nvb2tpZScsICdjb21tb24vanF1ZXJ5LWhhbmRsZXJzJywgJ2ludGwnXSwgZnVuY3Rpb24gKCkge1xuXHRyZXR1cm4gJDtcbn0pO1xuIiwiZGVmaW5lKFtdLCBmdW5jdGlvbigpIHtcblx0JChmdW5jdGlvbiAoKSB7XG5cdFx0d2luZG93LmNzcmZfdG9rZW4gPSBudWxsO1xuXG5cdFx0Ly8gc2hvdyBlcnJvciBkaWFsb2dzIG9uIGFqYXggZXJyb3JzXG5cdFx0JChkb2N1bWVudCkuYWpheEVycm9yKGZ1bmN0aW9uIChldmVudCwganFYSFIsIGFqYXhTZXR0aW5ncykge1xuXHRcdFx0Ly8gcmV0cnkgd2l0aCBmcmVzaCBDU1JGIHRva2VuLCBpZiB3ZSBnb3QgQ1NSRiBlcnJvciBmcm9tIHNlcnZlclxuXHRcdFx0Y29uc3QgY2FuQ3NyZlJldHJ5ID0gdHlwZW9mKGFqYXhTZXR0aW5ncy5jc3JmUmV0cnkpID09PSAndW5kZWZpbmVkJztcblx0XHRcdGNvbnN0IGNzcmZDb2RlID0ganFYSFIuZ2V0UmVzcG9uc2VIZWFkZXIoJ1gtWFNSRi1UT0tFTicpO1xuXHRcdFx0Y29uc3QgY3NyZkZhaWxlZCA9IGpxWEhSLmdldFJlc3BvbnNlSGVhZGVyKCdYLVhTUkYtRkFJTEVEJykgPT09ICd0cnVlJztcblxuXHRcdFx0aWYgKGNzcmZDb2RlKSB7XG5cdFx0XHRcdHdpbmRvdy5jc3JmX3Rva2VuID0gY3NyZkNvZGU7XG5cdFx0XHR9XG5cblx0XHRcdGlmIChqcVhIUi5zdGF0dXMgPT09IDQwMyAmJiBjc3JmRmFpbGVkICYmIGNhbkNzcmZSZXRyeSkge1xuXHRcdFx0XHRjb25zb2xlLmxvZygncmV0cnlpbmcgd2l0aCBmcmVzaCBDU1JGLCBzaG91bGQgcmVjZWl2ZSBvbiBpbiBoZWFkZXJzJyk7XG5cdFx0XHRcdC8vIG1hcmsgcmVxdWVzdCBhcyByZXRyeVxuXHRcdFx0XHRhamF4U2V0dGluZ3MuY3NyZlJldHJ5ID0gdHJ1ZTtcblx0XHRcdFx0JC5hamF4KGFqYXhTZXR0aW5ncyk7XG5cblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXHRcdFx0aWYgKGFqYXhTZXR0aW5ncy5kaXNhYmxlQXdFcnJvckhhbmRsZXIpIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXHRcdFx0cmVxdWlyZShbJ2xpYi9lcnJvckRpYWxvZyddLCBmdW5jdGlvbiAoc2hvd0Vycm9yRGlhbG9nKSB7XG5cdFx0XHRcdHNob3dFcnJvckRpYWxvZyh7XG5cdFx0XHRcdFx0c3RhdHVzOiBqcVhIUi5zdGF0dXMsXG5cdFx0XHRcdFx0ZGF0YToganFYSFIucmVzcG9uc2VKU09OID8ganFYSFIucmVzcG9uc2VKU09OIDoganFYSFIucmVzcG9uc2VUZXh0LFxuXHRcdFx0XHRcdGNvbmZpZzoge1xuXHRcdFx0XHRcdFx0bWV0aG9kOiBhamF4U2V0dGluZ3MudHlwZSxcblx0XHRcdFx0XHRcdHVybDogYWpheFNldHRpbmdzLnVybCxcblx0XHRcdFx0XHRcdGRhdGE6IGRlY29kZVVSSShhamF4U2V0dGluZ3MuZGF0YSlcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0sICh0eXBlb2YoYWpheFNldHRpbmdzLmRpc2FibGVFcnJvckRpYWxvZykgIT0gJ3VuZGVmaW5lZCcgJiYgYWpheFNldHRpbmdzLmRpc2FibGVFcnJvckRpYWxvZykpO1xuXHRcdFx0fSk7XG5cdFx0fSk7XG5cblx0XHQvLyBhZGQgQ1NSRiBoZWFkZXIgdG8gYWpheCBQT1NUIHJlcXVlc3RzXG5cdFx0JChkb2N1bWVudCkuYWpheFNlbmQoZnVuY3Rpb24gKGVsbSwgeGhyLCBzKSB7XG5cdFx0XHRpZiAod2luZG93LmNzcmZfdG9rZW4gPT09IG51bGwpIHtcblx0XHRcdFx0d2luZG93LmNzcmZfdG9rZW4gPSBkb2N1bWVudC5oZWFkLnF1ZXJ5U2VsZWN0b3IoJ21ldGFbbmFtZT1cImNzcmYtdG9rZW5cIl0nKS5jb250ZW50O1xuXHRcdFx0fVxuXHRcdFx0eGhyLnNldFJlcXVlc3RIZWFkZXIoJ1gtWFNSRi1UT0tFTicsIHdpbmRvdy5jc3JmX3Rva2VuKTtcblx0XHR9KTtcblxuXG5cdFx0JChkb2N1bWVudCkuYWpheFN1Y2Nlc3MoZnVuY3Rpb24oZXZlbnQsIGpxWEhSLCBzZXR0aW5ncyl7XG5cdFx0XHRjb25zdCBtYWlsRXJyb3JzID0gJC50cmltKGpxWEhSLmdldFJlc3BvbnNlSGVhZGVyKCd4LWF3LW1haWwtZmFpbGVkJykpO1xuXHRcdFx0Y29uc3QgY3NyZkNvZGUgPSBqcVhIUi5nZXRSZXNwb25zZUhlYWRlcignWC1YU1JGLVRPS0VOJyk7XG5cblx0XHRcdGlmIChtYWlsRXJyb3JzICE9ICcnICYmICFzZXR0aW5ncy5zdXBwcmVzc0Vycm9ycyl7XG5cdFx0XHRcdHJlcXVpcmUoWydsaWIvbWFpbEVycm9yRGlhbG9nJ10sIGZ1bmN0aW9uIChzaG93RXJyb3JEaWFsb2cpIHtcblx0XHRcdFx0XHRzaG93RXJyb3JEaWFsb2cobWFpbEVycm9ycyk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoY3NyZkNvZGUpIHtcblx0XHRcdFx0d2luZG93LmNzcmZfdG9rZW4gPSBjc3JmQ29kZTtcblx0XHRcdH1cblx0XHR9KTtcblxuXHRcdHdpbmRvdy5vbmVycm9yQ291bnRlciA9IDA7XG5cdFx0d2luZG93Lm9uZXJyb3JIYW5kbGVyID0gZnVuY3Rpb24oZSkge1xuXHRcdFx0d2luZG93Lm9uZXJyb3JDb3VudGVyKys7XG5cdFx0XHRpZiAod2luZG93Lm9uZXJyb3JDb3VudGVyIDwgMTApIHtcblx0XHRcdFx0JC5wb3N0KCcvanNfZXJyb3InLCB7XG5cdFx0XHRcdFx0ZXJyb3I6IGUubWVzc2FnZSxcblx0XHRcdFx0XHRmaWxlOiBlLmZpbGVOYW1lLFxuXHRcdFx0XHRcdGxpbmU6IGUubGluZU51bWJlcixcblx0XHRcdFx0XHRjb2x1bW46IGUuY29sdW1uTnVtYmVyLFxuXHRcdFx0XHRcdHN0YWNrOiBlLnN0YWNrXG5cdFx0XHRcdH0sIHtcblx0XHRcdFx0XHRkaXNhYmxlRXJyb3JEaWFsb2c6IHRydWVcblx0XHRcdFx0fSk7XG5cblx0XHRcdFx0aWYgKHR5cGVvZiAoZS5maWxlTmFtZSkgIT0gJ3VuZGVmaW5lZCcpIHtcblx0XHRcdFx0XHR2YXIgYSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2EnKTtcblx0XHRcdFx0XHRhLmhyZWYgPSBlLmZpbGVOYW1lO1xuXHRcdFx0XHRcdGlmIChhLmhyZWYuaW5kZXhPZignc2VydmljZS13b3JrZXIuanMnKSA+PSAwIHx8IChhLmhvc3RuYW1lICYmIChhLmhvc3RuYW1lID09IHdpbmRvdy5sb2NhdGlvbi5ob3N0bmFtZSkpKSB7IC8vIGV4Y2x1ZGUgZXh0ZXJuYWwgc2NyaXB0c1xuXHRcdFx0XHRcdFx0cmVxdWlyZShbJ2xpYi9lcnJvckRpYWxvZyddLCBmdW5jdGlvbiAoc2hvd0Vycm9yRGlhbG9nKSB7XG5cdFx0XHRcdFx0XHRcdHNob3dFcnJvckRpYWxvZyh7XG5cdFx0XHRcdFx0XHRcdFx0c3RhdHVzOiAwLFxuXHRcdFx0XHRcdFx0XHRcdGRhdGE6IGUubWVzc2FnZSArICc8YnJcXD48YnJcXD4nICsgZS5zdGFjayAvLyBvbmx5IGluIGRlYnVnIG1vZGVcblx0XHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdH1cblx0XHR9O1xuXHRcdHdpbmRvdy5vbmVycm9yID0gZnVuY3Rpb24obWVzc2FnZSwgZmlsZSwgbGluZSwgY29sdW1uLCBlKSB7XG5cdFx0XHRpZiAoZSAmJiB0eXBlb2YoZSkgPT0gJ29iamVjdCcpIHtcblx0XHRcdFx0d2luZG93Lm9uZXJyb3JIYW5kbGVyKGUpXG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHR3aW5kb3cub25lcnJvckhhbmRsZXIoe1xuXHRcdFx0XHRcdG1lc3NhZ2U6IG1lc3NhZ2UsXG5cdFx0XHRcdFx0ZmlsZU5hbWU6IGZpbGUsXG5cdFx0XHRcdFx0bGluZU51bWJlcjogbGluZSxcblx0XHRcdFx0XHRjb2x1bW5OdW1iZXI6IGNvbHVtbixcblx0XHRcdFx0XHRzdGFjazogbnVsbFxuXHRcdFx0XHR9KVxuXHRcdFx0fVxuXHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdH1cblxuXHR9KTtcbn0pO1xuXG4iLCIvKiFcbiAqIGpRdWVyeSBDb29raWUgUGx1Z2luIHYxLjQuMVxuICogaHR0cHM6Ly9naXRodWIuY29tL2NhcmhhcnRsL2pxdWVyeS1jb29raWVcbiAqXG4gKiBDb3B5cmlnaHQgMjAxMyBLbGF1cyBIYXJ0bFxuICogUmVsZWFzZWQgdW5kZXIgdGhlIE1JVCBsaWNlbnNlXG4gKi9cbihmdW5jdGlvbiAoZmFjdG9yeSkge1xuXHRpZiAodHlwZW9mIGRlZmluZSA9PT0gJ2Z1bmN0aW9uJyAmJiBkZWZpbmUuYW1kKSB7XG5cdFx0Ly8gQU1EXG5cdFx0ZGVmaW5lKFsnanF1ZXJ5J10sIGZhY3RvcnkpO1xuXHR9IGVsc2UgaWYgKHR5cGVvZiBleHBvcnRzID09PSAnb2JqZWN0Jykge1xuXHRcdC8vIENvbW1vbkpTXG5cdFx0ZmFjdG9yeShyZXF1aXJlKCdqcXVlcnknKSk7XG5cdH0gZWxzZSB7XG5cdFx0Ly8gQnJvd3NlciBnbG9iYWxzXG5cdFx0ZmFjdG9yeShqUXVlcnkpO1xuXHR9XG59KGZ1bmN0aW9uICgkKSB7XG5cblx0dmFyIHBsdXNlcyA9IC9cXCsvZztcblxuXHRmdW5jdGlvbiBlbmNvZGUocykge1xuXHRcdHJldHVybiBjb25maWcucmF3ID8gcyA6IGVuY29kZVVSSUNvbXBvbmVudChzKTtcblx0fVxuXG5cdGZ1bmN0aW9uIGRlY29kZShzKSB7XG5cdFx0cmV0dXJuIGNvbmZpZy5yYXcgPyBzIDogZGVjb2RlVVJJQ29tcG9uZW50KHMpO1xuXHR9XG5cblx0ZnVuY3Rpb24gc3RyaW5naWZ5Q29va2llVmFsdWUodmFsdWUpIHtcblx0XHRyZXR1cm4gZW5jb2RlKGNvbmZpZy5qc29uID8gSlNPTi5zdHJpbmdpZnkodmFsdWUpIDogU3RyaW5nKHZhbHVlKSk7XG5cdH1cblxuXHRmdW5jdGlvbiBwYXJzZUNvb2tpZVZhbHVlKHMpIHtcblx0XHRpZiAocy5pbmRleE9mKCdcIicpID09PSAwKSB7XG5cdFx0XHQvLyBUaGlzIGlzIGEgcXVvdGVkIGNvb2tpZSBhcyBhY2NvcmRpbmcgdG8gUkZDMjA2OCwgdW5lc2NhcGUuLi5cblx0XHRcdHMgPSBzLnNsaWNlKDEsIC0xKS5yZXBsYWNlKC9cXFxcXCIvZywgJ1wiJykucmVwbGFjZSgvXFxcXFxcXFwvZywgJ1xcXFwnKTtcblx0XHR9XG5cblx0XHR0cnkge1xuXHRcdFx0Ly8gUmVwbGFjZSBzZXJ2ZXItc2lkZSB3cml0dGVuIHBsdXNlcyB3aXRoIHNwYWNlcy5cblx0XHRcdC8vIElmIHdlIGNhbid0IGRlY29kZSB0aGUgY29va2llLCBpZ25vcmUgaXQsIGl0J3MgdW51c2FibGUuXG5cdFx0XHQvLyBJZiB3ZSBjYW4ndCBwYXJzZSB0aGUgY29va2llLCBpZ25vcmUgaXQsIGl0J3MgdW51c2FibGUuXG5cdFx0XHRzID0gZGVjb2RlVVJJQ29tcG9uZW50KHMucmVwbGFjZShwbHVzZXMsICcgJykpO1xuXHRcdFx0cmV0dXJuIGNvbmZpZy5qc29uID8gSlNPTi5wYXJzZShzKSA6IHM7XG5cdFx0fSBjYXRjaChlKSB7fVxuXHR9XG5cblx0ZnVuY3Rpb24gcmVhZChzLCBjb252ZXJ0ZXIpIHtcblx0XHR2YXIgdmFsdWUgPSBjb25maWcucmF3ID8gcyA6IHBhcnNlQ29va2llVmFsdWUocyk7XG5cdFx0cmV0dXJuICQuaXNGdW5jdGlvbihjb252ZXJ0ZXIpID8gY29udmVydGVyKHZhbHVlKSA6IHZhbHVlO1xuXHR9XG5cblx0dmFyIGNvbmZpZyA9ICQuY29va2llID0gZnVuY3Rpb24gKGtleSwgdmFsdWUsIG9wdGlvbnMpIHtcblxuXHRcdC8vIFdyaXRlXG5cblx0XHRpZiAodmFsdWUgIT09IHVuZGVmaW5lZCAmJiAhJC5pc0Z1bmN0aW9uKHZhbHVlKSkge1xuXHRcdFx0b3B0aW9ucyA9ICQuZXh0ZW5kKHt9LCBjb25maWcuZGVmYXVsdHMsIG9wdGlvbnMpO1xuXG5cdFx0XHRpZiAodHlwZW9mIG9wdGlvbnMuZXhwaXJlcyA9PT0gJ251bWJlcicpIHtcblx0XHRcdFx0dmFyIGRheXMgPSBvcHRpb25zLmV4cGlyZXMsIHQgPSBvcHRpb25zLmV4cGlyZXMgPSBuZXcgRGF0ZSgpO1xuXHRcdFx0XHR0LnNldFRpbWUoK3QgKyBkYXlzICogODY0ZSs1KTtcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIChkb2N1bWVudC5jb29raWUgPSBbXG5cdFx0XHRcdGVuY29kZShrZXkpLCAnPScsIHN0cmluZ2lmeUNvb2tpZVZhbHVlKHZhbHVlKSxcblx0XHRcdFx0b3B0aW9ucy5leHBpcmVzID8gJzsgZXhwaXJlcz0nICsgb3B0aW9ucy5leHBpcmVzLnRvVVRDU3RyaW5nKCkgOiAnJywgLy8gdXNlIGV4cGlyZXMgYXR0cmlidXRlLCBtYXgtYWdlIGlzIG5vdCBzdXBwb3J0ZWQgYnkgSUVcblx0XHRcdFx0b3B0aW9ucy5wYXRoICAgID8gJzsgcGF0aD0nICsgb3B0aW9ucy5wYXRoIDogJycsXG5cdFx0XHRcdG9wdGlvbnMuZG9tYWluICA/ICc7IGRvbWFpbj0nICsgb3B0aW9ucy5kb21haW4gOiAnJyxcblx0XHRcdFx0b3B0aW9ucy5zZWN1cmUgID8gJzsgc2VjdXJlJyA6ICcnXG5cdFx0XHRdLmpvaW4oJycpKTtcblx0XHR9XG5cblx0XHQvLyBSZWFkXG5cblx0XHR2YXIgcmVzdWx0ID0ga2V5ID8gdW5kZWZpbmVkIDoge307XG5cblx0XHQvLyBUbyBwcmV2ZW50IHRoZSBmb3IgbG9vcCBpbiB0aGUgZmlyc3QgcGxhY2UgYXNzaWduIGFuIGVtcHR5IGFycmF5XG5cdFx0Ly8gaW4gY2FzZSB0aGVyZSBhcmUgbm8gY29va2llcyBhdCBhbGwuIEFsc28gcHJldmVudHMgb2RkIHJlc3VsdCB3aGVuXG5cdFx0Ly8gY2FsbGluZyAkLmNvb2tpZSgpLlxuXHRcdHZhciBjb29raWVzID0gZG9jdW1lbnQuY29va2llID8gZG9jdW1lbnQuY29va2llLnNwbGl0KCc7ICcpIDogW107XG5cblx0XHRmb3IgKHZhciBpID0gMCwgbCA9IGNvb2tpZXMubGVuZ3RoOyBpIDwgbDsgaSsrKSB7XG5cdFx0XHR2YXIgcGFydHMgPSBjb29raWVzW2ldLnNwbGl0KCc9Jyk7XG5cdFx0XHR2YXIgbmFtZSA9IGRlY29kZShwYXJ0cy5zaGlmdCgpKTtcblx0XHRcdHZhciBjb29raWUgPSBwYXJ0cy5qb2luKCc9Jyk7XG5cblx0XHRcdGlmIChrZXkgJiYga2V5ID09PSBuYW1lKSB7XG5cdFx0XHRcdC8vIElmIHNlY29uZCBhcmd1bWVudCAodmFsdWUpIGlzIGEgZnVuY3Rpb24gaXQncyBhIGNvbnZlcnRlci4uLlxuXHRcdFx0XHRyZXN1bHQgPSByZWFkKGNvb2tpZSwgdmFsdWUpO1xuXHRcdFx0XHRicmVhaztcblx0XHRcdH1cblxuXHRcdFx0Ly8gUHJldmVudCBzdG9yaW5nIGEgY29va2llIHRoYXQgd2UgY291bGRuJ3QgZGVjb2RlLlxuXHRcdFx0aWYgKCFrZXkgJiYgKGNvb2tpZSA9IHJlYWQoY29va2llKSkgIT09IHVuZGVmaW5lZCkge1xuXHRcdFx0XHRyZXN1bHRbbmFtZV0gPSBjb29raWU7XG5cdFx0XHR9XG5cdFx0fVxuXG5cdFx0cmV0dXJuIHJlc3VsdDtcblx0fTtcblxuXHRjb25maWcuZGVmYXVsdHMgPSB7fTtcblxuXHQkLnJlbW92ZUNvb2tpZSA9IGZ1bmN0aW9uIChrZXksIG9wdGlvbnMpIHtcblx0XHRpZiAoJC5jb29raWUoa2V5KSA9PT0gdW5kZWZpbmVkKSB7XG5cdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0fVxuXG5cdFx0Ly8gTXVzdCBub3QgYWx0ZXIgb3B0aW9ucywgdGh1cyBleHRlbmRpbmcgYSBmcmVzaCBvYmplY3QuLi5cblx0XHQkLmNvb2tpZShrZXksICcnLCAkLmV4dGVuZCh7fSwgb3B0aW9ucywgeyBleHBpcmVzOiAtMSB9KSk7XG5cdFx0cmV0dXJuICEkLmNvb2tpZShrZXkpO1xuXHR9O1xuXG59KSk7XG4iLCIvKiAoaWdub3JlZCkgKi8iXSwibmFtZXMiOlsiZGVmaW5lIiwiJCIsInV0aWxzIiwiRGlhbG9nIiwiZWxlbWVudCIsImljb24iLCJkaWFsb2ciLCJmaW5kIiwicHJlcGVuZCIsImNzc0NsYXNzIiwiZGF0YSIsInBhcmVudCIsImFkZENsYXNzIiwicG9zaXRpb25BdCIsImF0IiwicHJvdG90eXBlIiwiaXNPcGVuIiwibW92ZVRvVG9wIiwib3BlbiIsImdldE9wdGlvbiIsImRvY3VtZW50U2Nyb2xsIiwibG9jayIsImNsb3NlIiwidW5sb2NrIiwiZGVzdHJveSIsInJlbW92ZSIsIm9wdGlvbiIsInNldE9wdGlvbiIsIm5hbWUiLCJ2YWx1ZSIsIl90eXBlb2YiLCJleHRlbmRPcHRpb25zIiwiZXh0ZW5kT3B0aW9uIiwib3B0aW9ucyIsImVhY2giLCJrZXkiLCJvIiwiZXZlbnQiLCJ1aSIsIm9uZSIsImlzIiwid2luZG93Iiwib2ZmIiwib24iLCJ0YXJnZXQiLCJteSIsIm9mIiwiT2JqZWN0IiwiZGlhbG9ncyIsImNyZWF0ZU5hbWVkIiwiZWxlbSIsImhhcyIsImdldCIsImZhc3RDcmVhdGUiLCJ0aXRsZSIsImNvbnRlbnQiLCJtb2RhbCIsImF1dG9PcGVuIiwiYnV0dG9ucyIsIndpZHRoIiwiaGVpZ2h0IiwidHlwZSIsImQiLCJhbGVydCIsInRleHQiLCJUcmFuc2xhdG9yIiwidHJhbnMiLCJjbGljayIsImNsb3NlT25Fc2NhcGUiLCJkcmFnZ2FibGUiLCJyZXNpemFibGUiLCJwcm9tcHQiLCJub2NhbGxiYWNrIiwieWVzY2FsbGJhY2siLCJnZXRVcmxQYXJhbSIsInVybCIsImxvY2F0aW9uIiwiaHJlZiIsInRlc3QiLCJyZXN1bHRzIiwiUmVnRXhwIiwiZXhlYyIsInNldENvb2tpZSIsImV4cGlyZXMiLCJwYXRoIiwiZG9tYWluIiwic2VjdXJlIiwiZG9jdW1lbnQiLCJjb29raWUiLCJlc2NhcGUiLCJnZXRDb29raWUiLCJtYXRjaGVzIiwibWF0Y2giLCJyZXBsYWNlIiwiZGVjb2RlVVJJQ29tcG9uZW50IiwidW5kZWZpbmVkIiwiZW50aXRpZXMiLCJpIiwibWF4IiwibGVuZ3RoIiwiZWxlbWVudEluVmlld3BvcnQiLCJlbCIsImpRdWVyeSIsInJlY3QiLCJnZXRCb3VuZGluZ0NsaWVudFJlY3QiLCJ0b3AiLCJsZWZ0IiwiYm90dG9tIiwiaW5uZXJIZWlnaHQiLCJkb2N1bWVudEVsZW1lbnQiLCJjbGllbnRIZWlnaHQiLCJyaWdodCIsImlubmVyV2lkdGgiLCJjbGllbnRXaWR0aCIsInRpbWVvdXQiLCJjYW5jZWxEZWJvdW5jZSIsImNsZWFyVGltZW91dCIsImRlYm91bmNlIiwiZnVuYyIsIndhaXQiLCJzZXRUaW1lb3V0IiwiZ2V0TnVtYmVyRm9ybWF0dGVyIiwic2VsZWN0b3IiLCJsb2NhbGUiLCJyZWdpb24iLCJhdHRyIiwibGFuZyIsInN1YnN0cmluZyIsInN1cHBvcnRlZExvY2FsZXMiLCJJbnRsIiwiTnVtYmVyRm9ybWF0Iiwic3VwcG9ydGVkTG9jYWxlc09mIiwidXNlckxvY2FsZSIsIm1heGltdW1GcmFjdGlvbkRpZ2l0cyIsInVjZmlyc3QiLCJzdHIiLCJjaGFyQXQiLCJ0b1VwcGVyQ2FzZSIsInNsaWNlIiwiZGlnaXRGaWx0ZXIiLCJpc05hTiIsIlN0cmluZyIsImZyb21DaGFyQ29kZSIsImtleUNvZGUiLCJwcmV2ZW50RGVmYXVsdCIsInJldmVyc2VGb3JtYXROdW1iZXIiLCJzdWJzdHIiLCJncm91cCIsImZvcm1hdCIsImRlY2ltYWwiLCJudW0iLCJwYXJzZUZsb2F0IiwiaXNGaW5pdGUiLCIkYm9keSIsImdldFNjcm9sbGJhcldpZHRoIiwiJHNjciIsImFwcGVuZCIsIm9mZnNldFdpZHRoIiwicm9vdCIsImNvbXBhdE1vZGUiLCJib2R5Iiwic2Nyb2xsSGVpZ2h0Iiwic2NyV2lkdGgiLCJjc3MiLCJmb3JtYXRGaWxlU2l6ZSIsImJ5dGVzIiwiZHAiLCJ0aHJlc2giLCJNYXRoIiwiYWJzIiwidW5pdHMiLCJyIiwicG93IiwidSIsInJvdW5kIiwidG9GaXhlZCIsImxpbmtpZnkiLCJwcm90b2NvbFBhdHRlcm4iLCJ3d3dQYXR0ZXJuIiwibWFpbFBhdHRlcm4iLCJjc3JmX3Rva2VuIiwiYWpheEVycm9yIiwianFYSFIiLCJhamF4U2V0dGluZ3MiLCJjYW5Dc3JmUmV0cnkiLCJjc3JmUmV0cnkiLCJjc3JmQ29kZSIsImdldFJlc3BvbnNlSGVhZGVyIiwiY3NyZkZhaWxlZCIsInN0YXR1cyIsImNvbnNvbGUiLCJsb2ciLCJhamF4IiwiZGlzYWJsZUF3RXJyb3JIYW5kbGVyIiwicmVxdWlyZSIsInNob3dFcnJvckRpYWxvZyIsInJlc3BvbnNlSlNPTiIsInJlc3BvbnNlVGV4dCIsImNvbmZpZyIsIm1ldGhvZCIsImRlY29kZVVSSSIsImRpc2FibGVFcnJvckRpYWxvZyIsImFqYXhTZW5kIiwiZWxtIiwieGhyIiwicyIsImhlYWQiLCJxdWVyeVNlbGVjdG9yIiwic2V0UmVxdWVzdEhlYWRlciIsImFqYXhTdWNjZXNzIiwic2V0dGluZ3MiLCJtYWlsRXJyb3JzIiwidHJpbSIsInN1cHByZXNzRXJyb3JzIiwib25lcnJvckNvdW50ZXIiLCJvbmVycm9ySGFuZGxlciIsImUiLCJwb3N0IiwiZXJyb3IiLCJtZXNzYWdlIiwiZmlsZSIsImZpbGVOYW1lIiwibGluZSIsImxpbmVOdW1iZXIiLCJjb2x1bW4iLCJjb2x1bW5OdW1iZXIiLCJzdGFjayIsImEiLCJjcmVhdGVFbGVtZW50IiwiaW5kZXhPZiIsImhvc3RuYW1lIiwib25lcnJvciIsImZhY3RvcnkiLCJhbWQiLCJleHBvcnRzIiwicGx1c2VzIiwiZW5jb2RlIiwicmF3IiwiZW5jb2RlVVJJQ29tcG9uZW50IiwiZGVjb2RlIiwic3RyaW5naWZ5Q29va2llVmFsdWUiLCJqc29uIiwiSlNPTiIsInN0cmluZ2lmeSIsInBhcnNlQ29va2llVmFsdWUiLCJwYXJzZSIsInJlYWQiLCJjb252ZXJ0ZXIiLCJpc0Z1bmN0aW9uIiwiZXh0ZW5kIiwiZGVmYXVsdHMiLCJkYXlzIiwidCIsIkRhdGUiLCJzZXRUaW1lIiwidG9VVENTdHJpbmciLCJqb2luIiwicmVzdWx0IiwiY29va2llcyIsInNwbGl0IiwibCIsInBhcnRzIiwic2hpZnQiLCJyZW1vdmVDb29raWUiXSwic291cmNlUm9vdCI6IiJ9