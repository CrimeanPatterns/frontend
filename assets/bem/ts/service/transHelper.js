import { extractOptions } from './env';
// noinspection NpmUsedModulesInstalled
import $ from 'jquery';

function base64_decode(data) {
    let b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
    let o1,
        o2,
        o3,
        h1,
        h2,
        h3,
        h4,
        bits,
        i = 0,
        enc = '';

    do {
        h1 = b64.indexOf(data.charAt(i++));
        h2 = b64.indexOf(data.charAt(i++));
        h3 = b64.indexOf(data.charAt(i++));
        h4 = b64.indexOf(data.charAt(i++));

        bits = (h1 << 18) | (h2 << 12) | (h3 << 6) | h4;

        o1 = (bits >> 16) & 0xff;
        o2 = (bits >> 8) & 0xff;
        o3 = bits & 0xff;

        if (h3 === 64) enc += String.fromCharCode(o1);
        else if (h4 === 64) enc += String.fromCharCode(o1, o2);
        else enc += String.fromCharCode(o1, o2, o3);
    } while (i < data.length);

    return enc;
}

let note;
let lastTootltip;

function replaceTags(parent) {
    let children = parent.childNodes;

    for (let i = 0; i < children.length; i++) {
        if (
            (children[i].nodeType === 3 && children[i].data && children[i].data.match(/<mark.+?<\/mark>/s)) ||
            children[i].nodeName === 'MARK'
        ) {
            children[i].parentNode.innerHTML = children[i].parentNode.innerHTML
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>');
        }
        if (children[i].childNodes.length !== 0 && children[i].nodeType !== 3) {
            replaceTags(children[i]);
        }
    }
}

function enable() {
    document.addEventListener(
        'animationstart',
        function (event) {
            if (event.animationName === 'nodeInserted') {
                let placeholder;
                if (
                    event.target.placeholder &&
                    (placeholder = /<mark.+?>(.+?)<\/mark>/.exec(event.target.placeholder))
                ) {
                    let tag = /data-title="(.*?)"/.exec(placeholder[0]);
                    event.target.setAttribute('data-title', tag[1]);
                    event.target.placeholder = placeholder[1];
                }

                let children = event.target.childNodes,
                    mark;

                replaceTags(event.target);

                for (let i = 0; i < children.length; i++) {
                    // For inputs which look as button (text in value attribute)
                    if (children[i].nodeName === 'INPUT') {
                        const inputValue = children[i].value;
                        const tempElement = document.createElement('div');
                        tempElement.innerHTML = inputValue;

                        const markElement = tempElement.querySelector('mark');

                        if (markElement) {
                            const dataTitle = markElement.getAttribute('data-title');
                            const innerText = markElement.textContent;
                            children[i].value = innerText;
                            children[i].setAttribute('data-title', dataTitle);
                        }
                    }

                    if (
                        children[i].nodeName === 'MARK' ||
                        children[i].nodeName === 'INPUT' ||
                        children[i].nodeName === 'A' ||
                        children[i].nodeName === 'BUTTON'
                    ) {
                        // noinspection JSUnresolvedVariable
                        children[i].addEventListener('click', function (event) {
                            // noinspection JSUnresolvedVariable
                            if (event.metaKey || event.altKey) {
                                let target = event.target;
                                if ('A' === event.target.nodeName) {
                                    mark = $('>mark', $(event.target));
                                    if (mark.length) target = mark[0];
                                } else if ('BUTTON' === event.target.nodeName) {
                                    mark = $('mark:first', $(event.target));
                                    if (mark.length) target = mark[0];
                                }

                                event.preventDefault();
                                event.stopPropagation();

                                let base64 = target.getAttribute('data-title');
                                if (base64) {
                                    let data = JSON.parse(base64_decode(base64));
                                    let id = document.createElement('div');
                                    id.innerText = 'ID: ' + data.id;
                                    let domain = document.createElement('div');
                                    domain.innerText = 'Domain: ' + data.domain;
                                    let message = document.createElement('div');
                                    message.innerText = 'Message: ' + data.message;

                                    note.empty();
                                    note.append(id);
                                    note.append(domain);
                                    note.append(message);
                                    note.css('display', 'block');
                                }

                                window.stop();
                                return false;
                            }
                        });
                    }

                    if (children[i].nodeName === 'A' && children[i].hasAttribute('title')) {
                        children[i].addEventListener('mouseenter', function (event) {
                            lastTootltip = event.target;
                        });
                    }
                }
            }
        },
        true,
    );
    const body = document.body;

    replaceTags(body);

    document.addEventListener('click', function (event) {
        if (typeof note != 'undefined' && event.target.parentElement !== note[0]) {
            note.css('display', 'none');
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.altKey && typeof lastTootltip != 'undefined') {
            $(lastTootltip).trigger('mouseenter');
        }
    });
    document.addEventListener('keyup', function (event) {
        if (event.altKey && typeof lastTootltip != 'undefined') {
            $(lastTootltip).trigger('mouseleave');
        }
    });
}

export default function () {
    const opts = extractOptions();

    if (opts.enabledTransHelper) {
        import(/* webpackPreload: true */ '../../scss/trans-helper.scss');
    }
    $(function () {
        if (opts.enabledTransHelper) {
            enable();
        }

        let menu = $('ul.footer-menu');
        if (menu.length === 1) {
            let li = $('<li>');
            let a = $('<a>', {
                href: '#',
                text: /transhelper=/.exec(document.cookie) ? 'Disable Transhelper' : 'Enable TransHelper',
                click: function (event) {
                    event.preventDefault();

                    if (!/transhelper/.exec(document.cookie)) {
                        document.cookie = 'transhelper=1;path=/';
                        console.log('Set transhelper cookie');
                    } else {
                        document.cookie = 'transhelper=;path=/;expires=Thu, 2 Aug 2001 20:47:11 UTC;domain=';
                        console.log('Destroy transhelper cookie');
                    }
                    location.reload();
                },
            });
            li.append(a);
            menu.append(li);

            note = $('<div id="trans-note"></div>');
            menu.after(note);
        } else {
            note = $('<div id="trans-note"></div>');
            $('main').append(note);
        }
        window.addEventListener('resize', function () {
            note.css('left', window.innerWidth / 2 - 200 + 'px');
        });
        note.css('left', window.innerWidth / 2 - 200 + 'px');
    });
}
