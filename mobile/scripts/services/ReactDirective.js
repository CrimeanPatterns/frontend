(function (window, document, angular, React) {
    function getReactComponent(name, $injector) {
        if (!name) {
            throw new Error('ReactComponent name attribute must be specified');
        }

        if (angular.isFunction(name)) {
            return name;
        }

        let reactComponent;
        try {
            reactComponent = $injector.get(name);
            // eslint-disable-next-line no-empty
        } catch(e) {}

        if (!reactComponent) {
            try {
                reactComponent = name.split('.').reduce(function(current, namePart) {
                    return current[namePart];
                }, window);
                // eslint-disable-next-line no-empty
            } catch (e) {}
        }

        if (!reactComponent) {
            throw Error('Cannot find react component ' + name);
        }

        return reactComponent;
    }

    function getPropName(prop) {
        return (Array.isArray(prop)) ? prop[0] : prop;
    }

    function getPropConfig(prop) {
        return (Array.isArray(prop)) ? prop[1] : {};
    }

    function findAttribute(attrs, propName) {
        const index = Object.keys(attrs).filter(function (attr) {
            return attr.toLowerCase() === propName.toLowerCase();
        })[0];

        return attrs[index];
    }

    function applyFunctions(obj, scope, propsConfig) {
        return Object.keys(obj || {}).reduce(function(prev, key) {
            const value = obj[key];
            const config = (propsConfig || {})[key] || {};
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

    function applied(fn, scope) {
        if (fn.wrappedInApply) {
            return fn;
        }
        const wrapped = function() {
            const args = arguments;
            const phase = scope.$root.$$phase;
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

    function watchProps (watchDepth, scope, watchExpressions, listener){
        const supportsWatchCollection = angular.isFunction(scope.$watchCollection);
        const supportsWatchGroup = angular.isFunction(scope.$watchGroup);

        const watchGroupExpressions = [];
        watchExpressions.forEach(function(expr){
            const actualExpr = getPropExpression(expr);
            const exprWatchDepth = getPropWatchDepth(watchDepth, expr);

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

    function getPropExpression(prop) {
        return (Array.isArray(prop)) ? prop[0] : prop;
    }

    function getPropWatchDepth(defaultWatch, prop) {
        const customWatchDepth = (
            Array.isArray(prop) &&
            angular.isObject(prop[1]) &&
            prop[1].watchDepth
        );
        return customWatchDepth || defaultWatch;
    }

    function renderComponent(component, props, scope, elem) {
        scope.$evalAsync(function() {
            React.render(React.createElement(component, props), elem[0]);
        });
    }

    function noop() {}

    angular.module('AwardWalletMobile')
        // Factory function to create directives for React components.
        //
        // With a component like this:
        //
        //     const module = angular.module('ace.react.components');
        //     module.value('Hello', React.createClass({
        //         render: function() {
        //             return <div>Hello {this.props.name}</div>;
        //         }
        //     }));
        //
        // A directive can be created and registered with:
        //
        //     module.directive('hello', function(reactDirective) {
        //         return reactDirective('Hello', ['name']);
        //     });
        //
        // Where the first argument is the injectable or globally accessible name of the React component
        // and the second argument is an array of property names to be watched and passed to the React component
        // as props.
        //
        // This directive can then be used like this:
        //
        //     <hello name="name"/>
        //
        .factory('ReactDirective', ['$injector', $injector => {
            return function(reactComponentName, reactStaticProps = [], directiveConfig = {}, lifecycle = {}, injectableProps ={}) {
                const {onLink = noop, onScopeDestroy = noop} = lifecycle;
                const directive = {
                    restrict: 'E',
                    replace: true,
                    link: function(scope, elem, attrs) {
                        const reactComponent = getReactComponent(reactComponentName, $injector);

                        // if props is not defined, fall back to use the React component's propTypes if present
                        let props = reactStaticProps || Object.keys(reactComponent.propTypes || {});
                        if (!props.length) {
                            const ngAttrNames = [];
                            angular.forEach(attrs.$attr, function (value, key) {
                                ngAttrNames.push(key);
                            });
                            props = ngAttrNames;
                        }

                        // for each of the properties, get their scope value and set it to scope.props
                        const renderMyComponent = function() {
                            let scopeProps = {}, config = {};
                            props.forEach(function(prop) {
                                const propName = getPropName(prop);
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
                        scope.$eval(onLink.bind(this, elem));

                        // cleanup when scope is destroyed
                        scope.$on('$destroy', function() {
                            scope.$eval(onScopeDestroy.bind(this, elem), {
                                unmountComponent: React.unmountComponentAtNode.bind(this, elem[0])
                            });
                        });
                    }
                };

                return angular.extend(directive, directiveConfig);
            };
        }])

        // Directive that allows React components to be used in Angular templates.
        //
        // Usage:
        //     <react-component name="Hello" props="name"/>
        //
        // This requires that there exists an injectable or globally available 'Hello' React component.
        // The 'props' attribute is optional and is passed to the component.
        //
        // The following would would create and register the component:
        //
        //     const module = angular.module('ace.react.components');
        //     module.value('Hello', React.createClass({
        //         render: function() {
        //             return <div>Hello {this.props.name}</div>;
        //         }
        //     }));
        .directive('reactComponent', ['$injector', ($injector) => {
            return {
                restrict: 'E',
                replace: true,
                link: function(scope, elem, attrs) {
                    const reactComponent = getReactComponent(attrs.name, $injector);

                    const renderMyComponent = function() {
                        const scopeProps = scope.$eval(attrs.props);
                        const props = applyFunctions(scopeProps, scope);

                        renderComponent(reactComponent, props, scope, elem);
                    };

                    // If there are props, re-render when they change
                    attrs.props ?
                        watchProps(attrs.watchDepth, scope, [attrs.props], renderMyComponent) :
                        renderMyComponent();

                    if (attrs.onLink) {
                        scope.$eval(attrs.onLink.bind(this, elem));
                    }

                    // cleanup when scope is destroyed
                    scope.$on('$destroy', function() {
                        if (!attrs.onScopeDestroy) {
                            React.unmountComponentAtNode(elem[0]);
                        } else {
                            scope.$eval(attrs.onScopeDestroy.bind(this, elem), {
                                unmountComponent: React.unmountComponentAtNode.bind(this, elem[0])
                            });
                        }
                    });
                }
            };
        }]);
})(window, document, angular, React);