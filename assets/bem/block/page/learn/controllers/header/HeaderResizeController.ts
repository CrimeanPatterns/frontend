import { HEADER_CONSTANTS } from './constants';

export class HeaderResizeController {
    constructor(
        private header: HTMLElement | null,
        private menuItems: NodeListOf<HTMLElement>,
    ) {}

    public calculateMenuItemsHeight(): void {
        if (window.innerWidth <= HEADER_CONSTANTS.MOBILE_BREAKPOINT) {
            return;
        }

        let maxPaddingTop = HEADER_CONSTANTS.MAX_PADDING_TOP_DESKTOP_LARGE;
        let maxPaddingBottom = HEADER_CONSTANTS.MAX_PADDING_BOTTOM_DESKTOP_LARGE;

        const scrollY = window.scrollY;
        const progress = Math.min(scrollY / HEADER_CONSTANTS.MAX_SCROLL, 1);

        const newPaddingTop = maxPaddingTop - progress * (maxPaddingTop - HEADER_CONSTANTS.MIN_PADDING_TOP_DESKTOP);
        const newPaddingBottom =
            maxPaddingBottom - progress * (maxPaddingBottom - HEADER_CONSTANTS.MIN_PADDING_BOTTOM_DESKTOP);

        this.menuItems.forEach((menuItem) => {
            menuItem.style.paddingTop = `${newPaddingTop}px`;
            menuItem.style.paddingBottom = `${newPaddingBottom}px`;
        });
    }

    public calculateHeaderHeight(): void {
        if (!this.header) {
            return;
        }

        if (window.innerWidth > HEADER_CONSTANTS.MOBILE_BREAKPOINT) {
            this.header.style.paddingBottom = `0px`;
            this.header.style.paddingTop = `0px`;
            return;
        }

        let maxPaddingTop = HEADER_CONSTANTS.MAX_PADDING_TOP_MOBILE_LARGE;
        let maxPaddingBottom = HEADER_CONSTANTS.MAX_PADDING_BOTTOM_MOBILE_LARGE;

        if (window.innerWidth <= HEADER_CONSTANTS.MOBILE_SMALL_BREAKPOINT) {
            maxPaddingTop = HEADER_CONSTANTS.MAX_PADDING_TOP_MOBILE_SMALL;
            maxPaddingBottom = HEADER_CONSTANTS.MAX_PADDING_BOTTOM_MOBILE_SMALL;
        }

        const scrollY = window.scrollY;
        const progress = Math.min(scrollY / HEADER_CONSTANTS.MAX_SCROLL, 1);

        const newPaddingTop = maxPaddingTop - progress * (maxPaddingTop - HEADER_CONSTANTS.MIN_PADDING_TOP_MOBILE);
        const newPaddingBottom =
            maxPaddingBottom - progress * (maxPaddingBottom - HEADER_CONSTANTS.MIN_PADDING_BOTTOM_MOBILE);

        this.header.style.paddingBottom = `${newPaddingBottom}px`;
        this.header.style.paddingTop = `${newPaddingTop}px`;

        const headerHeight = this.header.offsetHeight;
        document.documentElement.style.setProperty('--mobile-header-height', `${headerHeight}px`);
    }
}
