import React, { memo, useMemo } from 'react';
import classNames from 'classnames';
import classes from './Skeleton.module.scss';

type SkeletonProps = {
    width?: number | `${number}%`;
    height?: number | `${number}%`;
    className?: string;
} & ({ rounded: true; circle?: false } | { rounded?: false; circle: true } | { rounded?: false; circle?: false });

export const Skeleton = memo(({ rounded, circle, className: externalClass, width, height }: SkeletonProps) => {
    const skeletonClasses = useMemo(
        () =>
            classNames(
                classes.skeleton,
                {
                    [classes.skeletonRounded as string]: rounded,
                    [classes.skeletonCircle as string]: circle,
                },
                externalClass,
            ),
        [rounded, externalClass, circle],
    );
    return <div className={skeletonClasses} style={{ width, height }}></div>;
});

Skeleton.displayName = 'Skeleton';
