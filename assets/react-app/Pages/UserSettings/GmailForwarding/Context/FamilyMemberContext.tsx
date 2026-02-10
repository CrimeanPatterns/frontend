import React, { PropsWithChildren, createContext, useContext, useState } from 'react';

export type FamilyMembers = {
    key: string;
    label: string;
    description?: string;
    value: string;
};

type FamilyMemberContextValue = {
    selectedFamilyMember: FamilyMembers | null;
    setSelectedFamilyMember: (selectedFamilyMember: FamilyMembers | null) => void;
    alternativeAddress: string;
    setAlternativeAddress: (selectedFamilyMember: string) => void;
};

const FamilyMemberContext = createContext<null | FamilyMemberContextValue>(null);

export function FamilyMemberProvider({ children }: PropsWithChildren) {
    const [selectedFamilyMember, setSelectedFamilyMember] = useState<FamilyMembers | null>(null);

    const [alternativeAddress, setAlternativeAddress] = useState<string>('');

    return (
        <FamilyMemberContext.Provider
            value={{ selectedFamilyMember, setSelectedFamilyMember, alternativeAddress, setAlternativeAddress }}
        >
            {children}
        </FamilyMemberContext.Provider>
    );
}

export function useFamilyMember() {
    const context = useContext(FamilyMemberContext);
    if (context === null) {
        throw new Error('useFamilyMember must be used within a FamilyMemberContext');
    }
    return context;
}
