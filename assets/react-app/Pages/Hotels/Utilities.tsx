import { CentrifugeConfig } from '../../Contexts/CentrifugeContext';
import { ProviderBrand } from './Entities';
import HiltonLogo from './Assets/hilton-world-logo.svg';
import HyattLogo from './Assets/hyatt-logo.svg';
import IHGLogo from './Assets/ihg-logo.svg';
import MarriottLogo from './Assets/marriott-logo.svg';
import React, { ReactElement } from 'react';

export function getCentrifugeConfig(): CentrifugeConfig {
    const contentElement = document.getElementById('content') as HTMLElement;

    const centrifuge = JSON.parse(contentElement.dataset['centrifuge'] as string) as CentrifugeConfig;

    return centrifuge;
}

export function getHotelProviderLogo(provider: ProviderBrand, className?: string): ReactElement {
    switch (provider) {
        case ProviderBrand.GoldPassport:
            return <HyattLogo style={{ width: '100%', height: '100%' }} className={className} />;
        case ProviderBrand.Hhonors:
            return <HiltonLogo style={{ width: '100%', height: '100%' }} className={className} />;
        case ProviderBrand.IchotelGroup:
            return <IHGLogo style={{ width: '100%', height: '100%' }} className={className} />;
        case ProviderBrand.Marriot:
            return <MarriottLogo style={{ width: '100%', height: '100%' }} className={className} />;
    }
}
