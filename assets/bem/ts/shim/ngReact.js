import {createElement} from 'react';
import {render, unmountComponentAtNode} from 'react-dom';
import angular from './angular-boot';

if (process.env.NODE_ENV === 'production') {
  const originalConsoleError = console.error;
  console.error = (...args) => {
      if (!/ReactDOM\.render is no longer supported in React 18/.test(args[0])) {
          originalConsoleError(...args);
      }
  };
}

// wraps a function with scope.$apply, if already applied just return
function applied(fn, scope) {
  if (fn.wrappedInApply) {
    return fn;
  }
  var wrapped = function() {
    var args = arguments;
    var phase = scope.$root.$$phase;
    if (phase === "$apply" || phase === "$digest") {
      return fn.apply(null, args);
    } else {
      return scope.$apply(function() {
        return fn.apply( null, args );
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
  return Object.keys(obj || {}).reduce(function(prev, key) {
    var value = obj[key];
    var config = (propsConfig || {})[key] || {};
    /**
     * wrap functions in a function that ensures they are scope.$applied
     * ensures that when function is called from a React component
     * the Angular digest cycle is run
     */
    prev[key] = angular.isFunction(value) && config.wrapApply !== false
        ? applied(value, scope)
        : value;

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
function watchProps (watchDepth, scope, watchExpressions, listener){
  var supportsWatchCollection = angular.isFunction(scope.$watchCollection);
  var supportsWatchGroup = angular.isFunction(scope.$watchGroup);

  var watchGroupExpressions = [];
  watchExpressions.forEach(function(expr){
    var actualExpr = getPropExpression(expr);
    var exprWatchDepth = getPropWatchDepth(watchDepth, expr);

    if (exprWatchDepth === 'collection' && supportsWatchCollection) {
      scope.$watchCollection(actualExpr, listener);
    } else if (exprWatchDepth === 'reference' && supportsWatchGroup) {
      watchGroupExpressions.push(actualExpr);
    } else {
      scope.$watch(actualExpr, listener, (exprWatchDepth !== 'reference'));
    }
  });

  if (watchGroupExpressions.length) {
    scope.$watchGroup(watchGroupExpressions, listener);
  }
}

// render React component, with scope[attrs.props] being passed in as the component props
function renderComponent(component, props, scope, elem) {
  scope.$evalAsync(function() {
    render(createElement(component, props), elem[0]);
  });
}

// get prop expression from prop (string or array)
function getPropExpression(prop) {
  return (Array.isArray(prop)) ? prop[0] : prop;
}

// get watch depth of prop (string or array)
function getPropWatchDepth(defaultWatch, prop) {
  var customWatchDepth = (
      Array.isArray(prop) &&
      angular.isObject(prop[1]) &&
      prop[1].watchDepth
  );
  return customWatchDepth || defaultWatch;
}

// get prop name from prop (string or array)
function getPropName(prop) {
  return (Array.isArray(prop)) ? prop[0] : prop;
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
  return (Array.isArray(prop)) ? prop[1] : {};
}

var reactDirective = function($injector) {
  return function(reactComponent, staticProps, conf, injectableProps) {
    const directive = {
      restrict: 'EA',
      replace: true,
      link: function(scope, elem, attrs) {
        // if props is not defined, fall back to use the React component's propTypes if present
        let props = staticProps || Object.keys(reactComponent.propTypes || {});
        if (!props.length) {
          const ngAttrNames = [];
          const directiveName = reactComponent.name.toLowerCase();
          angular.forEach(attrs.$attr, function (value, key) {
            if (key.toLowerCase() !== directiveName) {
              ngAttrNames.push(key);
            }
          });
          props = ngAttrNames;
        }

        // for each of the properties, get their scope value and set it to scope.props
        const renderMyComponent = function() {
          let scopeProps = {}, config = {};
          props.forEach(function(prop) {
            var propName = getPropName(prop);
            scopeProps[propName] = scope.$eval(findAttribute(attrs, propName));
            config[propName] = getPropConfig(prop);
          });
          scopeProps = applyFunctions(scopeProps, scope, config);
          scopeProps = angular.extend({}, scopeProps, injectableProps);
          renderComponent(reactComponent, scopeProps, scope, elem);
        };

        // watch each property name and trigger an update whenever something changes,
        // to update scope.props with new values
        const propExpressions = props.map(function(prop){
          return (Array.isArray(prop)) ?
              [attrs[getPropName(prop)], getPropConfig(prop)] :
              attrs[prop];
        });

        watchProps(attrs.watchDepth, scope, propExpressions, renderMyComponent);

        renderMyComponent();

        // cleanup when scope is destroyed
        scope.$on('$destroy', function() {
          if (!attrs.onScopeDestroy) {
            unmountComponentAtNode(elem[0]);
          } else {
            scope.$eval(attrs.onScopeDestroy, {
              unmountComponent: unmountComponentAtNode.bind(this, elem[0])
            });
          }
        });
      }
    };
    return angular.extend(directive, conf);
  };
};

angular
    .module('react', [])
    .factory('reactDirective', ['$injector', reactDirective]);
