import { MediaQueryProvider } from '@Root/Contexts/MediaQueryContext';
import { ModalManagerProvider } from '@Root/Contexts/ModalManagerContext';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { RenderOptions, render } from '@testing-library/react';
import { ThemeProvider } from 'react-jss';
import { theme } from '@UI/Theme';
import React, { ReactElement, useEffect } from 'react';

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

const AllTheProviders = ({ children }: { children: React.ReactNode }) => {
    useEffect(() => {
        const metaTag = document.createElement('meta');
        metaTag.setAttribute('name', 'csrf-token');
        metaTag.setAttribute('content', '123');

        document.head.appendChild(metaTag);

        return () => {
            metaTag.remove();
        };
    }, []);
    return (
        <QueryClientProvider client={queryClient}>
            <MediaQueryProvider>
                <ThemeProvider theme={theme}>
                    <ModalManagerProvider>{children}</ModalManagerProvider>
                </ThemeProvider>
            </MediaQueryProvider>
        </QueryClientProvider>
    );
};

const customRender = (ui: ReactElement, options?: Omit<RenderOptions, 'wrapper'>) =>
    render(ui, { wrapper: AllTheProviders, ...options });

export * from '@testing-library/react';
export { customRender as render };
