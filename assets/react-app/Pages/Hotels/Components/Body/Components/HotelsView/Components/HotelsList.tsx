import { Hotel } from '@Root/Pages/Hotels/Contexts/HotelContext/HotelContext';
import { HotelCard } from './HotelCard';
import { HotelCardSkeleton } from './HotelCardSkeleton';
import React, { memo } from 'react';
import classes from './HotelsList.module.scss';

type HotelsListProps = {
    hotels: Hotel[];
    isLoading: boolean;
};

export const HotelsList = memo(({ hotels, isLoading }: HotelsListProps) => {
    return (
        <ul className={classes.hotelsList}>
            {hotels.map((hotel, index) => (
                <>
                    <HotelCard key={hotel.key} hotel={hotel} />
                    {index !== hotels.length - 1 && <div className={classes.hotelsListSeparator} />}
                </>
            ))}
            {isLoading && hotels.length === 0 && (
                <>
                    <HotelCardSkeleton />
                    <HotelCardSkeleton />
                    <HotelCardSkeleton />
                </>
            )}
        </ul>
    );
});

HotelsList.displayName = 'HotelsList';
