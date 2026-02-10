import {
    Adults_Count_Field_Default_Value,
    Adults_Count_Field_Name,
    Brands_Field_Default_Value,
    Brands_Field_Name,
    Children_Count_Field_Default_Value,
    Children_Count_Field_Name,
    Date_Field_Default_Value,
    Date_From_Field_Name,
    Date_Until_Field_Name,
    Destination_Field_Default_Value,
    Destination_Field_Name,
    Place_Id_Field_Name,
    Place_Id_Field__Default_Value,
    Rooms_Count_Field_Default_Value,
    Rooms_Count_Field_Name,
    sanitizingScheme,
    validationScheme,
} from './Constant';
import { DateRangeData } from '@UI/Inputs';
import { PlaceHintValue, getPlacesRequest } from '../Hooks/UseGetPlaces';
import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { SubmitHandler, useForm } from 'react-hook-form';
import { isDate, isSameDay, isSameMonth, isSameYear } from 'date-fns';
import {
    transformDateIntoString,
    transformIntoDate,
    transformIntoNumber,
    transformStringIntoArray,
} from '@Utilities/Hooks/UseSearchParams/Utilities';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useDebounce } from '@Utilities/Hooks/UseDebounce';
import { useHotels } from '@Root/Pages/Hotels/Contexts/HotelContext/HotelContext';
import { useSearchParams } from '@Utilities/Hooks/UseSearchParams';
import isEqual from 'lodash/isEqual';
import sortBy from 'lodash/sortBy';

export interface SearchForm {
    [Destination_Field_Name]: string;
    [Place_Id_Field_Name]: string | null;
    dateRange: {
        [Date_From_Field_Name]: Date | null;
        [Date_Until_Field_Name]: Date | null;
    };
    requirementsForHotels: {
        [Rooms_Count_Field_Name]: number;
        [Adults_Count_Field_Name]: number;
        [Children_Count_Field_Name]: number;
    };

    [Brands_Field_Name]: ProviderBrand[];
}

type UseSearchFormProps = {
    setIsSubmitButtonDisabled: (value: boolean) => void;
    fromDateError: string;
    untilDateError: string;
};

