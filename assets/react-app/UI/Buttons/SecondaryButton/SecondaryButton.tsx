import { BaseButtonProps, MemoizedBaseButton } from '../BaseButton/BaseButton';
import React, { forwardRef, memo, useMemo } from 'react';
import classNames from 'classnames';
import classes from './Secondary.module.scss';

type SecondaryButtonProps = Pick<
    BaseButtonProps,
    'onClick' | 'type' | 'disabled' | 'className' | 'iconSize' | 'iconType' | 'loading'
> &
    Required<Pick<BaseButtonProps, 'text'>>;

const SecondaryButtonBase = forwardRef<HTMLButtonElement, SecondaryButtonProps>((props, ref) => {
    const SecondaryButtonBase = useMemo(
        () => ({
            button: classNames(classes.secondaryButton, props.className?.button),
            text: props.className?.text,
            icon: props.className?.icon,
        }),
        [props.className?.button],
    );
    return <MemoizedBaseButton {...props} ref={ref} className={SecondaryButtonBase} />;
});

SecondaryButtonBase.displayName = 'SecondaryButton';

export const SecondaryButton = memo(SecondaryButtonBase);
