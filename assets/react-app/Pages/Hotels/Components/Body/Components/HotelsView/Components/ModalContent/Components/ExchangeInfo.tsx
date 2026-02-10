import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { Translator } from '@Services/Translator';
import { getHotelProviderLogo } from '@Root/Pages/Hotels/Utilities';
import React from 'react';
import classes from './ExchangeInfo.module.scss';

type ExchangeInfoProps = {
    brand: ProviderBrand;
    pointsExchange: string;
    currentBalance: string;
    futureBalance: string;
};

export function ExchangeInfo({ brand, pointsExchange, currentBalance, futureBalance }: ExchangeInfoProps) {
    const Logo = getHotelProviderLogo(brand, classes.logoExchangingSvg);
    return (
        <div className={classes.exchangeInfo}>
            <div className={classes.logoExchanging}>{Logo}</div>
            <div>
                <div className={classes.countPointsExchanging}>{pointsExchange}</div>
                <div className={classes.currentBalance}>
                    {`${Translator.trans(/** @Desc("Current balance") */ 'current-balance')}: ${currentBalance}`}
                </div>
                <div className={classes.newBalance}>
                    {`${Translator.trans('award.account.form.changed.balance')}: ${futureBalance}`}
                </div>
            </div>
        </div>
    );
}
