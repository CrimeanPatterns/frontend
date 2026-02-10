(function init() {
    if (window.top === window) {
        const documentReady = function ready(callback) {
            // in case the document is already rendered
            if (document.readyState !== 'loading') {
                callback();
            } else if (document.addEventListener) {
                document.addEventListener('DOMContentLoaded', callback);
            }
        };
        const ownerUrlMask = /^http(s?):\/\/([a-z0-9\-]+\.)?awardwallet\.com[$\/]/i;

        let documentReadyTimeout;

        const delayDocumentReady = (cb) => {
            console.log('[CS]', 'delayDocumentReady');
            documentReadyTimeout = setTimeout(() => documentReady(cb), 3000);
        };

        const chrome = `
        const chrome = {
                runtime: {
                    sendMessage: (msg) => {
                        const {command, params} = msg;

                        console.log('[CP] sendMessage', msg);
                        safari.extension.dispatchMessage(command, {params});
                    },
                    onMessage: {
                        addListener: (cb) => {
                            safari.self.addEventListener('message', (event) => {
                                const {name: type, message: {params}} = event;

                                if (type === 'message') {
                                  cb({params});
                                }
                            });
                        },
                    },
                },
            };
        `;

        if (!document.location.href.match(ownerUrlMask)) {
            let pageLoaded = false;

            window.addEventListener('beforeunload', () => {
                console.log('[CS]', 'beforeunload', 'clearTimeout');
                clearTimeout(documentReadyTimeout);
            });

            console.log('[CS]', 'tabUpdated', 'loading');
            safari.extension.dispatchMessage('tabUpdated', {params: [{status: 'loading'}]});

            const pageLoadingTimeout = setTimeout(() => {
                console.log('[CS]', 'tabUpdated', 'complete');
                safari.extension.dispatchMessage('tabUpdated', {params: [{status: 'complete'}]});
                pageLoaded = true;
            }, 60 * 1000);

            delayDocumentReady(() => {
                clearTimeout(pageLoadingTimeout);
                if (pageLoaded === false) {
                    console.log('[CS]', 'tabUpdated', 'complete');
                    safari.extension.dispatchMessage('tabUpdated', {params: [{status: 'complete'}]});
                }
            });
        } else {
            document.addEventListener('init_modern_extension', (event) => {
                if (document.getElementById('extListenButton')) {
                    document.getElementById('extParams').value = '2.32';
                    document.getElementById('extCommand').value = 'content_scripts_ready';
                }
                document.dispatchEvent(new CustomEvent('content_scripts_ready', {}));
            });

            documentReady(() => {
                const {pathname} = document.location;

                if (pathname === '/extension-install') {
                    document.location.href = '/';
                }
            });
        }

        document.addEventListener('aw_page_to_bg_v2', (event) => {
            const {
                target: {URL},
                detail: {command, params, callbackId},
            } = event;

            if (!URL.match(ownerUrlMask)) {
                console.log('[BG] unknown sender');

                return;
            }

            safari.extension.dispatchMessage(command, {params, callbackId});
        });

        safari.self.addEventListener('message', (event) => {
            const {
                name: type,
                message: {params},
            } = event;

            if (type === 'executeScript') {
                const [code] = params;

                try {
                    eval(`(function(){
                          ${chrome}
                          ${code}
                      })();`);
                } catch (e) {
                    alert(e);
                }
            } else {
                const detail = {
                    type,
                    params,
                };

                document.dispatchEvent(
                    new CustomEvent('aw_content_to_page_v2', {
                        detail: JSON.stringify(detail),
                    }),
                );
            }
        });
    }
})();
