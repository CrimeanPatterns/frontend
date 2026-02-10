import { DayProps, useDayRender } from 'react-day-picker';
import React, { useRef } from 'react';
import calendarClasses from '../Calendar.module.scss';
import classNames from 'classnames';
import dayClasses from './CalendarDay.module.scss';

interface CustomDayProps extends DayProps {
    tooltipText?: string;
    className?: string;
    onMouseEntry?: (day: Date) => void;
    onMouseLeave?: (day: Date) => void;
}

export const CalendarDay = ({
    date,
    displayMonth,
    className,
    tooltipText,
    onMouseEntry,
    onMouseLeave,
}: CustomDayProps) => {
    const buttonRef = useRef<HTMLButtonElement>(null);
    const dayRender = useDayRender(date, displayMonth, buttonRef);

    const needToShowTooltip =
        dayRender.activeModifiers.isDayHovered && !dayRender.activeModifiers.disabled && tooltipText !== '';

    if (dayRender.isHidden) {
        return <div role="gridcell"></div>;
    }
    if (!dayRender.isButton) {
        return <div {...dayRender.divProps} />;
    }

    return (
        <div className={dayClasses.dayWrapper}>
            {needToShowTooltip && <div className={dayClasses.tooltip}>{tooltipText}</div>}
            <div
                className={dayClasses.dayButtonWrapper}
                onMouseMove={() => {
                    if (dayRender.activeModifiers.disabled) {
                        onMouseLeave?.(date);
                        return;
                    }
                    onMouseEntry?.(date);
                }}
                onMouseLeave={() => {
                    onMouseLeave?.(date);
                }}
            >
                <button
                    ref={buttonRef}
                    {...dayRender.buttonProps}
                    className={classNames(
                        dayRender.buttonProps.className,
                        calendarClasses.rdpButton,
                        className,
                        dayRender.activeModifiers.isDayInHoverRange ? calendarClasses.selectedDay : '',
                    )}
                >
                    {date.getDate()}
                </button>
            </div>
        </div>
    );
};
