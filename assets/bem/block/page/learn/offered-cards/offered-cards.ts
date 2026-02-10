import './offered-cards.scss';
import Swiper from 'swiper';
import onReady from '@Bem/ts/service/on-ready';
import { pageName } from '../consts';

onReady(() => {
    // Offered cards <=lg section
    new Swiper(`.${pageName}__offered-card-swiper`, {
        spaceBetween: 10,
        slidesPerView: 'auto',
        breakpoints: {
            600: {
                spaceBetween: 16,
            },
        },
    });
});