export const useSearchForm = ({ setIsSubmitButtonDisabled, fromDateError, untilDateError }: UseSearchFormProps) => {
    const { setOneSearchParam, getParam } = useSearchParams(sanitizingScheme, validationScheme);
    const [valueForDestinationHints, setValueForDestinationHints] = useState(
        getParam(Destination_Field_Name, Destination_Field_Default_Value),
    );

    const formValueDefault = useMemo<SearchForm>(
        () => ({
            [Destination_Field_Name]: getParam(Destination_Field_Name, Destination_Field_Default_Value),
            [Place_Id_Field_Name]: Place_Id_Field__Default_Value,
            dateRange: {
                [Date_From_Field_Name]: getParam(Date_From_Field_Name, Date_Field_Default_Value, transformIntoDate),
                [Date_Until_Field_Name]: getParam(Date_Until_Field_Name, Date_Field_Default_Value, transformIntoDate),
            },
            requirementsForHotels: {
                [Rooms_Count_Field_Name]: getParam(
                    Rooms_Count_Field_Name,
                    Rooms_Count_Field_Default_Value,
                    transformIntoNumber,
                ),
                [Adults_Count_Field_Name]: getParam(
                    Adults_Count_Field_Name,
                    Adults_Count_Field_Default_Value,
                    transformIntoNumber,
                ),
                [Children_Count_Field_Name]: getParam(
                    Children_Count_Field_Name,
                    Children_Count_Field_Default_Value,
                    transformIntoNumber,
                ),
            },

            [Brands_Field_Name]: getParam<ProviderBrand[], ProviderBrand[]>(
                Brands_Field_Name,
                Brands_Field_Default_Value,
                transformStringIntoArray('&'),
            ),
        }),
        [],
    );

    const { searchData, setSearchFormData } = useHotels();

    const compareFormData = useCallback(() => {
        if (!searchData) return false;

        const formData = getValues();

        if (formData[Destination_Field_Name] !== searchData.destination) {
            return false;
        }

        const fromDate = new Date(searchData.checkIn);
        if (
            !isSameDay(formData.dateRange[Date_From_Field_Name] as Date, fromDate) ||
            !isSameMonth(formData.dateRange[Date_From_Field_Name] as Date, fromDate) ||
            !isSameYear(formData.dateRange[Date_From_Field_Name] as Date, fromDate)
        ) {
            return false;
        }

        const toDate = new Date(searchData.checkOut);
        if (
            !isSameDay(formData.dateRange[Date_Until_Field_Name] as Date, toDate) ||
            !isSameMonth(formData.dateRange[Date_Until_Field_Name] as Date, toDate) ||
            !isSameYear(formData.dateRange[Date_Until_Field_Name] as Date, toDate)
        ) {
            return false;
        }

        if (formData.requirementsForHotels[Adults_Count_Field_Name] !== searchData.numberOfAdults) {
            return false;
        }

        if (formData.requirementsForHotels[Children_Count_Field_Name] !== searchData.numberOfKids) {
            return false;
        }

        if (formData.requirementsForHotels[Rooms_Count_Field_Name] !== searchData.numberOfRooms) {
            return false;
        }

        if (!isEqual(sortBy(formData[Brands_Field_Name]), sortBy(searchData.providers))) {
            return false;
        }

        return true;
    }, [searchData]);

    const { control, handleSubmit, trigger, setValue, getValues } = useForm<SearchForm>({
        defaultValues: formValueDefault,
        reValidateMode: 'onChange',
    });

    const debouncedDestinationValue = useDebounce<string>(valueForDestinationHints, 400);

    const onRoomsCountChange = useCallback(
        (newValue: number) => {
            setValue(`requirementsForHotels.${Rooms_Count_Field_Name}`, newValue);

            const shouldSetSubmitButtonDisabled = compareFormData();
            setIsSubmitButtonDisabled(shouldSetSubmitButtonDisabled);

            setOneSearchParam(Rooms_Count_Field_Name, String(newValue), String(Rooms_Count_Field_Default_Value));
        },
        [compareFormData],
    );

    const onAdultsCountChange = useCallback(
        (newValue: number) => {
            setValue(`requirementsForHotels.${Adults_Count_Field_Name}`, newValue);

            setOneSearchParam(Adults_Count_Field_Name, String(newValue), String(Adults_Count_Field_Default_Value));

            const shouldSetSubmitButtonDisabled = compareFormData();
            setIsSubmitButtonDisabled(shouldSetSubmitButtonDisabled);
        },
        [compareFormData],
    );

    const onChildrenCountChange = useCallback(
        (newValue: number) => {
            setValue(`requirementsForHotels.${Children_Count_Field_Name}`, newValue);

            setOneSearchParam(Children_Count_Field_Name, String(newValue), String(Children_Count_Field_Default_Value));

            const shouldSetSubmitButtonDisabled = compareFormData();
            setIsSubmitButtonDisabled(shouldSetSubmitButtonDisabled);
        },
        [compareFormData],
    );

    const onDestinationInputChange = useCallback(
        async (value: string | PlaceHintValue, isHint: boolean) => {
            if (typeof value === 'string') {
                setValueForDestinationHints(value);
                setValue(Destination_Field_Name, '');
                setOneSearchParam(Destination_Field_Name, '');
                setValue(Place_Id_Field_Name, null);
                return;
            }

            if (isHint) {
                setValue(Destination_Field_Name, value.destination);
                setOneSearchParam(Destination_Field_Name, value.destination);
                setValueForDestinationHints(value.destination);
                setValue(Place_Id_Field_Name, value.place_id || null);
                const shouldSetSubmitButtonDisabled = compareFormData();
                setIsSubmitButtonDisabled(shouldSetSubmitButtonDisabled);
                await trigger(Destination_Field_Name);
            }
        },
        [compareFormData],
    );

    const onDateRangeChange = useCallback(
        async (newValue: DateRangeData) => {
            setValue(`dateRange.${Date_From_Field_Name}`, newValue.from);
            setValue(`dateRange.${Date_Until_Field_Name}`, newValue.until);

            if (fromDateError || untilDateError) {
                await trigger('dateRange');
            }

            const shouldSetSubmitButtonDisabled = compareFormData();
            setIsSubmitButtonDisabled(shouldSetSubmitButtonDisabled);

            setOneSearchParam(Date_From_Field_Name, transformDateIntoString(newValue.from), null);

            setOneSearchParam(Date_Until_Field_Name, transformDateIntoString(newValue.until), null);
        },
        [compareFormData, fromDateError, untilDateError],
    );

    const onSelectedProvidersChange = useCallback(
        (activeBrands: ProviderBrand[]) => {
            if (activeBrands.length < 1) return;
            setValue(Brands_Field_Name, activeBrands);
            setOneSearchParam(
                Brands_Field_Name,
                activeBrands.sort((a: string, b: string) => a.localeCompare(b)).join('&'),
                Brands_Field_Default_Value.join('&'),
            );
            const shouldSetSubmitButtonDisabled = compareFormData();
            setIsSubmitButtonDisabled(shouldSetSubmitButtonDisabled);
        },
        [compareFormData],
    );

    const onSubmit: SubmitHandler<SearchForm> = useCallback((data) => {
        setSearchFormData({
            destination: data[Destination_Field_Name],
            place_id: data[Place_Id_Field_Name] ?? undefined,
            checkIn: data.dateRange[Date_From_Field_Name]?.toISOString().split('T')[0] as string,
            checkOut: data.dateRange[Date_Until_Field_Name]?.toISOString().split('T')[0] as string,
            numberOfAdults: data.requirementsForHotels[Adults_Count_Field_Name],
            numberOfKids: data.requirementsForHotels[Children_Count_Field_Name],
            numberOfRooms: data.requirementsForHotels[Rooms_Count_Field_Name],
            providers: data[Brands_Field_Name],
        });
        setIsSubmitButtonDisabled(true);
    }, []);

    useEffect(() => {
        async function searchHotels() {
            const formData = getValues();

            if (formData[Destination_Field_Name].length === 0) {
                return;
            }

            if (!isDate(formData.dateRange[Date_From_Field_Name])) {
                return;
            }

            if (!isDate(formData.dateRange[Date_Until_Field_Name])) {
                return;
            }

            const destinations = await getPlacesRequest(formData[Destination_Field_Name]);

            if (destinations.every((destination) => destination.label === formData[Destination_Field_Name])) {
                setOneSearchParam(Destination_Field_Name, '');
                setValue(Destination_Field_Name, '');
                setValueForDestinationHints('');
                return;
            }

            onSubmit(formData);
        }

        searchHotels().catch(() => {});
    }, []);

    return {
        destinationValue: valueForDestinationHints,
        debouncedDestinationValue,
        onSubmit: handleSubmit(onSubmit),
        control,
        getValues,
        onDestinationInputChange,
        onDateRangeChange,
        onRoomsCountChange,
        onAdultsCountChange,
        onChildrenCountChange,
        onSelectedProvidersChange,
    };
};
