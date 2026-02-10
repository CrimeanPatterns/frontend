"use strict";
(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["web_assets_common_js_intro_min_js"],{

/***/ "./web/assets/common/js/intro.min.js":
/*!*******************************************!*\
  !*** ./web/assets/common/js/intro.min.js ***!
  \*******************************************/
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var __webpack_provided_window_dot_jQuery = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;

/**
 * Intro.js v2.9.0-alpha.1
 * https://github.com/usablica/intro.js
 *
 * Copyright (C) 2017 Afshin Mehrabani (@afshinmeh)
 */
__webpack_require__(/*! core-js/modules/es.array.sort.js */ "./node_modules/core-js/modules/es.array.sort.js");
__webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
__webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
__webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
__webpack_require__(/*! core-js/modules/es.array.splice.js */ "./node_modules/core-js/modules/es.array.splice.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
__webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
__webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
__webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
__webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
__webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
(function (f) {
  if (( false ? 0 : _typeof(exports)) === "object" && "object" !== "undefined") {
    module.exports = f();
    // deprecated function
    // @since 2.8.0
    module.exports.introJs = function () {
      console.warn('Deprecated: please use require("intro.js") directly, instead of the introJs method of the function');
      // introJs()
      return f().apply(this, arguments);
    };
  } else if (true) {
    !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_FACTORY__ = (f),
		__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
		(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
  } else { var g; }
})(function () {
  //Default config/variables
  var VERSION = '2.9.0-alpha.1';

  /**
   * IntroJs main class
   *
   * @class IntroJs
   */
  function IntroJs(obj) {
    this._targetElement = obj;
    this._introItems = [];
    this._options = {
      /* Next button label in tooltip box */
      nextLabel: 'Next &rarr;',
      /* Previous button label in tooltip box */
      prevLabel: '&larr; Back',
      /* Skip button label in tooltip box */
      skipLabel: 'Skip',
      /* Done button label in tooltip box */
      doneLabel: 'Done',
      /* Hide previous button in the first step? Otherwise, it will be disabled button. */
      hidePrev: false,
      /* Hide next button in the last step? Otherwise, it will be disabled button. */
      hideNext: false,
      /* Default tooltip box position */
      tooltipPosition: 'bottom',
      /* Next CSS class for tooltip boxes */
      tooltipClass: '',
      /* CSS class that is added to the helperLayer */
      highlightClass: '',
      /* Close introduction when pressing Escape button? */
      exitOnEsc: true,
      /* Close introduction when clicking on overlay layer? */
      exitOnOverlayClick: true,
      /* Show step numbers in introduction? */
      showStepNumbers: true,
      /* Let user use keyboard to navigate the tour? */
      keyboardNavigation: true,
      /* Show tour control buttons? */
      showButtons: true,
      /* Show tour bullets? */
      showBullets: true,
      /* Show tour progress? */
      showProgress: false,
      /* Scroll to highlighted element? */
      scrollToElement: true,
      /*
       * Should we scroll the tooltip or target element?
       *
       * Options are: 'element' or 'tooltip'
       */
      scrollTo: 'element',
      /* Padding to add after scrolling when element is not in the viewport (in pixels) */
      scrollPadding: 30,
      /* Set the overlay opacity */
      overlayOpacity: 0.8,
      /* Precedence of positions, when auto is enabled */
      positionPrecedence: ["bottom", "top", "right", "left"],
      /* Disable an interaction with element? */
      disableInteraction: false,
      /* Set how much padding to be used around helper element */
      helperElementPadding: 10,
      /* Default hint position */
      hintPosition: 'top-middle',
      /* Hint button label */
      hintButtonLabel: 'Got it',
      /* Adding animation to hints? */
      hintAnimation: true,
      /* additional classes to put on the buttons */
      buttonClass: ""
    };
  }

  /**
   * Initiate a new introduction/guide from an element in the page
   *
   * @api private
   * @method _introForElement
   * @param {Object} targetElm
   * @param {String} group
   * @returns {Boolean} Success or not?
   */
  function _introForElement(targetElm, group) {
    var allIntroSteps = targetElm.querySelectorAll("*[data-intro]"),
      introItems = [];
    if (-1 !== targetElm.className.indexOf('main-body')) {
      allIntroSteps = document.querySelectorAll('*[data-intro][data-show]');
      this._targetElement = targetElm = document.body;
    }
    if (0 === allIntroSteps.length && null !== targetElm.getAttribute('data-intro')) {
      allIntroSteps = document.querySelectorAll('*[data-intro="' + targetElm.getAttribute('data-intro') + '"]');
      this._targetElement = targetElm = document.body;
    }
    if (this._options.steps) {
      //use steps passed programmatically
      _forEach(this._options.steps, function (step) {
        var currentItem = _cloneObject(step);

        //set the step
        currentItem.step = introItems.length + 1;

        //use querySelector function only when developer used CSS selector
        if (typeof currentItem.element === 'string') {
          //grab the element with given selector from the page
          currentItem.element = document.querySelector(currentItem.element);
        }

        //intro without element
        if (typeof currentItem.element === 'undefined' || currentItem.element === null) {
          var floatingElementQuery = document.querySelector(".tipjsFloatingElement");
          if (floatingElementQuery === null) {
            floatingElementQuery = document.createElement('div');
            floatingElementQuery.className = 'introjsFloatingElement';
            document.body.appendChild(floatingElementQuery);
          }
          currentItem.element = floatingElementQuery;
          currentItem.position = 'floating';
        }
        currentItem.scrollTo = currentItem.scrollTo || this._options.scrollTo;
        if (typeof currentItem.disableInteraction === 'undefined') {
          currentItem.disableInteraction = this._options.disableInteraction;
        }
        if (currentItem.element !== null) {
          introItems.push(currentItem);
        }
      }.bind(this));
    } else {
      //use steps from data-* annotations
      var elmsLength = allIntroSteps.length;
      var disableInteraction;

      //if there's no element to intro
      if (elmsLength < 1) {
        return false;
      }
      _forEach(allIntroSteps, function (currentElement) {
        // PR #80
        // start intro for groups of elements
        if (group && currentElement.getAttribute("data-intro-group") !== group) {
          return;
        }

        // skip hidden elements
        if (currentElement.style.display === 'none') {
          return;
        }
        var step = parseInt(currentElement.getAttribute('data-step'), 10);
        if (typeof currentElement.getAttribute('data-disable-interaction') !== 'undefined') {
          disableInteraction = !!currentElement.getAttribute('data-disable-interaction');
        } else {
          disableInteraction = this._options.disableInteraction;
        }
        if (step > 0) {
          introItems[step - 1] = {
            element: currentElement,
            intro: decodeURIComponent(currentElement.getAttribute('data-intro')),
            step: parseInt(currentElement.getAttribute('data-step'), 10),
            tooltipClass: currentElement.getAttribute('data-tooltipclass'),
            highlightClass: currentElement.getAttribute('data-highlightclass'),
            position: currentElement.getAttribute('data-position') || this._options.tooltipPosition,
            scrollTo: currentElement.getAttribute('data-scrollto') || this._options.scrollTo,
            disableInteraction: disableInteraction
          };
        }
      }.bind(this));

      //next add intro items without data-step
      //todo: we need a cleanup here, two loops are redundant
      var nextStep = 0;
      _forEach(allIntroSteps, function (currentElement) {
        // PR #80
        // start intro for groups of elements
        if (group && currentElement.getAttribute("data-intro-group") !== group) {
          return;
        }
        if (currentElement.getAttribute('data-step') === null) {
          while (true) {
            if (typeof introItems[nextStep] === 'undefined') {
              break;
            } else {
              nextStep++;
            }
          }
          if (typeof currentElement.getAttribute('data-disable-interaction') !== 'undefined') {
            disableInteraction = !!currentElement.getAttribute('data-disable-interaction');
          } else {
            disableInteraction = this._options.disableInteraction;
          }
          introItems[nextStep] = {
            element: currentElement,
            intro: decodeURIComponent(currentElement.getAttribute('data-intro')),
            step: nextStep + 1,
            tooltipClass: currentElement.getAttribute('data-tooltipclass'),
            highlightClass: currentElement.getAttribute('data-highlightclass'),
            position: currentElement.getAttribute('data-position') || this._options.tooltipPosition,
            scrollTo: currentElement.getAttribute('data-scrollto') || this._options.scrollTo,
            disableInteraction: disableInteraction
          };
        }
      }.bind(this));
    }

    //removing undefined/null elements
    var tempIntroItems = [];
    for (var z = 0; z < introItems.length; z++) {
      if (introItems[z]) {
        // copy non-falsy values to the end of the array
        tempIntroItems.push(introItems[z]);
      }
    }
    introItems = tempIntroItems;

    //Ok, sort all items with given steps
    introItems.sort(function (a, b) {
      return a.step - b.step;
    });

    //set it to the introJs object
    this._introItems = introItems;

    //add overlay layer to the page
    if (_addOverlayLayer.call(this, targetElm)) {
      //then, start the show
      _nextStep.call(this);
      if (this._options.keyboardNavigation) {
        DOMEvent.on(window, 'keydown', _onKeyDown, this, true);
      }
      //for window resize
      DOMEvent.on(window, 'resize', _onResize, this, true);
    }
    if (this._introStartCallback !== undefined) {
      this._introStartCallback.call(this);
    }
    return false;
  }
  function _onResize() {
    this.refresh.call(this);
  }

  /**
   * on keyCode:
   * https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/keyCode
   * This feature has been removed from the Web standards.
   * Though some browsers may still support it, it is in
   * the process of being dropped.
   * Instead, you should use KeyboardEvent.code,
   * if it's implemented.
   *
   * jQuery's approach is to test for
   *   (1) e.which, then
   *   (2) e.charCode, then
   *   (3) e.keyCode
   * https://github.com/jquery/jquery/blob/a6b0705294d336ae2f63f7276de0da1195495363/src/event.js#L638
   *
   * @param type var
   * @return type
   */
  function _onKeyDown(e) {
    var code = e.code === null ? e.which : e.code;

    // if code/e.which is null
    if (code === null) {
      code = e.charCode === null ? e.keyCode : e.charCode;
    }
    if ((code === 'Escape' || code === 27) && this._options.exitOnEsc === true) {
      //escape key pressed, exit the intro
      //check if exit callback is defined
      _exitIntro.call(this, this._targetElement);
    } else if (code === 'ArrowLeft' || code === 37) {
      //left arrow
      _previousStep.call(this);
    } else if (code === 'ArrowRight' || code === 39) {
      //right arrow
      _nextStep.call(this);
    } else if (code === 'Enter' || code === 13) {
      //srcElement === ie
      var target = e.target || e.srcElement;
      if (target && target.className.match('introjs-prevbutton')) {
        //user hit enter while focusing on previous button
        _previousStep.call(this);
      } else if (target && target.className.match('introjs-skipbutton')) {
        //user hit enter while focusing on skip button
        if (this._introItems.length - 1 === this._currentStep && typeof this._introCompleteCallback === 'function') {
          this._introCompleteCallback.call(this);
        }
        _exitIntro.call(this, this._targetElement);
      } else if (target && target.getAttribute('data-stepnumber')) {
        // user hit enter while focusing on step bullet
        target.click();
      } else {
        //default behavior for responding to enter
        _nextStep.call(this);
      }

      //prevent default behaviour on hitting Enter, to prevent steps being skipped in some browsers
      if (e.preventDefault) {
        e.preventDefault();
      } else {
        e.returnValue = false;
      }
    }
  }

  /*
    * makes a copy of the object
    * @api private
    * @method _cloneObject
   */
  function _cloneObject(object) {
    if (object === null || _typeof(object) !== 'object' || typeof object.nodeType !== 'undefined') {
      return object;
    }
    var temp = {};
    for (var key in object) {
      if (typeof __webpack_provided_window_dot_jQuery !== 'undefined' && object[key] instanceof __webpack_provided_window_dot_jQuery) {
        temp[key] = object[key];
      } else {
        temp[key] = _cloneObject(object[key]);
      }
    }
    return temp;
  }
  /**
   * Go to specific step of introduction
   *
   * @api private
   * @method _goToStep
   */
  function _goToStep(step) {
    //because steps starts with zero
    this._currentStep = step - 2;
    if (typeof this._introItems !== 'undefined') {
      _nextStep.call(this);
    }
  }

  /**
   * Go to the specific step of introduction with the explicit [data-step] number
   *
   * @api private
   * @method _goToStepNumber
   */
  function _goToStepNumber(step) {
    this._currentStepNumber = step;
    if (typeof this._introItems !== 'undefined') {
      _nextStep.call(this);
    }
  }

  /**
   * Go to next step on intro
   *
   * @api private
   * @method _nextStep
   */
  function _nextStep() {
    this._direction = 'forward';
    if (typeof this._currentStepNumber !== 'undefined') {
      _forEach(this._introItems, function (item, i) {
        if (item.step === this._currentStepNumber) {
          this._currentStep = i - 1;
          this._currentStepNumber = undefined;
        }
      }.bind(this));
    }
    if (typeof this._currentStep === 'undefined') {
      this._currentStep = 0;
    } else {
      ++this._currentStep;
    }
    var nextStep = this._introItems[this._currentStep];
    var continueStep = true;
    if (typeof this._introBeforeChangeCallback !== 'undefined') {
      continueStep = this._introBeforeChangeCallback.call(this, nextStep.element);
    }

    // if `onbeforechange` returned `false`, stop displaying the element
    if (continueStep === false) {
      --this._currentStep;
      return false;
    }
    if (this._introItems.length <= this._currentStep) {
      //end of the intro
      //check if any callback is defined
      if (typeof this._introCompleteCallback === 'function') {
        this._introCompleteCallback.call(this);
      }
      _exitIntro.call(this, this._targetElement);
      return;
    }
    _showElement.call(this, nextStep);
  }

  /**
   * Go to previous step on intro
   *
   * @api private
   * @method _previousStep
   */
  function _previousStep() {
    this._direction = 'backward';
    if (this._currentStep === 0) {
      return false;
    }
    --this._currentStep;
    var nextStep = this._introItems[this._currentStep];
    var continueStep = true;
    if (typeof this._introBeforeChangeCallback !== 'undefined') {
      continueStep = this._introBeforeChangeCallback.call(this, nextStep.element);
    }

    // if `onbeforechange` returned `false`, stop displaying the element
    if (continueStep === false) {
      ++this._currentStep;
      return false;
    }
    _showElement.call(this, nextStep);
  }

  /**
   * Update placement of the intro objects on the screen
   * @api private
   */
  function _refresh() {
    // re-align intros
    _setHelperLayerPosition.call(this, document.querySelector('.introjs-helperLayer'));
    _setHelperLayerPosition.call(this, document.querySelector('.introjs-tooltipReferenceLayer'));
    _setHelperLayerPosition.call(this, document.querySelector('.introjs-disableInteraction'));

    // re-align tooltip
    if (this._currentStep !== undefined && this._currentStep !== null) {
      var oldHelperNumberLayer = document.querySelector('.introjs-helperNumberLayer'),
        oldArrowLayer = document.querySelector('.introjs-arrow'),
        oldtooltipContainer = document.querySelector('.introjs-tooltip');
      _placeTooltip.call(this, this._introItems[this._currentStep].element, oldtooltipContainer, oldArrowLayer, oldHelperNumberLayer);
    }

    //re-align hints
    _reAlignHints.call(this);
    return this;
  }

  /**
   * Exit from intro
   *
   * @api private
   * @method _exitIntro
   * @param {Object} targetElement
   * @param {Boolean} force - Setting to `true` will skip the result of beforeExit callback
   */
  function _exitIntro(targetElement, force) {
    var continueExit = true;

    //check if any callback is defined
    if (this._introExitCallback !== undefined) {
      this._introExitCallback.call(this);
    }

    // calling onbeforeexit callback
    //
    // If this callback return `false`, it would halt the process
    if (this._introBeforeExitCallback !== undefined) {
      continueExit = this._introBeforeExitCallback.call(this);
    }

    // skip this check if `force` parameter is `true`
    // otherwise, if `onbeforeexit` returned `false`, don't exit the intro
    if (!force && continueExit === false) return;

    //remove overlay layers from the page
    var overlayLayers = targetElement.querySelectorAll('.introjs-overlay');
    if (overlayLayers && overlayLayers.length) {
      _forEach(overlayLayers, function (overlayLayer) {
        overlayLayer.style.opacity = 0;
        window.setTimeout(function () {
          if (this.parentNode) {
            this.parentNode.removeChild(this);
          }
        }.bind(overlayLayer), 500);
      }.bind(this));
    }

    //remove all helper layers
    var helperLayer = targetElement.querySelector('.introjs-helperLayer');
    if (helperLayer) {
      helperLayer.parentNode.removeChild(helperLayer);
    }
    var referenceLayer = targetElement.querySelector('.introjs-tooltipReferenceLayer');
    if (referenceLayer) {
      referenceLayer.parentNode.removeChild(referenceLayer);
    }

    //remove disableInteractionLayer
    var disableInteractionLayer = targetElement.querySelector('.introjs-disableInteraction');
    if (disableInteractionLayer) {
      disableInteractionLayer.parentNode.removeChild(disableInteractionLayer);
    }

    //remove intro floating element
    var floatingElement = document.querySelector('.introjsFloatingElement');
    if (floatingElement) {
      floatingElement.parentNode.removeChild(floatingElement);
    }
    _removeShowElement();

    //remove `introjs-fixParent` class from the elements
    var fixParents = document.querySelectorAll('.introjs-fixParent');
    _forEach(fixParents, function (parent) {
      _removeClass(parent, /introjs-fixParent/g);
    });

    //clean listeners
    DOMEvent.off(window, 'keydown', _onKeyDown, this, true);
    DOMEvent.off(window, 'resize', _onResize, this, true);

    //set the step to zero
    this._currentStep = undefined;
  }

  /**
   * Render tooltip box in the page
   *
   * @api private
   * @method _placeTooltip
   * @param {HTMLElement} targetElement
   * @param {HTMLElement} tooltipLayer
   * @param {HTMLElement} arrowLayer
   * @param {HTMLElement} helperNumberLayer
   * @param {Boolean} hintMode
   */
  function _placeTooltip(targetElement, tooltipLayer, arrowLayer, helperNumberLayer, hintMode) {
    var tooltipCssClass = '',
      currentStepObj,
      tooltipOffset,
      targetOffset,
      windowSize,
      currentTooltipPosition;
    hintMode = hintMode || false;

    //reset the old style
    tooltipLayer.style.top = null;
    tooltipLayer.style.right = null;
    tooltipLayer.style.bottom = null;
    tooltipLayer.style.left = null;
    tooltipLayer.style.marginLeft = null;
    tooltipLayer.style.marginTop = null;
    arrowLayer.style.display = 'inherit';
    if (typeof helperNumberLayer !== 'undefined' && helperNumberLayer !== null) {
      helperNumberLayer.style.top = null;
      helperNumberLayer.style.left = null;
    }

    //prevent error when `this._currentStep` is undefined
    if (!this._introItems[this._currentStep]) return;

    //if we have a custom css class for each step
    currentStepObj = this._introItems[this._currentStep];
    if (typeof currentStepObj.tooltipClass === 'string') {
      tooltipCssClass = currentStepObj.tooltipClass;
    } else {
      tooltipCssClass = this._options.tooltipClass;
    }
    tooltipLayer.className = ('introjs-tooltip ' + tooltipCssClass).replace(/^\s+|\s+$/g, '');
    tooltipLayer.setAttribute('role', 'dialog');
    currentTooltipPosition = this._introItems[this._currentStep].position;

    // Floating is always valid, no point in calculating
    if (currentTooltipPosition !== "floating") {
      currentTooltipPosition = _determineAutoPosition.call(this, targetElement, tooltipLayer, currentTooltipPosition);
    }
    var tooltipLayerStyleLeft;
    targetOffset = _getOffset(targetElement);
    tooltipOffset = _getOffset(tooltipLayer);
    windowSize = _getWinSize();
    _addClass(tooltipLayer, 'introjs-' + currentTooltipPosition);
    switch (currentTooltipPosition) {
      case 'top-right-aligned':
        arrowLayer.className = 'introjs-arrow bottom-right';
        var tooltipLayerStyleRight = 0;
        _checkLeft(targetOffset, tooltipLayerStyleRight, tooltipOffset, tooltipLayer);
        tooltipLayer.style.bottom = targetOffset.height + 20 + 'px';
        break;
      case 'top-middle-aligned':
        arrowLayer.className = 'introjs-arrow bottom-middle';
        var tooltipLayerStyleLeftRight = targetOffset.width / 2 - tooltipOffset.width / 2;

        // a fix for middle aligned hints
        if (hintMode) {
          tooltipLayerStyleLeftRight += 5;
        }
        if (_checkLeft(targetOffset, tooltipLayerStyleLeftRight, tooltipOffset, tooltipLayer)) {
          tooltipLayer.style.right = null;
          _checkRight(targetOffset, tooltipLayerStyleLeftRight, tooltipOffset, windowSize, tooltipLayer);
        }
        tooltipLayer.style.bottom = targetOffset.height + 20 + 'px';
        break;
      case 'top-left-aligned':
      // top-left-aligned is the same as the default top
      case 'top':
        arrowLayer.className = 'introjs-arrow bottom';
        tooltipLayerStyleLeft = hintMode ? 0 : 15;
        _checkRight(targetOffset, tooltipLayerStyleLeft, tooltipOffset, windowSize, tooltipLayer);
        tooltipLayer.style.bottom = targetOffset.height + 20 + 'px';
        break;
      case 'right':
        tooltipLayer.style.left = targetOffset.width + 20 + 'px';
        if (targetOffset.top + tooltipOffset.height > windowSize.height && -1 === targetElement.className.indexOf('introjs-relativePosition')) {
          // In this case, right would have fallen below the bottom of the screen.
          // Modify so that the bottom of the tooltip connects with the target
          arrowLayer.className = "introjs-arrow left-bottom";
          tooltipLayer.style.top = "-" + (tooltipOffset.height - targetOffset.height - 20) + "px";
        } else {
          arrowLayer.className = 'introjs-arrow left';
        }
        break;
      case 'left':
        if (!hintMode && this._options.showStepNumbers === true) {
          tooltipLayer.style.top = '15px';
        }
        if (targetOffset.top + tooltipOffset.height > windowSize.height) {
          // In this case, left would have fallen below the bottom of the screen.
          // Modify so that the bottom of the tooltip connects with the target
          tooltipLayer.style.top = "-" + (tooltipOffset.height - targetOffset.height - 30) + "px";
          arrowLayer.className = 'introjs-arrow right-bottom';
        } else {
          arrowLayer.className = 'introjs-arrow right';
        }
        tooltipLayer.style.right = targetOffset.width + 20 + 'px';
        break;
      case 'floating':
        arrowLayer.style.display = 'none';

        //we have to adjust the top and left of layer manually for intro items without element
        tooltipLayer.style.left = '50%';
        tooltipLayer.style.top = '50%';
        tooltipLayer.style.marginLeft = '-' + tooltipOffset.width / 2 + 'px';
        tooltipLayer.style.marginTop = '-' + tooltipOffset.height / 2 + 'px';
        if (typeof helperNumberLayer !== 'undefined' && helperNumberLayer !== null) {
          helperNumberLayer.style.left = '-' + (tooltipOffset.width / 2 + 18) + 'px';
          helperNumberLayer.style.top = '-' + (tooltipOffset.height / 2 + 18) + 'px';
        }
        break;
      case 'bottom-right-aligned':
        arrowLayer.className = 'introjs-arrow top-right';
        tooltipLayerStyleRight = 0;
        _checkLeft(targetOffset, tooltipLayerStyleRight, tooltipOffset, tooltipLayer);
        tooltipLayer.style.top = targetOffset.height + 20 + 'px';
        break;
      case 'bottom-middle-aligned':
        arrowLayer.className = 'introjs-arrow top-middle';
        tooltipLayerStyleLeftRight = targetOffset.width / 2 - tooltipOffset.width / 2;

        // a fix for middle aligned hints
        if (hintMode) {
          tooltipLayerStyleLeftRight += 5;
        }
        if (_checkLeft(targetOffset, tooltipLayerStyleLeftRight, tooltipOffset, tooltipLayer)) {
          tooltipLayer.style.right = null;
          _checkRight(targetOffset, tooltipLayerStyleLeftRight, tooltipOffset, windowSize, tooltipLayer);
        }
        tooltipLayer.style.top = targetOffset.height + 20 + 'px';
        break;

      // case 'bottom-left-aligned':
      // Bottom-left-aligned is the same as the default bottom
      // case 'bottom':
      // Bottom going to follow the default behavior
      default:
        arrowLayer.className = 'introjs-arrow top';
        tooltipLayerStyleLeft = 0;
        _checkRight(targetOffset, tooltipLayerStyleLeft, tooltipOffset, windowSize, tooltipLayer);
        tooltipLayer.style.top = targetOffset.height + 20 + 'px';
    }
  }

  /**
   * Set tooltip left so it doesn't go off the right side of the window
   *
   * @return boolean true, if tooltipLayerStyleLeft is ok.  false, otherwise.
   */
  function _checkRight(targetOffset, tooltipLayerStyleLeft, tooltipOffset, windowSize, tooltipLayer) {
    if (targetOffset.left + tooltipLayerStyleLeft + tooltipOffset.width > windowSize.width) {
      // off the right side of the window
      tooltipLayer.style.left = windowSize.width - tooltipOffset.width - targetOffset.left + 'px';
      return false;
    }
    tooltipLayer.style.left = tooltipLayerStyleLeft + 'px';
    return true;
  }

  /**
   * Set tooltip right so it doesn't go off the left side of the window
   *
   * @return boolean true, if tooltipLayerStyleRight is ok.  false, otherwise.
   */
  function _checkLeft(targetOffset, tooltipLayerStyleRight, tooltipOffset, tooltipLayer) {
    if (targetOffset.left + targetOffset.width - tooltipLayerStyleRight - tooltipOffset.width < 0) {
      // off the left side of the window
      tooltipLayer.style.left = -targetOffset.left + 'px';
      return false;
    }
    tooltipLayer.style.right = tooltipLayerStyleRight + 'px';
    return true;
  }

  /**
   * Determines the position of the tooltip based on the position precedence and availability
   * of screen space.
   *
   * @param {Object}    targetElement
   * @param {Object}    tooltipLayer
   * @param {String}    desiredTooltipPosition
   * @return {String}   calculatedPosition
   */
  function _determineAutoPosition(targetElement, tooltipLayer, desiredTooltipPosition) {
    // Take a clone of position precedence. These will be the available
    var possiblePositions = this._options.positionPrecedence.slice();
    var windowSize = _getWinSize();
    var tooltipHeight = _getOffset(tooltipLayer).height + 10;
    var tooltipWidth = _getOffset(tooltipLayer).width + 20;
    var targetElementRect = targetElement.getBoundingClientRect();

    // If we check all the possible areas, and there are no valid places for the tooltip, the element
    // must take up most of the screen real estate. Show the tooltip floating in the middle of the screen.
    var calculatedPosition = "floating";

    /*
    * auto determine position
    */

    // Check for space below
    if (targetElementRect.bottom + tooltipHeight + tooltipHeight > windowSize.height) {
      _removeEntry(possiblePositions, "bottom");
    }

    // Check for space above
    if (targetElementRect.top - tooltipHeight < 0) {
      _removeEntry(possiblePositions, "top");
    }

    // Check for space to the right
    if (targetElementRect.right + tooltipWidth > windowSize.width) {
      _removeEntry(possiblePositions, "right");
    }

    // Check for space to the left
    if (targetElementRect.left - tooltipWidth < 0) {
      _removeEntry(possiblePositions, "left");
    }

    // @var {String}  ex: 'right-aligned'
    var desiredAlignment = function (pos) {
      var hyphenIndex = pos.indexOf('-');
      if (hyphenIndex !== -1) {
        // has alignment
        return pos.substr(hyphenIndex);
      }
      return '';
    }(desiredTooltipPosition || '');

    // strip alignment from position
    if (desiredTooltipPosition) {
      // ex: "bottom-right-aligned"
      // should return 'bottom'
      desiredTooltipPosition = desiredTooltipPosition.split('-')[0];
    }
    if (possiblePositions.length) {
      if (desiredTooltipPosition !== "auto" && possiblePositions.indexOf(desiredTooltipPosition) > -1) {
        // If the requested position is in the list, choose that
        calculatedPosition = desiredTooltipPosition;
      } else {
        // Pick the first valid position, in order
        calculatedPosition = possiblePositions[0];
      }
    }

    // only top and bottom positions have optional alignments
    if (['top', 'bottom'].indexOf(calculatedPosition) !== -1) {
      calculatedPosition += _determineAutoAlignment(targetElementRect.left, tooltipWidth, windowSize, desiredAlignment);
    }
    return calculatedPosition;
  }

  /**
   * auto-determine alignment
   * @param {Integer}  offsetLeft
   * @param {Integer}  tooltipWidth
   * @param {Object}   windowSize
   * @param {String}   desiredAlignment
   * @return {String}  calculatedAlignment
   */
  function _determineAutoAlignment(offsetLeft, tooltipWidth, windowSize, desiredAlignment) {
    var halfTooltipWidth = tooltipWidth / 2,
      winWidth = Math.min(windowSize.width, window.screen.width),
      possibleAlignments = ['-left-aligned', '-middle-aligned', '-right-aligned'],
      calculatedAlignment = '';

    // valid left must be at least a tooltipWidth
    // away from right side
    if (winWidth - offsetLeft < tooltipWidth) {
      _removeEntry(possibleAlignments, '-left-aligned');
    }

    // valid middle must be at least half
    // width away from both sides
    if (offsetLeft < halfTooltipWidth || winWidth - offsetLeft < halfTooltipWidth) {
      _removeEntry(possibleAlignments, '-middle-aligned');
    }

    // valid right must be at least a tooltipWidth
    // width away from left side
    if (offsetLeft < tooltipWidth) {
      _removeEntry(possibleAlignments, '-right-aligned');
    }
    if (possibleAlignments.length) {
      if (possibleAlignments.indexOf(desiredAlignment) !== -1) {
        // the desired alignment is valid
        calculatedAlignment = desiredAlignment;
      } else {
        // pick the first valid position, in order
        calculatedAlignment = possibleAlignments[0];
      }
    } else {
      // if screen width is too small
      // for ANY alignment, middle is
      // probably the best for visibility
      calculatedAlignment = '-middle-aligned';
    }
    return calculatedAlignment;
  }

  /**
   * Remove an entry from a string array if it's there, does nothing if it isn't there.
   *
   * @param {Array} stringArray
   * @param {String} stringToRemove
   */
  function _removeEntry(stringArray, stringToRemove) {
    if (stringArray.indexOf(stringToRemove) > -1) {
      stringArray.splice(stringArray.indexOf(stringToRemove), 1);
    }
  }

  /**
   * Update the position of the helper layer on the screen
   *
   * @api private
   * @method _setHelperLayerPosition
   * @param {Object} helperLayer
   */
  function _setHelperLayerPosition(helperLayer) {
    if (helperLayer) {
      //prevent error when `this._currentStep` in undefined
      if (!this._introItems[this._currentStep]) return;
      var currentElement = this._introItems[this._currentStep],
        elementPosition = _getOffset(currentElement.element),
        widthHeightPadding = this._options.helperElementPadding;

      // If the target element is fixed, the tooltip should be fixed as well.
      // Otherwise, remove a fixed class that may be left over from the previous
      // step.
      if (_isFixed(currentElement.element)) {
        _addClass(helperLayer, 'introjs-fixedTooltip');
        var __elementPosition = jQuery(currentElement.element).position();
        elementPosition.top = __elementPosition.top;
      } else {
        _removeClass(helperLayer, 'introjs-fixedTooltip');
      }
      if (currentElement.position === 'floating') {
        widthHeightPadding = 0;
      }

      //set new position to helper layer
      helperLayer.style.cssText = 'width: ' + (elementPosition.width + widthHeightPadding) + 'px; ' + 'height:' + (elementPosition.height + widthHeightPadding) + 'px; ' + 'top:' + (elementPosition.top - widthHeightPadding / 2) + 'px;' + 'left: ' + (elementPosition.left - widthHeightPadding / 2) + 'px;';
    }
  }

  /**
   * Add disableinteraction layer and adjust the size and position of the layer
   *
   * @api private
   * @method _disableInteraction
   */
  function _disableInteraction() {
    var disableInteractionLayer = document.querySelector('.introjs-disableInteraction');
    if (disableInteractionLayer === null) {
      disableInteractionLayer = document.createElement('div');
      disableInteractionLayer.className = 'introjs-disableInteraction';
      this._targetElement.appendChild(disableInteractionLayer);
    }
    _setHelperLayerPosition.call(this, disableInteractionLayer);
  }

  /**
   * Setting anchors to behave like buttons
   *
   * @api private
   * @method _setAnchorAsButton
   */
  function _setAnchorAsButton(anchor) {
    anchor.setAttribute('role', 'button');
    anchor.tabIndex = 0;
  }

  /**
   * Show an element on the page
   *
   * @api private
   * @method _showElement
   * @param {Object} targetElement
   */
  function _showElement(targetElement) {
    if (typeof this._introChangeCallback !== 'undefined') {
      this._introChangeCallback.call(this, targetElement.element);
    }
    var self = this,
      oldHelperLayer = document.querySelector('.introjs-helperLayer'),
      oldReferenceLayer = document.querySelector('.introjs-tooltipReferenceLayer'),
      highlightClass = 'introjs-helperLayer',
      nextTooltipButton,
      prevTooltipButton,
      skipTooltipButton,
      scrollParent;

    //check for a current step highlight class
    if (typeof targetElement.highlightClass === 'string') {
      highlightClass += ' ' + targetElement.highlightClass;
    }
    //check for options highlight class
    if (typeof this._options.highlightClass === 'string') {
      highlightClass += ' ' + this._options.highlightClass;
    }
    _setShowElement(targetElement);
    if (oldHelperLayer !== null) {
      var oldHelperNumberLayer = oldReferenceLayer.querySelector('.introjs-helperNumberLayer'),
        oldtooltipLayer = oldReferenceLayer.querySelector('.introjs-tooltiptext'),
        oldArrowLayer = oldReferenceLayer.querySelector('.introjs-arrow'),
        oldtooltipContainer = oldReferenceLayer.querySelector('.introjs-tooltip');
      skipTooltipButton = oldReferenceLayer.querySelector('.introjs-skipbutton');
      prevTooltipButton = oldReferenceLayer.querySelector('.introjs-prevbutton');
      nextTooltipButton = oldReferenceLayer.querySelector('.introjs-nextbutton');

      //update or reset the helper highlight class
      oldHelperLayer.className = highlightClass;
      //hide the tooltip
      oldtooltipContainer.style.opacity = 0;
      oldtooltipContainer.style.display = "none";
      if (oldHelperNumberLayer !== null) {
        var lastIntroItem = this._introItems[targetElement.step - 2 >= 0 ? targetElement.step - 2 : 0];
        if (lastIntroItem !== null && this._direction === 'forward' && lastIntroItem.position === 'floating' || this._direction === 'backward' && targetElement.position === 'floating') {
          oldHelperNumberLayer.style.opacity = 0;
        }
      }

      // scroll to element
      scrollParent = _getScrollParent(targetElement.element);
      if (scrollParent !== document.body) {
        // target is within a scrollable element
        _scrollParentToElement(scrollParent, targetElement.element);
      }

      // set new position to helper layer
      _setHelperLayerPosition.call(self, oldHelperLayer);
      _setHelperLayerPosition.call(self, oldReferenceLayer);

      //remove `introjs-fixParent` class from the elements
      var fixParents = document.querySelectorAll('.introjs-fixParent');
      _forEach(fixParents, function (parent) {
        _removeClass(parent, /introjs-fixParent/g);
      });

      //remove old classes if the element still exist
      _removeShowElement();

      //we should wait until the CSS3 transition is competed (it's 0.3 sec) to prevent incorrect `height` and `width` calculation
      if (self._lastShowElementTimer) {
        window.clearTimeout(self._lastShowElementTimer);
      }
      self._lastShowElementTimer = window.setTimeout(function () {
        //set current step to the label
        if (oldHelperNumberLayer !== null) {
          oldHelperNumberLayer.innerHTML = targetElement.step;
        }
        //set current tooltip text
        oldtooltipLayer.innerHTML = targetElement.intro;
        //set the tooltip position
        oldtooltipContainer.style.display = "block";
        _placeTooltip.call(self, targetElement.element, oldtooltipContainer, oldArrowLayer, oldHelperNumberLayer);

        //change active bullet
        if (self._options.showBullets) {
          oldReferenceLayer.querySelector('.introjs-bullets li > a.active').className = '';
          oldReferenceLayer.querySelector('.introjs-bullets li > a[data-stepnumber="' + targetElement.step + '"]').className = 'active';
        }
        oldReferenceLayer.querySelector('.introjs-progress .introjs-progressbar').style.cssText = 'width:' + _getProgress.call(self) + '%;';
        oldReferenceLayer.querySelector('.introjs-progress .introjs-progressbar').setAttribute('aria-valuenow', _getProgress.call(self));

        //show the tooltip
        oldtooltipContainer.style.opacity = 1;
        if (oldHelperNumberLayer) oldHelperNumberLayer.style.opacity = 1;

        //reset button focus
        if (typeof skipTooltipButton !== "undefined" && skipTooltipButton !== null && /introjs-donebutton/gi.test(skipTooltipButton.className)) {
          // skip button is now "done" button
          skipTooltipButton.focus();
        } else if (typeof nextTooltipButton !== "undefined" && nextTooltipButton !== null) {
          //still in the tour, focus on next
          nextTooltipButton.focus();
        }

        // change the scroll of the window, if needed
        _scrollTo.call(self, targetElement.scrollTo, targetElement, oldtooltipLayer);
      }, 50);

      // end of old element if-else condition
    } else {
      var helperLayer = document.createElement('div'),
        referenceLayer = document.createElement('div'),
        arrowLayer = document.createElement('div'),
        tooltipLayer = document.createElement('div'),
        tooltipTextLayer = document.createElement('div'),
        bulletsLayer = document.createElement('div'),
        progressLayer = document.createElement('div'),
        buttonsLayer = document.createElement('div');
      helperLayer.className = highlightClass;
      referenceLayer.id = 'tipjsTooltip';
      referenceLayer.className = 'introjs-tooltipReferenceLayer';

      // scroll to element
      scrollParent = _getScrollParent(targetElement.element);
      if (scrollParent !== document.body) {
        // target is within a scrollable element
        _scrollParentToElement(scrollParent, targetElement.element);
      }

      //set new position to helper layer
      _setHelperLayerPosition.call(self, helperLayer);
      _setHelperLayerPosition.call(self, referenceLayer);

      //add helper layer to target element
      this._targetElement.appendChild(helperLayer);
      this._targetElement.appendChild(referenceLayer);
      arrowLayer.className = 'introjs-arrow';
      tooltipTextLayer.className = 'introjs-tooltiptext';
      tooltipTextLayer.innerHTML = targetElement.intro;
      bulletsLayer.className = 'introjs-bullets';
      if (this._options.showBullets === false) {
        bulletsLayer.style.display = 'none';
      }
      var ulContainer = document.createElement('ul');
      ulContainer.setAttribute('role', 'tablist');
      var anchorClick = function anchorClick() {
        self.goToStep(this.getAttribute('data-stepnumber'));
      };
      _forEach(this._introItems, function (item, i) {
        var innerLi = document.createElement('li');
        var anchorLink = document.createElement('a');
        innerLi.setAttribute('role', 'presentation');
        anchorLink.setAttribute('role', 'tab');
        anchorLink.onclick = anchorClick;
        if (i === targetElement.step - 1) {
          anchorLink.className = 'active';
        }
        _setAnchorAsButton(anchorLink);
        anchorLink.innerHTML = "&nbsp;";
        anchorLink.setAttribute('data-stepnumber', item.step);
        innerLi.appendChild(anchorLink);
        ulContainer.appendChild(innerLi);
      });
      bulletsLayer.appendChild(ulContainer);
      progressLayer.className = 'introjs-progress';
      if (this._options.showProgress === false) {
        progressLayer.style.display = 'none';
      }
      var progressBar = document.createElement('div');
      progressBar.className = 'introjs-progressbar';
      progressBar.setAttribute('role', 'progress');
      progressBar.setAttribute('aria-valuemin', 0);
      progressBar.setAttribute('aria-valuemax', 100);
      progressBar.setAttribute('aria-valuenow', _getProgress.call(this));
      progressBar.style.cssText = 'width:' + _getProgress.call(this) + '%;';
      progressLayer.appendChild(progressBar);
      buttonsLayer.className = 'introjs-tooltipbuttons';
      if (this._options.showButtons === false) {
        buttonsLayer.style.display = 'none';
      }
      var closeBtn = '';
      var closeBtn = document.createElement('a');
      closeBtn.className = 'tip-close';
      closeBtn.setAttribute('role', 'button');
      closeBtn.setAttribute('href', '#close');
      closeBtn.innerHTML = '<i class="icon-close-white"></i>';
      closeBtn.onclick = this._introCloseCallback.bind(this);
      tooltipLayer.className = 'introjs-tooltip';
      tooltipLayer.appendChild(closeBtn);
      tooltipLayer.appendChild(tooltipTextLayer);
      tooltipLayer.appendChild(bulletsLayer);
      tooltipLayer.appendChild(progressLayer);

      //add helper layer number
      var helperNumberLayer = document.createElement('span');
      if (this._options.showStepNumbers === true) {
        helperNumberLayer.className = 'introjs-helperNumberLayer';
        helperNumberLayer.innerHTML = targetElement.step;
        referenceLayer.appendChild(helperNumberLayer);
      }
      tooltipLayer.appendChild(arrowLayer);
      referenceLayer.appendChild(tooltipLayer);

      //next button
      nextTooltipButton = document.createElement('a');
      nextTooltipButton.onclick = function () {
        if (self._introItems.length - 1 !== self._currentStep) {
          _nextStep.call(self);
        }
      };
      _setAnchorAsButton(nextTooltipButton);
      nextTooltipButton.innerHTML = this._options.nextLabel;

      //previous button
      prevTooltipButton = document.createElement('a');
      prevTooltipButton.onclick = function () {
        if (self._currentStep !== 0) {
          _previousStep.call(self);
        }
      };
      _setAnchorAsButton(prevTooltipButton);
      prevTooltipButton.innerHTML = this._options.prevLabel;

      //skip button
      skipTooltipButton = document.createElement('a');
      skipTooltipButton.className = this._options.buttonClass + ' introjs-skipbutton ';
      _setAnchorAsButton(skipTooltipButton);
      skipTooltipButton.innerHTML = this._options.skipLabel;
      skipTooltipButton.onclick = function () {
        if (self._introItems.length - 1 === self._currentStep && typeof self._introCompleteCallback === 'function') {
          self._introCompleteCallback.call(self);
        }
        if (self._introItems.length - 1 !== self._currentStep && typeof self._introExitCallback === 'function') {
          self._introExitCallback.call(self);
        }
        self._introSkipCallback.call(self);
        _exitIntro.call(self, self._targetElement);
      };
      buttonsLayer.appendChild(skipTooltipButton);

      //in order to prevent displaying next/previous button always
      if (this._introItems.length > 1) {
        buttonsLayer.appendChild(prevTooltipButton);
        buttonsLayer.appendChild(nextTooltipButton);
      }
      tooltipLayer.appendChild(buttonsLayer);

      //set proper position
      _placeTooltip.call(self, targetElement.element, tooltipLayer, arrowLayer, helperNumberLayer);

      // change the scroll of the window, if needed
      _scrollTo.call(this, targetElement.scrollTo, targetElement, tooltipLayer);

      //end of new element if-else condition
    }

    // removing previous disable interaction layer
    var disableInteractionLayer = self._targetElement.querySelector('.introjs-disableInteraction');
    if (disableInteractionLayer) {
      disableInteractionLayer.parentNode.removeChild(disableInteractionLayer);
    }

    //disable interaction
    if (targetElement.disableInteraction) {
      _disableInteraction.call(self);
    }

    // when it's the first step of tour
    if (this._currentStep === 0 && this._introItems.length > 1) {
      if (typeof skipTooltipButton !== "undefined" && skipTooltipButton !== null) {
        skipTooltipButton.className = this._options.buttonClass + ' introjs-skipbutton';
      }
      if (typeof nextTooltipButton !== "undefined" && nextTooltipButton !== null) {
        nextTooltipButton.className = this._options.buttonClass + ' introjs-nextbutton';
      }
      if (this._options.hidePrev === true) {
        if (typeof prevTooltipButton !== "undefined" && prevTooltipButton !== null) {
          prevTooltipButton.className = this._options.buttonClass + ' introjs-prevbutton introjs-hidden';
        }
        if (typeof nextTooltipButton !== "undefined" && nextTooltipButton !== null) {
          _addClass(nextTooltipButton, 'introjs-fullbutton');
        }
      } else {
        if (typeof prevTooltipButton !== "undefined" && prevTooltipButton !== null) {
          prevTooltipButton.className = this._options.buttonClass + ' introjs-prevbutton introjs-disabled';
        }
      }
      if (typeof skipTooltipButton !== "undefined" && skipTooltipButton !== null) {
        skipTooltipButton.innerHTML = this._options.skipLabel;
      }
    } else if (this._introItems.length - 1 === this._currentStep || this._introItems.length === 1) {
      // last step of tour
      if (typeof skipTooltipButton !== "undefined" && skipTooltipButton !== null) {
        skipTooltipButton.innerHTML = this._options.doneLabel;
        // adding donebutton class in addition to skipbutton
        _addClass(skipTooltipButton, 'introjs-donebutton');
      }
      if (typeof prevTooltipButton !== "undefined" && prevTooltipButton !== null) {
        prevTooltipButton.className = this._options.buttonClass + ' introjs-prevbutton';
      }
      if (this._options.hideNext === true) {
        if (typeof nextTooltipButton !== "undefined" && nextTooltipButton !== null) {
          nextTooltipButton.className = this._options.buttonClass + ' introjs-nextbutton introjs-hidden';
        }
        if (typeof prevTooltipButton !== "undefined" && prevTooltipButton !== null) {
          _addClass(prevTooltipButton, 'introjs-fullbutton');
        }
      } else {
        if (typeof nextTooltipButton !== "undefined" && nextTooltipButton !== null) {
          nextTooltipButton.className = this._options.buttonClass + ' introjs-nextbutton introjs-disabled';
        }
      }
    } else {
      // steps between start and end
      if (typeof skipTooltipButton !== "undefined" && skipTooltipButton !== null) {
        skipTooltipButton.className = this._options.buttonClass + ' introjs-skipbutton';
      }
      if (typeof prevTooltipButton !== "undefined" && prevTooltipButton !== null) {
        prevTooltipButton.className = this._options.buttonClass + ' introjs-prevbutton';
      }
      if (typeof nextTooltipButton !== "undefined" && nextTooltipButton !== null) {
        nextTooltipButton.className = this._options.buttonClass + ' introjs-nextbutton';
      }
      if (typeof skipTooltipButton !== "undefined" && skipTooltipButton !== null) {
        skipTooltipButton.innerHTML = this._options.skipLabel;
      }
    }
    prevTooltipButton.setAttribute('role', 'button');
    nextTooltipButton.setAttribute('role', 'button');
    skipTooltipButton.setAttribute('role', 'button');

    //Set focus on "next" button, so that hitting Enter always moves you onto the next step
    if (typeof nextTooltipButton !== "undefined" && nextTooltipButton !== null) {
      nextTooltipButton.focus();
    }
    _setShowElement(targetElement);
    if (typeof this._introAfterChangeCallback !== 'undefined') {
      this._introAfterChangeCallback.call(this, targetElement.element);
    }
  }

  /**
   * To change the scroll of `window` after highlighting an element
   *
   * @api private
   * @method _scrollTo
   * @param {String} scrollTo
   * @param {Object} targetElement
   * @param {Object} tooltipLayer
   */
  function _scrollTo(scrollTo, targetElement, tooltipLayer) {
    if (scrollTo === 'off') return;
    var rect;
    if (!this._options.scrollToElement) return;
    if (scrollTo === 'tooltip') {
      rect = tooltipLayer.getBoundingClientRect();
    } else {
      rect = targetElement.element.getBoundingClientRect();
    }
    if (!_elementInViewport(targetElement.element)) {
      var winHeight = _getWinSize().height;
      var top = rect.bottom - (rect.bottom - rect.top);

      // TODO (afshinm): do we need scroll padding now?
      // I have changed the scroll option and now it scrolls the window to
      // the center of the target element or tooltip.

      if (top < 0 || targetElement.element.clientHeight > winHeight) {
        window.scrollBy(0, rect.top - (winHeight / 2 - rect.height / 2) - this._options.scrollPadding); // 30px padding from edge to look nice

        //Scroll down
      } else {
        window.scrollBy(0, rect.top - (winHeight / 2 - rect.height / 2) + this._options.scrollPadding); // 30px padding from edge to look nice
      }
    }
  }

  /**
   * To remove all show element(s)
   *
   * @api private
   * @method _removeShowElement
   */
  function _removeShowElement() {
    var elms = document.querySelectorAll('.introjs-showElement');
    _forEach(elms, function (elm) {
      _removeClass(elm, /introjs-[a-zA-Z]+/g);
    });
  }

  /**
   * To set the show element
   * This function set a relative (in most cases) position and changes the z-index
   *
   * @api private
   * @method _setShowElement
   * @param {Object} targetElement
   */
  function _setShowElement(targetElement) {
    var parentElm;
    // we need to add this show element class to the parent of SVG elements
    // because the SVG elements can't have independent z-index
    if (targetElement.element instanceof SVGElement) {
      parentElm = targetElement.element.parentNode;
      while (targetElement.element.parentNode !== null) {
        if (!parentElm.tagName || parentElm.tagName.toLowerCase() === 'body') break;
        if (parentElm.tagName.toLowerCase() === 'svg') {
          _addClass(parentElm, 'introjs-showElement introjs-relativePosition');
        }
        parentElm = parentElm.parentNode;
      }
    }
    _addClass(targetElement.element, 'introjs-showElement');
    var currentElementPosition = _getPropValue(targetElement.element, 'position');
    if (currentElementPosition !== 'absolute' && currentElementPosition !== 'relative' && currentElementPosition !== 'fixed') {
      //change to new intro item
      _addClass(targetElement.element, 'introjs-relativePosition');
    }
    parentElm = targetElement.element.parentNode;
    while (parentElm !== null) {
      if (!parentElm.tagName || parentElm.tagName.toLowerCase() === 'body') break;

      //fix The Stacking Context problem.
      //More detail: https://developer.mozilla.org/en-US/docs/Web/Guide/CSS/Understanding_z_index/The_stacking_context
      var zIndex = _getPropValue(parentElm, 'z-index');
      var opacity = parseFloat(_getPropValue(parentElm, 'opacity'));
      var transform = _getPropValue(parentElm, 'transform') || _getPropValue(parentElm, '-webkit-transform') || _getPropValue(parentElm, '-moz-transform') || _getPropValue(parentElm, '-ms-transform') || _getPropValue(parentElm, '-o-transform');
      if (/[0-9]+/.test(zIndex) || opacity < 1 || transform !== 'none' && transform !== undefined) {
        _addClass(parentElm, 'introjs-fixParent');
      }
      parentElm = parentElm.parentNode;
    }
  }

  /**
   * Iterates arrays
   *
   * @param {Array} arr
   * @param {Function} forEachFnc
   * @param {Function} completeFnc
   * @return {Null}
   */
  function _forEach(arr, forEachFnc, completeFnc) {
    // in case arr is an empty query selector node list
    if (arr) {
      for (var i = 0, len = arr.length; i < len; i++) {
        forEachFnc(arr[i], i);
      }
    }
    if (typeof completeFnc === 'function') {
      completeFnc();
    }
  }

  /**
   * Mark any object with an incrementing number
   * used for keeping track of objects
   *
   * @param Object obj   Any object or DOM Element
   * @param String key
   * @return Object
   */
  var _stamp = function () {
    var keys = {};
    return function stamp(obj, key) {
      // get group key
      key = key || 'introjs-stamp';

      // each group increments from 0
      keys[key] = keys[key] || 0;

      // stamp only once per object
      if (obj[key] === undefined) {
        // increment key for each new object
        obj[key] = keys[key]++;
      }
      return obj[key];
    };
  }();

  /**
   * DOMEvent Handles all DOM events
   *
   * methods:
   *
   * on - add event handler
   * off - remove event
   */
  var DOMEvent = function () {
    function DOMEvent() {
      var events_key = 'introjs_event';

      /**
       * Gets a unique ID for an event listener
       *
       * @param Object obj
       * @param String type        event type
       * @param Function listener
       * @param Object context
       * @return String
       */
      this._id = function (obj, type, listener, context) {
        return type + _stamp(listener) + (context ? '_' + _stamp(context) : '');
      };

      /**
       * Adds event listener
       *
       * @param Object obj
       * @param String type        event type
       * @param Function listener
       * @param Object context
       * @param Boolean useCapture
       * @return null
       */
      this.on = function (obj, type, listener, context, useCapture) {
        var id = this._id.apply(this, arguments),
          handler = function handler(e) {
            return listener.call(context || obj, e || window.event);
          };
        if ('addEventListener' in obj) {
          obj.addEventListener(type, handler, useCapture);
        } else if ('attachEvent' in obj) {
          obj.attachEvent('on' + type, handler);
        }
        obj[events_key] = obj[events_key] || {};
        obj[events_key][id] = handler;
      };

      /**
       * Removes event listener
       *
       * @param Object obj
       * @param String type        event type
       * @param Function listener
       * @param Object context
       * @param Boolean useCapture
       * @return null
       */
      this.off = function (obj, type, listener, context, useCapture) {
        var id = this._id.apply(this, arguments),
          handler = obj[events_key] && obj[events_key][id];
        if ('removeEventListener' in obj) {
          obj.removeEventListener(type, handler, useCapture);
        } else if ('detachEvent' in obj) {
          obj.detachEvent('on' + type, handler);
        }
        obj[events_key][id] = null;
      };
    }
    return new DOMEvent();
  }();

  /**
   * Append a class to an element
   *
   * @api private
   * @method _addClass
   * @param {Object} element
   * @param {String} className
   * @returns null
   */
  function _addClass(element, className) {
    if (element instanceof SVGElement) {
      // svg
      var pre = element.getAttribute('class') || '';
      element.setAttribute('class', pre + ' ' + className);
    } else {
      if (element.classList !== undefined) {
        // check for modern classList property
        var classes = className.split(' ');
        _forEach(classes, function (cls) {
          element.classList.add(cls);
        });
      } else if (!element.className.match(className)) {
        // check if element doesn't already have className
        element.className += ' ' + className;
      }
    }
  }

  /**
   * Remove a class from an element
   *
   * @api private
   * @method _removeClass
   * @param {Object} element
   * @param {RegExp|String} classNameRegex can be regex or string
   * @returns null
   */
  function _removeClass(element, classNameRegex) {
    if (element instanceof SVGElement) {
      var pre = element.getAttribute('class') || '';
      element.setAttribute('class', pre.replace(classNameRegex, '').replace(/^\s+|\s+$/g, ''));
    } else {
      element.className = element.className.replace(classNameRegex, '').replace(/^\s+|\s+$/g, '');
    }
  }

  /**
   * Get an element CSS property on the page
   * Thanks to JavaScript Kit: http://www.javascriptkit.com/dhtmltutors/dhtmlcascade4.shtml
   *
   * @api private
   * @method _getPropValue
   * @param {Object} element
   * @param {String} propName
   * @returns Element's property value
   */
  function _getPropValue(element, propName) {
    var propValue = '';
    if (element.currentStyle) {
      //IE
      propValue = element.currentStyle[propName];
    } else if (document.defaultView && document.defaultView.getComputedStyle) {
      //Others
      propValue = document.defaultView.getComputedStyle(element, null).getPropertyValue(propName);
    }

    //Prevent exception in IE
    if (propValue && propValue.toLowerCase) {
      return propValue.toLowerCase();
    } else {
      return propValue;
    }
  }

  /**
   * Checks to see if target element (or parents) position is fixed or not
   *
   * @api private
   * @method _isFixed
   * @param {Object} element
   * @returns Boolean
   */
  function _isFixed(element) {
    var p = element.parentNode;
    if (!p || p.nodeName === 'HTML') {
      return false;
    }
    if (_getPropValue(element, 'position') === 'fixed') {
      return true;
    }
    return _isFixed(p);
  }

  /**
   * Provides a cross-browser way to get the screen dimensions
   * via: http://stackoverflow.com/questions/5864467/internet-explorer-innerheight
   *
   * @api private
   * @method _getWinSize
   * @returns {Object} width and height attributes
   */
  function _getWinSize() {
    if (window.innerWidth !== undefined) {
      return {
        width: window.innerWidth,
        height: window.innerHeight
      };
    } else {
      var D = document.documentElement;
      return {
        width: D.clientWidth,
        height: D.clientHeight
      };
    }
  }

  /**
   * Check to see if the element is in the viewport or not
   * http://stackoverflow.com/questions/123999/how-to-tell-if-a-dom-element-is-visible-in-the-current-viewport
   *
   * @api private
   * @method _elementInViewport
   * @param {Object} el
   */
  function _elementInViewport(el) {
    var rect = el.getBoundingClientRect();
    return rect.top >= 0 && rect.left >= 0 && rect.bottom + 80 <= window.innerHeight &&
    // add 80 to get the text right
    rect.right <= window.innerWidth;
  }

  /**
   * Add overlay layer to the page
   *
   * @api private
   * @method _addOverlayLayer
   * @param {Object} targetElm
   */
  function _addOverlayLayer(targetElm) {
    var overlayLayer = document.createElement('div'),
      styleText = '',
      self = this;

    //set css class name
    overlayLayer.id = 'tipjsOverlay';
    overlayLayer.className = 'introjs-overlay';

    //check if the target element is body, we should calculate the size of overlay layer in a better way
    if (!targetElm.tagName || targetElm.tagName.toLowerCase() === 'body') {
      styleText += 'top: 0;bottom: 0; left: 0;right: 0;position: fixed;';
      overlayLayer.style.cssText = styleText;
    } else {
      //set overlay layer position
      var elementPosition = _getOffset(targetElm);
      if (elementPosition) {
        styleText += 'width: ' + elementPosition.width + 'px; height:' + elementPosition.height + 'px; top:' + elementPosition.top + 'px;left: ' + elementPosition.left + 'px;';
        overlayLayer.style.cssText = styleText;
      }
    }
    targetElm.appendChild(overlayLayer);
    overlayLayer.onclick = function () {
      if (self._options.exitOnOverlayClick === true) {
        self._introSkipCallback.call(self);
        _exitIntro.call(self, targetElm);
      }
    };
    window.setTimeout(function () {
      styleText += 'opacity: ' + self._options.overlayOpacity.toString() + ';';
      overlayLayer.style.cssText = styleText;
    }, 1);
    return true;
  }

  /**
   * Removes open hint (tooltip hint)
   *
   * @api private
   * @method _removeHintTooltip
   */
  function _removeHintTooltip() {
    var tooltip = document.querySelector('.introjs-hintReference');
    if (tooltip) {
      var step = tooltip.getAttribute('data-step');
      tooltip.parentNode.removeChild(tooltip);
      return step;
    }
  }

  /**
   * Start parsing hint items
   *
   * @api private
   * @param {Object} targetElm
   * @method _startHint
   */
  function _populateHints(targetElm) {
    this._introItems = [];
    if (this._options.hints) {
      _forEach(this._options.hints, function (hint) {
        var currentItem = _cloneObject(hint);
        if (typeof currentItem.element === 'string') {
          //grab the element with given selector from the page
          currentItem.element = document.querySelector(currentItem.element);
        }
        currentItem.hintPosition = currentItem.hintPosition || this._options.hintPosition;
        currentItem.hintAnimation = currentItem.hintAnimation || this._options.hintAnimation;
        if (currentItem.element !== null) {
          this._introItems.push(currentItem);
        }
      }.bind(this));
    } else {
      var hints = targetElm.querySelectorAll('*[data-hint]');
      if (!hints || !hints.length) {
        return false;
      }

      //first add intro items with data-step
      _forEach(hints, function (currentElement) {
        // hint animation
        var hintAnimation = currentElement.getAttribute('data-hintanimation');
        if (hintAnimation) {
          hintAnimation = hintAnimation === 'true';
        } else {
          hintAnimation = this._options.hintAnimation;
        }
        this._introItems.push({
          element: currentElement,
          hint: currentElement.getAttribute('data-hint'),
          hintPosition: currentElement.getAttribute('data-hintposition') || this._options.hintPosition,
          hintAnimation: hintAnimation,
          tooltipClass: currentElement.getAttribute('data-tooltipclass'),
          position: currentElement.getAttribute('data-position') || this._options.tooltipPosition
        });
      }.bind(this));
    }
    _addHints.call(this);

    /*
    todo:
    these events should be removed at some point
    */
    DOMEvent.on(document, 'click', _removeHintTooltip, this, false);
    DOMEvent.on(window, 'resize', _reAlignHints, this, true);
  }

  /**
   * Re-aligns all hint elements
   *
   * @api private
   * @method _reAlignHints
   */
  function _reAlignHints() {
    _forEach(this._introItems, function (item) {
      if (typeof item.targetElement === 'undefined') {
        return;
      }
      _alignHintPosition.call(this, item.hintPosition, item.element, item.targetElement);
    }.bind(this));
  }

  /**
   * Get a queryselector within the hint wrapper
   *
   * @param {String} selector
   * @return {NodeList|Array}
   */
  function _hintQuerySelectorAll(selector) {
    var hintsWrapper = document.querySelector('.introjs-hints');
    return hintsWrapper ? hintsWrapper.querySelectorAll(selector) : [];
  }

  /**
   * Hide a hint
   *
   * @api private
   * @method _hideHint
   */
  function _hideHint(stepId) {
    var hint = _hintQuerySelectorAll('.introjs-hint[data-step="' + stepId + '"]')[0];
    _removeHintTooltip.call(this);
    if (hint) {
      _addClass(hint, 'introjs-hidehint');
    }

    // call the callback function (if any)
    if (typeof this._hintCloseCallback !== 'undefined') {
      this._hintCloseCallback.call(this, stepId);
    }
  }

  /**
   * Hide all hints
   *
   * @api private
   * @method _hideHints
   */
  function _hideHints() {
    var hints = _hintQuerySelectorAll('.introjs-hint');
    _forEach(hints, function (hint) {
      _hideHint.call(this, hint.getAttribute('data-step'));
    }.bind(this));
  }

  /**
   * Show all hints
   *
   * @api private
   * @method _showHints
   */
  function _showHints() {
    var hints = _hintQuerySelectorAll('.introjs-hint');
    if (hints && hints.length) {
      _forEach(hints, function (hint) {
        _showHint.call(this, hint.getAttribute('data-step'));
      }.bind(this));
    } else {
      _populateHints.call(this, this._targetElement);
    }
  }

  /**
   * Show a hint
   *
   * @api private
   * @method _showHint
   */
  function _showHint(stepId) {
    var hint = _hintQuerySelectorAll('.introjs-hint[data-step="' + stepId + '"]')[0];
    if (hint) {
      _removeClass(hint, /introjs-hidehint/g);
    }
  }

  /**
   * Removes all hint elements on the page
   * Useful when you want to destroy the elements and add them again (e.g. a modal or popup)
   *
   * @api private
   * @method _removeHints
   */
  function _removeHints() {
    var hints = _hintQuerySelectorAll('.introjs-hint');
    _forEach(hints, function (hint) {
      _removeHint.call(this, hint.getAttribute('data-step'));
    }.bind(this));
  }

  /**
   * Remove one single hint element from the page
   * Useful when you want to destroy the element and add them again (e.g. a modal or popup)
   * Use removeHints if you want to remove all elements.
   *
   * @api private
   * @method _removeHint
   */
  function _removeHint(stepId) {
    var hint = _hintQuerySelectorAll('.introjs-hint[data-step="' + stepId + '"]')[0];
    if (hint) {
      hint.parentNode.removeChild(hint);
    }
  }

  /**
   * Add all available hints to the page
   *
   * @api private
   * @method _addHints
   */
  function _addHints() {
    var self = this;
    var hintsWrapper = document.querySelector('.introjs-hints');
    if (hintsWrapper === null) {
      hintsWrapper = document.createElement('div');
      hintsWrapper.className = 'introjs-hints';
    }

    /**
     * Returns an event handler unique to the hint iteration
     *
     * @param {Integer} i
     * @return {Function}
     */
    var getHintClick = function getHintClick(i) {
      return function (e) {
        var evt = e ? e : window.event;
        if (evt.stopPropagation) {
          evt.stopPropagation();
        }
        if (evt.cancelBubble !== null) {
          evt.cancelBubble = true;
        }
        _showHintDialog.call(self, i);
      };
    };
    _forEach(this._introItems, function (item, i) {
      // avoid append a hint twice
      if (document.querySelector('.introjs-hint[data-step="' + i + '"]')) {
        return;
      }
      var hint = document.createElement('a');
      _setAnchorAsButton(hint);
      hint.onclick = getHintClick(i);
      hint.className = 'introjs-hint';
      if (!item.hintAnimation) {
        _addClass(hint, 'introjs-hint-no-anim');
      }

      // hint's position should be fixed if the target element's position is fixed
      if (_isFixed(item.element)) {
        _addClass(hint, 'introjs-fixedhint');
      }
      var hintDot = document.createElement('div');
      hintDot.className = 'introjs-hint-dot';
      var hintPulse = document.createElement('div');
      hintPulse.className = 'introjs-hint-pulse';
      hint.appendChild(hintDot);
      hint.appendChild(hintPulse);
      hint.setAttribute('data-step', i);

      // we swap the hint element with target element
      // because _setHelperLayerPosition uses `element` property
      item.targetElement = item.element;
      item.element = hint;

      // align the hint position
      _alignHintPosition.call(this, item.hintPosition, hint, item.targetElement);
      hintsWrapper.appendChild(hint);
    }.bind(this));

    // adding the hints wrapper
    document.body.appendChild(hintsWrapper);

    // call the callback function (if any)
    if (typeof this._hintsAddedCallback !== 'undefined') {
      this._hintsAddedCallback.call(this);
    }
  }

  /**
   * Aligns hint position
   *
   * @api private
   * @method _alignHintPosition
   * @param {String} position
   * @param {Object} hint
   * @param {Object} element
   */
  function _alignHintPosition(position, hint, element) {
    // get/calculate offset of target element
    var offset = _getOffset.call(this, element);
    var iconWidth = 20;
    var iconHeight = 20;

    // align the hint element
    switch (position) {
      default:
      case 'top-left':
        hint.style.left = offset.left + 'px';
        hint.style.top = offset.top + 'px';
        break;
      case 'top-right':
        hint.style.left = offset.left + offset.width - iconWidth + 'px';
        hint.style.top = offset.top + 'px';
        break;
      case 'bottom-left':
        hint.style.left = offset.left + 'px';
        hint.style.top = offset.top + offset.height - iconHeight + 'px';
        break;
      case 'bottom-right':
        hint.style.left = offset.left + offset.width - iconWidth + 'px';
        hint.style.top = offset.top + offset.height - iconHeight + 'px';
        break;
      case 'middle-left':
        hint.style.left = offset.left + 'px';
        hint.style.top = offset.top + (offset.height - iconHeight) / 2 + 'px';
        break;
      case 'middle-right':
        hint.style.left = offset.left + offset.width - iconWidth + 'px';
        hint.style.top = offset.top + (offset.height - iconHeight) / 2 + 'px';
        break;
      case 'middle-middle':
        hint.style.left = offset.left + (offset.width - iconWidth) / 2 + 'px';
        hint.style.top = offset.top + (offset.height - iconHeight) / 2 + 'px';
        break;
      case 'bottom-middle':
        hint.style.left = offset.left + (offset.width - iconWidth) / 2 + 'px';
        hint.style.top = offset.top + offset.height - iconHeight + 'px';
        break;
      case 'top-middle':
        hint.style.left = offset.left + (offset.width - iconWidth) / 2 + 'px';
        hint.style.top = offset.top + 'px';
        break;
    }
  }

  /**
   * Triggers when user clicks on the hint element
   *
   * @api private
   * @method _showHintDialog
   * @param {Number} stepId
   */
  function _showHintDialog(stepId) {
    var hintElement = document.querySelector('.introjs-hint[data-step="' + stepId + '"]');
    var item = this._introItems[stepId];

    // call the callback function (if any)
    if (typeof this._hintClickCallback !== 'undefined') {
      this._hintClickCallback.call(this, hintElement, item, stepId);
    }

    // remove all open tooltips
    var removedStep = _removeHintTooltip.call(this);

    // to toggle the tooltip
    if (parseInt(removedStep, 10) === stepId) {
      return;
    }
    var tooltipLayer = document.createElement('div');
    var tooltipTextLayer = document.createElement('div');
    var arrowLayer = document.createElement('div');
    var referenceLayer = document.createElement('div');
    tooltipLayer.className = 'introjs-tooltip';
    tooltipLayer.onclick = function (e) {
      //IE9 & Other Browsers
      if (e.stopPropagation) {
        e.stopPropagation();
      }
      //IE8 and Lower
      else {
        e.cancelBubble = true;
      }
    };
    tooltipTextLayer.className = 'introjs-tooltiptext';
    var tooltipWrapper = document.createElement('p');
    tooltipWrapper.innerHTML = item.hint;
    var closeButton = document.createElement('a');
    closeButton.className = this._options.buttonClass;
    closeButton.setAttribute('role', 'button');
    closeButton.innerHTML = this._options.hintButtonLabel;
    closeButton.onclick = _hideHint.bind(this, stepId);
    tooltipTextLayer.appendChild(tooltipWrapper);
    tooltipTextLayer.appendChild(closeButton);
    arrowLayer.className = 'introjs-arrow';
    tooltipLayer.appendChild(arrowLayer);
    tooltipLayer.appendChild(tooltipTextLayer);

    // set current step for _placeTooltip function
    this._currentStep = hintElement.getAttribute('data-step');

    // align reference layer position
    referenceLayer.className = 'introjs-tooltipReferenceLayer introjs-hintReference';
    referenceLayer.setAttribute('data-step', hintElement.getAttribute('data-step'));
    _setHelperLayerPosition.call(this, referenceLayer);
    referenceLayer.appendChild(tooltipLayer);
    document.body.appendChild(referenceLayer);

    //set proper position
    _placeTooltip.call(this, hintElement, tooltipLayer, arrowLayer, null, true);
  }

  /**
   * Get an element position on the page
   * Thanks to `meouw`: http://stackoverflow.com/a/442474/375966
   *
   * @api private
   * @method _getOffset
   * @param {Object} element
   * @returns Element's position info
   */
  function _getOffset(element) {
    var body = document.body;
    var docEl = document.documentElement;
    var scrollTop = window.pageYOffset || docEl.scrollTop || body.scrollTop;
    var scrollLeft = window.pageXOffset || docEl.scrollLeft || body.scrollLeft;
    var x = element.getBoundingClientRect();
    return {
      top: x.top + scrollTop,
      width: x.width,
      height: x.height,
      left: x.left + scrollLeft
    };
  }

  /**
   * Find the nearest scrollable parent
   * copied from https://stackoverflow.com/questions/35939886/find-first-scrollable-parent
   *
   * @param Element element
   * @return Element
   */
  function _getScrollParent(element) {
    var style = window.getComputedStyle(element);
    var excludeStaticParent = style.position === "absolute";
    var overflowRegex = /(auto|scroll)/;
    if (style.position === "fixed") return document.body;
    for (var parent = element; parent = parent.parentElement;) {
      style = window.getComputedStyle(parent);
      if (excludeStaticParent && style.position === "static") {
        continue;
      }
      if (overflowRegex.test(style.overflow + style.overflowY + style.overflowX)) return parent;
    }
    return document.body;
  }

  /**
   * scroll a scrollable element to a child element
   *
   * @param Element parent
   * @param Element element
   * @return Null
   */
  function _scrollParentToElement(parent, element) {
    parent.scrollTop = element.offsetTop - parent.offsetTop;
  }

  /**
   * Gets the current progress percentage
   *
   * @api private
   * @method _getProgress
   * @returns current progress percentage
   */
  function _getProgress() {
    // Steps are 0 indexed
    var currentStep = parseInt(this._currentStep + 1, 10);
    return currentStep / this._introItems.length * 100;
  }

  /**
   * Overwrites obj1's values with obj2's and adds obj2's if non existent in obj1
   * via: http://stackoverflow.com/questions/171251/how-can-i-merge-properties-of-two-javascript-objects-dynamically
   *
   * @param obj1
   * @param obj2
   * @returns obj3 a new object based on obj1 and obj2
   */
  function _mergeOptions(obj1, obj2) {
    var obj3 = {},
      attrname;
    for (attrname in obj1) {
      obj3[attrname] = obj1[attrname];
    }
    for (attrname in obj2) {
      obj3[attrname] = obj2[attrname];
    }
    return obj3;
  }
  var introJs = function introJs(targetElm) {
    var instance;
    if (_typeof(targetElm) === 'object') {
      //Ok, create a new instance
      instance = new IntroJs(targetElm);
    } else if (typeof targetElm === 'string') {
      //select the target element with query selector
      var targetElement = document.querySelector(targetElm);
      if (targetElement) {
        instance = new IntroJs(targetElement);
      } else {
        throw new Error('There is no element with given selector.');
      }
    } else {
      instance = new IntroJs(document.body);
    }
    // add instance to list of _instances
    // passing group to _stamp to increment
    // from 0 onward somewhat reliably
    introJs.instances[_stamp(instance, 'introjs-instance')] = instance;
    return instance;
  };

  /**
   * Current IntroJs version
   *
   * @property version
   * @type String
   */
  introJs.version = VERSION;

  /**
   * key-val object helper for introJs instances
   *
   * @property instances
   * @type Object
   */
  introJs.instances = {};

  //Prototype
  introJs.fn = IntroJs.prototype = {
    clone: function clone() {
      return new IntroJs(this);
    },
    setOption: function setOption(option, value) {
      this._options[option] = value;
      return this;
    },
    setOptions: function setOptions(options) {
      this._options = _mergeOptions(this._options, options);
      return this;
    },
    start: function start(group) {
      _introForElement.call(this, this._targetElement, group);
      return this;
    },
    goToStep: function goToStep(step) {
      _goToStep.call(this, step);
      return this;
    },
    addStep: function addStep(options) {
      if (!this._options.steps) {
        this._options.steps = [];
      }
      this._options.steps.push(options);
      return this;
    },
    addSteps: function addSteps(steps) {
      if (!steps.length) return;
      for (var index = 0; index < steps.length; index++) {
        this.addStep(steps[index]);
      }
      return this;
    },
    goToStepNumber: function goToStepNumber(step) {
      _goToStepNumber.call(this, step);
      return this;
    },
    nextStep: function nextStep() {
      _nextStep.call(this);
      return this;
    },
    previousStep: function previousStep() {
      _previousStep.call(this);
      return this;
    },
    exit: function exit(force) {
      _exitIntro.call(this, this._targetElement, force);
      return this;
    },
    refresh: function refresh() {
      _refresh.call(this);
      return this;
    },
    onstart: function onstart(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introStartCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onbeforechange was not a function');
      }
      return this;
    },
    onbeforechange: function onbeforechange(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introBeforeChangeCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onbeforechange was not a function');
      }
      return this;
    },
    onchange: function onchange(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introChangeCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onchange was not a function.');
      }
      return this;
    },
    onafterchange: function onafterchange(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introAfterChangeCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onafterchange was not a function');
      }
      return this;
    },
    oncomplete: function oncomplete(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introCompleteCallback = providedCallback;
      } else {
        throw new Error('Provided callback for oncomplete was not a function.');
      }
      return this;
    },
    onhintsadded: function onhintsadded(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._hintsAddedCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onhintsadded was not a function.');
      }
      return this;
    },
    onhintclick: function onhintclick(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._hintClickCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onhintclick was not a function.');
      }
      return this;
    },
    onhintclose: function onhintclose(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._hintCloseCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onhintclose was not a function.');
      }
      return this;
    },
    onexit: function onexit(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introExitCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onexit was not a function.');
      }
      return this;
    },
    onskip: function onskip(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introSkipCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onskip was not a function.');
      }
      return this;
    },
    onclose: function onclose(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introCloseCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onskip was not a function.');
      }
      return this;
    },
    onbeforeexit: function onbeforeexit(providedCallback) {
      if (typeof providedCallback === 'function') {
        this._introBeforeExitCallback = providedCallback;
      } else {
        throw new Error('Provided callback for onbeforeexit was not a function.');
      }
      return this;
    },
    addHints: function addHints() {
      _populateHints.call(this, this._targetElement);
      return this;
    },
    hideHint: function hideHint(stepId) {
      _hideHint.call(this, stepId);
      return this;
    },
    hideHints: function hideHints() {
      _hideHints.call(this);
      return this;
    },
    showHint: function showHint(stepId) {
      _showHint.call(this, stepId);
      return this;
    },
    showHints: function showHints() {
      _showHints.call(this);
      return this;
    },
    removeHints: function removeHints() {
      _removeHints.call(this);
      return this;
    },
    removeHint: function removeHint(stepId) {
      _removeHint.call(this, stepId);
      return this;
    },
    showHintDialog: function showHintDialog(stepId) {
      _showHintDialog.call(this, stepId);
      return this;
    }
  };
  return introJs;
});

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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QUM7SUFDRGdGLGNBQWMsRUFBRSxTQUFBQSxlQUFVaEYsTUFBTSxFQUFFO01BQzlCVyxlQUFlLENBQUNuUCxJQUFJLENBQUMsSUFBSSxFQUFFd08sTUFBTSxDQUFDO01BQ2xDLE9BQU8sSUFBSTtJQUNmO0VBQ0osQ0FBQztFQUVELE9BQU8zVCxPQUFPO0FBQ2xCLENBQUMsQ0FBQyIsInNvdXJjZXMiOlsid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9jb21tb24vanMvaW50cm8ubWluLmpzIl0sInNvdXJjZXNDb250ZW50IjpbIid1c2Ugc3RyaWN0JztcblxuLyoqXG4gKiBJbnRyby5qcyB2Mi45LjAtYWxwaGEuMVxuICogaHR0cHM6Ly9naXRodWIuY29tL3VzYWJsaWNhL2ludHJvLmpzXG4gKlxuICogQ29weXJpZ2h0IChDKSAyMDE3IEFmc2hpbiBNZWhyYWJhbmkgKEBhZnNoaW5tZWgpXG4gKi9cblxuKGZ1bmN0aW9uKGYpIHtcbiAgICBpZiAodHlwZW9mIGV4cG9ydHMgPT09IFwib2JqZWN0XCIgJiYgdHlwZW9mIG1vZHVsZSAhPT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICBtb2R1bGUuZXhwb3J0cyA9IGYoKTtcbiAgICAgICAgLy8gZGVwcmVjYXRlZCBmdW5jdGlvblxuICAgICAgICAvLyBAc2luY2UgMi44LjBcbiAgICAgICAgbW9kdWxlLmV4cG9ydHMuaW50cm9KcyA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGNvbnNvbGUud2FybignRGVwcmVjYXRlZDogcGxlYXNlIHVzZSByZXF1aXJlKFwiaW50cm8uanNcIikgZGlyZWN0bHksIGluc3RlYWQgb2YgdGhlIGludHJvSnMgbWV0aG9kIG9mIHRoZSBmdW5jdGlvbicpO1xuICAgICAgICAgICAgLy8gaW50cm9KcygpXG4gICAgICAgICAgICByZXR1cm4gZigpLmFwcGx5KHRoaXMsIGFyZ3VtZW50cyk7XG4gICAgICAgIH07XG4gICAgfSBlbHNlIGlmICh0eXBlb2YgZGVmaW5lID09PSBcImZ1bmN0aW9uXCIgJiYgZGVmaW5lLmFtZCkge1xuICAgICAgICBkZWZpbmUoW10sIGYpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgIHZhciBnO1xuICAgICAgICBpZiAodHlwZW9mIHdpbmRvdyAhPT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICAgICAgZyA9IHdpbmRvdztcbiAgICAgICAgfSBlbHNlIGlmICh0eXBlb2YgZ2xvYmFsICE9PSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgICAgICBnID0gZ2xvYmFsO1xuICAgICAgICB9IGVsc2UgaWYgKHR5cGVvZiBzZWxmICE9PSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgICAgICBnID0gc2VsZjtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGcgPSB0aGlzO1xuICAgICAgICB9XG4gICAgICAgIGcuaW50cm9KcyA9IGYoKTtcbiAgICB9XG59KShmdW5jdGlvbiAoKSB7XG4gICAgLy9EZWZhdWx0IGNvbmZpZy92YXJpYWJsZXNcbiAgICB2YXIgVkVSU0lPTiA9ICcyLjkuMC1hbHBoYS4xJztcblxuICAgIC8qKlxuICAgICAqIEludHJvSnMgbWFpbiBjbGFzc1xuICAgICAqXG4gICAgICogQGNsYXNzIEludHJvSnNcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBJbnRyb0pzKG9iaikge1xuICAgICAgICB0aGlzLl90YXJnZXRFbGVtZW50ID0gb2JqO1xuICAgICAgICB0aGlzLl9pbnRyb0l0ZW1zID0gW107XG5cbiAgICAgICAgdGhpcy5fb3B0aW9ucyA9IHtcbiAgICAgICAgICAgIC8qIE5leHQgYnV0dG9uIGxhYmVsIGluIHRvb2x0aXAgYm94ICovXG4gICAgICAgICAgICBuZXh0TGFiZWw6ICdOZXh0ICZyYXJyOycsXG4gICAgICAgICAgICAvKiBQcmV2aW91cyBidXR0b24gbGFiZWwgaW4gdG9vbHRpcCBib3ggKi9cbiAgICAgICAgICAgIHByZXZMYWJlbDogJyZsYXJyOyBCYWNrJyxcbiAgICAgICAgICAgIC8qIFNraXAgYnV0dG9uIGxhYmVsIGluIHRvb2x0aXAgYm94ICovXG4gICAgICAgICAgICBza2lwTGFiZWw6ICdTa2lwJyxcbiAgICAgICAgICAgIC8qIERvbmUgYnV0dG9uIGxhYmVsIGluIHRvb2x0aXAgYm94ICovXG4gICAgICAgICAgICBkb25lTGFiZWw6ICdEb25lJyxcbiAgICAgICAgICAgIC8qIEhpZGUgcHJldmlvdXMgYnV0dG9uIGluIHRoZSBmaXJzdCBzdGVwPyBPdGhlcndpc2UsIGl0IHdpbGwgYmUgZGlzYWJsZWQgYnV0dG9uLiAqL1xuICAgICAgICAgICAgaGlkZVByZXY6IGZhbHNlLFxuICAgICAgICAgICAgLyogSGlkZSBuZXh0IGJ1dHRvbiBpbiB0aGUgbGFzdCBzdGVwPyBPdGhlcndpc2UsIGl0IHdpbGwgYmUgZGlzYWJsZWQgYnV0dG9uLiAqL1xuICAgICAgICAgICAgaGlkZU5leHQ6IGZhbHNlLFxuICAgICAgICAgICAgLyogRGVmYXVsdCB0b29sdGlwIGJveCBwb3NpdGlvbiAqL1xuICAgICAgICAgICAgdG9vbHRpcFBvc2l0aW9uOiAnYm90dG9tJyxcbiAgICAgICAgICAgIC8qIE5leHQgQ1NTIGNsYXNzIGZvciB0b29sdGlwIGJveGVzICovXG4gICAgICAgICAgICB0b29sdGlwQ2xhc3M6ICcnLFxuICAgICAgICAgICAgLyogQ1NTIGNsYXNzIHRoYXQgaXMgYWRkZWQgdG8gdGhlIGhlbHBlckxheWVyICovXG4gICAgICAgICAgICBoaWdobGlnaHRDbGFzczogJycsXG4gICAgICAgICAgICAvKiBDbG9zZSBpbnRyb2R1Y3Rpb24gd2hlbiBwcmVzc2luZyBFc2NhcGUgYnV0dG9uPyAqL1xuICAgICAgICAgICAgZXhpdE9uRXNjOiB0cnVlLFxuICAgICAgICAgICAgLyogQ2xvc2UgaW50cm9kdWN0aW9uIHdoZW4gY2xpY2tpbmcgb24gb3ZlcmxheSBsYXllcj8gKi9cbiAgICAgICAgICAgIGV4aXRPbk92ZXJsYXlDbGljazogdHJ1ZSxcbiAgICAgICAgICAgIC8qIFNob3cgc3RlcCBudW1iZXJzIGluIGludHJvZHVjdGlvbj8gKi9cbiAgICAgICAgICAgIHNob3dTdGVwTnVtYmVyczogdHJ1ZSxcbiAgICAgICAgICAgIC8qIExldCB1c2VyIHVzZSBrZXlib2FyZCB0byBuYXZpZ2F0ZSB0aGUgdG91cj8gKi9cbiAgICAgICAgICAgIGtleWJvYXJkTmF2aWdhdGlvbjogdHJ1ZSxcbiAgICAgICAgICAgIC8qIFNob3cgdG91ciBjb250cm9sIGJ1dHRvbnM/ICovXG4gICAgICAgICAgICBzaG93QnV0dG9uczogdHJ1ZSxcbiAgICAgICAgICAgIC8qIFNob3cgdG91ciBidWxsZXRzPyAqL1xuICAgICAgICAgICAgc2hvd0J1bGxldHM6IHRydWUsXG4gICAgICAgICAgICAvKiBTaG93IHRvdXIgcHJvZ3Jlc3M/ICovXG4gICAgICAgICAgICBzaG93UHJvZ3Jlc3M6IGZhbHNlLFxuICAgICAgICAgICAgLyogU2Nyb2xsIHRvIGhpZ2hsaWdodGVkIGVsZW1lbnQ/ICovXG4gICAgICAgICAgICBzY3JvbGxUb0VsZW1lbnQ6IHRydWUsXG4gICAgICAgICAgICAvKlxuICAgICAgICAgICAgICogU2hvdWxkIHdlIHNjcm9sbCB0aGUgdG9vbHRpcCBvciB0YXJnZXQgZWxlbWVudD9cbiAgICAgICAgICAgICAqXG4gICAgICAgICAgICAgKiBPcHRpb25zIGFyZTogJ2VsZW1lbnQnIG9yICd0b29sdGlwJ1xuICAgICAgICAgICAgICovXG4gICAgICAgICAgICBzY3JvbGxUbzogJ2VsZW1lbnQnLFxuICAgICAgICAgICAgLyogUGFkZGluZyB0byBhZGQgYWZ0ZXIgc2Nyb2xsaW5nIHdoZW4gZWxlbWVudCBpcyBub3QgaW4gdGhlIHZpZXdwb3J0IChpbiBwaXhlbHMpICovXG4gICAgICAgICAgICBzY3JvbGxQYWRkaW5nOiAzMCxcbiAgICAgICAgICAgIC8qIFNldCB0aGUgb3ZlcmxheSBvcGFjaXR5ICovXG4gICAgICAgICAgICBvdmVybGF5T3BhY2l0eTogMC44LFxuICAgICAgICAgICAgLyogUHJlY2VkZW5jZSBvZiBwb3NpdGlvbnMsIHdoZW4gYXV0byBpcyBlbmFibGVkICovXG4gICAgICAgICAgICBwb3NpdGlvblByZWNlZGVuY2U6IFtcImJvdHRvbVwiLCBcInRvcFwiLCBcInJpZ2h0XCIsIFwibGVmdFwiXSxcbiAgICAgICAgICAgIC8qIERpc2FibGUgYW4gaW50ZXJhY3Rpb24gd2l0aCBlbGVtZW50PyAqL1xuICAgICAgICAgICAgZGlzYWJsZUludGVyYWN0aW9uOiBmYWxzZSxcbiAgICAgICAgICAgIC8qIFNldCBob3cgbXVjaCBwYWRkaW5nIHRvIGJlIHVzZWQgYXJvdW5kIGhlbHBlciBlbGVtZW50ICovXG4gICAgICAgICAgICBoZWxwZXJFbGVtZW50UGFkZGluZzogMTAsXG4gICAgICAgICAgICAvKiBEZWZhdWx0IGhpbnQgcG9zaXRpb24gKi9cbiAgICAgICAgICAgIGhpbnRQb3NpdGlvbjogJ3RvcC1taWRkbGUnLFxuICAgICAgICAgICAgLyogSGludCBidXR0b24gbGFiZWwgKi9cbiAgICAgICAgICAgIGhpbnRCdXR0b25MYWJlbDogJ0dvdCBpdCcsXG4gICAgICAgICAgICAvKiBBZGRpbmcgYW5pbWF0aW9uIHRvIGhpbnRzPyAqL1xuICAgICAgICAgICAgaGludEFuaW1hdGlvbjogdHJ1ZSxcbiAgICAgICAgICAgIC8qIGFkZGl0aW9uYWwgY2xhc3NlcyB0byBwdXQgb24gdGhlIGJ1dHRvbnMgKi9cbiAgICAgICAgICAgIGJ1dHRvbkNsYXNzOiBcIlwiXG4gICAgICAgIH07XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogSW5pdGlhdGUgYSBuZXcgaW50cm9kdWN0aW9uL2d1aWRlIGZyb20gYW4gZWxlbWVudCBpbiB0aGUgcGFnZVxuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfaW50cm9Gb3JFbGVtZW50XG4gICAgICogQHBhcmFtIHtPYmplY3R9IHRhcmdldEVsbVxuICAgICAqIEBwYXJhbSB7U3RyaW5nfSBncm91cFxuICAgICAqIEByZXR1cm5zIHtCb29sZWFufSBTdWNjZXNzIG9yIG5vdD9cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfaW50cm9Gb3JFbGVtZW50KHRhcmdldEVsbSwgZ3JvdXApIHtcbiAgICAgICAgdmFyIGFsbEludHJvU3RlcHMgPSB0YXJnZXRFbG0ucXVlcnlTZWxlY3RvckFsbChcIipbZGF0YS1pbnRyb11cIiksXG4gICAgICAgICAgICBpbnRyb0l0ZW1zID0gW107XG4gICAgICAgIGlmICgtMSAhPT0gdGFyZ2V0RWxtLmNsYXNzTmFtZS5pbmRleE9mKCdtYWluLWJvZHknKSkge1xuICAgICAgICAgICAgYWxsSW50cm9TdGVwcyAgICAgICA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJypbZGF0YS1pbnRyb11bZGF0YS1zaG93XScpO1xuICAgICAgICAgICAgdGhpcy5fdGFyZ2V0RWxlbWVudCA9IHRhcmdldEVsbSA9IGRvY3VtZW50LmJvZHk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKDAgPT09IGFsbEludHJvU3RlcHMubGVuZ3RoICYmIG51bGwgIT09IHRhcmdldEVsbS5nZXRBdHRyaWJ1dGUoJ2RhdGEtaW50cm8nKSkge1xuICAgICAgICAgICAgYWxsSW50cm9TdGVwcyAgICAgICA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJypbZGF0YS1pbnRybz1cIicgKyB0YXJnZXRFbG0uZ2V0QXR0cmlidXRlKCdkYXRhLWludHJvJykgKyAnXCJdJyk7XG4gICAgICAgICAgICB0aGlzLl90YXJnZXRFbGVtZW50ID0gdGFyZ2V0RWxtID0gZG9jdW1lbnQuYm9keTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh0aGlzLl9vcHRpb25zLnN0ZXBzKSB7XG4gICAgICAgICAgICAvL3VzZSBzdGVwcyBwYXNzZWQgcHJvZ3JhbW1hdGljYWxseVxuICAgICAgICAgICAgX2ZvckVhY2godGhpcy5fb3B0aW9ucy5zdGVwcywgZnVuY3Rpb24gKHN0ZXApIHtcbiAgICAgICAgICAgICAgICB2YXIgY3VycmVudEl0ZW0gPSBfY2xvbmVPYmplY3Qoc3RlcCk7XG5cbiAgICAgICAgICAgICAgICAvL3NldCB0aGUgc3RlcFxuICAgICAgICAgICAgICAgIGN1cnJlbnRJdGVtLnN0ZXAgPSBpbnRyb0l0ZW1zLmxlbmd0aCArIDE7XG5cbiAgICAgICAgICAgICAgICAvL3VzZSBxdWVyeVNlbGVjdG9yIGZ1bmN0aW9uIG9ubHkgd2hlbiBkZXZlbG9wZXIgdXNlZCBDU1Mgc2VsZWN0b3JcbiAgICAgICAgICAgICAgICBpZiAodHlwZW9mIChjdXJyZW50SXRlbS5lbGVtZW50KSA9PT0gJ3N0cmluZycpIHtcbiAgICAgICAgICAgICAgICAgICAgLy9ncmFiIHRoZSBlbGVtZW50IHdpdGggZ2l2ZW4gc2VsZWN0b3IgZnJvbSB0aGUgcGFnZVxuICAgICAgICAgICAgICAgICAgICBjdXJyZW50SXRlbS5lbGVtZW50ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihjdXJyZW50SXRlbS5lbGVtZW50KTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAvL2ludHJvIHdpdGhvdXQgZWxlbWVudFxuICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgKGN1cnJlbnRJdGVtLmVsZW1lbnQpID09PSAndW5kZWZpbmVkJyB8fCBjdXJyZW50SXRlbS5lbGVtZW50ID09PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgIHZhciBmbG9hdGluZ0VsZW1lbnRRdWVyeSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIudGlwanNGbG9hdGluZ0VsZW1lbnRcIik7XG5cbiAgICAgICAgICAgICAgICAgICAgaWYgKGZsb2F0aW5nRWxlbWVudFF1ZXJ5ID09PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBmbG9hdGluZ0VsZW1lbnRRdWVyeSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgICAgICAgICAgICAgICAgICAgICAgZmxvYXRpbmdFbGVtZW50UXVlcnkuY2xhc3NOYW1lID0gJ2ludHJvanNGbG9hdGluZ0VsZW1lbnQnO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICBkb2N1bWVudC5ib2R5LmFwcGVuZENoaWxkKGZsb2F0aW5nRWxlbWVudFF1ZXJ5KTtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgIGN1cnJlbnRJdGVtLmVsZW1lbnQgID0gZmxvYXRpbmdFbGVtZW50UXVlcnk7XG4gICAgICAgICAgICAgICAgICAgIGN1cnJlbnRJdGVtLnBvc2l0aW9uID0gJ2Zsb2F0aW5nJztcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBjdXJyZW50SXRlbS5zY3JvbGxUbyA9IGN1cnJlbnRJdGVtLnNjcm9sbFRvIHx8IHRoaXMuX29wdGlvbnMuc2Nyb2xsVG87XG5cbiAgICAgICAgICAgICAgICBpZiAodHlwZW9mIChjdXJyZW50SXRlbS5kaXNhYmxlSW50ZXJhY3Rpb24pID09PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgICAgICAgICBjdXJyZW50SXRlbS5kaXNhYmxlSW50ZXJhY3Rpb24gPSB0aGlzLl9vcHRpb25zLmRpc2FibGVJbnRlcmFjdGlvbjtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBpZiAoY3VycmVudEl0ZW0uZWxlbWVudCAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICBpbnRyb0l0ZW1zLnB1c2goY3VycmVudEl0ZW0pO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0uYmluZCh0aGlzKSk7XG5cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIC8vdXNlIHN0ZXBzIGZyb20gZGF0YS0qIGFubm90YXRpb25zXG4gICAgICAgICAgICB2YXIgZWxtc0xlbmd0aCA9IGFsbEludHJvU3RlcHMubGVuZ3RoO1xuICAgICAgICAgICAgdmFyIGRpc2FibGVJbnRlcmFjdGlvbjtcblxuICAgICAgICAgICAgLy9pZiB0aGVyZSdzIG5vIGVsZW1lbnQgdG8gaW50cm9cbiAgICAgICAgICAgIGlmIChlbG1zTGVuZ3RoIDwgMSkge1xuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgX2ZvckVhY2goYWxsSW50cm9TdGVwcywgZnVuY3Rpb24gKGN1cnJlbnRFbGVtZW50KSB7XG5cbiAgICAgICAgICAgICAgICAvLyBQUiAjODBcbiAgICAgICAgICAgICAgICAvLyBzdGFydCBpbnRybyBmb3IgZ3JvdXBzIG9mIGVsZW1lbnRzXG4gICAgICAgICAgICAgICAgaWYgKGdyb3VwICYmIChjdXJyZW50RWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJkYXRhLWludHJvLWdyb3VwXCIpICE9PSBncm91cCkpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIC8vIHNraXAgaGlkZGVuIGVsZW1lbnRzXG4gICAgICAgICAgICAgICAgaWYgKGN1cnJlbnRFbGVtZW50LnN0eWxlLmRpc3BsYXkgPT09ICdub25lJykge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgdmFyIHN0ZXAgPSBwYXJzZUludChjdXJyZW50RWxlbWVudC5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RlcCcpLCAxMCk7XG5cbiAgICAgICAgICAgICAgICBpZiAodHlwZW9mIChjdXJyZW50RWxlbWVudC5nZXRBdHRyaWJ1dGUoJ2RhdGEtZGlzYWJsZS1pbnRlcmFjdGlvbicpKSAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgICAgICAgICAgICAgZGlzYWJsZUludGVyYWN0aW9uID0gISFjdXJyZW50RWxlbWVudC5nZXRBdHRyaWJ1dGUoJ2RhdGEtZGlzYWJsZS1pbnRlcmFjdGlvbicpO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIGRpc2FibGVJbnRlcmFjdGlvbiA9IHRoaXMuX29wdGlvbnMuZGlzYWJsZUludGVyYWN0aW9uO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGlmIChzdGVwID4gMCkge1xuICAgICAgICAgICAgICAgICAgICBpbnRyb0l0ZW1zW3N0ZXAgLSAxXSA9IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGVsZW1lbnQ6IGN1cnJlbnRFbGVtZW50LFxuICAgICAgICAgICAgICAgICAgICAgICAgaW50cm86IGRlY29kZVVSSUNvbXBvbmVudCggY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLWludHJvJykgKSxcbiAgICAgICAgICAgICAgICAgICAgICAgIHN0ZXA6IHBhcnNlSW50KGN1cnJlbnRFbGVtZW50LmdldEF0dHJpYnV0ZSgnZGF0YS1zdGVwJyksIDEwKSxcbiAgICAgICAgICAgICAgICAgICAgICAgIHRvb2x0aXBDbGFzczogY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLXRvb2x0aXBjbGFzcycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgaGlnaGxpZ2h0Q2xhc3M6IGN1cnJlbnRFbGVtZW50LmdldEF0dHJpYnV0ZSgnZGF0YS1oaWdobGlnaHRjbGFzcycpLFxuICAgICAgICAgICAgICAgICAgICAgICAgcG9zaXRpb246IGN1cnJlbnRFbGVtZW50LmdldEF0dHJpYnV0ZSgnZGF0YS1wb3NpdGlvbicpIHx8IHRoaXMuX29wdGlvbnMudG9vbHRpcFBvc2l0aW9uLFxuICAgICAgICAgICAgICAgICAgICAgICAgc2Nyb2xsVG86IGN1cnJlbnRFbGVtZW50LmdldEF0dHJpYnV0ZSgnZGF0YS1zY3JvbGx0bycpIHx8IHRoaXMuX29wdGlvbnMuc2Nyb2xsVG8sXG4gICAgICAgICAgICAgICAgICAgICAgICBkaXNhYmxlSW50ZXJhY3Rpb246IGRpc2FibGVJbnRlcmFjdGlvblxuICAgICAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0uYmluZCh0aGlzKSk7XG5cbiAgICAgICAgICAgIC8vbmV4dCBhZGQgaW50cm8gaXRlbXMgd2l0aG91dCBkYXRhLXN0ZXBcbiAgICAgICAgICAgIC8vdG9kbzogd2UgbmVlZCBhIGNsZWFudXAgaGVyZSwgdHdvIGxvb3BzIGFyZSByZWR1bmRhbnRcbiAgICAgICAgICAgIHZhciBuZXh0U3RlcCA9IDA7XG5cbiAgICAgICAgICAgIF9mb3JFYWNoKGFsbEludHJvU3RlcHMsIGZ1bmN0aW9uIChjdXJyZW50RWxlbWVudCkge1xuXG4gICAgICAgICAgICAgICAgLy8gUFIgIzgwXG4gICAgICAgICAgICAgICAgLy8gc3RhcnQgaW50cm8gZm9yIGdyb3VwcyBvZiBlbGVtZW50c1xuICAgICAgICAgICAgICAgIGlmIChncm91cCAmJiAoY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKFwiZGF0YS1pbnRyby1ncm91cFwiKSAhPT0gZ3JvdXApKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBpZiAoY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLXN0ZXAnKSA9PT0gbnVsbCkge1xuXG4gICAgICAgICAgICAgICAgICAgIHdoaWxlICh0cnVlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAodHlwZW9mIGludHJvSXRlbXNbbmV4dFN0ZXBdID09PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBuZXh0U3RlcCsrO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiAoY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLWRpc2FibGUtaW50ZXJhY3Rpb24nKSkgIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBkaXNhYmxlSW50ZXJhY3Rpb24gPSAhIWN1cnJlbnRFbGVtZW50LmdldEF0dHJpYnV0ZSgnZGF0YS1kaXNhYmxlLWludGVyYWN0aW9uJyk7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBkaXNhYmxlSW50ZXJhY3Rpb24gPSB0aGlzLl9vcHRpb25zLmRpc2FibGVJbnRlcmFjdGlvbjtcbiAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgIGludHJvSXRlbXNbbmV4dFN0ZXBdID0ge1xuICAgICAgICAgICAgICAgICAgICAgICAgZWxlbWVudDogY3VycmVudEVsZW1lbnQsXG4gICAgICAgICAgICAgICAgICAgICAgICBpbnRybzogZGVjb2RlVVJJQ29tcG9uZW50KCBjdXJyZW50RWxlbWVudC5nZXRBdHRyaWJ1dGUoJ2RhdGEtaW50cm8nKSApLFxuICAgICAgICAgICAgICAgICAgICAgICAgc3RlcDogbmV4dFN0ZXAgKyAxLFxuICAgICAgICAgICAgICAgICAgICAgICAgdG9vbHRpcENsYXNzOiBjdXJyZW50RWxlbWVudC5nZXRBdHRyaWJ1dGUoJ2RhdGEtdG9vbHRpcGNsYXNzJyksXG4gICAgICAgICAgICAgICAgICAgICAgICBoaWdobGlnaHRDbGFzczogY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLWhpZ2hsaWdodGNsYXNzJyksXG4gICAgICAgICAgICAgICAgICAgICAgICBwb3NpdGlvbjogY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLXBvc2l0aW9uJykgfHwgdGhpcy5fb3B0aW9ucy50b29sdGlwUG9zaXRpb24sXG4gICAgICAgICAgICAgICAgICAgICAgICBzY3JvbGxUbzogY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLXNjcm9sbHRvJykgfHwgdGhpcy5fb3B0aW9ucy5zY3JvbGxUbyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGRpc2FibGVJbnRlcmFjdGlvbjogZGlzYWJsZUludGVyYWN0aW9uXG4gICAgICAgICAgICAgICAgICAgIH07XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfS5iaW5kKHRoaXMpKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vcmVtb3ZpbmcgdW5kZWZpbmVkL251bGwgZWxlbWVudHNcbiAgICAgICAgdmFyIHRlbXBJbnRyb0l0ZW1zID0gW107XG4gICAgICAgIGZvciAodmFyIHogPSAwOyB6IDwgaW50cm9JdGVtcy5sZW5ndGg7IHorKykge1xuICAgICAgICAgICAgaWYgKGludHJvSXRlbXNbel0pIHtcbiAgICAgICAgICAgICAgICAvLyBjb3B5IG5vbi1mYWxzeSB2YWx1ZXMgdG8gdGhlIGVuZCBvZiB0aGUgYXJyYXlcbiAgICAgICAgICAgICAgICB0ZW1wSW50cm9JdGVtcy5wdXNoKGludHJvSXRlbXNbel0pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgaW50cm9JdGVtcyA9IHRlbXBJbnRyb0l0ZW1zO1xuXG4gICAgICAgIC8vT2ssIHNvcnQgYWxsIGl0ZW1zIHdpdGggZ2l2ZW4gc3RlcHNcbiAgICAgICAgaW50cm9JdGVtcy5zb3J0KGZ1bmN0aW9uIChhLCBiKSB7XG4gICAgICAgICAgICByZXR1cm4gYS5zdGVwIC0gYi5zdGVwO1xuICAgICAgICB9KTtcblxuICAgICAgICAvL3NldCBpdCB0byB0aGUgaW50cm9KcyBvYmplY3RcbiAgICAgICAgdGhpcy5faW50cm9JdGVtcyA9IGludHJvSXRlbXM7XG5cbiAgICAgICAgLy9hZGQgb3ZlcmxheSBsYXllciB0byB0aGUgcGFnZVxuICAgICAgICBpZihfYWRkT3ZlcmxheUxheWVyLmNhbGwodGhpcywgdGFyZ2V0RWxtKSkge1xuICAgICAgICAgICAgLy90aGVuLCBzdGFydCB0aGUgc2hvd1xuICAgICAgICAgICAgX25leHRTdGVwLmNhbGwodGhpcyk7XG5cbiAgICAgICAgICAgIGlmICh0aGlzLl9vcHRpb25zLmtleWJvYXJkTmF2aWdhdGlvbikge1xuICAgICAgICAgICAgICAgIERPTUV2ZW50Lm9uKHdpbmRvdywgJ2tleWRvd24nLCBfb25LZXlEb3duLCB0aGlzLCB0cnVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIC8vZm9yIHdpbmRvdyByZXNpemVcbiAgICAgICAgICAgIERPTUV2ZW50Lm9uKHdpbmRvdywgJ3Jlc2l6ZScsIF9vblJlc2l6ZSwgdGhpcywgdHJ1ZSk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAodGhpcy5faW50cm9TdGFydENhbGxiYWNrICE9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgIHRoaXMuX2ludHJvU3RhcnRDYWxsYmFjay5jYWxsKHRoaXMpO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cblxuICAgIGZ1bmN0aW9uIF9vblJlc2l6ZSAoKSB7XG4gICAgICAgIHRoaXMucmVmcmVzaC5jYWxsKHRoaXMpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIG9uIGtleUNvZGU6XG4gICAgICogaHR0cHM6Ly9kZXZlbG9wZXIubW96aWxsYS5vcmcvZW4tVVMvZG9jcy9XZWIvQVBJL0tleWJvYXJkRXZlbnQva2V5Q29kZVxuICAgICAqIFRoaXMgZmVhdHVyZSBoYXMgYmVlbiByZW1vdmVkIGZyb20gdGhlIFdlYiBzdGFuZGFyZHMuXG4gICAgICogVGhvdWdoIHNvbWUgYnJvd3NlcnMgbWF5IHN0aWxsIHN1cHBvcnQgaXQsIGl0IGlzIGluXG4gICAgICogdGhlIHByb2Nlc3Mgb2YgYmVpbmcgZHJvcHBlZC5cbiAgICAgKiBJbnN0ZWFkLCB5b3Ugc2hvdWxkIHVzZSBLZXlib2FyZEV2ZW50LmNvZGUsXG4gICAgICogaWYgaXQncyBpbXBsZW1lbnRlZC5cbiAgICAgKlxuICAgICAqIGpRdWVyeSdzIGFwcHJvYWNoIGlzIHRvIHRlc3QgZm9yXG4gICAgICogICAoMSkgZS53aGljaCwgdGhlblxuICAgICAqICAgKDIpIGUuY2hhckNvZGUsIHRoZW5cbiAgICAgKiAgICgzKSBlLmtleUNvZGVcbiAgICAgKiBodHRwczovL2dpdGh1Yi5jb20vanF1ZXJ5L2pxdWVyeS9ibG9iL2E2YjA3MDUyOTRkMzM2YWUyZjYzZjcyNzZkZTBkYTExOTU0OTUzNjMvc3JjL2V2ZW50LmpzI0w2MzhcbiAgICAgKlxuICAgICAqIEBwYXJhbSB0eXBlIHZhclxuICAgICAqIEByZXR1cm4gdHlwZVxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9vbktleURvd24gKGUpIHtcbiAgICAgICAgdmFyIGNvZGUgPSAoZS5jb2RlID09PSBudWxsKSA/IGUud2hpY2ggOiBlLmNvZGU7XG5cbiAgICAgICAgLy8gaWYgY29kZS9lLndoaWNoIGlzIG51bGxcbiAgICAgICAgaWYgKGNvZGUgPT09IG51bGwpIHtcbiAgICAgICAgICAgIGNvZGUgPSAoZS5jaGFyQ29kZSA9PT0gbnVsbCkgPyBlLmtleUNvZGUgOiBlLmNoYXJDb2RlO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKChjb2RlID09PSAnRXNjYXBlJyB8fCBjb2RlID09PSAyNykgJiYgdGhpcy5fb3B0aW9ucy5leGl0T25Fc2MgPT09IHRydWUpIHtcbiAgICAgICAgICAgIC8vZXNjYXBlIGtleSBwcmVzc2VkLCBleGl0IHRoZSBpbnRyb1xuICAgICAgICAgICAgLy9jaGVjayBpZiBleGl0IGNhbGxiYWNrIGlzIGRlZmluZWRcbiAgICAgICAgICAgIF9leGl0SW50cm8uY2FsbCh0aGlzLCB0aGlzLl90YXJnZXRFbGVtZW50KTtcbiAgICAgICAgfSBlbHNlIGlmIChjb2RlID09PSAnQXJyb3dMZWZ0JyB8fCBjb2RlID09PSAzNykge1xuICAgICAgICAgICAgLy9sZWZ0IGFycm93XG4gICAgICAgICAgICBfcHJldmlvdXNTdGVwLmNhbGwodGhpcyk7XG4gICAgICAgIH0gZWxzZSBpZiAoY29kZSA9PT0gJ0Fycm93UmlnaHQnIHx8IGNvZGUgPT09IDM5KSB7XG4gICAgICAgICAgICAvL3JpZ2h0IGFycm93XG4gICAgICAgICAgICBfbmV4dFN0ZXAuY2FsbCh0aGlzKTtcbiAgICAgICAgfSBlbHNlIGlmIChjb2RlID09PSAnRW50ZXInIHx8IGNvZGUgPT09IDEzKSB7XG4gICAgICAgICAgICAvL3NyY0VsZW1lbnQgPT09IGllXG4gICAgICAgICAgICB2YXIgdGFyZ2V0ID0gZS50YXJnZXQgfHwgZS5zcmNFbGVtZW50O1xuICAgICAgICAgICAgaWYgKHRhcmdldCAmJiB0YXJnZXQuY2xhc3NOYW1lLm1hdGNoKCdpbnRyb2pzLXByZXZidXR0b24nKSkge1xuICAgICAgICAgICAgICAgIC8vdXNlciBoaXQgZW50ZXIgd2hpbGUgZm9jdXNpbmcgb24gcHJldmlvdXMgYnV0dG9uXG4gICAgICAgICAgICAgICAgX3ByZXZpb3VzU3RlcC5jYWxsKHRoaXMpO1xuICAgICAgICAgICAgfSBlbHNlIGlmICh0YXJnZXQgJiYgdGFyZ2V0LmNsYXNzTmFtZS5tYXRjaCgnaW50cm9qcy1za2lwYnV0dG9uJykpIHtcbiAgICAgICAgICAgICAgICAvL3VzZXIgaGl0IGVudGVyIHdoaWxlIGZvY3VzaW5nIG9uIHNraXAgYnV0dG9uXG4gICAgICAgICAgICAgICAgaWYgKHRoaXMuX2ludHJvSXRlbXMubGVuZ3RoIC0gMSA9PT0gdGhpcy5fY3VycmVudFN0ZXAgJiYgdHlwZW9mICh0aGlzLl9pbnRyb0NvbXBsZXRlQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuX2ludHJvQ29tcGxldGVDYWxsYmFjay5jYWxsKHRoaXMpO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIF9leGl0SW50cm8uY2FsbCh0aGlzLCB0aGlzLl90YXJnZXRFbGVtZW50KTtcbiAgICAgICAgICAgIH0gZWxzZSBpZiAodGFyZ2V0ICYmIHRhcmdldC5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RlcG51bWJlcicpKSB7XG4gICAgICAgICAgICAgICAgLy8gdXNlciBoaXQgZW50ZXIgd2hpbGUgZm9jdXNpbmcgb24gc3RlcCBidWxsZXRcbiAgICAgICAgICAgICAgICB0YXJnZXQuY2xpY2soKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgLy9kZWZhdWx0IGJlaGF2aW9yIGZvciByZXNwb25kaW5nIHRvIGVudGVyXG4gICAgICAgICAgICAgICAgX25leHRTdGVwLmNhbGwodGhpcyk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vcHJldmVudCBkZWZhdWx0IGJlaGF2aW91ciBvbiBoaXR0aW5nIEVudGVyLCB0byBwcmV2ZW50IHN0ZXBzIGJlaW5nIHNraXBwZWQgaW4gc29tZSBicm93c2Vyc1xuICAgICAgICAgICAgaWYoZS5wcmV2ZW50RGVmYXVsdCkge1xuICAgICAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgZS5yZXR1cm5WYWx1ZSA9IGZhbHNlO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuXG4gICAgLypcbiAgICAgICogbWFrZXMgYSBjb3B5IG9mIHRoZSBvYmplY3RcbiAgICAgICogQGFwaSBwcml2YXRlXG4gICAgICAqIEBtZXRob2QgX2Nsb25lT2JqZWN0XG4gICAgICovXG4gICAgZnVuY3Rpb24gX2Nsb25lT2JqZWN0KG9iamVjdCkge1xuICAgICAgICBpZiAob2JqZWN0ID09PSBudWxsIHx8IHR5cGVvZiAob2JqZWN0KSAhPT0gJ29iamVjdCcgfHwgdHlwZW9mIChvYmplY3Qubm9kZVR5cGUpICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgcmV0dXJuIG9iamVjdDtcbiAgICAgICAgfVxuICAgICAgICB2YXIgdGVtcCA9IHt9O1xuICAgICAgICBmb3IgKHZhciBrZXkgaW4gb2JqZWN0KSB7XG4gICAgICAgICAgICBpZiAodHlwZW9mKHdpbmRvdy5qUXVlcnkpICE9PSAndW5kZWZpbmVkJyAmJiBvYmplY3Rba2V5XSBpbnN0YW5jZW9mIHdpbmRvdy5qUXVlcnkpIHtcbiAgICAgICAgICAgICAgICB0ZW1wW2tleV0gPSBvYmplY3Rba2V5XTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdGVtcFtrZXldID0gX2Nsb25lT2JqZWN0KG9iamVjdFtrZXldKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdGVtcDtcbiAgICB9XG4gICAgLyoqXG4gICAgICogR28gdG8gc3BlY2lmaWMgc3RlcCBvZiBpbnRyb2R1Y3Rpb25cbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX2dvVG9TdGVwXG4gICAgICovXG4gICAgZnVuY3Rpb24gX2dvVG9TdGVwKHN0ZXApIHtcbiAgICAgICAgLy9iZWNhdXNlIHN0ZXBzIHN0YXJ0cyB3aXRoIHplcm9cbiAgICAgICAgdGhpcy5fY3VycmVudFN0ZXAgPSBzdGVwIC0gMjtcbiAgICAgICAgaWYgKHR5cGVvZiAodGhpcy5faW50cm9JdGVtcykgIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgICAgICBfbmV4dFN0ZXAuY2FsbCh0aGlzKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEdvIHRvIHRoZSBzcGVjaWZpYyBzdGVwIG9mIGludHJvZHVjdGlvbiB3aXRoIHRoZSBleHBsaWNpdCBbZGF0YS1zdGVwXSBudW1iZXJcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX2dvVG9TdGVwTnVtYmVyXG4gICAgICovXG4gICAgZnVuY3Rpb24gX2dvVG9TdGVwTnVtYmVyKHN0ZXApIHtcbiAgICAgICAgdGhpcy5fY3VycmVudFN0ZXBOdW1iZXIgPSBzdGVwO1xuICAgICAgICBpZiAodHlwZW9mICh0aGlzLl9pbnRyb0l0ZW1zKSAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgICAgIF9uZXh0U3RlcC5jYWxsKHRoaXMpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogR28gdG8gbmV4dCBzdGVwIG9uIGludHJvXG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9uZXh0U3RlcFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9uZXh0U3RlcCgpIHtcbiAgICAgICAgdGhpcy5fZGlyZWN0aW9uID0gJ2ZvcndhcmQnO1xuXG4gICAgICAgIGlmICh0eXBlb2YgKHRoaXMuX2N1cnJlbnRTdGVwTnVtYmVyKSAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgICAgIF9mb3JFYWNoKHRoaXMuX2ludHJvSXRlbXMsIGZ1bmN0aW9uIChpdGVtLCBpKSB7XG4gICAgICAgICAgICAgICAgaWYoIGl0ZW0uc3RlcCA9PT0gdGhpcy5fY3VycmVudFN0ZXBOdW1iZXIgKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuX2N1cnJlbnRTdGVwID0gaSAtIDE7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuX2N1cnJlbnRTdGVwTnVtYmVyID0gdW5kZWZpbmVkO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0uYmluZCh0aGlzKSk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAodHlwZW9mICh0aGlzLl9jdXJyZW50U3RlcCkgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgICAgICB0aGlzLl9jdXJyZW50U3RlcCA9IDA7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICArK3RoaXMuX2N1cnJlbnRTdGVwO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIG5leHRTdGVwID0gdGhpcy5faW50cm9JdGVtc1t0aGlzLl9jdXJyZW50U3RlcF07XG4gICAgICAgIHZhciBjb250aW51ZVN0ZXAgPSB0cnVlO1xuXG4gICAgICAgIGlmICh0eXBlb2YgKHRoaXMuX2ludHJvQmVmb3JlQ2hhbmdlQ2FsbGJhY2spICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgY29udGludWVTdGVwID0gdGhpcy5faW50cm9CZWZvcmVDaGFuZ2VDYWxsYmFjay5jYWxsKHRoaXMsIG5leHRTdGVwLmVsZW1lbnQpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gaWYgYG9uYmVmb3JlY2hhbmdlYCByZXR1cm5lZCBgZmFsc2VgLCBzdG9wIGRpc3BsYXlpbmcgdGhlIGVsZW1lbnRcbiAgICAgICAgaWYgKGNvbnRpbnVlU3RlcCA9PT0gZmFsc2UpIHtcbiAgICAgICAgICAgIC0tdGhpcy5fY3VycmVudFN0ZXA7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoKHRoaXMuX2ludHJvSXRlbXMubGVuZ3RoKSA8PSB0aGlzLl9jdXJyZW50U3RlcCkge1xuICAgICAgICAgICAgLy9lbmQgb2YgdGhlIGludHJvXG4gICAgICAgICAgICAvL2NoZWNrIGlmIGFueSBjYWxsYmFjayBpcyBkZWZpbmVkXG4gICAgICAgICAgICBpZiAodHlwZW9mICh0aGlzLl9pbnRyb0NvbXBsZXRlQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faW50cm9Db21wbGV0ZUNhbGxiYWNrLmNhbGwodGhpcyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBfZXhpdEludHJvLmNhbGwodGhpcywgdGhpcy5fdGFyZ2V0RWxlbWVudCk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICBfc2hvd0VsZW1lbnQuY2FsbCh0aGlzLCBuZXh0U3RlcCk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogR28gdG8gcHJldmlvdXMgc3RlcCBvbiBpbnRyb1xuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfcHJldmlvdXNTdGVwXG4gICAgICovXG4gICAgZnVuY3Rpb24gX3ByZXZpb3VzU3RlcCgpIHtcbiAgICAgICAgdGhpcy5fZGlyZWN0aW9uID0gJ2JhY2t3YXJkJztcblxuICAgICAgICBpZiAodGhpcy5fY3VycmVudFN0ZXAgPT09IDApIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuXG4gICAgICAgIC0tdGhpcy5fY3VycmVudFN0ZXA7XG5cbiAgICAgICAgdmFyIG5leHRTdGVwID0gdGhpcy5faW50cm9JdGVtc1t0aGlzLl9jdXJyZW50U3RlcF07XG4gICAgICAgIHZhciBjb250aW51ZVN0ZXAgPSB0cnVlO1xuXG4gICAgICAgIGlmICh0eXBlb2YgKHRoaXMuX2ludHJvQmVmb3JlQ2hhbmdlQ2FsbGJhY2spICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgY29udGludWVTdGVwID0gdGhpcy5faW50cm9CZWZvcmVDaGFuZ2VDYWxsYmFjay5jYWxsKHRoaXMsIG5leHRTdGVwLmVsZW1lbnQpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gaWYgYG9uYmVmb3JlY2hhbmdlYCByZXR1cm5lZCBgZmFsc2VgLCBzdG9wIGRpc3BsYXlpbmcgdGhlIGVsZW1lbnRcbiAgICAgICAgaWYgKGNvbnRpbnVlU3RlcCA9PT0gZmFsc2UpIHtcbiAgICAgICAgICAgICsrdGhpcy5fY3VycmVudFN0ZXA7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cblxuICAgICAgICBfc2hvd0VsZW1lbnQuY2FsbCh0aGlzLCBuZXh0U3RlcCk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogVXBkYXRlIHBsYWNlbWVudCBvZiB0aGUgaW50cm8gb2JqZWN0cyBvbiB0aGUgc2NyZWVuXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICovXG4gICAgZnVuY3Rpb24gX3JlZnJlc2goKSB7XG4gICAgICAgIC8vIHJlLWFsaWduIGludHJvc1xuICAgICAgICBfc2V0SGVscGVyTGF5ZXJQb3NpdGlvbi5jYWxsKHRoaXMsIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLWhlbHBlckxheWVyJykpO1xuICAgICAgICBfc2V0SGVscGVyTGF5ZXJQb3NpdGlvbi5jYWxsKHRoaXMsIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLXRvb2x0aXBSZWZlcmVuY2VMYXllcicpKTtcbiAgICAgICAgX3NldEhlbHBlckxheWVyUG9zaXRpb24uY2FsbCh0aGlzLCBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1kaXNhYmxlSW50ZXJhY3Rpb24nKSk7XG5cbiAgICAgICAgLy8gcmUtYWxpZ24gdG9vbHRpcFxuICAgICAgICBpZih0aGlzLl9jdXJyZW50U3RlcCAhPT0gdW5kZWZpbmVkICYmIHRoaXMuX2N1cnJlbnRTdGVwICE9PSBudWxsKSB7XG4gICAgICAgICAgICB2YXIgb2xkSGVscGVyTnVtYmVyTGF5ZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1oZWxwZXJOdW1iZXJMYXllcicpLFxuICAgICAgICAgICAgICAgIG9sZEFycm93TGF5ZXIgICAgICAgID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLmludHJvanMtYXJyb3cnKSxcbiAgICAgICAgICAgICAgICBvbGR0b29sdGlwQ29udGFpbmVyICA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLXRvb2x0aXAnKTtcbiAgICAgICAgICAgIF9wbGFjZVRvb2x0aXAuY2FsbCh0aGlzLCB0aGlzLl9pbnRyb0l0ZW1zW3RoaXMuX2N1cnJlbnRTdGVwXS5lbGVtZW50LCBvbGR0b29sdGlwQ29udGFpbmVyLCBvbGRBcnJvd0xheWVyLCBvbGRIZWxwZXJOdW1iZXJMYXllcik7XG4gICAgICAgIH1cblxuICAgICAgICAvL3JlLWFsaWduIGhpbnRzXG4gICAgICAgIF9yZUFsaWduSGludHMuY2FsbCh0aGlzKTtcbiAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogRXhpdCBmcm9tIGludHJvXG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9leGl0SW50cm9cbiAgICAgKiBAcGFyYW0ge09iamVjdH0gdGFyZ2V0RWxlbWVudFxuICAgICAqIEBwYXJhbSB7Qm9vbGVhbn0gZm9yY2UgLSBTZXR0aW5nIHRvIGB0cnVlYCB3aWxsIHNraXAgdGhlIHJlc3VsdCBvZiBiZWZvcmVFeGl0IGNhbGxiYWNrXG4gICAgICovXG4gICAgZnVuY3Rpb24gX2V4aXRJbnRybyh0YXJnZXRFbGVtZW50LCBmb3JjZSkge1xuICAgICAgICB2YXIgY29udGludWVFeGl0ID0gdHJ1ZTtcblxuICAgICAgICAvL2NoZWNrIGlmIGFueSBjYWxsYmFjayBpcyBkZWZpbmVkXG4gICAgICAgIGlmICh0aGlzLl9pbnRyb0V4aXRDYWxsYmFjayAhPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICB0aGlzLl9pbnRyb0V4aXRDYWxsYmFjay5jYWxsKHRoaXMpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gY2FsbGluZyBvbmJlZm9yZWV4aXQgY2FsbGJhY2tcbiAgICAgICAgLy9cbiAgICAgICAgLy8gSWYgdGhpcyBjYWxsYmFjayByZXR1cm4gYGZhbHNlYCwgaXQgd291bGQgaGFsdCB0aGUgcHJvY2Vzc1xuICAgICAgICBpZiAodGhpcy5faW50cm9CZWZvcmVFeGl0Q2FsbGJhY2sgIT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgY29udGludWVFeGl0ID0gdGhpcy5faW50cm9CZWZvcmVFeGl0Q2FsbGJhY2suY2FsbCh0aGlzKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vIHNraXAgdGhpcyBjaGVjayBpZiBgZm9yY2VgIHBhcmFtZXRlciBpcyBgdHJ1ZWBcbiAgICAgICAgLy8gb3RoZXJ3aXNlLCBpZiBgb25iZWZvcmVleGl0YCByZXR1cm5lZCBgZmFsc2VgLCBkb24ndCBleGl0IHRoZSBpbnRyb1xuICAgICAgICBpZiAoIWZvcmNlICYmIGNvbnRpbnVlRXhpdCA9PT0gZmFsc2UpIHJldHVybjtcblxuICAgICAgICAvL3JlbW92ZSBvdmVybGF5IGxheWVycyBmcm9tIHRoZSBwYWdlXG4gICAgICAgIHZhciBvdmVybGF5TGF5ZXJzID0gdGFyZ2V0RWxlbWVudC5xdWVyeVNlbGVjdG9yQWxsKCcuaW50cm9qcy1vdmVybGF5Jyk7XG5cbiAgICAgICAgaWYgKG92ZXJsYXlMYXllcnMgJiYgb3ZlcmxheUxheWVycy5sZW5ndGgpIHtcbiAgICAgICAgICAgIF9mb3JFYWNoKG92ZXJsYXlMYXllcnMsIGZ1bmN0aW9uIChvdmVybGF5TGF5ZXIpIHtcbiAgICAgICAgICAgICAgICBvdmVybGF5TGF5ZXIuc3R5bGUub3BhY2l0eSA9IDA7XG4gICAgICAgICAgICAgICAgd2luZG93LnNldFRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICBpZiAodGhpcy5wYXJlbnROb2RlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnBhcmVudE5vZGUucmVtb3ZlQ2hpbGQodGhpcyk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9LmJpbmQob3ZlcmxheUxheWVyKSwgNTAwKTtcbiAgICAgICAgICAgIH0uYmluZCh0aGlzKSk7XG4gICAgICAgIH1cblxuICAgICAgICAvL3JlbW92ZSBhbGwgaGVscGVyIGxheWVyc1xuICAgICAgICB2YXIgaGVscGVyTGF5ZXIgPSB0YXJnZXRFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLWhlbHBlckxheWVyJyk7XG4gICAgICAgIGlmIChoZWxwZXJMYXllcikge1xuICAgICAgICAgICAgaGVscGVyTGF5ZXIucGFyZW50Tm9kZS5yZW1vdmVDaGlsZChoZWxwZXJMYXllcik7XG4gICAgICAgIH1cblxuICAgICAgICB2YXIgcmVmZXJlbmNlTGF5ZXIgPSB0YXJnZXRFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLXRvb2x0aXBSZWZlcmVuY2VMYXllcicpO1xuICAgICAgICBpZiAocmVmZXJlbmNlTGF5ZXIpIHtcbiAgICAgICAgICAgIHJlZmVyZW5jZUxheWVyLnBhcmVudE5vZGUucmVtb3ZlQ2hpbGQocmVmZXJlbmNlTGF5ZXIpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy9yZW1vdmUgZGlzYWJsZUludGVyYWN0aW9uTGF5ZXJcbiAgICAgICAgdmFyIGRpc2FibGVJbnRlcmFjdGlvbkxheWVyID0gdGFyZ2V0RWxlbWVudC5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1kaXNhYmxlSW50ZXJhY3Rpb24nKTtcbiAgICAgICAgaWYgKGRpc2FibGVJbnRlcmFjdGlvbkxheWVyKSB7XG4gICAgICAgICAgICBkaXNhYmxlSW50ZXJhY3Rpb25MYXllci5wYXJlbnROb2RlLnJlbW92ZUNoaWxkKGRpc2FibGVJbnRlcmFjdGlvbkxheWVyKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vcmVtb3ZlIGludHJvIGZsb2F0aW5nIGVsZW1lbnRcbiAgICAgICAgdmFyIGZsb2F0aW5nRWxlbWVudCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzRmxvYXRpbmdFbGVtZW50Jyk7XG4gICAgICAgIGlmIChmbG9hdGluZ0VsZW1lbnQpIHtcbiAgICAgICAgICAgIGZsb2F0aW5nRWxlbWVudC5wYXJlbnROb2RlLnJlbW92ZUNoaWxkKGZsb2F0aW5nRWxlbWVudCk7XG4gICAgICAgIH1cblxuICAgICAgICBfcmVtb3ZlU2hvd0VsZW1lbnQoKTtcblxuICAgICAgICAvL3JlbW92ZSBgaW50cm9qcy1maXhQYXJlbnRgIGNsYXNzIGZyb20gdGhlIGVsZW1lbnRzXG4gICAgICAgIHZhciBmaXhQYXJlbnRzID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnLmludHJvanMtZml4UGFyZW50Jyk7XG4gICAgICAgIF9mb3JFYWNoKGZpeFBhcmVudHMsIGZ1bmN0aW9uIChwYXJlbnQpIHtcbiAgICAgICAgICAgIF9yZW1vdmVDbGFzcyhwYXJlbnQsIC9pbnRyb2pzLWZpeFBhcmVudC9nKTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgLy9jbGVhbiBsaXN0ZW5lcnNcbiAgICAgICAgRE9NRXZlbnQub2ZmKHdpbmRvdywgJ2tleWRvd24nLCBfb25LZXlEb3duLCB0aGlzLCB0cnVlKTtcbiAgICAgICAgRE9NRXZlbnQub2ZmKHdpbmRvdywgJ3Jlc2l6ZScsIF9vblJlc2l6ZSwgdGhpcywgdHJ1ZSk7XG5cblxuXG4gICAgICAgIC8vc2V0IHRoZSBzdGVwIHRvIHplcm9cbiAgICAgICAgdGhpcy5fY3VycmVudFN0ZXAgPSB1bmRlZmluZWQ7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogUmVuZGVyIHRvb2x0aXAgYm94IGluIHRoZSBwYWdlXG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9wbGFjZVRvb2x0aXBcbiAgICAgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSB0YXJnZXRFbGVtZW50XG4gICAgICogQHBhcmFtIHtIVE1MRWxlbWVudH0gdG9vbHRpcExheWVyXG4gICAgICogQHBhcmFtIHtIVE1MRWxlbWVudH0gYXJyb3dMYXllclxuICAgICAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGhlbHBlck51bWJlckxheWVyXG4gICAgICogQHBhcmFtIHtCb29sZWFufSBoaW50TW9kZVxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9wbGFjZVRvb2x0aXAodGFyZ2V0RWxlbWVudCwgdG9vbHRpcExheWVyLCBhcnJvd0xheWVyLCBoZWxwZXJOdW1iZXJMYXllciwgaGludE1vZGUpIHtcbiAgICAgICAgdmFyIHRvb2x0aXBDc3NDbGFzcyA9ICcnLFxuICAgICAgICAgICAgY3VycmVudFN0ZXBPYmosXG4gICAgICAgICAgICB0b29sdGlwT2Zmc2V0LFxuICAgICAgICAgICAgdGFyZ2V0T2Zmc2V0LFxuICAgICAgICAgICAgd2luZG93U2l6ZSxcbiAgICAgICAgICAgIGN1cnJlbnRUb29sdGlwUG9zaXRpb247XG5cbiAgICAgICAgaGludE1vZGUgPSBoaW50TW9kZSB8fCBmYWxzZTtcblxuICAgICAgICAvL3Jlc2V0IHRoZSBvbGQgc3R5bGVcbiAgICAgICAgdG9vbHRpcExheWVyLnN0eWxlLnRvcCAgICAgICAgPSBudWxsO1xuICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUucmlnaHQgICAgICA9IG51bGw7XG4gICAgICAgIHRvb2x0aXBMYXllci5zdHlsZS5ib3R0b20gICAgID0gbnVsbDtcbiAgICAgICAgdG9vbHRpcExheWVyLnN0eWxlLmxlZnQgICAgICAgPSBudWxsO1xuICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUubWFyZ2luTGVmdCA9IG51bGw7XG4gICAgICAgIHRvb2x0aXBMYXllci5zdHlsZS5tYXJnaW5Ub3AgID0gbnVsbDtcblxuICAgICAgICBhcnJvd0xheWVyLnN0eWxlLmRpc3BsYXkgPSAnaW5oZXJpdCc7XG5cbiAgICAgICAgaWYgKHR5cGVvZihoZWxwZXJOdW1iZXJMYXllcikgIT09ICd1bmRlZmluZWQnICYmIGhlbHBlck51bWJlckxheWVyICE9PSBudWxsKSB7XG4gICAgICAgICAgICBoZWxwZXJOdW1iZXJMYXllci5zdHlsZS50b3AgID0gbnVsbDtcbiAgICAgICAgICAgIGhlbHBlck51bWJlckxheWVyLnN0eWxlLmxlZnQgPSBudWxsO1xuICAgICAgICB9XG5cbiAgICAgICAgLy9wcmV2ZW50IGVycm9yIHdoZW4gYHRoaXMuX2N1cnJlbnRTdGVwYCBpcyB1bmRlZmluZWRcbiAgICAgICAgaWYgKCF0aGlzLl9pbnRyb0l0ZW1zW3RoaXMuX2N1cnJlbnRTdGVwXSkgcmV0dXJuO1xuXG4gICAgICAgIC8vaWYgd2UgaGF2ZSBhIGN1c3RvbSBjc3MgY2xhc3MgZm9yIGVhY2ggc3RlcFxuICAgICAgICBjdXJyZW50U3RlcE9iaiA9IHRoaXMuX2ludHJvSXRlbXNbdGhpcy5fY3VycmVudFN0ZXBdO1xuICAgICAgICBpZiAodHlwZW9mIChjdXJyZW50U3RlcE9iai50b29sdGlwQ2xhc3MpID09PSAnc3RyaW5nJykge1xuICAgICAgICAgICAgdG9vbHRpcENzc0NsYXNzID0gY3VycmVudFN0ZXBPYmoudG9vbHRpcENsYXNzO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdG9vbHRpcENzc0NsYXNzID0gdGhpcy5fb3B0aW9ucy50b29sdGlwQ2xhc3M7XG4gICAgICAgIH1cblxuICAgICAgICB0b29sdGlwTGF5ZXIuY2xhc3NOYW1lID0gKCdpbnRyb2pzLXRvb2x0aXAgJyArIHRvb2x0aXBDc3NDbGFzcykucmVwbGFjZSgvXlxccyt8XFxzKyQvZywgJycpO1xuICAgICAgICB0b29sdGlwTGF5ZXIuc2V0QXR0cmlidXRlKCdyb2xlJywgJ2RpYWxvZycpO1xuXG4gICAgICAgIGN1cnJlbnRUb29sdGlwUG9zaXRpb24gPSB0aGlzLl9pbnRyb0l0ZW1zW3RoaXMuX2N1cnJlbnRTdGVwXS5wb3NpdGlvbjtcblxuICAgICAgICAvLyBGbG9hdGluZyBpcyBhbHdheXMgdmFsaWQsIG5vIHBvaW50IGluIGNhbGN1bGF0aW5nXG4gICAgICAgIGlmIChjdXJyZW50VG9vbHRpcFBvc2l0aW9uICE9PSBcImZsb2F0aW5nXCIpIHtcbiAgICAgICAgICAgIGN1cnJlbnRUb29sdGlwUG9zaXRpb24gPSBfZGV0ZXJtaW5lQXV0b1Bvc2l0aW9uLmNhbGwodGhpcywgdGFyZ2V0RWxlbWVudCwgdG9vbHRpcExheWVyLCBjdXJyZW50VG9vbHRpcFBvc2l0aW9uKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciB0b29sdGlwTGF5ZXJTdHlsZUxlZnQ7XG4gICAgICAgIHRhcmdldE9mZnNldCAgPSBfZ2V0T2Zmc2V0KHRhcmdldEVsZW1lbnQpO1xuICAgICAgICB0b29sdGlwT2Zmc2V0ID0gX2dldE9mZnNldCh0b29sdGlwTGF5ZXIpO1xuICAgICAgICB3aW5kb3dTaXplICAgID0gX2dldFdpblNpemUoKTtcblxuICAgICAgICBfYWRkQ2xhc3ModG9vbHRpcExheWVyLCAnaW50cm9qcy0nICsgY3VycmVudFRvb2x0aXBQb3NpdGlvbik7XG5cbiAgICAgICAgc3dpdGNoIChjdXJyZW50VG9vbHRpcFBvc2l0aW9uKSB7XG4gICAgICAgICAgICBjYXNlICd0b3AtcmlnaHQtYWxpZ25lZCc6XG4gICAgICAgICAgICAgICAgYXJyb3dMYXllci5jbGFzc05hbWUgICAgICA9ICdpbnRyb2pzLWFycm93IGJvdHRvbS1yaWdodCc7XG5cbiAgICAgICAgICAgICAgICB2YXIgdG9vbHRpcExheWVyU3R5bGVSaWdodCA9IDA7XG4gICAgICAgICAgICAgICAgX2NoZWNrTGVmdCh0YXJnZXRPZmZzZXQsIHRvb2x0aXBMYXllclN0eWxlUmlnaHQsIHRvb2x0aXBPZmZzZXQsIHRvb2x0aXBMYXllcik7XG4gICAgICAgICAgICAgICAgdG9vbHRpcExheWVyLnN0eWxlLmJvdHRvbSAgICA9ICh0YXJnZXRPZmZzZXQuaGVpZ2h0ICsgIDIwKSArICdweCc7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAgIGNhc2UgJ3RvcC1taWRkbGUtYWxpZ25lZCc6XG4gICAgICAgICAgICAgICAgYXJyb3dMYXllci5jbGFzc05hbWUgICAgICA9ICdpbnRyb2pzLWFycm93IGJvdHRvbS1taWRkbGUnO1xuXG4gICAgICAgICAgICAgICAgdmFyIHRvb2x0aXBMYXllclN0eWxlTGVmdFJpZ2h0ID0gdGFyZ2V0T2Zmc2V0LndpZHRoIC8gMiAtIHRvb2x0aXBPZmZzZXQud2lkdGggLyAyO1xuXG4gICAgICAgICAgICAgICAgLy8gYSBmaXggZm9yIG1pZGRsZSBhbGlnbmVkIGhpbnRzXG4gICAgICAgICAgICAgICAgaWYgKGhpbnRNb2RlKSB7XG4gICAgICAgICAgICAgICAgICAgIHRvb2x0aXBMYXllclN0eWxlTGVmdFJpZ2h0ICs9IDU7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKF9jaGVja0xlZnQodGFyZ2V0T2Zmc2V0LCB0b29sdGlwTGF5ZXJTdHlsZUxlZnRSaWdodCwgdG9vbHRpcE9mZnNldCwgdG9vbHRpcExheWVyKSkge1xuICAgICAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUucmlnaHQgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICBfY2hlY2tSaWdodCh0YXJnZXRPZmZzZXQsIHRvb2x0aXBMYXllclN0eWxlTGVmdFJpZ2h0LCB0b29sdGlwT2Zmc2V0LCB3aW5kb3dTaXplLCB0b29sdGlwTGF5ZXIpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUuYm90dG9tID0gKHRhcmdldE9mZnNldC5oZWlnaHQgKyAyMCkgKyAncHgnO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuXG4gICAgICAgICAgICBjYXNlICd0b3AtbGVmdC1hbGlnbmVkJzpcbiAgICAgICAgICAgIC8vIHRvcC1sZWZ0LWFsaWduZWQgaXMgdGhlIHNhbWUgYXMgdGhlIGRlZmF1bHQgdG9wXG4gICAgICAgICAgICBjYXNlICd0b3AnOlxuICAgICAgICAgICAgICAgIGFycm93TGF5ZXIuY2xhc3NOYW1lID0gJ2ludHJvanMtYXJyb3cgYm90dG9tJztcblxuICAgICAgICAgICAgICAgIHRvb2x0aXBMYXllclN0eWxlTGVmdCA9IChoaW50TW9kZSkgPyAwIDogMTU7XG5cbiAgICAgICAgICAgICAgICBfY2hlY2tSaWdodCh0YXJnZXRPZmZzZXQsIHRvb2x0aXBMYXllclN0eWxlTGVmdCwgdG9vbHRpcE9mZnNldCwgd2luZG93U2l6ZSwgdG9vbHRpcExheWVyKTtcbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUuYm90dG9tID0gKHRhcmdldE9mZnNldC5oZWlnaHQgKyAgMjApICsgJ3B4JztcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ3JpZ2h0JzpcbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUubGVmdCA9ICh0YXJnZXRPZmZzZXQud2lkdGggKyAyMCkgKyAncHgnO1xuICAgICAgICAgICAgICAgIGlmICh0YXJnZXRPZmZzZXQudG9wICsgdG9vbHRpcE9mZnNldC5oZWlnaHQgPiB3aW5kb3dTaXplLmhlaWdodCAmJiAtMSA9PT0gdGFyZ2V0RWxlbWVudC5jbGFzc05hbWUuaW5kZXhPZignaW50cm9qcy1yZWxhdGl2ZVBvc2l0aW9uJykpIHtcbiAgICAgICAgICAgICAgICAgICAgLy8gSW4gdGhpcyBjYXNlLCByaWdodCB3b3VsZCBoYXZlIGZhbGxlbiBiZWxvdyB0aGUgYm90dG9tIG9mIHRoZSBzY3JlZW4uXG4gICAgICAgICAgICAgICAgICAgIC8vIE1vZGlmeSBzbyB0aGF0IHRoZSBib3R0b20gb2YgdGhlIHRvb2x0aXAgY29ubmVjdHMgd2l0aCB0aGUgdGFyZ2V0XG4gICAgICAgICAgICAgICAgICAgIGFycm93TGF5ZXIuY2xhc3NOYW1lID0gXCJpbnRyb2pzLWFycm93IGxlZnQtYm90dG9tXCI7XG4gICAgICAgICAgICAgICAgICAgIHRvb2x0aXBMYXllci5zdHlsZS50b3AgPSBcIi1cIiArICh0b29sdGlwT2Zmc2V0LmhlaWdodCAtIHRhcmdldE9mZnNldC5oZWlnaHQgLSAyMCkgKyBcInB4XCI7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgYXJyb3dMYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy1hcnJvdyBsZWZ0JztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlICdsZWZ0JzpcbiAgICAgICAgICAgICAgICBpZiAoIWhpbnRNb2RlICYmIHRoaXMuX29wdGlvbnMuc2hvd1N0ZXBOdW1iZXJzID09PSB0cnVlKSB7XG4gICAgICAgICAgICAgICAgICAgIHRvb2x0aXBMYXllci5zdHlsZS50b3AgPSAnMTVweCc7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKHRhcmdldE9mZnNldC50b3AgKyB0b29sdGlwT2Zmc2V0LmhlaWdodCA+IHdpbmRvd1NpemUuaGVpZ2h0KSB7XG4gICAgICAgICAgICAgICAgICAgIC8vIEluIHRoaXMgY2FzZSwgbGVmdCB3b3VsZCBoYXZlIGZhbGxlbiBiZWxvdyB0aGUgYm90dG9tIG9mIHRoZSBzY3JlZW4uXG4gICAgICAgICAgICAgICAgICAgIC8vIE1vZGlmeSBzbyB0aGF0IHRoZSBib3R0b20gb2YgdGhlIHRvb2x0aXAgY29ubmVjdHMgd2l0aCB0aGUgdGFyZ2V0XG4gICAgICAgICAgICAgICAgICAgIHRvb2x0aXBMYXllci5zdHlsZS50b3AgPSBcIi1cIiArICh0b29sdGlwT2Zmc2V0LmhlaWdodCAtIHRhcmdldE9mZnNldC5oZWlnaHQgLSAzMCkgKyBcInB4XCI7XG4gICAgICAgICAgICAgICAgICAgIGFycm93TGF5ZXIuY2xhc3NOYW1lID0gJ2ludHJvanMtYXJyb3cgcmlnaHQtYm90dG9tJztcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBhcnJvd0xheWVyLmNsYXNzTmFtZSA9ICdpbnRyb2pzLWFycm93IHJpZ2h0JztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgdG9vbHRpcExheWVyLnN0eWxlLnJpZ2h0ID0gKHRhcmdldE9mZnNldC53aWR0aCArIDIwKSArICdweCc7XG5cbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ2Zsb2F0aW5nJzpcbiAgICAgICAgICAgICAgICBhcnJvd0xheWVyLnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XG5cbiAgICAgICAgICAgICAgICAvL3dlIGhhdmUgdG8gYWRqdXN0IHRoZSB0b3AgYW5kIGxlZnQgb2YgbGF5ZXIgbWFudWFsbHkgZm9yIGludHJvIGl0ZW1zIHdpdGhvdXQgZWxlbWVudFxuICAgICAgICAgICAgICAgIHRvb2x0aXBMYXllci5zdHlsZS5sZWZ0ICAgPSAnNTAlJztcbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUudG9wICAgID0gJzUwJSc7XG4gICAgICAgICAgICAgICAgdG9vbHRpcExheWVyLnN0eWxlLm1hcmdpbkxlZnQgPSAnLScgKyAodG9vbHRpcE9mZnNldC53aWR0aCAvIDIpICArICdweCc7XG4gICAgICAgICAgICAgICAgdG9vbHRpcExheWVyLnN0eWxlLm1hcmdpblRvcCAgPSAnLScgKyAodG9vbHRpcE9mZnNldC5oZWlnaHQgLyAyKSArICdweCc7XG5cbiAgICAgICAgICAgICAgICBpZiAodHlwZW9mKGhlbHBlck51bWJlckxheWVyKSAhPT0gJ3VuZGVmaW5lZCcgJiYgaGVscGVyTnVtYmVyTGF5ZXIgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgaGVscGVyTnVtYmVyTGF5ZXIuc3R5bGUubGVmdCA9ICctJyArICgodG9vbHRpcE9mZnNldC53aWR0aCAvIDIpICsgMTgpICsgJ3B4JztcbiAgICAgICAgICAgICAgICAgICAgaGVscGVyTnVtYmVyTGF5ZXIuc3R5bGUudG9wICA9ICctJyArICgodG9vbHRpcE9mZnNldC5oZWlnaHQgLyAyKSArIDE4KSArICdweCc7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlICdib3R0b20tcmlnaHQtYWxpZ25lZCc6XG4gICAgICAgICAgICAgICAgYXJyb3dMYXllci5jbGFzc05hbWUgICAgICA9ICdpbnRyb2pzLWFycm93IHRvcC1yaWdodCc7XG5cbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXJTdHlsZVJpZ2h0ID0gMDtcbiAgICAgICAgICAgICAgICBfY2hlY2tMZWZ0KHRhcmdldE9mZnNldCwgdG9vbHRpcExheWVyU3R5bGVSaWdodCwgdG9vbHRpcE9mZnNldCwgdG9vbHRpcExheWVyKTtcbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUudG9wICAgID0gKHRhcmdldE9mZnNldC5oZWlnaHQgKyAgMjApICsgJ3B4JztcbiAgICAgICAgICAgICAgICBicmVhaztcblxuICAgICAgICAgICAgY2FzZSAnYm90dG9tLW1pZGRsZS1hbGlnbmVkJzpcbiAgICAgICAgICAgICAgICBhcnJvd0xheWVyLmNsYXNzTmFtZSAgICAgID0gJ2ludHJvanMtYXJyb3cgdG9wLW1pZGRsZSc7XG5cbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXJTdHlsZUxlZnRSaWdodCA9IHRhcmdldE9mZnNldC53aWR0aCAvIDIgLSB0b29sdGlwT2Zmc2V0LndpZHRoIC8gMjtcblxuICAgICAgICAgICAgICAgIC8vIGEgZml4IGZvciBtaWRkbGUgYWxpZ25lZCBoaW50c1xuICAgICAgICAgICAgICAgIGlmIChoaW50TW9kZSkge1xuICAgICAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXJTdHlsZUxlZnRSaWdodCArPSA1O1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGlmIChfY2hlY2tMZWZ0KHRhcmdldE9mZnNldCwgdG9vbHRpcExheWVyU3R5bGVMZWZ0UmlnaHQsIHRvb2x0aXBPZmZzZXQsIHRvb2x0aXBMYXllcikpIHtcbiAgICAgICAgICAgICAgICAgICAgdG9vbHRpcExheWVyLnN0eWxlLnJpZ2h0ID0gbnVsbDtcbiAgICAgICAgICAgICAgICAgICAgX2NoZWNrUmlnaHQodGFyZ2V0T2Zmc2V0LCB0b29sdGlwTGF5ZXJTdHlsZUxlZnRSaWdodCwgdG9vbHRpcE9mZnNldCwgd2luZG93U2l6ZSwgdG9vbHRpcExheWVyKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgdG9vbHRpcExheWVyLnN0eWxlLnRvcCA9ICh0YXJnZXRPZmZzZXQuaGVpZ2h0ICsgMjApICsgJ3B4JztcbiAgICAgICAgICAgICAgICBicmVhaztcblxuICAgICAgICAgICAgLy8gY2FzZSAnYm90dG9tLWxlZnQtYWxpZ25lZCc6XG4gICAgICAgICAgICAvLyBCb3R0b20tbGVmdC1hbGlnbmVkIGlzIHRoZSBzYW1lIGFzIHRoZSBkZWZhdWx0IGJvdHRvbVxuICAgICAgICAgICAgLy8gY2FzZSAnYm90dG9tJzpcbiAgICAgICAgICAgIC8vIEJvdHRvbSBnb2luZyB0byBmb2xsb3cgdGhlIGRlZmF1bHQgYmVoYXZpb3JcbiAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgYXJyb3dMYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy1hcnJvdyB0b3AnO1xuXG4gICAgICAgICAgICAgICAgdG9vbHRpcExheWVyU3R5bGVMZWZ0ID0gMDtcbiAgICAgICAgICAgICAgICBfY2hlY2tSaWdodCh0YXJnZXRPZmZzZXQsIHRvb2x0aXBMYXllclN0eWxlTGVmdCwgdG9vbHRpcE9mZnNldCwgd2luZG93U2l6ZSwgdG9vbHRpcExheWVyKTtcbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUudG9wICAgID0gKHRhcmdldE9mZnNldC5oZWlnaHQgKyAgMjApICsgJ3B4JztcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFNldCB0b29sdGlwIGxlZnQgc28gaXQgZG9lc24ndCBnbyBvZmYgdGhlIHJpZ2h0IHNpZGUgb2YgdGhlIHdpbmRvd1xuICAgICAqXG4gICAgICogQHJldHVybiBib29sZWFuIHRydWUsIGlmIHRvb2x0aXBMYXllclN0eWxlTGVmdCBpcyBvay4gIGZhbHNlLCBvdGhlcndpc2UuXG4gICAgICovXG4gICAgZnVuY3Rpb24gX2NoZWNrUmlnaHQodGFyZ2V0T2Zmc2V0LCB0b29sdGlwTGF5ZXJTdHlsZUxlZnQsIHRvb2x0aXBPZmZzZXQsIHdpbmRvd1NpemUsIHRvb2x0aXBMYXllcikge1xuICAgICAgICBpZiAodGFyZ2V0T2Zmc2V0LmxlZnQgKyB0b29sdGlwTGF5ZXJTdHlsZUxlZnQgKyB0b29sdGlwT2Zmc2V0LndpZHRoID4gd2luZG93U2l6ZS53aWR0aCkge1xuICAgICAgICAgICAgLy8gb2ZmIHRoZSByaWdodCBzaWRlIG9mIHRoZSB3aW5kb3dcbiAgICAgICAgICAgIHRvb2x0aXBMYXllci5zdHlsZS5sZWZ0ID0gKHdpbmRvd1NpemUud2lkdGggLSB0b29sdGlwT2Zmc2V0LndpZHRoIC0gdGFyZ2V0T2Zmc2V0LmxlZnQpICsgJ3B4JztcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUubGVmdCA9IHRvb2x0aXBMYXllclN0eWxlTGVmdCArICdweCc7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFNldCB0b29sdGlwIHJpZ2h0IHNvIGl0IGRvZXNuJ3QgZ28gb2ZmIHRoZSBsZWZ0IHNpZGUgb2YgdGhlIHdpbmRvd1xuICAgICAqXG4gICAgICogQHJldHVybiBib29sZWFuIHRydWUsIGlmIHRvb2x0aXBMYXllclN0eWxlUmlnaHQgaXMgb2suICBmYWxzZSwgb3RoZXJ3aXNlLlxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9jaGVja0xlZnQodGFyZ2V0T2Zmc2V0LCB0b29sdGlwTGF5ZXJTdHlsZVJpZ2h0LCB0b29sdGlwT2Zmc2V0LCB0b29sdGlwTGF5ZXIpIHtcbiAgICAgICAgaWYgKHRhcmdldE9mZnNldC5sZWZ0ICsgdGFyZ2V0T2Zmc2V0LndpZHRoIC0gdG9vbHRpcExheWVyU3R5bGVSaWdodCAtIHRvb2x0aXBPZmZzZXQud2lkdGggPCAwKSB7XG4gICAgICAgICAgICAvLyBvZmYgdGhlIGxlZnQgc2lkZSBvZiB0aGUgd2luZG93XG4gICAgICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUubGVmdCA9ICgtdGFyZ2V0T2Zmc2V0LmxlZnQpICsgJ3B4JztcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICB0b29sdGlwTGF5ZXIuc3R5bGUucmlnaHQgPSB0b29sdGlwTGF5ZXJTdHlsZVJpZ2h0ICsgJ3B4JztcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogRGV0ZXJtaW5lcyB0aGUgcG9zaXRpb24gb2YgdGhlIHRvb2x0aXAgYmFzZWQgb24gdGhlIHBvc2l0aW9uIHByZWNlZGVuY2UgYW5kIGF2YWlsYWJpbGl0eVxuICAgICAqIG9mIHNjcmVlbiBzcGFjZS5cbiAgICAgKlxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSAgICB0YXJnZXRFbGVtZW50XG4gICAgICogQHBhcmFtIHtPYmplY3R9ICAgIHRvb2x0aXBMYXllclxuICAgICAqIEBwYXJhbSB7U3RyaW5nfSAgICBkZXNpcmVkVG9vbHRpcFBvc2l0aW9uXG4gICAgICogQHJldHVybiB7U3RyaW5nfSAgIGNhbGN1bGF0ZWRQb3NpdGlvblxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9kZXRlcm1pbmVBdXRvUG9zaXRpb24odGFyZ2V0RWxlbWVudCwgdG9vbHRpcExheWVyLCBkZXNpcmVkVG9vbHRpcFBvc2l0aW9uKSB7XG5cbiAgICAgICAgLy8gVGFrZSBhIGNsb25lIG9mIHBvc2l0aW9uIHByZWNlZGVuY2UuIFRoZXNlIHdpbGwgYmUgdGhlIGF2YWlsYWJsZVxuICAgICAgICB2YXIgcG9zc2libGVQb3NpdGlvbnMgPSB0aGlzLl9vcHRpb25zLnBvc2l0aW9uUHJlY2VkZW5jZS5zbGljZSgpO1xuXG4gICAgICAgIHZhciB3aW5kb3dTaXplID0gX2dldFdpblNpemUoKTtcbiAgICAgICAgdmFyIHRvb2x0aXBIZWlnaHQgPSBfZ2V0T2Zmc2V0KHRvb2x0aXBMYXllcikuaGVpZ2h0ICsgMTA7XG4gICAgICAgIHZhciB0b29sdGlwV2lkdGggPSBfZ2V0T2Zmc2V0KHRvb2x0aXBMYXllcikud2lkdGggKyAyMDtcbiAgICAgICAgdmFyIHRhcmdldEVsZW1lbnRSZWN0ID0gdGFyZ2V0RWxlbWVudC5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTtcblxuICAgICAgICAvLyBJZiB3ZSBjaGVjayBhbGwgdGhlIHBvc3NpYmxlIGFyZWFzLCBhbmQgdGhlcmUgYXJlIG5vIHZhbGlkIHBsYWNlcyBmb3IgdGhlIHRvb2x0aXAsIHRoZSBlbGVtZW50XG4gICAgICAgIC8vIG11c3QgdGFrZSB1cCBtb3N0IG9mIHRoZSBzY3JlZW4gcmVhbCBlc3RhdGUuIFNob3cgdGhlIHRvb2x0aXAgZmxvYXRpbmcgaW4gdGhlIG1pZGRsZSBvZiB0aGUgc2NyZWVuLlxuICAgICAgICB2YXIgY2FsY3VsYXRlZFBvc2l0aW9uID0gXCJmbG9hdGluZ1wiO1xuXG4gICAgICAgIC8qXG4gICAgICAgICogYXV0byBkZXRlcm1pbmUgcG9zaXRpb25cbiAgICAgICAgKi9cblxuICAgICAgICAvLyBDaGVjayBmb3Igc3BhY2UgYmVsb3dcbiAgICAgICAgaWYgKHRhcmdldEVsZW1lbnRSZWN0LmJvdHRvbSArIHRvb2x0aXBIZWlnaHQgKyB0b29sdGlwSGVpZ2h0ID4gd2luZG93U2l6ZS5oZWlnaHQpIHtcbiAgICAgICAgICAgIF9yZW1vdmVFbnRyeShwb3NzaWJsZVBvc2l0aW9ucywgXCJib3R0b21cIik7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBDaGVjayBmb3Igc3BhY2UgYWJvdmVcbiAgICAgICAgaWYgKHRhcmdldEVsZW1lbnRSZWN0LnRvcCAtIHRvb2x0aXBIZWlnaHQgPCAwKSB7XG4gICAgICAgICAgICBfcmVtb3ZlRW50cnkocG9zc2libGVQb3NpdGlvbnMsIFwidG9wXCIpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gQ2hlY2sgZm9yIHNwYWNlIHRvIHRoZSByaWdodFxuICAgICAgICBpZiAodGFyZ2V0RWxlbWVudFJlY3QucmlnaHQgKyB0b29sdGlwV2lkdGggPiB3aW5kb3dTaXplLndpZHRoKSB7XG4gICAgICAgICAgICBfcmVtb3ZlRW50cnkocG9zc2libGVQb3NpdGlvbnMsIFwicmlnaHRcIik7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBDaGVjayBmb3Igc3BhY2UgdG8gdGhlIGxlZnRcbiAgICAgICAgaWYgKHRhcmdldEVsZW1lbnRSZWN0LmxlZnQgLSB0b29sdGlwV2lkdGggPCAwKSB7XG4gICAgICAgICAgICBfcmVtb3ZlRW50cnkocG9zc2libGVQb3NpdGlvbnMsIFwibGVmdFwiKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vIEB2YXIge1N0cmluZ30gIGV4OiAncmlnaHQtYWxpZ25lZCdcbiAgICAgICAgdmFyIGRlc2lyZWRBbGlnbm1lbnQgPSAoZnVuY3Rpb24gKHBvcykge1xuICAgICAgICAgICAgdmFyIGh5cGhlbkluZGV4ID0gcG9zLmluZGV4T2YoJy0nKTtcbiAgICAgICAgICAgIGlmIChoeXBoZW5JbmRleCAhPT0gLTEpIHtcbiAgICAgICAgICAgICAgICAvLyBoYXMgYWxpZ25tZW50XG4gICAgICAgICAgICAgICAgcmV0dXJuIHBvcy5zdWJzdHIoaHlwaGVuSW5kZXgpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuICcnO1xuICAgICAgICB9KShkZXNpcmVkVG9vbHRpcFBvc2l0aW9uIHx8ICcnKTtcblxuICAgICAgICAvLyBzdHJpcCBhbGlnbm1lbnQgZnJvbSBwb3NpdGlvblxuICAgICAgICBpZiAoZGVzaXJlZFRvb2x0aXBQb3NpdGlvbikge1xuICAgICAgICAgICAgLy8gZXg6IFwiYm90dG9tLXJpZ2h0LWFsaWduZWRcIlxuICAgICAgICAgICAgLy8gc2hvdWxkIHJldHVybiAnYm90dG9tJ1xuICAgICAgICAgICAgZGVzaXJlZFRvb2x0aXBQb3NpdGlvbiA9IGRlc2lyZWRUb29sdGlwUG9zaXRpb24uc3BsaXQoJy0nKVswXTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmIChwb3NzaWJsZVBvc2l0aW9ucy5sZW5ndGgpIHtcbiAgICAgICAgICAgIGlmIChkZXNpcmVkVG9vbHRpcFBvc2l0aW9uICE9PSBcImF1dG9cIiAmJlxuICAgICAgICAgICAgICAgIHBvc3NpYmxlUG9zaXRpb25zLmluZGV4T2YoZGVzaXJlZFRvb2x0aXBQb3NpdGlvbikgPiAtMSkge1xuICAgICAgICAgICAgICAgIC8vIElmIHRoZSByZXF1ZXN0ZWQgcG9zaXRpb24gaXMgaW4gdGhlIGxpc3QsIGNob29zZSB0aGF0XG4gICAgICAgICAgICAgICAgY2FsY3VsYXRlZFBvc2l0aW9uID0gZGVzaXJlZFRvb2x0aXBQb3NpdGlvbjtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgLy8gUGljayB0aGUgZmlyc3QgdmFsaWQgcG9zaXRpb24sIGluIG9yZGVyXG4gICAgICAgICAgICAgICAgY2FsY3VsYXRlZFBvc2l0aW9uID0gcG9zc2libGVQb3NpdGlvbnNbMF07XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICAvLyBvbmx5IHRvcCBhbmQgYm90dG9tIHBvc2l0aW9ucyBoYXZlIG9wdGlvbmFsIGFsaWdubWVudHNcbiAgICAgICAgaWYgKFsndG9wJywgJ2JvdHRvbSddLmluZGV4T2YoY2FsY3VsYXRlZFBvc2l0aW9uKSAhPT0gLTEpIHtcbiAgICAgICAgICAgIGNhbGN1bGF0ZWRQb3NpdGlvbiArPSBfZGV0ZXJtaW5lQXV0b0FsaWdubWVudCh0YXJnZXRFbGVtZW50UmVjdC5sZWZ0LCB0b29sdGlwV2lkdGgsIHdpbmRvd1NpemUsIGRlc2lyZWRBbGlnbm1lbnQpO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIGNhbGN1bGF0ZWRQb3NpdGlvbjtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBhdXRvLWRldGVybWluZSBhbGlnbm1lbnRcbiAgICAgKiBAcGFyYW0ge0ludGVnZXJ9ICBvZmZzZXRMZWZ0XG4gICAgICogQHBhcmFtIHtJbnRlZ2VyfSAgdG9vbHRpcFdpZHRoXG4gICAgICogQHBhcmFtIHtPYmplY3R9ICAgd2luZG93U2l6ZVxuICAgICAqIEBwYXJhbSB7U3RyaW5nfSAgIGRlc2lyZWRBbGlnbm1lbnRcbiAgICAgKiBAcmV0dXJuIHtTdHJpbmd9ICBjYWxjdWxhdGVkQWxpZ25tZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gX2RldGVybWluZUF1dG9BbGlnbm1lbnQgKG9mZnNldExlZnQsIHRvb2x0aXBXaWR0aCwgd2luZG93U2l6ZSwgZGVzaXJlZEFsaWdubWVudCkge1xuICAgICAgICB2YXIgaGFsZlRvb2x0aXBXaWR0aCA9IHRvb2x0aXBXaWR0aCAvIDIsXG4gICAgICAgICAgICB3aW5XaWR0aCA9IE1hdGgubWluKHdpbmRvd1NpemUud2lkdGgsIHdpbmRvdy5zY3JlZW4ud2lkdGgpLFxuICAgICAgICAgICAgcG9zc2libGVBbGlnbm1lbnRzID0gWyctbGVmdC1hbGlnbmVkJywgJy1taWRkbGUtYWxpZ25lZCcsICctcmlnaHQtYWxpZ25lZCddLFxuICAgICAgICAgICAgY2FsY3VsYXRlZEFsaWdubWVudCA9ICcnO1xuXG4gICAgICAgIC8vIHZhbGlkIGxlZnQgbXVzdCBiZSBhdCBsZWFzdCBhIHRvb2x0aXBXaWR0aFxuICAgICAgICAvLyBhd2F5IGZyb20gcmlnaHQgc2lkZVxuICAgICAgICBpZiAod2luV2lkdGggLSBvZmZzZXRMZWZ0IDwgdG9vbHRpcFdpZHRoKSB7XG4gICAgICAgICAgICBfcmVtb3ZlRW50cnkocG9zc2libGVBbGlnbm1lbnRzLCAnLWxlZnQtYWxpZ25lZCcpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gdmFsaWQgbWlkZGxlIG11c3QgYmUgYXQgbGVhc3QgaGFsZlxuICAgICAgICAvLyB3aWR0aCBhd2F5IGZyb20gYm90aCBzaWRlc1xuICAgICAgICBpZiAob2Zmc2V0TGVmdCA8IGhhbGZUb29sdGlwV2lkdGggfHxcbiAgICAgICAgICAgIHdpbldpZHRoIC0gb2Zmc2V0TGVmdCA8IGhhbGZUb29sdGlwV2lkdGgpIHtcbiAgICAgICAgICAgIF9yZW1vdmVFbnRyeShwb3NzaWJsZUFsaWdubWVudHMsICctbWlkZGxlLWFsaWduZWQnKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vIHZhbGlkIHJpZ2h0IG11c3QgYmUgYXQgbGVhc3QgYSB0b29sdGlwV2lkdGhcbiAgICAgICAgLy8gd2lkdGggYXdheSBmcm9tIGxlZnQgc2lkZVxuICAgICAgICBpZiAob2Zmc2V0TGVmdCA8IHRvb2x0aXBXaWR0aCkge1xuICAgICAgICAgICAgX3JlbW92ZUVudHJ5KHBvc3NpYmxlQWxpZ25tZW50cywgJy1yaWdodC1hbGlnbmVkJyk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAocG9zc2libGVBbGlnbm1lbnRzLmxlbmd0aCkge1xuICAgICAgICAgICAgaWYgKHBvc3NpYmxlQWxpZ25tZW50cy5pbmRleE9mKGRlc2lyZWRBbGlnbm1lbnQpICE9PSAtMSkge1xuICAgICAgICAgICAgICAgIC8vIHRoZSBkZXNpcmVkIGFsaWdubWVudCBpcyB2YWxpZFxuICAgICAgICAgICAgICAgIGNhbGN1bGF0ZWRBbGlnbm1lbnQgPSBkZXNpcmVkQWxpZ25tZW50O1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAvLyBwaWNrIHRoZSBmaXJzdCB2YWxpZCBwb3NpdGlvbiwgaW4gb3JkZXJcbiAgICAgICAgICAgICAgICBjYWxjdWxhdGVkQWxpZ25tZW50ID0gcG9zc2libGVBbGlnbm1lbnRzWzBdO1xuICAgICAgICAgICAgfVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgLy8gaWYgc2NyZWVuIHdpZHRoIGlzIHRvbyBzbWFsbFxuICAgICAgICAgICAgLy8gZm9yIEFOWSBhbGlnbm1lbnQsIG1pZGRsZSBpc1xuICAgICAgICAgICAgLy8gcHJvYmFibHkgdGhlIGJlc3QgZm9yIHZpc2liaWxpdHlcbiAgICAgICAgICAgIGNhbGN1bGF0ZWRBbGlnbm1lbnQgPSAnLW1pZGRsZS1hbGlnbmVkJztcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBjYWxjdWxhdGVkQWxpZ25tZW50O1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFJlbW92ZSBhbiBlbnRyeSBmcm9tIGEgc3RyaW5nIGFycmF5IGlmIGl0J3MgdGhlcmUsIGRvZXMgbm90aGluZyBpZiBpdCBpc24ndCB0aGVyZS5cbiAgICAgKlxuICAgICAqIEBwYXJhbSB7QXJyYXl9IHN0cmluZ0FycmF5XG4gICAgICogQHBhcmFtIHtTdHJpbmd9IHN0cmluZ1RvUmVtb3ZlXG4gICAgICovXG4gICAgZnVuY3Rpb24gX3JlbW92ZUVudHJ5KHN0cmluZ0FycmF5LCBzdHJpbmdUb1JlbW92ZSkge1xuICAgICAgICBpZiAoc3RyaW5nQXJyYXkuaW5kZXhPZihzdHJpbmdUb1JlbW92ZSkgPiAtMSkge1xuICAgICAgICAgICAgc3RyaW5nQXJyYXkuc3BsaWNlKHN0cmluZ0FycmF5LmluZGV4T2Yoc3RyaW5nVG9SZW1vdmUpLCAxKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFVwZGF0ZSB0aGUgcG9zaXRpb24gb2YgdGhlIGhlbHBlciBsYXllciBvbiB0aGUgc2NyZWVuXG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9zZXRIZWxwZXJMYXllclBvc2l0aW9uXG4gICAgICogQHBhcmFtIHtPYmplY3R9IGhlbHBlckxheWVyXG4gICAgICovXG4gICAgZnVuY3Rpb24gX3NldEhlbHBlckxheWVyUG9zaXRpb24oaGVscGVyTGF5ZXIpIHtcbiAgICAgICAgaWYgKGhlbHBlckxheWVyKSB7XG4gICAgICAgICAgICAvL3ByZXZlbnQgZXJyb3Igd2hlbiBgdGhpcy5fY3VycmVudFN0ZXBgIGluIHVuZGVmaW5lZFxuICAgICAgICAgICAgaWYgKCF0aGlzLl9pbnRyb0l0ZW1zW3RoaXMuX2N1cnJlbnRTdGVwXSkgcmV0dXJuO1xuXG4gICAgICAgICAgICB2YXIgY3VycmVudEVsZW1lbnQgID0gdGhpcy5faW50cm9JdGVtc1t0aGlzLl9jdXJyZW50U3RlcF0sXG4gICAgICAgICAgICAgICAgZWxlbWVudFBvc2l0aW9uID0gX2dldE9mZnNldChjdXJyZW50RWxlbWVudC5lbGVtZW50KSxcbiAgICAgICAgICAgICAgICB3aWR0aEhlaWdodFBhZGRpbmcgPSB0aGlzLl9vcHRpb25zLmhlbHBlckVsZW1lbnRQYWRkaW5nO1xuXG4gICAgICAgICAgICAvLyBJZiB0aGUgdGFyZ2V0IGVsZW1lbnQgaXMgZml4ZWQsIHRoZSB0b29sdGlwIHNob3VsZCBiZSBmaXhlZCBhcyB3ZWxsLlxuICAgICAgICAgICAgLy8gT3RoZXJ3aXNlLCByZW1vdmUgYSBmaXhlZCBjbGFzcyB0aGF0IG1heSBiZSBsZWZ0IG92ZXIgZnJvbSB0aGUgcHJldmlvdXNcbiAgICAgICAgICAgIC8vIHN0ZXAuXG4gICAgICAgICAgICBpZiAoX2lzRml4ZWQoY3VycmVudEVsZW1lbnQuZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICBfYWRkQ2xhc3MoaGVscGVyTGF5ZXIsICdpbnRyb2pzLWZpeGVkVG9vbHRpcCcpO1xuICAgICAgICAgICAgICAgIHZhciBfX2VsZW1lbnRQb3NpdGlvbiA9IGpRdWVyeShjdXJyZW50RWxlbWVudC5lbGVtZW50KS5wb3NpdGlvbigpO1xuICAgICAgICAgICAgICAgIGVsZW1lbnRQb3NpdGlvbi50b3AgPSBfX2VsZW1lbnRQb3NpdGlvbi50b3A7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIF9yZW1vdmVDbGFzcyhoZWxwZXJMYXllciwgJ2ludHJvanMtZml4ZWRUb29sdGlwJyk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmIChjdXJyZW50RWxlbWVudC5wb3NpdGlvbiA9PT0gJ2Zsb2F0aW5nJykge1xuICAgICAgICAgICAgICAgIHdpZHRoSGVpZ2h0UGFkZGluZyA9IDA7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vc2V0IG5ldyBwb3NpdGlvbiB0byBoZWxwZXIgbGF5ZXJcbiAgICAgICAgICAgIGhlbHBlckxheWVyLnN0eWxlLmNzc1RleHQgPSAnd2lkdGg6ICcgKyAoZWxlbWVudFBvc2l0aW9uLndpZHRoICArIHdpZHRoSGVpZ2h0UGFkZGluZykgICsgJ3B4OyAnICtcbiAgICAgICAgICAgICAgICAnaGVpZ2h0OicgKyAoZWxlbWVudFBvc2l0aW9uLmhlaWdodCArIHdpZHRoSGVpZ2h0UGFkZGluZykgICsgJ3B4OyAnICtcbiAgICAgICAgICAgICAgICAndG9wOicgICAgKyAoZWxlbWVudFBvc2l0aW9uLnRvcCAgICAtIHdpZHRoSGVpZ2h0UGFkZGluZyAvIDIpICAgKyAncHg7JyArXG4gICAgICAgICAgICAgICAgJ2xlZnQ6ICcgICsgKGVsZW1lbnRQb3NpdGlvbi5sZWZ0ICAgLSB3aWR0aEhlaWdodFBhZGRpbmcgLyAyKSAgICsgJ3B4Oyc7XG5cbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEFkZCBkaXNhYmxlaW50ZXJhY3Rpb24gbGF5ZXIgYW5kIGFkanVzdCB0aGUgc2l6ZSBhbmQgcG9zaXRpb24gb2YgdGhlIGxheWVyXG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9kaXNhYmxlSW50ZXJhY3Rpb25cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfZGlzYWJsZUludGVyYWN0aW9uKCkge1xuICAgICAgICB2YXIgZGlzYWJsZUludGVyYWN0aW9uTGF5ZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1kaXNhYmxlSW50ZXJhY3Rpb24nKTtcblxuICAgICAgICBpZiAoZGlzYWJsZUludGVyYWN0aW9uTGF5ZXIgPT09IG51bGwpIHtcbiAgICAgICAgICAgIGRpc2FibGVJbnRlcmFjdGlvbkxheWVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG4gICAgICAgICAgICBkaXNhYmxlSW50ZXJhY3Rpb25MYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy1kaXNhYmxlSW50ZXJhY3Rpb24nO1xuICAgICAgICAgICAgdGhpcy5fdGFyZ2V0RWxlbWVudC5hcHBlbmRDaGlsZChkaXNhYmxlSW50ZXJhY3Rpb25MYXllcik7XG4gICAgICAgIH1cblxuICAgICAgICBfc2V0SGVscGVyTGF5ZXJQb3NpdGlvbi5jYWxsKHRoaXMsIGRpc2FibGVJbnRlcmFjdGlvbkxheWVyKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBTZXR0aW5nIGFuY2hvcnMgdG8gYmVoYXZlIGxpa2UgYnV0dG9uc1xuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfc2V0QW5jaG9yQXNCdXR0b25cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfc2V0QW5jaG9yQXNCdXR0b24oYW5jaG9yKXtcbiAgICAgICAgYW5jaG9yLnNldEF0dHJpYnV0ZSgncm9sZScsICdidXR0b24nKTtcbiAgICAgICAgYW5jaG9yLnRhYkluZGV4ID0gMDtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBTaG93IGFuIGVsZW1lbnQgb24gdGhlIHBhZ2VcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX3Nob3dFbGVtZW50XG4gICAgICogQHBhcmFtIHtPYmplY3R9IHRhcmdldEVsZW1lbnRcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfc2hvd0VsZW1lbnQodGFyZ2V0RWxlbWVudCkge1xuICAgICAgICBpZiAodHlwZW9mICh0aGlzLl9pbnRyb0NoYW5nZUNhbGxiYWNrKSAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgICAgIHRoaXMuX2ludHJvQ2hhbmdlQ2FsbGJhY2suY2FsbCh0aGlzLCB0YXJnZXRFbGVtZW50LmVsZW1lbnQpO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIHNlbGYgPSB0aGlzLFxuICAgICAgICAgICAgb2xkSGVscGVyTGF5ZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1oZWxwZXJMYXllcicpLFxuICAgICAgICAgICAgb2xkUmVmZXJlbmNlTGF5ZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy10b29sdGlwUmVmZXJlbmNlTGF5ZXInKSxcbiAgICAgICAgICAgIGhpZ2hsaWdodENsYXNzID0gJ2ludHJvanMtaGVscGVyTGF5ZXInLFxuICAgICAgICAgICAgbmV4dFRvb2x0aXBCdXR0b24sXG4gICAgICAgICAgICBwcmV2VG9vbHRpcEJ1dHRvbixcbiAgICAgICAgICAgIHNraXBUb29sdGlwQnV0dG9uLFxuICAgICAgICAgICAgc2Nyb2xsUGFyZW50O1xuXG4gICAgICAgIC8vY2hlY2sgZm9yIGEgY3VycmVudCBzdGVwIGhpZ2hsaWdodCBjbGFzc1xuICAgICAgICBpZiAodHlwZW9mICh0YXJnZXRFbGVtZW50LmhpZ2hsaWdodENsYXNzKSA9PT0gJ3N0cmluZycpIHtcbiAgICAgICAgICAgIGhpZ2hsaWdodENsYXNzICs9ICgnICcgKyB0YXJnZXRFbGVtZW50LmhpZ2hsaWdodENsYXNzKTtcbiAgICAgICAgfVxuICAgICAgICAvL2NoZWNrIGZvciBvcHRpb25zIGhpZ2hsaWdodCBjbGFzc1xuICAgICAgICBpZiAodHlwZW9mICh0aGlzLl9vcHRpb25zLmhpZ2hsaWdodENsYXNzKSA9PT0gJ3N0cmluZycpIHtcbiAgICAgICAgICAgIGhpZ2hsaWdodENsYXNzICs9ICgnICcgKyB0aGlzLl9vcHRpb25zLmhpZ2hsaWdodENsYXNzKTtcbiAgICAgICAgfVxuXG4gICAgICAgIF9zZXRTaG93RWxlbWVudCh0YXJnZXRFbGVtZW50KTtcbiAgICAgICAgaWYgKG9sZEhlbHBlckxheWVyICE9PSBudWxsKSB7XG4gICAgICAgICAgICB2YXIgb2xkSGVscGVyTnVtYmVyTGF5ZXIgPSBvbGRSZWZlcmVuY2VMYXllci5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1oZWxwZXJOdW1iZXJMYXllcicpLFxuICAgICAgICAgICAgICAgIG9sZHRvb2x0aXBMYXllciAgICAgID0gb2xkUmVmZXJlbmNlTGF5ZXIucXVlcnlTZWxlY3RvcignLmludHJvanMtdG9vbHRpcHRleHQnKSxcbiAgICAgICAgICAgICAgICBvbGRBcnJvd0xheWVyICAgICAgICA9IG9sZFJlZmVyZW5jZUxheWVyLnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLWFycm93JyksXG4gICAgICAgICAgICAgICAgb2xkdG9vbHRpcENvbnRhaW5lciAgPSBvbGRSZWZlcmVuY2VMYXllci5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy10b29sdGlwJyk7XG5cbiAgICAgICAgICAgIHNraXBUb29sdGlwQnV0dG9uICAgID0gb2xkUmVmZXJlbmNlTGF5ZXIucXVlcnlTZWxlY3RvcignLmludHJvanMtc2tpcGJ1dHRvbicpO1xuICAgICAgICAgICAgcHJldlRvb2x0aXBCdXR0b24gICAgPSBvbGRSZWZlcmVuY2VMYXllci5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1wcmV2YnV0dG9uJyk7XG4gICAgICAgICAgICBuZXh0VG9vbHRpcEJ1dHRvbiAgICA9IG9sZFJlZmVyZW5jZUxheWVyLnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLW5leHRidXR0b24nKTtcblxuICAgICAgICAgICAgLy91cGRhdGUgb3IgcmVzZXQgdGhlIGhlbHBlciBoaWdobGlnaHQgY2xhc3NcbiAgICAgICAgICAgIG9sZEhlbHBlckxheWVyLmNsYXNzTmFtZSA9IGhpZ2hsaWdodENsYXNzO1xuICAgICAgICAgICAgLy9oaWRlIHRoZSB0b29sdGlwXG4gICAgICAgICAgICBvbGR0b29sdGlwQ29udGFpbmVyLnN0eWxlLm9wYWNpdHkgPSAwO1xuICAgICAgICAgICAgb2xkdG9vbHRpcENvbnRhaW5lci5zdHlsZS5kaXNwbGF5ID0gXCJub25lXCI7XG5cbiAgICAgICAgICAgIGlmIChvbGRIZWxwZXJOdW1iZXJMYXllciAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHZhciBsYXN0SW50cm9JdGVtID0gdGhpcy5faW50cm9JdGVtc1sodGFyZ2V0RWxlbWVudC5zdGVwIC0gMiA+PSAwID8gdGFyZ2V0RWxlbWVudC5zdGVwIC0gMiA6IDApXTtcblxuICAgICAgICAgICAgICAgIGlmIChsYXN0SW50cm9JdGVtICE9PSBudWxsICYmICh0aGlzLl9kaXJlY3Rpb24gPT09ICdmb3J3YXJkJyAmJiBsYXN0SW50cm9JdGVtLnBvc2l0aW9uID09PSAnZmxvYXRpbmcnKSB8fCAodGhpcy5fZGlyZWN0aW9uID09PSAnYmFja3dhcmQnICYmIHRhcmdldEVsZW1lbnQucG9zaXRpb24gPT09ICdmbG9hdGluZycpKSB7XG4gICAgICAgICAgICAgICAgICAgIG9sZEhlbHBlck51bWJlckxheWVyLnN0eWxlLm9wYWNpdHkgPSAwO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gc2Nyb2xsIHRvIGVsZW1lbnRcbiAgICAgICAgICAgIHNjcm9sbFBhcmVudCA9IF9nZXRTY3JvbGxQYXJlbnQoIHRhcmdldEVsZW1lbnQuZWxlbWVudCApO1xuXG4gICAgICAgICAgICBpZiAoc2Nyb2xsUGFyZW50ICE9PSBkb2N1bWVudC5ib2R5KSB7XG4gICAgICAgICAgICAgICAgLy8gdGFyZ2V0IGlzIHdpdGhpbiBhIHNjcm9sbGFibGUgZWxlbWVudFxuICAgICAgICAgICAgICAgIF9zY3JvbGxQYXJlbnRUb0VsZW1lbnQoc2Nyb2xsUGFyZW50LCB0YXJnZXRFbGVtZW50LmVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAvLyBzZXQgbmV3IHBvc2l0aW9uIHRvIGhlbHBlciBsYXllclxuICAgICAgICAgICAgX3NldEhlbHBlckxheWVyUG9zaXRpb24uY2FsbChzZWxmLCBvbGRIZWxwZXJMYXllcik7XG4gICAgICAgICAgICBfc2V0SGVscGVyTGF5ZXJQb3NpdGlvbi5jYWxsKHNlbGYsIG9sZFJlZmVyZW5jZUxheWVyKTtcblxuICAgICAgICAgICAgLy9yZW1vdmUgYGludHJvanMtZml4UGFyZW50YCBjbGFzcyBmcm9tIHRoZSBlbGVtZW50c1xuICAgICAgICAgICAgdmFyIGZpeFBhcmVudHMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcuaW50cm9qcy1maXhQYXJlbnQnKTtcbiAgICAgICAgICAgIF9mb3JFYWNoKGZpeFBhcmVudHMsIGZ1bmN0aW9uIChwYXJlbnQpIHtcbiAgICAgICAgICAgICAgICBfcmVtb3ZlQ2xhc3MocGFyZW50LCAvaW50cm9qcy1maXhQYXJlbnQvZyk7XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgLy9yZW1vdmUgb2xkIGNsYXNzZXMgaWYgdGhlIGVsZW1lbnQgc3RpbGwgZXhpc3RcbiAgICAgICAgICAgIF9yZW1vdmVTaG93RWxlbWVudCgpO1xuXG4gICAgICAgICAgICAvL3dlIHNob3VsZCB3YWl0IHVudGlsIHRoZSBDU1MzIHRyYW5zaXRpb24gaXMgY29tcGV0ZWQgKGl0J3MgMC4zIHNlYykgdG8gcHJldmVudCBpbmNvcnJlY3QgYGhlaWdodGAgYW5kIGB3aWR0aGAgY2FsY3VsYXRpb25cbiAgICAgICAgICAgIGlmIChzZWxmLl9sYXN0U2hvd0VsZW1lbnRUaW1lcikge1xuICAgICAgICAgICAgICAgIHdpbmRvdy5jbGVhclRpbWVvdXQoc2VsZi5fbGFzdFNob3dFbGVtZW50VGltZXIpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBzZWxmLl9sYXN0U2hvd0VsZW1lbnRUaW1lciA9IHdpbmRvdy5zZXRUaW1lb3V0KGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgIC8vc2V0IGN1cnJlbnQgc3RlcCB0byB0aGUgbGFiZWxcbiAgICAgICAgICAgICAgICBpZiAob2xkSGVscGVyTnVtYmVyTGF5ZXIgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgb2xkSGVscGVyTnVtYmVyTGF5ZXIuaW5uZXJIVE1MID0gdGFyZ2V0RWxlbWVudC5zdGVwO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAvL3NldCBjdXJyZW50IHRvb2x0aXAgdGV4dFxuICAgICAgICAgICAgICAgIG9sZHRvb2x0aXBMYXllci5pbm5lckhUTUwgPSB0YXJnZXRFbGVtZW50LmludHJvO1xuICAgICAgICAgICAgICAgIC8vc2V0IHRoZSB0b29sdGlwIHBvc2l0aW9uXG4gICAgICAgICAgICAgICAgb2xkdG9vbHRpcENvbnRhaW5lci5zdHlsZS5kaXNwbGF5ID0gXCJibG9ja1wiO1xuICAgICAgICAgICAgICAgIF9wbGFjZVRvb2x0aXAuY2FsbChzZWxmLCB0YXJnZXRFbGVtZW50LmVsZW1lbnQsIG9sZHRvb2x0aXBDb250YWluZXIsIG9sZEFycm93TGF5ZXIsIG9sZEhlbHBlck51bWJlckxheWVyKTtcblxuICAgICAgICAgICAgICAgIC8vY2hhbmdlIGFjdGl2ZSBidWxsZXRcbiAgICAgICAgICAgICAgICBpZiAoc2VsZi5fb3B0aW9ucy5zaG93QnVsbGV0cykge1xuICAgICAgICAgICAgICAgICAgICBvbGRSZWZlcmVuY2VMYXllci5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1idWxsZXRzIGxpID4gYS5hY3RpdmUnKS5jbGFzc05hbWUgPSAnJztcbiAgICAgICAgICAgICAgICAgICAgb2xkUmVmZXJlbmNlTGF5ZXIucXVlcnlTZWxlY3RvcignLmludHJvanMtYnVsbGV0cyBsaSA+IGFbZGF0YS1zdGVwbnVtYmVyPVwiJyArIHRhcmdldEVsZW1lbnQuc3RlcCArICdcIl0nKS5jbGFzc05hbWUgPSAnYWN0aXZlJztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgb2xkUmVmZXJlbmNlTGF5ZXIucXVlcnlTZWxlY3RvcignLmludHJvanMtcHJvZ3Jlc3MgLmludHJvanMtcHJvZ3Jlc3NiYXInKS5zdHlsZS5jc3NUZXh0ID0gJ3dpZHRoOicgKyBfZ2V0UHJvZ3Jlc3MuY2FsbChzZWxmKSArICclOyc7XG4gICAgICAgICAgICAgICAgb2xkUmVmZXJlbmNlTGF5ZXIucXVlcnlTZWxlY3RvcignLmludHJvanMtcHJvZ3Jlc3MgLmludHJvanMtcHJvZ3Jlc3NiYXInKS5zZXRBdHRyaWJ1dGUoJ2FyaWEtdmFsdWVub3cnLCBfZ2V0UHJvZ3Jlc3MuY2FsbChzZWxmKSk7XG5cbiAgICAgICAgICAgICAgICAvL3Nob3cgdGhlIHRvb2x0aXBcbiAgICAgICAgICAgICAgICBvbGR0b29sdGlwQ29udGFpbmVyLnN0eWxlLm9wYWNpdHkgPSAxO1xuICAgICAgICAgICAgICAgIGlmIChvbGRIZWxwZXJOdW1iZXJMYXllcikgb2xkSGVscGVyTnVtYmVyTGF5ZXIuc3R5bGUub3BhY2l0eSA9IDE7XG5cbiAgICAgICAgICAgICAgICAvL3Jlc2V0IGJ1dHRvbiBmb2N1c1xuICAgICAgICAgICAgICAgIGlmICh0eXBlb2Ygc2tpcFRvb2x0aXBCdXR0b24gIT09IFwidW5kZWZpbmVkXCIgJiYgc2tpcFRvb2x0aXBCdXR0b24gIT09IG51bGwgJiYgL2ludHJvanMtZG9uZWJ1dHRvbi9naS50ZXN0KHNraXBUb29sdGlwQnV0dG9uLmNsYXNzTmFtZSkpIHtcbiAgICAgICAgICAgICAgICAgICAgLy8gc2tpcCBidXR0b24gaXMgbm93IFwiZG9uZVwiIGJ1dHRvblxuICAgICAgICAgICAgICAgICAgICBza2lwVG9vbHRpcEJ1dHRvbi5mb2N1cygpO1xuICAgICAgICAgICAgICAgIH0gZWxzZSBpZiAodHlwZW9mIG5leHRUb29sdGlwQnV0dG9uICE9PSBcInVuZGVmaW5lZFwiICYmIG5leHRUb29sdGlwQnV0dG9uICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgIC8vc3RpbGwgaW4gdGhlIHRvdXIsIGZvY3VzIG9uIG5leHRcbiAgICAgICAgICAgICAgICAgICAgbmV4dFRvb2x0aXBCdXR0b24uZm9jdXMoKTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAvLyBjaGFuZ2UgdGhlIHNjcm9sbCBvZiB0aGUgd2luZG93LCBpZiBuZWVkZWRcbiAgICAgICAgICAgICAgICBfc2Nyb2xsVG8uY2FsbChzZWxmLCB0YXJnZXRFbGVtZW50LnNjcm9sbFRvLCB0YXJnZXRFbGVtZW50LCBvbGR0b29sdGlwTGF5ZXIpO1xuICAgICAgICAgICAgfSwgNTApO1xuXG4gICAgICAgICAgICAvLyBlbmQgb2Ygb2xkIGVsZW1lbnQgaWYtZWxzZSBjb25kaXRpb25cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHZhciBoZWxwZXJMYXllciAgICAgICA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpLFxuICAgICAgICAgICAgICAgIHJlZmVyZW5jZUxheWVyICAgID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2JyksXG4gICAgICAgICAgICAgICAgYXJyb3dMYXllciAgICAgICAgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKSxcbiAgICAgICAgICAgICAgICB0b29sdGlwTGF5ZXIgICAgICA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpLFxuICAgICAgICAgICAgICAgIHRvb2x0aXBUZXh0TGF5ZXIgID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2JyksXG4gICAgICAgICAgICAgICAgYnVsbGV0c0xheWVyICAgICAgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKSxcbiAgICAgICAgICAgICAgICBwcm9ncmVzc0xheWVyICAgICA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpLFxuICAgICAgICAgICAgICAgIGJ1dHRvbnNMYXllciAgICAgID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG5cbiAgICAgICAgICAgIGhlbHBlckxheWVyLmNsYXNzTmFtZSA9IGhpZ2hsaWdodENsYXNzO1xuICAgICAgICAgICAgcmVmZXJlbmNlTGF5ZXIuaWQgPSAndGlwanNUb29sdGlwJztcbiAgICAgICAgICAgIHJlZmVyZW5jZUxheWVyLmNsYXNzTmFtZSA9ICdpbnRyb2pzLXRvb2x0aXBSZWZlcmVuY2VMYXllcic7XG5cbiAgICAgICAgICAgIC8vIHNjcm9sbCB0byBlbGVtZW50XG4gICAgICAgICAgICBzY3JvbGxQYXJlbnQgPSBfZ2V0U2Nyb2xsUGFyZW50KCB0YXJnZXRFbGVtZW50LmVsZW1lbnQgKTtcblxuICAgICAgICAgICAgaWYgKHNjcm9sbFBhcmVudCAhPT0gZG9jdW1lbnQuYm9keSkge1xuICAgICAgICAgICAgICAgIC8vIHRhcmdldCBpcyB3aXRoaW4gYSBzY3JvbGxhYmxlIGVsZW1lbnRcbiAgICAgICAgICAgICAgICBfc2Nyb2xsUGFyZW50VG9FbGVtZW50KHNjcm9sbFBhcmVudCwgdGFyZ2V0RWxlbWVudC5lbGVtZW50KTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy9zZXQgbmV3IHBvc2l0aW9uIHRvIGhlbHBlciBsYXllclxuICAgICAgICAgICAgX3NldEhlbHBlckxheWVyUG9zaXRpb24uY2FsbChzZWxmLCBoZWxwZXJMYXllcik7XG4gICAgICAgICAgICBfc2V0SGVscGVyTGF5ZXJQb3NpdGlvbi5jYWxsKHNlbGYsIHJlZmVyZW5jZUxheWVyKTtcblxuICAgICAgICAgICAgLy9hZGQgaGVscGVyIGxheWVyIHRvIHRhcmdldCBlbGVtZW50XG4gICAgICAgICAgICB0aGlzLl90YXJnZXRFbGVtZW50LmFwcGVuZENoaWxkKGhlbHBlckxheWVyKTtcbiAgICAgICAgICAgIHRoaXMuX3RhcmdldEVsZW1lbnQuYXBwZW5kQ2hpbGQocmVmZXJlbmNlTGF5ZXIpO1xuXG4gICAgICAgICAgICBhcnJvd0xheWVyLmNsYXNzTmFtZSA9ICdpbnRyb2pzLWFycm93JztcblxuICAgICAgICAgICAgdG9vbHRpcFRleHRMYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy10b29sdGlwdGV4dCc7XG4gICAgICAgICAgICB0b29sdGlwVGV4dExheWVyLmlubmVySFRNTCA9IHRhcmdldEVsZW1lbnQuaW50cm87XG5cbiAgICAgICAgICAgIGJ1bGxldHNMYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy1idWxsZXRzJztcblxuICAgICAgICAgICAgaWYgKHRoaXMuX29wdGlvbnMuc2hvd0J1bGxldHMgPT09IGZhbHNlKSB7XG4gICAgICAgICAgICAgICAgYnVsbGV0c0xheWVyLnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHZhciB1bENvbnRhaW5lciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ3VsJyk7XG4gICAgICAgICAgICB1bENvbnRhaW5lci5zZXRBdHRyaWJ1dGUoJ3JvbGUnLCAndGFibGlzdCcpO1xuXG4gICAgICAgICAgICB2YXIgYW5jaG9yQ2xpY2sgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgc2VsZi5nb1RvU3RlcCh0aGlzLmdldEF0dHJpYnV0ZSgnZGF0YS1zdGVwbnVtYmVyJykpO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgX2ZvckVhY2godGhpcy5faW50cm9JdGVtcywgZnVuY3Rpb24gKGl0ZW0sIGkpIHtcbiAgICAgICAgICAgICAgICB2YXIgaW5uZXJMaSAgICA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2xpJyk7XG4gICAgICAgICAgICAgICAgdmFyIGFuY2hvckxpbmsgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdhJyk7XG5cbiAgICAgICAgICAgICAgICBpbm5lckxpLnNldEF0dHJpYnV0ZSgncm9sZScsICdwcmVzZW50YXRpb24nKTtcbiAgICAgICAgICAgICAgICBhbmNob3JMaW5rLnNldEF0dHJpYnV0ZSgncm9sZScsICd0YWInKTtcblxuICAgICAgICAgICAgICAgIGFuY2hvckxpbmsub25jbGljayA9IGFuY2hvckNsaWNrO1xuXG4gICAgICAgICAgICAgICAgaWYgKGkgPT09ICh0YXJnZXRFbGVtZW50LnN0ZXAtMSkpIHtcbiAgICAgICAgICAgICAgICAgICAgYW5jaG9yTGluay5jbGFzc05hbWUgPSAnYWN0aXZlJztcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBfc2V0QW5jaG9yQXNCdXR0b24oYW5jaG9yTGluayk7XG4gICAgICAgICAgICAgICAgYW5jaG9yTGluay5pbm5lckhUTUwgPSBcIiZuYnNwO1wiO1xuICAgICAgICAgICAgICAgIGFuY2hvckxpbmsuc2V0QXR0cmlidXRlKCdkYXRhLXN0ZXBudW1iZXInLCBpdGVtLnN0ZXApO1xuXG4gICAgICAgICAgICAgICAgaW5uZXJMaS5hcHBlbmRDaGlsZChhbmNob3JMaW5rKTtcbiAgICAgICAgICAgICAgICB1bENvbnRhaW5lci5hcHBlbmRDaGlsZChpbm5lckxpKTtcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICBidWxsZXRzTGF5ZXIuYXBwZW5kQ2hpbGQodWxDb250YWluZXIpO1xuXG4gICAgICAgICAgICBwcm9ncmVzc0xheWVyLmNsYXNzTmFtZSA9ICdpbnRyb2pzLXByb2dyZXNzJztcblxuICAgICAgICAgICAgaWYgKHRoaXMuX29wdGlvbnMuc2hvd1Byb2dyZXNzID09PSBmYWxzZSkge1xuICAgICAgICAgICAgICAgIHByb2dyZXNzTGF5ZXIuc3R5bGUuZGlzcGxheSA9ICdub25lJztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHZhciBwcm9ncmVzc0JhciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgICAgICAgICAgcHJvZ3Jlc3NCYXIuY2xhc3NOYW1lID0gJ2ludHJvanMtcHJvZ3Jlc3NiYXInO1xuICAgICAgICAgICAgcHJvZ3Jlc3NCYXIuc2V0QXR0cmlidXRlKCdyb2xlJywgJ3Byb2dyZXNzJyk7XG4gICAgICAgICAgICBwcm9ncmVzc0Jhci5zZXRBdHRyaWJ1dGUoJ2FyaWEtdmFsdWVtaW4nLCAwKTtcbiAgICAgICAgICAgIHByb2dyZXNzQmFyLnNldEF0dHJpYnV0ZSgnYXJpYS12YWx1ZW1heCcsIDEwMCk7XG4gICAgICAgICAgICBwcm9ncmVzc0Jhci5zZXRBdHRyaWJ1dGUoJ2FyaWEtdmFsdWVub3cnLCBfZ2V0UHJvZ3Jlc3MuY2FsbCh0aGlzKSk7XG4gICAgICAgICAgICBwcm9ncmVzc0Jhci5zdHlsZS5jc3NUZXh0ID0gJ3dpZHRoOicgKyBfZ2V0UHJvZ3Jlc3MuY2FsbCh0aGlzKSArICclOyc7XG5cbiAgICAgICAgICAgIHByb2dyZXNzTGF5ZXIuYXBwZW5kQ2hpbGQocHJvZ3Jlc3NCYXIpO1xuXG4gICAgICAgICAgICBidXR0b25zTGF5ZXIuY2xhc3NOYW1lID0gJ2ludHJvanMtdG9vbHRpcGJ1dHRvbnMnO1xuICAgICAgICAgICAgaWYgKHRoaXMuX29wdGlvbnMuc2hvd0J1dHRvbnMgPT09IGZhbHNlKSB7XG4gICAgICAgICAgICAgICAgYnV0dG9uc0xheWVyLnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHZhciBjbG9zZUJ0biA9ICcnO1xuICAgICAgICAgICAgdmFyIGNsb3NlQnRuID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYScpO1xuICAgICAgICAgICAgY2xvc2VCdG4uY2xhc3NOYW1lID0gJ3RpcC1jbG9zZSc7XG4gICAgICAgICAgICBjbG9zZUJ0bi5zZXRBdHRyaWJ1dGUoJ3JvbGUnLCAnYnV0dG9uJyk7XG4gICAgICAgICAgICBjbG9zZUJ0bi5zZXRBdHRyaWJ1dGUoJ2hyZWYnLCAnI2Nsb3NlJyk7XG4gICAgICAgICAgICBjbG9zZUJ0bi5pbm5lckhUTUwgPSAnPGkgY2xhc3M9XCJpY29uLWNsb3NlLXdoaXRlXCI+PC9pPic7XG4gICAgICAgICAgICBjbG9zZUJ0bi5vbmNsaWNrID0gdGhpcy5faW50cm9DbG9zZUNhbGxiYWNrLmJpbmQodGhpcyk7XG5cbiAgICAgICAgICAgIHRvb2x0aXBMYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy10b29sdGlwJztcbiAgICAgICAgICAgIHRvb2x0aXBMYXllci5hcHBlbmRDaGlsZChjbG9zZUJ0bik7XG4gICAgICAgICAgICB0b29sdGlwTGF5ZXIuYXBwZW5kQ2hpbGQodG9vbHRpcFRleHRMYXllcik7XG4gICAgICAgICAgICB0b29sdGlwTGF5ZXIuYXBwZW5kQ2hpbGQoYnVsbGV0c0xheWVyKTtcbiAgICAgICAgICAgIHRvb2x0aXBMYXllci5hcHBlbmRDaGlsZChwcm9ncmVzc0xheWVyKTtcblxuICAgICAgICAgICAgLy9hZGQgaGVscGVyIGxheWVyIG51bWJlclxuICAgICAgICAgICAgdmFyIGhlbHBlck51bWJlckxheWVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnc3BhbicpO1xuICAgICAgICAgICAgaWYgKHRoaXMuX29wdGlvbnMuc2hvd1N0ZXBOdW1iZXJzID09PSB0cnVlKSB7XG4gICAgICAgICAgICAgICAgaGVscGVyTnVtYmVyTGF5ZXIuY2xhc3NOYW1lID0gJ2ludHJvanMtaGVscGVyTnVtYmVyTGF5ZXInO1xuICAgICAgICAgICAgICAgIGhlbHBlck51bWJlckxheWVyLmlubmVySFRNTCA9IHRhcmdldEVsZW1lbnQuc3RlcDtcbiAgICAgICAgICAgICAgICByZWZlcmVuY2VMYXllci5hcHBlbmRDaGlsZChoZWxwZXJOdW1iZXJMYXllcik7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHRvb2x0aXBMYXllci5hcHBlbmRDaGlsZChhcnJvd0xheWVyKTtcbiAgICAgICAgICAgIHJlZmVyZW5jZUxheWVyLmFwcGVuZENoaWxkKHRvb2x0aXBMYXllcik7XG5cbiAgICAgICAgICAgIC8vbmV4dCBidXR0b25cbiAgICAgICAgICAgIG5leHRUb29sdGlwQnV0dG9uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYScpO1xuXG4gICAgICAgICAgICBuZXh0VG9vbHRpcEJ1dHRvbi5vbmNsaWNrID0gZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgaWYgKHNlbGYuX2ludHJvSXRlbXMubGVuZ3RoIC0gMSAhPT0gc2VsZi5fY3VycmVudFN0ZXApIHtcbiAgICAgICAgICAgICAgICAgICAgX25leHRTdGVwLmNhbGwoc2VsZik7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgX3NldEFuY2hvckFzQnV0dG9uKG5leHRUb29sdGlwQnV0dG9uKTtcbiAgICAgICAgICAgIG5leHRUb29sdGlwQnV0dG9uLmlubmVySFRNTCA9IHRoaXMuX29wdGlvbnMubmV4dExhYmVsO1xuXG4gICAgICAgICAgICAvL3ByZXZpb3VzIGJ1dHRvblxuICAgICAgICAgICAgcHJldlRvb2x0aXBCdXR0b24gPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdhJyk7XG5cbiAgICAgICAgICAgIHByZXZUb29sdGlwQnV0dG9uLm9uY2xpY2sgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICBpZiAoc2VsZi5fY3VycmVudFN0ZXAgIT09IDApIHtcbiAgICAgICAgICAgICAgICAgICAgX3ByZXZpb3VzU3RlcC5jYWxsKHNlbGYpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIF9zZXRBbmNob3JBc0J1dHRvbihwcmV2VG9vbHRpcEJ1dHRvbik7XG4gICAgICAgICAgICBwcmV2VG9vbHRpcEJ1dHRvbi5pbm5lckhUTUwgPSB0aGlzLl9vcHRpb25zLnByZXZMYWJlbDtcblxuICAgICAgICAgICAgLy9za2lwIGJ1dHRvblxuICAgICAgICAgICAgc2tpcFRvb2x0aXBCdXR0b24gPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdhJyk7XG4gICAgICAgICAgICBza2lwVG9vbHRpcEJ1dHRvbi5jbGFzc05hbWUgPSB0aGlzLl9vcHRpb25zLmJ1dHRvbkNsYXNzICsgJyBpbnRyb2pzLXNraXBidXR0b24gJztcbiAgICAgICAgICAgIF9zZXRBbmNob3JBc0J1dHRvbihza2lwVG9vbHRpcEJ1dHRvbik7XG4gICAgICAgICAgICBza2lwVG9vbHRpcEJ1dHRvbi5pbm5lckhUTUwgPSB0aGlzLl9vcHRpb25zLnNraXBMYWJlbDtcblxuICAgICAgICAgICAgc2tpcFRvb2x0aXBCdXR0b24ub25jbGljayA9IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgIGlmIChzZWxmLl9pbnRyb0l0ZW1zLmxlbmd0aCAtIDEgPT09IHNlbGYuX2N1cnJlbnRTdGVwICYmIHR5cGVvZiAoc2VsZi5faW50cm9Db21wbGV0ZUNhbGxiYWNrKSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgICAgICBzZWxmLl9pbnRyb0NvbXBsZXRlQ2FsbGJhY2suY2FsbChzZWxmKTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBpZiAoc2VsZi5faW50cm9JdGVtcy5sZW5ndGggLSAxICE9PSBzZWxmLl9jdXJyZW50U3RlcCAmJiB0eXBlb2YgKHNlbGYuX2ludHJvRXhpdENhbGxiYWNrKSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgICAgICBzZWxmLl9pbnRyb0V4aXRDYWxsYmFjay5jYWxsKHNlbGYpO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIHNlbGYuX2ludHJvU2tpcENhbGxiYWNrLmNhbGwoc2VsZik7XG4gICAgICAgICAgICAgICAgX2V4aXRJbnRyby5jYWxsKHNlbGYsIHNlbGYuX3RhcmdldEVsZW1lbnQpO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgYnV0dG9uc0xheWVyLmFwcGVuZENoaWxkKHNraXBUb29sdGlwQnV0dG9uKTtcblxuICAgICAgICAgICAgLy9pbiBvcmRlciB0byBwcmV2ZW50IGRpc3BsYXlpbmcgbmV4dC9wcmV2aW91cyBidXR0b24gYWx3YXlzXG4gICAgICAgICAgICBpZiAodGhpcy5faW50cm9JdGVtcy5sZW5ndGggPiAxKSB7XG4gICAgICAgICAgICAgICAgYnV0dG9uc0xheWVyLmFwcGVuZENoaWxkKHByZXZUb29sdGlwQnV0dG9uKTtcbiAgICAgICAgICAgICAgICBidXR0b25zTGF5ZXIuYXBwZW5kQ2hpbGQobmV4dFRvb2x0aXBCdXR0b24pO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB0b29sdGlwTGF5ZXIuYXBwZW5kQ2hpbGQoYnV0dG9uc0xheWVyKTtcblxuICAgICAgICAgICAgLy9zZXQgcHJvcGVyIHBvc2l0aW9uXG4gICAgICAgICAgICBfcGxhY2VUb29sdGlwLmNhbGwoc2VsZiwgdGFyZ2V0RWxlbWVudC5lbGVtZW50LCB0b29sdGlwTGF5ZXIsIGFycm93TGF5ZXIsIGhlbHBlck51bWJlckxheWVyKTtcblxuICAgICAgICAgICAgLy8gY2hhbmdlIHRoZSBzY3JvbGwgb2YgdGhlIHdpbmRvdywgaWYgbmVlZGVkXG4gICAgICAgICAgICBfc2Nyb2xsVG8uY2FsbCh0aGlzLCB0YXJnZXRFbGVtZW50LnNjcm9sbFRvLCB0YXJnZXRFbGVtZW50LCB0b29sdGlwTGF5ZXIpO1xuXG4gICAgICAgICAgICAvL2VuZCBvZiBuZXcgZWxlbWVudCBpZi1lbHNlIGNvbmRpdGlvblxuICAgICAgICB9XG5cbiAgICAgICAgLy8gcmVtb3ZpbmcgcHJldmlvdXMgZGlzYWJsZSBpbnRlcmFjdGlvbiBsYXllclxuICAgICAgICB2YXIgZGlzYWJsZUludGVyYWN0aW9uTGF5ZXIgPSBzZWxmLl90YXJnZXRFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLWRpc2FibGVJbnRlcmFjdGlvbicpO1xuICAgICAgICBpZiAoZGlzYWJsZUludGVyYWN0aW9uTGF5ZXIpIHtcbiAgICAgICAgICAgIGRpc2FibGVJbnRlcmFjdGlvbkxheWVyLnBhcmVudE5vZGUucmVtb3ZlQ2hpbGQoZGlzYWJsZUludGVyYWN0aW9uTGF5ZXIpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy9kaXNhYmxlIGludGVyYWN0aW9uXG4gICAgICAgIGlmICh0YXJnZXRFbGVtZW50LmRpc2FibGVJbnRlcmFjdGlvbikge1xuICAgICAgICAgICAgX2Rpc2FibGVJbnRlcmFjdGlvbi5jYWxsKHNlbGYpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gd2hlbiBpdCdzIHRoZSBmaXJzdCBzdGVwIG9mIHRvdXJcbiAgICAgICAgaWYgKHRoaXMuX2N1cnJlbnRTdGVwID09PSAwICYmIHRoaXMuX2ludHJvSXRlbXMubGVuZ3RoID4gMSkge1xuICAgICAgICAgICAgaWYgKHR5cGVvZiBza2lwVG9vbHRpcEJ1dHRvbiAhPT0gXCJ1bmRlZmluZWRcIiAmJiBza2lwVG9vbHRpcEJ1dHRvbiAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHNraXBUb29sdGlwQnV0dG9uLmNsYXNzTmFtZSA9IHRoaXMuX29wdGlvbnMuYnV0dG9uQ2xhc3MgKyAnIGludHJvanMtc2tpcGJ1dHRvbic7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodHlwZW9mIG5leHRUb29sdGlwQnV0dG9uICE9PSBcInVuZGVmaW5lZFwiICYmIG5leHRUb29sdGlwQnV0dG9uICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgbmV4dFRvb2x0aXBCdXR0b24uY2xhc3NOYW1lID0gdGhpcy5fb3B0aW9ucy5idXR0b25DbGFzcyArICcgaW50cm9qcy1uZXh0YnV0dG9uJztcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgaWYgKHRoaXMuX29wdGlvbnMuaGlkZVByZXYgPT09IHRydWUpIHtcbiAgICAgICAgICAgICAgICBpZiAodHlwZW9mIHByZXZUb29sdGlwQnV0dG9uICE9PSBcInVuZGVmaW5lZFwiICYmIHByZXZUb29sdGlwQnV0dG9uICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgIHByZXZUb29sdGlwQnV0dG9uLmNsYXNzTmFtZSA9IHRoaXMuX29wdGlvbnMuYnV0dG9uQ2xhc3MgKyAnIGludHJvanMtcHJldmJ1dHRvbiBpbnRyb2pzLWhpZGRlbic7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgbmV4dFRvb2x0aXBCdXR0b24gIT09IFwidW5kZWZpbmVkXCIgJiYgbmV4dFRvb2x0aXBCdXR0b24gIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgX2FkZENsYXNzKG5leHRUb29sdGlwQnV0dG9uLCAnaW50cm9qcy1mdWxsYnV0dG9uJyk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBpZiAodHlwZW9mIHByZXZUb29sdGlwQnV0dG9uICE9PSBcInVuZGVmaW5lZFwiICYmIHByZXZUb29sdGlwQnV0dG9uICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgIHByZXZUb29sdGlwQnV0dG9uLmNsYXNzTmFtZSA9IHRoaXMuX29wdGlvbnMuYnV0dG9uQ2xhc3MgKyAnIGludHJvanMtcHJldmJ1dHRvbiBpbnRyb2pzLWRpc2FibGVkJztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmICh0eXBlb2Ygc2tpcFRvb2x0aXBCdXR0b24gIT09IFwidW5kZWZpbmVkXCIgJiYgc2tpcFRvb2x0aXBCdXR0b24gIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICBza2lwVG9vbHRpcEJ1dHRvbi5pbm5lckhUTUwgPSB0aGlzLl9vcHRpb25zLnNraXBMYWJlbDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIGlmICh0aGlzLl9pbnRyb0l0ZW1zLmxlbmd0aCAtIDEgPT09IHRoaXMuX2N1cnJlbnRTdGVwIHx8IHRoaXMuX2ludHJvSXRlbXMubGVuZ3RoID09PSAxKSB7XG4gICAgICAgICAgICAvLyBsYXN0IHN0ZXAgb2YgdG91clxuICAgICAgICAgICAgaWYgKHR5cGVvZiBza2lwVG9vbHRpcEJ1dHRvbiAhPT0gXCJ1bmRlZmluZWRcIiAmJiBza2lwVG9vbHRpcEJ1dHRvbiAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHNraXBUb29sdGlwQnV0dG9uLmlubmVySFRNTCA9IHRoaXMuX29wdGlvbnMuZG9uZUxhYmVsO1xuICAgICAgICAgICAgICAgIC8vIGFkZGluZyBkb25lYnV0dG9uIGNsYXNzIGluIGFkZGl0aW9uIHRvIHNraXBidXR0b25cbiAgICAgICAgICAgICAgICBfYWRkQ2xhc3Moc2tpcFRvb2x0aXBCdXR0b24sICdpbnRyb2pzLWRvbmVidXR0b24nKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICh0eXBlb2YgcHJldlRvb2x0aXBCdXR0b24gIT09IFwidW5kZWZpbmVkXCIgJiYgcHJldlRvb2x0aXBCdXR0b24gIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICBwcmV2VG9vbHRpcEJ1dHRvbi5jbGFzc05hbWUgPSB0aGlzLl9vcHRpb25zLmJ1dHRvbkNsYXNzICsgJyBpbnRyb2pzLXByZXZidXR0b24nO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBpZiAodGhpcy5fb3B0aW9ucy5oaWRlTmV4dCA9PT0gdHJ1ZSkge1xuICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgbmV4dFRvb2x0aXBCdXR0b24gIT09IFwidW5kZWZpbmVkXCIgJiYgbmV4dFRvb2x0aXBCdXR0b24gIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgbmV4dFRvb2x0aXBCdXR0b24uY2xhc3NOYW1lID0gdGhpcy5fb3B0aW9ucy5idXR0b25DbGFzcyArICcgaW50cm9qcy1uZXh0YnV0dG9uIGludHJvanMtaGlkZGVuJztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgaWYgKHR5cGVvZiBwcmV2VG9vbHRpcEJ1dHRvbiAhPT0gXCJ1bmRlZmluZWRcIiAmJiBwcmV2VG9vbHRpcEJ1dHRvbiAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICBfYWRkQ2xhc3MocHJldlRvb2x0aXBCdXR0b24sICdpbnRyb2pzLWZ1bGxidXR0b24nKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgbmV4dFRvb2x0aXBCdXR0b24gIT09IFwidW5kZWZpbmVkXCIgJiYgbmV4dFRvb2x0aXBCdXR0b24gIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgbmV4dFRvb2x0aXBCdXR0b24uY2xhc3NOYW1lID0gdGhpcy5fb3B0aW9ucy5idXR0b25DbGFzcyArICcgaW50cm9qcy1uZXh0YnV0dG9uIGludHJvanMtZGlzYWJsZWQnO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIC8vIHN0ZXBzIGJldHdlZW4gc3RhcnQgYW5kIGVuZFxuICAgICAgICAgICAgaWYgKHR5cGVvZiBza2lwVG9vbHRpcEJ1dHRvbiAhPT0gXCJ1bmRlZmluZWRcIiAmJiBza2lwVG9vbHRpcEJ1dHRvbiAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHNraXBUb29sdGlwQnV0dG9uLmNsYXNzTmFtZSA9IHRoaXMuX29wdGlvbnMuYnV0dG9uQ2xhc3MgKyAnIGludHJvanMtc2tpcGJ1dHRvbic7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodHlwZW9mIHByZXZUb29sdGlwQnV0dG9uICE9PSBcInVuZGVmaW5lZFwiICYmIHByZXZUb29sdGlwQnV0dG9uICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgcHJldlRvb2x0aXBCdXR0b24uY2xhc3NOYW1lID0gdGhpcy5fb3B0aW9ucy5idXR0b25DbGFzcyArICcgaW50cm9qcy1wcmV2YnV0dG9uJztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICh0eXBlb2YgbmV4dFRvb2x0aXBCdXR0b24gIT09IFwidW5kZWZpbmVkXCIgJiYgbmV4dFRvb2x0aXBCdXR0b24gIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICBuZXh0VG9vbHRpcEJ1dHRvbi5jbGFzc05hbWUgPSB0aGlzLl9vcHRpb25zLmJ1dHRvbkNsYXNzICsgJyBpbnRyb2pzLW5leHRidXR0b24nO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKHR5cGVvZiBza2lwVG9vbHRpcEJ1dHRvbiAhPT0gXCJ1bmRlZmluZWRcIiAmJiBza2lwVG9vbHRpcEJ1dHRvbiAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHNraXBUb29sdGlwQnV0dG9uLmlubmVySFRNTCA9IHRoaXMuX29wdGlvbnMuc2tpcExhYmVsO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgcHJldlRvb2x0aXBCdXR0b24uc2V0QXR0cmlidXRlKCdyb2xlJywgJ2J1dHRvbicpO1xuICAgICAgICBuZXh0VG9vbHRpcEJ1dHRvbi5zZXRBdHRyaWJ1dGUoJ3JvbGUnLCAnYnV0dG9uJyk7XG4gICAgICAgIHNraXBUb29sdGlwQnV0dG9uLnNldEF0dHJpYnV0ZSgncm9sZScsICdidXR0b24nKTtcblxuICAgICAgICAvL1NldCBmb2N1cyBvbiBcIm5leHRcIiBidXR0b24sIHNvIHRoYXQgaGl0dGluZyBFbnRlciBhbHdheXMgbW92ZXMgeW91IG9udG8gdGhlIG5leHQgc3RlcFxuICAgICAgICBpZiAodHlwZW9mIG5leHRUb29sdGlwQnV0dG9uICE9PSBcInVuZGVmaW5lZFwiICYmIG5leHRUb29sdGlwQnV0dG9uICE9PSBudWxsKSB7XG4gICAgICAgICAgICBuZXh0VG9vbHRpcEJ1dHRvbi5mb2N1cygpO1xuICAgICAgICB9XG5cbiAgICAgICAgX3NldFNob3dFbGVtZW50KHRhcmdldEVsZW1lbnQpO1xuXG4gICAgICAgIGlmICh0eXBlb2YgKHRoaXMuX2ludHJvQWZ0ZXJDaGFuZ2VDYWxsYmFjaykgIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgICAgICB0aGlzLl9pbnRyb0FmdGVyQ2hhbmdlQ2FsbGJhY2suY2FsbCh0aGlzLCB0YXJnZXRFbGVtZW50LmVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogVG8gY2hhbmdlIHRoZSBzY3JvbGwgb2YgYHdpbmRvd2AgYWZ0ZXIgaGlnaGxpZ2h0aW5nIGFuIGVsZW1lbnRcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX3Njcm9sbFRvXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IHNjcm9sbFRvXG4gICAgICogQHBhcmFtIHtPYmplY3R9IHRhcmdldEVsZW1lbnRcbiAgICAgKiBAcGFyYW0ge09iamVjdH0gdG9vbHRpcExheWVyXG4gICAgICovXG4gICAgZnVuY3Rpb24gX3Njcm9sbFRvKHNjcm9sbFRvLCB0YXJnZXRFbGVtZW50LCB0b29sdGlwTGF5ZXIpIHtcbiAgICAgICAgaWYgKHNjcm9sbFRvID09PSAnb2ZmJykgcmV0dXJuO1xuICAgICAgICB2YXIgcmVjdDtcblxuICAgICAgICBpZiAoIXRoaXMuX29wdGlvbnMuc2Nyb2xsVG9FbGVtZW50KSByZXR1cm47XG5cbiAgICAgICAgaWYgKHNjcm9sbFRvID09PSAndG9vbHRpcCcpIHtcbiAgICAgICAgICAgIHJlY3QgPSB0b29sdGlwTGF5ZXIuZ2V0Qm91bmRpbmdDbGllbnRSZWN0KCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICByZWN0ID0gdGFyZ2V0RWxlbWVudC5lbGVtZW50LmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKCFfZWxlbWVudEluVmlld3BvcnQodGFyZ2V0RWxlbWVudC5lbGVtZW50KSkge1xuICAgICAgICAgICAgdmFyIHdpbkhlaWdodCA9IF9nZXRXaW5TaXplKCkuaGVpZ2h0O1xuICAgICAgICAgICAgdmFyIHRvcCA9IHJlY3QuYm90dG9tIC0gKHJlY3QuYm90dG9tIC0gcmVjdC50b3ApO1xuXG4gICAgICAgICAgICAvLyBUT0RPIChhZnNoaW5tKTogZG8gd2UgbmVlZCBzY3JvbGwgcGFkZGluZyBub3c/XG4gICAgICAgICAgICAvLyBJIGhhdmUgY2hhbmdlZCB0aGUgc2Nyb2xsIG9wdGlvbiBhbmQgbm93IGl0IHNjcm9sbHMgdGhlIHdpbmRvdyB0b1xuICAgICAgICAgICAgLy8gdGhlIGNlbnRlciBvZiB0aGUgdGFyZ2V0IGVsZW1lbnQgb3IgdG9vbHRpcC5cblxuICAgICAgICAgICAgaWYgKHRvcCA8IDAgfHwgdGFyZ2V0RWxlbWVudC5lbGVtZW50LmNsaWVudEhlaWdodCA+IHdpbkhlaWdodCkge1xuICAgICAgICAgICAgICAgIHdpbmRvdy5zY3JvbGxCeSgwLCByZWN0LnRvcCAtICgod2luSGVpZ2h0IC8gMikgLSAgKHJlY3QuaGVpZ2h0IC8gMikpIC0gdGhpcy5fb3B0aW9ucy5zY3JvbGxQYWRkaW5nKTsgLy8gMzBweCBwYWRkaW5nIGZyb20gZWRnZSB0byBsb29rIG5pY2VcblxuICAgICAgICAgICAgICAgIC8vU2Nyb2xsIGRvd25cbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgd2luZG93LnNjcm9sbEJ5KDAsIHJlY3QudG9wIC0gKCh3aW5IZWlnaHQgLyAyKSAtICAocmVjdC5oZWlnaHQgLyAyKSkgKyB0aGlzLl9vcHRpb25zLnNjcm9sbFBhZGRpbmcpOyAvLyAzMHB4IHBhZGRpbmcgZnJvbSBlZGdlIHRvIGxvb2sgbmljZVxuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogVG8gcmVtb3ZlIGFsbCBzaG93IGVsZW1lbnQocylcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX3JlbW92ZVNob3dFbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gX3JlbW92ZVNob3dFbGVtZW50KCkge1xuICAgICAgICB2YXIgZWxtcyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5pbnRyb2pzLXNob3dFbGVtZW50Jyk7XG5cbiAgICAgICAgX2ZvckVhY2goZWxtcywgZnVuY3Rpb24gKGVsbSkge1xuICAgICAgICAgICAgX3JlbW92ZUNsYXNzKGVsbSwgL2ludHJvanMtW2EtekEtWl0rL2cpO1xuICAgICAgICB9KTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBUbyBzZXQgdGhlIHNob3cgZWxlbWVudFxuICAgICAqIFRoaXMgZnVuY3Rpb24gc2V0IGEgcmVsYXRpdmUgKGluIG1vc3QgY2FzZXMpIHBvc2l0aW9uIGFuZCBjaGFuZ2VzIHRoZSB6LWluZGV4XG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9zZXRTaG93RWxlbWVudFxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSB0YXJnZXRFbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gX3NldFNob3dFbGVtZW50KHRhcmdldEVsZW1lbnQpIHtcbiAgICAgICAgdmFyIHBhcmVudEVsbTtcbiAgICAgICAgLy8gd2UgbmVlZCB0byBhZGQgdGhpcyBzaG93IGVsZW1lbnQgY2xhc3MgdG8gdGhlIHBhcmVudCBvZiBTVkcgZWxlbWVudHNcbiAgICAgICAgLy8gYmVjYXVzZSB0aGUgU1ZHIGVsZW1lbnRzIGNhbid0IGhhdmUgaW5kZXBlbmRlbnQgei1pbmRleFxuICAgICAgICBpZiAodGFyZ2V0RWxlbWVudC5lbGVtZW50IGluc3RhbmNlb2YgU1ZHRWxlbWVudCkge1xuICAgICAgICAgICAgcGFyZW50RWxtID0gdGFyZ2V0RWxlbWVudC5lbGVtZW50LnBhcmVudE5vZGU7XG5cbiAgICAgICAgICAgIHdoaWxlICh0YXJnZXRFbGVtZW50LmVsZW1lbnQucGFyZW50Tm9kZSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIGlmICghcGFyZW50RWxtLnRhZ05hbWUgfHwgcGFyZW50RWxtLnRhZ05hbWUudG9Mb3dlckNhc2UoKSA9PT0gJ2JvZHknKSBicmVhaztcblxuICAgICAgICAgICAgICAgIGlmIChwYXJlbnRFbG0udGFnTmFtZS50b0xvd2VyQ2FzZSgpID09PSAnc3ZnJykge1xuICAgICAgICAgICAgICAgICAgICBfYWRkQ2xhc3MocGFyZW50RWxtLCAnaW50cm9qcy1zaG93RWxlbWVudCBpbnRyb2pzLXJlbGF0aXZlUG9zaXRpb24nKTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBwYXJlbnRFbG0gPSBwYXJlbnRFbG0ucGFyZW50Tm9kZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIF9hZGRDbGFzcyh0YXJnZXRFbGVtZW50LmVsZW1lbnQsICdpbnRyb2pzLXNob3dFbGVtZW50Jyk7XG5cbiAgICAgICAgdmFyIGN1cnJlbnRFbGVtZW50UG9zaXRpb24gPSBfZ2V0UHJvcFZhbHVlKHRhcmdldEVsZW1lbnQuZWxlbWVudCwgJ3Bvc2l0aW9uJyk7XG4gICAgICAgIGlmIChjdXJyZW50RWxlbWVudFBvc2l0aW9uICE9PSAnYWJzb2x1dGUnICYmXG4gICAgICAgICAgICBjdXJyZW50RWxlbWVudFBvc2l0aW9uICE9PSAncmVsYXRpdmUnICYmXG4gICAgICAgICAgICBjdXJyZW50RWxlbWVudFBvc2l0aW9uICE9PSAnZml4ZWQnKSB7XG4gICAgICAgICAgICAvL2NoYW5nZSB0byBuZXcgaW50cm8gaXRlbVxuICAgICAgICAgICAgX2FkZENsYXNzKHRhcmdldEVsZW1lbnQuZWxlbWVudCwgJ2ludHJvanMtcmVsYXRpdmVQb3NpdGlvbicpO1xuICAgICAgICB9XG5cbiAgICAgICAgcGFyZW50RWxtID0gdGFyZ2V0RWxlbWVudC5lbGVtZW50LnBhcmVudE5vZGU7XG4gICAgICAgIHdoaWxlIChwYXJlbnRFbG0gIT09IG51bGwpIHtcbiAgICAgICAgICAgIGlmICghcGFyZW50RWxtLnRhZ05hbWUgfHwgcGFyZW50RWxtLnRhZ05hbWUudG9Mb3dlckNhc2UoKSA9PT0gJ2JvZHknKSBicmVhaztcblxuICAgICAgICAgICAgLy9maXggVGhlIFN0YWNraW5nIENvbnRleHQgcHJvYmxlbS5cbiAgICAgICAgICAgIC8vTW9yZSBkZXRhaWw6IGh0dHBzOi8vZGV2ZWxvcGVyLm1vemlsbGEub3JnL2VuLVVTL2RvY3MvV2ViL0d1aWRlL0NTUy9VbmRlcnN0YW5kaW5nX3pfaW5kZXgvVGhlX3N0YWNraW5nX2NvbnRleHRcbiAgICAgICAgICAgIHZhciB6SW5kZXggPSBfZ2V0UHJvcFZhbHVlKHBhcmVudEVsbSwgJ3otaW5kZXgnKTtcbiAgICAgICAgICAgIHZhciBvcGFjaXR5ID0gcGFyc2VGbG9hdChfZ2V0UHJvcFZhbHVlKHBhcmVudEVsbSwgJ29wYWNpdHknKSk7XG4gICAgICAgICAgICB2YXIgdHJhbnNmb3JtID0gX2dldFByb3BWYWx1ZShwYXJlbnRFbG0sICd0cmFuc2Zvcm0nKSB8fCBfZ2V0UHJvcFZhbHVlKHBhcmVudEVsbSwgJy13ZWJraXQtdHJhbnNmb3JtJykgfHwgX2dldFByb3BWYWx1ZShwYXJlbnRFbG0sICctbW96LXRyYW5zZm9ybScpIHx8IF9nZXRQcm9wVmFsdWUocGFyZW50RWxtLCAnLW1zLXRyYW5zZm9ybScpIHx8IF9nZXRQcm9wVmFsdWUocGFyZW50RWxtLCAnLW8tdHJhbnNmb3JtJyk7XG4gICAgICAgICAgICBpZiAoL1swLTldKy8udGVzdCh6SW5kZXgpIHx8IG9wYWNpdHkgPCAxIHx8ICh0cmFuc2Zvcm0gIT09ICdub25lJyAmJiB0cmFuc2Zvcm0gIT09IHVuZGVmaW5lZCkpIHtcbiAgICAgICAgICAgICAgICBfYWRkQ2xhc3MocGFyZW50RWxtLCAnaW50cm9qcy1maXhQYXJlbnQnKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgcGFyZW50RWxtID0gcGFyZW50RWxtLnBhcmVudE5vZGU7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBJdGVyYXRlcyBhcnJheXNcbiAgICAgKlxuICAgICAqIEBwYXJhbSB7QXJyYXl9IGFyclxuICAgICAqIEBwYXJhbSB7RnVuY3Rpb259IGZvckVhY2hGbmNcbiAgICAgKiBAcGFyYW0ge0Z1bmN0aW9ufSBjb21wbGV0ZUZuY1xuICAgICAqIEByZXR1cm4ge051bGx9XG4gICAgICovXG4gICAgZnVuY3Rpb24gX2ZvckVhY2goYXJyLCBmb3JFYWNoRm5jLCBjb21wbGV0ZUZuYykge1xuICAgICAgICAvLyBpbiBjYXNlIGFyciBpcyBhbiBlbXB0eSBxdWVyeSBzZWxlY3RvciBub2RlIGxpc3RcbiAgICAgICAgaWYgKGFycikge1xuICAgICAgICAgICAgZm9yICh2YXIgaSA9IDAsIGxlbiA9IGFyci5sZW5ndGg7IGkgPCBsZW47IGkrKykge1xuICAgICAgICAgICAgICAgIGZvckVhY2hGbmMoYXJyW2ldLCBpKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh0eXBlb2YoY29tcGxldGVGbmMpID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICBjb21wbGV0ZUZuYygpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogTWFyayBhbnkgb2JqZWN0IHdpdGggYW4gaW5jcmVtZW50aW5nIG51bWJlclxuICAgICAqIHVzZWQgZm9yIGtlZXBpbmcgdHJhY2sgb2Ygb2JqZWN0c1xuICAgICAqXG4gICAgICogQHBhcmFtIE9iamVjdCBvYmogICBBbnkgb2JqZWN0IG9yIERPTSBFbGVtZW50XG4gICAgICogQHBhcmFtIFN0cmluZyBrZXlcbiAgICAgKiBAcmV0dXJuIE9iamVjdFxuICAgICAqL1xuICAgIHZhciBfc3RhbXAgPSAoZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIga2V5cyA9IHt9O1xuICAgICAgICByZXR1cm4gZnVuY3Rpb24gc3RhbXAgKG9iaiwga2V5KSB7XG5cbiAgICAgICAgICAgIC8vIGdldCBncm91cCBrZXlcbiAgICAgICAgICAgIGtleSA9IGtleSB8fCAnaW50cm9qcy1zdGFtcCc7XG5cbiAgICAgICAgICAgIC8vIGVhY2ggZ3JvdXAgaW5jcmVtZW50cyBmcm9tIDBcbiAgICAgICAgICAgIGtleXNba2V5XSA9IGtleXNba2V5XSB8fCAwO1xuXG4gICAgICAgICAgICAvLyBzdGFtcCBvbmx5IG9uY2UgcGVyIG9iamVjdFxuICAgICAgICAgICAgaWYgKG9ialtrZXldID09PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgICAgICAvLyBpbmNyZW1lbnQga2V5IGZvciBlYWNoIG5ldyBvYmplY3RcbiAgICAgICAgICAgICAgICBvYmpba2V5XSA9IGtleXNba2V5XSsrO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICByZXR1cm4gb2JqW2tleV07XG4gICAgICAgIH07XG4gICAgfSkoKTtcblxuICAgIC8qKlxuICAgICAqIERPTUV2ZW50IEhhbmRsZXMgYWxsIERPTSBldmVudHNcbiAgICAgKlxuICAgICAqIG1ldGhvZHM6XG4gICAgICpcbiAgICAgKiBvbiAtIGFkZCBldmVudCBoYW5kbGVyXG4gICAgICogb2ZmIC0gcmVtb3ZlIGV2ZW50XG4gICAgICovXG4gICAgdmFyIERPTUV2ZW50ID0gKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgZnVuY3Rpb24gRE9NRXZlbnQgKCkge1xuICAgICAgICAgICAgdmFyIGV2ZW50c19rZXkgPSAnaW50cm9qc19ldmVudCc7XG5cbiAgICAgICAgICAgIC8qKlxuICAgICAgICAgICAgICogR2V0cyBhIHVuaXF1ZSBJRCBmb3IgYW4gZXZlbnQgbGlzdGVuZXJcbiAgICAgICAgICAgICAqXG4gICAgICAgICAgICAgKiBAcGFyYW0gT2JqZWN0IG9ialxuICAgICAgICAgICAgICogQHBhcmFtIFN0cmluZyB0eXBlICAgICAgICBldmVudCB0eXBlXG4gICAgICAgICAgICAgKiBAcGFyYW0gRnVuY3Rpb24gbGlzdGVuZXJcbiAgICAgICAgICAgICAqIEBwYXJhbSBPYmplY3QgY29udGV4dFxuICAgICAgICAgICAgICogQHJldHVybiBTdHJpbmdcbiAgICAgICAgICAgICAqL1xuICAgICAgICAgICAgdGhpcy5faWQgPSBmdW5jdGlvbiAob2JqLCB0eXBlLCBsaXN0ZW5lciwgY29udGV4dCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0eXBlICsgX3N0YW1wKGxpc3RlbmVyKSArIChjb250ZXh0ID8gJ18nICsgX3N0YW1wKGNvbnRleHQpIDogJycpO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgLyoqXG4gICAgICAgICAgICAgKiBBZGRzIGV2ZW50IGxpc3RlbmVyXG4gICAgICAgICAgICAgKlxuICAgICAgICAgICAgICogQHBhcmFtIE9iamVjdCBvYmpcbiAgICAgICAgICAgICAqIEBwYXJhbSBTdHJpbmcgdHlwZSAgICAgICAgZXZlbnQgdHlwZVxuICAgICAgICAgICAgICogQHBhcmFtIEZ1bmN0aW9uIGxpc3RlbmVyXG4gICAgICAgICAgICAgKiBAcGFyYW0gT2JqZWN0IGNvbnRleHRcbiAgICAgICAgICAgICAqIEBwYXJhbSBCb29sZWFuIHVzZUNhcHR1cmVcbiAgICAgICAgICAgICAqIEByZXR1cm4gbnVsbFxuICAgICAgICAgICAgICovXG4gICAgICAgICAgICB0aGlzLm9uID0gZnVuY3Rpb24gKG9iaiwgdHlwZSwgbGlzdGVuZXIsIGNvbnRleHQsIHVzZUNhcHR1cmUpIHtcbiAgICAgICAgICAgICAgICB2YXIgaWQgPSB0aGlzLl9pZC5hcHBseSh0aGlzLCBhcmd1bWVudHMpLFxuICAgICAgICAgICAgICAgICAgICBoYW5kbGVyID0gZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBsaXN0ZW5lci5jYWxsKGNvbnRleHQgfHwgb2JqLCBlIHx8IHdpbmRvdy5ldmVudCk7XG4gICAgICAgICAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgICAgICBpZiAoJ2FkZEV2ZW50TGlzdGVuZXInIGluIG9iaikge1xuICAgICAgICAgICAgICAgICAgICBvYmouYWRkRXZlbnRMaXN0ZW5lcih0eXBlLCBoYW5kbGVyLCB1c2VDYXB0dXJlKTtcbiAgICAgICAgICAgICAgICB9IGVsc2UgaWYgKCdhdHRhY2hFdmVudCcgaW4gb2JqKSB7XG4gICAgICAgICAgICAgICAgICAgIG9iai5hdHRhY2hFdmVudCgnb24nICsgdHlwZSwgaGFuZGxlcik7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgb2JqW2V2ZW50c19rZXldID0gb2JqW2V2ZW50c19rZXldIHx8IHt9O1xuICAgICAgICAgICAgICAgIG9ialtldmVudHNfa2V5XVtpZF0gPSBoYW5kbGVyO1xuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgLyoqXG4gICAgICAgICAgICAgKiBSZW1vdmVzIGV2ZW50IGxpc3RlbmVyXG4gICAgICAgICAgICAgKlxuICAgICAgICAgICAgICogQHBhcmFtIE9iamVjdCBvYmpcbiAgICAgICAgICAgICAqIEBwYXJhbSBTdHJpbmcgdHlwZSAgICAgICAgZXZlbnQgdHlwZVxuICAgICAgICAgICAgICogQHBhcmFtIEZ1bmN0aW9uIGxpc3RlbmVyXG4gICAgICAgICAgICAgKiBAcGFyYW0gT2JqZWN0IGNvbnRleHRcbiAgICAgICAgICAgICAqIEBwYXJhbSBCb29sZWFuIHVzZUNhcHR1cmVcbiAgICAgICAgICAgICAqIEByZXR1cm4gbnVsbFxuICAgICAgICAgICAgICovXG4gICAgICAgICAgICB0aGlzLm9mZiA9IGZ1bmN0aW9uIChvYmosIHR5cGUsIGxpc3RlbmVyLCBjb250ZXh0LCB1c2VDYXB0dXJlKSB7XG4gICAgICAgICAgICAgICAgdmFyIGlkID0gdGhpcy5faWQuYXBwbHkodGhpcywgYXJndW1lbnRzKSxcbiAgICAgICAgICAgICAgICAgICAgaGFuZGxlciA9IG9ialtldmVudHNfa2V5XSAmJiBvYmpbZXZlbnRzX2tleV1baWRdO1xuXG4gICAgICAgICAgICAgICAgaWYgKCdyZW1vdmVFdmVudExpc3RlbmVyJyBpbiBvYmopIHtcbiAgICAgICAgICAgICAgICAgICAgb2JqLnJlbW92ZUV2ZW50TGlzdGVuZXIodHlwZSwgaGFuZGxlciwgdXNlQ2FwdHVyZSk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIGlmICgnZGV0YWNoRXZlbnQnIGluIG9iaikge1xuICAgICAgICAgICAgICAgICAgICBvYmouZGV0YWNoRXZlbnQoJ29uJyArIHR5cGUsIGhhbmRsZXIpO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIG9ialtldmVudHNfa2V5XVtpZF0gPSBudWxsO1xuICAgICAgICAgICAgfTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBuZXcgRE9NRXZlbnQoKTtcbiAgICB9KSgpO1xuXG4gICAgLyoqXG4gICAgICogQXBwZW5kIGEgY2xhc3MgdG8gYW4gZWxlbWVudFxuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfYWRkQ2xhc3NcbiAgICAgKiBAcGFyYW0ge09iamVjdH0gZWxlbWVudFxuICAgICAqIEBwYXJhbSB7U3RyaW5nfSBjbGFzc05hbWVcbiAgICAgKiBAcmV0dXJucyBudWxsXG4gICAgICovXG4gICAgZnVuY3Rpb24gX2FkZENsYXNzKGVsZW1lbnQsIGNsYXNzTmFtZSkge1xuICAgICAgICBpZiAoZWxlbWVudCBpbnN0YW5jZW9mIFNWR0VsZW1lbnQpIHtcbiAgICAgICAgICAgIC8vIHN2Z1xuICAgICAgICAgICAgdmFyIHByZSA9IGVsZW1lbnQuZ2V0QXR0cmlidXRlKCdjbGFzcycpIHx8ICcnO1xuXG4gICAgICAgICAgICBlbGVtZW50LnNldEF0dHJpYnV0ZSgnY2xhc3MnLCBwcmUgKyAnICcgKyBjbGFzc05hbWUpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgaWYgKGVsZW1lbnQuY2xhc3NMaXN0ICE9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgICAgICAvLyBjaGVjayBmb3IgbW9kZXJuIGNsYXNzTGlzdCBwcm9wZXJ0eVxuICAgICAgICAgICAgICAgIHZhciBjbGFzc2VzID0gY2xhc3NOYW1lLnNwbGl0KCcgJyk7XG4gICAgICAgICAgICAgICAgX2ZvckVhY2goY2xhc3NlcywgZnVuY3Rpb24gKGNscykge1xuICAgICAgICAgICAgICAgICAgICBlbGVtZW50LmNsYXNzTGlzdC5hZGQoIGNscyApO1xuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfSBlbHNlIGlmICghZWxlbWVudC5jbGFzc05hbWUubWF0Y2goIGNsYXNzTmFtZSApKSB7XG4gICAgICAgICAgICAgICAgLy8gY2hlY2sgaWYgZWxlbWVudCBkb2Vzbid0IGFscmVhZHkgaGF2ZSBjbGFzc05hbWVcbiAgICAgICAgICAgICAgICBlbGVtZW50LmNsYXNzTmFtZSArPSAnICcgKyBjbGFzc05hbWU7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBSZW1vdmUgYSBjbGFzcyBmcm9tIGFuIGVsZW1lbnRcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX3JlbW92ZUNsYXNzXG4gICAgICogQHBhcmFtIHtPYmplY3R9IGVsZW1lbnRcbiAgICAgKiBAcGFyYW0ge1JlZ0V4cHxTdHJpbmd9IGNsYXNzTmFtZVJlZ2V4IGNhbiBiZSByZWdleCBvciBzdHJpbmdcbiAgICAgKiBAcmV0dXJucyBudWxsXG4gICAgICovXG4gICAgZnVuY3Rpb24gX3JlbW92ZUNsYXNzKGVsZW1lbnQsIGNsYXNzTmFtZVJlZ2V4KSB7XG4gICAgICAgIGlmIChlbGVtZW50IGluc3RhbmNlb2YgU1ZHRWxlbWVudCkge1xuICAgICAgICAgICAgdmFyIHByZSA9IGVsZW1lbnQuZ2V0QXR0cmlidXRlKCdjbGFzcycpIHx8ICcnO1xuXG4gICAgICAgICAgICBlbGVtZW50LnNldEF0dHJpYnV0ZSgnY2xhc3MnLCBwcmUucmVwbGFjZShjbGFzc05hbWVSZWdleCwgJycpLnJlcGxhY2UoL15cXHMrfFxccyskL2csICcnKSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBlbGVtZW50LmNsYXNzTmFtZSA9IGVsZW1lbnQuY2xhc3NOYW1lLnJlcGxhY2UoY2xhc3NOYW1lUmVnZXgsICcnKS5yZXBsYWNlKC9eXFxzK3xcXHMrJC9nLCAnJyk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBHZXQgYW4gZWxlbWVudCBDU1MgcHJvcGVydHkgb24gdGhlIHBhZ2VcbiAgICAgKiBUaGFua3MgdG8gSmF2YVNjcmlwdCBLaXQ6IGh0dHA6Ly93d3cuamF2YXNjcmlwdGtpdC5jb20vZGh0bWx0dXRvcnMvZGh0bWxjYXNjYWRlNC5zaHRtbFxuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfZ2V0UHJvcFZhbHVlXG4gICAgICogQHBhcmFtIHtPYmplY3R9IGVsZW1lbnRcbiAgICAgKiBAcGFyYW0ge1N0cmluZ30gcHJvcE5hbWVcbiAgICAgKiBAcmV0dXJucyBFbGVtZW50J3MgcHJvcGVydHkgdmFsdWVcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfZ2V0UHJvcFZhbHVlIChlbGVtZW50LCBwcm9wTmFtZSkge1xuICAgICAgICB2YXIgcHJvcFZhbHVlID0gJyc7XG4gICAgICAgIGlmIChlbGVtZW50LmN1cnJlbnRTdHlsZSkgeyAvL0lFXG4gICAgICAgICAgICBwcm9wVmFsdWUgPSBlbGVtZW50LmN1cnJlbnRTdHlsZVtwcm9wTmFtZV07XG4gICAgICAgIH0gZWxzZSBpZiAoZG9jdW1lbnQuZGVmYXVsdFZpZXcgJiYgZG9jdW1lbnQuZGVmYXVsdFZpZXcuZ2V0Q29tcHV0ZWRTdHlsZSkgeyAvL090aGVyc1xuICAgICAgICAgICAgcHJvcFZhbHVlID0gZG9jdW1lbnQuZGVmYXVsdFZpZXcuZ2V0Q29tcHV0ZWRTdHlsZShlbGVtZW50LCBudWxsKS5nZXRQcm9wZXJ0eVZhbHVlKHByb3BOYW1lKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vUHJldmVudCBleGNlcHRpb24gaW4gSUVcbiAgICAgICAgaWYgKHByb3BWYWx1ZSAmJiBwcm9wVmFsdWUudG9Mb3dlckNhc2UpIHtcbiAgICAgICAgICAgIHJldHVybiBwcm9wVmFsdWUudG9Mb3dlckNhc2UoKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBwcm9wVmFsdWU7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBDaGVja3MgdG8gc2VlIGlmIHRhcmdldCBlbGVtZW50IChvciBwYXJlbnRzKSBwb3NpdGlvbiBpcyBmaXhlZCBvciBub3RcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX2lzRml4ZWRcbiAgICAgKiBAcGFyYW0ge09iamVjdH0gZWxlbWVudFxuICAgICAqIEByZXR1cm5zIEJvb2xlYW5cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfaXNGaXhlZCAoZWxlbWVudCkge1xuICAgICAgICB2YXIgcCA9IGVsZW1lbnQucGFyZW50Tm9kZTtcblxuICAgICAgICBpZiAoIXAgfHwgcC5ub2RlTmFtZSA9PT0gJ0hUTUwnKSB7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoX2dldFByb3BWYWx1ZShlbGVtZW50LCAncG9zaXRpb24nKSA9PT0gJ2ZpeGVkJykge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gX2lzRml4ZWQocCk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogUHJvdmlkZXMgYSBjcm9zcy1icm93c2VyIHdheSB0byBnZXQgdGhlIHNjcmVlbiBkaW1lbnNpb25zXG4gICAgICogdmlhOiBodHRwOi8vc3RhY2tvdmVyZmxvdy5jb20vcXVlc3Rpb25zLzU4NjQ0NjcvaW50ZXJuZXQtZXhwbG9yZXItaW5uZXJoZWlnaHRcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX2dldFdpblNpemVcbiAgICAgKiBAcmV0dXJucyB7T2JqZWN0fSB3aWR0aCBhbmQgaGVpZ2h0IGF0dHJpYnV0ZXNcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfZ2V0V2luU2l6ZSgpIHtcbiAgICAgICAgaWYgKHdpbmRvdy5pbm5lcldpZHRoICE9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgIHJldHVybiB7IHdpZHRoOiB3aW5kb3cuaW5uZXJXaWR0aCwgaGVpZ2h0OiB3aW5kb3cuaW5uZXJIZWlnaHQgfTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHZhciBEID0gZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50O1xuICAgICAgICAgICAgcmV0dXJuIHsgd2lkdGg6IEQuY2xpZW50V2lkdGgsIGhlaWdodDogRC5jbGllbnRIZWlnaHQgfTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIENoZWNrIHRvIHNlZSBpZiB0aGUgZWxlbWVudCBpcyBpbiB0aGUgdmlld3BvcnQgb3Igbm90XG4gICAgICogaHR0cDovL3N0YWNrb3ZlcmZsb3cuY29tL3F1ZXN0aW9ucy8xMjM5OTkvaG93LXRvLXRlbGwtaWYtYS1kb20tZWxlbWVudC1pcy12aXNpYmxlLWluLXRoZS1jdXJyZW50LXZpZXdwb3J0XG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9lbGVtZW50SW5WaWV3cG9ydFxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSBlbFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9lbGVtZW50SW5WaWV3cG9ydChlbCkge1xuICAgICAgICB2YXIgcmVjdCA9IGVsLmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpO1xuXG4gICAgICAgIHJldHVybiAoXG4gICAgICAgICAgICByZWN0LnRvcCA+PSAwICYmXG4gICAgICAgICAgICByZWN0LmxlZnQgPj0gMCAmJlxuICAgICAgICAgICAgKHJlY3QuYm90dG9tKzgwKSA8PSB3aW5kb3cuaW5uZXJIZWlnaHQgJiYgLy8gYWRkIDgwIHRvIGdldCB0aGUgdGV4dCByaWdodFxuICAgICAgICAgICAgcmVjdC5yaWdodCA8PSB3aW5kb3cuaW5uZXJXaWR0aFxuICAgICAgICApO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEFkZCBvdmVybGF5IGxheWVyIHRvIHRoZSBwYWdlXG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9hZGRPdmVybGF5TGF5ZXJcbiAgICAgKiBAcGFyYW0ge09iamVjdH0gdGFyZ2V0RWxtXG4gICAgICovXG4gICAgZnVuY3Rpb24gX2FkZE92ZXJsYXlMYXllcih0YXJnZXRFbG0pIHtcbiAgICAgICAgdmFyIG92ZXJsYXlMYXllciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpLFxuICAgICAgICAgICAgc3R5bGVUZXh0ID0gJycsXG4gICAgICAgICAgICBzZWxmID0gdGhpcztcblxuICAgICAgICAvL3NldCBjc3MgY2xhc3MgbmFtZVxuICAgICAgICBvdmVybGF5TGF5ZXIuaWQgPSAndGlwanNPdmVybGF5JztcbiAgICAgICAgb3ZlcmxheUxheWVyLmNsYXNzTmFtZSA9ICdpbnRyb2pzLW92ZXJsYXknO1xuXG4gICAgICAgIC8vY2hlY2sgaWYgdGhlIHRhcmdldCBlbGVtZW50IGlzIGJvZHksIHdlIHNob3VsZCBjYWxjdWxhdGUgdGhlIHNpemUgb2Ygb3ZlcmxheSBsYXllciBpbiBhIGJldHRlciB3YXlcbiAgICAgICAgaWYgKCF0YXJnZXRFbG0udGFnTmFtZSB8fCB0YXJnZXRFbG0udGFnTmFtZS50b0xvd2VyQ2FzZSgpID09PSAnYm9keScpIHtcbiAgICAgICAgICAgIHN0eWxlVGV4dCArPSAndG9wOiAwO2JvdHRvbTogMDsgbGVmdDogMDtyaWdodDogMDtwb3NpdGlvbjogZml4ZWQ7JztcbiAgICAgICAgICAgIG92ZXJsYXlMYXllci5zdHlsZS5jc3NUZXh0ID0gc3R5bGVUZXh0O1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgLy9zZXQgb3ZlcmxheSBsYXllciBwb3NpdGlvblxuICAgICAgICAgICAgdmFyIGVsZW1lbnRQb3NpdGlvbiA9IF9nZXRPZmZzZXQodGFyZ2V0RWxtKTtcbiAgICAgICAgICAgIGlmIChlbGVtZW50UG9zaXRpb24pIHtcbiAgICAgICAgICAgICAgICBzdHlsZVRleHQgKz0gJ3dpZHRoOiAnICsgZWxlbWVudFBvc2l0aW9uLndpZHRoICsgJ3B4OyBoZWlnaHQ6JyArIGVsZW1lbnRQb3NpdGlvbi5oZWlnaHQgKyAncHg7IHRvcDonICsgZWxlbWVudFBvc2l0aW9uLnRvcCArICdweDtsZWZ0OiAnICsgZWxlbWVudFBvc2l0aW9uLmxlZnQgKyAncHg7JztcbiAgICAgICAgICAgICAgICBvdmVybGF5TGF5ZXIuc3R5bGUuY3NzVGV4dCA9IHN0eWxlVGV4dDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIHRhcmdldEVsbS5hcHBlbmRDaGlsZChvdmVybGF5TGF5ZXIpO1xuXG4gICAgICAgIG92ZXJsYXlMYXllci5vbmNsaWNrID0gZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICBpZiAoc2VsZi5fb3B0aW9ucy5leGl0T25PdmVybGF5Q2xpY2sgPT09IHRydWUpIHtcbiAgICAgICAgICAgICAgICBzZWxmLl9pbnRyb1NraXBDYWxsYmFjay5jYWxsKHNlbGYpO1xuICAgICAgICAgICAgICAgIF9leGl0SW50cm8uY2FsbChzZWxmLCB0YXJnZXRFbG0pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9O1xuXG4gICAgICAgIHdpbmRvdy5zZXRUaW1lb3V0KGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgc3R5bGVUZXh0ICs9ICdvcGFjaXR5OiAnICsgc2VsZi5fb3B0aW9ucy5vdmVybGF5T3BhY2l0eS50b1N0cmluZygpICsgJzsnO1xuICAgICAgICAgICAgb3ZlcmxheUxheWVyLnN0eWxlLmNzc1RleHQgPSBzdHlsZVRleHQ7XG4gICAgICAgIH0sIDEpO1xuXG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFJlbW92ZXMgb3BlbiBoaW50ICh0b29sdGlwIGhpbnQpXG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9yZW1vdmVIaW50VG9vbHRpcFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9yZW1vdmVIaW50VG9vbHRpcCgpIHtcbiAgICAgICAgdmFyIHRvb2x0aXAgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1oaW50UmVmZXJlbmNlJyk7XG5cbiAgICAgICAgaWYgKHRvb2x0aXApIHtcbiAgICAgICAgICAgIHZhciBzdGVwID0gdG9vbHRpcC5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RlcCcpO1xuICAgICAgICAgICAgdG9vbHRpcC5wYXJlbnROb2RlLnJlbW92ZUNoaWxkKHRvb2x0aXApO1xuICAgICAgICAgICAgcmV0dXJuIHN0ZXA7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBTdGFydCBwYXJzaW5nIGhpbnQgaXRlbXNcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSB0YXJnZXRFbG1cbiAgICAgKiBAbWV0aG9kIF9zdGFydEhpbnRcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfcG9wdWxhdGVIaW50cyh0YXJnZXRFbG0pIHtcblxuICAgICAgICB0aGlzLl9pbnRyb0l0ZW1zID0gW107XG5cbiAgICAgICAgaWYgKHRoaXMuX29wdGlvbnMuaGludHMpIHtcbiAgICAgICAgICAgIF9mb3JFYWNoKHRoaXMuX29wdGlvbnMuaGludHMsIGZ1bmN0aW9uIChoaW50KSB7XG4gICAgICAgICAgICAgICAgdmFyIGN1cnJlbnRJdGVtID0gX2Nsb25lT2JqZWN0KGhpbnQpO1xuXG4gICAgICAgICAgICAgICAgaWYgKHR5cGVvZihjdXJyZW50SXRlbS5lbGVtZW50KSA9PT0gJ3N0cmluZycpIHtcbiAgICAgICAgICAgICAgICAgICAgLy9ncmFiIHRoZSBlbGVtZW50IHdpdGggZ2l2ZW4gc2VsZWN0b3IgZnJvbSB0aGUgcGFnZVxuICAgICAgICAgICAgICAgICAgICBjdXJyZW50SXRlbS5lbGVtZW50ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihjdXJyZW50SXRlbS5lbGVtZW50KTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBjdXJyZW50SXRlbS5oaW50UG9zaXRpb24gPSBjdXJyZW50SXRlbS5oaW50UG9zaXRpb24gfHwgdGhpcy5fb3B0aW9ucy5oaW50UG9zaXRpb247XG4gICAgICAgICAgICAgICAgY3VycmVudEl0ZW0uaGludEFuaW1hdGlvbiA9IGN1cnJlbnRJdGVtLmhpbnRBbmltYXRpb24gfHwgdGhpcy5fb3B0aW9ucy5oaW50QW5pbWF0aW9uO1xuXG4gICAgICAgICAgICAgICAgaWYgKGN1cnJlbnRJdGVtLmVsZW1lbnQgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5faW50cm9JdGVtcy5wdXNoKGN1cnJlbnRJdGVtKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9LmJpbmQodGhpcykpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdmFyIGhpbnRzID0gdGFyZ2V0RWxtLnF1ZXJ5U2VsZWN0b3JBbGwoJypbZGF0YS1oaW50XScpO1xuXG4gICAgICAgICAgICBpZiAoIWhpbnRzIHx8ICFoaW50cy5sZW5ndGgpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vZmlyc3QgYWRkIGludHJvIGl0ZW1zIHdpdGggZGF0YS1zdGVwXG4gICAgICAgICAgICBfZm9yRWFjaChoaW50cywgZnVuY3Rpb24gKGN1cnJlbnRFbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgLy8gaGludCBhbmltYXRpb25cbiAgICAgICAgICAgICAgICB2YXIgaGludEFuaW1hdGlvbiA9IGN1cnJlbnRFbGVtZW50LmdldEF0dHJpYnV0ZSgnZGF0YS1oaW50YW5pbWF0aW9uJyk7XG5cbiAgICAgICAgICAgICAgICBpZiAoaGludEFuaW1hdGlvbikge1xuICAgICAgICAgICAgICAgICAgICBoaW50QW5pbWF0aW9uID0gKGhpbnRBbmltYXRpb24gPT09ICd0cnVlJyk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgaGludEFuaW1hdGlvbiA9IHRoaXMuX29wdGlvbnMuaGludEFuaW1hdGlvbjtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICB0aGlzLl9pbnRyb0l0ZW1zLnB1c2goe1xuICAgICAgICAgICAgICAgICAgICBlbGVtZW50OiBjdXJyZW50RWxlbWVudCxcbiAgICAgICAgICAgICAgICAgICAgaGludDogY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLWhpbnQnKSxcbiAgICAgICAgICAgICAgICAgICAgaGludFBvc2l0aW9uOiBjdXJyZW50RWxlbWVudC5nZXRBdHRyaWJ1dGUoJ2RhdGEtaGludHBvc2l0aW9uJykgfHwgdGhpcy5fb3B0aW9ucy5oaW50UG9zaXRpb24sXG4gICAgICAgICAgICAgICAgICAgIGhpbnRBbmltYXRpb246IGhpbnRBbmltYXRpb24sXG4gICAgICAgICAgICAgICAgICAgIHRvb2x0aXBDbGFzczogY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLXRvb2x0aXBjbGFzcycpLFxuICAgICAgICAgICAgICAgICAgICBwb3NpdGlvbjogY3VycmVudEVsZW1lbnQuZ2V0QXR0cmlidXRlKCdkYXRhLXBvc2l0aW9uJykgfHwgdGhpcy5fb3B0aW9ucy50b29sdGlwUG9zaXRpb25cbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH0uYmluZCh0aGlzKSk7XG4gICAgICAgIH1cblxuICAgICAgICBfYWRkSGludHMuY2FsbCh0aGlzKTtcblxuICAgICAgICAvKlxuICAgICAgICB0b2RvOlxuICAgICAgICB0aGVzZSBldmVudHMgc2hvdWxkIGJlIHJlbW92ZWQgYXQgc29tZSBwb2ludFxuICAgICAgICAqL1xuICAgICAgICBET01FdmVudC5vbihkb2N1bWVudCwgJ2NsaWNrJywgX3JlbW92ZUhpbnRUb29sdGlwLCB0aGlzLCBmYWxzZSk7XG4gICAgICAgIERPTUV2ZW50Lm9uKHdpbmRvdywgJ3Jlc2l6ZScsIF9yZUFsaWduSGludHMsIHRoaXMsIHRydWUpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFJlLWFsaWducyBhbGwgaGludCBlbGVtZW50c1xuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfcmVBbGlnbkhpbnRzXG4gICAgICovXG4gICAgZnVuY3Rpb24gX3JlQWxpZ25IaW50cygpIHtcbiAgICAgICAgX2ZvckVhY2godGhpcy5faW50cm9JdGVtcywgZnVuY3Rpb24gKGl0ZW0pIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YoaXRlbS50YXJnZXRFbGVtZW50KSA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIF9hbGlnbkhpbnRQb3NpdGlvbi5jYWxsKHRoaXMsIGl0ZW0uaGludFBvc2l0aW9uLCBpdGVtLmVsZW1lbnQsIGl0ZW0udGFyZ2V0RWxlbWVudCk7XG4gICAgICAgIH0uYmluZCh0aGlzKSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogR2V0IGEgcXVlcnlzZWxlY3RvciB3aXRoaW4gdGhlIGhpbnQgd3JhcHBlclxuICAgICAqXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IHNlbGVjdG9yXG4gICAgICogQHJldHVybiB7Tm9kZUxpc3R8QXJyYXl9XG4gICAgICovXG4gICAgZnVuY3Rpb24gX2hpbnRRdWVyeVNlbGVjdG9yQWxsKHNlbGVjdG9yKSB7XG4gICAgICAgIHZhciBoaW50c1dyYXBwZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcuaW50cm9qcy1oaW50cycpO1xuICAgICAgICByZXR1cm4gKGhpbnRzV3JhcHBlcikgPyBoaW50c1dyYXBwZXIucXVlcnlTZWxlY3RvckFsbChzZWxlY3RvcikgOiBbXTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBIaWRlIGEgaGludFxuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfaGlkZUhpbnRcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfaGlkZUhpbnQoc3RlcElkKSB7XG4gICAgICAgIHZhciBoaW50ID0gX2hpbnRRdWVyeVNlbGVjdG9yQWxsKCcuaW50cm9qcy1oaW50W2RhdGEtc3RlcD1cIicgKyBzdGVwSWQgKyAnXCJdJylbMF07XG5cbiAgICAgICAgX3JlbW92ZUhpbnRUb29sdGlwLmNhbGwodGhpcyk7XG5cbiAgICAgICAgaWYgKGhpbnQpIHtcbiAgICAgICAgICAgIF9hZGRDbGFzcyhoaW50LCAnaW50cm9qcy1oaWRlaGludCcpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gY2FsbCB0aGUgY2FsbGJhY2sgZnVuY3Rpb24gKGlmIGFueSlcbiAgICAgICAgaWYgKHR5cGVvZiAodGhpcy5faGludENsb3NlQ2FsbGJhY2spICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgdGhpcy5faGludENsb3NlQ2FsbGJhY2suY2FsbCh0aGlzLCBzdGVwSWQpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogSGlkZSBhbGwgaGludHNcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX2hpZGVIaW50c1xuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9oaWRlSGludHMoKSB7XG4gICAgICAgIHZhciBoaW50cyA9IF9oaW50UXVlcnlTZWxlY3RvckFsbCgnLmludHJvanMtaGludCcpO1xuXG4gICAgICAgIF9mb3JFYWNoKGhpbnRzLCBmdW5jdGlvbiAoaGludCkge1xuICAgICAgICAgICAgX2hpZGVIaW50LmNhbGwodGhpcywgaGludC5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RlcCcpKTtcbiAgICAgICAgfS5iaW5kKHRoaXMpKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBTaG93IGFsbCBoaW50c1xuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfc2hvd0hpbnRzXG4gICAgICovXG4gICAgZnVuY3Rpb24gX3Nob3dIaW50cygpIHtcbiAgICAgICAgdmFyIGhpbnRzID0gX2hpbnRRdWVyeVNlbGVjdG9yQWxsKCcuaW50cm9qcy1oaW50Jyk7XG5cbiAgICAgICAgaWYgKGhpbnRzICYmIGhpbnRzLmxlbmd0aCkge1xuICAgICAgICAgICAgX2ZvckVhY2goaGludHMsIGZ1bmN0aW9uIChoaW50KSB7XG4gICAgICAgICAgICAgICAgX3Nob3dIaW50LmNhbGwodGhpcywgaGludC5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RlcCcpKTtcbiAgICAgICAgICAgIH0uYmluZCh0aGlzKSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBfcG9wdWxhdGVIaW50cy5jYWxsKHRoaXMsIHRoaXMuX3RhcmdldEVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogU2hvdyBhIGhpbnRcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX3Nob3dIaW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gX3Nob3dIaW50KHN0ZXBJZCkge1xuICAgICAgICB2YXIgaGludCA9IF9oaW50UXVlcnlTZWxlY3RvckFsbCgnLmludHJvanMtaGludFtkYXRhLXN0ZXA9XCInICsgc3RlcElkICsgJ1wiXScpWzBdO1xuXG4gICAgICAgIGlmIChoaW50KSB7XG4gICAgICAgICAgICBfcmVtb3ZlQ2xhc3MoaGludCwgL2ludHJvanMtaGlkZWhpbnQvZyk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBSZW1vdmVzIGFsbCBoaW50IGVsZW1lbnRzIG9uIHRoZSBwYWdlXG4gICAgICogVXNlZnVsIHdoZW4geW91IHdhbnQgdG8gZGVzdHJveSB0aGUgZWxlbWVudHMgYW5kIGFkZCB0aGVtIGFnYWluIChlLmcuIGEgbW9kYWwgb3IgcG9wdXApXG4gICAgICpcbiAgICAgKiBAYXBpIHByaXZhdGVcbiAgICAgKiBAbWV0aG9kIF9yZW1vdmVIaW50c1xuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9yZW1vdmVIaW50cygpIHtcbiAgICAgICAgdmFyIGhpbnRzID0gX2hpbnRRdWVyeVNlbGVjdG9yQWxsKCcuaW50cm9qcy1oaW50Jyk7XG5cbiAgICAgICAgX2ZvckVhY2goaGludHMsIGZ1bmN0aW9uIChoaW50KSB7XG4gICAgICAgICAgICBfcmVtb3ZlSGludC5jYWxsKHRoaXMsIGhpbnQuZ2V0QXR0cmlidXRlKCdkYXRhLXN0ZXAnKSk7XG4gICAgICAgIH0uYmluZCh0aGlzKSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogUmVtb3ZlIG9uZSBzaW5nbGUgaGludCBlbGVtZW50IGZyb20gdGhlIHBhZ2VcbiAgICAgKiBVc2VmdWwgd2hlbiB5b3Ugd2FudCB0byBkZXN0cm95IHRoZSBlbGVtZW50IGFuZCBhZGQgdGhlbSBhZ2FpbiAoZS5nLiBhIG1vZGFsIG9yIHBvcHVwKVxuICAgICAqIFVzZSByZW1vdmVIaW50cyBpZiB5b3Ugd2FudCB0byByZW1vdmUgYWxsIGVsZW1lbnRzLlxuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfcmVtb3ZlSGludFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9yZW1vdmVIaW50KHN0ZXBJZCkge1xuICAgICAgICB2YXIgaGludCA9IF9oaW50UXVlcnlTZWxlY3RvckFsbCgnLmludHJvanMtaGludFtkYXRhLXN0ZXA9XCInICsgc3RlcElkICsgJ1wiXScpWzBdO1xuXG4gICAgICAgIGlmIChoaW50KSB7XG4gICAgICAgICAgICBoaW50LnBhcmVudE5vZGUucmVtb3ZlQ2hpbGQoaGludCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBBZGQgYWxsIGF2YWlsYWJsZSBoaW50cyB0byB0aGUgcGFnZVxuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfYWRkSGludHNcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfYWRkSGludHMoKSB7XG4gICAgICAgIHZhciBzZWxmID0gdGhpcztcblxuICAgICAgICB2YXIgaGludHNXcmFwcGVyID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLmludHJvanMtaGludHMnKTtcblxuICAgICAgICBpZiAoaGludHNXcmFwcGVyID09PSBudWxsKSB7XG4gICAgICAgICAgICBoaW50c1dyYXBwZXIgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcbiAgICAgICAgICAgIGhpbnRzV3JhcHBlci5jbGFzc05hbWUgPSAnaW50cm9qcy1oaW50cyc7XG4gICAgICAgIH1cblxuICAgICAgICAvKipcbiAgICAgICAgICogUmV0dXJucyBhbiBldmVudCBoYW5kbGVyIHVuaXF1ZSB0byB0aGUgaGludCBpdGVyYXRpb25cbiAgICAgICAgICpcbiAgICAgICAgICogQHBhcmFtIHtJbnRlZ2VyfSBpXG4gICAgICAgICAqIEByZXR1cm4ge0Z1bmN0aW9ufVxuICAgICAgICAgKi9cbiAgICAgICAgdmFyIGdldEhpbnRDbGljayA9IGZ1bmN0aW9uIChpKSB7XG4gICAgICAgICAgICByZXR1cm4gZnVuY3Rpb24oZSkge1xuICAgICAgICAgICAgICAgIHZhciBldnQgPSBlID8gZSA6IHdpbmRvdy5ldmVudDtcblxuICAgICAgICAgICAgICAgIGlmIChldnQuc3RvcFByb3BhZ2F0aW9uKSB7XG4gICAgICAgICAgICAgICAgICAgIGV2dC5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBpZiAoZXZ0LmNhbmNlbEJ1YmJsZSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICBldnQuY2FuY2VsQnViYmxlID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBfc2hvd0hpbnREaWFsb2cuY2FsbChzZWxmLCBpKTtcbiAgICAgICAgICAgIH07XG4gICAgICAgIH07XG5cbiAgICAgICAgX2ZvckVhY2godGhpcy5faW50cm9JdGVtcywgZnVuY3Rpb24oaXRlbSwgaSkge1xuICAgICAgICAgICAgLy8gYXZvaWQgYXBwZW5kIGEgaGludCB0d2ljZVxuICAgICAgICAgICAgaWYgKGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5pbnRyb2pzLWhpbnRbZGF0YS1zdGVwPVwiJyArIGkgKyAnXCJdJykpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHZhciBoaW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYScpO1xuICAgICAgICAgICAgX3NldEFuY2hvckFzQnV0dG9uKGhpbnQpO1xuXG4gICAgICAgICAgICBoaW50Lm9uY2xpY2sgPSBnZXRIaW50Q2xpY2soaSk7XG5cbiAgICAgICAgICAgIGhpbnQuY2xhc3NOYW1lID0gJ2ludHJvanMtaGludCc7XG5cbiAgICAgICAgICAgIGlmICghaXRlbS5oaW50QW5pbWF0aW9uKSB7XG4gICAgICAgICAgICAgICAgX2FkZENsYXNzKGhpbnQsICdpbnRyb2pzLWhpbnQtbm8tYW5pbScpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAvLyBoaW50J3MgcG9zaXRpb24gc2hvdWxkIGJlIGZpeGVkIGlmIHRoZSB0YXJnZXQgZWxlbWVudCdzIHBvc2l0aW9uIGlzIGZpeGVkXG4gICAgICAgICAgICBpZiAoX2lzRml4ZWQoaXRlbS5lbGVtZW50KSkge1xuICAgICAgICAgICAgICAgIF9hZGRDbGFzcyhoaW50LCAnaW50cm9qcy1maXhlZGhpbnQnKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdmFyIGhpbnREb3QgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcbiAgICAgICAgICAgIGhpbnREb3QuY2xhc3NOYW1lID0gJ2ludHJvanMtaGludC1kb3QnO1xuICAgICAgICAgICAgdmFyIGhpbnRQdWxzZSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgICAgICAgICAgaGludFB1bHNlLmNsYXNzTmFtZSA9ICdpbnRyb2pzLWhpbnQtcHVsc2UnO1xuXG4gICAgICAgICAgICBoaW50LmFwcGVuZENoaWxkKGhpbnREb3QpO1xuICAgICAgICAgICAgaGludC5hcHBlbmRDaGlsZChoaW50UHVsc2UpO1xuICAgICAgICAgICAgaGludC5zZXRBdHRyaWJ1dGUoJ2RhdGEtc3RlcCcsIGkpO1xuXG4gICAgICAgICAgICAvLyB3ZSBzd2FwIHRoZSBoaW50IGVsZW1lbnQgd2l0aCB0YXJnZXQgZWxlbWVudFxuICAgICAgICAgICAgLy8gYmVjYXVzZSBfc2V0SGVscGVyTGF5ZXJQb3NpdGlvbiB1c2VzIGBlbGVtZW50YCBwcm9wZXJ0eVxuICAgICAgICAgICAgaXRlbS50YXJnZXRFbGVtZW50ID0gaXRlbS5lbGVtZW50O1xuICAgICAgICAgICAgaXRlbS5lbGVtZW50ID0gaGludDtcblxuICAgICAgICAgICAgLy8gYWxpZ24gdGhlIGhpbnQgcG9zaXRpb25cbiAgICAgICAgICAgIF9hbGlnbkhpbnRQb3NpdGlvbi5jYWxsKHRoaXMsIGl0ZW0uaGludFBvc2l0aW9uLCBoaW50LCBpdGVtLnRhcmdldEVsZW1lbnQpO1xuXG4gICAgICAgICAgICBoaW50c1dyYXBwZXIuYXBwZW5kQ2hpbGQoaGludCk7XG4gICAgICAgIH0uYmluZCh0aGlzKSk7XG5cbiAgICAgICAgLy8gYWRkaW5nIHRoZSBoaW50cyB3cmFwcGVyXG4gICAgICAgIGRvY3VtZW50LmJvZHkuYXBwZW5kQ2hpbGQoaGludHNXcmFwcGVyKTtcblxuICAgICAgICAvLyBjYWxsIHRoZSBjYWxsYmFjayBmdW5jdGlvbiAoaWYgYW55KVxuICAgICAgICBpZiAodHlwZW9mICh0aGlzLl9oaW50c0FkZGVkQ2FsbGJhY2spICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgdGhpcy5faGludHNBZGRlZENhbGxiYWNrLmNhbGwodGhpcyk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBBbGlnbnMgaGludCBwb3NpdGlvblxuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfYWxpZ25IaW50UG9zaXRpb25cbiAgICAgKiBAcGFyYW0ge1N0cmluZ30gcG9zaXRpb25cbiAgICAgKiBAcGFyYW0ge09iamVjdH0gaGludFxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSBlbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gX2FsaWduSGludFBvc2l0aW9uKHBvc2l0aW9uLCBoaW50LCBlbGVtZW50KSB7XG4gICAgICAgIC8vIGdldC9jYWxjdWxhdGUgb2Zmc2V0IG9mIHRhcmdldCBlbGVtZW50XG4gICAgICAgIHZhciBvZmZzZXQgPSBfZ2V0T2Zmc2V0LmNhbGwodGhpcywgZWxlbWVudCk7XG4gICAgICAgIHZhciBpY29uV2lkdGggPSAyMDtcbiAgICAgICAgdmFyIGljb25IZWlnaHQgPSAyMDtcblxuICAgICAgICAvLyBhbGlnbiB0aGUgaGludCBlbGVtZW50XG4gICAgICAgIHN3aXRjaCAocG9zaXRpb24pIHtcbiAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICBjYXNlICd0b3AtbGVmdCc6XG4gICAgICAgICAgICAgICAgaGludC5zdHlsZS5sZWZ0ID0gb2Zmc2V0LmxlZnQgKyAncHgnO1xuICAgICAgICAgICAgICAgIGhpbnQuc3R5bGUudG9wID0gb2Zmc2V0LnRvcCArICdweCc7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlICd0b3AtcmlnaHQnOlxuICAgICAgICAgICAgICAgIGhpbnQuc3R5bGUubGVmdCA9IChvZmZzZXQubGVmdCArIG9mZnNldC53aWR0aCAtIGljb25XaWR0aCkgKyAncHgnO1xuICAgICAgICAgICAgICAgIGhpbnQuc3R5bGUudG9wID0gb2Zmc2V0LnRvcCArICdweCc7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlICdib3R0b20tbGVmdCc6XG4gICAgICAgICAgICAgICAgaGludC5zdHlsZS5sZWZ0ID0gb2Zmc2V0LmxlZnQgKyAncHgnO1xuICAgICAgICAgICAgICAgIGhpbnQuc3R5bGUudG9wID0gKG9mZnNldC50b3AgKyBvZmZzZXQuaGVpZ2h0IC0gaWNvbkhlaWdodCkgKyAncHgnO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgY2FzZSAnYm90dG9tLXJpZ2h0JzpcbiAgICAgICAgICAgICAgICBoaW50LnN0eWxlLmxlZnQgPSAob2Zmc2V0LmxlZnQgKyBvZmZzZXQud2lkdGggLSBpY29uV2lkdGgpICsgJ3B4JztcbiAgICAgICAgICAgICAgICBoaW50LnN0eWxlLnRvcCA9IChvZmZzZXQudG9wICsgb2Zmc2V0LmhlaWdodCAtIGljb25IZWlnaHQpICsgJ3B4JztcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ21pZGRsZS1sZWZ0JzpcbiAgICAgICAgICAgICAgICBoaW50LnN0eWxlLmxlZnQgPSBvZmZzZXQubGVmdCArICdweCc7XG4gICAgICAgICAgICAgICAgaGludC5zdHlsZS50b3AgPSAob2Zmc2V0LnRvcCArIChvZmZzZXQuaGVpZ2h0IC0gaWNvbkhlaWdodCkgLyAyKSArICdweCc7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlICdtaWRkbGUtcmlnaHQnOlxuICAgICAgICAgICAgICAgIGhpbnQuc3R5bGUubGVmdCA9IChvZmZzZXQubGVmdCArIG9mZnNldC53aWR0aCAtIGljb25XaWR0aCkgKyAncHgnO1xuICAgICAgICAgICAgICAgIGhpbnQuc3R5bGUudG9wID0gKG9mZnNldC50b3AgKyAob2Zmc2V0LmhlaWdodCAtIGljb25IZWlnaHQpIC8gMikgKyAncHgnO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgY2FzZSAnbWlkZGxlLW1pZGRsZSc6XG4gICAgICAgICAgICAgICAgaGludC5zdHlsZS5sZWZ0ID0gKG9mZnNldC5sZWZ0ICsgKG9mZnNldC53aWR0aCAtIGljb25XaWR0aCkgLyAyKSArICdweCc7XG4gICAgICAgICAgICAgICAgaGludC5zdHlsZS50b3AgPSAob2Zmc2V0LnRvcCArIChvZmZzZXQuaGVpZ2h0IC0gaWNvbkhlaWdodCkgLyAyKSArICdweCc7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlICdib3R0b20tbWlkZGxlJzpcbiAgICAgICAgICAgICAgICBoaW50LnN0eWxlLmxlZnQgPSAob2Zmc2V0LmxlZnQgKyAob2Zmc2V0LndpZHRoIC0gaWNvbldpZHRoKSAvIDIpICsgJ3B4JztcbiAgICAgICAgICAgICAgICBoaW50LnN0eWxlLnRvcCA9IChvZmZzZXQudG9wICsgb2Zmc2V0LmhlaWdodCAtIGljb25IZWlnaHQpICsgJ3B4JztcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ3RvcC1taWRkbGUnOlxuICAgICAgICAgICAgICAgIGhpbnQuc3R5bGUubGVmdCA9IChvZmZzZXQubGVmdCArIChvZmZzZXQud2lkdGggLSBpY29uV2lkdGgpIC8gMikgKyAncHgnO1xuICAgICAgICAgICAgICAgIGhpbnQuc3R5bGUudG9wID0gb2Zmc2V0LnRvcCArICdweCc7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBUcmlnZ2VycyB3aGVuIHVzZXIgY2xpY2tzIG9uIHRoZSBoaW50IGVsZW1lbnRcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX3Nob3dIaW50RGlhbG9nXG4gICAgICogQHBhcmFtIHtOdW1iZXJ9IHN0ZXBJZFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9zaG93SGludERpYWxvZyhzdGVwSWQpIHtcbiAgICAgICAgdmFyIGhpbnRFbGVtZW50ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLmludHJvanMtaGludFtkYXRhLXN0ZXA9XCInICsgc3RlcElkICsgJ1wiXScpO1xuICAgICAgICB2YXIgaXRlbSA9IHRoaXMuX2ludHJvSXRlbXNbc3RlcElkXTtcblxuICAgICAgICAvLyBjYWxsIHRoZSBjYWxsYmFjayBmdW5jdGlvbiAoaWYgYW55KVxuICAgICAgICBpZiAodHlwZW9mICh0aGlzLl9oaW50Q2xpY2tDYWxsYmFjaykgIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgICAgICB0aGlzLl9oaW50Q2xpY2tDYWxsYmFjay5jYWxsKHRoaXMsIGhpbnRFbGVtZW50LCBpdGVtLCBzdGVwSWQpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gcmVtb3ZlIGFsbCBvcGVuIHRvb2x0aXBzXG4gICAgICAgIHZhciByZW1vdmVkU3RlcCA9IF9yZW1vdmVIaW50VG9vbHRpcC5jYWxsKHRoaXMpO1xuXG4gICAgICAgIC8vIHRvIHRvZ2dsZSB0aGUgdG9vbHRpcFxuICAgICAgICBpZiAocGFyc2VJbnQocmVtb3ZlZFN0ZXAsIDEwKSA9PT0gc3RlcElkKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICB2YXIgdG9vbHRpcExheWVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG4gICAgICAgIHZhciB0b29sdGlwVGV4dExheWVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG4gICAgICAgIHZhciBhcnJvd0xheWVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG4gICAgICAgIHZhciByZWZlcmVuY2VMYXllciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuXG4gICAgICAgIHRvb2x0aXBMYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy10b29sdGlwJztcblxuICAgICAgICB0b29sdGlwTGF5ZXIub25jbGljayA9IGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgICAvL0lFOSAmIE90aGVyIEJyb3dzZXJzXG4gICAgICAgICAgICBpZiAoZS5zdG9wUHJvcGFnYXRpb24pIHtcbiAgICAgICAgICAgICAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgLy9JRTggYW5kIExvd2VyXG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICBlLmNhbmNlbEJ1YmJsZSA9IHRydWU7XG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG5cbiAgICAgICAgdG9vbHRpcFRleHRMYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy10b29sdGlwdGV4dCc7XG5cbiAgICAgICAgdmFyIHRvb2x0aXBXcmFwcGVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgncCcpO1xuICAgICAgICB0b29sdGlwV3JhcHBlci5pbm5lckhUTUwgPSBpdGVtLmhpbnQ7XG5cbiAgICAgICAgdmFyIGNsb3NlQnV0dG9uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYScpO1xuICAgICAgICBjbG9zZUJ1dHRvbi5jbGFzc05hbWUgPSB0aGlzLl9vcHRpb25zLmJ1dHRvbkNsYXNzO1xuICAgICAgICBjbG9zZUJ1dHRvbi5zZXRBdHRyaWJ1dGUoJ3JvbGUnLCAnYnV0dG9uJyk7XG4gICAgICAgIGNsb3NlQnV0dG9uLmlubmVySFRNTCA9IHRoaXMuX29wdGlvbnMuaGludEJ1dHRvbkxhYmVsO1xuICAgICAgICBjbG9zZUJ1dHRvbi5vbmNsaWNrID0gX2hpZGVIaW50LmJpbmQodGhpcywgc3RlcElkKTtcblxuICAgICAgICB0b29sdGlwVGV4dExheWVyLmFwcGVuZENoaWxkKHRvb2x0aXBXcmFwcGVyKTtcbiAgICAgICAgdG9vbHRpcFRleHRMYXllci5hcHBlbmRDaGlsZChjbG9zZUJ1dHRvbik7XG5cbiAgICAgICAgYXJyb3dMYXllci5jbGFzc05hbWUgPSAnaW50cm9qcy1hcnJvdyc7XG4gICAgICAgIHRvb2x0aXBMYXllci5hcHBlbmRDaGlsZChhcnJvd0xheWVyKTtcblxuICAgICAgICB0b29sdGlwTGF5ZXIuYXBwZW5kQ2hpbGQodG9vbHRpcFRleHRMYXllcik7XG5cbiAgICAgICAgLy8gc2V0IGN1cnJlbnQgc3RlcCBmb3IgX3BsYWNlVG9vbHRpcCBmdW5jdGlvblxuICAgICAgICB0aGlzLl9jdXJyZW50U3RlcCA9IGhpbnRFbGVtZW50LmdldEF0dHJpYnV0ZSgnZGF0YS1zdGVwJyk7XG5cbiAgICAgICAgLy8gYWxpZ24gcmVmZXJlbmNlIGxheWVyIHBvc2l0aW9uXG4gICAgICAgIHJlZmVyZW5jZUxheWVyLmNsYXNzTmFtZSA9ICdpbnRyb2pzLXRvb2x0aXBSZWZlcmVuY2VMYXllciBpbnRyb2pzLWhpbnRSZWZlcmVuY2UnO1xuICAgICAgICByZWZlcmVuY2VMYXllci5zZXRBdHRyaWJ1dGUoJ2RhdGEtc3RlcCcsIGhpbnRFbGVtZW50LmdldEF0dHJpYnV0ZSgnZGF0YS1zdGVwJykpO1xuICAgICAgICBfc2V0SGVscGVyTGF5ZXJQb3NpdGlvbi5jYWxsKHRoaXMsIHJlZmVyZW5jZUxheWVyKTtcblxuICAgICAgICByZWZlcmVuY2VMYXllci5hcHBlbmRDaGlsZCh0b29sdGlwTGF5ZXIpO1xuICAgICAgICBkb2N1bWVudC5ib2R5LmFwcGVuZENoaWxkKHJlZmVyZW5jZUxheWVyKTtcblxuICAgICAgICAvL3NldCBwcm9wZXIgcG9zaXRpb25cbiAgICAgICAgX3BsYWNlVG9vbHRpcC5jYWxsKHRoaXMsIGhpbnRFbGVtZW50LCB0b29sdGlwTGF5ZXIsIGFycm93TGF5ZXIsIG51bGwsIHRydWUpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEdldCBhbiBlbGVtZW50IHBvc2l0aW9uIG9uIHRoZSBwYWdlXG4gICAgICogVGhhbmtzIHRvIGBtZW91d2A6IGh0dHA6Ly9zdGFja292ZXJmbG93LmNvbS9hLzQ0MjQ3NC8zNzU5NjZcbiAgICAgKlxuICAgICAqIEBhcGkgcHJpdmF0ZVxuICAgICAqIEBtZXRob2QgX2dldE9mZnNldFxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSBlbGVtZW50XG4gICAgICogQHJldHVybnMgRWxlbWVudCdzIHBvc2l0aW9uIGluZm9cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfZ2V0T2Zmc2V0KGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIGJvZHkgPSBkb2N1bWVudC5ib2R5O1xuICAgICAgICB2YXIgZG9jRWwgPSBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQ7XG4gICAgICAgIHZhciBzY3JvbGxUb3AgPSB3aW5kb3cucGFnZVlPZmZzZXQgfHwgZG9jRWwuc2Nyb2xsVG9wIHx8IGJvZHkuc2Nyb2xsVG9wO1xuICAgICAgICB2YXIgc2Nyb2xsTGVmdCA9IHdpbmRvdy5wYWdlWE9mZnNldCB8fCBkb2NFbC5zY3JvbGxMZWZ0IHx8IGJvZHkuc2Nyb2xsTGVmdDtcbiAgICAgICAgdmFyIHggPSBlbGVtZW50LmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpO1xuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgdG9wOiB4LnRvcCArIHNjcm9sbFRvcCxcbiAgICAgICAgICAgIHdpZHRoOiB4LndpZHRoLFxuICAgICAgICAgICAgaGVpZ2h0OiB4LmhlaWdodCxcbiAgICAgICAgICAgIGxlZnQ6IHgubGVmdCArIHNjcm9sbExlZnRcbiAgICAgICAgfTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBGaW5kIHRoZSBuZWFyZXN0IHNjcm9sbGFibGUgcGFyZW50XG4gICAgICogY29waWVkIGZyb20gaHR0cHM6Ly9zdGFja292ZXJmbG93LmNvbS9xdWVzdGlvbnMvMzU5Mzk4ODYvZmluZC1maXJzdC1zY3JvbGxhYmxlLXBhcmVudFxuICAgICAqXG4gICAgICogQHBhcmFtIEVsZW1lbnQgZWxlbWVudFxuICAgICAqIEByZXR1cm4gRWxlbWVudFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9nZXRTY3JvbGxQYXJlbnQoZWxlbWVudCkge1xuICAgICAgICB2YXIgc3R5bGUgPSB3aW5kb3cuZ2V0Q29tcHV0ZWRTdHlsZShlbGVtZW50KTtcbiAgICAgICAgdmFyIGV4Y2x1ZGVTdGF0aWNQYXJlbnQgPSAoc3R5bGUucG9zaXRpb24gPT09IFwiYWJzb2x1dGVcIik7XG4gICAgICAgIHZhciBvdmVyZmxvd1JlZ2V4ID0gLyhhdXRvfHNjcm9sbCkvO1xuXG4gICAgICAgIGlmIChzdHlsZS5wb3NpdGlvbiA9PT0gXCJmaXhlZFwiKSByZXR1cm4gZG9jdW1lbnQuYm9keTtcblxuICAgICAgICBmb3IgKHZhciBwYXJlbnQgPSBlbGVtZW50OyAocGFyZW50ID0gcGFyZW50LnBhcmVudEVsZW1lbnQpOykge1xuICAgICAgICAgICAgc3R5bGUgPSB3aW5kb3cuZ2V0Q29tcHV0ZWRTdHlsZShwYXJlbnQpO1xuICAgICAgICAgICAgaWYgKGV4Y2x1ZGVTdGF0aWNQYXJlbnQgJiYgc3R5bGUucG9zaXRpb24gPT09IFwic3RhdGljXCIpIHtcbiAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmIChvdmVyZmxvd1JlZ2V4LnRlc3Qoc3R5bGUub3ZlcmZsb3cgKyBzdHlsZS5vdmVyZmxvd1kgKyBzdHlsZS5vdmVyZmxvd1gpKSByZXR1cm4gcGFyZW50O1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIGRvY3VtZW50LmJvZHk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogc2Nyb2xsIGEgc2Nyb2xsYWJsZSBlbGVtZW50IHRvIGEgY2hpbGQgZWxlbWVudFxuICAgICAqXG4gICAgICogQHBhcmFtIEVsZW1lbnQgcGFyZW50XG4gICAgICogQHBhcmFtIEVsZW1lbnQgZWxlbWVudFxuICAgICAqIEByZXR1cm4gTnVsbFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIF9zY3JvbGxQYXJlbnRUb0VsZW1lbnQgKHBhcmVudCwgZWxlbWVudCkge1xuICAgICAgICBwYXJlbnQuc2Nyb2xsVG9wID0gZWxlbWVudC5vZmZzZXRUb3AgLSBwYXJlbnQub2Zmc2V0VG9wO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEdldHMgdGhlIGN1cnJlbnQgcHJvZ3Jlc3MgcGVyY2VudGFnZVxuICAgICAqXG4gICAgICogQGFwaSBwcml2YXRlXG4gICAgICogQG1ldGhvZCBfZ2V0UHJvZ3Jlc3NcbiAgICAgKiBAcmV0dXJucyBjdXJyZW50IHByb2dyZXNzIHBlcmNlbnRhZ2VcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBfZ2V0UHJvZ3Jlc3MoKSB7XG4gICAgICAgIC8vIFN0ZXBzIGFyZSAwIGluZGV4ZWRcbiAgICAgICAgdmFyIGN1cnJlbnRTdGVwID0gcGFyc2VJbnQoKHRoaXMuX2N1cnJlbnRTdGVwICsgMSksIDEwKTtcbiAgICAgICAgcmV0dXJuICgoY3VycmVudFN0ZXAgLyB0aGlzLl9pbnRyb0l0ZW1zLmxlbmd0aCkgKiAxMDApO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIE92ZXJ3cml0ZXMgb2JqMSdzIHZhbHVlcyB3aXRoIG9iajIncyBhbmQgYWRkcyBvYmoyJ3MgaWYgbm9uIGV4aXN0ZW50IGluIG9iajFcbiAgICAgKiB2aWE6IGh0dHA6Ly9zdGFja292ZXJmbG93LmNvbS9xdWVzdGlvbnMvMTcxMjUxL2hvdy1jYW4taS1tZXJnZS1wcm9wZXJ0aWVzLW9mLXR3by1qYXZhc2NyaXB0LW9iamVjdHMtZHluYW1pY2FsbHlcbiAgICAgKlxuICAgICAqIEBwYXJhbSBvYmoxXG4gICAgICogQHBhcmFtIG9iajJcbiAgICAgKiBAcmV0dXJucyBvYmozIGEgbmV3IG9iamVjdCBiYXNlZCBvbiBvYmoxIGFuZCBvYmoyXG4gICAgICovXG4gICAgZnVuY3Rpb24gX21lcmdlT3B0aW9ucyhvYmoxLG9iajIpIHtcbiAgICAgICAgdmFyIG9iajMgPSB7fSxcbiAgICAgICAgICAgIGF0dHJuYW1lO1xuICAgICAgICBmb3IgKGF0dHJuYW1lIGluIG9iajEpIHsgb2JqM1thdHRybmFtZV0gPSBvYmoxW2F0dHJuYW1lXTsgfVxuICAgICAgICBmb3IgKGF0dHJuYW1lIGluIG9iajIpIHsgb2JqM1thdHRybmFtZV0gPSBvYmoyW2F0dHJuYW1lXTsgfVxuICAgICAgICByZXR1cm4gb2JqMztcbiAgICB9XG5cbiAgICB2YXIgaW50cm9KcyA9IGZ1bmN0aW9uICh0YXJnZXRFbG0pIHtcbiAgICAgICAgdmFyIGluc3RhbmNlO1xuXG4gICAgICAgIGlmICh0eXBlb2YgKHRhcmdldEVsbSkgPT09ICdvYmplY3QnKSB7XG4gICAgICAgICAgICAvL09rLCBjcmVhdGUgYSBuZXcgaW5zdGFuY2VcbiAgICAgICAgICAgIGluc3RhbmNlID0gbmV3IEludHJvSnModGFyZ2V0RWxtKTtcblxuICAgICAgICB9IGVsc2UgaWYgKHR5cGVvZiAodGFyZ2V0RWxtKSA9PT0gJ3N0cmluZycpIHtcbiAgICAgICAgICAgIC8vc2VsZWN0IHRoZSB0YXJnZXQgZWxlbWVudCB3aXRoIHF1ZXJ5IHNlbGVjdG9yXG4gICAgICAgICAgICB2YXIgdGFyZ2V0RWxlbWVudCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IodGFyZ2V0RWxtKTtcblxuICAgICAgICAgICAgaWYgKHRhcmdldEVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICBpbnN0YW5jZSA9IG5ldyBJbnRyb0pzKHRhcmdldEVsZW1lbnQpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1RoZXJlIGlzIG5vIGVsZW1lbnQgd2l0aCBnaXZlbiBzZWxlY3Rvci4nKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGluc3RhbmNlID0gbmV3IEludHJvSnMoZG9jdW1lbnQuYm9keSk7XG4gICAgICAgIH1cbiAgICAgICAgLy8gYWRkIGluc3RhbmNlIHRvIGxpc3Qgb2YgX2luc3RhbmNlc1xuICAgICAgICAvLyBwYXNzaW5nIGdyb3VwIHRvIF9zdGFtcCB0byBpbmNyZW1lbnRcbiAgICAgICAgLy8gZnJvbSAwIG9ud2FyZCBzb21ld2hhdCByZWxpYWJseVxuICAgICAgICBpbnRyb0pzLmluc3RhbmNlc1sgX3N0YW1wKGluc3RhbmNlLCAnaW50cm9qcy1pbnN0YW5jZScpIF0gPSBpbnN0YW5jZTtcblxuICAgICAgICByZXR1cm4gaW5zdGFuY2U7XG4gICAgfTtcblxuICAgIC8qKlxuICAgICAqIEN1cnJlbnQgSW50cm9KcyB2ZXJzaW9uXG4gICAgICpcbiAgICAgKiBAcHJvcGVydHkgdmVyc2lvblxuICAgICAqIEB0eXBlIFN0cmluZ1xuICAgICAqL1xuICAgIGludHJvSnMudmVyc2lvbiA9IFZFUlNJT047XG5cbiAgICAvKipcbiAgICAgKiBrZXktdmFsIG9iamVjdCBoZWxwZXIgZm9yIGludHJvSnMgaW5zdGFuY2VzXG4gICAgICpcbiAgICAgKiBAcHJvcGVydHkgaW5zdGFuY2VzXG4gICAgICogQHR5cGUgT2JqZWN0XG4gICAgICovXG4gICAgaW50cm9Kcy5pbnN0YW5jZXMgPSB7fTtcblxuICAgIC8vUHJvdG90eXBlXG4gICAgaW50cm9Kcy5mbiA9IEludHJvSnMucHJvdG90eXBlID0ge1xuICAgICAgICBjbG9uZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIG5ldyBJbnRyb0pzKHRoaXMpO1xuICAgICAgICB9LFxuICAgICAgICBzZXRPcHRpb246IGZ1bmN0aW9uKG9wdGlvbiwgdmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuX29wdGlvbnNbb3B0aW9uXSA9IHZhbHVlO1xuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIHNldE9wdGlvbnM6IGZ1bmN0aW9uKG9wdGlvbnMpIHtcbiAgICAgICAgICAgIHRoaXMuX29wdGlvbnMgPSBfbWVyZ2VPcHRpb25zKHRoaXMuX29wdGlvbnMsIG9wdGlvbnMpO1xuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIHN0YXJ0OiBmdW5jdGlvbiAoZ3JvdXApIHtcbiAgICAgICAgICAgIF9pbnRyb0ZvckVsZW1lbnQuY2FsbCh0aGlzLCB0aGlzLl90YXJnZXRFbGVtZW50LCBncm91cCk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgZ29Ub1N0ZXA6IGZ1bmN0aW9uKHN0ZXApIHtcbiAgICAgICAgICAgIF9nb1RvU3RlcC5jYWxsKHRoaXMsIHN0ZXApO1xuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIGFkZFN0ZXA6IGZ1bmN0aW9uKG9wdGlvbnMpIHtcbiAgICAgICAgICAgIGlmICghdGhpcy5fb3B0aW9ucy5zdGVwcykge1xuICAgICAgICAgICAgICAgIHRoaXMuX29wdGlvbnMuc3RlcHMgPSBbXTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdGhpcy5fb3B0aW9ucy5zdGVwcy5wdXNoKG9wdGlvbnMpO1xuXG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgYWRkU3RlcHM6IGZ1bmN0aW9uKHN0ZXBzKSB7XG4gICAgICAgICAgICBpZiAoIXN0ZXBzLmxlbmd0aCkgcmV0dXJuO1xuXG4gICAgICAgICAgICBmb3IodmFyIGluZGV4ID0gMDsgaW5kZXggPCBzdGVwcy5sZW5ndGg7IGluZGV4KyspIHtcbiAgICAgICAgICAgICAgICB0aGlzLmFkZFN0ZXAoc3RlcHNbaW5kZXhdKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIGdvVG9TdGVwTnVtYmVyOiBmdW5jdGlvbihzdGVwKSB7XG4gICAgICAgICAgICBfZ29Ub1N0ZXBOdW1iZXIuY2FsbCh0aGlzLCBzdGVwKTtcblxuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIG5leHRTdGVwOiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgIF9uZXh0U3RlcC5jYWxsKHRoaXMpO1xuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIHByZXZpb3VzU3RlcDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICBfcHJldmlvdXNTdGVwLmNhbGwodGhpcyk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgZXhpdDogZnVuY3Rpb24oZm9yY2UpIHtcbiAgICAgICAgICAgIF9leGl0SW50cm8uY2FsbCh0aGlzLCB0aGlzLl90YXJnZXRFbGVtZW50LCBmb3JjZSk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgcmVmcmVzaDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICBfcmVmcmVzaC5jYWxsKHRoaXMpO1xuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIG9uc3RhcnQ6IGZ1bmN0aW9uKHByb3ZpZGVkQ2FsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHByb3ZpZGVkQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faW50cm9TdGFydENhbGxiYWNrID0gcHJvdmlkZWRDYWxsYmFjaztcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdQcm92aWRlZCBjYWxsYmFjayBmb3Igb25iZWZvcmVjaGFuZ2Ugd2FzIG5vdCBhIGZ1bmN0aW9uJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgb25iZWZvcmVjaGFuZ2U6IGZ1bmN0aW9uKHByb3ZpZGVkQ2FsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHByb3ZpZGVkQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faW50cm9CZWZvcmVDaGFuZ2VDYWxsYmFjayA9IHByb3ZpZGVkQ2FsbGJhY2s7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcignUHJvdmlkZWQgY2FsbGJhY2sgZm9yIG9uYmVmb3JlY2hhbmdlIHdhcyBub3QgYSBmdW5jdGlvbicpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIG9uY2hhbmdlOiBmdW5jdGlvbihwcm92aWRlZENhbGxiYWNrKSB7XG4gICAgICAgICAgICBpZiAodHlwZW9mIChwcm92aWRlZENhbGxiYWNrKSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgIHRoaXMuX2ludHJvQ2hhbmdlQ2FsbGJhY2sgPSBwcm92aWRlZENhbGxiYWNrO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1Byb3ZpZGVkIGNhbGxiYWNrIGZvciBvbmNoYW5nZSB3YXMgbm90IGEgZnVuY3Rpb24uJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgb25hZnRlcmNoYW5nZTogZnVuY3Rpb24ocHJvdmlkZWRDYWxsYmFjaykge1xuICAgICAgICAgICAgaWYgKHR5cGVvZiAocHJvdmlkZWRDYWxsYmFjaykgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgICAgICAgICB0aGlzLl9pbnRyb0FmdGVyQ2hhbmdlQ2FsbGJhY2sgPSBwcm92aWRlZENhbGxiYWNrO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1Byb3ZpZGVkIGNhbGxiYWNrIGZvciBvbmFmdGVyY2hhbmdlIHdhcyBub3QgYSBmdW5jdGlvbicpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIG9uY29tcGxldGU6IGZ1bmN0aW9uKHByb3ZpZGVkQ2FsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHByb3ZpZGVkQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faW50cm9Db21wbGV0ZUNhbGxiYWNrID0gcHJvdmlkZWRDYWxsYmFjaztcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdQcm92aWRlZCBjYWxsYmFjayBmb3Igb25jb21wbGV0ZSB3YXMgbm90IGEgZnVuY3Rpb24uJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgb25oaW50c2FkZGVkOiBmdW5jdGlvbihwcm92aWRlZENhbGxiYWNrKSB7XG4gICAgICAgICAgICBpZiAodHlwZW9mIChwcm92aWRlZENhbGxiYWNrKSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgIHRoaXMuX2hpbnRzQWRkZWRDYWxsYmFjayA9IHByb3ZpZGVkQ2FsbGJhY2s7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcignUHJvdmlkZWQgY2FsbGJhY2sgZm9yIG9uaGludHNhZGRlZCB3YXMgbm90IGEgZnVuY3Rpb24uJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgb25oaW50Y2xpY2s6IGZ1bmN0aW9uKHByb3ZpZGVkQ2FsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHByb3ZpZGVkQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faGludENsaWNrQ2FsbGJhY2sgPSBwcm92aWRlZENhbGxiYWNrO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1Byb3ZpZGVkIGNhbGxiYWNrIGZvciBvbmhpbnRjbGljayB3YXMgbm90IGEgZnVuY3Rpb24uJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgb25oaW50Y2xvc2U6IGZ1bmN0aW9uKHByb3ZpZGVkQ2FsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHByb3ZpZGVkQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faGludENsb3NlQ2FsbGJhY2sgPSBwcm92aWRlZENhbGxiYWNrO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1Byb3ZpZGVkIGNhbGxiYWNrIGZvciBvbmhpbnRjbG9zZSB3YXMgbm90IGEgZnVuY3Rpb24uJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgb25leGl0OiBmdW5jdGlvbihwcm92aWRlZENhbGxiYWNrKSB7XG4gICAgICAgICAgICBpZiAodHlwZW9mIChwcm92aWRlZENhbGxiYWNrKSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgIHRoaXMuX2ludHJvRXhpdENhbGxiYWNrID0gcHJvdmlkZWRDYWxsYmFjaztcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdQcm92aWRlZCBjYWxsYmFjayBmb3Igb25leGl0IHdhcyBub3QgYSBmdW5jdGlvbi4nKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiB0aGlzO1xuICAgICAgICB9LFxuICAgICAgICBvbnNraXA6IGZ1bmN0aW9uKHByb3ZpZGVkQ2FsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHByb3ZpZGVkQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faW50cm9Ta2lwQ2FsbGJhY2sgPSBwcm92aWRlZENhbGxiYWNrO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1Byb3ZpZGVkIGNhbGxiYWNrIGZvciBvbnNraXAgd2FzIG5vdCBhIGZ1bmN0aW9uLicpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIG9uY2xvc2U6IGZ1bmN0aW9uKHByb3ZpZGVkQ2FsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHByb3ZpZGVkQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faW50cm9DbG9zZUNhbGxiYWNrID0gcHJvdmlkZWRDYWxsYmFjaztcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdQcm92aWRlZCBjYWxsYmFjayBmb3Igb25za2lwIHdhcyBub3QgYSBmdW5jdGlvbi4nKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiB0aGlzO1xuICAgICAgICB9LFxuICAgICAgICBvbmJlZm9yZWV4aXQ6IGZ1bmN0aW9uKHByb3ZpZGVkQ2FsbGJhY2spIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHByb3ZpZGVkQ2FsbGJhY2spID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5faW50cm9CZWZvcmVFeGl0Q2FsbGJhY2sgPSBwcm92aWRlZENhbGxiYWNrO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1Byb3ZpZGVkIGNhbGxiYWNrIGZvciBvbmJlZm9yZWV4aXQgd2FzIG5vdCBhIGZ1bmN0aW9uLicpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIGFkZEhpbnRzOiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgIF9wb3B1bGF0ZUhpbnRzLmNhbGwodGhpcywgdGhpcy5fdGFyZ2V0RWxlbWVudCk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgaGlkZUhpbnQ6IGZ1bmN0aW9uIChzdGVwSWQpIHtcbiAgICAgICAgICAgIF9oaWRlSGludC5jYWxsKHRoaXMsIHN0ZXBJZCk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgaGlkZUhpbnRzOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICBfaGlkZUhpbnRzLmNhbGwodGhpcyk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgc2hvd0hpbnQ6IGZ1bmN0aW9uIChzdGVwSWQpIHtcbiAgICAgICAgICAgIF9zaG93SGludC5jYWxsKHRoaXMsIHN0ZXBJZCk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgc2hvd0hpbnRzOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICBfc2hvd0hpbnRzLmNhbGwodGhpcyk7XG4gICAgICAgICAgICByZXR1cm4gdGhpcztcbiAgICAgICAgfSxcbiAgICAgICAgcmVtb3ZlSGludHM6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIF9yZW1vdmVIaW50cy5jYWxsKHRoaXMpO1xuICAgICAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICAgIH0sXG4gICAgICAgIHJlbW92ZUhpbnQ6IGZ1bmN0aW9uIChzdGVwSWQpIHtcbiAgICAgICAgICAgIF9yZW1vdmVIaW50LmNhbGwodGhpcywgc3RlcElkKTtcbiAgICAgICAgICAgIHJldHVybiB0aGlzO1xuICAgICAgICB9LFxuICAgICAgICBzaG93SGludERpYWxvZzogZnVuY3Rpb24gKHN0ZXBJZCkge1xuICAgICAgICAgICAgX3Nob3dIaW50RGlhbG9nLmNhbGwodGhpcywgc3RlcElkKTtcbiAgICAgICAgICAgIHJldHVybiB0aGlzO1xuICAgICAgICB9XG4gICAgfTtcblxuICAgIHJldHVybiBpbnRyb0pzO1xufSk7XG4iXSwibmFtZXMiOlsicmVxdWlyZSIsIl90eXBlb2YiLCJvIiwiU3ltYm9sIiwiaXRlcmF0b3IiLCJjb25zdHJ1Y3RvciIsInByb3RvdHlwZSIsImYiLCJleHBvcnRzIiwibW9kdWxlIiwiaW50cm9KcyIsImNvbnNvbGUiLCJ3YXJuIiwiYXBwbHkiLCJhcmd1bWVudHMiLCJkZWZpbmUiLCJhbWQiLCJnIiwid2luZG93IiwiZ2xvYmFsIiwic2VsZiIsIlZFUlNJT04iLCJJbnRyb0pzIiwib2JqIiwiX3RhcmdldEVsZW1lbnQiLCJfaW50cm9JdGVtcyIsIl9vcHRpb25zIiwibmV4dExhYmVsIiwicHJldkxhYmVsIiwic2tpcExhYmVsIiwiZG9uZUxhYmVsIiwiaGlkZVByZXYiLCJoaWRlTmV4dCIsInRvb2x0aXBQb3NpdGlvbiIsInRvb2x0aXBDbGFzcyIsImhpZ2hsaWdodENsYXNzIiwiZXhpdE9uRXNjIiwiZXhpdE9uT3ZlcmxheUNsaWNrIiwic2hvd1N0ZXBOdW1iZXJzIiwia2V5Ym9hcmROYXZpZ2F0aW9uIiwic2hvd0J1dHRvbnMiLCJzaG93QnVsbGV0cyIsInNob3dQcm9ncmVzcyIsInNjcm9sbFRvRWxlbWVudCIsInNjcm9sbFRvIiwic2Nyb2xsUGFkZGluZyIsIm92ZXJsYXlPcGFjaXR5IiwicG9zaXRpb25QcmVjZWRlbmNlIiwiZGlzYWJsZUludGVyYWN0aW9uIiwiaGVscGVyRWxlbWVudFBhZGRpbmciLCJoaW50UG9zaXRpb24iLCJoaW50QnV0dG9uTGFiZWwiLCJoaW50QW5pbWF0aW9uIiwiYnV0dG9uQ2xhc3MiLCJfaW50cm9Gb3JFbGVtZW50IiwidGFyZ2V0RWxtIiwiZ3JvdXAiLCJhbGxJbnRyb1N0ZXBzIiwicXVlcnlTZWxlY3RvckFsbCIsImludHJvSXRlbXMiLCJjbGFzc05hbWUiLCJpbmRleE9mIiwiZG9jdW1lbnQiLCJib2R5IiwibGVuZ3RoIiwiZ2V0QXR0cmlidXRlIiwic3RlcHMiLCJfZm9yRWFjaCIsInN0ZXAiLCJjdXJyZW50SXRlbSIsIl9jbG9uZU9iamVjdCIsImVsZW1lbnQiLCJxdWVyeVNlbGVjdG9yIiwiZmxvYXRpbmdFbGVtZW50UXVlcnkiLCJjcmVhdGVFbGVtZW50IiwiYXBwZW5kQ2hpbGQiLCJwb3NpdGlvbiIsInB1c2giLCJiaW5kIiwiZWxtc0xlbmd0aCIsImN1cnJlbnRFbGVtZW50Iiwic3R5bGUiLCJkaXNwbGF5IiwicGFyc2VJbnQiLCJpbnRybyIsImRlY29kZVVSSUNvbXBvbmVudCIsIm5leHRTdGVwIiwidGVtcEludHJvSXRlbXMiLCJ6Iiwic29ydCIsImEiLCJiIiwiX2FkZE92ZXJsYXlMYXllciIsImNhbGwiLCJfbmV4dFN0ZXAiLCJET01FdmVudCIsIm9uIiwiX29uS2V5RG93biIsIl9vblJlc2l6ZSIsIl9pbnRyb1N0YXJ0Q2FsbGJhY2siLCJ1bmRlZmluZWQiLCJyZWZyZXNoIiwiZSIsImNvZGUiLCJ3aGljaCIsImNoYXJDb2RlIiwia2V5Q29kZSIsIl9leGl0SW50cm8iLCJfcHJldmlvdXNTdGVwIiwidGFyZ2V0Iiwic3JjRWxlbWVudCIsIm1hdGNoIiwiX2N1cnJlbnRTdGVwIiwiX2ludHJvQ29tcGxldGVDYWxsYmFjayIsImNsaWNrIiwicHJldmVudERlZmF1bHQiLCJyZXR1cm5WYWx1ZSIsIm9iamVjdCIsIm5vZGVUeXBlIiwidGVtcCIsImtleSIsImpRdWVyeSIsIl9nb1RvU3RlcCIsIl9nb1RvU3RlcE51bWJlciIsIl9jdXJyZW50U3RlcE51bWJlciIsIl9kaXJlY3Rpb24iLCJpdGVtIiwiaSIsImNvbnRpbnVlU3RlcCIsIl9pbnRyb0JlZm9yZUNoYW5nZUNhbGxiYWNrIiwiX3Nob3dFbGVtZW50IiwiX3JlZnJlc2giLCJfc2V0SGVscGVyTGF5ZXJQb3NpdGlvbiIsIm9sZEhlbHBlck51bWJlckxheWVyIiwib2xkQXJyb3dMYXllciIsIm9sZHRvb2x0aXBDb250YWluZXIiLCJfcGxhY2VUb29sdGlwIiwiX3JlQWxpZ25IaW50cyIsInRhcmdldEVsZW1lbnQiLCJmb3JjZSIsImNvbnRpbnVlRXhpdCIsIl9pbnRyb0V4aXRDYWxsYmFjayIsIl9pbnRyb0JlZm9yZUV4aXRDYWxsYmFjayIsIm92ZXJsYXlMYXllcnMiLCJvdmVybGF5TGF5ZXIiLCJvcGFjaXR5Iiwic2V0VGltZW91dCIsInBhcmVudE5vZGUiLCJyZW1vdmVDaGlsZCIsImhlbHBlckxheWVyIiwicmVmZXJlbmNlTGF5ZXIiLCJkaXNhYmxlSW50ZXJhY3Rpb25MYXllciIsImZsb2F0aW5nRWxlbWVudCIsIl9yZW1vdmVTaG93RWxlbWVudCIsImZpeFBhcmVudHMiLCJwYXJlbnQiLCJfcmVtb3ZlQ2xhc3MiLCJvZmYiLCJ0b29sdGlwTGF5ZXIiLCJhcnJvd0xheWVyIiwiaGVscGVyTnVtYmVyTGF5ZXIiLCJoaW50TW9kZSIsInRvb2x0aXBDc3NDbGFzcyIsImN1cnJlbnRTdGVwT2JqIiwidG9vbHRpcE9mZnNldCIsInRhcmdldE9mZnNldCIsIndpbmRvd1NpemUiLCJjdXJyZW50VG9vbHRpcFBvc2l0aW9uIiwidG9wIiwicmlnaHQiLCJib3R0b20iLCJsZWZ0IiwibWFyZ2luTGVmdCIsIm1hcmdpblRvcCIsInJlcGxhY2UiLCJzZXRBdHRyaWJ1dGUiLCJfZGV0ZXJtaW5lQXV0b1Bvc2l0aW9uIiwidG9vbHRpcExheWVyU3R5bGVMZWZ0IiwiX2dldE9mZnNldCIsIl9nZXRXaW5TaXplIiwiX2FkZENsYXNzIiwidG9vbHRpcExheWVyU3R5bGVSaWdodCIsIl9jaGVja0xlZnQiLCJoZWlnaHQiLCJ0b29sdGlwTGF5ZXJTdHlsZUxlZnRSaWdodCIsIndpZHRoIiwiX2NoZWNrUmlnaHQiLCJkZXNpcmVkVG9vbHRpcFBvc2l0aW9uIiwicG9zc2libGVQb3NpdGlvbnMiLCJzbGljZSIsInRvb2x0aXBIZWlnaHQiLCJ0b29sdGlwV2lkdGgiLCJ0YXJnZXRFbGVtZW50UmVjdCIsImdldEJvdW5kaW5nQ2xpZW50UmVjdCIsImNhbGN1bGF0ZWRQb3NpdGlvbiIsIl9yZW1vdmVFbnRyeSIsImRlc2lyZWRBbGlnbm1lbnQiLCJwb3MiLCJoeXBoZW5JbmRleCIsInN1YnN0ciIsInNwbGl0IiwiX2RldGVybWluZUF1dG9BbGlnbm1lbnQiLCJvZmZzZXRMZWZ0IiwiaGFsZlRvb2x0aXBXaWR0aCIsIndpbldpZHRoIiwiTWF0aCIsIm1pbiIsInNjcmVlbiIsInBvc3NpYmxlQWxpZ25tZW50cyIsImNhbGN1bGF0ZWRBbGlnbm1lbnQiLCJzdHJpbmdBcnJheSIsInN0cmluZ1RvUmVtb3ZlIiwic3BsaWNlIiwiZWxlbWVudFBvc2l0aW9uIiwid2lkdGhIZWlnaHRQYWRkaW5nIiwiX2lzRml4ZWQiLCJfX2VsZW1lbnRQb3NpdGlvbiIsImNzc1RleHQiLCJfZGlzYWJsZUludGVyYWN0aW9uIiwiX3NldEFuY2hvckFzQnV0dG9uIiwiYW5jaG9yIiwidGFiSW5kZXgiLCJfaW50cm9DaGFuZ2VDYWxsYmFjayIsIm9sZEhlbHBlckxheWVyIiwib2xkUmVmZXJlbmNlTGF5ZXIiLCJuZXh0VG9vbHRpcEJ1dHRvbiIsInByZXZUb29sdGlwQnV0dG9uIiwic2tpcFRvb2x0aXBCdXR0b24iLCJzY3JvbGxQYXJlbnQiLCJfc2V0U2hvd0VsZW1lbnQiLCJvbGR0b29sdGlwTGF5ZXIiLCJsYXN0SW50cm9JdGVtIiwiX2dldFNjcm9sbFBhcmVudCIsIl9zY3JvbGxQYXJlbnRUb0VsZW1lbnQiLCJfbGFzdFNob3dFbGVtZW50VGltZXIiLCJjbGVhclRpbWVvdXQiLCJpbm5lckhUTUwiLCJfZ2V0UHJvZ3Jlc3MiLCJ0ZXN0IiwiZm9jdXMiLCJfc2Nyb2xsVG8iLCJ0b29sdGlwVGV4dExheWVyIiwiYnVsbGV0c0xheWVyIiwicHJvZ3Jlc3NMYXllciIsImJ1dHRvbnNMYXllciIsImlkIiwidWxDb250YWluZXIiLCJhbmNob3JDbGljayIsImdvVG9TdGVwIiwiaW5uZXJMaSIsImFuY2hvckxpbmsiLCJvbmNsaWNrIiwicHJvZ3Jlc3NCYXIiLCJjbG9zZUJ0biIsIl9pbnRyb0Nsb3NlQ2FsbGJhY2siLCJfaW50cm9Ta2lwQ2FsbGJhY2siLCJfaW50cm9BZnRlckNoYW5nZUNhbGxiYWNrIiwicmVjdCIsIl9lbGVtZW50SW5WaWV3cG9ydCIsIndpbkhlaWdodCIsImNsaWVudEhlaWdodCIsInNjcm9sbEJ5IiwiZWxtcyIsImVsbSIsInBhcmVudEVsbSIsIlNWR0VsZW1lbnQiLCJ0YWdOYW1lIiwidG9Mb3dlckNhc2UiLCJjdXJyZW50RWxlbWVudFBvc2l0aW9uIiwiX2dldFByb3BWYWx1ZSIsInpJbmRleCIsInBhcnNlRmxvYXQiLCJ0cmFuc2Zvcm0iLCJhcnIiLCJmb3JFYWNoRm5jIiwiY29tcGxldGVGbmMiLCJsZW4iLCJfc3RhbXAiLCJrZXlzIiwic3RhbXAiLCJldmVudHNfa2V5IiwiX2lkIiwidHlwZSIsImxpc3RlbmVyIiwiY29udGV4dCIsInVzZUNhcHR1cmUiLCJoYW5kbGVyIiwiZXZlbnQiLCJhZGRFdmVudExpc3RlbmVyIiwiYXR0YWNoRXZlbnQiLCJyZW1vdmVFdmVudExpc3RlbmVyIiwiZGV0YWNoRXZlbnQiLCJwcmUiLCJjbGFzc0xpc3QiLCJjbGFzc2VzIiwiY2xzIiwiYWRkIiwiY2xhc3NOYW1lUmVnZXgiLCJwcm9wTmFtZSIsInByb3BWYWx1ZSIsImN1cnJlbnRTdHlsZSIsImRlZmF1bHRWaWV3IiwiZ2V0Q29tcHV0ZWRTdHlsZSIsImdldFByb3BlcnR5VmFsdWUiLCJwIiwibm9kZU5hbWUiLCJpbm5lcldpZHRoIiwiaW5uZXJIZWlnaHQiLCJEIiwiZG9jdW1lbnRFbGVtZW50IiwiY2xpZW50V2lkdGgiLCJlbCIsInN0eWxlVGV4dCIsInRvU3RyaW5nIiwiX3JlbW92ZUhpbnRUb29sdGlwIiwidG9vbHRpcCIsIl9wb3B1bGF0ZUhpbnRzIiwiaGludHMiLCJoaW50IiwiX2FkZEhpbnRzIiwiX2FsaWduSGludFBvc2l0aW9uIiwiX2hpbnRRdWVyeVNlbGVjdG9yQWxsIiwic2VsZWN0b3IiLCJoaW50c1dyYXBwZXIiLCJfaGlkZUhpbnQiLCJzdGVwSWQiLCJfaGludENsb3NlQ2FsbGJhY2siLCJfaGlkZUhpbnRzIiwiX3Nob3dIaW50cyIsIl9zaG93SGludCIsIl9yZW1vdmVIaW50cyIsIl9yZW1vdmVIaW50IiwiZ2V0SGludENsaWNrIiwiZXZ0Iiwic3RvcFByb3BhZ2F0aW9uIiwiY2FuY2VsQnViYmxlIiwiX3Nob3dIaW50RGlhbG9nIiwiaGludERvdCIsImhpbnRQdWxzZSIsIl9oaW50c0FkZGVkQ2FsbGJhY2siLCJvZmZzZXQiLCJpY29uV2lkdGgiLCJpY29uSGVpZ2h0IiwiaGludEVsZW1lbnQiLCJfaGludENsaWNrQ2FsbGJhY2siLCJyZW1vdmVkU3RlcCIsInRvb2x0aXBXcmFwcGVyIiwiY2xvc2VCdXR0b24iLCJkb2NFbCIsInNjcm9sbFRvcCIsInBhZ2VZT2Zmc2V0Iiwic2Nyb2xsTGVmdCIsInBhZ2VYT2Zmc2V0IiwieCIsImV4Y2x1ZGVTdGF0aWNQYXJlbnQiLCJvdmVyZmxvd1JlZ2V4IiwicGFyZW50RWxlbWVudCIsIm92ZXJmbG93Iiwib3ZlcmZsb3dZIiwib3ZlcmZsb3dYIiwib2Zmc2V0VG9wIiwiY3VycmVudFN0ZXAiLCJfbWVyZ2VPcHRpb25zIiwib2JqMSIsIm9iajIiLCJvYmozIiwiYXR0cm5hbWUiLCJpbnN0YW5jZSIsIkVycm9yIiwiaW5zdGFuY2VzIiwidmVyc2lvbiIsImZuIiwiY2xvbmUiLCJzZXRPcHRpb24iLCJvcHRpb24iLCJ2YWx1ZSIsInNldE9wdGlvbnMiLCJvcHRpb25zIiwic3RhcnQiLCJhZGRTdGVwIiwiYWRkU3RlcHMiLCJpbmRleCIsImdvVG9TdGVwTnVtYmVyIiwicHJldmlvdXNTdGVwIiwiZXhpdCIsIm9uc3RhcnQiLCJwcm92aWRlZENhbGxiYWNrIiwib25iZWZvcmVjaGFuZ2UiLCJvbmNoYW5nZSIsIm9uYWZ0ZXJjaGFuZ2UiLCJvbmNvbXBsZXRlIiwib25oaW50c2FkZGVkIiwib25oaW50Y2xpY2siLCJvbmhpbnRjbG9zZSIsIm9uZXhpdCIsIm9uc2tpcCIsIm9uY2xvc2UiLCJvbmJlZm9yZWV4aXQiLCJhZGRIaW50cyIsImhpZGVIaW50IiwiaGlkZUhpbnRzIiwic2hvd0hpbnQiLCJzaG93SGludHMiLCJyZW1vdmVIaW50cyIsInJlbW92ZUhpbnQiLCJzaG93SGludERpYWxvZyJdLCJzb3VyY2VSb290IjoiIn0=