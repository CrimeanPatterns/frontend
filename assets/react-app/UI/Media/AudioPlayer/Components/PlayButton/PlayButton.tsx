import { Icon } from '@UI/Icon';
import { Loader } from '@UI/Icon/Loader';
import React, { useMemo } from 'react';
import classNames from 'classnames';
import classes from './PlayButton.module.scss';

type PlayButtonProps = {
    variant: 'primary' | 'secondary';
    onClick: () => void;
    isPlaying: boolean;
    isLoading: boolean;
};

export function PlayButton({ variant, onClick, isPlaying, isLoading }: PlayButtonProps) {
    const buttonClasses = useMemo(
        () =>
            classNames(classes.playButton, {
                [classes.playButtonPrimary as string]: variant === 'primary',
                [classes.playButtonSecondary as string]: variant === 'secondary',
            }),
        [variant],
    );
    return (
        <button className={buttonClasses} onClick={onClick} disabled={isLoading}>
            {isLoading && <Loader size="small" color={variant === 'primary' ? 'primary' : 'active'} />}
            {!isLoading && (
                <Icon
                    className={classNames(classes.playButtonIcon, {
                        [classes.playButtonIconPlay as string]: !isPlaying,
                    })}
                    type={isPlaying ? 'Pause' : 'Play'}
                    size="small"
                />
            )}
        </button>
    );
}
