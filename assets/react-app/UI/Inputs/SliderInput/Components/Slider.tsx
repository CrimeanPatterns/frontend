import { SliderHandle } from './SliderHandle';
import React, { forwardRef, useCallback, useEffect, useMemo } from 'react';
import SliderRC, { SliderRef } from 'rc-slider';
import classes from './Slider.module.scss';

type Range = {
    value: number[];
    onChange?: (value: number[]) => void;
    range: true;
};

type Slider = {
    value: number;
    onChange?: (value: number) => void;
    range?: false;
};

export type SliderProps = (Range | Slider) & {
    min?: number;
    max?: number;
    step?: number;
};

export const Slider = forwardRef<SliderRef, SliderProps>(({ range, min, max, step, value, onChange }, ref) => {
    const tabIndex = useMemo(() => {
        if (range) {
            return new Array<number>(value.length).fill(0);
        }
        return 0;
    }, [range]);

    const sliderClasses = useMemo(
        () => ({
            rail: classes.sliderRail,
            track: classes.sliderTrack,
            handle: classes.sliderHandle,
        }),
        [],
    );

    const onChangeHandler = useCallback(
        (value: number[] | number) => {
            // @ts-expect-error The same handler for both types
            onChange?.(value);
        },
        [onChange, range],
    );

    useEffect(() => {
        onChangeHandler(limitValueToBounds(value, min, max));
    }, [min, max]);
    return (
        <SliderRC
            range={range}
            min={min}
            max={max}
            step={step}
            value={value}
            onChange={onChangeHandler}
            tabIndex={tabIndex}
            ref={ref}
            classNames={sliderClasses}
            handleRender={SliderHandle}
        />
    );
});

Slider.displayName = 'Slider';

function limitValueToBounds(value: number | number[], min?: number, max?: number) {
    if (Array.isArray(value)) {
        let newValue = value;

        if (min) {
            newValue = newValue.map((rangeValue) => Math.max(rangeValue, min));
        }

        if (max) {
            newValue = newValue.map((rangeValue) => Math.min(rangeValue, max));
        }

        return newValue;
    }

    let newValue = value;

    if (min) {
        newValue = Math.max(newValue, min);
    }

    if (max) {
        newValue = Math.min(newValue, max);
    }

    return newValue;
}
