(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["page-landing/popups"],{

/***/ "./web/assets/awardwalletnewdesign/js/directives/dialog.js":
/*!*****************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/directives/dialog.js ***!
  \*****************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular */ "./assets/bem/ts/shim/angular.js"), __webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (angular, $, dialog) {
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module('dialog-directive', []).service('dialogService', [function () {
    return dialog;
  }]).directive('dialog', ['dialogService', function (dialogService) {
    'use strict';

    var options = $.unique(Object.keys($.ui.dialog.prototype.options));
    return {
      restrict: 'E',
      scope: options.reduce(function (acc, val) {
        acc[val] = "&";
        return acc;
      }, {
        bindToScope: '='
      }),
      replace: true,
      transclude: true,
      template: '<div style="display:none" data-ng-transclude></div>',
      link: function link(scope, element, attr, ctrl, transclude) {
        var opts = options.reduce(function (acc, val) {
          var value = scope[val] ? scope[val]() : undefined;
          if (value !== undefined) {
            acc[val] = value;
          }
          return acc;
        }, {});
        dialogService.createNamed(attr.id, element, opts);
        if (scope.bindToScope) {
          transclude(scope, function (clone, scope) {
            element.html(clone);
          });
        }
      }
    };
  }]);
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/lib/design.js":
/*!**********************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/lib/design.js ***!
  \**********************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js"), __webpack_require__(/*! pages/agent/addDialog */ "./web/assets/awardwalletnewdesign/js/pages/agent/addDialog.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function ($) {
  $(function () {
    var top = $('.header-site').length ? $('.header-site').offset().top - parseFloat($('.header-site').css('margin-top').replace(/auto/, 0)) : 0;
    $('.menu-close').click(function () {
      $('.main-body').toggleClass('hide-menu').addClass('manual-hidden');
      $(window).trigger('resize');
    });
    if ($('.menu-button').length) {
      $('.menu-button').click(function () {
        $(this).toggleClass('active');
        $('.header-site,.fixed-header').toggleClass('active');
        $('body').toggleClass('overflow');
      });
    }
    $('.api-nav__has-submenu > a').click(function (e) {
      e.preventDefault();
      $(this).parent().toggleClass('active');
    });
    $('.list-apis a, .about__tags a').click(function (e) {
      e.preventDefault();
      var hash = $(this).attr('href'),
        headerHeight = $('html').hasClass('mobile-device') ? $('.fixed-header').innerHeight() : $('.header-site').innerHeight();
      $('body,html').animate({
        scrollTop: $(hash).offset().top - headerHeight
      }, 500);
      if (history.pushState) {
        history.pushState(null, '', hash);
      } else {
        location.hash = hash;
      }
    });
    $('.main-form .styled-select select').focus(function () {
      $(this).closest('.styled-select').addClass('focus');
    }).blur(function () {
      $(this).closest('.styled-select').removeClass('focus');
    });
    $(window).each(function () {
      var body = $('.main-body');
      if ($(window).width() < 1024) {
        body.addClass('small-desktop');
      } else {
        body.removeClass('small-desktop');
      }
      if (body.hasClass('manual-hidden')) return;
      if ($(window).width() < 1024) {
        body.addClass('hide-menu');
      } else {
        body.removeClass('hide-menu');
      }
    });
    $(window).on('scroll', function () {
      var nav = $('.nav-row');
      var last = $('.last-update');
      if ($('div.fixed-header').length) {
        if ($(this).scrollTop() > 120) {
          $('div.fixed-header').fadeIn();
        } else {
          $('div.fixed-header').fadeOut();
        }
      }
      if ($(this).scrollTop() > 0) {
        nav.addClass('scrolled');
        last.addClass('scrolled');
      } else {
        nav.removeClass('scrolled');
        last.removeClass('scrolled');
      }
      if ($(this).scrollTop() > 65) {
        nav.addClass('active');
        nav.offset({
          left: 0
        });
      } else {
        nav.removeClass('active');
        nav.css({
          left: 0
        });
      }
    });
    var liActive,
      leftMenu = $('.user-blk'),
      content = $('div.content'),
      liClass = 'beyond';
    var liActiveHandler = function liActiveHandler() {
      liActive = leftMenu.find('li.active');
      if (liActive.length != 1) return;
      if (liActive.offset().top + liActive.outerHeight() > content.offset().top + content.outerHeight()) {
        if (!liActive.hasClass(liClass)) liActive.addClass(liClass);
      } else {
        if (liActive.hasClass(liClass)) liActive.removeClass(liClass);
      }
    };
    if (leftMenu.length == 1 && content.length == 1) {
      liActiveHandler();
      setInterval(function () {
        liActiveHandler();
      }, 700);
    }
    var oldRight = 0;
    $(window).on('resize scroll', function () {
      var header = $('.header-site');
      if ($(window).width() < 1000) {
        if ($(window).scrollLeft() == 0) {
          if (header.css('left') != '0px') {
            header.css('left', 0);
          }
        } else {
          if (header.css('left') != '-' + $(window).scrollLeft() + 'px') {
            header.css('left', -$(window).scrollLeft());
          }
        }
      } else {
        if (header.css('left') != '0px') {
          header.css('left', 0);
        }
      }
    }).trigger('resize');
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
    $(document).on('change keyup paste', '.row.error input:visible, .row.error textarea:visible, .row.error checkbox:visible', function () {
      var inputItem = $(this).closest('.input-item');
      if (inputItem.length == 0 || !$(this).hasClass('ng-invalid')) {
        $(this).closest('.error').removeClass('error');
      }
    }).on('change', '.styled-file input[type=file]', function () {
      var fullPath = $(this).val();
      if (fullPath) {
        var startIndex = fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/');
        var filename = fullPath.substring(startIndex);
        if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
          filename = filename.substring(1);
        }
        $('.file-name').text(filename);
      }
    }).on('click', '.spinnerable:not(form)', function () {
      $(this).addClass('loader');
    }).on('submit', 'form.spinnerable', function () {
      var button = $(this).find('[type="submit"]').first();
      if (!button.hasClass('loader')) {
        button.addClass('loader').attr('disabled', 'disabled');
      }
    });
    $(document).on('click', '.js-add-new-person, #add-person-btn, .js-persons-menu a[href="/user/connections"].add', function (e) {
      e.preventDefault();
      Promise.resolve(/*! AMD require */).then(function() { var __WEBPACK_AMD_REQUIRE_ARRAY__ = [__webpack_require__(/*! pages/agent/addDialog */ "./web/assets/awardwalletnewdesign/js/pages/agent/addDialog.js")]; (function (clickHandler) {
        clickHandler();
      }).apply(null, __WEBPACK_AMD_REQUIRE_ARRAY__);})['catch'](__webpack_require__.oe);
    });
    var $addNewPerson = $('<option>' + Translator.trans( /** @Desc("Add new person") */'add.new.person') + '</option>');
    var $prevSelected;
    if (!$('.main-body.business')) {
      $('.js-useragent-select').append($addNewPerson).on('change', function (el) {
        if ($(el.target).find('option:selected')[0].text === $addNewPerson[0].text) {
          $prevSelected.prop('selected', true);
          $('.js-add-new-person').trigger('click');
        } else {
          $prevSelected = $(el.target).find('option:selected');
        }
      }).trigger('change');
    }

    // Open person add popup if param addNewPerson is present
    if (document.location.href.match(/add-new-person=/)) $('#add-person-btn').trigger('click');
  });
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/lib/passwordComplexity.js":
/*!**********************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/lib/passwordComplexity.js ***!
  \**********************************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
var passwordComplexity;
(function () {
  function showPasswordNotice() {
    var frame = $(this).closest('table.inputFrame');
    if (frame.length === 0) {
      frame = $(this);
    }
    var div = $('#password-notice');
    div.prependTo(frame.parent());
    var left = frame.position().left + frame.width() + parsePosition(frame.css('padding-left')) + parsePosition(frame.css('padding-right')) + 5 - parsePosition(div.css('margin-left'));
    div.css('top', frame.position().top - parsePosition(div.css('margin-top'))).css('left', left).css('visibility', 'hidden').show();
    var height = 0;
    div.children().each(function (index, el) {
      el = $(el);
      height += el.height() + parsePosition(el.css('margin-top')) + parsePosition(el.css('margin-bottom')) + parsePosition(el.css('padding-top')) + parsePosition(el.css('padding-bottom'));
    });
    div.css('height', height + parsePosition(div.css('padding-top')) + parsePosition(div.css('padding-bottom'))).css('visibility', 'visible');
  }
  function hidePasswordNotice() {
    $('#password-notice').hide();
  }
  function trackComplexity(value) {
    var checks = {
      'password-length': value.length >= 8 && lengthInUtf8Bytes(value) <= 72,
      'lower-case': value.match(/[a-z]/) != null,
      'upper-case': value.match(/[A-Z]/) != null,
      'special-char': value.match(/[^a-zA-Z\s]/) != null
    };
    if (self.getLoginCallback) {
      var login = self.getLoginCallback().toLowerCase();
      var email = self.getEmailCallback().replace(/@.*$/, '').toLowerCase();
      checks.login = (value.toLowerCase().indexOf(login) === -1 || login === '') && (value.toLowerCase().indexOf(email) === -1 || email === '');
    }
    $('#meet-login').toggle(self.getLoginCallback != null);
    var errors = [];
    $.each(checks, function (key, match) {
      var meetDiv = $('#meet-' + key);
      meetDiv.toggleClass('allowed', match);
      if (!match) {
        errors.push(meetDiv.text());
      }
    });
    return errors;
  }
  function parsePosition(pos) {
    var result = parseInt(pos);
    if (isNaN(result)) {
      result = 0;
    }
    return result;
  }
  function lengthInUtf8Bytes(str) {
    // Matches only the 10.. bytes that are non-initial characters in a multi-byte sequence.
    var m = encodeURIComponent(str).match(/%[89ABab]/g);
    return str.length + (m ? m.length : 0);
  }
  var self = {
    passwordField: null,
    getLoginCallback: null,
    getEmailCallback: null,
    init: function init(passwordField, getLoginCallback, getEmailCallback) {
      self.passwordField = passwordField;
      self.getLoginCallback = getLoginCallback;
      self.getEmailCallback = getEmailCallback;
      passwordField.on("focus", null, null, showPasswordNotice).on("blur", null, null, hidePasswordNotice).on("keypress paste change keydown focus input", null, null, function () {
        setTimeout(function () {
          trackComplexity(self.passwordField.val());
        }, 0);
      });
      trackComplexity(self.passwordField.val());
    },
    getErrors: function getErrors() {
      return trackComplexity(self.passwordField.val());
    }
  };
  if (true) {
    !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
      return self;
    }).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
  } else {}
})();

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/agent/addDialog.js":
/*!*********************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/agent/addDialog.js ***!
  \*********************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts"), __webpack_require__(/*! routing */ "./assets/bem/ts/service/router.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function ($, dialog) {
  var dialogElement;

  // Add persons popup
  if (typeof dialogElement == 'undefined') {
    dialogElement = $('<div />').appendTo('body').html(Translator.trans( /** @Desc("You have two options, you can connect with another person on AwardWallet, or you can just create another name to better organize your rewards.") */'agents.popup.content'));
    dialog.createNamed('persons-menu', dialogElement, {
      width: '600',
      autoOpen: false,
      modal: true,
      title: Translator.trans( /** @Desc("Select connection type") */'agents.popup.header'),
      buttons: [{
        'text': Translator.trans( /** @Desc("Connect with another person") */'agents.popup.connect.btn'),
        'class': 'btn-blue spinnerable',
        'click': function click() {
          window.location.href = Routing.generate('aw_create_connection');
        }
      }, {
        'text': Translator.trans( /** @Desc("Just add a new name") */'agents.popup.add.btn'),
        'class': 'btn-blue spinnerable',
        'click': function click() {
          window.location.href = Routing.generate('aw_add_agent');
        }
      }],
      open: function open() {
        // Remove bottons focus
        $('.ui-dialog :button').blur();
        history.pushState(null, null, '?add-new-person=true');
      },
      close: function close() {
        history.back();
      }
    });
  }
  var clickHandler = function clickHandler() {
    dialogElement.dialog('open');
  };
  return clickHandler;
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/landing/controllers.js":
/*!*************************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/landing/controllers.js ***!
  \*************************************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
__webpack_require__(/*! core-js/modules/es.string.search.js */ "./node_modules/core-js/modules/es.string.search.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
__webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
__webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
__webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
__webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
__webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/*
global
whenRecaptchaLoaded,
renderRecaptcha,
whenRecaptchaSolved,
Dn698tCQ
*/

!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! lib/customizer */ "./web/assets/awardwalletnewdesign/js/lib/customizer.js"), __webpack_require__(/*! lib/passwordComplexity */ "./web/assets/awardwalletnewdesign/js/lib/passwordComplexity.js"), __webpack_require__(/*! lib/ga-wrapper */ "./web/assets/awardwalletnewdesign/js/lib/ga-wrapper.js"), __webpack_require__(/*! pages/landing/oauth */ "./web/assets/awardwalletnewdesign/js/pages/landing/oauth.js"), __webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! directives/dialog */ "./web/assets/awardwalletnewdesign/js/directives/dialog.js"), __webpack_require__(/*! angular-boot */ "./assets/bem/ts/shim/angular-boot.js"), __webpack_require__(/*! routing */ "./assets/bem/ts/service/router.ts"), __webpack_require__(/*! lib/design */ "./web/assets/awardwalletnewdesign/js/lib/design.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (customizer, passwordComplexity, gaWrapper, initOauthLinks) {
  function initRecaptcha(scope) {
    setTimeout(function () {
      whenRecaptchaLoaded(function () {
        renderRecaptcha(scope);
      });
    }, 100);
  }
  angular.module('landingPage-ctrl', ['dialog-directive']).service('User', function () {
    return {
      login: '',
      _remember_me: true
    };
  }).controller('registerBusinessCtrl', ['$state', function ($state) {
    if (!/^business/.test(window.location.hostname)) {
      return $state.go('register');
    }
    function addError(field, text) {
      var error = $('<div class="req" data-role="tooltip" title="' + text + '"><i class="icon-warning-small"></i></div>');
      $(field).before(error);
      customizer.initTooltips(error);
      error.tooltip('open').off('mouseenter mouseleave');
      field.parents('.row').addClass('error');
      $('#register-button').prop('disabled', true);
    }
    var form = $('#registerForm');
    form.on('submit', function (e) {
      e.preventDefault();
      if (passwordComplexity.getErrors().length > 0) {
        $('#user_pass_Password').focus();
        return;
      }
      $('#register-button').prop('disabled', true).addClass('loader');
      whenRecaptchaSolved(function (recaptcha_code) {
        var data = new FormData($('#registerForm')[0]);
        data.append('recaptcha', recaptcha_code);
        $.ajax({
          url: Routing.generate('aw_users_register_business'),
          data: data,
          method: 'post',
          processData: false,
          contentType: false,
          success: function success(data) {
            if (data.errors && data.errors.length > 0) {
              var error = data.errors[0];
              addError($('[name="' + error.name + '"]'), error.errorText);
            } else {
              document.location.href = Routing.generate('aw_business_account_list');
            }
            $('#register-button').removeClass('loader');
          }
        });
      });
    }).find('input').on('keyup paste change', function () {
      var erroredField = form.find('.req');
      if (erroredField.length) {
        erroredField.tooltip('destroy').remove();
      }
      $('#register-button').prop('disabled', false);
    });
    initRecaptcha();
    passwordComplexity.init($('#user_pass_Password'), function () {
      return $('#user_login').val();
    }, function () {
      return $('#user_email').val();
    });
  }]).controller('registerCtrl', ['$scope', '$http', '$timeout', '$location', 'dialogService', '$state', function ($scope, $http, $timeout, $location, dialogService, $state) {
    if (/^business/.test(window.location.hostname)) {
      return $state.go('registerBusiness');
    }
    document.title = Translator.trans('meta.title.register');
    function focusOnError() {
      var filter = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
      setTimeout(function () {
        var row = $('.row.error' + filter);
        if (row.length) {
          $('.req', row).mouseover();
          $('input', row).focus();
        }
      }, 100);
    }
    var stepInit = {};
    function initFirstStep() {
      initOauthLinks(function () {
        $scope.setStep(3);
        $scope.$apply();
      }, function () {
        $scope.setStep(1);
        $scope.$apply();
      });
    }
    function initSecondStep() {
      passwordComplexity.init($('#password'), null, function () {
        return $('#registration_email').val();
      });
    }
    function validateForm(form) {
      var deferred = $.Deferred();
      if (form.$invalid) {
        return deferred.reject();
      }
      if (passwordComplexity.getErrors().length > 0) {
        return deferred.reject('password');
      }
      $.when($.post('/user/check_email', {
        value: $scope.form.email
      })).done(function (result) {
        if (result === 'false') {
          $scope.registerForm.email.$setValidity("taken", false);
          return deferred.reject();
        }
        if (result === 'locked') {
          $scope.registerForm.email.$setValidity("locked", false);
          return deferred.reject();
        }
        $scope.emailChecked = true;
        deferred.resolve();
      }).fail(function () {
        deferred.reject();
      });
      return deferred.promise();
    }
    var uriParams = $location.search();
    $scope.isStep = function (s) {
      return s === $scope.step;
    };
    $scope.setStep = function (s) {
      $scope.step = s;
      if (!stepInit[s]) {
        if (s === 1) {
          initFirstStep();
        } else if (s === 2) {
          initSecondStep();
        }
        stepInit[s] = true;
      }
    };
    $scope.setStep(1);
    $scope.submitted = false;
    $scope.showPass = false;
    $scope.form = {
      email: window.inviteEmail || null,
      pass: null,
      firstname: window.firstName || null,
      lastname: window.lastName || null
    };
    $scope.coupon = uriParams.code || uriParams.Code || null;
    $scope.emailChecked = false;
    $scope.toggleShowPass = function () {
      $scope.showPass = !$scope.showPass;
    };
    $scope.resetErrors = function () {
      $scope.submitted = false;
      $scope.registerForm.email.$setValidity("taken", true);
      $scope.registerForm.email.$setValidity("locked", true);
    };
    $scope.submit = function (form) {
      $scope.submitted = true;
      $scope.spinner = true;
      validateForm(form).done(function () {
        $timeout(function () {
          whenRecaptchaSolved(function (captcha_key) {
            $http({
              url: Routing.generate('aw_users_register', uriParams.BackTo ? {
                "BackTo": uriParams.BackTo
              } : {}),
              method: 'post',
              data: {
                user: $scope.form,
                coupon: $scope.coupon,
                recaptcha: captcha_key
              }
            }).then(function (_ref) {
              var data = _ref.data;
              if (data.success === true) {
                console.log('sending registered gtag event');
                gaWrapper('event', 'registered', {
                  'event_category': 'user',
                  'event_label': 'desktop',
                  'value': 1,
                  'event_callback': function event_callback() {
                    if (uriParams.BackTo) {
                      var anchor = document.createElement('a');
                      anchor.href = uriParams.BackTo;
                      if (anchor.protocol !== "javascript:") {
                        window.location.href = uriParams.BackTo.replace(/^.*\/\/[^\/]+/, '');
                      }
                    }
                    if ($scope.coupon) {
                      window.location.href = data.beta ? Routing.generate('aw_users_usecoupon', {
                        back: data.targetPage
                      }) : '/user/useCoupon.php?Code=' + $scope.coupon;
                    } else {
                      window.location.href = data.beta ? data.targetPage : 'account/list';
                    }
                  }
                });
              } else {
                $scope.spinner = false;
                var error = data.errors;
                if (error.indexOf('ERROR:') !== -1) {
                  error = error.substring(error.indexOf('ERROR:') + 7);
                }
                dialogService.alert(error, Translator.trans("alerts.error"));
              }
            }).always(function () {
              $scope.spinner = false;
            });
          });
        }, 0);
      }).fail(function (field) {
        focusOnError(':first');
        if (field === 'password') {
          $('#password').focus();
        }
        $scope.spinner = false;
        $timeout(function () {
          $scope.$apply();
        });
      });
    };
    $('html, body').animate({
      scrollTop: $('#register').offset().top - 60
    }, 1000);
  }]).controller('loginCtrl', ['$scope', '$http', '$location', '$timeout', '$sce', 'User', function ($scope, $http, $location, $timeout, $sce, User) {
    document.title = Translator.trans('meta.title.login');
    var uriParams = $location.search();
    var prevStep;
    $scope.user = User;
    $scope.step = 'login';
    $scope.informationMessage = false;
    $scope.answer = '';
    $scope.recaptcha = '';
    if ($location.search().error) {
      $scope.error = $location.search().error;
      $scope.user.login = $('#username').data('login-hint');
    }
    $scope.submitButton = {
      login: Translator.trans( /** @Desc("Sign in") */'sign-in.button'),
      otcRecovery: Translator.trans( /** @Desc("Recover") */'login.button.recovery'),
      question: Translator.trans('login.button.login')
    };
    $scope.submitButton.otc = $scope.submitButton.login;
    initOauthLinks(function () {
      prevStep = $scope.step;
      $scope.step = 'mb_question';
      $scope.$apply();
    }, function () {
      $scope.step = prevStep;
      $scope.$apply();
    });

    // TODO Trace in ie8
    //$timeout(function () {
    //	if(typeof navigator != 'undefined' && !navigator.userAgent.match('Firefox') && $('input:-webkit-autofill').length){
    //		$scope.autofill = true;
    //	}
    //}, 250);

    $scope.popupTitle = {
      login: $sce.trustAsHtml(Translator.trans('login.title.login')),
      mb_question: $sce.trustAsHtml(Translator.trans('login.title.login')),
      question: $sce.trustAsHtml(Translator.trans('login.title.login')),
      otcRecovery: $sce.trustAsHtml(Translator.trans( /** @Desc("Recovery") */'login.title.recovery'))
    };
    $scope.popupTitle.otc = $scope.popupTitle.login;
    $scope.otcInputLabel = Translator.trans( /* @Desc("One-time code") */'login.otc');
    $scope.otcInputHint = null;
    $scope.submit = function () {
      if ('otc' !== $scope.step && 'question' !== $scope.step) {
        $scope.user._otc = null;
      }
      if ('otcRecovery' !== $scope.step) {
        delete $scope.user._otc_recovery;
      }
      $scope.showForgotLink = false;
      $scope.spinner = true;
      // ie11 fix, see #10625
      var cookie = $.cookie();
      if (Object.prototype.hasOwnProperty.call(cookie, 'XSRF-TOKEN')) {
        cookie = cookie['XSRF-TOKEN'];
      } else {
        cookie = $.cookie('XSRF-TOKEN');
      }
      $scope.user.FormToken = cookie;
      $http({
        url: Routing.generate('aw_login_client_check'),
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(function (res) {
        Dn698tCQ = eval(res.data.expr); // eslint-disable-line no-global-assign
        $scope.user._csrf_token = res.data.csrf_token;
        if ($scope.recaptchRequired) {
          console.log('recaptcha required on submit');
          initRecaptcha($scope);
          whenRecaptchaSolved(function (recaptcha_code) {
            $scope.recaptchRequired = false;
            $scope.recaptcha = recaptcha_code;
            console.log('sent recaptcha on submit');
            $scope.tryLogin();
          });
          return;
        }
        $scope.tryLogin();
      });
    };
    $scope.tryLogin = function () {
      var data = angular.copy($scope.user);
      if ($scope.step === 'question') {
        data._otc = $scope.question.question + '=' + $scope.answer;
      }
      data.recaptcha = $scope.recaptcha;
      $http({
        url: Routing.generate('aw_users_logincheck'),
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Scripted': Dn698tCQ || Math.random
        },
        data: $.param(data)
        // todo fail!
      }).then(function (res) {
        var data = res.data;
        if (_typeof(data) === 'object') {
          if (data.success) {
            if (window.inviteCode) {
              window.location.href = Routing.generate('aw_invite_confirm', {
                'shareCode': window.inviteCode
              });
              return;
            }
            if (sessionStorage.backUrl && sessionStorage.backUrl.indexOf("/logout") === -1 && sessionStorage.backUrl.indexOf("/loginFrame") === -1) {
              window.location.href = sessionStorage.backUrl;
              return;
            }
            if (uriParams.BackTo) {
              var anchor = document.createElement('a');
              anchor.href = uriParams.BackTo;
              if (anchor.protocol !== "javascript:") {
                window.location.href = uriParams.BackTo.replace(/^.*\/\/[^\/]+/, '');
                return;
              }
            }
            if ($scope.step === 'otcRecovery' && $scope.user._otc_recovery) {
              // TODO: change to routing
              window.location.href = Routing.generate('aw_profile_2factor');
            } else {
              window.location.href = '/';
            }
          } else {
            $scope.spinner = false;
            if ((null !== $scope.user._otc || '' !== $scope.user._otc) && 'otcRecovery' !== $scope.step && !!data.otcRequired) {
              $scope.otcInputHint = data.otcInputHint;
              if (typeof data.otcInputLabel === 'string') {
                $scope.step = 'otc';
                $scope.otcInputLabel = data.otcInputLabel;
                $timeout(function () {
                  $('#otc').focus();
                }, 200);
              } else {
                $scope.step = 'question';
                var selected = 0;
                $scope.questions = angular.copy(data.otcInputLabel);
                $scope.selectQuestionText = Translator.trans( /** @Desc("Please select a question") */"select-question");
                $scope.questions.unshift({
                  "question": $scope.selectQuestionText,
                  "maskInput": false
                });
                if (_typeof($scope.question) === 'object') for (var idx in $scope.questions) {
                  if (Object.prototype.hasOwnProperty.call($scope.questions, idx) && $scope.questions[idx].question === $scope.question.question) {
                    selected = idx;
                    break;
                  }
                }
                $scope.question = $scope.questions[selected];
                if ($scope.answer !== "" && $scope.answer !== null) $scope.error = data.message;else $scope.informationMessage = data.message;
                if (data.message.indexOf("CSRF") !== -1) $scope.csrf = true;
                $scope.answer = '';
                $timeout(function () {
                  $('#question-answer').focus();
                }, 200);
                $scope.questionChanged();
              }
              $scope.otcShowRecovery = data.otcShowRecovery;
            }
            if (data.badCredentials) {
              $scope.showForgotLink = true;
            }
            if ('otcRecovery' === $scope.step && null !== $scope.user._otc_recovery && '' !== $scope.user._otc_recovery || 'otc' === $scope.step && null !== $scope.user._otc && '' !== $scope.user._otc || 'question' === $scope.step && null !== $scope.answer && '' !== $scope.answer || 'login' === $scope.step) {
              $scope.error = data.message;
              if (data.message.indexOf("CSRF") !== -1) {
                $scope.csrf = true;
              }
            } else {
              $scope.informationMessage = data.message;
            }
            if (data.recaptchaRequired) {
              console.log('recaptcha required');
              $scope.recaptchRequired = true;
              if (!$scope.recaptcha) {
                console.log('retry submit with recaptcha');
                $scope.clearMessages();
                setTimeout($scope.submit, 10);
              }
            }
          }
        }
      });
    };
    $scope.questionChanged = function () {
      $('#question-answer').attr('type', $scope.question.maskInput ? "password" : "text");
    };
    $scope.clearMessages = function () {
      $scope.error = false;
      //$scope.informationMessage = false;
    };

    $scope.back = function () {
      $scope.user._otc = null;
      $scope.answer = '';
      $scope.user._otc_recovery = null;
      switch ($scope.step) {
        case 'login':
          return;
        case 'otc':
        case 'question':
          delete $scope.user._otc;
          $scope.step = 'login';
          $timeout(function () {
            $('#username').focus();
          });
          break;
        case 'otcRecovery':
          $timeout(function () {
            $('#otc').focus();
          });
          $scope.step = 'otc';
          break;
      }
      $scope.clearMessages();
    };
    $scope.doStep = function (step) {
      switch (step) {
        case 'login':
          break;
        case 'otc':
          break;
        case 'otcRecovery':
          $timeout(function () {
            $('#otcRecovery').focus();
          });
          break;
      }
      $scope.clearMessages();
      $scope.step = step;
    };
  }]).controller('restoreCtrl', ['$scope', '$http', '$timeout', 'User', function ($scope, $http, $timeout, User) {
    $scope.success = false;
    $scope.error = false;
    $scope.errorText = false;
    $scope.user = {
      username: User.login
    };
    $scope.submitted = false;
    $scope.submit = function () {
      if ($scope.restoreForm.$invalid) {
        $scope.submitted = true;
        return false;
      } else {
        $scope.spinner = true;
        $http({
          url: Routing.generate('aw_users_restore'),
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          data: $.param($scope.user)
          // todo fail!
        }).then(function (_ref2) {
          var data = _ref2.data;
          $scope.spinner = false;
          if (data.success) $scope.success = true;else {
            if (data.error) {
              $scope.error = false;
              $scope.errorText = data.error;
            } else {
              $scope.error = true;
            }
            $timeout(function () {
              $('#forgot-password').select().focus();
            });
          }
        });
      }
    };
    $scope.change = function () {
      $scope.submitted = false;
      $scope.error = false;
      $scope.errorText = false;
    };
  }]).controller('homeCtrl', ['$scope', '$state', '$location', '$http', 'dialogService', function ($scope, $state, $location, $http, dialogService) {
    document.title = Translator.trans('meta.title');
    var uriParams = $location.search();
    function createErrorDialog() {
      dialogService.fastCreate("Error", "Currently we are limiting our users to send no more than 100 lookup requests per 5 minutes. You have reached your limit please come back in 5 minutes if you wish to continue searching.", true, true, [{
        text: Translator.trans('button.ok'),
        click: function click() {
          $(this).dialog('close');
        },
        'class': 'btn-blue'
      }], 500);
    }
    function autocompleteSource(request, response) {
      merchantInput.addClass('loading-input').removeClass('search-input');
      $http.post(Routing.generate('aw_merchant_lookup_data'), $.param({
        query: request.term
      }), {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(function (_ref3) {
        var data = _ref3.data;
        var result;
        if ($.isEmptyObject(data)) {
          result = [{
            label: 'No merchants found',
            value: ""
          }];
        } else {
          result = data;
        }
        response(result);
        merchantInput.removeClass('loading-input').addClass('search-input');
      }).catch(function () {
        createErrorDialog();
        merchantInput.removeClass('loading-input').addClass('search-input');
      });
    }
    $scope.$on('$stateChangeSuccess', function (ev, toState, toParams, fromState) {
      if (uriParams.BackTo && fromState.name !== 'login') {
        $state.go('login');
      }
    });
    var merchantInput = $('#merchant');
    merchantInput.removeClass('loading-input').addClass('search-input');
    merchantInput.autocomplete({
      minLength: 3,
      delay: 500,
      source: function source(request, response) {
        autocompleteSource(request, response);
      },
      select: function select(event, ui) {
        window.open(($('html:first').hasClass('mobile-device') ? '/m' : '') + Routing.generate('aw_merchant_lookup') + '/' + ui.item.nameToUrl, '_blank');
      },
      create: function create() {
        $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
          var label = item.label,
            category = item.category;
          if (!label) {
            return;
          }
          var element = $('<a></a>').append($("<span></span>").html("".concat(label, "&nbsp;")));
          if (category) {
            element.append($("<span></span>").addClass("blue").html("(".concat(category, ")")));
          }
          return $('<li></li>').data("item.autocomplete", item).append(element).appendTo(ul);
        };
      },
      open: function open() {
        $("ul.ui-menu").width($(this).innerWidth());
      }
    }).off('blur');
  }]);
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/landing/directives.js":
/*!************************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/landing/directives.js ***!
  \************************************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.search.js */ "./node_modules/core-js/modules/es.string.search.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular-boot */ "./assets/bem/ts/shim/angular-boot.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
  angular.module('landingPage-dir', []).directive('autofill', ['$location', '$timeout', function ($location, $timeout) {
    var setValue = function setValue(name, model, scope) {
      var value = model.$viewValue;
      if (Object.prototype.hasOwnProperty.call($location.search(), name) && !value) {
        model.$setViewValue($location.search()[name]);
        model.$render();
        scope.$apply();
      }
    };
    return {
      restrict: 'A',
      require: 'ngModel',
      link: function link(scope, elem, attrs, ctrl) {
        $timeout(function () {
          setValue(attrs.autofill, ctrl, scope);
        });
        scope.$on('$locationChangeSuccess', function () {
          setValue(attrs.autofill, ctrl, scope);
        });
      }
    };
  }]).directive('samePassword', [function () {
    return {
      require: 'ngModel',
      link: function link(scope, elem, attrs, ctrl) {
        var originalPass = '#' + attrs.samePassword;
        elem.add(originalPass).on('keyup', function () {
          scope.$apply(function () {
            var originalPassValue = $(originalPass).val();
            var samePassValue = elem.val();
            var validity = originalPassValue === samePassValue || samePassValue === '' || originalPassValue === '';
            ctrl.$setValidity('samePassword', validity);
          });
        });
      }
    };
  }]);
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/landing/main.js":
/*!******************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/landing/main.js ***!
  \******************************************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! angular-boot */ "./assets/bem/ts/shim/angular-boot.js"), __webpack_require__(/*! routing */ "./assets/bem/ts/service/router.ts"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts"), __webpack_require__(/*! angular-ui-router */ "./web/assets/common/vendors/angular-ui-router/release/angular-ui-router.js"), __webpack_require__(/*! directives/customizer */ "./web/assets/awardwalletnewdesign/js/directives/customizer.js"), __webpack_require__(/*! directives/autoFocus */ "./web/assets/awardwalletnewdesign/js/directives/autoFocus.js"), __webpack_require__(/*! pages/landing/controllers */ "./web/assets/awardwalletnewdesign/js/pages/landing/controllers.js"), __webpack_require__(/*! pages/landing/directives */ "./web/assets/awardwalletnewdesign/js/pages/landing/directives.js")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
  angular.module('landingPage', ['ui.router', 'customizer-directive', 'auto-focus-directive', 'appConfig', 'landingPage-ctrl', 'landingPage-dir']).config(['$stateProvider', '$urlRouterProvider', '$locationProvider', function ($stateProvider, $urlRouterProvider, $locationProvider) {
    // Video
    $('#video-btn').on('click', function (e) {
      e.preventDefault();
      $(this).replaceWith($('<iframe src="//player.vimeo.com/video/319469220?color=4684c4" width="545" height="307" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>'));
    });
    $urlRouterProvider.otherwise(function ($injector, $location) {
      // if ('/index.php' !== $location.path()) {
      //     location.href = $location.path();
      // }
    });
    $urlRouterProvider.when(/^\/[a-z]{2}\//, function ($location) {
      if ($location.path().substr(0, 4) !== "/".concat(locale, "/")) {
        console.log('redirect to ', $location.path());
        location.href = $location.path();
        return true;
      } else {
        return false;
      }
    });
    $stateProvider.state('home', {
      url: '/',
      controller: 'homeCtrl'
    }).state('loc-home', {
      url: '/{locale:[a-z][a-z]}/',
      controller: 'homeCtrl'
    }).state('register', {
      url: '/register',
      templateUrl: '/register',
      controller: 'registerCtrl'
    }).state('loc-register', {
      url: '/{locale:[a-z][a-z]}/register',
      templateUrl: '/register',
      controller: 'registerCtrl'
    }).state('registerBusiness', {
      url: '/registerBusiness',
      templateUrl: '/registerBusiness',
      controller: 'registerBusinessCtrl'
    }).state('loc-registerBusiness', {
      url: '/{locale:[a-z][a-z]}/registerBusiness',
      templateUrl: '/registerBusiness',
      controller: 'registerBusinessCtrl'
    }).state('login', {
      url: '/login',
      templateUrl: '/login',
      controller: 'loginCtrl'
    }).state('loc-login', {
      url: '/{locale:[a-z][a-z]}/login',
      templateUrl: '/login',
      controller: 'loginCtrl'
    }).state('restore', {
      url: '/restore',
      templateUrl: '/restore',
      controller: 'restoreCtrl'
    }).state('loc-restore', {
      url: '/{locale:[a-z][a-z]}/restore',
      templateUrl: '/restore',
      controller: 'restoreCtrl'
    });
    $locationProvider.html5Mode({
      enabled: true,
      rewriteLinks: false
    });
  }]).run();
  $(document).ready(function () {
    var app = document.getElementById('main-body');
    if (app) {
      angular.bootstrap(app, ['landingPage']);
    } else {
      angular.bootstrap(document.getElementsByClassName('page-landing__container')[0], ['landingPage']);
    }
  });
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/landing/oauth.js":
/*!*******************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/landing/oauth.js ***!
  \*******************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.search.js */ "./node_modules/core-js/modules/es.string.search.js");
__webpack_require__(/*! core-js/modules/es.object.assign.js */ "./node_modules/core-js/modules/es.object.assign.js");
__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! lib/utils */ "./web/assets/awardwalletnewdesign/js/lib/utils.js"), __webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! routing */ "./assets/bem/ts/service/router.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function ($, utils) {
  return function (onShowQuestion, onHideQuestion) {
    function getKeyTypeName(type) {
      return "".concat(type, "_mb_answer");
    }
    function getAnswer(type) {
      var answer = localStorage.getItem(getKeyTypeName(type));
      if (null === answer) {
        answer = utils.getCookie(getKeyTypeName(type));
        if ('undefined' !== typeof answer) {
          return answer;
        }
      }
      return null;
    }
    function saveAnswer(type, answer) {
      localStorage.setItem(getKeyTypeName(type), answer);
    }
    function getQueryParams() {
      if (!window.location.search) {
        return {};
      }
      var result = {};
      var query = window.location.search.substring(1);
      var vars = query.split('&');
      for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        var name = decodeURIComponent(pair[0]);
        if (name === 'error') {
          continue;
        }
        result[name] = decodeURIComponent(pair[1]);
      }
      return result;
    }
    function redirect(type, action, mailboxAccess) {
      document.location.href = Routing.generate('aw_usermailbox_oauth', Object.assign(getQueryParams(), {
        'type': type,
        'action': action,
        'mailboxAccess': mailboxAccess,
        'rememberMe': $('#remember_me').is(':checked') && action === 'login'
      }));
    }
    function noop() {}
    var questionElem = $('#scan-mailbox-question');
    $('.oauth-buttons-list a').on('click', function (event) {
      event.preventDefault();
      var link = $(this);
      var type = link.data('type');
      var action = link.data('action');
      if (link.data('mailbox-support') !== 'off') {
        var answer = getAnswer(type);
        if (null !== answer || /^business/.test(window.location.hostname)) {
          redirect(type, action, answer || false);
        } else {
          questionElem.data('type', type);
          questionElem.data('action', action);
          questionElem.show();
          (onShowQuestion || noop)();
        }
        return;
      }
      redirect(type, action, false);
    });
    questionElem.find('button').on('click', function (event) {
      event.preventDefault();
      var answer = $(this).data('mailbox-access');
      var type = questionElem.data('type');
      var action = questionElem.data('action');
      saveAnswer(type, answer);
      questionElem.hide();
      (onHideQuestion || noop)();
      redirect(type, action, answer);
    });
  };
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./assets/bem/block/page/landing/popups.entry.ts":
/*!*******************************************************!*\
  !*** ./assets/bem/block/page/landing/popups.entry.ts ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _page_landing_less__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./page-landing.less */ "./assets/bem/block/page/landing/page-landing.less");
