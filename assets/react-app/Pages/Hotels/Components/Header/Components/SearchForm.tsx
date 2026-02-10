import {
    Adults_Count_Field_Name,
    Brands_Field_Name,
    Children_Count_Field_Name,
    Date_From_Field_Name,
    Date_Until_Field_Name,
    Destination_Field_Name,
    Rooms_Count_Field_Name,
} from './Constant';
import { AutoComplete } from '@UI/Inputs/AutoComplete';
import { Controller, ControllerFieldState, ControllerRenderProps } from 'react-hook-form';
import { DateRange } from '@UI/Inputs/DateRange';
import { GuestsAndRoomsSelect } from './GuestsAndRoomsSelect/GuestsAndRoomsSelect';
import { HotelBrandsSelect } from './HotelBrandsSelect/HotelBrandsSelect';
import { PrimaryButton } from '@UI/Buttons/PrimaryButton';
import { SearchForm, useSearchForm } from './UseSearchForm';
import { Translator } from '@Services/Translator';
import { useAppSettingsContext } from '../../../../../Contexts/AppSettingsContext';
import { useGetPlaces } from '../Hooks/UseGetPlaces';
import React, { useCallback, useMemo, useRef, useState } from 'react';
import classes from './SearchForm.module.scss';

export function SearchForm() {
    const appSettings = useAppSettingsContext();

    const [fromDateError, setFromDateError] = useState('');
    const [untilDateError, setUntilDateError] = useState('');
    const [isSubmitButtonDisabled, setIsSubmitButtonDisabled] = useState(false);

    const fromDateRef = useRef<HTMLInputElement>(null);
    const untilDateRef = useRef<HTMLInputElement>(null);

    const {
        destinationValue,
        debouncedDestinationValue,
        onSubmit,
        control,
        getValues,
        onDestinationInputChange,
        onDateRangeChange,
        onRoomsCountChange,
        onAdultsCountChange,
        onChildrenCountChange,
        onSelectedProvidersChange,
    } = useSearchForm({
        setIsSubmitButtonDisabled,
        fromDateError,
        untilDateError,
    });

    const isDestinationSearchDisabled = useRef(destinationValue.length > 0 ? false : true);

    const destinationFiledRules = useMemo(
        () => ({
            required: Translator.trans(/** @Desc("Choose destination from hints") */ 'choose-destination-from-hints'),
        }),
        [],
    );

    const buttonClasses = useMemo(
        () => ({
            button: classes.searchButton,
            text: classes.searchButtonContent,
            icon: classes.searchButtonContent,
        }),
        [],
    );
    const dateRangeClasses = useMemo(() => ({ dateInputContainer: classes.dateInput }), []);
    const autoCompleteClasses = useMemo(
        () => ({
            container: classes.autocompleteInput,
        }),
        [],
    );
    const dateRangeErrors = useMemo(
        () => ({
            fromInputErrorText: fromDateError,
            untilInputErrorText: untilDateError,
        }),
        [fromDateError, untilDateError],
    );

    const { places, isLoading } = useGetPlaces(debouncedDestinationValue, isDestinationSearchDisabled.current);

    const validateDateRange = useCallback((value: { dateFrom: Date | null; dateUntil: Date | null }) => {
        let isComponentValid = true;

        if (!value.dateFrom) {
            setFromDateError(Translator.trans(/** @Desc("Choose check-in date") */ 'choose-check-in-date'));
            isComponentValid = false;
        } else {
            setFromDateError('');
        }

        if (!value.dateUntil) {
            setUntilDateError(Translator.trans(/** @Desc("Choose check-out date") */ 'choose-check-out-date'));
            isComponentValid = false;
        } else {
            setUntilDateError('');
        }

        const destinationValue = getValues(Destination_Field_Name);

        if (!fromDateError && destinationValue !== '' && !value.dateFrom) {
            fromDateRef.current?.focus();
        }

        if (!untilDateError && destinationValue !== '' && value.dateFrom && !value.dateUntil) {
            untilDateRef.current?.focus();
        }

        return isComponentValid;
    }, []);

    const onDestinationInputFocus = useCallback(() => {
        isDestinationSearchDisabled.current = false;
    }, []);
    const onDestinationInputBlur = useCallback(() => {
        isDestinationSearchDisabled.current = true;
    }, []);

    const autoCompleteRender = useCallback(
        ({
            field,
            fieldState,
        }: {
            field: ControllerRenderProps<SearchForm, typeof Destination_Field_Name>;
            fieldState: ControllerFieldState;
        }) => {
            const onBlurHandler = () => {
                field.onBlur();
                onDestinationInputBlur();
            };
            return (
                <AutoComplete
                    iconType="Location"
                    placeholder={Translator.trans(/** @Desc("Where are you going?") */ 'where-are-you-going')}
                    hints={places}
                    errorText={fieldState.error?.message}
                    showLoader={isLoading}
                    onChange={onDestinationInputChange}
                    forbiddenChars="<>"
                    classes={autoCompleteClasses}
                    value={destinationValue}
                    onBlur={onBlurHandler}
                    onFocus={onDestinationInputFocus}
                    ref={field.ref}
                />
            );
        },
        [places, isLoading, destinationValue],
    );

    const brandFieldRender = useCallback(
        ({ field }: { field: ControllerRenderProps<SearchForm, typeof Brands_Field_Name> }) => {
            return (
                <HotelBrandsSelect
                    activeBrands={field.value}
                    onActiveBrandsChange={onSelectedProvidersChange}
                    onSubmit={onSubmit}
                    isSubmitButtonDisabled={isSubmitButtonDisabled}
                />
            );
        },
        [isSubmitButtonDisabled],
    );

    return (
        <form className={classes.form} onSubmit={onSubmit}>
            <Controller
                name={Destination_Field_Name}
                control={control}
                rules={destinationFiledRules}
                render={autoCompleteRender}
            />
            <Controller
                name="dateRange"
                control={control}
                rules={{
                    validate: validateDateRange,
                }}
                render={({ field }) => {
                    return (
                        <DateRange
                            fromValue={field.value[Date_From_Field_Name]}
                            untilValue={field.value[Date_Until_Field_Name]}
                            onChange={onDateRangeChange}
                            locale={appSettings.localeForIntl}
                            errors={dateRangeErrors}
                            fromDatePlaceholder={Translator.trans(/** @Desc("Check-in Date") */ 'check-in-date')}
                            untilDatePlaceholder={Translator.trans(/** @Desc("Check-out Date") */ 'check-out-date')}
                            fromInputRef={fromDateRef}
                            untilInputRef={untilDateRef}
                            classes={dateRangeClasses}
                        />
                    );
                }}
            />
            <PrimaryButton
                className={buttonClasses}
                text={Translator.trans('search')}
                iconType="Search"
                type="submit"
                disabled={isSubmitButtonDisabled}
            />
            <div className={classes.brandAndGuestsRow}>
                <Controller
                    name="requirementsForHotels"
                    control={control}
                    render={({ field }) => {
                        return (
                            <>
                                <GuestsAndRoomsSelect
                                    numberRooms={field.value[Rooms_Count_Field_Name]}
                                    onNumberRoomsChange={onRoomsCountChange}
                                    numberAdults={field.value[Adults_Count_Field_Name]}
                                    onNumberAdultsChange={onAdultsCountChange}
                                    numberChildren={field.value[Children_Count_Field_Name]}
                                    onNumberChildrenChange={onChildrenCountChange}
                                />
                            </>
                        );
                    }}
                />
                <Controller name={Brands_Field_Name} control={control} render={brandFieldRender} />
            </div>
        </form>
    );
}
