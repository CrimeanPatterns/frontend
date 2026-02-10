import { AutoCompleteHint } from '..';
import { Theme } from '@UI/Theme';
import { createUseStyles } from 'react-jss';
import React from 'react';
import classNames from 'classnames';
import classes from './Hint.module.scss';

export const Hint_Height = 32;

interface HintProps<T> {
    hint: AutoCompleteHint<T>;
    selected: boolean;
    onClick: () => void;
}
interface CreateHintClassesProps {
    selected: boolean;
}

const createClasses = createUseStyles((theme: Theme) => ({
    selected: ({ selected }: CreateHintClassesProps) => {
        if (!selected) return;

        return {
            backgroundColor: theme.backgroundColor.secondary,
        };
    },
}));

export function Hint<T>({ hint, selected, onClick }: HintProps<T>) {
    const jssClasses = createClasses({ selected });

    return (
        <div onClick={onClick} className={classNames(classes.hintContainer, jssClasses.selected)} title={hint.label}>
            <p className={classes.hintText}>{hint.label}</p>
        </div>
    );
}
