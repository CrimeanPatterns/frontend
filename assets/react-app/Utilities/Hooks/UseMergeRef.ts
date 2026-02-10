import { MutableRefObject, useCallback } from 'react';

type RefItems<T> = ((element: T | null) => void) | MutableRefObject<T | null> | null | undefined;

export function useMergeRef<T>(...refs: RefItems<T>[]) {
    const refCallback = useCallback(
        (element: T | null) => {
            refs.forEach((ref) => {
                if (!ref) return;
                if (typeof ref === 'function') {
                    ref(element);
                } else {
                    ref.current = element;
                }
            });
        },
        [refs],
    );
    return refCallback;
}
