import { DateInput } from '../DateInput/DateInput';
import { DoubleCalendar } from '../../Calendar/DoubleCalendar';
import { LocaleForIntl } from '@Services/Env';
import { Popover } from '@UI/Popovers';
import { addYears, isAfter, isBefore, startOfTomorrow, sub } from 'date-fns';
import { useMergeRef } from '@Utilities/Hooks/UseMergeRef';
import { useMousemoveOutside } from './Hooks';
import { useOutsideClickMultipleRefs } from '@Utilities/Hooks/UseOutsideClick';
import React, { RefObject, memo, useCallback, useMemo, useRef, useState } from 'react';
import classes from './DateRange.module.scss';

export interface DateRangeData {
    from: Date | null;
    until: Date | null;
}

export interface DateRangeErrors {
    fromInputErrorText?: string;
    untilInputErrorText?: string;
}
interface DateRangeProps {
    locale: LocaleForIntl;
    fromValue: Date | null;
    untilValue: Date | null;
    onChange: (newDateRange: DateRangeData) => void;
    fromDatePlaceholder?: string;
    untilDatePlaceholder?: string;
    errors?: DateRangeErrors;
    fromInputRef?: RefObject<HTMLInputElement>;
    untilInputRef?: RefObject<HTMLInputElement>;
    classes?: {
        dateInputContainer?: string;
    };
}

export const DateRange = memo(
    ({
        fromValue,
        untilValue,
        onChange,
        locale,
        errors,
        fromDatePlaceholder,
        untilDatePlaceholder,
        fromInputRef: externalFromInputRef,
        untilInputRef: externalUntilInputRef,
        classes: externalClasses,
    }: DateRangeProps) => {
        const today = useRef(new Date()).current;
        const tomorrow = useRef(startOfTomorrow()).current;
        const lastAvailableDay = useRef(sub(addYears(today, 1), { days: 1 })).current;
        const secondToLastDay = useRef(sub(addYears(today, 1), { days: 2 })).current;

        const isFromInputInFocusRef = useRef(false);
        const isUntilInputInFocusRef = useRef(false);

        const [isPopoverOpen, setIsPopoverOpen] = useState(false);

        const fromInputRef = useRef<HTMLInputElement>(null);
        const untilInputRef = useRef<HTMLInputElement>(null);
        const popoverContentRef = useRef<HTMLDivElement>(null);
        const fromInputContainerRef = useRef<HTMLDivElement>(null);
        const untilInputContainerRef = useRef<HTMLDivElement>(null);
        const anchorRef = useRef<HTMLDivElement>(null);

        const dateInputClass = useMemo(
            () => ({ inputContainer: externalClasses?.dateInputContainer }),
            [externalClasses],
        );

        const isClickOutside = useMousemoveOutside(popoverContentRef);

        const onFromInputChange = useCallback(
            (newDateObject: Date | null) => {
                if (!newDateObject) {
                    onChange({ from: null, until: untilValue });
                    return;
                }
                if (untilValue && !isBefore(newDateObject, untilValue)) {
                    onChange({ from: newDateObject, until: null });
                    return;
                }

                onChange({ from: newDateObject, until: untilValue });
            },
            [untilValue, onChange],
        );

        const onUntilInputChange = useCallback(
            (newDateObject: Date | null) => {
                if (!newDateObject) {
                    onChange({ from: fromValue, until: null });
                    return;
                }

                if (fromValue && !isBefore(fromValue, newDateObject)) {
                    onChange({ from: null, until: newDateObject });
                    return;
                }

                onChange({ from: fromValue, until: newDateObject });
            },
            [fromValue, onChange],
        );

        const onFromInputFocus = useCallback(() => {
            isFromInputInFocusRef.current = true;
            setIsPopoverOpen(true);
        }, []);

        const onUntilInputFocus = useCallback(() => {
            isUntilInputInFocusRef.current = true;
            setIsPopoverOpen(true);
        }, []);

        const onDayClick = useCallback(
            (day: Date) => {
                if (isFromInputInFocusRef.current) {
                    isFromInputInFocusRef.current = false;
                    untilInputRef.current?.focus();

                    if (untilValue && !isBefore(day, untilValue)) {
                        onChange({ until: null, from: day });
                        return;
                    }
                    onChange({ until: untilValue, from: day });

                    return;
                }

                if (isUntilInputInFocusRef.current) {
                    isUntilInputInFocusRef.current = false;
                    fromInputRef.current?.focus();

                    if (fromValue && !isAfter(day, fromValue)) {
                        onChange({ from: null, until: day });
                        return;
                    }

                    onChange({ from: fromValue, until: day });
                }
            },
            [untilValue, fromValue],
        );

        const onFromInputBlur = useCallback(() => {
            if (!isClickOutside.current && isFromInputInFocusRef.current) {
                fromInputRef.current?.focus();
                return;
            }

            isFromInputInFocusRef.current = false;
        }, [isClickOutside]);

        const closePopover = useCallback(() => {
            setIsPopoverOpen(false);
        }, []);

        const onUntilInputBlur = useCallback(() => {
            if (!isClickOutside.current && isUntilInputInFocusRef.current) {
                untilInputRef.current?.focus();
                return;
            }

            isUntilInputInFocusRef.current = false;
        }, [isClickOutside]);

        useOutsideClickMultipleRefs(
            [popoverContentRef, fromInputContainerRef, untilInputContainerRef],
            isPopoverOpen,
            () => {
                setIsPopoverOpen(false);
                isFromInputInFocusRef.current = false;
                isUntilInputInFocusRef.current = false;
            },
        );

        return (
            <>
                <div ref={anchorRef} className={classes.container}>
                    <DateInput
                        ref={useMergeRef(fromInputRef, externalFromInputRef)}
                        locale={locale}
                        fromDate={today}
                        toDate={secondToLastDay}
                        dateValue={fromValue}
                        onChange={onFromInputChange}
                        onFocus={onFromInputFocus}
                        onBlur={onFromInputBlur}
                        placeholder={fromDatePlaceholder}
                        inputContainerRef={fromInputContainerRef}
                        errorText={errors?.fromInputErrorText}
                        classes={dateInputClass}
                    />

                    <DateInput
                        locale={locale}
                        dateValue={untilValue}
                        fromDate={tomorrow}
                        toDate={lastAvailableDay}
                        onChange={onUntilInputChange}
                        onFocus={onUntilInputFocus}
                        onBlur={onUntilInputBlur}
                        placeholder={untilDatePlaceholder}
                        ref={useMergeRef(untilInputRef, externalUntilInputRef)}
                        inputContainerRef={untilInputContainerRef}
                        errorText={errors?.untilInputErrorText}
                        classes={dateInputClass}
                    />
                </div>
                <Popover
                    open={isPopoverOpen}
                    anchor={anchorRef}
                    offsetFromAnchorInPx={4}
                    closeTrigger="click"
                    onClose={closePopover}
                >
                    <div ref={popoverContentRef}>
                        <DoubleCalendar
                            onDayClick={onDayClick}
                            locale={locale}
                            untilDate={untilValue}
                            fromDate={fromValue}
                        />
                    </div>
                </Popover>
            </>
        );
    },
);

DateRange.displayName = 'DateRange';
