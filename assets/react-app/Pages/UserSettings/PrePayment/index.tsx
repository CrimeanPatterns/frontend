import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { ErrorPage } from '../GmailForwarding/Components/ErrorPage';
import { Form } from './Components/Form/Form';
import { Translator } from '@Services/Translator';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import { useInitialData } from './UseInitialData';
import React, { useEffect } from 'react';
import classes from './PrePaymentPage.module.scss';

export default function PrePaymentPage() {
    const {
        email,
        price,
        purchaseTypes,
        hash,
        refCode,
        error,
        canBuyNewSubscription,
        appleSubscription,
        membershipExpiration,
    } = useInitialData();

    useEffect(() => {
        hideGlobalLoader();
    }, []);

    if (error) {
        return <ErrorPage hideDefaultButton errorText={error} />;
    }

    if (!email || !price || purchaseTypes.length === 0 || !hash || !refCode) {
        throw new Error();
    }

    return (
        <AppSettingsProvider>
            <div className={classes.prePaymentPage}>
                <h1
                    className={classes.prePaymentPageTitle}
                    dangerouslySetInnerHTML={{
                        __html: Translator.trans('pre-payment.title', {
                            email: `<span class='${classes.prePaymentPageTitleHighlighted}'>${email}</span>`,
                            wrapperOn: `<span class='${classes.prePaymentPageTitleWrapper}'>`,
                            wrapperOff: `</span'>`,
                            forWrapperOn: `<span class=${classes.prePaymentPageTitleRegularColor}>`,
                            forWrapperOff: '</span>',
                        }),
                    }}
                />
                <Form
                    canBuyNewSubscription={canBuyNewSubscription}
                    price={price}
                    purchaseTypes={purchaseTypes}
                    hash={hash}
                    appleSubscription={appleSubscription}
                    refCode={refCode}
                    membershipExpiration={membershipExpiration}
                />
            </div>
        </AppSettingsProvider>
    );
}
