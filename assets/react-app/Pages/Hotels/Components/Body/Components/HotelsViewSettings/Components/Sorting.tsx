import { Align, Popover } from '@UI/Popovers';
import { SortingRow } from './SortingRow';
import { SortingType } from '@Root/Pages/Hotels/Contexts/HotelContext/HotelContext';
import { TextButton } from '@UI/Buttons';
import { Translator } from '@Services/Translator';
import React, { memo, useRef, useState } from 'react';
import classes from './Sorting.module.scss';

interface SortingOption {
    type: SortingType;
    text: string;
}

const sortingOptions: Record<SortingType, SortingOption> = {
    [SortingType.RedemptionValue]: {
        type: SortingType.RedemptionValue,
        text: Translator.trans('redemption-value'),
    },
    [SortingType.LeastExpensive]: {
        type: SortingType.LeastExpensive,
        text: Translator.trans(/** @Desc("Least Expensive") */ 'least-expensive'),
    },
    [SortingType.MostExpensive]: {
        type: SortingType.MostExpensive,
        text: Translator.trans(/** @Desc("Most Expensive") */ 'most-expensive'),
    },
    [SortingType.Distance]: { type: SortingType.Distance, text: Translator.trans('trips.distance', {}, 'trips') },
    [SortingType.CustomerRating]: {
        type: SortingType.CustomerRating,
        text: Translator.trans(/** @Desc("Customer Rating") */ 'customer-rating'),
    },
};

interface SortingProps {
    activeSortingType: SortingType;
    setActiveSortingType: (sortingType: SortingType) => void;
}
export const Sorting = memo(({ activeSortingType, setActiveSortingType }: SortingProps) => {
    const [isPopoverOpen, setIsPopoverOpen] = useState(false);

    const anchorRef = useRef<HTMLButtonElement>(null);

    const onButtonClick = () => {
        setIsPopoverOpen(!isPopoverOpen);
    };
    const closePopover = () => {
        setIsPopoverOpen(false);
    };
    return (
        <>
            <TextButton
                className={{ button: classes.anchorButton }}
                ref={anchorRef}
                iconType="Sorting"
                text={sortingOptions[activeSortingType].text}
                onClick={onButtonClick}
            />
            <Popover
                open={isPopoverOpen}
                anchor={anchorRef}
                onClose={closePopover}
                closeTrigger="click"
                offsetFromAnchorInPx={11}
                align={Align.Right}
            >
                <ul className={classes.popoverContentContainer}>
                    {Object.keys(sortingOptions).map((key) => {
                        const sortOption = sortingOptions[key as unknown as SortingType];
                        const onClick = () => {
                            setActiveSortingType(sortOption.type);
                            closePopover();
                        };
                        return (
                            <SortingRow
                                key={sortOption.type}
                                active={activeSortingType === sortOption.type}
                                text={sortOption.text}
                                onClick={onClick}
                            />
                        );
                    })}
                </ul>
            </Popover>
        </>
    );
});

Sorting.displayName = 'Sorting';
