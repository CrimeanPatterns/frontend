import { ExchangeOption } from './ExchangeOption';
import { ExchangePoint } from '@Root/Pages/Hotels/Contexts/HotelContext/HotelContext';
import { Line } from './Line';
import { PrimaryButton } from '@UI/Buttons/PrimaryButton';
import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { StepHeader } from './StepHeader';
import { Translator } from '@Services/Translator';
import { getHotelBrandName } from '../Utilities';
import React, { useState } from 'react';
import classes from './ExchangePoints.module.scss';

type ExchangePointsProps = {
    totalCost: string;
    brand: ProviderBrand;
    exchangeOptions: ExchangePoint[];
};

export function ExchangePoints({ totalCost, brand, exchangeOptions }: ExchangePointsProps) {
    const [selectedOption, setSelectedOption] = useState(0);

    const onClick = () => {
        window.open('https://www.hyatt.com/', '_blank');
    };
    return (
        <div className={classes.stepContainer}>
            <StepHeader
                numberOfStep={2}
                title={Translator.trans(/** @Desc("Select points to transfer") */ 'select-points-transfer')}
                description={Translator.trans(
                    /** @Desc("You are %missing_points% points short to book this %cost%-point %brand% stay. We have selected some of the best
                transfer options for you:") */ 'select-points-transfer-description',
                    {
                        missing_points: exchangeOptions[0]?.pointsExchange ?? '',
                        cost: totalCost,
                        brand: getHotelBrandName(brand),
                    },
                )}
                iconType="Change"
            />
            <Line />
            <div className={classes.exchangeOptionContainer}>
                {exchangeOptions.map((option, index) => (
                    <ExchangeOption
                        key={index}
                        selected={index === selectedOption}
                        fromBrand={option.fromBrand}
                        toBrand={option.toBrand}
                        pointsExchange={option.pointsExchange}
                        currentBalanceFrom={option.currentBalanceFrom}
                        currentBalanceTo={option.currentBalanceTo}
                        onClick={() => {
                            setSelectedOption(index);
                        }}
                    />
                ))}
                <PrimaryButton
                    text={Translator.trans(/** @Desc("Transfer points") */ 'transfer-points')}
                    className={{ button: classes.exchangeButton }}
                    onClick={onClick}
                />
            </div>
        </div>
    );
}