/* harmony import */ var pages_landing_main__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! pages/landing/main */ "./web/assets/awardwalletnewdesign/js/pages/landing/main.js");
/* harmony import */ var pages_landing_main__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(pages_landing_main__WEBPACK_IMPORTED_MODULE_1__);
 // old js: angular 1, login, register popups, etc


/***/ }),

/***/ "./node_modules/core-js/internals/correct-is-regexp-logic.js":
/*!*******************************************************************!*\
  !*** ./node_modules/core-js/internals/correct-is-regexp-logic.js ***!
  \*******************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/core-js/internals/well-known-symbol.js");

var MATCH = wellKnownSymbol('match');

module.exports = function (METHOD_NAME) {
  var regexp = /./;
  try {
    '/./'[METHOD_NAME](regexp);
  } catch (error1) {
    try {
      regexp[MATCH] = false;
      return '/./'[METHOD_NAME](regexp);
    } catch (error2) { /* empty */ }
  } return false;
};


/***/ }),

/***/ "./node_modules/core-js/internals/not-a-regexp.js":
/*!********************************************************!*\
  !*** ./node_modules/core-js/internals/not-a-regexp.js ***!
  \********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var isRegExp = __webpack_require__(/*! ../internals/is-regexp */ "./node_modules/core-js/internals/is-regexp.js");

var $TypeError = TypeError;

module.exports = function (it) {
  if (isRegExp(it)) {
    throw $TypeError("The method doesn't accept regular expressions");
  } return it;
};


/***/ }),

/***/ "./node_modules/core-js/modules/es.array.includes.js":
/*!***********************************************************!*\
  !*** ./node_modules/core-js/modules/es.array.includes.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var $includes = (__webpack_require__(/*! ../internals/array-includes */ "./node_modules/core-js/internals/array-includes.js").includes);
var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/core-js/internals/fails.js");
var addToUnscopables = __webpack_require__(/*! ../internals/add-to-unscopables */ "./node_modules/core-js/internals/add-to-unscopables.js");

// FF99+ bug
var BROKEN_ON_SPARSE = fails(function () {
  // eslint-disable-next-line es/no-array-prototype-includes -- detection
  return !Array(1).includes();
});

// `Array.prototype.includes` method
// https://tc39.es/ecma262/#sec-array.prototype.includes
$({ target: 'Array', proto: true, forced: BROKEN_ON_SPARSE }, {
  includes: function includes(el /* , fromIndex = 0 */) {
    return $includes(this, el, arguments.length > 1 ? arguments[1] : undefined);
  }
});

// https://tc39.es/ecma262/#sec-array.prototype-@@unscopables
addToUnscopables('includes');


/***/ }),

/***/ "./node_modules/core-js/modules/es.string.includes.js":
/*!************************************************************!*\
  !*** ./node_modules/core-js/modules/es.string.includes.js ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var uncurryThis = __webpack_require__(/*! ../internals/function-uncurry-this */ "./node_modules/core-js/internals/function-uncurry-this.js");
var notARegExp = __webpack_require__(/*! ../internals/not-a-regexp */ "./node_modules/core-js/internals/not-a-regexp.js");
var requireObjectCoercible = __webpack_require__(/*! ../internals/require-object-coercible */ "./node_modules/core-js/internals/require-object-coercible.js");
var toString = __webpack_require__(/*! ../internals/to-string */ "./node_modules/core-js/internals/to-string.js");
var correctIsRegExpLogic = __webpack_require__(/*! ../internals/correct-is-regexp-logic */ "./node_modules/core-js/internals/correct-is-regexp-logic.js");

var stringIndexOf = uncurryThis(''.indexOf);

// `String.prototype.includes` method
// https://tc39.es/ecma262/#sec-string.prototype.includes
$({ target: 'String', proto: true, forced: !correctIsRegExpLogic('includes') }, {
  includes: function includes(searchString /* , position = 0 */) {
    return !!~stringIndexOf(
      toString(requireObjectCoercible(this)),
      toString(notARegExp(searchString)),
      arguments.length > 1 ? arguments[1] : undefined
    );
  }
});


/***/ }),

