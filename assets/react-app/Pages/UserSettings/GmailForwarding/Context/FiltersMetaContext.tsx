import { FiltersMeta, useFetchFiltersMeta } from '../UseFetchFiltersMeta';
import React, { PropsWithChildren, createContext, useContext } from 'react';

type FiltersMetaContextValue = { loading: boolean; filtersMeta: FiltersMeta | null; error: Error | null };

const FiltersMetaContext = createContext<null | FiltersMetaContextValue>(null);

export function FiltersMetaProvider({ children }: PropsWithChildren) {
    const { filtersMeta, isLoading, error } = useFetchFiltersMeta();

    return (
        <FiltersMetaContext.Provider value={{ loading: isLoading, filtersMeta, error }}>
            {children}
        </FiltersMetaContext.Provider>
    );
}

export function useFiltersMeta() {
    const context = useContext(FiltersMetaContext);
    if (context === null) {
        throw new Error('useFiltersMeta must be used within a FiltersMetaProvider');
    }
    return context;
}
