import * as styles from 'react-day-picker/dist/style.module.css';
import { CalendarDay } from './Components/CalendarDay';
import { CalendarHeader } from './Components/CalendarHeader';
import { DayPicker, DayProps } from 'react-day-picker';
import { Icon } from '..';
import { LocaleForIntl } from '@Services/Env';
import { addMonths, addYears, isAfter, isBefore, isSameDay, sub } from 'date-fns';
import { getContentForTooltip, isMonthDisplayed } from './Utilities';
import { getDateFNSLocale } from '@Utilities/DateUtilities';
import React, { memo, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import classes from './Calendar.module.scss';

interface DoubleCalendarProps {
    fromDate: Date | null;
    untilDate: Date | null;
    onDayClick: (day: Date) => void;
    locale: LocaleForIntl;
}

export const DoubleCalendar = memo(({ onDayClick, locale, untilDate, fromDate }: DoubleCalendarProps) => {
    const today = useRef(new Date()).current;
    const lastAvailableDay = useRef(sub(addYears(today, 1), { days: 1 })).current;

    const [displayedMonth, setDisplayedMonth] = useState(today);
    const [hoveredDay, setHoveredDay] = useState<Date | null>(null);

    const localeDateFns = useRef(getDateFNSLocale(locale)).current;

    const selectedDates = useMemo(
        () => ({ from: fromDate || undefined, to: untilDate || undefined }),
        [fromDate, untilDate],
    );
    const calendarClasses = useMemo(
        () => ({
            ...styles,
            root: classes.rdp,
            caption: classes.rdpCaption,
            caption_start: classes.rdpCaptionStart,
            caption_end: classes.rdpCaptionEnd,
            months: classes.rdpMonths,
            month: classes.rdpMonth,
            table: classes.rdpTable,
            cell: classes.rdpSell,
            head_cell: classes.rdpHeaderSell,
            day: classes.rdpDay,
            nav_button: classes.rdpButtonNav,
            button: classes.rdpButton,
            day_today: classes.rdpToday,
            day_selected: classes.selectedDay,
            day_range_start: classes.rangeStart,
            day_range_end: classes.rangeEnd,
        }),
        [],
    );

    const onMonthChange = useCallback((month: Date) => {
        setDisplayedMonth(month);
    }, []);
    const onDayMouseEntry = useCallback((day: Date) => {
        setHoveredDay(day);
    }, []);
    const onDayMouseLeave = useCallback(() => {
        setHoveredDay(null);
    }, []);

    useEffect(() => {
        function checkMouseMove(event: MouseEvent) {
            if (!(event.target instanceof HTMLElement) || !event.target.matches('button')) {
                setHoveredDay(null);
            }
        }

        document.addEventListener('mousemove', checkMouseMove);

        return () => {
            document.removeEventListener('mousemove', checkMouseMove);
        };
    }, []);

    useEffect(() => {
        if (fromDate) {
            if (!isMonthDisplayed(displayedMonth, fromDate)) {
                setDisplayedMonth(fromDate);
            }
        }
        if (untilDate) {
            if (!isMonthDisplayed(displayedMonth, untilDate)) {
                setDisplayedMonth(addMonths(untilDate, -1));
            }
        }
    }, [fromDate, untilDate]);

    return (
        <DayPicker
            mode="range"
            onDayClick={onDayClick}
            selected={selectedDates}
            modifiers={{
                isDayInHoverRange: (day) => {
                    if (!hoveredDay) return false;

                    if (fromDate && !untilDate && !isAfter(day, hoveredDay) && isAfter(day, fromDate)) {
                        return true;
                    }

                    if (!fromDate && untilDate && !isBefore(day, hoveredDay) && isBefore(day, untilDate)) {
                        return true;
                    }
                    return false;
                },
                isDayHovered: (day) => {
                    if (hoveredDay && isSameDay(day, hoveredDay)) {
                        return true;
                    }
                    return false;
                },
            }}
            fromDate={today}
            toDate={lastAvailableDay}
            month={displayedMonth}
            onMonthChange={onMonthChange}
            numberOfMonths={2}
            components={{
                CaptionLabel: (props) => <CalendarHeader {...props} locale={locale} />,
                IconLeft: () => <Icon type="ArrowLeft" />,
                IconRight: () => <Icon type="ArrowRight" />,
                Day: (props: DayProps) => (
                    <CalendarDay
                        {...props}
                        tooltipText={getContentForTooltip(fromDate, untilDate, props.date)}
                        onMouseEntry={onDayMouseEntry}
                        onMouseLeave={onDayMouseLeave}
                    />
                ),
            }}
            classNames={calendarClasses}
            locale={localeDateFns}
        />
    );
});

DoubleCalendar.displayName = 'DoubleCalendar';
