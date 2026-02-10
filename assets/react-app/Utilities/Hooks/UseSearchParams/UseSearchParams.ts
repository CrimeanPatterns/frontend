import { SanitizingURLScheme, ValidationURLScheme } from './Entities';
import { sanitizeAndValidateSearchParams } from './Utilities';
import { useCallback, useMemo } from 'react';
import { useSearchParams as useSearchParamsRRD } from 'react-router-dom';

export function useSearchParams(sanitizedScheme?: SanitizingURLScheme[], validationScheme?: ValidationURLScheme[]) {
    const [searchParams, setSearchParams] = useSearchParamsRRD();

    const cleanedSearchParams = useMemo(
        () => sanitizeAndValidateSearchParams(searchParams, sanitizedScheme, validationScheme),
        [],
    );

    const getParam = useCallback(
        <T, R>(
            key: string,
            defaultValue: T,
            transform?: (value: string, defaultValue: T) => R,
        ): T | (R extends undefined ? string : R) => {
            const value = cleanedSearchParams.get(key);

            if (!value) return defaultValue;
            if (transform) {
                try {
                    return transform(value, defaultValue) as R extends undefined ? string : R;
                } catch {
                    cleanedSearchParams.delete(key);
                    setSearchParams(cleanedSearchParams);
                    return defaultValue;
                }
            }

            return value as R extends undefined ? string : R;
        },
        [],
    );

    const setOneSearchParam = useCallback(
        <T extends string, V extends string | null>(key: T, value: V, defaultValue?: V) => {
            if (value) {
                cleanedSearchParams.set(key, value);
            } else {
                cleanedSearchParams.delete(key);
            }

            if (value === defaultValue) {
                cleanedSearchParams.delete(key);
            }

            setSearchParams(cleanedSearchParams);
        },
        [],
    );

    return { searchParams: cleanedSearchParams, setSearchParams, getParam, setOneSearchParam };
}
