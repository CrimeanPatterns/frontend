import React, { ReactNode, memo } from 'react';
import classNames from 'classnames';
import classes from './Switcher.module.scss';

type SwitcherClasses = {
    label?: string;
    text?: string;
};
interface SwitcherProps {
    active: boolean;
    onChange?: (switcherState: boolean) => void;
    labelText?: string;
    customRightLabelComponent?: ReactNode;
    classNames?: SwitcherClasses;
}

export const Switcher = memo(
    ({
        active,
        onChange,
        labelText,
        customRightLabelComponent: RightLabelComponent,
        classNames: externalClasses,
    }: SwitcherProps) => {
        const onChangeHandler = () => {
            onChange?.(!active);
        };

        return (
            <label className={classNames(classes.label, externalClasses?.label)} onChange={onChangeHandler}>
                {labelText && <span className={externalClasses?.text}>{labelText}</span>}
                <div className={classes.switcher}>
                    <input type="checkbox" checked={active} readOnly className={classes.inputCheckbox} />
                    <span className={classes.slider} />
                </div>
                {RightLabelComponent}
            </label>
        );
    },
);

Switcher.displayName = 'Switcher';
