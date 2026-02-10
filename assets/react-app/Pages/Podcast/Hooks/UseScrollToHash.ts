import { useEffect } from 'react';

export function useScrollToHashOnRender() {
    useEffect(() => {
        const hash = window.location.hash.substring(1);
        if (!hash) return;

        let animationFrameId: number;

        const scrollToHash = () => {
            const element = document.getElementById(hash);
            if (element) {
                const screenWidth = window.innerWidth;
                const topOffset = screenWidth > 1024 ? 50 : 0;
                const elementPosition = element.getBoundingClientRect().top + window.scrollY;
                const offsetPosition = elementPosition - topOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth',
                });
            } else {
                animationFrameId = requestAnimationFrame(scrollToHash);
            }
        };

        const observer = new MutationObserver(() => {
            const element = document.getElementById(hash);
            if (element) {
                observer.disconnect();
                animationFrameId = requestAnimationFrame(scrollToHash);
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });

        return () => {
            observer.disconnect();
            cancelAnimationFrame(animationFrameId);
        };
    }, []);
}
