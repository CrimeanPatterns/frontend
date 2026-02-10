/* eslint-disable @typescript-eslint/no-explicit-any */
import React, { useMemo, useRef, useState } from 'react';

import { CircularProgress } from '@UI/Feedback/CircleProgress/CircleProgress';
import { Cross } from '@UI/Icon/Cross/Cross';
import { IconButton } from '@UI/Buttons';
import { Popover } from '@UI/Popovers/Popover';
import { Segment, Speedometer } from '@UI/Misc/Speedometer/Speedometer';
import { Translator } from '@Services/Translator';
import classNames from 'classnames';
import classes from './FicoAccount.module.scss';

export enum FicoRangeName {
    Poor = 'Poor',
    Fair = 'Fair',
    Good = 'Good',
    VeryGood = 'Very Good',
    Excellent = 'Excellent',
}
interface FicoRange {
    min: number;
    max: number;
    name: FicoRangeName;
}
export interface FicoAccountProps {
    isChangePositive: boolean;
    balance: string;
    balanceChangeNumber: string;
    name: string;
    lastUpdatedDate: string;
    accountId: string;
    subAccountId?: string;
    isUpdateAvailable: boolean;
    account: any;
    onUpdate?: () => void;
    isUpdating?: boolean;
    ficoRanges: FicoRange[];
}

