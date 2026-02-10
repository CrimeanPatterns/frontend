import { ExchangeInfo } from './ExchangeInfo';
import { Icon } from '@UI/Icon';
import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { getCostWithCommas } from '../Utilities';
import React from 'react';
import classNames from 'classnames';
import classes from './ExchangeOption.module.scss';

type ExchangeOptionProps = {
    fromBrand: ProviderBrand;
    toBrand: ProviderBrand;
    pointsExchange: number;
    currentBalanceFrom: number;
    currentBalanceTo: number;
    selected: boolean;
    onClick: () => void;
};

export function ExchangeOption({
    fromBrand,
    toBrand,
    pointsExchange,
    currentBalanceFrom,
    currentBalanceTo,
    selected,
    onClick,
}: ExchangeOptionProps) {
    return (
        <div className={classes.exchangeContainer} onClick={onClick}>
            <div
                className={classNames(classes.option, {
                    [classes.optionActive as string]: selected,
                })}
            ></div>
            <div className={classes.exchangePointsContainer}>
                <ExchangeInfo
                    brand={fromBrand}
                    pointsExchange={`-${getCostWithCommas(pointsExchange)}`}
                    currentBalance={getCostWithCommas(currentBalanceFrom)}
                    futureBalance={getCostWithCommas(currentBalanceFrom - pointsExchange)}
                />
                <Icon type="ArrowRightWithPoints" />
                <ExchangeInfo
                    brand={toBrand}
                    pointsExchange={`+${getCostWithCommas(pointsExchange)}`}
                    currentBalance={getCostWithCommas(currentBalanceTo)}
                    futureBalance={getCostWithCommas(currentBalanceTo + pointsExchange)}
                />
            </div>
        </div>
    );
}
