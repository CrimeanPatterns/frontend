import { ConfirmationModal } from '@UI/Popovers';
import { Router } from '@Services/Router';
import { SecondaryButton } from '@UI/Buttons/SecondaryButton';
import { Translator } from '@Services/Translator';
import { useCancelSubscription } from '../../Hook/UseCancelSubcription';
import React, { useState } from 'react';
import classes from './CancellationView.module.scss';

type CancellationViewProps = {
    afterCancellationCallback: () => void;
    userInfo: string;
    canCancel: string | undefined;
    manualCancellation: string | undefined;
    cancelButtonLabel: string | undefined;
    isAT201: boolean;
    confirmationTitle?: string;
    confirmationBody?: string;
    confirmationButtonNo?: string;
    confirmationButtonYes?: string;
};

export function CancellationView({
    afterCancellationCallback,
    userInfo,
    canCancel,
    manualCancellation,
    cancelButtonLabel,
    isAT201,
    confirmationTitle,
    confirmationBody,
    confirmationButtonNo,
    confirmationButtonYes
}: CancellationViewProps) {
    const { cancelSubscription, isPending } = useCancelSubscription(afterCancellationCallback);

    const [isConfirmationModalOpen, setIsConfirmationModalOpen] = useState(false);

    const openConfirmationModal = () => {
        setIsConfirmationModalOpen(true);
    };

    const closeConfirmationModal = () => {
        setIsConfirmationModalOpen(false);
    };

    const onConfirmCancellation = () => {
        closeConfirmationModal();
        cancelSubscription();
    };

    const onUnsubscribe = () => {
        if (canCancel === 'true') {
            openConfirmationModal();
            return;
        }
        if (manualCancellation === 'true') {
            window.location.href = Router.generate('aw_user_applesubscription_cancel');
        }
    };

    const redirectToAccountList = () => {
        window.location.href = Router.generate('aw_account_list');
    };

    return (
        <div className={classes.cancellation}>
            <h1 className={classes.cancellationTitle}>{Translator.trans('cancel-subscription.title')}</h1>
            {(canCancel !== 'true' && manualCancellation !== 'true') || isAT201 ? (
                <p className={classes.cancellationDescription} dangerouslySetInnerHTML={{ __html: userInfo }} />
            ) : (
                <>
                    <p
                        className={classes.cancellationDescription}
                        dangerouslySetInnerHTML={{
                            __html: Translator.trans(
                                /**@Desc("Before you cancel your AwardWallet Plus membership, take a moment to consider %link_on%all the valuable benefits%link_off% you’ll lose, such as:")*/ 'cancel-subscription.awplus.before.cancelling',
                                {
                                    link_on: `<a href="${Router.generate('aw_pricing')}">`,
                                    link_off: `</a>`,
                                },
                            ),
                        }}
                    />
                    <ul className={classes.cancellationBenefitsList}>
                        <li className={classes.cancellationBenefitsItem}>
                            {Translator.trans('pricing.comparison.statement2')}
                        </li>
                        <li className={classes.cancellationBenefitsItem}>
                            {Translator.trans('pricing.comparison.statement7')}
                        </li>
                        <li className={classes.cancellationBenefitsItem}>
                            {Translator.trans(
                                /**@Desc("Unlimited number of updates per loyalty account per day")*/ 'cancel-subscription.awplus.benefit.unlimited.updates',
                            )}
                            .
                        </li>
                    </ul>
                    <p className={classes.cancellationDescription}>
                        {Translator.trans(
                            /**@Desc("Your AwardWallet Plus membership helps you get the most out of your rewards and keeps your travels and
                loyalty accounts perfectly organized. If you’re sure about canceling, click the button below. But we’d
                love to keep you on board and continue helping you achieve your travel goals!")*/ 'cancel-subscription.awplus.last.words',
                        )}
                    </p>
                </>
            )}

            {(canCancel === 'true' || manualCancellation === 'true') && (
                <SecondaryButton
                    className={{ button: classes.cancellationButton }}
                    loading={isPending}
                    text={cancelButtonLabel || ''}
                    onClick={onUnsubscribe}
                />
            )}
            {canCancel !== 'true' && manualCancellation !== 'true' && (
                <SecondaryButton
                    className={{ button: classes.cancellationButton }}
                    text={Translator.trans('button.back')}
                    onClick={redirectToAccountList}
                />
            )}
            <ConfirmationModal
                open={isConfirmationModalOpen}
                onClose={closeConfirmationModal}
                onConfirm={onConfirmCancellation}
                titleText={
                    confirmationTitle || (
                        isAT201
                            ? Translator.trans(/**@Desc("Cancel AwardTravel 201")*/ 'cancel-subscription.popup.title.at201')
                            : Translator.trans(
                                  /**@Desc("Cancel AwardWallet Plus")*/
                                  'cancel-subscription.confirmation.popup.title.awplus',
                              )
                    )
                }
                descriptionText={
                    confirmationBody || (
                        Translator.trans(
                            /**@Desc("Are you sure you want to cancel your %subscription_type% subscription?")*/ 'cancel-subscription.confirmation.text',
                            {
                                subscription_type: isAT201 ? 'AwardTravel 201' : 'AwardWallet Plus',
                            },
                        )
                    )
                }
                cancelButtonText={
                    confirmationButtonNo || Translator.trans('button.back')
                }
                confirmationButtonText={
                    confirmationButtonYes || Translator.trans('yes.cancel')
                }
            />
        </div>
    );
}
