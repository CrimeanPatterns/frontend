import { Cross } from '@UI/Icon/Cross/Cross';
import React from 'react';
import classes from './FilterTag.module.scss';

type FilterTagProps = {
    label: string;
    onRemove: () => void;
};

export function FilterTag({ label, onRemove }: FilterTagProps) {
    return (
        <div className={classes.filterTag}>
            <span>{label}</span>
            <Cross onClick={onRemove} />
        </div>
    );
}
