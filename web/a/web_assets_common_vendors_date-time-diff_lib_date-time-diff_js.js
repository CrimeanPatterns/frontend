(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["web_assets_common_vendors_date-time-diff_lib_date-time-diff_js"],{

/***/ "./web/assets/common/vendors/date-time-diff/lib/date-time-diff.js":
/*!************************************************************************!*\
  !*** ./web/assets/common/vendors/date-time-diff/lib/date-time-diff.js ***!
  \************************************************************************/
/***/ (function(module) {

(function webpackUniversalModuleDefinition(root, factory) {
  if (true) module.exports = factory();else {}
})(typeof self !== 'undefined' ? self : this, function () {
  return (/******/function (modules) {
      // webpackBootstrap
      /******/ // The module cache
      /******/
      var installedModules = {};
      /******/
      /******/ // The require function
      /******/
      function __nested_webpack_require_643__(moduleId) {
        /******/
        /******/ // Check if module is in cache
        /******/if (installedModules[moduleId]) {
          /******/return installedModules[moduleId].exports;
          /******/
        }
        /******/ // Create a new module (and put it into the cache)
        /******/
        var module = installedModules[moduleId] = {
          /******/i: moduleId,
          /******/l: false,
          /******/exports: {}
          /******/
        };
        /******/
        /******/ // Execute the module function
        /******/
        modules[moduleId].call(module.exports, module, module.exports, __nested_webpack_require_643__);
        /******/
        /******/ // Flag the module as loaded
        /******/
        module.l = true;
        /******/
        /******/ // Return the exports of the module
        /******/
        return module.exports;
        /******/
      }
      /******/
      /******/
      /******/ // expose the modules object (__webpack_modules__)
      /******/
      __nested_webpack_require_643__.m = modules;
      /******/
      /******/ // expose the module cache
      /******/
      __nested_webpack_require_643__.c = installedModules;
      /******/
      /******/ // define getter function for harmony exports
      /******/
      __nested_webpack_require_643__.d = function (exports, name, getter) {
        /******/if (!__nested_webpack_require_643__.o(exports, name)) {
          /******/Object.defineProperty(exports, name, {
            enumerable: true,
            get: getter
          });
          /******/
        }
        /******/
      };
      /******/
      /******/ // define __esModule on exports
      /******/
      __nested_webpack_require_643__.r = function (exports) {
        /******/if (typeof Symbol !== 'undefined' && Symbol.toStringTag) {
          /******/Object.defineProperty(exports, Symbol.toStringTag, {
            value: 'Module'
          });
          /******/
        }
        /******/
        Object.defineProperty(exports, '__esModule', {
          value: true
        });
        /******/
      };
      /******/
      /******/ // create a fake namespace object
      /******/ // mode & 1: value is a module id, require it
      /******/ // mode & 2: merge all properties of value into the ns
      /******/ // mode & 4: return value when already ns object
      /******/ // mode & 8|1: behave like require
      /******/
      __nested_webpack_require_643__.t = function (value, mode) {
        /******/if (mode & 1) value = __nested_webpack_require_643__(value);
        /******/
        if (mode & 8) return value;
        /******/
        if (mode & 4 && typeof value === 'object' && value && value.__esModule) return value;
        /******/
        var ns = Object.create(null);
        /******/
        __nested_webpack_require_643__.r(ns);
        /******/
        Object.defineProperty(ns, 'default', {
          enumerable: true,
          value: value
        });
        /******/
        if (mode & 2 && typeof value != 'string') for (var key in value) __nested_webpack_require_643__.d(ns, key, function (key) {
          return value[key];
        }.bind(null, key));
        /******/
        return ns;
        /******/
      };
      /******/
      /******/ // getDefaultExport function for compatibility with non-harmony modules
      /******/
      __nested_webpack_require_643__.n = function (module) {
        /******/var getter = module && module.__esModule ? /******/function getDefault() {
          return module['default'];
        } : /******/function getModuleExports() {
          return module;
        };
        /******/
        __nested_webpack_require_643__.d(getter, 'a', getter);
        /******/
        return getter;
        /******/
      };
      /******/
      /******/ // Object.prototype.hasOwnProperty.call
      /******/
      __nested_webpack_require_643__.o = function (object, property) {
        return Object.prototype.hasOwnProperty.call(object, property);
      };
      /******/
      /******/ // __webpack_public_path__
      /******/
      __nested_webpack_require_643__.p = "";
      /******/
      /******/
      /******/ // Load entry module and return exports
      /******/
      return __nested_webpack_require_643__(__nested_webpack_require_643__.s = "./src/index.js");
      /******/
    }
    /************************************************************************/
    /******/({
      /***/"./src/index.js":
      /*!**********************!*\
        !*** ./src/index.js ***!
        \**********************/
      /*! exports provided: default */
      /***/
      function (module, __nested_webpack_exports__, __nested_webpack_require_5175__) {
        "use strict";

        __nested_webpack_require_5175__.r(__nested_webpack_exports__);
        /* harmony export (binding) */
        __nested_webpack_require_5175__.d(__nested_webpack_exports__, "default", function () {
          return DateTimeDiff;
        });
        function ownKeys(object, enumerableOnly) {
          var keys = Object.keys(object);
          if (Object.getOwnPropertySymbols) {
            var symbols = Object.getOwnPropertySymbols(object);
            if (enumerableOnly) symbols = symbols.filter(function (sym) {
              return Object.getOwnPropertyDescriptor(object, sym).enumerable;
            });
            keys.push.apply(keys, symbols);
          }
          return keys;
        }
        function _objectSpread(target) {
          for (var i = 1; i < arguments.length; i++) {
            var source = arguments[i] != null ? arguments[i] : {};
            if (i % 2) {
              ownKeys(Object(source), true).forEach(function (key) {
                _defineProperty(target, key, source[key]);
              });
            } else if (Object.getOwnPropertyDescriptors) {
              Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
            } else {
              ownKeys(Object(source)).forEach(function (key) {
                Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
              });
            }
          }
          return target;
        }
        function _defineProperty(obj, key, value) {
          if (key in obj) {
            Object.defineProperty(obj, key, {
              value: value,
              enumerable: true,
              configurable: true,
              writable: true
            });
          } else {
            obj[key] = value;
          }
          return obj;
        }
        function _slicedToArray(arr, i) {
          return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest();
        }
        function _nonIterableRest() {
          throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
        }
        function _unsupportedIterableToArray(o, minLen) {
          if (!o) return;
          if (typeof o === "string") return _arrayLikeToArray(o, minLen);
          var n = Object.prototype.toString.call(o).slice(8, -1);
          if (n === "Object" && o.constructor) n = o.constructor.name;
          if (n === "Map" || n === "Set") return Array.from(n);
          if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
        }
        function _arrayLikeToArray(arr, len) {
          if (len == null || len > arr.length) len = arr.length;
          for (var i = 0, arr2 = new Array(len); i < len; i++) {
            arr2[i] = arr[i];
          }
          return arr2;
        }
        function _iterableToArrayLimit(arr, i) {
          if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return;
          var _arr = [];
          var _n = true;
          var _d = false;
          var _e = undefined;
          try {
            for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
              _arr.push(_s.value);
              if (i && _arr.length === i) break;
            }
          } catch (err) {
            _d = true;
            _e = err;
          } finally {
            try {
              if (!_n && _i["return"] != null) _i["return"]();
            } finally {
              if (_d) throw _e;
            }
          }
          return _arr;
        }
        function _arrayWithHoles(arr) {
          if (Array.isArray(arr)) return arr;
        }
        function _classCallCheck(instance, Constructor) {
          if (!(instance instanceof Constructor)) {
            throw new TypeError("Cannot call a class as a function");
          }
        }
        function _defineProperties(target, props) {
          for (var i = 0; i < props.length; i++) {
            var descriptor = props[i];
            descriptor.enumerable = descriptor.enumerable || false;
            descriptor.configurable = true;
            if ("value" in descriptor) descriptor.writable = true;
            Object.defineProperty(target, descriptor.key, descriptor);
          }
        }
        function _createClass(Constructor, protoProps, staticProps) {
          if (protoProps) _defineProperties(Constructor.prototype, protoProps);
          if (staticProps) _defineProperties(Constructor, staticProps);
          return Constructor;
        }

        /**
         * Translator Interface
         *
         * @interface Translator
         */

        /**
         * Translates the given message.
         *
         * @function
         * @name Translator#trans
         * @param {String} id             The message id
         * @param {Object=} parameters    An array of parameters for the message
         * @param {String=} domain        The domain for the message or null to guess it
         * @param {String=} locale        The locale or null to use the default
         * @return {String}               The translated string
         */

        /**
         * Translates the given choice message by choosing a translation according to a number.
         *
         * @function
         * @name Translator#transChoice
         * @param {String} id             The message id
         * @param {Number} number         The number to use to find the indice of the message
         * @param {Object=} parameters    An array of parameters for the message
         * @param {String=} domain        The domain for the message or null to guess it
         * @param {String=} locale        The locale or null to use the default
         * @return {String}               The translated string
         */

        /**
         * @typedef {Object} ParsedDate
         * @property {number} y
         * @property {number} m
         * @property {number} d
         * @property {number} h
         * @property {number} i
         * @property {number} s
         */

        /**
         * @typedef {Object} DateTimeDiff
         * @property {number} y
         * @property {number} m
         * @property {number} d
         * @property {number} h
         * @property {number} i
         * @property {number} s
         * @property {boolean} invert
         * @property {number} days
         */

        /**
         * @typedef {Object} TimeRemaining
         * @property {number} total
         * @property {number} days
         * @property {number} hours
         * @property {number} minutes
         * @property {number} seconds
         */

        /**
         * @param {number} number
         * @returns {string}
         */
        function pad(number) {
          if (number < 10) {
            return '0' + number;
          }
          return number.toString();
        }
        /**
         * @param {Date} date
         * @param {boolean=} [withTime=false]
         * @returns {string}
         */

        function formatDate(date) {
          var withTime = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
          var formatted = "".concat(date.getFullYear(), " ").concat(pad(date.getMonth() + 1), " ").concat(pad(date.getDate()));
          if (withTime) {
            formatted = "".concat(formatted, " ").concat(pad(date.getHours()), " ").concat(pad(date.getMinutes()), " ").concat(pad(date.getSeconds()));
          }
          return formatted;
        }
        /**
         * @param {Date} date
         * @returns {ParsedDate}
         */

        function parseDate(date) {
          var parts = formatDate(date, true).split(' ');
          var keys = ['y', 'm', 'd', 'h', 'i', 's'];
          var newArray = {};
          var i;
          for (i = 0; i < keys.length; i++) {
            newArray[keys[i]] = parseInt(parts[i], 10);
          }
          return newArray;
        }
        /**
         * @param {ParsedDate} base
         * @param {DateTimeDiff} r
         * @returns {DateTimeDiff}
         */

        function dateNormalize(base, r) {
          dateRangeLimit(0, 60, 60, 's', 'i', r);
          dateRangeLimit(0, 60, 60, 'i', 'h', r);
          dateRangeLimit(0, 24, 24, 'h', 'd', r);
          dateRangeLimit(0, 12, 12, 'm', 'y', r);
          dateRangeLimitDays(base, r);
          dateRangeLimit(0, 12, 12, 'm', 'y', r);
          return r;
        }
        /**
         * @param {number} start
         * @param {number} end
         * @param {number} adj
         * @param {string} a - ParsedDate props
         * @param {string} b - ParsedDate props
         * @param {DateTimeDiff|ParsedDate} result
         */

        function dateRangeLimit(start, end, adj, a, b, result) {
          if (result[a] < start) {
            result[b] -= parseInt((start - result[a] - 1) / adj, 10) + 1;
            result[a] += adj * parseInt((start - result[a] - 1) / adj + 1, 10);
          }
          if (result[a] >= end) {
            result[b] += parseInt(result[a] / adj, 10);
            result[a] -= adj * parseInt(result[a] / adj, 10);
          }
        }
        /**
         * @param {ParsedDate} base
         * @param {DateTimeDiff} result
         */

        function dateRangeLimitDays(base, result) {
          var daysInMonthLeap = [31, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
          var daysInMonth = [31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
          dateRangeLimit(1, 13, 12, 'm', 'y', base);
          var year = base.y;
          var month = base.m;
          var days;
          var leapyear;
          if (!result.invert) {
            while (result.d < 0) {
              month--;
              if (month < 1) {
                month += 12;
                year--;
              }
              leapyear = year % 400 === 0 || year % 100 !== 0 && year % 4 === 0;
              days = leapyear ? daysInMonthLeap[month] : daysInMonth[month];
              result.d += days;
              result.m--;
            }
          } else {
            while (result.d < 0) {
              leapyear = year % 400 === 0 || year % 100 !== 0 && year % 4 === 0;
              days = leapyear ? daysInMonthLeap[month] : daysInMonth[month];
              result.d += days;
              result.m--;
              month++;
              if (month > 12) {
                month -= 12;
                year++;
              }
            }
          }
        }
        /**
         * @param {Date} date1
         * @param {Date} date2
         * @param {boolean=} [onlySeconds=false]
         * @returns {Date[]}
         */

        function resetTime(date1, date2, onlySeconds) {
          if (onlySeconds) {
            return [new Date(date1.getFullYear(), date1.getMonth(), date1.getDate(), date1.getHours(), date1.getMinutes(), 0, 0), new Date(date2.getFullYear(), date2.getMonth(), date2.getDate(), date2.getHours(), date2.getMinutes(), 0, 0)];
          }
          return [new Date(date1.getFullYear(), date1.getMonth(), date1.getDate(), 0, 0, 0, 0), new Date(date2.getFullYear(), date2.getMonth(), date2.getDate(), 0, 0, 0, 0)];
        }
        /**
         * @param {number} month
         * @param {number} year
         * @returns {number}
         */

        function daysInMonth(month, year) {
          return new Date(year, month + 1, 0).getDate();
        }
        /**
         * @param {Date} date
         * @returns {number}
         */

        function daysInYear(date) {
          var year = date.getFullYear();
          var leapyear = year % 400 === 0 || year % 100 !== 0 && year % 4 === 0;
          return leapyear ? 366 : 365;
        }
        /**
         * @param {Date} date
         * @param {number} ts2
         * @returns {number}
         */

        function getSeconds(date, ts2) {
          return Math.abs(date.getTime() - ts2) / 1000;
        }
        /**
         * @param {number} num
         * @returns {number}
         */

        function round(num) {
          return +(Math.round(num + "e+1") + "e-1");
        }
        function objectValues(obj) {
          var vals = [];
          for (var prop in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, prop)) {
              vals.push(obj[prop]);
            }
          }
          return vals;
        }
        var DateTimeDiff = /*#__PURE__*/function () {
          /**
           * @type Translator
           */

          /**
           * @type Function
           */

          /**
           * @param {Translator} translator
           * @param {Function} numberFormatter
           */
          function DateTimeDiff(translator, numberFormatter) {
            _classCallCheck(this, DateTimeDiff);
            this.translator = translator;
            this.numberFormatter = numberFormatter;
          }
          /**
           * @param {Date} fromDateTime
           * @param {Date} toDateTime
           * @param {boolean=} [onlyDate=false] true - time is set to 00:00:00
           * @param {boolean=} [shortFormat=false] true - fraction in years and months, only 1 unit (e.g. 1.7 years or 4.8 months or 10 hours)
           * @param {boolean=} [fromToday=false] true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
           * @param {(string|null)=} [locale=null]
           * @returns {string}
           */

          _createClass(DateTimeDiff, [{
            key: "formatDuration",
            value: function formatDuration(fromDateTime, toDateTime) {
              var onlyDate = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
              var shortFormat = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
              var fromToday = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : false;
              var locale = arguments.length > 5 && arguments[5] !== undefined ? arguments[5] : null;
              return this._format(fromDateTime, toDateTime, onlyDate, shortFormat, false, fromToday, true, locale);
            }
            /**
             * Uses the date difference to display the absolute value in hours and minutes
             *
             * @param {Date} fromDateTime
             * @param {Date} toDateTime
             * @param {(string|null)=} [locale=null]
             * @returns {string}
             */
          }, {
            key: "formatDurationInHours",
            value: function formatDurationInHours(fromDateTime, toDateTime, locale) {
              var seconds = Math.abs(fromDateTime.getTime() - toDateTime.getTime()) / 1000;
              var hours = Math.floor(seconds / (60 * 60));
              var minutes = Math.floor(seconds / 60 % 60);
              var units = [];
              if (hours > 0) {
                units.push(this.translator.transChoice('hours.short', hours, {
                  count: this.numberFormatter(hours)
                }, null, locale));
              }
              if (minutes > 0) {
                units.push(this.translator.transChoice('minutes.short', minutes, {
                  count: this.numberFormatter(minutes)
                }, null, locale));
              }
              if (units.length === 0) {
                return this.translator.trans('seconds', {}, null, locale);
              }
              return units.join(' ');
            }
            /**
             * @param {Date} fromDateTime
             * @param {Date} toDateTime
             * @param {boolean=} [suffix=true] true - wrap "in %text%" or "%text% ago"
             * @param {boolean=} [fromToday=true] true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
             * @param {(string|null)=} [locale=null]
             * @returns {string}
             */
          }, {
            key: "shortFormatViaDates",
            value: function shortFormatViaDates(fromDateTime, toDateTime) {
              var suffix = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
              var fromToday = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
              var locale = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;
              return this._format(fromDateTime, toDateTime, true, true, suffix, fromToday, false, locale);
            }
            /**
             * @param {Date} fromDateTime
             * @param {Date} toDateTime
             * @param {boolean=} [suffix=true] true - wrap "in %text%" or "%text% ago"
             * @param {boolean=} [fromToday=true] true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
             * @param {(string|null)=} [locale=null]
             * @returns {string}
             */
          }, {
            key: "longFormatViaDates",
            value: function longFormatViaDates(fromDateTime, toDateTime) {
              var suffix = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
              var fromToday = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
              var locale = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;
              return this._format(fromDateTime, toDateTime, true, false, suffix, fromToday, false, locale);
            }
            /**
             * @param {Date} fromDateTime
             * @param {Date} toDateTime
             * @param {boolean=} [suffix=true] true - wrap "in %text%" or "%text% ago"
             * @param {boolean=} [fromToday=true] true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
             * @param {(string|null)=} [locale=null]
             * @returns {string}
             */
          }, {
            key: "shortFormatViaDateTimes",
            value: function shortFormatViaDateTimes(fromDateTime, toDateTime) {
              var suffix = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
              var fromToday = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
              var locale = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;
              return this._format(fromDateTime, toDateTime, false, true, suffix, fromToday, false, locale);
            }
            /**
             * @param {Date} fromDateTime
             * @param {Date} toDateTime
             * @param {boolean=} [suffix=true] true - wrap "in %text%" or "%text% ago"
             * @param {boolean=} [fromToday=true] true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
             * @param {(string|null)=} [locale=null]
             * @returns {string}
             */
          }, {
            key: "longFormatViaDateTimes",
            value: function longFormatViaDateTimes(fromDateTime, toDateTime) {
              var suffix = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
              var fromToday = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
              var locale = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;
              return this._format(fromDateTime, toDateTime, false, false, suffix, fromToday, false, locale);
            }
            /**
             * @param {Date} expectDate
             * @param {(Date|null)=} [currentDate=null]
             * @returns {TimeRemaining}
             */
          }, {
            key: "getTimeRemaining",
            value: function getTimeRemaining(expectDate) {
              var currentDate = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
              if (!(currentDate instanceof Date)) {
                currentDate = new Date();
              }
              var diff = expectDate.getTime() - currentDate.getTime();
              return {
                'total': diff,
                'days': Math.floor(diff / (1000 * 60 * 60 * 24)),
                'hours': Math.floor(diff / (1000 * 60 * 60) % 24),
                'minutes': Math.floor(diff / 1000 / 60 % 60),
                'seconds': Math.floor(diff / 1000 % 60)
              };
            }
            /**
             * @private
             * @param {Date} fromDateTime
             * @param {Date} toDateTime
             * @param {boolean} onlyDate
             * @param {boolean} shortFormat
             * @param {boolean} addSuffix
             * @param {boolean} fromToday
             * @param {boolean} duration
             * @param {(string|null)} locale
             */
          }, {
            key: "_format",
            value: function _format(fromDateTime, toDateTime, onlyDate, shortFormat, addSuffix, fromToday, duration, locale) {
              if (onlyDate || duration) {
                var _resetTime = resetTime(fromDateTime, toDateTime, !onlyDate && duration);
                var _resetTime2 = _slicedToArray(_resetTime, 2);
                fromDateTime = _resetTime2[0];
                toDateTime = _resetTime2[1];
              }
              var diff = this._getExtraDiffData(fromDateTime, toDateTime, shortFormat);
              var future = diff.invert === 0;
              var hasTime = diff.h > 0 || diff.i > 0 || diff.s > 0;
              var hasOnlySeconds = diff.h === 0 && diff.i === 0 && diff.s > 0;
              var showTime = diff.days < 2 && hasTime;
              var units = {};
              if (diff.y > 0) {
                units.y = "".concat(this.numberFormatter(diff.y), " ").concat(this.translator.transChoice( /** @Desc("year|years") */
                'years', Math.ceil(diff.y), {}, null, locale));
              }
              if (diff.m > 0) {
                units.m = "".concat(this.numberFormatter(diff.m), " ").concat(this.translator.transChoice( /** @Desc("month|months") */
                'months', Math.ceil(diff.m), {}, null, locale));
              }
              if (diff.d > 1 || diff.d === 1 && (Object.keys(units).length > 0 || showTime && !hasOnlySeconds || !fromToday)) {
                units.d = "".concat(this.numberFormatter(diff.d), " ").concat(this.translator.transChoice( /** @Desc("day|days") */
                'days', diff.d, {}, null, locale));
              } else if (diff.d === 1 && (!showTime || hasOnlySeconds) && fromToday) {
                addSuffix = false;
                if (future) {
                  units.d = this.translator.trans( /** @Desc("Tomorrow") */
                  'tomorrow', {}, null, locale).toLowerCase();
                } else {
                  units.d = this.translator.trans( /** @Desc("Yesterday") */
                  'yesterday', {}, null, locale).toLowerCase();
                }
              }
              if (showTime) {
                var shortyTime = diff.h > 0 && diff.i > 0 && !shortFormat;
                if (diff.h > 0) {
                  if (shortyTime) {
                    units.h = this.translator.transChoice('hours.short', diff.h, {
                      count: this.numberFormatter(diff.h)
                    }, null, locale);
                  } else {
                    units.h = this.translator.transChoice('hours', diff.h, {
                      count: this.numberFormatter(diff.h)
                    }, null, locale);
                  }
                }
                if (diff.i > 0) {
                  if (shortyTime) {
                    units.i = this.translator.transChoice('minutes.short', diff.i, {
                      count: this.numberFormatter(diff.i)
                    }, null, locale);
                  } else {
                    units.i = this.translator.transChoice('minutes', diff.i, {
                      count: this.numberFormatter(diff.i)
                    }, null, locale);
                  }
                }
                if (hasOnlySeconds && Object.keys(units).length === 0) {
                  units.s = this.translator.trans( /** @Desc("a few seconds") */
                  'seconds', {}, null, locale);
                }
              }
              if (Object.keys(units).length === 0) {
                if (fromToday && onlyDate) {
                  addSuffix = false;
                  units.s = this.translator.trans( /** @Desc("Today") */
                  'today', {}, null, locale).toLowerCase();
                } else {
                  units.s = this.translator.trans( /** @Desc("a few seconds") */
                  'seconds', {}, null, locale);
                }
              }
              var formatted;
              if (shortFormat) {
                formatted = objectValues(units).shift();
              } else {
                var u = [];
                var started = false;
                for (var _i2 = 0, _arr2 = ['y', 'm', 'd', 'h', 'i', 's']; _i2 < _arr2.length; _i2++) {
                  var i = _arr2[_i2];
                  if (Object.prototype.hasOwnProperty.call(units, i)) {
                    started = true;
                    u.push(units[i]);
                  } else if (started) {
                    break;
                  }
                }
                formatted = u.slice(0, 2).join(' ');
              }
              if (addSuffix) {
                return this._addSuffix(future, formatted, locale);
              }
              return formatted;
            }
            /**
             * @private
             * @param {Date} fromDateTime
             * @param {Date} toDateTime
             * @param {boolean} short
             * @returns {DateTimeDiff}
             */
          }, {
            key: "_getExtraDiffData",
            value: function _getExtraDiffData(fromDateTime, toDateTime, _short) {
              var invert = false;
              var ts1 = fromDateTime.getTime();
              var ts2 = toDateTime.getTime();
              if (ts1 >= ts2) {
                var _ref = [toDateTime, fromDateTime];
                fromDateTime = _ref[0];
                toDateTime = _ref[1];
                invert = true;
              }
              var a = parseDate(fromDateTime);
              var b = parseDate(toDateTime);
              var interval = {};
              interval.y = b.y - a.y;
              interval.m = b.m - a.m;
              interval.d = b.d - a.d;
              interval.h = b.h - a.h;
              interval.i = b.i - a.i;
              interval.s = b.s - a.s;
              interval.invert = invert ? 1 : 0;
              interval.days = parseInt(Math.abs((ts1 - ts2) / 1000 / 60 / 60 / 24), 10);
              if (invert) {
                interval = dateNormalize(a, interval);
              } else {
                interval = dateNormalize(b, interval);
              }
              var result = _objectSpread({}, interval);
              result.y = result.m = result.d = result.h = result.i = result.s = 0;
              var newDate = new Date(ts1);
              var fract; // years

              if (interval.y > 0) {
                newDate.setFullYear(invert ? newDate.getFullYear() - interval.y : newDate.getFullYear() + interval.y);
              }
              fract = getSeconds(newDate, ts2) / 60 / 60 / 24 / daysInYear(newDate);
              result.y = interval.y;
              if (fract > 0.9) {
                result.y += 1;
                return result;
              } else if (result.y > 0 && _short) {
                result.y += round(fract);
                return result;
              } // months

              if (interval.m > 0) {
                newDate.setMonth(invert ? newDate.getMonth() - interval.m : newDate.getMonth() + interval.m);
              }
              fract = getSeconds(newDate, ts2) / 60 / 60 / 24 / daysInMonth(newDate.getMonth(), newDate.getFullYear());
              result.m = interval.m;
              if (fract > 0.9) {
                result.m += 1;
                return result;
              } else if (result.m > 0 && _short) {
                result.m += round(fract);
                return result;
              } // days

              if (interval.d > 0) {
                newDate.setDate(invert ? newDate.getDate() - interval.d : newDate.getDate() + interval.d);
              }
              fract = getSeconds(newDate, ts2) / 60 / 60 / 24;
              result.d = interval.d;
              if (fract > 0.9) {
                result.d += 1;
                return result;
              } else if (result.d >= 2 || result.d > 0 && fract < 0.1) {
                return result;
              } // hours

              if (interval.h > 0) {
                newDate.setHours(invert ? newDate.getHours() - interval.h : newDate.getHours() + interval.h);
              }
              fract = getSeconds(newDate, ts2) / 60 / 60;
              result.h = interval.h;
              if (fract > 0.9) {
                result.h += 1;
                return result;
              } // minutes

              if (interval.i > 0) {
                newDate.setMinutes(invert ? newDate.getMinutes() - interval.i : newDate.getMinutes() + interval.i);
              }
              fract = getSeconds(newDate, ts2) / 60;
              result.i = interval.i;
              if (fract > 0.9) {
                result.i += 1;
                return result;
              } // seconds

              if (interval.s > 0) {
                result.s = interval.s;
              }
              return result;
            }
            /**
             * @private
             * @param {boolean} future
             * @param {string} text
             * @param {string|null} locale
             * @returns {string}
             */
          }, {
            key: "_addSuffix",
            value: function _addSuffix(future, text, locale) {
              if (future) {
                return this.translator.trans( /** @Desc("in %text%") */
                'relative_date.future', {
                  'text': text
                }, null, locale);
              }
              return this.translator.trans( /** @Desc("%text% ago") */
              'relative_date.past', {
                'text': text
              }, null, locale);
            }
          }]);
          return DateTimeDiff;
        }();

        /***/
      }

      /******/
    })
  );
});

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoid2ViX2Fzc2V0c19jb21tb25fdmVuZG9yc19kYXRlLXRpbWUtZGlmZl9saWJfZGF0ZS10aW1lLWRpZmZfanMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7QUFBQSxVQUFBQSxpQ0FBQUMsSUFBQSxFQUFBQyxPQUFBO0VBQ0EsVUFDQUUsTUFBQSxDQUFBRCxPQUFBLEdBQUFELE9BQUEsUUFDQSxFQUtBO0FBQ0EsQ0FBQyxTQUFBSyxJQUFBLG1CQUFBQSxJQUFBO0VBQ0Q7O2VDVkE7O01BQ0EsSUFBQUMsZ0JBQUE7O2VBRUE7O01BQ0EsU0FBQUMsOEJBQUFBLENBQUFDLFFBQUE7O2lCQUVBO2dCQUNBLElBQUFGLGdCQUFBLENBQUFFLFFBQUE7a0JBQ0EsT0FBQUYsZ0JBQUEsQ0FBQUUsUUFBQSxFQUFBUCxPQUFBOztRQUNBO2lCQUNBOztRQUNBLElBQUFDLE1BQUEsR0FBQUksZ0JBQUEsQ0FBQUUsUUFBQTtrQkFDQUMsQ0FBQSxFQUFBRCxRQUFBO2tCQUNBRSxDQUFBO2tCQUNBVCxPQUFBOztRQUNBOztpQkFFQTs7UUFDQVUsT0FBQSxDQUFBSCxRQUFBLEVBQUFJLElBQUEsQ0FBQVYsTUFBQSxDQUFBRCxPQUFBLEVBQUFDLE1BQUEsRUFBQUEsTUFBQSxDQUFBRCxPQUFBLEVBQUFNLDhCQUFBOztpQkFFQTs7UUFDQUwsTUFBQSxDQUFBUSxDQUFBOztpQkFFQTs7UUFDQSxPQUFBUixNQUFBLENBQUFELE9BQUE7O01BQ0E7OztlQUdBOztNQUNBTSw4QkFBQSxDQUFBTSxDQUFBLEdBQUFGLE9BQUE7O2VBRUE7O01BQ0FKLDhCQUFBLENBQUFPLENBQUEsR0FBQVIsZ0JBQUE7O2VBRUE7O01BQ0FDLDhCQUFBLENBQUFRLENBQUEsYUFBQWQsT0FBQSxFQUFBZSxJQUFBLEVBQUFDLE1BQUE7Z0JBQ0EsS0FBQVYsOEJBQUEsQ0FBQVcsQ0FBQSxDQUFBakIsT0FBQSxFQUFBZSxJQUFBO2tCQUNBRyxNQUFBLENBQUFDLGNBQUEsQ0FBQW5CLE9BQUEsRUFBQWUsSUFBQTtZQUEwQ0ss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TUFBUDtjQUNILENBdkc4QyxDQXlHL0M7O2NBQ0EsSUFBSTBFLFFBQVEsQ0FBQzNHLENBQVQsR0FBYSxDQUFqQixFQUFvQjtnQkFDaEJpQyxNQUFNLENBQUNqQyxDQUFQLEdBQVcyRyxRQUFRLENBQUMzRyxDQUFwQjtjQUNIO2NBRUQsT0FBT2lDLE1BQVA7WUFDSDtZQUVEOzs7Ozs7Ozs7dUNBT1d3RCxRQUFRMkIsTUFBTWhELFFBQVE7Y0FDN0IsSUFBSXFCLE1BQUosRUFBWTtnQkFDUixPQUFPLEtBQUs3QixVQUFMLENBQWdCaUIsS0FBaEIsRUFBc0I7Z0JBQTBCLHNCQUFoRCxFQUF3RTtrQkFBQyxRQUFRdUM7Z0JBQVQsQ0FBeEUsRUFBd0YsSUFBeEYsRUFBOEZoRCxNQUE5RixDQUFQO2NBQ0g7Y0FFRCxPQUFPLEtBQUtSLFVBQUwsQ0FBZ0JpQixLQUFoQixFQUFzQjtjQUEyQixvQkFBakQsRUFBdUU7Z0JBQUMsUUFBUXVDO2NBQVQsQ0FBdkUsRUFBdUYsSUFBdkYsRUFBNkZoRCxNQUE3RixDQUFQO1lBQ0giLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC9kYXRlLXRpbWUtZGlmZi93ZWJwYWNrL3VuaXZlcnNhbE1vZHVsZURlZmluaXRpb24iLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvZGF0ZS10aW1lLWRpZmYvd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvZGF0ZS10aW1lLWRpZmYvc3JjL2luZGV4LmpzIl0sInNvdXJjZXNDb250ZW50IjpbIihmdW5jdGlvbiB3ZWJwYWNrVW5pdmVyc2FsTW9kdWxlRGVmaW5pdGlvbihyb290LCBmYWN0b3J5KSB7XG5cdGlmKHR5cGVvZiBleHBvcnRzID09PSAnb2JqZWN0JyAmJiB0eXBlb2YgbW9kdWxlID09PSAnb2JqZWN0Jylcblx0XHRtb2R1bGUuZXhwb3J0cyA9IGZhY3RvcnkoKTtcblx0ZWxzZSBpZih0eXBlb2YgZGVmaW5lID09PSAnZnVuY3Rpb24nICYmIGRlZmluZS5hbWQpXG5cdFx0ZGVmaW5lKFwiZGF0ZS10aW1lLWRpZmZcIiwgW10sIGZhY3RvcnkpO1xuXHRlbHNlIGlmKHR5cGVvZiBleHBvcnRzID09PSAnb2JqZWN0Jylcblx0XHRleHBvcnRzW1wiZGF0ZS10aW1lLWRpZmZcIl0gPSBmYWN0b3J5KCk7XG5cdGVsc2Vcblx0XHRyb290W1wiZGF0ZS10aW1lLWRpZmZcIl0gPSBmYWN0b3J5KCk7XG59KSh0eXBlb2Ygc2VsZiAhPT0gJ3VuZGVmaW5lZCcgPyBzZWxmIDogdGhpcywgZnVuY3Rpb24oKSB7XG5yZXR1cm4gIiwiIFx0Ly8gVGhlIG1vZHVsZSBjYWNoZVxuIFx0dmFyIGluc3RhbGxlZE1vZHVsZXMgPSB7fTtcblxuIFx0Ly8gVGhlIHJlcXVpcmUgZnVuY3Rpb25cbiBcdGZ1bmN0aW9uIF9fd2VicGFja19yZXF1aXJlX18obW9kdWxlSWQpIHtcblxuIFx0XHQvLyBDaGVjayBpZiBtb2R1bGUgaXMgaW4gY2FjaGVcbiBcdFx0aWYoaW5zdGFsbGVkTW9kdWxlc1ttb2R1bGVJZF0pIHtcbiBcdFx0XHRyZXR1cm4gaW5zdGFsbGVkTW9kdWxlc1ttb2R1bGVJZF0uZXhwb3J0cztcbiBcdFx0fVxuIFx0XHQvLyBDcmVhdGUgYSBuZXcgbW9kdWxlIChhbmQgcHV0IGl0IGludG8gdGhlIGNhY2hlKVxuIFx0XHR2YXIgbW9kdWxlID0gaW5zdGFsbGVkTW9kdWxlc1ttb2R1bGVJZF0gPSB7XG4gXHRcdFx0aTogbW9kdWxlSWQsXG4gXHRcdFx0bDogZmFsc2UsXG4gXHRcdFx0ZXhwb3J0czoge31cbiBcdFx0fTtcblxuIFx0XHQvLyBFeGVjdXRlIHRoZSBtb2R1bGUgZnVuY3Rpb25cbiBcdFx0bW9kdWxlc1ttb2R1bGVJZF0uY2FsbChtb2R1bGUuZXhwb3J0cywgbW9kdWxlLCBtb2R1bGUuZXhwb3J0cywgX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cbiBcdFx0Ly8gRmxhZyB0aGUgbW9kdWxlIGFzIGxvYWRlZFxuIFx0XHRtb2R1bGUubCA9IHRydWU7XG5cbiBcdFx0Ly8gUmV0dXJuIHRoZSBleHBvcnRzIG9mIHRoZSBtb2R1bGVcbiBcdFx0cmV0dXJuIG1vZHVsZS5leHBvcnRzO1xuIFx0fVxuXG5cbiBcdC8vIGV4cG9zZSB0aGUgbW9kdWxlcyBvYmplY3QgKF9fd2VicGFja19tb2R1bGVzX18pXG4gXHRfX3dlYnBhY2tfcmVxdWlyZV9fLm0gPSBtb2R1bGVzO1xuXG4gXHQvLyBleHBvc2UgdGhlIG1vZHVsZSBjYWNoZVxuIFx0X193ZWJwYWNrX3JlcXVpcmVfXy5jID0gaW5zdGFsbGVkTW9kdWxlcztcblxuIFx0Ly8gZGVmaW5lIGdldHRlciBmdW5jdGlvbiBmb3IgaGFybW9ueSBleHBvcnRzXG4gXHRfX3dlYnBhY2tfcmVxdWlyZV9fLmQgPSBmdW5jdGlvbihleHBvcnRzLCBuYW1lLCBnZXR0ZXIpIHtcbiBcdFx0aWYoIV9fd2VicGFja19yZXF1aXJlX18ubyhleHBvcnRzLCBuYW1lKSkge1xuIFx0XHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBuYW1lLCB7IGVudW1lcmFibGU6IHRydWUsIGdldDogZ2V0dGVyIH0pO1xuIFx0XHR9XG4gXHR9O1xuXG4gXHQvLyBkZWZpbmUgX19lc01vZHVsZSBvbiBleHBvcnRzXG4gXHRfX3dlYnBhY2tfcmVxdWlyZV9fLnIgPSBmdW5jdGlvbihleHBvcnRzKSB7XG4gXHRcdGlmKHR5cGVvZiBTeW1ib2wgIT09ICd1bmRlZmluZWQnICYmIFN5bWJvbC50b1N0cmluZ1RhZykge1xuIFx0XHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBTeW1ib2wudG9TdHJpbmdUYWcsIHsgdmFsdWU6ICdNb2R1bGUnIH0pO1xuIFx0XHR9XG4gXHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCAnX19lc01vZHVsZScsIHsgdmFsdWU6IHRydWUgfSk7XG4gXHR9O1xuXG4gXHQvLyBjcmVhdGUgYSBmYWtlIG5hbWVzcGFjZSBvYmplY3RcbiBcdC8vIG1vZGUgJiAxOiB2YWx1ZSBpcyBhIG1vZHVsZSBpZCwgcmVxdWlyZSBpdFxuIFx0Ly8gbW9kZSAmIDI6IG1lcmdlIGFsbCBwcm9wZXJ0aWVzIG9mIHZhbHVlIGludG8gdGhlIG5zXG4gXHQvLyBtb2RlICYgNDogcmV0dXJuIHZhbHVlIHdoZW4gYWxyZWFkeSBucyBvYmplY3RcbiBcdC8vIG1vZGUgJiA4fDE6IGJlaGF2ZSBsaWtlIHJlcXVpcmVcbiBcdF9fd2VicGFja19yZXF1aXJlX18udCA9IGZ1bmN0aW9uKHZhbHVlLCBtb2RlKSB7XG4gXHRcdGlmKG1vZGUgJiAxKSB2YWx1ZSA9IF9fd2VicGFja19yZXF1aXJlX18odmFsdWUpO1xuIFx0XHRpZihtb2RlICYgOCkgcmV0dXJuIHZhbHVlO1xuIFx0XHRpZigobW9kZSAmIDQpICYmIHR5cGVvZiB2YWx1ZSA9PT0gJ29iamVjdCcgJiYgdmFsdWUgJiYgdmFsdWUuX19lc01vZHVsZSkgcmV0dXJuIHZhbHVlO1xuIFx0XHR2YXIgbnMgPSBPYmplY3QuY3JlYXRlKG51bGwpO1xuIFx0XHRfX3dlYnBhY2tfcmVxdWlyZV9fLnIobnMpO1xuIFx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkobnMsICdkZWZhdWx0JywgeyBlbnVtZXJhYmxlOiB0cnVlLCB2YWx1ZTogdmFsdWUgfSk7XG4gXHRcdGlmKG1vZGUgJiAyICYmIHR5cGVvZiB2YWx1ZSAhPSAnc3RyaW5nJykgZm9yKHZhciBrZXkgaW4gdmFsdWUpIF9fd2VicGFja19yZXF1aXJlX18uZChucywga2V5LCBmdW5jdGlvbihrZXkpIHsgcmV0dXJuIHZhbHVlW2tleV07IH0uYmluZChudWxsLCBrZXkpKTtcbiBcdFx0cmV0dXJuIG5zO1xuIFx0fTtcblxuIFx0Ly8gZ2V0RGVmYXVsdEV4cG9ydCBmdW5jdGlvbiBmb3IgY29tcGF0aWJpbGl0eSB3aXRoIG5vbi1oYXJtb255IG1vZHVsZXNcbiBcdF9fd2VicGFja19yZXF1aXJlX18ubiA9IGZ1bmN0aW9uKG1vZHVsZSkge1xuIFx0XHR2YXIgZ2V0dGVyID0gbW9kdWxlICYmIG1vZHVsZS5fX2VzTW9kdWxlID9cbiBcdFx0XHRmdW5jdGlvbiBnZXREZWZhdWx0KCkgeyByZXR1cm4gbW9kdWxlWydkZWZhdWx0J107IH0gOlxuIFx0XHRcdGZ1bmN0aW9uIGdldE1vZHVsZUV4cG9ydHMoKSB7IHJldHVybiBtb2R1bGU7IH07XG4gXHRcdF9fd2VicGFja19yZXF1aXJlX18uZChnZXR0ZXIsICdhJywgZ2V0dGVyKTtcbiBcdFx0cmV0dXJuIGdldHRlcjtcbiBcdH07XG5cbiBcdC8vIE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbFxuIFx0X193ZWJwYWNrX3JlcXVpcmVfXy5vID0gZnVuY3Rpb24ob2JqZWN0LCBwcm9wZXJ0eSkgeyByZXR1cm4gT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG9iamVjdCwgcHJvcGVydHkpOyB9O1xuXG4gXHQvLyBfX3dlYnBhY2tfcHVibGljX3BhdGhfX1xuIFx0X193ZWJwYWNrX3JlcXVpcmVfXy5wID0gXCJcIjtcblxuXG4gXHQvLyBMb2FkIGVudHJ5IG1vZHVsZSBhbmQgcmV0dXJuIGV4cG9ydHNcbiBcdHJldHVybiBfX3dlYnBhY2tfcmVxdWlyZV9fKF9fd2VicGFja19yZXF1aXJlX18ucyA9IFwiLi9zcmMvaW5kZXguanNcIik7XG4iLCIvKipcbiAqIFRyYW5zbGF0b3IgSW50ZXJmYWNlXG4gKlxuICogQGludGVyZmFjZSBUcmFuc2xhdG9yXG4gKi9cblxuLyoqXG4gKiBUcmFuc2xhdGVzIHRoZSBnaXZlbiBtZXNzYWdlLlxuICpcbiAqIEBmdW5jdGlvblxuICogQG5hbWUgVHJhbnNsYXRvciN0cmFuc1xuICogQHBhcmFtIHtTdHJpbmd9IGlkICAgICAgICAgICAgIFRoZSBtZXNzYWdlIGlkXG4gKiBAcGFyYW0ge09iamVjdD19IHBhcmFtZXRlcnMgICAgQW4gYXJyYXkgb2YgcGFyYW1ldGVycyBmb3IgdGhlIG1lc3NhZ2VcbiAqIEBwYXJhbSB7U3RyaW5nPX0gZG9tYWluICAgICAgICBUaGUgZG9tYWluIGZvciB0aGUgbWVzc2FnZSBvciBudWxsIHRvIGd1ZXNzIGl0XG4gKiBAcGFyYW0ge1N0cmluZz19IGxvY2FsZSAgICAgICAgVGhlIGxvY2FsZSBvciBudWxsIHRvIHVzZSB0aGUgZGVmYXVsdFxuICogQHJldHVybiB7U3RyaW5nfSAgICAgICAgICAgICAgIFRoZSB0cmFuc2xhdGVkIHN0cmluZ1xuICovXG5cbi8qKlxuICogVHJhbnNsYXRlcyB0aGUgZ2l2ZW4gY2hvaWNlIG1lc3NhZ2UgYnkgY2hvb3NpbmcgYSB0cmFuc2xhdGlvbiBhY2NvcmRpbmcgdG8gYSBudW1iZXIuXG4gKlxuICogQGZ1bmN0aW9uXG4gKiBAbmFtZSBUcmFuc2xhdG9yI3RyYW5zQ2hvaWNlXG4gKiBAcGFyYW0ge1N0cmluZ30gaWQgICAgICAgICAgICAgVGhlIG1lc3NhZ2UgaWRcbiAqIEBwYXJhbSB7TnVtYmVyfSBudW1iZXIgICAgICAgICBUaGUgbnVtYmVyIHRvIHVzZSB0byBmaW5kIHRoZSBpbmRpY2Ugb2YgdGhlIG1lc3NhZ2VcbiAqIEBwYXJhbSB7T2JqZWN0PX0gcGFyYW1ldGVycyAgICBBbiBhcnJheSBvZiBwYXJhbWV0ZXJzIGZvciB0aGUgbWVzc2FnZVxuICogQHBhcmFtIHtTdHJpbmc9fSBkb21haW4gICAgICAgIFRoZSBkb21haW4gZm9yIHRoZSBtZXNzYWdlIG9yIG51bGwgdG8gZ3Vlc3MgaXRcbiAqIEBwYXJhbSB7U3RyaW5nPX0gbG9jYWxlICAgICAgICBUaGUgbG9jYWxlIG9yIG51bGwgdG8gdXNlIHRoZSBkZWZhdWx0XG4gKiBAcmV0dXJuIHtTdHJpbmd9ICAgICAgICAgICAgICAgVGhlIHRyYW5zbGF0ZWQgc3RyaW5nXG4gKi9cblxuLyoqXG4gKiBAdHlwZWRlZiB7T2JqZWN0fSBQYXJzZWREYXRlXG4gKiBAcHJvcGVydHkge251bWJlcn0geVxuICogQHByb3BlcnR5IHtudW1iZXJ9IG1cbiAqIEBwcm9wZXJ0eSB7bnVtYmVyfSBkXG4gKiBAcHJvcGVydHkge251bWJlcn0gaFxuICogQHByb3BlcnR5IHtudW1iZXJ9IGlcbiAqIEBwcm9wZXJ0eSB7bnVtYmVyfSBzXG4gKi9cblxuLyoqXG4gKiBAdHlwZWRlZiB7T2JqZWN0fSBEYXRlVGltZURpZmZcbiAqIEBwcm9wZXJ0eSB7bnVtYmVyfSB5XG4gKiBAcHJvcGVydHkge251bWJlcn0gbVxuICogQHByb3BlcnR5IHtudW1iZXJ9IGRcbiAqIEBwcm9wZXJ0eSB7bnVtYmVyfSBoXG4gKiBAcHJvcGVydHkge251bWJlcn0gaVxuICogQHByb3BlcnR5IHtudW1iZXJ9IHNcbiAqIEBwcm9wZXJ0eSB7Ym9vbGVhbn0gaW52ZXJ0XG4gKiBAcHJvcGVydHkge251bWJlcn0gZGF5c1xuICovXG5cbi8qKlxuICogQHR5cGVkZWYge09iamVjdH0gVGltZVJlbWFpbmluZ1xuICogQHByb3BlcnR5IHtudW1iZXJ9IHRvdGFsXG4gKiBAcHJvcGVydHkge251bWJlcn0gZGF5c1xuICogQHByb3BlcnR5IHtudW1iZXJ9IGhvdXJzXG4gKiBAcHJvcGVydHkge251bWJlcn0gbWludXRlc1xuICogQHByb3BlcnR5IHtudW1iZXJ9IHNlY29uZHNcbiAqL1xuXG4vKipcbiAqIEBwYXJhbSB7bnVtYmVyfSBudW1iZXJcbiAqIEByZXR1cm5zIHtzdHJpbmd9XG4gKi9cbmZ1bmN0aW9uIHBhZChudW1iZXIpIHtcbiAgICBpZiAobnVtYmVyIDwgMTApIHtcbiAgICAgICAgcmV0dXJuICcwJyArIG51bWJlcjtcbiAgICB9XG4gICAgcmV0dXJuIG51bWJlci50b1N0cmluZygpO1xufVxuXG4vKipcbiAqIEBwYXJhbSB7RGF0ZX0gZGF0ZVxuICogQHBhcmFtIHtib29sZWFuPX0gW3dpdGhUaW1lPWZhbHNlXVxuICogQHJldHVybnMge3N0cmluZ31cbiAqL1xuZnVuY3Rpb24gZm9ybWF0RGF0ZShkYXRlLCB3aXRoVGltZSA9IGZhbHNlKSB7XG4gICAgbGV0IGZvcm1hdHRlZCA9IGAke2RhdGUuZ2V0RnVsbFllYXIoKX0gJHtwYWQoZGF0ZS5nZXRNb250aCgpICsgMSl9ICR7cGFkKGRhdGUuZ2V0RGF0ZSgpKX1gO1xuXG4gICAgaWYgKHdpdGhUaW1lKSB7XG4gICAgICAgIGZvcm1hdHRlZCA9IGAke2Zvcm1hdHRlZH0gJHtwYWQoZGF0ZS5nZXRIb3VycygpKX0gJHtwYWQoZGF0ZS5nZXRNaW51dGVzKCkpfSAke3BhZChkYXRlLmdldFNlY29uZHMoKSl9YDtcbiAgICB9XG5cbiAgICByZXR1cm4gZm9ybWF0dGVkO1xufVxuXG4vKipcbiAqIEBwYXJhbSB7RGF0ZX0gZGF0ZVxuICogQHJldHVybnMge1BhcnNlZERhdGV9XG4gKi9cbmZ1bmN0aW9uIHBhcnNlRGF0ZShkYXRlKSB7XG4gICAgY29uc3QgcGFydHMgPSBmb3JtYXREYXRlKGRhdGUsIHRydWUpLnNwbGl0KCcgJyk7XG4gICAgY29uc3Qga2V5cyA9IFsneScsICdtJywgJ2QnLCAnaCcsICdpJywgJ3MnXTtcbiAgICBjb25zdCBuZXdBcnJheSA9IHt9O1xuICAgIGxldCBpO1xuXG4gICAgZm9yIChpID0gMDsgaSA8IGtleXMubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgbmV3QXJyYXlba2V5c1tpXV0gPSBwYXJzZUludChwYXJ0c1tpXSwgMTApO1xuICAgIH1cblxuICAgIHJldHVybiBuZXdBcnJheTtcbn1cblxuLyoqXG4gKiBAcGFyYW0ge1BhcnNlZERhdGV9IGJhc2VcbiAqIEBwYXJhbSB7RGF0ZVRpbWVEaWZmfSByXG4gKiBAcmV0dXJucyB7RGF0ZVRpbWVEaWZmfVxuICovXG5mdW5jdGlvbiBkYXRlTm9ybWFsaXplKGJhc2UsIHIpIHtcbiAgICBkYXRlUmFuZ2VMaW1pdCgwLCA2MCwgNjAsICdzJywgJ2knLCByKTtcbiAgICBkYXRlUmFuZ2VMaW1pdCgwLCA2MCwgNjAsICdpJywgJ2gnLCByKTtcbiAgICBkYXRlUmFuZ2VMaW1pdCgwLCAyNCwgMjQsICdoJywgJ2QnLCByKTtcbiAgICBkYXRlUmFuZ2VMaW1pdCgwLCAxMiwgMTIsICdtJywgJ3knLCByKTtcbiAgICBkYXRlUmFuZ2VMaW1pdERheXMoYmFzZSwgcik7XG4gICAgZGF0ZVJhbmdlTGltaXQoMCwgMTIsIDEyLCAnbScsICd5Jywgcik7XG5cbiAgICByZXR1cm4gcjtcbn1cblxuLyoqXG4gKiBAcGFyYW0ge251bWJlcn0gc3RhcnRcbiAqIEBwYXJhbSB7bnVtYmVyfSBlbmRcbiAqIEBwYXJhbSB7bnVtYmVyfSBhZGpcbiAqIEBwYXJhbSB7c3RyaW5nfSBhIC0gUGFyc2VkRGF0ZSBwcm9wc1xuICogQHBhcmFtIHtzdHJpbmd9IGIgLSBQYXJzZWREYXRlIHByb3BzXG4gKiBAcGFyYW0ge0RhdGVUaW1lRGlmZnxQYXJzZWREYXRlfSByZXN1bHRcbiAqL1xuZnVuY3Rpb24gZGF0ZVJhbmdlTGltaXQoc3RhcnQsIGVuZCwgYWRqLCBhLCBiLCByZXN1bHQpIHtcbiAgICBpZiAocmVzdWx0W2FdIDwgc3RhcnQpIHtcbiAgICAgICAgcmVzdWx0W2JdIC09IHBhcnNlSW50KChzdGFydCAtIHJlc3VsdFthXSAtIDEpIC8gYWRqLCAxMCkgKyAxO1xuICAgICAgICByZXN1bHRbYV0gKz0gYWRqICogcGFyc2VJbnQoKHN0YXJ0IC0gcmVzdWx0W2FdIC0gMSkgLyBhZGogKyAxLCAxMCk7XG4gICAgfVxuXG4gICAgaWYgKHJlc3VsdFthXSA+PSBlbmQpIHtcbiAgICAgICAgcmVzdWx0W2JdICs9IHBhcnNlSW50KHJlc3VsdFthXSAvIGFkaiwgMTApO1xuICAgICAgICByZXN1bHRbYV0gLT0gYWRqICogcGFyc2VJbnQocmVzdWx0W2FdIC8gYWRqLCAxMCk7XG4gICAgfVxufVxuXG4vKipcbiAqIEBwYXJhbSB7UGFyc2VkRGF0ZX0gYmFzZVxuICogQHBhcmFtIHtEYXRlVGltZURpZmZ9IHJlc3VsdFxuICovXG5mdW5jdGlvbiBkYXRlUmFuZ2VMaW1pdERheXMoYmFzZSwgcmVzdWx0KSB7XG4gICAgY29uc3QgZGF5c0luTW9udGhMZWFwID0gWzMxLCAzMSwgMjksIDMxLCAzMCwgMzEsIDMwLCAzMSwgMzEsIDMwLCAzMSwgMzAsIDMxXTtcbiAgICBjb25zdCBkYXlzSW5Nb250aCA9IFszMSwgMzEsIDI4LCAzMSwgMzAsIDMxLCAzMCwgMzEsIDMxLCAzMCwgMzEsIDMwLCAzMV07XG5cbiAgICBkYXRlUmFuZ2VMaW1pdCgxLCAxMywgMTIsICdtJywgJ3knLCBiYXNlKTtcbiAgICBsZXQgeWVhciA9IGJhc2UueTtcbiAgICBsZXQgbW9udGggPSBiYXNlLm07XG4gICAgbGV0IGRheXM7XG4gICAgbGV0IGxlYXB5ZWFyO1xuXG4gICAgaWYgKCFyZXN1bHQuaW52ZXJ0KSB7XG4gICAgICAgIHdoaWxlIChyZXN1bHQuZCA8IDApIHtcbiAgICAgICAgICAgIG1vbnRoLS07XG4gICAgICAgICAgICBpZiAobW9udGggPCAxKSB7XG4gICAgICAgICAgICAgICAgbW9udGggKz0gMTI7XG4gICAgICAgICAgICAgICAgeWVhci0tO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBsZWFweWVhciA9IHllYXIgJSA0MDAgPT09IDAgfHwgKHllYXIgJSAxMDAgIT09IDAgJiYgeWVhciAlIDQgPT09IDApO1xuICAgICAgICAgICAgZGF5cyA9IGxlYXB5ZWFyID8gZGF5c0luTW9udGhMZWFwW21vbnRoXSA6IGRheXNJbk1vbnRoW21vbnRoXTtcblxuICAgICAgICAgICAgcmVzdWx0LmQgKz0gZGF5cztcbiAgICAgICAgICAgIHJlc3VsdC5tLS07XG4gICAgICAgIH1cbiAgICB9IGVsc2Uge1xuICAgICAgICB3aGlsZSAocmVzdWx0LmQgPCAwKSB7XG4gICAgICAgICAgICBsZWFweWVhciA9IHllYXIgJSA0MDAgPT09IDAgfHwgKHllYXIgJSAxMDAgIT09IDAgJiYgeWVhciAlIDQgPT09IDApO1xuICAgICAgICAgICAgZGF5cyA9IGxlYXB5ZWFyID8gZGF5c0luTW9udGhMZWFwW21vbnRoXSA6IGRheXNJbk1vbnRoW21vbnRoXTtcblxuICAgICAgICAgICAgcmVzdWx0LmQgKz0gZGF5cztcbiAgICAgICAgICAgIHJlc3VsdC5tLS07XG5cbiAgICAgICAgICAgIG1vbnRoKys7XG4gICAgICAgICAgICBpZiAobW9udGggPiAxMikge1xuICAgICAgICAgICAgICAgIG1vbnRoIC09IDEyO1xuICAgICAgICAgICAgICAgIHllYXIrKztcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbn1cblxuLyoqXG4gKiBAcGFyYW0ge0RhdGV9IGRhdGUxXG4gKiBAcGFyYW0ge0RhdGV9IGRhdGUyXG4gKiBAcGFyYW0ge2Jvb2xlYW49fSBbb25seVNlY29uZHM9ZmFsc2VdXG4gKiBAcmV0dXJucyB7RGF0ZVtdfVxuICovXG5mdW5jdGlvbiByZXNldFRpbWUoZGF0ZTEsIGRhdGUyLCBvbmx5U2Vjb25kcykge1xuICAgIGlmIChvbmx5U2Vjb25kcykge1xuICAgICAgICByZXR1cm4gW1xuICAgICAgICAgICAgbmV3IERhdGUoZGF0ZTEuZ2V0RnVsbFllYXIoKSwgZGF0ZTEuZ2V0TW9udGgoKSwgZGF0ZTEuZ2V0RGF0ZSgpLCBkYXRlMS5nZXRIb3VycygpLCBkYXRlMS5nZXRNaW51dGVzKCksIDAsIDApLFxuICAgICAgICAgICAgbmV3IERhdGUoZGF0ZTIuZ2V0RnVsbFllYXIoKSwgZGF0ZTIuZ2V0TW9udGgoKSwgZGF0ZTIuZ2V0RGF0ZSgpLCBkYXRlMi5nZXRIb3VycygpLCBkYXRlMi5nZXRNaW51dGVzKCksIDAsIDApXG4gICAgICAgIF07XG4gICAgfVxuXG4gICAgcmV0dXJuIFtcbiAgICAgICAgbmV3IERhdGUoZGF0ZTEuZ2V0RnVsbFllYXIoKSwgZGF0ZTEuZ2V0TW9udGgoKSwgZGF0ZTEuZ2V0RGF0ZSgpLCAwLCAwLCAwLCAwKSxcbiAgICAgICAgbmV3IERhdGUoZGF0ZTIuZ2V0RnVsbFllYXIoKSwgZGF0ZTIuZ2V0TW9udGgoKSwgZGF0ZTIuZ2V0RGF0ZSgpLCAwLCAwLCAwLCAwKVxuICAgIF07XG59XG5cbi8qKlxuICogQHBhcmFtIHtudW1iZXJ9IG1vbnRoXG4gKiBAcGFyYW0ge251bWJlcn0geWVhclxuICogQHJldHVybnMge251bWJlcn1cbiAqL1xuZnVuY3Rpb24gZGF5c0luTW9udGgobW9udGgsIHllYXIpIHtcbiAgICByZXR1cm4gbmV3IERhdGUoeWVhciwgbW9udGggKyAxLCAwKS5nZXREYXRlKCk7XG59XG5cbi8qKlxuICogQHBhcmFtIHtEYXRlfSBkYXRlXG4gKiBAcmV0dXJucyB7bnVtYmVyfVxuICovXG5mdW5jdGlvbiBkYXlzSW5ZZWFyKGRhdGUpIHtcbiAgICBjb25zdCB5ZWFyID0gZGF0ZS5nZXRGdWxsWWVhcigpO1xuICAgIGNvbnN0IGxlYXB5ZWFyID0geWVhciAlIDQwMCA9PT0gMCB8fCAoeWVhciAlIDEwMCAhPT0gMCAmJiB5ZWFyICUgNCA9PT0gMCk7XG5cbiAgICByZXR1cm4gbGVhcHllYXIgPyAzNjYgOiAzNjU7XG59XG5cbi8qKlxuICogQHBhcmFtIHtEYXRlfSBkYXRlXG4gKiBAcGFyYW0ge251bWJlcn0gdHMyXG4gKiBAcmV0dXJucyB7bnVtYmVyfVxuICovXG5mdW5jdGlvbiBnZXRTZWNvbmRzKGRhdGUsIHRzMikge1xuICAgIHJldHVybiBNYXRoLmFicyhkYXRlLmdldFRpbWUoKSAtIHRzMikgLyAxMDAwO1xufVxuXG4vKipcbiAqIEBwYXJhbSB7bnVtYmVyfSBudW1cbiAqIEByZXR1cm5zIHtudW1iZXJ9XG4gKi9cbmZ1bmN0aW9uIHJvdW5kKG51bSkge1xuICAgIHJldHVybiArKE1hdGgucm91bmQobnVtICsgXCJlKzFcIikgICsgXCJlLTFcIik7XG59XG5cbmZ1bmN0aW9uIG9iamVjdFZhbHVlcyhvYmopIHtcbiAgICBsZXQgdmFscyA9IFtdO1xuICAgIGZvciAoY29uc3QgcHJvcCBpbiBvYmopIHtcbiAgICAgICAgaWYgKE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbChvYmosIHByb3ApKSB7XG4gICAgICAgICAgICB2YWxzLnB1c2gob2JqW3Byb3BdKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZXR1cm4gdmFscztcbn1cblxuZXhwb3J0IGRlZmF1bHQgY2xhc3MgRGF0ZVRpbWVEaWZmIHtcbiAgICAvKipcbiAgICAgKiBAdHlwZSBUcmFuc2xhdG9yXG4gICAgICovXG4gICAgdHJhbnNsYXRvcjtcblxuICAgIC8qKlxuICAgICAqIEB0eXBlIEZ1bmN0aW9uXG4gICAgICovXG4gICAgbnVtYmVyRm9ybWF0dGVyO1xuXG4gICAgLyoqXG4gICAgICogQHBhcmFtIHtUcmFuc2xhdG9yfSB0cmFuc2xhdG9yXG4gICAgICogQHBhcmFtIHtGdW5jdGlvbn0gbnVtYmVyRm9ybWF0dGVyXG4gICAgICovXG4gICAgY29uc3RydWN0b3IodHJhbnNsYXRvciwgbnVtYmVyRm9ybWF0dGVyKSB7XG4gICAgICAgIHRoaXMudHJhbnNsYXRvciA9IHRyYW5zbGF0b3I7XG4gICAgICAgIHRoaXMubnVtYmVyRm9ybWF0dGVyID0gbnVtYmVyRm9ybWF0dGVyO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBwYXJhbSB7RGF0ZX0gZnJvbURhdGVUaW1lXG4gICAgICogQHBhcmFtIHtEYXRlfSB0b0RhdGVUaW1lXG4gICAgICogQHBhcmFtIHtib29sZWFuPX0gW29ubHlEYXRlPWZhbHNlXSB0cnVlIC0gdGltZSBpcyBzZXQgdG8gMDA6MDA6MDBcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW49fSBbc2hvcnRGb3JtYXQ9ZmFsc2VdIHRydWUgLSBmcmFjdGlvbiBpbiB5ZWFycyBhbmQgbW9udGhzLCBvbmx5IDEgdW5pdCAoZS5nLiAxLjcgeWVhcnMgb3IgNC44IG1vbnRocyBvciAxMCBob3VycylcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW49fSBbZnJvbVRvZGF5PWZhbHNlXSB0cnVlIC0gZnJvbSBkYXRldGltZSA9IG5vdyBkYXRldGltZSwgZW5hYmxlIFwiVG9tb3Jyb3dcIiwgXCJZZXN0ZXJkYXlcIiBhbmQgXCJUb2RheVwiXG4gICAgICogQHBhcmFtIHsoc3RyaW5nfG51bGwpPX0gW2xvY2FsZT1udWxsXVxuICAgICAqIEByZXR1cm5zIHtzdHJpbmd9XG4gICAgICovXG4gICAgZm9ybWF0RHVyYXRpb24oXG4gICAgICAgIGZyb21EYXRlVGltZSxcbiAgICAgICAgdG9EYXRlVGltZSxcbiAgICAgICAgb25seURhdGUgPSBmYWxzZSxcbiAgICAgICAgc2hvcnRGb3JtYXQgPSBmYWxzZSxcbiAgICAgICAgZnJvbVRvZGF5ID0gZmFsc2UsXG4gICAgICAgIGxvY2FsZSA9IG51bGxcbiAgICApIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuX2Zvcm1hdChcbiAgICAgICAgICAgIGZyb21EYXRlVGltZSxcbiAgICAgICAgICAgIHRvRGF0ZVRpbWUsXG4gICAgICAgICAgICBvbmx5RGF0ZSxcbiAgICAgICAgICAgIHNob3J0Rm9ybWF0LFxuICAgICAgICAgICAgZmFsc2UsXG4gICAgICAgICAgICBmcm9tVG9kYXksXG4gICAgICAgICAgICB0cnVlLFxuICAgICAgICAgICAgbG9jYWxlXG4gICAgICAgICk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogVXNlcyB0aGUgZGF0ZSBkaWZmZXJlbmNlIHRvIGRpc3BsYXkgdGhlIGFic29sdXRlIHZhbHVlIGluIGhvdXJzIGFuZCBtaW51dGVzXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge0RhdGV9IGZyb21EYXRlVGltZVxuICAgICAqIEBwYXJhbSB7RGF0ZX0gdG9EYXRlVGltZVxuICAgICAqIEBwYXJhbSB7KHN0cmluZ3xudWxsKT19IFtsb2NhbGU9bnVsbF1cbiAgICAgKiBAcmV0dXJucyB7c3RyaW5nfVxuICAgICAqL1xuICAgIGZvcm1hdER1cmF0aW9uSW5Ib3VycyhcbiAgICAgICAgZnJvbURhdGVUaW1lLFxuICAgICAgICB0b0RhdGVUaW1lLFxuICAgICAgICBsb2NhbGVcbiAgICApIHtcbiAgICAgICAgY29uc3Qgc2Vjb25kcyA9IE1hdGguYWJzKGZyb21EYXRlVGltZS5nZXRUaW1lKCkgLSB0b0RhdGVUaW1lLmdldFRpbWUoKSkgLyAxMDAwO1xuICAgICAgICBjb25zdCBob3VycyA9IE1hdGguZmxvb3Ioc2Vjb25kcyAvICg2MCAqIDYwKSk7XG4gICAgICAgIGNvbnN0IG1pbnV0ZXMgPSBNYXRoLmZsb29yKChzZWNvbmRzIC8gNjApICUgNjApO1xuICAgICAgICBjb25zdCB1bml0cyA9IFtdO1xuXG4gICAgICAgIGlmIChob3VycyA+IDApIHtcbiAgICAgICAgICAgIHVuaXRzLnB1c2goXG4gICAgICAgICAgICAgICAgdGhpcy50cmFuc2xhdG9yLnRyYW5zQ2hvaWNlKCdob3Vycy5zaG9ydCcsIGhvdXJzLCB7XG4gICAgICAgICAgICAgICAgICAgIGNvdW50OiB0aGlzLm51bWJlckZvcm1hdHRlcihob3VycylcbiAgICAgICAgICAgICAgICB9LCBudWxsLCBsb2NhbGUpXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKG1pbnV0ZXMgPiAwKSB7XG4gICAgICAgICAgICB1bml0cy5wdXNoKFxuICAgICAgICAgICAgICAgIHRoaXMudHJhbnNsYXRvci50cmFuc0Nob2ljZSgnbWludXRlcy5zaG9ydCcsIG1pbnV0ZXMsIHtcbiAgICAgICAgICAgICAgICAgICAgY291bnQ6IHRoaXMubnVtYmVyRm9ybWF0dGVyKG1pbnV0ZXMpXG4gICAgICAgICAgICAgICAgfSwgbnVsbCwgbG9jYWxlKVxuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh1bml0cy5sZW5ndGggPT09IDApIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnRyYW5zbGF0b3IudHJhbnMoJ3NlY29uZHMnLCB7fSwgbnVsbCwgbG9jYWxlKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiB1bml0cy5qb2luKCcgJyk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQHBhcmFtIHtEYXRlfSBmcm9tRGF0ZVRpbWVcbiAgICAgKiBAcGFyYW0ge0RhdGV9IHRvRGF0ZVRpbWVcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW49fSBbc3VmZml4PXRydWVdIHRydWUgLSB3cmFwIFwiaW4gJXRleHQlXCIgb3IgXCIldGV4dCUgYWdvXCJcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW49fSBbZnJvbVRvZGF5PXRydWVdIHRydWUgLSBmcm9tIGRhdGV0aW1lID0gbm93IGRhdGV0aW1lLCBlbmFibGUgXCJUb21vcnJvd1wiLCBcIlllc3RlcmRheVwiIGFuZCBcIlRvZGF5XCJcbiAgICAgKiBAcGFyYW0geyhzdHJpbmd8bnVsbCk9fSBbbG9jYWxlPW51bGxdXG4gICAgICogQHJldHVybnMge3N0cmluZ31cbiAgICAgKi9cbiAgICBzaG9ydEZvcm1hdFZpYURhdGVzKFxuICAgICAgICBmcm9tRGF0ZVRpbWUsXG4gICAgICAgIHRvRGF0ZVRpbWUsXG4gICAgICAgIHN1ZmZpeCA9IHRydWUsXG4gICAgICAgIGZyb21Ub2RheSA9IHRydWUsXG4gICAgICAgIGxvY2FsZSA9IG51bGxcbiAgICApIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuX2Zvcm1hdChcbiAgICAgICAgICAgIGZyb21EYXRlVGltZSxcbiAgICAgICAgICAgIHRvRGF0ZVRpbWUsXG4gICAgICAgICAgICB0cnVlLFxuICAgICAgICAgICAgdHJ1ZSxcbiAgICAgICAgICAgIHN1ZmZpeCxcbiAgICAgICAgICAgIGZyb21Ub2RheSxcbiAgICAgICAgICAgIGZhbHNlLFxuICAgICAgICAgICAgbG9jYWxlXG4gICAgICAgICk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQHBhcmFtIHtEYXRlfSBmcm9tRGF0ZVRpbWVcbiAgICAgKiBAcGFyYW0ge0RhdGV9IHRvRGF0ZVRpbWVcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW49fSBbc3VmZml4PXRydWVdIHRydWUgLSB3cmFwIFwiaW4gJXRleHQlXCIgb3IgXCIldGV4dCUgYWdvXCJcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW49fSBbZnJvbVRvZGF5PXRydWVdIHRydWUgLSBmcm9tIGRhdGV0aW1lID0gbm93IGRhdGV0aW1lLCBlbmFibGUgXCJUb21vcnJvd1wiLCBcIlllc3RlcmRheVwiIGFuZCBcIlRvZGF5XCJcbiAgICAgKiBAcGFyYW0geyhzdHJpbmd8bnVsbCk9fSBbbG9jYWxlPW51bGxdXG4gICAgICogQHJldHVybnMge3N0cmluZ31cbiAgICAgKi9cbiAgICBsb25nRm9ybWF0VmlhRGF0ZXMoXG4gICAgICAgIGZyb21EYXRlVGltZSxcbiAgICAgICAgdG9EYXRlVGltZSxcbiAgICAgICAgc3VmZml4ID0gdHJ1ZSxcbiAgICAgICAgZnJvbVRvZGF5ID0gdHJ1ZSxcbiAgICAgICAgbG9jYWxlID0gbnVsbFxuICAgICkge1xuICAgICAgICByZXR1cm4gdGhpcy5fZm9ybWF0KFxuICAgICAgICAgICAgZnJvbURhdGVUaW1lLFxuICAgICAgICAgICAgdG9EYXRlVGltZSxcbiAgICAgICAgICAgIHRydWUsXG4gICAgICAgICAgICBmYWxzZSxcbiAgICAgICAgICAgIHN1ZmZpeCxcbiAgICAgICAgICAgIGZyb21Ub2RheSxcbiAgICAgICAgICAgIGZhbHNlLFxuICAgICAgICAgICAgbG9jYWxlXG4gICAgICAgICk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQHBhcmFtIHtEYXRlfSBmcm9tRGF0ZVRpbWVcbiAgICAgKiBAcGFyYW0ge0RhdGV9IHRvRGF0ZVRpbWVcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW49fSBbc3VmZml4PXRydWVdIHRydWUgLSB3cmFwIFwiaW4gJXRleHQlXCIgb3IgXCIldGV4dCUgYWdvXCJcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW49fSBbZnJvbVRvZGF5PXRydWVdIHRydWUgLSBmcm9tIGRhdGV0aW1lID0gbm93IGRhdGV0aW1lLCBlbmFibGUgXCJUb21vcnJvd1wiLCBcIlllc3RlcmRheVwiIGFuZCBcIlRvZGF5XCJcbiAgICAgKiBAcGFyYW0geyhzdHJpbmd8bnVsbCk9fSBbbG9jYWxlPW51bGxdXG4gICAgICogQHJldHVybnMge3N0cmluZ31cbiAgICAgKi9cbiAgICBzaG9ydEZvcm1hdFZpYURhdGVUaW1lcyhcbiAgICAgICAgZnJvbURhdGVUaW1lLFxuICAgICAgICB0b0RhdGVUaW1lLFxuICAgICAgICBzdWZmaXggPSB0cnVlLFxuICAgICAgICBmcm9tVG9kYXkgPSB0cnVlLFxuICAgICAgICBsb2NhbGUgPSBudWxsXG4gICAgKSB7XG4gICAgICAgIHJldHVybiB0aGlzLl9mb3JtYXQoXG4gICAgICAgICAgICBmcm9tRGF0ZVRpbWUsXG4gICAgICAgICAgICB0b0RhdGVUaW1lLFxuICAgICAgICAgICAgZmFsc2UsXG4gICAgICAgICAgICB0cnVlLFxuICAgICAgICAgICAgc3VmZml4LFxuICAgICAgICAgICAgZnJvbVRvZGF5LFxuICAgICAgICAgICAgZmFsc2UsXG4gICAgICAgICAgICBsb2NhbGVcbiAgICAgICAgKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAcGFyYW0ge0RhdGV9IGZyb21EYXRlVGltZVxuICAgICAqIEBwYXJhbSB7RGF0ZX0gdG9EYXRlVGltZVxuICAgICAqIEBwYXJhbSB7Ym9vbGVhbj19IFtzdWZmaXg9dHJ1ZV0gdHJ1ZSAtIHdyYXAgXCJpbiAldGV4dCVcIiBvciBcIiV0ZXh0JSBhZ29cIlxuICAgICAqIEBwYXJhbSB7Ym9vbGVhbj19IFtmcm9tVG9kYXk9dHJ1ZV0gdHJ1ZSAtIGZyb20gZGF0ZXRpbWUgPSBub3cgZGF0ZXRpbWUsIGVuYWJsZSBcIlRvbW9ycm93XCIsIFwiWWVzdGVyZGF5XCIgYW5kIFwiVG9kYXlcIlxuICAgICAqIEBwYXJhbSB7KHN0cmluZ3xudWxsKT19IFtsb2NhbGU9bnVsbF1cbiAgICAgKiBAcmV0dXJucyB7c3RyaW5nfVxuICAgICAqL1xuICAgIGxvbmdGb3JtYXRWaWFEYXRlVGltZXMoXG4gICAgICAgIGZyb21EYXRlVGltZSxcbiAgICAgICAgdG9EYXRlVGltZSxcbiAgICAgICAgc3VmZml4ID0gdHJ1ZSxcbiAgICAgICAgZnJvbVRvZGF5ID0gdHJ1ZSxcbiAgICAgICAgbG9jYWxlID0gbnVsbFxuICAgICkge1xuICAgICAgICByZXR1cm4gdGhpcy5fZm9ybWF0KFxuICAgICAgICAgICAgZnJvbURhdGVUaW1lLFxuICAgICAgICAgICAgdG9EYXRlVGltZSxcbiAgICAgICAgICAgIGZhbHNlLFxuICAgICAgICAgICAgZmFsc2UsXG4gICAgICAgICAgICBzdWZmaXgsXG4gICAgICAgICAgICBmcm9tVG9kYXksXG4gICAgICAgICAgICBmYWxzZSxcbiAgICAgICAgICAgIGxvY2FsZVxuICAgICAgICApO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBwYXJhbSB7RGF0ZX0gZXhwZWN0RGF0ZVxuICAgICAqIEBwYXJhbSB7KERhdGV8bnVsbCk9fSBbY3VycmVudERhdGU9bnVsbF1cbiAgICAgKiBAcmV0dXJucyB7VGltZVJlbWFpbmluZ31cbiAgICAgKi9cbiAgICBnZXRUaW1lUmVtYWluaW5nKGV4cGVjdERhdGUsIGN1cnJlbnREYXRlID0gbnVsbCkge1xuICAgICAgICBpZiAoIShjdXJyZW50RGF0ZSBpbnN0YW5jZW9mIERhdGUpKSB7XG4gICAgICAgICAgICBjdXJyZW50RGF0ZSA9IG5ldyBEYXRlKCk7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgZGlmZiA9IGV4cGVjdERhdGUuZ2V0VGltZSgpIC0gY3VycmVudERhdGUuZ2V0VGltZSgpO1xuXG4gICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAndG90YWwnICAgOiBkaWZmLFxuICAgICAgICAgICAgJ2RheXMnICAgIDogTWF0aC5mbG9vcihkaWZmIC8gKDEwMDAgKiA2MCAqIDYwICogMjQpKSxcbiAgICAgICAgICAgICdob3VycycgICA6IE1hdGguZmxvb3IoKGRpZmYgLyAoMTAwMCAqIDYwICogNjApKSAlIDI0KSxcbiAgICAgICAgICAgICdtaW51dGVzJyA6IE1hdGguZmxvb3IoKGRpZmYgLyAxMDAwIC8gNjApICUgNjApLFxuICAgICAgICAgICAgJ3NlY29uZHMnIDogTWF0aC5mbG9vcigoZGlmZiAvIDEwMDApICUgNjApXG4gICAgICAgIH07XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQHByaXZhdGVcbiAgICAgKiBAcGFyYW0ge0RhdGV9IGZyb21EYXRlVGltZVxuICAgICAqIEBwYXJhbSB7RGF0ZX0gdG9EYXRlVGltZVxuICAgICAqIEBwYXJhbSB7Ym9vbGVhbn0gb25seURhdGVcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW59IHNob3J0Rm9ybWF0XG4gICAgICogQHBhcmFtIHtib29sZWFufSBhZGRTdWZmaXhcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW59IGZyb21Ub2RheVxuICAgICAqIEBwYXJhbSB7Ym9vbGVhbn0gZHVyYXRpb25cbiAgICAgKiBAcGFyYW0geyhzdHJpbmd8bnVsbCl9IGxvY2FsZVxuICAgICAqL1xuICAgIF9mb3JtYXQoXG4gICAgICAgIGZyb21EYXRlVGltZSxcbiAgICAgICAgdG9EYXRlVGltZSxcbiAgICAgICAgb25seURhdGUsXG4gICAgICAgIHNob3J0Rm9ybWF0LFxuICAgICAgICBhZGRTdWZmaXgsXG4gICAgICAgIGZyb21Ub2RheSxcbiAgICAgICAgZHVyYXRpb24sXG4gICAgICAgIGxvY2FsZVxuICAgICkge1xuICAgICAgICBpZiAob25seURhdGUgfHwgZHVyYXRpb24pIHtcbiAgICAgICAgICAgIFtmcm9tRGF0ZVRpbWUsIHRvRGF0ZVRpbWVdID0gcmVzZXRUaW1lKGZyb21EYXRlVGltZSwgdG9EYXRlVGltZSwgIW9ubHlEYXRlICYmIGR1cmF0aW9uKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IGRpZmYgPSB0aGlzLl9nZXRFeHRyYURpZmZEYXRhKGZyb21EYXRlVGltZSwgdG9EYXRlVGltZSwgc2hvcnRGb3JtYXQpO1xuICAgICAgICBjb25zdCBmdXR1cmUgPSBkaWZmLmludmVydCA9PT0gMDtcbiAgICAgICAgY29uc3QgaGFzVGltZSA9IGRpZmYuaCA+IDAgfHwgZGlmZi5pID4gMCB8fCBkaWZmLnMgPiAwO1xuICAgICAgICBjb25zdCBoYXNPbmx5U2Vjb25kcyA9IGRpZmYuaCA9PT0gMCAmJiBkaWZmLmkgPT09IDAgJiYgZGlmZi5zID4gMDtcbiAgICAgICAgY29uc3Qgc2hvd1RpbWUgPSBkaWZmLmRheXMgPCAyICYmIGhhc1RpbWU7XG4gICAgICAgIGNvbnN0IHVuaXRzID0ge307XG5cbiAgICAgICAgaWYgKGRpZmYueSA+IDApIHtcbiAgICAgICAgICAgIHVuaXRzLnkgPSBgJHt0aGlzLm51bWJlckZvcm1hdHRlcihkaWZmLnkpfSAke3RoaXMudHJhbnNsYXRvci50cmFuc0Nob2ljZSgvKiogQERlc2MoXCJ5ZWFyfHllYXJzXCIpICovICd5ZWFycycsIE1hdGguY2VpbChkaWZmLnkpLCB7fSwgbnVsbCwgbG9jYWxlKX1gO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKGRpZmYubSA+IDApIHtcbiAgICAgICAgICAgIHVuaXRzLm0gPSBgJHt0aGlzLm51bWJlckZvcm1hdHRlcihkaWZmLm0pfSAke3RoaXMudHJhbnNsYXRvci50cmFuc0Nob2ljZSgvKiogQERlc2MoXCJtb250aHxtb250aHNcIikgKi8gJ21vbnRocycsIE1hdGguY2VpbChkaWZmLm0pLCB7fSwgbnVsbCwgbG9jYWxlKX1gO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKFxuICAgICAgICAgICAgZGlmZi5kID4gMSB8fFxuICAgICAgICAgICAgKFxuICAgICAgICAgICAgICAgIGRpZmYuZCA9PT0gMSAmJiAoXG4gICAgICAgICAgICAgICAgICAgIE9iamVjdC5rZXlzKHVuaXRzKS5sZW5ndGggPiAwIHx8IChzaG93VGltZSAmJiAhaGFzT25seVNlY29uZHMpIHx8ICFmcm9tVG9kYXlcbiAgICAgICAgICAgICAgICApXG4gICAgICAgICAgICApXG4gICAgICAgICkge1xuICAgICAgICAgICAgdW5pdHMuZCA9IGAke3RoaXMubnVtYmVyRm9ybWF0dGVyKGRpZmYuZCl9ICR7dGhpcy50cmFuc2xhdG9yLnRyYW5zQ2hvaWNlKC8qKiBARGVzYyhcImRheXxkYXlzXCIpICovICdkYXlzJywgZGlmZi5kLCB7fSwgbnVsbCwgbG9jYWxlKX1gO1xuICAgICAgICB9IGVsc2UgaWYgKGRpZmYuZCA9PT0gMSAmJiAoIXNob3dUaW1lIHx8IGhhc09ubHlTZWNvbmRzKSAmJiBmcm9tVG9kYXkpIHtcbiAgICAgICAgICAgIGFkZFN1ZmZpeCA9IGZhbHNlO1xuICAgICAgICAgICAgaWYgKGZ1dHVyZSkge1xuICAgICAgICAgICAgICAgIHVuaXRzLmQgPSB0aGlzLnRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiVG9tb3Jyb3dcIikgKi8gJ3RvbW9ycm93Jywge30sIG51bGwsIGxvY2FsZSkudG9Mb3dlckNhc2UoKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdW5pdHMuZCA9IHRoaXMudHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJZZXN0ZXJkYXlcIikgKi8gJ3llc3RlcmRheScsIHt9LCBudWxsLCBsb2NhbGUpLnRvTG93ZXJDYXNlKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoc2hvd1RpbWUpIHtcbiAgICAgICAgICAgIGNvbnN0IHNob3J0eVRpbWUgPSAoZGlmZi5oID4gMCAmJiBkaWZmLmkgPiAwKSAmJiAhc2hvcnRGb3JtYXQ7XG5cbiAgICAgICAgICAgIGlmIChkaWZmLmggPiAwKSB7XG4gICAgICAgICAgICAgICAgaWYgKHNob3J0eVRpbWUpIHtcbiAgICAgICAgICAgICAgICAgICAgdW5pdHMuaCA9IHRoaXMudHJhbnNsYXRvci50cmFuc0Nob2ljZSgnaG91cnMuc2hvcnQnLCBkaWZmLmgsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvdW50OiB0aGlzLm51bWJlckZvcm1hdHRlcihkaWZmLmgpXG4gICAgICAgICAgICAgICAgICAgIH0sIG51bGwsIGxvY2FsZSk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdW5pdHMuaCA9IHRoaXMudHJhbnNsYXRvci50cmFuc0Nob2ljZSgnaG91cnMnLCBkaWZmLmgsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvdW50OiB0aGlzLm51bWJlckZvcm1hdHRlcihkaWZmLmgpXG4gICAgICAgICAgICAgICAgICAgIH0sIG51bGwsIGxvY2FsZSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBpZiAoZGlmZi5pID4gMCkge1xuICAgICAgICAgICAgICAgIGlmIChzaG9ydHlUaW1lKSB7XG4gICAgICAgICAgICAgICAgICAgIHVuaXRzLmkgPSB0aGlzLnRyYW5zbGF0b3IudHJhbnNDaG9pY2UoJ21pbnV0ZXMuc2hvcnQnLCBkaWZmLmksIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvdW50OiB0aGlzLm51bWJlckZvcm1hdHRlcihkaWZmLmkpXG4gICAgICAgICAgICAgICAgICAgIH0sIG51bGwsIGxvY2FsZSk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdW5pdHMuaSA9IHRoaXMudHJhbnNsYXRvci50cmFuc0Nob2ljZSgnbWludXRlcycsIGRpZmYuaSwge1xuICAgICAgICAgICAgICAgICAgICAgICAgY291bnQ6IHRoaXMubnVtYmVyRm9ybWF0dGVyKGRpZmYuaSlcbiAgICAgICAgICAgICAgICAgICAgfSwgbnVsbCwgbG9jYWxlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmIChoYXNPbmx5U2Vjb25kcyAmJiBPYmplY3Qua2V5cyh1bml0cykubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICAgICAgdW5pdHMucyA9IHRoaXMudHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJhIGZldyBzZWNvbmRzXCIpICovICdzZWNvbmRzJywge30sIG51bGwsIGxvY2FsZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoT2JqZWN0LmtleXModW5pdHMpLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgaWYgKGZyb21Ub2RheSAmJiBvbmx5RGF0ZSkge1xuICAgICAgICAgICAgICAgIGFkZFN1ZmZpeCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIHVuaXRzLnMgPSB0aGlzLnRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiVG9kYXlcIikgKi8gJ3RvZGF5Jywge30sIG51bGwsIGxvY2FsZSkudG9Mb3dlckNhc2UoKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdW5pdHMucyA9IHRoaXMudHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJhIGZldyBzZWNvbmRzXCIpICovICdzZWNvbmRzJywge30sIG51bGwsIGxvY2FsZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICBsZXQgZm9ybWF0dGVkO1xuICAgICAgICBpZiAoc2hvcnRGb3JtYXQpIHtcbiAgICAgICAgICAgIGZvcm1hdHRlZCA9IG9iamVjdFZhbHVlcyh1bml0cykuc2hpZnQoKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNvbnN0IHUgPSBbXTtcbiAgICAgICAgICAgIGxldCBzdGFydGVkID0gZmFsc2U7XG5cbiAgICAgICAgICAgIGZvciAobGV0IGkgb2YgWyd5JywgJ20nLCAnZCcsICdoJywgJ2knLCAncyddKSB7XG4gICAgICAgICAgICAgICAgaWYgKE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbCh1bml0cywgaSkpIHtcbiAgICAgICAgICAgICAgICAgICAgc3RhcnRlZCA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgIHUucHVzaCh1bml0c1tpXSk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIGlmIChzdGFydGVkKSB7XG4gICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgZm9ybWF0dGVkID0gdS5zbGljZSgwLCAyKS5qb2luKCcgJyk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoYWRkU3VmZml4KSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5fYWRkU3VmZml4KGZ1dHVyZSwgZm9ybWF0dGVkLCBsb2NhbGUpXG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gZm9ybWF0dGVkO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBwcml2YXRlXG4gICAgICogQHBhcmFtIHtEYXRlfSBmcm9tRGF0ZVRpbWVcbiAgICAgKiBAcGFyYW0ge0RhdGV9IHRvRGF0ZVRpbWVcbiAgICAgKiBAcGFyYW0ge2Jvb2xlYW59IHNob3J0XG4gICAgICogQHJldHVybnMge0RhdGVUaW1lRGlmZn1cbiAgICAgKi9cbiAgICBfZ2V0RXh0cmFEaWZmRGF0YShmcm9tRGF0ZVRpbWUsIHRvRGF0ZVRpbWUsIHNob3J0KSB7XG4gICAgICAgIGxldCBpbnZlcnQgPSBmYWxzZTtcbiAgICAgICAgbGV0IHRzMSA9IGZyb21EYXRlVGltZS5nZXRUaW1lKCk7XG4gICAgICAgIGxldCB0czIgPSB0b0RhdGVUaW1lLmdldFRpbWUoKTtcblxuICAgICAgICBpZiAodHMxID49IHRzMikge1xuICAgICAgICAgICAgW2Zyb21EYXRlVGltZSwgdG9EYXRlVGltZV0gPSBbdG9EYXRlVGltZSwgZnJvbURhdGVUaW1lXTtcbiAgICAgICAgICAgIGludmVydCA9IHRydWU7XG4gICAgICAgIH1cblxuICAgICAgICBjb25zdCBhID0gcGFyc2VEYXRlKGZyb21EYXRlVGltZSk7XG4gICAgICAgIGNvbnN0IGIgPSBwYXJzZURhdGUodG9EYXRlVGltZSk7XG5cbiAgICAgICAgbGV0IGludGVydmFsID0ge307XG4gICAgICAgIGludGVydmFsLnkgPSBiLnkgLSBhLnk7XG4gICAgICAgIGludGVydmFsLm0gPSBiLm0gLSBhLm07XG4gICAgICAgIGludGVydmFsLmQgPSBiLmQgLSBhLmQ7XG4gICAgICAgIGludGVydmFsLmggPSBiLmggLSBhLmg7XG4gICAgICAgIGludGVydmFsLmkgPSBiLmkgLSBhLmk7XG4gICAgICAgIGludGVydmFsLnMgPSBiLnMgLSBhLnM7XG4gICAgICAgIGludGVydmFsLmludmVydCA9IGludmVydCA/IDEgOiAwO1xuICAgICAgICBpbnRlcnZhbC5kYXlzID0gcGFyc2VJbnQoTWF0aC5hYnMoKHRzMSAtIHRzMikgLyAxMDAwIC8gNjAgLyA2MCAvIDI0KSwgMTApO1xuXG4gICAgICAgIGlmIChpbnZlcnQpIHtcbiAgICAgICAgICAgIGludGVydmFsID0gZGF0ZU5vcm1hbGl6ZShhLCBpbnRlcnZhbCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBpbnRlcnZhbCA9IGRhdGVOb3JtYWxpemUoYiwgaW50ZXJ2YWwpO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3QgcmVzdWx0ID0gey4uLmludGVydmFsfTtcbiAgICAgICAgcmVzdWx0LnkgPSByZXN1bHQubSA9IHJlc3VsdC5kID0gcmVzdWx0LmggPSByZXN1bHQuaSA9IHJlc3VsdC5zID0gMDtcblxuICAgICAgICBjb25zdCBuZXdEYXRlID0gbmV3IERhdGUodHMxKTtcbiAgICAgICAgbGV0IGZyYWN0O1xuXG4gICAgICAgIC8vIHllYXJzXG4gICAgICAgIGlmIChpbnRlcnZhbC55ID4gMCkge1xuICAgICAgICAgICAgbmV3RGF0ZS5zZXRGdWxsWWVhcihpbnZlcnQgPyBuZXdEYXRlLmdldEZ1bGxZZWFyKCkgLSBpbnRlcnZhbC55IDogbmV3RGF0ZS5nZXRGdWxsWWVhcigpICsgaW50ZXJ2YWwueSk7XG4gICAgICAgIH1cbiAgICAgICAgZnJhY3QgPSAoZ2V0U2Vjb25kcyhuZXdEYXRlLCB0czIpIC8gNjAgLyA2MCAvIDI0KSAvIGRheXNJblllYXIobmV3RGF0ZSk7XG4gICAgICAgIHJlc3VsdC55ID0gaW50ZXJ2YWwueTtcbiAgICAgICAgaWYgKGZyYWN0ID4gMC45KSB7XG4gICAgICAgICAgICByZXN1bHQueSArPSAxO1xuXG4gICAgICAgICAgICByZXR1cm4gcmVzdWx0O1xuICAgICAgICB9IGVsc2UgaWYgKHJlc3VsdC55ID4gMCAmJiBzaG9ydCkge1xuICAgICAgICAgICAgcmVzdWx0LnkgKz0gcm91bmQoZnJhY3QpO1xuXG4gICAgICAgICAgICByZXR1cm4gcmVzdWx0O1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gbW9udGhzXG4gICAgICAgIGlmIChpbnRlcnZhbC5tID4gMCkge1xuICAgICAgICAgICAgbmV3RGF0ZS5zZXRNb250aChpbnZlcnQgPyBuZXdEYXRlLmdldE1vbnRoKCkgLSBpbnRlcnZhbC5tIDogbmV3RGF0ZS5nZXRNb250aCgpICsgaW50ZXJ2YWwubSk7XG4gICAgICAgIH1cbiAgICAgICAgZnJhY3QgPSAoZ2V0U2Vjb25kcyhuZXdEYXRlLCB0czIpIC8gNjAgLyA2MCAvIDI0KSAvIGRheXNJbk1vbnRoKG5ld0RhdGUuZ2V0TW9udGgoKSwgbmV3RGF0ZS5nZXRGdWxsWWVhcigpKTtcbiAgICAgICAgcmVzdWx0Lm0gPSBpbnRlcnZhbC5tO1xuICAgICAgICBpZiAoZnJhY3QgPiAwLjkpIHtcbiAgICAgICAgICAgIHJlc3VsdC5tICs9IDE7XG5cbiAgICAgICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgICAgIH0gZWxzZSBpZiAocmVzdWx0Lm0gPiAwICYmIHNob3J0KSB7XG4gICAgICAgICAgICByZXN1bHQubSArPSByb3VuZChmcmFjdCk7XG5cbiAgICAgICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBkYXlzXG4gICAgICAgIGlmIChpbnRlcnZhbC5kID4gMCkge1xuICAgICAgICAgICAgbmV3RGF0ZS5zZXREYXRlKGludmVydCA/IG5ld0RhdGUuZ2V0RGF0ZSgpIC0gaW50ZXJ2YWwuZCA6IG5ld0RhdGUuZ2V0RGF0ZSgpICsgaW50ZXJ2YWwuZCk7XG4gICAgICAgIH1cbiAgICAgICAgZnJhY3QgPSBnZXRTZWNvbmRzKG5ld0RhdGUsIHRzMikgLyA2MCAvIDYwIC8gMjQ7XG4gICAgICAgIHJlc3VsdC5kID0gaW50ZXJ2YWwuZDtcbiAgICAgICAgaWYgKGZyYWN0ID4gMC45KSB7XG4gICAgICAgICAgICByZXN1bHQuZCArPSAxO1xuXG4gICAgICAgICAgICByZXR1cm4gcmVzdWx0O1xuICAgICAgICB9IGVsc2UgaWYgKHJlc3VsdC5kID49IDIgfHwgKHJlc3VsdC5kID4gMCAmJiBmcmFjdCA8IDAuMSkpIHtcbiAgICAgICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBob3Vyc1xuICAgICAgICBpZiAoaW50ZXJ2YWwuaCA+IDApIHtcbiAgICAgICAgICAgIG5ld0RhdGUuc2V0SG91cnMoaW52ZXJ0ID8gbmV3RGF0ZS5nZXRIb3VycygpIC0gaW50ZXJ2YWwuaCA6IG5ld0RhdGUuZ2V0SG91cnMoKSArIGludGVydmFsLmgpO1xuICAgICAgICB9XG4gICAgICAgIGZyYWN0ID0gZ2V0U2Vjb25kcyhuZXdEYXRlLCB0czIpIC8gNjAgLyA2MDtcbiAgICAgICAgcmVzdWx0LmggPSBpbnRlcnZhbC5oO1xuICAgICAgICBpZiAoZnJhY3QgPiAwLjkpIHtcbiAgICAgICAgICAgIHJlc3VsdC5oICs9IDE7XG5cbiAgICAgICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBtaW51dGVzXG4gICAgICAgIGlmIChpbnRlcnZhbC5pID4gMCkge1xuICAgICAgICAgICAgbmV3RGF0ZS5zZXRNaW51dGVzKGludmVydCA/IG5ld0RhdGUuZ2V0TWludXRlcygpIC0gaW50ZXJ2YWwuaSA6IG5ld0RhdGUuZ2V0TWludXRlcygpICsgaW50ZXJ2YWwuaSk7XG4gICAgICAgIH1cbiAgICAgICAgZnJhY3QgPSBnZXRTZWNvbmRzKG5ld0RhdGUsIHRzMikgLyA2MDtcbiAgICAgICAgcmVzdWx0LmkgPSBpbnRlcnZhbC5pO1xuICAgICAgICBpZiAoZnJhY3QgPiAwLjkpIHtcbiAgICAgICAgICAgIHJlc3VsdC5pICs9IDE7XG5cbiAgICAgICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBzZWNvbmRzXG4gICAgICAgIGlmIChpbnRlcnZhbC5zID4gMCkge1xuICAgICAgICAgICAgcmVzdWx0LnMgPSBpbnRlcnZhbC5zO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIHJlc3VsdDtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAcHJpdmF0ZVxuICAgICAqIEBwYXJhbSB7Ym9vbGVhbn0gZnV0dXJlXG4gICAgICogQHBhcmFtIHtzdHJpbmd9IHRleHRcbiAgICAgKiBAcGFyYW0ge3N0cmluZ3xudWxsfSBsb2NhbGVcbiAgICAgKiBAcmV0dXJucyB7c3RyaW5nfVxuICAgICAqL1xuICAgIF9hZGRTdWZmaXgoZnV0dXJlLCB0ZXh0LCBsb2NhbGUpIHtcbiAgICAgICAgaWYgKGZ1dHVyZSkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMudHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJpbiAldGV4dCVcIikgKi8gJ3JlbGF0aXZlX2RhdGUuZnV0dXJlJywgeyd0ZXh0JzogdGV4dH0sIG51bGwsIGxvY2FsZSk7XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gdGhpcy50cmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIiV0ZXh0JSBhZ29cIikgKi8gJ3JlbGF0aXZlX2RhdGUucGFzdCcsIHsndGV4dCc6IHRleHR9LCBudWxsLCBsb2NhbGUpO1xuICAgIH1cbn1cbiJdLCJuYW1lcyI6WyJ3ZWJwYWNrVW5pdmVyc2FsTW9kdWxlRGVmaW5pdGlvbiIsInJvb3QiLCJmYWN0b3J5IiwiZXhwb3J0cyIsIm1vZHVsZSIsImRlZmluZSIsImFtZCIsInNlbGYiLCJpbnN0YWxsZWRNb2R1bGVzIiwiX193ZWJwYWNrX3JlcXVpcmVfXyIsIm1vZHVsZUlkIiwiaSIsImwiLCJtb2R1bGVzIiwiY2FsbCIsIm0iLCJjIiwiZCIsIm5hbWUiLCJnZXR0ZXIiLCJvIiwiT2JqZWN0IiwiZGVmaW5lUHJvcGVydHkiLCJlbnVtZXJhYmxlIiwiZ2V0IiwiciIsIlN5bWJvbCIsInRvU3RyaW5nVGFnIiwidmFsdWUiLCJ0IiwibW9kZSIsIl9fZXNNb2R1bGUiLCJucyIsImNyZWF0ZSIsImtleSIsImJpbmQiLCJuIiwiZ2V0RGVmYXVsdCIsImdldE1vZHVsZUV4cG9ydHMiLCJvYmplY3QiLCJwcm9wZXJ0eSIsInByb3RvdHlwZSIsImhhc093blByb3BlcnR5IiwicCIsInMiLCJwYWQiLCJudW1iZXIiLCJ0b1N0cmluZyIsImZvcm1hdERhdGUiLCJkYXRlIiwid2l0aFRpbWUiLCJhcmd1bWVudHMiLCJsZW5ndGgiLCJ1bmRlZmluZWQiLCJmb3JtYXR0ZWQiLCJjb25jYXQiLCJnZXRGdWxsWWVhciIsImdldE1vbnRoIiwiZ2V0RGF0ZSIsImdldEhvdXJzIiwiZ2V0TWludXRlcyIsImdldFNlY29uZHMiLCJwYXJzZURhdGUiLCJwYXJ0cyIsInNwbGl0Iiwia2V5cyIsIm5ld0FycmF5IiwicGFyc2VJbnQiLCJkYXRlTm9ybWFsaXplIiwiYmFzZSIsImRhdGVSYW5nZUxpbWl0IiwiZGF0ZVJhbmdlTGltaXREYXlzIiwic3RhcnQiLCJlbmQiLCJhZGoiLCJhIiwiYiIsInJlc3VsdCIsImRheXNJbk1vbnRoTGVhcCIsImRheXNJbk1vbnRoIiwieWVhciIsInkiLCJtb250aCIsImRheXMiLCJsZWFweWVhciIsImludmVydCIsInJlc2V0VGltZSIsImRhdGUxIiwiZGF0ZTIiLCJvbmx5U2Vjb25kcyIsIkRhdGUiLCJkYXlzSW5ZZWFyIiwidHMyIiwiTWF0aCIsImFicyIsImdldFRpbWUiLCJyb3VuZCIsIm51bSIsIm9iamVjdFZhbHVlcyIsIm9iaiIsInZhbHMiLCJwcm9wIiwicHVzaCIsIkRhdGVUaW1lRGlmZiIsInRyYW5zbGF0b3IiLCJudW1iZXJGb3JtYXR0ZXIiLCJfY2xhc3NDYWxsQ2hlY2siLCJmcm9tRGF0ZVRpbWUiLCJ0b0RhdGVUaW1lIiwib25seURhdGUiLCJzaG9ydEZvcm1hdCIsImZyb21Ub2RheSIsImxvY2FsZSIsIl9mb3JtYXQiLCJzZWNvbmRzIiwiaG91cnMiLCJmbG9vciIsIm1pbnV0ZXMiLCJ1bml0cyIsInRyYW5zQ2hvaWNlIiwiY291bnQiLCJ0cmFucyIsImpvaW4iLCJzdWZmaXgiLCJleHBlY3REYXRlIiwiY3VycmVudERhdGUiLCJkaWZmIiwiYWRkU3VmZml4IiwiZHVyYXRpb24iLCJfcmVzZXRUaW1lIiwiX3Jlc2V0VGltZTIiLCJfc2xpY2VkVG9BcnJheSIsIl9nZXRFeHRyYURpZmZEYXRhIiwiZnV0dXJlIiwiaGFzVGltZSIsImgiLCJoYXNPbmx5U2Vjb25kcyIsInNob3dUaW1lIiwiY2VpbCIsInRvTG93ZXJDYXNlIiwic2hvcnR5VGltZSIsInNoaWZ0IiwidSIsInN0YXJ0ZWQiLCJfaTIiLCJfYXJyMiIsInNsaWNlIiwiX2FkZFN1ZmZpeCIsIl9zaG9ydCIsInRzMSIsIl9yZWYiLCJpbnRlcnZhbCIsIl9vYmplY3RTcHJlYWQiLCJuZXdEYXRlIiwiZnJhY3QiLCJzZXRGdWxsWWVhciIsInNldE1vbnRoIiwic2V0RGF0ZSIsInNldEhvdXJzIiwic2V0TWludXRlcyIsInRleHQiXSwic291cmNlUm9vdCI6IiJ9