import { extractOptions } from './env';

export function numberFormat(value: number): string {
    return new Intl.NumberFormat(extractOptions().locale.replace('_', '-'))
        .format(value);
}

export function currencyFormat(value: number, currency = 'USD', options = {}): string {
    return new Intl.NumberFormat(
        extractOptions().locale.replace('_', '-'),
        Object.assign({
            style: 'currency',
            currency: currency,
        }, options)
    ).format(value);
}

export function formatFileSize(bytes: number, dp = 1): string {
    const thresh = 1024;

    if (Math.abs(bytes) < thresh) {
        return bytes.toString() + ' B';
    }

    const units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const r = 10 ** dp;
    let u = -1;

    do {
        bytes /= thresh;
        ++u;
    } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);


    return bytes.toFixed(dp) + ' ' + units[u];
}
