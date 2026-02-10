import { Theme } from '@UI/Theme';
import { createUseStyles } from 'react-jss';
import React from 'react';
import classNames from 'classnames';
import classes from './SortingRow.module.scss';

interface SortingRow {
    text: string;
    active: boolean;
    onClick: () => void;
}
interface CreateClassProps {
    active: boolean;
}
const createClasses = createUseStyles((theme: Theme) => ({
    active: ({ active }: CreateClassProps) => {
        if (!active) return;

        return {
            color: theme.textColor.hover,
        };
    },
}));

export function SortingRow({ text, onClick, active }: SortingRow) {
    const jssClasses = createClasses({ active });

    return (
        <li className={classNames(classes.sortingOptionContainer, jssClasses.active)} onClick={onClick}>
            {text}
        </li>
    );
}
