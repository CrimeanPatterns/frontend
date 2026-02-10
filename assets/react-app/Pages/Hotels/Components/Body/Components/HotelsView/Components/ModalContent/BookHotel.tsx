import { HotelCard } from './Components/HotelCard';
import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { StepHeader } from './Components/StepHeader';
import { Translator } from '@Services/Translator';
import React from 'react';
import classes from './ModalContent.module.scss';

type BookHotelProps = {
    imgSrc: string;
    name: string;
    address: string;
    pointsPerNight: string;
    provider: ProviderBrand;
};

export function BookHotel({ imgSrc, name, address, pointsPerNight, provider }: BookHotelProps) {
    return (
        <div className={classes.stepContainer}>
            <StepHeader
                numberOfStep={3}
                title={Translator.trans(/** @Desc("Book your hotel") */ 'book-your-hotel')}
                iconType="CheckedCalendar"
            />
            <HotelCard
                buttonText={Translator.trans(/** @Desc("Book now") */ 'book-now')}
                imgSrc={imgSrc}
                name={name}
                address={address}
                pointsPerNight={pointsPerNight}
                provider={provider}
            />
        </div>
    );
}
