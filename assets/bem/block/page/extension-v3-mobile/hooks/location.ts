import {useState, useCallback, useEffect} from 'react';
import {patchHistoryMethods} from "../utils";
import _ from "lodash";

/**
 * Gets the current location and URL parameters
 */
function getCurrentLocation() {
    return {
        pathname: window.location.pathname,
        search: window.location.search,
        searchParams: new URLSearchParams(window.location.search),
        url: new URL(window.location.href)
    }
}

/**
 * Combined hook for working with location and URL parameters
 * Combines the functionality of useLocation and useSearchParams
 */
export function useLocation() {
    // Initialize state with current location
    const [location, setLocation] = useState(getCurrentLocation());

    // Handler for location changes
    function handleLocationChange() {
        setLocation(getCurrentLocation());
    }

    // Set up event listeners when component mounts
    useEffect(() => {
        patchHistoryMethods();

        window.addEventListener('pushState', handleLocationChange);
        window.addEventListener('replaceState', handleLocationChange);
        window.addEventListener('popstate', handleLocationChange);

        return () => {
            window.removeEventListener('pushState', handleLocationChange);
            window.removeEventListener('replaceState', handleLocationChange);
            window.removeEventListener('popstate', handleLocationChange);
        };
    }, []);

    /**
     * Navigate to a new URL with state preservation
     */
    const pushState = useCallback((url: string, stateParams: any = {}) => {
        window.history.pushState(stateParams, document.title, url);
    }, []);

    /**
     * Update URL search parameters
     * @param paramsOrUpdater - New parameters or update function
     */
    const setSearchParams = useCallback((
        paramsOrUpdater:
            | URLSearchParams
            | ((prev: URLSearchParams) => URLSearchParams)
            | Record<string, any>
    ) => {
        const currentLocation = getCurrentLocation();
        const newUrl = new URL(window.location.href);
        let newParams: URLSearchParams;

        if (typeof paramsOrUpdater === 'function') {
            // If an update function is provided
            newParams = paramsOrUpdater(currentLocation.searchParams);
            newUrl.search = '';
            for (const [key, value] of newParams.entries()) {
                newUrl.searchParams.append(key, value);
            }
        } else if (paramsOrUpdater instanceof URLSearchParams) {
            // If a URLSearchParams object is provided
            newUrl.search = '';
            for (const [key, value] of paramsOrUpdater.entries()) {
                newUrl.searchParams.append(key, value);
            }
            newParams = paramsOrUpdater;
        } else {
            // If an object with parameters is provided
            newParams = new URLSearchParams(currentLocation.searchParams.toString());

            if (_.isObject(paramsOrUpdater)) {
                Object.entries(paramsOrUpdater).forEach(([key, value]) => {
                    if (_.isEmpty(value)) {
                        newParams.delete(key);
                        newUrl.searchParams.delete(key);
                    } else {
                        newParams.set(key, value.toString());
                        newUrl.searchParams.delete(key);
                        newUrl.searchParams.append(key, value.toString());
                    }
                });
            }
        }
        pushState(newUrl.toString())
    }, []);

    return {
        // Current location data
        ...location,

        // Methods from useLocation
        pushState,

        // Methods from useSearchParams
        setSearchParams
    };
}

/**
 * Alias for backward compatibility with existing code using useSearchParams
 */
export function useSearchParams() {
    const {searchParams, setSearchParams} = useLocation();
    return {searchParams, setSearchParams};
}
