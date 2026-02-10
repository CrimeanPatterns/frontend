import { Align } from '@UI/Popovers';
import { Breadcrumb } from '@UI/Layout/Breadcrumb';
import { GuestsAndRoomsSelectRow } from './GuestsAndRoomsSelectRow';
import { Icon, IconType } from '@UI/Icon';
import {
    Max_Adults_Count,
    Max_Children_Count,
    Max_Guests_Count,
    Max_Rooms_Count,
    Min_Adults_Count,
    Min_Children_Count,
    Min_Rooms_Count,
} from '../Constant';
import { Popover } from '@UI/Popovers/Popover/Popover';
import { Separator } from './Separator';
import { Translator } from '@Services/Translator';
import React, { memo, useCallback, useEffect, useRef, useState } from 'react';
import classes from './GuestsAndRoomsSelect.module.scss';

interface GuestsAndRoomsSelectProps {
    numberRooms: number;
    onNumberRoomsChange: (value: number) => void;
    numberAdults: number;
    onNumberAdultsChange: (value: number) => void;
    numberChildren: number;
    onNumberChildrenChange: (value: number) => void;
}
export const GuestsAndRoomsSelect = memo(
    ({
        numberRooms,
        onNumberRoomsChange,
        numberAdults,
        onNumberAdultsChange,
        numberChildren,
        onNumberChildrenChange,
    }: GuestsAndRoomsSelectProps) => {
        const [iconType, setIconType] = useState<IconType>('ChevronDown');

        const [isPopoverOpen, setIsPopoverOpen] = useState(false);

        const [leftAvailableGuest, setLeftAvailableGuest] = useState(Max_Guests_Count - numberAdults - numberChildren);

        const anchorRef = useRef<HTMLDivElement>(null);

        const openPopover = useCallback(() => {
            setIsPopoverOpen(true);
            setIconType('ChevronUp');
        }, []);

        const closePopover = useCallback(() => {
            setIsPopoverOpen(false);
            setIconType('ChevronDown');
        }, []);

        const onAnchorClick = useCallback(() => {
            if (isPopoverOpen) {
                closePopover();
                return;
            }

            openPopover();
        }, [openPopover, closePopover]);

        useEffect(() => {
            setLeftAvailableGuest(Max_Guests_Count - numberAdults - numberChildren);
        }, [numberAdults, numberChildren]);

        return (
            <>
                <div className={classes.container}>
                    <Breadcrumb
                        ref={anchorRef}
                        onClick={onAnchorClick}
                        className={classes.breadcrumbContainer}
                        items={[
                            <Icon key="1" type="Person" />,
                            <span key="2" className={classes.description} style={{ minWidth: 55 }}>
                                {`${numberRooms} ${Translator.transChoice(
                                    /** @Desc("{0}rooms|{1}room|[2,Inf]rooms") */ 'rooms-count',
                                    numberRooms,
                                )}`}
                            </span>,
                            <React.Fragment key="3">
                                <span className={classes.description} style={{ minWidth: 56 }}>
                                    {`${numberAdults + numberChildren} ${Translator.transChoice(
                                        /** @Desc("{0}guests|{1}guest|[2,Inf]guests") */ 'guests-count',
                                        numberAdults + numberChildren,
                                    )}`}
                                </span>
                                <Icon type={iconType} size="small" color="disabled" />
                            </React.Fragment>,
                        ]}
                        separator={<Separator />}
                    />
                </div>

                <Popover
                    open={isPopoverOpen}
                    anchor={anchorRef}
                    onClose={closePopover}
                    align={Align.Left}
                    closeTrigger="click"
                    offsetFromAnchorInPx={20}
                >
                    <div className={classes.popoverContentContainer}>
                        <GuestsAndRoomsSelectRow
                            label={Translator.transChoice(
                                /** @Desc("{0}rooms|{1}room|[2,Inf]rooms") */ 'rooms-count',
                                1,
                            )}
                            value={numberRooms}
                            onValueChange={onNumberRoomsChange}
                            minValue={Min_Rooms_Count}
                            maxValue={Max_Rooms_Count}
                        />
                        <GuestsAndRoomsSelectRow
                            label={Translator.trans('itineraries.trip.adults', undefined, 'trips')}
                            value={numberAdults}
                            onValueChange={onNumberAdultsChange}
                            minValue={Min_Adults_Count}
                            maxValue={Math.min(Max_Adults_Count, leftAvailableGuest + numberAdults)}
                        />
                        <GuestsAndRoomsSelectRow
                            label={Translator.trans(/** @Desc("Children") */ 'children')}
                            value={numberChildren}
                            onValueChange={onNumberChildrenChange}
                            minValue={Min_Children_Count}
                            maxValue={Math.min(Max_Children_Count, leftAvailableGuest + numberChildren)}
                        />
                    </div>
                </Popover>
            </>
        );
    },
);

GuestsAndRoomsSelect.displayName = 'GuestsAndRoomsSelect';
