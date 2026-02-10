import { ConfirmationModal } from '@UI/Popovers/ConfirmationModal';
import { Controller, ControllerFieldState, ControllerRenderProps, useForm } from 'react-hook-form';
import { SecondaryButton } from '@UI/Buttons/SecondaryButton';
import { TextInput } from '@UI/Inputs/TextInput';
import { Translator } from '@Services/Translator';
import { axios } from '@Services/Axios';
import { toast } from '@Utilities/Toast';
import { useMutation } from '@tanstack/react-query';
import React, { useCallback, useMemo, useRef, useState } from 'react';
import classes from './DeletionForm.module.scss';

const Reason_Field_Name = 'reason';
const Reason_Field_Default_Value = '';

interface DeletionForm {
    [Reason_Field_Name]: string;
}

interface DeletionFormProps {
    isBusinessArea: boolean;
}

export function DeletionForm({ isBusinessArea }: DeletionFormProps) {
    const [isConfirmationModalOpen, setIsConfirmationModalOpen] = useState(false);

    const formRef = useRef<HTMLFormElement>(null);
    const deleteButtonText = useMemo(
        () => (isBusinessArea ? Translator.trans('user.delete.business') : Translator.trans('user.delete.personal')),
        [],
    );

    const { control, handleSubmit, trigger, getValues, setFocus, setError } = useForm<DeletionForm>({
        defaultValues: {
            [Reason_Field_Name]: Reason_Field_Default_Value,
        },
    });

    const { mutate: deleteUser, isPending: isDeleting } = useMutation({
        mutationFn: deleteUserRequest,
        onSuccess(data) {
            if (data.success) {
                window.location.href = '/user/deleted' + (data.isAppleSubscriber ? '?isAppleSubscriber=1' : '');
                return;
            }

            if (data.error) {
                setError(Reason_Field_Name, {
                    message: data.error,
                });
                setFocus(Reason_Field_Name);
                onConfirmationModalClose();
                return;
            }
        },
        onError(error) {
            onConfirmationModalClose();

            toast(error.message, { type: 'error', toastId: 'deleteUser' });
        },
    });

    const reasonFieldRender = useCallback(
        ({
            field,
            fieldState,
        }: {
            field: ControllerRenderProps<DeletionForm, typeof Reason_Field_Name>;
            fieldState: ControllerFieldState;
        }) => (
            <TextInput
                {...field}
                value={field.value}
                onChange={async (event) => {
                    field.onChange(event);
                    await trigger(Reason_Field_Name);
                }}
                forbiddenChars="<>"
                classes={{
                    container: classes.deletionFormReasonTextInput,
                }}
                errorText={fieldState.error?.message}
            />
        ),
        [],
    );

    const onConfirmationModalClose = useCallback(() => {
        setIsConfirmationModalOpen(false);
    }, []);

    const onConfirmationButtonClick = useCallback(() => {
        deleteUser(getValues(Reason_Field_Name));
        setIsConfirmationModalOpen(false);
    }, [getValues, deleteUser]);

    const onSubmit = useCallback(() => {
        setIsConfirmationModalOpen(true);
    }, []);

    return (
        <>
            <form aria-label="deletion-form" ref={formRef} onSubmit={handleSubmit(onSubmit)}>
                <label className={classes.deletionFormLabel}>
                    <span className={classes.deletionFormLabelText}>
                        {Translator.trans(/**@Desc("Please Enter Your Feedback:")*/ 'user.delete.enter.feedback')}
                    </span>
                    <Controller
                        name={Reason_Field_Name}
                        control={control}
                        render={reasonFieldRender}
                        rules={{ required: Translator.trans('notblank', undefined, 'validators') }}
                    />
                </label>
                <p className={classes.deletionFormButtonDescription}>
                    {Translator.trans(
                        /**@Desc("By clicking '%button%', you acknowledge that you understand the data deletion and retention policies. Your account and associated data will be permanently removed according to these terms.")*/ 'user.delete.button.description',
                        {
                            button: deleteButtonText,
                        },
                    )}
                </p>

                <SecondaryButton
                    text={deleteButtonText}
                    type="submit"
                    loading={!isConfirmationModalOpen ? isDeleting : false}
                    className={{ button: classes.deletionFormButton }}
                />
            </form>
            <ConfirmationModal
                open={isConfirmationModalOpen}
                onClose={onConfirmationModalClose}
                onConfirm={onConfirmationButtonClick}
                descriptionText={Translator.trans('user.delete.confirm-text')}
                isConfirmationButtonDisabled={isDeleting}
            />
        </>
    );
}

interface DeleteUserResponse {
    success: boolean;
    error?: string;
    isAppleSubscriber: boolean;
}
async function deleteUserRequest(reason: string) {
    return (
        await axios.post<DeleteUserResponse>('/user/delete', {
            reason,
        })
    ).data;
}
