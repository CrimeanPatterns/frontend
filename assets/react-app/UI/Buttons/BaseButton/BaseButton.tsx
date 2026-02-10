import { Icon, IconColor, IconSize, IconType } from '../../Icon';
import { Loader } from '../../Icon/Loader';
import React, { ButtonHTMLAttributes, ReactNode, forwardRef, memo } from 'react';
import classNames from 'classnames';
import classes from './BaseButton.module.scss';

type ButtonClasses = {
    button?: string;
    text?: string;
    icon?: string;
};

export type BaseButtonProps = Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'className'> & {
    loading?: boolean;
    iconType?: IconType;
    iconColor?: IconColor;
    disabledIconColor?: IconColor;
    loaderColor?: IconColor;
    iconSize?: IconSize | number;
    className?: ButtonClasses;
    text?: ReactNode;
};

const BaseButton = forwardRef<HTMLButtonElement, BaseButtonProps>(
    (
        {
            loading,
            iconType,
            iconColor,
            iconSize,
            className,
            text,
            loaderColor,
            disabled,
            disabledIconColor = iconColor,
            ...rest
        },
        ref,
    ) => {
        const textButtonClassWithLoader = classNames({
            [classes.buttonTextWithLoader as string]: loading !== undefined,
            [classes.buttonTextWithLoaderWithoutIcon as string]: loading !== undefined && !iconType,
        });

        return (
            <button
                className={classNames(classes.button, className?.button)}
                {...rest}
                type={rest.type || 'button'}
                ref={ref}
                disabled={disabled}
            >
                {iconType &&
                    (loading ? (
                        <Loader size={iconSize} color={loaderColor} />
                    ) : (
                        <Icon
                            color={disabled ? disabledIconColor : iconColor}
                            type={iconType}
                            size={iconSize}
                            className={className?.icon}
                        />
                    ))}
                {!iconType && loading && (
                    <div className={classes.buttonLoader}>
                        <Loader color={loaderColor} size={iconSize} />
                    </div>
                )}
                {text && <div className={classNames(textButtonClassWithLoader, className?.text)}>{text}</div>}
            </button>
        );
    },
);

BaseButton.displayName = 'BaseButton';

export const MemoizedBaseButton = memo(BaseButton);
