(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["timeline"],{

/***/ "./assets/bem/ts/shim/ngReact.js":
/*!***************************************!*\
  !*** ./assets/bem/ts/shim/ngReact.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react-dom */ "./node_modules/react-dom/index.js");
/* harmony import */ var _angular_boot__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./angular-boot */ "./assets/bem/ts/shim/angular-boot.js");










// wraps a function with scope.$apply, if already applied just return
function applied(fn, scope) {
  if (fn.wrappedInApply) {
    return fn;
  }
  var wrapped = function wrapped() {
    var args = arguments;
    var phase = scope.$root.$$phase;
    if (phase === "$apply" || phase === "$digest") {
      return fn.apply(null, args);
    } else {
      return scope.$apply(function () {
        return fn.apply(null, args);
      });
    }
  };
  wrapped.wrappedInApply = true;
  return wrapped;
}

/**
 * wraps functions on obj in scope.$apply
 *
 * keeps backwards compatibility, as if propsConfig is not passed, it will
 * work as before, wrapping all functions and won't wrap only when specified.
 *
 * @version 0.4.1
 * @param obj react component props
 * @param scope current scope
 * @param propsConfig configuration object for all properties
 * @returns {Object} props with the functions wrapped in scope.$apply
 */
function applyFunctions(obj, scope, propsConfig) {
  return Object.keys(obj || {}).reduce(function (prev, key) {
    var value = obj[key];
    var config = (propsConfig || {})[key] || {};
    /**
     * wrap functions in a function that ensures they are scope.$applied
     * ensures that when function is called from a React component
     * the Angular digest cycle is run
     */
    prev[key] = _angular_boot__WEBPACK_IMPORTED_MODULE_8__["default"].isFunction(value) && config.wrapApply !== false ? applied(value, scope) : value;
    return prev;
  }, {});
}

/**
 *
 * @param watchDepth (value of HTML watch-depth attribute)
 * @param scope (angular scope)
 *
 * Uses the watchDepth attribute to determine how to watch props on scope.
 * If watchDepth attribute is NOT reference or collection, watchDepth defaults to deep watching by value
 */
function watchProps(watchDepth, scope, watchExpressions, listener) {
  var supportsWatchCollection = _angular_boot__WEBPACK_IMPORTED_MODULE_8__["default"].isFunction(scope.$watchCollection);
  var supportsWatchGroup = _angular_boot__WEBPACK_IMPORTED_MODULE_8__["default"].isFunction(scope.$watchGroup);
  var watchGroupExpressions = [];
  watchExpressions.forEach(function (expr) {
    var actualExpr = getPropExpression(expr);
    var exprWatchDepth = getPropWatchDepth(watchDepth, expr);
    if (exprWatchDepth === 'collection' && supportsWatchCollection) {
      scope.$watchCollection(actualExpr, listener);
    } else if (exprWatchDepth === 'reference' && supportsWatchGroup) {
      watchGroupExpressions.push(actualExpr);
    } else {
      scope.$watch(actualExpr, listener, exprWatchDepth !== 'reference');
    }
  });
  if (watchGroupExpressions.length) {
    scope.$watchGroup(watchGroupExpressions, listener);
  }
}

// render React component, with scope[attrs.props] being passed in as the component props
function renderComponent(component, props, scope, elem) {
  scope.$evalAsync(function () {
    (0,react_dom__WEBPACK_IMPORTED_MODULE_7__.render)( /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_6__.createElement)(component, props), elem[0]);
  });
}

// get prop expression from prop (string or array)
function getPropExpression(prop) {
  return Array.isArray(prop) ? prop[0] : prop;
}

// get watch depth of prop (string or array)
function getPropWatchDepth(defaultWatch, prop) {
  var customWatchDepth = Array.isArray(prop) && _angular_boot__WEBPACK_IMPORTED_MODULE_8__["default"].isObject(prop[1]) && prop[1].watchDepth;
  return customWatchDepth || defaultWatch;
}

// get prop name from prop (string or array)
function getPropName(prop) {
  return Array.isArray(prop) ? prop[0] : prop;
}

// find the normalized attribute knowing that React props accept any type of capitalization
function findAttribute(attrs, propName) {
  var index = Object.keys(attrs).filter(function (attr) {
    return attr.toLowerCase() === propName.toLowerCase();
  })[0];
  return attrs[index];
}

// get prop name from prop (string or array)
function getPropConfig(prop) {
  return Array.isArray(prop) ? prop[1] : {};
}
var reactDirective = function reactDirective($injector) {
  return function (reactComponent, staticProps, conf, injectableProps) {
    var directive = {
      restrict: 'EA',
      replace: true,
      link: function link(scope, elem, attrs) {
        // if props is not defined, fall back to use the React component's propTypes if present
        var props = staticProps || Object.keys(reactComponent.propTypes || {});
        if (!props.length) {
          var ngAttrNames = [];
          var directiveName = reactComponent.name.toLowerCase();
          _angular_boot__WEBPACK_IMPORTED_MODULE_8__["default"].forEach(attrs.$attr, function (value, key) {
            if (key.toLowerCase() !== directiveName) {
              ngAttrNames.push(key);
            }
          });
          props = ngAttrNames;
        }

        // for each of the properties, get their scope value and set it to scope.props
        var renderMyComponent = function renderMyComponent() {
          var scopeProps = {},
            config = {};
          props.forEach(function (prop) {
            var propName = getPropName(prop);
            scopeProps[propName] = scope.$eval(findAttribute(attrs, propName));
            config[propName] = getPropConfig(prop);
          });
          scopeProps = applyFunctions(scopeProps, scope, config);
          scopeProps = _angular_boot__WEBPACK_IMPORTED_MODULE_8__["default"].extend({}, scopeProps, injectableProps);
          renderComponent(reactComponent, scopeProps, scope, elem);
        };

        // watch each property name and trigger an update whenever something changes,
        // to update scope.props with new values
        var propExpressions = props.map(function (prop) {
          return Array.isArray(prop) ? [attrs[getPropName(prop)], getPropConfig(prop)] : attrs[prop];
        });
        watchProps(attrs.watchDepth, scope, propExpressions, renderMyComponent);
        renderMyComponent();

        // cleanup when scope is destroyed
        scope.$on('$destroy', function () {
          if (!attrs.onScopeDestroy) {
            (0,react_dom__WEBPACK_IMPORTED_MODULE_7__.unmountComponentAtNode)(elem[0]);
          } else {
            scope.$eval(attrs.onScopeDestroy, {
              unmountComponent: react_dom__WEBPACK_IMPORTED_MODULE_7__.unmountComponentAtNode.bind(this, elem[0])
            });
          }
        });
      }
    };
    return _angular_boot__WEBPACK_IMPORTED_MODULE_8__["default"].extend(directive, conf);
  };
};
_angular_boot__WEBPACK_IMPORTED_MODULE_8__["default"].module('react', []).factory('reactDirective', ['$injector', reactDirective]);

/***/ }),

/***/ "./assets/entry-point-deprecated/main.js":
/*!***********************************************!*\
  !*** ./assets/entry-point-deprecated/main.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _less_deprecated_main_less__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../less-deprecated/main.less */ "./assets/less-deprecated/main.less");
/* harmony import */ var jqueryui__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js");
/* harmony import */ var jqueryui__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(jqueryui__WEBPACK_IMPORTED_MODULE_7__);
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");







/*eslint no-unused-vars: "jqueryui"*/
 // .menu()

(function main() {
  toggleSidebarVisible();
  initDropdowns($('body'));
})();
function toggleSidebarVisible() {
  $(window).resize(function () {
    var sizeWindow = $('body').width();
    if (sizeWindow < 1024) {
      $('.main-body').addClass('small-desktop');
    } else {
      $('.main-body').removeClass('small-desktop');
    }
    if ($('.main-body').hasClass('manual-hidden')) return;
    if (sizeWindow < 1024) {
      $('.main-body').addClass('hide-menu');
    } else {
      $('.main-body').removeClass('hide-menu');
    }
  });
  var menuClose = document.querySelector('.menu-close');
  if (menuClose) {
    var menuBody = document.querySelector('.main-body');
    menuClose.onclick = function () {
      menuBody.classList.toggle('hide-menu');
      menuBody.classList.add('manual-hidden');
    };
  }
}
function initDropdowns(area, options) {
  options = options || {};
  var selector = '[data-role="dropdown"]';
  var dropdown = undefined != area ? $(area).find(selector).addBack(selector) : $(selector);
  var ofParentSelector = options.ofParent || 'li';
  dropdown.each(function (id, el) {
    $(el).removeAttr('data-role').menu().hide().on('menu.hide', function (e) {
      $(e.target).hide(200);
    });
    $('[data-target=' + $(el).data('id') + ']').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $('.ui-menu:visible').not('[data-id="' + $(this).data('target') + '"]').trigger('menu.hide');
      $(el).toggle(0, function () {
        var _options;
        $(el).position({
          my: ((_options = options) === null || _options === void 0 || (_options = _options.position) === null || _options === void 0 ? void 0 : _options.my) || 'left top',
          at: "left bottom",
          of: $(e.target).parents(ofParentSelector).find('.rel-this'),
          collision: "fit"
        });
      });
    });
  });
  $(document).on('click', function (e) {
    $('.ui-menu:visible').trigger('menu.hide');
  });
}
;
function autoCompleteRenderItem() {
  var renderFunction = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
  if (null === renderFunction) {
    renderFunction = function renderFunction(ul, item) {
      var regex = new RegExp('(' + this.element.val().replace(/[^A-Za-z0-9А-Яа-я]+/g, '') + ')', 'gi'),
        html = $('<div/>').text(item.label).html().replace(regex, '<b>$1</b>');
      return $('<li></li>').data('item.autocomplete', item).append($('<a></a>').html(html)).appendTo(ul);
    };
  }
  $.ui.autocomplete.prototype._renderItem = renderFunction;
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  initDropdowns: initDropdowns,
  autoCompleteRenderItem: autoCompleteRenderItem
});

/***/ }),

/***/ "./assets/entry-point-deprecated/timeline/index.js":
/*!*********************************************************!*\
  !*** ./assets/entry-point-deprecated/timeline/index.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _main__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../main */ "./assets/entry-point-deprecated/main.js");
/* harmony import */ var pages_timeline_timeline__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! pages/timeline/timeline */ "./web/assets/awardwalletnewdesign/js/pages/timeline/timeline.js");
/* harmony import */ var pages_timeline_timeline__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(pages_timeline_timeline__WEBPACK_IMPORTED_MODULE_1__);



/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/directives/extendedDialog.js":
/*!*************************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/directives/extendedDialog.js ***!
  \*************************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular */ "./assets/bem/ts/shim/angular.js"), __webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (angular, $) {
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module('extendedDialog', []).directive('dialogHeader', function () {
    return {
      restrict: 'AE',
      template: '<span class="ui-dialog-title" ng-transclude></span>',
      transclude: true,
      replace: true,
      link: function link(scope, element) {
        scope.$on('renderParts', function (event, el) {
          el.find('.ui-dialog-titlebar .ui-dialog-title').replaceWith(element);
        });
      }
    };
  }).directive('dialogFooter', function () {
    return {
      restrict: 'AE',
      template: '<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix"><div class="ui-dialog-buttonset" ng-transclude></div></div>',
      transclude: true,
      link: function link(scope, element) {
        scope.$on('renderParts', function (event, el) {
          element.appendTo(el);
        });
        scope.dialogClose = function () {
          scope.$emit('dialogClose');
        };
      }
    };
  }).directive('extDialog', ['$timeout', '$window', function ($timeout, $window) {
    return {
      restrict: 'AE',
      template: '<div ng-transclude style="display: none"></div>',
      transclude: true,
      replace: true,
      scope: {
        onclose: '&'
      },
      link: function link(scope, element, attr) {
        $timeout(function () {
          var options = $.extend(attr, {
            hide: 'fade',
            show: 'fade',
            appendTo: element.parent(),
            autoOpen: true,
            close: function close() {
              $($window).off('resize.dialog');
              $timeout(function () {
                scope.$apply(function () {
                  scope.onclose();
                });
              }, 100);
            },
            create: function create() {
              scope.$broadcast('renderParts', element.parent());
            },
            open: function open(event) {
              $($window).off('resize.dialog').on('resize.dialog', function () {
                $(event.target).dialog("option", "position", {
                  my: "center",
                  at: "center",
                  of: $window
                });
              });
              $('body').one('click', '.ui-widget-overlay', function () {
                $('.ui-dialog').filter(function () {
                  return $(this).css("display") === "block";
                }).find('.ui-dialog-content').dialog('close');
              });
            }
          });
          $(element).dialog(options);
          scope.$on('dialogClose', function () {
            $(element).dialog('close');
          });
        }, 1);
      }
    };
  }]);
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/mailbox/add.js":
/*!*****************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/mailbox/add.js ***!
  \*****************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.array.join.js */ "./node_modules/core-js/modules/es.array.join.js");
__webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! pages/mailbox/request */ "./web/assets/awardwalletnewdesign/js/pages/mailbox/request.js"), __webpack_require__(/*! lib/ga-wrapper */ "./web/assets/awardwalletnewdesign/js/lib/ga-wrapper.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts"), __webpack_require__(/*! routing */ "./assets/bem/ts/service/router.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function ($, dialog, requester, gaWrapper) {
  var AddMailbox = function () {
    function AddMailbox() {
      this.selectOwnerDialog = null;
      this.selectOwnerDeferred = null;
      this.owner = null;
      this.emailOwners = {};
      this.onSubmitAddForm = this.onSubmitAddForm.bind(this);
      this.selectFamilyMember = this.selectFamilyMember.bind(this);
    }
    var _proto = AddMailbox.prototype;
    _proto.setFamilyMembers = function setFamilyMembers(userFullName, familyMembers) {
      this.familyMembers = familyMembers;
      this.familyMembers.unshift({
        useragentid: '',
        fullName: userFullName
      });
    };
    _proto.setOwner = function setOwner(owner) {
      this.owner = owner;
    };
    _proto.setRedirectUrl = function setRedirectUrl(url) {
      this.redirectUrl = url;
    };
    _proto.subscribe = function subscribe() {
      var _this = this;
      $(document).on('submit', 'form[name="user_mailbox"]', this.onSubmitAddForm);
      $(document).on('click', '.add-mailbox-link', function (event) {
        event.preventDefault();
        var url = $(this).attr('href');
        _this.selectFamilyMember().then(function (agentId) {
          document.location.href = url + '?agentId=' + agentId;
        });
      });
    };
    _proto.onSubmitAddForm = function onSubmitAddForm() {
      var _this = this;
      this.selectFamilyMember($('#user_mailbox_email').val()).then(function (agentId) {
        var form = $('form[name="user_mailbox"]');
        requester.request(Routing.generate('aw_usermailbox_add', {
          agentId: agentId
        }), 'post', {
          'email': $('#user_mailbox_email').val(),
          'password': $('#user_mailbox_password').is(':visible') ? $('#user_mailbox_password').val() : ''
        }, {
          timeout: 1000 * 60 * 2,
          button: form.find('div.submit'),
          before: function before() {
            $('div.error-mailbox-login').remove();
          },
          success: function success(data) {
            var unlock = true;
            switch (data.status) {
              case "redirect":
                document.location.href = data.url;
                unlock = false;
                break;
              case "error":
                var message = $('div.row-email div[class="error-message"][data-type=serverError]');
                message.find('div.error-message-description').text(data.error);
                message.css('display', 'table-row');
                $('div.row-email').addClass('error');
                break;
              case "ask_password":
                $('div.row-password').show(400, function () {
                  $('#user_mailbox_password').focus();
                });
                break;
              case "added":
                unlock = false;
                console.log('sending mailbox added event: imap');
                gaWrapper('event', 'added', {
                  'event_category': 'mailbox',
                  'event_label': 'imap',
                  'event_callback': function event_callback() {
                    if (_this.redirectUrl) {
                      document.location.href = _this.redirectUrl;
                    } else {
                      document.location.reload();
                    }
                  }
                });
                break;
            }
            return unlock;
          }
        });
      });
      return false;
    };
    _proto.selectFamilyMember = function selectFamilyMember(email) {
      this.selectOwnerDeferred = $.Deferred();
      if (typeof this.owner === 'string') {
        this.selectOwnerDeferred.resolve(this.owner);
        return this.selectOwnerDeferred.promise();
      }
      if (this.familyMembers.length === 0) {
        this.selectOwnerDeferred.resolve('');
        return this.selectOwnerDeferred.promise();
      }
      if (email && email in this.emailOwners) {
        this.selectOwnerDeferred.resolve(this.emailOwners[email]);
        return this.selectOwnerDeferred.promise();
      }
      if (this.selectOwnerDialog === null) {
        var _this = this;
        this.selectOwnerDialog = dialog.fastCreate(Translator.trans('mailbox_owner'), "<div>\n" + "            <label for=\"set-owner\">" + Translator.trans('mailbox_owner') + ":</label>\n" + "            <div class=\"input\">\n" + "                <div class=\"input-item\">\n" + "                    <div class=\"styled-select\">\n" + "                        <div>\n" + "                        <select class=\"mailbox-owner\">\n" + _this.familyMembers.map(function (familyMember) {
          return "<option value=\"" + familyMember.useragentid + "\">" + familyMember.fullName + "</option>\n";
        }).join() + "                        </select>\n" + "                        </div>\n" + "                    </div>\n" + "                </div>\n" + "            </div>\n" + "        </div>", true, false, [{
          text: Translator.trans('button.ok'),
          click: function click() {
            var agentId = $('.ui-dialog-content .mailbox-owner').val();
            if (email) {
              _this.emailOwners[email] = agentId;
            }
            $(this).dialog('close');
            _this.selectOwnerDeferred.resolve(agentId);
          },
          'class': 'btn-blue'
        }, {
          text: Translator.trans('button.cancel'),
          click: function click() {
            $(this).dialog('close');
          },
          'class': 'btn-silver'
        }], 500);
        this.selectOwnerDialog.setOption('close', null);
      }
      this.selectOwnerDialog.open();
      return this.selectOwnerDeferred.promise();
    };
    return AddMailbox;
  }();
  return new AddMailbox();
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/mailbox/request.js":
/*!*********************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/mailbox/request.js ***!
  \*********************************************************************/
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
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function ($) {
  var Request = function () {
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
          if (_typeof(settings.button) === 'object' && settings.button !== null) {
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
            if (_typeof(settings.button) === 'object') {
              _this.hideButtonProgress(settings.button);
            }
          }
        },
        error: function error(jqXHR, status, _error) {
          _this.unlock();
          if (_typeof(settings.button) === 'object') {
            _this.hideButtonProgress(settings.button);
          }
          settings.error(jqXHR, status, _error);
        }
      });
    };
    return Request;
  }();
  return new Request();
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/timeline/directives.js":
/*!*************************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/timeline/directives.js ***!
  \*************************************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
__webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
__webpack_require__(/*! core-js/modules/es.number.is-integer.js */ "./node_modules/core-js/modules/es.number.is-integer.js");
__webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
__webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
__webpack_require__(/*! core-js/modules/es.string.trim.js */ "./node_modules/core-js/modules/es.string.trim.js");
__webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
__webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular-boot */ "./assets/bem/ts/shim/angular-boot.js"), __webpack_require__(/*! lib/utils */ "./web/assets/awardwalletnewdesign/js/lib/utils.js"), __webpack_require__(/*! lib/customizer */ "./web/assets/awardwalletnewdesign/js/lib/customizer.js"), __webpack_require__(/*! dateTimeDiff */ "./web/assets/common/js/dateTimeDiff.js"), __webpack_require__(/*! pages/timeline/main */ "./web/assets/awardwalletnewdesign/js/pages/timeline/main.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (angular, utils, customizer, dateTimeDiff) {
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module('app').directive('onFinishRender', ['$timeout', function ($timeout) {
    return {
      restrict: 'A',
      link: function link(scope, element, attr) {
        if (scope.$last === true) {
          $timeout(function () {
            scope.$emit(attr.onFinishRender ? attr.onFinishRender : 'ngRepeatFinished');
          });
        }
      }
    };
  }]).directive('onError', function () {
    return {
      restrict: 'A',
      link: function link($scope, $element, $attr) {
        $element.on('error', function () {
          $element.attr('src', $attr.onError);
        });
      }
    };
  }).directive('imageLazySrc', ['$document', 'scrollAndResizeListener', function ($document, scrollAndResizeListener) {
    var offsetFactor = 0.5;
    return {
      restrict: 'A',
      scope: {
        imageLazySrc: '='
      },
      link: function link($scope, $element, $attr) {
        var listenerRemover;
        function isInView(clientHeight, clientWidth) {
          var imageRect = $element[0].getBoundingClientRect();
          var offsetHeight = clientHeight * offsetFactor;
          var offsetWidth = clientWidth * offsetFactor;
          if (imageRect.top >= -offsetHeight && imageRect.bottom <= clientHeight + offsetHeight && imageRect.left >= -offsetWidth && imageRect.right <= clientWidth + offsetWidth) {
            $element.attr('src', $scope.imageLazySrc);
            listenerRemover();
            $scope.$watch('imageLazySrc', function (val) {
              if (val) {
                $element.attr('src', val);
              }
            });
          }
        }
        listenerRemover = scrollAndResizeListener.addListener(isInView);
        $element.on('$destroy', function () {
          return listenerRemover();
        });
        isInView($document[0].documentElement.clientHeight, $document[0].documentElement.clientWidth);
      }
    };
  }]).directive('wrapper', ['$timeout', function ($timeout) {
    return {
      restrict: 'A',
      link: function link(scope, element) {
        scope.$watch(function () {
          return scope.segment.undroppable;
        }, function (val) {
          var el = element.parents('.wrapper');
          if (val) {
            if (!el.hasClass('undroppable')) {
              el.addClass('undroppable');
            }
          } else {
            if (el.hasClass('undroppable')) {
              el.removeClass('undroppable');
            }
          }
        });
      }
    };
  }]).directive('tripStart', function () {
    return {
      restrict: 'EA',
      scope: {
        plans: '=',
        segment: '='
      },
      link: function link(scope, element) {
        scope.$watch('segment', function () {
          if (!scope.plans[scope.segment.planId] && window.showTooltips) {
            scope.plans[scope.segment.planId] = scope.segment;
            scope.plans[scope.segment.planId].needShowTooltips = true;
            window.tripStart = element;
          } else scope.plans[scope.segment.planId] = scope.segment;
        });
      }
    };
  }).directive('tripEnd', ['$timeout', function ($timeout) {
    return {
      restrict: 'EA',
      scope: {
        plans: '=',
        segment: '=',
        segments: '='
      },
      link: function link(scope, element) {
        scope.$watch('plans', function (o) {
          if (o && scope.plans[scope.segment.planId]) {
            var plan = scope.plans[scope.segment.planId];
            scope.segment.name = plan.name;
            var startSegment = scope.segments.indexOf(plan),
              endSegment = scope.segments.indexOf(scope.segment);
            var points = [],
              planLastUpdate = 0;
            for (var i = startSegment; i < endSegment; i++) {
              var current = scope.segments[i];
              if (current && (!current.icon || ['fly', 'bus', 'boat', 'passage-boat', 'train'].indexOf(current.icon) > -1 || current.icon.indexOf('fly') > -1) && current.type === 'segment' && current.map && current.map.points.length > 0 && points.length < 10 // Максимальное количество точек на миникарте
              ) {
                if (current.map.points.length === 2) {
                  points.push(current.map.points[0] + '-' + current.map.points[1]);
                } else {
                  points.push(current.map.points[0]);
                }
              }
              if (!current || undefined === current.lastUpdated) {
                continue;
              }
              if (current.type === 'planStart') {
                planLastUpdate = current.lastUpdated;
              }
              if (current.type === 'segment' && planLastUpdate < current.lastUpdated) {
                planLastUpdate = current.lastUpdated;
              }
            }
            if (points.length) {
              plan.map = points;
            }
            if (Number.isInteger(planLastUpdate) && planLastUpdate > 0) {
              plan.lastUpdated = dateTimeDiff.longFormatViaDates(new Date(), new Date(planLastUpdate * 1000));
            }
            if (plan.needShowTooltips) {
              plan.needShowTooltips = window.showTooltips = false;
              $timeout(function () {
                window.tripStart.find('[data-tip]').filter(function (id, el) {
                  return !!$(el).prop('tooltip-initialized');
                }).tooltip('open');
                if (element.find('[data-tip]').prop('tooltip-initialized')) element.find('[data-tip]').tooltip('open');
                $(document).one('click', function () {
                  try {
                    $('[data-tip]').filter(function (id, el) {
                      return !!$(el).prop('tooltip-initialized');
                    }).tooltip('close');
                    // eslint-disable-next-line no-empty
                  } catch (e) {}
                });
              }, 100);
            }
          }
        }, true);
      }
    };
  }]).directive('tripExpand', ['$timeout', '$state', function ($timeout, $state) {
    return {
      restrict: 'A',
      scope: {
        segment: '=tripExpand'
      },
      link: function link(scope, element) {
        function close() {
          $(element.next()).slideUp(300, function () {
            scope.$apply(function () {
              scope.segment.opened = false;
              $state.params.openSegment = null;
            });
          });
          if (undefined != scope.segment.dialogFlight) {
            scope.segment.dialogFlight.close();
          }
        }
        function open(duration) {
          duration = duration || 300;
          if (!scope.segment.details) {
            scope.$apply(function () {
              scope.segment.opened = false;
              $state.params.openSegment = null;
            });
            return;
          }
          scope.$apply(function () {
            scope.segment.opened = true;
            $state.params.openSegment = scope.segment.id;
          });
          $timeout(function () {
            $(element.next()).slideDown(duration, function () {
              if (document.location.href.match(/\/print\//)) {
                utils.debounce(function () {
                  window.print();
                }, 250);
              }
              if (!scope.segment.details.bookingLink) {
                return;
              }
              var row = $(element).closest('.trip-row');
              row.find('.checkin-date, .checkout-date').attr('data-role', 'datepicker');
              customizer.initDatepickers(row, function () {
                var checkinDatepicker = $(element).closest('.trip-row').find('input.checkin-date');
                var checkoutDatepicker = $(element).closest('.trip-row').find('input.checkout-date');
                var datepickerValue = checkinDatepicker.val();
                checkinDatepicker.datepicker('option', 'onSelect', function (date) {
                  var selectedDate = checkinDatepicker.datepicker("getDate");
                  selectedDate.setDate(selectedDate.getDate() + 1);
                  console.log(checkoutDatepicker.datepicker('option', 'all'));
                  var options = checkoutDatepicker.datepicker('option', 'all');
                  options.minDate = selectedDate;
                  checkoutDatepicker.datepicker(options);
                  checkoutDatepicker.datepicker('setDate', selectedDate);
                });
              });
              var autocompleteInput = $(element).closest('.trip-row').find('input.airport-name:not(.ui-autocomplete-input)');
              var autocompleteRequest;
              var autoCompleteData;
              autocompleteInput.off('keydown keyup change').on('keydown', function (e) {
                if (!$.trim($(e.target).val()) && (e.keyCode === 0 || e.keyCode === 32)) e.preventDefault();
              }).on('keyup', function (e) {
                scope.segment.details.bookingLink.formFields.selectedIata = null;
                scope.segment.details.bookingLink.formFields.selectedDestination = null;
              }).on('blur', function (e) {
                if (autoCompleteData.length) {
                  autocompleteInput.val(autoCompleteData[0].value);
                  scope.segment.details.bookingLink.formFields.selectedDestination = autoCompleteData[0].destination;
                } else {
                  autocompleteInput.val('');
                  scope.segment.details.bookingLink.formFields.selectedDestination = null;
                }
              }).autocomplete({
                delay: 200,
                minLength: 2,
                source: function source(request, response) {
                  if (request.term && request.term.length >= 3) {
                    var self = this;
                    if (autocompleteRequest) autocompleteRequest.abort();
                    autocompleteRequest = $.get(Routing.generate("google_geo_code", {
                      query: request.term
                    }), function (data) {
                      $(self.element).removeClass('loading-input');
                      var result = data.map(function (item) {
                        var country = item.address_components.filter(function (component) {
                          return component.types.indexOf('country') > -1;
                        });
                        var countryLong = country.length && country[0].long_name;
                        var city = item.address_components.filter(function (component) {
                          return component.types.indexOf('locality') > -1;
                        });
                        city = city.length && city[0].long_name;
                        return {
                          label: item.formatted_address,
                          value: city + ', ' + countryLong,
                          destination: city
                        };
                      });
                      if (!autocompleteInput.is(':focus')) {
                        if (result.length) {
                          autocompleteInput.val(result[0].value);
                          scope.segment.details.bookingLink.formFields.selectedDestination = result[0].destination;
                        } else {
                          scope.segment.details.bookingLink.formFields.selectedDestination = null;
                        }
                        scope.segment.details.bookingLink.formFields.selectedIata = null;
                      }
                      autoCompleteData = result;
                      response(result);
                    });
                  }
                },
                search: function search(event, ui) {
                  if ($(event.target).val().length >= 3) $(event.target).addClass('loading-input');else $(event.target).removeClass('loading-input');
                  $(event.target).nextAll('input').val("");
                },
                open: function open(event, ui) {
                  $(event.target).removeClass('loading-input');
                },
                create: function create() {
                  $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                    var regex = new RegExp("(" + this.element.val() + ")", "gi");
                    var itemLabel = item.label.replace(regex, "<b>$1</b>");
                    return $('<li></li>').data("item.autocomplete", item).append($('<a></a>').html(itemLabel)).appendTo(ul);
                  };
                },
                select: function select(event, ui) {
                  event.preventDefault();
                  $(event.target).val(ui.item.value);
                  scope.segment.details.bookingLink.formFields.selectedIata = null;
                  scope.segment.details.bookingLink.formFields.selectedDestination = ui.item.destination;
                }
              });
            });
          }, 1);
        }
        if (scope.segment.opened) {
          $timeout(function () {
            open(0);
          });
        }
        $(element).on('click', function () {
          if (scope.segment.opened) {
            close();
          } else {
            open();
          }
        });
        if ($state.params.openSegment === scope.segment.id) {
          $timeout(function () {
            if ($state.is('timeline')) {
              $('html, body').scrollTop($(element).offset().top - 50);
            }
          }, 100);
          $timeout(function () {
            $(element).trigger('click');
            $(element).next().effect('highlight');
          }, 200);
        }
      }
    };
  }]).directive('ownerAutocomplete', ['$rootScope', function ($rootScope) {
    return {
      restrict: 'A',
      scope: {
        ngData: '='
      },
      link: function link(scope, elem, attrs) {
        $rootScope.agentIsSet = false;
        var NoResultsLabel = '<i class="icon-warning-small"></i>&nbsp; No members found';
        elem.autocomplete({
          minLength: 2,
          source: function source(request, response) {
            var element = $(this.element).attr('class', 'loading-input');
            var lastResponse = $.ajax({
              url: Routing.generate('aw_business_members_dropdown_timeline', {
                q: request.term,
                add: true
              }),
              method: 'POST',
              success: function success(data, status, xhr) {
                if ($.isEmptyObject(data)) {
                  data = {
                    label: NoResultsLabel
                  };
                }
                if (lastResponse === xhr) {
                  response(data);
                  element.attr('class', 'clear-input');
                }
              }
            });
          },
          select: function select(event, ui) {
            if (ui.item.label === NoResultsLabel) {
              scope.ngData = '';
              return false;
            }
            $(event.target).val(ui.item.label);
            scope.ngData = ui.item.value;
            scope.$apply();
            $rootScope.agentIsSet = true;
            return false;
          },
          focus: function focus(event, ui) {
            if (ui.item.label === NoResultsLabel) {
              return false;
            }
            if (event.keyCode == 40 || event.keyCode == 38) $(event.target).val(ui.item.label);
            return false;
          }
        });
      }
    };
  }]);
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/timeline/filters.js":
/*!**********************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/timeline/filters.js ***!
  \**********************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular-boot */ "./assets/bem/ts/shim/angular-boot.js"), __webpack_require__(/*! pages/timeline/main */ "./web/assets/awardwalletnewdesign/js/pages/timeline/main.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (angular) {
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module('app').filter('capitalize', function () {
    return function (str) {
      if (typeof str !== 'string') {
        return '';
      }
      return str.charAt(0).toUpperCase() + str.slice(1);
    };
  });
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/timeline/main.js":
/*!*******************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/timeline/main.js ***!
  \*******************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular-boot */ "./assets/bem/ts/shim/angular-boot.js"), __webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! directives/autoFocus */ "./web/assets/awardwalletnewdesign/js/directives/autoFocus.js"), __webpack_require__(/*! webpack-ts/shim/ngReact */ "./assets/bem/ts/shim/ngReact.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
  angular.module('app', ['appConfig', 'react', 'ui.router', 'extendedDialog', 'customizer-directive', 'auto-focus-directive']).config(['$stateProvider', '$urlRouterProvider', '$locationProvider', function ($stateProvider, $urlRouterProvider, $locationProvider) {
    $locationProvider.html5Mode({
      enabled: true,
      rewriteLinks: true
    });
    $stateProvider.state({
      name: 'timeline',
      url: '/:agentId?before&openSegment&showDeleted&shownStart&openSegmentDate',
      params: {
        showDeleted: '0'
      }
    }).state({
      name: 'shared',
      url: '/shared/{code}'
    }).state({
      name: 'itineraries',
      url: '/itineraries/{itIds}?agentId'
    }).state({
      name: 'shared-plan',
      url: '/shared-plan/{code}'
    });
    $urlRouterProvider.otherwise('/');
    $urlRouterProvider.when('/{agentId}/itineraries/{itIds}', function ($state, $match) {
      var params = {
        itIds: $match.itIds
      };
      if ($match.agentId) {
        params.agentId = $match.agentId;
      }
      $state.go('itineraries', params);
    });
  }]).factory('httpInterceptor', ['$q', '$rootScope', function ($q, $rootScope) {
    var loadingCount = 0;
    return {
      request: function request(config) {
        window.$httpLoading = true;
        return config || $q.when(config);
      },
      response: function response(_response) {
        if (loadingCount-- < 1) {
          window.$httpLoading = false;
        }
        return _response || $q.when(_response);
      },
      responseError: function responseError(response) {
        if (loadingCount-- < 1) {
          window.$httpLoading = false;
        }
        return $q.reject(response);
      }
    };
  }]).config(['$httpProvider', function ($httpProvider) {
    $httpProvider.interceptors.push('httpInterceptor');
  }]).directive('notes', ['reactDirective', function (reactDirective) {
    return reactDirective((__webpack_require__(/*! webpack/js-deprecated/component-deprecated/timeline/Notes */ "./assets/js-deprecated/component-deprecated/timeline/Notes.js")["default"]));
  }]);
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/timeline/services.js":
/*!***********************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/timeline/services.js ***!
  \***********************************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
__webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
__webpack_require__(/*! core-js/modules/es.array.join.js */ "./node_modules/core-js/modules/es.array.join.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
__webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
__webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
__webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
__webpack_require__(/*! core-js/modules/es.object.values.js */ "./node_modules/core-js/modules/es.object.values.js");
__webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
__webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
__webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
__webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
__webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
__webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular-boot */ "./assets/bem/ts/shim/angular-boot.js"), __webpack_require__(/*! lib/utils */ "./web/assets/awardwalletnewdesign/js/lib/utils.js"), __webpack_require__(/*! dateTimeDiff */ "./web/assets/common/js/dateTimeDiff.js"), __webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! lib/customizer */ "./web/assets/awardwalletnewdesign/js/lib/customizer.js"), __webpack_require__(/*! pages/timeline/main */ "./web/assets/awardwalletnewdesign/js/pages/timeline/main.js"), __webpack_require__(/*! routing */ "./assets/bem/ts/service/router.ts"), __webpack_require__(/*! common/alerts */ "./web/assets/common/js/alerts.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (angular, utils, dateTimeDiff, dialog, customizer) {
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module('app').service('$timelineData', ['$stateParams', '$q', '$http', '$sce', '$state', '$window', function ($stateParams, $q, $http, $sce, $state, $window) {
    var options = {};
    var extend = function extend(item) {
      if (!item.type) return;
      if (!item.type.match(/plan/)) item.undroppable = true;
      $.extend(item, {
        trustHtml: function trustHtml(html) {
          return $sce.trustAsHtml(html);
        }
      });
      if (item.type === 'planStart') {
        $.extend(item, {
          getMapUrl: function getMapUrl() {
            if (typeof this.map === 'undefined' || angular.isArray(this.map) && this.map.length === 0) {
              return null;
            }
            return Routing.generate('aw_flight_map', {
              code: this.map.join(','),
              size: '240x240'
            });
          },
          printTravelPlan: function printTravelPlan() {
            $window.open(Routing.generate('aw_timeline_print') + 'shared-plan/' + this.shareCode);
          },
          planDuration: function planDuration() {
            return $sce.trustAsHtml(Translator.trans( /** @Desc("for %duration%") */'plan-duration', {
              duration: "<b>".concat(this.duration, "</b>")
            }, 'trips'));
          },
          getNotes: function getNotes() {
            return $sce.trustAsHtml(utils.linkify(item.notes.text));
          }
        });
      }
      if (item.type === 'date') {
        $.extend(item, {
          getRelativeDate: function getRelativeDate() {
            return dateTimeDiff.longFormatViaDates(new Date(), new Date(this.localDateISO));
          },
          getState: function getState() {
            var dayStart = new Date();
            dayStart.setHours(0, 0, 0, 0);
            return this.startDate <= dayStart / 1000;
          },
          getDaysNumberFromToday: function getDaysNumberFromToday() {
            var diff = Math.abs(new Date(this.startDate * 1000) - new Date());
            return Math.floor(diff / 1000 / 60 / 60 / 24);
          }
        });
      }
      if (item.type === 'segment') {
        if (item.details) {
          item.details.extProperties = Object.keys(item.details).filter(function (propName) {
            return typeof item.details[propName] === 'string' && ['notes', 'monitoredStatus', 'canEdit', 'shareCode', 'autoLoginLink', 'refreshLink', 'accountId', 'currencyCode'].indexOf(propName) === -1;
          }).reduce(function (acc, propName) {
            acc[propName] = item.details[propName];
            return acc;
          }, {});
        }
        $.extend(item, {
          _formatTime: function _formatTime(time) {
            var parts = time.split(' ');
            return parts.length > 1 ? $sce.trustAsHtml(parts[0] + '<span>' + parts[1] + '</span>') : $sce.trustAsHtml(time);
          },
          getTitle: function getTitle() {
            return $sce.trustAsHtml(this.title);
          },
          getImgSrc: function getImgSrc(size) {
            if (typeof this.map.points === 'undefined' || angular.isArray(this.map.points) && this.map.points.length === 0) {
              return null;
            }
            if (this.map.points.length > 1) {
              return Routing.generate('aw_flight_map', {
                code: this.map.points.join('-'),
                size: size
              });
            } else {
              return Routing.generate('aw_flight_map', {
                code: this.map.points[0],
                size: size
              });
            }
          },
          getLocalTime: function getLocalTime() {
            return this._formatTime(this.localTime);
          },
          getArrDate: function getArrDate() {
            return this._formatTime(this.map.arrTime);
          },
          getState: function getState() {
            return this.endDate <= Date.now() / 1000;
          },
          getBetween: function getBetween() {
            if ('undefined' !== typeof this.localDateISO) {
              return dateTimeDiff.longFormatViaDates(new Date(), new Date(this.localDateISO));
            }
            return dateTimeDiff.longFormatViaDates(new Date(), new Date(this.startDate * 1000));
          },
          getBetweenText: function getBetweenText(row) {
            var date = '<span class="blue">' + row.date + '</span>',
              term;
            if (row.type === 'checkin') {
              term = '<span class="red">' + row.nights + '</span> ' + Translator.transChoice( /** @Desc("night|nights") */'nights', row.nights);
            } else if ('undefined' === typeof row.days) {
              return $sce.trustAsHtml(date);
            } else {
              term = '<span class="red">' + row.days + '</span> ' + Translator.transChoice( /** @Desc("day|days") */'days', row.days);
            }
            var text = Translator.trans( /** @Desc("on %date% for %term%") */'between.text', {
              date: date,
              term: term
            });
            return $sce.trustAsHtml(text);
          },
          getNotes: function getNotes() {
            var isShort = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
            var notes = this.details.notes;
            if (-1 !== notes.indexOf("\n") && -1 === notes.indexOf('<br>')) {
              notes = notes.replace(/\n/g, '<br>');
            }
            if (isShort) {
              var flatTags = ['i', 'em', 'strong', 'b', 'u'];
              flatTags.forEach(function (tag) {
                notes = notes.replace(new RegExp("\n<" + tag + '>', "g"), '').replace(new RegExp("\n</" + tag + ">", "g"), '').replace(new RegExp('<' + tag + ">\n", "g"), '').replace(new RegExp('</' + tag + ">\n", "g"), '').replace(new RegExp('<' + tag + '>', "g"), '').replace(new RegExp('</' + tag + '>', "g"), '');
              });
              notes = notes.replace(/(\r\n){2,}|\r{2,}|\n{2,}/g, ' ').replace(/(<([^>]+)>)/gi, ' ');
            }
            notes = utils.linkify(notes);
            return $sce.trustAsHtml(notes);
          },
          getRelativeDate: function getRelativeDate() {
            return dateTimeDiff.longFormatViaDates(new Date(), new Date(this.localDateISO));
          },
          getDaysNumberFromToday: function getDaysNumberFromToday() {
            var diff = Math.abs(new Date(this.startDate * 1000) - new Date());
            return Math.floor(diff / 1000 / 60 / 60 / 24);
          },
          getTimeDiffFormated: function getTimeDiffFormated(row) {
            var diff = row.timestamp - Date.now() / 1000;
            if (diff > 0 && diff <= 86400) {
              return dateTimeDiff.longFormatViaDateTimes(new Date(), new Date(row.timestamp * 1000));
            }
            return false;
          },
          getDiffTimeAgo: function getDiffTimeAgo(time) {
            return dateTimeDiff.longFormatViaDateTimes(new Date(), new Date(time * 1000));
          },
          isShowMoreLinks: function isShowMoreLinks() {
            var is = this.details.extProperties && Object.keys(this.details.extProperties).length > 0 || this.details.notes || this.isManualSegment() || this.isAutoAddedSegment();
            if (is && 'undefined' === typeof this.isShownInfo && 'undefined' !== typeof this.alternativeFlights) {
              this.isShownInfo = true;
            }
            return is;
          },
          isManualSegment: function isManualSegment() {
            return this.origins && this.origins.manual;
          },
          isAirSegment: function isAirSegment() {
            return this.air;
          },
          isAutoAddedSegment: function isAutoAddedSegment() {
            return this.origins && !this.origins.manual && this.origins.auto && this.origins.auto.length > 0;
          },
          getEditLink: function getEditLink() {
            return Routing.generate('aw_trips_edit', {
              tripId: this.id
            });
          },
          visible: false,
          getEliteLevel: function getEliteLevel(phoneItem) {
            return utils.escape(phoneItem.level);
          },
          redirectToBooking: function redirectToBooking() {
            var payload;
            var row = $('.trip-title[data-id="' + this.id + '"]').closest('.trip-row');
            var checkinDate = new Date(row.find('input[type="hidden"][id^="checkinDate_"]').val()).toISOString();
            var checkoutDate = new Date(row.find('input[type="hidden"][id^="checkoutDate_"]').val()).toISOString();
            var destination = this.details.bookingLink.formFields.selectedDestination || this.details.bookingLink.formFields.destination;
            if (!this.details.bookingLink.formFields.selectedIata && !destination) return;
            payload = {
              ss: destination,
              checkin_monthday: checkinDate.slice(8, 10),
              checkin_year_month: checkinDate.slice(0, 7),
              checkout_monthday: checkoutDate.slice(8, 10),
              checkout_year_month: checkoutDate.slice(0, 7),
              timelineForm: true
            };
            payload.ss.replace(/Russian Federation/gi, 'Russia');
            var url = this.details.bookingLink.formFields.url + '&' + $.param(payload);
            var link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.click();
          },
          formatCost: function formatCost(value) {
            return Intl.NumberFormat(customizer.locales()).format(value);
          },
          getTravelersCount: function getTravelersCount(count) {
            return $sce.trustAsHtml(Translator.transChoice( /** @Desc("%number% passenger|%number% passengers") */'number-passengers', count, {
              'number': count
            }, 'trips'));
          },
          alternativeFlight: function alternativeFlight($event) {
            var _this = this;
            var oldPopup = $('.alternative-flight:visible');
            if (oldPopup.length) {
              oldPopup.closest('.ui-dialog').find('.ui-dialog-titlebar-close').click();
            }
            var popup = jQuery('.alternative-flight', $($event.target).closest('.details-info')).clone();
            popup.find('input[ng-checked="true"]').prop('checked', true);
            popup.find('.alternative-flight_block__name, .alternative-flight_block__add span').click(function (e) {
              return $(e.target).closest('.alternative-flight_block').find('.alternative-flight_block__check input[name="customPick"]').prop('checked', true).trigger('change');
            }).end().find('input[name="customValue"]').keyup(function (e) {
              if ('' !== $(e.target).val()) {
                $(e.target).closest('.alternative-flight_block__add').find('>span').trigger('click');
              }
            }).end().data('addclass', 'dialog-alternative-flight').find('.js-btn-save').click(function (e) {
              return _this.updateAlternativeFlight(e);
            }).find('.alternative-flight-tpl').removeClass('alternative-flight-tpl');
            this.dialogFlight = dialog.createNamed('flights', popup, {
              title: Translator.trans( /** @Desc("Choose Alternative Flight") */'choose-alternative-flight', {}, 'trips'),
              width: 700,
              resizable: false
            });
            this.dialogFlight.open();
            this.dialogFlight.setOption('close', function () {
              return _this.dialogFlight.destroy();
            });
          },
          updateAlternativeFlight: function updateAlternativeFlight(e) {
            var $dialog = $(e.target).closest('.ui-widget-content');
            $('.customset-errors', $dialog).empty();
            var $btn = $(e.target),
              pick = $('input[name="customPick"]:checked', $dialog).val(),
              customValue = $('input[name="customValue"]', $dialog).val();
            $btn.addClass('loader');
            var self = this;
            $.post(Routing.generate('aw_timeline_milevalue_customset'), {
              'id': this.id,
              'customPick': pick,
              'customValue': customValue
            }, function (response) {
              $btn.removeClass('loader');
              if (response.success) {
                for (var i in response.data) {
                  self.alternativeFlights[i] = response.data[i];
                }
                self.dialogFlight.close();
              } else if (response.errors) {
                $('.customset-errors', $dialog).html(Object.values(response.errors).join('<br>'));
              }
            }, 'json');
          },
          formatFileSize: function formatFileSize(bytes) {
            return utils.formatFileSize(bytes);
          },
          formatDateTime: function formatDateTime(strDate) {
            return new Intl.DateTimeFormat(customizer.locales(), {
              dateStyle: 'medium',
              timeStyle: 'short'
            }).format(Date.parse(strDate));
          },
          getFileLink: function getFileLink(fileId) {
            return Routing.generate('aw_timeline_itinerary_fetch_file', {
              itineraryFileId: fileId
            });
          },
          setOptions: function setOptions(params) {
            options = params;
          },
          printPropertiesValue: function printPropertiesValue(name, value) {
            if (Object.prototype.hasOwnProperty.call(options, 'collapseFieldProperties') && -1 !== options.collapseFieldProperties.indexOf(name)) {
              return $sce.trustAsHtml("\n                                    <a class=\"properties-value-collapse\" href=\"#collapse\"></a>\n                                    <div class=\"details-property-name\"><a class=\"properties-value-collapse-name\" href=\"#collapse\">".concat(name, "</a></div>\n                                    <div class=\"details-property-value details-properties-collapse\">").concat(value, "</div>\n                                "));
            }
            return $sce.trustAsHtml("\n                                <div class=\"details-property-name\">".concat(name, "</div>\n                                <div class=\"details-property-value\">").concat(value, "</div>\n                            "));
          },
          isLayoverSegment: function isLayoverSegment() {
            return 'L.' === this.id.substr(0, 2);
          },
          showPopupNativeApps: function showPopupNativeApps() {
            var head = Translator.trans( /** @Desc("AwardWallet has native iOS and Android apps, %break%please pick the one you need") */'awardwallet-has-native-apps-pick-need', {
              'break': ''
            });
            var content = "\n                                <div>\n                                    <a href=\"https://apps.apple.com/us/app/awardwallet-track-rewards/id388442727\" target=\"app\"><img src=\"/assets/awardwalletnewdesign/img/device/ios/en.png\" alt=\"\"></a>\n                                    <a href=\"https://play.google.com/store/apps/details?id=com.itlogy.awardwallet\" target=\"app\"><img src=\"/assets/awardwalletnewdesign/img/device/android/en.png\" alt=\"\"></a>\n                                </div>\n                            ";
            var popup = dialog.createNamed('nativeApps', $("<div data-addclass=\"popup-native-apps\">".concat(content, "</div>")), {
              title: head,
              width: 474,
              autoOpen: true,
              modal: true,
              onClose: function onClose() {
                popup.destroy();
              }
            });
          }
        });
      }
      var segmentClasses = item.icon;
      if (item.air && (typeof item.map === 'undefined' || typeof item.map.points === 'undefined' || angular.isArray(item.map.points) && item.map.points.length < 2)) {
        segmentClasses += ' partial';
      }
      item.segmentClasses = segmentClasses;
    };
    return {
      fetch: function fetch(after) {
        var defer = $q.defer();
        defer.promise.cancel = function () {
          defer.reject();
        };

        // preloaded
        if (Object.prototype.hasOwnProperty.call(window, 'TimelineData') && _typeof(window.TimelineData) == 'object') {
          var data = window.TimelineData;
          data.segments.map(function (el) {
            extend(el, data.segments);
          });
          defer.resolve(data);
          return defer.promise;
        }
        var route = Routing.generate('aw_timeline_data', {
          agentId: $stateParams.agentId || null,
          before: after ? null : $stateParams.before || null,
          after: after || null,
          showDeleted: $stateParams.showDeleted || 0
        });
        if ($state.is('shared')) route = Routing.generate('aw_timeline_data_shared', {
          shareCode: $stateParams.code
        });
        if ($state.is('shared-plan')) route = Routing.generate('aw_travelplan_data_shared', {
          shareCode: $stateParams.code
        });
        if ($state.is('itineraries')) route = Routing.generate('aw_timeline_data_segments', {
          'itIds': $stateParams.itIds,
          'agentId': $stateParams.agentId || null
        });
        $http({
          url: route,
          disableErrorDialog: true
        }).then(function (response) {
          if (response.status !== 200 || _typeof(response.data) !== 'object') {
            if ($stateParams.openSegment) sessionStorage.backUrl = Routing.generate('aw_timeline') + '?openSegment=' + $stateParams.openSegment;
            location.href = '/login';
          } else {
            response.data.segments.map(function (el) {
              extend(el);
            });
            defer.resolve(response.data);
          }
        }, function (response) {
          if (response.status === 403) {
            var options = {
              content: Translator.trans( /** @Desc("You are attempting to access a travel reservation that belongs to a different account than the one you are logged in as right now. If you opened this link by mistake, please navigate to another page, if you know this is your travel reservation then you must login as a user to whom this travel reservation belongs. If you are coming to this page from an email you received try using that email address as your login value.") */'trips.access.denied.popup'),
              title: Translator.trans( /** @Desc("Access Denied") */'access.denied'),
              closeOnEscape: false,
              width: 600,
              open: function open(event, ui) {
                $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
              },
              buttons: [{
                text: Translator.trans( /**@Desc("Ok")*/'alerts.btn.ok'),
                click: function click() {
                  location.href = '/timeline/';
                },
                'class': 'btn-blue'
              }]
            };
            jAlert(options);
          } else if (response.status === 406) {
            var _options = {
              content: response.data.error,
              title: Translator.trans( /** @Desc("Access Denied") */'access.denied'),
              closeOnEscape: false,
              width: 600,
              open: function open(event, ui) {
                $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
              },
              buttons: [{
                text: Translator.trans( /**@Desc("Ok")*/'alerts.btn.ok'),
                click: function click() {
                  location.href = '/members/connection/' + response.data.agentId;
                },
                'class': 'btn-blue'
              }]
            };
            jAlert(_options);
          }
        });
        return defer.promise;
      }
    };
  }]).service('$travelPlans', ['$http', function ($http) {
    return {
      move: function move(params) {
        return $http.post(Routing.generate('aw_travelplan_move'), $.param(params), {
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        });
      }
    };
  }]).service('scrollAndResizeListener', ['$window', '$document', '$timeout', function ($window, $document, $timeout) {
    var scrollTimeout;
    var resizeTimeout;
    var id = 0;
    var listeners = {};
    function invokeListeners() {
      var clientHeight = $document[0].documentElement.clientHeight;
      var clientWidth = $document[0].documentElement.clientWidth;
      for (var key in listeners) {
        if (Object.prototype.hasOwnProperty.call(listeners, key)) {
          listeners[key](clientHeight, clientWidth);
        }
      }
    }
    $window.addEventListener('scroll', function () {
      $timeout.cancel(scrollTimeout);
      scrollTimeout = $timeout(invokeListeners, 200);
    });
    $window.addEventListener('resize', function () {
      $timeout.cancel(resizeTimeout);
      resizeTimeout = $timeout(invokeListeners, 200);
    });
    return {
      addListener: function addListener(listener) {
        var index = ++id;
        listeners[id] = listener;
        return function () {
          return delete listeners[index];
        };
      }
    };
  }]);
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/timeline/timeline.js":
/*!***********************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/timeline/timeline.js ***!
  \***********************************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
__webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
__webpack_require__(/*! core-js/modules/es.promise.finally.js */ "./node_modules/core-js/modules/es.promise.finally.js");
__webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
__webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
__webpack_require__(/*! core-js/modules/es.string.search.js */ "./node_modules/core-js/modules/es.string.search.js");
__webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
__webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
__webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
__webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
__webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
__webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
__webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular-boot */ "./assets/bem/ts/shim/angular-boot.js"), __webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! lib/utils */ "./web/assets/awardwalletnewdesign/js/lib/utils.js"), __webpack_require__(/*! pages/mailbox/add */ "./web/assets/awardwalletnewdesign/js/pages/mailbox/add.js"), __webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! lib/customizer */ "./web/assets/awardwalletnewdesign/js/lib/customizer.js"), __webpack_require__(/*! directives/customizer */ "./web/assets/awardwalletnewdesign/js/directives/customizer.js"), __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js"), __webpack_require__(/*! routing */ "./assets/bem/ts/service/router.ts"), __webpack_require__(/*! angular-ui-router */ "./web/assets/common/vendors/angular-ui-router/release/angular-ui-router.js"), __webpack_require__(/*! directives/extendedDialog */ "./web/assets/awardwalletnewdesign/js/directives/extendedDialog.js"), __webpack_require__(/*! pages/timeline/main */ "./web/assets/awardwalletnewdesign/js/pages/timeline/main.js"), __webpack_require__(/*! pages/timeline/directives */ "./web/assets/awardwalletnewdesign/js/pages/timeline/directives.js"), __webpack_require__(/*! pages/timeline/filters */ "./web/assets/awardwalletnewdesign/js/pages/timeline/filters.js"), __webpack_require__(/*! pages/timeline/services */ "./web/assets/awardwalletnewdesign/js/pages/timeline/services.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (angular, $, utils, addMailbox, dialog, customizer) {
  angular = angular && angular.__esModule ? angular.default : angular;

  // persons_menu
  var countWithNull;
  var showWithNull = false;
  var isTimeline = true;
  if (isTimeline) {
    $(document).on('update.hidden.users', function () {
      countWithNull = 0;
      if (!showWithNull) {
        $('.js-persons-menu').find('li').each(function (id, el) {
          if ($(el).find('.count').length) {
            if ($(el).find('.count').text() === '0' && !$(el).hasClass('active') && $(el).find('a[data-id=my]').length === 0) {
              $(el).slideUp();
              countWithNull++;
            } else {
              $(el).slideDown();
            }
          }
        });
        if (countWithNull) {
          $('#users_showmore').closest('li').slideDown();
        } else {
          $('#users_showmore').closest('li').slideUp();
        }
      }
    });
    $(document).on('click', '#users_showmore', function (e) {
      e.preventDefault();
      $('.js-persons-menu').find('li:hidden').slideDown();
      $('#users_showmore').closest('li').slideUp();
      showWithNull = true;
    });
  }
  $(window).on('person.activate', function (event, id) {
    var $persons = $('.js-persons-menu'),
      $person = null;
    if (!(id instanceof jQuery)) {
      if (-1 !== id.indexOf('_')) id = id.split('_')[1] || 'my';
      if ('' == id) id = 'my';
      $person = $persons.find('a[data-id="' + id + '"]');
      0 === $person.length ? $person = $persons.find('a[data-agentid="' + id + '"]:first') : null;
      0 === $person.length ? $person = $persons.find('a[data-id="my"]') : null;
    }
    if ($person instanceof jQuery) {
      $persons.children().removeClass('active');
      $persons.find('a span.count').removeClass('blue').addClass('silver');
      $person.parents('li').addClass('active');
      $person.find('span.count').removeClass('silver').addClass('blue');
      $(window).trigger('person.active', $($person).data('id'));
    }
    $(document).trigger('update.hidden.users');
  });

  // lib/design
  $(document).on('click', '.js-add-new-person, #add-person-btn, .js-persons-menu a[href="/user/connections"].add', function (e) {
    e.preventDefault();
    __webpack_require__.e(/*! AMD require */ "web_assets_awardwalletnewdesign_js_pages_agent_addDialog_js").then(function() { var __WEBPACK_AMD_REQUIRE_ARRAY__ = [__webpack_require__(/*! pages/agent/addDialog */ "./web/assets/awardwalletnewdesign/js/pages/agent/addDialog.js")]; (function (clickHandler) {
      clickHandler();
    }).apply(null, __WEBPACK_AMD_REQUIRE_ARRAY__);})['catch'](__webpack_require__.oe);
  });
  angular.module('app').controller('timeline', ['$scope', '$timelineData', '$stateParams', '$state', '$filter', '$http', '$timeout', '$sce', '$travelPlans', '$log', '$location', '$window', '$transitions', function ($scope, $timelineData, $stateParams, $state, $filter, $http, $timeout, $sce, $travelPlans, $log, $location, $window, $transitions) {
    $scope.stateParams = $stateParams;
    $scope.$log = $log;
    $scope.segments = [];
    $scope.haveFutureSegments = false;
    $scope.agents = [];
    $scope.plans = [];
    $scope.agent = {
      newowner: '',
      copy: false
    };
    $scope.canAdd = false;
    $scope.embeddedData = Object.prototype.hasOwnProperty.call(window, 'TimelineData') && _typeof(window.TimelineData) == 'object';
    $scope.activeSegmentNumber = null;
    $scope.noForeignFeesCards = [];
    $scope.options = {};
    var overlay = $('<div class="ui-widget-overlay"></div>').hide().appendTo('body');
    addMailbox.subscribe();
    addMailbox.setRedirectUrl(Routing.generate('aw_usermailbox_view'));
    $scope.methods = {
      segmentLink: function segmentLink(segmentId) {
        return Routing.generate('aw_timeline_show', {
          segmentId: segmentId
        });
      },
      tossingFill: function tossingFill(segment, segments) {
        if (segment.type.match(/plan/)) {
          this.tossingClear(segment, segments);
          var i = segments.indexOf(segment) - 1;
          while (i > 0 && !segments[i].type.match(/plan/)) {
            segments[i].undroppable = false;
            i--;
          }
          i = segments.indexOf(segment) + 1;
          while (i < segments.length && !segments[i].type.match(/plan/)) {
            segments[i].undroppable = false;
            i++;
          }
          $timeout(function () {
            $(".ui-sortable").sortable("refresh");
          }, 100);
        }
      },
      tossingClear: function tossingClear(segment, segments) {
        if (segment.type.match(/plan/)) {
          angular.forEach(segments, function (seg) {
            seg.undroppable = true;
          });
        }
      },
      tossingDrop: function tossingDrop(segment, segments, $event) {
        $scope.res = segments;
      },
      escape: function escape(event, segment) {
        if (event.keyCode == 27) segment.changeNameState = false;
      },
      move: function move(segment, agent, event) {
        $(event.target).addClass('loader').prop('disabled', true);
        $http({
          url: agent.newowner != 'my' ? Routing.generate('aw_timeline_move', {
            'itCode': segment.id,
            'agent': agent.newowner
          }) : Routing.generate('aw_timeline_move', {
            'itCode': segment.id
          }),
          method: 'POST',
          data: $.param({
            copy: agent.copy
          }),
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        }).then(function () {
          $scope.recalculateAfter = true;
          $state.reload();
        })["finally"](function () {
          $(event.target).removeClass('loader').prop('disabled', false);
        });
      },
      getMoveText: function getMoveText(segments, conf_no) {
        var n_segments = Translator.transChoice( /** @Desc("%count% segment|%count% segments") */'n_segments', segments, {
          'count': segments
        }, 'trips');
        return Translator.trans( /** @Desc("All %segments% of Conf# %conf_no% will be moved (or copied), if this is not when you intended you can delete the segments you don't need later.") */'move_all_segments', {
          'segments': n_segments,
          conf_no: conf_no
        }, 'trips');
      },
      getOriginText: function getOriginText(origin, listItem) {
        if (origin.type === 'account') {
          var params = {
            'providerName': origin.provider,
            'accountNumber': origin.accountNumber,
            'owner': origin.owner,
            'link_on': '<a target="_blank" href="' + Routing.generate('aw_account_list') + '/?account=' + origin.accountId + '">',
            'link_off': '</a>',
            'bold_on': '<b>',
            'bold_off': '</b>'
          };
          if (listItem) {
            return $sce.trustAsHtml(Translator.trans( /** @Desc("%link_on%%bold_on%%providerName%%bold_off% online account %bold_on%%accountNumber%%bold_off%%link_off% that belongs to %owner%") */'trips.segment.added-from.account', params, 'trips'));
          } else {
            return $sce.trustAsHtml(Translator.trans( /** @Desc("This trip segment was automatically added by retrieving it from %link_on%%bold_on%%providerName%%bold_off% online account %bold_on%%accountNumber%%bold_off%%link_off%, which belongs to %owner%.") */'trips.segment.added-from.account.extended', params, 'trips'));
          }
        } else if (origin.type === 'confNumber') {
          var _params = {
            'providerName': origin.provider,
            'confNumber': origin.confNumber,
            'bold_on': '<b>',
            'bold_off': '</b>'
          };
          if (listItem) {
            return $sce.trustAsHtml(Translator.trans( /** @Desc("From %bold_on%%providerName%%bold_off% using confirmation number %bold_on%%confNumber%%bold_off%") */'trips.segment.added-from.conf-number', _params, 'trips'));
          } else {
            return $sce.trustAsHtml(Translator.trans( /** @Desc("This trip segment was automatically added by retrieving it from %bold_on%%providerName%%bold_off% using confirmation number %bold_on%%confNumber%%bold_off%.") */'trips.segment.added-from.conf-number.extended', _params, 'trips'));
          }
        } else if (origin.type === 'email') {
          if (origin.from === 2 || origin.from === 1) {
            // from scanner or plans
            var _params2;
            if (origin.from === 1) {
              _params2 = {
                'email': origin.email,
                'link_on': '',
                'link_off': ''
              };
            } else {
              _params2 = {
                'email': origin.email,
                'link_on': '<a target="_blank" href="' + Routing.generate('aw_usermailbox_view') + '">',
                'link_off': '</a>'
              };
            }
            if (listItem) {
              return $sce.trustAsHtml(Translator.trans( /** @Desc("An email that was sent to %link_on%%email%%link_off%") */'trips.segment.added-from.email', _params2, 'trips'));
            } else {
              return $sce.trustAsHtml(Translator.trans( /** @Desc("This trip segment was automatically added by parsing a reservation email that was sent to %link_on%%email%%link_off%.") */'trips.segment.added-from.email.extended', _params2, 'trips'));
            }
          } else {
            if (listItem) {
              return $sce.trustAsHtml(Translator.trans( /** @Desc("An email that was forwarded to us") */'trips.segment.added-from.unknown-email', {}, 'trips'));
            } else {
              return $sce.trustAsHtml(Translator.trans( /** @Desc("This trip segment was automatically added by parsing a reservation email that was forwarded to us.") */'trips.segment.added-from.unknown-email.extended', {}, 'trips'));
            }
          }
        } else if (origin.type === 'tripit') {
          var _params3 = {
            'email': origin.email,
            'link_on': '<a target="_blank" href="' + Routing.generate('aw_usermailbox_view') + '">',
            'link_off': '</a>'
          };
          if (listItem) {
            return $sce.trustAsHtml(Translator.trans( /** @Desc("Your TripIt account %email%") */'trips.segment.added-from.tripit', _params3, 'trips'));
          } else {
            return $sce.trustAsHtml(Translator.trans( /** @Desc("This trip segment was automatically added by synchronizing with your TripIt account, %link_on%%email%%link_off%") */'trips.segment.added-from.tripit.extended', _params3, 'trips'));
          }
        }
      },
      changeName: function changeName(segment, e) {
        e.preventDefault();
        segment.renamingState = true;
        $http({
          url: Routing.generate('aw_travelplan_rename', {
            'plan': segment.planId
          }),
          data: $.param({
            name: segment.name
          }),
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })['finally'](function () {
          segment.renamingState = false;
          segment.changeNameState = false;
        });
      },
      requestDeletePlan: function requestDeletePlan(segment) {
        overlay.fadeIn();
        //segment.deletePlanState = true;
        $http({
          url: Routing.generate('aw_travelplan_delete', {
            'plan': segment.planId
          }),
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        }).then(function (result) {
          $scope.recalculateAfter = true;
          $state.reload();
        });
      },
      deletePlan: function deletePlan($event, segment) {
        var _this = this;
        if ($($event.currentTarget).closest('div[data-trip-start]').find('.js-notes-filled').length > 0) {
          var confirmPopup = dialog.fastCreate(Translator.trans('confirmation', {}, 'trips'), Translator.trans('you-sure-also-delete-notes', {}, 'trips'), true, true, [{
            'class': 'btn-silver',
            'text': Translator.trans('button.no'),
            'click': function click() {
              return confirmPopup.destroy();
            }
          }, {
            'class': 'btn-blue',
            'text': Translator.trans('button.yes'),
            'click': function click() {
              confirmPopup.destroy();
              _this.requestDeletePlan(segment);
            }
          }], 400, 300);
          return;
        }
        this.requestDeletePlan(segment);
      },
      deleteOrUndelete: function deleteOrUndelete(segment, isUndelete) {
        segment.deleteLoader = true;
        $http.post(Routing.generate('aw_timeline_delete', {
          segmentId: segment.id,
          undelete: isUndelete || null
        })).then(function (res) {
          if (res.data === true) {
            // segment.deleteLoader = false;
            $scope.recalculateAfter = true;
            $scope.showDeleted = isUndelete;
            $state.reload();
          }
        });
      },
      confirmChanges: function confirmChanges(segment) {
        segment.confirmLoader = true;
        $http.post(Routing.generate('aw_timeline_confirm_changes', {
          segmentId: segment.id
        })).then(function (res) {
          segment.confirmLoader = false;
          if (res.data === true) {
            segment.changed = false;

            // refs #15588
            $scope.segments.filter(function (_) {
              return _.group === segment.group && !_.details;
            }).forEach(function (_) {
              return _.changed = false;
            });
          }
        });
      },
      goRefresh: function goRefresh(link) {
        $('<form method="post"/>').attr('action', link).appendTo('body').submit();
      },
      scrollToTop: function scrollToTop() {
        $timeout(function () {
          $("html,body").stop().animate({
            scrollTop: 0
          }, 500);
        }, 0);
      },
      hoverInSegment: function hoverInSegment(segment) {
        $scope.activeSegmentNumber = segment.group ? segment.group : null;
      },
      hoverOutSegment: function hoverOutSegment() {
        $scope.activeSegmentNumber = null;
      },
      createPlan: function createPlan(segment) {
        segment.createPlanState = true;
        window.showTooltips = true;
        overlay.fadeIn();
        $http({
          url: Routing.generate('aw_travelplan_create'),
          data: $.param({
            userAgentId: $stateParams.agentId && $stateParams.agentId != '' ? $stateParams.agentId : null,
            startTime: segment.startDate
          }),
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        }).then(function (res) {
          if (res != null) {
            $scope.shownFrom = res.startTime;
          }
          $scope.recalculateAfter = true;
          $state.reload();
        }).finally(function () {
          window.afterPlanCreated = true;
          //segment.createPlanState = false;
        });
      }
    };

    $scope.$on('print', function () {
      //печать таймлайна
      if (/\/print\//.test($location.$$absUrl) && !$scope.spinner) {
        $window.print();
      }
    });
    var dataRequest;
    $transitions.onSuccess({}, function (transition) {
      var toParams = transition.params('to');
      var fromParams = transition.params('from');

      // Редирект на авторизацию, если неавторизованный зашел на таймлайн
      if (!($state.is('shared') || $state.is('shared-plan')) && $('a[href*="login"]').length && !$scope.embeddedData) {
        location.href = '/login?BackTo=%2Ftimeline%2F';
      }
      if (true !== $scope.recalculateAfter && fromParams.openSegment !== toParams.openSegment && fromParams.agentId && fromParams.agentId === toParams.agentId) {
        return;
      }
      $scope.showDeleted = $stateParams.showDeleted || $scope.showDeleted;
      var agentMatch = location.search.match(/\?agentId=(\d+)/);
      if (agentMatch) {
        $scope.agentId = agentMatch[1];
      } else {
        $scope.agentId = $stateParams.agentId || '';
      }
      addMailbox.setOwner($scope.agentId);

      /*
          Нужно перезагрузить таймлайн в случаях:
           смены собственника
           при первой загрузке (не загружены сегменты)
           удаление before из адресной строки
       */
      if (fromParams.agentId !== toParams.agentId || !$scope.segments.length || fromParams.before && !toParams.before) {
        $scope.segments.length = 0;
        $scope.spinner = true;
        // если оставить after, то таймлайн перезагрузится
        // на нужном отскроллированном месте (сохранение позиции) в прошлом
        if (!$scope.forceAfter) {
          $scope.after = undefined;
        }
      }

      // Сохранение позиции перед перегрузкой таймлайна
      // при Show/Hide deleted а так же иных действиях над сегментами (recalculateAfter)
      if (typeof fromParams.showDeleted !== 'undefined' && typeof toParams.showDeleted !== 'undefined' && fromParams.showDeleted !== toParams.showDeleted || $scope.recalculateAfter) {
        console.log('Recalculate after...');
        if ($scope.segments.length) {
          // after будет использован для запроса данных с сервера начиная с этой даты
          $scope.after = parseInt($scope.segments[0].startDate);
          $scope.shownSegments = $filter('filter')($scope.segments, {
            visible: true
          });
          if ($scope.shownSegments.length) {
            $scope.forceAfter = true;
            // shownFrom с какого времени показывать сегменты.
            // Полученные с сервера сегменты будут сравниваться с этой датой показываться/скрываться
            if ($scope.shownFrom && $scope.after > $scope.shownFrom) {
              $scope.after = $scope.shownFrom;
            } else {
              $scope.shownFrom = parseInt($scope.shownSegments[0].startDate);
            }
          }
        }
        if (!$scope.recalculateAfter) {
          $scope.pastSpinner = true;
        }
        $scope.containerHeight = $('.trip').height();
      }
      var anchor = null;
      var shownSegments = $filter('filter')($scope.segments, {
        visible: true
      });
      var offsetTop = 0;
      if (shownSegments.length) {
        anchor = shownSegments[0];
        var anchorElement = $('div[data-id="' + anchor.id + '"]');
        if (anchorElement.length) offsetTop = anchorElement.offset().top;
      } else offsetTop = 0;

      // Если в массиве сегментов есть непоказанные объекты - показываем
      // либо, если страницу таймлайна перегружают с параметром before - открываем future

      // показываем скрытые сегменты (грузим про запас). Например, при скроллинге в прошлое одновременно запрашивая
      // дополнительные данные с сервера
      if ($scope.segments.length && !$scope.after && !$scope.shownFrom) {
        $scope.segments.map(function (segment) {
          segment.visible = true;
          segment.future = false;
        });
        // загрузить таймлайн с указанной позиции
      } else if (!fromParams.openSegmentDate && toParams.openSegmentDate > 0 && toParams.openSegment) {
        $scope.after = $stateParams.openSegmentDate;
        $scope.shownFrom = $stateParams.openSegmentDate;
        $scope.forceAfter = true;
        $stateParams.openSegmentDate = null;
        $state.reload();
        return;
        // перезагрузка при наличии before (непонятно где используется)
      } else if ($stateParams.before) {
        $stateParams.before = null;
        $state.reload();
        return;
      }

      // Показываем спиннер при нажатии Past, либо при Show/Hide deleted
      // или удалении/востановлении сегментов
      if ($stateParams.before) $scope.pastSpinner = true;

      // отключил анимацию при скроле в прошлое
      // if ($scope.segments.length > 0 && (toParams.before || toParams.after) && !$scope.recalculateAfter) {
      //     // keep position when loading past
      //     if (!anchor)
      //         anchor = $scope.segments[$scope.segments.length - 1];
      //
      //     var id = anchor.id;
      //     setTimeout(function () {
      //         var $el = $('div[data-id="' + id + '"]');
      //         if ($el.length) {
      //             $('html, body').scrollTop($el.offset().top - offsetTop);
      //             setTimeout(function () {
      //                 $('html, body').animate({
      //                     scrollTop: $('div[data-id="' + id + '"]').offset().top - offsetTop - $(window).height() * 0.7
      //                 }, 1000);
      //             }, 200);
      //         }
      //     }, 10);
      // }

      if (_typeof(dataRequest) == 'object' && Object.prototype.hasOwnProperty.call(dataRequest, 'cancel')) {
        dataRequest.cancel();
      }

      // Загрузка данных (+ поправка времени для корректного отображения, если будущее начинается с травел-плана)
      dataRequest = $timelineData.fetch($scope.after);
      dataRequest.then(function (data) {
        console.log('loaded');
        $scope.deleteLoader = false;
        $scope.agents = data.agents || [];
        $scope.sharableAgents = $scope.agents.filter(function (agent) {
          return agent.sharable;
        });
        $scope.canAdd = data.canAdd || false;
        $scope.noForeignFeesCards = data.noForeignFeesCards || [];
        $scope.options = data.options || {};
        overlay.fadeOut();
        if ($scope.containerHeight) $('.trip').css('min-height', $scope.containerHeight);
        $scope.segments = $scope.after ? data.segments : data.segments.concat($scope.segments);
        var now = new Date();
        for (var i = $scope.segments.length; i > 0; i--) {
          var segment = $scope.segments[i - 1];
          segment.future = segment.startDate > Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0) / 1000;
          if (segment.future || $scope.segments[i] && $scope.segments[i].visible && !segment.breakAfter) {
            segment.visible = true;
          }

          // новые подгруженые сегменты не покажутся а будут скрыты до дальнейшего скрола в прошлое
          if ($scope.shownFrom && segment.startDate >= $scope.shownFrom) segment.visible = true;
          if ($state.is('shared') || $state.is('shared-plan') || $state.is('itineraries') || $scope.embeddedData) segment.visible = true;
          if (segment.details && segment.details.monitoredStatus) $scope.segments.filter(function (item) {
            return item.group == segment.group;
          }).forEach(function (item) {
            if (-1 === $.inArray(item.id.substr(0, 2), ['CO', 'L.'])) {
              if (!item.details) item.details = {};
              item.details.monitoredStatus = segment.details.monitoredStatus;
            }
          });
          $scope.segments.forEach(function (item) {
            if ('CI' === item.id.substr(0, 2) && Object.prototype.hasOwnProperty.call($scope.options, 'reservation')) {
              item.setOptions($scope.options.reservation);
            }
          });
        }
        $scope.haveFutureSegments = $filter('filter')($scope.segments, {
          future: true
        }).length > 0;

        // Обновление счетчиков
        if ($state.is('timeline') && !$scope.embeddedData) {
          // Forwarding email
          $scope.forwardingEmail = data.forwardingEmail;
          $scope.mailboxes = {};
          var totals = 0;
          var counts = {};
          angular.forEach($scope.agents, function (agent) {
            $scope.mailboxes[agent.id] = agent.mailboxes;
            counts[agent.id] = agent.count;
            totals = totals + agent.count;

            // if (agent.id == 'my')
            //     $('.user-blk a[data-id]').first().find('.count').text(agent.count);
            // else if (agent.count >= 0)
            //     $('.user-blk a[data-id=' + agent.id + ']').find('.count').text(agent.count);
            //
            // if (agent.count >= 0)
            //     totals = totals + agent.count;
          });

          $(document).trigger('persons.update', counts);
          $('#trips-count').text(counts.my);
        }
        if (!$state.is('itineraries')) {
          $scope.fullName = $sce.trustAsHtml(Translator.trans('timeline.of.name', {
            name: '<b>' + data.fullName + '</b>'
          }));
        } else {
          $scope.fullName = $sce.trustAsHtml(Translator.trans( /** @Desc("Retrieved travel plans") */'retrieved.travelplans'));
        }

        // Чистим состояния
        $scope.spinner = $scope.pastSpinner = $scope.after = $scope.shownSegments = $scope.shownFrom = $scope.forceAfter = $scope.recalculateAfter = undefined;
        $scope.agent.newowner = '';
        $scope.agent.copy = false;

        // Rewrapping
        $timeout(function () {
          $('.wrapper').remove();
          var items = $('.trip-list > div');
          items.each(function (id, item) {
            var prev = $(item).prev();
            if (prev.hasClass('trip-blk')) prev.addBack().wrapAll('<div class="undraggable undroppable wrapper" />');
          });
          $(".ui-sortable").sortable("refresh");
          customizer.initHtml5Inputs('.trip');
        });
      });

      // Подсветка пользователя в левом меню, чей таймлайн загрузили
      if (!$state.is('itineraries')) $(window).trigger('person.activate', $stateParams.agentId || 'my');else {
        var agentId = location.href.match(/agentId=(\d+)/);
        if (agentId && agentId[1] | 0) $(window).trigger('person.activate', agentId[1]);
      }
    });
    $scope.$on('timelineFinishRender', function () {
      var hideTooltips;
      var requestPlanMove = function requestPlanMove(ui, nextSegment) {
        overlay.fadeIn();
        $travelPlans.move({
          planId: angular.element(ui.item).scope().segment.planId,
          nextSegmentId: nextSegment.data('id'),
          nextSegmentTs: angular.element(nextSegment).scope().$parent.segment.startDate,
          type: angular.element(ui.item).scope().segment.type
        }).then(function (resp) {
          $scope.shownFrom = resp.data.startTime;
          $scope.recalculateAfter = true;
          $state.transitionTo($state.current, $stateParams, {
            reload: true,
            inherit: true
          });
        });
      };
      $('.trip-list').sortable({
        cancel: '.undraggable,input',
        axis: "y",
        handle: '.draggable',
        items: '> div:not(.undroppable)',
        revert: true,
        opacity: 0.7,
        start: function start() {
          hideTooltips = true;
        },
        stop: function stop(event, ui) {
          hideTooltips = false;
          var elements = $('.trip-list').find('div[data-id]');
          var uiIndex = elements.index($(ui.item).find('div').first());
          var nextSegment,
            $notesWrap = null;
          if (angular.element(ui.item).scope().segment.type == 'planStart') {
            nextSegment = $(elements[uiIndex + 1]);
            if ($(nextSegment).is('[data-trip-end]')) {
              $notesWrap = $(nextSegment).parent().prev();
            }
          } else {
            nextSegment = $(elements[uiIndex - 1]);
            $notesWrap = $(nextSegment);
          }
          if (null !== $notesWrap && $notesWrap.find('.js-notes-filled').length > 0) {
            var confirmPopup = dialog.fastCreate(Translator.trans('confirmation', {}, 'trips'), Translator.trans('you-sure-also-delete-notes', {}, 'trips'), true, true, [{
              'class': 'btn-silver',
              'text': Translator.trans('button.no'),
              'click': function click() {
                confirmPopup.destroy();
                $('.trip-list').sortable('cancel');
              }
            }, {
              'class': 'btn-blue',
              'text': Translator.trans('button.yes'),
              'click': function click() {
                confirmPopup.destroy();
                requestPlanMove(ui, nextSegment);
              }
            }], 400, 300);
            return;
          }
          requestPlanMove(ui, nextSegment);
        }
      }).on('click', '.details-extproperties-row a[href="#collapse"]', function (e) {
        e.preventDefault();
        var $rowParent = $(this).parent();
        if ($(this).hasClass('properties-value-collapse-name')) {
          $rowParent = $rowParent.parent();
        }
        $rowParent.toggleClass('detail-property-expanded');
      });
      if (/\/print\//.test($location.$$absUrl) && !$scope.spinner) {
        $timeout($window.print, 1000);
        //$window.print();
      }
    });
  }]).controller('flashMessageTripit', ['$scope', function ($scope) {
    dialog.fastCreate(Translator.trans( /** @Desc("Import TripIt Reservations") */'timeline.tripit_popup.title'), Translator.trans( /** @Desc("We did not find any travel reservations in your TripIt account.") */'timeline.tripit_popup.content'), true, true, [{
      'text': Translator.trans('button.close'),
      'click': function click() {
        $(this).dialog('close');
      },
      'class': 'btn-silver'
    }], 500);
  }]);
  $(function () {
    angular.bootstrap('body', ['app']);
  });
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/common/js/alerts.js":
/*!****************************************!*\
  !*** ./web/assets/common/js/alerts.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
$(function () {
  window.jError = function (options) {
    var settings = {
      error: 'error',
      type: 'error',
      content: Translator.trans( /**@Desc("There has been an error on this page. This error was recorded and will be fixed as soon as possible.")*/'alerts.text.error'),
      title: ''
    };
    settings.content += '<img src="/ajax_error.gif?message=error" width="1" height="1">';
    settings = $.extend(settings, options);
    switch (settings.error) {
      case "timeout":
        settings.content = Translator.trans( /**@Desc("It looks like your request has timed out. Please try again. If you get this error again you can try refreshing the page. If you get stuck, feel free to <a href='https://awardwallet.com/contact.php'>contact us</a>.")*/'alerts.text.error.timeout');
        settings.title = Translator.trans( /**@Desc("Operation Timed Out")*/'alerts.title.error.timeout');
        settings.content += '<img src="/ajax_error.gif?message=timeout" width="1" height="1">';
        break;
      case "parsererror":
        settings.content = Translator.trans( /**@Desc("An invalid response was received from the server. Please try again. If you get this error again you can try refreshing the page. If you get stuck, feel free to <a href='https://awardwallet.com/contact.php'>contact us</a>.")*/'alerts.text.error.parsererror');
        settings.title = Translator.trans( /**@Desc("Server Error Occurred")*/'alerts.title.error.parsererror');
        settings.content += '<img src="/ajax_error.gif?message=parsererror" width="1" height="1">';
        break;
      case "abort":
        settings.content = Translator.trans( /**@Desc("Your current request was aborted. If this was not intentional you can try again. If you get stuck, feel free to <a href='https://awardwallet.com/contact.php'>contact us</a>.")*/'alerts.text.error.abort');
        settings.title = Translator.trans( /**@Desc("Operation Aborted")*/'alerts.title.error.abort');
        settings.content += '<img src="/ajax_error.gif?message=abort" width="1" height="1">';
        break;
      case "error":
      default:
        break;
    }
    jAlert(settings);
  };
  window.jAlert = function (options) {
    var settings = {
      type: 'info',
      title: '',
      modal: true,
      width: 400,
      content: '',
      html: $("<div/>"),
      buttons: [{
        text: Translator.trans( /**@Desc("Ok")*/'alerts.btn.ok'),
        click: function click() {
          $(this).dialog('close');
        },
        'class': 'btn-blue'
      }]
    };

    // Check if dialog is open
    if ($('.ui-dialog').is(":visible")) return;
    settings.create = function (e, ui) {
      $(e.target).closest('.ui-dialog').find('.ui-dialog-title').prepend('<i class="icon-' + settings.type + '"></i>');
      $(e.target).prev('.ui-dialog-titlebar').addClass('alert-' + settings.type + '-header');
      $(e.target).next('.ui-dialog-buttonpane').addClass('alert-' + settings.type + '-bottom');
    };
    if (options.content) {
      settings = $.extend(settings, options);
    } else {
      settings.content = options;
    }
    if (settings.title == '') {
      if (settings.type === 'info') settings.title = Translator.trans( /**@Desc("Information")*/'alerts.info');else if (settings.type === 'error') settings.title = Translator.trans( /**@Desc("Error")*/'alerts.error');else if (settings.type === 'success') settings.title = Translator.trans( /**@Desc("Success")*/'alerts.success');else if (settings.type === 'warning') settings.title = Translator.trans( /**@Desc("Warning")*/'alerts.warning');else settings.title = Translator.trans( /**@Desc("Error")*/'alerts.error');
    }
    var el = settings.html;
    el.addClass('alert-' + settings.type).html(settings.content);
    $("body").append(el);
    $(el).dialog(settings);
    return el;
  };
  window.jConfirm = function (question, callback) {
    return jAlert({
      content: question,
      title: Translator.trans( /**@Desc("Please confirm")*/'alerts.text.confirm'),
      buttons: [{
        text: Translator.trans( /**@Desc("Cancel")*/'alerts.btn.cancel'),
        click: function click() {
          $(this).dialog('close');
          return false;
        },
        'class': 'btn-silver'
      }, {
        text: Translator.trans( /**@Desc("Ok")*/'alerts.btn.ok'),
        click: function click() {
          $(this).dialog('close');
          callback();
        },
        'class': 'btn-blue'
      }]
    });
  };
  window.jPrompt = function (question, callback) {
    var el = $("<input/>").css('width', '100%');
    return jAlert({
      title: question,
      content: el,
      buttons: [{
        text: Translator.trans( /**@Desc("Ok")*/'alerts.btn.ok'),
        click: function click() {
          $(this).dialog('close');
          callback($(el).val());
        },
        'class': 'btn-blue'
      }]
    });
  };
  window.jAjaxErrorHandler = function (jqXHR, textStatus, errorThrown) {
    if (typeof $.browser != 'undefined' && $.browser.webkit && textStatus === 'error' && !jqXHR.getAllResponseHeaders()) textStatus = 'abort'; // chrome throw "error" on user refresh
    /* "success", "notmodified", "error", "timeout", "abort", or "parsererror" */
    //        if ($.inArray(textStatus, ["timeout", "abort", "parsererror"]) >= 0) return;
    if (textStatus === "abort") return;
    if (jqXHR.responseText === 'unauthorized') {
      try {
        if (window.parent != window) {
          parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
          return;
        }
        // eslint-disable-next-line no-empty
      } catch (e) {}
      location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
      return;
    }
    var options = {
      error: textStatus
    };
    if (typeof window.debugMode != 'undefined' && window.debugMode) options.content = '[ajax error: ' + jqXHR.status + ' ' + textStatus + ']\n\n' + jqXHR.responseText;
    window.jError(options);
  };
});

/***/ }),

/***/ "./node_modules/core-js/internals/is-integral-number.js":
/*!**************************************************************!*\
  !*** ./node_modules/core-js/internals/is-integral-number.js ***!
  \**************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/core-js/internals/is-object.js");

var floor = Math.floor;

// `IsIntegralNumber` abstract operation
// https://tc39.es/ecma262/#sec-isintegralnumber
// eslint-disable-next-line es/no-number-isinteger -- safe
module.exports = Number.isInteger || function isInteger(it) {
  return !isObject(it) && isFinite(it) && floor(it) === it;
};


/***/ }),

/***/ "./node_modules/core-js/modules/es.number.is-integer.js":
/*!**************************************************************!*\
  !*** ./node_modules/core-js/modules/es.number.is-integer.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var isIntegralNumber = __webpack_require__(/*! ../internals/is-integral-number */ "./node_modules/core-js/internals/is-integral-number.js");

// `Number.isInteger` method
// https://tc39.es/ecma262/#sec-number.isinteger
$({ target: 'Number', stat: true }, {
  isInteger: isIntegralNumber
});


/***/ }),

/***/ "./assets/less-deprecated/main.less":
/*!******************************************!*\
  !*** ./assets/less-deprecated/main.less ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js","vendors-node_modules_core-js_internals_array-slice-simple_js-node_modules_core-js_internals_f-4bac30","vendors-node_modules_core-js_modules_es_string_replace_js","vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444","vendors-node_modules_core-js_internals_array-method-is-strict_js-node_modules_core-js_modules-ea2489","vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41","vendors-node_modules_core-js_modules_es_promise_js-node_modules_core-js_modules_web_dom-colle-054322","vendors-node_modules_core-js_internals_string-trim_js-node_modules_core-js_internals_this-num-d4bf43","vendors-node_modules_core-js_modules_es_json_to-string-tag_js-node_modules_core-js_modules_es-dd246b","vendors-node_modules_core-js_modules_es_array_from_js-node_modules_core-js_modules_es_date_to-6fede4","vendors-node_modules_axios-hooks_es_index_js-node_modules_classnames_index_js-node_modules_is-e8b457","vendors-node_modules_core-js_modules_es_number_to-fixed_js-node_modules_intl_index_js","vendors-node_modules_prop-types_index_js","vendors-node_modules_ckeditor_ckeditor5-react_dist_ckeditor_js-node_modules_core-js_modules_e-eae954","assets_bem_ts_service_translator_ts","web_assets_common_vendors_jquery_dist_jquery_js","web_assets_common_vendors_jquery-ui_jquery-ui_min_js","assets_bem_ts_service_router_ts","web_assets_common_fonts_webfonts_open-sans_css-web_assets_common_fonts_webfonts_roboto_css","assets_less-deprecated_main_less","web_assets_awardwalletnewdesign_js_lib_dialog_js","assets_bem_ts_service_axios_index_js-assets_bem_ts_service_env_ts","web_assets_common_vendors_date-time-diff_lib_date-time-diff_js","web_assets_awardwalletnewdesign_js_directives_autoFocus_js-web_assets_awardwalletnewdesign_js-b24d40","assets_js-deprecated_component-deprecated_timeline_Notes_js"], () => (__webpack_exec__("./assets/entry-point-deprecated/timeline/index.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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cGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS90cy9zaGltL25nUmVhY3QuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvZW50cnktcG9pbnQtZGVwcmVjYXRlZC9tYWluLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2VudHJ5LXBvaW50LWRlcHJlY2F0ZWQvdGltZWxpbmUvaW5kZXguanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL2RpcmVjdGl2ZXMvZXh0ZW5kZWREaWFsb2cuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL3BhZ2VzL21haWxib3gvYWRkLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9hd2FyZHdhbGxldG5ld2Rlc2lnbi9qcy9wYWdlcy9tYWlsYm94L3JlcXVlc3QuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL3BhZ2VzL3RpbWVsaW5lL2RpcmVjdGl2ZXMuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL3BhZ2VzL3RpbWVsaW5lL2ZpbHRlcnMuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL3BhZ2VzL3RpbWVsaW5lL21haW4uanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL3BhZ2VzL3RpbWVsaW5lL3NlcnZpY2VzLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9hd2FyZHdhbGxldG5ld2Rlc2lnbi9qcy9wYWdlcy90aW1lbGluZS90aW1lbGluZS5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL3dlYi9hc3NldHMvY29tbW9uL2pzL2FsZXJ0cy5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL2ludGVybmFscy9pcy1pbnRlZ3JhbC1udW1iZXIuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9tb2R1bGVzL2VzLm51bWJlci5pcy1pbnRlZ2VyLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2xlc3MtZGVwcmVjYXRlZC9tYWluLmxlc3M/MTk4NSJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQge2NyZWF0ZUVsZW1lbnR9IGZyb20gJ3JlYWN0JztcbmltcG9ydCB7cmVuZGVyLCB1bm1vdW50Q29tcG9uZW50QXROb2RlfSBmcm9tICdyZWFjdC1kb20nO1xuaW1wb3J0IGFuZ3VsYXIgZnJvbSAnLi9hbmd1bGFyLWJvb3QnO1xuXG4vLyB3cmFwcyBhIGZ1bmN0aW9uIHdpdGggc2NvcGUuJGFwcGx5LCBpZiBhbHJlYWR5IGFwcGxpZWQganVzdCByZXR1cm5cbmZ1bmN0aW9uIGFwcGxpZWQoZm4sIHNjb3BlKSB7XG4gIGlmIChmbi53cmFwcGVkSW5BcHBseSkge1xuICAgIHJldHVybiBmbjtcbiAgfVxuICB2YXIgd3JhcHBlZCA9IGZ1bmN0aW9uKCkge1xuICAgIHZhciBhcmdzID0gYXJndW1lbnRzO1xuICAgIHZhciBwaGFzZSA9IHNjb3BlLiRyb290LiQkcGhhc2U7XG4gICAgaWYgKHBoYXNlID09PSBcIiRhcHBseVwiIHx8IHBoYXNlID09PSBcIiRkaWdlc3RcIikge1xuICAgICAgcmV0dXJuIGZuLmFwcGx5KG51bGwsIGFyZ3MpO1xuICAgIH0gZWxzZSB7XG4gICAgICByZXR1cm4gc2NvcGUuJGFwcGx5KGZ1bmN0aW9uKCkge1xuICAgICAgICByZXR1cm4gZm4uYXBwbHkoIG51bGwsIGFyZ3MgKTtcbiAgICAgIH0pO1xuICAgIH1cbiAgfTtcbiAgd3JhcHBlZC53cmFwcGVkSW5BcHBseSA9IHRydWU7XG4gIHJldHVybiB3cmFwcGVkO1xufVxuXG4vKipcbiAqIHdyYXBzIGZ1bmN0aW9ucyBvbiBvYmogaW4gc2NvcGUuJGFwcGx5XG4gKlxuICoga2VlcHMgYmFja3dhcmRzIGNvbXBhdGliaWxpdHksIGFzIGlmIHByb3BzQ29uZmlnIGlzIG5vdCBwYXNzZWQsIGl0IHdpbGxcbiAqIHdvcmsgYXMgYmVmb3JlLCB3cmFwcGluZyBhbGwgZnVuY3Rpb25zIGFuZCB3b24ndCB3cmFwIG9ubHkgd2hlbiBzcGVjaWZpZWQuXG4gKlxuICogQHZlcnNpb24gMC40LjFcbiAqIEBwYXJhbSBvYmogcmVhY3QgY29tcG9uZW50IHByb3BzXG4gKiBAcGFyYW0gc2NvcGUgY3VycmVudCBzY29wZVxuICogQHBhcmFtIHByb3BzQ29uZmlnIGNvbmZpZ3VyYXRpb24gb2JqZWN0IGZvciBhbGwgcHJvcGVydGllc1xuICogQHJldHVybnMge09iamVjdH0gcHJvcHMgd2l0aCB0aGUgZnVuY3Rpb25zIHdyYXBwZWQgaW4gc2NvcGUuJGFwcGx5XG4gKi9cbmZ1bmN0aW9uIGFwcGx5RnVuY3Rpb25zKG9iaiwgc2NvcGUsIHByb3BzQ29uZmlnKSB7XG4gIHJldHVybiBPYmplY3Qua2V5cyhvYmogfHwge30pLnJlZHVjZShmdW5jdGlvbihwcmV2LCBrZXkpIHtcbiAgICB2YXIgdmFsdWUgPSBvYmpba2V5XTtcbiAgICB2YXIgY29uZmlnID0gKHByb3BzQ29uZmlnIHx8IHt9KVtrZXldIHx8IHt9O1xuICAgIC8qKlxuICAgICAqIHdyYXAgZnVuY3Rpb25zIGluIGEgZnVuY3Rpb24gdGhhdCBlbnN1cmVzIHRoZXkgYXJlIHNjb3BlLiRhcHBsaWVkXG4gICAgICogZW5zdXJlcyB0aGF0IHdoZW4gZnVuY3Rpb24gaXMgY2FsbGVkIGZyb20gYSBSZWFjdCBjb21wb25lbnRcbiAgICAgKiB0aGUgQW5ndWxhciBkaWdlc3QgY3ljbGUgaXMgcnVuXG4gICAgICovXG4gICAgcHJldltrZXldID0gYW5ndWxhci5pc0Z1bmN0aW9uKHZhbHVlKSAmJiBjb25maWcud3JhcEFwcGx5ICE9PSBmYWxzZVxuICAgICAgICA/IGFwcGxpZWQodmFsdWUsIHNjb3BlKVxuICAgICAgICA6IHZhbHVlO1xuXG4gICAgcmV0dXJuIHByZXY7XG4gIH0sIHt9KTtcbn1cblxuLyoqXG4gKlxuICogQHBhcmFtIHdhdGNoRGVwdGggKHZhbHVlIG9mIEhUTUwgd2F0Y2gtZGVwdGggYXR0cmlidXRlKVxuICogQHBhcmFtIHNjb3BlIChhbmd1bGFyIHNjb3BlKVxuICpcbiAqIFVzZXMgdGhlIHdhdGNoRGVwdGggYXR0cmlidXRlIHRvIGRldGVybWluZSBob3cgdG8gd2F0Y2ggcHJvcHMgb24gc2NvcGUuXG4gKiBJZiB3YXRjaERlcHRoIGF0dHJpYnV0ZSBpcyBOT1QgcmVmZXJlbmNlIG9yIGNvbGxlY3Rpb24sIHdhdGNoRGVwdGggZGVmYXVsdHMgdG8gZGVlcCB3YXRjaGluZyBieSB2YWx1ZVxuICovXG5mdW5jdGlvbiB3YXRjaFByb3BzICh3YXRjaERlcHRoLCBzY29wZSwgd2F0Y2hFeHByZXNzaW9ucywgbGlzdGVuZXIpe1xuICB2YXIgc3VwcG9ydHNXYXRjaENvbGxlY3Rpb24gPSBhbmd1bGFyLmlzRnVuY3Rpb24oc2NvcGUuJHdhdGNoQ29sbGVjdGlvbik7XG4gIHZhciBzdXBwb3J0c1dhdGNoR3JvdXAgPSBhbmd1bGFyLmlzRnVuY3Rpb24oc2NvcGUuJHdhdGNoR3JvdXApO1xuXG4gIHZhciB3YXRjaEdyb3VwRXhwcmVzc2lvbnMgPSBbXTtcbiAgd2F0Y2hFeHByZXNzaW9ucy5mb3JFYWNoKGZ1bmN0aW9uKGV4cHIpe1xuICAgIHZhciBhY3R1YWxFeHByID0gZ2V0UHJvcEV4cHJlc3Npb24oZXhwcik7XG4gICAgdmFyIGV4cHJXYXRjaERlcHRoID0gZ2V0UHJvcFdhdGNoRGVwdGgod2F0Y2hEZXB0aCwgZXhwcik7XG5cbiAgICBpZiAoZXhwcldhdGNoRGVwdGggPT09ICdjb2xsZWN0aW9uJyAmJiBzdXBwb3J0c1dhdGNoQ29sbGVjdGlvbikge1xuICAgICAgc2NvcGUuJHdhdGNoQ29sbGVjdGlvbihhY3R1YWxFeHByLCBsaXN0ZW5lcik7XG4gICAgfSBlbHNlIGlmIChleHByV2F0Y2hEZXB0aCA9PT0gJ3JlZmVyZW5jZScgJiYgc3VwcG9ydHNXYXRjaEdyb3VwKSB7XG4gICAgICB3YXRjaEdyb3VwRXhwcmVzc2lvbnMucHVzaChhY3R1YWxFeHByKTtcbiAgICB9IGVsc2Uge1xuICAgICAgc2NvcGUuJHdhdGNoKGFjdHVhbEV4cHIsIGxpc3RlbmVyLCAoZXhwcldhdGNoRGVwdGggIT09ICdyZWZlcmVuY2UnKSk7XG4gICAgfVxuICB9KTtcblxuICBpZiAod2F0Y2hHcm91cEV4cHJlc3Npb25zLmxlbmd0aCkge1xuICAgIHNjb3BlLiR3YXRjaEdyb3VwKHdhdGNoR3JvdXBFeHByZXNzaW9ucywgbGlzdGVuZXIpO1xuICB9XG59XG5cbi8vIHJlbmRlciBSZWFjdCBjb21wb25lbnQsIHdpdGggc2NvcGVbYXR0cnMucHJvcHNdIGJlaW5nIHBhc3NlZCBpbiBhcyB0aGUgY29tcG9uZW50IHByb3BzXG5mdW5jdGlvbiByZW5kZXJDb21wb25lbnQoY29tcG9uZW50LCBwcm9wcywgc2NvcGUsIGVsZW0pIHtcbiAgc2NvcGUuJGV2YWxBc3luYyhmdW5jdGlvbigpIHtcbiAgICByZW5kZXIoY3JlYXRlRWxlbWVudChjb21wb25lbnQsIHByb3BzKSwgZWxlbVswXSk7XG4gIH0pO1xufVxuXG4vLyBnZXQgcHJvcCBleHByZXNzaW9uIGZyb20gcHJvcCAoc3RyaW5nIG9yIGFycmF5KVxuZnVuY3Rpb24gZ2V0UHJvcEV4cHJlc3Npb24ocHJvcCkge1xuICByZXR1cm4gKEFycmF5LmlzQXJyYXkocHJvcCkpID8gcHJvcFswXSA6IHByb3A7XG59XG5cbi8vIGdldCB3YXRjaCBkZXB0aCBvZiBwcm9wIChzdHJpbmcgb3IgYXJyYXkpXG5mdW5jdGlvbiBnZXRQcm9wV2F0Y2hEZXB0aChkZWZhdWx0V2F0Y2gsIHByb3ApIHtcbiAgdmFyIGN1c3RvbVdhdGNoRGVwdGggPSAoXG4gICAgICBBcnJheS5pc0FycmF5KHByb3ApICYmXG4gICAgICBhbmd1bGFyLmlzT2JqZWN0KHByb3BbMV0pICYmXG4gICAgICBwcm9wWzFdLndhdGNoRGVwdGhcbiAgKTtcbiAgcmV0dXJuIGN1c3RvbVdhdGNoRGVwdGggfHwgZGVmYXVsdFdhdGNoO1xufVxuXG4vLyBnZXQgcHJvcCBuYW1lIGZyb20gcHJvcCAoc3RyaW5nIG9yIGFycmF5KVxuZnVuY3Rpb24gZ2V0UHJvcE5hbWUocHJvcCkge1xuICByZXR1cm4gKEFycmF5LmlzQXJyYXkocHJvcCkpID8gcHJvcFswXSA6IHByb3A7XG59XG5cbi8vIGZpbmQgdGhlIG5vcm1hbGl6ZWQgYXR0cmlidXRlIGtub3dpbmcgdGhhdCBSZWFjdCBwcm9wcyBhY2NlcHQgYW55IHR5cGUgb2YgY2FwaXRhbGl6YXRpb25cbmZ1bmN0aW9uIGZpbmRBdHRyaWJ1dGUoYXR0cnMsIHByb3BOYW1lKSB7XG4gIHZhciBpbmRleCA9IE9iamVjdC5rZXlzKGF0dHJzKS5maWx0ZXIoZnVuY3Rpb24gKGF0dHIpIHtcbiAgICByZXR1cm4gYXR0ci50b0xvd2VyQ2FzZSgpID09PSBwcm9wTmFtZS50b0xvd2VyQ2FzZSgpO1xuICB9KVswXTtcbiAgcmV0dXJuIGF0dHJzW2luZGV4XTtcbn1cblxuLy8gZ2V0IHByb3AgbmFtZSBmcm9tIHByb3AgKHN0cmluZyBvciBhcnJheSlcbmZ1bmN0aW9uIGdldFByb3BDb25maWcocHJvcCkge1xuICByZXR1cm4gKEFycmF5LmlzQXJyYXkocHJvcCkpID8gcHJvcFsxXSA6IHt9O1xufVxuXG52YXIgcmVhY3REaXJlY3RpdmUgPSBmdW5jdGlvbigkaW5qZWN0b3IpIHtcbiAgcmV0dXJuIGZ1bmN0aW9uKHJlYWN0Q29tcG9uZW50LCBzdGF0aWNQcm9wcywgY29uZiwgaW5qZWN0YWJsZVByb3BzKSB7XG4gICAgY29uc3QgZGlyZWN0aXZlID0ge1xuICAgICAgcmVzdHJpY3Q6ICdFQScsXG4gICAgICByZXBsYWNlOiB0cnVlLFxuICAgICAgbGluazogZnVuY3Rpb24oc2NvcGUsIGVsZW0sIGF0dHJzKSB7XG4gICAgICAgIC8vIGlmIHByb3BzIGlzIG5vdCBkZWZpbmVkLCBmYWxsIGJhY2sgdG8gdXNlIHRoZSBSZWFjdCBjb21wb25lbnQncyBwcm9wVHlwZXMgaWYgcHJlc2VudFxuICAgICAgICBsZXQgcHJvcHMgPSBzdGF0aWNQcm9wcyB8fCBPYmplY3Qua2V5cyhyZWFjdENvbXBvbmVudC5wcm9wVHlwZXMgfHwge30pO1xuICAgICAgICBpZiAoIXByb3BzLmxlbmd0aCkge1xuICAgICAgICAgIGNvbnN0IG5nQXR0ck5hbWVzID0gW107XG4gICAgICAgICAgY29uc3QgZGlyZWN0aXZlTmFtZSA9IHJlYWN0Q29tcG9uZW50Lm5hbWUudG9Mb3dlckNhc2UoKTtcbiAgICAgICAgICBhbmd1bGFyLmZvckVhY2goYXR0cnMuJGF0dHIsIGZ1bmN0aW9uICh2YWx1ZSwga2V5KSB7XG4gICAgICAgICAgICBpZiAoa2V5LnRvTG93ZXJDYXNlKCkgIT09IGRpcmVjdGl2ZU5hbWUpIHtcbiAgICAgICAgICAgICAgbmdBdHRyTmFtZXMucHVzaChrZXkpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH0pO1xuICAgICAgICAgIHByb3BzID0gbmdBdHRyTmFtZXM7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBmb3IgZWFjaCBvZiB0aGUgcHJvcGVydGllcywgZ2V0IHRoZWlyIHNjb3BlIHZhbHVlIGFuZCBzZXQgaXQgdG8gc2NvcGUucHJvcHNcbiAgICAgICAgY29uc3QgcmVuZGVyTXlDb21wb25lbnQgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgICBsZXQgc2NvcGVQcm9wcyA9IHt9LCBjb25maWcgPSB7fTtcbiAgICAgICAgICBwcm9wcy5mb3JFYWNoKGZ1bmN0aW9uKHByb3ApIHtcbiAgICAgICAgICAgIHZhciBwcm9wTmFtZSA9IGdldFByb3BOYW1lKHByb3ApO1xuICAgICAgICAgICAgc2NvcGVQcm9wc1twcm9wTmFtZV0gPSBzY29wZS4kZXZhbChmaW5kQXR0cmlidXRlKGF0dHJzLCBwcm9wTmFtZSkpO1xuICAgICAgICAgICAgY29uZmlnW3Byb3BOYW1lXSA9IGdldFByb3BDb25maWcocHJvcCk7XG4gICAgICAgICAgfSk7XG4gICAgICAgICAgc2NvcGVQcm9wcyA9IGFwcGx5RnVuY3Rpb25zKHNjb3BlUHJvcHMsIHNjb3BlLCBjb25maWcpO1xuICAgICAgICAgIHNjb3BlUHJvcHMgPSBhbmd1bGFyLmV4dGVuZCh7fSwgc2NvcGVQcm9wcywgaW5qZWN0YWJsZVByb3BzKTtcbiAgICAgICAgICByZW5kZXJDb21wb25lbnQocmVhY3RDb21wb25lbnQsIHNjb3BlUHJvcHMsIHNjb3BlLCBlbGVtKTtcbiAgICAgICAgfTtcblxuICAgICAgICAvLyB3YXRjaCBlYWNoIHByb3BlcnR5IG5hbWUgYW5kIHRyaWdnZXIgYW4gdXBkYXRlIHdoZW5ldmVyIHNvbWV0aGluZyBjaGFuZ2VzLFxuICAgICAgICAvLyB0byB1cGRhdGUgc2NvcGUucHJvcHMgd2l0aCBuZXcgdmFsdWVzXG4gICAgICAgIGNvbnN0IHByb3BFeHByZXNzaW9ucyA9IHByb3BzLm1hcChmdW5jdGlvbihwcm9wKXtcbiAgICAgICAgICByZXR1cm4gKEFycmF5LmlzQXJyYXkocHJvcCkpID9cbiAgICAgICAgICAgICAgW2F0dHJzW2dldFByb3BOYW1lKHByb3ApXSwgZ2V0UHJvcENvbmZpZyhwcm9wKV0gOlxuICAgICAgICAgICAgICBhdHRyc1twcm9wXTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgd2F0Y2hQcm9wcyhhdHRycy53YXRjaERlcHRoLCBzY29wZSwgcHJvcEV4cHJlc3Npb25zLCByZW5kZXJNeUNvbXBvbmVudCk7XG5cbiAgICAgICAgcmVuZGVyTXlDb21wb25lbnQoKTtcblxuICAgICAgICAvLyBjbGVhbnVwIHdoZW4gc2NvcGUgaXMgZGVzdHJveWVkXG4gICAgICAgIHNjb3BlLiRvbignJGRlc3Ryb3knLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICBpZiAoIWF0dHJzLm9uU2NvcGVEZXN0cm95KSB7XG4gICAgICAgICAgICB1bm1vdW50Q29tcG9uZW50QXROb2RlKGVsZW1bMF0pO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBzY29wZS4kZXZhbChhdHRycy5vblNjb3BlRGVzdHJveSwge1xuICAgICAgICAgICAgICB1bm1vdW50Q29tcG9uZW50OiB1bm1vdW50Q29tcG9uZW50QXROb2RlLmJpbmQodGhpcywgZWxlbVswXSlcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICB9XG4gICAgfTtcbiAgICByZXR1cm4gYW5ndWxhci5leHRlbmQoZGlyZWN0aXZlLCBjb25mKTtcbiAgfTtcbn07XG5cbmFuZ3VsYXJcbiAgICAubW9kdWxlKCdyZWFjdCcsIFtdKVxuICAgIC5mYWN0b3J5KCdyZWFjdERpcmVjdGl2ZScsIFsnJGluamVjdG9yJywgcmVhY3REaXJlY3RpdmVdKTsiLCJpbXBvcnQgJy4uL2xlc3MtZGVwcmVjYXRlZC9tYWluLmxlc3MnO1xuLyplc2xpbnQgbm8tdW51c2VkLXZhcnM6IFwianF1ZXJ5dWlcIiovXG5pbXBvcnQganF1ZXJ5dWkgZnJvbSAnanF1ZXJ5dWknOyAvLyAubWVudSgpXG5cbihmdW5jdGlvbiBtYWluKCkge1xuICAgIHRvZ2dsZVNpZGViYXJWaXNpYmxlKCk7XG4gICAgaW5pdERyb3Bkb3ducygkKCdib2R5JykpO1xufSkoKTtcblxuZnVuY3Rpb24gdG9nZ2xlU2lkZWJhclZpc2libGUoKSB7XG4gICAgJCh3aW5kb3cpLnJlc2l6ZShmdW5jdGlvbigpIHtcbiAgICAgICAgbGV0IHNpemVXaW5kb3cgPSAkKCdib2R5Jykud2lkdGgoKTtcbiAgICAgICAgaWYgKHNpemVXaW5kb3cgPCAxMDI0KSB7XG4gICAgICAgICAgICAkKCcubWFpbi1ib2R5JykuYWRkQ2xhc3MoJ3NtYWxsLWRlc2t0b3AnKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICQoJy5tYWluLWJvZHknKS5yZW1vdmVDbGFzcygnc21hbGwtZGVza3RvcCcpO1xuICAgICAgICB9XG4gICAgICAgIGlmICgkKCcubWFpbi1ib2R5JykuaGFzQ2xhc3MoJ21hbnVhbC1oaWRkZW4nKSkgcmV0dXJuO1xuICAgICAgICBpZiAoc2l6ZVdpbmRvdyA8IDEwMjQpIHtcbiAgICAgICAgICAgICQoJy5tYWluLWJvZHknKS5hZGRDbGFzcygnaGlkZS1tZW51Jyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAkKCcubWFpbi1ib2R5JykucmVtb3ZlQ2xhc3MoJ2hpZGUtbWVudScpO1xuICAgICAgICB9XG4gICAgfSk7XG5cbiAgICBjb25zdCBtZW51Q2xvc2UgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcubWVudS1jbG9zZScpO1xuICAgIGlmIChtZW51Q2xvc2UpIHtcbiAgICAgICAgY29uc3QgbWVudUJvZHkgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcubWFpbi1ib2R5Jyk7XG4gICAgICAgIG1lbnVDbG9zZS5vbmNsaWNrID0gKCkgPT4ge1xuICAgICAgICAgICAgbWVudUJvZHkuY2xhc3NMaXN0LnRvZ2dsZSgnaGlkZS1tZW51Jyk7XG4gICAgICAgICAgICBtZW51Qm9keS5jbGFzc0xpc3QuYWRkKCdtYW51YWwtaGlkZGVuJyk7XG4gICAgICAgIH07XG4gICAgfVxufVxuXG5mdW5jdGlvbiBpbml0RHJvcGRvd25zKGFyZWEsIG9wdGlvbnMpIHtcbiAgICBvcHRpb25zID0gb3B0aW9ucyB8fCB7fTtcbiAgICBjb25zdCBzZWxlY3RvciA9ICdbZGF0YS1yb2xlPVwiZHJvcGRvd25cIl0nO1xuICAgIGNvbnN0IGRyb3Bkb3duID0gdW5kZWZpbmVkICE9IGFyZWFcbiAgICAgICAgPyAkKGFyZWEpLmZpbmQoc2VsZWN0b3IpLmFkZEJhY2soc2VsZWN0b3IpXG4gICAgICAgIDogJChzZWxlY3RvcilcbiAgICBjb25zdCBvZlBhcmVudFNlbGVjdG9yID0gb3B0aW9ucy5vZlBhcmVudCB8fCAnbGknO1xuXG4gICAgZHJvcGRvd24uZWFjaChmdW5jdGlvbihpZCwgZWwpIHtcbiAgICAgICAgJChlbClcbiAgICAgICAgICAgIC5yZW1vdmVBdHRyKCdkYXRhLXJvbGUnKVxuICAgICAgICAgICAgLm1lbnUoKVxuICAgICAgICAgICAgLmhpZGUoKVxuICAgICAgICAgICAgLm9uKCdtZW51LmhpZGUnLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICAgICAgJChlLnRhcmdldCkuaGlkZSgyMDApO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICQoJ1tkYXRhLXRhcmdldD0nICsgJChlbCkuZGF0YSgnaWQnKSArICddJykub24oJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgZS5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgICAgICAgICQoJy51aS1tZW51OnZpc2libGUnKS5ub3QoJ1tkYXRhLWlkPVwiJyArICQodGhpcykuZGF0YSgndGFyZ2V0JykgKyAnXCJdJykudHJpZ2dlcignbWVudS5oaWRlJyk7XG4gICAgICAgICAgICAkKGVsKS50b2dnbGUoMCwgZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgJChlbCkucG9zaXRpb24oe1xuICAgICAgICAgICAgICAgICAgICBteTogb3B0aW9ucz8ucG9zaXRpb24/Lm15IHx8ICdsZWZ0IHRvcCcsXG4gICAgICAgICAgICAgICAgICAgIGF0OiBcImxlZnQgYm90dG9tXCIsXG4gICAgICAgICAgICAgICAgICAgIG9mOiAkKGUudGFyZ2V0KS5wYXJlbnRzKG9mUGFyZW50U2VsZWN0b3IpLmZpbmQoJy5yZWwtdGhpcycpLFxuICAgICAgICAgICAgICAgICAgICBjb2xsaXNpb246IFwiZml0XCJcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9KTtcbiAgICB9KTtcbiAgICAkKGRvY3VtZW50KS5vbignY2xpY2snLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICQoJy51aS1tZW51OnZpc2libGUnKS50cmlnZ2VyKCdtZW51LmhpZGUnKTtcbiAgICB9KTtcbn07XG5cbmZ1bmN0aW9uIGF1dG9Db21wbGV0ZVJlbmRlckl0ZW0ocmVuZGVyRnVuY3Rpb24gPSBudWxsKSB7XG4gICAgaWYgKG51bGwgPT09IHJlbmRlckZ1bmN0aW9uKSB7XG4gICAgICAgIHJlbmRlckZ1bmN0aW9uID0gZnVuY3Rpb24odWwsIGl0ZW0pIHtcbiAgICAgICAgICAgIGNvbnN0IHJlZ2V4ID0gbmV3IFJlZ0V4cCgnKCcgKyB0aGlzLmVsZW1lbnQudmFsKCkucmVwbGFjZSgvW15BLVphLXowLTnQkC3Qr9CwLdGPXSsvZywgJycpICsgJyknLCAnZ2knKSxcbiAgICAgICAgICAgICAgICBodG1sID0gJCgnPGRpdi8+JykudGV4dChpdGVtLmxhYmVsKS5odG1sKCkucmVwbGFjZShyZWdleCwgJzxiPiQxPC9iPicpO1xuICAgICAgICAgICAgcmV0dXJuICQoJzxsaT48L2xpPicpXG4gICAgICAgICAgICAgICAgLmRhdGEoJ2l0ZW0uYXV0b2NvbXBsZXRlJywgaXRlbSlcbiAgICAgICAgICAgICAgICAuYXBwZW5kKCQoJzxhPjwvYT4nKS5odG1sKGh0bWwpKVxuICAgICAgICAgICAgICAgIC5hcHBlbmRUbyh1bCk7XG4gICAgICAgIH07XG4gICAgfVxuXG4gICAgJC51aS5hdXRvY29tcGxldGUucHJvdG90eXBlLl9yZW5kZXJJdGVtID0gcmVuZGVyRnVuY3Rpb247XG59XG5cbmV4cG9ydCBkZWZhdWx0IHsgaW5pdERyb3Bkb3ducywgYXV0b0NvbXBsZXRlUmVuZGVySXRlbSB9OyIsImltcG9ydCAnLi4vbWFpbic7XG5pbXBvcnQgJ3BhZ2VzL3RpbWVsaW5lL3RpbWVsaW5lJztcbiIsImRlZmluZShbJ2FuZ3VsYXInLCAnanF1ZXJ5LWJvb3QnLCAnanF1ZXJ5dWknXSwgZnVuY3Rpb24gKGFuZ3VsYXIsICQpIHtcbiAgICBhbmd1bGFyID0gYW5ndWxhciAmJiBhbmd1bGFyLl9fZXNNb2R1bGUgPyBhbmd1bGFyLmRlZmF1bHQgOiBhbmd1bGFyO1xuXG4gICAgYW5ndWxhci5tb2R1bGUoJ2V4dGVuZGVkRGlhbG9nJywgW10pXG4gICAgICAgIC5kaXJlY3RpdmUoJ2RpYWxvZ0hlYWRlcicsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgcmVzdHJpY3Q6ICdBRScsXG4gICAgICAgICAgICAgICAgdGVtcGxhdGU6ICc8c3BhbiBjbGFzcz1cInVpLWRpYWxvZy10aXRsZVwiIG5nLXRyYW5zY2x1ZGU+PC9zcGFuPicsXG4gICAgICAgICAgICAgICAgdHJhbnNjbHVkZTogdHJ1ZSxcbiAgICAgICAgICAgICAgICByZXBsYWNlOiB0cnVlLFxuICAgICAgICAgICAgICAgIGxpbms6IGZ1bmN0aW9uIChzY29wZSwgZWxlbWVudCkge1xuICAgICAgICAgICAgICAgICAgICBzY29wZS4kb24oJ3JlbmRlclBhcnRzJywgZnVuY3Rpb24gKGV2ZW50LCBlbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgZWwuZmluZCgnLnVpLWRpYWxvZy10aXRsZWJhciAudWktZGlhbG9nLXRpdGxlJykucmVwbGFjZVdpdGgoZWxlbWVudCk7XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSlcbiAgICAgICAgLmRpcmVjdGl2ZSgnZGlhbG9nRm9vdGVyJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICByZXN0cmljdDogJ0FFJyxcbiAgICAgICAgICAgICAgICB0ZW1wbGF0ZTogJzxkaXYgY2xhc3M9XCJ1aS1kaWFsb2ctYnV0dG9ucGFuZSB1aS13aWRnZXQtY29udGVudCB1aS1oZWxwZXItY2xlYXJmaXhcIj48ZGl2IGNsYXNzPVwidWktZGlhbG9nLWJ1dHRvbnNldFwiIG5nLXRyYW5zY2x1ZGU+PC9kaXY+PC9kaXY+JyxcbiAgICAgICAgICAgICAgICB0cmFuc2NsdWRlOiB0cnVlLFxuICAgICAgICAgICAgICAgIGxpbms6IGZ1bmN0aW9uIChzY29wZSwgZWxlbWVudCkge1xuICAgICAgICAgICAgICAgICAgICBzY29wZS4kb24oJ3JlbmRlclBhcnRzJywgZnVuY3Rpb24gKGV2ZW50LCBlbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgZWxlbWVudC5hcHBlbmRUbyhlbCk7XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgICAgIHNjb3BlLmRpYWxvZ0Nsb3NlID0gZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgc2NvcGUuJGVtaXQoJ2RpYWxvZ0Nsb3NlJyk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pXG4gICAgICAgIC5kaXJlY3RpdmUoJ2V4dERpYWxvZycsIFsnJHRpbWVvdXQnLCAnJHdpbmRvdycsIGZ1bmN0aW9uICgkdGltZW91dCwgJHdpbmRvdykge1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICByZXN0cmljdDogJ0FFJyxcbiAgICAgICAgICAgICAgICB0ZW1wbGF0ZTogJzxkaXYgbmctdHJhbnNjbHVkZSBzdHlsZT1cImRpc3BsYXk6IG5vbmVcIj48L2Rpdj4nLFxuICAgICAgICAgICAgICAgIHRyYW5zY2x1ZGU6IHRydWUsXG4gICAgICAgICAgICAgICAgcmVwbGFjZTogdHJ1ZSxcbiAgICAgICAgICAgICAgICBzY29wZToge1xuICAgICAgICAgICAgICAgICAgICBvbmNsb3NlOiAnJidcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGxpbms6IGZ1bmN0aW9uIChzY29wZSwgZWxlbWVudCwgYXR0cikge1xuICAgICAgICAgICAgICAgICAgICAkdGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgb3B0aW9ucyA9ICQuZXh0ZW5kKGF0dHIsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBoaWRlOiAnZmFkZScsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2hvdzogJ2ZhZGUnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFwcGVuZFRvOiBlbGVtZW50LnBhcmVudCgpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGF1dG9PcGVuOiB0cnVlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNsb3NlOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJHdpbmRvdykub2ZmKCdyZXNpemUuZGlhbG9nJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICR0aW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLiRhcHBseShmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2NvcGUub25jbG9zZSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSwgMTAwKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNyZWF0ZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS4kYnJvYWRjYXN0KCdyZW5kZXJQYXJ0cycsIGVsZW1lbnQucGFyZW50KCkpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgb3BlbjogZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJHdpbmRvdykub2ZmKCdyZXNpemUuZGlhbG9nJykub24oJ3Jlc2l6ZS5kaWFsb2cnLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKGV2ZW50LnRhcmdldCkuZGlhbG9nKFwib3B0aW9uXCIsIFwicG9zaXRpb25cIiwge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG15OiBcImNlbnRlclwiLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGF0OiBcImNlbnRlclwiLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG9mOiAkd2luZG93XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnYm9keScpLm9uZSgnY2xpY2snLCcudWktd2lkZ2V0LW92ZXJsYXknLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJy51aS1kaWFsb2cnKS5maWx0ZXIoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiAkKHRoaXMpLmNzcyhcImRpc3BsYXlcIikgPT09IFwiYmxvY2tcIjtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLmZpbmQoJy51aS1kaWFsb2ctY29udGVudCcpLmRpYWxvZygnY2xvc2UnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKGVsZW1lbnQpLmRpYWxvZyhvcHRpb25zKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgc2NvcGUuJG9uKCdkaWFsb2dDbG9zZScsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKGVsZW1lbnQpLmRpYWxvZygnY2xvc2UnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICB9LCAxKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH1dKVxufSk7IiwiZGVmaW5lKFsnanF1ZXJ5LWJvb3QnLCAnbGliL2RpYWxvZycsICdwYWdlcy9tYWlsYm94L3JlcXVlc3QnLCAnbGliL2dhLXdyYXBwZXInLCAndHJhbnNsYXRvci1ib290JywgJ3JvdXRpbmcnXSwgZnVuY3Rpb24gKCQsIGRpYWxvZywgcmVxdWVzdGVyLCBnYVdyYXBwZXIpIHtcbiAgICB2YXIgQWRkTWFpbGJveCA9XG4gICAgICAgIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGZ1bmN0aW9uIEFkZE1haWxib3goKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5zZWxlY3RPd25lckRpYWxvZyA9IG51bGw7XG4gICAgICAgICAgICAgICAgdGhpcy5zZWxlY3RPd25lckRlZmVycmVkID0gbnVsbDtcbiAgICAgICAgICAgICAgICB0aGlzLm93bmVyID0gbnVsbDtcbiAgICAgICAgICAgICAgICB0aGlzLmVtYWlsT3duZXJzID0ge307XG4gICAgICAgICAgICAgICAgdGhpcy5vblN1Ym1pdEFkZEZvcm0gPSB0aGlzLm9uU3VibWl0QWRkRm9ybS5iaW5kKHRoaXMpO1xuICAgICAgICAgICAgICAgIHRoaXMuc2VsZWN0RmFtaWx5TWVtYmVyID0gdGhpcy5zZWxlY3RGYW1pbHlNZW1iZXIuYmluZCh0aGlzKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdmFyIF9wcm90byA9IEFkZE1haWxib3gucHJvdG90eXBlO1xuXG4gICAgICAgICAgICBfcHJvdG8uc2V0RmFtaWx5TWVtYmVycyA9IGZ1bmN0aW9uIHNldEZhbWlseU1lbWJlcnModXNlckZ1bGxOYW1lLCBmYW1pbHlNZW1iZXJzKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5mYW1pbHlNZW1iZXJzID0gZmFtaWx5TWVtYmVycztcbiAgICAgICAgICAgICAgICB0aGlzLmZhbWlseU1lbWJlcnMudW5zaGlmdCh7XG4gICAgICAgICAgICAgICAgICAgIHVzZXJhZ2VudGlkOiAnJyxcbiAgICAgICAgICAgICAgICAgICAgZnVsbE5hbWU6IHVzZXJGdWxsTmFtZVxuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgX3Byb3RvLnNldE93bmVyID0gZnVuY3Rpb24gc2V0T3duZXIob3duZXIpIHtcbiAgICAgICAgICAgICAgICB0aGlzLm93bmVyID0gb3duZXI7XG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICBfcHJvdG8uc2V0UmVkaXJlY3RVcmwgPSBmdW5jdGlvbiBzZXRSZWRpcmVjdFVybCh1cmwpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnJlZGlyZWN0VXJsID0gdXJsO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgX3Byb3RvLnN1YnNjcmliZSA9IGZ1bmN0aW9uIHN1YnNjcmliZSgpIHtcbiAgICAgICAgICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuXG4gICAgICAgICAgICAgICAgJChkb2N1bWVudCkub24oJ3N1Ym1pdCcsICdmb3JtW25hbWU9XCJ1c2VyX21haWxib3hcIl0nLCB0aGlzLm9uU3VibWl0QWRkRm9ybSk7XG4gICAgICAgICAgICAgICAgJChkb2N1bWVudCkub24oJ2NsaWNrJywgJy5hZGQtbWFpbGJveC1saW5rJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgIHZhciB1cmwgPSAkKHRoaXMpLmF0dHIoJ2hyZWYnKTtcblxuICAgICAgICAgICAgICAgICAgICBfdGhpcy5zZWxlY3RGYW1pbHlNZW1iZXIoKS50aGVuKGZ1bmN0aW9uIChhZ2VudElkKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBkb2N1bWVudC5sb2NhdGlvbi5ocmVmID0gdXJsICsgJz9hZ2VudElkPScgKyBhZ2VudElkO1xuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIF9wcm90by5vblN1Ym1pdEFkZEZvcm0gPSBmdW5jdGlvbiBvblN1Ym1pdEFkZEZvcm0oKSB7XG4gICAgICAgICAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgICAgICAgICB0aGlzLnNlbGVjdEZhbWlseU1lbWJlcigkKCcjdXNlcl9tYWlsYm94X2VtYWlsJykudmFsKCkpLnRoZW4oZnVuY3Rpb24gKGFnZW50SWQpIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGZvcm0gPSAkKCdmb3JtW25hbWU9XCJ1c2VyX21haWxib3hcIl0nKTtcbiAgICAgICAgICAgICAgICAgICAgcmVxdWVzdGVyLnJlcXVlc3QoUm91dGluZy5nZW5lcmF0ZSgnYXdfdXNlcm1haWxib3hfYWRkJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgYWdlbnRJZDogYWdlbnRJZFxuICAgICAgICAgICAgICAgICAgICB9KSwgJ3Bvc3QnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAnZW1haWwnOiAkKCcjdXNlcl9tYWlsYm94X2VtYWlsJykudmFsKCksXG4gICAgICAgICAgICAgICAgICAgICAgICAncGFzc3dvcmQnOiAkKCcjdXNlcl9tYWlsYm94X3Bhc3N3b3JkJykuaXMoJzp2aXNpYmxlJykgPyAkKCcjdXNlcl9tYWlsYm94X3Bhc3N3b3JkJykudmFsKCkgOiAnJ1xuICAgICAgICAgICAgICAgICAgICB9LCB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aW1lb3V0OiAxMDAwICogNjAgKiAyLFxuICAgICAgICAgICAgICAgICAgICAgICAgYnV0dG9uOiBmb3JtLmZpbmQoJ2Rpdi5zdWJtaXQnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGJlZm9yZTogZnVuY3Rpb24gYmVmb3JlKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJ2Rpdi5lcnJvci1tYWlsYm94LWxvZ2luJykucmVtb3ZlKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgc3VjY2VzczogZnVuY3Rpb24gc3VjY2VzcyhkYXRhKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHVubG9jayA9IHRydWU7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzd2l0Y2ggKGRhdGEuc3RhdHVzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNhc2UgXCJyZWRpcmVjdFwiOlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgZG9jdW1lbnQubG9jYXRpb24uaHJlZiA9IGRhdGEudXJsO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdW5sb2NrID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBicmVhaztcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjYXNlIFwiZXJyb3JcIjpcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBtZXNzYWdlID0gJCgnZGl2LnJvdy1lbWFpbCBkaXZbY2xhc3M9XCJlcnJvci1tZXNzYWdlXCJdW2RhdGEtdHlwZT1zZXJ2ZXJFcnJvcl0nKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1lc3NhZ2UuZmluZCgnZGl2LmVycm9yLW1lc3NhZ2UtZGVzY3JpcHRpb24nKS50ZXh0KGRhdGEuZXJyb3IpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbWVzc2FnZS5jc3MoJ2Rpc3BsYXknLCAndGFibGUtcm93Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKCdkaXYucm93LWVtYWlsJykuYWRkQ2xhc3MoJ2Vycm9yJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBicmVhaztcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjYXNlIFwiYXNrX3Bhc3N3b3JkXCI6XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKCdkaXYucm93LXBhc3N3b3JkJykuc2hvdyg0MDAsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKCcjdXNlcl9tYWlsYm94X3Bhc3N3b3JkJykuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY2FzZSBcImFkZGVkXCI6XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB1bmxvY2sgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKCdzZW5kaW5nIG1haWxib3ggYWRkZWQgZXZlbnQ6IGltYXAnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGdhV3JhcHBlcignZXZlbnQnLCAnYWRkZWQnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2V2ZW50X2NhdGVnb3J5JzogJ21haWxib3gnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdldmVudF9sYWJlbCc6ICdpbWFwJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnZXZlbnRfY2FsbGJhY2snOiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKF90aGlzLnJlZGlyZWN0VXJsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkb2N1bWVudC5sb2NhdGlvbi5ocmVmID0gX3RoaXMucmVkaXJlY3RVcmw7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkb2N1bWVudC5sb2NhdGlvbi5yZWxvYWQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHVubG9jaztcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgX3Byb3RvLnNlbGVjdEZhbWlseU1lbWJlciA9IGZ1bmN0aW9uIHNlbGVjdEZhbWlseU1lbWJlcihlbWFpbCkge1xuICAgICAgICAgICAgICAgIHRoaXMuc2VsZWN0T3duZXJEZWZlcnJlZCA9ICQuRGVmZXJyZWQoKTtcblxuICAgICAgICAgICAgICAgIGlmICh0eXBlb2YodGhpcy5vd25lcikgPT09ICdzdHJpbmcnKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuc2VsZWN0T3duZXJEZWZlcnJlZC5yZXNvbHZlKHRoaXMub3duZXIpO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5zZWxlY3RPd25lckRlZmVycmVkLnByb21pc2UoKTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBpZiAodGhpcy5mYW1pbHlNZW1iZXJzLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLnNlbGVjdE93bmVyRGVmZXJyZWQucmVzb2x2ZSgnJyk7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLnNlbGVjdE93bmVyRGVmZXJyZWQucHJvbWlzZSgpO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGlmIChlbWFpbCAmJiBlbWFpbCBpbiB0aGlzLmVtYWlsT3duZXJzKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuc2VsZWN0T3duZXJEZWZlcnJlZC5yZXNvbHZlKHRoaXMuZW1haWxPd25lcnNbZW1haWxdKTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2VsZWN0T3duZXJEZWZlcnJlZC5wcm9taXNlKCk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKHRoaXMuc2VsZWN0T3duZXJEaWFsb2cgPT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIF90aGlzID0gdGhpcztcblxuICAgICAgICAgICAgICAgICAgICB0aGlzLnNlbGVjdE93bmVyRGlhbG9nID0gZGlhbG9nLmZhc3RDcmVhdGUoXG4gICAgICAgICAgICAgICAgICAgICAgICBUcmFuc2xhdG9yLnRyYW5zKCdtYWlsYm94X293bmVyJyksXG4gICAgICAgICAgICAgICAgICAgICAgICBcIjxkaXY+XFxuXCIgK1xuICAgICAgICAgICAgICAgICAgICAgICAgXCIgICAgICAgICAgICA8bGFiZWwgZm9yPVxcXCJzZXQtb3duZXJcXFwiPlwiICsgVHJhbnNsYXRvci50cmFucygnbWFpbGJveF9vd25lcicpICsgXCI6PC9sYWJlbD5cXG5cIiArXG4gICAgICAgICAgICAgICAgICAgICAgICBcIiAgICAgICAgICAgIDxkaXYgY2xhc3M9XFxcImlucHV0XFxcIj5cXG5cIiArXG4gICAgICAgICAgICAgICAgICAgICAgICBcIiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPVxcXCJpbnB1dC1pdGVtXFxcIj5cXG5cIiArXG4gICAgICAgICAgICAgICAgICAgICAgICBcIiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz1cXFwic3R5bGVkLXNlbGVjdFxcXCI+XFxuXCIgK1xuICAgICAgICAgICAgICAgICAgICAgICAgXCIgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2PlxcblwiICtcbiAgICAgICAgICAgICAgICAgICAgICAgIFwiICAgICAgICAgICAgICAgICAgICAgICAgPHNlbGVjdCBjbGFzcz1cXFwibWFpbGJveC1vd25lclxcXCI+XFxuXCIgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIF90aGlzLmZhbWlseU1lbWJlcnMubWFwKGZ1bmN0aW9uKGZhbWlseU1lbWJlcikge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gXCI8b3B0aW9uIHZhbHVlPVxcXCJcIiArIGZhbWlseU1lbWJlci51c2VyYWdlbnRpZCArIFwiXFxcIj5cIiArIGZhbWlseU1lbWJlci5mdWxsTmFtZSArIFwiPC9vcHRpb24+XFxuXCI7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSkuam9pbigpICtcbiAgICAgICAgICAgICAgICAgICAgICAgIFwiICAgICAgICAgICAgICAgICAgICAgICAgPC9zZWxlY3Q+XFxuXCIgK1xuICAgICAgICAgICAgICAgICAgICAgICAgXCIgICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cXG5cIiArXG4gICAgICAgICAgICAgICAgICAgICAgICBcIiAgICAgICAgICAgICAgICAgICAgPC9kaXY+XFxuXCIgK1xuICAgICAgICAgICAgICAgICAgICAgICAgXCIgICAgICAgICAgICAgICAgPC9kaXY+XFxuXCIgK1xuICAgICAgICAgICAgICAgICAgICAgICAgXCIgICAgICAgICAgICA8L2Rpdj5cXG5cIiArXG4gICAgICAgICAgICAgICAgICAgICAgICBcIiAgICAgICAgPC9kaXY+XCIsXG4gICAgICAgICAgICAgICAgICAgICAgICB0cnVlLFxuICAgICAgICAgICAgICAgICAgICAgICAgZmFsc2UsXG4gICAgICAgICAgICAgICAgICAgICAgICBbXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXh0OiBUcmFuc2xhdG9yLnRyYW5zKCdidXR0b24ub2snKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY2xpY2s6IGZ1bmN0aW9uIGNsaWNrKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGFnZW50SWQgPSAkKCcudWktZGlhbG9nLWNvbnRlbnQgLm1haWxib3gtb3duZXInKS52YWwoKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGVtYWlsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgX3RoaXMuZW1haWxPd25lcnNbZW1haWxdID0gYWdlbnRJZDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kaWFsb2coJ2Nsb3NlJyk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIF90aGlzLnNlbGVjdE93bmVyRGVmZXJyZWQucmVzb2x2ZShhZ2VudElkKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2NsYXNzJzogJ2J0bi1ibHVlJ1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXh0OiBUcmFuc2xhdG9yLnRyYW5zKCdidXR0b24uY2FuY2VsJyksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNsaWNrOiBmdW5jdGlvbiBjbGljaygpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQodGhpcykuZGlhbG9nKCdjbG9zZScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnY2xhc3MnOiAnYnRuLXNpbHZlcidcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICBdLFxuICAgICAgICAgICAgICAgICAgICAgICAgNTAwXG4gICAgICAgICAgICAgICAgICAgICk7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuc2VsZWN0T3duZXJEaWFsb2cuc2V0T3B0aW9uKCdjbG9zZScsIG51bGwpO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIHRoaXMuc2VsZWN0T3duZXJEaWFsb2cub3BlbigpO1xuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLnNlbGVjdE93bmVyRGVmZXJyZWQucHJvbWlzZSgpO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgcmV0dXJuIEFkZE1haWxib3g7XG4gICAgICAgIH0oKTtcblxuICAgIHJldHVybiBuZXcgQWRkTWFpbGJveCgpO1xufSk7IiwiZGVmaW5lKFsnanF1ZXJ5LWJvb3QnXSwgZnVuY3Rpb24gKCQpIHtcbiAgICB2YXIgUmVxdWVzdCA9XG4gICAgICAgIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGZ1bmN0aW9uIFJlcXVlc3QoKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5jb250YWluZXIgPSBudWxsO1xuICAgICAgICAgICAgICAgIHRoaXMuYnVzeSA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIHRoaXMuYnVzeVRpbWVyID0gbnVsbDtcbiAgICAgICAgICAgICAgICB0aGlzLmZhZGVySWQgPSAndXNlck1haWxib3hGYWRlcic7XG4gICAgICAgICAgICAgICAgdGhpcy5mYWRlciA9ICQoJzxkaXYgc3R5bGU9XCJkaXNwbGF5OiBub25lOyBwb3NpdGlvbjogYWJzb2x1dGU7IHRvcDogMDsgbGVmdDogMDsgd2lkdGg6IDEwMCU7IGhlaWdodDogMTAwJTsgYmFja2dyb3VuZC1jb2xvcjogd2hpdGU7IC1tcy1maWx0ZXI6IFxcJ3Byb2dpZDpEWEltYWdlVHJhbnNmb3JtLk1pY3Jvc29mdC5BbHBoYShPcGFjaXR5PTgwKVxcJzsgZmlsdGVyOiBhbHBoYShvcGFjaXR5PTgwKTsgb3BhY2l0eTogMC44OyB6LWluZGV4OiAxMDA7XCIgaWQ9XCInICsgdGhpcy5mYWRlcklkICsgJ1wiPjwvZGl2PicpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB2YXIgX3Byb3RvID0gUmVxdWVzdC5wcm90b3R5cGU7XG5cbiAgICAgICAgICAgIF9wcm90by5zZXRDb250YWluZXIgPSBmdW5jdGlvbiBzZXRDb250YWluZXIoY29udGFpbmVyKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5jb250YWluZXIgPSBjb250YWluZXI7XG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICBfcHJvdG8uc2hvd0J1dHRvblByb2dyZXNzID0gZnVuY3Rpb24gc2hvd0J1dHRvblByb2dyZXNzKGJ1dHRvbikge1xuICAgICAgICAgICAgICAgICQoYnV0dG9uKS5maW5kKCdpbnB1dCcpLmF0dHIoJ2Rpc2FibGVkJywgJ2Rpc2FibGVkJyk7XG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICBfcHJvdG8uaGlkZUJ1dHRvblByb2dyZXNzID0gZnVuY3Rpb24gaGlkZUJ1dHRvblByb2dyZXNzKGJ1dHRvbikge1xuICAgICAgICAgICAgICAgICQoYnV0dG9uKS5maW5kKCdpbnB1dCcpLnJlbW92ZUF0dHIoJ2Rpc2FibGVkJyk7XG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICBfcHJvdG8ubG9jayA9IGZ1bmN0aW9uIGxvY2soKSB7XG4gICAgICAgICAgICAgICAgaWYgKHRoaXMuYnVzeSkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKHRoaXMuY29udGFpbmVyKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZmFkZXIuY2xvbmUoKS5hcHBlbmRUbyh0aGlzLmNvbnRhaW5lcikuY3NzKHtcbiAgICAgICAgICAgICAgICAgICAgICAgIG9wYWNpdHk6IDBcbiAgICAgICAgICAgICAgICAgICAgfSkuaGVpZ2h0KCQoZG9jdW1lbnQpLmhlaWdodCgpKS5zaG93KCkuc3RvcCgpLmFuaW1hdGUoe1xuICAgICAgICAgICAgICAgICAgICAgICAgb3BhY2l0eTogMC41XG4gICAgICAgICAgICAgICAgICAgIH0sIDIwMDApO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIHRoaXMuYnVzeSA9IHRydWU7XG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICBfcHJvdG8udW5sb2NrID0gZnVuY3Rpb24gdW5sb2NrKCkge1xuICAgICAgICAgICAgICAgIGlmICh0aGlzLmNvbnRhaW5lcikge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmNvbnRhaW5lci5maW5kKCcjJyArIHRoaXMuZmFkZXJJZCkuc3RvcCgpLmFuaW1hdGUoe1xuICAgICAgICAgICAgICAgICAgICAgICAgb3BhY2l0eTogMFxuICAgICAgICAgICAgICAgICAgICB9LCB7XG4gICAgICAgICAgICAgICAgICAgICAgICBkdXJhdGlvbjogNjAwLFxuICAgICAgICAgICAgICAgICAgICAgICAgY29tcGxldGU6IGZ1bmN0aW9uIGNvbXBsZXRlKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQodGhpcykucmVtb3ZlKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIHRoaXMuYnVzeSA9IGZhbHNlO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgX3Byb3RvLnJlcXVlc3QgPSBmdW5jdGlvbiByZXF1ZXN0KHVybCwgbWV0aG9kLCBkYXRhLCBzZXR0aW5ncykge1xuICAgICAgICAgICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG5cbiAgICAgICAgICAgICAgICBpZiAodGhpcy5idXN5KSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICB2YXIgZGVmYXVsdHMgPSB7XG4gICAgICAgICAgICAgICAgICAgIHRpbWVvdXQ6IDMwMDAwLFxuICAgICAgICAgICAgICAgICAgICBiZWZvcmU6IGZ1bmN0aW9uIGJlZm9yZSgpIHt9LFxuICAgICAgICAgICAgICAgICAgICBjb21wbGV0ZTogZnVuY3Rpb24gY29tcGxldGUoKSB7fSxcbiAgICAgICAgICAgICAgICAgICAgc3VjY2VzczogZnVuY3Rpb24gc3VjY2VzcygpIHt9LFxuICAgICAgICAgICAgICAgICAgICBlcnJvcjogZnVuY3Rpb24gZXJyb3IoKSB7fSxcbiAgICAgICAgICAgICAgICAgICAgYnV0dG9uOiBudWxsXG4gICAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICAgICBzZXR0aW5ncyA9ICQuZXh0ZW5kKHt9LCBkZWZhdWx0cywgc2V0dGluZ3MpO1xuICAgICAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgICAgIHVybDogdXJsLFxuICAgICAgICAgICAgICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICAgICAgICAgICAgICB0eXBlOiBtZXRob2QsXG4gICAgICAgICAgICAgICAgICAgIGRhdGE6IGRhdGEsXG4gICAgICAgICAgICAgICAgICAgIHRpbWVvdXQ6IHNldHRpbmdzLnRpbWVvdXQsXG4gICAgICAgICAgICAgICAgICAgIGJlZm9yZVNlbmQ6IGZ1bmN0aW9uIGJlZm9yZVNlbmQoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoIV90aGlzLmJ1c3kpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGVhclRpbWVvdXQoX3RoaXMuYnVzeVRpbWVyKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBfdGhpcy5idXN5VGltZXIgPSBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgX3RoaXMudW5sb2NrKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSwgc2V0dGluZ3MudGltZW91dCArIDEwMDApO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICBfdGhpcy5sb2NrKCk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0eXBlb2Yoc2V0dGluZ3MuYnV0dG9uKSA9PT0gJ29iamVjdCcgJiYgc2V0dGluZ3MuYnV0dG9uICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgX3RoaXMuc2hvd0J1dHRvblByb2dyZXNzKHNldHRpbmdzLmJ1dHRvbik7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIHNldHRpbmdzLmJlZm9yZSgpO1xuICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICBjb21wbGV0ZTogZnVuY3Rpb24gY29tcGxldGUoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzZXR0aW5ncy5jb21wbGV0ZSgpO1xuICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAvLyB0b2RvIGRlcHJlY2F0ZWRcbiAgICAgICAgICAgICAgICAgICAgc3VjY2VzczogZnVuY3Rpb24gc3VjY2Vzcyhqc29uKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoc2V0dGluZ3Muc3VjY2Vzcyhqc29uKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIF90aGlzLnVubG9jaygpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZihzZXR0aW5ncy5idXR0b24pID09PSAnb2JqZWN0Jykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBfdGhpcy5oaWRlQnV0dG9uUHJvZ3Jlc3Moc2V0dGluZ3MuYnV0dG9uKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgIGVycm9yOiBmdW5jdGlvbiBlcnJvcihqcVhIUiwgc3RhdHVzLCBfZXJyb3IpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIF90aGlzLnVubG9jaygpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAodHlwZW9mKHNldHRpbmdzLmJ1dHRvbikgPT09ICdvYmplY3QnKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgX3RoaXMuaGlkZUJ1dHRvblByb2dyZXNzKHNldHRpbmdzLmJ1dHRvbik7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIHNldHRpbmdzLmVycm9yKGpxWEhSLCBzdGF0dXMsIF9lcnJvcik7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIHJldHVybiBSZXF1ZXN0O1xuICAgICAgICB9KCk7XG5cbiAgICByZXR1cm4gbmV3IFJlcXVlc3QoKTtcbn0pOyIsImRlZmluZShbXG4gICAgJ2FuZ3VsYXItYm9vdCcsXG4gICAgJ2xpYi91dGlscycsXG4gICAgJ2xpYi9jdXN0b21pemVyJyxcbiAgICAnZGF0ZVRpbWVEaWZmJyxcbiAgICAncGFnZXMvdGltZWxpbmUvbWFpbidcbl0sIGZ1bmN0aW9uIChhbmd1bGFyLCB1dGlscywgY3VzdG9taXplciwgZGF0ZVRpbWVEaWZmKSB7XG4gICAgYW5ndWxhciA9IGFuZ3VsYXIgJiYgYW5ndWxhci5fX2VzTW9kdWxlID8gYW5ndWxhci5kZWZhdWx0IDogYW5ndWxhcjtcblxuICAgIGFuZ3VsYXIubW9kdWxlKCdhcHAnKVxuICAgICAgICAuZGlyZWN0aXZlKCdvbkZpbmlzaFJlbmRlcicsIFsnJHRpbWVvdXQnLCBmdW5jdGlvbiAoJHRpbWVvdXQpIHtcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgcmVzdHJpY3Q6ICdBJyxcbiAgICAgICAgICAgICAgICBsaW5rOiBmdW5jdGlvbiAoc2NvcGUsIGVsZW1lbnQsIGF0dHIpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKHNjb3BlLiRsYXN0ID09PSB0cnVlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkdGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2NvcGUuJGVtaXQoYXR0ci5vbkZpbmlzaFJlbmRlciA/IGF0dHIub25GaW5pc2hSZW5kZXIgOiAnbmdSZXBlYXRGaW5pc2hlZCcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH1dKVxuICAgICAgICAuZGlyZWN0aXZlKCdvbkVycm9yJywgKCkgPT4ge1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICByZXN0cmljdDogJ0EnLFxuICAgICAgICAgICAgICAgIGxpbms6IGZ1bmN0aW9uKCRzY29wZSwgJGVsZW1lbnQsICRhdHRyKSB7XG4gICAgICAgICAgICAgICAgICAgICRlbGVtZW50Lm9uKCdlcnJvcicsICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICRlbGVtZW50LmF0dHIoJ3NyYycsICRhdHRyLm9uRXJyb3IpO1xuICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSlcbiAgICAgICAgLmRpcmVjdGl2ZSgnaW1hZ2VMYXp5U3JjJywgWyckZG9jdW1lbnQnLCAnc2Nyb2xsQW5kUmVzaXplTGlzdGVuZXInLCAoJGRvY3VtZW50LCBzY3JvbGxBbmRSZXNpemVMaXN0ZW5lcikgPT4ge1xuICAgICAgICAgICAgY29uc3Qgb2Zmc2V0RmFjdG9yID0gMC41O1xuXG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIHJlc3RyaWN0OiAnQScsXG4gICAgICAgICAgICAgICAgc2NvcGU6IHtcbiAgICAgICAgICAgICAgICAgICAgaW1hZ2VMYXp5U3JjOiAnPSdcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGxpbmsoJHNjb3BlLCAkZWxlbWVudCwgJGF0dHIpIHtcbiAgICAgICAgICAgICAgICAgICAgbGV0IGxpc3RlbmVyUmVtb3ZlcjtcblxuICAgICAgICAgICAgICAgICAgICBmdW5jdGlvbiBpc0luVmlldyhjbGllbnRIZWlnaHQsIGNsaWVudFdpZHRoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBpbWFnZVJlY3QgPSAkZWxlbWVudFswXS5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IG9mZnNldEhlaWdodCA9IGNsaWVudEhlaWdodCAqIG9mZnNldEZhY3RvcjtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IG9mZnNldFdpZHRoID0gY2xpZW50V2lkdGggKiBvZmZzZXRGYWN0b3I7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAoaW1hZ2VSZWN0LnRvcCA+PSAtb2Zmc2V0SGVpZ2h0ICYmIGltYWdlUmVjdC5ib3R0b20gPD0gKGNsaWVudEhlaWdodCArIG9mZnNldEhlaWdodCkpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJiZcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAoaW1hZ2VSZWN0LmxlZnQgPj0gLW9mZnNldFdpZHRoICYmIGltYWdlUmVjdC5yaWdodCA8PSAoY2xpZW50V2lkdGggKyBvZmZzZXRXaWR0aCkpXG4gICAgICAgICAgICAgICAgICAgICAgICApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkZWxlbWVudC5hdHRyKCdzcmMnLCAkc2NvcGUuaW1hZ2VMYXp5U3JjKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBsaXN0ZW5lclJlbW92ZXIoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuJHdhdGNoKCdpbWFnZUxhenlTcmMnLCB2YWwgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAodmFsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkZWxlbWVudC5hdHRyKCdzcmMnLCB2YWwpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICBsaXN0ZW5lclJlbW92ZXIgPSBzY3JvbGxBbmRSZXNpemVMaXN0ZW5lci5hZGRMaXN0ZW5lcihpc0luVmlldyk7XG4gICAgICAgICAgICAgICAgICAgICRlbGVtZW50Lm9uKCckZGVzdHJveScsICgpID0+IGxpc3RlbmVyUmVtb3ZlcigpKTtcblxuICAgICAgICAgICAgICAgICAgICBpc0luVmlldyhcbiAgICAgICAgICAgICAgICAgICAgICAgICRkb2N1bWVudFswXS5kb2N1bWVudEVsZW1lbnQuY2xpZW50SGVpZ2h0LFxuICAgICAgICAgICAgICAgICAgICAgICAgJGRvY3VtZW50WzBdLmRvY3VtZW50RWxlbWVudC5jbGllbnRXaWR0aFxuICAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH07XG4gICAgICAgIH1dKVxuICAgICAgICAuZGlyZWN0aXZlKCd3cmFwcGVyJywgWyckdGltZW91dCcsIGZ1bmN0aW9uICgkdGltZW91dCkge1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICByZXN0cmljdDogJ0EnLFxuICAgICAgICAgICAgICAgIGxpbms6IGZ1bmN0aW9uIChzY29wZSwgZWxlbWVudCkge1xuICAgICAgICAgICAgICAgICAgICBzY29wZS4kd2F0Y2goKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHNjb3BlLnNlZ21lbnQudW5kcm9wcGFibGU7XG4gICAgICAgICAgICAgICAgICAgIH0sIHZhbCA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBlbCA9IGVsZW1lbnQucGFyZW50cygnLndyYXBwZXInKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHZhbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICghZWwuaGFzQ2xhc3MoJ3VuZHJvcHBhYmxlJykpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgZWwuYWRkQ2xhc3MoJ3VuZHJvcHBhYmxlJylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChlbC5oYXNDbGFzcygndW5kcm9wcGFibGUnKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbC5yZW1vdmVDbGFzcygndW5kcm9wcGFibGUnKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9XSlcbiAgICAgICAgLmRpcmVjdGl2ZSgndHJpcFN0YXJ0JywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICByZXN0cmljdDogJ0VBJyxcbiAgICAgICAgICAgICAgICBzY29wZToge1xuICAgICAgICAgICAgICAgICAgICBwbGFuczogJz0nLFxuICAgICAgICAgICAgICAgICAgICBzZWdtZW50OiAnPSdcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGxpbms6IGZ1bmN0aW9uIChzY29wZSwgZWxlbWVudCkge1xuICAgICAgICAgICAgICAgICAgICBzY29wZS4kd2F0Y2goJ3NlZ21lbnQnLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoIXNjb3BlLnBsYW5zW3Njb3BlLnNlZ21lbnQucGxhbklkXSAmJiB3aW5kb3cuc2hvd1Rvb2x0aXBzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2NvcGUucGxhbnNbc2NvcGUuc2VnbWVudC5wbGFuSWRdID0gc2NvcGUuc2VnbWVudDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS5wbGFuc1tzY29wZS5zZWdtZW50LnBsYW5JZF0ubmVlZFNob3dUb29sdGlwcyA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LnRyaXBTdGFydCA9IGVsZW1lbnQ7XG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2VcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS5wbGFuc1tzY29wZS5zZWdtZW50LnBsYW5JZF0gPSBzY29wZS5zZWdtZW50O1xuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pXG4gICAgICAgIC5kaXJlY3RpdmUoJ3RyaXBFbmQnLCBbJyR0aW1lb3V0JywgZnVuY3Rpb24gKCR0aW1lb3V0KSB7XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIHJlc3RyaWN0OiAnRUEnLFxuICAgICAgICAgICAgICAgIHNjb3BlOiB7XG4gICAgICAgICAgICAgICAgICAgIHBsYW5zOiAnPScsXG4gICAgICAgICAgICAgICAgICAgIHNlZ21lbnQ6ICc9JyxcbiAgICAgICAgICAgICAgICAgICAgc2VnbWVudHM6ICc9J1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgbGluazogZnVuY3Rpb24gKHNjb3BlLCBlbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgIHNjb3BlLiR3YXRjaCgncGxhbnMnLCBmdW5jdGlvbiAobykge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKG8gJiYgc2NvcGUucGxhbnNbc2NvcGUuc2VnbWVudC5wbGFuSWRdKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHBsYW4gPSBzY29wZS5wbGFuc1tzY29wZS5zZWdtZW50LnBsYW5JZF07XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2NvcGUuc2VnbWVudC5uYW1lID0gcGxhbi5uYW1lO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHN0YXJ0U2VnbWVudCA9IHNjb3BlLnNlZ21lbnRzLmluZGV4T2YocGxhbiksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGVuZFNlZ21lbnQgPSBzY29wZS5zZWdtZW50cy5pbmRleE9mKHNjb3BlLnNlZ21lbnQpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHBvaW50cyA9IFtdLCBwbGFuTGFzdFVwZGF0ZSA9IDA7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZm9yICh2YXIgaSA9IHN0YXJ0U2VnbWVudDsgaSA8IGVuZFNlZ21lbnQ7IGkrKykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgY3VycmVudCA9IHNjb3BlLnNlZ21lbnRzW2ldO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoY3VycmVudCAmJlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgKCFjdXJyZW50Lmljb24gfHwgWydmbHknLCAnYnVzJywgJ2JvYXQnLCAncGFzc2FnZS1ib2F0JywgJ3RyYWluJ10uaW5kZXhPZihjdXJyZW50Lmljb24pID4gLTEgfHwgY3VycmVudC5pY29uLmluZGV4T2YoJ2ZseScpID4gLTEpICYmXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjdXJyZW50LnR5cGUgPT09ICdzZWdtZW50JyAmJlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY3VycmVudC5tYXAgJiZcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGN1cnJlbnQubWFwLnBvaW50cy5sZW5ndGggPiAwICYmXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBwb2ludHMubGVuZ3RoIDwgMTAgLy8g0JzQsNC60YHQuNC80LDQu9GM0L3QvtC1INC60L7Qu9C40YfQtdGB0YLQstC+INGC0L7Rh9C10Log0L3QsCDQvNC40L3QuNC60LDRgNGC0LVcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgKXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChjdXJyZW50Lm1hcC5wb2ludHMubGVuZ3RoID09PSAyKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcG9pbnRzLnB1c2goY3VycmVudC5tYXAucG9pbnRzWzBdICsgJy0nICsgY3VycmVudC5tYXAucG9pbnRzWzFdKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcG9pbnRzLnB1c2goY3VycmVudC5tYXAucG9pbnRzWzBdKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICghY3VycmVudCB8fCB1bmRlZmluZWQgPT09IGN1cnJlbnQubGFzdFVwZGF0ZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGN1cnJlbnQudHlwZSA9PT0gJ3BsYW5TdGFydCcpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBsYW5MYXN0VXBkYXRlID0gY3VycmVudC5sYXN0VXBkYXRlZDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChjdXJyZW50LnR5cGUgPT09ICdzZWdtZW50JyAmJiBwbGFuTGFzdFVwZGF0ZSA8IGN1cnJlbnQubGFzdFVwZGF0ZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBsYW5MYXN0VXBkYXRlID0gY3VycmVudC5sYXN0VXBkYXRlZDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChwb2ludHMubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBsYW4ubWFwID0gcG9pbnRzO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChOdW1iZXIuaXNJbnRlZ2VyKHBsYW5MYXN0VXBkYXRlKSAmJiBwbGFuTGFzdFVwZGF0ZSA+IDApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcGxhbi5sYXN0VXBkYXRlZCA9IGRhdGVUaW1lRGlmZi5sb25nRm9ybWF0VmlhRGF0ZXMobmV3IERhdGUoKSwgbmV3IERhdGUocGxhbkxhc3RVcGRhdGUgKiAxMDAwKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHBsYW4ubmVlZFNob3dUb29sdGlwcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBwbGFuLm5lZWRTaG93VG9vbHRpcHMgPSB3aW5kb3cuc2hvd1Rvb2x0aXBzID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICR0aW1lb3V0KGZ1bmN0aW9uICgpIHtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LnRyaXBTdGFydC5maW5kKCdbZGF0YS10aXBdJykuZmlsdGVyKGZ1bmN0aW9uIChpZCwgZWwpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gISEkKGVsKS5wcm9wKCd0b29sdGlwLWluaXRpYWxpemVkJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KS50b29sdGlwKCdvcGVuJyk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChlbGVtZW50LmZpbmQoJ1tkYXRhLXRpcF0nKS5wcm9wKCd0b29sdGlwLWluaXRpYWxpemVkJykpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgZWxlbWVudC5maW5kKCdbZGF0YS10aXBdJykudG9vbHRpcCgnb3BlbicpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKGRvY3VtZW50KS5vbmUoJ2NsaWNrJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRyeXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnW2RhdGEtdGlwXScpLmZpbHRlcihmdW5jdGlvbiAoaWQsIGVsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gISEkKGVsKS5wcm9wKCd0b29sdGlwLWluaXRpYWxpemVkJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLnRvb2x0aXAoJ2Nsb3NlJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby1lbXB0eVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1jYXRjaChlKXt9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LCAxMDApO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSwgdHJ1ZSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9XSlcbiAgICAgICAgLmRpcmVjdGl2ZSgndHJpcEV4cGFuZCcsIFsnJHRpbWVvdXQnLCAnJHN0YXRlJywgZnVuY3Rpb24gKCR0aW1lb3V0LCAkc3RhdGUpIHtcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgcmVzdHJpY3Q6ICdBJyxcbiAgICAgICAgICAgICAgICBzY29wZToge1xuICAgICAgICAgICAgICAgICAgICBzZWdtZW50OiAnPXRyaXBFeHBhbmQnXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBsaW5rOiBmdW5jdGlvbiAoc2NvcGUsIGVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgZnVuY3Rpb24gY2xvc2UoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKGVsZW1lbnQubmV4dCgpKS5zbGlkZVVwKDMwMCwgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLiRhcHBseShmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLnNlZ21lbnQub3BlbmVkID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzdGF0ZS5wYXJhbXMub3BlblNlZ21lbnQgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmICh1bmRlZmluZWQgIT0gc2NvcGUuc2VnbWVudC5kaWFsb2dGbGlnaHQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS5zZWdtZW50LmRpYWxvZ0ZsaWdodC5jbG9zZSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgZnVuY3Rpb24gb3BlbihkdXJhdGlvbikge1xuICAgICAgICAgICAgICAgICAgICAgICAgZHVyYXRpb24gPSBkdXJhdGlvbiB8fCAzMDA7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoIXNjb3BlLnNlZ21lbnQuZGV0YWlscykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLiRhcHBseShmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLnNlZ21lbnQub3BlbmVkID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzdGF0ZS5wYXJhbXMub3BlblNlZ21lbnQgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICBzY29wZS4kYXBwbHkoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLnNlZ21lbnQub3BlbmVkID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc3RhdGUucGFyYW1zLm9wZW5TZWdtZW50ID0gc2NvcGUuc2VnbWVudC5pZDtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoZWxlbWVudC5uZXh0KCkpLnNsaWRlRG93bihkdXJhdGlvbiwgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoZG9jdW1lbnQubG9jYXRpb24uaHJlZi5tYXRjaCgvXFwvcHJpbnRcXC8vKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdXRpbHMuZGVib3VuY2UoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHdpbmRvdy5wcmludCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSwgMjUwKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICghc2NvcGUuc2VnbWVudC5kZXRhaWxzLmJvb2tpbmdMaW5rKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgcm93ID0gJChlbGVtZW50KS5jbG9zZXN0KCcudHJpcC1yb3cnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcm93LmZpbmQoJy5jaGVja2luLWRhdGUsIC5jaGVja291dC1kYXRlJykuYXR0cignZGF0YS1yb2xlJywgJ2RhdGVwaWNrZXInKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY3VzdG9taXplci5pbml0RGF0ZXBpY2tlcnMocm93LCBmdW5jdGlvbigpe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGNoZWNraW5EYXRlcGlja2VyID0gJChlbGVtZW50KS5jbG9zZXN0KCcudHJpcC1yb3cnKS5maW5kKCdpbnB1dC5jaGVja2luLWRhdGUnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBjaGVja291dERhdGVwaWNrZXIgPSAkKGVsZW1lbnQpLmNsb3Nlc3QoJy50cmlwLXJvdycpLmZpbmQoJ2lucHV0LmNoZWNrb3V0LWRhdGUnKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGRhdGVwaWNrZXJWYWx1ZSA9IGNoZWNraW5EYXRlcGlja2VyLnZhbCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY2hlY2tpbkRhdGVwaWNrZXIuZGF0ZXBpY2tlcignb3B0aW9uJywgJ29uU2VsZWN0JywgZnVuY3Rpb24gKGRhdGUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgc2VsZWN0ZWREYXRlID0gY2hlY2tpbkRhdGVwaWNrZXIuZGF0ZXBpY2tlcihcImdldERhdGVcIik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZWN0ZWREYXRlLnNldERhdGUoc2VsZWN0ZWREYXRlLmdldERhdGUoKSArIDEpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKGNoZWNrb3V0RGF0ZXBpY2tlci5kYXRlcGlja2VyKCdvcHRpb24nLCAnYWxsJykpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIG9wdGlvbnMgPSBjaGVja291dERhdGVwaWNrZXIuZGF0ZXBpY2tlcignb3B0aW9uJywgJ2FsbCcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG9wdGlvbnMubWluRGF0ZSA9IHNlbGVjdGVkRGF0ZTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNoZWNrb3V0RGF0ZXBpY2tlci5kYXRlcGlja2VyKG9wdGlvbnMpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNoZWNrb3V0RGF0ZXBpY2tlci5kYXRlcGlja2VyKCdzZXREYXRlJyxzZWxlY3RlZERhdGUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBhdXRvY29tcGxldGVJbnB1dCA9ICQoZWxlbWVudCkuY2xvc2VzdCgnLnRyaXAtcm93JykuZmluZCgnaW5wdXQuYWlycG9ydC1uYW1lOm5vdCgudWktYXV0b2NvbXBsZXRlLWlucHV0KScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgYXV0b2NvbXBsZXRlUmVxdWVzdDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGF1dG9Db21wbGV0ZURhdGE7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYXV0b2NvbXBsZXRlSW5wdXRcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5vZmYoJ2tleWRvd24ga2V5dXAgY2hhbmdlJylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5vbigna2V5ZG93bicsIGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAhJC50cmltKCQoZS50YXJnZXQpLnZhbCgpKSAmJlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAoZS5rZXlDb2RlID09PSAwIHx8IGUua2V5Q29kZSA9PT0gMzIpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgKSBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLm9uKCdrZXl1cCcsIGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2NvcGUuc2VnbWVudC5kZXRhaWxzLmJvb2tpbmdMaW5rLmZvcm1GaWVsZHMuc2VsZWN0ZWRJYXRhID0gbnVsbDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS5zZWdtZW50LmRldGFpbHMuYm9va2luZ0xpbmsuZm9ybUZpZWxkcy5zZWxlY3RlZERlc3RpbmF0aW9uID0gbnVsbDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAub24oJ2JsdXInLCBmdW5jdGlvbiAoZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmKGF1dG9Db21wbGV0ZURhdGEubGVuZ3RoKXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYXV0b2NvbXBsZXRlSW5wdXQudmFsKGF1dG9Db21wbGV0ZURhdGFbMF0udmFsdWUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS5zZWdtZW50LmRldGFpbHMuYm9va2luZ0xpbmsuZm9ybUZpZWxkcy5zZWxlY3RlZERlc3RpbmF0aW9uID0gYXV0b0NvbXBsZXRlRGF0YVswXS5kZXN0aW5hdGlvbjtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9ZWxzZXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYXV0b2NvbXBsZXRlSW5wdXQudmFsKCcnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2NvcGUuc2VnbWVudC5kZXRhaWxzLmJvb2tpbmdMaW5rLmZvcm1GaWVsZHMuc2VsZWN0ZWREZXN0aW5hdGlvbiA9IG51bGw7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5hdXRvY29tcGxldGUoe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRlbGF5OiAyMDAsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbWluTGVuZ3RoOiAyLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNvdXJjZTogZnVuY3Rpb24gKHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChyZXF1ZXN0LnRlcm0gJiYgcmVxdWVzdC50ZXJtLmxlbmd0aCA+PSAzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgc2VsZiA9IHRoaXM7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmKGF1dG9jb21wbGV0ZVJlcXVlc3QpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYXV0b2NvbXBsZXRlUmVxdWVzdC5hYm9ydCgpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBhdXRvY29tcGxldGVSZXF1ZXN0ID0gJC5nZXQoUm91dGluZy5nZW5lcmF0ZShcImdvb2dsZV9nZW9fY29kZVwiLCB7cXVlcnk6IHJlcXVlc3QudGVybX0pLCBmdW5jdGlvbiAoZGF0YSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoc2VsZi5lbGVtZW50KS5yZW1vdmVDbGFzcygnbG9hZGluZy1pbnB1dCcpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHJlc3VsdCA9IGRhdGEubWFwKGZ1bmN0aW9uIChpdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBjb3VudHJ5ID0gaXRlbS5hZGRyZXNzX2NvbXBvbmVudHNcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5maWx0ZXIoZnVuY3Rpb24gKGNvbXBvbmVudCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBjb21wb25lbnQudHlwZXMuaW5kZXhPZignY291bnRyeScpID4gLTFcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgY291bnRyeUxvbmcgPSBjb3VudHJ5Lmxlbmd0aCAmJiBjb3VudHJ5WzBdLmxvbmdfbmFtZTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgY2l0eSA9IGl0ZW0uYWRkcmVzc19jb21wb25lbnRzXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuZmlsdGVyKGZ1bmN0aW9uIChjb21wb25lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gY29tcG9uZW50LnR5cGVzLmluZGV4T2YoJ2xvY2FsaXR5JykgPiAtMVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNpdHkgPSBjaXR5Lmxlbmd0aCAmJiBjaXR5WzBdLmxvbmdfbmFtZTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4ge2xhYmVsOiBpdGVtLmZvcm1hdHRlZF9hZGRyZXNzLCB2YWx1ZTogY2l0eSArICcsICcgKyBjb3VudHJ5TG9uZywgZGVzdGluYXRpb246IGNpdHl9O1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYoIWF1dG9jb21wbGV0ZUlucHV0LmlzKCc6Zm9jdXMnKSl7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmKHJlc3VsdC5sZW5ndGgpe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYXV0b2NvbXBsZXRlSW5wdXQudmFsKHJlc3VsdFswXS52YWx1ZSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS5zZWdtZW50LmRldGFpbHMuYm9va2luZ0xpbmsuZm9ybUZpZWxkcy5zZWxlY3RlZERlc3RpbmF0aW9uID0gcmVzdWx0WzBdLmRlc3RpbmF0aW9uO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9ZWxzZXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLnNlZ21lbnQuZGV0YWlscy5ib29raW5nTGluay5mb3JtRmllbGRzLnNlbGVjdGVkRGVzdGluYXRpb24gPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLnNlZ21lbnQuZGV0YWlscy5ib29raW5nTGluay5mb3JtRmllbGRzLnNlbGVjdGVkSWF0YSA9IG51bGw7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYXV0b0NvbXBsZXRlRGF0YSA9IHJlc3VsdDtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlc3BvbnNlKHJlc3VsdCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWFyY2g6IGZ1bmN0aW9uIChldmVudCwgdWkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCQoZXZlbnQudGFyZ2V0KS52YWwoKS5sZW5ndGggPj0gMylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoZXZlbnQudGFyZ2V0KS5hZGRDbGFzcygnbG9hZGluZy1pbnB1dCcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbHNlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKGV2ZW50LnRhcmdldCkucmVtb3ZlQ2xhc3MoJ2xvYWRpbmctaW5wdXQnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJChldmVudC50YXJnZXQpLm5leHRBbGwoJ2lucHV0JykudmFsKFwiXCIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgb3BlbjogZnVuY3Rpb24gKGV2ZW50LCB1aSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKGV2ZW50LnRhcmdldCkucmVtb3ZlQ2xhc3MoJ2xvYWRpbmctaW5wdXQnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNyZWF0ZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmRhdGEoJ3VpLWF1dG9jb21wbGV0ZScpLl9yZW5kZXJJdGVtID0gZnVuY3Rpb24gKHVsLCBpdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgcmVnZXggPSBuZXcgUmVnRXhwKFwiKFwiICsgdGhpcy5lbGVtZW50LnZhbCgpICsgXCIpXCIsIFwiZ2lcIik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgaXRlbUxhYmVsID0gaXRlbS5sYWJlbC5yZXBsYWNlKHJlZ2V4LCBcIjxiPiQxPC9iPlwiKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiAkKCc8bGk+PC9saT4nKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5kYXRhKFwiaXRlbS5hdXRvY29tcGxldGVcIiwgaXRlbSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuYXBwZW5kKCQoJzxhPjwvYT4nKS5odG1sKGl0ZW1MYWJlbCkpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLmFwcGVuZFRvKHVsKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlbGVjdDogZnVuY3Rpb24oZXZlbnQsIHVpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoZXZlbnQudGFyZ2V0KS52YWwodWkuaXRlbS52YWx1ZSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLnNlZ21lbnQuZGV0YWlscy5ib29raW5nTGluay5mb3JtRmllbGRzLnNlbGVjdGVkSWF0YSA9IG51bGw7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLnNlZ21lbnQuZGV0YWlscy5ib29raW5nTGluay5mb3JtRmllbGRzLnNlbGVjdGVkRGVzdGluYXRpb24gPSB1aS5pdGVtLmRlc3RpbmF0aW9uO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LCAxKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgIGlmIChzY29wZS5zZWdtZW50Lm9wZW5lZCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG9wZW4oMCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICQoZWxlbWVudClcbiAgICAgICAgICAgICAgICAgICAgICAgIC5vbignY2xpY2snLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHNjb3BlLnNlZ21lbnQub3BlbmVkKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNsb3NlKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgb3BlbigpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICBpZiAoJHN0YXRlLnBhcmFtcy5vcGVuU2VnbWVudCA9PT0gc2NvcGUuc2VnbWVudC5pZCkge1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAkdGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCRzdGF0ZS5pcygndGltZWxpbmUnKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKCdodG1sLCBib2R5Jykuc2Nyb2xsVG9wKCQoZWxlbWVudCkub2Zmc2V0KCkudG9wIC0gNTApO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH0sIDEwMCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAkdGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJChlbGVtZW50KS50cmlnZ2VyKCdjbGljaycpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoZWxlbWVudCkubmV4dCgpLmVmZmVjdCgnaGlnaGxpZ2h0Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LCAyMDApXG5cbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfV0pXG4gICAgICAgIC5kaXJlY3RpdmUoJ293bmVyQXV0b2NvbXBsZXRlJywgWyckcm9vdFNjb3BlJywgZnVuY3Rpb24gKCRyb290U2NvcGUpIHtcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgcmVzdHJpY3Q6ICdBJyxcbiAgICAgICAgICAgICAgICBzY29wZToge1xuICAgICAgICAgICAgICAgICAgICBuZ0RhdGE6ICc9J1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgbGluazogZnVuY3Rpb24gKHNjb3BlLCBlbGVtLCBhdHRycykge1xuICAgICAgICAgICAgICAgICAgICAkcm9vdFNjb3BlLmFnZW50SXNTZXQgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIE5vUmVzdWx0c0xhYmVsID0gJzxpIGNsYXNzPVwiaWNvbi13YXJuaW5nLXNtYWxsXCI+PC9pPiZuYnNwOyBObyBtZW1iZXJzIGZvdW5kJztcbiAgICAgICAgICAgICAgICAgICAgZWxlbS5hdXRvY29tcGxldGUoe1xuICAgICAgICAgICAgICAgICAgICAgICAgbWluTGVuZ3RoOiAyLFxuICAgICAgICAgICAgICAgICAgICAgICAgc291cmNlOiBmdW5jdGlvbiAocmVxdWVzdCwgcmVzcG9uc2UpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBlbGVtZW50ID0gJCh0aGlzLmVsZW1lbnQpLmF0dHIoJ2NsYXNzJywgJ2xvYWRpbmctaW5wdXQnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBsYXN0UmVzcG9uc2UgPSAkLmFqYXgoe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB1cmw6IFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X2J1c2luZXNzX21lbWJlcnNfZHJvcGRvd25fdGltZWxpbmUnLCB7cTogcmVxdWVzdC50ZXJtLCBhZGQ6IHRydWV9KSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHN1Y2Nlc3M6IGZ1bmN0aW9uIChkYXRhLCBzdGF0dXMsIHhocikge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCQuaXNFbXB0eU9iamVjdChkYXRhKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEgPSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxhYmVsOiBOb1Jlc3VsdHNMYWJlbFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH07XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAobGFzdFJlc3BvbnNlID09PSB4aHIpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXNwb25zZShkYXRhKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbGVtZW50LmF0dHIoJ2NsYXNzJywgJ2NsZWFyLWlucHV0Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGVjdDogZnVuY3Rpb24gKGV2ZW50LCB1aSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICh1aS5pdGVtLmxhYmVsID09PSBOb1Jlc3VsdHNMYWJlbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS5uZ0RhdGEgPSAnJztcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJChldmVudC50YXJnZXQpLnZhbCh1aS5pdGVtLmxhYmVsKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY29wZS5uZ0RhdGEgPSB1aS5pdGVtLnZhbHVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNjb3BlLiRhcHBseSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRyb290U2NvcGUuYWdlbnRJc1NldCA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGZvY3VzOiBmdW5jdGlvbiAoZXZlbnQsIHVpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHVpLml0ZW0ubGFiZWwgPT09IE5vUmVzdWx0c0xhYmVsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGV2ZW50LmtleUNvZGUgPT0gNDAgfHwgZXZlbnQua2V5Q29kZSA9PSAzOClcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJChldmVudC50YXJnZXQpLnZhbCh1aS5pdGVtLmxhYmVsKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfV0pXG59KTsiLCJkZWZpbmUoW1xuICAgICdhbmd1bGFyLWJvb3QnLFxuICAgICdwYWdlcy90aW1lbGluZS9tYWluJ1xuXSwgYW5ndWxhciA9PiB7XG4gICAgYW5ndWxhciA9IGFuZ3VsYXIgJiYgYW5ndWxhci5fX2VzTW9kdWxlID8gYW5ndWxhci5kZWZhdWx0IDogYW5ndWxhcjtcblxuICAgIGFuZ3VsYXIubW9kdWxlKCdhcHAnKVxuICAgICAgICAuZmlsdGVyKCdjYXBpdGFsaXplJywgKCkgPT4ge1xuICAgICAgICAgICAgcmV0dXJuIHN0ciA9PiB7XG4gICAgICAgICAgICAgICAgaWYgKHR5cGVvZiBzdHIgIT09ICdzdHJpbmcnKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiAnJztcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICByZXR1cm4gc3RyLmNoYXJBdCgwKS50b1VwcGVyQ2FzZSgpICsgc3RyLnNsaWNlKDEpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbn0pOyIsImRlZmluZShbJ2FuZ3VsYXItYm9vdCcsICdqcXVlcnktYm9vdCcsICdkaXJlY3RpdmVzL2F1dG9Gb2N1cycsICd3ZWJwYWNrLXRzL3NoaW0vbmdSZWFjdCddLCBmdW5jdGlvbiAoKSB7XG4gICAgYW5ndWxhclxuICAgICAgICAubW9kdWxlKCdhcHAnLCBbJ2FwcENvbmZpZycsICdyZWFjdCcsICd1aS5yb3V0ZXInLCAnZXh0ZW5kZWREaWFsb2cnLCAnY3VzdG9taXplci1kaXJlY3RpdmUnLCAnYXV0by1mb2N1cy1kaXJlY3RpdmUnXSlcbiAgICAgICAgLmNvbmZpZyhbJyRzdGF0ZVByb3ZpZGVyJywgJyR1cmxSb3V0ZXJQcm92aWRlcicsICckbG9jYXRpb25Qcm92aWRlcicsIGZ1bmN0aW9uICgkc3RhdGVQcm92aWRlciwgJHVybFJvdXRlclByb3ZpZGVyLCAkbG9jYXRpb25Qcm92aWRlcikge1xuICAgICAgICAgICAgJGxvY2F0aW9uUHJvdmlkZXIuaHRtbDVNb2RlKHtcbiAgICAgICAgICAgICAgICBlbmFibGVkOiB0cnVlLFxuICAgICAgICAgICAgICAgIHJld3JpdGVMaW5rczogdHJ1ZVxuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICRzdGF0ZVByb3ZpZGVyXG4gICAgICAgICAgICAgICAgLnN0YXRlKHtcbiAgICAgICAgICAgICAgICAgICAgbmFtZTogJ3RpbWVsaW5lJyxcbiAgICAgICAgICAgICAgICAgICAgdXJsOiAnLzphZ2VudElkP2JlZm9yZSZvcGVuU2VnbWVudCZzaG93RGVsZXRlZCZzaG93blN0YXJ0Jm9wZW5TZWdtZW50RGF0ZScsXG4gICAgICAgICAgICAgICAgICAgIHBhcmFtczoge1xuICAgICAgICAgICAgICAgICAgICAgICAgc2hvd0RlbGV0ZWQ6ICcwJ1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAuc3RhdGUoe1xuICAgICAgICAgICAgICAgICAgICBuYW1lOiAnc2hhcmVkJyxcbiAgICAgICAgICAgICAgICAgICAgdXJsOiAnL3NoYXJlZC97Y29kZX0nXG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAuc3RhdGUoe1xuICAgICAgICAgICAgICAgICAgICBuYW1lOiAnaXRpbmVyYXJpZXMnLFxuICAgICAgICAgICAgICAgICAgICB1cmw6ICcvaXRpbmVyYXJpZXMve2l0SWRzfT9hZ2VudElkJ1xuICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgLnN0YXRlKHtcbiAgICAgICAgICAgICAgICAgICAgbmFtZTogJ3NoYXJlZC1wbGFuJyxcbiAgICAgICAgICAgICAgICAgICAgdXJsOiAnL3NoYXJlZC1wbGFuL3tjb2RlfSdcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICR1cmxSb3V0ZXJQcm92aWRlci5vdGhlcndpc2UoJy8nKTtcblxuICAgICAgICAgICAgJHVybFJvdXRlclByb3ZpZGVyLndoZW4oJy97YWdlbnRJZH0vaXRpbmVyYXJpZXMve2l0SWRzfScsICgkc3RhdGUsICRtYXRjaCkgPT4ge1xuICAgICAgICAgICAgICAgIGNvbnN0IHBhcmFtcyA9IHtcbiAgICAgICAgICAgICAgICAgICAgaXRJZHM6ICRtYXRjaC5pdElkcyxcbiAgICAgICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAgICAgaWYoJG1hdGNoLmFnZW50SWQpe1xuICAgICAgICAgICAgICAgICAgICBwYXJhbXMuYWdlbnRJZCA9ICRtYXRjaC5hZ2VudElkO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAkc3RhdGUuZ28oJ2l0aW5lcmFyaWVzJywgcGFyYW1zKTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XSlcbiAgICAgICAgLmZhY3RvcnkoJ2h0dHBJbnRlcmNlcHRvcicsIFsnJHEnLCAnJHJvb3RTY29wZScsIGZ1bmN0aW9uKCRxLCAkcm9vdFNjb3BlKSB7XG4gICAgICAgICAgICB2YXIgbG9hZGluZ0NvdW50ID0gMDtcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgcmVxdWVzdCAgICAgICA6IGZ1bmN0aW9uKGNvbmZpZykge1xuICAgICAgICAgICAgICAgICAgICB3aW5kb3cuJGh0dHBMb2FkaW5nID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGNvbmZpZyB8fCAkcS53aGVuKGNvbmZpZyk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICByZXNwb25zZSAgICAgIDogZnVuY3Rpb24ocmVzcG9uc2UpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGxvYWRpbmdDb3VudC0tIDwgMSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LiRodHRwTG9hZGluZyA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiByZXNwb25zZSB8fCAkcS53aGVuKHJlc3BvbnNlKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHJlc3BvbnNlRXJyb3IgOiBmdW5jdGlvbihyZXNwb25zZSkge1xuICAgICAgICAgICAgICAgICAgICBpZiAobG9hZGluZ0NvdW50LS0gPCAxKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cuJGh0dHBMb2FkaW5nID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuICRxLnJlamVjdChyZXNwb25zZSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgfV0pXG4gICAgICAgIC5jb25maWcoWyckaHR0cFByb3ZpZGVyJywgZnVuY3Rpb24oJGh0dHBQcm92aWRlcikge1xuICAgICAgICAgICAgJGh0dHBQcm92aWRlci5pbnRlcmNlcHRvcnMucHVzaCgnaHR0cEludGVyY2VwdG9yJyk7XG4gICAgICAgIH1dKVxuICAgICAgICAuZGlyZWN0aXZlKCdub3RlcycsIFsncmVhY3REaXJlY3RpdmUnLCBmdW5jdGlvbihyZWFjdERpcmVjdGl2ZSkge1xuICAgICAgICAgICAgcmV0dXJuIHJlYWN0RGlyZWN0aXZlKHJlcXVpcmUoJ3dlYnBhY2svanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC90aW1lbGluZS9Ob3RlcycpLmRlZmF1bHQpO1xuICAgICAgICB9XSlcbn0pOyIsImRlZmluZShbXG4gICAgJ2FuZ3VsYXItYm9vdCcsXG4gICAgJ2xpYi91dGlscycsXG4gICAgJ2RhdGVUaW1lRGlmZicsXG4gICAgJ2xpYi9kaWFsb2cnLFxuICAgICdsaWIvY3VzdG9taXplcicsXG4gICAgJ3BhZ2VzL3RpbWVsaW5lL21haW4nLFxuICAgICdyb3V0aW5nJyxcbiAgICAnY29tbW9uL2FsZXJ0cydcbl0sIGZ1bmN0aW9uIChhbmd1bGFyLCB1dGlscywgZGF0ZVRpbWVEaWZmLCBkaWFsb2csIGN1c3RvbWl6ZXIpIHtcbiAgICBhbmd1bGFyID0gYW5ndWxhciAmJiBhbmd1bGFyLl9fZXNNb2R1bGUgPyBhbmd1bGFyLmRlZmF1bHQgOiBhbmd1bGFyO1xuXG4gICAgYW5ndWxhci5tb2R1bGUoJ2FwcCcpXG4gICAgICAgIC5zZXJ2aWNlKCckdGltZWxpbmVEYXRhJywgWyckc3RhdGVQYXJhbXMnLCAnJHEnLCAnJGh0dHAnLCAnJHNjZScsICckc3RhdGUnLCAnJHdpbmRvdycsIGZ1bmN0aW9uICgkc3RhdGVQYXJhbXMsICRxLCAkaHR0cCwgJHNjZSwgJHN0YXRlLCAkd2luZG93KSB7XG4gICAgICAgICAgICBsZXQgb3B0aW9ucyA9IHt9O1xuICAgICAgICAgICAgdmFyIGV4dGVuZCA9IGZ1bmN0aW9uIChpdGVtKSB7XG5cbiAgICAgICAgICAgICAgICBpZighaXRlbS50eXBlKVxuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG5cbiAgICAgICAgICAgICAgICBpZiAoIWl0ZW0udHlwZS5tYXRjaCgvcGxhbi8pKVxuICAgICAgICAgICAgICAgICAgICBpdGVtLnVuZHJvcHBhYmxlID0gdHJ1ZTtcblxuICAgICAgICAgICAgICAgICQuZXh0ZW5kKGl0ZW0sIHtcbiAgICAgICAgICAgICAgICAgICAgdHJ1c3RIdG1sOiAoaHRtbCkgPT4gJHNjZS50cnVzdEFzSHRtbChodG1sKSxcbiAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgIGlmIChpdGVtLnR5cGUgPT09ICdwbGFuU3RhcnQnKSB7XG4gICAgICAgICAgICAgICAgICAgICQuZXh0ZW5kKGl0ZW0sIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGdldE1hcFVybDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgdGhpcy5tYXAgPT09ICd1bmRlZmluZWQnIHx8IChhbmd1bGFyLmlzQXJyYXkodGhpcy5tYXApICYmIHRoaXMubWFwLmxlbmd0aCA9PT0gMCkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIG51bGw7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X2ZsaWdodF9tYXAnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvZGU6IHRoaXMubWFwLmpvaW4oJywnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2l6ZTogJzI0MHgyNDAnXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgcHJpbnRUcmF2ZWxQbGFuOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHdpbmRvdy5vcGVuKFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3RpbWVsaW5lX3ByaW50JykgKyAnc2hhcmVkLXBsYW4vJyArIHRoaXMuc2hhcmVDb2RlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBwbGFuRHVyYXRpb246IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJHNjZS50cnVzdEFzSHRtbChUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcImZvciAlZHVyYXRpb24lXCIpICovICdwbGFuLWR1cmF0aW9uJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkdXJhdGlvbjogYDxiPiR7dGhpcy5kdXJhdGlvbn08L2I+YFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sICd0cmlwcycpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXROb3RlczogKCkgPT4gJHNjZS50cnVzdEFzSHRtbCh1dGlscy5saW5raWZ5KGl0ZW0ubm90ZXMudGV4dCkpLFxuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBpZiAoaXRlbS50eXBlID09PSAnZGF0ZScpIHtcbiAgICAgICAgICAgICAgICAgICAgJC5leHRlbmQoaXRlbSwge1xuICAgICAgICAgICAgICAgICAgICAgICAgZ2V0UmVsYXRpdmVEYXRlOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRhdGVUaW1lRGlmZi5sb25nRm9ybWF0VmlhRGF0ZXMobmV3IERhdGUoKSwgbmV3IERhdGUodGhpcy5sb2NhbERhdGVJU08pKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRTdGF0ZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IGRheVN0YXJ0ID0gbmV3IERhdGUoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXlTdGFydC5zZXRIb3VycygwLDAsMCwwKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5zdGFydERhdGUgPD0gKGRheVN0YXJ0IC8gMTAwMCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgZ2V0RGF5c051bWJlckZyb21Ub2RheTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkaWZmID0gTWF0aC5hYnMobmV3IERhdGUodGhpcy5zdGFydERhdGUgKiAxMDAwKSAtIG5ldyBEYXRlKCkpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBNYXRoLmZsb29yKGRpZmYgLyAxMDAwIC8gNjAgLyA2MCAvIDI0KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKGl0ZW0udHlwZSA9PT0gJ3NlZ21lbnQnKSB7XG5cbiAgICAgICAgICAgICAgICAgICAgaWYoaXRlbS5kZXRhaWxzKXtcbiAgICAgICAgICAgICAgICAgICAgICAgIGl0ZW0uZGV0YWlscy5leHRQcm9wZXJ0aWVzID0gT2JqZWN0XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLmtleXMoaXRlbS5kZXRhaWxzKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5maWx0ZXIocHJvcE5hbWUgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gdHlwZW9mIGl0ZW0uZGV0YWlsc1twcm9wTmFtZV0gPT09ICdzdHJpbmcnICYmIFtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdub3RlcycsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnbW9uaXRvcmVkU3RhdHVzJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjYW5FZGl0JyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdzaGFyZUNvZGUnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2F1dG9Mb2dpbkxpbmsnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ3JlZnJlc2hMaW5rJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdhY2NvdW50SWQnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2N1cnJlbmN5Q29kZSdcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgXS5pbmRleE9mKHByb3BOYW1lKSA9PT0gLTE7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSkucmVkdWNlKChhY2MsIHByb3BOYW1lKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFjY1twcm9wTmFtZV0gPSBpdGVtLmRldGFpbHNbcHJvcE5hbWVdO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gYWNjO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sIHt9KTtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICQuZXh0ZW5kKGl0ZW0sIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIF9mb3JtYXRUaW1lOiBmdW5jdGlvbiAodGltZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBwYXJ0cyA9IHRpbWUuc3BsaXQoJyAnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gKHBhcnRzLmxlbmd0aCA+IDEpID8gJHNjZS50cnVzdEFzSHRtbChwYXJ0c1swXSArICc8c3Bhbj4nICsgcGFydHNbMV0gKyAnPC9zcGFuPicpIDogJHNjZS50cnVzdEFzSHRtbCh0aW1lKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRUaXRsZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiAkc2NlLnRydXN0QXNIdG1sKHRoaXMudGl0bGUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGdldEltZ1NyYzogZnVuY3Rpb24oc2l6ZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHlwZW9mIHRoaXMubWFwLnBvaW50cyA9PT0gJ3VuZGVmaW5lZCdcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfHwgKGFuZ3VsYXIuaXNBcnJheSh0aGlzLm1hcC5wb2ludHMpICYmIHRoaXMubWFwLnBvaW50cy5sZW5ndGggPT09IDApKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBudWxsO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0aGlzLm1hcC5wb2ludHMubGVuZ3RoID4gMSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gUm91dGluZy5nZW5lcmF0ZSgnYXdfZmxpZ2h0X21hcCcsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvZGU6IHRoaXMubWFwLnBvaW50cy5qb2luKCctJyksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzaXplOiBzaXplXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBSb3V0aW5nLmdlbmVyYXRlKCdhd19mbGlnaHRfbWFwJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29kZTogdGhpcy5tYXAucG9pbnRzWzBdLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2l6ZTogc2l6ZVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgZ2V0TG9jYWxUaW1lOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMuX2Zvcm1hdFRpbWUodGhpcy5sb2NhbFRpbWUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGdldEFyckRhdGU6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5fZm9ybWF0VGltZSh0aGlzLm1hcC5hcnJUaW1lKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRTdGF0ZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLmVuZERhdGUgPD0gKERhdGUubm93KCkgLyAxMDAwKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRCZXR3ZWVuOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCd1bmRlZmluZWQnICE9PSB0eXBlb2YgdGhpcy5sb2NhbERhdGVJU08pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRhdGVUaW1lRGlmZi5sb25nRm9ybWF0VmlhRGF0ZXMobmV3IERhdGUoKSwgbmV3IERhdGUodGhpcy5sb2NhbERhdGVJU08pKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZGF0ZVRpbWVEaWZmLmxvbmdGb3JtYXRWaWFEYXRlcyhuZXcgRGF0ZSgpLCBuZXcgRGF0ZSh0aGlzLnN0YXJ0RGF0ZSAqIDEwMDApKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRCZXR3ZWVuVGV4dDogZnVuY3Rpb24gKHJvdykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkYXRlID0gJzxzcGFuIGNsYXNzPVwiYmx1ZVwiPicgKyByb3cuZGF0ZSArICc8L3NwYW4+JyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGVybTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAocm93LnR5cGUgPT09ICdjaGVja2luJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXJtID0gJzxzcGFuIGNsYXNzPVwicmVkXCI+JyArIHJvdy5uaWdodHMgKyAnPC9zcGFuPiAnICsgVHJhbnNsYXRvci50cmFuc0Nob2ljZSgvKiogQERlc2MoXCJuaWdodHxuaWdodHNcIikgKi8gJ25pZ2h0cycsIHJvdy5uaWdodHMpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSBpZiAoJ3VuZGVmaW5lZCcgPT09IHR5cGVvZiByb3cuZGF5cykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJHNjZS50cnVzdEFzSHRtbChkYXRlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXJtID0gJzxzcGFuIGNsYXNzPVwicmVkXCI+JyArIHJvdy5kYXlzICsgJzwvc3Bhbj4gJyArIFRyYW5zbGF0b3IudHJhbnNDaG9pY2UoLyoqIEBEZXNjKFwiZGF5fGRheXNcIikgKi8gJ2RheXMnLCByb3cuZGF5cyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciB0ZXh0ID0gVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJvbiAlZGF0ZSUgZm9yICV0ZXJtJVwiKSAqLyAnYmV0d2Vlbi50ZXh0Jywge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRlOiBkYXRlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXJtOiB0ZXJtXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICRzY2UudHJ1c3RBc0h0bWwodGV4dCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgZ2V0Tm90ZXM6IGZ1bmN0aW9uKGlzU2hvcnQgPSB0cnVlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbGV0IG5vdGVzID0gdGhpcy5kZXRhaWxzLm5vdGVzO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICgtMSAhPT0gbm90ZXMuaW5kZXhPZihcIlxcblwiKSAmJiAtMSA9PT0gbm90ZXMuaW5kZXhPZignPGJyPicpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG5vdGVzID0gbm90ZXMucmVwbGFjZSgvXFxuL2csICc8YnI+Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGlzU2hvcnQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc3QgZmxhdFRhZ3MgPSBbJ2knLCAnZW0nLCAnc3Ryb25nJywgJ2InLCAndSddO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBmbGF0VGFncy5mb3JFYWNoKHRhZyA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBub3RlcyA9IG5vdGVzXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLnJlcGxhY2UobmV3IFJlZ0V4cChcIlxcbjxcIiArIHRhZyArICc+JywgXCJnXCIpLCAnJykucmVwbGFjZShuZXcgUmVnRXhwKFwiXFxuPC9cIiArIHRhZyArIFwiPlwiLCBcImdcIiksICcnKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5yZXBsYWNlKG5ldyBSZWdFeHAoJzwnICsgdGFnICsgXCI+XFxuXCIsIFwiZ1wiKSwgJycpLnJlcGxhY2UobmV3IFJlZ0V4cCgnPC8nICsgdGFnICsgXCI+XFxuXCIsIFwiZ1wiKSwgJycpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLnJlcGxhY2UobmV3IFJlZ0V4cCgnPCcgKyB0YWcgKyAnPicsIFwiZ1wiKSwgJycpLnJlcGxhY2UobmV3IFJlZ0V4cCgnPC8nICsgdGFnICsgJz4nLCBcImdcIiksICcnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbm90ZXMgPSBub3Rlc1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLnJlcGxhY2UoLyhcXHJcXG4pezIsfXxcXHJ7Mix9fFxcbnsyLH0vZywgJyAnKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLnJlcGxhY2UoLyg8KFtePl0rKT4pL2dpLCAnICcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBub3RlcyA9IHV0aWxzLmxpbmtpZnkobm90ZXMpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICRzY2UudHJ1c3RBc0h0bWwobm90ZXMpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGdldFJlbGF0aXZlRGF0ZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBkYXRlVGltZURpZmYubG9uZ0Zvcm1hdFZpYURhdGVzKG5ldyBEYXRlKCksIG5ldyBEYXRlKHRoaXMubG9jYWxEYXRlSVNPKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgZ2V0RGF5c051bWJlckZyb21Ub2RheTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkaWZmID0gTWF0aC5hYnMobmV3IERhdGUodGhpcy5zdGFydERhdGUgKiAxMDAwKSAtIG5ldyBEYXRlKCkpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBNYXRoLmZsb29yKGRpZmYgLyAxMDAwIC8gNjAgLyA2MCAvIDI0KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRUaW1lRGlmZkZvcm1hdGVkOiBmdW5jdGlvbiAocm93KSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGRpZmYgPSByb3cudGltZXN0YW1wIC0gRGF0ZS5ub3coKSAvIDEwMDA7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoZGlmZiA+IDAgJiYgZGlmZiA8PSA4NjQwMCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZGF0ZVRpbWVEaWZmLmxvbmdGb3JtYXRWaWFEYXRlVGltZXMobmV3IERhdGUoKSwgbmV3IERhdGUocm93LnRpbWVzdGFtcCAqIDEwMDApKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgZ2V0RGlmZlRpbWVBZ28gOiBmdW5jdGlvbih0aW1lKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRhdGVUaW1lRGlmZi5sb25nRm9ybWF0VmlhRGF0ZVRpbWVzKG5ldyBEYXRlKCksIG5ldyBEYXRlKHRpbWUgKiAxMDAwKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgaXNTaG93TW9yZUxpbmtzOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbGV0IGlzID0gKHRoaXMuZGV0YWlscy5leHRQcm9wZXJ0aWVzICYmIE9iamVjdC5rZXlzKHRoaXMuZGV0YWlscy5leHRQcm9wZXJ0aWVzKS5sZW5ndGggPiAwKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB8fCB0aGlzLmRldGFpbHMubm90ZXNcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfHwgdGhpcy5pc01hbnVhbFNlZ21lbnQoKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB8fCB0aGlzLmlzQXV0b0FkZGVkU2VnbWVudCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChpc1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAmJiAndW5kZWZpbmVkJyA9PT0gdHlwZW9mIHRoaXMuaXNTaG93bkluZm9cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJiYgJ3VuZGVmaW5lZCcgIT09IHR5cGVvZiB0aGlzLmFsdGVybmF0aXZlRmxpZ2h0cykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aGlzLmlzU2hvd25JbmZvID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGlzO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGlzTWFudWFsU2VnbWVudDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMub3JpZ2lucyAmJiB0aGlzLm9yaWdpbnMubWFudWFsO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGlzQWlyU2VnbWVudDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMuYWlyO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGlzQXV0b0FkZGVkU2VnbWVudDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMub3JpZ2lucyAmJiAhdGhpcy5vcmlnaW5zLm1hbnVhbCAmJiB0aGlzLm9yaWdpbnMuYXV0byAmJiB0aGlzLm9yaWdpbnMuYXV0by5sZW5ndGggPiAwO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGdldEVkaXRMaW5rOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3RyaXBzX2VkaXQnLCB7dHJpcElkOiB0aGlzLmlkfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgdmlzaWJsZTogZmFsc2UsXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRFbGl0ZUxldmVsOiBmdW5jdGlvbiAocGhvbmVJdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHV0aWxzLmVzY2FwZShwaG9uZUl0ZW0ubGV2ZWwpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIHJlZGlyZWN0VG9Cb29raW5nOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHBheWxvYWQ7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHJvdyA9ICQoJy50cmlwLXRpdGxlW2RhdGEtaWQ9XCInICsgdGhpcy5pZCArICdcIl0nKS5jbG9zZXN0KCcudHJpcC1yb3cnKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBjaGVja2luRGF0ZSA9IG5ldyBEYXRlKFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByb3cuZmluZCgnaW5wdXRbdHlwZT1cImhpZGRlblwiXVtpZF49XCJjaGVja2luRGF0ZV9cIl0nKS52YWwoKSkudG9JU09TdHJpbmcoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgY2hlY2tvdXREYXRlID0gbmV3IERhdGUoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJvdy5maW5kKCdpbnB1dFt0eXBlPVwiaGlkZGVuXCJdW2lkXj1cImNoZWNrb3V0RGF0ZV9cIl0nKS52YWwoKSkudG9JU09TdHJpbmcoKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkZXN0aW5hdGlvbiA9IHRoaXMuZGV0YWlscy5ib29raW5nTGluay5mb3JtRmllbGRzLnNlbGVjdGVkRGVzdGluYXRpb24gfHwgdGhpcy5kZXRhaWxzLmJvb2tpbmdMaW5rLmZvcm1GaWVsZHMuZGVzdGluYXRpb247XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZighdGhpcy5kZXRhaWxzLmJvb2tpbmdMaW5rLmZvcm1GaWVsZHMuc2VsZWN0ZWRJYXRhICYmICFkZXN0aW5hdGlvbilcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcGF5bG9hZCA9IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc3M6IGRlc3RpbmF0aW9uLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjaGVja2luX21vbnRoZGF5OiBjaGVja2luRGF0ZS5zbGljZSg4LDEwKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY2hlY2tpbl95ZWFyX21vbnRoOiBjaGVja2luRGF0ZS5zbGljZSgwLDcpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjaGVja291dF9tb250aGRheTogY2hlY2tvdXREYXRlLnNsaWNlKDgsMTApLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjaGVja291dF95ZWFyX21vbnRoOiBjaGVja291dERhdGUuc2xpY2UoMCw3KSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGltZWxpbmVGb3JtOiB0cnVlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBheWxvYWQuc3MucmVwbGFjZSgvUnVzc2lhbiBGZWRlcmF0aW9uL2dpLCAnUnVzc2lhJyk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgdXJsID0gdGhpcy5kZXRhaWxzLmJvb2tpbmdMaW5rLmZvcm1GaWVsZHMudXJsICsgJyYnICsgJC5wYXJhbShwYXlsb2FkKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBsaW5rID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxpbmsuaHJlZiA9IHVybDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBsaW5rLnRhcmdldCA9ICdfYmxhbmsnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxpbmsuY2xpY2soKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBmb3JtYXRDb3N0OiBmdW5jdGlvbih2YWx1ZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBJbnRsLk51bWJlckZvcm1hdChjdXN0b21pemVyLmxvY2FsZXMoKSkuZm9ybWF0KHZhbHVlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRUcmF2ZWxlcnNDb3VudDogZnVuY3Rpb24oY291bnQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJHNjZS50cnVzdEFzSHRtbChUcmFuc2xhdG9yLnRyYW5zQ2hvaWNlKC8qKiBARGVzYyhcIiVudW1iZXIlIHBhc3NlbmdlcnwlbnVtYmVyJSBwYXNzZW5nZXJzXCIpICovJ251bWJlci1wYXNzZW5nZXJzJywgY291bnQsIHsnbnVtYmVyJyA6IGNvdW50fSwgJ3RyaXBzJykpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGFsdGVybmF0aXZlRmxpZ2h0IDogZnVuY3Rpb24oJGV2ZW50KSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbGV0IG9sZFBvcHVwID0gJCgnLmFsdGVybmF0aXZlLWZsaWdodDp2aXNpYmxlJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKG9sZFBvcHVwLmxlbmd0aCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBvbGRQb3B1cC5jbG9zZXN0KCcudWktZGlhbG9nJykuZmluZCgnLnVpLWRpYWxvZy10aXRsZWJhci1jbG9zZScpLmNsaWNrKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxldCBwb3B1cCA9IGpRdWVyeSgnLmFsdGVybmF0aXZlLWZsaWdodCcsICQoJGV2ZW50LnRhcmdldCkuY2xvc2VzdCgnLmRldGFpbHMtaW5mbycpKS5jbG9uZSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBvcHVwLmZpbmQoJ2lucHV0W25nLWNoZWNrZWQ9XCJ0cnVlXCJdJykucHJvcCgnY2hlY2tlZCcsIHRydWUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBvcHVwXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5maW5kKCcuYWx0ZXJuYXRpdmUtZmxpZ2h0X2Jsb2NrX19uYW1lLCAuYWx0ZXJuYXRpdmUtZmxpZ2h0X2Jsb2NrX19hZGQgc3BhbicpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5jbGljaygoZSkgPT4gJChlLnRhcmdldCkuY2xvc2VzdCgnLmFsdGVybmF0aXZlLWZsaWdodF9ibG9jaycpLmZpbmQoJy5hbHRlcm5hdGl2ZS1mbGlnaHRfYmxvY2tfX2NoZWNrIGlucHV0W25hbWU9XCJjdXN0b21QaWNrXCJdJykucHJvcCgnY2hlY2tlZCcsIHRydWUpLnRyaWdnZXIoJ2NoYW5nZScpKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuZW5kKClcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLmZpbmQoJ2lucHV0W25hbWU9XCJjdXN0b21WYWx1ZVwiXScpLmtleXVwKChlKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoJycgIT09ICQoZS50YXJnZXQpLnZhbCgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJChlLnRhcmdldCkuY2xvc2VzdCgnLmFsdGVybmF0aXZlLWZsaWdodF9ibG9ja19fYWRkJykuZmluZCgnPnNwYW4nKS50cmlnZ2VyKCdjbGljaycpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuZW5kKClcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLmRhdGEoJ2FkZGNsYXNzJywgJ2RpYWxvZy1hbHRlcm5hdGl2ZS1mbGlnaHQnKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuZmluZCgnLmpzLWJ0bi1zYXZlJylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLmNsaWNrKChlKSA9PiB0aGlzLnVwZGF0ZUFsdGVybmF0aXZlRmxpZ2h0KGUpKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuZmluZCgnLmFsdGVybmF0aXZlLWZsaWdodC10cGwnKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAucmVtb3ZlQ2xhc3MoJ2FsdGVybmF0aXZlLWZsaWdodC10cGwnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aGlzLmRpYWxvZ0ZsaWdodCA9IGRpYWxvZy5jcmVhdGVOYW1lZCgnZmxpZ2h0cycsIHBvcHVwLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRpdGxlIDogVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJDaG9vc2UgQWx0ZXJuYXRpdmUgRmxpZ2h0XCIpICovICdjaG9vc2UtYWx0ZXJuYXRpdmUtZmxpZ2h0Jywge30sICd0cmlwcycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aWR0aCA6IDcwMCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVzaXphYmxlIDogZmFsc2VcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMuZGlhbG9nRmxpZ2h0Lm9wZW4oKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aGlzLmRpYWxvZ0ZsaWdodC5zZXRPcHRpb24oJ2Nsb3NlJywgKCkgPT4gdGhpcy5kaWFsb2dGbGlnaHQuZGVzdHJveSgpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICB1cGRhdGVBbHRlcm5hdGl2ZUZsaWdodCA6IGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCAkZGlhbG9nID0gJChlLnRhcmdldCkuY2xvc2VzdCgnLnVpLXdpZGdldC1jb250ZW50Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnLmN1c3RvbXNldC1lcnJvcnMnLCAkZGlhbG9nKS5lbXB0eSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxldCAkYnRuID0gJChlLnRhcmdldCksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBpY2sgPSAkKCdpbnB1dFtuYW1lPVwiY3VzdG9tUGlja1wiXTpjaGVja2VkJywgJGRpYWxvZykudmFsKCksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGN1c3RvbVZhbHVlID0gJCgnaW5wdXRbbmFtZT1cImN1c3RvbVZhbHVlXCJdJywgJGRpYWxvZykudmFsKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJGJ0bi5hZGRDbGFzcygnbG9hZGVyJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc3Qgc2VsZiA9IHRoaXM7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJC5wb3N0KFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3RpbWVsaW5lX21pbGV2YWx1ZV9jdXN0b21zZXQnKSwge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnaWQnIDogdGhpcy5pZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2N1c3RvbVBpY2snIDogcGljayxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2N1c3RvbVZhbHVlJyA6IGN1c3RvbVZhbHVlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSwgZnVuY3Rpb24ocmVzcG9uc2UpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJGJ0bi5yZW1vdmVDbGFzcygnbG9hZGVyJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChyZXNwb25zZS5zdWNjZXNzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBmb3IgKGxldCBpIGluIHJlc3BvbnNlLmRhdGEpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWxmLmFsdGVybmF0aXZlRmxpZ2h0c1tpXSA9IHJlc3BvbnNlLmRhdGFbaV07XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWxmLmRpYWxvZ0ZsaWdodC5jbG9zZSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2UgaWYgKHJlc3BvbnNlLmVycm9ycykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnLmN1c3RvbXNldC1lcnJvcnMnLCAkZGlhbG9nKS5odG1sKE9iamVjdC52YWx1ZXMocmVzcG9uc2UuZXJyb3JzKS5qb2luKCc8YnI+JykpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSwgJ2pzb24nKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBmb3JtYXRGaWxlU2l6ZTogZnVuY3Rpb24oYnl0ZXMpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gdXRpbHMuZm9ybWF0RmlsZVNpemUoYnl0ZXMpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGZvcm1hdERhdGVUaW1lOiBmdW5jdGlvbihzdHJEYXRlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIG5ldyBJbnRsLkRhdGVUaW1lRm9ybWF0KGN1c3RvbWl6ZXIubG9jYWxlcygpLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGVTdHlsZTogJ21lZGl1bScsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRpbWVTdHlsZTogJ3Nob3J0J1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLmZvcm1hdChEYXRlLnBhcnNlKHN0ckRhdGUpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBnZXRGaWxlTGluayhmaWxlSWQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gUm91dGluZy5nZW5lcmF0ZSgnYXdfdGltZWxpbmVfaXRpbmVyYXJ5X2ZldGNoX2ZpbGUnLCB7IGl0aW5lcmFyeUZpbGVJZDogZmlsZUlkIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIHNldE9wdGlvbnM6IGZ1bmN0aW9uKHBhcmFtcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG9wdGlvbnMgPSBwYXJhbXM7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgcHJpbnRQcm9wZXJ0aWVzVmFsdWUobmFtZSwgdmFsdWUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG9wdGlvbnMsICdjb2xsYXBzZUZpZWxkUHJvcGVydGllcycpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICYmIC0xICE9PSBvcHRpb25zLmNvbGxhcHNlRmllbGRQcm9wZXJ0aWVzLmluZGV4T2YobmFtZSkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICRzY2UudHJ1c3RBc0h0bWwoYFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGEgY2xhc3M9XCJwcm9wZXJ0aWVzLXZhbHVlLWNvbGxhcHNlXCIgaHJlZj1cIiNjb2xsYXBzZVwiPjwvYT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9XCJkZXRhaWxzLXByb3BlcnR5LW5hbWVcIj48YSBjbGFzcz1cInByb3BlcnRpZXMtdmFsdWUtY29sbGFwc2UtbmFtZVwiIGhyZWY9XCIjY29sbGFwc2VcIj4ke25hbWV9PC9hPjwvZGl2PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz1cImRldGFpbHMtcHJvcGVydHktdmFsdWUgZGV0YWlscy1wcm9wZXJ0aWVzLWNvbGxhcHNlXCI+JHt2YWx1ZX08L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICRzY2UudHJ1c3RBc0h0bWwoYFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPVwiZGV0YWlscy1wcm9wZXJ0eS1uYW1lXCI+JHtuYW1lfTwvZGl2PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPVwiZGV0YWlscy1wcm9wZXJ0eS12YWx1ZVwiPiR7dmFsdWV9PC9kaXY+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgYCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgaXNMYXlvdmVyU2VnbWVudDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICdMLicgPT09IHRoaXMuaWQuc3Vic3RyKDAsIDIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIHNob3dQb3B1cE5hdGl2ZUFwcHM6IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IGhlYWQgPSBUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIkF3YXJkV2FsbGV0IGhhcyBuYXRpdmUgaU9TIGFuZCBBbmRyb2lkIGFwcHMsICVicmVhayVwbGVhc2UgcGljayB0aGUgb25lIHlvdSBuZWVkXCIpICovJ2F3YXJkd2FsbGV0LWhhcy1uYXRpdmUtYXBwcy1waWNrLW5lZWQnLCB7J2JyZWFrJzogJyd9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBjb250ZW50ID0gYFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGEgaHJlZj1cImh0dHBzOi8vYXBwcy5hcHBsZS5jb20vdXMvYXBwL2F3YXJkd2FsbGV0LXRyYWNrLXJld2FyZHMvaWQzODg0NDI3MjdcIiB0YXJnZXQ9XCJhcHBcIj48aW1nIHNyYz1cIi9hc3NldHMvYXdhcmR3YWxsZXRuZXdkZXNpZ24vaW1nL2RldmljZS9pb3MvZW4ucG5nXCIgYWx0PVwiXCI+PC9hPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGEgaHJlZj1cImh0dHBzOi8vcGxheS5nb29nbGUuY29tL3N0b3JlL2FwcHMvZGV0YWlscz9pZD1jb20uaXRsb2d5LmF3YXJkd2FsbGV0XCIgdGFyZ2V0PVwiYXBwXCI+PGltZyBzcmM9XCIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2ltZy9kZXZpY2UvYW5kcm9pZC9lbi5wbmdcIiBhbHQ9XCJcIj48L2E+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGA7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc3QgcG9wdXAgPSBkaWFsb2cuY3JlYXRlTmFtZWQoJ25hdGl2ZUFwcHMnLCAkKGA8ZGl2IGRhdGEtYWRkY2xhc3M9XCJwb3B1cC1uYXRpdmUtYXBwc1wiPiR7Y29udGVudH08L2Rpdj5gKSwge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aXRsZTogaGVhZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgd2lkdGg6IDQ3NCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYXV0b09wZW46IHRydWUsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1vZGFsOiB0cnVlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBvbkNsb3NlOiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBvcHVwLmRlc3Ryb3koKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgbGV0IHNlZ21lbnRDbGFzc2VzID0gaXRlbS5pY29uO1xuXG4gICAgICAgICAgICAgICAgaWYgKFxuICAgICAgICAgICAgICAgICAgICBpdGVtLmFpclxuICAgICAgICAgICAgICAgICAgICAmJiAoXG4gICAgICAgICAgICAgICAgICAgICAgICAoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdHlwZW9mIGl0ZW0ubWFwID09PSAndW5kZWZpbmVkJ1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHx8IHR5cGVvZiBpdGVtLm1hcC5wb2ludHMgPT09ICd1bmRlZmluZWQnXG4gICAgICAgICAgICAgICAgICAgICAgICApXG4gICAgICAgICAgICAgICAgICAgICAgICB8fFxuICAgICAgICAgICAgICAgICAgICAgICAgKFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFuZ3VsYXIuaXNBcnJheShpdGVtLm1hcC5wb2ludHMpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJiYgaXRlbS5tYXAucG9pbnRzLmxlbmd0aCA8IDJcbiAgICAgICAgICAgICAgICAgICAgICAgIClcbiAgICAgICAgICAgICAgICAgICAgKVxuICAgICAgICAgICAgICAgICkge1xuICAgICAgICAgICAgICAgICAgICBzZWdtZW50Q2xhc3NlcyArPSAnIHBhcnRpYWwnO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGl0ZW0uc2VnbWVudENsYXNzZXMgPSBzZWdtZW50Q2xhc3NlcztcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgZmV0Y2g6IGZ1bmN0aW9uIChhZnRlcikge1xuICAgICAgICAgICAgICAgICAgICB2YXIgZGVmZXIgPSAkcS5kZWZlcigpO1xuICAgICAgICAgICAgICAgICAgICBkZWZlci5wcm9taXNlLmNhbmNlbCA9IGZ1bmN0aW9uICgpIHtkZWZlci5yZWplY3QoKX07XG5cbiAgICAgICAgICAgICAgICAgICAgLy8gcHJlbG9hZGVkXG4gICAgICAgICAgICAgICAgICAgIGlmIChPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwod2luZG93LCAnVGltZWxpbmVEYXRhJykgJiYgdHlwZW9mIHdpbmRvdy5UaW1lbGluZURhdGEgPT0gJ29iamVjdCcpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkYXRhID0gd2luZG93LlRpbWVsaW5lRGF0YTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEuc2VnbWVudHMubWFwKGZ1bmN0aW9uIChlbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGV4dGVuZChlbCwgZGF0YS5zZWdtZW50cyk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGRlZmVyLnJlc29sdmUoZGF0YSk7XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZGVmZXIucHJvbWlzZTtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgIHZhciByb3V0ZSA9IFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3RpbWVsaW5lX2RhdGEnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICBhZ2VudElkOiAkc3RhdGVQYXJhbXMuYWdlbnRJZCB8fCBudWxsLFxuICAgICAgICAgICAgICAgICAgICAgICAgYmVmb3JlOiBhZnRlciA/IG51bGwgOiAkc3RhdGVQYXJhbXMuYmVmb3JlIHx8IG51bGwsXG4gICAgICAgICAgICAgICAgICAgICAgICBhZnRlcjogYWZ0ZXIgfHwgbnVsbCxcbiAgICAgICAgICAgICAgICAgICAgICAgIHNob3dEZWxldGVkOiAkc3RhdGVQYXJhbXMuc2hvd0RlbGV0ZWQgfHwgMFxuICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICBpZiAoJHN0YXRlLmlzKCdzaGFyZWQnKSlcbiAgICAgICAgICAgICAgICAgICAgICAgIHJvdXRlID0gUm91dGluZy5nZW5lcmF0ZSgnYXdfdGltZWxpbmVfZGF0YV9zaGFyZWQnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2hhcmVDb2RlOiAkc3RhdGVQYXJhbXMuY29kZVxuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgaWYgKCRzdGF0ZS5pcygnc2hhcmVkLXBsYW4nKSlcbiAgICAgICAgICAgICAgICAgICAgICAgIHJvdXRlID0gUm91dGluZy5nZW5lcmF0ZSgnYXdfdHJhdmVscGxhbl9kYXRhX3NoYXJlZCcsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzaGFyZUNvZGU6ICRzdGF0ZVBhcmFtcy5jb2RlXG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICBpZiAoJHN0YXRlLmlzKCdpdGluZXJhcmllcycpKVxuICAgICAgICAgICAgICAgICAgICAgICAgcm91dGUgPSBSb3V0aW5nLmdlbmVyYXRlKCdhd190aW1lbGluZV9kYXRhX3NlZ21lbnRzJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdpdElkcyc6ICRzdGF0ZVBhcmFtcy5pdElkcyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnYWdlbnRJZCc6ICRzdGF0ZVBhcmFtcy5hZ2VudElkIHx8IG51bGwsXG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICAkaHR0cCh7XG4gICAgICAgICAgICAgICAgICAgICAgICB1cmw6IHJvdXRlLFxuICAgICAgICAgICAgICAgICAgICAgICAgZGlzYWJsZUVycm9yRGlhbG9nOiB0cnVlXG4gICAgICAgICAgICAgICAgICAgIH0pLnRoZW4oZnVuY3Rpb24gKHJlc3BvbnNlKSB7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChyZXNwb25zZS5zdGF0dXMgIT09IDIwMCB8fCB0eXBlb2YgcmVzcG9uc2UuZGF0YSAhPT0gJ29iamVjdCcpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoJHN0YXRlUGFyYW1zLm9wZW5TZWdtZW50KVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZXNzaW9uU3RvcmFnZS5iYWNrVXJsID0gUm91dGluZy5nZW5lcmF0ZSgnYXdfdGltZWxpbmUnKSArICc/b3BlblNlZ21lbnQ9JyArICRzdGF0ZVBhcmFtcy5vcGVuU2VnbWVudDtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxvY2F0aW9uLmhyZWYgPSAnL2xvZ2luJztcbiAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVzcG9uc2UuZGF0YS5zZWdtZW50cy5tYXAoZnVuY3Rpb24gKGVsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGV4dGVuZChlbCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZGVmZXIucmVzb2x2ZShyZXNwb25zZS5kYXRhKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSwgZnVuY3Rpb24gKHJlc3BvbnNlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2Uuc3RhdHVzID09PSA0MDMpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgb3B0aW9ucyA9IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29udGVudDogVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJZb3UgYXJlIGF0dGVtcHRpbmcgdG8gYWNjZXNzIGEgdHJhdmVsIHJlc2VydmF0aW9uIHRoYXQgYmVsb25ncyB0byBhIGRpZmZlcmVudCBhY2NvdW50IHRoYW4gdGhlIG9uZSB5b3UgYXJlIGxvZ2dlZCBpbiBhcyByaWdodCBub3cuIElmIHlvdSBvcGVuZWQgdGhpcyBsaW5rIGJ5IG1pc3Rha2UsIHBsZWFzZSBuYXZpZ2F0ZSB0byBhbm90aGVyIHBhZ2UsIGlmIHlvdSBrbm93IHRoaXMgaXMgeW91ciB0cmF2ZWwgcmVzZXJ2YXRpb24gdGhlbiB5b3UgbXVzdCBsb2dpbiBhcyBhIHVzZXIgdG8gd2hvbSB0aGlzIHRyYXZlbCByZXNlcnZhdGlvbiBiZWxvbmdzLiBJZiB5b3UgYXJlIGNvbWluZyB0byB0aGlzIHBhZ2UgZnJvbSBhbiBlbWFpbCB5b3UgcmVjZWl2ZWQgdHJ5IHVzaW5nIHRoYXQgZW1haWwgYWRkcmVzcyBhcyB5b3VyIGxvZ2luIHZhbHVlLlwiKSAqLyAndHJpcHMuYWNjZXNzLmRlbmllZC5wb3B1cCcpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aXRsZTogVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJBY2Nlc3MgRGVuaWVkXCIpICovICdhY2Nlc3MuZGVuaWVkJyksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNsb3NlT25Fc2NhcGU6IGZhbHNlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aWR0aDogNjAwLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBvcGVuOiBmdW5jdGlvbiAoZXZlbnQsIHVpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKFwiLnVpLWRpYWxvZy10aXRsZWJhci1jbG9zZVwiLCB1aS5kaWFsb2cgfCB1aSkuaGlkZSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBidXR0b25zOiBbXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGV4dDogVHJhbnNsYXRvci50cmFucygvKipARGVzYyhcIk9rXCIpKi8nYWxlcnRzLmJ0bi5vaycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNsaWNrOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxvY2F0aW9uLmhyZWYgPSAnL3RpbWVsaW5lLyc7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnY2xhc3MnOiAnYnRuLWJsdWUnXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIF1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH07XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgakFsZXJ0KG9wdGlvbnMpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfWVsc2UgaWYgKHJlc3BvbnNlLnN0YXR1cyA9PT0gNDA2KSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc3Qgb3B0aW9ucyA9IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29udGVudDogcmVzcG9uc2UuZGF0YS5lcnJvcixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGl0bGU6IFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiQWNjZXNzIERlbmllZFwiKSAqLyAnYWNjZXNzLmRlbmllZCcpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbG9zZU9uRXNjYXBlOiBmYWxzZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgd2lkdGg6IDYwMCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgb3BlbjogZnVuY3Rpb24gKGV2ZW50LCB1aSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJChcIi51aS1kaWFsb2ctdGl0bGViYXItY2xvc2VcIiwgdWkuZGlhbG9nIHwgdWkpLmhpZGUoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYnV0dG9uczogW1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRleHQ6IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJPa1wiKSovJ2FsZXJ0cy5idG4ub2snKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGljazogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBsb2NhdGlvbi5ocmVmID0gJy9tZW1iZXJzL2Nvbm5lY3Rpb24vJyArIHJlc3BvbnNlLmRhdGEuYWdlbnRJZDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGFzcyc6ICdidG4tYmx1ZSdcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgXVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBqQWxlcnQob3B0aW9ucyk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gZGVmZXIucHJvbWlzZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH1dKVxuICAgICAgICAuc2VydmljZSgnJHRyYXZlbFBsYW5zJywgWyckaHR0cCcsIGZ1bmN0aW9uICgkaHR0cCkge1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICBtb3ZlOiBmdW5jdGlvbiAocGFyYW1zKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiAkaHR0cC5wb3N0KFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3RyYXZlbHBsYW5fbW92ZScpLCAkLnBhcmFtKHBhcmFtcyksIHtoZWFkZXJzOiB7J0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi94LXd3dy1mb3JtLXVybGVuY29kZWQnfX0pO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfV0pXG4gICAgICAgIC5zZXJ2aWNlKCdzY3JvbGxBbmRSZXNpemVMaXN0ZW5lcicsIFsnJHdpbmRvdycsICckZG9jdW1lbnQnLCAnJHRpbWVvdXQnLCBmdW5jdGlvbiAoJHdpbmRvdywgJGRvY3VtZW50LCAkdGltZW91dCkge1xuICAgICAgICAgICAgbGV0IHNjcm9sbFRpbWVvdXQ7XG4gICAgICAgICAgICBsZXQgcmVzaXplVGltZW91dDtcbiAgICAgICAgICAgIGxldCBpZCA9IDA7XG4gICAgICAgICAgICBjb25zdCBsaXN0ZW5lcnMgPSB7fTtcblxuICAgICAgICAgICAgZnVuY3Rpb24gaW52b2tlTGlzdGVuZXJzKCkge1xuICAgICAgICAgICAgICAgIGNvbnN0IGNsaWVudEhlaWdodCA9ICRkb2N1bWVudFswXS5kb2N1bWVudEVsZW1lbnQuY2xpZW50SGVpZ2h0O1xuICAgICAgICAgICAgICAgIGNvbnN0IGNsaWVudFdpZHRoID0gJGRvY3VtZW50WzBdLmRvY3VtZW50RWxlbWVudC5jbGllbnRXaWR0aDtcblxuICAgICAgICAgICAgICAgIGZvciAobGV0IGtleSBpbiBsaXN0ZW5lcnMpIHtcbiAgICAgICAgICAgICAgICAgICBpZiAoT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKGxpc3RlbmVycywga2V5KSkge1xuICAgICAgICAgICAgICAgICAgICAgICBsaXN0ZW5lcnNba2V5XShjbGllbnRIZWlnaHQsIGNsaWVudFdpZHRoKTtcbiAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAkd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3Njcm9sbCcsICgpID0+IHtcbiAgICAgICAgICAgICAgICAkdGltZW91dC5jYW5jZWwoc2Nyb2xsVGltZW91dCk7XG4gICAgICAgICAgICAgICAgc2Nyb2xsVGltZW91dCA9ICR0aW1lb3V0KGludm9rZUxpc3RlbmVycywgMjAwKTtcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAkd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3Jlc2l6ZScsICgpID0+IHtcbiAgICAgICAgICAgICAgICAkdGltZW91dC5jYW5jZWwocmVzaXplVGltZW91dCk7XG4gICAgICAgICAgICAgICAgcmVzaXplVGltZW91dCA9ICR0aW1lb3V0KGludm9rZUxpc3RlbmVycywgMjAwKTtcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIGFkZExpc3RlbmVyKGxpc3RlbmVyKSB7XG4gICAgICAgICAgICAgICAgICAgIGxldCBpbmRleCA9ICsraWQ7XG5cbiAgICAgICAgICAgICAgICAgICAgbGlzdGVuZXJzW2lkXSA9IGxpc3RlbmVyO1xuXG4gICAgICAgICAgICAgICAgICAgIHJldHVybiAoKSA9PiBkZWxldGUgbGlzdGVuZXJzW2luZGV4XTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9O1xuICAgICAgICB9XSk7XG59KTtcbiIsImRlZmluZShbXG4gICAgJ2FuZ3VsYXItYm9vdCcsXG4gICAgJ2pxdWVyeS1ib290JyxcbiAgICAnbGliL3V0aWxzJyxcbiAgICAncGFnZXMvbWFpbGJveC9hZGQnLFxuICAgICdsaWIvZGlhbG9nJyxcbiAgICAnbGliL2N1c3RvbWl6ZXInLFxuICAgICdkaXJlY3RpdmVzL2N1c3RvbWl6ZXInLFxuICAgICdqcXVlcnl1aScsXG4gICAgJ3JvdXRpbmcnLFxuICAgICdhbmd1bGFyLXVpLXJvdXRlcicsXG4gICAgJ2RpcmVjdGl2ZXMvZXh0ZW5kZWREaWFsb2cnLFxuICAgICdwYWdlcy90aW1lbGluZS9tYWluJyxcbiAgICAncGFnZXMvdGltZWxpbmUvZGlyZWN0aXZlcycsXG4gICAgJ3BhZ2VzL3RpbWVsaW5lL2ZpbHRlcnMnLFxuICAgICdwYWdlcy90aW1lbGluZS9zZXJ2aWNlcycsXG4gICAgJ3RyYW5zbGF0b3ItYm9vdCdcbl0sIGZ1bmN0aW9uIChhbmd1bGFyLCAkLCB1dGlscywgYWRkTWFpbGJveCwgZGlhbG9nLCBjdXN0b21pemVyKSB7XG4gICAgYW5ndWxhciA9IGFuZ3VsYXIgJiYgYW5ndWxhci5fX2VzTW9kdWxlID8gYW5ndWxhci5kZWZhdWx0IDogYW5ndWxhcjtcblxuICAgIC8vIHBlcnNvbnNfbWVudVxuICAgIHZhciBjb3VudFdpdGhOdWxsO1xuICAgIHZhciBzaG93V2l0aE51bGwgPSBmYWxzZTtcbiAgICB2YXIgaXNUaW1lbGluZSA9IHRydWU7XG5cbiAgICBpZiAoaXNUaW1lbGluZSkge1xuICAgICAgICAkKGRvY3VtZW50KS5vbigndXBkYXRlLmhpZGRlbi51c2VycycsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGNvdW50V2l0aE51bGwgPSAwO1xuXG4gICAgICAgICAgICBpZiAoIXNob3dXaXRoTnVsbCkge1xuICAgICAgICAgICAgICAgICQoJy5qcy1wZXJzb25zLW1lbnUnKS5maW5kKCdsaScpLmVhY2goZnVuY3Rpb24gKGlkLCBlbCkge1xuICAgICAgICAgICAgICAgICAgICBpZiAoJChlbCkuZmluZCgnLmNvdW50JykubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoJChlbCkuZmluZCgnLmNvdW50JykudGV4dCgpID09PSAnMCcgJiYgISQoZWwpLmhhc0NsYXNzKCdhY3RpdmUnKSAmJiAkKGVsKS5maW5kKCdhW2RhdGEtaWQ9bXldJykubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJChlbCkuc2xpZGVVcCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvdW50V2l0aE51bGwrKztcbiAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJChlbCkuc2xpZGVEb3duKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgIGlmIChjb3VudFdpdGhOdWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICQoJyN1c2Vyc19zaG93bW9yZScpLmNsb3Nlc3QoJ2xpJykuc2xpZGVEb3duKCk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgJCgnI3VzZXJzX3Nob3dtb3JlJykuY2xvc2VzdCgnbGknKS5zbGlkZVVwKCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcblxuICAgICAgICAkKGRvY3VtZW50KS5vbignY2xpY2snLCAnI3VzZXJzX3Nob3dtb3JlJywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICQoJy5qcy1wZXJzb25zLW1lbnUnKS5maW5kKCdsaTpoaWRkZW4nKS5zbGlkZURvd24oKTtcbiAgICAgICAgICAgICQoJyN1c2Vyc19zaG93bW9yZScpLmNsb3Nlc3QoJ2xpJykuc2xpZGVVcCgpO1xuICAgICAgICAgICAgc2hvd1dpdGhOdWxsID0gdHJ1ZTtcbiAgICAgICAgfSk7XG4gICAgfVxuXG4gICAgJCh3aW5kb3cpLm9uKCdwZXJzb24uYWN0aXZhdGUnLCBmdW5jdGlvbiAoZXZlbnQsIGlkKSB7XG4gICAgICAgIGxldCAkcGVyc29ucyA9ICQoJy5qcy1wZXJzb25zLW1lbnUnKSwgJHBlcnNvbiA9IG51bGw7XG4gICAgICAgIGlmICghKGlkIGluc3RhbmNlb2YgalF1ZXJ5KSkge1xuICAgICAgICAgICAgaWYgKC0xICE9PSBpZC5pbmRleE9mKCdfJykpXG4gICAgICAgICAgICAgICAgaWQgPSBpZC5zcGxpdCgnXycpWzFdIHx8ICdteSc7XG4gICAgICAgICAgICBpZiAoJycgPT0gaWQpIGlkID0gJ215JztcbiAgICAgICAgICAgICRwZXJzb24gPSAkcGVyc29ucy5maW5kKCdhW2RhdGEtaWQ9XCInICsgaWQgKyAnXCJdJyk7XG4gICAgICAgICAgICAwID09PSAkcGVyc29uLmxlbmd0aCA/ICRwZXJzb24gPSAkcGVyc29ucy5maW5kKCdhW2RhdGEtYWdlbnRpZD1cIicgKyBpZCArICdcIl06Zmlyc3QnKSA6IG51bGw7XG4gICAgICAgICAgICAwID09PSAkcGVyc29uLmxlbmd0aCA/ICRwZXJzb24gPSAkcGVyc29ucy5maW5kKCdhW2RhdGEtaWQ9XCJteVwiXScpIDogbnVsbDtcbiAgICAgICAgfVxuICAgICAgICBpZiAoJHBlcnNvbiBpbnN0YW5jZW9mIGpRdWVyeSkge1xuICAgICAgICAgICAgJHBlcnNvbnMuY2hpbGRyZW4oKS5yZW1vdmVDbGFzcygnYWN0aXZlJyk7XG4gICAgICAgICAgICAkcGVyc29ucy5maW5kKCdhIHNwYW4uY291bnQnKS5yZW1vdmVDbGFzcygnYmx1ZScpLmFkZENsYXNzKCdzaWx2ZXInKTtcbiAgICAgICAgICAgICRwZXJzb24ucGFyZW50cygnbGknKS5hZGRDbGFzcygnYWN0aXZlJyk7XG4gICAgICAgICAgICAkcGVyc29uLmZpbmQoJ3NwYW4uY291bnQnKS5yZW1vdmVDbGFzcygnc2lsdmVyJykuYWRkQ2xhc3MoJ2JsdWUnKTtcbiAgICAgICAgICAgICQod2luZG93KS50cmlnZ2VyKCdwZXJzb24uYWN0aXZlJywgJCgkcGVyc29uKS5kYXRhKCdpZCcpKTtcbiAgICAgICAgfVxuXG4gICAgICAgICQoZG9jdW1lbnQpLnRyaWdnZXIoJ3VwZGF0ZS5oaWRkZW4udXNlcnMnKTtcbiAgICB9KTtcblxuICAgIC8vIGxpYi9kZXNpZ25cbiAgICAkKGRvY3VtZW50KS5vbignY2xpY2snLCAnLmpzLWFkZC1uZXctcGVyc29uLCAjYWRkLXBlcnNvbi1idG4sIC5qcy1wZXJzb25zLW1lbnUgYVtocmVmPVwiL3VzZXIvY29ubmVjdGlvbnNcIl0uYWRkJywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICByZXF1aXJlKFsncGFnZXMvYWdlbnQvYWRkRGlhbG9nJ10sIGZ1bmN0aW9uIChjbGlja0hhbmRsZXIpIHtcbiAgICAgICAgICAgIGNsaWNrSGFuZGxlcigpO1xuICAgICAgICB9KTtcbiAgICB9KTtcblxuICAgIGFuZ3VsYXJcbiAgICAgICAgLm1vZHVsZSgnYXBwJylcbiAgICAgICAgLmNvbnRyb2xsZXIoJ3RpbWVsaW5lJywgW1xuICAgICAgICAgICAgJyRzY29wZScsXG4gICAgICAgICAgICAnJHRpbWVsaW5lRGF0YScsXG4gICAgICAgICAgICAnJHN0YXRlUGFyYW1zJyxcbiAgICAgICAgICAgICckc3RhdGUnLFxuICAgICAgICAgICAgJyRmaWx0ZXInLFxuICAgICAgICAgICAgJyRodHRwJyxcbiAgICAgICAgICAgICckdGltZW91dCcsXG4gICAgICAgICAgICAnJHNjZScsXG4gICAgICAgICAgICAnJHRyYXZlbFBsYW5zJyxcbiAgICAgICAgICAgICckbG9nJyxcbiAgICAgICAgICAgICckbG9jYXRpb24nLFxuICAgICAgICAgICAgJyR3aW5kb3cnLFxuICAgICAgICAgICAgJyR0cmFuc2l0aW9ucycsXG4gICAgICAgICAgICBmdW5jdGlvbiAoXG4gICAgICAgICAgICAgICAgJHNjb3BlLFxuICAgICAgICAgICAgICAgICR0aW1lbGluZURhdGEsXG4gICAgICAgICAgICAgICAgJHN0YXRlUGFyYW1zLFxuICAgICAgICAgICAgICAgICRzdGF0ZSxcbiAgICAgICAgICAgICAgICAkZmlsdGVyLFxuICAgICAgICAgICAgICAgICRodHRwLFxuICAgICAgICAgICAgICAgICR0aW1lb3V0LFxuICAgICAgICAgICAgICAgICRzY2UsXG4gICAgICAgICAgICAgICAgJHRyYXZlbFBsYW5zLFxuICAgICAgICAgICAgICAgICRsb2csXG4gICAgICAgICAgICAgICAgJGxvY2F0aW9uLFxuICAgICAgICAgICAgICAgICR3aW5kb3csXG4gICAgICAgICAgICAgICAgJHRyYW5zaXRpb25zXG4gICAgICAgICkge1xuICAgICAgICAgICAgJHNjb3BlLnN0YXRlUGFyYW1zID0gJHN0YXRlUGFyYW1zO1xuICAgICAgICAgICAgJHNjb3BlLiRsb2cgPSAkbG9nO1xuICAgICAgICAgICAgJHNjb3BlLnNlZ21lbnRzID0gW107XG4gICAgICAgICAgICAkc2NvcGUuaGF2ZUZ1dHVyZVNlZ21lbnRzID0gZmFsc2U7XG4gICAgICAgICAgICAkc2NvcGUuYWdlbnRzID0gW107XG4gICAgICAgICAgICAkc2NvcGUucGxhbnMgPSBbXTtcbiAgICAgICAgICAgICRzY29wZS5hZ2VudCA9IHtcbiAgICAgICAgICAgICAgICBuZXdvd25lcjogJycsXG4gICAgICAgICAgICAgICAgY29weTogZmFsc2VcbiAgICAgICAgICAgIH07XG4gICAgICAgICAgICAkc2NvcGUuY2FuQWRkID0gZmFsc2U7XG4gICAgICAgICAgICAkc2NvcGUuZW1iZWRkZWREYXRhID0gT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKHdpbmRvdywgJ1RpbWVsaW5lRGF0YScpICYmIHR5cGVvZiB3aW5kb3cuVGltZWxpbmVEYXRhID09ICdvYmplY3QnO1xuICAgICAgICAgICAgJHNjb3BlLmFjdGl2ZVNlZ21lbnROdW1iZXIgPSBudWxsO1xuICAgICAgICAgICAgJHNjb3BlLm5vRm9yZWlnbkZlZXNDYXJkcyA9IFtdO1xuICAgICAgICAgICAgJHNjb3BlLm9wdGlvbnMgPSB7fTtcbiAgICAgICAgICAgIHZhciBvdmVybGF5ID0gJCgnPGRpdiBjbGFzcz1cInVpLXdpZGdldC1vdmVybGF5XCI+PC9kaXY+JykuaGlkZSgpLmFwcGVuZFRvKCdib2R5Jyk7XG5cbiAgICAgICAgICAgIGFkZE1haWxib3guc3Vic2NyaWJlKCk7XG4gICAgICAgICAgICBhZGRNYWlsYm94LnNldFJlZGlyZWN0VXJsKFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3VzZXJtYWlsYm94X3ZpZXcnKSk7XG5cbiAgICAgICAgICAgICRzY29wZS5tZXRob2RzID0ge1xuICAgICAgICAgICAgICAgIHNlZ21lbnRMaW5rOiBmdW5jdGlvbiAoc2VnbWVudElkKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBSb3V0aW5nLmdlbmVyYXRlKCdhd190aW1lbGluZV9zaG93Jywge3NlZ21lbnRJZH0pXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICB0b3NzaW5nRmlsbDogZnVuY3Rpb24gKHNlZ21lbnQsIHNlZ21lbnRzKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChzZWdtZW50LnR5cGUubWF0Y2goL3BsYW4vKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy50b3NzaW5nQ2xlYXIoc2VnbWVudCwgc2VnbWVudHMpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgaSA9IHNlZ21lbnRzLmluZGV4T2Yoc2VnbWVudCkgLSAxO1xuICAgICAgICAgICAgICAgICAgICAgICAgd2hpbGUgKGkgPiAwICYmICFzZWdtZW50c1tpXS50eXBlLm1hdGNoKC9wbGFuLykpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50c1tpXS51bmRyb3BwYWJsZSA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGktLTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgaSA9IHNlZ21lbnRzLmluZGV4T2Yoc2VnbWVudCkgKyAxO1xuICAgICAgICAgICAgICAgICAgICAgICAgd2hpbGUgKGkgPCBzZWdtZW50cy5sZW5ndGggJiYgIXNlZ21lbnRzW2ldLnR5cGUubWF0Y2goL3BsYW4vKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlZ21lbnRzW2ldLnVuZHJvcHBhYmxlID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaSsrO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAkdGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJChcIi51aS1zb3J0YWJsZVwiKS5zb3J0YWJsZShcInJlZnJlc2hcIik7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LCAxMDApO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICB0b3NzaW5nQ2xlYXI6IGZ1bmN0aW9uIChzZWdtZW50LCBzZWdtZW50cykge1xuICAgICAgICAgICAgICAgICAgICBpZiAoc2VnbWVudC50eXBlLm1hdGNoKC9wbGFuLykpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGFuZ3VsYXIuZm9yRWFjaChzZWdtZW50cywgZnVuY3Rpb24gKHNlZykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlZy51bmRyb3BwYWJsZSA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICB0b3NzaW5nRHJvcDogZnVuY3Rpb24gKHNlZ21lbnQsIHNlZ21lbnRzLCAkZXZlbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnJlcyA9IHNlZ21lbnRzO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgZXNjYXBlOiBmdW5jdGlvbiAoZXZlbnQsIHNlZ21lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGV2ZW50LmtleUNvZGUgPT0gMjcpXG4gICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50LmNoYW5nZU5hbWVTdGF0ZSA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgbW92ZTogZnVuY3Rpb24gKHNlZ21lbnQsIGFnZW50LCBldmVudCkge1xuICAgICAgICAgICAgICAgICAgICAkKGV2ZW50LnRhcmdldCkuYWRkQ2xhc3MoJ2xvYWRlcicpLnByb3AoJ2Rpc2FibGVkJywgdHJ1ZSk7XG4gICAgICAgICAgICAgICAgICAgICRodHRwKHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHVybDogYWdlbnQubmV3b3duZXIgIT0gJ215JyA/XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgUm91dGluZy5nZW5lcmF0ZSgnYXdfdGltZWxpbmVfbW92ZScsIHsnaXRDb2RlJzogc2VnbWVudC5pZCwgJ2FnZW50JzogYWdlbnQubmV3b3duZXJ9KSA6XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgUm91dGluZy5nZW5lcmF0ZSgnYXdfdGltZWxpbmVfbW92ZScsIHsnaXRDb2RlJzogc2VnbWVudC5pZH0pLFxuICAgICAgICAgICAgICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICAgICAgICAgICAgICBkYXRhOiAkLnBhcmFtKHtjb3B5OiBhZ2VudC5jb3B5fSksXG4gICAgICAgICAgICAgICAgICAgICAgICBoZWFkZXJzOiB7J0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi94LXd3dy1mb3JtLXVybGVuY29kZWQnfVxuICAgICAgICAgICAgICAgICAgICB9KS50aGVuKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5yZWNhbGN1bGF0ZUFmdGVyID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzdGF0ZS5yZWxvYWQoKTtcbiAgICAgICAgICAgICAgICAgICAgfSlbXCJmaW5hbGx5XCJdKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICQoZXZlbnQudGFyZ2V0KS5yZW1vdmVDbGFzcygnbG9hZGVyJykucHJvcCgnZGlzYWJsZWQnLCBmYWxzZSk7XG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgfSxcblxuICAgICAgICAgICAgICAgIGdldE1vdmVUZXh0OiBmdW5jdGlvbiAoc2VnbWVudHMsIGNvbmZfbm8pIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIG5fc2VnbWVudHMgPSBUcmFuc2xhdG9yLnRyYW5zQ2hvaWNlKC8qKiBARGVzYyhcIiVjb3VudCUgc2VnbWVudHwlY291bnQlIHNlZ21lbnRzXCIpICovICduX3NlZ21lbnRzJywgc2VnbWVudHMsIHsnY291bnQnOiBzZWdtZW50c30sICd0cmlwcycpO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJBbGwgJXNlZ21lbnRzJSBvZiBDb25mIyAlY29uZl9ubyUgd2lsbCBiZSBtb3ZlZCAob3IgY29waWVkKSwgaWYgdGhpcyBpcyBub3Qgd2hlbiB5b3UgaW50ZW5kZWQgeW91IGNhbiBkZWxldGUgdGhlIHNlZ21lbnRzIHlvdSBkb24ndCBuZWVkIGxhdGVyLlwiKSAqLyAnbW92ZV9hbGxfc2VnbWVudHMnLCB7J3NlZ21lbnRzJzogbl9zZWdtZW50cywgY29uZl9ubzogY29uZl9ub30sICd0cmlwcycpO1xuICAgICAgICAgICAgICAgIH0sXG5cbiAgICAgICAgICAgICAgICBnZXRPcmlnaW5UZXh0OiBmdW5jdGlvbihvcmlnaW4sIGxpc3RJdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChvcmlnaW4udHlwZSA9PT0gJ2FjY291bnQnKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBwYXJhbXMgPSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ3Byb3ZpZGVyTmFtZSc6IG9yaWdpbi5wcm92aWRlcixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnYWNjb3VudE51bWJlcic6IG9yaWdpbi5hY2NvdW50TnVtYmVyLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdvd25lcic6IG9yaWdpbi5vd25lcixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnbGlua19vbic6ICc8YSB0YXJnZXQ9XCJfYmxhbmtcIiBocmVmPVwiJyArIFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X2FjY291bnRfbGlzdCcpICsgJy8/YWNjb3VudD0nICsgb3JpZ2luLmFjY291bnRJZCArICdcIj4nLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdsaW5rX29mZic6ICc8L2E+JyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnYm9sZF9vbic6ICc8Yj4nLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdib2xkX29mZic6ICc8L2I+J1xuICAgICAgICAgICAgICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGxpc3RJdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICRzY2UudHJ1c3RBc0h0bWwoVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCIlbGlua19vbiUlYm9sZF9vbiUlcHJvdmlkZXJOYW1lJSVib2xkX29mZiUgb25saW5lIGFjY291bnQgJWJvbGRfb24lJWFjY291bnROdW1iZXIlJWJvbGRfb2ZmJSVsaW5rX29mZiUgdGhhdCBiZWxvbmdzIHRvICVvd25lciVcIikgKi8gJ3RyaXBzLnNlZ21lbnQuYWRkZWQtZnJvbS5hY2NvdW50JywgcGFyYW1zLCAndHJpcHMnKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiAkc2NlLnRydXN0QXNIdG1sKFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiVGhpcyB0cmlwIHNlZ21lbnQgd2FzIGF1dG9tYXRpY2FsbHkgYWRkZWQgYnkgcmV0cmlldmluZyBpdCBmcm9tICVsaW5rX29uJSVib2xkX29uJSVwcm92aWRlck5hbWUlJWJvbGRfb2ZmJSBvbmxpbmUgYWNjb3VudCAlYm9sZF9vbiUlYWNjb3VudE51bWJlciUlYm9sZF9vZmYlJWxpbmtfb2ZmJSwgd2hpY2ggYmVsb25ncyB0byAlb3duZXIlLlwiKSAqLyAndHJpcHMuc2VnbWVudC5hZGRlZC1mcm9tLmFjY291bnQuZXh0ZW5kZWQnLCBwYXJhbXMsICd0cmlwcycpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIGlmIChvcmlnaW4udHlwZSA9PT0gJ2NvbmZOdW1iZXInKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBwYXJhbXMgPSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ3Byb3ZpZGVyTmFtZSc6IG9yaWdpbi5wcm92aWRlcixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnY29uZk51bWJlcic6IG9yaWdpbi5jb25mTnVtYmVyLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdib2xkX29uJzogJzxiPicsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2JvbGRfb2ZmJzogJzwvYj4nXG4gICAgICAgICAgICAgICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAobGlzdEl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJHNjZS50cnVzdEFzSHRtbChUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIkZyb20gJWJvbGRfb24lJXByb3ZpZGVyTmFtZSUlYm9sZF9vZmYlIHVzaW5nIGNvbmZpcm1hdGlvbiBudW1iZXIgJWJvbGRfb24lJWNvbmZOdW1iZXIlJWJvbGRfb2ZmJVwiKSAqLyAndHJpcHMuc2VnbWVudC5hZGRlZC1mcm9tLmNvbmYtbnVtYmVyJywgcGFyYW1zLCAndHJpcHMnKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiAkc2NlLnRydXN0QXNIdG1sKFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiVGhpcyB0cmlwIHNlZ21lbnQgd2FzIGF1dG9tYXRpY2FsbHkgYWRkZWQgYnkgcmV0cmlldmluZyBpdCBmcm9tICVib2xkX29uJSVwcm92aWRlck5hbWUlJWJvbGRfb2ZmJSB1c2luZyBjb25maXJtYXRpb24gbnVtYmVyICVib2xkX29uJSVjb25mTnVtYmVyJSVib2xkX29mZiUuXCIpICovICd0cmlwcy5zZWdtZW50LmFkZGVkLWZyb20uY29uZi1udW1iZXIuZXh0ZW5kZWQnLCBwYXJhbXMsICd0cmlwcycpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIGlmIChvcmlnaW4udHlwZSA9PT0gJ2VtYWlsJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKG9yaWdpbi5mcm9tID09PSAyIHx8IG9yaWdpbi5mcm9tID09PSAxKSB7IC8vIGZyb20gc2Nhbm5lciBvciBwbGFuc1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxldCBwYXJhbXM7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAob3JpZ2luLmZyb20gPT09IDEpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcGFyYW1zID0ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2VtYWlsJzogb3JpZ2luLmVtYWlsLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2xpbmtfb24nOiAnJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdsaW5rX29mZic6ICcnXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH07XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcGFyYW1zID0ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2VtYWlsJzogb3JpZ2luLmVtYWlsLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2xpbmtfb24nOiAnPGEgdGFyZ2V0PVwiX2JsYW5rXCIgaHJlZj1cIicrIFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3VzZXJtYWlsYm94X3ZpZXcnKSArJ1wiPicsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnbGlua19vZmYnOiAnPC9hPidcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAobGlzdEl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICRzY2UudHJ1c3RBc0h0bWwoVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJBbiBlbWFpbCB0aGF0IHdhcyBzZW50IHRvICVsaW5rX29uJSVlbWFpbCUlbGlua19vZmYlXCIpICovICd0cmlwcy5zZWdtZW50LmFkZGVkLWZyb20uZW1haWwnLCBwYXJhbXMsICd0cmlwcycpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJHNjZS50cnVzdEFzSHRtbChUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIlRoaXMgdHJpcCBzZWdtZW50IHdhcyBhdXRvbWF0aWNhbGx5IGFkZGVkIGJ5IHBhcnNpbmcgYSByZXNlcnZhdGlvbiBlbWFpbCB0aGF0IHdhcyBzZW50IHRvICVsaW5rX29uJSVlbWFpbCUlbGlua19vZmYlLlwiKSAqLyAndHJpcHMuc2VnbWVudC5hZGRlZC1mcm9tLmVtYWlsLmV4dGVuZGVkJywgcGFyYW1zLCAndHJpcHMnKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAobGlzdEl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICRzY2UudHJ1c3RBc0h0bWwoVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJBbiBlbWFpbCB0aGF0IHdhcyBmb3J3YXJkZWQgdG8gdXNcIikgKi8gJ3RyaXBzLnNlZ21lbnQuYWRkZWQtZnJvbS51bmtub3duLWVtYWlsJywge30sICd0cmlwcycpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJHNjZS50cnVzdEFzSHRtbChUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIlRoaXMgdHJpcCBzZWdtZW50IHdhcyBhdXRvbWF0aWNhbGx5IGFkZGVkIGJ5IHBhcnNpbmcgYSByZXNlcnZhdGlvbiBlbWFpbCB0aGF0IHdhcyBmb3J3YXJkZWQgdG8gdXMuXCIpICovICd0cmlwcy5zZWdtZW50LmFkZGVkLWZyb20udW5rbm93bi1lbWFpbC5leHRlbmRlZCcsIHt9LCAndHJpcHMnKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9IGVsc2UgaWYgKG9yaWdpbi50eXBlID09PSAndHJpcGl0Jykge1xuICAgICAgICAgICAgICAgICAgICAgICAgbGV0IHBhcmFtcyA9IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnZW1haWwnOiBvcmlnaW4uZW1haWwsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2xpbmtfb24nOiAnPGEgdGFyZ2V0PVwiX2JsYW5rXCIgaHJlZj1cIicgKyBSb3V0aW5nLmdlbmVyYXRlKCdhd191c2VybWFpbGJveF92aWV3JykgKyAnXCI+JyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnbGlua19vZmYnOiAnPC9hPidcbiAgICAgICAgICAgICAgICAgICAgICAgIH07XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAobGlzdEl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJHNjZS50cnVzdEFzSHRtbChUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIllvdXIgVHJpcEl0IGFjY291bnQgJWVtYWlsJVwiKSAqLyAndHJpcHMuc2VnbWVudC5hZGRlZC1mcm9tLnRyaXBpdCcsIHBhcmFtcywgJ3RyaXBzJykpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJHNjZS50cnVzdEFzSHRtbChUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIlRoaXMgdHJpcCBzZWdtZW50IHdhcyBhdXRvbWF0aWNhbGx5IGFkZGVkIGJ5IHN5bmNocm9uaXppbmcgd2l0aCB5b3VyIFRyaXBJdCBhY2NvdW50LCAlbGlua19vbiUlZW1haWwlJWxpbmtfb2ZmJVwiKSAqLyAndHJpcHMuc2VnbWVudC5hZGRlZC1mcm9tLnRyaXBpdC5leHRlbmRlZCcsIHBhcmFtcywgJ3RyaXBzJykpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSxcblxuICAgICAgICAgICAgICAgIGNoYW5nZU5hbWU6IGZ1bmN0aW9uIChzZWdtZW50LCBlKSB7XG4gICAgICAgICAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICAgICAgc2VnbWVudC5yZW5hbWluZ1N0YXRlID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgJGh0dHAoe1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiBSb3V0aW5nLmdlbmVyYXRlKCdhd190cmF2ZWxwbGFuX3JlbmFtZScsIHsncGxhbic6IHNlZ21lbnQucGxhbklkfSksXG4gICAgICAgICAgICAgICAgICAgICAgICBkYXRhOiAkLnBhcmFtKHtuYW1lOiBzZWdtZW50Lm5hbWV9KSxcbiAgICAgICAgICAgICAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLFxuICAgICAgICAgICAgICAgICAgICAgICAgaGVhZGVyczogeydDb250ZW50LVR5cGUnOiAnYXBwbGljYXRpb24veC13d3ctZm9ybS11cmxlbmNvZGVkJ31cbiAgICAgICAgICAgICAgICAgICAgfSlbJ2ZpbmFsbHknXShmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50LnJlbmFtaW5nU3RhdGUgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlZ21lbnQuY2hhbmdlTmFtZVN0YXRlID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgcmVxdWVzdERlbGV0ZVBsYW46IGZ1bmN0aW9uKHNlZ21lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgb3ZlcmxheS5mYWRlSW4oKTtcbiAgICAgICAgICAgICAgICAgICAgLy9zZWdtZW50LmRlbGV0ZVBsYW5TdGF0ZSA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICRodHRwKHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHVybDogUm91dGluZy5nZW5lcmF0ZSgnYXdfdHJhdmVscGxhbl9kZWxldGUnLCB7J3BsYW4nOiBzZWdtZW50LnBsYW5JZH0pLFxuICAgICAgICAgICAgICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICAgICAgICAgICAgICBoZWFkZXJzOiB7J0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi94LXd3dy1mb3JtLXVybGVuY29kZWQnfVxuICAgICAgICAgICAgICAgICAgICB9KS50aGVuKGZ1bmN0aW9uIChyZXN1bHQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5yZWNhbGN1bGF0ZUFmdGVyID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzdGF0ZS5yZWxvYWQoKTtcbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBkZWxldGVQbGFuOiBmdW5jdGlvbiAoJGV2ZW50LCBzZWdtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgIGlmICgkKCRldmVudC5jdXJyZW50VGFyZ2V0KS5jbG9zZXN0KCdkaXZbZGF0YS10cmlwLXN0YXJ0XScpLmZpbmQoJy5qcy1ub3Rlcy1maWxsZWQnKS5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBjb25maXJtUG9wdXAgPSBkaWFsb2cuZmFzdENyZWF0ZShcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBUcmFuc2xhdG9yLnRyYW5zKCdjb25maXJtYXRpb24nLCB7fSwgJ3RyaXBzJyksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgVHJhbnNsYXRvci50cmFucygneW91LXN1cmUtYWxzby1kZWxldGUtbm90ZXMnLCB7fSwgJ3RyaXBzJyksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0cnVlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2NsYXNzJzogJ2J0bi1zaWx2ZXInLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ3RleHQnOiBUcmFuc2xhdG9yLnRyYW5zKCdidXR0b24ubm8nKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGljayc6ICgpID0+IGNvbmZpcm1Qb3B1cC5kZXN0cm95KCksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGFzcyc6ICdidG4tYmx1ZScsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAndGV4dCc6IFRyYW5zbGF0b3IudHJhbnMoJ2J1dHRvbi55ZXMnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGljayc6ICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25maXJtUG9wdXAuZGVzdHJveSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMucmVxdWVzdERlbGV0ZVBsYW4oc2VnbWVudCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIF0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgNDAwLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDMwMFxuICAgICAgICAgICAgICAgICAgICAgICAgKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgIHRoaXMucmVxdWVzdERlbGV0ZVBsYW4oc2VnbWVudCk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBkZWxldGVPclVuZGVsZXRlOiBmdW5jdGlvbiAoc2VnbWVudCwgaXNVbmRlbGV0ZSkge1xuICAgICAgICAgICAgICAgICAgICBzZWdtZW50LmRlbGV0ZUxvYWRlciA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICRodHRwLnBvc3QoUm91dGluZy5nZW5lcmF0ZSgnYXdfdGltZWxpbmVfZGVsZXRlJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgc2VnbWVudElkOiBzZWdtZW50LmlkLFxuICAgICAgICAgICAgICAgICAgICAgICAgdW5kZWxldGU6IGlzVW5kZWxldGUgfHwgbnVsbFxuICAgICAgICAgICAgICAgICAgICB9KSkudGhlbihyZXMgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHJlcy5kYXRhID09PSB0cnVlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gc2VnbWVudC5kZWxldGVMb2FkZXIgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUucmVjYWxjdWxhdGVBZnRlciA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnNob3dEZWxldGVkID0gaXNVbmRlbGV0ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc3RhdGUucmVsb2FkKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBjb25maXJtQ2hhbmdlczogZnVuY3Rpb24gKHNlZ21lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgc2VnbWVudC5jb25maXJtTG9hZGVyID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgJGh0dHAucG9zdChSb3V0aW5nLmdlbmVyYXRlKCdhd190aW1lbGluZV9jb25maXJtX2NoYW5nZXMnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50SWQ6IHNlZ21lbnQuaWRcbiAgICAgICAgICAgICAgICAgICAgfSkpLnRoZW4oZnVuY3Rpb24gKHJlcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgc2VnbWVudC5jb25maXJtTG9hZGVyID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAocmVzLmRhdGEgPT09IHRydWUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50LmNoYW5nZWQgPSBmYWxzZTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIHJlZnMgIzE1NTg4XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnNlZ21lbnRzXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5maWx0ZXIoXyA9PiBfLmdyb3VwID09PSBzZWdtZW50Lmdyb3VwICYmICFfLmRldGFpbHMpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5mb3JFYWNoKF8gPT4gXy5jaGFuZ2VkID0gZmFsc2UpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgZ29SZWZyZXNoOiBmdW5jdGlvbiAobGluaykge1xuICAgICAgICAgICAgICAgICAgICAkKCc8Zm9ybSBtZXRob2Q9XCJwb3N0XCIvPicpLmF0dHIoJ2FjdGlvbicsIGxpbmspLmFwcGVuZFRvKCdib2R5Jykuc3VibWl0KCk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBzY3JvbGxUb1RvcDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAkdGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKFwiaHRtbCxib2R5XCIpLnN0b3AoKS5hbmltYXRlKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzY3JvbGxUb3A6IDBcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sIDUwMCk7XG4gICAgICAgICAgICAgICAgICAgIH0sIDApO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgaG92ZXJJblNlZ21lbnQ6IGZ1bmN0aW9uIChzZWdtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5hY3RpdmVTZWdtZW50TnVtYmVyID0gc2VnbWVudC5ncm91cCA/IHNlZ21lbnQuZ3JvdXAgOiBudWxsO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgaG92ZXJPdXRTZWdtZW50OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5hY3RpdmVTZWdtZW50TnVtYmVyID0gbnVsbDtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGNyZWF0ZVBsYW46IGZ1bmN0aW9uIChzZWdtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgIHNlZ21lbnQuY3JlYXRlUGxhblN0YXRlID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgd2luZG93LnNob3dUb29sdGlwcyA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgIG92ZXJsYXkuZmFkZUluKCk7XG5cbiAgICAgICAgICAgICAgICAgICAgJGh0dHAoe1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiBSb3V0aW5nLmdlbmVyYXRlKCdhd190cmF2ZWxwbGFuX2NyZWF0ZScpLFxuICAgICAgICAgICAgICAgICAgICAgICAgZGF0YTogJC5wYXJhbSh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdXNlckFnZW50SWQ6ICgkc3RhdGVQYXJhbXMuYWdlbnRJZCAmJiAkc3RhdGVQYXJhbXMuYWdlbnRJZCAhPSAnJykgPyAkc3RhdGVQYXJhbXMuYWdlbnRJZCA6IG51bGwsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc3RhcnRUaW1lOiBzZWdtZW50LnN0YXJ0RGF0ZVxuICAgICAgICAgICAgICAgICAgICAgICAgfSksXG4gICAgICAgICAgICAgICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGhlYWRlcnM6IHsnQ29udGVudC1UeXBlJzogJ2FwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZCd9XG4gICAgICAgICAgICAgICAgICAgIH0pLnRoZW4oZnVuY3Rpb24gKHJlcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHJlcyAhPSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnNob3duRnJvbSA9IHJlcy5zdGFydFRpbWU7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUucmVjYWxjdWxhdGVBZnRlciA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICAkc3RhdGUucmVsb2FkKCk7XG4gICAgICAgICAgICAgICAgICAgIH0pLmZpbmFsbHkoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LmFmdGVyUGxhbkNyZWF0ZWQgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgLy9zZWdtZW50LmNyZWF0ZVBsYW5TdGF0ZSA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgICRzY29wZS4kb24oJ3ByaW50JywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIC8v0L/QtdGH0LDRgtGMINGC0LDQudC80LvQsNC50L3QsFxuICAgICAgICAgICAgICAgIGlmICgvXFwvcHJpbnRcXC8vLnRlc3QoJGxvY2F0aW9uLiQkYWJzVXJsKSAmJiAhJHNjb3BlLnNwaW5uZXIpIHtcbiAgICAgICAgICAgICAgICAgICAgJHdpbmRvdy5wcmludCgpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICBsZXQgZGF0YVJlcXVlc3Q7XG4gICAgICAgICAgICAkdHJhbnNpdGlvbnMub25TdWNjZXNzKHt9LCBmdW5jdGlvbiAodHJhbnNpdGlvbikge1xuICAgICAgICAgICAgICAgIGNvbnN0IHRvUGFyYW1zID0gdHJhbnNpdGlvbi5wYXJhbXMoJ3RvJyk7XG4gICAgICAgICAgICAgICAgY29uc3QgZnJvbVBhcmFtcyA9IHRyYW5zaXRpb24ucGFyYW1zKCdmcm9tJyk7XG5cbiAgICAgICAgICAgICAgICAvLyDQoNC10LTQuNGA0LXQutGCINC90LAg0LDQstGC0L7RgNC40LfQsNGG0LjRjiwg0LXRgdC70Lgg0L3QtdCw0LLRgtC+0YDQuNC30L7QstCw0L3QvdGL0Lkg0LfQsNGI0LXQuyDQvdCwINGC0LDQudC80LvQsNC50L1cbiAgICAgICAgICAgICAgICBpZiAoISgkc3RhdGUuaXMoJ3NoYXJlZCcpIHx8ICRzdGF0ZS5pcygnc2hhcmVkLXBsYW4nKSkgJiYgJCgnYVtocmVmKj1cImxvZ2luXCJdJykubGVuZ3RoICYmICEkc2NvcGUuZW1iZWRkZWREYXRhKSB7XG4gICAgICAgICAgICAgICAgICAgIGxvY2F0aW9uLmhyZWYgPSAnL2xvZ2luP0JhY2tUbz0lMkZ0aW1lbGluZSUyRic7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKHRydWUgIT09ICRzY29wZS5yZWNhbGN1bGF0ZUFmdGVyICYmIChmcm9tUGFyYW1zLm9wZW5TZWdtZW50ICE9PSB0b1BhcmFtcy5vcGVuU2VnbWVudCAmJiBmcm9tUGFyYW1zLmFnZW50SWQgJiYgZnJvbVBhcmFtcy5hZ2VudElkID09PSB0b1BhcmFtcy5hZ2VudElkKSkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgJHNjb3BlLnNob3dEZWxldGVkID0gJHN0YXRlUGFyYW1zLnNob3dEZWxldGVkIHx8ICRzY29wZS5zaG93RGVsZXRlZDtcblxuICAgICAgICAgICAgICAgIGNvbnN0IGFnZW50TWF0Y2ggPSBsb2NhdGlvbi5zZWFyY2gubWF0Y2goL1xcP2FnZW50SWQ9KFxcZCspLyk7XG4gICAgICAgICAgICAgICAgaWYgKGFnZW50TWF0Y2gpIHtcbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmFnZW50SWQgPSBhZ2VudE1hdGNoWzFdO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5hZ2VudElkID0gJHN0YXRlUGFyYW1zLmFnZW50SWQgfHwgJyc7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgYWRkTWFpbGJveC5zZXRPd25lcigkc2NvcGUuYWdlbnRJZCk7XG5cbiAgICAgICAgICAgICAgICAvKlxuICAgICAgICAgICAgICAgICAgICDQndGD0LbQvdC+INC/0LXRgNC10LfQsNCz0YDRg9C30LjRgtGMINGC0LDQudC80LvQsNC50L0g0LIg0YHQu9GD0YfQsNGP0YU6XG4gICAgICAgICAgICAgICAgICAgICDRgdC80LXQvdGLINGB0L7QsdGB0YLQstC10L3QvdC40LrQsFxuICAgICAgICAgICAgICAgICAgICAg0L/RgNC4INC/0LXRgNCy0L7QuSDQt9Cw0LPRgNGD0LfQutC1ICjQvdC1INC30LDQs9GA0YPQttC10L3RiyDRgdC10LPQvNC10L3RgtGLKVxuICAgICAgICAgICAgICAgICAgICAg0YPQtNCw0LvQtdC90LjQtSBiZWZvcmUg0LjQtyDQsNC00YDQtdGB0L3QvtC5INGB0YLRgNC+0LrQuFxuICAgICAgICAgICAgICAgICAqL1xuICAgICAgICAgICAgICAgIGlmIChmcm9tUGFyYW1zLmFnZW50SWQgIT09IHRvUGFyYW1zLmFnZW50SWQgfHwgISRzY29wZS5zZWdtZW50cy5sZW5ndGggfHwgKGZyb21QYXJhbXMuYmVmb3JlICYmICF0b1BhcmFtcy5iZWZvcmUpKSB7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5zZWdtZW50cy5sZW5ndGggPSAwO1xuICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3Bpbm5lciA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgIC8vINC10YHQu9C4INC+0YHRgtCw0LLQuNGC0YwgYWZ0ZXIsINGC0L4g0YLQsNC50LzQu9Cw0LnQvSDQv9C10YDQtdC30LDQs9GA0YPQt9C40YLRgdGPXG4gICAgICAgICAgICAgICAgICAgIC8vINC90LAg0L3Rg9C20L3QvtC8INC+0YLRgdC60YDQvtC70LvQuNGA0L7QstCw0L3QvdC+0Lwg0LzQtdGB0YLQtSAo0YHQvtGF0YDQsNC90LXQvdC40LUg0L/QvtC30LjRhtC40LgpINCyINC/0YDQvtGI0LvQvtC8XG4gICAgICAgICAgICAgICAgICAgIGlmICghJHNjb3BlLmZvcmNlQWZ0ZXIpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5hZnRlciA9IHVuZGVmaW5lZDtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIC8vINCh0L7RhdGA0LDQvdC10L3QuNC1INC/0L7Qt9C40YbQuNC4INC/0LXRgNC10LQg0L/QtdGA0LXQs9GA0YPQt9C60L7QuSDRgtCw0LnQvNC70LDQudC90LBcbiAgICAgICAgICAgICAgICAvLyDQv9GA0LggU2hvdy9IaWRlIGRlbGV0ZWQg0LAg0YLQsNC6INC20LUg0LjQvdGL0YUg0LTQtdC50YHRgtCy0LjRj9GFINC90LDQtCDRgdC10LPQvNC10L3RgtCw0LzQuCAocmVjYWxjdWxhdGVBZnRlcilcbiAgICAgICAgICAgICAgICBpZiAoXG4gICAgICAgICAgICAgICAgICAgIChcbiAgICAgICAgICAgICAgICAgICAgICAgIHR5cGVvZiBmcm9tUGFyYW1zLnNob3dEZWxldGVkICE9PSAndW5kZWZpbmVkJyAmJiB0eXBlb2YgdG9QYXJhbXMuc2hvd0RlbGV0ZWQgIT09ICd1bmRlZmluZWQnXG4gICAgICAgICAgICAgICAgICAgICAgICAmJiBmcm9tUGFyYW1zLnNob3dEZWxldGVkICE9PSB0b1BhcmFtcy5zaG93RGVsZXRlZFxuICAgICAgICAgICAgICAgICAgICApXG4gICAgICAgICAgICAgICAgICAgIHx8ICRzY29wZS5yZWNhbGN1bGF0ZUFmdGVyXG4gICAgICAgICAgICAgICAgKSB7XG5cbiAgICAgICAgICAgICAgICAgICAgY29uc29sZS5sb2coJ1JlY2FsY3VsYXRlIGFmdGVyLi4uJyk7XG5cbiAgICAgICAgICAgICAgICAgICAgaWYgKCRzY29wZS5zZWdtZW50cy5sZW5ndGgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIGFmdGVyINCx0YPQtNC10YIg0LjRgdC/0L7Qu9GM0LfQvtCy0LDQvSDQtNC70Y8g0LfQsNC/0YDQvtGB0LAg0LTQsNC90L3Ri9GFINGBINGB0LXRgNCy0LXRgNCwINC90LDRh9C40L3QsNGPINGBINGN0YLQvtC5INC00LDRgtGLXG4gICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuYWZ0ZXIgPSBwYXJzZUludCgkc2NvcGUuc2VnbWVudHNbMF0uc3RhcnREYXRlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5zaG93blNlZ21lbnRzID0gJGZpbHRlcignZmlsdGVyJykoJHNjb3BlLnNlZ21lbnRzLCB7dmlzaWJsZTogdHJ1ZX0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCRzY29wZS5zaG93blNlZ21lbnRzLmxlbmd0aCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5mb3JjZUFmdGVyID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyBzaG93bkZyb20g0YEg0LrQsNC60L7Qs9C+INCy0YDQtdC80LXQvdC4INC/0L7QutCw0LfRi9Cy0LDRgtGMINGB0LXQs9C80LXQvdGC0YsuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8g0J/QvtC70YPRh9C10L3QvdGL0LUg0YEg0YHQtdGA0LLQtdGA0LAg0YHQtdCz0LzQtdC90YLRiyDQsdGD0LTRg9GCINGB0YDQsNCy0L3QuNCy0LDRgtGM0YHRjyDRgSDRjdGC0L7QuSDQtNCw0YLQvtC5INC/0L7QutCw0LfRi9Cy0LDRgtGM0YHRjy/RgdC60YDRi9Cy0LDRgtGM0YHRj1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICgkc2NvcGUuc2hvd25Gcm9tICYmICRzY29wZS5hZnRlciA+ICRzY29wZS5zaG93bkZyb20pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmFmdGVyID0gJHNjb3BlLnNob3duRnJvbTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc2hvd25Gcm9tID0gcGFyc2VJbnQoJHNjb3BlLnNob3duU2VnbWVudHNbMF0uc3RhcnREYXRlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICBpZiAoISRzY29wZS5yZWNhbGN1bGF0ZUFmdGVyKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUucGFzdFNwaW5uZXIgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmNvbnRhaW5lckhlaWdodCA9ICQoJy50cmlwJykuaGVpZ2h0KCk7XG5cbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICB2YXIgYW5jaG9yID0gbnVsbDtcbiAgICAgICAgICAgICAgICB2YXIgc2hvd25TZWdtZW50cyA9ICRmaWx0ZXIoJ2ZpbHRlcicpKCRzY29wZS5zZWdtZW50cywge3Zpc2libGU6IHRydWV9KTtcbiAgICAgICAgICAgICAgICB2YXIgb2Zmc2V0VG9wID0gMDtcbiAgICAgICAgICAgICAgICBpZiAoc2hvd25TZWdtZW50cy5sZW5ndGgpIHtcbiAgICAgICAgICAgICAgICAgICAgYW5jaG9yID0gc2hvd25TZWdtZW50c1swXTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGFuY2hvckVsZW1lbnQgPSAkKCdkaXZbZGF0YS1pZD1cIicgKyBhbmNob3IuaWQgKyAnXCJdJyk7XG4gICAgICAgICAgICAgICAgICAgIGlmIChhbmNob3JFbGVtZW50Lmxlbmd0aClcbiAgICAgICAgICAgICAgICAgICAgICAgIG9mZnNldFRvcCA9IGFuY2hvckVsZW1lbnQub2Zmc2V0KCkudG9wO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlXG4gICAgICAgICAgICAgICAgICAgIG9mZnNldFRvcCA9IDA7XG5cbiAgICAgICAgICAgICAgICAvLyDQldGB0LvQuCDQsiDQvNCw0YHRgdC40LLQtSDRgdC10LPQvNC10L3RgtC+0LIg0LXRgdGC0Ywg0L3QtdC/0L7QutCw0LfQsNC90L3Ri9C1INC+0LHRitC10LrRgtGLIC0g0L/QvtC60LDQt9GL0LLQsNC10LxcbiAgICAgICAgICAgICAgICAvLyDQu9C40LHQviwg0LXRgdC70Lgg0YHRgtGA0LDQvdC40YbRgyDRgtCw0LnQvNC70LDQudC90LAg0L/QtdGA0LXQs9GA0YPQttCw0Y7RgiDRgSDQv9Cw0YDQsNC80LXRgtGA0L7QvCBiZWZvcmUgLSDQvtGC0LrRgNGL0LLQsNC10LwgZnV0dXJlXG5cbiAgICAgICAgICAgICAgICAvLyDQv9C+0LrQsNC30YvQstCw0LXQvCDRgdC60YDRi9GC0YvQtSDRgdC10LPQvNC10L3RgtGLICjQs9GA0YPQt9C40Lwg0L/RgNC+INC30LDQv9Cw0YEpLiDQndCw0L/RgNC40LzQtdGALCDQv9GA0Lgg0YHQutGA0L7Qu9C70LjQvdCz0LUg0LIg0L/RgNC+0YjQu9C+0LUg0L7QtNC90L7QstGA0LXQvNC10L3QvdC+INC30LDQv9GA0LDRiNC40LLQsNGPXG4gICAgICAgICAgICAgICAgLy8g0LTQvtC/0L7Qu9C90LjRgtC10LvRjNC90YvQtSDQtNCw0L3QvdGL0LUg0YEg0YHQtdGA0LLQtdGA0LBcbiAgICAgICAgICAgICAgICBpZiAoJHNjb3BlLnNlZ21lbnRzLmxlbmd0aCAmJiAhJHNjb3BlLmFmdGVyICYmICEkc2NvcGUuc2hvd25Gcm9tKSB7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5zZWdtZW50cy5tYXAoZnVuY3Rpb24gKHNlZ21lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlZ21lbnQudmlzaWJsZSA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50LmZ1dHVyZSA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAvLyDQt9Cw0LPRgNGD0LfQuNGC0Ywg0YLQsNC50LzQu9Cw0LnQvSDRgSDRg9C60LDQt9Cw0L3QvdC+0Lkg0L/QvtC30LjRhtC40LhcbiAgICAgICAgICAgICAgICB9IGVsc2UgaWYgKCFmcm9tUGFyYW1zLm9wZW5TZWdtZW50RGF0ZSAmJiB0b1BhcmFtcy5vcGVuU2VnbWVudERhdGUgPiAwICYmIHRvUGFyYW1zLm9wZW5TZWdtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5hZnRlciA9ICRzdGF0ZVBhcmFtcy5vcGVuU2VnbWVudERhdGU7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5zaG93bkZyb20gPSAkc3RhdGVQYXJhbXMub3BlblNlZ21lbnREYXRlO1xuICAgICAgICAgICAgICAgICAgICAkc2NvcGUuZm9yY2VBZnRlciA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICRzdGF0ZVBhcmFtcy5vcGVuU2VnbWVudERhdGUgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICAkc3RhdGUucmVsb2FkKCk7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICAvLyDQv9C10YDQtdC30LDQs9GA0YPQt9C60LAg0L/RgNC4INC90LDQu9C40YfQuNC4IGJlZm9yZSAo0L3QtdC/0L7QvdGP0YLQvdC+INCz0LTQtSDQuNGB0L/QvtC70YzQt9GD0LXRgtGB0Y8pXG4gICAgICAgICAgICAgICAgfSBlbHNlIGlmICgkc3RhdGVQYXJhbXMuYmVmb3JlKSB7XG4gICAgICAgICAgICAgICAgICAgICRzdGF0ZVBhcmFtcy5iZWZvcmUgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICAkc3RhdGUucmVsb2FkKCk7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAvLyDQn9C+0LrQsNC30YvQstCw0LXQvCDRgdC/0LjQvdC90LXRgCDQv9GA0Lgg0L3QsNC20LDRgtC40LggUGFzdCwg0LvQuNCx0L4g0L/RgNC4IFNob3cvSGlkZSBkZWxldGVkXG4gICAgICAgICAgICAgICAgLy8g0LjQu9C4INGD0LTQsNC70LXQvdC40Lgv0LLQvtGB0YLQsNC90L7QstC70LXQvdC40Lgg0YHQtdCz0LzQtdC90YLQvtCyXG4gICAgICAgICAgICAgICAgaWYgKCRzdGF0ZVBhcmFtcy5iZWZvcmUpXG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5wYXN0U3Bpbm5lciA9IHRydWU7XG5cbiAgICAgICAgICAgICAgICAvLyDQvtGC0LrQu9GO0YfQuNC7INCw0L3QuNC80LDRhtC40Y4g0L/RgNC4INGB0LrRgNC+0LvQtSDQsiDQv9GA0L7RiNC70L7QtVxuICAgICAgICAgICAgICAgIC8vIGlmICgkc2NvcGUuc2VnbWVudHMubGVuZ3RoID4gMCAmJiAodG9QYXJhbXMuYmVmb3JlIHx8IHRvUGFyYW1zLmFmdGVyKSAmJiAhJHNjb3BlLnJlY2FsY3VsYXRlQWZ0ZXIpIHtcbiAgICAgICAgICAgICAgICAvLyAgICAgLy8ga2VlcCBwb3NpdGlvbiB3aGVuIGxvYWRpbmcgcGFzdFxuICAgICAgICAgICAgICAgIC8vICAgICBpZiAoIWFuY2hvcilcbiAgICAgICAgICAgICAgICAvLyAgICAgICAgIGFuY2hvciA9ICRzY29wZS5zZWdtZW50c1skc2NvcGUuc2VnbWVudHMubGVuZ3RoIC0gMV07XG4gICAgICAgICAgICAgICAgLy9cbiAgICAgICAgICAgICAgICAvLyAgICAgdmFyIGlkID0gYW5jaG9yLmlkO1xuICAgICAgICAgICAgICAgIC8vICAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAvLyAgICAgICAgIHZhciAkZWwgPSAkKCdkaXZbZGF0YS1pZD1cIicgKyBpZCArICdcIl0nKTtcbiAgICAgICAgICAgICAgICAvLyAgICAgICAgIGlmICgkZWwubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgLy8gICAgICAgICAgICAgJCgnaHRtbCwgYm9keScpLnNjcm9sbFRvcCgkZWwub2Zmc2V0KCkudG9wIC0gb2Zmc2V0VG9wKTtcbiAgICAgICAgICAgICAgICAvLyAgICAgICAgICAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAvLyAgICAgICAgICAgICAgICAgJCgnaHRtbCwgYm9keScpLmFuaW1hdGUoe1xuICAgICAgICAgICAgICAgIC8vICAgICAgICAgICAgICAgICAgICAgc2Nyb2xsVG9wOiAkKCdkaXZbZGF0YS1pZD1cIicgKyBpZCArICdcIl0nKS5vZmZzZXQoKS50b3AgLSBvZmZzZXRUb3AgLSAkKHdpbmRvdykuaGVpZ2h0KCkgKiAwLjdcbiAgICAgICAgICAgICAgICAvLyAgICAgICAgICAgICAgICAgfSwgMTAwMCk7XG4gICAgICAgICAgICAgICAgLy8gICAgICAgICAgICAgfSwgMjAwKTtcbiAgICAgICAgICAgICAgICAvLyAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAvLyAgICAgfSwgMTApO1xuICAgICAgICAgICAgICAgIC8vIH1cblxuICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgZGF0YVJlcXVlc3QgPT0gJ29iamVjdCcgJiYgT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKGRhdGFSZXF1ZXN0LCAnY2FuY2VsJykpIHtcbiAgICAgICAgICAgICAgICAgICAgZGF0YVJlcXVlc3QuY2FuY2VsKCk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgLy8g0JfQsNCz0YDRg9C30LrQsCDQtNCw0L3QvdGL0YUgKCsg0L/QvtC/0YDQsNCy0LrQsCDQstGA0LXQvNC10L3QuCDQtNC70Y8g0LrQvtGA0YDQtdC60YLQvdC+0LPQviDQvtGC0L7QsdGA0LDQttC10L3QuNGPLCDQtdGB0LvQuCDQsdGD0LTRg9GJ0LXQtSDQvdCw0YfQuNC90LDQtdGC0YHRjyDRgSDRgtGA0LDQstC10Lst0L/Qu9Cw0L3QsClcbiAgICAgICAgICAgICAgICBkYXRhUmVxdWVzdCA9ICR0aW1lbGluZURhdGEuZmV0Y2goJHNjb3BlLmFmdGVyKTtcbiAgICAgICAgICAgICAgICBkYXRhUmVxdWVzdC50aGVuKGZ1bmN0aW9uIChkYXRhKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKCdsb2FkZWQnKTtcbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmRlbGV0ZUxvYWRlciA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAkc2NvcGUuYWdlbnRzID0gZGF0YS5hZ2VudHMgfHwgW107XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5zaGFyYWJsZUFnZW50cyA9ICRzY29wZS5hZ2VudHMuZmlsdGVyKGFnZW50ID0+IGFnZW50LnNoYXJhYmxlKTtcbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmNhbkFkZCA9IGRhdGEuY2FuQWRkIHx8IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAkc2NvcGUubm9Gb3JlaWduRmVlc0NhcmRzID0gZGF0YS5ub0ZvcmVpZ25GZWVzQ2FyZHMgfHwgW107XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5vcHRpb25zID0gZGF0YS5vcHRpb25zIHx8IHt9O1xuICAgICAgICAgICAgICAgICAgICBvdmVybGF5LmZhZGVPdXQoKTtcblxuICAgICAgICAgICAgICAgICAgICBpZiAoJHNjb3BlLmNvbnRhaW5lckhlaWdodClcbiAgICAgICAgICAgICAgICAgICAgICAgICQoJy50cmlwJykuY3NzKCdtaW4taGVpZ2h0JywgJHNjb3BlLmNvbnRhaW5lckhlaWdodCk7XG5cbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnNlZ21lbnRzID0gJHNjb3BlLmFmdGVyID8gZGF0YS5zZWdtZW50cyA6IGRhdGEuc2VnbWVudHMuY29uY2F0KCRzY29wZS5zZWdtZW50cyk7XG4gICAgICAgICAgICAgICAgICAgIHZhciBub3cgPSBuZXcgRGF0ZSgpO1xuXG4gICAgICAgICAgICAgICAgICAgIGZvciAodmFyIGkgPSAkc2NvcGUuc2VnbWVudHMubGVuZ3RoOyBpID4gMDsgaS0tKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgc2VnbWVudCA9ICRzY29wZS5zZWdtZW50c1tpIC0gMV07XG4gICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50LmZ1dHVyZSA9IHNlZ21lbnQuc3RhcnREYXRlID4gKERhdGUuVVRDKG5vdy5nZXRGdWxsWWVhcigpLCBub3cuZ2V0TW9udGgoKSwgbm93LmdldERhdGUoKSwgMCwgMCwgMCwgMCkgLyAxMDAwKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHNlZ21lbnQuZnV0dXJlIHx8ICgkc2NvcGUuc2VnbWVudHNbaV0gJiYgJHNjb3BlLnNlZ21lbnRzW2ldLnZpc2libGUgJiYgIXNlZ21lbnQuYnJlYWtBZnRlcikpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50LnZpc2libGUgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAvLyDQvdC+0LLRi9C1INC/0L7QtNCz0YDRg9C20LXQvdGL0LUg0YHQtdCz0LzQtdC90YLRiyDQvdC1INC/0L7QutCw0LbRg9GC0YHRjyDQsCDQsdGD0LTRg9GCINGB0LrRgNGL0YLRiyDQtNC+INC00LDQu9GM0L3QtdC50YjQtdCz0L4g0YHQutGA0L7Qu9CwINCyINC/0YDQvtGI0LvQvtC1XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoJHNjb3BlLnNob3duRnJvbSAmJiBzZWdtZW50LnN0YXJ0RGF0ZSA+PSAkc2NvcGUuc2hvd25Gcm9tKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlZ21lbnQudmlzaWJsZSA9IHRydWU7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIGlmICgkc3RhdGUuaXMoJ3NoYXJlZCcpIHx8ICRzdGF0ZS5pcygnc2hhcmVkLXBsYW4nKSB8fCAkc3RhdGUuaXMoJ2l0aW5lcmFyaWVzJykgfHwgJHNjb3BlLmVtYmVkZGVkRGF0YSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWdtZW50LnZpc2libGUgPSB0cnVlO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoc2VnbWVudC5kZXRhaWxzICYmIHNlZ21lbnQuZGV0YWlscy5tb25pdG9yZWRTdGF0dXMpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnNlZ21lbnRzXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuZmlsdGVyKGZ1bmN0aW9uIChpdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGl0ZW0uZ3JvdXAgPT0gc2VnbWVudC5ncm91cDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuZm9yRWFjaChmdW5jdGlvbiAoaXRlbSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICgtMSA9PT0gJC5pbkFycmF5KGl0ZW0uaWQuc3Vic3RyKDAsIDIpLCBbJ0NPJywgJ0wuJ10pKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICghaXRlbS5kZXRhaWxzKSBpdGVtLmRldGFpbHMgPSB7fTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaXRlbS5kZXRhaWxzLm1vbml0b3JlZFN0YXR1cyA9IHNlZ21lbnQuZGV0YWlscy5tb25pdG9yZWRTdGF0dXM7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5zZWdtZW50cy5mb3JFYWNoKGZ1bmN0aW9uIChpdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCdDSScgPT09IGl0ZW0uaWQuc3Vic3RyKDAsIDIpICYmIE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbCgkc2NvcGUub3B0aW9ucywgJ3Jlc2VydmF0aW9uJykpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaXRlbS5zZXRPcHRpb25zKCRzY29wZS5vcHRpb25zLnJlc2VydmF0aW9uKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5oYXZlRnV0dXJlU2VnbWVudHMgPSAkZmlsdGVyKCdmaWx0ZXInKSgkc2NvcGUuc2VnbWVudHMsIHtmdXR1cmU6IHRydWV9KS5sZW5ndGggPiAwO1xuXG4gICAgICAgICAgICAgICAgICAgIC8vINCe0LHQvdC+0LLQu9C10L3QuNC1INGB0YfQtdGC0YfQuNC60L7QslxuICAgICAgICAgICAgICAgICAgICBpZiAoJHN0YXRlLmlzKCd0aW1lbGluZScpICYmICEkc2NvcGUuZW1iZWRkZWREYXRhKSB7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIEZvcndhcmRpbmcgZW1haWxcbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5mb3J3YXJkaW5nRW1haWwgPSBkYXRhLmZvcndhcmRpbmdFbWFpbDtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5tYWlsYm94ZXMgPSB7fTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHRvdGFscyA9IDA7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgY291bnRzID0ge307XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIGFuZ3VsYXIuZm9yRWFjaCgkc2NvcGUuYWdlbnRzLCBmdW5jdGlvbiAoYWdlbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUubWFpbGJveGVzW2FnZW50LmlkXSA9IGFnZW50Lm1haWxib3hlcztcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb3VudHNbYWdlbnQuaWRdID0gYWdlbnQuY291bnQ7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdG90YWxzID0gdG90YWxzICsgYWdlbnQuY291bnQ7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyBpZiAoYWdlbnQuaWQgPT0gJ215JylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyAgICAgJCgnLnVzZXItYmxrIGFbZGF0YS1pZF0nKS5maXJzdCgpLmZpbmQoJy5jb3VudCcpLnRleHQoYWdlbnQuY291bnQpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGVsc2UgaWYgKGFnZW50LmNvdW50ID49IDApXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gICAgICQoJy51c2VyLWJsayBhW2RhdGEtaWQ9JyArIGFnZW50LmlkICsgJ10nKS5maW5kKCcuY291bnQnKS50ZXh0KGFnZW50LmNvdW50KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvL1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGlmIChhZ2VudC5jb3VudCA+PSAwKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vICAgICB0b3RhbHMgPSB0b3RhbHMgKyBhZ2VudC5jb3VudDtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAkKGRvY3VtZW50KS50cmlnZ2VyKCdwZXJzb25zLnVwZGF0ZScsIGNvdW50cyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKCcjdHJpcHMtY291bnQnKS50ZXh0KGNvdW50cy5teSk7XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICBpZiAoISRzdGF0ZS5pcygnaXRpbmVyYXJpZXMnKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmZ1bGxOYW1lID0gJHNjZS50cnVzdEFzSHRtbChUcmFuc2xhdG9yLnRyYW5zKCd0aW1lbGluZS5vZi5uYW1lJywge25hbWU6ICc8Yj4nICsgZGF0YS5mdWxsTmFtZSArICc8L2I+J30pKTtcbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5mdWxsTmFtZSA9ICRzY2UudHJ1c3RBc0h0bWwoVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJSZXRyaWV2ZWQgdHJhdmVsIHBsYW5zXCIpICovICdyZXRyaWV2ZWQudHJhdmVscGxhbnMnKSk7XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAvLyDQp9C40YHRgtC40Lwg0YHQvtGB0YLQvtGP0L3QuNGPXG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5zcGlubmVyID0gJHNjb3BlLnBhc3RTcGlubmVyID0gJHNjb3BlLmFmdGVyID0gJHNjb3BlLnNob3duU2VnbWVudHMgPSAkc2NvcGUuc2hvd25Gcm9tID0gJHNjb3BlLmZvcmNlQWZ0ZXIgPSAkc2NvcGUucmVjYWxjdWxhdGVBZnRlciA9IHVuZGVmaW5lZDtcbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmFnZW50Lm5ld293bmVyID0gJyc7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5hZ2VudC5jb3B5ID0gZmFsc2U7XG5cblxuICAgICAgICAgICAgICAgICAgICAvLyBSZXdyYXBwaW5nXG4gICAgICAgICAgICAgICAgICAgICR0aW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICQoJy53cmFwcGVyJykucmVtb3ZlKCk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBpdGVtcyA9ICQoJy50cmlwLWxpc3QgPiBkaXYnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGl0ZW1zLmVhY2goZnVuY3Rpb24gKGlkLCBpdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHByZXYgPSAkKGl0ZW0pLnByZXYoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAocHJldi5oYXNDbGFzcygndHJpcC1ibGsnKSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcHJldi5hZGRCYWNrKCkud3JhcEFsbCgnPGRpdiBjbGFzcz1cInVuZHJhZ2dhYmxlIHVuZHJvcHBhYmxlIHdyYXBwZXJcIiAvPicpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICQoXCIudWktc29ydGFibGVcIikuc29ydGFibGUoXCJyZWZyZXNoXCIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgY3VzdG9taXplci5pbml0SHRtbDVJbnB1dHMoJy50cmlwJyk7XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAvLyDQn9C+0LTRgdCy0LXRgtC60LAg0L/QvtC70YzQt9C+0LLQsNGC0LXQu9GPINCyINC70LXQstC+0Lwg0LzQtdC90Y4sINGH0LXQuSDRgtCw0LnQvNC70LDQudC9INC30LDQs9GA0YPQt9C40LvQuFxuICAgICAgICAgICAgICAgIGlmICghJHN0YXRlLmlzKCdpdGluZXJhcmllcycpKVxuICAgICAgICAgICAgICAgICAgICAkKHdpbmRvdykudHJpZ2dlcigncGVyc29uLmFjdGl2YXRlJywgJHN0YXRlUGFyYW1zLmFnZW50SWQgfHwgJ215Jyk7XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHZhciBhZ2VudElkID0gbG9jYXRpb24uaHJlZi5tYXRjaCgvYWdlbnRJZD0oXFxkKykvKTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGFnZW50SWQgJiYgYWdlbnRJZFsxXXwwKVxuICAgICAgICAgICAgICAgICAgICAgICAgJCh3aW5kb3cpLnRyaWdnZXIoJ3BlcnNvbi5hY3RpdmF0ZScsIGFnZW50SWRbMV0pO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAkc2NvcGUuJG9uKCd0aW1lbGluZUZpbmlzaFJlbmRlcicsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgaGlkZVRvb2x0aXBzO1xuXG4gICAgICAgICAgICAgICAgY29uc3QgcmVxdWVzdFBsYW5Nb3ZlID0gKHVpLCBuZXh0U2VnbWVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICBvdmVybGF5LmZhZGVJbigpO1xuICAgICAgICAgICAgICAgICAgICAkdHJhdmVsUGxhbnMubW92ZSh7XG4gICAgICAgICAgICAgICAgICAgICAgICBwbGFuSWQ6IGFuZ3VsYXIuZWxlbWVudCh1aS5pdGVtKS5zY29wZSgpLnNlZ21lbnQucGxhbklkLFxuICAgICAgICAgICAgICAgICAgICAgICAgbmV4dFNlZ21lbnRJZDogbmV4dFNlZ21lbnQuZGF0YSgnaWQnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgIG5leHRTZWdtZW50VHM6IGFuZ3VsYXIuZWxlbWVudChuZXh0U2VnbWVudCkuc2NvcGUoKS4kcGFyZW50LnNlZ21lbnQuc3RhcnREYXRlLFxuICAgICAgICAgICAgICAgICAgICAgICAgdHlwZTogYW5ndWxhci5lbGVtZW50KHVpLml0ZW0pLnNjb3BlKCkuc2VnbWVudC50eXBlXG4gICAgICAgICAgICAgICAgICAgIH0pLnRoZW4oZnVuY3Rpb24gKHJlc3ApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5zaG93bkZyb20gPSByZXNwLmRhdGEuc3RhcnRUaW1lO1xuICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnJlY2FsY3VsYXRlQWZ0ZXIgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgJHN0YXRlLnRyYW5zaXRpb25Ubygkc3RhdGUuY3VycmVudCwgJHN0YXRlUGFyYW1zLCB7cmVsb2FkOiB0cnVlLCBpbmhlcml0OiB0cnVlfSk7XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICQoJy50cmlwLWxpc3QnKS5zb3J0YWJsZSh7XG4gICAgICAgICAgICAgICAgICAgIGNhbmNlbDogJy51bmRyYWdnYWJsZSxpbnB1dCcsXG4gICAgICAgICAgICAgICAgICAgIGF4aXM6IFwieVwiLFxuICAgICAgICAgICAgICAgICAgICBoYW5kbGU6ICcuZHJhZ2dhYmxlJyxcbiAgICAgICAgICAgICAgICAgICAgaXRlbXM6ICc+IGRpdjpub3QoLnVuZHJvcHBhYmxlKScsXG4gICAgICAgICAgICAgICAgICAgIHJldmVydDogdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgb3BhY2l0eTogMC43LFxuICAgICAgICAgICAgICAgICAgICBzdGFydDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgaGlkZVRvb2x0aXBzID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgc3RvcDogZnVuY3Rpb24gKGV2ZW50LCB1aSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgaGlkZVRvb2x0aXBzID0gZmFsc2U7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBlbGVtZW50cyA9ICQoJy50cmlwLWxpc3QnKS5maW5kKCdkaXZbZGF0YS1pZF0nKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciB1aUluZGV4ID0gZWxlbWVudHMuaW5kZXgoJCh1aS5pdGVtKS5maW5kKCdkaXYnKS5maXJzdCgpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBuZXh0U2VnbWVudCwgJG5vdGVzV3JhcCA9IG51bGw7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoYW5ndWxhci5lbGVtZW50KHVpLml0ZW0pLnNjb3BlKCkuc2VnbWVudC50eXBlID09ICdwbGFuU3RhcnQnKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbmV4dFNlZ21lbnQgPSAkKGVsZW1lbnRzW3VpSW5kZXggKyAxXSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoJChuZXh0U2VnbWVudCkuaXMoJ1tkYXRhLXRyaXAtZW5kXScpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRub3Rlc1dyYXAgPSAkKG5leHRTZWdtZW50KS5wYXJlbnQoKS5wcmV2KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBuZXh0U2VnbWVudCA9ICQoZWxlbWVudHNbdWlJbmRleCAtIDFdKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkbm90ZXNXcmFwID0gJChuZXh0U2VnbWVudCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChudWxsICE9PSAkbm90ZXNXcmFwICYmICRub3Rlc1dyYXAuZmluZCgnLmpzLW5vdGVzLWZpbGxlZCcpLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBjb25maXJtUG9wdXAgPSBkaWFsb2cuZmFzdENyZWF0ZShcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgVHJhbnNsYXRvci50cmFucygnY29uZmlybWF0aW9uJywge30sICd0cmlwcycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBUcmFuc2xhdG9yLnRyYW5zKCd5b3Utc3VyZS1hbHNvLWRlbGV0ZS1ub3RlcycsIHt9LCAndHJpcHMnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgW1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGFzcyc6ICdidG4tc2lsdmVyJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAndGV4dCc6IFRyYW5zbGF0b3IudHJhbnMoJ2J1dHRvbi5ubycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGljayc6ICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uZmlybVBvcHVwLmRlc3Ryb3koKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnLnRyaXAtbGlzdCcpLnNvcnRhYmxlKCdjYW5jZWwnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnY2xhc3MnOiAnYnRuLWJsdWUnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICd0ZXh0JzogVHJhbnNsYXRvci50cmFucygnYnV0dG9uLnllcycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGljayc6ICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uZmlybVBvcHVwLmRlc3Ryb3koKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVxdWVzdFBsYW5Nb3ZlKHVpLCBuZXh0U2VnbWVudCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIF0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDQwMCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMzAwXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIHJlcXVlc3RQbGFuTW92ZSh1aSwgbmV4dFNlZ21lbnQpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgLm9uKCdjbGljaycsICcuZGV0YWlscy1leHRwcm9wZXJ0aWVzLXJvdyBhW2hyZWY9XCIjY29sbGFwc2VcIl0nLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICBsZXQgJHJvd1BhcmVudCA9ICQodGhpcykucGFyZW50KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoJCh0aGlzKS5oYXNDbGFzcygncHJvcGVydGllcy12YWx1ZS1jb2xsYXBzZS1uYW1lJykpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkcm93UGFyZW50ID0gJHJvd1BhcmVudC5wYXJlbnQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICRyb3dQYXJlbnQudG9nZ2xlQ2xhc3MoJ2RldGFpbC1wcm9wZXJ0eS1leHBhbmRlZCcpO1xuICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgIGlmICgvXFwvcHJpbnRcXC8vLnRlc3QoJGxvY2F0aW9uLiQkYWJzVXJsKSAmJiAhJHNjb3BlLnNwaW5uZXIpIHtcbiAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoJHdpbmRvdy5wcmludCwgMTAwMCk7XG4gICAgICAgICAgICAgICAgICAgIC8vJHdpbmRvdy5wcmludCgpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XSlcbiAgICAgICAgLmNvbnRyb2xsZXIoJ2ZsYXNoTWVzc2FnZVRyaXBpdCcsIFtcbiAgICAgICAgICAgICckc2NvcGUnLFxuICAgICAgICAgICAgZnVuY3Rpb24gKCRzY29wZSkge1xuICAgICAgICAgICAgICAgIGRpYWxvZy5mYXN0Q3JlYXRlKFxuICAgICAgICAgICAgICAgICAgICBUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIkltcG9ydCBUcmlwSXQgUmVzZXJ2YXRpb25zXCIpICovICd0aW1lbGluZS50cmlwaXRfcG9wdXAudGl0bGUnKSxcbiAgICAgICAgICAgICAgICAgICAgVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJXZSBkaWQgbm90IGZpbmQgYW55IHRyYXZlbCByZXNlcnZhdGlvbnMgaW4geW91ciBUcmlwSXQgYWNjb3VudC5cIikgKi8gJ3RpbWVsaW5lLnRyaXBpdF9wb3B1cC5jb250ZW50JyksXG4gICAgICAgICAgICAgICAgICAgIHRydWUsXG4gICAgICAgICAgICAgICAgICAgIHRydWUsXG4gICAgICAgICAgICAgICAgICAgIFtcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAndGV4dCc6IFRyYW5zbGF0b3IudHJhbnMoJ2J1dHRvbi5jbG9zZScpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdjbGljayc6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kaWFsb2coJ2Nsb3NlJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnY2xhc3MnOiAnYnRuLXNpbHZlcidcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgXSxcbiAgICAgICAgICAgICAgICAgICAgNTAwXG4gICAgICAgICAgICAgICAgKVxuICAgICAgICAgICAgfVxuICAgICAgICBdKTtcblxuICAgICQoZnVuY3Rpb24gKCkge1xuICAgICAgICBhbmd1bGFyLmJvb3RzdHJhcCgnYm9keScsIFsnYXBwJ10pO1xuICAgIH0pO1xufSk7XG4iLCIkKGZ1bmN0aW9uICgpIHtcbiAgICB3aW5kb3cuakVycm9yID0gZnVuY3Rpb24gKG9wdGlvbnMpIHtcbiAgICAgICAgdmFyIHNldHRpbmdzID0ge1xuICAgICAgICAgICAgZXJyb3I6ICdlcnJvcicsXG4gICAgICAgICAgICB0eXBlOiAnZXJyb3InLFxuICAgICAgICAgICAgY29udGVudDogVHJhbnNsYXRvci50cmFucygvKipARGVzYyhcIlRoZXJlIGhhcyBiZWVuIGFuIGVycm9yIG9uIHRoaXMgcGFnZS4gVGhpcyBlcnJvciB3YXMgcmVjb3JkZWQgYW5kIHdpbGwgYmUgZml4ZWQgYXMgc29vbiBhcyBwb3NzaWJsZS5cIikqLydhbGVydHMudGV4dC5lcnJvcicpLFxuICAgICAgICAgICAgdGl0bGU6ICcnXG4gICAgICAgIH07XG4gICAgICAgIHNldHRpbmdzLmNvbnRlbnQgKz0gJzxpbWcgc3JjPVwiL2FqYXhfZXJyb3IuZ2lmP21lc3NhZ2U9ZXJyb3JcIiB3aWR0aD1cIjFcIiBoZWlnaHQ9XCIxXCI+JztcbiAgICAgICAgc2V0dGluZ3MgPSAkLmV4dGVuZChzZXR0aW5ncywgb3B0aW9ucyk7XG4gICAgICAgIHN3aXRjaCAoc2V0dGluZ3MuZXJyb3IpIHtcbiAgICAgICAgICAgIGNhc2UgXCJ0aW1lb3V0XCI6XG4gICAgICAgICAgICAgICAgc2V0dGluZ3MuY29udGVudCA9IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJJdCBsb29rcyBsaWtlIHlvdXIgcmVxdWVzdCBoYXMgdGltZWQgb3V0LiBQbGVhc2UgdHJ5IGFnYWluLiBJZiB5b3UgZ2V0IHRoaXMgZXJyb3IgYWdhaW4geW91IGNhbiB0cnkgcmVmcmVzaGluZyB0aGUgcGFnZS4gSWYgeW91IGdldCBzdHVjaywgZmVlbCBmcmVlIHRvIDxhIGhyZWY9J2h0dHBzOi8vYXdhcmR3YWxsZXQuY29tL2NvbnRhY3QucGhwJz5jb250YWN0IHVzPC9hPi5cIikqLydhbGVydHMudGV4dC5lcnJvci50aW1lb3V0Jyk7XG4gICAgICAgICAgICAgICAgc2V0dGluZ3MudGl0bGUgPSBUcmFuc2xhdG9yLnRyYW5zKC8qKkBEZXNjKFwiT3BlcmF0aW9uIFRpbWVkIE91dFwiKSovJ2FsZXJ0cy50aXRsZS5lcnJvci50aW1lb3V0Jyk7XG4gICAgICAgICAgICAgICAgc2V0dGluZ3MuY29udGVudCArPSAnPGltZyBzcmM9XCIvYWpheF9lcnJvci5naWY/bWVzc2FnZT10aW1lb3V0XCIgd2lkdGg9XCIxXCIgaGVpZ2h0PVwiMVwiPic7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlIFwicGFyc2VyZXJyb3JcIjpcbiAgICAgICAgICAgICAgICBzZXR0aW5ncy5jb250ZW50ID0gVHJhbnNsYXRvci50cmFucygvKipARGVzYyhcIkFuIGludmFsaWQgcmVzcG9uc2Ugd2FzIHJlY2VpdmVkIGZyb20gdGhlIHNlcnZlci4gUGxlYXNlIHRyeSBhZ2Fpbi4gSWYgeW91IGdldCB0aGlzIGVycm9yIGFnYWluIHlvdSBjYW4gdHJ5IHJlZnJlc2hpbmcgdGhlIHBhZ2UuIElmIHlvdSBnZXQgc3R1Y2ssIGZlZWwgZnJlZSB0byA8YSBocmVmPSdodHRwczovL2F3YXJkd2FsbGV0LmNvbS9jb250YWN0LnBocCc+Y29udGFjdCB1czwvYT4uXCIpKi8nYWxlcnRzLnRleHQuZXJyb3IucGFyc2VyZXJyb3InKTtcbiAgICAgICAgICAgICAgICBzZXR0aW5ncy50aXRsZSA9IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJTZXJ2ZXIgRXJyb3IgT2NjdXJyZWRcIikqLydhbGVydHMudGl0bGUuZXJyb3IucGFyc2VyZXJyb3InKTtcbiAgICAgICAgICAgICAgICBzZXR0aW5ncy5jb250ZW50ICs9ICc8aW1nIHNyYz1cIi9hamF4X2Vycm9yLmdpZj9tZXNzYWdlPXBhcnNlcmVycm9yXCIgd2lkdGg9XCIxXCIgaGVpZ2h0PVwiMVwiPic7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlIFwiYWJvcnRcIjpcbiAgICAgICAgICAgICAgICBzZXR0aW5ncy5jb250ZW50ID0gVHJhbnNsYXRvci50cmFucygvKipARGVzYyhcIllvdXIgY3VycmVudCByZXF1ZXN0IHdhcyBhYm9ydGVkLiBJZiB0aGlzIHdhcyBub3QgaW50ZW50aW9uYWwgeW91IGNhbiB0cnkgYWdhaW4uIElmIHlvdSBnZXQgc3R1Y2ssIGZlZWwgZnJlZSB0byA8YSBocmVmPSdodHRwczovL2F3YXJkd2FsbGV0LmNvbS9jb250YWN0LnBocCc+Y29udGFjdCB1czwvYT4uXCIpKi8nYWxlcnRzLnRleHQuZXJyb3IuYWJvcnQnKTtcbiAgICAgICAgICAgICAgICBzZXR0aW5ncy50aXRsZSA9IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJPcGVyYXRpb24gQWJvcnRlZFwiKSovJ2FsZXJ0cy50aXRsZS5lcnJvci5hYm9ydCcpO1xuICAgICAgICAgICAgICAgIHNldHRpbmdzLmNvbnRlbnQgKz0gJzxpbWcgc3JjPVwiL2FqYXhfZXJyb3IuZ2lmP21lc3NhZ2U9YWJvcnRcIiB3aWR0aD1cIjFcIiBoZWlnaHQ9XCIxXCI+JztcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgXCJlcnJvclwiOlxuICAgICAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgICAgICBicmVha1xuICAgICAgICB9XG4gICAgICAgIGpBbGVydChzZXR0aW5ncyk7XG4gICAgfTtcblxuICAgIHdpbmRvdy5qQWxlcnQgPSBmdW5jdGlvbiAob3B0aW9ucykge1xuICAgICAgICB2YXIgc2V0dGluZ3MgPSB7XG4gICAgICAgICAgICB0eXBlOiAnaW5mbycsXG4gICAgICAgICAgICB0aXRsZTogJycsXG4gICAgICAgICAgICBtb2RhbDogdHJ1ZSxcbiAgICAgICAgICAgIHdpZHRoOiA0MDAsXG4gICAgICAgICAgICBjb250ZW50OiAnJyxcbiAgICAgICAgICAgIGh0bWw6ICQoXCI8ZGl2Lz5cIiksXG4gICAgICAgICAgICBidXR0b25zOiBbXG4gICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICB0ZXh0OiBUcmFuc2xhdG9yLnRyYW5zKC8qKkBEZXNjKFwiT2tcIikqLydhbGVydHMuYnRuLm9rJyksXG4gICAgICAgICAgICAgICAgICAgIGNsaWNrOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmRpYWxvZygnY2xvc2UnKTtcbiAgICAgICAgICAgICAgICAgICAgfSxcblx0XHRcdFx0XHQnY2xhc3MnOiAnYnRuLWJsdWUnXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgXVxuICAgICAgICB9O1xuXG4gICAgICAgIC8vIENoZWNrIGlmIGRpYWxvZyBpcyBvcGVuXG4gICAgICAgIGlmICgkKCcudWktZGlhbG9nJykuaXMoXCI6dmlzaWJsZVwiKSlcbiAgICAgICAgICAgIHJldHVybjtcblxuICAgICAgICBzZXR0aW5ncy5jcmVhdGUgPSBmdW5jdGlvbiAoZSwgdWkpIHtcbiAgICAgICAgICAgICQoZS50YXJnZXQpLmNsb3Nlc3QoJy51aS1kaWFsb2cnKS5maW5kKCcudWktZGlhbG9nLXRpdGxlJykucHJlcGVuZCgnPGkgY2xhc3M9XCJpY29uLScgKyBzZXR0aW5ncy50eXBlICsgJ1wiPjwvaT4nKTtcbiAgICAgICAgICAgICQoZS50YXJnZXQpLnByZXYoJy51aS1kaWFsb2ctdGl0bGViYXInKS5hZGRDbGFzcygnYWxlcnQtJyArIHNldHRpbmdzLnR5cGUgKyAnLWhlYWRlcicpO1xuICAgICAgICAgICAgJChlLnRhcmdldCkubmV4dCgnLnVpLWRpYWxvZy1idXR0b25wYW5lJykuYWRkQ2xhc3MoJ2FsZXJ0LScgKyBzZXR0aW5ncy50eXBlICsgJy1ib3R0b20nKTtcbiAgICAgICAgfTtcblxuICAgICAgICBpZiAob3B0aW9ucy5jb250ZW50KSB7XG4gICAgICAgICAgICBzZXR0aW5ncyA9ICQuZXh0ZW5kKHNldHRpbmdzLCBvcHRpb25zKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHNldHRpbmdzLmNvbnRlbnQgPSBvcHRpb25zO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKHNldHRpbmdzLnRpdGxlID09ICcnKSB7XG4gICAgICAgICAgICBpZiAoc2V0dGluZ3MudHlwZSA9PT0gJ2luZm8nKVxuICAgICAgICAgICAgICAgIHNldHRpbmdzLnRpdGxlID0gVHJhbnNsYXRvci50cmFucygvKipARGVzYyhcIkluZm9ybWF0aW9uXCIpKi8nYWxlcnRzLmluZm8nKTtcbiAgICAgICAgICAgIGVsc2UgaWYgKHNldHRpbmdzLnR5cGUgPT09ICdlcnJvcicpXG4gICAgICAgICAgICAgICAgc2V0dGluZ3MudGl0bGUgPSBUcmFuc2xhdG9yLnRyYW5zKC8qKkBEZXNjKFwiRXJyb3JcIikqLydhbGVydHMuZXJyb3InKTtcbiAgICAgICAgICAgIGVsc2UgaWYgKHNldHRpbmdzLnR5cGUgPT09ICdzdWNjZXNzJylcbiAgICAgICAgICAgICAgICBzZXR0aW5ncy50aXRsZSA9IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJTdWNjZXNzXCIpKi8nYWxlcnRzLnN1Y2Nlc3MnKTtcbiAgICAgICAgICAgIGVsc2UgaWYgKHNldHRpbmdzLnR5cGUgPT09ICd3YXJuaW5nJylcbiAgICAgICAgICAgICAgICBzZXR0aW5ncy50aXRsZSA9IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJXYXJuaW5nXCIpKi8nYWxlcnRzLndhcm5pbmcnKTtcbiAgICAgICAgICAgIGVsc2VcbiAgICAgICAgICAgICAgICBzZXR0aW5ncy50aXRsZSA9IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJFcnJvclwiKSovJ2FsZXJ0cy5lcnJvcicpO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIGVsID0gc2V0dGluZ3MuaHRtbDtcbiAgICAgICAgZWwuYWRkQ2xhc3MoJ2FsZXJ0LScgKyBzZXR0aW5ncy50eXBlKS5odG1sKHNldHRpbmdzLmNvbnRlbnQpO1xuICAgICAgICAkKFwiYm9keVwiKS5hcHBlbmQoZWwpO1xuICAgICAgICAkKGVsKS5kaWFsb2coc2V0dGluZ3MpO1xuICAgICAgICByZXR1cm4gZWw7XG4gICAgfTtcblxuICAgIHdpbmRvdy5qQ29uZmlybSA9IGZ1bmN0aW9uIChxdWVzdGlvbiwgY2FsbGJhY2spIHtcbiAgICAgICAgcmV0dXJuIGpBbGVydCh7XG4gICAgICAgICAgICBjb250ZW50OiBxdWVzdGlvbixcbiAgICAgICAgICAgIHRpdGxlOiBUcmFuc2xhdG9yLnRyYW5zKC8qKkBEZXNjKFwiUGxlYXNlIGNvbmZpcm1cIikqLydhbGVydHMudGV4dC5jb25maXJtJyksXG4gICAgICAgICAgICBidXR0b25zOiBbXG4gICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICB0ZXh0OiBUcmFuc2xhdG9yLnRyYW5zKC8qKkBEZXNjKFwiQ2FuY2VsXCIpKi8gJ2FsZXJ0cy5idG4uY2FuY2VsJyksXG4gICAgICAgICAgICAgICAgICAgIGNsaWNrOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmRpYWxvZygnY2xvc2UnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgJ2NsYXNzJzogJ2J0bi1zaWx2ZXInXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgICAgIHRleHQ6IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJPa1wiKSovJ2FsZXJ0cy5idG4ub2snKSxcbiAgICAgICAgICAgICAgICAgICAgY2xpY2s6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICQodGhpcykuZGlhbG9nKCdjbG9zZScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgY2FsbGJhY2soKTtcbiAgICAgICAgICAgICAgICAgICAgfSxcblx0XHRcdFx0XHQnY2xhc3MnOiAnYnRuLWJsdWUnXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgXVxuICAgICAgICB9KTtcbiAgICB9O1xuXG4gICAgd2luZG93LmpQcm9tcHQgPSBmdW5jdGlvbiAocXVlc3Rpb24sIGNhbGxiYWNrKSB7XG4gICAgICAgIHZhciBlbCA9ICQoXCI8aW5wdXQvPlwiKS5jc3MoJ3dpZHRoJywgJzEwMCUnKTtcbiAgICAgICAgcmV0dXJuIGpBbGVydCh7XG4gICAgICAgICAgICB0aXRsZTogcXVlc3Rpb24sXG4gICAgICAgICAgICBjb250ZW50OiBlbCxcbiAgICAgICAgICAgIGJ1dHRvbnM6IFtcbiAgICAgICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgICAgIHRleHQ6IFRyYW5zbGF0b3IudHJhbnMoLyoqQERlc2MoXCJPa1wiKSovJ2FsZXJ0cy5idG4ub2snKSxcbiAgICAgICAgICAgICAgICAgICAgY2xpY2s6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICQodGhpcykuZGlhbG9nKCdjbG9zZScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgY2FsbGJhY2soJChlbCkudmFsKCkpO1xuICAgICAgICAgICAgICAgICAgICB9LFxuXHRcdFx0XHRcdCdjbGFzcyc6ICdidG4tYmx1ZSdcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICBdXG4gICAgICAgIH0pXG4gICAgfTtcblxuICAgIHdpbmRvdy5qQWpheEVycm9ySGFuZGxlciA9IGZ1bmN0aW9uIChqcVhIUiwgdGV4dFN0YXR1cywgZXJyb3JUaHJvd24pIHtcbiAgICAgICAgaWYgKHR5cGVvZigkLmJyb3dzZXIpICE9ICd1bmRlZmluZWQnICYmICQuYnJvd3Nlci53ZWJraXQgJiYgdGV4dFN0YXR1cyA9PT0gJ2Vycm9yJyAmJiAhanFYSFIuZ2V0QWxsUmVzcG9uc2VIZWFkZXJzKCkpIHRleHRTdGF0dXMgPSAnYWJvcnQnOyAvLyBjaHJvbWUgdGhyb3cgXCJlcnJvclwiIG9uIHVzZXIgcmVmcmVzaFxuICAgICAgICAvKiBcInN1Y2Nlc3NcIiwgXCJub3Rtb2RpZmllZFwiLCBcImVycm9yXCIsIFwidGltZW91dFwiLCBcImFib3J0XCIsIG9yIFwicGFyc2VyZXJyb3JcIiAqL1xuLy8gICAgICAgIGlmICgkLmluQXJyYXkodGV4dFN0YXR1cywgW1widGltZW91dFwiLCBcImFib3J0XCIsIFwicGFyc2VyZXJyb3JcIl0pID49IDApIHJldHVybjtcbiAgICAgICAgaWYgKHRleHRTdGF0dXMgPT09IFwiYWJvcnRcIikgcmV0dXJuO1xuXG4gICAgICAgIGlmIChqcVhIUi5yZXNwb25zZVRleHQgPT09ICd1bmF1dGhvcml6ZWQnKSB7XG4gICAgICAgICAgICB0cnkge1xuICAgICAgICAgICAgICAgIGlmICh3aW5kb3cucGFyZW50ICE9IHdpbmRvdyl7XG4gICAgICAgICAgICAgICAgICAgIHBhcmVudC5sb2NhdGlvbi5ocmVmID0gJy9zZWN1cml0eS91bmF1dGhvcml6ZWQucGhwP0JhY2tUbz0nICsgZW5jb2RlVVJJKHBhcmVudC5sb2NhdGlvbi5ocmVmKTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgbm8tZW1wdHlcbiAgICAgICAgICAgIH0gY2F0Y2goZSl7fVxuICAgICAgICAgICAgbG9jYXRpb24uaHJlZiA9ICcvc2VjdXJpdHkvdW5hdXRob3JpemVkLnBocD9CYWNrVG89JyArIGVuY29kZVVSSShsb2NhdGlvbi5ocmVmKTtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICB2YXIgb3B0aW9ucyA9IHtlcnJvcjogdGV4dFN0YXR1c307XG4gICAgICAgIGlmKHR5cGVvZih3aW5kb3cuZGVidWdNb2RlKSAhPSAndW5kZWZpbmVkJyAmJiB3aW5kb3cuZGVidWdNb2RlKVxuICAgICAgICAgICAgb3B0aW9ucy5jb250ZW50ID0gJ1thamF4IGVycm9yOiAnICsganFYSFIuc3RhdHVzICsgJyAnICsgdGV4dFN0YXR1cyArICddXFxuXFxuJyArIGpxWEhSLnJlc3BvbnNlVGV4dDtcbiAgICAgICAgd2luZG93LmpFcnJvcihvcHRpb25zKTtcbiAgICB9O1xuXG59KTsiLCIndXNlIHN0cmljdCc7XG52YXIgaXNPYmplY3QgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvaXMtb2JqZWN0Jyk7XG5cbnZhciBmbG9vciA9IE1hdGguZmxvb3I7XG5cbi8vIGBJc0ludGVncmFsTnVtYmVyYCBhYnN0cmFjdCBvcGVyYXRpb25cbi8vIGh0dHBzOi8vdGMzOS5lcy9lY21hMjYyLyNzZWMtaXNpbnRlZ3JhbG51bWJlclxuLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIGVzL25vLW51bWJlci1pc2ludGVnZXIgLS0gc2FmZVxubW9kdWxlLmV4cG9ydHMgPSBOdW1iZXIuaXNJbnRlZ2VyIHx8IGZ1bmN0aW9uIGlzSW50ZWdlcihpdCkge1xuICByZXR1cm4gIWlzT2JqZWN0KGl0KSAmJiBpc0Zpbml0ZShpdCkgJiYgZmxvb3IoaXQpID09PSBpdDtcbn07XG4iLCIndXNlIHN0cmljdCc7XG52YXIgJCA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9leHBvcnQnKTtcbnZhciBpc0ludGVncmFsTnVtYmVyID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2lzLWludGVncmFsLW51bWJlcicpO1xuXG4vLyBgTnVtYmVyLmlzSW50ZWdlcmAgbWV0aG9kXG4vLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLW51bWJlci5pc2ludGVnZXJcbiQoeyB0YXJnZXQ6ICdOdW1iZXInLCBzdGF0OiB0cnVlIH0sIHtcbiAgaXNJbnRlZ2VyOiBpc0ludGVncmFsTnVtYmVyXG59KTtcbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyJdLCJuYW1lcyI6WyJjcmVhdGVFbGVtZW50IiwicmVuZGVyIiwidW5tb3VudENvbXBvbmVudEF0Tm9kZSIsImFuZ3VsYXIiLCJhcHBsaWVkIiwiZm4iLCJzY29wZSIsIndyYXBwZWRJbkFwcGx5Iiwid3JhcHBlZCIsImFyZ3MiLCJhcmd1bWVudHMiLCJwaGFzZSIsIiRyb290IiwiJCRwaGFzZSIsImFwcGx5IiwiJGFwcGx5IiwiYXBwbHlGdW5jdGlvbnMiLCJvYmoiLCJwcm9wc0NvbmZpZyIsIk9iamVjdCIsImtleXMiLCJyZWR1Y2UiLCJwcmV2Iiwia2V5IiwidmFsdWUiLCJjb25maWciLCJpc0Z1bmN0aW9uIiwid3JhcEFwcGx5Iiwid2F0Y2hQcm9wcyIsIndhdGNoRGVwdGgiLCJ3YXRjaEV4cHJlc3Npb25zIiwibGlzdGVuZXIiLCJzdXBwb3J0c1dhdGNoQ29sbGVjdGlvbiIsIiR3YXRjaENvbGxlY3Rpb24iLCJzdXBwb3J0c1dhdGNoR3JvdXAiLCIkd2F0Y2hHcm91cCIsIndhdGNoR3JvdXBFeHByZXNzaW9ucyIsImZvckVhY2giLCJleHByIiwiYWN0dWFsRXhwciIsImdldFByb3BFeHByZXNzaW9uIiwiZXhwcldhdGNoRGVwdGgiLCJnZXRQcm9wV2F0Y2hEZXB0aCIsInB1c2giLCIkd2F0Y2giLCJsZW5ndGgiLCJyZW5kZXJDb21wb25lbnQiLCJjb21wb25lbnQiLCJwcm9wcyIsImVsZW0iLCIkZXZhbEFzeW5jIiwicHJvcCIsIkFycmF5IiwiaXNBcnJheSIsImRlZmF1bHRXYXRjaCIsImN1c3RvbVdhdGNoRGVwdGgiLCJpc09iamVjdCIsImdldFByb3BOYW1lIiwiZmluZEF0dHJpYnV0ZSIsImF0dHJzIiwicHJvcE5hbWUiLCJpbmRleCIsImZpbHRlciIsImF0dHIiLCJ0b0xvd2VyQ2FzZSIsImdldFByb3BDb25maWciLCJyZWFjdERpcmVjdGl2ZSIsIiRpbmplY3RvciIsInJlYWN0Q29tcG9uZW50Iiwic3RhdGljUHJvcHMiLCJjb25mIiwiaW5qZWN0YWJsZVByb3BzIiwiZGlyZWN0aXZlIiwicmVzdHJpY3QiLCJyZXBsYWNlIiwibGluayIsInByb3BUeXBlcyIsIm5nQXR0ck5hbWVzIiwiZGlyZWN0aXZlTmFtZSIsIm5hbWUiLCIkYXR0ciIsInJlbmRlck15Q29tcG9uZW50Iiwic2NvcGVQcm9wcyIsIiRldmFsIiwiZXh0ZW5kIiwicHJvcEV4cHJlc3Npb25zIiwibWFwIiwiJG9uIiwib25TY29wZURlc3Ryb3kiLCJ1bm1vdW50Q29tcG9uZW50IiwiYmluZCIsIm1vZHVsZSIsImZhY3RvcnkiLCJqcXVlcnl1aSIsIm1haW4iLCJ0b2dnbGVTaWRlYmFyVmlzaWJsZSIsImluaXREcm9wZG93bnMiLCIkIiwid2luZG93IiwicmVzaXplIiwic2l6ZVdpbmRvdyIsIndpZHRoIiwiYWRkQ2xhc3MiLCJyZW1vdmVDbGFzcyIsImhhc0NsYXNzIiwibWVudUNsb3NlIiwiZG9jdW1lbnQiLCJxdWVyeVNlbGVjdG9yIiwibWVudUJvZHkiLCJvbmNsaWNrIiwiY2xhc3NMaXN0IiwidG9nZ2xlIiwiYWRkIiwiYXJlYSIsIm9wdGlvbnMiLCJzZWxlY3RvciIsImRyb3Bkb3duIiwidW5kZWZpbmVkIiwiZmluZCIsImFkZEJhY2siLCJvZlBhcmVudFNlbGVjdG9yIiwib2ZQYXJlbnQiLCJlYWNoIiwiaWQiLCJlbCIsInJlbW92ZUF0dHIiLCJtZW51IiwiaGlkZSIsIm9uIiwiZSIsInRhcmdldCIsImRhdGEiLCJwcmV2ZW50RGVmYXVsdCIsInN0b3BQcm9wYWdhdGlvbiIsIm5vdCIsInRyaWdnZXIiLCJfb3B0aW9ucyIsInBvc2l0aW9uIiwibXkiLCJhdCIsIm9mIiwicGFyZW50cyIsImNvbGxpc2lvbiIsImF1dG9Db21wbGV0ZVJlbmRlckl0ZW0iLCJyZW5kZXJGdW5jdGlvbiIsInVsIiwiaXRlbSIsInJlZ2V4IiwiUmVnRXhwIiwiZWxlbWVudCIsInZhbCIsImh0bWwiLCJ0ZXh0IiwibGFiZWwiLCJhcHBlbmQiLCJhcHBlbmRUbyIsInVpIiwiYXV0b2NvbXBsZXRlIiwicHJvdG90eXBlIiwiX3JlbmRlckl0ZW0iLCJkZWZpbmUiLCJfX2VzTW9kdWxlIiwiZGVmYXVsdCIsInRlbXBsYXRlIiwidHJhbnNjbHVkZSIsImV2ZW50IiwicmVwbGFjZVdpdGgiLCJkaWFsb2dDbG9zZSIsIiRlbWl0IiwiJHRpbWVvdXQiLCIkd2luZG93Iiwib25jbG9zZSIsInNob3ciLCJwYXJlbnQiLCJhdXRvT3BlbiIsImNsb3NlIiwib2ZmIiwiY3JlYXRlIiwiJGJyb2FkY2FzdCIsIm9wZW4iLCJkaWFsb2ciLCJvbmUiLCJjc3MiLCJyZXF1ZXN0ZXIiLCJnYVdyYXBwZXIiLCJBZGRNYWlsYm94Iiwic2VsZWN0T3duZXJEaWFsb2ciLCJzZWxlY3RPd25lckRlZmVycmVkIiwib3duZXIiLCJlbWFpbE93bmVycyIsIm9uU3VibWl0QWRkRm9ybSIsInNlbGVjdEZhbWlseU1lbWJlciIsIl9wcm90byIsInNldEZhbWlseU1lbWJlcnMiLCJ1c2VyRnVsbE5hbWUiLCJmYW1pbHlNZW1iZXJzIiwidW5zaGlmdCIsInVzZXJhZ2VudGlkIiwiZnVsbE5hbWUiLCJzZXRPd25lciIsInNldFJlZGlyZWN0VXJsIiwidXJsIiwicmVkaXJlY3RVcmwiLCJzdWJzY3JpYmUiLCJfdGhpcyIsInRoZW4iLCJhZ2VudElkIiwibG9jYXRpb24iLCJocmVmIiwiZm9ybSIsInJlcXVlc3QiLCJSb3V0aW5nIiwiZ2VuZXJhdGUiLCJpcyIsInRpbWVvdXQiLCJidXR0b24iLCJiZWZvcmUiLCJyZW1vdmUiLCJzdWNjZXNzIiwidW5sb2NrIiwic3RhdHVzIiwibWVzc2FnZSIsImVycm9yIiwiZm9jdXMiLCJjb25zb2xlIiwibG9nIiwiZXZlbnRfY2FsbGJhY2siLCJyZWxvYWQiLCJlbWFpbCIsIkRlZmVycmVkIiwicmVzb2x2ZSIsInByb21pc2UiLCJmYXN0Q3JlYXRlIiwiVHJhbnNsYXRvciIsInRyYW5zIiwiZmFtaWx5TWVtYmVyIiwiam9pbiIsImNsaWNrIiwic2V0T3B0aW9uIiwiUmVxdWVzdCIsImNvbnRhaW5lciIsImJ1c3kiLCJidXN5VGltZXIiLCJmYWRlcklkIiwiZmFkZXIiLCJzZXRDb250YWluZXIiLCJzaG93QnV0dG9uUHJvZ3Jlc3MiLCJoaWRlQnV0dG9uUHJvZ3Jlc3MiLCJsb2NrIiwiY2xvbmUiLCJvcGFjaXR5IiwiaGVpZ2h0Iiwic3RvcCIsImFuaW1hdGUiLCJkdXJhdGlvbiIsImNvbXBsZXRlIiwibWV0aG9kIiwic2V0dGluZ3MiLCJkZWZhdWx0cyIsImFqYXgiLCJkYXRhVHlwZSIsInR5cGUiLCJiZWZvcmVTZW5kIiwiY2xlYXJUaW1lb3V0Iiwic2V0VGltZW91dCIsIl90eXBlb2YiLCJqc29uIiwianFYSFIiLCJfZXJyb3IiLCJ1dGlscyIsImN1c3RvbWl6ZXIiLCJkYXRlVGltZURpZmYiLCIkbGFzdCIsIm9uRmluaXNoUmVuZGVyIiwiJHNjb3BlIiwiJGVsZW1lbnQiLCJvbkVycm9yIiwiJGRvY3VtZW50Iiwic2Nyb2xsQW5kUmVzaXplTGlzdGVuZXIiLCJvZmZzZXRGYWN0b3IiLCJpbWFnZUxhenlTcmMiLCJsaXN0ZW5lclJlbW92ZXIiLCJpc0luVmlldyIsImNsaWVudEhlaWdodCIsImNsaWVudFdpZHRoIiwiaW1hZ2VSZWN0IiwiZ2V0Qm91bmRpbmdDbGllbnRSZWN0Iiwib2Zmc2V0SGVpZ2h0Iiwib2Zmc2V0V2lkdGgiLCJ0b3AiLCJib3R0b20iLCJsZWZ0IiwicmlnaHQiLCJhZGRMaXN0ZW5lciIsImRvY3VtZW50RWxlbWVudCIsInNlZ21lbnQiLCJ1bmRyb3BwYWJsZSIsInBsYW5zIiwicGxhbklkIiwic2hvd1Rvb2x0aXBzIiwibmVlZFNob3dUb29sdGlwcyIsInRyaXBTdGFydCIsInNlZ21lbnRzIiwibyIsInBsYW4iLCJzdGFydFNlZ21lbnQiLCJpbmRleE9mIiwiZW5kU2VnbWVudCIsInBvaW50cyIsInBsYW5MYXN0VXBkYXRlIiwiaSIsImN1cnJlbnQiLCJpY29uIiwibGFzdFVwZGF0ZWQiLCJOdW1iZXIiLCJpc0ludGVnZXIiLCJsb25nRm9ybWF0VmlhRGF0ZXMiLCJEYXRlIiwidG9vbHRpcCIsIiRzdGF0ZSIsIm5leHQiLCJzbGlkZVVwIiwib3BlbmVkIiwicGFyYW1zIiwib3BlblNlZ21lbnQiLCJkaWFsb2dGbGlnaHQiLCJkZXRhaWxzIiwic2xpZGVEb3duIiwibWF0Y2giLCJkZWJvdW5jZSIsInByaW50IiwiYm9va2luZ0xpbmsiLCJyb3ciLCJjbG9zZXN0IiwiaW5pdERhdGVwaWNrZXJzIiwiY2hlY2tpbkRhdGVwaWNrZXIiLCJjaGVja291dERhdGVwaWNrZXIiLCJkYXRlcGlja2VyVmFsdWUiLCJkYXRlcGlja2VyIiwiZGF0ZSIsInNlbGVjdGVkRGF0ZSIsInNldERhdGUiLCJnZXREYXRlIiwibWluRGF0ZSIsImF1dG9jb21wbGV0ZUlucHV0IiwiYXV0b2NvbXBsZXRlUmVxdWVzdCIsImF1dG9Db21wbGV0ZURhdGEiLCJ0cmltIiwia2V5Q29kZSIsImZvcm1GaWVsZHMiLCJzZWxlY3RlZElhdGEiLCJzZWxlY3RlZERlc3RpbmF0aW9uIiwiZGVzdGluYXRpb24iLCJkZWxheSIsIm1pbkxlbmd0aCIsInNvdXJjZSIsInJlc3BvbnNlIiwidGVybSIsInNlbGYiLCJhYm9ydCIsImdldCIsInF1ZXJ5IiwicmVzdWx0IiwiY291bnRyeSIsImFkZHJlc3NfY29tcG9uZW50cyIsInR5cGVzIiwiY291bnRyeUxvbmciLCJsb25nX25hbWUiLCJjaXR5IiwiZm9ybWF0dGVkX2FkZHJlc3MiLCJzZWFyY2giLCJuZXh0QWxsIiwiaXRlbUxhYmVsIiwic2VsZWN0Iiwic2Nyb2xsVG9wIiwib2Zmc2V0IiwiZWZmZWN0IiwiJHJvb3RTY29wZSIsIm5nRGF0YSIsImFnZW50SXNTZXQiLCJOb1Jlc3VsdHNMYWJlbCIsImxhc3RSZXNwb25zZSIsInEiLCJ4aHIiLCJpc0VtcHR5T2JqZWN0Iiwic3RyIiwiY2hhckF0IiwidG9VcHBlckNhc2UiLCJzbGljZSIsIiRzdGF0ZVByb3ZpZGVyIiwiJHVybFJvdXRlclByb3ZpZGVyIiwiJGxvY2F0aW9uUHJvdmlkZXIiLCJodG1sNU1vZGUiLCJlbmFibGVkIiwicmV3cml0ZUxpbmtzIiwic3RhdGUiLCJzaG93RGVsZXRlZCIsIm90aGVyd2lzZSIsIndoZW4iLCIkbWF0Y2giLCJpdElkcyIsImdvIiwiJHEiLCJsb2FkaW5nQ291bnQiLCIkaHR0cExvYWRpbmciLCJyZXNwb25zZUVycm9yIiwicmVqZWN0IiwiJGh0dHBQcm92aWRlciIsImludGVyY2VwdG9ycyIsInJlcXVpcmUiLCJzZXJ2aWNlIiwiJHN0YXRlUGFyYW1zIiwiJGh0dHAiLCIkc2NlIiwidHJ1c3RIdG1sIiwidHJ1c3RBc0h0bWwiLCJnZXRNYXBVcmwiLCJjb2RlIiwic2l6ZSIsInByaW50VHJhdmVsUGxhbiIsInNoYXJlQ29kZSIsInBsYW5EdXJhdGlvbiIsImNvbmNhdCIsImdldE5vdGVzIiwibGlua2lmeSIsIm5vdGVzIiwiZ2V0UmVsYXRpdmVEYXRlIiwibG9jYWxEYXRlSVNPIiwiZ2V0U3RhdGUiLCJkYXlTdGFydCIsInNldEhvdXJzIiwic3RhcnREYXRlIiwiZ2V0RGF5c051bWJlckZyb21Ub2RheSIsImRpZmYiLCJNYXRoIiwiYWJzIiwiZmxvb3IiLCJleHRQcm9wZXJ0aWVzIiwiYWNjIiwiX2Zvcm1hdFRpbWUiLCJ0aW1lIiwicGFydHMiLCJzcGxpdCIsImdldFRpdGxlIiwidGl0bGUiLCJnZXRJbWdTcmMiLCJnZXRMb2NhbFRpbWUiLCJsb2NhbFRpbWUiLCJnZXRBcnJEYXRlIiwiYXJyVGltZSIsImVuZERhdGUiLCJub3ciLCJnZXRCZXR3ZWVuIiwiZ2V0QmV0d2VlblRleHQiLCJuaWdodHMiLCJ0cmFuc0Nob2ljZSIsImRheXMiLCJpc1Nob3J0IiwiZmxhdFRhZ3MiLCJ0YWciLCJnZXRUaW1lRGlmZkZvcm1hdGVkIiwidGltZXN0YW1wIiwibG9uZ0Zvcm1hdFZpYURhdGVUaW1lcyIsImdldERpZmZUaW1lQWdvIiwiaXNTaG93TW9yZUxpbmtzIiwiaXNNYW51YWxTZWdtZW50IiwiaXNBdXRvQWRkZWRTZWdtZW50IiwiaXNTaG93bkluZm8iLCJhbHRlcm5hdGl2ZUZsaWdodHMiLCJvcmlnaW5zIiwibWFudWFsIiwiaXNBaXJTZWdtZW50IiwiYWlyIiwiYXV0byIsImdldEVkaXRMaW5rIiwidHJpcElkIiwidmlzaWJsZSIsImdldEVsaXRlTGV2ZWwiLCJwaG9uZUl0ZW0iLCJlc2NhcGUiLCJsZXZlbCIsInJlZGlyZWN0VG9Cb29raW5nIiwicGF5bG9hZCIsImNoZWNraW5EYXRlIiwidG9JU09TdHJpbmciLCJjaGVja291dERhdGUiLCJzcyIsImNoZWNraW5fbW9udGhkYXkiLCJjaGVja2luX3llYXJfbW9udGgiLCJjaGVja291dF9tb250aGRheSIsImNoZWNrb3V0X3llYXJfbW9udGgiLCJ0aW1lbGluZUZvcm0iLCJwYXJhbSIsImZvcm1hdENvc3QiLCJJbnRsIiwiTnVtYmVyRm9ybWF0IiwibG9jYWxlcyIsImZvcm1hdCIsImdldFRyYXZlbGVyc0NvdW50IiwiY291bnQiLCJhbHRlcm5hdGl2ZUZsaWdodCIsIiRldmVudCIsIm9sZFBvcHVwIiwicG9wdXAiLCJqUXVlcnkiLCJlbmQiLCJrZXl1cCIsInVwZGF0ZUFsdGVybmF0aXZlRmxpZ2h0IiwiY3JlYXRlTmFtZWQiLCJyZXNpemFibGUiLCJkZXN0cm95IiwiJGRpYWxvZyIsImVtcHR5IiwiJGJ0biIsInBpY2siLCJjdXN0b21WYWx1ZSIsInBvc3QiLCJlcnJvcnMiLCJ2YWx1ZXMiLCJmb3JtYXRGaWxlU2l6ZSIsImJ5dGVzIiwiZm9ybWF0RGF0ZVRpbWUiLCJzdHJEYXRlIiwiRGF0ZVRpbWVGb3JtYXQiLCJkYXRlU3R5bGUiLCJ0aW1lU3R5bGUiLCJwYXJzZSIsImdldEZpbGVMaW5rIiwiZmlsZUlkIiwiaXRpbmVyYXJ5RmlsZUlkIiwic2V0T3B0aW9ucyIsInByaW50UHJvcGVydGllc1ZhbHVlIiwiaGFzT3duUHJvcGVydHkiLCJjYWxsIiwiY29sbGFwc2VGaWVsZFByb3BlcnRpZXMiLCJpc0xheW92ZXJTZWdtZW50Iiwic3Vic3RyIiwic2hvd1BvcHVwTmF0aXZlQXBwcyIsImhlYWQiLCJjb250ZW50IiwibW9kYWwiLCJvbkNsb3NlIiwic2VnbWVudENsYXNzZXMiLCJmZXRjaCIsImFmdGVyIiwiZGVmZXIiLCJjYW5jZWwiLCJUaW1lbGluZURhdGEiLCJyb3V0ZSIsImRpc2FibGVFcnJvckRpYWxvZyIsInNlc3Npb25TdG9yYWdlIiwiYmFja1VybCIsImNsb3NlT25Fc2NhcGUiLCJidXR0b25zIiwiakFsZXJ0IiwibW92ZSIsImhlYWRlcnMiLCJzY3JvbGxUaW1lb3V0IiwicmVzaXplVGltZW91dCIsImxpc3RlbmVycyIsImludm9rZUxpc3RlbmVycyIsImFkZEV2ZW50TGlzdGVuZXIiLCJhZGRNYWlsYm94IiwiY291bnRXaXRoTnVsbCIsInNob3dXaXRoTnVsbCIsImlzVGltZWxpbmUiLCIkcGVyc29ucyIsIiRwZXJzb24iLCJjaGlsZHJlbiIsImNsaWNrSGFuZGxlciIsImNvbnRyb2xsZXIiLCIkdGltZWxpbmVEYXRhIiwiJGZpbHRlciIsIiR0cmF2ZWxQbGFucyIsIiRsb2ciLCIkbG9jYXRpb24iLCIkdHJhbnNpdGlvbnMiLCJzdGF0ZVBhcmFtcyIsImhhdmVGdXR1cmVTZWdtZW50cyIsImFnZW50cyIsImFnZW50IiwibmV3b3duZXIiLCJjb3B5IiwiY2FuQWRkIiwiZW1iZWRkZWREYXRhIiwiYWN0aXZlU2VnbWVudE51bWJlciIsIm5vRm9yZWlnbkZlZXNDYXJkcyIsIm92ZXJsYXkiLCJtZXRob2RzIiwic2VnbWVudExpbmsiLCJzZWdtZW50SWQiLCJ0b3NzaW5nRmlsbCIsInRvc3NpbmdDbGVhciIsInNvcnRhYmxlIiwic2VnIiwidG9zc2luZ0Ryb3AiLCJyZXMiLCJjaGFuZ2VOYW1lU3RhdGUiLCJyZWNhbGN1bGF0ZUFmdGVyIiwiZ2V0TW92ZVRleHQiLCJjb25mX25vIiwibl9zZWdtZW50cyIsImdldE9yaWdpblRleHQiLCJvcmlnaW4iLCJsaXN0SXRlbSIsInByb3ZpZGVyIiwiYWNjb3VudE51bWJlciIsImFjY291bnRJZCIsImNvbmZOdW1iZXIiLCJmcm9tIiwiY2hhbmdlTmFtZSIsInJlbmFtaW5nU3RhdGUiLCJyZXF1ZXN0RGVsZXRlUGxhbiIsImZhZGVJbiIsImRlbGV0ZVBsYW4iLCJjdXJyZW50VGFyZ2V0IiwiY29uZmlybVBvcHVwIiwiZGVsZXRlT3JVbmRlbGV0ZSIsImlzVW5kZWxldGUiLCJkZWxldGVMb2FkZXIiLCJ1bmRlbGV0ZSIsImNvbmZpcm1DaGFuZ2VzIiwiY29uZmlybUxvYWRlciIsImNoYW5nZWQiLCJfIiwiZ3JvdXAiLCJnb1JlZnJlc2giLCJzdWJtaXQiLCJzY3JvbGxUb1RvcCIsImhvdmVySW5TZWdtZW50IiwiaG92ZXJPdXRTZWdtZW50IiwiY3JlYXRlUGxhbiIsImNyZWF0ZVBsYW5TdGF0ZSIsInVzZXJBZ2VudElkIiwic3RhcnRUaW1lIiwic2hvd25Gcm9tIiwiZmluYWxseSIsImFmdGVyUGxhbkNyZWF0ZWQiLCJ0ZXN0IiwiJCRhYnNVcmwiLCJzcGlubmVyIiwiZGF0YVJlcXVlc3QiLCJvblN1Y2Nlc3MiLCJ0cmFuc2l0aW9uIiwidG9QYXJhbXMiLCJmcm9tUGFyYW1zIiwiYWdlbnRNYXRjaCIsImZvcmNlQWZ0ZXIiLCJwYXJzZUludCIsInNob3duU2VnbWVudHMiLCJwYXN0U3Bpbm5lciIsImNvbnRhaW5lckhlaWdodCIsImFuY2hvciIsIm9mZnNldFRvcCIsImFuY2hvckVsZW1lbnQiLCJmdXR1cmUiLCJvcGVuU2VnbWVudERhdGUiLCJzaGFyYWJsZUFnZW50cyIsInNoYXJhYmxlIiwiZmFkZU91dCIsIlVUQyIsImdldEZ1bGxZZWFyIiwiZ2V0TW9udGgiLCJicmVha0FmdGVyIiwibW9uaXRvcmVkU3RhdHVzIiwiaW5BcnJheSIsInJlc2VydmF0aW9uIiwiZm9yd2FyZGluZ0VtYWlsIiwibWFpbGJveGVzIiwidG90YWxzIiwiY291bnRzIiwiaXRlbXMiLCJ3cmFwQWxsIiwiaW5pdEh0bWw1SW5wdXRzIiwiaGlkZVRvb2x0aXBzIiwicmVxdWVzdFBsYW5Nb3ZlIiwibmV4dFNlZ21lbnQiLCJuZXh0U2VnbWVudElkIiwibmV4dFNlZ21lbnRUcyIsIiRwYXJlbnQiLCJyZXNwIiwidHJhbnNpdGlvblRvIiwiaW5oZXJpdCIsImF4aXMiLCJoYW5kbGUiLCJyZXZlcnQiLCJzdGFydCIsImVsZW1lbnRzIiwidWlJbmRleCIsImZpcnN0IiwiJG5vdGVzV3JhcCIsIiRyb3dQYXJlbnQiLCJ0b2dnbGVDbGFzcyIsImJvb3RzdHJhcCIsImpFcnJvciIsInByZXBlbmQiLCJqQ29uZmlybSIsInF1ZXN0aW9uIiwiY2FsbGJhY2siLCJqUHJvbXB0IiwiakFqYXhFcnJvckhhbmRsZXIiLCJ0ZXh0U3RhdHVzIiwiZXJyb3JUaHJvd24iLCJicm93c2VyIiwid2Via2l0IiwiZ2V0QWxsUmVzcG9uc2VIZWFkZXJzIiwicmVzcG9uc2VUZXh0IiwiZW5jb2RlVVJJIiwiZGVidWdNb2RlIl0sInNvdXJjZVJvb3QiOiIifQ==