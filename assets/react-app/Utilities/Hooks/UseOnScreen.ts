import { useEffect, useState } from 'react';

type RootMarginProp = `${number}px`;

export function useOnScreen(ref: React.MutableRefObject<HTMLElement | null>, rootMargin: RootMarginProp = '0px') {
    const [isIntersecting, setIntersecting] = useState(false);

    useEffect(() => {
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry) {
                    setIntersecting(entry.isIntersecting);
                }
            },
            {
                rootMargin,
            },
        );
        if (ref.current) {
            observer.observe(ref.current);
        }
        return () => {
            if (ref.current) {
                observer.unobserve(ref.current);
            }
        };
    }, []);

    return isIntersecting;
}
