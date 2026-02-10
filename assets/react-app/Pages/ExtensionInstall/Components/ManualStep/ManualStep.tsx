import React, { ReactNode } from 'react';
import classes from './ManualStep.module.scss';

type ManualStepProps = {
    stepNumber: number;
    text: string;
    extraContent?: ReactNode;
};
export function ManualStep({ extraContent, stepNumber, text }: ManualStepProps) {
    return (
        <li className={classes.instructionStep}>
            <div className={classes.instructionStepNumber}>{stepNumber}</div>
            <p className={classes.instructionStepDescription} dangerouslySetInnerHTML={{ __html: text }} />
            {extraContent && <>{extraContent}</>}
        </li>
    );
}
