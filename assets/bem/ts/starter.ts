import { extractOptions } from './service/env';
import onReady from './service/on-ready';

onReady(function () {
    const opts = extractOptions();

    if (opts.enabledTransHelper || opts.hasRoleTranslator) {
        console.log('init transhelper');
        import(/* webpackPreload: true */ './service/transHelper')
            .then(({ default: init }) => { init(); }, () => { console.error('transhelper failed to load'); });
    }

});