import { ProviderBrand } from '../Entities';
import HiltonLogo from '../Assets/hilton-world-logo.svg';
import HyattLogo from '../Assets/hyatt-logo.svg';
import IHGLogo from '../Assets/ihg-logo.svg';
import MarriottLogo from '../Assets/marriott-logo.svg';
import React, { PropsWithChildren, ReactElement, createContext, useContext } from 'react';

interface HotelsProviderInitialData {
    code: ProviderBrand;
    displayName: string;
    shortName: string;
    balance: number;
}
interface HotelsProvider extends HotelsProviderInitialData {
    logo: () => ReactElement;
}

type HotelsProvidersApi = { [key in ProviderBrand]: HotelsProviderInitialData };

type HotelPageInitialDataContextValue = {
    providers: { [key in ProviderBrand]: HotelsProvider };
    isDebug: boolean;
};

const providersLogo: { [key in ProviderBrand]: () => ReactElement } = {
    goldpassport: () => <HyattLogo style={{ flexGrow: 1 }} />,
    hhonors: () => <HiltonLogo style={{ flexGrow: 1 }} />,
    ichotelsgroup: () => <IHGLogo style={{ flexGrow: 1 }} />,
    marriott: () => <MarriottLogo style={{ flexGrow: 1 }} />,
};

const HotelPageInitialDataContext = createContext<null | HotelPageInitialDataContextValue>(null);

export function HotelPageInitialDataProvider({ children }: PropsWithChildren) {
    const contentElement = document.getElementById('content') as HTMLElement;

    const providers = JSON.parse(contentElement.dataset['providers'] as string) as HotelsProvidersApi;

    const initialProvidersData = {} as { [key in ProviderBrand]: HotelsProvider };

    Object.keys(providers).forEach((provider) => {
        initialProvidersData[provider as ProviderBrand] = {
            ...providers[provider as ProviderBrand],
            logo: providersLogo[provider as ProviderBrand],
        };
    });

    const isDebug = contentElement.dataset['debug'] === 'true' ? true : false;

    const contextValue = {
        providers: initialProvidersData,
        isDebug,
    };

    return <HotelPageInitialDataContext.Provider value={contextValue}>{children}</HotelPageInitialDataContext.Provider>;
}

export function useHotelPageInitialData() {
    const context = useContext(HotelPageInitialDataContext);
    if (context === null) {
        throw new Error('useHotels must be used within a HotelProvider');
    }
    return context;
}
