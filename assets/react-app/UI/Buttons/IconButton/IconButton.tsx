import { BaseButtonProps, MemoizedBaseButton } from '../BaseButton/BaseButton';
import React, { forwardRef, memo, useMemo } from 'react';
import classNames from 'classnames';
import classes from './IconButton.module.scss';

export type IconButtonProps = Pick<
    BaseButtonProps,
    'onClick' | 'type' | 'disabled' | 'className' | 'iconSize' | 'iconColor'| 'loading'
> &
    Required<Pick<BaseButtonProps, 'iconType'>>;

const IconButtonBase = forwardRef<HTMLButtonElement, IconButtonProps>((props, ref) => {
    const iconBaseClasses = useMemo(
        () => ({ ...props.className, button: classNames(classes.button, props.className?.button) }),
        [props.className],
    );

    return <MemoizedBaseButton {...props} className={iconBaseClasses} ref={ref} disabledIconColor="disabled" />;
});

IconButtonBase.displayName = 'IconButton';

export const IconButton = memo(IconButtonBase);
