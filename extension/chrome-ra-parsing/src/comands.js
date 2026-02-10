export const commandsDict = {
    newTab: function (data, url) {
        let properties = {
            url: data.url,
            active: true
        }
        
        chrome.tabs.create(properties, function(tab) {
            console.log(tab);

            let sendData = {
                cacheKey: data.cacheKey,
                tabId: tab.id,
            }

            commandsDict.sendResponse(sendData, url)
        })
    },

    findElements: function (data, url) {
        chrome.scripting.executeScript({
            target : {tabId : Number(data.tabId)},
            func : (selector) => {
                let obj = document.querySelectorAll(selector);

                if (obj) {
                    return obj.length;
                }
                return 0;
            },
            args : [data.selector],
            world : "MAIN"
        }).then((result) => {
            console.log(result)

            let sendData = {
                cacheKey: data.cacheKey,
                tabId: data.tabId,
                countElements: result[0].result,
            }

            commandsDict.sendResponse(sendData, url)
        });
    },

    clickElement: function (data, url) {
        chrome.scripting.executeScript({
            target : {tabId : Number(data.tabId)},
            func : (selector) => {
                let obj = document.querySelector(selector);

                if (obj) {
                    obj.click()
                    return true;
                }
                return false;
            },
            args : [data.selector],
            world : "MAIN"
        }).then((result) => {
            console.log(result);

            let sendData = {
                cacheKey: data.cacheKey,
                tabId: data.tabId,
            }

            commandsDict.sendResponse(sendData, url)
        });
    },

    sendResponse: (data, url) => {
        fetch(url, {
            "method": "POST",
            "headers": {
                "accept": "*",
                "content-type": "application/json",
            },
            "body": JSON.stringify(data),
        }).then((result) => console.log(result));
    }
}

