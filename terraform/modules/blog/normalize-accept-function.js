// normalize "accept" header for images, we only interested in webp, to improve cache hit ratio
function handler(event) {
    var request = event.request;

    var webpSupport = request.headers.accept && request.headers.accept.value.indexOf('webp') >= 0;

    if (!webpSupport) {
        // safari does not add image/webp to accept, although it supports it from >= 14 version on mobile, and from 16 on desktop
        // https://wordpress.org/support/topic/webp-support-detection-issue/
        // https://caniuse.com/webp
        var ua = request.headers['user-agent'] ? request.headers['user-agent'].value : '';
        var iOS = !!ua.match(/iPad/i) || !!ua.match(/iPhone/i);
        var webkit = !!ua.match(/WebKit/i);
        var iOSSafari = iOS && webkit && !ua.match(/CriOS/i);
        var version = 0
        var versionInfo = ua.match(/Version\/(\d{2}).*Safari/i);

        if (versionInfo) {
            version = parseInt(versionInfo[1])
        }

        if (iOSSafari && version >= 14) {
            webpSupport = true
        }

        if (version >= 16) { // desktop
            webpSupport = true
        }
    }


    if (webpSupport) {
        request.headers['accept'] = {value: 'image/webp'};
        return request;
    }

    request.headers['accept'] = {value: 'text/html'};

    return request;
}