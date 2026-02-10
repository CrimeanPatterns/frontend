"use strict";
(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["page-manager/Lounge.form"],{

/***/ "./assets/bem/block/page/manager/Lounge.form.entry.ts":
/*!************************************************************!*\
  !*** ./assets/bem/block/page/manager/Lounge.form.entry.ts ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.string.trim.js */ "./node_modules/core-js/modules/es.string.trim.js");
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _Lounge_form_css__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Lounge.form.css */ "./assets/bem/block/page/manager/Lounge.form.css");
/* harmony import */ var _ts_service_on_ready__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../ts/service/on-ready */ "./assets/bem/ts/service/on-ready.ts");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");







(0,_ts_service_on_ready__WEBPACK_IMPORTED_MODULE_6__["default"])(function () {
  var openingHours = document.getElementById('lounge_openingHours');
  if (openingHours) {
    var html = "\n            <div class=\"opening-hours-actions\">\n                <span class=\"status\"></span>\n            </div>\n        ";
    openingHours.insertAdjacentHTML('afterend', html);
    openingHours.addEventListener('input', function () {
      checkOpeningHours(this);
    });
    openingHours.style.height = 'auto';
    checkOpeningHours(openingHours);
    openingHours.style.height = openingHours.scrollHeight.toString() + 'px';
  }
  function setState(state) {
    var isError = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    $('.opening-hours-actions .status').removeClass('success error').addClass(isError ? 'error' : 'success').text(state);
  }
  function checkOpeningHours(elem) {
    var ugly = elem.value;
    try {
      var obj = JSON.parse(ugly);
      elem.value = JSON.stringify(obj, null, 4);
      setState('Valid JSON');
    } catch (e) {
      if (ugly.indexOf('{') === 0) {
        setState('Invalid JSON', true);
      } else if (ugly.trim() === '') {
        setState('Empty');
      } else {
        setState('Raw Text');
      }
    }
  }
  var textareas = document.querySelectorAll('textarea:not(#lounge_openingHours)');
  function autoAdjustTextareaHeight(textarea) {
    textarea.style.height = 'auto'; // Сбросить высоту
    textarea.style.height = textarea.scrollHeight.toString() + 'px';
  }
  textareas.forEach(function (textarea) {
    textarea.addEventListener('input', function () {
      autoAdjustTextareaHeight(textarea);
    });
    autoAdjustTextareaHeight(textarea);
  });
  var preview = null;
  var loungeChanges = document.querySelectorAll('a.lounge-changes');
  loungeChanges.forEach(function (elem) {
    elem.addEventListener('click', function (e) {
      e.preventDefault();
      var dataChanges = elem.getAttribute('data-changes');
      if (!dataChanges) {
        return;
      }
      var data = JSON.parse(dataChanges);
      if (preview) {
        preview.close();
      }
      preview = window.open("", "Preview", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1024, height=600");
      var table = '<table style="width: 100%; border: 1px solid black">' + '<tr style="background-color: beige; font-size: 1.2em; font-family: monospace;"><td style="width: 15%; padding: 5px;">Change Date</td><td style="width: 15%; padding: 5px;">Property</td><td style="min-width: 100px; padding: 5px;">Old Value</td><td style="min-width: 100px; padding: 5px;">New Value</td></tr>';
      data.forEach(function (item) {
        table += '<tr style="font-size: small;">' + '<td style="padding: 5px;">' + item.ChangeDate + '</td>' + '<td style="padding: 5px;">' + item.Property + '</td>' + '<td style="background-color: cornsilk; padding: 5px;">' + item.OldVal + '</td>' + '<td style="background-color: greenyellow; padding: 5px;">' + item.NewVal + '</td>' + '</tr>';
      });
      table += '</table>';
      if (preview) {
        preview.document.write(table);
        preview.focus();
      }
    });
  });
});

/***/ }),

/***/ "./assets/bem/ts/service/on-ready.ts":
/*!*******************************************!*\
  !*** ./assets/bem/ts/service/on-ready.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ onReady)
/* harmony export */ });
function onReady(callback) {
  if (document.readyState === 'loading') {
    // The DOM is not yet ready.
    document.addEventListener('DOMContentLoaded', callback);
  } else {
    // The DOM is already ready.
    callback();
  }
}

/***/ }),

/***/ "./node_modules/core-js/internals/array-for-each.js":
/*!**********************************************************!*\
  !*** ./node_modules/core-js/internals/array-for-each.js ***!
  \**********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {


var $forEach = (__webpack_require__(/*! ../internals/array-iteration */ "./node_modules/core-js/internals/array-iteration.js").forEach);
var arrayMethodIsStrict = __webpack_require__(/*! ../internals/array-method-is-strict */ "./node_modules/core-js/internals/array-method-is-strict.js");

var STRICT_METHOD = arrayMethodIsStrict('forEach');

// `Array.prototype.forEach` method implementation
// https://tc39.es/ecma262/#sec-array.prototype.foreach
module.exports = !STRICT_METHOD ? function forEach(callbackfn /* , thisArg */) {
  return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
} : [].forEach;


/***/ }),

/***/ "./node_modules/core-js/internals/does-not-exceed-safe-integer.js":
/*!************************************************************************!*\
  !*** ./node_modules/core-js/internals/does-not-exceed-safe-integer.js ***!
  \************************************************************************/
/***/ ((module) => {


var $TypeError = TypeError;
var MAX_SAFE_INTEGER = 0x1FFFFFFFFFFFFF; // 2 ** 53 - 1 == 9007199254740991

module.exports = function (it) {
  if (it > MAX_SAFE_INTEGER) throw $TypeError('Maximum allowed index exceeded');
  return it;
};


/***/ }),

/***/ "./node_modules/core-js/internals/string-trim.js":
/*!*******************************************************!*\
  !*** ./node_modules/core-js/internals/string-trim.js ***!
  \*******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {


var uncurryThis = __webpack_require__(/*! ../internals/function-uncurry-this */ "./node_modules/core-js/internals/function-uncurry-this.js");
var requireObjectCoercible = __webpack_require__(/*! ../internals/require-object-coercible */ "./node_modules/core-js/internals/require-object-coercible.js");
var toString = __webpack_require__(/*! ../internals/to-string */ "./node_modules/core-js/internals/to-string.js");
var whitespaces = __webpack_require__(/*! ../internals/whitespaces */ "./node_modules/core-js/internals/whitespaces.js");

var replace = uncurryThis(''.replace);
var ltrim = RegExp('^[' + whitespaces + ']+');
var rtrim = RegExp('(^|[^' + whitespaces + '])[' + whitespaces + ']+$');

// `String.prototype.{ trim, trimStart, trimEnd, trimLeft, trimRight }` methods implementation
var createMethod = function (TYPE) {
  return function ($this) {
    var string = toString(requireObjectCoercible($this));
    if (TYPE & 1) string = replace(string, ltrim, '');
    if (TYPE & 2) string = replace(string, rtrim, '$1');
    return string;
  };
};

module.exports = {
  // `String.prototype.{ trimLeft, trimStart }` methods
  // https://tc39.es/ecma262/#sec-string.prototype.trimstart
  start: createMethod(1),
  // `String.prototype.{ trimRight, trimEnd }` methods
  // https://tc39.es/ecma262/#sec-string.prototype.trimend
  end: createMethod(2),
  // `String.prototype.trim` method
  // https://tc39.es/ecma262/#sec-string.prototype.trim
  trim: createMethod(3)
};


/***/ }),

/***/ "./node_modules/core-js/internals/whitespaces.js":
/*!*******************************************************!*\
  !*** ./node_modules/core-js/internals/whitespaces.js ***!
  \*******************************************************/
/***/ ((module) => {


// a string of all valid unicode whitespaces
module.exports = '\u0009\u000A\u000B\u000C\u000D\u0020\u00A0\u1680\u2000\u2001\u2002' +
  '\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028\u2029\uFEFF';


/***/ }),

