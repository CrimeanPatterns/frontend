import { Hotel, HotelFilters, SortingType } from './HotelContext';
import { HotelsSearchResponseApi, isHotelsSearchResponseWithError } from '../../Hooks/UseHotelsSearch';
import { Router } from '@Services/Router';
import { Translator } from '@Services/Translator';
import { axios } from '@Services/Axios';
import { getHotelProviderLogo } from '../../Utilities';
import { toast } from '@Utilities/Toast';

export function prepareHotels(hotels: Hotel[]): Hotel[] {
    return hotels.map((hotel) => ({
        ...hotel,
        Logo: getHotelProviderLogo(hotel.providercode),
    }));
}

export function sortHotels(sortingType: SortingType, hotels: Hotel[]): Hotel[] {
    const copyHotels = [...hotels];

    switch (sortingType) {
        case SortingType.RedemptionValue:
            return hotels;
        case SortingType.LeastExpensive:
            copyHotels.sort((a, b) => {
                return a.cashPerNight - b.cashPerNight;
            });
            return copyHotels;
        case SortingType.MostExpensive:
            copyHotels.sort((a, b) => {
                return b.cashPerNight - a.cashPerNight;
            });
            return copyHotels;
        case SortingType.Distance:
            copyHotels.sort((a, b) => {
                return Number(a.distance) - Number(b.distance);
            });
            return copyHotels;
        case SortingType.CustomerRating:
            copyHotels.sort((a, b) => {
                return Number(b.rating) - Number(a.rating);
            });
            return copyHotels;
    }
}
export function handleHotelsSearchResponseErrors(hotelsResponse: HotelsSearchResponseApi) {
    if (isHotelsSearchResponseWithError(hotelsResponse)) {
        toast(
            Translator.trans(
                /** @Desc("Not all providers are available.") */ 'search-hotel-not-all-providers-available',
            ),
            {
                toastId: 'hotelRequest',
                type: 'error',
            },
        );
    }
}

export function filterHotels(hotels: Hotel[], filters: HotelFilters): Hotel[] {
    //TODO: Implement filters by AW assessment, bookable hotels
    const resultHotels = hotels.filter((hotel) => {
        if (filters.distance && (hotel.distance < filters.distance[0] || hotel.distance > filters.distance[1])) {
            return false;
        }

        if (filters.minCustomRating && Number(hotel.rating) < filters.minCustomRating) {
            return false;
        }

        if (
            filters.averageCost &&
            (Number(hotel.cashPerNight) < filters.averageCost[0] || Number(hotel.cashPerNight) > filters.averageCost[1])
        ) {
            return false;
        }

        return true;
    });
    return resultHotels;
}

//Only for dev
export function checkIsDataReady(requestId: string) {
    let isFinished = true;
    const timerID = setInterval(() => {
        (async () => {
            if (isFinished) {
                isFinished = false;

                await axios.get(
                    Router.generate('aw_hotels_data_getresult', {
                        requestId: encodeURIComponent(requestId),
                    }),
                );
            }
        })()
            .catch((error: unknown) => {
                // eslint-disable-next-line no-console
                console.error(error);
            })
            .finally(() => {
                isFinished = true;
            });
    }, 10000);
    return timerID;
}
