import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { SanitizingURLScheme, ValidationURLScheme } from '@Utilities/Hooks/UseSearchParams/Entities';

export const Destination_Field_Name = 'destination';
export const Place_Id_Field_Name = 'place_id';
export const Date_From_Field_Name = 'dateFrom';
export const Date_Until_Field_Name = 'dateUntil';
export const Rooms_Count_Field_Name = 'roomsCount';
export const Adults_Count_Field_Name = 'adultsCount';
export const Children_Count_Field_Name = 'childrenCount';
export const Brands_Field_Name = 'brands';

export const Max_Rooms_Count = 2;
export const Min_Rooms_Count = 1;
export const Max_Adults_Count = 8;
export const Min_Adults_Count = 1;
export const Max_Children_Count = 4;
export const Min_Children_Count = 0;
export const Max_Guests_Count = 8;

export const Destination_Field_Default_Value = '';
export const Place_Id_Field__Default_Value = null;
export const Date_Field_Default_Value = null;
export const Rooms_Count_Field_Default_Value = Min_Rooms_Count;
export const Adults_Count_Field_Default_Value = 1;
export const Children_Count_Field_Default_Value = 0;
export const Brands_Field_Default_Value: ProviderBrand[] = [
    ProviderBrand.GoldPassport,
    ProviderBrand.Hhonors,
    ProviderBrand.IchotelGroup,
    ProviderBrand.Marriot,
].sort((a: string, b: string) => a.localeCompare(b));

export const sanitizingScheme: SanitizingURLScheme[] = [
    { paramName: Destination_Field_Name, typeOf: 'string', sanitizeRules: { forbiddenChars: '<>' } },
    {
        paramName: Rooms_Count_Field_Name,
        typeOf: 'number',
        sanitizeRules: { minValue: Min_Rooms_Count, maxValue: Max_Rooms_Count },
    },
    {
        paramName: Adults_Count_Field_Name,
        typeOf: 'number',
        sanitizeRules: {
            minValue: Min_Adults_Count,
            maxValue: Max_Adults_Count,
        },
    },
    {
        paramName: Children_Count_Field_Name,
        typeOf: 'number',
        sanitizeRules: { minValue: Min_Children_Count, maxValue: Max_Children_Count },
    },
    { paramName: Date_From_Field_Name, typeOf: 'date' },
    { paramName: Date_Until_Field_Name, typeOf: 'date' },
    {
        paramName: Brands_Field_Name,
        typeOf: 'string',
        sanitizeRules: {
            availableValues: {
                values: [
                    ProviderBrand.GoldPassport,
                    ProviderBrand.Hhonors,
                    ProviderBrand.IchotelGroup,
                    ProviderBrand.Marriot,
                ],
                separator: '&',
            },
        },
    },
];

export const validationScheme: ValidationURLScheme[] = [
    {
        paramName: Adults_Count_Field_Name,
        typeOf: 'number',
        validationRules: {
            connectedParams: {
                paramName: [Children_Count_Field_Name],
                upLimit: Max_Guests_Count,
            },
            minValue: Min_Adults_Count,
        },
    },
    {
        paramName: Children_Count_Field_Name,
        typeOf: 'number',
        validationRules: {
            connectedParams: {
                paramName: [Adults_Count_Field_Name],
                upLimit: Max_Guests_Count,
            },
            minValue: Min_Children_Count,
        },
    },
];
