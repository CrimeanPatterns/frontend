import 'rc-dropdown/assets/index.css';
import { Icon } from '@UI/Icon';
import { Menu, MenuItem, SelectInfo } from '@UI/Layout/Menu/Menu';
import RcDropdown from 'rc-dropdown';
import React, { useCallback, useEffect, useState } from 'react';
import classNames from 'classnames';
import classes from './Dropdown.module.scss';

type DropdownClasses = {
    anchor?: string;
    anchorText?: string;
    anchorActive?: string;
    anchorLabel?: string;
    anchorStaticInfo?: string;
};

type DropdownProps<T extends MenuItem> = {
    items?: T[];
    placeholder?: string;
    onSelect?: (selectedItems: T) => void;
    selectedItem?: T | null;
    classes?: DropdownClasses;
    label?: string;
    staticInfo?: string;
};

export function Dropdown<T extends MenuItem>({
    placeholder,
    items,
    selectedItem,
    onSelect,
    classes: externalClasses,
    label,
    staticInfo,
}: DropdownProps<T>) {
    const [isVisible, setIsVisible] = useState(false);
    const [anchorText, setAnchorText] = useState(placeholder);
    const [selectedKeys, setSelectedKeys] = useState<string[]>([]);

    const onChangeHandler = useCallback(
        (info: SelectInfo) => {
            setIsVisible(false);
            const selectedItem = items?.find((item) => String(item.key) === info.selectedKeys[0]);

            if (selectedItem) {
                onSelect?.(selectedItem);
            }
        },
        [onSelect, items],
    );

    useEffect(() => {
        if (selectedItem) {
            setAnchorText(selectedItem.label);

            setSelectedKeys([String(selectedItem.key)]);
        }
    }, [selectedItem]);
    return (
        <RcDropdown
            autoDestroy
            onVisibleChange={setIsVisible}
            animation="slide-up"
            overlay={<Menu items={items} selectedKeys={selectedKeys} onSelect={onChangeHandler} />}
            trigger="click"
            visible={isVisible}
            overlayClassName={classes.dropdownContainer}
        >
            <button
                type="button"
                className={classNames(
                    classes.dropdownAnchor,
                    {
                        [classes.dropdownAnchorWithDescription as string]: selectedItem?.description,
                    },
                    externalClasses?.anchor,
                    { [externalClasses?.anchorActive as string]: isVisible && externalClasses?.anchorActive },
                )}
            >
                <div className={classes.dropdownAnchorLabelBlock}>
                    {label && (
                        <span className={classNames(classes.dropdownAnchorLabelText, externalClasses?.anchorLabel)}>
                            {label}
                        </span>
                    )}
                    <span
                        title={anchorText}
                        className={classNames(classes.dropdownAnchorText, externalClasses?.anchorText)}
                    >
                        {anchorText}
                    </span>
                    {!selectedItem?.hideDescriptionInAnchor && selectedItem?.description && (
                        <span className={classes.dropdownAnchorDescription}>{selectedItem.description}</span>
                    )}
                </div>

                <div className={classes.dropdownAnchorStaticInfoBlock}>
                    {staticInfo && (
                        <div
                            className={classNames(classes.dropdownAnchorStaticInfo, externalClasses?.anchorStaticInfo)}
                        >
                            {staticInfo}
                        </div>
                    )}

                    <Icon
                        type="ArrowDown"
                        size="small"
                        className={classNames(classes.dropdownAnchorIcon, {
                            [classes.dropdownAnchorIconActive as string]: isVisible,
                        })}
                        color="disabled"
                    />
                </div>
            </button>
        </RcDropdown>
    );
}
