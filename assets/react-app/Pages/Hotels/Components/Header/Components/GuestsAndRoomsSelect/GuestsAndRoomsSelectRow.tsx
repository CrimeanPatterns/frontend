import { IconButton } from '@UI/Buttons/IconButton';
import React, { memo, useEffect, useState } from 'react';
import classes from './GuestsAndRoomsSelectRow.module.scss';

interface GuestsAndRoomsRowSelectProps {
    label: string;
    value: number;
    onValueChange: (newValue: number) => void;
    minValue?: number;
    maxValue?: number;
}

export const GuestsAndRoomsSelectRow = memo(
    ({ label, value, onValueChange, minValue, maxValue }: GuestsAndRoomsRowSelectProps) => {
        const [isIncreaseButtonDisabled, setIsIncreaseButtonDisabled] = useState(
            maxValue !== undefined ? maxValue <= value : false,
        );
        const [isDecreaseButtonDisabled, setIsDecreaseButtonDisabled] = useState(
            minValue !== undefined ? minValue >= value : false,
        );

        const onIncreaseValueHandler = () => {
            if (maxValue !== undefined && value >= maxValue) return;

            const newValue = value + 1;

            onValueChange(newValue);
        };

        const onDecreaseValueHandler = () => {
            if (minValue !== undefined && value <= minValue) return;

            const newValue = value - 1;

            onValueChange(newValue);
        };

        useEffect(() => {
            if (maxValue !== undefined && maxValue <= value) {
                setIsIncreaseButtonDisabled(true);
            } else {
                setIsIncreaseButtonDisabled(false);
            }

            if (minValue !== undefined && minValue >= value) {
                setIsDecreaseButtonDisabled(true);
            } else {
                setIsDecreaseButtonDisabled(false);
            }
        }, [maxValue, value, minValue]);

        return (
            <div className={classes.container}>
                <span className={classes.label}>{label}</span>
                <div className={classes.buttonsContainer}>
                    <IconButton
                        iconType="Minus"
                        onClick={onDecreaseValueHandler}
                        className={{ button: classes.decreaseButton }}
                        disabled={isDecreaseButtonDisabled}
                        iconSize="small"
                    />
                    <span className={classes.counter}>{value}</span>
                    <IconButton
                        iconType="Plus"
                        onClick={onIncreaseValueHandler}
                        className={{ button: classes.increaseButton }}
                        disabled={isIncreaseButtonDisabled}
                        iconSize="small"
                    />
                </div>
            </div>
        );
    },
);
GuestsAndRoomsSelectRow.displayName = 'GuestsAndRoomsSelectRow';
