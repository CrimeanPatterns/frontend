/* eslint-disable @typescript-eslint/no-unnecessary-condition */
import { CentrifugePublishData, useCentrifuge } from '../../../../Contexts/CentrifugeContext';
import { HotelsRequestData, HotelsSearchResponseApi, useHotelSearch } from '../../Hooks/UseHotelsSearch';
import { ProviderBrand } from '../../Entities';
import {
    checkIsDataReady,
    filterHotels,
    handleHotelsSearchResponseErrors,
    prepareHotels,
    sortHotels,
} from './Utilities';
import { nanoid } from 'nanoid';
import { useHotelPageInitialData } from '../HotelPageInitialDataContext';
import React, { PropsWithChildren, createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';

export interface ExchangePoint {
    fromBrand: ProviderBrand;
    toBrand: ProviderBrand;
    pointsExchange: number;
    currentBalanceFrom: number;
    currentBalanceTo: number;
}
export interface Hotel {
    key: string;
    name: string;
    checkInDate: string;
    checkOutDate: string;
    cashPerNight: number;
    cashPerNightFormatted: string;
    address: string;
    thumb: string;
    rating: number;
    ratingFormatted: string;
    distance: number;
    distanceFormatted: string;
    pointsPerNight: number;
    pointsPerNightFormatted: string;
    providercode: ProviderBrand;
    transferOptions?: ExchangePoint[];
}

interface CentrifugePublishedHotels {
    requestId: string;
    searchId: string;
    hotels: Hotel[];
    provider: ProviderBrand;
}

export type SearchFormData = Omit<HotelsRequestData, 'channelName' | 'searchId'>;

export enum SortingType {
    RedemptionValue,
    LeastExpensive,
    MostExpensive,
    Distance,
    CustomerRating,
}

export interface HotelFilters {
    distance?: [number, number];
    assessmentAW?: {
        excellent?: boolean;
        good?: boolean;
        fair?: boolean;
        bad?: boolean;
    };
    minCustomRating?: number;
    averageCost?: [number, number];
    showOnlyBookable?: boolean;
}

interface HotelFiltersSettings {
    distance: {
        maxValue: number;
        minValue: number;
    };
    averageCost: {
        maxValue: number;
        minValue: number;
    };
}

interface HotelContextValue {
    hotels: Hotel[];
    loadingPercentage: number | null;
    activeSortingType: SortingType;
    isLoading: boolean;
    filters: HotelFilters;
    filtersSettings: HotelFiltersSettings;
    isFiltersActive: boolean;
    searchData: SearchFormData | null;
    totalLoadingSteps: number;
    finishedLoadingSteps: number;

    setActiveSortingType: (sortingType: SortingType) => void;
    setSearchFormData: (requestData: SearchFormData) => void;
    setFilters: (filters: HotelFilters) => void;
    setIsFiltersActive: (isFiltersActive: boolean) => void;
    onLoadingFinish: () => void;
}

const HotelContext = createContext<null | HotelContextValue>(null);

export function HotelsProvider({ children }: PropsWithChildren) {
    const { centrifugeConfig } = useCentrifuge();

    const { isDebug } = useHotelPageInitialData();

    const [searchFormData, setSearchFormData] = useState<SearchFormData | null>(null);
    const [hotels, setHotels] = useState<Hotel[]>([
        // {
        //     address: '8985 W. Amarillo Blvd, , Amarillo, Texas 79124, United States',
        //     cashPerNight: '123',
        //     checkInDate: new Date('2023-12-07T00:00:00+00:00'),
        //     checkOutDate: new Date('2023-12-11T00:00:00+00:00'),
        //     distance: 6.6,
        //     key: '5cc5a237c15c7a9124178234dec288ab9a006707',
        //     name: 'Hyatt Place Amarillo - West',
        //     pointsPerNight: '6,500',
        //     providercode: ProviderBrand.IchotelGroup,
        //     rating: '4.50',
        //     thumb: '/data/thumbnial/5cc5a237c15c7a9124178234dec288ab9a006707',
        //     transferOptions: [
        //         {
        //             currentBalanceFrom: 30000,
        //             currentBalanceTo: 15230,
        //             fromBrand: ProviderBrand.IchotelGroup,
        //             toBrand: ProviderBrand.Hhonors,
        //             pointsExchange: 3571,
        //         },
        //         {
        //             currentBalanceFrom: 5000,
        //             currentBalanceTo: 15230,
        //             fromBrand: ProviderBrand.Marriot,
        //             toBrand: ProviderBrand.Hhonors,
        //             pointsExchange: 150,
        //         },
        //         {
        //             currentBalanceFrom: 9000,
        //             currentBalanceTo: 15230,
        //             fromBrand: ProviderBrand.Marriot,
        //             toBrand: ProviderBrand.Hhonors,
        //             pointsExchange: 6000,
        //         },
        //     ],
        // },
        // {
        //     address: '8985 W. Amarillo Blvd, , Amarillo, Texas 79124, United States',
        //     cashPerNight: '1234',
        //     checkInDate: new Date('2023-12-07T00:00:00+00:00'),
        //     checkOutDate: new Date('2023-12-11T00:00:00+00:00'),
        //     distance: 10,
        //     key: '5cc5a237c15c7a9124178234dec288ab9a0067071',
        //     name: 'Hyatt Place Amarillo - West',
        //     pointsPerNight: '6,500',
        //     providercode: ProviderBrand.IchotelGroup,
        //     rating: '2.3',
        //     thumb: '/data/thumbnial/5cc5a237c15c7a9124178234dec288ab9a006707',
        //     transferOptions: [],
        // },
    ]);
    const [shownHotels, setShownHotels] = useState<Hotel[]>([]);
    const [activeSortingType, setActiveSortingType] = useState(SortingType.RedemptionValue);

    const [filters, setFilters] = useState<HotelFilters>({});
    const [filtersSettings, setFiltersSettings] = useState<HotelFiltersSettings>({
        distance: {
            maxValue: 0,
            minValue: 0,
        },
        averageCost: {
            maxValue: 0,
            minValue: 0,
        },
    });
    const [isFiltersActive, setIsFiltersActive] = useState(false);

    const [isLoading, setIsLoading] = useState(false);
    const [totalLoadingSteps, setTotalLoadingSteps] = useState(0);
    const [finishedLoadingSteps, setFinishedLoadingSteps] = useState(0);

    const searchId = useRef<null | string>(null);

    //ONLY FOR DEV
    const intervalObjectRef = useRef<{ [key: string]: ReturnType<typeof setTimeout> }>({});

    const { subscribe, unsubscribe } = useCentrifuge();

    const onGetHotelsFailed = useCallback(() => {
        setIsLoading(false);
    }, []);

    const onGetHotelsSuccesses = useCallback((response: HotelsSearchResponseApi) => {
        handleHotelsSearchResponseErrors(response);

        if (response.steps === 0) {
            setIsLoading(false);
            return;
        }

        if (isDebug) {
            clearIntervalObject();
            watchRequestsStatus(response.requests);
        }

        setTotalLoadingSteps(response.steps);
    }, []);

    const hotelsSearch = useHotelSearch(onGetHotelsSuccesses, onGetHotelsFailed);

    const clearIntervalObject = useCallback(() => {
        if (isDebug) {
            Object.keys(intervalObjectRef.current).forEach((key) => {
                clearInterval(intervalObjectRef.current[key]);
            });
        }
    }, []);

    const watchRequestsStatus = useCallback((requests: string[]) => {
        if (isDebug) {
            requests.map((requestId) => {
                intervalObjectRef.current[requestId] = checkIsDataReady(requestId);
            });
        }
    }, []);

    const onPublish = (response: CentrifugePublishData<CentrifugePublishedHotels>) => {
        if (response.data.searchId === searchId.current) {
            if (isDebug) {
                if (!intervalObjectRef.current[response.data.requestId]) {
                    return;
                }

                clearInterval(intervalObjectRef.current[response.data.requestId]);
                delete intervalObjectRef.current[response.data.provider];
            }
            setFinishedLoadingSteps((prevState) => prevState + 1);

            // eslint-disable-next-line @typescript-eslint/no-confusing-void-expression
            setHotels((prev) => sortHotels(activeSortingType, prepareHotels([...prev, ...response.data.hotels])));
        }
    };

    const onLoadingFinish = useCallback(() => {
        setIsLoading(false);
    }, []);

    useEffect(() => {
        if (isFiltersActive) {
            setShownHotels(filterHotels(hotels, filters));
            return;
        }

        setShownHotels(hotels);
    }, [hotels, filters, isFiltersActive]);

    useEffect(() => {
        let minDistance: number | null = null;
        let maxDistance: number | null = null;
        let minAverageCost: number | null = null;
        let maxAverageCost: number | null = null;

        hotels.forEach((hotel) => {
            if (!minDistance || hotel.distance < minDistance) {
                minDistance = hotel.distance;
            }

            if (!maxDistance || hotel.distance > maxDistance) {
                maxDistance = hotel.distance;
            }

            if (!minAverageCost || Number(hotel.cashPerNight) < minAverageCost) {
                minAverageCost = Number(hotel.cashPerNight);
            }

            if (!maxAverageCost || Number(hotel.cashPerNight) > minAverageCost) {
                maxAverageCost = Number(hotel.cashPerNight);
            }
        });

        if (minDistance && maxDistance && minAverageCost && maxAverageCost) {
            setFiltersSettings({
                distance: {
                    minValue: minDistance,
                    maxValue: maxDistance,
                },
                averageCost: {
                    minValue: minAverageCost,
                    maxValue: maxAverageCost,
                },
            });
        }
    }, [hotels]);

    useEffect(() => {
        if (!searchFormData) return;

        searchId.current = nanoid(12);
        hotelsSearch.mutate({
            ...searchFormData,
            channelName: centrifugeConfig.channelName,
            searchId: searchId.current,
        });

        setHotels([]);
        setIsLoading(true);
    }, [searchFormData]);

    useEffect(() => {
        setHotels(sortHotels(activeSortingType, hotels));
    }, [activeSortingType]);

    useEffect(() => {
        subscribe(centrifugeConfig.channelName, onPublish);
        return () => {
            unsubscribe(centrifugeConfig.channelName);
        };
    }, []);
    return (
        <HotelContext.Provider
            value={{
                hotels: shownHotels,
                searchData: searchFormData,
                setSearchFormData,
                onLoadingFinish,
                loadingPercentage: 0,
                activeSortingType,
                setActiveSortingType,
                isLoading,
                filters,
                setFilters,
                filtersSettings,
                isFiltersActive,
                setIsFiltersActive,
                totalLoadingSteps,
                finishedLoadingSteps,
            }}
        >
            {children}
        </HotelContext.Provider>
    );
}

export function useHotels() {
    const context = useContext(HotelContext);
    if (context === null) {
        throw new Error('useHotels must be used within a HotelProvider');
    }
    return context;
}
