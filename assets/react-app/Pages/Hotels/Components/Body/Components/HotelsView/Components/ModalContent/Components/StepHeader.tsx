import { Icon } from '@UI/Icon';
import { Translator } from '@Services/Translator';
import React from 'react';
import classes from './StepHeader.module.scss';

interface StepHeaderProps {
    numberOfStep: number;
    iconType: 'DoubleTick' | 'Change' | 'CheckedCalendar';
    title: string;
    description?: string;
}
export function StepHeader({ numberOfStep, iconType, title, description }: StepHeaderProps) {
    return (
        <>
            <div className={classes.iconContainer}>
                <Icon type={iconType} color="primary" />
            </div>

            <div className={classes.titleAndDescribeContainer}>
                <div className={classes.titleContainer}>
                    <span className={classes.stepCount}>
                        {Translator.trans(/** @Desc("Step") */ 'step')} {numberOfStep}.
                    </span>
                    {title}
                </div>
                <p className={classes.description}>{description}</p>
            </div>
        </>
    );
}
