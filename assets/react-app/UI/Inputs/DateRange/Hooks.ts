import { RefObject, useEffect, useRef } from 'react';

export function useMousemoveOutside(ref: RefObject<HTMLDivElement>) {
    const isCursorOverRef = useRef(true);

    useEffect(() => {
        const handleMousemove = (event: MouseEvent) => {
            if (ref.current && ref.current.contains(event.target as Node)) {
                isCursorOverRef.current = false;

                return;
            }

            isCursorOverRef.current = true;
        };

        document.addEventListener('mouseover', handleMousemove, true);

        return () => {
            document.removeEventListener('mouseover', handleMousemove, true);
        };
    }, [ref]);

    return isCursorOverRef;
}
