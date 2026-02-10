import '../../button';
import '../../button-platform';
import '../../footer';
import '../../logo';
import '../../simple-header';
import '../../popup-media-logos';
import './after-deletion.scss';
import onReady from '@Bem/ts/service/on-ready';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';

onReady(() => {
    hideGlobalLoader();
});
