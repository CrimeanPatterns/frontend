import { BaseButtonProps, MemoizedBaseButton } from '../BaseButton/BaseButton';
import React, { forwardRef, memo, useMemo } from 'react';
import classNames from 'classnames';
import classes from './PrimaryButton.module.scss';

type PrimaryButtonBaseProps = Pick<
    BaseButtonProps,
    'onClick' | 'type' | 'disabled' | 'className' | 'iconSize' | 'iconType' | 'loading'
> &
    Required<Pick<BaseButtonProps, 'text'>>;

const PrimaryButtonBase = forwardRef<HTMLButtonElement, PrimaryButtonBaseProps>((props, ref) => {
    const primaryButtonClasses = useMemo(
        () => ({
            button: classNames(classes.primaryButton, props.className?.button),
            text: props.className?.text,
            icon: props.className?.icon,
        }),
        [],
    );
    return (
        <MemoizedBaseButton
            {...props}
            ref={ref}
            className={primaryButtonClasses}
            iconColor="primary"
            loaderColor="primary"
        />
    );
});

PrimaryButtonBase.displayName = 'PrimaryButton';

export const PrimaryButton = memo(PrimaryButtonBase);
