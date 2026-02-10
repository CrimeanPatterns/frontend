import Swiper from 'swiper';
import { pageName } from '../consts';
import { Manipulation, Mousewheel } from 'swiper/modules';

const SPACINGS = {
    DEFAULT: 10,
    DESKTOP: 28,
    TABLET: 16,
    MOBILE: 12,
};

export class LatestNewsController {
    private swiper: Swiper | null = null;
    private container: HTMLElement | null;
    private swiperSelector: string;
    private isPositionLocked = false;
    private animationFrameId: number | null = null;

    constructor() {
        this.swiperSelector = `.${pageName}__latest-news-swiper`;
        this.container = document.querySelector(this.swiperSelector);
        this.initLazySwiper();
    }

    private initSwiper(): void {
        try {
            this.swiper = new Swiper(this.swiperSelector, {
                spaceBetween: SPACINGS.DEFAULT,
                slidesPerView: 'auto',
                modules: [Manipulation, Mousewheel],
                on: {
                    afterInit: this.setCustomSlideIndexes,
                },
                initialSlide: 2,
                mousewheel: {
                    enabled: true,
                    forceToAxis: true,
                },
                breakpoints: {
                    1400: { spaceBetween: SPACINGS.DESKTOP },
                    1024: { spaceBetween: SPACINGS.TABLET },
                    768: { spaceBetween: SPACINGS.MOBILE },
                },
                centerInsufficientSlides: true,
                preventClicks: true,
                centeredSlides: true,
            });
        } catch (error) {
            console.error('Failed to initialize latest news swiper:', error);
        }
    }

    private initLazySwiper(): void {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        this.initSwiper();
                        observer.disconnect();
                    }
                });
            },
            { threshold: 0.1 },
        );

        if (this.container) {
            observer.observe(this.container);
        }
    }

    private setCustomSlideIndexes(swiper: Swiper) {
        swiper.slides.forEach((slideElement, index) => {
            const initialIndex = slideElement.getAttribute('data-swiper-slide-index');
            if (initialIndex) {
                slideElement.setAttribute('data-slide-initial-index', initialIndex);
                slideElement.setAttribute('data-slide-index', initialIndex);
            } else {
                slideElement.setAttribute('data-slide-initial-index', String(index));
                slideElement.setAttribute('data-slide-index', String(index));
            }
            slideElement.style.height = `${slideElement.clientHeight}px`;
        });
    }

    public updateSwiperSlidesCustomIndexes = () => {
        this.swiper?.slides.forEach((slide, index) => {
            slide.setAttribute('data-slide-index', String(index));
        });
    };

    public addSlide = (slide: HTMLElement) => {
        const originalPlace = slide.getAttribute('data-slide-initial-index');
        let indexCurrent = 0;

        for (let slide of this.swiper!.slides) {
            const slideOriginalPlace = slide.getAttribute('data-slide-initial-index');
            const slideCurrentIndex = slide.getAttribute('data-slide-index');
            if (originalPlace && slideOriginalPlace && originalPlace < slideOriginalPlace) {
                indexCurrent = Number(slideCurrentIndex);
                break;
            }
        }

        this.swiper?.addSlide(indexCurrent, slide);
        this.updateSwiperSlidesCustomIndexes();
    };

    public removeSlide = (slideIndex: number) => {
        const prevSlideRemoved = slideIndex < (this.swiper?.activeIndex || 0);

        if (prevSlideRemoved && this.swiper?.activeIndex) {
            this.swiper.activeIndex = this.swiper.activeIndex + 1;
        }
        this.swiper?.removeSlide(slideIndex);
        this.updateSwiperSlidesCustomIndexes();
        this.isPositionLocked = false;

        setTimeout(() => {
            if (this.swiper && this.animationFrameId !== null) {
                cancelAnimationFrame(this.animationFrameId);
                this.swiper?.update();
            }
        }, 10);
    };

    public prepareSlideForRemoval() {
        if (this.swiper) {
            this.isPositionLocked = true;

            const currentPosition = this.swiper.getTranslate();

            const keepPosition = () => {
                if (!this.isPositionLocked) {
                    if (this.animationFrameId !== null) {
                        cancelAnimationFrame(this.animationFrameId);
                        this.animationFrameId = null;
                    }
                    return;
                }

                if (this.swiper) {
                    this.swiper.setTranslate(currentPosition);
                }
                this.animationFrameId = requestAnimationFrame(keepPosition);
            };

            this.animationFrameId = requestAnimationFrame(keepPosition);
        }
    }
}
