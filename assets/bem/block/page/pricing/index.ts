import '../../stars-rating/stars-rating.scss';
import '../../button';
import '../../logo';
import '../../simple-header';
import '../../popup-media-logos';
import '../../footer';
import '../../upgrade-aw-block';
import '../../comparison-versions-table';
import 'swiper/css';
import './pricing.scss';
import './react';
import Swiper from 'swiper';
import { initStickyHeader } from '../../simple-header/index';
import onReady from '../../../ts/service/on-ready';
import { Autoplay, FreeMode } from 'swiper/modules';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';

function switchTableView(
    clickedButton: HTMLButtonElement,
    secondButton: HTMLButtonElement | null,
    eventName: 'showPlus' | 'showFree',
) {
    clickedButton.disabled = true;
    clickedButton.classList.add('page-pricing__comparison-mobile-tab--active');
    if (secondButton) {
        secondButton.disabled = false;
        secondButton.classList.remove('page-pricing__comparison-mobile-tab--active');
    }

    const awPlusCells: Element[] = [];
    const awFreeCells: Element[] = [];

    const mobileCells = document.querySelectorAll('.comparison-versions-table__cell--mobile');
    mobileCells.forEach((cell) => {
        const awPlusElement = cell.querySelector('.comparison-versions-table__cell--plus');
        if (awPlusElement) {
            awPlusCells.push(awPlusElement);
        }
        const awFreeElement = cell.querySelector('.comparison-versions-table__cell--free');
        if (awFreeElement) {
            awFreeCells.push(awFreeElement);
        }
        const awFreeIconElement = cell.querySelector('.comparison-versions-table__cell--free-icon');
        if (awFreeIconElement) {
            awFreeCells.push(awFreeIconElement);
        }
    });

    const awPlusPriceBlock = document.querySelector('.page-pricing__comparison-mobile-price-block--aw-plus');
    const awFreePriceBlock = document.querySelector('.page-pricing__comparison-mobile-price-block--free');
    console.log(awFreePriceBlock);

    if (eventName === 'showFree') {
        awPlusCells.forEach((cell) => {
            cell.classList.remove('comparison-versions-table__cell--active');
            cell.classList.add('comparison-versions-table__cell--leave');
        });
        awFreeCells.forEach((cell) => {
            cell.classList.add('comparison-versions-table__cell--active');
            cell.classList.remove('comparison-versions-table__cell--leave');
        });

        awPlusPriceBlock?.classList.remove('page-pricing__comparison-mobile-price-block--active');
        awPlusPriceBlock?.classList.add('page-pricing__comparison-mobile-price-block--leave');

        awFreePriceBlock?.classList.add('page-pricing__comparison-mobile-price-block--active');
        awFreePriceBlock?.classList.remove('page-pricing__comparison-mobile-price-block--leave');
    }

    if (eventName === 'showPlus') {
        awPlusCells.forEach((cell) => {
            cell.classList.add('comparison-versions-table__cell--active');
            cell.classList.remove('comparison-versions-table__cell--leave');
        });
        awFreeCells.forEach((cell) => {
            cell.classList.remove('comparison-versions-table__cell--active');
            cell.classList.add('comparison-versions-table__cell--leave');
        });

        awPlusPriceBlock?.classList.add('page-pricing__comparison-mobile-price-block--active');
        awPlusPriceBlock?.classList.remove('page-pricing__comparison-mobile-price-block--leave');

        awFreePriceBlock?.classList.remove('page-pricing__comparison-mobile-price-block--active');
        awFreePriceBlock?.classList.add('page-pricing__comparison-mobile-price-block--leave');
    }
}

