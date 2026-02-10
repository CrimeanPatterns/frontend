import { Icon, IconType } from '@UI/Icon';
import { Loader } from '@UI/Icon/Loader';
import React, {
    ChangeEvent,
    KeyboardEvent,
    MouseEvent,
    RefObject,
    forwardRef,
    useImperativeHandle,
    useRef,
} from 'react';
import classNames from 'classnames';
import classes from '../CommonInputClasses.module.scss';

interface PasswordInputProps {
    value: string;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
    onEnter?: () => void;
    iconType?: IconType;
    className?: string;
    placeholder?: string;
    errorText?: string;
    showLoader?: boolean;
    containerRef?: RefObject<HTMLDivElement>;
    forbiddenChars?: string;
}

interface PasswordInputRef {
    focus: () => void;
}

export const PasswordInput = forwardRef<PasswordInputRef, PasswordInputProps>(
    (
        {
            value,
            onChange,
            iconType,
            className,
            placeholder,
            errorText,
            showLoader,
            containerRef,
            forbiddenChars,
            onEnter,
        },
        ref,
    ) => {
        const inputRef = useRef<HTMLInputElement>(null);

        const onContainerClick = (event: MouseEvent<HTMLDivElement>) => {
            if (!inputRef.current) return;

            if (event.target === containerRef?.current) {
                inputRef.current.selectionStart = inputRef.current.value.length;
            }
            inputRef.current.focus();
        };

        const onChangeHandler = (event: ChangeEvent<HTMLInputElement>) => {
            if (forbiddenChars) {
                const regex = new RegExp(`[${forbiddenChars}]`, 'g');
                event.currentTarget.value = event.currentTarget.value.replace(regex, '');
            }
            onChange(event);
        };

        const onKeyUpHandler = (event: KeyboardEvent<HTMLInputElement>) => {
            if (event.key === 'Enter') {
                onEnter?.();
            }
        };

        useImperativeHandle(ref, () => ({
            focus: () => inputRef.current?.focus(),
        }));
        return (
            <div className={classNames(classes.container, className)}>
                <div
                    ref={containerRef}
                    className={classNames(classes.textInputContainer, {
                        [classes.textInputContainerWithPlaceHolder as string]: placeholder,
                        [classes.textInputContainerError as string]: errorText,
                    })}
                    onClick={onContainerClick}
                >
                    {placeholder && <span className={classes.placeholder}>{placeholder}</span>}
                    {iconType !== undefined && (showLoader ? <Loader /> : <Icon type={iconType} />)}
                    <input
                        role="textbox"
                        type="password"
                        ref={inputRef}
                        value={value}
                        onChange={onChangeHandler}
                        onKeyUp={onKeyUpHandler}
                    ></input>
                </div>
                <div className={classes.error}>{errorText}</div>
            </div>
        );
    },
);

PasswordInput.displayName = 'PasswordInput';
