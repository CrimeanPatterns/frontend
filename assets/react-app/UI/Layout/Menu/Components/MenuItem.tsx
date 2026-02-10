import React from 'react';
import classNames from 'classnames';
import classes from './MenuItem.module.scss';

type MenuItemClasses = {
    container?: string;
};
export interface MenuItemProps<T> {
    label: string;
    value?: T;
    description?: string;
    hideDescriptionInAnchor?: boolean;
    classes?: MenuItemClasses;
}

export type MenuItemType<T> = MenuItemProps<T> & Required<Pick<MenuItemProps<T>, 'value'>>;

export function MenuItem<T>({ label, description, classes: externalClasses }: MenuItemType<T>) {
    return (
        <div className={classNames(classes.menuItem, externalClasses?.container)}>
            <span className={classes.menuItemLabel}>{label}</span>
            {description && <span className={classes.menuItemDescription}>{description}</span>}
        </div>
    );
}
