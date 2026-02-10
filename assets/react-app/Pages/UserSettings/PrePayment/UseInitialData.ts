export type PurchaseType = {
    label: string;
    newPriceFormatted?: string;
    newPriceRaw?: number;
    priceFormatted: string;
    priceRaw: number;
    value: number;
};
export const useInitialData = () => {
    const contentElement = document.getElementById('content');

    const email = contentElement?.dataset['email'];
    const refCode = contentElement?.dataset['ref'];
    const hash = contentElement?.dataset['hash'];
    const price = contentElement?.dataset['price'];
    const dropdownOptions = contentElement?.dataset['dropdownoptions'];
    const error = contentElement?.dataset['error'];
    const canBuyNewSubscription = contentElement?.dataset['canbuynewsubscription'];
    const membershipExpiration = contentElement?.dataset['membershipexpiration'];
    const appleSubscription = contentElement?.dataset['applesubscription'];
    if ((!dropdownOptions || !email || !refCode || !hash || !price) && !error) {
        throw new Error("Initial data didn't load correctly");
    }

    let purchaseTypes: PurchaseType[] = [];
    if (dropdownOptions) {
        purchaseTypes = JSON.parse(dropdownOptions) as PurchaseType[];
    }

    return {
        email,
        price,
        purchaseTypes,
        refCode,
        hash,
        error,
        canBuyNewSubscription: canBuyNewSubscription === 'true' ? true : false,
        membershipExpiration,
        appleSubscription,
    };
};
