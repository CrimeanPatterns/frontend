(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["assets_bem_ts_service_translator_ts"],{

/***/ "./web/assets/common/js/translator.js":
/*!********************************************!*\
  !*** ./web/assets/common/js/translator.js ***!
  \********************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
__webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
__webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
__webpack_require__(/*! core-js/modules/es.string.split.js */ "./node_modules/core-js/modules/es.string.split.js");
__webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/*!
 * William DURAND <william.durand1@gmail.com>
 * MIT Licensed
 */
var Translator = function (document) {
  "use strict";

  var base64encode = function base64_encode(data) {
    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1,
      o2,
      o3,
      h1,
      h2,
      h3,
      h4,
      bits,
      i = 0,
      enc = '';
    do {
      o1 = data.charCodeAt(i++);
      o2 = data.charCodeAt(i++);
      o3 = data.charCodeAt(i++);
      bits = o1 << 16 | o2 << 8 | o3;
      h1 = bits >> 18 & 0x3f;
      h2 = bits >> 12 & 0x3f;
      h3 = bits >> 6 & 0x3f;
      h4 = bits & 0x3f;
      enc += b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
    } while (i < data.length);
    switch (data.length % 3) {
      case 1:
        enc = enc.slice(0, -2) + '==';
        break;
      case 2:
        enc = enc.slice(0, -1) + '=';
        break;
    }
    return enc;
  };
  var isTranshelper = document.cookie.match(/transhelper/);
  var _messages = {},
    _sPluralRegex = /^\w+\: +(.+)$/,
    _cPluralRegex = /^\s*((\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]]))\s?(.+?)$/,
    _iPluralRegex = /^\s*(\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]])/;

  /**
   * Replace placeholders in given message.
   *
   * **WARNING:** used placeholders are removed.
   *
   * @param {String} message      The translated message
   * @param {Object} placeholders The placeholders to replace
   * @return {String}             A human readable message
   * @api private
   */
  function replace_placeholders(message, placeholders) {
    var _i,
      _prefix = Translator.placeHolderPrefix,
      _suffix = Translator.placeHolderSuffix;
    for (_i in placeholders) {
      var _r = new RegExp(_prefix + _i + _suffix, 'g');
      if (_r.test(message)) {
        message = message.replace(_r, placeholders[_i]);
        delete placeholders[_i];
      }
    }
    return message;
  }

  /**
   * Get the message based on its id, its domain, and its locale. If domain or
   * locale are not specified, it will try to find the message using fallbacks.
   *
   * @param {String} id               The message id
   * @param {String} domain           The domain for the message or null to guess it
   * @param {String} locale           The locale or null to use the default
   * @param {String} currentLocale    The current locale or null to use the default
   * @param {String} localeFallback   The fallback (default) locale
   * @param {String} defaultDomain    Default domain
   * @return {String}                 The right message if found, `undefined` otherwise
   * @api private
   */
  function get_message(id, domain, locale, currentLocale, localeFallback, defaultDomain) {
    var _locale = locale || currentLocale || localeFallback,
      _domain = domain || defaultDomain;
    if (undefined === _messages[_locale]) {
      if (undefined === _messages[localeFallback]) {
        return id;
      }
      _locale = localeFallback;
    }
    if (undefined !== _messages[_locale][_domain] && undefined !== _messages[_locale][_domain][id]) {
      return _messages[_locale][_domain][id];
    }
    if (_locale != localeFallback) {
      if (undefined !== _messages[localeFallback][_domain] && undefined !== _messages[localeFallback][_domain][id]) {
        return _messages[localeFallback][_domain][id];
      }
    }
    return id;
  }

  /**
   * The logic comes from the Symfony2 PHP Framework.
   *
   * Given a message with different plural translations separated by a
   * pipe (|), this method returns the correct portion of the message based
   * on the given number, the current locale and the pluralization rules
   * in the message itself.
   *
   * The message supports two different types of pluralization rules:
   *
   * interval: {0} There is no apples|{1} There is one apple|]1,Inf] There is %count% apples
   * indexed:  There is one apple|There is %count% apples
   *
   * The indexed solution can also contain labels (e.g. one: There is one apple).
   * This is purely for making the translations more clear - it does not
   * affect the functionality.
   *
   * The two methods can also be mixed:
   *     {0} There is no apples|one: There is one apple|more: There is %count% apples
   *
   * @param {String} message  The message id
   * @param {Number} number   The number to use to find the indice of the message
   * @param {String} locale   The locale
   * @return {String}         The message part to use for translation
   * @api private
   */
  function pluralize(message, number, locale) {
    var _p,
      _e,
      _explicitRules = [],
      _standardRules = [],
      _parts = message.split(Translator.pluralSeparator),
      _matches = [];
    for (_p in _parts) {
      var _part = _parts[_p];
      var _rc = new RegExp(_cPluralRegex);
      var _rs = new RegExp(_sPluralRegex);
      if (_rc.test(_part)) {
        _matches = _part.match(_rc);
        _explicitRules[_matches[0]] = _matches[_matches.length - 1];
      } else if (_rs.test(_part)) {
        _matches = _part.match(_rs);
        _standardRules.push(_matches[1]);
      } else {
        _standardRules.push(_part);
      }
    }
    for (_e in _explicitRules) {
      var _r = new RegExp(_iPluralRegex);
      if (_r.test(_e)) {
        _matches = _e.match(_r);
        if (_matches[1]) {
          var _ns = _matches[2].split(','),
            _n;
          for (_n in _ns) {
            if (number == _ns[_n]) {
              return _explicitRules[_e];
            }
          }
        } else {
          var _leftNumber = convert_number(_matches[4]);
          var _rightNumber = convert_number(_matches[5]);
          if (('[' === _matches[3] ? number >= _leftNumber : number > _leftNumber) && (']' === _matches[6] ? number <= _rightNumber : number < _rightNumber)) {
            return _explicitRules[_e];
          }
        }
      }
    }
    return _standardRules[plural_position(number, locale)] || _standardRules[0] || undefined;
  }

  /**
   * The logic comes from the Symfony2 PHP Framework.
   *
   * Convert number as String, "Inf" and "-Inf"
   * values to number values.
   *
   * @param {String} number   A litteral number
   * @return {Number}         The int value of the number
   * @api private
   */
  function convert_number(number) {
    if ('-Inf' === number) {
      return Math.log(0);
    } else if ('+Inf' === number || 'Inf' === number) {
      return -Math.log(0);
    }
    return parseInt(number, 10);
  }

  /**
   * The logic comes from the Symfony2 PHP Framework.
   *
   * Returns the plural position to use for the given locale and number.
   *
   * @param {Number} number  The number to use to find the indice of the message
   * @param {String} locale  The locale
   * @return {Number}        The plural position
   * @api private
   */
  function plural_position(number, locale) {
    var _locale = locale;
    if ('pt_BR' === _locale) {
      _locale = 'xbr';
    }
    if (_locale.length > 3) {
      _locale = _locale.split('_')[0];
    }
    switch (_locale) {
      case 'bo':
      case 'dz':
      case 'id':
      case 'ja':
      case 'jv':
      case 'ka':
      case 'km':
      case 'kn':
      case 'ko':
      case 'ms':
      case 'th':
      case 'tr':
      case 'vi':
      case 'zh':
        return 0;
      case 'af':
      case 'az':
      case 'bn':
      case 'bg':
      case 'ca':
      case 'da':
      case 'de':
      case 'el':
      case 'en':
      case 'eo':
      case 'es':
      case 'et':
      case 'eu':
      case 'fa':
      case 'fi':
      case 'fo':
      case 'fur':
      case 'fy':
      case 'gl':
      case 'gu':
      case 'ha':
      case 'he':
      case 'hu':
      case 'is':
      case 'it':
      case 'ku':
      case 'lb':
      case 'ml':
      case 'mn':
      case 'mr':
      case 'nah':
      case 'nb':
      case 'ne':
      case 'nl':
      case 'nn':
      case 'no':
      case 'om':
      case 'or':
      case 'pa':
      case 'pap':
      case 'ps':
      case 'pt':
      case 'so':
      case 'sq':
      case 'sv':
      case 'sw':
      case 'ta':
      case 'te':
      case 'tk':
      case 'ur':
      case 'zu':
        return number == 1 ? 0 : 1;
      case 'am':
      case 'bh':
      case 'fil':
      case 'fr':
      case 'gun':
      case 'hi':
      case 'ln':
      case 'mg':
      case 'nso':
      case 'xbr':
      case 'ti':
      case 'wa':
        return number === 0 || number == 1 ? 0 : 1;
      case 'be':
      case 'bs':
      case 'hr':
      case 'ru':
      case 'sr':
      case 'uk':
        return number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;
      case 'cs':
      case 'sk':
        return number == 1 ? 0 : number >= 2 && number <= 4 ? 1 : 2;
      case 'ga':
        return number == 1 ? 0 : number == 2 ? 1 : 2;
      case 'lt':
        return number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;
      case 'sl':
        return number % 100 == 1 ? 0 : number % 100 == 2 ? 1 : number % 100 == 3 || number % 100 == 4 ? 2 : 3;
      case 'mk':
        return number % 10 == 1 ? 0 : 1;
      case 'mt':
        return number == 1 ? 0 : number === 0 || number % 100 > 1 && number % 100 < 11 ? 1 : number % 100 > 10 && number % 100 < 20 ? 2 : 3;
      case 'lv':
        return number === 0 ? 0 : number % 10 == 1 && number % 100 != 11 ? 1 : 2;
      case 'pl':
        return number == 1 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 12 || number % 100 > 14) ? 1 : 2;
      case 'cy':
        return number == 1 ? 0 : number == 2 ? 1 : number == 8 || number == 11 ? 2 : 3;
      case 'ro':
        return number == 1 ? 0 : number === 0 || number % 100 > 0 && number % 100 < 20 ? 1 : 2;
      case 'ar':
        return number === 0 ? 0 : number == 1 ? 1 : number == 2 ? 2 : number >= 3 && number <= 10 ? 3 : number >= 11 && number <= 99 ? 4 : 5;
      default:
        return 0;
    }
  }

  /**
   * Get the current application's locale based on the `lang` attribute
   * on the `html` tag.
   *
   * @return {String}     The current application's locale
   * @api private
   */
  function get_current_locale() {
    return document.documentElement.lang;
  }
  return {
    /**
     * The current locale.
     *
     * @type {String}
     * @api public
     */
    locale: get_current_locale(),
    /**
     * Fallback locale.
     *
     * @type {String}
     * @api public
     */
    fallback: 'en',
    /**
     * Placeholder prefix.
     *
     * @type {String}
     * @api public
     */
    placeHolderPrefix: '%',
    /**
     * Placeholder suffix.
     *
     * @type {String}
     * @api public
     */
    placeHolderSuffix: '%',
    /**
     * Default domain.
     *
     * @type {String}
     * @api public
     */
    defaultDomain: 'messages',
    /**
     * Plurar separator.
     *
     * @type {String}
     * @api public
     */
    pluralSeparator: '|',
    /**
     * Adds a translation entry.
     *
     * @param {String} id       The message id
     * @param {String} message  The message to register for the given id
     * @param {String} domain   The domain for the message or null to use the default
     * @param {String} locale   The locale or null to use the default
     * @return {Object}         Translator
     * @api public
     */
    add: function add(id, message, domain, locale) {
      var _locale = locale || this.locale || this.fallback,
        _domain = domain || this.defaultDomain;
      if (!_messages[_locale]) {
        _messages[_locale] = {};
      }
      if (!_messages[_locale][_domain]) {
        _messages[_locale][_domain] = {};
      }
      _messages[_locale][_domain][id] = message;
      return this;
    },
    /**
     * Translates the given message.
     *
     * @param {String} id             The message id
     * @param {Object} parameters     An array of parameters for the message
     * @param {String} domain         The domain for the message or null to guess it
     * @param {String} locale         The locale or null to use the default
     * @return {String}               The translated string
     * @api public
     */
    trans: function trans(id, parameters, domain, locale) {
      var _message = get_message(id, domain, locale, this.locale, this.fallback, this.defaultDomain);
      return this.addMark(id, domain, replace_placeholders(_message, parameters || {}));
    },
    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param {String} id             The message id
     * @param {Number} number         The number to use to find the indice of the message
     * @param {Object} parameters     An array of parameters for the message
     * @param {String} domain         The domain for the message or null to guess it
     * @param {String} locale         The locale or null to use the default
     * @return {String}               The translated string
     * @api public
     */
    transChoice: function transChoice(id, number, parameters, domain, locale) {
      var _message = get_message(id, domain, locale, this.locale, this.fallback, this.defaultDomain);
      var _number = parseInt(number, 10);
      if (undefined !== _message && !isNaN(_number)) {
        _message = pluralize(_message, _number, locale || this.locale || this.fallback);
      }
      return this.addMark(id, domain, replace_placeholders(_message, parameters || {}));
    },
    addMark: function addMark(id, domain, mess) {
      if (isTranshelper) {
        var mark = base64encode(JSON.stringify({
          id: id,
          domain: domain || 'messages',
          message: mess
        }));
        return '<mark data-title="' + mark + '">' + mess + '</mark>';
      } else {
        return mess;
      }
    },
    /**
     * Loads translations from JSON.
     *
     * @param {String} data     A JSON string or object literal
     * @return {Object}         Translator
     * @api public
     */
    fromJSON: function fromJSON(data) {
      if (typeof data === 'string') {
        data = JSON.parse(data);
      }
      if (data.locale) {
        this.locale = data.locale;
      }
      if (data.fallback) {
        this.fallback = data.fallback;
      }
      if (data.defaultDomain) {
        this.defaultDomain = data.defaultDomain;
      }
      if (data.translations) {
        for (var locale in data.translations) {
          for (var domain in data.translations[locale]) {
            for (var id in data.translations[locale][domain]) {
              this.add(id, data.translations[locale][domain][id], domain, locale);
            }
          }
        }
      }
      return this;
    },
    /**
     * @api public
     */
    reset: function reset() {
      _messages = {};
      this.locale = get_current_locale();
    }
  };
}(document);
if (typeof window.define === 'function' && window.define.amd) {
  !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
    return Translator;
  }).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
}
if ( true && module.exports) {
  module.exports = Translator;
}

