// will be replaced by desktopGrunt
var portComm = true;

if (document.getElementById('extListenButton')) {
    document.getElementById('extParams').value = JSON.stringify({
        'version': '2.30',
        'id' : chrome.runtime.id,
        'port_comm': portComm,
    });
    document.getElementById('extCommand').value = 'content_scripts_ready';
}

if (!portComm) {
// will listen for commands from awardwallet.com and forward them to background
    document.addEventListener("aw_page_to_bg", function (event) {
        chrome.runtime.sendMessage(event.detail);
    });

    chrome.runtime.onMessage.addListener(function (message, sender, sendResponse) {
        var event = new CustomEvent('aw_content_to_page', {detail: JSON.stringify(message)});
        document.dispatchEvent(event);
    });
}