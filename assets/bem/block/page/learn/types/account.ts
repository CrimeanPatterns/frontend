export type ExpirationAccount = {
    balance: string;
    displayName: string;
    expirationDate: null | string;
    expirationDateShort: null | string;
    expirationState: null | 'far' | 'soon' | 'expired';
    link: string;
    owner: string;
    logo: string;
    providerCode: string;
    providerId: string;
};

export type CreditCardAccount = {
    balance: string;
    displayName: string;
    expirationDate: null | string;
    expirationDateShort: null | string;
    expirationState: null | 'far' | 'soon' | 'expired';
    link: string;
    owner: string;
    logo: string;
};
