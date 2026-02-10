import { CloseButton } from './Components/CloseButton';
import { Image } from './../../Layout/Image';
import { ModalPriority, useModalManager } from '@Root/Contexts/ModalManagerContext';
import { animated, useTransition } from '@react-spring/web';
import { nanoid } from 'nanoid';
import { useFocusTrap } from '@Utilities/Hooks/UseFocusTrap';
import React, { MouseEvent, memo, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import classes from './ViewMediaModal.module.scss';

const Start_Animation_Scale = 0.8;

type ViewImageModalProps = {
    open: boolean;
    onClose: () => void;
    src: string;
    srcSet?: string;
    alt?: string;
    priority?: ModalPriority;
};

type InnerModalProps = Omit<ViewImageModalProps, 'open' | 'priority'>;

export const ViewMediaModal = memo(({ open, priority = ModalPriority.Low, ...props }: ViewImageModalProps) => {
    const { addModal, updateProps, closeModal } = useModalManager();

    const modalIdRef = useRef(nanoid(12));
    const isModalRenderedRef = useRef(false);

    const modalItem = useMemo(
        () => ({
            modalComponent: ViewMediaModalInner,
            id: modalIdRef.current,
            props: props,
            priority: priority,
        }),
        [props],
    );

    useEffect(() => {
        if (open && !isModalRenderedRef.current) {
            addModal(modalItem);
            isModalRenderedRef.current = true;
        }

        if (!open) {
            closeModal(modalIdRef.current);
            isModalRenderedRef.current = false;
        }
    }, [open]);

    useEffect(() => {
        if (open) {
            updateProps(modalItem.id, props);
            return;
        }
    }, [props]);

    return null;
});

ViewMediaModal.displayName = 'ViewMediaModal';

const ViewMediaModalInner = memo(({ onClose, src, srcSet, alt }: InnerModalProps) => {
    const [isVisible, setIsVisible] = useState(true);

    const focusTrapContainerRef = useFocusTrap(isVisible);
    const contentRef = useRef<HTMLDivElement>(null);

    const imageClasses = useMemo(
        () => ({
            errorContainer: classes.imageErrorContainer,
            loadingContainer: classes.imageLoadingContainer,
        }),
        [],
    );

    const transition = useTransition(isVisible, {
        from: {
            opacity: 0,
            scale: Start_Animation_Scale,
        },
        enter: {
            opacity: 1,
            scale: 1,
        },
        leave: {
            opacity: 0,
            scale: Start_Animation_Scale,
        },
        config: {
            duration: 200,
        },
        onRest: () => {
            if (!isVisible) {
                onClose();
            }
        },
    });

    const onCloseHandle = useCallback(() => {
        setIsVisible(false);
    }, []);

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
        if (isVisible) {
            document.addEventListener('keydown', handleEscapeKey);
        } else {
            document.removeEventListener('keydown', handleEscapeKey);
        }

        return () => {
            document.removeEventListener('keydown', handleEscapeKey);
        };
    }, [handleEscapeKey, isVisible]);

    return (
        <div ref={focusTrapContainerRef} onClick={onBackgroundClick} className={classes.container}>
            {transition((style, open) => (
                <>
                    {open && (
                        <animated.div ref={contentRef} style={{ opacity: style.opacity }}>
                            <CloseButton onClick={onCloseHandle} />
                            <animated.div style={{ scale: style.scale }} className={classes.modal}>
                                <Image src={src} srcSet={srcSet} classes={imageClasses} alt={alt} />
                            </animated.div>
                        </animated.div>
                    )}
                </>
            ))}
        </div>
    );
});

ViewMediaModalInner.displayName = 'ViewMediaModalInner';
