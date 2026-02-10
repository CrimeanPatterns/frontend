import { HotelsList } from './Components/HotelsList';
import { Progressbar } from './Components/Progressbar';
import { useHotels } from '@Root/Pages/Hotels/Contexts/HotelContext/HotelContext';
import React from 'react';

export function HotelsView() {
    const { hotels, isLoading } = useHotels();
    return (
        <>
            <Progressbar />
            <HotelsList hotels={hotels} isLoading={isLoading} />
        </>
    );
}