export function FicoAccount({
    balance,
    isChangePositive,
    balanceChangeNumber,
    name,
    lastUpdatedDate,
    isUpdateAvailable,
    onUpdate,
    isUpdating,
    ficoRanges: initialFicoRanges,
}: FicoAccountProps) {
    const [isPopoverOpen, setIsPopoverOpen] = useState(false);
    const popoverAnchorRef = useRef<HTMLDivElement>(null);
    const numericBalance = useMemo(() => parseInt(balance), [balance]);
    const numericBalanceChange = useMemo(() => parseInt(balanceChangeNumber), [balanceChangeNumber]);

    const excellentMinBalance = useRef(0);
    const goodMinBalance = useRef(0);
    const fairMinBalance = useRef(0);

    const ficoRanges = useMemo(() => {
        const segmentPercentage = 100 / initialFicoRanges.length;

        return initialFicoRanges.map((ficoRange, index) => {
            if (ficoRange.name === FicoRangeName.Excellent) {
                excellentMinBalance.current = ficoRange.min;
            }
            if (ficoRange.name === FicoRangeName.Good) {
                goodMinBalance.current = ficoRange.min;
            }
            if (ficoRange.name === FicoRangeName.Fair) {
                fairMinBalance.current = ficoRange.min;
            }
            return {
                ...ficoRange,
                minPercentage: segmentPercentage * index,
                maxPercentage: segmentPercentage * (index + 1),
            };
        });
    }, [initialFicoRanges]);

    const speedometerSegments: Segment[] = useMemo(() => {
        return ficoRanges.map((ficoRange) => ({
            min: ficoRange.min,
            max: ficoRange.max,
            className: getRangeClassName(ficoRange.name),
            label: `${ficoRange.min}-${ficoRange.max}`,
            description: getRangeDescription(ficoRange.name),
            labelDistance: getRangeLabelDistance(ficoRange.name),
        }));
    }, [ficoRanges]);

    const calculateFicoPercentage = useMemo(() => {
        if (isNaN(numericBalance)) {
            return 0;
        }

        const currentSegment = ficoRanges.find((range) => numericBalance >= range.min && numericBalance <= range.max);

        if (!currentSegment) {
            const firstSegment = ficoRanges[0];
            const lastSegment = ficoRanges[ficoRanges.length - 1];
            if (firstSegment && numericBalance < firstSegment.min) {
                return 0;
            }
            if (lastSegment && numericBalance > lastSegment.max) {
                return 100;
            }
            return 0;
        }

        const segmentRange = currentSegment.max - currentSegment.min;
        const scoreProgress = numericBalance - currentSegment.min;
        const segmentPercentage = Math.round((scoreProgress / segmentRange) * 100);

        const segmentPercentageRange = currentSegment.maxPercentage - currentSegment.minPercentage;

        return Math.round((segmentPercentageRange / 100) * segmentPercentage) + currentSegment.minPercentage;
    }, [balance, ficoRanges, numericBalance]);

    const circleProgressGradient = useMemo(() => {
        if (isNaN(numericBalance)) {
            return 'bad';
        }

        if (numericBalance >= excellentMinBalance.current) {
            return 'excellent';
        } else if (numericBalance >= goodMinBalance.current) {
            return 'good';
        } else if (numericBalance >= fairMinBalance.current) {
            return 'fair';
        } else {
            return 'bad';
        }
    }, [numericBalance, ficoRanges]);

    const handleMouseClick = () => {
        setIsPopoverOpen(true);
    };

    const handlePopoverClose = () => {
        setIsPopoverOpen(false);
    };

    return (
        <>
            <div ref={popoverAnchorRef} className={classes.ficoAccount} onClick={handleMouseClick}>
                <CircularProgress
                    radius={25}
                    percent={calculateFicoPercentage}
                    gradientType={circleProgressGradient}
                    classes={{
                        trackCircle: classes.circleProgressTrack,
                    }}
                >
                    <div className={classes.balanceContainer}>
                        <p className={classes.balance}>{balance}</p>
                        {!isNaN(numericBalanceChange) && numericBalanceChange !== 0 && (
                            <div className={classes.balanceChangeCountContainer}>
                                <p
                                    className={classNames(classes.balanceChangeCount, {
                                        [classes.balanceChangeCountPositive as string]: isChangePositive,
                                        [classes.balanceChangeCountNegative as string]: !isChangePositive,
                                    })}
                                >
                                    {`${isChangePositive ? '+' : '-'}${balanceChangeNumber}`}
                                </p>
                                <svg
                                    width="11"
                                    height="11"
                                    viewBox="0 0 11 11"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    className={classNames(classes.balanceIcon, {
                                        [classes.balanceIconVisible as string]: isChangePositive,
                                    })}
                                >
                                    <path
                                        d="M0.234375 0.591026H10.2344V10.591H0.234375V0.591026ZM8.41773 5.77439L5.23438 2.59103L2.05102 5.77439L2.93438 6.65774L4.48438 5.10775V8.5911H5.98438V5.10775L7.53438 6.65774L8.41773 5.77439Z"
                                        fill="#27A887"
                                    />
                                </svg>

                                <svg
                                    width="10"
                                    height="11"
                                    viewBox="0 0 10 11"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    className={classNames(classes.balanceIcon, {
                                        [classes.balanceIconVisible as string]: !isChangePositive,
                                    })}
                                >
                                    <path
                                        d="M0 10.091H10V0.0910263H0V10.091ZM8.18336 4.90767L5 8.09103L1.81664 4.90767L2.7 4.02431L4.25 5.57431V2.09095H5.75V5.57431L7.3 4.02431L8.18336 4.90767Z"
                                        fill="#F34141"
                                    />
                                </svg>
                            </div>
                        )}
                    </div>
                </CircularProgress>
                <div className={classes.titleContainer}>
                    <h6 className={classes.title}>{name}</h6>
                    <p className={classes.titleDescription}>
                        {Translator.trans('as-of-date', {
                            date: lastUpdatedDate,
                        })}
                    </p>
                </div>
                {isUpdateAvailable && (
                    <IconButton
                        iconType="Update"
                        className={{ button: classes.updateButton, icon: classes.updateButtonIcon }}
                        iconSize={16}
                        onClick={onUpdate}
                        disabled={isUpdating}
                        loading={isUpdating}
                    />
                )}
            </div>
            <Popover
                open={isPopoverOpen}
                anchor={popoverAnchorRef}
                offsetFromAnchorInPx={25}
                onClose={handlePopoverClose}
                closeTrigger="click"
                showShadow
                lockGlobalScroll
            >
                <div className={classes.popoverContent}>
                    <Cross onClick={handlePopoverClose} className={classes.popoverCross} />
                    <div className={classes.speedometerContainer}>
                        <Speedometer
                            currentValue={Number(balance)}
                            segmentThickness={10}
                            height={150}
                            segments={speedometerSegments}
                        />
                    </div>
                    <div className={classes.popoverInfo}>
                        <div className={classes.popoverBalanceContainer}>
                            <p className={classes.popoverBalance}>{balance}</p>
                            {!isNaN(numericBalanceChange) && numericBalanceChange !== 0 && (
                                <div className={classes.balanceChangeCountContainer}>
                                    <p
                                        className={classNames(classes.popoverBalanceChangeCount, {
                                            [classes.balanceChangeCountPositive as string]: isChangePositive,
                                            [classes.balanceChangeCountNegative as string]: !isChangePositive,
                                        })}
                                    >
                                        {`${isChangePositive ? '+' : '-'}${balanceChangeNumber}`}
                                    </p>
                                    <svg
                                        width="11"
                                        height="11"
                                        viewBox="0 0 11 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        className={classNames(classes.balanceIcon, {
                                            [classes.balanceIconVisible as string]: isChangePositive,
                                        })}
                                    >
                                        <path
                                            d="M0.234375 0.591026H10.2344V10.591H0.234375V0.591026ZM8.41773 5.77439L5.23438 2.59103L2.05102 5.77439L2.93438 6.65774L4.48438 5.10775V8.5911H5.98438V5.10775L7.53438 6.65774L8.41773 5.77439Z"
                                            fill="#27A887"
                                        />
                                    </svg>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        className={classNames(classes.balanceIcon, {
                                            [classes.balanceIconVisible as string]: !isChangePositive,
                                        })}
                                    >
                                        <path
                                            d="M0 10.091H10V0.0910263H0V10.091ZM8.18336 4.90767L5 8.09103L1.81664 4.90767L2.7 4.02431L4.25 5.57431V2.09095H5.75V5.57431L7.3 4.02431L8.18336 4.90767Z"
                                            fill="#F34141"
                                        />
                                    </svg>
                                </div>
                            )}
                        </div>
                        <p className={classes.title}>{name}</p>
                        <p className={classes.titleDescription}>
                            {Translator.trans('as-of-date', {
                                date: lastUpdatedDate,
                            })}
                        </p>
                    </div>
                </div>
            </Popover>
        </>
    );
}

