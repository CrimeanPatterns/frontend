import React, { PropsWithChildren } from 'react';
import classNames from 'classnames';
import classes from './CircleProgress.module.scss';

type CircularProgressClasses = {
    container?: string;
    progressCircle?: string;
    trackCircle?: string;
};

type GradientType = 'excellent' | 'good' | 'fair' | 'bad';

type CircularProgressProps = {
    radius?: number;
    circleWidth?: number;
    gradientType?: GradientType;
    percent: number;
    classes?: CircularProgressClasses;
} & PropsWithChildren;

export const CircularProgress: React.FC<CircularProgressProps> = ({
    radius = 30,
    circleWidth = 3,
    gradientType,
    classes: externalClasses,
    percent,
    children,
}) => {
    const size = (radius + circleWidth) * 2;

    const clampedProgress = Math.max(0, Math.min(percent, 100));

    const progressDegrees = (clampedProgress / 100) * 360;

    const cssVars = {
        '--circular-progress-size': `${size}px`,
        '--circular-progress-radius': `${radius}px`,
        '--circular-progress-stroke-width': `${circleWidth}px`,
        '--circular-progress-degrees': `${progressDegrees}deg`,
        '--circular-progress-outer-radius': `${radius + circleWidth}px`,
    } as React.CSSProperties;

    return (
        <div
            className={classNames(classes.circularProgress, externalClasses?.container)}
            role="progressbar"
            aria-valuenow={percent}
            aria-valuemin={0}
            aria-valuemax={100}
            style={cssVars}
        >
            <div className={classNames(classes.circularProgressTrackCircle, externalClasses?.trackCircle)} />

            <div
                className={classNames(
                    classes.circularProgressCircle,
                    {
                        [classes.circularProgressCircleGradientExcellent as string]: gradientType === 'excellent',
                        [classes.circularProgressCircleGradientGood as string]: gradientType === 'good',
                        [classes.circularProgressCircleGradientFair as string]: gradientType === 'fair',
                        [classes.circularProgressCircleGradientBad as string]: gradientType === 'bad',
                    },
                    externalClasses?.progressCircle,
                )}
            />

            {children && <div className={classes.circularProgressContentContainer}>{children}</div>}
        </div>
    );
};
