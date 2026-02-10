import 'jquery-boot';
import '../bem/ts/starter';
import angular from 'angular-boot';
import 'libscripts';
import 'awardwallet';
import 'browserext';
import 'forge-api-awardwallet';
import 'extension-main';
import 'pages/accounts/controllers/accountList';
import '../bem/block/button';
import '../bem/block/account-update-failed-cell';
import onReady from '../bem/ts/service/on-ready';

onReady(async function () {
    const content = document.getElementById('update-all-account-container');

    const isBusiness = content.dataset['isbusiness']
        ? content.dataset['isbusiness'] === 'false'
            ? false
            : true
        : false;

    if (isBusiness) {
        const sockJS = await import('sockjs');
        console.log(sockJS);

        window.SockJS = sockJS.default;
    }

    const isIE = content.dataset['isie'] ? (content.dataset['isie'] === 'false' ? false : true) : false;

    const isPartial = content.dataset['ispartial'];

    let isTrips = content.dataset['istrips'];

    if (isTrips && isTrips === 'true') {
        isTrips = true;
    } else {
        isTrips = false;
    }

    const agentId = content.dataset['agentid'];
    if (agentId) {
        $(function () {
            $(window).trigger('person.activate', '{{ agentId }}');
        });
    }

    let limit = content.dataset['limit'];

    if (limit && limit === 'false') {
        limit = false;
    } else {
        if (isNaN(Number(limit))) {
            limit = null;
        } else {
            limit = Number(limit);
        }
    }

    angular.module('accountListApp').constant('ListConfig', {
        isIE,
        isBusiness,
        userAccountsLimit: limit,
        isPartial,
        isTrips: isTrips,
    });

    if (content.dataset['nexturl']) {
        angular.module('accountListApp').constant('NextUrl', content.dataset['nexturl']);
    }

    if (content.dataset['adsdata']) {
        const adsData = JSON.parse(content.dataset['adsdata']);
        angular.module('accountListApp').constant('AdsData', adsData);
    }

    if (content.dataset['accountsdata']) {
        const accountsData = JSON.parse(content.dataset['accountsdata']);
        angular.module('accountListApp').constant('ListData', accountsData);
    }

    $(function () {
        angular.bootstrap($('body').get(), ['accountListApp']);
    });
});
