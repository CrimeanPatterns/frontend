import 'rc-slider/assets/index.css';
import { SliderInput as Input } from './Components/SliderInput';
import { Slider, SliderProps } from './Components/Slider';
import { SliderRef } from 'rc-slider';
import React, { Fragment, forwardRef, memo, useCallback } from 'react';
import classNames from 'classnames';
import classes from './SliderInput.module.scss';

type SliderInputClasses = {
    container?: string;
};

type SliderInputProps = SliderProps & {
    classNames?: SliderInputClasses;
};

const SliderInputBase = forwardRef<SliderRef, SliderInputProps>(
    ({ value, range, min = 0, max = 100, step = 1, onChange, classNames: externalClasses }, ref) => {
        const onRangeInputChange = useCallback((newValue: number, index: number) => {
            if (Array.isArray(value)) {
                const newArrayValue = [...value];
                newArrayValue[index] = newValue;
                newArrayValue.sort((a, b) => a - b);
                // @ts-expect-error TS can't define value's type
                onChange?.(newArrayValue);
            }
        }, []);

        return (
            <div className={classNames(classes.sliderInputContainer, externalClasses?.container)}>
                {/* @ts-expect-error TS can't define value's type */}
                <Slider range={range} min={min} max={max} step={step} value={value} onChange={onChange} ref={ref} />
                <div className={classes.sliderInputInputContainer}>
                    {typeof value === 'number' && (
                        // @ts-expect-error TS can't define value's type
                        <Input value={value} onChange={onChange} step={step} min={min} max={max} />
                    )}
                    {Array.isArray(value) &&
                        value.map((indexValue, index) => {
                            return (
                                <Fragment key={index}>
                                    <Input
                                        value={indexValue}
                                        onChange={(newValue) => {
                                            onRangeInputChange(newValue, index);
                                        }}
                                        step={step}
                                        min={min}
                                        max={max}
                                    />
                                    {index !== value.length - 1 && (
                                        <div className={classes.sliderInputInputSeparator}></div>
                                    )}
                                </Fragment>
                            );
                        })}
                </div>
            </div>
        );
    },
);

SliderInputBase.displayName = 'SliderInputBase';

export const SliderInput = memo(SliderInputBase);
