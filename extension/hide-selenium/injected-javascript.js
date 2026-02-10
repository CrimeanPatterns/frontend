// Breaks out of the content script context by injecting a specially
// constructed script tag and injecting it into the page.
const runInPageContext = (method, ...args) => {
    // The stringified method which will be parsed as a function object.
    const stringifiedMethod = method instanceof Function
            ? method.toString()
            : `() => { ${method} }`;

    // The stringified arguments for the method as JS code that will reconstruct the array.
    const stringifiedArgs = JSON.stringify(args);

    // The full content of the script tag.
    const scriptContent = `
    // Parse and run the method with its arguments.
    (${stringifiedMethod})(...${stringifiedArgs});

    // Remove the script element to cover our tracks.
    document.currentScript.parentElement
      .removeChild(document.currentScript);
  `;

    // Create a script tag and inject it into the document.
    const scriptElement = document.createElement('script');
    scriptElement.innerHTML = scriptContent;
    document.documentElement.prepend(scriptElement);
};

const overwriteFingerprints = () => {

    // see FingerprintParams php class
    // will be replaced in ChromiumStarter::replaceFingerprintParams
    const fingerprintParams = {
        "platform": "MacIntel",
        "webglVendor": "ATI Technologies Inc.",
        "webglRenderer": "AMD Radeon R9 M370X OpenGL Engine",
        "replaceBrokenImage": true,
        "hairline": true,
        "fonts": [
            "Hoefler Text",
            "Monaco",
            "Georgia",
            "Trebuchet MS",
            "Verdana",
            "Andale Mono",
            "Monaco",
            "Courier New",
            "Courier",
        ],
        "firefox": false,
        "random": 0.6
    };

    console.log('hiding selenium, params', fingerprintParams);

    if (fingerprintParams.firefox) {
        navigator.webdriver = false;
    } else {
        const newProto = navigator.__proto__
        delete newProto.webdriver;
        navigator.__proto__ = newProto;
    }

    Object.defineProperty(navigator, 'language', {
        get: () => 'en-US'
    });

    Object.defineProperty(navigator, 'languages', {
        get: () => ['en-US', 'en']
    });

    Object.defineProperty(navigator, 'platform', {
        get: () => fingerprintParams.platform
    });

    class Plugin extends Array {
        constructor(description, filename, name, ...items) {
            super(items);
            this.description = description;
            this.filename = filename;
            this.name = name;
        }
    }

    console.log('fixing plugins main');

    function mockPluginsAndMimeTypes() {
        /* global MimeType MimeTypeArray PluginArray */

        // Disguise custom functions as being native
        const makeFnsNative = (fns = []) => {
            const oldCall = Function.prototype.call

            function call() {
                return oldCall.apply(this, arguments)
            }

            // eslint-disable-next-line
            Function.prototype.call = call

            const nativeToStringFunctionString = Error.toString().replace(
                    /Error/g,
                    'toString'
            )
            const oldToString = Function.prototype.toString

            function functionToString() {
                for (const fn of fns) {
                    if (this === fn.ref) {
                        return `function ${fn.name}() { [native code] }`
                    }
                }

                if (this === functionToString) {
                    return nativeToStringFunctionString
                }
                return oldCall.call(oldToString, this)
            }

            // eslint-disable-next-line
            Function.prototype.toString = functionToString
        }

        const mockedFns = []

        let mimeTypes = [
            {
                type: 'application/pdf',
                suffixes: 'pdf',
                description: '',
                __pluginName: 'Chrome PDF Viewer'
            },
            {
                type: 'application/x-google-chrome-pdf',
                suffixes: 'pdf',
                description: 'Portable Document Format',
                __pluginName: 'Chrome PDF Plugin'
            },
            {
                type: 'application/x-nacl',
                suffixes: '',
                description: 'Native Client Executable',
                enabledPlugin: Plugin,
                __pluginName: 'Native Client'
            },
            {
                type: 'application/x-pnacl',
                suffixes: '',
                description: 'Portable Native Client Executable',
                __pluginName: 'Native Client'
            }
        ];

        let plugins = [
            {
                name: 'Chrome PDF Plugin',
                filename: 'internal-pdf-viewer',
                description: 'Portable Document Format'
            },
            {
                name: 'Chrome PDF Viewer',
                filename: 'mhjfbmdgcfjbbpaeojofohoefgiehjai',
                description: ''
            },
            {
                name: 'Native Client',
                filename: 'internal-nacl-plugin',
                description: ''
            }
        ];

        if (fingerprintParams.firefox) {
            mimeTypes = [
                {
                    type: 'application/x-shockwave-flash"',
                    suffixes: 'swf',
                    description: 'Shockwave Flash',
                    __pluginName: 'Shockwave Flash'
                },
                {
                    type: 'application/futuresplash',
                    suffixes: 'spl',
                    description: 'FutureSplash Player',
                    __pluginName: 'Shockwave Flash'
                }
            ];

            plugins = [
                {
                    name: 'Shockwave Flash',
                    filename: 'Flash Player.plugin',
                    description: 'Shockwave Flash 21.0 r0'
                }
            ];
        }

        const fakeData = {
            mimeTypes: mimeTypes,
            plugins: plugins,
            fns: {
                namedItem: instanceName => {
                    // Returns the Plugin/MimeType with the specified name.
                    const fn = function (name) {
                        if (!arguments.length) {
                            throw new TypeError(
                                    `Failed to execute 'namedItem' on '${instanceName}': 1 argument required, but only 0 present.`
                            )
                        }
                        return this[name] || null
                    }
                    mockedFns.push({ref: fn, name: 'namedItem'})
                    return fn
                },
                item: instanceName => {
                    // Returns the Plugin/MimeType at the specified index into the array.
                    const fn = function (index) {
                        if (!arguments.length) {
                            throw new TypeError(
                                    `Failed to execute 'namedItem' on '${instanceName}': 1 argument required, but only 0 present.`
                            )
                        }
                        return this[index] || null
                    }
                    mockedFns.push({ref: fn, name: 'item'})
                    return fn
                },
                refresh: instanceName => {
                    // Refreshes all plugins on the current page, optionally reloading documents.
                    const fn = function () {
                        return undefined
                    }
                    mockedFns.push({ref: fn, name: 'refresh'})
                    return fn
                }
            }
        }
        // Poor mans _.pluck
        const getSubset = (keys, obj) =>
                keys.reduce((a, c) => ({...a, [c]: obj[c]}), {})

        function generateMimeTypeArray() {
            const arr = fakeData.mimeTypes
                    .map(obj => getSubset(['type', 'suffixes', 'description'], obj))
                    .map(obj => Object.setPrototypeOf(obj, MimeType.prototype))
            arr.forEach(obj => {
                arr[obj.type] = obj
            })

            // Mock functions
            arr.namedItem = fakeData.fns.namedItem('MimeTypeArray')
            arr.item = fakeData.fns.item('MimeTypeArray')

            return Object.setPrototypeOf(arr, MimeTypeArray.prototype)
        }

        const mimeTypeArray = generateMimeTypeArray()
        Object.defineProperty(navigator, 'mimeTypes', {
            get: () => mimeTypeArray
        })

        function generatePluginArray() {
            const arr = fakeData.plugins
                    .map(obj => getSubset(['name', 'filename', 'description'], obj))
                    .map(obj => {
                        const mimes = fakeData.mimeTypes.filter(
                                m => m.__pluginName === obj.name
                        )
                        // Add mimetypes
                        mimes.forEach((mime, index) => {
                            navigator.mimeTypes[mime.type].enabledPlugin = obj
                            obj[mime.type] = navigator.mimeTypes[mime.type]
                            obj[index] = navigator.mimeTypes[mime.type]
                        })
                        obj.length = mimes.length
                        obj.toString = function () {
                            return '[object Plugin]';
                        }
                        return obj
                    })
                    .map(obj => {
                        // Mock functions
                        obj.namedItem = fakeData.fns.namedItem('Plugin')
                        obj.item = fakeData.fns.item('Plugin')
                        return obj
                    })
                    .map(obj => Object.setPrototypeOf(obj, Plugin.prototype))
            arr.forEach(obj => {
                arr[obj.name] = obj
            })

            // Mock functions
            arr.namedItem = fakeData.fns.namedItem('PluginArray')
            arr.item = fakeData.fns.item('PluginArray')
            arr.refresh = fakeData.fns.refresh('PluginArray')
            arr.toString = function () {
                return '[object PluginArray]';
            }

            return Object.setPrototypeOf(arr, PluginArray.prototype)
        }

        const pluginArray = generatePluginArray()
        Object.defineProperty(navigator, 'plugins', {
            get: () => pluginArray
        })

        // Make mockedFns toString() representation resemble a native function
        makeFnsNative(mockedFns)
    }

    mockPluginsAndMimeTypes()

    const getParameter = WebGLRenderingContext.prototype.getParameter;
    WebGLRenderingContext.prototype.getParameter = function (parameter) {
        // UNMASKED_VENDOR_WEBGL
        if (parameter === 37445) {
            return fingerprintParams.webglVendor;
        }
        // UNMASKED_RENDERER_WEBGL
        if (parameter === 37446) {
            return fingerprintParams.webglRenderer;
        }

        return getParameter.call(this, parameter);
    };

    Object.defineProperty(Notification, 'permission', {
        get: () => 'default'
    });

    const originalQuery = window.navigator.permissions.query;
    navigator.permissions.query = (parameters) => (
            parameters.name === 'notifications' ? Promise.resolve({state: 'prompt'}) : originalQuery(parameters)
    );

    if (fingerprintParams.replaceBrokenImage) {
        console.log('replacing broken image');
        ['height', 'width'].forEach(property => {
            // store the existing descriptor
            const imageDescriptor = Object.getOwnPropertyDescriptor(HTMLImageElement.prototype, property);

            // redefine the property with a patched descriptor
            Object.defineProperty(HTMLImageElement.prototype, property, {
                ...imageDescriptor,
                get: function () {
                    // return an arbitrary non-zero dimension if the image failed to load
                    if (this.complete && this.naturalHeight == 0) {
                        return 16;
                    }
                    // otherwise, return the actual dimension
                    return imageDescriptor.get.apply(this);
                },
            });
        });
    }

    if (fingerprintParams.hairline) {
        // store the existing descriptor
        const elementDescriptor = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'offsetHeight');

        // redefine the property with a patched descriptor
        Object.defineProperty(HTMLDivElement.prototype, 'offsetHeight', {
            ...elementDescriptor,
            get: function () {
                if (this.id === 'modernizr') {
                    return 1;
                }
                return elementDescriptor.get.apply(this);
            },
        });
    }

    // patch fonts detection, see distill.js:277
    if (fingerprintParams.fonts.length > 0) {
        console.log('patching fonts');
        ['offsetHeight', 'offsetWidth'].forEach(property => {
            // store the existing descriptor
            console.log('patching ' + property);
            const originalDescriptor = Object.getOwnPropertyDescriptor(HTMLElement.prototype, property);

            // redefine the property with a patched descriptor
            Object.defineProperty(HTMLElement.prototype, property, {
                ...originalDescriptor,
                get: function () {
                    let result = originalDescriptor.get.apply(this);
//                    console.log('called patched ' + property + ' on font ' + this.style.fontFamily + ', original: ' + result);
                    const currentFont = this.style.fontFamily;
                    const listOfFonts = currentFont.indexOf(',') > 0;
                    if (!listOfFonts) {
                        this.originalFontResult = result;
                        this.originalFontFamily = currentFont;
                    }
                    let fontAllowed = false;
                    for (var i in fingerprintParams.fonts) {
                        if (currentFont.indexOf(fingerprintParams.fonts[i]) >= 0) {
                            result = Math.floor(result * (Math.random() * 0.4 + 0.8));
                            //                          console.log('patched to ' + result);
                            fontAllowed = true;
                        }
                    }
                    if (!fontAllowed && listOfFonts && this.originalFontResult && currentFont.indexOf(this.originalFontFamily) > 0) {
                        //                    console.log('font ' + currentFont + ' is not allowed, showed results for ' + this.originalFontFamily);
                        result = this.originalFontResult;
                    }
                    return result;
                },
            });
        });
    }

    if (!fingerprintParams.firefox) {
        window.chrome = {
            runtime: {}
        }
    }

    console.log('masking audio context');
    const context = {
      "BUFFER": null,
      "getChannelData": function (e) {
        const getChannelData = e.prototype.getChannelData;
        Object.defineProperty(e.prototype, "getChannelData", {
          "value": function () {
            const results_1 = getChannelData.apply(this, arguments);
            if (context.BUFFER !== results_1) {
              context.BUFFER = results_1;
              window.top.postMessage("audiocontext-fingerprint-defender-alert", '*');
              for (var i = 0; i < results_1.length; i += 100) {
                let index = Math.floor(fingerprintParams.random * i);
                results_1[index] = results_1[index] + fingerprintParams.random * 0.0000001;
              }
            }
            //
            return results_1;
          }
        });
      },
      "createAnalyser": function (e) {
        const createAnalyser = e.prototype.__proto__.createAnalyser;
        Object.defineProperty(e.prototype.__proto__, "createAnalyser", {
          "value": function () {
            const results_2 = createAnalyser.apply(this, arguments);
            const getFloatFrequencyData = results_2.__proto__.getFloatFrequencyData;
            Object.defineProperty(results_2.__proto__, "getFloatFrequencyData", {
              "value": function () {
                window.top.postMessage("audiocontext-fingerprint-defender-alert", '*');
                const results_3 = getFloatFrequencyData.apply(this, arguments);
                for (var i = 0; i < arguments[0].length; i += 100) {
                  let index = Math.floor(fingerprintParams.random * i);
                  arguments[0][index] = arguments[0][index] + fingerprintParams.random * 0.1;
                }
                //
                return results_3;
              }
            });
            //
            return results_2;
          }
        });
      }
    };
    context.getChannelData(AudioBuffer);
    context.createAnalyser(AudioContext);
    context.getChannelData(OfflineAudioContext);
    context.createAnalyser(OfflineAudioContext);

    console.log('hide-selenium complete');
    // const historyLength = Math.random() * 20 + 5;
    // for(let n = 0; n < historyLength; n++) {
    //     window.history.pushState({}, 'Awesome', '/' + n + '.some');
    // }

}

// Break out of the sandbox and run `overwriteFingerprints()` in the page context.
runInPageContext(overwriteFingerprints);