import '@Bem/scss/_reset.scss';
// eslint-disable-next-line sort-imports-es6-autofix/sort-imports-es6
import '@Bem/block/button';
import '@Bem/block/button-platform';
import '@Bem/block/footer';
import '@Bem/block/logo';
import '@Bem/block/simple-header';

import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { Translator } from '@Services/Translator';
import React, { useEffect } from 'react';

import { SecondaryButton } from '@UI/Buttons';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import { useSearchParams } from '@Utilities/Hooks/UseSearchParams';
import AppleIcon from './Assets/apple-icon.svg';
import classNames from 'classnames';
import classes from './AppleSubscriptionCancellation.module.scss';

export default function CancelSubscriptionPage() {
    const { searchParams } = useSearchParams();

    const fromApp = searchParams.get('fromapp');

    const isFromApp = fromApp === '1';

    const onRedirectToAppleAccount = () => {
        document.location.href = 'https://apps.apple.com/account/subscriptions';
    };

    useEffect(() => {
        hideGlobalLoader();
    }, []);

    return (
        <AppSettingsProvider>
            <div
                className={classNames(classes.appleSubscriptionCancellationPage, {
                    [classes.appleSubscriptionCancellationPageMobile as string]: isFromApp,
                })}
            >
                <div className={classes.appleSubscriptionCancellationPageContainer}>
                    <h1 className={classes.appleSubscriptionCancellationPageTitle}>
                        {Translator.trans(
                            /**@Desc("How to cancel Apple Subscription?")*/ 'cancel-subscription.apple.title',
                        )}
                    </h1>
                    <p
                        className={classes.appleSubscriptionCancellationPageDescription}
                        dangerouslySetInnerHTML={{
                            __html: Translator.trans(
                                /**@Desc("To cancel your AwardWallet subscription through Apple, open your list of active Apple subscriptions, locate AwardWallet, and select "Cancel Subscription".")*/ 'cancel-subscription.apple.description',
                            ),
                        }}
                    />
                    <div className={classes.appleSubscriptionCancellationPageSeparator} />

                    <p className={classes.appleSubscriptionCancellationPageApology}>
                        {Translator.trans(
                            /**@Desc("We apologize for any inconvenience, but Apple does not allow us to cancel subscriptions on your behalf.")*/ 'cancel-subscription.apple.apology',
                        )}
                    </p>
                    <SecondaryButton
                        text={
                            <>
                                <AppleIcon className={classes.appleSubscriptionCancellationPageAppleIcon} />
                                {Translator.trans(
                                    /**@Desc("Go to My Active Apple Subscriptions")*/ 'cancel-subscription.apple.button',
                                )}
                            </>
                        }
                        className={{
                            text: classes.appleSubscriptionCancellationPageButtonText,
                            button: classes.appleSubscriptionCancellationPageButton,
                        }}
                        onClick={onRedirectToAppleAccount}
                    />
                </div>
            </div>
        </AppSettingsProvider>
    );
}
