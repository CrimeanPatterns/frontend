import React, { memo } from 'react';
import classNames from 'classnames';
import classes from './Cross.module.scss';

interface CrossProps {
    onClick?: () => void;
    className?: string;
}

export const Cross = memo(({ onClick, className }: CrossProps) => {
    return (
        <button
            aria-label="close-button"
            type="button"
            className={classNames(classes.cross, className)}
            onClick={onClick}
        ></button>
    );
});

Cross.displayName = 'Cross';
