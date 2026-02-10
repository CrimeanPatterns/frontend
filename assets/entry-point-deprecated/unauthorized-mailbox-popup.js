import popupFunc from 'pages/mailbox/unauth-popup';
import onReady from '../bem/ts/service/on-ready';

onReady(() => {
    popupFunc(window.unauthMailboxId, window.unauthMailbox);
});
