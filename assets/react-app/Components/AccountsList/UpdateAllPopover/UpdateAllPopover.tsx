import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { CircularProgress } from '../../../UI/Feedback/CircleProgress/CircleProgress';
import { Modal, ModalMobileViewOption } from '@UI/Popovers/Modal';
import { PrimaryButton } from '@UI/Buttons/PrimaryButton';
import { SecondaryButton } from '@UI/Buttons/SecondaryButton';
import { TextButton } from '@UI/Buttons/TextButton';
import { Translator } from '@Services/Translator';
import { createPortal } from 'react-dom';
import Logo from '../../../Assets/Images/aw-logo.png';
import LogoRetina from '../../../Assets/Images/aw-logo@2x.png';
import React, { useEffect, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './UpdateAllPopover.module.scss';

type UpdateAllAccountsConfirmationModalProps = {
    open: boolean;
    outdatedAccountsCount: number;
    allAccountsCount: number;
    onClose: () => void;
    onUpdateOutdatedAccounts: () => void;
    onUpdateAllAccounts: () => void;
};

export default function UpdateAllAccountsConfirmationModal({
    open,
    onClose,
    outdatedAccountsCount,
    allAccountsCount,
    onUpdateOutdatedAccounts,
    onUpdateAllAccounts,
}: UpdateAllAccountsConfirmationModalProps) {
    const [outdatedAccountPercent, setOutdatedAccountPercent] = useState(0);

    const isBigModalRef = useRef(allAccountsCount > 20 && outdatedAccountsCount > 0);

    const popoverDescriptionClasses = classNames(classes.UpdateAllPopoverDescription, {
        [classes.UpdateAllPopoverDescriptionSmall as string]: !isBigModalRef,
    });

    useEffect(() => {
        setOutdatedAccountPercent(allAccountsCount > 0 ? (outdatedAccountsCount / allAccountsCount) * 100 : 0);
    }, [outdatedAccountsCount, allAccountsCount]);
    useEffect(() => {
        isBigModalRef.current = allAccountsCount > 20 && outdatedAccountsCount > 0;
    }, [allAccountsCount, outdatedAccountsCount]);

    return createPortal(
        <AppSettingsProvider>
            <Modal open={open} onClose={onClose} mobileView={ModalMobileViewOption.Centered}>
                {isBigModalRef.current && (
                    <div className={classes.UpdateAllPopoverCircleProgressContainer}>
                        <CircularProgress percent={outdatedAccountPercent} radius={40}>
                            <div className={classes.UpdateAllPopoverIconContainer}>
                                <img src={Logo} srcSet={`${Logo} 1x, ${LogoRetina} 2x`}></img>
                            </div>
                        </CircularProgress>
                    </div>
                )}
                <div className={classes.UpdateAllPopoverContentContainer}>
                    {isBigModalRef.current && (
                        <>
                            <h2 className={classes.UpdateAllPopoverTitle}>
                                {Translator.trans('update-accounts.title-outdated', {}, 'mobile-native')}
                            </h2>
                            <p className={popoverDescriptionClasses}>
                                {Translator.trans('update-accounts.description-outdated', {}, 'mobile-native')}
                            </p>
                            <div className={classes.UpdateAllPopoverAccountsCountersContainer}>
                                <div className={classes.UpdateAllPopoverAccountsCounter}>
                                    <span className={classes.UpdateAllPopoverAccountsCounterNumber}>
                                        {allAccountsCount}
                                    </span>
                                    <span
                                        className={classNames(
                                            classes.UpdateAllPopoverAccountsCounterName,
                                            classes.UpdateAllPopoverAccountsCounterNameShort,
                                        )}
                                    >
                                        {Translator.trans('update-accounts.total-accounts', {}, 'mobile-native')}
                                    </span>
                                </div>
                                <div className={classes.UpdateAllPopoverAccountsCounter}>
                                    <span className={classes.UpdateAllPopoverAccountsCounterNumber}>
                                        {outdatedAccountsCount}
                                    </span>
                                    <span className={classes.UpdateAllPopoverAccountsCounterName}>
                                        {Translator.trans('update-accounts.outdated-accounts', {}, 'mobile-native')}
                                    </span>
                                </div>
                            </div>
                        </>
                    )}

                    {!isBigModalRef.current && (
                        <>
                            <h2 className={classes.UpdateAllPopoverTitle}>
                                {Translator.trans('account.buttons.update-all', {}, 'mobile-native')}
                            </h2>
                            <p className={classes.UpdateAllPopoverDescription}>
                                {allAccountsCount > 0 &&
                                    Translator.trans('update-accounts.description-non-outdated', {}, 'mobile-native')}
                                {allAccountsCount === 0 &&
                                    Translator.trans('update-accounts.title-empty', {}, 'mobile-native')}
                            </p>
                        </>
                    )}
                    <div className={classes.UpdateAllPopoverButtonsContainer}>
                        {allAccountsCount > 0 && (
                            <>
                                {isBigModalRef.current && (
                                    <SecondaryButton
                                        text={Translator.trans(
                                            'update-accounts.outdated-accounts-counter',
                                            { counter: outdatedAccountsCount },
                                            'mobile-native',
                                        )}
                                        onClick={onUpdateOutdatedAccounts}
                                    />
                                )}
                                <PrimaryButton
                                    text={Translator.trans(
                                        'update-accounts.total-accounts-counter',
                                        { counter: allAccountsCount },
                                        'mobile-native',
                                    )}
                                    onClick={onUpdateAllAccounts}
                                ></PrimaryButton>
                            </>
                        )}
                        <TextButton
                            onClick={onClose}
                            text={Translator.trans('alerts.btn.cancel')}
                            className={{ button: classes.UpdateAllPopoverCancelButton }}
                        />
                    </div>
                </div>
            </Modal>
        </AppSettingsProvider>,
        document.body,
    );
}