onReady(() => {
    initStickyHeader();
    hideGlobalLoader();

    const header = document.querySelector('header');

    if (window.scrollY > 70) {
        header?.classList.add('page-pricing__header--scrolled');
    }

    window.addEventListener('scroll', () => {
        if (window.scrollY > 70) {
            header?.classList.add('page-pricing__header--scrolled');
        } else {
            header?.classList.remove('page-pricing__header--scrolled');
        }
    });

    const AWPlusButton = document.querySelector(
        '.page-pricing__comparison-mobile-tab--plus',
    ) as HTMLButtonElement | null;
    const AWFreeButton = document.querySelector(
        '.page-pricing__comparison-mobile-tab--free',
    ) as HTMLButtonElement | null;

    AWFreeButton?.addEventListener('click', () => {
        switchTableView(AWFreeButton, AWPlusButton, 'showFree');
    });

    AWPlusButton?.addEventListener('click', () => {
        switchTableView(AWPlusButton, AWFreeButton, 'showPlus');
    });

    // Featured on block
    new Swiper('.page-pricing__featured-company-container', {
        autoplay: {
            delay: 0,
            disableOnInteraction: false,
        },
        speed: 3000,
        slidesPerView: 3,
        loop: true,
        modules: [Autoplay],
        breakpoints: {
            1800: {
                slidesPerView: 9,
            },
            1600: {
                slidesPerView: 7,
            },
            1400: {
                slidesPerView: 5,
            },
        },
    });

    // Reviews block
    const reviewSwiper = new Swiper('.page-pricing__reviews-container', {
        spaceBetween: 6,
        slidesPerView: 'auto',
        grabCursor: true,
        freeMode: {
            enabled: true,
            sticky: false,
            momentum: true,
            momentumRatio: 0.5,
        },
        modules: [FreeMode],
        on: {
            init: function (swiper) {
                if (window.innerWidth <= 350) {
                    return;
                }
                if (window.innerWidth <= 768) {
                    swiper.setTranslate(-swiper.width / 1.5);
                    return;
                }
                if (window.innerWidth <= 1024) {
                    swiper.setTranslate(-swiper.width / 6);
                    return;
                }

                if (window.innerWidth > 1024 && window.innerWidth <= 2550) {
                    swiper.setTranslate(-swiper.width / 15);
                }
            },
        },
        breakpoints: {
            768: {
                spaceBetween: 17,
            },
        },
    });

    let isScrolling = false;
    let scrollVelocity = 0;

    function smoothScroll() {
        if (isScrolling) {
            const currentTranslate = reviewSwiper.getTranslate();

            let newTranslate = currentTranslate + scrollVelocity;
            //@ts-expect-error
            const maxTranslate = -reviewSwiper.snapGrid[reviewSwiper.snapGrid.length - 1];
            const minTranslate = 0;

            newTranslate = Math.max(maxTranslate, Math.min(minTranslate, newTranslate));

            reviewSwiper.setTranslate(newTranslate);

            scrollVelocity *= 0.95;

            if (Math.abs(scrollVelocity) < 0.1) {
                isScrolling = false;
            } else {
                requestAnimationFrame(() => smoothScroll());
            }
        }
    }

    const reviewsContainer = document.querySelector('.page-pricing__reviews-container') as HTMLElement | null;

    if (reviewsContainer) {
        reviewsContainer.addEventListener('wheel', (event: WheelEvent) => {
            event.preventDefault();

            scrollVelocity += event.deltaY * 0.1;

            if (!isScrolling) {
                isScrolling = true;

                smoothScroll();
            }
        });
    }

    //review popup
    const reviewElements = document.querySelectorAll('.page-pricing__review');
    const reviewElementsWithButton = Array.from(reviewElements).filter((element) =>
        element.querySelector('.page-pricing__review-button'),
    );

    reviewElementsWithButton.forEach((element) => {
        element.addEventListener('click', () => {
            const textElement = element.querySelector('.page-pricing__review-text');
            const authorElement = element.querySelector('.page-pricing__review-author');

            const reviewText = textElement ? textElement.textContent || '' : '';
            const reviewAuthor = authorElement ? authorElement.textContent || '' : '';
            //@ts-ignore
            window.openModalWithContent(reviewText, reviewAuthor);
        });
        element.classList.add('page-pricing__review--clickable');
    });
});
