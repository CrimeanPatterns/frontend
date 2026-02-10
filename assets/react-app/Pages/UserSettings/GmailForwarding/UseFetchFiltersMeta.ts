import { FamilyMembers } from './Context/FamilyMemberContext';
import { Translator } from '@Services/Translator';
import { axios } from '@Services/Axios';
import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';

export type FiltersMeta = {
    familyMembers: FamilyMembers[];
    listCount: number;
};

export const useFetchFiltersMeta = () => {
    const [filtersMeta, setFiltersMeta] = useState<null | FiltersMeta>(null);

    const filtersMetaQuery = useQuery({
        queryKey: ['file-count'],
        queryFn: fetchFiltersMetaCount,
        retry: false,
    });

    useEffect(() => {
        if (!filtersMetaQuery.data) return;

        const familyMembers: FamilyMembers[] = filtersMetaQuery.data.users.map((user, index) => ({
            key: String(index),
            label: user.name,
            description: user.alias.length === 0 ? Translator.trans('account.label.owner') : undefined,
            value: user.alias,
        }));

        setFiltersMeta({
            familyMembers,
            listCount: filtersMetaQuery.data.listCount,
        });
    }, [filtersMetaQuery.data]);

    return { filtersMeta, isLoading: filtersMetaQuery.isLoading, error: filtersMetaQuery.error };
};

type FiltersMetaResponse = {
    users: {
        name: string;
        alias: string;
    }[];
    listCount: number;
};

const fetchFiltersMetaCount = async () => {
    return (await axios.get<FiltersMetaResponse>('/user/get-filter-meta')).data;
};
