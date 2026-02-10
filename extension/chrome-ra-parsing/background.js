import './src/centrifuge.js';
import {commandsDict} from "./src/comands.js";

let apiUrl = null;
let isConnected = false;

function connectToCentrifuge(centrifugeConfig) {
    let centrifuge = new Centrifuge(centrifugeConfig);

    centrifuge.on('connect', function () {
        console.log('centrifuge connected');

        const onMessage = function onMessage(message) {
            console.log(message.data);
            commandsDict[message.data.type](message.data.message, apiUrl)
        };

        var subscription = centrifuge.subscribe('ra_extension_parsing', onMessage);
        subscription.history().then(function (message) {
            console.log('history messages received', message);
            $.each(message.data.reverse(), function (index, value) {
                onMessage(value);
            });
        }, function (err) {
            console.log('history call failed', err);
        });
    });
    centrifuge.connect();
    isConnected = true;
}

chrome.tabs.onUpdated.addListener(() => {
    chrome.tabs.query(
        {
            active: true,
        },
        (result) => {
            if (isConnected) {
                return null;
            }
            console.log('Get tabs list');
            let tab = result[0];

            if (tab.url.indexOf('awardwallet') === -1) {
                return null;
            }

            chrome.scripting.executeScript({
                target : {tabId : tab.id},
                func : () => {
                    let url = document.querySelector('#apiUrl');
                    let centrifugeConfig = document.querySelector('#centrifugeConfig');

                    if (centrifugeConfig && url) {
                        return {
                            centrifugeConfig: JSON.parse(centrifugeConfig.innerText),
                            apiUrl: url.innerText
                        };
                    }

                    return false;
                },
                world : "MAIN"
            }).then((data) => {
                console.log('Set apiUrl');
                apiUrl = data[0].result.apiUrl;

                console.log('Set config data');
                if (data[0].result.centrifugeConfig && !isConnected) {
                    connectToCentrifuge(data[0].result.centrifugeConfig);
                }
            });
        },
    )
})