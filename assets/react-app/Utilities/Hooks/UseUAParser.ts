import { UAParser } from 'ua-parser-js';
import { useMemo } from 'react';

export const useUAParser = (uastring?: string) => {
    const parsedData = useMemo(() => {
        return UAParser(uastring);
    }, []);

    return parsedData;
};