/***/ "./assets/bem/block/page/landing/page-landing.less":
/*!*********************************************************!*\
  !*** ./assets/bem/block/page/landing/page-landing.less ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js","vendors-node_modules_core-js_internals_array-slice-simple_js-node_modules_core-js_internals_f-4bac30","vendors-node_modules_core-js_modules_es_string_replace_js","vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444","vendors-node_modules_core-js_internals_array-method-is-strict_js-node_modules_core-js_modules-ea2489","vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41","vendors-node_modules_core-js_modules_es_promise_js-node_modules_core-js_modules_web_dom-colle-054322","vendors-node_modules_core-js_internals_string-trim_js-node_modules_core-js_internals_this-num-d4bf43","vendors-node_modules_core-js_modules_es_array_from_js-node_modules_core-js_modules_es_date_to-6fede4","vendors-node_modules_core-js_modules_es_number_to-fixed_js-node_modules_intl_index_js","assets_bem_ts_service_translator_ts","web_assets_common_vendors_jquery_dist_jquery_js","web_assets_common_vendors_jquery-ui_jquery-ui_min_js","assets_bem_ts_service_router_ts","web_assets_awardwalletnewdesign_js_lib_dialog_js","web_assets_common_vendors_date-time-diff_lib_date-time-diff_js","web_assets_awardwalletnewdesign_js_directives_autoFocus_js-web_assets_awardwalletnewdesign_js-b24d40"], () => (__webpack_exec__("./assets/bem/block/page/landing/popups.entry.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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O0FBQ3hDLHVCQUF1QixtQkFBTyxDQUFDLCtGQUFpQzs7QUFFaEU7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDOztBQUVEO0FBQ0E7QUFDQSxJQUFJLHdEQUF3RDtBQUM1RDtBQUNBO0FBQ0E7QUFDQSxDQUFDOztBQUVEO0FBQ0E7Ozs7Ozs7Ozs7OztBQ3JCYTtBQUNiLFFBQVEsbUJBQU8sQ0FBQyx1RUFBcUI7QUFDckMsa0JBQWtCLG1CQUFPLENBQUMscUdBQW9DO0FBQzlELGlCQUFpQixtQkFBTyxDQUFDLG1GQUEyQjtBQUNwRCw2QkFBNkIsbUJBQU8sQ0FBQywyR0FBdUM7QUFDNUUsZUFBZSxtQkFBTyxDQUFDLDZFQUF3QjtBQUMvQywyQkFBMkIsbUJBQU8sQ0FBQyx5R0FBc0M7O0FBRXpFOztBQUVBO0FBQ0E7QUFDQSxJQUFJLDBFQUEwRTtBQUM5RTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLENBQUM7Ozs7Ozs7Ozs7Ozs7QUNwQkQiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL3dlYi9hc3NldHMvYXdhcmR3YWxsZXRuZXdkZXNpZ24vanMvZGlyZWN0aXZlcy9kaWFsb2cuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL2xpYi9kZXNpZ24uanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL2xpYi9wYXNzd29yZENvbXBsZXhpdHkuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL3BhZ2VzL2FnZW50L2FkZERpYWxvZy5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL3dlYi9hc3NldHMvYXdhcmR3YWxsZXRuZXdkZXNpZ24vanMvcGFnZXMvbGFuZGluZy9jb250cm9sbGVycy5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL3dlYi9hc3NldHMvYXdhcmR3YWxsZXRuZXdkZXNpZ24vanMvcGFnZXMvbGFuZGluZy9kaXJlY3RpdmVzLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9hd2FyZHdhbGxldG5ld2Rlc2lnbi9qcy9wYWdlcy9sYW5kaW5nL21haW4uanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL3BhZ2VzL2xhbmRpbmcvb2F1dGguanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL2Jsb2NrL3BhZ2UvbGFuZGluZy9wb3B1cHMuZW50cnkudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9pbnRlcm5hbHMvY29ycmVjdC1pcy1yZWdleHAtbG9naWMuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9pbnRlcm5hbHMvbm90LWEtcmVnZXhwLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vbm9kZV9tb2R1bGVzL2NvcmUtanMvbW9kdWxlcy9lcy5hcnJheS5pbmNsdWRlcy5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL21vZHVsZXMvZXMuc3RyaW5nLmluY2x1ZGVzLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS9ibG9jay9wYWdlL2xhbmRpbmcvcGFnZS1sYW5kaW5nLmxlc3M/ZjkwYSJdLCJzb3VyY2VzQ29udGVudCI6WyJkZWZpbmUoWydhbmd1bGFyJywgJ2pxdWVyeS1ib290JywgJ2xpYi9kaWFsb2cnLCAnanF1ZXJ5dWknXSwgZnVuY3Rpb24gKGFuZ3VsYXIsICQsIGRpYWxvZykge1xuICAgIGFuZ3VsYXIgPSBhbmd1bGFyICYmIGFuZ3VsYXIuX19lc01vZHVsZSA/IGFuZ3VsYXIuZGVmYXVsdCA6IGFuZ3VsYXI7XG5cbiAgICBhbmd1bGFyLm1vZHVsZSgnZGlhbG9nLWRpcmVjdGl2ZScsIFtdKVxuICAgICAgICAuc2VydmljZSgnZGlhbG9nU2VydmljZScsIFtmdW5jdGlvbigpe1xuICAgICAgICAgICAgcmV0dXJuIGRpYWxvZztcbiAgICAgICAgfV0pXG5cbiAgICAgICAgLmRpcmVjdGl2ZSgnZGlhbG9nJywgWydkaWFsb2dTZXJ2aWNlJywgZnVuY3Rpb24oZGlhbG9nU2VydmljZSkge1xuICAgICAgICAgICAgJ3VzZSBzdHJpY3QnO1xuICAgICAgICAgICAgdmFyIG9wdGlvbnMgPSAkLnVuaXF1ZShPYmplY3Qua2V5cygkLnVpLmRpYWxvZy5wcm90b3R5cGUub3B0aW9ucykpO1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICByZXN0cmljdDogJ0UnLFxuICAgICAgICAgICAgICAgIHNjb3BlOiBvcHRpb25zLnJlZHVjZShmdW5jdGlvbihhY2MsIHZhbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgYWNjW3ZhbF0gPSBcIiZcIjsgcmV0dXJuIGFjYztcbiAgICAgICAgICAgICAgICAgICAgfSwge2JpbmRUb1Njb3BlOiAnPSd9KSxcbiAgICAgICAgICAgICAgICByZXBsYWNlOiB0cnVlLFxuICAgICAgICAgICAgICAgIHRyYW5zY2x1ZGU6IHRydWUsXG4gICAgICAgICAgICAgICAgdGVtcGxhdGU6ICc8ZGl2IHN0eWxlPVwiZGlzcGxheTpub25lXCIgZGF0YS1uZy10cmFuc2NsdWRlPjwvZGl2PicsXG4gICAgICAgICAgICAgICAgbGluazogZnVuY3Rpb24gKHNjb3BlLCBlbGVtZW50LCBhdHRyLCBjdHJsLCB0cmFuc2NsdWRlKSB7XG4gICAgICAgICAgICAgICAgICAgIHZhciBvcHRzID0gb3B0aW9ucy5yZWR1Y2UoZnVuY3Rpb24oYWNjLCB2YWwpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciB2YWx1ZSA9IHNjb3BlW3ZhbF0gPyBzY29wZVt2YWxdKCkgOiB1bmRlZmluZWQ7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAodmFsdWUgIT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFjY1t2YWxdID0gdmFsdWU7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gYWNjO1xuICAgICAgICAgICAgICAgICAgICB9LCB7fSk7XG4gICAgICAgICAgICAgICAgICAgIGRpYWxvZ1NlcnZpY2UuY3JlYXRlTmFtZWQoYXR0ci5pZCwgZWxlbWVudCwgb3B0cyk7XG4gICAgICAgICAgICAgICAgICAgIGlmIChzY29wZS5iaW5kVG9TY29wZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgdHJhbnNjbHVkZShzY29wZSwgZnVuY3Rpb24oY2xvbmUsIHNjb3BlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZWxlbWVudC5odG1sKGNsb25lKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgfV0pO1xufSk7IiwiZGVmaW5lKFsnanF1ZXJ5LWJvb3QnLCAnanF1ZXJ5dWknLCAncGFnZXMvYWdlbnQvYWRkRGlhbG9nJ10sIGZ1bmN0aW9uICgkKSB7XG4gICAgJChmdW5jdGlvbiAoKSB7XG4gICAgICAgIHZhciB0b3AgPSAkKCcuaGVhZGVyLXNpdGUnKS5sZW5ndGggPyAkKCcuaGVhZGVyLXNpdGUnKS5vZmZzZXQoKS50b3AgLSBwYXJzZUZsb2F0KCQoJy5oZWFkZXItc2l0ZScpLmNzcygnbWFyZ2luLXRvcCcpLnJlcGxhY2UoL2F1dG8vLCAwKSkgOiAwO1xuICAgICAgICAkKCcubWVudS1jbG9zZScpLmNsaWNrKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICQoJy5tYWluLWJvZHknKS50b2dnbGVDbGFzcygnaGlkZS1tZW51JykuYWRkQ2xhc3MoJ21hbnVhbC1oaWRkZW4nKTtcbiAgICAgICAgICAgICQod2luZG93KS50cmlnZ2VyKCdyZXNpemUnKTtcbiAgICAgICAgfSk7XG4gICAgICAgIGlmICgkKCcubWVudS1idXR0b24nKS5sZW5ndGgpIHtcbiAgICAgICAgICAgICQoJy5tZW51LWJ1dHRvbicpLmNsaWNrKGZ1bmN0aW9uKCl7XG4gICAgICAgICAgICAgICAgJCh0aGlzKS50b2dnbGVDbGFzcygnYWN0aXZlJyk7XG4gICAgICAgICAgICAgICAgJCgnLmhlYWRlci1zaXRlLC5maXhlZC1oZWFkZXInKS50b2dnbGVDbGFzcygnYWN0aXZlJyk7XG4gICAgICAgICAgICAgICAgJCgnYm9keScpLnRvZ2dsZUNsYXNzKCdvdmVyZmxvdycpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgICAgJCgnLmFwaS1uYXZfX2hhcy1zdWJtZW51ID4gYScpLmNsaWNrKGZ1bmN0aW9uKGUpe1xuICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgJCh0aGlzKS5wYXJlbnQoKS50b2dnbGVDbGFzcygnYWN0aXZlJyk7XG4gICAgICAgIH0pO1xuICAgICAgICAkKCcubGlzdC1hcGlzIGEsIC5hYm91dF9fdGFncyBhJykuY2xpY2soZnVuY3Rpb24oZSl7XG4gICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICB2YXIgaGFzaCA9ICQodGhpcykuYXR0cignaHJlZicpLFxuICAgICAgICAgICAgICAgIGhlYWRlckhlaWdodCA9ICQoJ2h0bWwnKS5oYXNDbGFzcygnbW9iaWxlLWRldmljZScpID8gJCgnLmZpeGVkLWhlYWRlcicpLmlubmVySGVpZ2h0KCkgOiAkKCcuaGVhZGVyLXNpdGUnKS5pbm5lckhlaWdodCgpO1xuICAgICAgICAgICAgJCgnYm9keSxodG1sJylcbiAgICAgICAgICAgICAgICAuYW5pbWF0ZSh7XG4gICAgICAgICAgICAgICAgICAgIHNjcm9sbFRvcDogJChoYXNoKS5vZmZzZXQoKS50b3AgLSBoZWFkZXJIZWlnaHRcbiAgICAgICAgICAgICAgICB9LCA1MDApO1xuICAgICAgICAgICAgaWYoaGlzdG9yeS5wdXNoU3RhdGUpIHtcbiAgICAgICAgICAgICAgICBoaXN0b3J5LnB1c2hTdGF0ZShudWxsLCAnJywgaGFzaCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICBsb2NhdGlvbi5oYXNoID0gaGFzaDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICAgICQoJy5tYWluLWZvcm0gLnN0eWxlZC1zZWxlY3Qgc2VsZWN0JykuZm9jdXMoZnVuY3Rpb24oKXtcbiAgICAgICAgICAgICQodGhpcykuY2xvc2VzdCgnLnN0eWxlZC1zZWxlY3QnKS5hZGRDbGFzcygnZm9jdXMnKTtcbiAgICAgICAgfSkuYmx1cihmdW5jdGlvbigpe1xuICAgICAgICAgICAgJCh0aGlzKS5jbG9zZXN0KCcuc3R5bGVkLXNlbGVjdCcpLnJlbW92ZUNsYXNzKCdmb2N1cycpO1xuICAgICAgICB9KTtcbiAgICAgICAgJCh3aW5kb3cpLmVhY2goZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyIGJvZHkgPSAkKCcubWFpbi1ib2R5Jyk7XG4gICAgICAgICAgICBpZiAoJCh3aW5kb3cpLndpZHRoKCkgPCAxMDI0KSB7XG4gICAgICAgICAgICAgICAgYm9keS5hZGRDbGFzcygnc21hbGwtZGVza3RvcCcpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgYm9keS5yZW1vdmVDbGFzcygnc21hbGwtZGVza3RvcCcpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKGJvZHkuaGFzQ2xhc3MoJ21hbnVhbC1oaWRkZW4nKSkgcmV0dXJuO1xuICAgICAgICAgICAgaWYgKCQod2luZG93KS53aWR0aCgpIDwgMTAyNCkge1xuICAgICAgICAgICAgICAgIGJvZHkuYWRkQ2xhc3MoJ2hpZGUtbWVudScpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgYm9keS5yZW1vdmVDbGFzcygnaGlkZS1tZW51Jyk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgICAkKHdpbmRvdykub24oJ3Njcm9sbCcsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHZhciBuYXYgPSAkKCcubmF2LXJvdycpO1xuICAgICAgICAgICAgdmFyIGxhc3QgPSAkKCcubGFzdC11cGRhdGUnKTtcblxuICAgICAgICAgICAgaWYgKCQoJ2Rpdi5maXhlZC1oZWFkZXInKS5sZW5ndGgpe1xuICAgICAgICAgICAgICAgIGlmICgkKHRoaXMpLnNjcm9sbFRvcCgpID4gMTIwKXtcbiAgICAgICAgICAgICAgICAgICAgJCgnZGl2LmZpeGVkLWhlYWRlcicpLmZhZGVJbigpO1xuICAgICAgICAgICAgICAgIH0gZWxzZXtcbiAgICAgICAgICAgICAgICAgICAgJCgnZGl2LmZpeGVkLWhlYWRlcicpLmZhZGVPdXQoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmICgkKHRoaXMpLnNjcm9sbFRvcCgpID4gMCkge1xuICAgICAgICAgICAgICAgIG5hdi5hZGRDbGFzcygnc2Nyb2xsZWQnKTtcbiAgICAgICAgICAgICAgICBsYXN0LmFkZENsYXNzKCdzY3JvbGxlZCcpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBuYXYucmVtb3ZlQ2xhc3MoJ3Njcm9sbGVkJyk7XG4gICAgICAgICAgICAgICAgbGFzdC5yZW1vdmVDbGFzcygnc2Nyb2xsZWQnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICgkKHRoaXMpLnNjcm9sbFRvcCgpID4gNjUpIHtcbiAgICAgICAgICAgICAgICBuYXYuYWRkQ2xhc3MoJ2FjdGl2ZScpO1xuICAgICAgICAgICAgICAgIG5hdi5vZmZzZXQoe1xuICAgICAgICAgICAgICAgICAgICBsZWZ0OiAwXG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIG5hdi5yZW1vdmVDbGFzcygnYWN0aXZlJyk7XG4gICAgICAgICAgICAgICAgbmF2LmNzcyh7XG4gICAgICAgICAgICAgICAgICAgIGxlZnQ6IDBcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgdmFyIGxpQWN0aXZlLFxuICAgICAgICAgICAgbGVmdE1lbnUgPSAkKCcudXNlci1ibGsnKSxcbiAgICAgICAgICAgIGNvbnRlbnQgPSAkKCdkaXYuY29udGVudCcpLFxuICAgICAgICAgICAgbGlDbGFzcyA9ICdiZXlvbmQnO1xuICAgICAgICB2YXIgbGlBY3RpdmVIYW5kbGVyID0gZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgbGlBY3RpdmUgPSBsZWZ0TWVudS5maW5kKCdsaS5hY3RpdmUnKTtcbiAgICAgICAgICAgIGlmIChsaUFjdGl2ZS5sZW5ndGggIT0gMSkgcmV0dXJuO1xuICAgICAgICAgICAgaWYgKGxpQWN0aXZlLm9mZnNldCgpLnRvcCArIGxpQWN0aXZlLm91dGVySGVpZ2h0KCkgPiBjb250ZW50Lm9mZnNldCgpLnRvcCArIGNvbnRlbnQub3V0ZXJIZWlnaHQoKSkge1xuICAgICAgICAgICAgICAgIGlmICghbGlBY3RpdmUuaGFzQ2xhc3MobGlDbGFzcykpIGxpQWN0aXZlLmFkZENsYXNzKGxpQ2xhc3MpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBpZiAobGlBY3RpdmUuaGFzQ2xhc3MobGlDbGFzcykpIGxpQWN0aXZlLnJlbW92ZUNsYXNzKGxpQ2xhc3MpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9O1xuICAgICAgICBpZiAobGVmdE1lbnUubGVuZ3RoID09IDEgJiYgY29udGVudC5sZW5ndGggPT0gMSkge1xuICAgICAgICAgICAgbGlBY3RpdmVIYW5kbGVyKCk7XG4gICAgICAgICAgICBzZXRJbnRlcnZhbChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgbGlBY3RpdmVIYW5kbGVyKCk7XG4gICAgICAgICAgICB9LCA3MDApO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIG9sZFJpZ2h0ID0gMDtcblxuICAgICAgICAkKHdpbmRvdykub24oJ3Jlc2l6ZSBzY3JvbGwnLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgaGVhZGVyID0gJCgnLmhlYWRlci1zaXRlJyk7XG4gICAgICAgICAgICBpZiAoJCh3aW5kb3cpLndpZHRoKCkgPCAxMDAwKSB7XG4gICAgICAgICAgICAgICAgaWYgKCQod2luZG93KS5zY3JvbGxMZWZ0KCkgPT0gMCkge1xuICAgICAgICAgICAgICAgICAgICBpZiAoaGVhZGVyLmNzcygnbGVmdCcpICE9ICcwcHgnKSB7IGhlYWRlci5jc3MoJ2xlZnQnLCAwKTsgfVxuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChoZWFkZXIuY3NzKCdsZWZ0JykgIT0gJy0nICsgJCh3aW5kb3cpLnNjcm9sbExlZnQoKSArICdweCcpIHsgaGVhZGVyLmNzcygnbGVmdCcsIC0kKHdpbmRvdykuc2Nyb2xsTGVmdCgpKTsgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgaWYgKGhlYWRlci5jc3MoJ2xlZnQnKSAhPSAnMHB4JykgeyBoZWFkZXIuY3NzKCdsZWZ0JywgMCk7IH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSkudHJpZ2dlcigncmVzaXplJyk7XG5cbiAgICAgICAgJCh3aW5kb3cpLnJlc2l6ZShmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgc2l6ZVdpbmRvdyA9ICQoJ2JvZHknKS53aWR0aCgpO1xuICAgICAgICAgICAgaWYgKHNpemVXaW5kb3cgPCAxMDI0KSB7XG4gICAgICAgICAgICAgICAgJCgnLm1haW4tYm9keScpLmFkZENsYXNzKCdzbWFsbC1kZXNrdG9wJyk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICQoJy5tYWluLWJvZHknKS5yZW1vdmVDbGFzcygnc21hbGwtZGVza3RvcCcpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKCQoJy5tYWluLWJvZHknKS5oYXNDbGFzcygnbWFudWFsLWhpZGRlbicpKSByZXR1cm47XG4gICAgICAgICAgICBpZiAoc2l6ZVdpbmRvdyA8IDEwMjQpIHtcbiAgICAgICAgICAgICAgICAkKCcubWFpbi1ib2R5JykuYWRkQ2xhc3MoJ2hpZGUtbWVudScpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAkKCcubWFpbi1ib2R5JykucmVtb3ZlQ2xhc3MoJ2hpZGUtbWVudScpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcblxuICAgICAgICAkKGRvY3VtZW50KVxuICAgICAgICAgICAgLm9uKCdjaGFuZ2Uga2V5dXAgcGFzdGUnLCAnLnJvdy5lcnJvciBpbnB1dDp2aXNpYmxlLCAucm93LmVycm9yIHRleHRhcmVhOnZpc2libGUsIC5yb3cuZXJyb3IgY2hlY2tib3g6dmlzaWJsZScsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgaW5wdXRJdGVtID0gJCh0aGlzKS5jbG9zZXN0KCcuaW5wdXQtaXRlbScpO1xuICAgICAgICAgICAgICAgIGlmIChpbnB1dEl0ZW0ubGVuZ3RoID09IDAgfHwgISQodGhpcykuaGFzQ2xhc3MoJ25nLWludmFsaWQnKSkge1xuICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmNsb3Nlc3QoJy5lcnJvcicpLnJlbW92ZUNsYXNzKCdlcnJvcicpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAub24oJ2NoYW5nZScsICcuc3R5bGVkLWZpbGUgaW5wdXRbdHlwZT1maWxlXScsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgZnVsbFBhdGggPSAkKHRoaXMpLnZhbCgpO1xuICAgICAgICAgICAgICAgIGlmIChmdWxsUGF0aCkge1xuICAgICAgICAgICAgICAgICAgICB2YXIgc3RhcnRJbmRleCA9IChmdWxsUGF0aC5pbmRleE9mKCdcXFxcJykgPj0gMCA/IGZ1bGxQYXRoLmxhc3RJbmRleE9mKCdcXFxcJykgOiBmdWxsUGF0aC5sYXN0SW5kZXhPZignLycpKTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGZpbGVuYW1lID0gZnVsbFBhdGguc3Vic3RyaW5nKHN0YXJ0SW5kZXgpO1xuICAgICAgICAgICAgICAgICAgICBpZiAoZmlsZW5hbWUuaW5kZXhPZignXFxcXCcpID09PSAwIHx8IGZpbGVuYW1lLmluZGV4T2YoJy8nKSA9PT0gMCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgZmlsZW5hbWUgPSBmaWxlbmFtZS5zdWJzdHJpbmcoMSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgJCgnLmZpbGUtbmFtZScpLnRleHQoZmlsZW5hbWUpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAub24oJ2NsaWNrJywgJy5zcGlubmVyYWJsZTpub3QoZm9ybSknLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgJCh0aGlzKS5hZGRDbGFzcygnbG9hZGVyJyk7XG4gICAgICAgICAgICB9KVxuICAgICAgICAgICAgLm9uKCdzdWJtaXQnLCAnZm9ybS5zcGlubmVyYWJsZScsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgYnV0dG9uID0gJCh0aGlzKS5maW5kKCdbdHlwZT1cInN1Ym1pdFwiXScpLmZpcnN0KCk7XG5cbiAgICAgICAgICAgICAgICBpZiAoIWJ1dHRvbi5oYXNDbGFzcygnbG9hZGVyJykpIHtcbiAgICAgICAgICAgICAgICAgICAgYnV0dG9uLmFkZENsYXNzKCdsb2FkZXInKS5hdHRyKCdkaXNhYmxlZCcsICdkaXNhYmxlZCcpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcuanMtYWRkLW5ldy1wZXJzb24sICNhZGQtcGVyc29uLWJ0biwgLmpzLXBlcnNvbnMtbWVudSBhW2hyZWY9XCIvdXNlci9jb25uZWN0aW9uc1wiXS5hZGQnLCBmdW5jdGlvbiAoZSkge1xuICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG4gICAgICAgICAgICByZXF1aXJlKFsncGFnZXMvYWdlbnQvYWRkRGlhbG9nJ10sIGZ1bmN0aW9uIChjbGlja0hhbmRsZXIpIHtcbiAgICAgICAgICAgICAgICBjbGlja0hhbmRsZXIoKTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9KTtcblxuICAgICAgICB2YXIgJGFkZE5ld1BlcnNvbiA9ICQoJzxvcHRpb24+JyArIFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiQWRkIG5ldyBwZXJzb25cIikgKi8gJ2FkZC5uZXcucGVyc29uJykgKyAnPC9vcHRpb24+Jyk7XG4gICAgICAgIHZhciAkcHJldlNlbGVjdGVkO1xuICAgICAgICBpZiAoISQoJy5tYWluLWJvZHkuYnVzaW5lc3MnKSkge1xuICAgICAgICAgICAgJCgnLmpzLXVzZXJhZ2VudC1zZWxlY3QnKS5hcHBlbmQoJGFkZE5ld1BlcnNvbikub24oJ2NoYW5nZScsIGZ1bmN0aW9uIChlbCkge1xuICAgICAgICAgICAgICAgIGlmICgkKGVsLnRhcmdldCkuZmluZCgnb3B0aW9uOnNlbGVjdGVkJylbMF0udGV4dCA9PT0gJGFkZE5ld1BlcnNvblswXS50ZXh0KSB7XG4gICAgICAgICAgICAgICAgICAgICRwcmV2U2VsZWN0ZWQucHJvcCgnc2VsZWN0ZWQnLCB0cnVlKTtcbiAgICAgICAgICAgICAgICAgICAgJCgnLmpzLWFkZC1uZXctcGVyc29uJykudHJpZ2dlcignY2xpY2snKTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAkcHJldlNlbGVjdGVkID0gJChlbC50YXJnZXQpLmZpbmQoJ29wdGlvbjpzZWxlY3RlZCcpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pLnRyaWdnZXIoJ2NoYW5nZScpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gT3BlbiBwZXJzb24gYWRkIHBvcHVwIGlmIHBhcmFtIGFkZE5ld1BlcnNvbiBpcyBwcmVzZW50XG4gICAgICAgIGlmIChkb2N1bWVudC5sb2NhdGlvbi5ocmVmLm1hdGNoKC9hZGQtbmV3LXBlcnNvbj0vKSlcbiAgICAgICAgICAgICQoJyNhZGQtcGVyc29uLWJ0bicpLnRyaWdnZXIoJ2NsaWNrJyk7XG4gICAgfSk7XG59KTtcbiIsImxldCBwYXNzd29yZENvbXBsZXhpdHk7XG5cbigoKSA9PiB7XG5cdGZ1bmN0aW9uIHNob3dQYXNzd29yZE5vdGljZSgpIHtcblx0XHRsZXQgZnJhbWUgPSAkKHRoaXMpLmNsb3Nlc3QoJ3RhYmxlLmlucHV0RnJhbWUnKTtcblx0XHRpZiAoZnJhbWUubGVuZ3RoID09PSAwKSB7XG5cdFx0XHRmcmFtZSA9ICQodGhpcyk7XG5cdFx0fVxuXHRcdGNvbnN0IGRpdiA9ICQoJyNwYXNzd29yZC1ub3RpY2UnKTtcblx0XHRkaXYucHJlcGVuZFRvKGZyYW1lLnBhcmVudCgpKTtcblx0XHRjb25zdCBsZWZ0ID0gZnJhbWUucG9zaXRpb24oKS5sZWZ0ICsgZnJhbWUud2lkdGgoKSArIHBhcnNlUG9zaXRpb24oZnJhbWUuY3NzKCdwYWRkaW5nLWxlZnQnKSkgKyBwYXJzZVBvc2l0aW9uKGZyYW1lLmNzcygncGFkZGluZy1yaWdodCcpKSArIDUgLSBwYXJzZVBvc2l0aW9uKGRpdi5jc3MoJ21hcmdpbi1sZWZ0JykpO1xuXHRcdGRpdlxuXHRcdFx0LmNzcygndG9wJywgZnJhbWUucG9zaXRpb24oKS50b3AgLSBwYXJzZVBvc2l0aW9uKGRpdi5jc3MoJ21hcmdpbi10b3AnKSkpXG5cdFx0XHQuY3NzKCdsZWZ0JywgbGVmdCkuY3NzKCd2aXNpYmlsaXR5JywgJ2hpZGRlbicpLnNob3coKTtcblx0XHRsZXQgaGVpZ2h0ID0gMDtcblx0XHRkaXYuY2hpbGRyZW4oKS5lYWNoKChpbmRleCwgZWwpID0+IHtcblx0XHRcdGVsID0gJChlbCk7XG5cdFx0XHRoZWlnaHQgKz0gZWwuaGVpZ2h0KCkgKyBwYXJzZVBvc2l0aW9uKGVsLmNzcygnbWFyZ2luLXRvcCcpKSArIHBhcnNlUG9zaXRpb24oZWwuY3NzKCdtYXJnaW4tYm90dG9tJykpICsgcGFyc2VQb3NpdGlvbihlbC5jc3MoJ3BhZGRpbmctdG9wJykpICsgcGFyc2VQb3NpdGlvbihlbC5jc3MoJ3BhZGRpbmctYm90dG9tJykpO1xuXHRcdH0pO1xuXHRcdGRpdi5jc3MoJ2hlaWdodCcsIGhlaWdodCArIHBhcnNlUG9zaXRpb24oZGl2LmNzcygncGFkZGluZy10b3AnKSkgKyBwYXJzZVBvc2l0aW9uKGRpdi5jc3MoJ3BhZGRpbmctYm90dG9tJykpKS5jc3MoJ3Zpc2liaWxpdHknLCAndmlzaWJsZScpO1xuXHR9XG5cblx0ZnVuY3Rpb24gaGlkZVBhc3N3b3JkTm90aWNlKCkge1xuXHRcdCQoJyNwYXNzd29yZC1ub3RpY2UnKS5oaWRlKCk7XG5cdH1cblxuXHRmdW5jdGlvbiB0cmFja0NvbXBsZXhpdHkodmFsdWUpIHtcblx0XHRjb25zdCBjaGVja3MgPSB7XG5cdFx0XHQncGFzc3dvcmQtbGVuZ3RoJzogdmFsdWUubGVuZ3RoID49IDggJiYgbGVuZ3RoSW5VdGY4Qnl0ZXModmFsdWUpIDw9IDcyLFxuXHRcdFx0J2xvd2VyLWNhc2UnOiB2YWx1ZS5tYXRjaCgvW2Etel0vKSAhPSBudWxsLFxuXHRcdFx0J3VwcGVyLWNhc2UnOiB2YWx1ZS5tYXRjaCgvW0EtWl0vKSAhPSBudWxsLFxuXHRcdFx0J3NwZWNpYWwtY2hhcic6IHZhbHVlLm1hdGNoKC9bXmEtekEtWlxcc10vKSAhPSBudWxsXG5cdFx0fTtcblx0XHRpZiAoc2VsZi5nZXRMb2dpbkNhbGxiYWNrKSB7XG5cdFx0XHRjb25zdCBsb2dpbiA9IHNlbGYuZ2V0TG9naW5DYWxsYmFjaygpLnRvTG93ZXJDYXNlKCk7XG5cdFx0XHRjb25zdCBlbWFpbCA9IHNlbGYuZ2V0RW1haWxDYWxsYmFjaygpLnJlcGxhY2UoL0AuKiQvLCAnJykudG9Mb3dlckNhc2UoKTtcblx0XHRcdGNoZWNrcy5sb2dpbiA9ICh2YWx1ZS50b0xvd2VyQ2FzZSgpLmluZGV4T2YobG9naW4pID09PSAtMSB8fCBsb2dpbiA9PT0gJycpICYmICh2YWx1ZS50b0xvd2VyQ2FzZSgpLmluZGV4T2YoZW1haWwpID09PSAtMSB8fCBlbWFpbCA9PT0gJycpO1xuXHRcdH1cblx0XHQkKCcjbWVldC1sb2dpbicpLnRvZ2dsZShzZWxmLmdldExvZ2luQ2FsbGJhY2sgIT0gbnVsbCk7XG5cblx0XHRjb25zdCBlcnJvcnMgPSBbXTtcblx0XHQkLmVhY2goY2hlY2tzLCAoa2V5LCBtYXRjaCkgPT4ge1xuXHRcdFx0Y29uc3QgbWVldERpdiA9ICQoJyNtZWV0LScgKyBrZXkpO1xuXHRcdFx0bWVldERpdi50b2dnbGVDbGFzcygnYWxsb3dlZCcsIG1hdGNoKTtcblx0XHRcdGlmICghbWF0Y2gpIHtcblx0XHRcdFx0ZXJyb3JzLnB1c2gobWVldERpdi50ZXh0KCkpO1xuXHRcdFx0fVxuXHRcdH0pO1xuXG5cdFx0cmV0dXJuIGVycm9ycztcblx0fVxuXG5cdGZ1bmN0aW9uIHBhcnNlUG9zaXRpb24ocG9zKXtcblx0XHRsZXQgcmVzdWx0ID0gcGFyc2VJbnQocG9zKTtcblx0XHRpZiAoaXNOYU4ocmVzdWx0KSkge1xuXHRcdFx0cmVzdWx0ID0gMDtcblx0XHR9XG5cblx0XHRyZXR1cm4gcmVzdWx0O1xuXHR9XG5cblx0ZnVuY3Rpb24gbGVuZ3RoSW5VdGY4Qnl0ZXMoc3RyKSB7XG5cdFx0Ly8gTWF0Y2hlcyBvbmx5IHRoZSAxMC4uIGJ5dGVzIHRoYXQgYXJlIG5vbi1pbml0aWFsIGNoYXJhY3RlcnMgaW4gYSBtdWx0aS1ieXRlIHNlcXVlbmNlLlxuXHRcdGNvbnN0IG0gPSBlbmNvZGVVUklDb21wb25lbnQoc3RyKS5tYXRjaCgvJVs4OUFCYWJdL2cpO1xuXHRcdHJldHVybiBzdHIubGVuZ3RoICsgKG0gPyBtLmxlbmd0aCA6IDApO1xuXHR9XG5cblx0Y29uc3Qgc2VsZiA9IHtcblx0XHRwYXNzd29yZEZpZWxkOiBudWxsLFxuXHRcdGdldExvZ2luQ2FsbGJhY2s6IG51bGwsXG5cdFx0Z2V0RW1haWxDYWxsYmFjazogbnVsbCxcblxuXHRcdGluaXQ6IChwYXNzd29yZEZpZWxkLCBnZXRMb2dpbkNhbGxiYWNrLCBnZXRFbWFpbENhbGxiYWNrKSA9PiB7XG5cdFx0XHRzZWxmLnBhc3N3b3JkRmllbGQgPSBwYXNzd29yZEZpZWxkO1xuXHRcdFx0c2VsZi5nZXRMb2dpbkNhbGxiYWNrID0gZ2V0TG9naW5DYWxsYmFjaztcblx0XHRcdHNlbGYuZ2V0RW1haWxDYWxsYmFjayA9IGdldEVtYWlsQ2FsbGJhY2s7XG5cdFx0XHRwYXNzd29yZEZpZWxkXG5cdFx0XHRcdC5vbihcImZvY3VzXCIsIG51bGwsIG51bGwsIHNob3dQYXNzd29yZE5vdGljZSlcblx0XHRcdFx0Lm9uKFwiYmx1clwiLCBudWxsLCBudWxsLCBoaWRlUGFzc3dvcmROb3RpY2UpXG5cdFx0XHRcdC5vbihcImtleXByZXNzIHBhc3RlIGNoYW5nZSBrZXlkb3duIGZvY3VzIGlucHV0XCIsIG51bGwsIG51bGwsICgpID0+IHtcblx0XHRcdFx0XHRzZXRUaW1lb3V0KCgpID0+IHtcblx0XHRcdFx0XHRcdHRyYWNrQ29tcGxleGl0eShzZWxmLnBhc3N3b3JkRmllbGQudmFsKCkpO1xuXHRcdFx0XHRcdH0sIDApO1xuXHRcdFx0XHR9KTtcblx0XHRcdHRyYWNrQ29tcGxleGl0eShzZWxmLnBhc3N3b3JkRmllbGQudmFsKCkpO1xuXHRcdH0sXG5cblx0XHRnZXRFcnJvcnM6ICgpID0+IHtcblx0XHRcdHJldHVybiB0cmFja0NvbXBsZXhpdHkoc2VsZi5wYXNzd29yZEZpZWxkLnZhbCgpKTtcblx0XHR9XG5cdH07XG5cblx0aWYgKHR5cGVvZihkZWZpbmUpICE9PSAndW5kZWZpbmVkJykge1xuXHRcdGRlZmluZShbJ2pxdWVyeS1ib290JywgJ3RyYW5zbGF0b3ItYm9vdCddLCAoKSA9PiB7XG5cdFx0XHRyZXR1cm4gc2VsZjtcblx0XHR9KTtcblx0fSBlbHNlIHtcblx0XHRwYXNzd29yZENvbXBsZXhpdHkgPSBzZWxmO1xuXHR9XG59KSgpO1xuXG4iLCJkZWZpbmUoWydqcXVlcnktYm9vdCcsICdsaWIvZGlhbG9nJywgJ3RyYW5zbGF0b3ItYm9vdCcsICdyb3V0aW5nJ10sIGZ1bmN0aW9uKCQsIGRpYWxvZyl7XG5cblx0dmFyIGRpYWxvZ0VsZW1lbnQ7XG5cblx0Ly8gQWRkIHBlcnNvbnMgcG9wdXBcblx0aWYodHlwZW9mKGRpYWxvZ0VsZW1lbnQpID09ICd1bmRlZmluZWQnKSB7XG5cdFx0ZGlhbG9nRWxlbWVudCA9ICQoJzxkaXYgLz4nKS5hcHBlbmRUbygnYm9keScpLmh0bWwoXG5cdFx0XHRcdFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiWW91IGhhdmUgdHdvIG9wdGlvbnMsIHlvdSBjYW4gY29ubmVjdCB3aXRoIGFub3RoZXIgcGVyc29uIG9uIEF3YXJkV2FsbGV0LCBvciB5b3UgY2FuIGp1c3QgY3JlYXRlIGFub3RoZXIgbmFtZSB0byBiZXR0ZXIgb3JnYW5pemUgeW91ciByZXdhcmRzLlwiKSAqLydhZ2VudHMucG9wdXAuY29udGVudCcpXG5cdFx0KTtcblx0XHRkaWFsb2cuY3JlYXRlTmFtZWQoJ3BlcnNvbnMtbWVudScsIGRpYWxvZ0VsZW1lbnQsIHtcblx0XHRcdHdpZHRoOiAnNjAwJyxcblx0XHRcdGF1dG9PcGVuOiBmYWxzZSxcblx0XHRcdG1vZGFsOiB0cnVlLFxuXHRcdFx0dGl0bGU6IFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiU2VsZWN0IGNvbm5lY3Rpb24gdHlwZVwiKSAqLyAnYWdlbnRzLnBvcHVwLmhlYWRlcicpLFxuXHRcdFx0YnV0dG9uczogW1xuXHRcdFx0XHR7XG5cdFx0XHRcdFx0J3RleHQnOiBUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIkNvbm5lY3Qgd2l0aCBhbm90aGVyIHBlcnNvblwiKSAqLydhZ2VudHMucG9wdXAuY29ubmVjdC5idG4nKSxcblx0XHRcdFx0XHQnY2xhc3MnOiAnYnRuLWJsdWUgc3Bpbm5lcmFibGUnLFxuXHRcdFx0XHRcdCdjbGljayc6IGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0XHRcdHdpbmRvdy5sb2NhdGlvbi5ocmVmID0gUm91dGluZy5nZW5lcmF0ZSgnYXdfY3JlYXRlX2Nvbm5lY3Rpb24nKVxuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSxcblx0XHRcdFx0e1xuXHRcdFx0XHRcdCd0ZXh0JzogVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJKdXN0IGFkZCBhIG5ldyBuYW1lXCIpICovJ2FnZW50cy5wb3B1cC5hZGQuYnRuJyksXG5cdFx0XHRcdFx0J2NsYXNzJzogJ2J0bi1ibHVlIHNwaW5uZXJhYmxlJyxcblx0XHRcdFx0XHQnY2xpY2snOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHRcdFx0XHR3aW5kb3cubG9jYXRpb24uaHJlZiA9IFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X2FkZF9hZ2VudCcpXG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cdFx0XHRdLFxuXHRcdFx0b3BlbjogZnVuY3Rpb24gKCkge1xuXHRcdFx0XHQvLyBSZW1vdmUgYm90dG9ucyBmb2N1c1xuXHRcdFx0XHQkKCcudWktZGlhbG9nIDpidXR0b24nKS5ibHVyKCk7XG4gICAgICAgICAgICAgICAgaGlzdG9yeS5wdXNoU3RhdGUobnVsbCwgbnVsbCwgJz9hZGQtbmV3LXBlcnNvbj10cnVlJyk7XG5cbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBjbG9zZTogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgaGlzdG9yeS5iYWNrKCk7XG4gICAgICAgICAgICB9XG5cdFx0fSk7XG5cdH1cblxuXHR2YXIgY2xpY2tIYW5kbGVyID0gZnVuY3Rpb24oKSB7XG5cdFx0ZGlhbG9nRWxlbWVudC5kaWFsb2coJ29wZW4nKTtcblx0fTtcblxuXHRyZXR1cm4gY2xpY2tIYW5kbGVyO1xufSk7XG4iLCIvKlxuZ2xvYmFsXG53aGVuUmVjYXB0Y2hhTG9hZGVkLFxucmVuZGVyUmVjYXB0Y2hhLFxud2hlblJlY2FwdGNoYVNvbHZlZCxcbkRuNjk4dENRXG4qL1xuXG5kZWZpbmUoW1xuICAgICAgICAnbGliL2N1c3RvbWl6ZXInLCAnbGliL3Bhc3N3b3JkQ29tcGxleGl0eScsICdsaWIvZ2Etd3JhcHBlcicsICdwYWdlcy9sYW5kaW5nL29hdXRoJyxcbiAgICAgICAgJ2pxdWVyeS1ib290JywgJ2RpcmVjdGl2ZXMvZGlhbG9nJywgJ2FuZ3VsYXItYm9vdCcsICdyb3V0aW5nJywgJ2xpYi9kZXNpZ24nLFxuICAgICAgICAndHJhbnNsYXRvci1ib290J1xuXSwgKGN1c3RvbWl6ZXIsIHBhc3N3b3JkQ29tcGxleGl0eSwgZ2FXcmFwcGVyLCBpbml0T2F1dGhMaW5rcykgPT4ge1xuXG4gICAgZnVuY3Rpb24gaW5pdFJlY2FwdGNoYShzY29wZSkge1xuICAgICAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgICAgIHdoZW5SZWNhcHRjaGFMb2FkZWQoKCkgPT4ge1xuICAgICAgICAgICAgICAgIHJlbmRlclJlY2FwdGNoYShzY29wZSk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSwgMTAwKTtcbiAgICB9XG5cbiAgICBhbmd1bGFyXG4gICAgICAgIC5tb2R1bGUoJ2xhbmRpbmdQYWdlLWN0cmwnLCBbJ2RpYWxvZy1kaXJlY3RpdmUnXSlcbiAgICAgICAgLnNlcnZpY2UoJ1VzZXInLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgbG9naW46ICcnLFxuICAgICAgICAgICAgICAgIF9yZW1lbWJlcl9tZTogdHJ1ZVxuICAgICAgICAgICAgfTtcbiAgICAgICAgfSlcbiAgICAgICAgLmNvbnRyb2xsZXIoJ3JlZ2lzdGVyQnVzaW5lc3NDdHJsJywgWyckc3RhdGUnLCBmdW5jdGlvbigkc3RhdGUpIHtcbiAgICAgICAgICAgIGlmICghL15idXNpbmVzcy8udGVzdCh3aW5kb3cubG9jYXRpb24uaG9zdG5hbWUpKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuICRzdGF0ZS5nbygncmVnaXN0ZXInKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGZ1bmN0aW9uIGFkZEVycm9yKGZpZWxkLCB0ZXh0KSB7XG4gICAgICAgICAgICAgICAgY29uc3QgZXJyb3IgPSAkKCc8ZGl2IGNsYXNzPVwicmVxXCIgZGF0YS1yb2xlPVwidG9vbHRpcFwiIHRpdGxlPVwiJyArIHRleHQgKyAnXCI+PGkgY2xhc3M9XCJpY29uLXdhcm5pbmctc21hbGxcIj48L2k+PC9kaXY+Jyk7XG5cbiAgICAgICAgICAgICAgICAkKGZpZWxkKS5iZWZvcmUoZXJyb3IpO1xuICAgICAgICAgICAgICAgIGN1c3RvbWl6ZXIuaW5pdFRvb2x0aXBzKGVycm9yKTtcbiAgICAgICAgICAgICAgICBlcnJvci50b29sdGlwKCdvcGVuJykub2ZmKCdtb3VzZWVudGVyIG1vdXNlbGVhdmUnKTtcbiAgICAgICAgICAgICAgICBmaWVsZC5wYXJlbnRzKCcucm93JykuYWRkQ2xhc3MoJ2Vycm9yJyk7XG4gICAgICAgICAgICAgICAgJCgnI3JlZ2lzdGVyLWJ1dHRvbicpLnByb3AoJ2Rpc2FibGVkJywgdHJ1ZSk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGNvbnN0IGZvcm0gPSAkKCcjcmVnaXN0ZXJGb3JtJyk7XG5cbiAgICAgICAgICAgIGZvcm1cbiAgICAgICAgICAgICAgICAub24oJ3N1Ym1pdCcsIGUgPT4ge1xuICAgICAgICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG5cbiAgICAgICAgICAgICAgICAgICAgaWYgKHBhc3N3b3JkQ29tcGxleGl0eS5nZXRFcnJvcnMoKS5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKCcjdXNlcl9wYXNzX1Bhc3N3b3JkJykuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICQoJyNyZWdpc3Rlci1idXR0b24nKS5wcm9wKCdkaXNhYmxlZCcsIHRydWUpLmFkZENsYXNzKCdsb2FkZXInKTtcblxuICAgICAgICAgICAgICAgICAgICB3aGVuUmVjYXB0Y2hhU29sdmVkKHJlY2FwdGNoYV9jb2RlID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IGRhdGEgPSBuZXcgRm9ybURhdGEoJCgnI3JlZ2lzdGVyRm9ybScpWzBdKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEuYXBwZW5kKCdyZWNhcHRjaGEnLCByZWNhcHRjaGFfY29kZSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAkLmFqYXgoe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHVybDogUm91dGluZy5nZW5lcmF0ZSgnYXdfdXNlcnNfcmVnaXN0ZXJfYnVzaW5lc3MnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRhOiBkYXRhLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1ldGhvZDogJ3Bvc3QnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHByb2Nlc3NEYXRhOiBmYWxzZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb250ZW50VHlwZTogZmFsc2UsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc3VjY2VzczogZGF0YSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChkYXRhLmVycm9ycyAmJiBkYXRhLmVycm9ycy5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBlcnJvciA9IGRhdGEuZXJyb3JzWzBdO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYWRkRXJyb3IoJCgnW25hbWU9XCInICsgZXJyb3IubmFtZSArICdcIl0nKSwgZXJyb3IuZXJyb3JUZXh0KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRvY3VtZW50LmxvY2F0aW9uLmhyZWYgPSBSb3V0aW5nLmdlbmVyYXRlKCdhd19idXNpbmVzc19hY2NvdW50X2xpc3QnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKCcjcmVnaXN0ZXItYnV0dG9uJykucmVtb3ZlQ2xhc3MoJ2xvYWRlcicpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAuZmluZCgnaW5wdXQnKS5vbigna2V5dXAgcGFzdGUgY2hhbmdlJywgKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICBjb25zdCBlcnJvcmVkRmllbGQgPSBmb3JtLmZpbmQoJy5yZXEnKTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGVycm9yZWRGaWVsZC5sZW5ndGgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGVycm9yZWRGaWVsZC50b29sdGlwKCdkZXN0cm95JykucmVtb3ZlKCk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgJCgnI3JlZ2lzdGVyLWJ1dHRvbicpLnByb3AoJ2Rpc2FibGVkJywgZmFsc2UpO1xuICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICBpbml0UmVjYXB0Y2hhKCk7XG4gICAgICAgICAgICBwYXNzd29yZENvbXBsZXhpdHkuaW5pdCgkKCcjdXNlcl9wYXNzX1Bhc3N3b3JkJyksICgpID0+IHtcbiAgICAgICAgICAgICAgICByZXR1cm4gJCgnI3VzZXJfbG9naW4nKS52YWwoKVxuICAgICAgICAgICAgfSwgKCkgPT4ge1xuICAgICAgICAgICAgICAgIHJldHVybiAkKCcjdXNlcl9lbWFpbCcpLnZhbCgpXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfV0pXG4gICAgICAgIC5jb250cm9sbGVyKCdyZWdpc3RlckN0cmwnLCBbJyRzY29wZScsICckaHR0cCcsICckdGltZW91dCcsICckbG9jYXRpb24nLCAnZGlhbG9nU2VydmljZScsICckc3RhdGUnLCBmdW5jdGlvbigkc2NvcGUsICRodHRwLCAkdGltZW91dCwgJGxvY2F0aW9uLCBkaWFsb2dTZXJ2aWNlLCAkc3RhdGUpIHtcbiAgICAgICAgICAgIGlmICgvXmJ1c2luZXNzLy50ZXN0KHdpbmRvdy5sb2NhdGlvbi5ob3N0bmFtZSkpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gJHN0YXRlLmdvKCdyZWdpc3RlckJ1c2luZXNzJyk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGRvY3VtZW50LnRpdGxlID0gVHJhbnNsYXRvci50cmFucygnbWV0YS50aXRsZS5yZWdpc3RlcicpO1xuXG4gICAgICAgICAgICBmdW5jdGlvbiBmb2N1c09uRXJyb3IoZmlsdGVyID0gJycpIHtcbiAgICAgICAgICAgICAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3Qgcm93ID0gJCgnLnJvdy5lcnJvcicgKyBmaWx0ZXIpO1xuICAgICAgICAgICAgICAgICAgICBpZiAocm93Lmxlbmd0aCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgJCgnLnJlcScsIHJvdykubW91c2VvdmVyKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAkKCdpbnB1dCcsIHJvdykuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH0sIDEwMCk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGNvbnN0IHN0ZXBJbml0ID0ge307XG5cbiAgICAgICAgICAgIGZ1bmN0aW9uIGluaXRGaXJzdFN0ZXAoKSB7XG4gICAgICAgICAgICAgICAgaW5pdE9hdXRoTGlua3MoKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc2V0U3RlcCgzKTtcbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLiRhcHBseSgpO1xuICAgICAgICAgICAgICAgIH0sICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnNldFN0ZXAoMSk7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS4kYXBwbHkoKTtcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgZnVuY3Rpb24gaW5pdFNlY29uZFN0ZXAoKSB7XG4gICAgICAgICAgICAgICAgcGFzc3dvcmRDb21wbGV4aXR5LmluaXQoJCgnI3Bhc3N3b3JkJyksIG51bGwsICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuICQoJyNyZWdpc3RyYXRpb25fZW1haWwnKS52YWwoKVxuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBmdW5jdGlvbiB2YWxpZGF0ZUZvcm0oZm9ybSkge1xuICAgICAgICAgICAgICAgIGNvbnN0IGRlZmVycmVkID0gJC5EZWZlcnJlZCgpO1xuXG4gICAgICAgICAgICAgICAgaWYgKGZvcm0uJGludmFsaWQpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRlZmVycmVkLnJlamVjdCgpO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGlmIChwYXNzd29yZENvbXBsZXhpdHkuZ2V0RXJyb3JzKCkubGVuZ3RoID4gMCkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gZGVmZXJyZWQucmVqZWN0KCdwYXNzd29yZCcpO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICQud2hlbigkLnBvc3QoJy91c2VyL2NoZWNrX2VtYWlsJywge3ZhbHVlOiAkc2NvcGUuZm9ybS5lbWFpbH0pKVxuICAgICAgICAgICAgICAgICAgICAuZG9uZShyZXN1bHQgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHJlc3VsdCA9PT0gJ2ZhbHNlJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5yZWdpc3RlckZvcm0uZW1haWwuJHNldFZhbGlkaXR5KFwidGFrZW5cIiwgZmFsc2UpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRlZmVycmVkLnJlamVjdCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHJlc3VsdCA9PT0gJ2xvY2tlZCcpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUucmVnaXN0ZXJGb3JtLmVtYWlsLiRzZXRWYWxpZGl0eShcImxvY2tlZFwiLCBmYWxzZSk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZGVmZXJyZWQucmVqZWN0KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5lbWFpbENoZWNrZWQgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgZGVmZXJyZWQucmVzb2x2ZSgpO1xuICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAuZmFpbCgoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICBkZWZlcnJlZC5yZWplY3QoKTtcbiAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICByZXR1cm4gZGVmZXJyZWQucHJvbWlzZSgpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBjb25zdCB1cmlQYXJhbXMgPSAkbG9jYXRpb24uc2VhcmNoKCk7XG5cbiAgICAgICAgICAgICRzY29wZS5pc1N0ZXAgPSBzID0+IHtcbiAgICAgICAgICAgICAgICByZXR1cm4gcyA9PT0gJHNjb3BlLnN0ZXA7XG4gICAgICAgICAgICB9O1xuICAgICAgICAgICAgJHNjb3BlLnNldFN0ZXAgPSBzID0+IHtcbiAgICAgICAgICAgICAgICAkc2NvcGUuc3RlcCA9IHM7XG5cbiAgICAgICAgICAgICAgICBpZiAoIXN0ZXBJbml0W3NdKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChzID09PSAxKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpbml0Rmlyc3RTdGVwKCk7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSBpZiAocyA9PT0gMikge1xuICAgICAgICAgICAgICAgICAgICAgICAgaW5pdFNlY29uZFN0ZXAoKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBzdGVwSW5pdFtzXSA9IHRydWU7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICRzY29wZS5zZXRTdGVwKDEpO1xuXG4gICAgICAgICAgICAkc2NvcGUuc3VibWl0dGVkID0gZmFsc2U7XG4gICAgICAgICAgICAkc2NvcGUuc2hvd1Bhc3MgPSBmYWxzZTtcbiAgICAgICAgICAgICRzY29wZS5mb3JtID0ge1xuICAgICAgICAgICAgICAgIGVtYWlsOiB3aW5kb3cuaW52aXRlRW1haWwgfHwgbnVsbCxcbiAgICAgICAgICAgICAgICBwYXNzOiBudWxsLFxuICAgICAgICAgICAgICAgIGZpcnN0bmFtZTogd2luZG93LmZpcnN0TmFtZSB8fCBudWxsLFxuICAgICAgICAgICAgICAgIGxhc3RuYW1lOiB3aW5kb3cubGFzdE5hbWUgfHwgbnVsbFxuICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICRzY29wZS5jb3Vwb24gPSB1cmlQYXJhbXMuY29kZSB8fCB1cmlQYXJhbXMuQ29kZSB8fCBudWxsO1xuICAgICAgICAgICAgJHNjb3BlLmVtYWlsQ2hlY2tlZCA9IGZhbHNlO1xuXG4gICAgICAgICAgICAkc2NvcGUudG9nZ2xlU2hvd1Bhc3MgPSAoKSA9PiB7XG4gICAgICAgICAgICAgICAgJHNjb3BlLnNob3dQYXNzID0gISRzY29wZS5zaG93UGFzcztcbiAgICAgICAgICAgIH07XG4gICAgICAgICAgICAkc2NvcGUucmVzZXRFcnJvcnMgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgJHNjb3BlLnN1Ym1pdHRlZCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICRzY29wZS5yZWdpc3RlckZvcm0uZW1haWwuJHNldFZhbGlkaXR5KFwidGFrZW5cIiwgdHJ1ZSk7XG4gICAgICAgICAgICAgICAgJHNjb3BlLnJlZ2lzdGVyRm9ybS5lbWFpbC4kc2V0VmFsaWRpdHkoXCJsb2NrZWRcIiwgdHJ1ZSk7XG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAkc2NvcGUuc3VibWl0ID0gZnVuY3Rpb24gKGZvcm0pIHtcbiAgICAgICAgICAgICAgICAkc2NvcGUuc3VibWl0dGVkID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAkc2NvcGUuc3Bpbm5lciA9IHRydWU7XG5cbiAgICAgICAgICAgICAgICB2YWxpZGF0ZUZvcm0oZm9ybSlcbiAgICAgICAgICAgICAgICAgICAgLmRvbmUoKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHdoZW5SZWNhcHRjaGFTb2x2ZWQoZnVuY3Rpb24oY2FwdGNoYV9rZXkpe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkaHR0cCh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB1cmw6IFJvdXRpbmcuZ2VuZXJhdGUoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2F3X3VzZXJzX3JlZ2lzdGVyJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAodXJpUGFyYW1zLkJhY2tUbykgPyB7XCJCYWNrVG9cIjp1cmlQYXJhbXMuQmFja1RvfSA6IHt9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICApLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbWV0aG9kOiAncG9zdCcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRhOiB7dXNlcjogJHNjb3BlLmZvcm0sIGNvdXBvbjogJHNjb3BlLmNvdXBvbiwgcmVjYXB0Y2hhOiBjYXB0Y2hhX2tleX1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSkudGhlbigoe2RhdGF9KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoZGF0YS5zdWNjZXNzID09PSB0cnVlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc29sZS5sb2coJ3NlbmRpbmcgcmVnaXN0ZXJlZCBndGFnIGV2ZW50Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGdhV3JhcHBlcignZXZlbnQnLCAncmVnaXN0ZXJlZCcsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2V2ZW50X2NhdGVnb3J5JzogJ3VzZXInLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnZXZlbnRfbGFiZWwnOiAnZGVza3RvcCcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICd2YWx1ZSc6IDEsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdldmVudF9jYWxsYmFjayc6IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHVyaVBhcmFtcy5CYWNrVG8pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBhbmNob3IgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdhJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYW5jaG9yLmhyZWYgPSB1cmlQYXJhbXMuQmFja1RvO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGFuY2hvci5wcm90b2NvbCAhPT0gXCJqYXZhc2NyaXB0OlwiKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHdpbmRvdy5sb2NhdGlvbi5ocmVmID0gdXJpUGFyYW1zLkJhY2tUby5yZXBsYWNlKC9eLipcXC9cXC9bXlxcL10rLywgJycpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCRzY29wZS5jb3Vwb24pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IGRhdGEuYmV0YSA/IFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3VzZXJzX3VzZWNvdXBvbicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYmFjazogZGF0YS50YXJnZXRQYWdlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSkgOiAnL3VzZXIvdXNlQ291cG9uLnBocD9Db2RlPScgKyAkc2NvcGUuY291cG9uO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IGRhdGEuYmV0YSA/IGRhdGEudGFyZ2V0UGFnZSA6ICdhY2NvdW50L2xpc3QnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5zcGlubmVyID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbGV0IGVycm9yID0gZGF0YS5lcnJvcnM7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoZXJyb3IuaW5kZXhPZignRVJST1I6JykgIT09IC0xKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGVycm9yID0gZXJyb3Iuc3Vic3RyaW5nKGVycm9yLmluZGV4T2YoJ0VSUk9SOicpICsgNyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgZGlhbG9nU2VydmljZS5hbGVydChlcnJvciwgVHJhbnNsYXRvci50cmFucyhcImFsZXJ0cy5lcnJvclwiKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5hbHdheXMoKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnNwaW5uZXIgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LCAwKTtcbiAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgLmZhaWwoZmllbGQgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgZm9jdXNPbkVycm9yKCc6Zmlyc3QnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChmaWVsZCA9PT0gJ3Bhc3N3b3JkJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJyNwYXNzd29yZCcpLmZvY3VzKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3Bpbm5lciA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS4kYXBwbHkoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH07XG4gICAgICAgICAgICAkKCdodG1sLCBib2R5JykuYW5pbWF0ZSh7c2Nyb2xsVG9wIDogJCgnI3JlZ2lzdGVyJykub2Zmc2V0KCkudG9wIC0gNjB9LCAxMDAwKTtcbiAgICAgICAgfV0pXG4gICAgICAgIC5jb250cm9sbGVyKCdsb2dpbkN0cmwnLCBbJyRzY29wZScsICckaHR0cCcsICckbG9jYXRpb24nLCAnJHRpbWVvdXQnLCAnJHNjZScsICdVc2VyJywgZnVuY3Rpb24gKCRzY29wZSwgJGh0dHAsICRsb2NhdGlvbiwgJHRpbWVvdXQsICRzY2UsIFVzZXIpIHtcbiAgICAgICAgICAgIGRvY3VtZW50LnRpdGxlID0gVHJhbnNsYXRvci50cmFucygnbWV0YS50aXRsZS5sb2dpbicpO1xuICAgICAgICAgICAgbGV0IHVyaVBhcmFtcyA9ICRsb2NhdGlvbi5zZWFyY2goKTtcbiAgICAgICAgICAgIGxldCBwcmV2U3RlcDtcblxuICAgICAgICAgICAgJHNjb3BlLnVzZXIgPSBVc2VyO1xuXG4gICAgICAgICAgICAkc2NvcGUuc3RlcCA9ICdsb2dpbic7XG4gICAgICAgICAgICAkc2NvcGUuaW5mb3JtYXRpb25NZXNzYWdlID0gZmFsc2U7XG4gICAgICAgICAgICAkc2NvcGUuYW5zd2VyID0gJyc7XG4gICAgICAgICAgICAkc2NvcGUucmVjYXB0Y2hhID0gJyc7XG5cbiAgICAgICAgICAgIGlmICgkbG9jYXRpb24uc2VhcmNoKCkuZXJyb3IpIHtcbiAgICAgICAgICAgICAgICAkc2NvcGUuZXJyb3IgPSAkbG9jYXRpb24uc2VhcmNoKCkuZXJyb3I7XG4gICAgICAgICAgICAgICAgJHNjb3BlLnVzZXIubG9naW4gPSAkKCcjdXNlcm5hbWUnKS5kYXRhKCdsb2dpbi1oaW50Jyk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICRzY29wZS5zdWJtaXRCdXR0b24gPSB7XG4gICAgICAgICAgICAgICAgbG9naW46IFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiU2lnbiBpblwiKSAqLydzaWduLWluLmJ1dHRvbicpLFxuICAgICAgICAgICAgICAgIG90Y1JlY292ZXJ5OiBUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIlJlY292ZXJcIikgKi8nbG9naW4uYnV0dG9uLnJlY292ZXJ5JyksXG4gICAgICAgICAgICAgICAgcXVlc3Rpb246IFRyYW5zbGF0b3IudHJhbnMoJ2xvZ2luLmJ1dHRvbi5sb2dpbicpXG4gICAgICAgICAgICB9O1xuICAgICAgICAgICAgJHNjb3BlLnN1Ym1pdEJ1dHRvbi5vdGMgPSAkc2NvcGUuc3VibWl0QnV0dG9uLmxvZ2luO1xuXG4gICAgICAgICAgICBpbml0T2F1dGhMaW5rcygoKSA9PiB7XG4gICAgICAgICAgICAgICAgcHJldlN0ZXAgPSAkc2NvcGUuc3RlcDtcbiAgICAgICAgICAgICAgICAkc2NvcGUuc3RlcCA9ICdtYl9xdWVzdGlvbic7XG4gICAgICAgICAgICAgICAgJHNjb3BlLiRhcHBseSgpO1xuICAgICAgICAgICAgfSwgKCkgPT4ge1xuICAgICAgICAgICAgICAgICRzY29wZS5zdGVwID0gcHJldlN0ZXA7XG4gICAgICAgICAgICAgICAgJHNjb3BlLiRhcHBseSgpO1xuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIC8vIFRPRE8gVHJhY2UgaW4gaWU4XG4gICAgICAgICAgICAvLyR0aW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIC8vXHRpZih0eXBlb2YgbmF2aWdhdG9yICE9ICd1bmRlZmluZWQnICYmICFuYXZpZ2F0b3IudXNlckFnZW50Lm1hdGNoKCdGaXJlZm94JykgJiYgJCgnaW5wdXQ6LXdlYmtpdC1hdXRvZmlsbCcpLmxlbmd0aCl7XG4gICAgICAgICAgICAvL1x0XHQkc2NvcGUuYXV0b2ZpbGwgPSB0cnVlO1xuICAgICAgICAgICAgLy9cdH1cbiAgICAgICAgICAgIC8vfSwgMjUwKTtcblxuICAgICAgICAgICAgJHNjb3BlLnBvcHVwVGl0bGUgPSB7XG4gICAgICAgICAgICAgICAgbG9naW46ICRzY2UudHJ1c3RBc0h0bWwoVHJhbnNsYXRvci50cmFucygnbG9naW4udGl0bGUubG9naW4nKSksXG4gICAgICAgICAgICAgICAgbWJfcXVlc3Rpb246ICRzY2UudHJ1c3RBc0h0bWwoVHJhbnNsYXRvci50cmFucygnbG9naW4udGl0bGUubG9naW4nKSksXG4gICAgICAgICAgICAgICAgcXVlc3Rpb246ICRzY2UudHJ1c3RBc0h0bWwoVHJhbnNsYXRvci50cmFucygnbG9naW4udGl0bGUubG9naW4nKSksXG4gICAgICAgICAgICAgICAgb3RjUmVjb3Zlcnk6ICRzY2UudHJ1c3RBc0h0bWwoVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJSZWNvdmVyeVwiKSAqLydsb2dpbi50aXRsZS5yZWNvdmVyeScpKVxuICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICRzY29wZS5wb3B1cFRpdGxlLm90YyA9ICRzY29wZS5wb3B1cFRpdGxlLmxvZ2luO1xuICAgICAgICAgICAgJHNjb3BlLm90Y0lucHV0TGFiZWwgPSBUcmFuc2xhdG9yLnRyYW5zKC8qIEBEZXNjKFwiT25lLXRpbWUgY29kZVwiKSAqLyAnbG9naW4ub3RjJyk7XG4gICAgICAgICAgICAkc2NvcGUub3RjSW5wdXRIaW50ID0gbnVsbDtcblxuICAgICAgICAgICAgJHNjb3BlLnN1Ym1pdCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICBpZiAoJ290YycgIT09ICRzY29wZS5zdGVwICYmICdxdWVzdGlvbicgIT09ICRzY29wZS5zdGVwKSB7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS51c2VyLl9vdGMgPSBudWxsO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBpZiAoJ290Y1JlY292ZXJ5JyAhPT0gJHNjb3BlLnN0ZXApIHtcbiAgICAgICAgICAgICAgICAgICAgZGVsZXRlICRzY29wZS51c2VyLl9vdGNfcmVjb3Zlcnk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICRzY29wZS5zaG93Rm9yZ290TGluayA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICRzY29wZS5zcGlubmVyID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAvLyBpZTExIGZpeCwgc2VlICMxMDYyNVxuICAgICAgICAgICAgICAgIHZhciBjb29raWUgPSAkLmNvb2tpZSgpO1xuICAgICAgICAgICAgICAgIGlmIChPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwoY29va2llLCAnWFNSRi1UT0tFTicpKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvb2tpZSA9IGNvb2tpZVsnWFNSRi1UT0tFTiddO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIGNvb2tpZSA9ICQuY29va2llKCdYU1JGLVRPS0VOJyk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICRzY29wZS51c2VyLkZvcm1Ub2tlbiA9IGNvb2tpZTtcblxuICAgICAgICAgICAgICAgICRodHRwKHtcbiAgICAgICAgICAgICAgICAgICAgdXJsOiBSb3V0aW5nLmdlbmVyYXRlKCdhd19sb2dpbl9jbGllbnRfY2hlY2snKSxcbiAgICAgICAgICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICAgICAgICAgIGhlYWRlcnM6IHsnQ29udGVudC1UeXBlJzogJ2FwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZCd9XG4gICAgICAgICAgICAgICAgfSkudGhlbihmdW5jdGlvbiAocmVzKSB7XG4gICAgICAgICAgICAgICAgICAgIERuNjk4dENRID0gZXZhbChyZXMuZGF0YS5leHByKTsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBuby1nbG9iYWwtYXNzaWduXG4gICAgICAgICAgICAgICAgICAgICRzY29wZS51c2VyLl9jc3JmX3Rva2VuID0gcmVzLmRhdGEuY3NyZl90b2tlbjtcblxuICAgICAgICAgICAgICAgICAgICBpZiAoJHNjb3BlLnJlY2FwdGNoUmVxdWlyZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKCdyZWNhcHRjaGEgcmVxdWlyZWQgb24gc3VibWl0Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICBpbml0UmVjYXB0Y2hhKCRzY29wZSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB3aGVuUmVjYXB0Y2hhU29sdmVkKGZ1bmN0aW9uKHJlY2FwdGNoYV9jb2RlKXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUucmVjYXB0Y2hSZXF1aXJlZCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5yZWNhcHRjaGEgPSByZWNhcHRjaGFfY29kZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zb2xlLmxvZygnc2VudCByZWNhcHRjaGEgb24gc3VibWl0Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnRyeUxvZ2luKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICRzY29wZS50cnlMb2dpbigpO1xuICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAkc2NvcGUudHJ5TG9naW4gPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgdmFyIGRhdGEgPSBhbmd1bGFyLmNvcHkoJHNjb3BlLnVzZXIpO1xuICAgICAgICAgICAgICAgIGlmKCRzY29wZS5zdGVwID09PSAncXVlc3Rpb24nKXtcbiAgICAgICAgICAgICAgICAgICAgZGF0YS5fb3RjID0gJHNjb3BlLnF1ZXN0aW9uLnF1ZXN0aW9uICsgJz0nICsgJHNjb3BlLmFuc3dlcjtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZGF0YS5yZWNhcHRjaGEgPSAkc2NvcGUucmVjYXB0Y2hhO1xuICAgICAgICAgICAgICAgICRodHRwKHtcbiAgICAgICAgICAgICAgICAgICAgdXJsOiBSb3V0aW5nLmdlbmVyYXRlKCdhd191c2Vyc19sb2dpbmNoZWNrJyksXG4gICAgICAgICAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLFxuICAgICAgICAgICAgICAgICAgICBoZWFkZXJzOiB7J0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi94LXd3dy1mb3JtLXVybGVuY29kZWQnLCAnWC1TY3JpcHRlZCc6ICBEbjY5OHRDUSB8fCBNYXRoLnJhbmRvbX0sXG4gICAgICAgICAgICAgICAgICAgIGRhdGE6ICQucGFyYW0oZGF0YSlcbiAgICAgICAgICAgICAgICAgICAgLy8gdG9kbyBmYWlsIVxuICAgICAgICAgICAgICAgIH0pLnRoZW4oZnVuY3Rpb24gKHJlcykge1xuICAgICAgICAgICAgICAgICAgICBjb25zdCBkYXRhID0gcmVzLmRhdGE7XG4gICAgICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgZGF0YSA9PT0gJ29iamVjdCcpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChkYXRhLnN1Y2Nlc3MpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAod2luZG93Lmludml0ZUNvZGUpe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X2ludml0ZV9jb25maXJtJywgeydzaGFyZUNvZGUnOiB3aW5kb3cuaW52aXRlQ29kZX0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChzZXNzaW9uU3RvcmFnZS5iYWNrVXJsICYmXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlc3Npb25TdG9yYWdlLmJhY2tVcmwuaW5kZXhPZihcIi9sb2dvdXRcIikgPT09IC0xICYmXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlc3Npb25TdG9yYWdlLmJhY2tVcmwuaW5kZXhPZihcIi9sb2dpbkZyYW1lXCIpID09PSAtMSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IHNlc3Npb25TdG9yYWdlLmJhY2tVcmw7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHVyaVBhcmFtcy5CYWNrVG8pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGFuY2hvciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2EnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYW5jaG9yLmhyZWYgPSB1cmlQYXJhbXMuQmFja1RvO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmKGFuY2hvci5wcm90b2NvbCAhPT0gXCJqYXZhc2NyaXB0OlwiKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IHVyaVBhcmFtcy5CYWNrVG8ucmVwbGFjZSgvXi4qXFwvXFwvW15cXC9dKy8sICcnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoJHNjb3BlLnN0ZXAgPT09ICdvdGNSZWNvdmVyeScgJiYgJHNjb3BlLnVzZXIuX290Y19yZWNvdmVyeSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyBUT0RPOiBjaGFuZ2UgdG8gcm91dGluZ1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X3Byb2ZpbGVfMmZhY3RvcicpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LmxvY2F0aW9uLmhyZWYgPSAnLyc7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3Bpbm5lciA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICgobnVsbCAhPT0gJHNjb3BlLnVzZXIuX290YyB8fCAnJyAhPT0gJHNjb3BlLnVzZXIuX290YykgJiYgJ290Y1JlY292ZXJ5JyAhPT0gJHNjb3BlLnN0ZXAgJiYgISFkYXRhLm90Y1JlcXVpcmVkKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5vdGNJbnB1dEhpbnQgPSBkYXRhLm90Y0lucHV0SGludDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYodHlwZW9mKGRhdGEub3RjSW5wdXRMYWJlbCkgPT09ICdzdHJpbmcnKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3RlcCA9ICdvdGMnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLm90Y0lucHV0TGFiZWwgPSBkYXRhLm90Y0lucHV0TGFiZWw7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkdGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnI290YycpLmZvY3VzKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9LCAyMDApXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgZWxzZXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5zdGVwID0gJ3F1ZXN0aW9uJztcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxldCBzZWxlY3RlZCA9IDA7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUucXVlc3Rpb25zID0gYW5ndWxhci5jb3B5KGRhdGEub3RjSW5wdXRMYWJlbCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc2VsZWN0UXVlc3Rpb25UZXh0ID0gVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJQbGVhc2Ugc2VsZWN0IGEgcXVlc3Rpb25cIikgKi8gXCJzZWxlY3QtcXVlc3Rpb25cIik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUucXVlc3Rpb25zLnVuc2hpZnQoe1wicXVlc3Rpb25cIjogJHNjb3BlLnNlbGVjdFF1ZXN0aW9uVGV4dCwgXCJtYXNrSW5wdXRcIjogZmFsc2V9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmKHR5cGVvZigkc2NvcGUucXVlc3Rpb24pID09PSAnb2JqZWN0JylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBmb3IobGV0IGlkeCBpbiAkc2NvcGUucXVlc3Rpb25zKXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYoT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKCRzY29wZS5xdWVzdGlvbnMsIGlkeCkgJiYgJHNjb3BlLnF1ZXN0aW9uc1tpZHhdLnF1ZXN0aW9uID09PSAkc2NvcGUucXVlc3Rpb24ucXVlc3Rpb24pe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZWN0ZWQgPSBpZHg7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5xdWVzdGlvbiA9ICRzY29wZS5xdWVzdGlvbnNbc2VsZWN0ZWRdO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYoJHNjb3BlLmFuc3dlciAhPT0gXCJcIiAmJiAkc2NvcGUuYW5zd2VyICE9PSBudWxsKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5lcnJvciA9IGRhdGEubWVzc2FnZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGVsc2VcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuaW5mb3JtYXRpb25NZXNzYWdlID0gZGF0YS5tZXNzYWdlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGRhdGEubWVzc2FnZS5pbmRleE9mKFwiQ1NSRlwiKSAhPT0gLTEpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmNzcmYgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmFuc3dlciA9ICcnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJyNxdWVzdGlvbi1hbnN3ZXInKS5mb2N1cygpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSwgMjAwKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5xdWVzdGlvbkNoYW5nZWQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUub3RjU2hvd1JlY292ZXJ5ID0gZGF0YS5vdGNTaG93UmVjb3Zlcnk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGRhdGEuYmFkQ3JlZGVudGlhbHMpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLnNob3dGb3Jnb3RMaW5rID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICgnb3RjUmVjb3ZlcnknID09PSAkc2NvcGUuc3RlcCAmJiBudWxsICE9PSAkc2NvcGUudXNlci5fb3RjX3JlY292ZXJ5ICYmICcnICE9PSAkc2NvcGUudXNlci5fb3RjX3JlY292ZXJ5KSB8fFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAoJ290YycgPT09ICRzY29wZS5zdGVwICYmIG51bGwgIT09ICRzY29wZS51c2VyLl9vdGMgJiYgJycgIT09ICRzY29wZS51c2VyLl9vdGMpIHx8XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICgncXVlc3Rpb24nID09PSAkc2NvcGUuc3RlcCAmJiBudWxsICE9PSAkc2NvcGUuYW5zd2VyICYmICcnICE9PSAkc2NvcGUuYW5zd2VyKSB8fFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAoJ2xvZ2luJyA9PT0gJHNjb3BlLnN0ZXApXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5lcnJvciA9IGRhdGEubWVzc2FnZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGRhdGEubWVzc2FnZS5pbmRleE9mKFwiQ1NSRlwiKSAhPT0gLTEpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5jc3JmID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRzY29wZS5pbmZvcm1hdGlvbk1lc3NhZ2UgPSBkYXRhLm1lc3NhZ2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGRhdGEucmVjYXB0Y2hhUmVxdWlyZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc29sZS5sb2coJ3JlY2FwdGNoYSByZXF1aXJlZCcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUucmVjYXB0Y2hSZXF1aXJlZCA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICghJHNjb3BlLnJlY2FwdGNoYSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc29sZS5sb2coJ3JldHJ5IHN1Ym1pdCB3aXRoIHJlY2FwdGNoYScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmNsZWFyTWVzc2FnZXMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNldFRpbWVvdXQoJHNjb3BlLnN1Ym1pdCwgMTApO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgICRzY29wZS5xdWVzdGlvbkNoYW5nZWQgPSBmdW5jdGlvbigpe1xuICAgICAgICAgICAgICAgICQoJyNxdWVzdGlvbi1hbnN3ZXInKS5hdHRyKCd0eXBlJywgJHNjb3BlLnF1ZXN0aW9uLm1hc2tJbnB1dCA/IFwicGFzc3dvcmRcIiA6IFwidGV4dFwiKTtcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgICRzY29wZS5jbGVhck1lc3NhZ2VzID0gZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICRzY29wZS5lcnJvciA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIC8vJHNjb3BlLmluZm9ybWF0aW9uTWVzc2FnZSA9IGZhbHNlO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgJHNjb3BlLmJhY2sgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgJHNjb3BlLnVzZXIuX290YyA9IG51bGw7XG4gICAgICAgICAgICAgICAgJHNjb3BlLmFuc3dlciA9ICcnO1xuICAgICAgICAgICAgICAgICRzY29wZS51c2VyLl9vdGNfcmVjb3ZlcnkgPSBudWxsO1xuXG4gICAgICAgICAgICAgICAgc3dpdGNoICgkc2NvcGUuc3RlcCkge1xuICAgICAgICAgICAgICAgICAgICBjYXNlICdsb2dpbic6XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm47XG5cbiAgICAgICAgICAgICAgICAgICAgY2FzZSAnb3RjJzpcbiAgICAgICAgICAgICAgICAgICAgY2FzZSAncXVlc3Rpb24nOlxuICAgICAgICAgICAgICAgICAgICAgICAgZGVsZXRlICRzY29wZS51c2VyLl9vdGM7XG4gICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3RlcCA9ICdsb2dpbic7XG4gICAgICAgICAgICAgICAgICAgICAgICAkdGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnI3VzZXJuYW1lJykuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAgICAgICAgICAgY2FzZSAnb3RjUmVjb3ZlcnknOlxuICAgICAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJyNvdGMnKS5mb2N1cygpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3RlcCA9ICdvdGMnO1xuICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICRzY29wZS5jbGVhck1lc3NhZ2VzKCk7XG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAkc2NvcGUuZG9TdGVwID0gZnVuY3Rpb24gKHN0ZXApIHtcbiAgICAgICAgICAgICAgICBzd2l0Y2ggKHN0ZXApIHtcbiAgICAgICAgICAgICAgICAgICAgY2FzZSAnbG9naW4nOlxuICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAgICAgICAgICAgY2FzZSAnb3RjJzpcblxuICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAgICAgICAgICAgY2FzZSAnb3RjUmVjb3ZlcnknOlxuICAgICAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJyNvdGNSZWNvdmVyeScpLmZvY3VzKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAkc2NvcGUuY2xlYXJNZXNzYWdlcygpO1xuICAgICAgICAgICAgICAgICRzY29wZS5zdGVwID0gc3RlcDtcbiAgICAgICAgICAgIH07XG4gICAgICAgIH1dKVxuICAgICAgICAuY29udHJvbGxlcigncmVzdG9yZUN0cmwnLCBbJyRzY29wZScsICckaHR0cCcsICckdGltZW91dCcsICdVc2VyJywgZnVuY3Rpb24gKCRzY29wZSwgJGh0dHAsICR0aW1lb3V0LCBVc2VyKSB7XG4gICAgICAgICAgICAkc2NvcGUuc3VjY2VzcyA9IGZhbHNlO1xuICAgICAgICAgICAgJHNjb3BlLmVycm9yID0gZmFsc2U7XG4gICAgICAgICAgICAkc2NvcGUuZXJyb3JUZXh0ID0gZmFsc2U7XG4gICAgICAgICAgICAkc2NvcGUudXNlciA9IHt1c2VybmFtZTogVXNlci5sb2dpbn07XG4gICAgICAgICAgICAkc2NvcGUuc3VibWl0dGVkID0gZmFsc2U7XG5cbiAgICAgICAgICAgICRzY29wZS5zdWJtaXQgPSAoKSA9PiB7XG4gICAgICAgICAgICAgICAgaWYgKCRzY29wZS5yZXN0b3JlRm9ybS4kaW52YWxpZCkge1xuICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3VibWl0dGVkID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICRzY29wZS5zcGlubmVyID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgJGh0dHAoe1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiBSb3V0aW5nLmdlbmVyYXRlKCdhd191c2Vyc19yZXN0b3JlJyksXG4gICAgICAgICAgICAgICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGhlYWRlcnM6IHsnQ29udGVudC1UeXBlJzogJ2FwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZCd9LFxuICAgICAgICAgICAgICAgICAgICAgICAgZGF0YTogJC5wYXJhbSgkc2NvcGUudXNlcilcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIHRvZG8gZmFpbCFcbiAgICAgICAgICAgICAgICAgICAgfSkudGhlbigoe2RhdGF9KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3Bpbm5lciA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGRhdGEuc3VjY2VzcylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuc3VjY2VzcyA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoZGF0YS5lcnJvcikge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc2NvcGUuZXJyb3IgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmVycm9yVGV4dCA9IGRhdGEuZXJyb3I7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJHNjb3BlLmVycm9yID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHRpbWVvdXQoKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKCcjZm9yZ290LXBhc3N3b3JkJykuc2VsZWN0KCkuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAkc2NvcGUuY2hhbmdlID0gKCkgPT4ge1xuICAgICAgICAgICAgICAgICRzY29wZS5zdWJtaXR0ZWQgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAkc2NvcGUuZXJyb3IgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAkc2NvcGUuZXJyb3JUZXh0ID0gZmFsc2U7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1dKVxuICAgICAgICAuY29udHJvbGxlcignaG9tZUN0cmwnLCBbJyRzY29wZScsICckc3RhdGUnLCAnJGxvY2F0aW9uJywgJyRodHRwJywgJ2RpYWxvZ1NlcnZpY2UnLCBmdW5jdGlvbigkc2NvcGUsICRzdGF0ZSwgJGxvY2F0aW9uLCAkaHR0cCwgZGlhbG9nU2VydmljZSkge1xuICAgICAgICAgICAgZG9jdW1lbnQudGl0bGUgPSBUcmFuc2xhdG9yLnRyYW5zKCdtZXRhLnRpdGxlJyk7XG4gICAgICAgICAgICBjb25zdCB1cmlQYXJhbXMgPSAkbG9jYXRpb24uc2VhcmNoKCk7XG5cbiAgICAgICAgICAgIGZ1bmN0aW9uIGNyZWF0ZUVycm9yRGlhbG9nKCkge1xuICAgICAgICAgICAgICAgIGRpYWxvZ1NlcnZpY2UuZmFzdENyZWF0ZShcbiAgICAgICAgICAgICAgICAgICAgXCJFcnJvclwiLFxuICAgICAgICAgICAgICAgICAgICBcIkN1cnJlbnRseSB3ZSBhcmUgbGltaXRpbmcgb3VyIHVzZXJzIHRvIHNlbmQgbm8gbW9yZSB0aGFuIDEwMCBsb29rdXAgcmVxdWVzdHMgcGVyIDUgbWludXRlcy4gWW91IGhhdmUgcmVhY2hlZCB5b3VyIGxpbWl0IHBsZWFzZSBjb21lIGJhY2sgaW4gNSBtaW51dGVzIGlmIHlvdSB3aXNoIHRvIGNvbnRpbnVlIHNlYXJjaGluZy5cIixcbiAgICAgICAgICAgICAgICAgICAgdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgW1xuICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRleHQ6IFRyYW5zbGF0b3IudHJhbnMoJ2J1dHRvbi5vaycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNsaWNrOiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kaWFsb2coJ2Nsb3NlJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnY2xhc3MnOiAnYnRuLWJsdWUnXG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIF0sXG4gICAgICAgICAgICAgICAgICAgIDUwMFxuICAgICAgICAgICAgICAgICk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBmdW5jdGlvbiBhdXRvY29tcGxldGVTb3VyY2UocmVxdWVzdCwgcmVzcG9uc2UpIHtcbiAgICAgICAgICAgICAgICBtZXJjaGFudElucHV0LmFkZENsYXNzKCdsb2FkaW5nLWlucHV0JykucmVtb3ZlQ2xhc3MoJ3NlYXJjaC1pbnB1dCcpO1xuXG4gICAgICAgICAgICAgICAgJGh0dHAucG9zdChcbiAgICAgICAgICAgICAgICAgICAgUm91dGluZy5nZW5lcmF0ZSgnYXdfbWVyY2hhbnRfbG9va3VwX2RhdGEnKSxcbiAgICAgICAgICAgICAgICAgICAgJC5wYXJhbSh7XG4gICAgICAgICAgICAgICAgICAgICAgICBxdWVyeTogcmVxdWVzdC50ZXJtXG4gICAgICAgICAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgICAgICAgICB7IGhlYWRlcnM6IHsnQ29udGVudC1UeXBlJzogJ2FwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZCd9IH1cbiAgICAgICAgICAgICAgICApLnRoZW4oKHsgZGF0YSB9KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIGxldCByZXN1bHQ7XG4gICAgICAgICAgICAgICAgICAgIGlmICgkLmlzRW1wdHlPYmplY3QoZGF0YSkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJlc3VsdCA9IFt7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbGFiZWw6ICdObyBtZXJjaGFudHMgZm91bmQnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlOiBcIlwiXG4gICAgICAgICAgICAgICAgICAgICAgICB9XTtcbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJlc3VsdCA9IGRhdGE7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgcmVzcG9uc2UocmVzdWx0KTtcbiAgICAgICAgICAgICAgICAgICAgbWVyY2hhbnRJbnB1dC5yZW1vdmVDbGFzcygnbG9hZGluZy1pbnB1dCcpLmFkZENsYXNzKCdzZWFyY2gtaW5wdXQnKTtcbiAgICAgICAgICAgICAgICB9KS5jYXRjaCgoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIGNyZWF0ZUVycm9yRGlhbG9nKCk7XG4gICAgICAgICAgICAgICAgICAgIG1lcmNoYW50SW5wdXQucmVtb3ZlQ2xhc3MoJ2xvYWRpbmctaW5wdXQnKS5hZGRDbGFzcygnc2VhcmNoLWlucHV0Jyk7XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICAkc2NvcGUuJG9uKCckc3RhdGVDaGFuZ2VTdWNjZXNzJywgKGV2LCB0b1N0YXRlLCB0b1BhcmFtcywgZnJvbVN0YXRlKSA9PiB7XG4gICAgICAgICAgICAgICAgaWYgKHVyaVBhcmFtcy5CYWNrVG8gJiYgZnJvbVN0YXRlLm5hbWUgIT09ICdsb2dpbicpIHtcbiAgICAgICAgICAgICAgICAgICAgJHN0YXRlLmdvKCdsb2dpbicpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICBjb25zdCBtZXJjaGFudElucHV0ID0gJCgnI21lcmNoYW50Jyk7XG4gICAgICAgICAgICBtZXJjaGFudElucHV0LnJlbW92ZUNsYXNzKCdsb2FkaW5nLWlucHV0JykuYWRkQ2xhc3MoJ3NlYXJjaC1pbnB1dCcpO1xuXG4gICAgICAgICAgICBtZXJjaGFudElucHV0LmF1dG9jb21wbGV0ZSh7XG4gICAgICAgICAgICAgICAgbWluTGVuZ3RoOiAzLFxuICAgICAgICAgICAgICAgIGRlbGF5OiA1MDAsXG4gICAgICAgICAgICAgICAgc291cmNlOiAocmVxdWVzdCwgcmVzcG9uc2UpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgYXV0b2NvbXBsZXRlU291cmNlKHJlcXVlc3QsIHJlc3BvbnNlKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHNlbGVjdDogKGV2ZW50LCB1aSkgPT4ge1xuICAgICAgICAgICAgICAgICAgIHdpbmRvdy5vcGVuKFxuICAgICAgICAgICAgICAgICAgICAgICAoJCgnaHRtbDpmaXJzdCcpLmhhc0NsYXNzKCdtb2JpbGUtZGV2aWNlJykgPyAnL20nIDogJycpICtcbiAgICAgICAgICAgICAgICAgICAgICAgUm91dGluZy5nZW5lcmF0ZSgnYXdfbWVyY2hhbnRfbG9va3VwJykgKyAnLycgKyB1aS5pdGVtLm5hbWVUb1VybCxcbiAgICAgICAgICAgICAgICAgICAgICAgJ19ibGFuaydcbiAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgY3JlYXRlOiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kYXRhKCd1aS1hdXRvY29tcGxldGUnKS5fcmVuZGVySXRlbSA9ICh1bCwgaXRlbSkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgY29uc3QgeyBsYWJlbCwgY2F0ZWdvcnkgfSA9IGl0ZW07XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoIWxhYmVsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgY29uc3QgZWxlbWVudCA9ICQoJzxhPjwvYT4nKS5hcHBlbmQoJChcIjxzcGFuPjwvc3Bhbj5cIikuaHRtbChgJHtsYWJlbH0mbmJzcDtgKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoY2F0ZWdvcnkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbGVtZW50LmFwcGVuZCgkKFwiPHNwYW4+PC9zcGFuPlwiKS5hZGRDbGFzcyhcImJsdWVcIikuaHRtbChgKCR7Y2F0ZWdvcnl9KWApKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICQoJzxsaT48L2xpPicpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLmRhdGEoXCJpdGVtLmF1dG9jb21wbGV0ZVwiLCBpdGVtKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5hcHBlbmQoZWxlbWVudClcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAuYXBwZW5kVG8odWwpO1xuICAgICAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgb3BlbjogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAkKFwidWwudWktbWVudVwiKS53aWR0aCgkKHRoaXMpLmlubmVyV2lkdGgoKSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSkub2ZmKCdibHVyJyk7XG4gICAgICAgIH1dKVxufSk7XG4iLCJkZWZpbmUoWydhbmd1bGFyLWJvb3QnXSwgKCkgPT4ge1xuXHRhbmd1bGFyXG5cdFx0Lm1vZHVsZSgnbGFuZGluZ1BhZ2UtZGlyJywgW10pXG5cdFx0LmRpcmVjdGl2ZSgnYXV0b2ZpbGwnLCBbJyRsb2NhdGlvbicsICckdGltZW91dCcsICgkbG9jYXRpb24sICR0aW1lb3V0KSA9PiB7XG5cdFx0XHRjb25zdCBzZXRWYWx1ZSA9IChuYW1lLCBtb2RlbCwgc2NvcGUpID0+IHtcblx0XHRcdFx0Y29uc3QgdmFsdWUgPSBtb2RlbC4kdmlld1ZhbHVlO1xuXHRcdFx0XHRpZiAoT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKCRsb2NhdGlvbi5zZWFyY2goKSwgbmFtZSkgJiYgIXZhbHVlKSB7XG5cdFx0XHRcdFx0bW9kZWwuJHNldFZpZXdWYWx1ZSgkbG9jYXRpb24uc2VhcmNoKClbbmFtZV0pO1xuXHRcdFx0XHRcdG1vZGVsLiRyZW5kZXIoKTtcblx0XHRcdFx0XHRzY29wZS4kYXBwbHkoKTtcblx0XHRcdFx0fVxuXHRcdFx0fTtcblx0XHRcdHJldHVybiB7XG5cdFx0XHRcdHJlc3RyaWN0OiAnQScsXG5cdFx0XHRcdHJlcXVpcmU6ICduZ01vZGVsJyxcblx0XHRcdFx0bGluazogKHNjb3BlLCBlbGVtLCBhdHRycywgY3RybCkgPT4ge1xuXHRcdFx0XHRcdCR0aW1lb3V0KCgpID0+IHtcblx0XHRcdFx0XHRcdHNldFZhbHVlKGF0dHJzLmF1dG9maWxsLCBjdHJsLCBzY29wZSk7XG5cdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0c2NvcGUuJG9uKCckbG9jYXRpb25DaGFuZ2VTdWNjZXNzJywgKCkgPT4ge1xuXHRcdFx0XHRcdFx0c2V0VmFsdWUoYXR0cnMuYXV0b2ZpbGwsIGN0cmwsIHNjb3BlKTtcblx0XHRcdFx0XHR9KTtcblx0XHRcdFx0fVxuXHRcdFx0fVxuXHRcdH1dKVxuXHRcdC5kaXJlY3RpdmUoJ3NhbWVQYXNzd29yZCcsIFsoKSA9PiB7XG5cdFx0XHRyZXR1cm4ge1xuXHRcdFx0XHRyZXF1aXJlOiAnbmdNb2RlbCcsXG5cdFx0XHRcdGxpbms6IChzY29wZSwgZWxlbSwgYXR0cnMsIGN0cmwpID0+IHtcblx0XHRcdFx0XHRjb25zdCBvcmlnaW5hbFBhc3MgPSAnIycgKyBhdHRycy5zYW1lUGFzc3dvcmQ7XG5cblx0XHRcdFx0XHRlbGVtLmFkZChvcmlnaW5hbFBhc3MpLm9uKCdrZXl1cCcsICgpID0+IHtcblx0XHRcdFx0XHRcdHNjb3BlLiRhcHBseSgoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdGNvbnN0IG9yaWdpbmFsUGFzc1ZhbHVlID0gJChvcmlnaW5hbFBhc3MpLnZhbCgpO1xuXHRcdFx0XHRcdFx0XHRjb25zdCBzYW1lUGFzc1ZhbHVlID0gZWxlbS52YWwoKTtcblx0XHRcdFx0XHRcdFx0Y29uc3QgdmFsaWRpdHkgPSBvcmlnaW5hbFBhc3NWYWx1ZSA9PT0gc2FtZVBhc3NWYWx1ZSB8fCBzYW1lUGFzc1ZhbHVlID09PSAnJyB8fCBvcmlnaW5hbFBhc3NWYWx1ZSA9PT0gJyc7XG5cdFx0XHRcdFx0XHRcdGN0cmwuJHNldFZhbGlkaXR5KCdzYW1lUGFzc3dvcmQnLCB2YWxpZGl0eSk7XG5cdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHR9KTtcblx0XHRcdFx0fVxuXHRcdFx0fVxuXHRcdH1dKTtcbn0pOyIsImRlZmluZShbXG4gICAgICAgICdhbmd1bGFyLWJvb3QnLCAncm91dGluZycsICd0cmFuc2xhdG9yLWJvb3QnLCAnYW5ndWxhci11aS1yb3V0ZXInLFxuICAgICAgICAnZGlyZWN0aXZlcy9jdXN0b21pemVyJywgJ2RpcmVjdGl2ZXMvYXV0b0ZvY3VzJyxcbiAgICAgICAgJ3BhZ2VzL2xhbmRpbmcvY29udHJvbGxlcnMnLCAncGFnZXMvbGFuZGluZy9kaXJlY3RpdmVzJ1xuICAgIF0sXG4gICAgKCkgPT4ge1xuICAgICAgICBhbmd1bGFyXG4gICAgICAgICAgICAubW9kdWxlKCdsYW5kaW5nUGFnZScsIFtcbiAgICAgICAgICAgICAgICAndWkucm91dGVyJywgJ2N1c3RvbWl6ZXItZGlyZWN0aXZlJywgJ2F1dG8tZm9jdXMtZGlyZWN0aXZlJyxcbiAgICAgICAgICAgICAgICAnYXBwQ29uZmlnJywgJ2xhbmRpbmdQYWdlLWN0cmwnLCAnbGFuZGluZ1BhZ2UtZGlyJ1xuICAgICAgICAgICAgXSlcbiAgICAgICAgICAgIC5jb25maWcoWyckc3RhdGVQcm92aWRlcicsICckdXJsUm91dGVyUHJvdmlkZXInLCAnJGxvY2F0aW9uUHJvdmlkZXInLCAoJHN0YXRlUHJvdmlkZXIsICR1cmxSb3V0ZXJQcm92aWRlciwgJGxvY2F0aW9uUHJvdmlkZXIpID0+IHtcbiAgICAgICAgICAgICAgICAvLyBWaWRlb1xuICAgICAgICAgICAgICAgICQoJyN2aWRlby1idG4nKS5vbignY2xpY2snLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICAgICAgJCh0aGlzKVxuICAgICAgICAgICAgICAgICAgICAgICAgLnJlcGxhY2VXaXRoKFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJzxpZnJhbWUgc3JjPVwiLy9wbGF5ZXIudmltZW8uY29tL3ZpZGVvLzMxOTQ2OTIyMD9jb2xvcj00Njg0YzRcIiB3aWR0aD1cIjU0NVwiIGhlaWdodD1cIjMwN1wiIGZyYW1lYm9yZGVyPVwiMFwiIHdlYmtpdGFsbG93ZnVsbHNjcmVlbiBtb3phbGxvd2Z1bGxzY3JlZW4gYWxsb3dmdWxsc2NyZWVuPjwvaWZyYW1lPicpXG4gICAgICAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgJHVybFJvdXRlclByb3ZpZGVyLm90aGVyd2lzZSgoJGluamVjdG9yLCAkbG9jYXRpb24pID0+IHtcbiAgICAgICAgICAgICAgICAgICAgLy8gaWYgKCcvaW5kZXgucGhwJyAhPT0gJGxvY2F0aW9uLnBhdGgoKSkge1xuICAgICAgICAgICAgICAgICAgICAvLyAgICAgbG9jYXRpb24uaHJlZiA9ICRsb2NhdGlvbi5wYXRoKCk7XG4gICAgICAgICAgICAgICAgICAgIC8vIH1cbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAkdXJsUm91dGVyUHJvdmlkZXIud2hlbigvXlxcL1thLXpdezJ9XFwvLywgJGxvY2F0aW9uID0+IHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCRsb2NhdGlvbi5wYXRoKCkuc3Vic3RyKDAsIDQpICE9PSBgLyR7bG9jYWxlfS9gKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zb2xlLmxvZygncmVkaXJlY3QgdG8gJywgJGxvY2F0aW9uLnBhdGgoKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICBsb2NhdGlvbi5ocmVmID0gJGxvY2F0aW9uLnBhdGgoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgJHN0YXRlUHJvdmlkZXJcbiAgICAgICAgICAgICAgICAgICAgLnN0YXRlKCdob21lJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiAnLycsXG4gICAgICAgICAgICAgICAgICAgICAgICBjb250cm9sbGVyOiAnaG9tZUN0cmwnXG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgIC5zdGF0ZSgnbG9jLWhvbWUnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICB1cmw6ICcve2xvY2FsZTpbYS16XVthLXpdfS8nLFxuICAgICAgICAgICAgICAgICAgICAgICAgY29udHJvbGxlcjogJ2hvbWVDdHJsJ1xuICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAuc3RhdGUoJ3JlZ2lzdGVyJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiAnL3JlZ2lzdGVyJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIHRlbXBsYXRlVXJsOiAnL3JlZ2lzdGVyJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnRyb2xsZXI6ICdyZWdpc3RlckN0cmwnXG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgIC5zdGF0ZSgnbG9jLXJlZ2lzdGVyJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiAnL3tsb2NhbGU6W2Etel1bYS16XX0vcmVnaXN0ZXInLFxuICAgICAgICAgICAgICAgICAgICAgICAgdGVtcGxhdGVVcmw6ICcvcmVnaXN0ZXInLFxuICAgICAgICAgICAgICAgICAgICAgICAgY29udHJvbGxlcjogJ3JlZ2lzdGVyQ3RybCdcbiAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgLnN0YXRlKCdyZWdpc3RlckJ1c2luZXNzJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiAnL3JlZ2lzdGVyQnVzaW5lc3MnLFxuICAgICAgICAgICAgICAgICAgICAgICAgdGVtcGxhdGVVcmw6ICcvcmVnaXN0ZXJCdXNpbmVzcycsXG4gICAgICAgICAgICAgICAgICAgICAgICBjb250cm9sbGVyOiAncmVnaXN0ZXJCdXNpbmVzc0N0cmwnXG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgIC5zdGF0ZSgnbG9jLXJlZ2lzdGVyQnVzaW5lc3MnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICB1cmw6ICcve2xvY2FsZTpbYS16XVthLXpdfS9yZWdpc3RlckJ1c2luZXNzJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIHRlbXBsYXRlVXJsOiAnL3JlZ2lzdGVyQnVzaW5lc3MnLFxuICAgICAgICAgICAgICAgICAgICAgICAgY29udHJvbGxlcjogJ3JlZ2lzdGVyQnVzaW5lc3NDdHJsJ1xuICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAuc3RhdGUoJ2xvZ2luJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiAnL2xvZ2luJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIHRlbXBsYXRlVXJsOiAnL2xvZ2luJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnRyb2xsZXI6ICdsb2dpbkN0cmwnXG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgIC5zdGF0ZSgnbG9jLWxvZ2luJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiAnL3tsb2NhbGU6W2Etel1bYS16XX0vbG9naW4nLFxuICAgICAgICAgICAgICAgICAgICAgICAgdGVtcGxhdGVVcmw6ICcvbG9naW4nLFxuICAgICAgICAgICAgICAgICAgICAgICAgY29udHJvbGxlcjogJ2xvZ2luQ3RybCdcbiAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgLnN0YXRlKCdyZXN0b3JlJywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiAnL3Jlc3RvcmUnLFxuICAgICAgICAgICAgICAgICAgICAgICAgdGVtcGxhdGVVcmw6ICcvcmVzdG9yZScsXG4gICAgICAgICAgICAgICAgICAgICAgICBjb250cm9sbGVyOiAncmVzdG9yZUN0cmwnXG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgIC5zdGF0ZSgnbG9jLXJlc3RvcmUnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICB1cmw6ICcve2xvY2FsZTpbYS16XVthLXpdfS9yZXN0b3JlJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIHRlbXBsYXRlVXJsOiAnL3Jlc3RvcmUnLFxuICAgICAgICAgICAgICAgICAgICAgICAgY29udHJvbGxlcjogJ3Jlc3RvcmVDdHJsJ1xuICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICRsb2NhdGlvblByb3ZpZGVyLmh0bWw1TW9kZSh7XG4gICAgICAgICAgICAgICAgICAgIGVuYWJsZWQ6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIHJld3JpdGVMaW5rcyA6IGZhbHNlXG4gICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIH1dKVxuICAgICAgICAgICAgLnJ1bigpO1xuXG4gICAgICAgICQoZG9jdW1lbnQpLnJlYWR5KCgpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IGFwcCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdtYWluLWJvZHknKTtcblxuICAgICAgICAgICAgaWYgKGFwcCkge1xuICAgICAgICAgICAgICAgIGFuZ3VsYXIuYm9vdHN0cmFwKGFwcCwgWydsYW5kaW5nUGFnZSddKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgYW5ndWxhci5ib290c3RyYXAoZG9jdW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgncGFnZS1sYW5kaW5nX19jb250YWluZXInKVswXSwgWydsYW5kaW5nUGFnZSddKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgfSk7IiwiZGVmaW5lKFsnanF1ZXJ5LWJvb3QnLCAnbGliL3V0aWxzJywgJ2xpYi9kaWFsb2cnLCAncm91dGluZyddLCBmdW5jdGlvbigkLCB1dGlscykge1xuICAgIHJldHVybiBmdW5jdGlvbihvblNob3dRdWVzdGlvbiwgb25IaWRlUXVlc3Rpb24pIHtcblxuICAgICAgICBmdW5jdGlvbiBnZXRLZXlUeXBlTmFtZSh0eXBlKSB7XG4gICAgICAgICAgICByZXR1cm4gYCR7dHlwZX1fbWJfYW5zd2VyYDtcbiAgICAgICAgfVxuXG4gICAgICAgIGZ1bmN0aW9uIGdldEFuc3dlcih0eXBlKSB7XG4gICAgICAgICAgICBsZXQgYW5zd2VyID0gbG9jYWxTdG9yYWdlLmdldEl0ZW0oZ2V0S2V5VHlwZU5hbWUodHlwZSkpO1xuICAgICAgICAgICAgaWYgKG51bGwgPT09IGFuc3dlcikge1xuICAgICAgICAgICAgICAgIGFuc3dlciA9IHV0aWxzLmdldENvb2tpZShnZXRLZXlUeXBlTmFtZSh0eXBlKSk7XG4gICAgICAgICAgICAgICAgaWYgKCd1bmRlZmluZWQnICE9PSB0eXBlb2YgYW5zd2VyKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBhbnN3ZXI7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIG51bGw7XG4gICAgICAgIH1cblxuICAgICAgICBmdW5jdGlvbiBzYXZlQW5zd2VyKHR5cGUsIGFuc3dlcikge1xuICAgICAgICAgICAgbG9jYWxTdG9yYWdlLnNldEl0ZW0oZ2V0S2V5VHlwZU5hbWUodHlwZSksIGFuc3dlcik7XG4gICAgICAgIH1cblxuICAgICAgICBmdW5jdGlvbiBnZXRRdWVyeVBhcmFtcygpIHtcbiAgICAgICAgICAgIGlmICghd2luZG93LmxvY2F0aW9uLnNlYXJjaCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB7fTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGNvbnN0IHJlc3VsdCA9IHt9O1xuICAgICAgICAgICAgdmFyIHF1ZXJ5ID0gd2luZG93LmxvY2F0aW9uLnNlYXJjaC5zdWJzdHJpbmcoMSk7XG4gICAgICAgICAgICB2YXIgdmFycyA9IHF1ZXJ5LnNwbGl0KCcmJyk7XG4gICAgICAgICAgICBmb3IgKHZhciBpID0gMDsgaSA8IHZhcnMubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICAgICAgICB2YXIgcGFpciA9IHZhcnNbaV0uc3BsaXQoJz0nKTtcbiAgICAgICAgICAgICAgICB2YXIgbmFtZSA9IGRlY29kZVVSSUNvbXBvbmVudChwYWlyWzBdKTtcbiAgICAgICAgICAgICAgICBpZiAobmFtZSA9PT0gJ2Vycm9yJykge1xuICAgICAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgcmVzdWx0W25hbWVdID0gZGVjb2RlVVJJQ29tcG9uZW50KHBhaXJbMV0pO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIHJlc3VsdDtcbiAgICAgICAgfVxuXG4gICAgICAgIGZ1bmN0aW9uIHJlZGlyZWN0KHR5cGUsIGFjdGlvbiwgbWFpbGJveEFjY2Vzcykge1xuICAgICAgICAgICAgZG9jdW1lbnQubG9jYXRpb24uaHJlZiA9IFJvdXRpbmcuZ2VuZXJhdGUoXG4gICAgICAgICAgICAgICAgJ2F3X3VzZXJtYWlsYm94X29hdXRoJyxcbiAgICAgICAgICAgICAgICBPYmplY3QuYXNzaWduKGdldFF1ZXJ5UGFyYW1zKCksIHtcbiAgICAgICAgICAgICAgICAgICAgJ3R5cGUnOiB0eXBlLFxuICAgICAgICAgICAgICAgICAgICAnYWN0aW9uJzogYWN0aW9uLFxuICAgICAgICAgICAgICAgICAgICAnbWFpbGJveEFjY2Vzcyc6IG1haWxib3hBY2Nlc3MsXG4gICAgICAgICAgICAgICAgICAgICdyZW1lbWJlck1lJzogJCgnI3JlbWVtYmVyX21lJykuaXMoJzpjaGVja2VkJykgJiYgYWN0aW9uID09PSAnbG9naW4nXG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cblxuICAgICAgICBmdW5jdGlvbiBub29wKCkge31cblxuICAgICAgICBjb25zdCBxdWVzdGlvbkVsZW0gPSAkKCcjc2Nhbi1tYWlsYm94LXF1ZXN0aW9uJyk7XG5cbiAgICAgICAgJCgnLm9hdXRoLWJ1dHRvbnMtbGlzdCBhJykub24oJ2NsaWNrJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuXG4gICAgICAgICAgICBjb25zdCBsaW5rID0gJCh0aGlzKTtcbiAgICAgICAgICAgIGNvbnN0IHR5cGUgPSBsaW5rLmRhdGEoJ3R5cGUnKTtcbiAgICAgICAgICAgIGNvbnN0IGFjdGlvbiA9IGxpbmsuZGF0YSgnYWN0aW9uJyk7XG5cbiAgICAgICAgICAgIGlmIChsaW5rLmRhdGEoJ21haWxib3gtc3VwcG9ydCcpICE9PSAnb2ZmJykge1xuICAgICAgICAgICAgICAgIGNvbnN0IGFuc3dlciA9IGdldEFuc3dlcih0eXBlKTtcblxuICAgICAgICAgICAgICAgIGlmIChudWxsICE9PSBhbnN3ZXIgfHwgL15idXNpbmVzcy8udGVzdCh3aW5kb3cubG9jYXRpb24uaG9zdG5hbWUpKSB7XG4gICAgICAgICAgICAgICAgICAgIHJlZGlyZWN0KHR5cGUsIGFjdGlvbiwgYW5zd2VyIHx8IGZhbHNlKTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBxdWVzdGlvbkVsZW0uZGF0YSgndHlwZScsIHR5cGUpO1xuICAgICAgICAgICAgICAgICAgICBxdWVzdGlvbkVsZW0uZGF0YSgnYWN0aW9uJywgYWN0aW9uKTtcbiAgICAgICAgICAgICAgICAgICAgcXVlc3Rpb25FbGVtLnNob3coKTtcbiAgICAgICAgICAgICAgICAgICAgKG9uU2hvd1F1ZXN0aW9uIHx8IG5vb3ApKCk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICByZWRpcmVjdCh0eXBlLCBhY3Rpb24sIGZhbHNlKTtcbiAgICAgICAgfSk7XG5cblxuICAgICAgICBxdWVzdGlvbkVsZW0uZmluZCgnYnV0dG9uJykub24oJ2NsaWNrJywgZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG5cbiAgICAgICAgICAgIGNvbnN0IGFuc3dlciA9ICQodGhpcykuZGF0YSgnbWFpbGJveC1hY2Nlc3MnKTtcbiAgICAgICAgICAgIGNvbnN0IHR5cGUgPSBxdWVzdGlvbkVsZW0uZGF0YSgndHlwZScpO1xuICAgICAgICAgICAgY29uc3QgYWN0aW9uID0gcXVlc3Rpb25FbGVtLmRhdGEoJ2FjdGlvbicpO1xuXG4gICAgICAgICAgICBzYXZlQW5zd2VyKHR5cGUsIGFuc3dlcik7XG4gICAgICAgICAgICBxdWVzdGlvbkVsZW0uaGlkZSgpO1xuICAgICAgICAgICAgKG9uSGlkZVF1ZXN0aW9uIHx8IG5vb3ApKCk7XG5cbiAgICAgICAgICAgIHJlZGlyZWN0KHR5cGUsIGFjdGlvbiwgYW5zd2VyKTtcbiAgICAgICAgfSk7XG5cbiAgICB9O1xufSk7XG4iLCJpbXBvcnQgJy4vcGFnZS1sYW5kaW5nLmxlc3MnOyAvLyBvbGQganM6IGFuZ3VsYXIgMSwgbG9naW4sIHJlZ2lzdGVyIHBvcHVwcywgZXRjXG5pbXBvcnQgJ3BhZ2VzL2xhbmRpbmcvbWFpbic7XG4iLCIndXNlIHN0cmljdCc7XG52YXIgd2VsbEtub3duU3ltYm9sID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3dlbGwta25vd24tc3ltYm9sJyk7XG5cbnZhciBNQVRDSCA9IHdlbGxLbm93blN5bWJvbCgnbWF0Y2gnKTtcblxubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbiAoTUVUSE9EX05BTUUpIHtcbiAgdmFyIHJlZ2V4cCA9IC8uLztcbiAgdHJ5IHtcbiAgICAnLy4vJ1tNRVRIT0RfTkFNRV0ocmVnZXhwKTtcbiAgfSBjYXRjaCAoZXJyb3IxKSB7XG4gICAgdHJ5IHtcbiAgICAgIHJlZ2V4cFtNQVRDSF0gPSBmYWxzZTtcbiAgICAgIHJldHVybiAnLy4vJ1tNRVRIT0RfTkFNRV0ocmVnZXhwKTtcbiAgICB9IGNhdGNoIChlcnJvcjIpIHsgLyogZW1wdHkgKi8gfVxuICB9IHJldHVybiBmYWxzZTtcbn07XG4iLCIndXNlIHN0cmljdCc7XG52YXIgaXNSZWdFeHAgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvaXMtcmVnZXhwJyk7XG5cbnZhciAkVHlwZUVycm9yID0gVHlwZUVycm9yO1xuXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uIChpdCkge1xuICBpZiAoaXNSZWdFeHAoaXQpKSB7XG4gICAgdGhyb3cgJFR5cGVFcnJvcihcIlRoZSBtZXRob2QgZG9lc24ndCBhY2NlcHQgcmVndWxhciBleHByZXNzaW9uc1wiKTtcbiAgfSByZXR1cm4gaXQ7XG59O1xuIiwiJ3VzZSBzdHJpY3QnO1xudmFyICQgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvZXhwb3J0Jyk7XG52YXIgJGluY2x1ZGVzID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2FycmF5LWluY2x1ZGVzJykuaW5jbHVkZXM7XG52YXIgZmFpbHMgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvZmFpbHMnKTtcbnZhciBhZGRUb1Vuc2NvcGFibGVzID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2FkZC10by11bnNjb3BhYmxlcycpO1xuXG4vLyBGRjk5KyBidWdcbnZhciBCUk9LRU5fT05fU1BBUlNFID0gZmFpbHMoZnVuY3Rpb24gKCkge1xuICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgZXMvbm8tYXJyYXktcHJvdG90eXBlLWluY2x1ZGVzIC0tIGRldGVjdGlvblxuICByZXR1cm4gIUFycmF5KDEpLmluY2x1ZGVzKCk7XG59KTtcblxuLy8gYEFycmF5LnByb3RvdHlwZS5pbmNsdWRlc2AgbWV0aG9kXG4vLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLWFycmF5LnByb3RvdHlwZS5pbmNsdWRlc1xuJCh7IHRhcmdldDogJ0FycmF5JywgcHJvdG86IHRydWUsIGZvcmNlZDogQlJPS0VOX09OX1NQQVJTRSB9LCB7XG4gIGluY2x1ZGVzOiBmdW5jdGlvbiBpbmNsdWRlcyhlbCAvKiAsIGZyb21JbmRleCA9IDAgKi8pIHtcbiAgICByZXR1cm4gJGluY2x1ZGVzKHRoaXMsIGVsLCBhcmd1bWVudHMubGVuZ3RoID4gMSA/IGFyZ3VtZW50c1sxXSA6IHVuZGVmaW5lZCk7XG4gIH1cbn0pO1xuXG4vLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLWFycmF5LnByb3RvdHlwZS1AQHVuc2NvcGFibGVzXG5hZGRUb1Vuc2NvcGFibGVzKCdpbmNsdWRlcycpO1xuIiwiJ3VzZSBzdHJpY3QnO1xudmFyICQgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvZXhwb3J0Jyk7XG52YXIgdW5jdXJyeVRoaXMgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvZnVuY3Rpb24tdW5jdXJyeS10aGlzJyk7XG52YXIgbm90QVJlZ0V4cCA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9ub3QtYS1yZWdleHAnKTtcbnZhciByZXF1aXJlT2JqZWN0Q29lcmNpYmxlID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3JlcXVpcmUtb2JqZWN0LWNvZXJjaWJsZScpO1xudmFyIHRvU3RyaW5nID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3RvLXN0cmluZycpO1xudmFyIGNvcnJlY3RJc1JlZ0V4cExvZ2ljID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2NvcnJlY3QtaXMtcmVnZXhwLWxvZ2ljJyk7XG5cbnZhciBzdHJpbmdJbmRleE9mID0gdW5jdXJyeVRoaXMoJycuaW5kZXhPZik7XG5cbi8vIGBTdHJpbmcucHJvdG90eXBlLmluY2x1ZGVzYCBtZXRob2Rcbi8vIGh0dHBzOi8vdGMzOS5lcy9lY21hMjYyLyNzZWMtc3RyaW5nLnByb3RvdHlwZS5pbmNsdWRlc1xuJCh7IHRhcmdldDogJ1N0cmluZycsIHByb3RvOiB0cnVlLCBmb3JjZWQ6ICFjb3JyZWN0SXNSZWdFeHBMb2dpYygnaW5jbHVkZXMnKSB9LCB7XG4gIGluY2x1ZGVzOiBmdW5jdGlvbiBpbmNsdWRlcyhzZWFyY2hTdHJpbmcgLyogLCBwb3NpdGlvbiA9IDAgKi8pIHtcbiAgICByZXR1cm4gISF+c3RyaW5nSW5kZXhPZihcbiAgICAgIHRvU3RyaW5nKHJlcXVpcmVPYmplY3RDb2VyY2libGUodGhpcykpLFxuICAgICAgdG9TdHJpbmcobm90QVJlZ0V4cChzZWFyY2hTdHJpbmcpKSxcbiAgICAgIGFyZ3VtZW50cy5sZW5ndGggPiAxID8gYXJndW1lbnRzWzFdIDogdW5kZWZpbmVkXG4gICAgKTtcbiAgfVxufSk7XG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiXSwibmFtZXMiOlsiZGVmaW5lIiwiYW5ndWxhciIsIiQiLCJkaWFsb2ciLCJfX2VzTW9kdWxlIiwiZGVmYXVsdCIsIm1vZHVsZSIsInNlcnZpY2UiLCJkaXJlY3RpdmUiLCJkaWFsb2dTZXJ2aWNlIiwib3B0aW9ucyIsInVuaXF1ZSIsIk9iamVjdCIsImtleXMiLCJ1aSIsInByb3RvdHlwZSIsInJlc3RyaWN0Iiwic2NvcGUiLCJyZWR1Y2UiLCJhY2MiLCJ2YWwiLCJiaW5kVG9TY29wZSIsInJlcGxhY2UiLCJ0cmFuc2NsdWRlIiwidGVtcGxhdGUiLCJsaW5rIiwiZWxlbWVudCIsImF0dHIiLCJjdHJsIiwib3B0cyIsInZhbHVlIiwidW5kZWZpbmVkIiwiY3JlYXRlTmFtZWQiLCJpZCIsImNsb25lIiwiaHRtbCIsInRvcCIsImxlbmd0aCIsIm9mZnNldCIsInBhcnNlRmxvYXQiLCJjc3MiLCJjbGljayIsInRvZ2dsZUNsYXNzIiwiYWRkQ2xhc3MiLCJ3aW5kb3ciLCJ0cmlnZ2VyIiwiZSIsInByZXZlbnREZWZhdWx0IiwicGFyZW50IiwiaGFzaCIsImhlYWRlckhlaWdodCIsImhhc0NsYXNzIiwiaW5uZXJIZWlnaHQiLCJhbmltYXRlIiwic2Nyb2xsVG9wIiwiaGlzdG9yeSIsInB1c2hTdGF0ZSIsImxvY2F0aW9uIiwiZm9jdXMiLCJjbG9zZXN0IiwiYmx1ciIsInJlbW92ZUNsYXNzIiwiZWFjaCIsImJvZHkiLCJ3aWR0aCIsIm9uIiwibmF2IiwibGFzdCIsImZhZGVJbiIsImZhZGVPdXQiLCJsZWZ0IiwibGlBY3RpdmUiLCJsZWZ0TWVudSIsImNvbnRlbnQiLCJsaUNsYXNzIiwibGlBY3RpdmVIYW5kbGVyIiwiZmluZCIsIm91dGVySGVpZ2h0Iiwic2V0SW50ZXJ2YWwiLCJvbGRSaWdodCIsImhlYWRlciIsInNjcm9sbExlZnQiLCJyZXNpemUiLCJzaXplV2luZG93IiwiZG9jdW1lbnQiLCJpbnB1dEl0ZW0iLCJmdWxsUGF0aCIsInN0YXJ0SW5kZXgiLCJpbmRleE9mIiwibGFzdEluZGV4T2YiLCJmaWxlbmFtZSIsInN1YnN0cmluZyIsInRleHQiLCJidXR0b24iLCJmaXJzdCIsInJlcXVpcmUiLCJjbGlja0hhbmRsZXIiLCIkYWRkTmV3UGVyc29uIiwiVHJhbnNsYXRvciIsInRyYW5zIiwiJHByZXZTZWxlY3RlZCIsImFwcGVuZCIsImVsIiwidGFyZ2V0IiwicHJvcCIsImhyZWYiLCJtYXRjaCIsInBhc3N3b3JkQ29tcGxleGl0eSIsInNob3dQYXNzd29yZE5vdGljZSIsImZyYW1lIiwiZGl2IiwicHJlcGVuZFRvIiwicG9zaXRpb24iLCJwYXJzZVBvc2l0aW9uIiwic2hvdyIsImhlaWdodCIsImNoaWxkcmVuIiwiaW5kZXgiLCJoaWRlUGFzc3dvcmROb3RpY2UiLCJoaWRlIiwidHJhY2tDb21wbGV4aXR5IiwiY2hlY2tzIiwibGVuZ3RoSW5VdGY4Qnl0ZXMiLCJzZWxmIiwiZ2V0TG9naW5DYWxsYmFjayIsImxvZ2luIiwidG9Mb3dlckNhc2UiLCJlbWFpbCIsImdldEVtYWlsQ2FsbGJhY2siLCJ0b2dnbGUiLCJlcnJvcnMiLCJrZXkiLCJtZWV0RGl2IiwicHVzaCIsInBvcyIsInJlc3VsdCIsInBhcnNlSW50IiwiaXNOYU4iLCJzdHIiLCJtIiwiZW5jb2RlVVJJQ29tcG9uZW50IiwicGFzc3dvcmRGaWVsZCIsImluaXQiLCJzZXRUaW1lb3V0IiwiZ2V0RXJyb3JzIiwiZGlhbG9nRWxlbWVudCIsImFwcGVuZFRvIiwiYXV0b09wZW4iLCJtb2RhbCIsInRpdGxlIiwiYnV0dG9ucyIsIlJvdXRpbmciLCJnZW5lcmF0ZSIsIm9wZW4iLCJjbG9zZSIsImJhY2siLCJjdXN0b21pemVyIiwiZ2FXcmFwcGVyIiwiaW5pdE9hdXRoTGlua3MiLCJpbml0UmVjYXB0Y2hhIiwid2hlblJlY2FwdGNoYUxvYWRlZCIsInJlbmRlclJlY2FwdGNoYSIsIl9yZW1lbWJlcl9tZSIsImNvbnRyb2xsZXIiLCIkc3RhdGUiLCJ0ZXN0IiwiaG9zdG5hbWUiLCJnbyIsImFkZEVycm9yIiwiZmllbGQiLCJlcnJvciIsImJlZm9yZSIsImluaXRUb29sdGlwcyIsInRvb2x0aXAiLCJvZmYiLCJwYXJlbnRzIiwiZm9ybSIsIndoZW5SZWNhcHRjaGFTb2x2ZWQiLCJyZWNhcHRjaGFfY29kZSIsImRhdGEiLCJGb3JtRGF0YSIsImFqYXgiLCJ1cmwiLCJtZXRob2QiLCJwcm9jZXNzRGF0YSIsImNvbnRlbnRUeXBlIiwic3VjY2VzcyIsIm5hbWUiLCJlcnJvclRleHQiLCJlcnJvcmVkRmllbGQiLCJyZW1vdmUiLCIkc2NvcGUiLCIkaHR0cCIsIiR0aW1lb3V0IiwiJGxvY2F0aW9uIiwiZm9jdXNPbkVycm9yIiwiZmlsdGVyIiwiYXJndW1lbnRzIiwicm93IiwibW91c2VvdmVyIiwic3RlcEluaXQiLCJpbml0Rmlyc3RTdGVwIiwic2V0U3RlcCIsIiRhcHBseSIsImluaXRTZWNvbmRTdGVwIiwidmFsaWRhdGVGb3JtIiwiZGVmZXJyZWQiLCJEZWZlcnJlZCIsIiRpbnZhbGlkIiwicmVqZWN0Iiwid2hlbiIsInBvc3QiLCJkb25lIiwicmVnaXN0ZXJGb3JtIiwiJHNldFZhbGlkaXR5IiwiZW1haWxDaGVja2VkIiwicmVzb2x2ZSIsImZhaWwiLCJwcm9taXNlIiwidXJpUGFyYW1zIiwic2VhcmNoIiwiaXNTdGVwIiwicyIsInN0ZXAiLCJzdWJtaXR0ZWQiLCJzaG93UGFzcyIsImludml0ZUVtYWlsIiwicGFzcyIsImZpcnN0bmFtZSIsImZpcnN0TmFtZSIsImxhc3RuYW1lIiwibGFzdE5hbWUiLCJjb3Vwb24iLCJjb2RlIiwiQ29kZSIsInRvZ2dsZVNob3dQYXNzIiwicmVzZXRFcnJvcnMiLCJzdWJtaXQiLCJzcGlubmVyIiwiY2FwdGNoYV9rZXkiLCJCYWNrVG8iLCJ1c2VyIiwicmVjYXB0Y2hhIiwidGhlbiIsIl9yZWYiLCJjb25zb2xlIiwibG9nIiwiZXZlbnRfY2FsbGJhY2siLCJhbmNob3IiLCJjcmVhdGVFbGVtZW50IiwicHJvdG9jb2wiLCJiZXRhIiwidGFyZ2V0UGFnZSIsImFsZXJ0IiwiYWx3YXlzIiwiJHNjZSIsIlVzZXIiLCJwcmV2U3RlcCIsImluZm9ybWF0aW9uTWVzc2FnZSIsImFuc3dlciIsInN1Ym1pdEJ1dHRvbiIsIm90Y1JlY292ZXJ5IiwicXVlc3Rpb24iLCJvdGMiLCJwb3B1cFRpdGxlIiwidHJ1c3RBc0h0bWwiLCJtYl9xdWVzdGlvbiIsIm90Y0lucHV0TGFiZWwiLCJvdGNJbnB1dEhpbnQiLCJfb3RjIiwiX290Y19yZWNvdmVyeSIsInNob3dGb3Jnb3RMaW5rIiwiY29va2llIiwiaGFzT3duUHJvcGVydHkiLCJjYWxsIiwiRm9ybVRva2VuIiwiaGVhZGVycyIsInJlcyIsIkRuNjk4dENRIiwiZXZhbCIsImV4cHIiLCJfY3NyZl90b2tlbiIsImNzcmZfdG9rZW4iLCJyZWNhcHRjaFJlcXVpcmVkIiwidHJ5TG9naW4iLCJjb3B5IiwiTWF0aCIsInJhbmRvbSIsInBhcmFtIiwiX3R5cGVvZiIsImludml0ZUNvZGUiLCJzZXNzaW9uU3RvcmFnZSIsImJhY2tVcmwiLCJvdGNSZXF1aXJlZCIsInNlbGVjdGVkIiwicXVlc3Rpb25zIiwic2VsZWN0UXVlc3Rpb25UZXh0IiwidW5zaGlmdCIsImlkeCIsIm1lc3NhZ2UiLCJjc3JmIiwicXVlc3Rpb25DaGFuZ2VkIiwib3RjU2hvd1JlY292ZXJ5IiwiYmFkQ3JlZGVudGlhbHMiLCJyZWNhcHRjaGFSZXF1aXJlZCIsImNsZWFyTWVzc2FnZXMiLCJtYXNrSW5wdXQiLCJkb1N0ZXAiLCJ1c2VybmFtZSIsInJlc3RvcmVGb3JtIiwiX3JlZjIiLCJzZWxlY3QiLCJjaGFuZ2UiLCJjcmVhdGVFcnJvckRpYWxvZyIsImZhc3RDcmVhdGUiLCJhdXRvY29tcGxldGVTb3VyY2UiLCJyZXF1ZXN0IiwicmVzcG9uc2UiLCJtZXJjaGFudElucHV0IiwicXVlcnkiLCJ0ZXJtIiwiX3JlZjMiLCJpc0VtcHR5T2JqZWN0IiwibGFiZWwiLCJjYXRjaCIsIiRvbiIsImV2IiwidG9TdGF0ZSIsInRvUGFyYW1zIiwiZnJvbVN0YXRlIiwiYXV0b2NvbXBsZXRlIiwibWluTGVuZ3RoIiwiZGVsYXkiLCJzb3VyY2UiLCJldmVudCIsIml0ZW0iLCJuYW1lVG9VcmwiLCJjcmVhdGUiLCJfcmVuZGVySXRlbSIsInVsIiwiY2F0ZWdvcnkiLCJjb25jYXQiLCJpbm5lcldpZHRoIiwic2V0VmFsdWUiLCJtb2RlbCIsIiR2aWV3VmFsdWUiLCIkc2V0Vmlld1ZhbHVlIiwiJHJlbmRlciIsImVsZW0iLCJhdHRycyIsImF1dG9maWxsIiwib3JpZ2luYWxQYXNzIiwic2FtZVBhc3N3b3JkIiwiYWRkIiwib3JpZ2luYWxQYXNzVmFsdWUiLCJzYW1lUGFzc1ZhbHVlIiwidmFsaWRpdHkiLCJjb25maWciLCIkc3RhdGVQcm92aWRlciIsIiR1cmxSb3V0ZXJQcm92aWRlciIsIiRsb2NhdGlvblByb3ZpZGVyIiwicmVwbGFjZVdpdGgiLCJvdGhlcndpc2UiLCIkaW5qZWN0b3IiLCJwYXRoIiwic3Vic3RyIiwibG9jYWxlIiwic3RhdGUiLCJ0ZW1wbGF0ZVVybCIsImh0bWw1TW9kZSIsImVuYWJsZWQiLCJyZXdyaXRlTGlua3MiLCJydW4iLCJyZWFkeSIsImFwcCIsImdldEVsZW1lbnRCeUlkIiwiYm9vdHN0cmFwIiwiZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSIsInV0aWxzIiwib25TaG93UXVlc3Rpb24iLCJvbkhpZGVRdWVzdGlvbiIsImdldEtleVR5cGVOYW1lIiwidHlwZSIsImdldEFuc3dlciIsImxvY2FsU3RvcmFnZSIsImdldEl0ZW0iLCJnZXRDb29raWUiLCJzYXZlQW5zd2VyIiwic2V0SXRlbSIsImdldFF1ZXJ5UGFyYW1zIiwidmFycyIsInNwbGl0IiwiaSIsInBhaXIiLCJkZWNvZGVVUklDb21wb25lbnQiLCJyZWRpcmVjdCIsImFjdGlvbiIsIm1haWxib3hBY2Nlc3MiLCJhc3NpZ24iLCJpcyIsIm5vb3AiLCJxdWVzdGlvbkVsZW0iXSwic291cmNlUm9vdCI6IiJ9