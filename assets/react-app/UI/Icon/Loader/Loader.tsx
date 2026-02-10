import { IconColor, IconSize } from '..';
import { Theme } from '@UI/Theme';
import { getColor } from '../Utilities';
import { useTheme } from 'react-jss';
import React, { memo } from 'react';
import classNames from 'classnames';
import classes from './Loader.module.scss';
import iconClasses from '../Icon.module.scss';

const thickness = 2;
const SIZE = 44;

type LoaderClasses = {
    backgroundCircle?: string;
    circle?: string;
};
interface LoaderProps {
    size?: IconSize | number;
    color?: IconColor;
    classes?: LoaderClasses;
}

export const Loader = memo(({ size = 'medium', color = 'secondary', classes: externalClasses }: LoaderProps) => {
    const theme: Theme = useTheme();

    const loaderSize = classNames({
        [iconClasses.smallSize as string]: size === 'small',
        [iconClasses.mediumSize as string]: size === 'medium',
        [iconClasses.bigSize as string]: size === 'big',
    });

    const loaderColor = getColor(color, theme);
    return (
        <div
            className={classNames(classes.container, loaderSize)}
            style={{
                width: typeof size === 'number' ? size : undefined,
                height: typeof size === 'number' ? size : undefined,
            }}
            role="progressbar"
        >
            <svg className={classes.svg} viewBox={`${SIZE / 2} ${SIZE / 2} ${SIZE} ${SIZE}`}>
                <circle
                    className={classNames(classes.backgroundCircle, externalClasses?.backgroundCircle)}
                    cx={SIZE}
                    cy={SIZE}
                    r={(SIZE - thickness) / 2}
                    fill="none"
                    strokeWidth={thickness}
                />
                <circle
                    className={classNames(classes.circle, externalClasses?.circle)}
                    cx={SIZE}
                    cy={SIZE}
                    r={(SIZE - thickness) / 2}
                    fill="none"
                    strokeWidth={thickness}
                    stroke={loaderColor}
                />
            </svg>
        </div>
    );
});

Loader.displayName = 'Loader';
