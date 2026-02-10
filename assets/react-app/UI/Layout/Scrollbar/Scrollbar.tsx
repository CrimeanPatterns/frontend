import React, {
    PropsWithChildren,
    RefObject,
    forwardRef,
    useCallback,
    useEffect,
    useImperativeHandle,
    useRef,
    useState,
} from 'react';
import classes from './Scrollbar.module.scss';

type ScrollbarProps = {
    contentRef: RefObject<HTMLElement>;
    containerRef: RefObject<HTMLElement>;
    fullHeightScrollbar?: boolean;
    hideScrollbarPadding?: boolean;
} & PropsWithChildren;

export type ScrollbarRef = {
    resetScrollPosition: () => void;
};

export const Scrollbar = forwardRef<ScrollbarRef, ScrollbarProps>(
    ({ contentRef, containerRef, children, fullHeightScrollbar, hideScrollbarPadding }, ref) => {
        const [isVisible, setIsVisible] = useState(false);
        const [thumbHeight, setThumbHeight] = useState(20);
        const [isDragging, setIsDragging] = useState(false);
        const [scrollStartPosition, setScrollStartPosition] = useState(0);
        const [initialContentScrollTop, setInitialContentScrollTop] = useState(0);
        const [scrollbarMaxHeight, setScrollbarMaxHeight] = useState<number | string>('unset');

        const scrollbarContentRef = useRef<HTMLDivElement>(null);
        const scrollTrackRef = useRef<HTMLDivElement>(null);
        const scrollThumbRef = useRef<HTMLDivElement>(null);
        const observer = useRef<ResizeObserver | null>(null);

        const calculateThumbHeight = useCallback(() => {
            if (scrollTrackRef.current && scrollbarContentRef.current) {
                const { clientHeight: trackSize } = scrollTrackRef.current;
                const { clientHeight: contentVisible, scrollHeight: contentTotalHeight } = scrollbarContentRef.current;

                setThumbHeight(Math.max((contentVisible / contentTotalHeight) * trackSize, 20));
            }
        }, []);

        function handleResize() {
            calculateThumbHeight();
        }

        function handleThumbPosition() {
            if (!scrollbarContentRef.current || !scrollTrackRef.current || !scrollThumbRef.current) {
                return;
            }

            const { scrollTop: contentTop, scrollHeight: contentHeight } = scrollbarContentRef.current;
            const { clientHeight: trackHeight } = scrollTrackRef.current;

            let newTop = (contentTop / contentHeight) * trackHeight;
            newTop = Math.min(newTop, trackHeight - thumbHeight);

            const thumb = scrollThumbRef.current;
            requestAnimationFrame(() => {
                thumb.style.top = `${newTop}px`;
            });
        }

        function handleTrackClick(e: React.MouseEvent<HTMLDivElement>) {
            e.preventDefault();
            e.stopPropagation();
            const { current: track } = scrollTrackRef;
            const { current: content } = scrollbarContentRef;
            if (track && content) {
                const { clientY } = e;
                const target = e.target as HTMLDivElement;
                const rect = target.getBoundingClientRect();
                const trackTop = rect.top;
                const thumbOffset = -(thumbHeight / 2);
                const clickRatio = (clientY - trackTop + thumbOffset) / track.clientHeight;
                const scrollAmount = Math.floor(clickRatio * content.scrollHeight);
                content.scrollTo({
                    top: scrollAmount,
                    behavior: 'smooth',
                });
            }
        }

        function handleThumbMousedown(e: React.MouseEvent<HTMLDivElement>) {
            e.preventDefault();
            e.stopPropagation();
            setScrollStartPosition(e.clientY);
            if (scrollbarContentRef.current) setInitialContentScrollTop(scrollbarContentRef.current.scrollTop);
            setIsDragging(true);
        }

        function handleThumbMouseup(e: MouseEvent) {
            e.preventDefault();
            e.stopPropagation();
            if (isDragging) {
                setIsDragging(false);
            }
        }

        function handleThumbMousemove(e: MouseEvent) {
            if (scrollbarContentRef.current) {
                e.preventDefault();
                e.stopPropagation();
                if (isDragging) {
                    const { scrollHeight: contentScrollHeight, clientHeight: contentClientHeight } =
                        scrollbarContentRef.current;

                    const deltaY = (e.clientY - scrollStartPosition) * (contentClientHeight / thumbHeight);

                    const newScrollTop = Math.min(
                        initialContentScrollTop + deltaY,
                        contentScrollHeight - contentClientHeight,
                    );

                    scrollbarContentRef.current.scrollTop = newScrollTop;
                }
            }
        }

        useImperativeHandle(
            ref,
            () => ({
                resetScrollPosition() {
                    if (scrollbarContentRef.current) {
                        scrollbarContentRef.current.scrollTop = 0;
                    }
                },
            }),
            [],
        );

        useEffect(() => {
            document.addEventListener('mousemove', handleThumbMousemove);
            document.addEventListener('mouseup', handleThumbMouseup);
            return () => {
                document.removeEventListener('mousemove', handleThumbMousemove);
                document.removeEventListener('mouseup', handleThumbMouseup);
            };
        }, [handleThumbMousemove, handleThumbMouseup]);

        useEffect(() => {
            if (contentRef.current && scrollbarContentRef.current) {
                const computeScrollHeight = () => {
                    if (contentRef.current && scrollbarContentRef.current) {
                        setIsVisible(contentRef.current.scrollHeight > scrollbarContentRef.current.clientHeight);
                    }
                };

                computeScrollHeight();

                observer.current = new ResizeObserver(() => {
                    handleResize();
                    computeScrollHeight();
                });

                observer.current.observe(contentRef.current);
                scrollbarContentRef.current.addEventListener('scroll', handleThumbPosition);

                return () => {
                    if (scrollbarContentRef.current) {
                        observer.current?.unobserve(scrollbarContentRef.current);
                        scrollbarContentRef.current.removeEventListener('scroll', handleThumbPosition);
                    }
                };
            }

            return;
        }, []);

        useEffect(() => {
            if (containerRef.current) {
                if (getComputedStyle(containerRef.current).maxHeight !== 'none') {
                    setScrollbarMaxHeight(Number(getComputedStyle(containerRef.current).maxHeight.replace('px', '')));
                }
            }
        }, [containerRef]);

        useEffect(() => {
            if (isVisible) {
                handleResize();
            }
        }, [isVisible]);

        return (
            <div className={classes.scrollbar} style={{ gap: hideScrollbarPadding ? '' : '14px' }}>
                <div
                    ref={scrollbarContentRef}
                    className={classes.scrollbarContent}
                    style={{ maxHeight: scrollbarMaxHeight }}
                >
                    {children}
                </div>
                <div
                    className={classes.scrollbarTrackAndThumb}
                    style={{ height: fullHeightScrollbar ? '100%' : 'calc(100% - 50px)' }}
                >
                    {isVisible && (
                        <>
                            <div ref={scrollTrackRef} className={classes.scrollbarTrack} onClick={handleTrackClick} />
                            <div
                                ref={scrollThumbRef}
                                style={{
                                    height: `${thumbHeight}px`,
                                    cursor: isDragging ? 'grabbing' : 'grab',
                                }}
                                className={classes.scrollbarThumb}
                                onMouseDown={handleThumbMousedown}
                            />
                        </>
                    )}
                </div>
            </div>
        );
    },
);

Scrollbar.displayName = 'Scrollbar';