/***/ "./node_modules/core-js/modules/es.array.concat.js":
/*!*********************************************************!*\
  !*** ./node_modules/core-js/modules/es.array.concat.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/core-js/internals/fails.js");
var isArray = __webpack_require__(/*! ../internals/is-array */ "./node_modules/core-js/internals/is-array.js");
var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/core-js/internals/is-object.js");
var toObject = __webpack_require__(/*! ../internals/to-object */ "./node_modules/core-js/internals/to-object.js");
var lengthOfArrayLike = __webpack_require__(/*! ../internals/length-of-array-like */ "./node_modules/core-js/internals/length-of-array-like.js");
var doesNotExceedSafeInteger = __webpack_require__(/*! ../internals/does-not-exceed-safe-integer */ "./node_modules/core-js/internals/does-not-exceed-safe-integer.js");
var createProperty = __webpack_require__(/*! ../internals/create-property */ "./node_modules/core-js/internals/create-property.js");
var arraySpeciesCreate = __webpack_require__(/*! ../internals/array-species-create */ "./node_modules/core-js/internals/array-species-create.js");
var arrayMethodHasSpeciesSupport = __webpack_require__(/*! ../internals/array-method-has-species-support */ "./node_modules/core-js/internals/array-method-has-species-support.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/core-js/internals/well-known-symbol.js");
var V8_VERSION = __webpack_require__(/*! ../internals/engine-v8-version */ "./node_modules/core-js/internals/engine-v8-version.js");

var IS_CONCAT_SPREADABLE = wellKnownSymbol('isConcatSpreadable');

// We can't use this feature detection in V8 since it causes
// deoptimization and serious performance degradation
// https://github.com/zloirock/core-js/issues/679
var IS_CONCAT_SPREADABLE_SUPPORT = V8_VERSION >= 51 || !fails(function () {
  var array = [];
  array[IS_CONCAT_SPREADABLE] = false;
  return array.concat()[0] !== array;
});

var isConcatSpreadable = function (O) {
  if (!isObject(O)) return false;
  var spreadable = O[IS_CONCAT_SPREADABLE];
  return spreadable !== undefined ? !!spreadable : isArray(O);
};

var FORCED = !IS_CONCAT_SPREADABLE_SUPPORT || !arrayMethodHasSpeciesSupport('concat');

// `Array.prototype.concat` method
// https://tc39.es/ecma262/#sec-array.prototype.concat
// with adding support of @@isConcatSpreadable and @@species
$({ target: 'Array', proto: true, arity: 1, forced: FORCED }, {
  // eslint-disable-next-line no-unused-vars -- required for `.length`
  concat: function concat(arg) {
    var O = toObject(this);
    var A = arraySpeciesCreate(O, 0);
    var n = 0;
    var i, k, length, len, E;
    for (i = -1, length = arguments.length; i < length; i++) {
      E = i === -1 ? O : arguments[i];
      if (isConcatSpreadable(E)) {
        len = lengthOfArrayLike(E);
        doesNotExceedSafeInteger(n + len);
        for (k = 0; k < len; k++, n++) if (k in E) createProperty(A, n, E[k]);
      } else {
        doesNotExceedSafeInteger(n + 1);
        createProperty(A, n++, E);
      }
    }
    A.length = n;
    return A;
  }
});


/***/ }),

/***/ "./node_modules/core-js/modules/es.array.filter.js":
/*!*********************************************************!*\
  !*** ./node_modules/core-js/modules/es.array.filter.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var $filter = (__webpack_require__(/*! ../internals/array-iteration */ "./node_modules/core-js/internals/array-iteration.js").filter);
var arrayMethodHasSpeciesSupport = __webpack_require__(/*! ../internals/array-method-has-species-support */ "./node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('filter');

// `Array.prototype.filter` method
// https://tc39.es/ecma262/#sec-array.prototype.filter
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  filter: function filter(callbackfn /* , thisArg */) {
    return $filter(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "./node_modules/core-js/modules/es.array.join.js":
/*!*******************************************************!*\
  !*** ./node_modules/core-js/modules/es.array.join.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var uncurryThis = __webpack_require__(/*! ../internals/function-uncurry-this */ "./node_modules/core-js/internals/function-uncurry-this.js");
var IndexedObject = __webpack_require__(/*! ../internals/indexed-object */ "./node_modules/core-js/internals/indexed-object.js");
var toIndexedObject = __webpack_require__(/*! ../internals/to-indexed-object */ "./node_modules/core-js/internals/to-indexed-object.js");
var arrayMethodIsStrict = __webpack_require__(/*! ../internals/array-method-is-strict */ "./node_modules/core-js/internals/array-method-is-strict.js");

var nativeJoin = uncurryThis([].join);

var ES3_STRINGS = IndexedObject !== Object;
var FORCED = ES3_STRINGS || !arrayMethodIsStrict('join', ',');

// `Array.prototype.join` method
// https://tc39.es/ecma262/#sec-array.prototype.join
$({ target: 'Array', proto: true, forced: FORCED }, {
  join: function join(separator) {
    return nativeJoin(toIndexedObject(this), separator === undefined ? ',' : separator);
  }
});


/***/ }),

/***/ "./node_modules/core-js/modules/es.array.map.js":
/*!******************************************************!*\
  !*** ./node_modules/core-js/modules/es.array.map.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var $map = (__webpack_require__(/*! ../internals/array-iteration */ "./node_modules/core-js/internals/array-iteration.js").map);
var arrayMethodHasSpeciesSupport = __webpack_require__(/*! ../internals/array-method-has-species-support */ "./node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('map');

// `Array.prototype.map` method
// https://tc39.es/ecma262/#sec-array.prototype.map
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  map: function map(callbackfn /* , thisArg */) {
    return $map(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "./node_modules/core-js/modules/web.dom-collections.for-each.js":
/*!**********************************************************************!*\
  !*** ./node_modules/core-js/modules/web.dom-collections.for-each.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var global = __webpack_require__(/*! ../internals/global */ "./node_modules/core-js/internals/global.js");
var DOMIterables = __webpack_require__(/*! ../internals/dom-iterables */ "./node_modules/core-js/internals/dom-iterables.js");
var DOMTokenListPrototype = __webpack_require__(/*! ../internals/dom-token-list-prototype */ "./node_modules/core-js/internals/dom-token-list-prototype.js");
var forEach = __webpack_require__(/*! ../internals/array-for-each */ "./node_modules/core-js/internals/array-for-each.js");
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/core-js/internals/create-non-enumerable-property.js");

var handlePrototype = function (CollectionPrototype) {
  // some Chrome versions have non-configurable methods on DOMTokenList
  if (CollectionPrototype && CollectionPrototype.forEach !== forEach) try {
    createNonEnumerableProperty(CollectionPrototype, 'forEach', forEach);
  } catch (error) {
    CollectionPrototype.forEach = forEach;
  }
};

for (var COLLECTION_NAME in DOMIterables) {
  if (DOMIterables[COLLECTION_NAME]) {
    handlePrototype(global[COLLECTION_NAME] && global[COLLECTION_NAME].prototype);
  }
}

handlePrototype(DOMTokenListPrototype);


/***/ }),

