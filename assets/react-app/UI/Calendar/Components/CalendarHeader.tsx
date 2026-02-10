import { CaptionLabelProps } from 'react-day-picker';
import { LocaleForIntl } from '@Services/Env';
import React from 'react';
import classes from './CalendarHeader.module.scss';

interface CalendarHeaderProps extends CaptionLabelProps {
    locale: LocaleForIntl;
}

export function CalendarHeader({ displayMonth, locale }: CalendarHeaderProps) {
    const formattedDate = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(displayMonth);

    const firstChar = formattedDate.charAt(0).toUpperCase();
    const remainingString = formattedDate.slice(1);

    return <div className={classes.container}>{`${firstChar}${remainingString}`}</div>;
}