function getRangeClassName(rangeName: FicoRangeName) {
    switch (rangeName) {
        case FicoRangeName.Poor:
            return classes.speedometerSegmentsBad;
        case FicoRangeName.Fair:
            return classes.speedometerSegmentsFair;
        case FicoRangeName.Good:
            return classes.speedometerSegmentsGood;
        case FicoRangeName.VeryGood:
            return classes.speedometerSegmentsVeryGood;
        case FicoRangeName.Excellent:
            return classes.speedometerSegmentsExcellent;

        default:
            return undefined;
    }
}

function getRangeDescription(rangeName: FicoRangeName) {
    switch (rangeName) {
        case FicoRangeName.Poor:
            return Translator.trans('bad');
        case FicoRangeName.Fair:
            return Translator.trans('fair');
        case FicoRangeName.Good:
            return Translator.trans('good');
        case FicoRangeName.VeryGood:
            return Translator.trans(/** @Desc("Very Good") */ 'very-good');
        case FicoRangeName.Excellent:
            return Translator.trans('excellent');

        default:
            return null;
    }
}

function getRangeLabelDistance(rangeName: FicoRangeName) {
    switch (rangeName) {
        case FicoRangeName.Poor:
            return 30;
        case FicoRangeName.Fair:
            return 20;
        case FicoRangeName.Good:
            return 14;
        case FicoRangeName.VeryGood:
            return 30;
        case FicoRangeName.Excellent:
            return 40;
        default:
            return undefined;
    }
}
