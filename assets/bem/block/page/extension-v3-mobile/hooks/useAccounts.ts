import {useState, useEffect, useCallback} from 'react';

export interface AccountData {
    providerCode: string;
    login: string;
    displayName: string;
    error?: string;
    status?: string;
    balance?: string;
    increased?: boolean;
    change?: number;
    lastChange?: string;
}

export type Accounts = Record<number, AccountData>;

export interface UseAccountsReturn {
    accounts: Accounts;
    currentAccountId: number;
    currentAccount: AccountData | undefined;
    setCurrentAccountId: (id: number) => void;
    getAccount: (id: number) => AccountData | undefined;
    addAccount: (id: number, data: AccountData) => void;
    updateAccount: (id: number, data: Partial<AccountData>) => void;
}

const saveLatestState = (sessionId: string, properties: AccountData & {accountId: number}) => {
    localStorage.setItem(sessionId, JSON.stringify(properties));
};

export const useAccounts = (sessionId: string): UseAccountsReturn => {
    const [accounts, setAccounts] = useState<Accounts>({});
    const [currentAccountId, setCurrentAccountId] = useState<number>(-1);

    const getAccount = useCallback((id: number): AccountData | undefined => {
        return accounts[id];
    }, [accounts]);

    const addAccount = useCallback((id: number, data: AccountData) => {
        setAccounts(prevAccounts => ({
            ...prevAccounts,
            [id]: data
        }));
    }, []);

    const updateAccount = useCallback((id: number, data: Partial<AccountData>) => {
        setAccounts(prevAccounts => {
            const currentAccount = prevAccounts[id];

            if (currentAccount) {
                return {
                    ...prevAccounts,
                    [id]: {...currentAccount, ...data}
                };
            }

            return prevAccounts;
        });
    }, []);

    const currentAccount = getAccount(currentAccountId);

    useEffect(() => {
        if (currentAccount) {
            saveLatestState(sessionId, {accountId: currentAccountId, ...currentAccount});
            console.log('saveLatestState', sessionId, currentAccount);
        }
    }, [currentAccountId, getAccount]);

    return {
        accounts,
        currentAccountId,
        currentAccount,
        setCurrentAccountId,
        getAccount,
        addAccount,
        updateAccount
    };
};
