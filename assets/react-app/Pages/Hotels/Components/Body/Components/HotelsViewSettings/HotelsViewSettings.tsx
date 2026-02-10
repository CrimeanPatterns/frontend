import { Cross } from '@UI/Icon/Cross/Cross';
import { FilterTag } from './Components/FilterTag';
import { Filters } from './Components/Filters';
import { HotelFilters, useHotels } from '../../../../Contexts/HotelContext/HotelContext';
import { Sorting } from './Components/Sorting';
import { Translator } from '@Services/Translator';
import React, { useCallback } from 'react';
import classes from './HotelsViewSettings.module.scss';
import isEmpty from 'lodash/isEmpty';

export function HotelsViewSettings() {
    const { hotels, setActiveSortingType, activeSortingType, filters, setFilters, setIsFiltersActive } = useHotels();

    const onRemoveTag = useCallback(
        (filterField: keyof HotelFilters) => {
            const newFilters = { ...filters };
            delete newFilters[filterField];

            if (isEmpty(newFilters)) {
                setIsFiltersActive(false);
                return;
            }

            setFilters(newFilters);
        },
        [filters],
    );

    const onRemoveAllTags = useCallback(() => {
        setIsFiltersActive(false);
        setFilters({});
    }, []);

    return (
        <div className={classes.hotelsViewSettings}>
            <div className={classes.hotelsViewSettingsWrapper}>
                <div className={classes.hotelsViewSettingsTitle}>
                    {hotels.length > 0
                        ? `${Translator.trans(/** @Desc("Showing") */ 'showing')} ${hotels.length}`
                        : Translator.trans(/** @Desc("Best Deals") */ 'best-deals')}
                </div>
                <div className={classes.hotelsViewSettingsActionButtonWrapper}>
                    <Filters />
                    <Sorting activeSortingType={activeSortingType} setActiveSortingType={setActiveSortingType} />
                </div>
            </div>
            {!isEmpty(filters) && (
                <div className={classes.hotelsViewSettingsFiltersTagContainer}>
                    <div className={classes.hotelsViewSettingsFiltersTagWrapper}>
                        {filters.distance && (
                            <FilterTag
                                label={`${Translator.trans('trips.distance', {}, 'trips')}: ${filters.distance[0]}-${
                                    filters.distance[1]
                                } ${Translator.trans('km')}`}
                                onRemove={() => {
                                    onRemoveTag('distance');
                                }}
                            />
                        )}
                        {filters.assessmentAW && Object.values(filters.assessmentAW).some((value) => value) && (
                            <FilterTag
                                label={`${Translator.trans(
                                    'awardwallet-assessment',
                                )}: ${getAwardWalletAssessmentLabelText(filters.assessmentAW)}`}
                                onRemove={() => {
                                    onRemoveTag('assessmentAW');
                                }}
                            />
                        )}
                        {filters.minCustomRating && (
                            <FilterTag
                                label={`${Translator.trans('customer-rating')}: ${Translator.trans(
                                    /** @Desc("No less than") */ 'no-less-than',
                                )} ${filters.minCustomRating}`}
                                onRemove={() => {
                                    onRemoveTag('minCustomRating');
                                }}
                            />
                        )}
                        {filters.averageCost && (
                            <FilterTag
                                label={`${Translator.trans('avg-per-night')}: ${filters.averageCost[0]}-${
                                    filters.averageCost[1]
                                }$`}
                                onRemove={() => {
                                    onRemoveTag('averageCost');
                                }}
                            />
                        )}
                    </div>
                    <Cross onClick={onRemoveAllTags} className={classes.hotelsViewSettingsFiltersTagCrossButton} />
                </div>
            )}
        </div>
    );
}

type GetAwardWalletAssessmentLabelTextArgs = {
    excellent?: boolean;
    good?: boolean;
    fair?: boolean;
    bad?: boolean;
};
function getAwardWalletAssessmentLabelText({ excellent, good, fair, bad }: GetAwardWalletAssessmentLabelTextArgs) {
    const result = [];

    if (excellent) {
        result.push(Translator.trans('excellent'));
    }

    if (good) {
        result.push(Translator.trans('good'));
    }

    if (fair) {
        result.push(Translator.trans('fair'));
    }

    if (bad) {
        result.push(Translator.trans('Bad'));
    }

    return result.join(',');
}
