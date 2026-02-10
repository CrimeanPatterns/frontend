define('sockjs-boot', ['sockjs'], function(SockJS) {
    console.log('sockjs-boot');
    window.SockJS = SockJS;
    /* eslint-disable */
    if ('object' === typeof global && null !== global) {
        global.SockJS = SockJS;
    }
    /* eslint-enable */
    return SockJS;
});
