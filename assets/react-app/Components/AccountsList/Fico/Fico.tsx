/* eslint-disable @typescript-eslint/no-unsafe-assignment */
import { FicoAccount, FicoAccountProps } from '../FicoAccount/FicoAccount';
import React from 'react';

import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import classes from './Fico.module.scss';

interface FicoProps {
    ficoAccounts: FicoAccountProps[];
}

export default function Fico({ ficoAccounts }: FicoProps) {
    if (ficoAccounts.length === 0) {
        return null;
    }
    return (
        <AppSettingsProvider>
            <div className={classes.ficoAccounts}>
                {ficoAccounts.map((account) => (
                    <FicoAccount
                        key={account.name}
                        balance={account.balance}
                        isChangePositive={account.isChangePositive}
                        balanceChangeNumber={account.balanceChangeNumber}
                        name={account.name}
                        onUpdate={account.onUpdate}
                        lastUpdatedDate={account.lastUpdatedDate}
                        isUpdateAvailable={account.isUpdateAvailable}
                        accountId={account.accountId}
                        account={account.account}
                        isUpdating={account.isUpdating}
                        ficoRanges={account.ficoRanges}
                    />
                ))}
            </div>
        </AppSettingsProvider>
    );
}