/***/ "./assets/bem/block/page/manager/Lounge.form.css":
/*!*******************************************************!*\
  !*** ./assets/bem/block/page/manager/Lounge.form.css ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js","vendors-node_modules_core-js_internals_array-slice-simple_js-node_modules_core-js_internals_f-4bac30","vendors-node_modules_core-js_modules_es_string_replace_js","vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444","vendors-node_modules_core-js_internals_array-method-is-strict_js-node_modules_core-js_modules-ea2489","vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41","web_assets_common_vendors_jquery_dist_jquery_js"], () => (__webpack_exec__("./assets/bem/block/page/manager/Lounge.form.entry.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoicGFnZS1tYW5hZ2VyL0xvdW5nZS5mb3JtLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7QUFBMkI7QUFDd0I7QUFDbkRBLGdFQUFPLENBQUMsWUFBTTtFQUNWLElBQU1DLFlBQVksR0FBR0MsUUFBUSxDQUFDQyxjQUFjLENBQUMscUJBQXFCLENBQUM7RUFDbkUsSUFBSUYsWUFBWSxFQUFFO0lBQ2QsSUFBTUcsSUFBSSxzSUFJVDtJQUNESCxZQUFZLENBQUNJLGtCQUFrQixDQUFDLFVBQVUsRUFBRUQsSUFBSSxDQUFDO0lBQ2pESCxZQUFZLENBQUNLLGdCQUFnQixDQUFDLE9BQU8sRUFBRSxZQUFZO01BQy9DQyxpQkFBaUIsQ0FBQyxJQUFJLENBQUM7SUFDM0IsQ0FBQyxDQUFDO0lBQ0ZOLFlBQVksQ0FBQ08sS0FBSyxDQUFDQyxNQUFNLEdBQUcsTUFBTTtJQUNsQ0YsaUJBQWlCLENBQUNOLFlBQVksQ0FBQztJQUMvQkEsWUFBWSxDQUFDTyxLQUFLLENBQUNDLE1BQU0sR0FBSVIsWUFBWSxDQUFDUyxZQUFZLENBQUVDLFFBQVEsQ0FBQyxDQUFDLEdBQUcsSUFBSTtFQUM3RTtFQUNBLFNBQVNDLFFBQVFBLENBQUNDLEtBQUssRUFBbUI7SUFBQSxJQUFqQkMsT0FBTyxHQUFBQyxTQUFBLENBQUFDLE1BQUEsUUFBQUQsU0FBQSxRQUFBRSxTQUFBLEdBQUFGLFNBQUEsTUFBRyxLQUFLO0lBQ3BDRyxDQUFDLENBQUMsZ0NBQWdDLENBQUMsQ0FDOUJDLFdBQVcsQ0FBQyxlQUFlLENBQUMsQ0FDNUJDLFFBQVEsQ0FBQ04sT0FBTyxHQUFHLE9BQU8sR0FBRyxTQUFTLENBQUMsQ0FDdkNPLElBQUksQ0FBQ1IsS0FBSyxDQUFDO0VBQ3BCO0VBQ0EsU0FBU04saUJBQWlCQSxDQUFDZSxJQUFJLEVBQUU7SUFDN0IsSUFBTUMsSUFBSSxHQUFHRCxJQUFJLENBQUNFLEtBQUs7SUFDdkIsSUFBSTtNQUNBLElBQU1DLEdBQUcsR0FBR0MsSUFBSSxDQUFDQyxLQUFLLENBQUNKLElBQUksQ0FBQztNQUM1QkQsSUFBSSxDQUFDRSxLQUFLLEdBQUdFLElBQUksQ0FBQ0UsU0FBUyxDQUFDSCxHQUFHLEVBQUUsSUFBSSxFQUFFLENBQUMsQ0FBQztNQUN6Q2IsUUFBUSxDQUFDLFlBQVksQ0FBQztJQUMxQixDQUFDLENBQ0QsT0FBT2lCLENBQUMsRUFBRTtNQUNOLElBQUlOLElBQUksQ0FBQ08sT0FBTyxDQUFDLEdBQUcsQ0FBQyxLQUFLLENBQUMsRUFBRTtRQUN6QmxCLFFBQVEsQ0FBQyxjQUFjLEVBQUUsSUFBSSxDQUFDO01BQ2xDLENBQUMsTUFDSSxJQUFJVyxJQUFJLENBQUNRLElBQUksQ0FBQyxDQUFDLEtBQUssRUFBRSxFQUFFO1FBQ3pCbkIsUUFBUSxDQUFDLE9BQU8sQ0FBQztNQUNyQixDQUFDLE1BQ0k7UUFDREEsUUFBUSxDQUFDLFVBQVUsQ0FBQztNQUN4QjtJQUNKO0VBQ0o7RUFDQSxJQUFNb0IsU0FBUyxHQUFHOUIsUUFBUSxDQUFDK0IsZ0JBQWdCLENBQUMsb0NBQW9DLENBQUM7RUFDakYsU0FBU0Msd0JBQXdCQSxDQUFDQyxRQUFRLEVBQUU7SUFDeENBLFFBQVEsQ0FBQzNCLEtBQUssQ0FBQ0MsTUFBTSxHQUFHLE1BQU0sQ0FBQyxDQUFDO0lBQ2hDMEIsUUFBUSxDQUFDM0IsS0FBSyxDQUFDQyxNQUFNLEdBQUkwQixRQUFRLENBQUN6QixZQUFZLENBQUVDLFFBQVEsQ0FBQyxDQUFDLEdBQUcsSUFBSTtFQUNyRTtFQUNBcUIsU0FBUyxDQUFDSSxPQUFPLENBQUMsVUFBQ0QsUUFBUSxFQUFLO0lBQzVCQSxRQUFRLENBQUM3QixnQkFBZ0IsQ0FBQyxPQUFPLEVBQUUsWUFBTTtNQUNyQzRCLHdCQUF3QixDQUFDQyxRQUFRLENBQUM7SUFDdEMsQ0FBQyxDQUFDO0lBQ0ZELHdCQUF3QixDQUFDQyxRQUFRLENBQUM7RUFDdEMsQ0FBQyxDQUFDO0VBQ0YsSUFBSUUsT0FBTyxHQUFHLElBQUk7RUFDbEIsSUFBTUMsYUFBYSxHQUFHcEMsUUFBUSxDQUFDK0IsZ0JBQWdCLENBQUMsa0JBQWtCLENBQUM7RUFDbkVLLGFBQWEsQ0FBQ0YsT0FBTyxDQUFDLFVBQUNkLElBQUksRUFBSztJQUM1QkEsSUFBSSxDQUFDaEIsZ0JBQWdCLENBQUMsT0FBTyxFQUFFLFVBQUN1QixDQUFDLEVBQUs7TUFDbENBLENBQUMsQ0FBQ1UsY0FBYyxDQUFDLENBQUM7TUFDbEIsSUFBTUMsV0FBVyxHQUFHbEIsSUFBSSxDQUFDbUIsWUFBWSxDQUFDLGNBQWMsQ0FBQztNQUNyRCxJQUFJLENBQUNELFdBQVcsRUFBRTtRQUNkO01BQ0o7TUFDQSxJQUFNRSxJQUFJLEdBQUdoQixJQUFJLENBQUNDLEtBQUssQ0FBQ2EsV0FBVyxDQUFDO01BQ3BDLElBQUlILE9BQU8sRUFBRTtRQUNUQSxPQUFPLENBQUNNLEtBQUssQ0FBQyxDQUFDO01BQ25CO01BQ0FOLE9BQU8sR0FBR08sTUFBTSxDQUFDQyxJQUFJLENBQUMsRUFBRSxFQUFFLFNBQVMsRUFBRSx1SEFBdUgsQ0FBQztNQUM3SixJQUFJQyxLQUFLLEdBQUcsc0RBQXNELEdBQzlELG1UQUFtVDtNQUN2VEosSUFBSSxDQUFDTixPQUFPLENBQUMsVUFBVVcsSUFBSSxFQUFFO1FBQ3pCRCxLQUFLLElBQUksZ0NBQWdDLEdBQ3JDLDRCQUE0QixHQUFHQyxJQUFJLENBQUNDLFVBQVUsR0FBRyxPQUFPLEdBQ3hELDRCQUE0QixHQUFHRCxJQUFJLENBQUNFLFFBQVEsR0FBRyxPQUFPLEdBQ3RELHdEQUF3RCxHQUFHRixJQUFJLENBQUNHLE1BQU0sR0FBRyxPQUFPLEdBQ2hGLDJEQUEyRCxHQUFHSCxJQUFJLENBQUNJLE1BQU0sR0FBRyxPQUFPLEdBQ25GLE9BQU87TUFDZixDQUFDLENBQUM7TUFDRkwsS0FBSyxJQUFJLFVBQVU7TUFDbkIsSUFBSVQsT0FBTyxFQUFFO1FBQ1RBLE9BQU8sQ0FBQ25DLFFBQVEsQ0FBQ2tELEtBQUssQ0FBQ04sS0FBSyxDQUFDO1FBQzdCVCxPQUFPLENBQUNnQixLQUFLLENBQUMsQ0FBQztNQUNuQjtJQUNKLENBQUMsQ0FBQztFQUNOLENBQUMsQ0FBQztBQUNOLENBQUMsQ0FBQzs7Ozs7Ozs7Ozs7Ozs7QUNyRmEsU0FBU3JELE9BQU9BLENBQUNzRCxRQUFRLEVBQUU7RUFDdEMsSUFBSXBELFFBQVEsQ0FBQ3FELFVBQVUsS0FBSyxTQUFTLEVBQUU7SUFDbkM7SUFDQXJELFFBQVEsQ0FBQ0ksZ0JBQWdCLENBQUMsa0JBQWtCLEVBQUVnRCxRQUFRLENBQUM7RUFDM0QsQ0FBQyxNQUNJO0lBQ0Q7SUFDQUEsUUFBUSxDQUFDLENBQUM7RUFDZDtBQUNKOzs7Ozs7Ozs7O0FDVGE7QUFDYixlQUFlLHdIQUErQztBQUM5RCwwQkFBMEIsbUJBQU8sQ0FBQyx1R0FBcUM7O0FBRXZFOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxFQUFFOzs7Ozs7Ozs7OztBQ1hXO0FBQ2I7QUFDQSx5Q0FBeUM7O0FBRXpDO0FBQ0E7QUFDQTtBQUNBOzs7Ozs7Ozs7OztBQ1BhO0FBQ2Isa0JBQWtCLG1CQUFPLENBQUMscUdBQW9DO0FBQzlELDZCQUE2QixtQkFBTyxDQUFDLDJHQUF1QztBQUM1RSxlQUFlLG1CQUFPLENBQUMsNkVBQXdCO0FBQy9DLGtCQUFrQixtQkFBTyxDQUFDLGlGQUEwQjs7QUFFcEQ7QUFDQTtBQUNBOztBQUVBLHVCQUF1QiwrQ0FBK0M7QUFDdEU7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBLHlCQUF5QixxQkFBcUI7QUFDOUM7QUFDQTtBQUNBLHlCQUF5QixvQkFBb0I7QUFDN0M7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7Ozs7Ozs7OztBQzlCYTtBQUNiO0FBQ0E7QUFDQTs7Ozs7Ozs7Ozs7QUNIYTtBQUNiLFFBQVEsbUJBQU8sQ0FBQyx1RUFBcUI7QUFDckMsWUFBWSxtQkFBTyxDQUFDLHFFQUFvQjtBQUN4QyxjQUFjLG1CQUFPLENBQUMsMkVBQXVCO0FBQzdDLGVBQWUsbUJBQU8sQ0FBQyw2RUFBd0I7QUFDL0MsZUFBZSxtQkFBTyxDQUFDLDZFQUF3QjtBQUMvQyx3QkFBd0IsbUJBQU8sQ0FBQyxtR0FBbUM7QUFDbkUsK0JBQStCLG1CQUFPLENBQUMsbUhBQTJDO0FBQ2xGLHFCQUFxQixtQkFBTyxDQUFDLHlGQUE4QjtBQUMzRCx5QkFBeUIsbUJBQU8sQ0FBQyxtR0FBbUM7QUFDcEUsbUNBQW1DLG1CQUFPLENBQUMsMkhBQStDO0FBQzFGLHNCQUFzQixtQkFBTyxDQUFDLDZGQUFnQztBQUM5RCxpQkFBaUIsbUJBQU8sQ0FBQyw2RkFBZ0M7O0FBRXpEOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsQ0FBQzs7QUFFRDtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBOztBQUVBO0FBQ0E7QUFDQTtBQUNBLElBQUksd0RBQXdEO0FBQzVEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDRDQUE0QyxZQUFZO0FBQ3hEO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esb0JBQW9CLFNBQVM7QUFDN0IsUUFBUTtBQUNSO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsQ0FBQzs7Ozs7Ozs7Ozs7QUN6RFk7QUFDYixRQUFRLG1CQUFPLENBQUMsdUVBQXFCO0FBQ3JDLGNBQWMsdUhBQThDO0FBQzVELG1DQUFtQyxtQkFBTyxDQUFDLDJIQUErQzs7QUFFMUY7O0FBRUE7QUFDQTtBQUNBO0FBQ0EsSUFBSSw0REFBNEQ7QUFDaEU7QUFDQTtBQUNBO0FBQ0EsQ0FBQzs7Ozs7Ozs7Ozs7QUNkWTtBQUNiLFFBQVEsbUJBQU8sQ0FBQyx1RUFBcUI7QUFDckMsa0JBQWtCLG1CQUFPLENBQUMscUdBQW9DO0FBQzlELG9CQUFvQixtQkFBTyxDQUFDLHVGQUE2QjtBQUN6RCxzQkFBc0IsbUJBQU8sQ0FBQyw2RkFBZ0M7QUFDOUQsMEJBQTBCLG1CQUFPLENBQUMsdUdBQXFDOztBQUV2RTs7QUFFQTtBQUNBOztBQUVBO0FBQ0E7QUFDQSxJQUFJLDhDQUE4QztBQUNsRDtBQUNBO0FBQ0E7QUFDQSxDQUFDOzs7Ozs7Ozs7OztBQ2xCWTtBQUNiLFFBQVEsbUJBQU8sQ0FBQyx1RUFBcUI7QUFDckMsV0FBVyxvSEFBMkM7QUFDdEQsbUNBQW1DLG1CQUFPLENBQUMsMkhBQStDOztBQUUxRjs7QUFFQTtBQUNBO0FBQ0E7QUFDQSxJQUFJLDREQUE0RDtBQUNoRTtBQUNBO0FBQ0E7QUFDQSxDQUFDOzs7Ozs7Ozs7OztBQ2RZO0FBQ2IsYUFBYSxtQkFBTyxDQUFDLHVFQUFxQjtBQUMxQyxtQkFBbUIsbUJBQU8sQ0FBQyxxRkFBNEI7QUFDdkQsNEJBQTRCLG1CQUFPLENBQUMsMkdBQXVDO0FBQzNFLGNBQWMsbUJBQU8sQ0FBQyx1RkFBNkI7QUFDbkQsa0NBQWtDLG1CQUFPLENBQUMsdUhBQTZDOztBQUV2RjtBQUNBO0FBQ0E7QUFDQTtBQUNBLElBQUk7QUFDSjtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTs7Ozs7Ozs7Ozs7O0FDdEJBIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL2Jsb2NrL3BhZ2UvbWFuYWdlci9Mb3VuZ2UuZm9ybS5lbnRyeS50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vdHMvc2VydmljZS9vbi1yZWFkeS50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL2ludGVybmFscy9hcnJheS1mb3ItZWFjaC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL2ludGVybmFscy9kb2VzLW5vdC1leGNlZWQtc2FmZS1pbnRlZ2VyLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vbm9kZV9tb2R1bGVzL2NvcmUtanMvaW50ZXJuYWxzL3N0cmluZy10cmltLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vbm9kZV9tb2R1bGVzL2NvcmUtanMvaW50ZXJuYWxzL3doaXRlc3BhY2VzLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vbm9kZV9tb2R1bGVzL2NvcmUtanMvbW9kdWxlcy9lcy5hcnJheS5jb25jYXQuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9tb2R1bGVzL2VzLmFycmF5LmZpbHRlci5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL21vZHVsZXMvZXMuYXJyYXkuam9pbi5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL21vZHVsZXMvZXMuYXJyYXkubWFwLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vbm9kZV9tb2R1bGVzL2NvcmUtanMvbW9kdWxlcy93ZWIuZG9tLWNvbGxlY3Rpb25zLmZvci1lYWNoLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS9ibG9jay9wYWdlL21hbmFnZXIvTG91bmdlLmZvcm0uY3NzPzNiZDYiXSwic291cmNlc0NvbnRlbnQiOlsiaW1wb3J0ICcuL0xvdW5nZS5mb3JtLmNzcyc7XG5pbXBvcnQgb25SZWFkeSBmcm9tICcuLi8uLi8uLi90cy9zZXJ2aWNlL29uLXJlYWR5Jztcbm9uUmVhZHkoKCkgPT4ge1xuICAgIGNvbnN0IG9wZW5pbmdIb3VycyA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdsb3VuZ2Vfb3BlbmluZ0hvdXJzJyk7XG4gICAgaWYgKG9wZW5pbmdIb3Vycykge1xuICAgICAgICBjb25zdCBodG1sID0gYFxuICAgICAgICAgICAgPGRpdiBjbGFzcz1cIm9wZW5pbmctaG91cnMtYWN0aW9uc1wiPlxuICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPVwic3RhdHVzXCI+PC9zcGFuPlxuICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgIGA7XG4gICAgICAgIG9wZW5pbmdIb3Vycy5pbnNlcnRBZGphY2VudEhUTUwoJ2FmdGVyZW5kJywgaHRtbCk7XG4gICAgICAgIG9wZW5pbmdIb3Vycy5hZGRFdmVudExpc3RlbmVyKCdpbnB1dCcsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGNoZWNrT3BlbmluZ0hvdXJzKHRoaXMpO1xuICAgICAgICB9KTtcbiAgICAgICAgb3BlbmluZ0hvdXJzLnN0eWxlLmhlaWdodCA9ICdhdXRvJztcbiAgICAgICAgY2hlY2tPcGVuaW5nSG91cnMob3BlbmluZ0hvdXJzKTtcbiAgICAgICAgb3BlbmluZ0hvdXJzLnN0eWxlLmhlaWdodCA9IChvcGVuaW5nSG91cnMuc2Nyb2xsSGVpZ2h0KS50b1N0cmluZygpICsgJ3B4JztcbiAgICB9XG4gICAgZnVuY3Rpb24gc2V0U3RhdGUoc3RhdGUsIGlzRXJyb3IgPSBmYWxzZSkge1xuICAgICAgICAkKCcub3BlbmluZy1ob3Vycy1hY3Rpb25zIC5zdGF0dXMnKVxuICAgICAgICAgICAgLnJlbW92ZUNsYXNzKCdzdWNjZXNzIGVycm9yJylcbiAgICAgICAgICAgIC5hZGRDbGFzcyhpc0Vycm9yID8gJ2Vycm9yJyA6ICdzdWNjZXNzJylcbiAgICAgICAgICAgIC50ZXh0KHN0YXRlKTtcbiAgICB9XG4gICAgZnVuY3Rpb24gY2hlY2tPcGVuaW5nSG91cnMoZWxlbSkge1xuICAgICAgICBjb25zdCB1Z2x5ID0gZWxlbS52YWx1ZTtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIGNvbnN0IG9iaiA9IEpTT04ucGFyc2UodWdseSk7XG4gICAgICAgICAgICBlbGVtLnZhbHVlID0gSlNPTi5zdHJpbmdpZnkob2JqLCBudWxsLCA0KTtcbiAgICAgICAgICAgIHNldFN0YXRlKCdWYWxpZCBKU09OJyk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGUpIHtcbiAgICAgICAgICAgIGlmICh1Z2x5LmluZGV4T2YoJ3snKSA9PT0gMCkge1xuICAgICAgICAgICAgICAgIHNldFN0YXRlKCdJbnZhbGlkIEpTT04nLCB0cnVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2UgaWYgKHVnbHkudHJpbSgpID09PSAnJykge1xuICAgICAgICAgICAgICAgIHNldFN0YXRlKCdFbXB0eScpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgc2V0U3RhdGUoJ1JhdyBUZXh0Jyk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29uc3QgdGV4dGFyZWFzID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgndGV4dGFyZWE6bm90KCNsb3VuZ2Vfb3BlbmluZ0hvdXJzKScpO1xuICAgIGZ1bmN0aW9uIGF1dG9BZGp1c3RUZXh0YXJlYUhlaWdodCh0ZXh0YXJlYSkge1xuICAgICAgICB0ZXh0YXJlYS5zdHlsZS5oZWlnaHQgPSAnYXV0byc7IC8vINCh0LHRgNC+0YHQuNGC0Ywg0LLRi9GB0L7RgtGDXG4gICAgICAgIHRleHRhcmVhLnN0eWxlLmhlaWdodCA9ICh0ZXh0YXJlYS5zY3JvbGxIZWlnaHQpLnRvU3RyaW5nKCkgKyAncHgnO1xuICAgIH1cbiAgICB0ZXh0YXJlYXMuZm9yRWFjaCgodGV4dGFyZWEpID0+IHtcbiAgICAgICAgdGV4dGFyZWEuYWRkRXZlbnRMaXN0ZW5lcignaW5wdXQnLCAoKSA9PiB7XG4gICAgICAgICAgICBhdXRvQWRqdXN0VGV4dGFyZWFIZWlnaHQodGV4dGFyZWEpO1xuICAgICAgICB9KTtcbiAgICAgICAgYXV0b0FkanVzdFRleHRhcmVhSGVpZ2h0KHRleHRhcmVhKTtcbiAgICB9KTtcbiAgICBsZXQgcHJldmlldyA9IG51bGw7XG4gICAgY29uc3QgbG91bmdlQ2hhbmdlcyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJ2EubG91bmdlLWNoYW5nZXMnKTtcbiAgICBsb3VuZ2VDaGFuZ2VzLmZvckVhY2goKGVsZW0pID0+IHtcbiAgICAgICAgZWxlbS5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIChlKSA9PiB7XG4gICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICBjb25zdCBkYXRhQ2hhbmdlcyA9IGVsZW0uZ2V0QXR0cmlidXRlKCdkYXRhLWNoYW5nZXMnKTtcbiAgICAgICAgICAgIGlmICghZGF0YUNoYW5nZXMpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBjb25zdCBkYXRhID0gSlNPTi5wYXJzZShkYXRhQ2hhbmdlcyk7XG4gICAgICAgICAgICBpZiAocHJldmlldykge1xuICAgICAgICAgICAgICAgIHByZXZpZXcuY2xvc2UoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHByZXZpZXcgPSB3aW5kb3cub3BlbihcIlwiLCBcIlByZXZpZXdcIiwgXCJ0b29sYmFyPW5vLCBsb2NhdGlvbj1ubywgZGlyZWN0b3JpZXM9bm8sIHN0YXR1cz1ubywgbWVudWJhcj1ubywgc2Nyb2xsYmFycz15ZXMsIHJlc2l6YWJsZT15ZXMsIHdpZHRoPTEwMjQsIGhlaWdodD02MDBcIik7XG4gICAgICAgICAgICBsZXQgdGFibGUgPSAnPHRhYmxlIHN0eWxlPVwid2lkdGg6IDEwMCU7IGJvcmRlcjogMXB4IHNvbGlkIGJsYWNrXCI+JyArXG4gICAgICAgICAgICAgICAgJzx0ciBzdHlsZT1cImJhY2tncm91bmQtY29sb3I6IGJlaWdlOyBmb250LXNpemU6IDEuMmVtOyBmb250LWZhbWlseTogbW9ub3NwYWNlO1wiPjx0ZCBzdHlsZT1cIndpZHRoOiAxNSU7IHBhZGRpbmc6IDVweDtcIj5DaGFuZ2UgRGF0ZTwvdGQ+PHRkIHN0eWxlPVwid2lkdGg6IDE1JTsgcGFkZGluZzogNXB4O1wiPlByb3BlcnR5PC90ZD48dGQgc3R5bGU9XCJtaW4td2lkdGg6IDEwMHB4OyBwYWRkaW5nOiA1cHg7XCI+T2xkIFZhbHVlPC90ZD48dGQgc3R5bGU9XCJtaW4td2lkdGg6IDEwMHB4OyBwYWRkaW5nOiA1cHg7XCI+TmV3IFZhbHVlPC90ZD48L3RyPic7XG4gICAgICAgICAgICBkYXRhLmZvckVhY2goZnVuY3Rpb24gKGl0ZW0pIHtcbiAgICAgICAgICAgICAgICB0YWJsZSArPSAnPHRyIHN0eWxlPVwiZm9udC1zaXplOiBzbWFsbDtcIj4nICtcbiAgICAgICAgICAgICAgICAgICAgJzx0ZCBzdHlsZT1cInBhZGRpbmc6IDVweDtcIj4nICsgaXRlbS5DaGFuZ2VEYXRlICsgJzwvdGQ+JyArXG4gICAgICAgICAgICAgICAgICAgICc8dGQgc3R5bGU9XCJwYWRkaW5nOiA1cHg7XCI+JyArIGl0ZW0uUHJvcGVydHkgKyAnPC90ZD4nICtcbiAgICAgICAgICAgICAgICAgICAgJzx0ZCBzdHlsZT1cImJhY2tncm91bmQtY29sb3I6IGNvcm5zaWxrOyBwYWRkaW5nOiA1cHg7XCI+JyArIGl0ZW0uT2xkVmFsICsgJzwvdGQ+JyArXG4gICAgICAgICAgICAgICAgICAgICc8dGQgc3R5bGU9XCJiYWNrZ3JvdW5kLWNvbG9yOiBncmVlbnllbGxvdzsgcGFkZGluZzogNXB4O1wiPicgKyBpdGVtLk5ld1ZhbCArICc8L3RkPicgK1xuICAgICAgICAgICAgICAgICAgICAnPC90cj4nO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB0YWJsZSArPSAnPC90YWJsZT4nO1xuICAgICAgICAgICAgaWYgKHByZXZpZXcpIHtcbiAgICAgICAgICAgICAgICBwcmV2aWV3LmRvY3VtZW50LndyaXRlKHRhYmxlKTtcbiAgICAgICAgICAgICAgICBwcmV2aWV3LmZvY3VzKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgIH0pO1xufSk7XG4iLCJleHBvcnQgZGVmYXVsdCBmdW5jdGlvbiBvblJlYWR5KGNhbGxiYWNrKSB7XG4gICAgaWYgKGRvY3VtZW50LnJlYWR5U3RhdGUgPT09ICdsb2FkaW5nJykge1xuICAgICAgICAvLyBUaGUgRE9NIGlzIG5vdCB5ZXQgcmVhZHkuXG4gICAgICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ0RPTUNvbnRlbnRMb2FkZWQnLCBjYWxsYmFjayk7XG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICAvLyBUaGUgRE9NIGlzIGFscmVhZHkgcmVhZHkuXG4gICAgICAgIGNhbGxiYWNrKCk7XG4gICAgfVxufVxuIiwiJ3VzZSBzdHJpY3QnO1xudmFyICRmb3JFYWNoID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2FycmF5LWl0ZXJhdGlvbicpLmZvckVhY2g7XG52YXIgYXJyYXlNZXRob2RJc1N0cmljdCA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9hcnJheS1tZXRob2QtaXMtc3RyaWN0Jyk7XG5cbnZhciBTVFJJQ1RfTUVUSE9EID0gYXJyYXlNZXRob2RJc1N0cmljdCgnZm9yRWFjaCcpO1xuXG4vLyBgQXJyYXkucHJvdG90eXBlLmZvckVhY2hgIG1ldGhvZCBpbXBsZW1lbnRhdGlvblxuLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1hcnJheS5wcm90b3R5cGUuZm9yZWFjaFxubW9kdWxlLmV4cG9ydHMgPSAhU1RSSUNUX01FVEhPRCA/IGZ1bmN0aW9uIGZvckVhY2goY2FsbGJhY2tmbiAvKiAsIHRoaXNBcmcgKi8pIHtcbiAgcmV0dXJuICRmb3JFYWNoKHRoaXMsIGNhbGxiYWNrZm4sIGFyZ3VtZW50cy5sZW5ndGggPiAxID8gYXJndW1lbnRzWzFdIDogdW5kZWZpbmVkKTtcbi8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBlcy9uby1hcnJheS1wcm90b3R5cGUtZm9yZWFjaCAtLSBzYWZlXG59IDogW10uZm9yRWFjaDtcbiIsIid1c2Ugc3RyaWN0JztcbnZhciAkVHlwZUVycm9yID0gVHlwZUVycm9yO1xudmFyIE1BWF9TQUZFX0lOVEVHRVIgPSAweDFGRkZGRkZGRkZGRkZGOyAvLyAyICoqIDUzIC0gMSA9PSA5MDA3MTk5MjU0NzQwOTkxXG5cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24gKGl0KSB7XG4gIGlmIChpdCA+IE1BWF9TQUZFX0lOVEVHRVIpIHRocm93ICRUeXBlRXJyb3IoJ01heGltdW0gYWxsb3dlZCBpbmRleCBleGNlZWRlZCcpO1xuICByZXR1cm4gaXQ7XG59O1xuIiwiJ3VzZSBzdHJpY3QnO1xudmFyIHVuY3VycnlUaGlzID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2Z1bmN0aW9uLXVuY3VycnktdGhpcycpO1xudmFyIHJlcXVpcmVPYmplY3RDb2VyY2libGUgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvcmVxdWlyZS1vYmplY3QtY29lcmNpYmxlJyk7XG52YXIgdG9TdHJpbmcgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvdG8tc3RyaW5nJyk7XG52YXIgd2hpdGVzcGFjZXMgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvd2hpdGVzcGFjZXMnKTtcblxudmFyIHJlcGxhY2UgPSB1bmN1cnJ5VGhpcygnJy5yZXBsYWNlKTtcbnZhciBsdHJpbSA9IFJlZ0V4cCgnXlsnICsgd2hpdGVzcGFjZXMgKyAnXSsnKTtcbnZhciBydHJpbSA9IFJlZ0V4cCgnKF58W14nICsgd2hpdGVzcGFjZXMgKyAnXSlbJyArIHdoaXRlc3BhY2VzICsgJ10rJCcpO1xuXG4vLyBgU3RyaW5nLnByb3RvdHlwZS57IHRyaW0sIHRyaW1TdGFydCwgdHJpbUVuZCwgdHJpbUxlZnQsIHRyaW1SaWdodCB9YCBtZXRob2RzIGltcGxlbWVudGF0aW9uXG52YXIgY3JlYXRlTWV0aG9kID0gZnVuY3Rpb24gKFRZUEUpIHtcbiAgcmV0dXJuIGZ1bmN0aW9uICgkdGhpcykge1xuICAgIHZhciBzdHJpbmcgPSB0b1N0cmluZyhyZXF1aXJlT2JqZWN0Q29lcmNpYmxlKCR0aGlzKSk7XG4gICAgaWYgKFRZUEUgJiAxKSBzdHJpbmcgPSByZXBsYWNlKHN0cmluZywgbHRyaW0sICcnKTtcbiAgICBpZiAoVFlQRSAmIDIpIHN0cmluZyA9IHJlcGxhY2Uoc3RyaW5nLCBydHJpbSwgJyQxJyk7XG4gICAgcmV0dXJuIHN0cmluZztcbiAgfTtcbn07XG5cbm1vZHVsZS5leHBvcnRzID0ge1xuICAvLyBgU3RyaW5nLnByb3RvdHlwZS57IHRyaW1MZWZ0LCB0cmltU3RhcnQgfWAgbWV0aG9kc1xuICAvLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLXN0cmluZy5wcm90b3R5cGUudHJpbXN0YXJ0XG4gIHN0YXJ0OiBjcmVhdGVNZXRob2QoMSksXG4gIC8vIGBTdHJpbmcucHJvdG90eXBlLnsgdHJpbVJpZ2h0LCB0cmltRW5kIH1gIG1ldGhvZHNcbiAgLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1zdHJpbmcucHJvdG90eXBlLnRyaW1lbmRcbiAgZW5kOiBjcmVhdGVNZXRob2QoMiksXG4gIC8vIGBTdHJpbmcucHJvdG90eXBlLnRyaW1gIG1ldGhvZFxuICAvLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLXN0cmluZy5wcm90b3R5cGUudHJpbVxuICB0cmltOiBjcmVhdGVNZXRob2QoMylcbn07XG4iLCIndXNlIHN0cmljdCc7XG4vLyBhIHN0cmluZyBvZiBhbGwgdmFsaWQgdW5pY29kZSB3aGl0ZXNwYWNlc1xubW9kdWxlLmV4cG9ydHMgPSAnXFx1MDAwOVxcdTAwMEFcXHUwMDBCXFx1MDAwQ1xcdTAwMERcXHUwMDIwXFx1MDBBMFxcdTE2ODBcXHUyMDAwXFx1MjAwMVxcdTIwMDInICtcbiAgJ1xcdTIwMDNcXHUyMDA0XFx1MjAwNVxcdTIwMDZcXHUyMDA3XFx1MjAwOFxcdTIwMDlcXHUyMDBBXFx1MjAyRlxcdTIwNUZcXHUzMDAwXFx1MjAyOFxcdTIwMjlcXHVGRUZGJztcbiIsIid1c2Ugc3RyaWN0JztcbnZhciAkID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2V4cG9ydCcpO1xudmFyIGZhaWxzID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2ZhaWxzJyk7XG52YXIgaXNBcnJheSA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9pcy1hcnJheScpO1xudmFyIGlzT2JqZWN0ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2lzLW9iamVjdCcpO1xudmFyIHRvT2JqZWN0ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3RvLW9iamVjdCcpO1xudmFyIGxlbmd0aE9mQXJyYXlMaWtlID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2xlbmd0aC1vZi1hcnJheS1saWtlJyk7XG52YXIgZG9lc05vdEV4Y2VlZFNhZmVJbnRlZ2VyID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2RvZXMtbm90LWV4Y2VlZC1zYWZlLWludGVnZXInKTtcbnZhciBjcmVhdGVQcm9wZXJ0eSA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9jcmVhdGUtcHJvcGVydHknKTtcbnZhciBhcnJheVNwZWNpZXNDcmVhdGUgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvYXJyYXktc3BlY2llcy1jcmVhdGUnKTtcbnZhciBhcnJheU1ldGhvZEhhc1NwZWNpZXNTdXBwb3J0ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2FycmF5LW1ldGhvZC1oYXMtc3BlY2llcy1zdXBwb3J0Jyk7XG52YXIgd2VsbEtub3duU3ltYm9sID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3dlbGwta25vd24tc3ltYm9sJyk7XG52YXIgVjhfVkVSU0lPTiA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9lbmdpbmUtdjgtdmVyc2lvbicpO1xuXG52YXIgSVNfQ09OQ0FUX1NQUkVBREFCTEUgPSB3ZWxsS25vd25TeW1ib2woJ2lzQ29uY2F0U3ByZWFkYWJsZScpO1xuXG4vLyBXZSBjYW4ndCB1c2UgdGhpcyBmZWF0dXJlIGRldGVjdGlvbiBpbiBWOCBzaW5jZSBpdCBjYXVzZXNcbi8vIGRlb3B0aW1pemF0aW9uIGFuZCBzZXJpb3VzIHBlcmZvcm1hbmNlIGRlZ3JhZGF0aW9uXG4vLyBodHRwczovL2dpdGh1Yi5jb20vemxvaXJvY2svY29yZS1qcy9pc3N1ZXMvNjc5XG52YXIgSVNfQ09OQ0FUX1NQUkVBREFCTEVfU1VQUE9SVCA9IFY4X1ZFUlNJT04gPj0gNTEgfHwgIWZhaWxzKGZ1bmN0aW9uICgpIHtcbiAgdmFyIGFycmF5ID0gW107XG4gIGFycmF5W0lTX0NPTkNBVF9TUFJFQURBQkxFXSA9IGZhbHNlO1xuICByZXR1cm4gYXJyYXkuY29uY2F0KClbMF0gIT09IGFycmF5O1xufSk7XG5cbnZhciBpc0NvbmNhdFNwcmVhZGFibGUgPSBmdW5jdGlvbiAoTykge1xuICBpZiAoIWlzT2JqZWN0KE8pKSByZXR1cm4gZmFsc2U7XG4gIHZhciBzcHJlYWRhYmxlID0gT1tJU19DT05DQVRfU1BSRUFEQUJMRV07XG4gIHJldHVybiBzcHJlYWRhYmxlICE9PSB1bmRlZmluZWQgPyAhIXNwcmVhZGFibGUgOiBpc0FycmF5KE8pO1xufTtcblxudmFyIEZPUkNFRCA9ICFJU19DT05DQVRfU1BSRUFEQUJMRV9TVVBQT1JUIHx8ICFhcnJheU1ldGhvZEhhc1NwZWNpZXNTdXBwb3J0KCdjb25jYXQnKTtcblxuLy8gYEFycmF5LnByb3RvdHlwZS5jb25jYXRgIG1ldGhvZFxuLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1hcnJheS5wcm90b3R5cGUuY29uY2F0XG4vLyB3aXRoIGFkZGluZyBzdXBwb3J0IG9mIEBAaXNDb25jYXRTcHJlYWRhYmxlIGFuZCBAQHNwZWNpZXNcbiQoeyB0YXJnZXQ6ICdBcnJheScsIHByb3RvOiB0cnVlLCBhcml0eTogMSwgZm9yY2VkOiBGT1JDRUQgfSwge1xuICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgbm8tdW51c2VkLXZhcnMgLS0gcmVxdWlyZWQgZm9yIGAubGVuZ3RoYFxuICBjb25jYXQ6IGZ1bmN0aW9uIGNvbmNhdChhcmcpIHtcbiAgICB2YXIgTyA9IHRvT2JqZWN0KHRoaXMpO1xuICAgIHZhciBBID0gYXJyYXlTcGVjaWVzQ3JlYXRlKE8sIDApO1xuICAgIHZhciBuID0gMDtcbiAgICB2YXIgaSwgaywgbGVuZ3RoLCBsZW4sIEU7XG4gICAgZm9yIChpID0gLTEsIGxlbmd0aCA9IGFyZ3VtZW50cy5sZW5ndGg7IGkgPCBsZW5ndGg7IGkrKykge1xuICAgICAgRSA9IGkgPT09IC0xID8gTyA6IGFyZ3VtZW50c1tpXTtcbiAgICAgIGlmIChpc0NvbmNhdFNwcmVhZGFibGUoRSkpIHtcbiAgICAgICAgbGVuID0gbGVuZ3RoT2ZBcnJheUxpa2UoRSk7XG4gICAgICAgIGRvZXNOb3RFeGNlZWRTYWZlSW50ZWdlcihuICsgbGVuKTtcbiAgICAgICAgZm9yIChrID0gMDsgayA8IGxlbjsgaysrLCBuKyspIGlmIChrIGluIEUpIGNyZWF0ZVByb3BlcnR5KEEsIG4sIEVba10pO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgZG9lc05vdEV4Y2VlZFNhZmVJbnRlZ2VyKG4gKyAxKTtcbiAgICAgICAgY3JlYXRlUHJvcGVydHkoQSwgbisrLCBFKTtcbiAgICAgIH1cbiAgICB9XG4gICAgQS5sZW5ndGggPSBuO1xuICAgIHJldHVybiBBO1xuICB9XG59KTtcbiIsIid1c2Ugc3RyaWN0JztcbnZhciAkID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2V4cG9ydCcpO1xudmFyICRmaWx0ZXIgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvYXJyYXktaXRlcmF0aW9uJykuZmlsdGVyO1xudmFyIGFycmF5TWV0aG9kSGFzU3BlY2llc1N1cHBvcnQgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvYXJyYXktbWV0aG9kLWhhcy1zcGVjaWVzLXN1cHBvcnQnKTtcblxudmFyIEhBU19TUEVDSUVTX1NVUFBPUlQgPSBhcnJheU1ldGhvZEhhc1NwZWNpZXNTdXBwb3J0KCdmaWx0ZXInKTtcblxuLy8gYEFycmF5LnByb3RvdHlwZS5maWx0ZXJgIG1ldGhvZFxuLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1hcnJheS5wcm90b3R5cGUuZmlsdGVyXG4vLyB3aXRoIGFkZGluZyBzdXBwb3J0IG9mIEBAc3BlY2llc1xuJCh7IHRhcmdldDogJ0FycmF5JywgcHJvdG86IHRydWUsIGZvcmNlZDogIUhBU19TUEVDSUVTX1NVUFBPUlQgfSwge1xuICBmaWx0ZXI6IGZ1bmN0aW9uIGZpbHRlcihjYWxsYmFja2ZuIC8qICwgdGhpc0FyZyAqLykge1xuICAgIHJldHVybiAkZmlsdGVyKHRoaXMsIGNhbGxiYWNrZm4sIGFyZ3VtZW50cy5sZW5ndGggPiAxID8gYXJndW1lbnRzWzFdIDogdW5kZWZpbmVkKTtcbiAgfVxufSk7XG4iLCIndXNlIHN0cmljdCc7XG52YXIgJCA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9leHBvcnQnKTtcbnZhciB1bmN1cnJ5VGhpcyA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9mdW5jdGlvbi11bmN1cnJ5LXRoaXMnKTtcbnZhciBJbmRleGVkT2JqZWN0ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2luZGV4ZWQtb2JqZWN0Jyk7XG52YXIgdG9JbmRleGVkT2JqZWN0ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3RvLWluZGV4ZWQtb2JqZWN0Jyk7XG52YXIgYXJyYXlNZXRob2RJc1N0cmljdCA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9hcnJheS1tZXRob2QtaXMtc3RyaWN0Jyk7XG5cbnZhciBuYXRpdmVKb2luID0gdW5jdXJyeVRoaXMoW10uam9pbik7XG5cbnZhciBFUzNfU1RSSU5HUyA9IEluZGV4ZWRPYmplY3QgIT09IE9iamVjdDtcbnZhciBGT1JDRUQgPSBFUzNfU1RSSU5HUyB8fCAhYXJyYXlNZXRob2RJc1N0cmljdCgnam9pbicsICcsJyk7XG5cbi8vIGBBcnJheS5wcm90b3R5cGUuam9pbmAgbWV0aG9kXG4vLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLWFycmF5LnByb3RvdHlwZS5qb2luXG4kKHsgdGFyZ2V0OiAnQXJyYXknLCBwcm90bzogdHJ1ZSwgZm9yY2VkOiBGT1JDRUQgfSwge1xuICBqb2luOiBmdW5jdGlvbiBqb2luKHNlcGFyYXRvcikge1xuICAgIHJldHVybiBuYXRpdmVKb2luKHRvSW5kZXhlZE9iamVjdCh0aGlzKSwgc2VwYXJhdG9yID09PSB1bmRlZmluZWQgPyAnLCcgOiBzZXBhcmF0b3IpO1xuICB9XG59KTtcbiIsIid1c2Ugc3RyaWN0JztcbnZhciAkID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2V4cG9ydCcpO1xudmFyICRtYXAgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvYXJyYXktaXRlcmF0aW9uJykubWFwO1xudmFyIGFycmF5TWV0aG9kSGFzU3BlY2llc1N1cHBvcnQgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvYXJyYXktbWV0aG9kLWhhcy1zcGVjaWVzLXN1cHBvcnQnKTtcblxudmFyIEhBU19TUEVDSUVTX1NVUFBPUlQgPSBhcnJheU1ldGhvZEhhc1NwZWNpZXNTdXBwb3J0KCdtYXAnKTtcblxuLy8gYEFycmF5LnByb3RvdHlwZS5tYXBgIG1ldGhvZFxuLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1hcnJheS5wcm90b3R5cGUubWFwXG4vLyB3aXRoIGFkZGluZyBzdXBwb3J0IG9mIEBAc3BlY2llc1xuJCh7IHRhcmdldDogJ0FycmF5JywgcHJvdG86IHRydWUsIGZvcmNlZDogIUhBU19TUEVDSUVTX1NVUFBPUlQgfSwge1xuICBtYXA6IGZ1bmN0aW9uIG1hcChjYWxsYmFja2ZuIC8qICwgdGhpc0FyZyAqLykge1xuICAgIHJldHVybiAkbWFwKHRoaXMsIGNhbGxiYWNrZm4sIGFyZ3VtZW50cy5sZW5ndGggPiAxID8gYXJndW1lbnRzWzFdIDogdW5kZWZpbmVkKTtcbiAgfVxufSk7XG4iLCIndXNlIHN0cmljdCc7XG52YXIgZ2xvYmFsID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2dsb2JhbCcpO1xudmFyIERPTUl0ZXJhYmxlcyA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9kb20taXRlcmFibGVzJyk7XG52YXIgRE9NVG9rZW5MaXN0UHJvdG90eXBlID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2RvbS10b2tlbi1saXN0LXByb3RvdHlwZScpO1xudmFyIGZvckVhY2ggPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvYXJyYXktZm9yLWVhY2gnKTtcbnZhciBjcmVhdGVOb25FbnVtZXJhYmxlUHJvcGVydHkgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvY3JlYXRlLW5vbi1lbnVtZXJhYmxlLXByb3BlcnR5Jyk7XG5cbnZhciBoYW5kbGVQcm90b3R5cGUgPSBmdW5jdGlvbiAoQ29sbGVjdGlvblByb3RvdHlwZSkge1xuICAvLyBzb21lIENocm9tZSB2ZXJzaW9ucyBoYXZlIG5vbi1jb25maWd1cmFibGUgbWV0aG9kcyBvbiBET01Ub2tlbkxpc3RcbiAgaWYgKENvbGxlY3Rpb25Qcm90b3R5cGUgJiYgQ29sbGVjdGlvblByb3RvdHlwZS5mb3JFYWNoICE9PSBmb3JFYWNoKSB0cnkge1xuICAgIGNyZWF0ZU5vbkVudW1lcmFibGVQcm9wZXJ0eShDb2xsZWN0aW9uUHJvdG90eXBlLCAnZm9yRWFjaCcsIGZvckVhY2gpO1xuICB9IGNhdGNoIChlcnJvcikge1xuICAgIENvbGxlY3Rpb25Qcm90b3R5cGUuZm9yRWFjaCA9IGZvckVhY2g7XG4gIH1cbn07XG5cbmZvciAodmFyIENPTExFQ1RJT05fTkFNRSBpbiBET01JdGVyYWJsZXMpIHtcbiAgaWYgKERPTUl0ZXJhYmxlc1tDT0xMRUNUSU9OX05BTUVdKSB7XG4gICAgaGFuZGxlUHJvdG90eXBlKGdsb2JhbFtDT0xMRUNUSU9OX05BTUVdICYmIGdsb2JhbFtDT0xMRUNUSU9OX05BTUVdLnByb3RvdHlwZSk7XG4gIH1cbn1cblxuaGFuZGxlUHJvdG90eXBlKERPTVRva2VuTGlzdFByb3RvdHlwZSk7XG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiXSwibmFtZXMiOlsib25SZWFkeSIsIm9wZW5pbmdIb3VycyIsImRvY3VtZW50IiwiZ2V0RWxlbWVudEJ5SWQiLCJodG1sIiwiaW5zZXJ0QWRqYWNlbnRIVE1MIiwiYWRkRXZlbnRMaXN0ZW5lciIsImNoZWNrT3BlbmluZ0hvdXJzIiwic3R5bGUiLCJoZWlnaHQiLCJzY3JvbGxIZWlnaHQiLCJ0b1N0cmluZyIsInNldFN0YXRlIiwic3RhdGUiLCJpc0Vycm9yIiwiYXJndW1lbnRzIiwibGVuZ3RoIiwidW5kZWZpbmVkIiwiJCIsInJlbW92ZUNsYXNzIiwiYWRkQ2xhc3MiLCJ0ZXh0IiwiZWxlbSIsInVnbHkiLCJ2YWx1ZSIsIm9iaiIsIkpTT04iLCJwYXJzZSIsInN0cmluZ2lmeSIsImUiLCJpbmRleE9mIiwidHJpbSIsInRleHRhcmVhcyIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJhdXRvQWRqdXN0VGV4dGFyZWFIZWlnaHQiLCJ0ZXh0YXJlYSIsImZvckVhY2giLCJwcmV2aWV3IiwibG91bmdlQ2hhbmdlcyIsInByZXZlbnREZWZhdWx0IiwiZGF0YUNoYW5nZXMiLCJnZXRBdHRyaWJ1dGUiLCJkYXRhIiwiY2xvc2UiLCJ3aW5kb3ciLCJvcGVuIiwidGFibGUiLCJpdGVtIiwiQ2hhbmdlRGF0ZSIsIlByb3BlcnR5IiwiT2xkVmFsIiwiTmV3VmFsIiwid3JpdGUiLCJmb2N1cyIsImNhbGxiYWNrIiwicmVhZHlTdGF0ZSJdLCJzb3VyY2VSb290IjoiIn0=