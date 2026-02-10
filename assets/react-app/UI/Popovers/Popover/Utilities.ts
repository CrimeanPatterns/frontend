import { Align, PositionFromAnchor } from '..';
import { CSSProperties } from 'react';

// TODO: Change when header will be recreated
const Header_Height_In_Px = 55;

interface GetPopoverStylesArgs {
    anchorElement: HTMLElement;
    popoverRect: DOMRect;
    offsetFromDocumentInPercentage: number;
    positionFromAnchor: PositionFromAnchor;
    popoverAlign?: Align;
    offsetFromAnchorInPx: number;
}
export function getPopoverStyles({
    anchorElement,
    popoverRect,
    offsetFromDocumentInPercentage,
    positionFromAnchor,
    popoverAlign,
    offsetFromAnchorInPx,
}: GetPopoverStylesArgs): CSSProperties {
    const anchorRect = anchorElement.getBoundingClientRect();

    const offsetFromDocumentInPx = (window.innerHeight / 100) * offsetFromDocumentInPercentage;

    const align = getPopoverAlign(positionFromAnchor, popoverRect, offsetFromDocumentInPx, anchorRect, popoverAlign);

    const position = getPopoverPosition(
        anchorRect,
        positionFromAnchor,
        popoverRect,
        offsetFromAnchorInPx,
        offsetFromDocumentInPx,
    );

    return {
        ...position,
        ...align,
    };
}

function getPopoverPosition(
    anchorRect: DOMRect,
    positionFromAnchor: PositionFromAnchor,
    popoverContentRect: DOMRect,
    offsetFromAnchorInPx: number,
    offsetFromDocumentInPx: number,
): CSSProperties {
    if (positionFromAnchor === PositionFromAnchor.Below) {
        const topCoordinate = anchorRect.bottom + offsetFromAnchorInPx + window.scrollY;

        return {
            top: topCoordinate,
        };
    }

    if (positionFromAnchor === PositionFromAnchor.Left) {
        const leftCoordinate = Math.max(
            anchorRect.left - popoverContentRect.width - offsetFromAnchorInPx + window.scrollX,
            offsetFromDocumentInPx,
        );

        return {
            left: leftCoordinate,
        };
    }

    if (positionFromAnchor === PositionFromAnchor.Above) {
        let topCoordinate = anchorRect.top - offsetFromAnchorInPx + window.scrollY - popoverContentRect.height;

        const minOffset = Math.max(Header_Height_In_Px, offsetFromDocumentInPx);

        if (topCoordinate < minOffset) {
            topCoordinate = minOffset;
        }

        return {
            top: topCoordinate,
        };
    }

    let leftCoordinate = anchorRect.right + offsetFromAnchorInPx + window.scrollX;
    if (leftCoordinate + popoverContentRect.width > document.documentElement.clientWidth) {
        leftCoordinate = document.documentElement.clientWidth - offsetFromDocumentInPx - popoverContentRect.width;
    }
    return {
        left: leftCoordinate,
    };
}

function verticalPopoverAlign(
    anchorRect: DOMRect,
    modalRect: DOMRect,
    offsetFromDocument: number,
    modalAlign?: Align,
): { top: number; maxHeight?: number } {
    const maxModalHeight = window.innerHeight - offsetFromDocument * 2;

    if (maxModalHeight < modalRect.height) {
        return {
            top: offsetFromDocument,
            maxHeight: maxModalHeight,
        };
    }

    if (modalAlign === Align.Top) {
        return { top: Math.max(Header_Height_In_Px, anchorRect.top + window.scrollY) };
    }

    if (modalAlign === Align.Bottom) {
        return { top: Math.max(Header_Height_In_Px, anchorRect.top - modalRect.height + window.scrollY) };
    }

    return {
        top: Math.max(
            Header_Height_In_Px,
            anchorRect.top + window.scrollY + anchorRect.height / 2 - modalRect.height / 2,
        ),
    };
}

function horizontalPopoverAlign(
    anchorRect: DOMRect,
    modalRect: DOMRect,
    offsetFromDocument: number,
    modalAlign?: Align,
): { left?: number; maxWidth?: number } {
    const maxModalWidth = window.innerWidth - 2 * offsetFromDocument;
    const windowLeftBorder = offsetFromDocument;

    if (modalRect.width > maxModalWidth) {
        return {
            left: windowLeftBorder,
            maxWidth: maxModalWidth,
        };
    }

    if (modalAlign === Align.Left) {
        let left = anchorRect.left + window.scrollX;
        const right = left + modalRect.width;
        const windowRightBorder = window.innerWidth - offsetFromDocument;

        if (right > windowRightBorder) {
            left = left - (right - windowRightBorder);
        }

        return { left };
    }

    if (modalAlign === Align.Right) {
        return { left: Math.max(anchorRect.right - modalRect.width + window.scrollX, offsetFromDocument) };
    }

    let leftCoordinate = Math.max(
        offsetFromDocument,
        anchorRect.left + window.scrollX + anchorRect.width / 2 - modalRect.width / 2,
    );

    if (leftCoordinate + window.scrollX + modalRect.width > window.innerWidth) {
        leftCoordinate = window.scrollX + window.innerWidth - modalRect.width;
    }
    return { left: leftCoordinate };
}

function getPopoverAlign(
    positionFromAnchor: PositionFromAnchor,
    popoverContentRect: DOMRect,
    offsetFromDocumentInPx: number,
    anchorRect: DOMRect,
    align?: Align,
) {
    if (positionFromAnchor === PositionFromAnchor.Below || positionFromAnchor === PositionFromAnchor.Above) {
        return horizontalPopoverAlign(anchorRect, popoverContentRect, offsetFromDocumentInPx, align);
    }

    return verticalPopoverAlign(anchorRect, popoverContentRect, offsetFromDocumentInPx, align);
}
