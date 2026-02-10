import { Cross } from '@UI/Icon/Cross/Cross';
import { PrimaryButton, SecondaryButton } from '@UI/Buttons';
import { Scrollbar } from '../Scrollbar/Scrollbar';
import { Translator } from '@Services/Translator';
import { animated, useTransition } from '@react-spring/web';
import { createPortal } from 'react-dom';
import { useFocusTrap } from '@Utilities/Hooks/UseFocusTrap';
import React, { PropsWithChildren, memo, useEffect, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './SidePanel.module.scss';

type SidePanelProps = {
    isOpen: boolean;
    title: string;
    onClose?: () => void;
    onApply?: () => void;
    side?: 'left' | 'right';
} & PropsWithChildren;

export const SidePanel = memo(({ isOpen, onClose, title, children, onApply, side = 'left' }: SidePanelProps) => {
    const [isMounted, setIsMounted] = useState(false);

    const focusTrapContainerRef = useFocusTrap(!isMounted);

    const contentRef = useRef<HTMLDivElement>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    const transition = useTransition(isOpen, {
        from: {
            translateX: side === 'right' ? 400 : -400,
            opacity: 0,
        },
        enter: {
            translateX: 0,
            opacity: 1,
        },
        leave: {
            translateX: side === 'right' ? 400 : -400,
            opacity: 0,
        },
        config: {
            duration: 300,
        },
        onRest: () => {
            if (!isOpen) {
                setIsMounted(false);
            }
        },
    });

    useEffect(() => {
        if (isOpen) {
            setIsMounted(true);
        }
    }, [isOpen]);

    if (!isMounted) {
        return null;
    }
    return createPortal(
        <div ref={focusTrapContainerRef}>
            {transition((style, isOpen) => (
                <>
                    {isOpen && (
                        <>
                            <animated.div
                                style={{ opacity: style.opacity }}
                                className={classes.sidePanelShadow}
                                onClick={onClose}
                            ></animated.div>
                            <animated.div
                                style={{ translateX: style.translateX }}
                                className={classNames(classes.sidePanelContainer, {
                                    [classes.sidePanelContainerRight as string]: side === 'right',
                                })}
                                ref={containerRef}
                            >
                                <Scrollbar
                                    contentRef={contentRef}
                                    containerRef={containerRef}
                                    fullHeightScrollbar
                                    hideScrollbarPadding
                                >
                                    <div ref={contentRef}>
                                        <div className={classes.sidePanelHeader}>
                                            <h3 className={classes.sidePanelHeaderText}>{title}</h3>
                                            <Cross onClick={onClose} />
                                        </div>
                                        <div className={classes.sidePanelDivider}></div>
                                        <div className={classes.sidePanelContentContainer}> {children}</div>
                                        <div className={classes.sidePanelFooter}>
                                            <SecondaryButton
                                                text={Translator.trans('form.button.cancel')}
                                                className={{ button: classes.sidePanelFooterButton }}
                                                onClick={onClose}
                                            />
                                            <PrimaryButton
                                                text={Translator.trans('button.apply', {}, 'mobile-native')}
                                                className={{ button: classes.sidePanelFooterButton }}
                                                onClick={onApply}
                                            />
                                        </div>
                                    </div>
                                </Scrollbar>
                            </animated.div>
                        </>
                    )}
                </>
            ))}
        </div>,
        document.body,
    );
});

SidePanel.displayName = 'SidePanel';
