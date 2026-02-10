import { RefObject, useEffect, useRef } from 'react';

export const useOutsideClickMultipleRefs = <T extends HTMLElement>(
    elementsRefs: RefObject<T>[],
    listen: boolean,
    callback?: () => void,
) => {
    const isClickOutsideRef = useRef(true);

    useEffect(() => {
        const handleClick = (event: MouseEvent) => {
            let isClickOutside = true;
            for (const ref of elementsRefs) {
                if (ref.current && ref.current.contains(event.target as Node)) {
                    isClickOutside = false;
                    break;
                }
            }

            if (isClickOutside) {
                callback?.();
            }
            isClickOutsideRef.current = isClickOutside;
        };

        if (listen) {
            document.addEventListener('click', handleClick, true);
        } else {
            document.removeEventListener('click', handleClick, true);
        }

        return () => {
            document.removeEventListener('click', handleClick, true);
        };
    }, [elementsRefs, callback, listen]);

    return isClickOutsideRef.current;
};
