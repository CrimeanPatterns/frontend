import '../../../ts/starter';
import '../../button';
import '../../button-platform';
import '../../icon-program-kind';
import '../../logo';
import '../../popup-media-logos';
import './page-landing.scss';
import onReady from '../../../ts/service/on-ready';
import stickyHeader from './sticky-header';

interface BodyDataset {
    inviteEmail?: string;
    inviteFn?: string;
    inviteLn?: string;
    inviteCode?: string;
}

onReady(function () {
    const dataset: BodyDataset = document.body.dataset;

    if (dataset.inviteEmail) {
        window.inviteEmail = dataset.inviteEmail;
    }

    if (dataset.inviteFn) {
        window.firstName = dataset.inviteFn;
    }

    if (dataset.inviteLn) {
        window.lastName = dataset.inviteLn;
    }

    if (dataset.inviteCode) {
        window.inviteCode = dataset.inviteCode;
    }

    stickyHeader();
});