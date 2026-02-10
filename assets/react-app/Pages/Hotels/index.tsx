import { AppSettingsProvider } from '../../Contexts/AppSettingsContext';
import { Body } from './Components/Body/Body';
import { CentrifugeProvider } from '../../Contexts/CentrifugeContext';
import { Header } from './Components/Header/Header';
import { HotelPageInitialDataProvider } from './Contexts/HotelPageInitialDataContext';
import { HotelsProvider } from './Contexts/HotelContext/HotelContext';
import { getCentrifugeConfig } from './Utilities';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import React, { useEffect, useRef } from 'react';
import classes from './HotelPage.module.scss';

export default function HotelPage() {
    const centrifugeConfig = useRef(getCentrifugeConfig()).current;

    useEffect(() => {
        hideGlobalLoader();
    }, []);
    return (
        <AppSettingsProvider>
            <CentrifugeProvider centrifugeConfig={centrifugeConfig}>
                <HotelPageInitialDataProvider>
                    <HotelsProvider>
                        <div className={classes.container}>
                            <Header />
                            <Body />
                        </div>
                    </HotelsProvider>
                </HotelPageInitialDataProvider>
            </CentrifugeProvider>
        </AppSettingsProvider>
    );
}
