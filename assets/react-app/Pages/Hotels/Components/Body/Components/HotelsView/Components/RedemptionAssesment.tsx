import { Popover, PositionFromAnchor } from '@UI/Popovers';
import { Translator } from '@Services/Translator';
import React, { RefObject, useEffect, useState } from 'react';
import classes from './RedemptionAssessmeent.module.scss';

type RedemptionValueProps = {
    isPopoverOpen: boolean;
    anchor: RefObject<HTMLElement>;
};

export const RedemptionAssessment = ({ isPopoverOpen, anchor }: RedemptionValueProps) => {
    return (
        <Popover open={isPopoverOpen} anchor={anchor} positionFromAnchor={PositionFromAnchor.Above}>
            <div>
                <RedemptionValue value={4} />
            </div>
        </Popover>
    );
};

function RedemptionValue({ value }: { value: number }) {
    const [rotation, setRotation] = useState(-90);

    useEffect(() => {
        //1-bad 2-not good 3-good 4-excellent
        const angle = (value / 4) * 180 - 125; // Convert value to degrees
        setRotation(angle);
    }, [value]);

    return (
        <div style={{ padding: 27 }}>
            <div style={{ position: 'relative', paddingBottom: 50 }}>
                <div style={{ display: 'flex', height: 106 }}>
                    <div style={{ alignSelf: 'flex-end', width: 90, textAlign: 'center' }}>
                        <div style={{ color: '#FF3434' }} className={classes.redemptionValue}>
                            0.1-1.5¢
                        </div>
                        <span style={{ color: '#FF3434' }} className={classes.redemptionAssessment}>
                            {Translator.trans(/** @Desc("Bad") */ 'bad')}
                        </span>
                    </div>
                    <div style={{ alignSelf: 'flex-start', width: 90, textAlign: 'center' }}>
                        <div style={{ color: '#E89828' }} className={classes.redemptionValue}>
                            1.5-2.22¢
                        </div>
                        <span style={{ color: '#E89828' }} className={classes.redemptionAssessment}>
                            {Translator.trans(/** @Desc("Fair") */ 'fair')}
                        </span>
                    </div>
                    <div style={{ alignSelf: 'flex-start', width: 90, textAlign: 'center' }}>
                        <div style={{ color: '#96C35C' }} className={classes.redemptionValue}>
                            2.22-3¢
                        </div>
                        <span style={{ color: '#96C35C' }} className={classes.redemptionAssessment}>
                            {Translator.trans(/** @Desc("Good") */ 'good')}
                        </span>
                    </div>
                    <div style={{ alignSelf: 'flex-end', width: 90, textAlign: 'center' }}>
                        <div style={{ color: '#27A887' }} className={classes.redemptionValue}>
                            3-5¢
                        </div>
                        <span style={{ color: '#27A887' }} className={classes.redemptionAssessment}>
                            {Translator.trans(/** @Desc("Excellent") */ 'excellent')}
                        </span>
                    </div>
                </div>
                <div className={classes.speedometer}>
                    <div className={classes.speedometerBase}>
                        <div className={classes.circle}></div>

                        <div className={classes.grayCircle}></div>
                    </div>
                    <div
                        className={classes.arrow}
                        style={{
                            transform: `translateX(-50%)  rotate(${rotation}deg)`,
                        }}
                    >
                        <svg
                            style={{ position: 'absolute', bottom: -8 }}
                            width="15"
                            height="104"
                            viewBox="0 0 15 104"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                fillRule="evenodd"
                                clipRule="evenodd"
                                d="M7.1001 0.314453L4.1001 96.3145H7.1001L7.1001 0.314453Z"
                                fill="#A4ABC2"
                            />
                            <path d="M10.1001 96.3145L7.1001 0.314453L7.1001 96.3145H10.1001Z" fill="#646C8B" />
                            <circle cx="7.1001" cy="96.3145" r="7" fill="#646C8B" />
                            <circle cx="7.10005" cy="96.3147" r="2.8" fill="#A4ABC2" />
                        </svg>
                    </div>
                </div>
            </div>
            <div className={classes.textAssessment}>
                {Translator.trans(/** @Desc("Excellent Redemption Value") */ 'excellent-redemption-value')}
            </div>
            <span style={{ display: 'flex', gap: 8, justifyContent: 'center', alignItems: 'center' }}>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="10" viewBox="0 0 12 10" fill="none">
                    <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M0.490219 1.99655L9.05848 0.0131578C9.34497 -0.0529553 9.5962 0.136552 9.61383 0.436264L10.0149 6.80075C10.0326 7.10047 9.81217 7.39577 9.53009 7.46188L0.957417 9.44528C0.670927 9.51139 0.41972 9.32186 0.40209 9.02656L0.000988393 2.66207C-0.0166418 2.36235 0.203729 2.06705 0.490219 2.00094V1.99655Z"
                        fill="#515765"
                    />
                    <path
                        d="M11.6756 5.26238H11.1644C10.9572 5.26238 10.7853 5.09049 10.7853 4.88334C10.7853 4.67618 10.9528 4.50429 11.1644 4.50429H11.6095L11.4421 2.60465C11.4156 2.30934 11.1512 2.0625 10.8558 2.0625H10.5032L10.803 6.75213C10.847 7.43089 10.3622 8.0832 9.70548 8.23305L4.27539 9.48922H11.5038C11.7991 9.48922 12.0239 9.24679 11.9974 8.95149L11.6756 5.26238Z"
                        fill="#A1A6B2"
                    />
                </svg>
                <span className={classes.reviewer}>
                    {Translator.trans(/** @Desc("Rated by AwardWallet") */ 'rated-by-awardwallet')}
                </span>
            </span>
        </div>
    );
}
