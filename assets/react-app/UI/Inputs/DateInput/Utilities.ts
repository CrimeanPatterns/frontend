import { LocaleForIntl } from '@Services/Env';
import { MaskSeparator } from './DateInput';
import { MaskitoDateMode } from '@maskito/kit';

type DateMask = Extract<MaskitoDateMode, 'dd/mm/yyyy' | 'mm/dd/yyyy' | 'yyyy/mm/dd'>;

interface MaskPattern {
    mask: DateMask;
    separator: MaskSeparator;
}

export const getMaskPattern = (locale: LocaleForIntl): MaskPattern => {
    const etalonDate = new Date(2023, 0, 1);
    const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: '2-digit', day: '2-digit' };
    const formatter = new Intl.DateTimeFormat(locale as string, options);
    const formattedDate = formatter.formatToParts(etalonDate);

    let mask = '' as DateMask;

    let separator: MaskSeparator = '/';

    for (const part of formattedDate) {
        switch (part.type) {
            case 'day':
                mask += 'dd';
                break;
            case 'month':
                mask += 'mm';
                break;
            case 'year':
                mask += 'yyyy';
                break;
            default:
                mask += part.value;
                separator = part.value as MaskSeparator;
        }
    }

    return {
        mask,
        separator,
    };
};

export const prepareStringForDate = (string: string, mask: string, separator: string): string => {
    const literalIndexes: {
        y: number;
        m: number;
        d: number;
    } = { y: 0, m: 0, d: 0 };

    mask.split(separator).forEach((pattern, index) => {
        if (pattern.includes('y')) {
            literalIndexes.y = index;
        }
        if (pattern.includes('m')) {
            literalIndexes.m = index;
        }
        if (pattern.includes('d')) {
            literalIndexes.d = index;
        }
    });

    const partOfDate = string.split(separator);

    return `${partOfDate[literalIndexes.y]}-${partOfDate[literalIndexes.m]}-${partOfDate[literalIndexes.d]}`;
};
