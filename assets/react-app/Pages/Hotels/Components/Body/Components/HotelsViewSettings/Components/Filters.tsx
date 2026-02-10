import { Checkbox } from '@UI/Inputs/Checkbox';
import { Icon } from '@UI/Icon';
import { SidePanel } from '@UI/Layout/SidePanel';
import { SliderInput } from '@UI/Inputs/SliderInput';
import { Switcher } from '@UI/Inputs/Switcher';
import { TextButton } from '@UI/Buttons/TextButton';
import { Translator } from '@Services/Translator';
import { useHotels } from '@Root/Pages/Hotels/Contexts/HotelContext/HotelContext';
import React, { ChangeEvent, Fragment, useCallback, useEffect, useState } from 'react';
import classNames from 'classnames';
import classes from './Filters.module.scss';

export function Filters() {
    const { filters, setFilters, filtersSettings, isFiltersActive, setIsFiltersActive } = useHotels();

    const [isFiltersOpen, setIsFiltersOpen] = useState(false);

    const [distanceRange, setDistanceRange] = useState<number[]>([
        filtersSettings.distance.minValue,
        filtersSettings.distance.maxValue,
    ]);
    const [excellentValueCheckbox, setExcellentValueCheckbox] = useState(true);
    const [goodValueCheckbox, setGoodValueCheckbox] = useState(true);
    const [fairValueCheckbox, setFairValueCheckbox] = useState(true);
    const [badValueCheckbox, setBadValueCheckbox] = useState(true);
    const [minCustomerRating, setMinCustomerRating] = useState(4);
    const [averageCostRange, setAverageCostRange] = useState<number[]>([
        filtersSettings.averageCost.minValue,
        filtersSettings.averageCost.maxValue,
    ]);
    const [showOnlyBookable, setShowOnlyBookable] = useState(true);

    const openFilters = useCallback(() => {
        setIsFiltersOpen(true);
    }, []);
    const closeFilters = useCallback(() => {
        setIsFiltersOpen(false);
    }, []);

    const onCustomRatingChange = useCallback((event: ChangeEvent<HTMLInputElement>) => {
        setMinCustomerRating(Number(event.target.value));
    }, []);

    const onApplyClick = useCallback(() => {
        setFilters({
            distance: distanceRange as [number, number],
            assessmentAW: {
                excellent: excellentValueCheckbox,
                good: goodValueCheckbox,
                fair: fairValueCheckbox,
                bad: badValueCheckbox,
            },
            minCustomRating: minCustomerRating,
            averageCost: averageCostRange as [number, number],
            showOnlyBookable: showOnlyBookable,
        });
        setIsFiltersActive(true);

        closeFilters();
    }, [
        distanceRange,
        excellentValueCheckbox,
        goodValueCheckbox,
        fairValueCheckbox,
        badValueCheckbox,
        minCustomerRating,
        averageCostRange,
        showOnlyBookable,
    ]);

    useEffect(() => {
        if (!filters.distance) {
            setDistanceRange([filtersSettings.distance.minValue, filtersSettings.distance.maxValue]);
        }
    }, [filtersSettings.distance]);

    useEffect(() => {
        if (!filters.averageCost) {
            setAverageCostRange([filtersSettings.averageCost.minValue, filtersSettings.averageCost.maxValue]);
        }
    }, [filtersSettings.averageCost]);

    return (
        <>
            <TextButton
                text={Translator.trans('filters')}
                onClick={openFilters}
                iconType={isFiltersActive ? 'FilterActive' : 'Filter'}
                iconColor={isFiltersActive ? 'active' : 'secondary'}
                type="button"
                className={{
                    text: classNames(classes.filtersButton, {
                        [classes.filtersButtonActive as string]: isFiltersActive,
                    }),
                }}
            />

            <SidePanel
                isOpen={isFiltersOpen}
                onClose={closeFilters}
                title={Translator.trans('filters')}
                onApply={onApplyClick}
            >
                <div className={classes.filters}>
                    <div className={classes.filtersWrapper}>
                        <div className={classes.filtersTitle}>Distance, km</div>
                        <SliderInput
                            range
                            value={distanceRange}
                            onChange={(value) => {
                                setDistanceRange(value);
                            }}
                            classNames={{ container: classes.filtersSlideInputContainer }}
                            max={filtersSettings.distance.maxValue}
                            min={filtersSettings.distance.minValue}
                            step={0.1}
                        />
                        <div className={classes.filtersTitle}>AwardWallet Assessment</div>
                        <div className={classes.filtersCheckboxesContainer}>
                            <Checkbox
                                checked={excellentValueCheckbox}
                                label="Excellent"
                                onChange={setExcellentValueCheckbox}
                            />
                            <Checkbox checked={goodValueCheckbox} label="Good" onChange={setGoodValueCheckbox} />
                            <Checkbox checked={fairValueCheckbox} label="Fair" onChange={setFairValueCheckbox} />
                            <Checkbox checked={badValueCheckbox} label="Bad" onChange={setBadValueCheckbox} />
                        </div>
                        <div className={classes.filtersTitle}>
                            Customer Rating
                            <div className={classes.filtersTitleRating}>
                                <Icon type="Star" size="small" color="primary" />
                                {minCustomerRating}
                            </div>
                        </div>
                        <div className={classes.filtersRating}>
                            <p>No less than:</p>
                            <div className={classes.filtersRatingContainer}>
                                {Array(5)
                                    .fill(0)
                                    .map((_, index) => {
                                        const value = index + 1;

                                        return (
                                            <Fragment key={index}>
                                                <input
                                                    name="rating"
                                                    type="radio"
                                                    id={`star-${value}`}
                                                    value={value}
                                                    checked={value === minCustomerRating}
                                                    onChange={onCustomRatingChange}
                                                    className={classes.filtersRatingInput}
                                                ></input>
                                                <label htmlFor={`star-${value}`}>
                                                    <Icon type="Star" color="active" />
                                                </label>
                                            </Fragment>
                                        );
                                    })}
                            </div>
                        </div>
                        <div className={classes.filtersTitle}>AVG. / Per Night, $</div>
                        <SliderInput
                            range
                            value={averageCostRange}
                            onChange={setAverageCostRange}
                            classNames={{ container: classes.filtersSlideInputContainer }}
                            max={filtersSettings.averageCost.maxValue}
                            min={filtersSettings.averageCost.minValue}
                        />
                        <div className={classes.filtersDivider}></div>
                        <Switcher
                            active={showOnlyBookable}
                            onChange={setShowOnlyBookable}
                            labelText={Translator.trans(
                                /**@Desc("Only show hotels bookable with my points") */ 'only-show-hotels-bookable-with-my-points',
                            )}
                            classNames={{
                                label: classes.filtersSwitcherLabel,
                                text: classes.filtersSwitcherText,
                            }}
                        />
                        <div className={classes.filtersDivider}></div>
                    </div>
                </div>
            </SidePanel>
        </>
    );
}
