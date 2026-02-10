import { Modal, ModalMobileViewOption } from '@UI/Popovers';
import { PasswordInput } from '@UI/Inputs/PasswordInput';
import { PrimaryButton, SecondaryButton } from '@UI/Buttons';
import { ReauthContext, ReauthRequiredEventData } from '@Services/Axios/Reauth/ReauthInterceptor';
import { TextInput } from '@UI/Inputs/TextInput';
import { Translator } from '@Services/Translator';
import { reauthEventManager } from '@Services/Event/ReauthEvents';
import React, { ChangeEvent, memo, useCallback, useEffect, useRef, useState } from 'react';
import classes from './ReauthenticateService.module.scss';

export const ReauthService = memo(() => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [inputValue, setInputValue] = useState('');
    const [isSubmitButtonDisabled, setIsSubmitButtonDisabled] = useState(true);
    const [isResendButtonDisabled, setIsResendButtonDisabled] = useState(false);

    const [reauthData, setReauthData] = useState<ReauthRequiredEventData | null>(null);
    const [errorMessage, setErrorMessage] = useState('');
    const [isSubmitButtonLoading, setIsSubmitButtonLoading] = useState(false);
    const [isResendButtonLoading, setIsResendButtonLoading] = useState(false);

    const inputRef = useRef<HTMLInputElement>(null);
    const submitButtonRef = useRef<HTMLButtonElement>(null);

    const onCloseModal = useCallback(() => {
        reauthData?.onCancel();
        setIsModalOpen(false);
        resetState();
    }, [reauthData]);

    const resetState = useCallback(() => {
        setInputValue('');
        setErrorMessage('');
        setIsSubmitButtonDisabled(true);
        setIsResendButtonDisabled(false);
        setIsSubmitButtonLoading(false);
    }, []);

    const onInputChange = useCallback((event: ChangeEvent<HTMLInputElement>) => {
        if (event.target.value.length === 0) {
            setIsSubmitButtonDisabled(true);
        } else {
            setIsSubmitButtonDisabled(false);
        }
        setErrorMessage('');
        setInputValue(event.target.value);
    }, []);

    const onEnterKeyPressed = useCallback(() => {
        if (!isSubmitButtonDisabled && submitButtonRef.current) {
            submitButtonRef.current.click();
        }
    }, [isSubmitButtonDisabled, submitButtonRef]);

    const onSubmit = useCallback(() => {
        reauthData?.onSubmit(inputValue);
        setIsSubmitButtonLoading(true);
    }, [inputValue, reauthData]);

    const onResend = useCallback(() => {
        reauthData?.onResend();
        setIsResendButtonLoading(true);
    }, [inputValue, reauthData]);

    useEffect(() => {
        function handleReauthRequired(event: CustomEvent<ReauthRequiredEventData>) {
            setReauthData(event.detail);
            setIsModalOpen(true);
            setIsResendButtonLoading(false);
        }

        function handleReauthError(event: CustomEvent<string | null>) {
            if (event.detail) {
                setErrorMessage(event.detail);
                setIsSubmitButtonDisabled(true);
                setIsSubmitButtonLoading(false);
                return;
            }

            setIsModalOpen(false);
            resetState();
        }

        function handleReauthSuccess() {
            setIsModalOpen(false);
            setIsSubmitButtonLoading(false);
            resetState();
        }

        reauthEventManager.subscribe(reauthEventManager.getEventNames().reauthRequired, handleReauthRequired);

        reauthEventManager.subscribe(reauthEventManager.getEventNames().reauthError, handleReauthError);

        reauthEventManager.subscribe(reauthEventManager.getEventNames().reauthSuccess, handleReauthSuccess);

        return () => {
            reauthEventManager.unsubscribe(reauthEventManager.getEventNames().reauthRequired, handleReauthRequired);

            reauthEventManager.unsubscribe(reauthEventManager.getEventNames().reauthError, handleReauthError);

            reauthEventManager.unsubscribe(reauthEventManager.getEventNames().reauthSuccess, handleReauthSuccess);
        };
    }, []);

    return (
        <Modal
            open={isModalOpen}
            onClose={onCloseModal}
            blockInteraction={isSubmitButtonLoading}
            mobileView={ModalMobileViewOption.Centered}
        >
            <div className={classes.conformationModal}>
                <h3 className={classes.conformationModalTitle}>{Translator.trans('confirm-identity')}</h3>
                <div className={classes.conformationModalInputContainer}>
                    <label className={classes.conformationModalLabel}>
                        {reauthData?.context === ReauthContext.Password
                            ? Translator.trans('provide-aw-password')
                            : reauthData?.labelText}
                    </label>
                    {reauthData?.context === ReauthContext.Password && (
                        <PasswordInput
                            ref={inputRef}
                            value={inputValue}
                            onChange={onInputChange}
                            className={classes.conformationModalInput}
                            onEnter={onEnterKeyPressed}
                            errorText={errorMessage}
                        />
                    )}
                    {reauthData?.context === ReauthContext.Code && (
                        <TextInput
                            ref={inputRef}
                            value={inputValue}
                            onChange={onInputChange}
                            classes={{
                                containerWithError: classes.conformationModalInput,
                            }}
                            onEnter={onEnterKeyPressed}
                            errorText={errorMessage}
                        />
                    )}
                </div>
                <div className={classes.conformationModalButtonContainer}>
                    {reauthData?.context === ReauthContext.Code && (
                        <SecondaryButton
                            text={Translator.trans('button.resend')}
                            type="button"
                            className={{ button: classes.conformationModalButton }}
                            disabled={isResendButtonDisabled}
                            onClick={onResend}
                            loading={isResendButtonLoading}
                        />
                    )}
                    <PrimaryButton
                        text={Translator.trans('form.button.submit')}
                        type="button"
                        className={{ button: classes.conformationModalButton }}
                        ref={submitButtonRef}
                        onClick={onSubmit}
                        disabled={isSubmitButtonDisabled}
                        loading={isSubmitButtonLoading}
                    />
                </div>
            </div>
        </Modal>
    );
});

ReauthService.displayName = 'ReauthService';
