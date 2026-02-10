import { IconColor, IconSize, IconType, Icons } from '.';
import React, { memo } from 'react';
import classNames from 'classnames';
import classes from './Icon.module.scss';

interface IconProps {
    type: IconType;
    size?: IconSize | number;
    color?: IconColor;
    className?: string;
}

export const Icon = memo(({ type, size = 'medium', color = 'secondary', className }: IconProps) => {
    const iconSize = classNames({
        [classes.smallSize as string]: size === 'small',
        [classes.mediumSize as string]: size === 'medium',
        [classes.bigSize as string]: size === 'big',
    });

    const iconColor = classNames({
        [classes.primary as string]: color === 'primary',
        [classes.secondary as string]: color === 'secondary',
        [classes.active as string]: color === 'active',
        [classes.disabled as string]: color === 'disabled',
        [classes.warning as string]: color === 'warning',
    });

    const Component = Icons[type];

    return (
        <Component
            style={{
                width: typeof size === 'number' ? size : undefined,
                height: typeof size === 'number' ? size : undefined,
            }}
            className={classNames(classes.icon, iconColor, iconSize, className)}
        />
    );
});

Icon.displayName = 'Icon';
