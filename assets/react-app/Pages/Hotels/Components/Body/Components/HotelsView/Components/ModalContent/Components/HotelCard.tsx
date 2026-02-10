import { Image } from '@UI/Layout/Image';
import { PrimaryButton } from '@UI/Buttons/PrimaryButton';
import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { Translator } from '@Services/Translator';
import { getHotelProviderLogo } from '@Root/Pages/Hotels/Utilities';
import React from 'react';
import classes from './HotelCard.module.scss';

type HotelCardProps = {
    buttonText: string;
    imgSrc: string;
    name: string;
    address: string;
    pointsPerNight: string;
    provider: ProviderBrand;
};

export function HotelCard({ buttonText, imgSrc, name, address, provider, pointsPerNight }: HotelCardProps) {
    return (
        <div className={classes.hotelCard}>
            <Image
                src={imgSrc}
                classes={{
                    container: classes.hotelImgContainer,
                    img: classes.hotelImg,
                    errorContainer: classes.hotelImgContainer,
                }}
            />
            <div>
                <h4 className={classes.hotelName}>{name}</h4>
                <p className={classes.hotelAddress}>{address}</p>
            </div>
            <div className={classes.additionalInfo}>
                <div className={classes.hotelLogoContainer}>{getHotelProviderLogo(provider, classes.logo)}</div>
                <div className={classes.pointsContainer}>
                    <div className={classes.pointsNumber}>{pointsPerNight}</div>
                    {Translator.trans(/** @Desc("Points / per night") */ 'points-per-night')}
                </div>
                <PrimaryButton text={buttonText} className={{ button: classes.hotelButton }} />
            </div>
        </div>
    );
}
