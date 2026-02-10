import { HotelCard } from './Components/HotelCard';
import { Line } from './Components/Line';
import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { StepHeader } from './Components/StepHeader';
import { Translator } from '@Services/Translator';
import React from 'react';
import classes from './ModalContent.module.scss';

type CheckAvailabilityProps = {
    imgSrc: string;
    name: string;
    address: string;
    brand: string;
    pointsPerNight: string;
    provider: ProviderBrand;
};

export function CheckAvailability(props: CheckAvailabilityProps) {
    return (
        <div className={classes.stepContainer}>
            <StepHeader
                numberOfStep={1}
                title={Translator.trans(
                    /** @Desc("Check availability of the hotel offer") */ 'check-hotel-availability',
                )}
                description={Translator.trans(
                    /** @Desc("Please verify that this hotel is still available for the listed price before initiating the transfer.") */ 'check-hotel-availability',
                )}
                iconType="DoubleTick"
            />
            <Line />
            <HotelCard
                buttonText={Translator.trans(/** @Desc("Check availability") */ 'check-availability')}
                {...props}
            />
        </div>
    );
}
