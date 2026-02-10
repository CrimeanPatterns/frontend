import { AfterCancellationView } from './Components/AfterCancellationView/AfterCancellationView';
import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { CancellationView } from './Components/CancellationView/CancellationView';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import { useInitialData } from './Hook/UseInitialData';
import React, { useEffect, useState } from 'react';
import classes from './CancelSubscriptionPage.module.scss';
enum CurrentView {
    Cancellation,
    AfterCancellation,
}

export default function CancelSubscriptionPage() {
    const { 
        canCancel, 
        userInfo, 
        manualCancellation, 
        cancelButtonLabel, 
        isAT201,
        confirmationTitle,
        confirmationBody,
        confirmationButtonNo,
        confirmationButtonYes 
    } = useInitialData();
    const [currentView, setCurrentView] = useState<CurrentView>(CurrentView.Cancellation);

    useEffect(() => {
        hideGlobalLoader();
    }, []);

    return (
        <AppSettingsProvider>
            <div className={classes.cancelSubscriptionPage}>
                {currentView === CurrentView.Cancellation && (
                    <CancellationView
                        cancelButtonLabel={cancelButtonLabel}
                        canCancel={canCancel}
                        userInfo={userInfo}
                        afterCancellationCallback={() => {
                            setCurrentView(CurrentView.AfterCancellation);
                        }}
                        manualCancellation={manualCancellation}
                        isAT201={isAT201}
                        confirmationTitle={confirmationTitle}
                        confirmationBody={confirmationBody}
                        confirmationButtonNo={confirmationButtonNo}
                        confirmationButtonYes={confirmationButtonYes}
                    />
                )}
                {currentView === CurrentView.AfterCancellation && <AfterCancellationView />}
            </div>
        </AppSettingsProvider>
    );
}
