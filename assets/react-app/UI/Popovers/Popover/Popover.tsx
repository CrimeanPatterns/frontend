import { Align, PositionFromAnchor } from '..';
import { animated, useTransition } from '@react-spring/web';
import { createPortal } from 'react-dom';
import { getPopoverStyles } from './Utilities';
import { useMouseLeaveMultipleRefs } from '@Utilities/Hooks/UseMouseLeaveMultipleRefs';
import { useOutsideClickMultipleRefs } from '@Utilities/Hooks/UseOutsideClick';
import React, { CSSProperties, PropsWithChildren, RefObject, useEffect, useRef, useState } from 'react';
import classNamesLib from 'classnames';
import classes from './Popover.module.scss';

const OFFSET_FROM_DOCUMENT_IN_PERCENTAGE = 5;

interface PopoverProps extends PropsWithChildren {
    open: boolean;
    onClose?: () => void;
    closeTrigger?: 'click' | 'mouseLeave';
    anchor: RefObject<HTMLElement>;
    positionFromAnchor?: PositionFromAnchor;
    align?: Align;
    offsetFromAnchorInPx?: number;
    showShadow?: boolean;
    classNames?: {
        popoverContainer?: string;
    };
    lockGlobalScroll?: boolean;
}

/**
 * If the anchor isn't defined, the modal will appear in a screen center (positionFromAnchor, modalAlign, offsetFromAnchorInPx do nothing in this case)
 * If the anchor is defined, so the default position is below anchor and horizontal centered
 * Modal will automatic define vertical or horizontal position
 * and change it if opposite side has more free space for content
 *
 * All calculations start from a position from the anchor. If you choose above or below the anchor, only horizontal align will work.
 * If your choice is left or right anchor, only vertical align will work
 *
 * Default horizontal and vertical alignment is centered.
 *
 * When Modal is open, body tag has style overflow:hidden, to block external scroll
 *
 * If content doesn't fit, modal will restrict its width and height
 */

export function Popover({
    open,
    onClose,
    closeTrigger,
    anchor,
    positionFromAnchor = PositionFromAnchor.Below,
    align,
    offsetFromAnchorInPx = 0,
    children,
    classNames,
    showShadow,
    lockGlobalScroll,
}: PopoverProps) {
    const [modalContainerStyles, setModalContainerStyles] = useState<CSSProperties>({});
    const [isVisible, setIsVisible] = useState(false);

    const popoverRef = useRef<HTMLDivElement>(null);

    const popoverRect = useRef<DOMRect | null>(null);
    const latestOpenValue = useRef(open);

    const transition = useTransition(open, {
        from: {
            opacity: 0,
            scale: 0.8,
        },
        enter: {
            opacity: 1,
            scale: 1,
        },
        leave: {
            opacity: 0,
            scale: 0.8,
        },
        config: {
            duration: 200,
        },
        onRest: () => {
            if (!latestOpenValue.current) {
                setModalContainerStyles({});
                popoverRect.current = null;
                setIsVisible(false);
            }
        },
    });

    useOutsideClickMultipleRefs([popoverRef, anchor], isVisible && closeTrigger === 'click', () => {
        if (closeTrigger === 'click') {
            onClose?.();
        }
    });

    useMouseLeaveMultipleRefs([popoverRef, anchor], isVisible && closeTrigger === 'mouseLeave', () => {
        onClose?.();
    });

    useEffect(() => {
        if (isVisible && anchor.current && popoverRef.current) {
            popoverRect.current = popoverRef.current.getBoundingClientRect();

            const styles = getPopoverStyles({
                anchorElement: anchor.current,
                popoverRect: popoverRect.current,
                positionFromAnchor,
                popoverAlign: align,
                offsetFromAnchorInPx,
                offsetFromDocumentInPercentage: OFFSET_FROM_DOCUMENT_IN_PERCENTAGE,
            });
            setModalContainerStyles(styles);
        }
    }, [isVisible, anchor.current]);

    useEffect(() => {
        if (open) {
            setIsVisible(true);
        }
        latestOpenValue.current = open;
    }, [open]);

    function recalculatePosition() {
        if (isVisible && anchor.current && popoverRef.current && popoverRect.current) {
            const styles = getPopoverStyles({
                anchorElement: anchor.current,
                popoverRect: popoverRect.current,
                positionFromAnchor,
                popoverAlign: align,
                offsetFromAnchorInPx,
                offsetFromDocumentInPercentage: OFFSET_FROM_DOCUMENT_IN_PERCENTAGE,
            });

            setModalContainerStyles(styles);
        }
    }

    useEffect(() => {
        window.addEventListener('resize', recalculatePosition);

        return () => {
            window.removeEventListener('resize', recalculatePosition);
        };
    }, [isVisible]);

    useEffect(() => {
        if (lockGlobalScroll && isVisible) {
            const scrollWidth = window.innerWidth - document.documentElement.clientWidth;

            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = `${scrollWidth}px`;
        } else {
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = `0`;
        }

        return () => {
            document.body.style.overflow = 'auto';
        };
    }, [lockGlobalScroll, isVisible]);

    if (!isVisible) return null;

    return createPortal(
        <div>
            {transition((style, open) => (
                <>
                    {open && showShadow && (
                        <animated.div style={{ opacity: style.opacity }} className={classes.shadow} onClick={onClose} />
                    )}
                    {open && (
                        <div className={classes.popoverContainer} style={modalContainerStyles} ref={popoverRef}>
                            <div
                                className={classes.spacer}
                                style={{
                                    transform:
                                        positionFromAnchor === PositionFromAnchor.Below
                                            ? `translateY(-${offsetFromAnchorInPx}px)`
                                            : positionFromAnchor === PositionFromAnchor.Above
                                              ? `translateY(${offsetFromAnchorInPx}px)`
                                              : positionFromAnchor === PositionFromAnchor.Left
                                                ? `translateX(${offsetFromAnchorInPx}px)`
                                                : `translateX(-${offsetFromAnchorInPx}px)`,
                                }}
                            />
                            <animated.div
                                style={{ ...style }}
                                className={classNamesLib(classes.popover, classNames?.popoverContainer)}
                            >
                                {children}
                            </animated.div>
                        </div>
                    )}
                </>
            ))}
        </div>,
        document.body,
    );
}
