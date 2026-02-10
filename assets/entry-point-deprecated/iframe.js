function resize() {
    if (window.self === window.top) {
        return;
    }

    const elements = parent.document.getElementsByClassName('autoResizable');

    [].forEach.call(elements, function (elem) {
        if (elem.contentWindow.document === window.document) {
            let height;
            if (elem.dataset.body) {
                height = elem.contentWindow.document.getElementById(elem.dataset.body).scrollHeight;
            } else {
                height = elem.contentWindow.document.body.scrollHeight;
            }
            elem.style.height = height + 'px';
            elem.style.width = '100%';
        }
    });
}

resize();
window.addEventListener('resize', resize);
setInterval(function () {
    resize();
}, 500);