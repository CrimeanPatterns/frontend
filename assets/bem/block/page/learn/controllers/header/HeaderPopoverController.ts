import { HEADER_CONSTANTS } from './constants';

export class HeaderPopoverController {
    private dropdowns: NodeListOf<HTMLElement>;

    constructor() {
        this.dropdowns = document.querySelectorAll<HTMLElement>(`.blog-menu__dropdown`);
    }

    public adjustAllPopoversPosition(): void {
        if (this.dropdowns) {
            this.dropdowns.forEach((popup) => {
                const parentMenuItem = popup.closest<HTMLElement>(`.blog-menu__item`);
                if (parentMenuItem) {
                    this.adjustPopoverPosition(popup, parentMenuItem);
                }
            });
        }
    }

    private adjustPopoverPosition(popover: HTMLElement, anchor: HTMLElement): void {
        const viewportWidth = window.innerWidth;
        const popoverRect = popover.getBoundingClientRect();
        const anchorRect = anchor.getBoundingClientRect();

        let popoverLeft = anchorRect.left;
        let popoverRight = popoverLeft + popoverRect.width;

        if (popoverRight > viewportWidth - HEADER_CONSTANTS.POPOVER_OFFSET_FROM_WINDOW) {
            popoverLeft -= popoverRight - viewportWidth + HEADER_CONSTANTS.POPOVER_OFFSET_FROM_WINDOW;
        }

        if (popoverLeft < HEADER_CONSTANTS.POPOVER_OFFSET_FROM_WINDOW) {
            popoverLeft = HEADER_CONSTANTS.POPOVER_OFFSET_FROM_WINDOW;
        }

        popover.style.top = `${anchorRect.bottom + HEADER_CONSTANTS.POPOVER_OFFSET_FROM_ANCHOR}px`;
        popover.style.left = `${popoverLeft}px`;
    }
}
