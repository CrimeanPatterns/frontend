var forge = {

    onMessage: null,
    callbackManager: new CallbackManager(),
    pendingMessages: 0,
    waitStart: null,
    waitStep: 200,

    message: {

        broadcastBackground: function (to, message, callback) {

            // waiting for ack needed for Safari
            // in some situations later message will be delivered before previous one
            // so we will wait before previous message is delivered before sending next one

            // do not ack ready/log and other frequent messages to prevent callback buffer overflow
            var waitAck = forge.isSafari() && (
                message.command === 'saveProperties'
                || message.command === 'saveTemp'
                || message.command === 'setNextStep'
                || message.command === 'complete'
                || message.command === 'setError'
                || message.command === 'setWarning'
                || message.command === 'keepTabOpen'
                || message.command === 'setTimeout'
                || message.command === 'setIdleTimer'
            );

            if (forge.pendingMessages > 0 && waitAck) {
                console.log(['[FP] waiting for ack for ' + forge.pendingMessages + ' messages']);
                var d = new Date();
                if ((d.getTime() - forge.waitStart.getTime()) > 30000) {
                    console.log(['[FP] timed out while wating for ack']);
                    return;
                }
                setTimeout(function(){
                    forge.message.broadcastBackground(to, message, callback);
                }, forge.waitStep);
                return;
            }

            message.senderTabId = myTabId;
            if (typeof(callback) === 'function') {
                message.callbackId = forge.callbackManager.add(callback);
                console.log('[FP] registered callback for ' + message.command + ': ' + message.callbackId);
            }

            if (waitAck) {
                forge.pendingMessages++;
                forge.waitStart = new Date();
                message.ackId = forge.callbackManager.add(function () {
                    forge.pendingMessages--;
                });
            }
            var msg = {command: "sendToTab", params: [ownerTabId, message]};
            chrome.runtime.sendMessage(msg);
        },

        listen: function (type, onMessage, onError) {
            console.log('[FP] listening', onMessage);
            forge.onMessage = onMessage;
        }

    },

    isSafari: function() {
        var ua = navigator.userAgent.toLowerCase();
        return (ua.indexOf('safari') !== -1) && (ua.indexOf('chrome') === -1);
    },

    logging: {

        log: function (message) {
            console.log(message);
        }

    },


    tabs: {

        closeCurrent: function () {
            chrome.runtime.sendMessage({command: "closeCurrentTab", params: []});
        }

    },

    document: {

        location: function(callback){
            $(document).ready(function(){
                callback(document.location);
            });
        }

    }

};

chrome.runtime.onMessage.addListener(function (message, sender, sendResponse) {
    console.log('[FP] received message', message);
    if (message.params[0]['message'] === 'callback') {
        forge.callbackManager.fire(message.params[0]['callbackId'], [message.params[0]['response']]);
        return;
    }
    forge.onMessage(message.params[0], function(){});
});

console.log('[FP] forge api loaded');