/***/ }),

/***/ "./web/assets/translations/config.js":
/*!*******************************************!*\
  !*** ./web/assets/translations/config.js ***!
  \*******************************************/
/***/ (() => {

(function (t) {
  t.fallback = 'en';
  t.defaultDomain = 'messages';
})(Translator);

/***/ }),

/***/ "./assets/bem/ts/service/translator.ts":
/*!*********************************************!*\
  !*** ./assets/bem/ts/service/translator.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _web_assets_common_js_translator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../web/assets/common/js/translator */ "./web/assets/common/js/translator.js");
/* harmony import */ var _web_assets_common_js_translator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_web_assets_common_js_translator__WEBPACK_IMPORTED_MODULE_0__);

// global variable for legacy code only
var Service = (_web_assets_common_js_translator__WEBPACK_IMPORTED_MODULE_0___default());
window.Translator = Service;
__webpack_require__(/*! ../../../../web/assets/translations/config */ "./web/assets/translations/config.js");
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Service);

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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LElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7UUFDTCxPQUFPLENBQUM7TUFDWixLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLEtBQUs7TUFDVixLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLEtBQUs7TUFDVixLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLEtBQUs7TUFDVixLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7UUFDTCxPQUFRSSxNQUFNLElBQUksQ0FBQyxHQUFJLENBQUMsR0FBRyxDQUFDO01BRWhDLEtBQUssSUFBSTtNQUNULEtBQUssSUFBSTtNQUNULEtBQUssS0FBSztNQUNWLEtBQUssSUFBSTtNQUNULEtBQUssS0FBSztNQUNWLEtBQUssSUFBSTtNQUNULEtBQUssSUFBSTtNQUNULEtBQUssSUFBSTtNQUNULEtBQUssS0FBSztNQUNWLEtBQUssS0FBSztNQUNWLEtBQUssSUFBSTtNQUNULEtBQUssSUFBSTtRQUNMLE9BQVNBLE1BQU0sS0FBSyxDQUFDLElBQU1BLE1BQU0sSUFBSSxDQUFFLEdBQUksQ0FBQyxHQUFHLENBQUM7TUFFcEQsS0FBSyxJQUFJO01BQ1QsS0FBSyxJQUFJO01BQ1QsS0FBSyxJQUFJO01BQ1QsS0FBSyxJQUFJO01BQ1QsS0FBSyxJQUFJO01BQ1QsS0FBSyxJQUFJO1FBQ0wsT0FBU0EsTUFBTSxHQUFHLEVBQUUsSUFBSSxDQUFDLElBQU1BLE1BQU0sR0FBRyxHQUFHLElBQUksRUFBRyxHQUFJLENBQUMsR0FBTUEsTUFBTSxHQUFHLEVBQUUsSUFBSSxDQUFDLElBQU1BLE1BQU0sR0FBRyxFQUFFLElBQUksQ0FBRSxLQUFNQSxNQUFNLEdBQUcsR0FBRyxHQUFHLEVBQUUsSUFBTUEsTUFBTSxHQUFHLEdBQUcsSUFBSSxFQUFHLENBQUMsR0FBSSxDQUFDLEdBQUcsQ0FBRTtNQUVuSyxLQUFLLElBQUk7TUFDVCxLQUFLLElBQUk7UUFDTCxPQUFRQSxNQUFNLElBQUksQ0FBQyxHQUFJLENBQUMsR0FBTUEsTUFBTSxJQUFJLENBQUMsSUFBTUEsTUFBTSxJQUFJLENBQUUsR0FBSSxDQUFDLEdBQUcsQ0FBRTtNQUV6RSxLQUFLLElBQUk7UUFDTCxPQUFRQSxNQUFNLElBQUksQ0FBQyxHQUFJLENBQUMsR0FBS0EsTUFBTSxJQUFJLENBQUMsR0FBSSxDQUFDLEdBQUcsQ0FBRTtNQUV0RCxLQUFLLElBQUk7UUFDTCxPQUFTQSxNQUFNLEdBQUcsRUFBRSxJQUFJLENBQUMsSUFBTUEsTUFBTSxHQUFHLEdBQUcsSUFBSSxFQUFHLEdBQUksQ0FBQyxHQUFNQSxNQUFNLEdBQUcsRUFBRSxJQUFJLENBQUMsS0FBT0EsTUFBTSxHQUFHLEdBQUcsR0FBRyxFQUFFLElBQU1BLE1BQU0sR0FBRyxHQUFHLElBQUksRUFBRyxDQUFDLEdBQUksQ0FBQyxHQUFHLENBQUU7TUFFN0ksS0FBSyxJQUFJO1FBQ0wsT0FBUUEsTUFBTSxHQUFHLEdBQUcsSUFBSSxDQUFDLEdBQUksQ0FBQyxHQUFLQSxNQUFNLEdBQUcsR0FBRyxJQUFJLENBQUMsR0FBSSxDQUFDLEdBQU1BLE1BQU0sR0FBRyxHQUFHLElBQUksQ0FBQyxJQUFNQSxNQUFNLEdBQUcsR0FBRyxJQUFJLENBQUUsR0FBSSxDQUFDLEdBQUcsQ0FBRztNQUV2SCxLQUFLLElBQUk7UUFDTCxPQUFRQSxNQUFNLEdBQUcsRUFBRSxJQUFJLENBQUMsR0FBSSxDQUFDLEdBQUcsQ0FBQztNQUVyQyxLQUFLLElBQUk7UUFDTCxPQUFRQSxNQUFNLElBQUksQ0FBQyxHQUFJLENBQUMsR0FBTUEsTUFBTSxLQUFLLENBQUMsSUFBT0EsTUFBTSxHQUFHLEdBQUcsR0FBRyxDQUFDLElBQU1BLE1BQU0sR0FBRyxHQUFHLEdBQUcsRUFBSSxHQUFJLENBQUMsR0FBTUEsTUFBTSxHQUFHLEdBQUcsR0FBRyxFQUFFLElBQU1BLE1BQU0sR0FBRyxHQUFHLEdBQUcsRUFBRyxHQUFJLENBQUMsR0FBRyxDQUFHO01BRTdKLEtBQUssSUFBSTtRQUNMLE9BQVFBLE1BQU0sS0FBSyxDQUFDLEdBQUksQ0FBQyxHQUFNQSxNQUFNLEdBQUcsRUFBRSxJQUFJLENBQUMsSUFBTUEsTUFBTSxHQUFHLEdBQUcsSUFBSSxFQUFHLEdBQUksQ0FBQyxHQUFHLENBQUU7TUFFdEYsS0FBSyxJQUFJO1FBQ0wsT0FBUUEsTUFBTSxJQUFJLENBQUMsR0FBSSxDQUFDLEdBQU1BLE1BQU0sR0FBRyxFQUFFLElBQUksQ0FBQyxJQUFNQSxNQUFNLEdBQUcsRUFBRSxJQUFJLENBQUUsS0FBTUEsTUFBTSxHQUFHLEdBQUcsR0FBRyxFQUFFLElBQU1BLE1BQU0sR0FBRyxHQUFHLEdBQUcsRUFBRyxD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Ly9Bd2FyZFdhbGxldC8uL3dlYi9hc3NldHMvY29tbW9uL2pzL3RyYW5zbGF0b3IuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL3RyYW5zbGF0aW9ucy9jb25maWcuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2UvdHJhbnNsYXRvci50cyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIFdpbGxpYW0gRFVSQU5EIDx3aWxsaWFtLmR1cmFuZDFAZ21haWwuY29tPlxuICogTUlUIExpY2Vuc2VkXG4gKi9cbnZhciBUcmFuc2xhdG9yID0gKGZ1bmN0aW9uKGRvY3VtZW50KSB7XG4gICAgXCJ1c2Ugc3RyaWN0XCI7XG5cbiAgICB2YXIgYmFzZTY0ZW5jb2RlID0gZnVuY3Rpb24gYmFzZTY0X2VuY29kZShkYXRhKSB7XG5cbiAgICAgICAgdmFyIGI2NCA9IFwiQUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVphYmNkZWZnaGlqa2xtbm9wcXJzdHV2d3h5ejAxMjM0NTY3ODkrLz1cIjtcbiAgICAgICAgdmFyIG8xLCBvMiwgbzMsIGgxLCBoMiwgaDMsIGg0LCBiaXRzLCBpID0gMCwgZW5jID0gJyc7XG5cbiAgICAgICAgZG8ge1xuICAgICAgICAgICAgbzEgPSBkYXRhLmNoYXJDb2RlQXQoaSsrKTtcbiAgICAgICAgICAgIG8yID0gZGF0YS5jaGFyQ29kZUF0KGkrKyk7XG4gICAgICAgICAgICBvMyA9IGRhdGEuY2hhckNvZGVBdChpKyspO1xuXG4gICAgICAgICAgICBiaXRzID0gbzEgPDwgMTYgfCBvMiA8PCA4IHwgbzM7XG5cbiAgICAgICAgICAgIGgxID0gYml0cyA+PiAxOCAmIDB4M2Y7XG4gICAgICAgICAgICBoMiA9IGJpdHMgPj4gMTIgJiAweDNmO1xuICAgICAgICAgICAgaDMgPSBiaXRzID4+IDYgJiAweDNmO1xuICAgICAgICAgICAgaDQgPSBiaXRzICYgMHgzZjtcblxuICAgICAgICAgICAgZW5jICs9IGI2NC5jaGFyQXQoaDEpICsgYjY0LmNoYXJBdChoMikgKyBiNjQuY2hhckF0KGgzKSArIGI2NC5jaGFyQXQoaDQpO1xuICAgICAgICB9IHdoaWxlIChpIDwgZGF0YS5sZW5ndGgpO1xuXG4gICAgICAgIHN3aXRjaCAoZGF0YS5sZW5ndGggJSAzKSB7XG4gICAgICAgICAgICBjYXNlIDE6XG4gICAgICAgICAgICAgICAgZW5jID0gZW5jLnNsaWNlKDAsIC0yKSArICc9PSc7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlIDI6XG4gICAgICAgICAgICAgICAgZW5jID0gZW5jLnNsaWNlKDAsIC0xKSArICc9JztcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBlbmM7XG4gICAgfTtcblxuICAgIHZhciBpc1RyYW5zaGVscGVyID0gZG9jdW1lbnQuY29va2llLm1hdGNoKC90cmFuc2hlbHBlci8pO1xuXG4gICAgdmFyIF9tZXNzYWdlcyAgICAgPSB7fSxcbiAgICAgICAgX3NQbHVyYWxSZWdleCA9IC9eXFx3K1xcOiArKC4rKSQvLFxuICAgICAgICBfY1BsdXJhbFJlZ2V4ID0gL15cXHMqKChcXHtcXHMqKFxcLT9cXGQrW1xccyosXFxzKlxcLT9cXGQrXSopXFxzKlxcfSl8KFtcXFtcXF1dKVxccyooLUluZnxcXC0/XFxkKylcXHMqLFxccyooXFwrP0luZnxcXC0/XFxkKylcXHMqKFtcXFtcXF1dKSlcXHM/KC4rPykkLyxcbiAgICAgICAgX2lQbHVyYWxSZWdleCA9IC9eXFxzKihcXHtcXHMqKFxcLT9cXGQrW1xccyosXFxzKlxcLT9cXGQrXSopXFxzKlxcfSl8KFtcXFtcXF1dKVxccyooLUluZnxcXC0/XFxkKylcXHMqLFxccyooXFwrP0luZnxcXC0/XFxkKylcXHMqKFtcXFtcXF1dKS87XG5cbiAgICAvKipcbiAgICAgKiBSZXBsYWNlIHBsYWNlaG9sZGVycyBpbiBnaXZlbiBtZXNzYWdlLlxuICAgICAqXG4gICAgICogKipXQVJOSU5HOioqIHVzZWQgcGxhY2Vob2xkZXJzIGFyZSByZW1vdmVkLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IG1lc3NhZ2UgICAgICBUaGUgdHJhbnNsYXRlZCBtZXNzYWdlXG4gICAgICogQHBhcmFtIHtPYmplY3R9IHBsYWNlaG9sZGVycyBUaGUgcGxhY2Vob2xkZXJzIHRvIHJlcGxhY2VcbiAgICAgKiBAcmV0dXJuIHtTdHJpbmd9ICAgICAgICAgICAgIEEgaHVtYW4gcmVhZGFibGUgbWVzc2FnZVxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqL1xuICAgIGZ1bmN0aW9uIHJlcGxhY2VfcGxhY2Vob2xkZXJzKG1lc3NhZ2UsIHBsYWNlaG9sZGVycykge1xuICAgICAgICB2YXIgX2ksXG4gICAgICAgICAgICBfcHJlZml4ID0gVHJhbnNsYXRvci5wbGFjZUhvbGRlclByZWZpeCxcbiAgICAgICAgICAgIF9zdWZmaXggPSBUcmFuc2xhdG9yLnBsYWNlSG9sZGVyU3VmZml4O1xuXG4gICAgICAgIGZvciAoX2kgaW4gcGxhY2Vob2xkZXJzKSB7XG4gICAgICAgICAgICB2YXIgX3IgPSBuZXcgUmVnRXhwKF9wcmVmaXggKyBfaSArIF9zdWZmaXgsICdnJyk7XG5cbiAgICAgICAgICAgIGlmIChfci50ZXN0KG1lc3NhZ2UpKSB7XG4gICAgICAgICAgICAgICAgbWVzc2FnZSA9IG1lc3NhZ2UucmVwbGFjZShfciwgcGxhY2Vob2xkZXJzW19pXSk7XG4gICAgICAgICAgICAgICAgZGVsZXRlKHBsYWNlaG9sZGVyc1tfaV0pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIG1lc3NhZ2U7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogR2V0IHRoZSBtZXNzYWdlIGJhc2VkIG9uIGl0cyBpZCwgaXRzIGRvbWFpbiwgYW5kIGl0cyBsb2NhbGUuIElmIGRvbWFpbiBvclxuICAgICAqIGxvY2FsZSBhcmUgbm90IHNwZWNpZmllZCwgaXQgd2lsbCB0cnkgdG8gZmluZCB0aGUgbWVzc2FnZSB1c2luZyBmYWxsYmFja3MuXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge1N0cmluZ30gaWQgICAgICAgICAgICAgICBUaGUgbWVzc2FnZSBpZFxuICAgICAqIEBwYXJhbSB7U3RyaW5nfSBkb21haW4gICAgICAgICAgIFRoZSBkb21haW4gZm9yIHRoZSBtZXNzYWdlIG9yIG51bGwgdG8gZ3Vlc3MgaXRcbiAgICAgKiBAcGFyYW0ge1N0cmluZ30gbG9jYWxlICAgICAgICAgICBUaGUgbG9jYWxlIG9yIG51bGwgdG8gdXNlIHRoZSBkZWZhdWx0XG4gICAgICogQHBhcmFtIHtTdHJpbmd9IGN1cnJlbnRMb2NhbGUgICAgVGhlIGN1cnJlbnQgbG9jYWxlIG9yIG51bGwgdG8gdXNlIHRoZSBkZWZhdWx0XG4gICAgICogQHBhcmFtIHtTdHJpbmd9IGxvY2FsZUZhbGxiYWNrICAgVGhlIGZhbGxiYWNrIChkZWZhdWx0KSBsb2NhbGVcbiAgICAgKiBAcGFyYW0ge1N0cmluZ30gZGVmYXVsdERvbWFpbiAgICBEZWZhdWx0IGRvbWFpblxuICAgICAqIEByZXR1cm4ge1N0cmluZ30gICAgICAgICAgICAgICAgIFRoZSByaWdodCBtZXNzYWdlIGlmIGZvdW5kLCBgdW5kZWZpbmVkYCBvdGhlcndpc2VcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBnZXRfbWVzc2FnZShpZCwgZG9tYWluLCBsb2NhbGUsIGN1cnJlbnRMb2NhbGUsIGxvY2FsZUZhbGxiYWNrLCBkZWZhdWx0RG9tYWluKSB7XG4gICAgICAgIHZhciBfbG9jYWxlID0gbG9jYWxlIHx8IGN1cnJlbnRMb2NhbGUgfHwgbG9jYWxlRmFsbGJhY2ssXG4gICAgICAgICAgICBfZG9tYWluID0gZG9tYWluIHx8IGRlZmF1bHREb21haW47XG5cbiAgICAgICAgaWYgKHVuZGVmaW5lZCA9PT0gX21lc3NhZ2VzW19sb2NhbGVdKSB7XG4gICAgICAgICAgICBpZiAodW5kZWZpbmVkID09PSBfbWVzc2FnZXNbbG9jYWxlRmFsbGJhY2tdKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGlkO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBfbG9jYWxlID0gbG9jYWxlRmFsbGJhY2s7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAodW5kZWZpbmVkICE9PSBfbWVzc2FnZXNbX2xvY2FsZV1bX2RvbWFpbl0gJiZcbiAgICAgICAgICAgIHVuZGVmaW5lZCAhPT0gX21lc3NhZ2VzW19sb2NhbGVdW19kb21haW5dW2lkXSkge1xuICAgICAgICAgICAgcmV0dXJuIF9tZXNzYWdlc1tfbG9jYWxlXVtfZG9tYWluXVtpZF07XG4gICAgICAgIH1cbiAgICAgICAgaWYgKF9sb2NhbGUgIT0gbG9jYWxlRmFsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh1bmRlZmluZWQgIT09IF9tZXNzYWdlc1tsb2NhbGVGYWxsYmFja11bX2RvbWFpbl0gJiZcbiAgICAgICAgICAgICAgICB1bmRlZmluZWQgIT09IF9tZXNzYWdlc1tsb2NhbGVGYWxsYmFja11bX2RvbWFpbl1baWRdKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIF9tZXNzYWdlc1tsb2NhbGVGYWxsYmFja11bX2RvbWFpbl1baWRdO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIGlkO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFRoZSBsb2dpYyBjb21lcyBmcm9tIHRoZSBTeW1mb255MiBQSFAgRnJhbWV3b3JrLlxuICAgICAqXG4gICAgICogR2l2ZW4gYSBtZXNzYWdlIHdpdGggZGlmZmVyZW50IHBsdXJhbCB0cmFuc2xhdGlvbnMgc2VwYXJhdGVkIGJ5IGFcbiAgICAgKiBwaXBlICh8KSwgdGhpcyBtZXRob2QgcmV0dXJucyB0aGUgY29ycmVjdCBwb3J0aW9uIG9mIHRoZSBtZXNzYWdlIGJhc2VkXG4gICAgICogb24gdGhlIGdpdmVuIG51bWJlciwgdGhlIGN1cnJlbnQgbG9jYWxlIGFuZCB0aGUgcGx1cmFsaXphdGlvbiBydWxlc1xuICAgICAqIGluIHRoZSBtZXNzYWdlIGl0c2VsZi5cbiAgICAgKlxuICAgICAqIFRoZSBtZXNzYWdlIHN1cHBvcnRzIHR3byBkaWZmZXJlbnQgdHlwZXMgb2YgcGx1cmFsaXphdGlvbiBydWxlczpcbiAgICAgKlxuICAgICAqIGludGVydmFsOiB7MH0gVGhlcmUgaXMgbm8gYXBwbGVzfHsxfSBUaGVyZSBpcyBvbmUgYXBwbGV8XTEsSW5mXSBUaGVyZSBpcyAlY291bnQlIGFwcGxlc1xuICAgICAqIGluZGV4ZWQ6ICBUaGVyZSBpcyBvbmUgYXBwbGV8VGhlcmUgaXMgJWNvdW50JSBhcHBsZXNcbiAgICAgKlxuICAgICAqIFRoZSBpbmRleGVkIHNvbHV0aW9uIGNhbiBhbHNvIGNvbnRhaW4gbGFiZWxzIChlLmcuIG9uZTogVGhlcmUgaXMgb25lIGFwcGxlKS5cbiAgICAgKiBUaGlzIGlzIHB1cmVseSBmb3IgbWFraW5nIHRoZSB0cmFuc2xhdGlvbnMgbW9yZSBjbGVhciAtIGl0IGRvZXMgbm90XG4gICAgICogYWZmZWN0IHRoZSBmdW5jdGlvbmFsaXR5LlxuICAgICAqXG4gICAgICogVGhlIHR3byBtZXRob2RzIGNhbiBhbHNvIGJlIG1peGVkOlxuICAgICAqICAgICB7MH0gVGhlcmUgaXMgbm8gYXBwbGVzfG9uZTogVGhlcmUgaXMgb25lIGFwcGxlfG1vcmU6IFRoZXJlIGlzICVjb3VudCUgYXBwbGVzXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge1N0cmluZ30gbWVzc2FnZSAgVGhlIG1lc3NhZ2UgaWRcbiAgICAgKiBAcGFyYW0ge051bWJlcn0gbnVtYmVyICAgVGhlIG51bWJlciB0byB1c2UgdG8gZmluZCB0aGUgaW5kaWNlIG9mIHRoZSBtZXNzYWdlXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IGxvY2FsZSAgIFRoZSBsb2NhbGVcbiAgICAgKiBAcmV0dXJuIHtTdHJpbmd9ICAgICAgICAgVGhlIG1lc3NhZ2UgcGFydCB0byB1c2UgZm9yIHRyYW5zbGF0aW9uXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICovXG4gICAgZnVuY3Rpb24gcGx1cmFsaXplKG1lc3NhZ2UsIG51bWJlciwgbG9jYWxlKSB7XG4gICAgICAgIHZhciBfcCxcbiAgICAgICAgICAgIF9lLFxuICAgICAgICAgICAgX2V4cGxpY2l0UnVsZXMgPSBbXSxcbiAgICAgICAgICAgIF9zdGFuZGFyZFJ1bGVzID0gW10sXG4gICAgICAgICAgICBfcGFydHMgICAgICAgICA9IG1lc3NhZ2Uuc3BsaXQoVHJhbnNsYXRvci5wbHVyYWxTZXBhcmF0b3IpLFxuICAgICAgICAgICAgX21hdGNoZXMgICAgICAgPSBbXTtcblxuICAgICAgICBmb3IgKF9wIGluIF9wYXJ0cykge1xuICAgICAgICAgICAgdmFyIF9wYXJ0ID0gX3BhcnRzW19wXTtcbiAgICAgICAgICAgIHZhciBfcmMgPSBuZXcgUmVnRXhwKF9jUGx1cmFsUmVnZXgpO1xuICAgICAgICAgICAgdmFyIF9ycyA9IG5ldyBSZWdFeHAoX3NQbHVyYWxSZWdleCk7XG5cbiAgICAgICAgICAgIGlmIChfcmMudGVzdChfcGFydCkpIHtcbiAgICAgICAgICAgICAgICBfbWF0Y2hlcyA9IF9wYXJ0Lm1hdGNoKF9yYyk7XG4gICAgICAgICAgICAgICAgX2V4cGxpY2l0UnVsZXNbX21hdGNoZXNbMF1dID0gX21hdGNoZXNbX21hdGNoZXMubGVuZ3RoIC0gMV07XG4gICAgICAgICAgICB9IGVsc2UgaWYgKF9ycy50ZXN0KF9wYXJ0KSkge1xuICAgICAgICAgICAgICAgIF9tYXRjaGVzID0gX3BhcnQubWF0Y2goX3JzKTtcbiAgICAgICAgICAgICAgICBfc3RhbmRhcmRSdWxlcy5wdXNoKF9tYXRjaGVzWzFdKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgX3N0YW5kYXJkUnVsZXMucHVzaChfcGFydCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICBmb3IgKF9lIGluIF9leHBsaWNpdFJ1bGVzKSB7XG4gICAgICAgICAgICB2YXIgX3IgPSBuZXcgUmVnRXhwKF9pUGx1cmFsUmVnZXgpO1xuXG4gICAgICAgICAgICBpZiAoX3IudGVzdChfZSkpIHtcbiAgICAgICAgICAgICAgICBfbWF0Y2hlcyA9IF9lLm1hdGNoKF9yKTtcblxuICAgICAgICAgICAgICAgIGlmIChfbWF0Y2hlc1sxXSkge1xuICAgICAgICAgICAgICAgICAgICB2YXIgX25zID0gX21hdGNoZXNbMl0uc3BsaXQoJywnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgIF9uO1xuXG4gICAgICAgICAgICAgICAgICAgIGZvciAoX24gaW4gX25zKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAobnVtYmVyID09IF9uc1tfbl0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gX2V4cGxpY2l0UnVsZXNbX2VdO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIF9sZWZ0TnVtYmVyICA9IGNvbnZlcnRfbnVtYmVyKF9tYXRjaGVzWzRdKTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIF9yaWdodE51bWJlciA9IGNvbnZlcnRfbnVtYmVyKF9tYXRjaGVzWzVdKTtcblxuICAgICAgICAgICAgICAgICAgICBpZiAoKCdbJyA9PT0gX21hdGNoZXNbM10gPyBudW1iZXIgPj0gX2xlZnROdW1iZXIgOiBudW1iZXIgPiBfbGVmdE51bWJlcikgJiZcbiAgICAgICAgICAgICAgICAgICAgICAgICgnXScgPT09IF9tYXRjaGVzWzZdID8gbnVtYmVyIDw9IF9yaWdodE51bWJlciA6IG51bWJlciA8IF9yaWdodE51bWJlcikpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBfZXhwbGljaXRSdWxlc1tfZV07XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gX3N0YW5kYXJkUnVsZXNbcGx1cmFsX3Bvc2l0aW9uKG51bWJlciwgbG9jYWxlKV0gfHwgX3N0YW5kYXJkUnVsZXNbMF0gfHwgdW5kZWZpbmVkO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFRoZSBsb2dpYyBjb21lcyBmcm9tIHRoZSBTeW1mb255MiBQSFAgRnJhbWV3b3JrLlxuICAgICAqXG4gICAgICogQ29udmVydCBudW1iZXIgYXMgU3RyaW5nLCBcIkluZlwiIGFuZCBcIi1JbmZcIlxuICAgICAqIHZhbHVlcyB0byBudW1iZXIgdmFsdWVzLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IG51bWJlciAgIEEgbGl0dGVyYWwgbnVtYmVyXG4gICAgICogQHJldHVybiB7TnVtYmVyfSAgICAgICAgIFRoZSBpbnQgdmFsdWUgb2YgdGhlIG51bWJlclxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGNvbnZlcnRfbnVtYmVyKG51bWJlcikge1xuICAgICAgICBpZiAoJy1JbmYnID09PSBudW1iZXIpIHtcbiAgICAgICAgICAgIHJldHVybiBNYXRoLmxvZygwKTtcbiAgICAgICAgfSBlbHNlIGlmICgnK0luZicgPT09IG51bWJlciB8fCAnSW5mJyA9PT0gbnVtYmVyKSB7XG4gICAgICAgICAgICByZXR1cm4gLU1hdGgubG9nKDApO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIHBhcnNlSW50KG51bWJlciwgMTApO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFRoZSBsb2dpYyBjb21lcyBmcm9tIHRoZSBTeW1mb255MiBQSFAgRnJhbWV3b3JrLlxuICAgICAqXG4gICAgICogUmV0dXJucyB0aGUgcGx1cmFsIHBvc2l0aW9uIHRvIHVzZSBmb3IgdGhlIGdpdmVuIGxvY2FsZSBhbmQgbnVtYmVyLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtOdW1iZXJ9IG51bWJlciAgVGhlIG51bWJlciB0byB1c2UgdG8gZmluZCB0aGUgaW5kaWNlIG9mIHRoZSBtZXNzYWdlXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IGxvY2FsZSAgVGhlIGxvY2FsZVxuICAgICAqIEByZXR1cm4ge051bWJlcn0gICAgICAgIFRoZSBwbHVyYWwgcG9zaXRpb25cbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBwbHVyYWxfcG9zaXRpb24obnVtYmVyLCBsb2NhbGUpIHtcbiAgICAgICAgdmFyIF9sb2NhbGUgPSBsb2NhbGU7XG5cbiAgICAgICAgaWYgKCdwdF9CUicgPT09IF9sb2NhbGUpIHtcbiAgICAgICAgICAgIF9sb2NhbGUgPSAneGJyJztcbiAgICAgICAgfVxuXG4gICAgICAgIGlmIChfbG9jYWxlLmxlbmd0aCA+IDMpIHtcbiAgICAgICAgICAgIF9sb2NhbGUgPSBfbG9jYWxlLnNwbGl0KCdfJylbMF07XG4gICAgICAgIH1cblxuICAgICAgICBzd2l0Y2ggKF9sb2NhbGUpIHtcbiAgICAgICAgICAgIGNhc2UgJ2JvJzpcbiAgICAgICAgICAgIGNhc2UgJ2R6JzpcbiAgICAgICAgICAgIGNhc2UgJ2lkJzpcbiAgICAgICAgICAgIGNhc2UgJ2phJzpcbiAgICAgICAgICAgIGNhc2UgJ2p2JzpcbiAgICAgICAgICAgIGNhc2UgJ2thJzpcbiAgICAgICAgICAgIGNhc2UgJ2ttJzpcbiAgICAgICAgICAgIGNhc2UgJ2tuJzpcbiAgICAgICAgICAgIGNhc2UgJ2tvJzpcbiAgICAgICAgICAgIGNhc2UgJ21zJzpcbiAgICAgICAgICAgIGNhc2UgJ3RoJzpcbiAgICAgICAgICAgIGNhc2UgJ3RyJzpcbiAgICAgICAgICAgIGNhc2UgJ3ZpJzpcbiAgICAgICAgICAgIGNhc2UgJ3poJzpcbiAgICAgICAgICAgICAgICByZXR1cm4gMDtcbiAgICAgICAgICAgIGNhc2UgJ2FmJzpcbiAgICAgICAgICAgIGNhc2UgJ2F6JzpcbiAgICAgICAgICAgIGNhc2UgJ2JuJzpcbiAgICAgICAgICAgIGNhc2UgJ2JnJzpcbiAgICAgICAgICAgIGNhc2UgJ2NhJzpcbiAgICAgICAgICAgIGNhc2UgJ2RhJzpcbiAgICAgICAgICAgIGNhc2UgJ2RlJzpcbiAgICAgICAgICAgIGNhc2UgJ2VsJzpcbiAgICAgICAgICAgIGNhc2UgJ2VuJzpcbiAgICAgICAgICAgIGNhc2UgJ2VvJzpcbiAgICAgICAgICAgIGNhc2UgJ2VzJzpcbiAgICAgICAgICAgIGNhc2UgJ2V0JzpcbiAgICAgICAgICAgIGNhc2UgJ2V1JzpcbiAgICAgICAgICAgIGNhc2UgJ2ZhJzpcbiAgICAgICAgICAgIGNhc2UgJ2ZpJzpcbiAgICAgICAgICAgIGNhc2UgJ2ZvJzpcbiAgICAgICAgICAgIGNhc2UgJ2Z1cic6XG4gICAgICAgICAgICBjYXNlICdmeSc6XG4gICAgICAgICAgICBjYXNlICdnbCc6XG4gICAgICAgICAgICBjYXNlICdndSc6XG4gICAgICAgICAgICBjYXNlICdoYSc6XG4gICAgICAgICAgICBjYXNlICdoZSc6XG4gICAgICAgICAgICBjYXNlICdodSc6XG4gICAgICAgICAgICBjYXNlICdpcyc6XG4gICAgICAgICAgICBjYXNlICdpdCc6XG4gICAgICAgICAgICBjYXNlICdrdSc6XG4gICAgICAgICAgICBjYXNlICdsYic6XG4gICAgICAgICAgICBjYXNlICdtbCc6XG4gICAgICAgICAgICBjYXNlICdtbic6XG4gICAgICAgICAgICBjYXNlICdtcic6XG4gICAgICAgICAgICBjYXNlICduYWgnOlxuICAgICAgICAgICAgY2FzZSAnbmInOlxuICAgICAgICAgICAgY2FzZSAnbmUnOlxuICAgICAgICAgICAgY2FzZSAnbmwnOlxuICAgICAgICAgICAgY2FzZSAnbm4nOlxuICAgICAgICAgICAgY2FzZSAnbm8nOlxuICAgICAgICAgICAgY2FzZSAnb20nOlxuICAgICAgICAgICAgY2FzZSAnb3InOlxuICAgICAgICAgICAgY2FzZSAncGEnOlxuICAgICAgICAgICAgY2FzZSAncGFwJzpcbiAgICAgICAgICAgIGNhc2UgJ3BzJzpcbiAgICAgICAgICAgIGNhc2UgJ3B0JzpcbiAgICAgICAgICAgIGNhc2UgJ3NvJzpcbiAgICAgICAgICAgIGNhc2UgJ3NxJzpcbiAgICAgICAgICAgIGNhc2UgJ3N2JzpcbiAgICAgICAgICAgIGNhc2UgJ3N3JzpcbiAgICAgICAgICAgIGNhc2UgJ3RhJzpcbiAgICAgICAgICAgIGNhc2UgJ3RlJzpcbiAgICAgICAgICAgIGNhc2UgJ3RrJzpcbiAgICAgICAgICAgIGNhc2UgJ3VyJzpcbiAgICAgICAgICAgIGNhc2UgJ3p1JzpcbiAgICAgICAgICAgICAgICByZXR1cm4gKG51bWJlciA9PSAxKSA/IDAgOiAxO1xuXG4gICAgICAgICAgICBjYXNlICdhbSc6XG4gICAgICAgICAgICBjYXNlICdiaCc6XG4gICAgICAgICAgICBjYXNlICdmaWwnOlxuICAgICAgICAgICAgY2FzZSAnZnInOlxuICAgICAgICAgICAgY2FzZSAnZ3VuJzpcbiAgICAgICAgICAgIGNhc2UgJ2hpJzpcbiAgICAgICAgICAgIGNhc2UgJ2xuJzpcbiAgICAgICAgICAgIGNhc2UgJ21nJzpcbiAgICAgICAgICAgIGNhc2UgJ25zbyc6XG4gICAgICAgICAgICBjYXNlICd4YnInOlxuICAgICAgICAgICAgY2FzZSAndGknOlxuICAgICAgICAgICAgY2FzZSAnd2EnOlxuICAgICAgICAgICAgICAgIHJldHVybiAoKG51bWJlciA9PT0gMCkgfHwgKG51bWJlciA9PSAxKSkgPyAwIDogMTtcblxuICAgICAgICAgICAgY2FzZSAnYmUnOlxuICAgICAgICAgICAgY2FzZSAnYnMnOlxuICAgICAgICAgICAgY2FzZSAnaHInOlxuICAgICAgICAgICAgY2FzZSAncnUnOlxuICAgICAgICAgICAgY2FzZSAnc3InOlxuICAgICAgICAgICAgY2FzZSAndWsnOlxuICAgICAgICAgICAgICAgIHJldHVybiAoKG51bWJlciAlIDEwID09IDEpICYmIChudW1iZXIgJSAxMDAgIT0gMTEpKSA/IDAgOiAoKChudW1iZXIgJSAxMCA+PSAyKSAmJiAobnVtYmVyICUgMTAgPD0gNCkgJiYgKChudW1iZXIgJSAxMDAgPCAxMCkgfHwgKG51bWJlciAlIDEwMCA+PSAyMCkpKSA/IDEgOiAyKTtcblxuICAgICAgICAgICAgY2FzZSAnY3MnOlxuICAgICAgICAgICAgY2FzZSAnc2snOlxuICAgICAgICAgICAgICAgIHJldHVybiAobnVtYmVyID09IDEpID8gMCA6ICgoKG51bWJlciA+PSAyKSAmJiAobnVtYmVyIDw9IDQpKSA/IDEgOiAyKTtcblxuICAgICAgICAgICAgY2FzZSAnZ2EnOlxuICAgICAgICAgICAgICAgIHJldHVybiAobnVtYmVyID09IDEpID8gMCA6ICgobnVtYmVyID09IDIpID8gMSA6IDIpO1xuXG4gICAgICAgICAgICBjYXNlICdsdCc6XG4gICAgICAgICAgICAgICAgcmV0dXJuICgobnVtYmVyICUgMTAgPT0gMSkgJiYgKG51bWJlciAlIDEwMCAhPSAxMSkpID8gMCA6ICgoKG51bWJlciAlIDEwID49IDIpICYmICgobnVtYmVyICUgMTAwIDwgMTApIHx8IChudW1iZXIgJSAxMDAgPj0gMjApKSkgPyAxIDogMik7XG5cbiAgICAgICAgICAgIGNhc2UgJ3NsJzpcbiAgICAgICAgICAgICAgICByZXR1cm4gKG51bWJlciAlIDEwMCA9PSAxKSA/IDAgOiAoKG51bWJlciAlIDEwMCA9PSAyKSA/IDEgOiAoKChudW1iZXIgJSAxMDAgPT0gMykgfHwgKG51bWJlciAlIDEwMCA9PSA0KSkgPyAyIDogMykpO1xuXG4gICAgICAgICAgICBjYXNlICdtayc6XG4gICAgICAgICAgICAgICAgcmV0dXJuIChudW1iZXIgJSAxMCA9PSAxKSA/IDAgOiAxO1xuXG4gICAgICAgICAgICBjYXNlICdtdCc6XG4gICAgICAgICAgICAgICAgcmV0dXJuIChudW1iZXIgPT0gMSkgPyAwIDogKCgobnVtYmVyID09PSAwKSB8fCAoKG51bWJlciAlIDEwMCA+IDEpICYmIChudW1iZXIgJSAxMDAgPCAxMSkpKSA/IDEgOiAoKChudW1iZXIgJSAxMDAgPiAxMCkgJiYgKG51bWJlciAlIDEwMCA8IDIwKSkgPyAyIDogMykpO1xuXG4gICAgICAgICAgICBjYXNlICdsdic6XG4gICAgICAgICAgICAgICAgcmV0dXJuIChudW1iZXIgPT09IDApID8gMCA6ICgoKG51bWJlciAlIDEwID09IDEpICYmIChudW1iZXIgJSAxMDAgIT0gMTEpKSA/IDEgOiAyKTtcblxuICAgICAgICAgICAgY2FzZSAncGwnOlxuICAgICAgICAgICAgICAgIHJldHVybiAobnVtYmVyID09IDEpID8gMCA6ICgoKG51bWJlciAlIDEwID49IDIpICYmIChudW1iZXIgJSAxMCA8PSA0KSAmJiAoKG51bWJlciAlIDEwMCA8IDEyKSB8fCAobnVtYmVyICUgMTAwID4gMTQpKSkgPyAxIDogMik7XG5cbiAgICAgICAgICAgIGNhc2UgJ2N5JzpcbiAgICAgICAgICAgICAgICByZXR1cm4gKG51bWJlciA9PSAxKSA/IDAgOiAoKG51bWJlciA9PSAyKSA/IDEgOiAoKChudW1iZXIgPT0gOCkgfHwgKG51bWJlciA9PSAxMSkpID8gMiA6IDMpKTtcblxuICAgICAgICAgICAgY2FzZSAncm8nOlxuICAgICAgICAgICAgICAgIHJldHVybiAobnVtYmVyID09IDEpID8gMCA6ICgoKG51bWJlciA9PT0gMCkgfHwgKChudW1iZXIgJSAxMDAgPiAwKSAmJiAobnVtYmVyICUgMTAwIDwgMjApKSkgPyAxIDogMik7XG5cbiAgICAgICAgICAgIGNhc2UgJ2FyJzpcbiAgICAgICAgICAgICAgICByZXR1cm4gKG51bWJlciA9PT0gMCkgPyAwIDogKChudW1iZXIgPT0gMSkgPyAxIDogKChudW1iZXIgPT0gMikgPyAyIDogKCgobnVtYmVyID49IDMpICYmIChudW1iZXIgPD0gMTApKSA/IDMgOiAoKChudW1iZXIgPj0gMTEpICYmIChudW1iZXIgPD0gOTkpKSA/IDQgOiA1KSkpKTtcblxuICAgICAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgICAgICByZXR1cm4gMDtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEdldCB0aGUgY3VycmVudCBhcHBsaWNhdGlvbidzIGxvY2FsZSBiYXNlZCBvbiB0aGUgYGxhbmdgIGF0dHJpYnV0ZVxuICAgICAqIG9uIHRoZSBgaHRtbGAgdGFnLlxuICAgICAqXG4gICAgICogQHJldHVybiB7U3RyaW5nfSAgICAgVGhlIGN1cnJlbnQgYXBwbGljYXRpb24ncyBsb2NhbGVcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBnZXRfY3VycmVudF9sb2NhbGUoKSB7XG4gICAgICAgIHJldHVybiBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQubGFuZztcbiAgICB9XG5cbiAgICByZXR1cm4ge1xuICAgICAgICAvKipcbiAgICAgICAgICogVGhlIGN1cnJlbnQgbG9jYWxlLlxuICAgICAgICAgKlxuICAgICAgICAgKiBAdHlwZSB7U3RyaW5nfVxuICAgICAgICAgKiBAYXBpIHB1YmxpY1xuICAgICAgICAgKi9cbiAgICAgICAgbG9jYWxlOiBnZXRfY3VycmVudF9sb2NhbGUoKSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogRmFsbGJhY2sgbG9jYWxlLlxuICAgICAgICAgKlxuICAgICAgICAgKiBAdHlwZSB7U3RyaW5nfVxuICAgICAgICAgKiBAYXBpIHB1YmxpY1xuICAgICAgICAgKi9cbiAgICAgICAgZmFsbGJhY2s6ICdlbicsXG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIFBsYWNlaG9sZGVyIHByZWZpeC5cbiAgICAgICAgICpcbiAgICAgICAgICogQHR5cGUge1N0cmluZ31cbiAgICAgICAgICogQGFwaSBwdWJsaWNcbiAgICAgICAgICovXG4gICAgICAgIHBsYWNlSG9sZGVyUHJlZml4OiAnJScsXG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIFBsYWNlaG9sZGVyIHN1ZmZpeC5cbiAgICAgICAgICpcbiAgICAgICAgICogQHR5cGUge1N0cmluZ31cbiAgICAgICAgICogQGFwaSBwdWJsaWNcbiAgICAgICAgICovXG4gICAgICAgIHBsYWNlSG9sZGVyU3VmZml4OiAnJScsXG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIERlZmF1bHQgZG9tYWluLlxuICAgICAgICAgKlxuICAgICAgICAgKiBAdHlwZSB7U3RyaW5nfVxuICAgICAgICAgKiBAYXBpIHB1YmxpY1xuICAgICAgICAgKi9cbiAgICAgICAgZGVmYXVsdERvbWFpbjogJ21lc3NhZ2VzJyxcblxuICAgICAgICAvKipcbiAgICAgICAgICogUGx1cmFyIHNlcGFyYXRvci5cbiAgICAgICAgICpcbiAgICAgICAgICogQHR5cGUge1N0cmluZ31cbiAgICAgICAgICogQGFwaSBwdWJsaWNcbiAgICAgICAgICovXG4gICAgICAgIHBsdXJhbFNlcGFyYXRvcjogJ3wnLFxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBBZGRzIGEgdHJhbnNsYXRpb24gZW50cnkuXG4gICAgICAgICAqXG4gICAgICAgICAqIEBwYXJhbSB7U3RyaW5nfSBpZCAgICAgICBUaGUgbWVzc2FnZSBpZFxuICAgICAgICAgKiBAcGFyYW0ge1N0cmluZ30gbWVzc2FnZSAgVGhlIG1lc3NhZ2UgdG8gcmVnaXN0ZXIgZm9yIHRoZSBnaXZlbiBpZFxuICAgICAgICAgKiBAcGFyYW0ge1N0cmluZ30gZG9tYWluICAgVGhlIGRvbWFpbiBmb3IgdGhlIG1lc3NhZ2Ugb3IgbnVsbCB0byB1c2UgdGhlIGRlZmF1bHRcbiAgICAgICAgICogQHBhcmFtIHtTdHJpbmd9IGxvY2FsZSAgIFRoZSBsb2NhbGUgb3IgbnVsbCB0byB1c2UgdGhlIGRlZmF1bHRcbiAgICAgICAgICogQHJldHVybiB7T2JqZWN0fSAgICAgICAgIFRyYW5zbGF0b3JcbiAgICAgICAgICogQGFwaSBwdWJsaWNcbiAgICAgICAgICovXG4gICAgICAgIGFkZDogZnVuY3Rpb24oaWQsIG1lc3NhZ2UsIGRvbWFpbiwgbG9jYWxlKSB7XG4gICAgICAgICAgICB2YXIgX2xvY2FsZSA9IGxvY2FsZSB8fCB0aGlzLmxvY2FsZSB8fCB0aGlzLmZhbGxiYWNrLFxuICAgICAgICAgICAgICAgIF9kb21haW4gPSBkb21haW4gfHwgdGhpcy5kZWZhdWx0RG9tYWluO1xuXG4gICAgICAgICAgICBpZiAoIV9tZXNzYWdlc1tfbG9jYWxlXSkge1xuICAgICAgICAgICAgICAgIF9tZXNzYWdlc1tfbG9jYWxlXSA9IHt9O1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBpZiAoIV9tZXNzYWdlc1tfbG9jYWxlXVtfZG9tYWluXSkge1xuICAgICAgICAgICAgICAgIF9tZXNzYWdlc1tfbG9jYWxlXVtfZG9tYWluXSA9IHt9O1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBfbWVzc2FnZXNbX2xvY2FsZV1bX2RvbWFpbl1baWRdID0gbWVzc2FnZTtcblxuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG5cblxuICAgICAgICAvKipcbiAgICAgICAgICogVHJhbnNsYXRlcyB0aGUgZ2l2ZW4gbWVzc2FnZS5cbiAgICAgICAgICpcbiAgICAgICAgICogQHBhcmFtIHtTdHJpbmd9IGlkICAgICAgICAgICAgIFRoZSBtZXNzYWdlIGlkXG4gICAgICAgICAqIEBwYXJhbSB7T2JqZWN0fSBwYXJhbWV0ZXJzICAgICBBbiBhcnJheSBvZiBwYXJhbWV0ZXJzIGZvciB0aGUgbWVzc2FnZVxuICAgICAgICAgKiBAcGFyYW0ge1N0cmluZ30gZG9tYWluICAgICAgICAgVGhlIGRvbWFpbiBmb3IgdGhlIG1lc3NhZ2Ugb3IgbnVsbCB0byBndWVzcyBpdFxuICAgICAgICAgKiBAcGFyYW0ge1N0cmluZ30gbG9jYWxlICAgICAgICAgVGhlIGxvY2FsZSBvciBudWxsIHRvIHVzZSB0aGUgZGVmYXVsdFxuICAgICAgICAgKiBAcmV0dXJuIHtTdHJpbmd9ICAgICAgICAgICAgICAgVGhlIHRyYW5zbGF0ZWQgc3RyaW5nXG4gICAgICAgICAqIEBhcGkgcHVibGljXG4gICAgICAgICAqL1xuICAgICAgICB0cmFuczogZnVuY3Rpb24gKGlkLCBwYXJhbWV0ZXJzLCBkb21haW4sIGxvY2FsZSkge1xuICAgICAgICAgICAgdmFyIF9tZXNzYWdlID0gZ2V0X21lc3NhZ2UoXG4gICAgICAgICAgICAgICAgaWQsXG4gICAgICAgICAgICAgICAgZG9tYWluLFxuICAgICAgICAgICAgICAgIGxvY2FsZSxcbiAgICAgICAgICAgICAgICB0aGlzLmxvY2FsZSxcbiAgICAgICAgICAgICAgICB0aGlzLmZhbGxiYWNrLFxuICAgICAgICAgICAgICAgIHRoaXMuZGVmYXVsdERvbWFpblxuICAgICAgICAgICAgKTtcblxuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYWRkTWFyayhpZCwgZG9tYWluLCByZXBsYWNlX3BsYWNlaG9sZGVycyhfbWVzc2FnZSwgcGFyYW1ldGVycyB8fCB7fSkpO1xuICAgICAgICB9LFxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBUcmFuc2xhdGVzIHRoZSBnaXZlbiBjaG9pY2UgbWVzc2FnZSBieSBjaG9vc2luZyBhIHRyYW5zbGF0aW9uIGFjY29yZGluZyB0byBhIG51bWJlci5cbiAgICAgICAgICpcbiAgICAgICAgICogQHBhcmFtIHtTdHJpbmd9IGlkICAgICAgICAgICAgIFRoZSBtZXNzYWdlIGlkXG4gICAgICAgICAqIEBwYXJhbSB7TnVtYmVyfSBudW1iZXIgICAgICAgICBUaGUgbnVtYmVyIHRvIHVzZSB0byBmaW5kIHRoZSBpbmRpY2Ugb2YgdGhlIG1lc3NhZ2VcbiAgICAgICAgICogQHBhcmFtIHtPYmplY3R9IHBhcmFtZXRlcnMgICAgIEFuIGFycmF5IG9mIHBhcmFtZXRlcnMgZm9yIHRoZSBtZXNzYWdlXG4gICAgICAgICAqIEBwYXJhbSB7U3RyaW5nfSBkb21haW4gICAgICAgICBUaGUgZG9tYWluIGZvciB0aGUgbWVzc2FnZSBvciBudWxsIHRvIGd1ZXNzIGl0XG4gICAgICAgICAqIEBwYXJhbSB7U3RyaW5nfSBsb2NhbGUgICAgICAgICBUaGUgbG9jYWxlIG9yIG51bGwgdG8gdXNlIHRoZSBkZWZhdWx0XG4gICAgICAgICAqIEByZXR1cm4ge1N0cmluZ30gICAgICAgICAgICAgICBUaGUgdHJhbnNsYXRlZCBzdHJpbmdcbiAgICAgICAgICogQGFwaSBwdWJsaWNcbiAgICAgICAgICovXG4gICAgICAgIHRyYW5zQ2hvaWNlOiBmdW5jdGlvbihpZCwgbnVtYmVyLCBwYXJhbWV0ZXJzLCBkb21haW4sIGxvY2FsZSkge1xuICAgICAgICAgICAgdmFyIF9tZXNzYWdlID0gZ2V0X21lc3NhZ2UoXG4gICAgICAgICAgICAgICAgaWQsXG4gICAgICAgICAgICAgICAgZG9tYWluLFxuICAgICAgICAgICAgICAgIGxvY2FsZSxcbiAgICAgICAgICAgICAgICB0aGlzLmxvY2FsZSxcbiAgICAgICAgICAgICAgICB0aGlzLmZhbGxiYWNrLFxuICAgICAgICAgICAgICAgIHRoaXMuZGVmYXVsdERvbWFpblxuICAgICAgICAgICAgKTtcblxuICAgICAgICAgICAgdmFyIF9udW1iZXIgID0gcGFyc2VJbnQobnVtYmVyLCAxMCk7XG5cbiAgICAgICAgICAgIGlmICh1bmRlZmluZWQgIT09IF9tZXNzYWdlICYmICFpc05hTihfbnVtYmVyKSkge1xuICAgICAgICAgICAgICAgIF9tZXNzYWdlID0gcGx1cmFsaXplKFxuICAgICAgICAgICAgICAgICAgICBfbWVzc2FnZSxcbiAgICAgICAgICAgICAgICAgICAgX251bWJlcixcbiAgICAgICAgICAgICAgICAgICAgbG9jYWxlIHx8IHRoaXMubG9jYWxlIHx8IHRoaXMuZmFsbGJhY2tcbiAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hZGRNYXJrKGlkLCBkb21haW4sIHJlcGxhY2VfcGxhY2Vob2xkZXJzKF9tZXNzYWdlLCBwYXJhbWV0ZXJzIHx8IHt9KSk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgYWRkTWFyazogZnVuY3Rpb24oaWQsIGRvbWFpbiwgbWVzcykge1xuICAgICAgICAgICAgaWYgKGlzVHJhbnNoZWxwZXIpIHtcbiAgICAgICAgICAgICAgICB2YXIgbWFyayA9IGJhc2U2NGVuY29kZShKU09OLnN0cmluZ2lmeSh7XG4gICAgICAgICAgICAgICAgICAgIGlkOiBpZCxcbiAgICAgICAgICAgICAgICAgICAgZG9tYWluOiBkb21haW4gfHwgJ21lc3NhZ2VzJyxcbiAgICAgICAgICAgICAgICAgICAgbWVzc2FnZTogbWVzc1xuICAgICAgICAgICAgICAgIH0pKTtcblxuICAgICAgICAgICAgICAgIHJldHVybiAnPG1hcmsgZGF0YS10aXRsZT1cIicgKyBtYXJrICsgJ1wiPicgKyBtZXNzICsgJzwvbWFyaz4nO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gbWVzcztcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogTG9hZHMgdHJhbnNsYXRpb25zIGZyb20gSlNPTi5cbiAgICAgICAgICpcbiAgICAgICAgICogQHBhcmFtIHtTdHJpbmd9IGRhdGEgICAgIEEgSlNPTiBzdHJpbmcgb3Igb2JqZWN0IGxpdGVyYWxcbiAgICAgICAgICogQHJldHVybiB7T2JqZWN0fSAgICAgICAgIFRyYW5zbGF0b3JcbiAgICAgICAgICogQGFwaSBwdWJsaWNcbiAgICAgICAgICovXG4gICAgICAgIGZyb21KU09OOiBmdW5jdGlvbihkYXRhKSB7XG4gICAgICAgICAgICBpZih0eXBlb2YgZGF0YSA9PT0gJ3N0cmluZycpIHtcbiAgICAgICAgICAgICAgICBkYXRhID0gSlNPTi5wYXJzZShkYXRhKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgaWYgKGRhdGEubG9jYWxlKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5sb2NhbGUgPSBkYXRhLmxvY2FsZTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgaWYgKGRhdGEuZmFsbGJhY2spIHtcbiAgICAgICAgICAgICAgICB0aGlzLmZhbGxiYWNrID0gZGF0YS5mYWxsYmFjaztcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgaWYgKGRhdGEuZGVmYXVsdERvbWFpbikge1xuICAgICAgICAgICAgICAgIHRoaXMuZGVmYXVsdERvbWFpbiA9IGRhdGEuZGVmYXVsdERvbWFpbjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgaWYgKGRhdGEudHJhbnNsYXRpb25zKSB7XG4gICAgICAgICAgICAgICAgZm9yICh2YXIgbG9jYWxlIGluIGRhdGEudHJhbnNsYXRpb25zKSB7XG4gICAgICAgICAgICAgICAgICAgIGZvciAodmFyIGRvbWFpbiBpbiBkYXRhLnRyYW5zbGF0aW9uc1tsb2NhbGVdKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBmb3IgKHZhciBpZCBpbiBkYXRhLnRyYW5zbGF0aW9uc1tsb2NhbGVdW2RvbWFpbl0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aGlzLmFkZChpZCwgZGF0YS50cmFuc2xhdGlvbnNbbG9jYWxlXVtkb21haW5dW2lkXSwgZG9tYWluLCBsb2NhbGUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogQGFwaSBwdWJsaWNcbiAgICAgICAgICovXG4gICAgICAgIHJlc2V0OiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgIF9tZXNzYWdlcyAgID0ge307XG4gICAgICAgICAgICB0aGlzLmxvY2FsZSA9IGdldF9jdXJyZW50X2xvY2FsZSgpO1xuICAgICAgICB9XG4gICAgfTtcbn0pKGRvY3VtZW50KTtcblxuaWYgKHR5cGVvZiB3aW5kb3cuZGVmaW5lID09PSAnZnVuY3Rpb24nICYmIHdpbmRvdy5kZWZpbmUuYW1kKSB7XG4gICAgZGVmaW5lKCdUcmFuc2xhdG9yJywgW10sIGZ1bmN0aW9uKCkge1xuICAgICAgICByZXR1cm4gVHJhbnNsYXRvcjtcbiAgICB9KTtcbn1cblxuaWYgKHR5cGVvZiBtb2R1bGUgIT09ICd1bmRlZmluZWQnICYmIG1vZHVsZS5leHBvcnRzKSB7XG4gICAgbW9kdWxlLmV4cG9ydHMgPSBUcmFuc2xhdG9yO1xufSIsIihmdW5jdGlvbiAodCkge1xudC5mYWxsYmFjayA9ICdlbic7XG50LmRlZmF1bHREb21haW4gPSAnbWVzc2FnZXMnO1xufSkoVHJhbnNsYXRvcik7XG4iLCJpbXBvcnQgVHJhbnNsYXRvciBmcm9tICcuLi8uLi8uLi8uLi93ZWIvYXNzZXRzL2NvbW1vbi9qcy90cmFuc2xhdG9yJztcbi8vIGdsb2JhbCB2YXJpYWJsZSBmb3IgbGVnYWN5IGNvZGUgb25seVxuY29uc3QgU2VydmljZSA9IFRyYW5zbGF0b3I7XG53aW5kb3cuVHJhbnNsYXRvciA9IFNlcnZpY2U7XG5yZXF1aXJlKCcuLi8uLi8uLi8uLi93ZWIvYXNzZXRzL3RyYW5zbGF0aW9ucy9jb25maWcnKTtcbmV4cG9ydCBkZWZhdWx0IFNlcnZpY2U7XG4iXSwibmFtZXMiOlsiVHJhbnNsYXRvciIsImRvY3VtZW50IiwiYmFzZTY0ZW5jb2RlIiwiYmFzZTY0X2VuY29kZSIsImRhdGEiLCJiNjQiLCJvMSIsIm8yIiwibzMiLCJoMSIsImgyIiwiaDMiLCJoNCIsImJpdHMiLCJpIiwiZW5jIiwiY2hhckNvZGVBdCIsImNoYXJBdCIsImxlbmd0aCIsInNsaWNlIiwiaXNUcmFuc2hlbHBlciIsImNvb2tpZSIsIm1hdGNoIiwiX21lc3NhZ2VzIiwiX3NQbHVyYWxSZWdleCIsIl9jUGx1cmFsUmVnZXgiLCJfaVBsdXJhbFJlZ2V4IiwicmVwbGFjZV9wbGFjZWhvbGRlcnMiLCJtZXNzYWdlIiwicGxhY2Vob2xkZXJzIiwiX2kiLCJfcHJlZml4IiwicGxhY2VIb2xkZXJQcmVmaXgiLCJfc3VmZml4IiwicGxhY2VIb2xkZXJTdWZmaXgiLCJfciIsIlJlZ0V4cCIsInRlc3QiLCJyZXBsYWNlIiwiZ2V0X21lc3NhZ2UiLCJpZCIsImRvbWFpbiIsImxvY2FsZSIsImN1cnJlbnRMb2NhbGUiLCJsb2NhbGVGYWxsYmFjayIsImRlZmF1bHREb21haW4iLCJfbG9jYWxlIiwiX2RvbWFpbiIsInVuZGVmaW5lZCIsInBsdXJhbGl6ZSIsIm51bWJlciIsIl9wIiwiX2UiLCJfZXhwbGljaXRSdWxlcyIsIl9zdGFuZGFyZFJ1bGVzIiwiX3BhcnRzIiwic3BsaXQiLCJwbHVyYWxTZXBhcmF0b3IiLCJfbWF0Y2hlcyIsIl9wYXJ0IiwiX3JjIiwiX3JzIiwicHVzaCIsIl9ucyIsIl9uIiwiX2xlZnROdW1iZXIiLCJjb252ZXJ0X251bWJlciIsIl9yaWdodE51bWJlciIsInBsdXJhbF9wb3NpdGlvbiIsIk1hdGgiLCJsb2ciLCJwYXJzZUludCIsImdldF9jdXJyZW50X2xvY2FsZSIsImRvY3VtZW50RWxlbWVudCIsImxhbmciLCJmYWxsYmFjayIsImFkZCIsInRyYW5zIiwicGFyYW1ldGVycyIsIl9tZXNzYWdlIiwiYWRkTWFyayIsInRyYW5zQ2hvaWNlIiwiX251bWJlciIsImlzTmFOIiwibWVzcyIsIm1hcmsiLCJKU09OIiwic3RyaW5naWZ5IiwiZnJvbUpTT04iLCJwYXJzZSIsInRyYW5zbGF0aW9ucyIsInJlc2V0Iiwid2luZG93IiwiZGVmaW5lIiwiYW1kIiwibW9kdWxlIiwiZXhwb3J0cyIsInQiLCJTZXJ2aWNlIiwicmVxdWlyZSJdLCJzb3VyY2VSb290IjoiIn0=