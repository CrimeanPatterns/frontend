import { Router } from '@Services/Router';
import { axios } from '@Services/Axios';
import { toast } from '@Utilities/Toast';
import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';

export interface PlaceHintValue {
    destination: string;
    place_id?: string;
}
interface Place {
    label: string;
    value: PlaceHintValue;
}

export function useGetPlaces(searchValue: string, disable: boolean) {
    const [places, setPlaces] = useState<Place[]>([]);

    const getPlaces = useQuery({
        queryKey: ['places-search', searchValue],
        queryFn: async () => {
            try {
                const cities = await getPlacesRequest(searchValue);
                return cities;
            } catch (error) {
                const errorText = (error as Error).message;
                toast(errorText, { toastId: 'places-search', type: 'error', recharge: 60 * 1000 });
                return;
            }
        },
        enabled: searchValue !== '' && !places.find((city) => city.value.destination === searchValue) && !disable,
        staleTime: 60 * 1000,
        gcTime: 60 * 1000,
    });

    useEffect(() => {
        if (searchValue === '') {
            setPlaces([]);
        }

        if (!getPlaces.data) return;

        setPlaces(getPlaces.data);
    }, [getPlaces.data]);

    return { places, isLoading: getPlaces.isFetching };
}

type PlaceHints = {
    description: string;
    place_id?: string;
};

export async function getPlacesRequest(searchValue: string): Promise<Place[]> {
    const response = (
        await axios.get<PlaceHints[]>(
            Router.generate('google_location_autocomplete', {
                query: encodeURIComponent(searchValue),
            }),
        )
    ).data.map(({ description, place_id }) => {
        return {
            label: description,
            value: {
                destination: description,
                place_id,
            },
        };
    });

    return response;
}
