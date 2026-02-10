import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { differenceInDays } from 'date-fns';

export function getTotalHotelCost(pointsPerNight: string | null, checkInDate: Date, checkOutDate: Date): string {
    if (!pointsPerNight) {
        return '';
    }

    const total_cost = (
        Number(pointsPerNight.replace(',', '')) * differenceInDays(new Date(checkOutDate), new Date(checkInDate))
    ).toString();

    return getCostWithCommas(total_cost);
}

export function getCostWithCommas(cost: number | string) {
    let result = String(cost);

    for (let i = result.length - 3; i > 0; i -= 3) {
        result = result.slice(0, i) + ',' + result.slice(i);
    }
    return result;
}

export function getHotelBrandName(brand: ProviderBrand): string {
    switch (brand) {
        case ProviderBrand.GoldPassport:
            return 'Hyatt';
        case ProviderBrand.Hhonors:
            return 'Hilton';
        case ProviderBrand.IchotelGroup:
            return 'IHG';
        case ProviderBrand.Marriot:
            return 'Marriot';
    }
}
