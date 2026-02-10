import { useEffect, useRef } from 'react';

export function useUpdateEffect(effect: React.EffectCallback, deps?: React.DependencyList) {
    const isInitialRender = useRef(true);

    useEffect(() => {
        if (isInitialRender.current) {
            isInitialRender.current = false;
        } else {
            return effect();
        }
    }, deps);
}
