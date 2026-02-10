import { Translator } from '@Services/Translator';
import { addMonths, differenceInDays, isBefore } from 'date-fns';

export function isMonthDisplayed(displayedMonth: Date, setMonth: Date): boolean {
    const secondDisplayedMonth = addMonths(displayedMonth, 1);

    const isCurrentMonthDisplayed =
        displayedMonth.getMonth() === setMonth.getMonth() && displayedMonth.getFullYear() === setMonth.getFullYear();

    const isNextMonthDisplayed =
        secondDisplayedMonth.getMonth() === setMonth.getMonth() &&
        secondDisplayedMonth.getFullYear() === setMonth.getFullYear();

    return isCurrentMonthDisplayed || isNextMonthDisplayed;
}

export function getContentForTooltip(fromDate: Date | null, untilDate: Date | null, cellDay: Date) {
    if (fromDate && !untilDate && isBefore(fromDate, cellDay)) {
        const countDays = differenceInDays(cellDay, fromDate.setHours(0));
        return `${countDays} ${Translator.transChoice('nights', countDays)}`;
    }

    if (!fromDate && untilDate && isBefore(cellDay, untilDate)) {
        const countDays = differenceInDays(untilDate.setHours(0), cellDay);

        return `${countDays} ${Translator.transChoice('nights', countDays)}`;
    }

    return '';
}
