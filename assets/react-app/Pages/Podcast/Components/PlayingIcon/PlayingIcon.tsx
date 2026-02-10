import React from 'react';
import classNames from 'classnames';
import classes from './PlayingIcon.module.scss';

type PlayingIconProps = {
    size?: 'small' | 'middle';
};

export function PlayingIcon({ size = 'middle' }: PlayingIconProps) {
    const sizeClass = classNames({
        [classes.playingIconMiddle as string]: size === 'middle',
        [classes.playingIconSmall as string]: size === 'small',
    });
    return (
        <div className={classNames(classes.playingIcon, sizeClass)}>
            <span className={classes.playingIconBar} />
            <span className={classes.playingIconBar} />
            <span className={classes.playingIconBar} />
        </div>
    );
}
