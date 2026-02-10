import './logo.scss';
import onReady from '../../ts/service/on-ready';

onReady(function () {
    const contextEvent = new Event('logo:context-menu');
    const logo = document.querySelector('.logo');

    if (logo) {
        logo.addEventListener('contextmenu', function(event) {
            event.preventDefault();
            const target = event.target as HTMLElement;

            if (target.tagName === 'IMG') {
                document.dispatchEvent(contextEvent);
            }
        });
    }
});