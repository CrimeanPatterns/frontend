var tabOwnerMap = {};
var openPorts = {};

var commands = {

    openTab: function(ownerTabId, url, active, blockImages) {
        console.log('opening tab', url, active, ownerTabId);
        return new Promise(function(resolve, reject) {
            chrome.tabs.get(ownerTabId, function(ownerTabInfo){
                chrome.tabs.create({
                    url: url,
                    active: active,
                    openerTabId: ownerTabId,
                    windowId: ownerTabInfo.windowId
                }, function(tab) {
                    console.log('opened tab at ' + url + ', tab id: ' + tab.id + ', owner tab id: ' + ownerTabId);
                    tabOwnerMap[tab.id] = ownerTabId;

                    if (blockImages) {
                        console.log('blocking images');
                        chrome.webRequest.onBeforeRequest.addListener(
                                function (details) {
                                    if (details.url.indexOf('captcha') !== -1) {
                                        return {};
                                    }
                                    return {cancel: true};
                                },
                                {urls: ["<all_urls>"], types: ["image", "media"], tabId: tab.id},
                                ["blocking"]
                        );
                    }

                    console.log('before load installed');

                    resolve(tab.id);
                });
            });
        });
    },

    executeScript: function(ownerTabId, tabId, code) {
        console.log('executing script', tabId, code);
        return new Promise(function(resolve, reject) {
            chrome.tabs.executeScript(tabId, {code: code}, function (result) {
                resolve(result);
            });
        });
    },

    getMyTabId: function(senderTabId){
        return new Promise(function(resolve, reject) {
            resolve(senderTabId);
        });
    },

    sendToTab: function(senderTabId, targetTabId, message){
        if (targetTabId in openPorts) {
            // sending to awardwallet.com
            openPorts[targetTabId].postMessage({type: "message", params: [message]});
        } else {
            // sending to provider
            chrome.tabs.sendMessage(targetTabId, {type: "message", params: [message]});
        }
        // intentionally do not send response
        return new Promise(function(resolve, reject) {});
    },

    closeCurrentTab: function(senderTabId){
        chrome.tabs.remove(senderTabId);
        // intentionally do not send response
        return new Promise(function(resolve, reject) {});
    }

};

// listener for commands from awardwallet.com
chrome.runtime.onConnectExternal.addListener(function(port) {
    console.log('[BG] received connection', port);

    var mask = /^https:\/\/([a-z0-9\-]+\.)?awardwallet\.com[$\/]/i;
    if (!port.sender || !port.sender.url || !port.sender.url.match(mask)) {
        console.log('[BG] unknown sender');
        return;
    }

    var sender = port.sender;

    openPorts[sender.tab.id] = port;

    console.log('[BG] registering port listener');
    port.onMessage.addListener(function(message) {
        console.log('[BG] received command from awardwallet.com', message);
        message.params.unshift(sender.tab.id);
        commands[message.command].apply(this, message.params).then(function (result) {
            console.log('[BG] sending response to awardwallet.com', message, result);
            port.postMessage({type: "response", params: [message, result]});
        });
    });
});

// listener for commands from from provider tabs
chrome.runtime.onMessage.addListener(function (message, sender, sendResponse) {
    console.log('[BG] received command', message);
    message.params.unshift(sender.tab.id);
    commands[message.command].apply(this, message.params).then(function (result) {
        console.log('[BG] sending response to tab ' + sender.tab.id);
        chrome.tabs.sendMessage(sender.tab.id, {type: "response", params: [message, result]});
    });
});

chrome.tabs.onUpdated.addListener(function(tabId, changeInfo, tab){
    if(tabId in tabOwnerMap){
        console.log('[BG] tab ' + tabId + ' updated', changeInfo);
        var targetTabId = tabOwnerMap[tabId];
        if (targetTabId in openPorts) {
            openPorts[targetTabId].postMessage({type: "tabUpdated", params: [tabId, changeInfo, tab]});
        } else {
            chrome.tabs.sendMessage(targetTabId, {type: "tabUpdated", params: [tabId, changeInfo, tab]});
        }
    }
});

chrome.runtime.onInstalled.addListener(function(details){
    console.log('[BG] extension installed', details.reason);
    // Get all windows
    chrome.windows.getAll({
        populate: true
    }, function (windows) {
        var i = 0, w = windows.length, currentWindow;
        for( ; i < w; i++ ) {
            currentWindow = windows[i];
            var j = 0, t = currentWindow.tabs.length, currentTab;
            for( ; j < t; j++ ) {
                currentTab = currentWindow.tabs[j];
                // Skip chrome:// and https:// pages
                if( currentTab.url.match(/https?:\/\/(\w+\.)*awardwallet\.com\/extension\-install/gi) ) {
                    console.log('[BG] reloading tab', currentTab);
                    chrome.tabs.executeScript(currentTab.id, {code: "document.location.reload();"});
                }
            }
        }
    });
});
