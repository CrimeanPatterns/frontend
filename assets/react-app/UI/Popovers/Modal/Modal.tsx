import { Cross } from '@UI/Icon/Cross/Cross';
import { ModalPriority, useModalManager } from '@Root/Contexts/ModalManagerContext';
import { Scrollbar } from '@UI/Layout/Scrollbar/Scrollbar';
import { animated, useTransition } from '@react-spring/web';
import { nanoid } from 'nanoid';
import { useFocusTrap } from '@Utilities/Hooks/UseFocusTrap';
import { useReactMediaQuery } from '@Root/Contexts/MediaQueryContext';
import React, { MouseEvent, PropsWithChildren, memo, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './Modal.module.scss';

export enum ModalMobileViewOption {
    FullScreen,
    Centered,
}
export interface ModalProps extends PropsWithChildren {
    open: boolean;
    onClose?: () => void;
    mobileView?: ModalMobileViewOption;
    onAfterAnimation?: (isOpen: boolean) => void;
    hideCross?: boolean;
    className?: {
        container?: string;
    };
    blockInteraction?: boolean;
    priority?: ModalPriority;
}

type InnerModalProps = Omit<ModalProps, 'priority'>;

const Start_Animation_Scale = 0.8;

export const Modal = memo(({ priority = ModalPriority.Low, ...props }: ModalProps) => {
    const { addModal, updateProps, closeModal } = useModalManager();

    const modalIdRef = useRef(nanoid(12));
    const isModalRenderedRef = useRef(false);

    const onAfterAnimation = useRef((isVisible: boolean) => {
        if (!isVisible) {
            closeModal(modalIdRef.current);
            isModalRenderedRef.current = false;
        }

        props.onAfterAnimation?.(isVisible);
    }).current;

    const modalItem = useMemo(
        () => ({
            modalComponent: ModalInner,
            id: modalIdRef.current,
            props: { ...props, onAfterAnimation },
            priority: priority,
        }),
        [props],
    );

    useEffect(() => {
        if (props.open && !isModalRenderedRef.current) {
            addModal(modalItem);
            isModalRenderedRef.current = true;
        }
    }, [props.open]);

    useEffect(() => {
        updateProps(modalItem.id, { ...props, onAfterAnimation });
        return;
    }, [props]);

    return null;
});

Modal.displayName = 'Modal';

const ModalInner = memo(
    ({
        open,
        onClose,
        mobileView = ModalMobileViewOption.FullScreen,
        onAfterAnimation,
        className,
        hideCross,
        blockInteraction,
        children,
    }: InnerModalProps) => {
        const [isVisible, setIsVisible] = useState(true);

        const showMobile = useReactMediaQuery('<=md');
        const showDesktop = useReactMediaQuery('>md');

        const focusTrapContainerRef = useFocusTrap(isVisible && showDesktop, false);
        const contentRef = useRef<HTMLDivElement>(null);
        const scrollbarContentRef = useRef<HTMLDivElement>(null);
        const scrollbarContainerRef = useRef<HTMLDivElement>(null);

        const transition = useTransition(isVisible, {
            from: {
                scale: Start_Animation_Scale,
                opacity: 0,
            },
            enter: {
                scale: 1,
                opacity: 1,
            },
            leave: {
                scale: Start_Animation_Scale,
                opacity: 0,
            },
            config: {
                duration: 200,
            },
            onRest: () => {
                onAfterAnimation?.(isVisible);
                if (!isVisible && open) {
                    onClose?.();
                }
            },
        });

        const onCloseHandle = useCallback(() => {
            if (!blockInteraction) {
                setIsVisible(false);
            }
        }, [blockInteraction]);

        const handleEscapeKey = useCallback(
            (event: KeyboardEvent) => {
                if (event.key === 'Escape' && isVisible) {
                    onCloseHandle();
                }
            },
            [isVisible, onCloseHandle],
        );

        const onBackgroundClick = useCallback((event: MouseEvent) => {
            if (contentRef.current && event.target instanceof Node && !contentRef.current.contains(event.target)) {
                onCloseHandle();
            }
        }, []);

        useEffect(() => {
            if (isVisible && showDesktop) {
                document.addEventListener('keydown', handleEscapeKey);
            } else {
                document.removeEventListener('keydown', handleEscapeKey);
            }

            return () => {
                document.removeEventListener('keydown', handleEscapeKey);
            };
        }, [handleEscapeKey, isVisible, showDesktop]);

        useEffect(() => {
            if (!open) {
                onCloseHandle();
            }
        }, [open]);

        return (
            <div
                ref={focusTrapContainerRef}
                className={classNames({
                    [classes.container as string]: showDesktop || mobileView === ModalMobileViewOption.Centered,
                    [classes.containerMobile as string]: showMobile && mobileView === ModalMobileViewOption.Centered,
                })}
                onClick={onBackgroundClick}
            >
                {transition((style, isVisible) => (
                    <>
                        {isVisible && (
                            <animated.div
                                style={{ ...style }}
                                className={classNames(classes.modal, className?.container)}
                                ref={contentRef}
                            >
                                {!hideCross && (
                                    <div className={classes.modalHeader}>
                                        <Cross onClick={onCloseHandle} />
                                    </div>
                                )}

                                <div
                                    ref={scrollbarContainerRef}
                                    className={classNames(classes.modalContent, {
                                        [classes.modalContentFullScreen as string]:
                                            showMobile && mobileView === ModalMobileViewOption.FullScreen,
                                    })}
                                >
                                    <Scrollbar contentRef={scrollbarContentRef} containerRef={scrollbarContainerRef}>
                                        <div ref={scrollbarContentRef}>{children}</div>
                                    </Scrollbar>
                                </div>
                            </animated.div>
                        )}
                    </>
                ))}
            </div>
        );
    },
);

ModalInner.displayName = 'ModalInner';
