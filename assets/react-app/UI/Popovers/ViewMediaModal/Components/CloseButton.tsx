import React from 'react';
import classes from './CloseButton.module.scss';

interface CloseButtonProps {
    onClick?: () => void;
}

export function CloseButton({ onClick }: CloseButtonProps) {
    return (
        <button aria-label="close-button" type="button" className={classes.button} onClick={onClick}>
            <div className={classes.cross} />
        </button>
    );
}
