import { Icon, IconType } from '@UI/Icon';
import React from 'react';
import classes from './Term.module.scss';

interface TermProps {
    iconType: IconType;
    title: string;
    description: string;
}

export function Term({ iconType, title, description }: TermProps) {
    return (
        <li className={classes.termContainer}>
            <div className={classes.termIconContainer}>
                <Icon type={iconType} />
            </div>
            <p className={classes.termDescription}>
                <span className={classes.termTitle}>{`${title} `}</span>
                {description}
            </p>
        </li>
    );
}
