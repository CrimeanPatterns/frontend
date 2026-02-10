import './popup-media-logos.scss';
import Dialog from '../../ts/service/dialog';
import Router from '../../ts/service/router';
import Translator from '../../ts/service/translator';
import onReady from '../../ts/service/on-ready';

onReady(function () {
    const popup = document.querySelector('.popup-media-logos');

    if (popup) {
        document.addEventListener('logo:context-menu', () => {
            const d = Dialog.createNamed('mediaLogos', $(popup), {
                autoOpen: true,
                modal: true,
                minWidth: 550,
                buttons: [
                    {
                        text: Translator.trans('button.close'),
                        class: 'btn-silver',
                        click: () => {
                            d.close();
                        }
                    },
                    {
                        text: Translator.trans('button.proceed_to_page'),
                        class: 'btn-blue',
                        click: () => {
                            window.location.href = Router.generate('aw_media_logos');
                        }
                    }
                ]
            });
        });
    }
});