import { useEffect, useRef, useState } from 'react';

type UseStateCallback<T> = ((value: T) => void) | null;
type InitState<T> = T | (() => T);
type SetState<T> = T | ((prevState: T) => T);

export default function useStateWithCallback<T>(
    initialValue: InitState<T>,
): [T, (newValue: SetState<T>, callback?: UseStateCallback<T>) => void] {
    const callbackRef = useRef<UseStateCallback<T>>(null);

    const [value, setValue] = useState(initialValue);

    useEffect(() => {
        if (callbackRef.current) {
            callbackRef.current(value);

            callbackRef.current = null;
        }
    }, [value]);

    const setValueWithCallback = (newValue: SetState<T>, callback?: UseStateCallback<T>) => {
        if (callback) {
            callbackRef.current = callback;
        }

        setValue(newValue);
    };

    return [value, setValueWithCallback];
}
