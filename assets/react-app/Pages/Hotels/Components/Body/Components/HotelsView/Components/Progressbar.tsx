import { createUseStyles } from 'react-jss';
import { useHotels } from '../../../../../Contexts/HotelContext/HotelContext';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './Progressbar.module.scss';

interface CreateProgressbarClasses {
    loadingPercentage: number | null;
}
const createClasses = createUseStyles(() => ({
    progressbar: ({ loadingPercentage }: CreateProgressbarClasses) => {
        return {
            '&::after': {
                transform: `translateX(-${loadingPercentage ? 100 - loadingPercentage : 100}%)`,
            },
        };
    },
}));

export function Progressbar() {
    const { totalLoadingSteps, finishedLoadingSteps, isLoading, onLoadingFinish } = useHotels();

    const [loadingPercentage, setLoadingPercentage] = useState(0);
    const interval = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    const jssClasses = createClasses({ loadingPercentage });

    const updateProgressBar = useCallback(() => {
        clearInterval(interval.current);
        const nextStepBoundary = Math.min((100 / totalLoadingSteps) * (finishedLoadingSteps + 1), 100);

        interval.current = setInterval(() => {
            setLoadingPercentage((currentProgress) => {
                const sectorProgress =
                    (currentProgress -
                        Math.floor(currentProgress / (100 / totalLoadingSteps)) * (100 / totalLoadingSteps)) /
                    (100 / totalLoadingSteps);

                const maxSpeed = 0.2;
                const minSpeed = 0.005;
                const acceleration = 0.03;

                const speed = maxSpeed - sectorProgress * (maxSpeed - minSpeed) - acceleration * sectorProgress;

                if (currentProgress + speed < nextStepBoundary) {
                    return currentProgress + speed;
                } else {
                    return nextStepBoundary - 0.01;
                }
            });
        }, 100);
    }, [totalLoadingSteps, finishedLoadingSteps]);

    useEffect(() => {
        if (totalLoadingSteps > 0) {
            const oneStepPercentage = 100 / totalLoadingSteps;

            setLoadingPercentage(Math.max(oneStepPercentage * finishedLoadingSteps, loadingPercentage));
            updateProgressBar();
        }
    }, [totalLoadingSteps, finishedLoadingSteps]);

    useEffect(() => {
        if (loadingPercentage >= 100) {
            onLoadingFinish();
            clearInterval(interval.current);
        }
    }, [loadingPercentage]);

    if (!isLoading) return null;
    return (
        <div className={classes.container}>
            <div className={classes.progressbarContainer}>
                <div className={classNames(classes.progressbar, jssClasses.progressbar)}></div>
            </div>
        </div>
    );
}
