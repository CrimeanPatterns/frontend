import { BaseButtonProps, MemoizedBaseButton } from '../BaseButton/BaseButton';
import React, { forwardRef, memo, useMemo } from 'react';
import classNames from 'classnames';
import classes from './TextButton.module.scss';

type TextButtonBaseProps = Pick<
    BaseButtonProps,
    | 'onClick'
    | 'type'
    | 'disabled'
    | 'iconSize'
    | 'iconType'
    | 'loading'
    | 'iconColor'
    | 'className'
    | 'onTouchStart'
    | 'onMouseEnter'
> &
    Required<Pick<BaseButtonProps, 'text'>>;

const TextButtonBase = forwardRef<HTMLButtonElement, TextButtonBaseProps>((props, ref) => {
    const textButtonClasses = useMemo(
        () => ({ button: classNames(classes.textButton, props.className?.button), text: props.className?.text }),
        [props.className],
    );
    return <MemoizedBaseButton {...props} ref={ref} className={textButtonClasses} />;
});

TextButtonBase.displayName = 'TextButton';

export const TextButton = memo(TextButtonBase);
