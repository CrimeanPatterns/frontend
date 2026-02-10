import {
    NumberSanitizeRules,
    NumberValidationRules,
    SanitizingURLScheme,
    StringSanitizeRules,
    ValidationURLScheme,
} from './Entities';
import { lightFormat } from 'date-fns';

export function transformIntoDate<T>(value: number | string, defaultValue?: T): Date | T | null {
    const timestamp = Date.parse(String(value));

    if (!isNaN(timestamp)) {
        return new Date(timestamp);
    }

    return defaultValue ? defaultValue : null;
}

export function transformDateIntoString(value: Date | null): string | null {
    if (!value) return null;

    return lightFormat(value, 'yyyy-MM-dd');
}

export function transformIntoNumber<T>(value: string, defaultValue?: T): number | (T extends undefined ? null : T) {
    const numericValue = Number(value);

    if (isNaN(numericValue)) {
        return defaultValue
            ? (defaultValue as T extends undefined ? null : T)
            : (null as T extends undefined ? null : T);
    }

    return numericValue;
}

export function transformStringIntoArray(separator: string) {
    function transform<T extends string>(value: string): T[] {
        const arrayValue = value.split(separator);

        return arrayValue as T[];
    }
    return transform;
}

export function sanitizeAndValidateSearchParams(
    params: URLSearchParams,
    sanitizingScheme?: SanitizingURLScheme[],
    validationScheme?: ValidationURLScheme[],
) {
    let sanitizedAndValidatedParams = params;

    if (sanitizingScheme) {
        sanitizedAndValidatedParams = sanitizeSearchParams(params, sanitizingScheme);
    }

    if (validationScheme) {
        sanitizedAndValidatedParams = validateSearchParams(sanitizedAndValidatedParams, validationScheme);
    }

    return sanitizedAndValidatedParams;
}

export function sanitizeSearchParams(params: URLSearchParams, scheme: SanitizingURLScheme[]): URLSearchParams {
    const sanitizedParams = new URLSearchParams();

    scheme.forEach(({ paramName, typeOf, sanitizeRules }) => {
        const paramValue = params.get(paramName);

        if (paramValue !== null) {
            switch (typeOf) {
                case 'string': {
                    if (paramValue.length !== 0) {
                        sanitizedParams.set(paramName, sanitizeStringValue(paramValue, sanitizeRules));
                    }

                    break;
                }
                case 'number': {
                    const parsedNumber = parseFloat(paramValue);

                    if (!isNaN(parsedNumber)) {
                        sanitizedParams.set(paramName, sanitizeNumberValue(parsedNumber, sanitizeRules));
                    }

                    break;
                }
                case 'date': {
                    const parsedDate = new Date(paramValue);

                    if (!isNaN(parsedDate.getTime())) {
                        sanitizedParams.set(paramName, paramValue);
                    }
                    break;
                }

                default:
                    break;
            }
        }
    });

    return sanitizedParams;
}

function sanitizeNumberValue(value: number, sanitizingRules?: NumberSanitizeRules): string {
    if (!sanitizingRules) return String(value);

    let newValue = value;

    if (sanitizingRules.maxValue !== undefined) {
        newValue = Math.min(sanitizingRules.maxValue, newValue);
    }

    if (sanitizingRules.minValue !== undefined) {
        newValue = Math.max(sanitizingRules.minValue, newValue);
    }

    return String(newValue);
}

function sanitizeStringValue(value: string, sanitizingRules?: StringSanitizeRules): string {
    if (!sanitizingRules) return value;

    let newValue = value.trim();

    if (sanitizingRules.forbiddenChars) {
        const regex = new RegExp(`[${sanitizingRules.forbiddenChars}]`, 'g');

        newValue = newValue.replace(regex, '');
    }

    if (sanitizingRules.availableValues) {
        const values = newValue.split(sanitizingRules.availableValues.separator);

        const newValues: string[] = [];

        for (const value of values) {
            if (sanitizingRules.availableValues.values.includes(value)) {
                newValues.push(value);
            }
        }

        newValue = newValues.join(sanitizingRules.availableValues.separator);
    }

    return newValue;
}

export function validateSearchParams(params: URLSearchParams, validationScheme: ValidationURLScheme[]) {
    validationScheme.forEach(({ paramName, typeOf, validationRules }) => {
        const paramValue = params.get(paramName);

        if (paramValue !== null) {
            switch (typeOf) {
                case 'number': {
                    const parsedNumber = parseFloat(paramValue);

                    if (!isNaN(parsedNumber)) {
                        params.set(paramName, validateNumberValue(parsedNumber, params, validationRules));
                    }

                    break;
                }

                default:
                    break;
            }
        }
    });

    return params;
}

function validateNumberValue(value: number, searchParams: URLSearchParams, validationRules?: NumberValidationRules) {
    if (!validationRules) return String(value);

    let newValue = value;

    if (validationRules.connectedParams && validationRules.connectedParams.paramName.length !== 0) {
        let sum = newValue;

        for (const filedName of validationRules.connectedParams.paramName) {
            const paramValue = searchParams.get(filedName);
            if (paramValue !== null) {
                const parsedParamValue = parseFloat(paramValue);
                if (!isNaN(parsedParamValue) && validationRules.connectedParams.upLimit) {
                    sum += parsedParamValue;
                }
            }
        }

        if (validationRules.connectedParams.upLimit && sum > validationRules.connectedParams.upLimit) {
            newValue =
                validationRules.minValue !== undefined
                    ? Math.max(validationRules.minValue, validationRules.connectedParams.upLimit - (sum - newValue))
                    : validationRules.connectedParams.upLimit - (sum - newValue);
        }
    }

    return String(newValue);
}
