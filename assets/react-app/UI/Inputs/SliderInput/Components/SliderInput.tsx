import React, { ChangeEvent, KeyboardEvent, useCallback, useEffect, useState } from 'react';
import classes from './SliderInput.module.scss';

type SliderInputProps = {
    value: number;
    onChange?: (newValue: number) => void;
    step: number;
    min?: number;
    max?: number;
};

export function SliderInput({ value, onChange, step, min, max }: SliderInputProps) {
    const [inputValue, setInputValue] = useState(String(value));

    const onChangeHandler = useCallback((event: ChangeEvent<HTMLInputElement>) => {
        if (!isValidNumberString(event.target.value)) return;

        let newValue = event.target.value;

        if (newValue.length !== 0 && !Number.isNaN(Number(newValue)) && !newValue.endsWith('.')) {
            const decimalPlaces = defineDecimalPlaces(step);
            newValue = (
                Math.floor(Number(newValue) * Math.pow(10, decimalPlaces)) / Math.pow(10, decimalPlaces)
            ).toString();
        }

        setInputValue(newValue);
    }, []);

    const onKeyDownHandler = useCallback(
        (event: KeyboardEvent<HTMLInputElement>) => {
            let newValue = value;

            if (event.key === 'ArrowUp') {
                newValue = roundToDecimalPlaces(newValue + step, defineDecimalPlaces(step));
                onChange?.(newValue);
                return;
            }

            if (event.key === 'ArrowDown') {
                newValue = roundToDecimalPlaces(newValue - step, defineDecimalPlaces(step));
                onChange?.(newValue);
                return;
            }
        },
        [value, onChange],
    );

    const onBlurHandler = useCallback(() => {
        if (inputValue.length !== 0 && !Number.isNaN(Number(inputValue))) {
            let sanitizedValue = Number(inputValue);

            if (min !== undefined && min > sanitizedValue) {
                sanitizedValue = min;
            }

            if (max !== undefined && max < sanitizedValue) {
                sanitizedValue = max;
            }
            setInputValue(String(sanitizedValue));
            onChange?.(sanitizedValue);
        }
    }, [inputValue, onChange, min, max]);

    useEffect(() => {
        if (String(value) !== inputValue) {
            setInputValue(String(value));
        }
    }, [value]);
    return (
        <input
            type="text"
            value={inputValue}
            className={classes.sliderInput}
            onChange={onChangeHandler}
            onKeyDown={onKeyDownHandler}
            onBlur={onBlurHandler}
        ></input>
    );
}

function defineDecimalPlaces(value: number) {
    return value.toString().split('.')[1]?.length || 0;
}

function roundToDecimalPlaces(value: number, decimalPlaces: number): number {
    return Math.round(value * Math.pow(10, decimalPlaces)) / Math.pow(10, decimalPlaces);
}

function isValidNumberString(value: string) {
    const regex = /^-?(\d+\.\d*|\d*)$/;
    return regex.test(value);
}
