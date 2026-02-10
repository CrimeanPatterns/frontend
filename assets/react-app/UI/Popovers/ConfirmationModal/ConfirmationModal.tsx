import { Modal, ModalMobileViewOption, ModalProps } from '..';
import { PrimaryButton } from '@UI/Buttons/PrimaryButton';
import { SecondaryButton } from '@UI/Buttons/SecondaryButton';
import { Translator } from '@Services/Translator';
import React from 'react';
import classes from './ConfirmationModal.module.scss';

interface ConfirmationProps extends ModalProps {
    open: boolean;
    onConfirm?: () => void;
    isLoading?: boolean;
    descriptionText?: string;
    isConfirmationButtonDisabled?: boolean;
    titleText?: string;
    cancelButtonText?: string;
    confirmationButtonText?: string;
}

export function ConfirmationModal({
    open,
    onClose,
    onConfirm,
    isLoading,
    descriptionText,
    isConfirmationButtonDisabled,
    titleText = Translator.trans('status.please-confirm'),
    cancelButtonText = Translator.trans('alerts.btn.cancel'),
    confirmationButtonText = Translator.trans('alerts.btn.ok'),
}: ConfirmationProps) {
    return (
        <Modal open={open} onClose={onClose} blockInteraction={isLoading} mobileView={ModalMobileViewOption.Centered}>
            <div className={classes.conformationModal}>
                <h3 className={classes.conformationModalTitle}>{titleText}</h3>
                {descriptionText && <p className={classes.conformationModalDescription}>{descriptionText}</p>}
                <div className={classes.conformationModalButtonContainer}>
                    <SecondaryButton
                        text={cancelButtonText}
                        className={{ button: classes.conformationModalButton }}
                        onClick={onClose}
                    />
                    <PrimaryButton
                        text={confirmationButtonText}
                        type="button"
                        className={{ button: classes.conformationModalButton }}
                        onClick={onConfirm}
                        loading={isLoading}
                        disabled={isConfirmationButtonDisabled}
                    />
                </div>
            </div>
        </Modal>
    );
}
