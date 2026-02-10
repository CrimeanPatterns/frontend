import { ReauthContext, ReauthRequiredEventData } from '@Services/Axios/Reauth/ReauthInterceptor';
import { ReauthService } from '@Services/Axios/Reauth/ReauthService';
import { Translator } from '@Services/Translator';
import { act, fireEvent, render, screen, waitFor } from '../../TestUtils';
import { reauthEventManager } from '@Services/Event/ReauthEvents';
import React from 'react';

describe('ReauthService', () => {
    beforeAll(() => {
        jest.spyOn(console, 'warn').mockImplementation(() => {});
    });

    test("shouldn't change DOM, when modal is close", () => {
        const { container } = render(<ReauthService />);

        expect(container.firstChild).toBeNull();

        expect(container).toBeEmptyDOMElement();
    });

    describe('should open modal with initial state, if reauth required event is called', () => {
        test('context code (submit button is disabled, input value is empty, resend button is active)', () => {
            render(<ReauthService />);

            act(() => {
                reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                    onCancel() {},
                    onResend() {},
                    onSubmit() {},
                    context: ReauthContext.Code,
                    labelText: 'Label Text',
                });
            });

            const reauthModal = screen.getByText(/Label Text/i);
            const submitButton = screen.getByText(Translator.trans('form.button.submit')).closest('button');
            const resentButton = screen.getByText(Translator.trans('button.resend')).closest('button');
            const inputElement = screen.getByRole('textbox');

            expect(reauthModal).toBeInTheDocument();

            expect(submitButton).toBeInTheDocument();
            expect(submitButton).toBeDisabled();

            expect(resentButton).toBeInTheDocument();
            expect(resentButton).not.toBeDisabled();

            expect(inputElement).toBeInTheDocument();
            expect(inputElement).toHaveValue('');
        });

        test('context password (submit button is disabled, input should be empty, resend should not be in document)', () => {
            render(<ReauthService />);

            act(() => {
                reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                    onCancel() {},
                    onResend() {},
                    onSubmit() {},
                    context: ReauthContext.Password,
                });
            });

            const reauthModal = screen.getByText(Translator.trans('provide-aw-password'));
            const submitButton = screen.getByText(Translator.trans('form.button.submit')).closest('button');
            const resendButton = screen.queryByText(Translator.trans('button.resend'));
            const passwordInput = screen.getByRole('textbox');

            expect(reauthModal).toBeInTheDocument();

            expect(submitButton).toBeInTheDocument();
            expect(submitButton).toBeDisabled();

            expect(resendButton).not.toBeInTheDocument();

            expect(passwordInput).toBeInTheDocument();
            expect(passwordInput).toHaveValue('');
        });
    });

    test('should remove disabled from submit button, if text is inserted in input', () => {
        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Password,
            });
        });

        const input = screen.getByRole('textbox');
        const submitButton = screen.getByText(Translator.trans('form.button.submit')).closest('button');

        fireEvent.change(input, { target: { value: 'a' } });

        expect(input).toHaveValue('a');
        expect(submitButton).toBeInTheDocument();
        expect(submitButton).not.toBeDisabled();
    });

    test('should set disable to submit button, if input value became empty ', () => {
        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Password,
            });
        });

        const input = screen.getByRole('textbox');
        const submitButton = screen.getByText(Translator.trans('form.button.submit')).closest('button');

        fireEvent.change(input, { target: { value: 'a' } });
        fireEvent.change(input, { target: { value: '' } });

        expect(input).toHaveValue('');
        expect(submitButton).toBeInTheDocument();
        expect(submitButton).toBeDisabled();
    });

    test('should call onSubmit, when the button is clicked and there is value in input', () => {
        const mockOnSubmit = jest.fn();

        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend() {},
                onSubmit: mockOnSubmit,
                context: ReauthContext.Password,
            });
        });

        const input = screen.getByRole('textbox');
        const submitButton = screen.getByRole('button', { name: Translator.trans('form.button.submit') });

        fireEvent.change(input, { target: { value: 'a' } });

        fireEvent.click(submitButton);

        expect(mockOnSubmit).toHaveBeenCalledTimes(1);
    });

    test('should call onResend, when the resend button is clicked', () => {
        const mockOnResend = jest.fn();

        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend: mockOnResend,
                onSubmit() {},
                context: ReauthContext.Code,
            });
        });

        const resendButton = screen.getByRole('button', { name: Translator.trans('button.resend') });

        fireEvent.click(resendButton);

        expect(mockOnResend).toHaveBeenCalledTimes(1);
    });

    test('should call onCancel and remove reauth modal', async () => {
        const mockOnCancel = jest.fn();

        const { container } = render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel: mockOnCancel,
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Code,
            });
        });

        const closeButton = screen.getByLabelText(Translator.trans('close-button'));

        fireEvent.click(closeButton);

        await waitFor(() => {
            expect(mockOnCancel).toHaveBeenCalledTimes(1);
            if (container.firstChild) {
                fireEvent.animationEnd(container.firstChild);
            }
            expect(container.firstChild).toBeNull();
            expect(container).toBeEmptyDOMElement();
        });
    });

    test('should show error after error event', () => {
        const mockOnCancel = jest.fn();

        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel: mockOnCancel,
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Code,
            });
        });

        act(() => {
            reauthEventManager.publish<string>(reauthEventManager.getEventNames().reauthError, 'Error!');
        });

        const errorBlock = screen.getByText(/Error!/i);
        const submitButton = screen.getByRole('button', { name: Translator.trans('form.button.submit') });

        expect(errorBlock).toBeInTheDocument();
        expect(submitButton).toBeInTheDocument();
        expect(submitButton).toBeDisabled();
    });

    test('should show loading submit button state after being clicked', () => {
        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Password,
            });
        });

        const input = screen.getByRole('textbox');
        const submitButton = screen.getByRole('button', { name: Translator.trans('form.button.submit') });

        fireEvent.change(input, { target: { value: 'a' } });

        fireEvent.click(submitButton);

        const submitButtonLoader = screen.getByRole('progressbar');

        expect(submitButton).toBeInTheDocument();
        expect(submitButtonLoader).toBeInTheDocument();
    });

    test('should remove loading submit button state, when error action is dispatched', () => {
        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Password,
            });
        });

        const input = screen.getByRole('textbox');
        const submitButton = screen.getByRole('button', { name: Translator.trans('form.button.submit') });

        fireEvent.change(input, { target: { value: 'a' } });

        fireEvent.click(submitButton);

        act(() => {
            reauthEventManager.publish<string>(reauthEventManager.getEventNames().reauthError, 'Error!');
        });

        const submitButtonLoader = screen.queryByRole('progressbar');

        expect(submitButton).toBeInTheDocument();
        expect(submitButtonLoader).not.toBeInTheDocument();
    });

    test('should show loading resend button state', () => {
        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Code,
            });
        });

        const resendButton = screen.getByRole('button', { name: Translator.trans('button.resend') });

        fireEvent.click(resendButton);

        const submitButtonLoader = screen.getByRole('progressbar');

        expect(resendButton).toBeInTheDocument();
        expect(submitButtonLoader).toBeInTheDocument();
    });

    test('should remove loading submit button state, when error action is dispatched', () => {
        render(<ReauthService />);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Code,
            });
        });

        const resendButton = screen.getByRole('button', { name: Translator.trans('button.resend') });

        fireEvent.click(resendButton);

        act(() => {
            reauthEventManager.publish<ReauthRequiredEventData>(reauthEventManager.getEventNames().reauthRequired, {
                onCancel() {},
                onResend() {},
                onSubmit() {},
                context: ReauthContext.Code,
            });
        });

        const submitButtonLoader = screen.queryByRole('progressbar');

        expect(resendButton).toBeInTheDocument();
        expect(submitButtonLoader).not.toBeInTheDocument();
    });
});
