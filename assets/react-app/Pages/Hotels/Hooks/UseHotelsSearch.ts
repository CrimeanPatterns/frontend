import { ProviderBrand } from '../Entities';
import { Router } from '@Services/Router';
import { axios } from '@Services/Axios';
import { toast } from '@Utilities/Toast';
import { useMutation } from '@tanstack/react-query';

export function useHotelSearch(onSuccesses: (response: HotelsSearchResponseApi) => void, onError: () => void) {
    const response = useMutation({
        mutationFn: getHotelsRequest,
        onError: (error) => {
            toast(error.message, { toastId: 'hotelsRequest', type: 'error' });
            onError();
        },
        onSuccess: (response) => {
            onSuccesses(response);
        },
    });

    return response;
}

export type HotelsRequestData = {
    destination: string;
    place_id?: string;
    checkIn: string;
    checkOut: string;
    numberOfRooms: number;
    numberOfAdults: number;
    numberOfKids: number;
    providers: string[];
    channelName: string;
    searchId: string;
};

type HotelsSearchResponseApiWithoutError = {
    steps: number;
    // Only for dev
    requests: string[];
};

interface HotelsSearchResponseApiWithError extends HotelsSearchResponseApiWithoutError {
    partial: true;
    errors: { provider: ProviderBrand; error: string }[];
}

export type HotelsSearchResponseApi = HotelsSearchResponseApiWithoutError | HotelsSearchResponseApiWithError;

export function isHotelsSearchResponseWithError(
    response: HotelsSearchResponseApi,
): response is HotelsSearchResponseApiWithError {
    return 'partial' in response;
}

async function getHotelsRequest(data: HotelsRequestData) {
    return (await axios.post<HotelsSearchResponseApi>(Router.generate('aw_hotels_data_search'), data)).data;
}
