import { BookHotel } from './BookHotel';
import { CheckAvailability } from './CheckAvailability';
import { ExchangePoints } from './Components/ExchangePoints';
import { Hotel } from '@Root/Pages/Hotels/Contexts/HotelContext/HotelContext';
import { getTotalHotelCost } from './Utilities';
import React from 'react';
import classes from './ModalContent.module.scss';

interface ModalContentProps {
    hotel: Hotel | null;
}

export function ModalContent({ hotel }: ModalContentProps) {
    if (!hotel) return null;
    if (!hotel.transferOptions) return null;

    return (
        <div className={classes.modalContentContainer}>
            <CheckAvailability
                imgSrc={hotel.thumb}
                name={hotel.name}
                address={hotel.address}
                brand={hotel.providercode}
                pointsPerNight={hotel.pointsPerNightFormatted}
                provider={hotel.providercode}
            />
            <ExchangePoints
                totalCost={getTotalHotelCost(
                    hotel.pointsPerNightFormatted,
                    new Date(hotel.checkInDate),
                    new Date(hotel.checkOutDate),
                )}
                brand={hotel.providercode}
                exchangeOptions={hotel.transferOptions}
            />
            <BookHotel
                imgSrc={hotel.thumb}
                name={hotel.name}
                address={hotel.address}
                pointsPerNight={hotel.pointsPerNightFormatted}
                provider={hotel.providercode}
            />
        </div>
    );
}
