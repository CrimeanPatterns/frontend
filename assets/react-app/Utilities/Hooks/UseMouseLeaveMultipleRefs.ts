import { RefObject, useEffect, useRef } from 'react';

export const useMouseLeaveMultipleRefs = <T extends HTMLElement>(
    elementsRefs: RefObject<T>[],
    enable: boolean,
    callback?: () => void,
) => {
    const setTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const previousMouseEnterHandlers = useRef<Map<T, EventListenerOrEventListenerObject | null>>(new Map());
    const previousMouseLeaveHandlers = useRef<Map<T, EventListenerOrEventListenerObject | null>>(new Map());

    useEffect(() => {
        const mouseEnter = () => {
            if (setTimeoutRef.current) {
                clearTimeout(setTimeoutRef.current);
            }
        };

        const mouseLeave = () => {
            setTimeoutRef.current = setTimeout(() => {
                callback?.();
            }, 200);
        };

        const restorePrevHandlers = (elementsRefs: RefObject<T>[]) => {
            elementsRefs.forEach((elementRef) => {
                const element = elementRef.current;
                if (element) {
                    const prevMouseEnterHandler = previousMouseEnterHandlers.current.get(element);
                    const prevMouseLeaveHandler = previousMouseLeaveHandlers.current.get(element);

                    if (prevMouseEnterHandler) {
                        element.addEventListener('mouseenter', prevMouseEnterHandler);
                    } else {
                        element.removeEventListener('mouseenter', mouseEnter);
                    }

                    if (prevMouseLeaveHandler) {
                        element.addEventListener('mouseleave', prevMouseLeaveHandler);
                    } else {
                        element.removeEventListener('mouseleave', mouseLeave);
                    }
                }
            });
        };

        if (enable) {
            elementsRefs.forEach((elementRef) => {
                const element = elementRef.current;
                if (element) {
                    previousMouseEnterHandlers.current.set(element, element.onmouseenter as EventListener);
                    previousMouseLeaveHandlers.current.set(element, element.onmouseleave as EventListener);

                    element.addEventListener('mouseenter', mouseEnter);
                    element.addEventListener('mouseleave', mouseLeave);
                }
            });
        } else {
            restorePrevHandlers(elementsRefs);
        }

        return () => {
            restorePrevHandlers(elementsRefs);
        };
    }, [elementsRefs, callback, enable]);
};
