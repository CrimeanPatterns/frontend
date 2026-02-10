export type SanitizingURLScheme = {
    paramName: string;
} & (NumberValue | StringValue | DateValidationRules);

type NumberValue = {
    typeOf: 'number';
    sanitizeRules?: NumberSanitizeRules;
};

export interface NumberSanitizeRules {
    minValue?: number;
    maxValue?: number;
}

type StringValue = {
    typeOf: 'string';
    sanitizeRules?: StringSanitizeRules;
};
export interface StringSanitizeRules {
    forbiddenChars?: string;
    availableValues?: {
        values: string[];
        separator: string;
    };
}

type DateValidationRules = {
    typeOf: 'date';
    sanitizeRules?: undefined;
};

export type ValidationURLScheme = {
    paramName: string;
} & NumberValidation;

interface NumberValidation {
    typeOf: 'number';
    validationRules?: NumberValidationRules;
}

export interface NumberValidationRules {
    connectedParams?: {
        paramName: string[];
        upLimit?: number;
    };
    minValue?: number;
    maxValue?: number;
}
