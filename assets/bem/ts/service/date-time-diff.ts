/* eslint-disable @typescript-eslint/no-unsafe-call */
import { extractOptions } from './env';
import { isNull } from 'lodash';
//@ts-expect-error Something wrong with packages types
import DTDiff from 'date-time-diff';
import Translator from './translator';

function getFormatter(): Intl.NumberFormat | null {
    const opts = extractOptions();

    try {
        return Intl.NumberFormat(opts.locale);
    } catch (e) {
        if (e instanceof RangeError) {
            return Intl.NumberFormat(opts.defaultLocale);
        } else {
            return null;
        }
    }
}

export default new DTDiff(Translator, (number: number) => {
    const formatter = getFormatter();

    if (isNull(formatter)) {
        return number.toString();
    }

    return formatter.format(number);
});
