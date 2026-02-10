import './Menu.scss';
import { MenuItem, MenuItemType } from './Components/MenuItem';
import { ItemType as RcItemType } from 'rc-menu/lib/interface';
import RcMenu from 'rc-menu';
import React, { memo, useEffect, useState } from 'react';

export interface MenuInfo {
    key: string;
    keyPath: string[];
    domEvent: React.MouseEvent<HTMLElement> | React.KeyboardEvent<HTMLElement>;
}
export interface SelectInfo extends MenuInfo {
    selectedKeys: string[];
}
export type SelectEventHandler = (info: SelectInfo) => void;

export type MenuItem<T extends string | number = string | number, K = unknown> = MenuItemType<K> & {
    key: T;
};

export type MenuProps = {
    items?: MenuItem[];
    selectedKeys?: string[];
    onSelect?: (info: SelectInfo) => void;
};

export const Menu = memo(({ items, selectedKeys, onSelect }: MenuProps) => {
    const [menuItems, setMenuItems] = useState<RcItemType[] | undefined>(undefined);

    useEffect(() => {
        const newItem = items?.map((item) => {
            const newItem = {
                label: (
                    <MenuItem
                        label={item.label}
                        description={item.description}
                        classes={item.classes}
                        value={item.value}
                    />
                ),
                key: item.key,
            };

            return newItem;
        });

        setMenuItems(newItem);
    }, [items]);

    return <RcMenu prefixCls="menu" items={menuItems} selectedKeys={selectedKeys} onSelect={onSelect} />;
});

Menu.displayName = 'Menu';
