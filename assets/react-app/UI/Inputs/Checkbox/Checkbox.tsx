import React, { ReactNode, memo } from 'react';
import classes from './Checkbox.module.scss';

type CheckboxProps = {
    checked: boolean;
    onChange?: (newState: boolean) => void;
    label?: string | ReactNode;
};

export const Checkbox = memo(({ checked, onChange, label }: CheckboxProps) => {
    const handleChange = () => {
        onChange?.(!checked);
    };

    return (
        <label className={classes.checkboxLabel}>
            <input type="checkbox" checked={checked} onChange={handleChange} className={classes.checkboxInput} />
            <span className={classes.checkboxCustom}></span>
            {label && <span className={classes.checkboxText}>{label}</span>}
        </label>
    );
});

Checkbox.displayName = 'Checkbox';
