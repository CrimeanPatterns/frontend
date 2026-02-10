import '@Root/Styles/GlobalStyles.scss';
import '@Services/Starter';
import { Environment, extractOptions } from '@Services/Env';
import { MediaQueryProvider } from './MediaQueryContext';
import { ModalManagerProvider } from './ModalManagerContext';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ThemeProvider } from 'react-jss';
import { ToastContainer } from 'react-toastify';
import { theme } from '@UI/Theme';
import React, { PropsWithChildren, createContext, useContext, useRef } from 'react';

interface AppSettings extends Environment {}

const createInitialState = (): Environment => ({
    defaultLang: 'en',
    defaultLocale: 'en',
    authorized: false,
    booking: false,
    business: false,
    debug: false,
    enabledTransHelper: false,
    hasRoleTranslator: false,
    impersonated: false,
    lang: '',
    locale: 'en',
    loadExternalScripts: false,
    localeForIntl: 'en',
});
const AppSettingsContext = createContext<AppSettings>(createInitialState());

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            networkMode: 'always',
            refetchOnWindowFocus: false,
        },
        mutations: {
            networkMode: 'always',
        },
    },
});

export function AppSettingsProvider({ children }: PropsWithChildren) {
    const appSettingRef = useRef(extractOptions());

    return (
        <QueryClientProvider client={queryClient}>
            <ThemeProvider theme={theme}>
                <AppSettingsContext.Provider value={appSettingRef.current}>
                    <MediaQueryProvider>
                        <ModalManagerProvider>{children}</ModalManagerProvider>
                    </MediaQueryProvider>

                    <ToastContainer position="bottom-right" />
                </AppSettingsContext.Provider>
            </ThemeProvider>
        </QueryClientProvider>
    );
}

export const useAppSettingsContext = () => useContext(AppSettingsContext);
