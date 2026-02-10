import { Breakpoints, BreakpointsDesignation, Theme } from '@UI/Theme';
import { MediaQueryFeatures, useMediaQuery } from 'react-responsive';
import { useTheme } from 'react-jss';
import React, { PropsWithChildren, createContext, useContext, useMemo } from 'react';

//TODO: Investigate possibility of creating the same listeners once
interface MediaQueryContextValue {
    addListener: (listener: MediaQuery, isActive: boolean) => void;
    isListenerAdded: (listener: MediaQuery) => boolean;
    getListener: (listener: MediaQuery) => boolean | null;
}

const MediaQueryContext = createContext<MediaQueryContextValue | null>(null);

export function MediaQueryProvider({ children }: PropsWithChildren) {
    const addedListeners = useMemo(() => new Map<MediaQuery, boolean>(), []);

    const addListener = (listener: MediaQuery, isActive: boolean): void => {
        addedListeners.set(listener, isActive);
    };

    const isListenerAdded = (listener: MediaQuery): boolean => {
        return addedListeners.has(listener);
    };

    const getListener = (listener: MediaQuery) => {
        return addedListeners.get(listener) ?? null;
    };

    const contextValue: MediaQueryContextValue = {
        addListener,
        isListenerAdded,
        getListener,
    };

    return <MediaQueryContext.Provider value={contextValue}>{children}</MediaQueryContext.Provider>;
}

export const useMediaQueryContext = (): MediaQueryContextValue => {
    const context = useContext(MediaQueryContext);

    if (!context) {
        throw new Error('useMediaQueryContext must be used within a MediaQueryProvider');
    }

    return context;
};

type CompactionSign = '>' | '<' | '>=' | '<=';

export type MediaQuery = `${CompactionSign}${BreakpointsDesignation}`;

export function useReactMediaQuery(mediaQuery: MediaQuery) {
    const theme: Theme = useTheme();

    const mediaQueryParsed = useMemo(() => prepareMediaQuery(mediaQuery, theme.breakpoints), [mediaQuery]);

    // const { addListener, getListener } = useMediaQueryContext();

    const match = useMediaQuery(mediaQueryParsed);

    // const listener = getListener(mediaQuery);

    // if (listener) {
    //     return listener;
    // }
    // addListener(mediaQuery, match);

    return match;
}

function prepareMediaQuery(mediaQuery: MediaQuery, breakpoints: Breakpoints): MediaQueryFeatures {
    const matchResult = mediaQuery.match(/([<>]=?)([a-z]+)$/);

    if (!matchResult) {
        return {};
    }
    const [compactionSign, designation] = matchResult.slice(1);
    const breakpointValue = breakpoints[designation as BreakpointsDesignation];

    switch (compactionSign) {
        case '>':
            return { minWidth: breakpointValue + 1 };
        case '<':
            return { maxWidth: breakpointValue - 1 };
        case '>=':
            return { minWidth: breakpointValue };
        case '<=':
            return { maxWidth: breakpointValue };
        default:
            return {};
    }
}
