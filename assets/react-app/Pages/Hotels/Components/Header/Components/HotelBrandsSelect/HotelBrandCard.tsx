import React, { ReactElement } from 'react';
import classNames from 'classnames';
import classes from './HotelBrandCard.module.scss';

export interface HotelBrandCardProps {
    brand: string;
    points: number;
    logo: () => ReactElement;
    active: boolean;
    onClick: (state: boolean) => void;
}
export function HotelBrandCard({ brand, points, logo, active, onClick }: HotelBrandCardProps) {
    const onClickHandler = () => {
        onClick(!active);
    };
    return (
        <div className={classes.container} onClick={onClickHandler}>
            <div className={classNames(classes.logoContainer, active ? classes.logoContainerActive : '')}>{logo()}</div>
            <span className={classes.descriptionContainer}>
                <h4 className={classes.cardTitle}>{brand}</h4>
                <span className={classes.points}>{points}</span>
            </span>
        </div>
    );
}
