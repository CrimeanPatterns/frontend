import { Icon, IconType } from '../../Icon';
import { Loader } from '@UI/Icon/Loader';
import { useMergeRef } from '@Utilities/Hooks/UseMergeRef';
import React, { ChangeEvent, KeyboardEvent, MouseEvent, RefObject, forwardRef, useCallback, useRef } from 'react';
import classNames from 'classnames';
import defaultClasses from '../CommonInputClasses.module.scss';

export interface TextInputRef {
    focus: () => void;
}

type TextInputClasses = {
    container?: string;
    containerWithError?: string;
};
export interface TextInputProps {
    value: string;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
    iconType?: IconType;
    placeholder?: string;
    hint?: string;
    onFocus?: () => void;
    onBlur?: () => void;
    onEnter?: () => void;
    containerRef?: RefObject<HTMLDivElement>;
    hideError?: boolean;
    errorText?: string;
    showLoader?: boolean;
    forbiddenChars?: string;
    name?: string;
    classes?: TextInputClasses;
}

export const TextInput = forwardRef<HTMLInputElement, TextInputProps>((props, ref) => {
    const {
        value,
        onChange,
        placeholder,
        errorText,
        iconType,
        onFocus,
        onBlur,
        containerRef,
        showLoader,
        name,
        forbiddenChars,
        classes,
        onEnter,
        hint,
        hideError = false,
    } = props;

    const inputRef = useRef<HTMLInputElement>(null);

    const onContainerClick = useCallback((event: MouseEvent<HTMLDivElement>) => {
        if (!inputRef.current) return;

        if (event.target === containerRef?.current) {
            inputRef.current.selectionStart = inputRef.current.value.length;
        }
        inputRef.current.focus();
    }, []);

    const onChangeHandler = useCallback(
        (event: ChangeEvent<HTMLInputElement>) => {
            if (forbiddenChars) {
                const regex = new RegExp(`[${forbiddenChars}]`, 'g');
                event.currentTarget.value = event.currentTarget.value.replace(regex, '');
            }

            event.currentTarget.value = event.currentTarget.value.trimStart();

            onChange(event);
        },
        [forbiddenChars, onChange],
    );

    const onKeyUpHandler = useCallback((event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            onEnter?.();
        }
    }, []);

    return (
        <div className={classNames(defaultClasses.container, classes?.containerWithError)}>
            <div
                ref={containerRef}
                className={classNames(defaultClasses.textInputContainer, classes?.container, {
                    [defaultClasses.textInputContainerWithPlaceHolder as string]: placeholder,
                    [defaultClasses.textInputContainerError as string]: errorText,
                })}
                onClick={onContainerClick}
            >
                {placeholder && (
                    <span
                        className={classNames(defaultClasses.placeholder, {
                            [defaultClasses.placeholderWithIcon as string]: iconType,
                            [defaultClasses.placeholderWithValue as string]: value,
                        })}
                    >
                        {placeholder}
                    </span>
                )}
                {iconType !== undefined && (showLoader ? <Loader color="active" /> : <Icon type={iconType} />)}
                <input
                    name={name}
                    type="text"
                    ref={useMergeRef(inputRef, ref)}
                    value={value}
                    onChange={onChangeHandler}
                    onFocus={onFocus}
                    onBlur={onBlur}
                    onKeyUp={onKeyUpHandler}
                    placeholder={hint}
                ></input>
            </div>
            {!hideError && <div className={defaultClasses.error}>{errorText}</div>}
        </div>
    );
});

TextInput.displayName = 'TextInput';
