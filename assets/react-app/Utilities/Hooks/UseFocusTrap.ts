import { useCallback, useEffect, useRef } from 'react';

const Focusable_Elements_Query = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

export const useFocusTrap = (active: boolean = true, focusFirstElement: boolean = true) => {
    const containerRef = useRef<HTMLDivElement>(null);

    const findNextFocusableElement = useCallback(
        (currentElement: HTMLElement, step: 1 | -1, elements: HTMLElement[]) => {
            const currentIndex = elements.indexOf(currentElement);
            let startIndex = currentIndex + step;

            if (startIndex < 0) startIndex = elements.length - 1;
            if (startIndex >= elements.length) startIndex = 0;

            let index = startIndex;
            while (index !== startIndex - 1) {
                const nextFocusableElement = elements[index];
                // @ts-expect-error Element might not be input
                if (nextFocusableElement && !nextFocusableElement.disabled) {
                    return nextFocusableElement;
                }

                index += step;

                if (index < 0) index = elements.length - 1;

                if (index >= elements.length) index = 0;
            }
            return elements[startIndex - 1];
        },
        [],
    );

    useEffect(() => {
        if (active) {
            const previouslyFocusedElement = document.activeElement;

            const containerElement = containerRef.current;
            if (!containerElement) return;

            const focusableElements: HTMLElement[] = Array.from(
                containerElement.querySelectorAll(Focusable_Elements_Query),
            );

            if (focusableElements.length === 0) return;

            if (focusFirstElement) {
                const firstFocusableElement = focusableElements[0] as HTMLElement;
                firstFocusableElement.focus();
            }

            const handleKeyDown = (event: KeyboardEvent) => {
                if (event.key === 'Tab') {
                    event.preventDefault();
                    if (event.shiftKey) {
                        findNextFocusableElement(document.activeElement as HTMLElement, -1, focusableElements)?.focus();
                    } else {
                        findNextFocusableElement(document.activeElement as HTMLElement, 1, focusableElements)?.focus();
                    }
                }
            };

            document.addEventListener('keydown', handleKeyDown);

            return () => {
                document.removeEventListener('keydown', handleKeyDown);

                if (previouslyFocusedElement) {
                    (previouslyFocusedElement as HTMLElement).focus();
                }
            };
        }
        return;
    }, [active]);

    return containerRef;
};
