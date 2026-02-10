import { Icon } from '../..';
import { LocaleForIntl } from '@Services/Env';
import { MaskitoOptions } from '@maskito/core';
import { getMaskPattern, prepareStringForDate } from './Utilities';
import { isDate, isSameDay } from 'date-fns';
import { maskitoDateOptionsGenerator, maskitoEventHandler, maskitoWithPlaceholder } from '@maskito/kit';
import { useMaskito } from '@maskito/react';
import { useMergeRef } from '@Utilities/Hooks/UseMergeRef';
import React, {
    FormEvent,
    RefObject,
    forwardRef,
    memo,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import classNames from 'classnames';
import classes from '../CommonInputClasses.module.scss';

interface DateInputProps {
    locale: LocaleForIntl;
    dateValue: Date | null;
    onChange: (newDateValue: Date | null) => void;
    placeholder?: string;
    onFocus?: () => void;
    onBlur?: () => void;
    inputContainerRef?: RefObject<HTMLDivElement>;
    fromDate?: Date;
    toDate?: Date;
    errorText?: string;
    classes?: {
        inputContainer?: string;
    };
}

export type MaskSeparator = '/' | '.' | '-';

type InputValue = {
    value: string;
    shouldReturn?: boolean;
};

const DateInputBase = forwardRef<HTMLInputElement, DateInputProps>((props, ref) => {
    const {
        locale,
        dateValue,
        onChange,
        placeholder,
        onFocus,
        onBlur,
        inputContainerRef,
        fromDate,
        toDate,
        errorText,
        classes: externalClasses,
    } = props;

    const [inputValue, setInputValue] = useState<InputValue>({ value: '' });
    const [isInputInFocus, setIsInputInFocus] = useState(false);

    const maskPattern = useMemo(() => getMaskPattern(locale), []);

    const inputRef = useRef<HTMLInputElement>(null);

    const maskitoDateOptionsRef = useMemo(
        () =>
            maskitoDateOptionsGenerator({
                mode: maskPattern.mask,
                separator: maskPattern.separator,
                min: fromDate,
                max: toDate,
            }),
        [],
    );

    const placeholderOptionRef = useMemo<
        Pick<Required<MaskitoOptions>, 'plugins' | 'postprocessors' | 'preprocessors'> & {
            removePlaceholder: (value: string) => string;
        }
    >(() => maskitoWithPlaceholder(maskPattern.mask), []);

    const maskitoOption = useMemo(
        () => ({
            options: {
                ...maskitoDateOptionsRef,
                plugins: placeholderOptionRef.plugins.concat(maskitoDateOptionsRef.plugins, [
                    maskitoEventHandler(
                        'focus',
                        (element) => {
                            setIsInputInFocus(true);

                            setInputValue({ value: element.value + maskPattern.mask.slice(element.value.length) });

                            onFocus?.();
                        },
                        { capture: true },
                    ),
                    maskitoEventHandler(
                        'blur',
                        (element) => {
                            setIsInputInFocus(false);

                            const valueWithoutMask = placeholderOptionRef.removePlaceholder(element.value);

                            if (valueWithoutMask.length !== maskPattern.mask.length) {
                                setInputValue({ value: valueWithoutMask });
                            }
                            onBlur?.();
                        },
                        { capture: true },
                    ),
                ]),
                preprocessors: [...placeholderOptionRef.preprocessors, ...maskitoDateOptionsRef.preprocessors],
                postprocessors: [...maskitoDateOptionsRef.postprocessors, ...placeholderOptionRef.postprocessors],
            },
        }),
        [],
    );

    const maskOptions = useMaskito(maskitoOption);

    const onContainerClick = useCallback(() => {
        inputRef.current?.focus();
    }, []);

    const onInputHandler = useCallback(
        (event: FormEvent<HTMLInputElement>) => {
            const target = event.target as HTMLInputElement;

            setInputValue({ value: target.value, shouldReturn: true });
        },
        [placeholderOptionRef, maskPattern],
    );

    useEffect(() => {
        if (isDate(dateValue)) {
            const stringDate = Intl.DateTimeFormat(locale)
                .format(dateValue)
                .split(maskPattern.separator)
                .map((value) => {
                    if (value.length < 2) {
                        return `0${value}`;
                    }
                    return value;
                })
                .join(maskPattern.separator);
            if (stringDate !== inputValue.value) {
                setInputValue({ value: stringDate });
            }
        }
        const valueWithoutMask = placeholderOptionRef.removePlaceholder(inputValue.value);

        if (dateValue === null && valueWithoutMask.length === maskPattern.mask.length) {
            if (isInputInFocus) {
                setInputValue({ value: maskPattern.mask });
                if (inputRef.current) {
                    inputRef.current.value = maskPattern.mask;
                }
            } else {
                setInputValue({ value: '' });
                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            }
        }
    }, [dateValue]);

    useEffect(() => {
        if (!inputValue.shouldReturn) return;

        const valueWithoutMask = placeholderOptionRef.removePlaceholder(inputValue.value);

        if (dateValue === null && valueWithoutMask.length === maskPattern.mask.length) {
            const newDate = new Date(prepareStringForDate(valueWithoutMask, maskPattern.mask, maskPattern.separator));

            onChange(newDate);
            return;
        }

        if (isDate(dateValue) && valueWithoutMask.length === maskPattern.mask.length) {
            const newDate = new Date(prepareStringForDate(valueWithoutMask, maskPattern.mask, maskPattern.separator));
            if (!isSameDay(dateValue, newDate)) {
                onChange(newDate);
                return;
            }
        }

        if (isDate(dateValue) && valueWithoutMask.length < maskPattern.mask.length) {
            onChange(null);
            return;
        }
    }, [inputValue]);

    return (
        <div className={classes.container}>
            <div
                ref={inputContainerRef}
                className={classNames(classes.textInputContainer, externalClasses?.inputContainer, {
                    [classes.textInputContainerWithPlaceHolder as string]: placeholder,
                    [classes.textInputContainerError as string]: errorText,
                })}
                onClick={onContainerClick}
            >
                <Icon type="Calendar" />
                <div className={classes.inputWrapper}>
                    <input
                        type="text"
                        ref={useMergeRef(maskOptions, ref, inputRef)}
                        onInput={onInputHandler}
                        value={inputValue.value}
                        inputMode="numeric"
                    />
                    {placeholder && <span className={classes.placeholder}>{placeholder}</span>}
                </div>
            </div>
            <div className={classes.error}>{errorText}</div>
        </div>
    );
});

DateInputBase.displayName = 'DateInputBase';

export const DateInput = memo(DateInputBase);
