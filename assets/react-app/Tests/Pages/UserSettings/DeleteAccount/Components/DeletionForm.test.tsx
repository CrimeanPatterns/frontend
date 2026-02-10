import { DeletionForm } from '@Root/Pages/UserSettings/DeleteAccount/Components/DeletionForm';
import { Translator } from '@Services/Translator';
import { act, fireEvent, render, screen, waitFor } from '../../../../TestUtils';
import { axios } from '@Services/Axios';
import MockAdapter from 'axios-mock-adapter';
import React from 'react';

describe('DeletionForm', () => {
    let mock = new MockAdapter(axios);

    beforeAll(() => {
        jest.spyOn(console, 'warn').mockImplementation(() => {});
        mock = new MockAdapter(axios);
    });

    afterEach(() => {
        mock.reset();
    });

    afterAll(() => {
        mock.restore();
    });

    test('should render', () => {
        render(<DeletionForm isBusinessArea={false} />);

        const deletionForm = screen.getByLabelText(Translator.trans('deletion-form'));

        expect(deletionForm).toBeInTheDocument();
    });

    test('should show error, when delete button is clicked and input is empty', async () => {
        render(<DeletionForm isBusinessArea={false} />);

        const deleteButton = screen.getByRole('button');
        const reasonInput = screen.getByRole('textbox');

        act(() => {
            fireEvent.click(deleteButton);
        });

        const errorMessage = await screen.findByText(Translator.trans('notblank', {}, 'validators'));

        expect(reasonInput).toHaveFocus();
        expect(errorMessage).toBeInTheDocument();
    });

    test('should show confirmation modal, when delete button is clicked and input has value', async () => {
        render(<DeletionForm isBusinessArea={false} />);

        const deleteButton = screen.getByRole('button');
        const reasonInput = screen.getByRole('textbox');

        act(() => {
            fireEvent.input(reasonInput, { target: { value: 'a' } });
            fireEvent.click(deleteButton);
        });

        const confirmationModal = await screen.findByText(Translator.trans('user.delete.confirm-text'));

        expect(confirmationModal).toBeInTheDocument();
    });

    test('should show button loading state, when OK button is clicked in confirmation modal', async () => {
        render(<DeletionForm isBusinessArea={false} />);

        const deleteButton = screen.getByRole('button');
        const reasonInput = screen.getByRole('textbox');

        act(() => {
            fireEvent.input(reasonInput, { target: { value: 'a' } });
            fireEvent.click(deleteButton);
        });

        const confirmationButton = await screen.findByText(Translator.trans('alerts.btn.ok'));

        act(() => {
            fireEvent.click(confirmationButton);
        });

        const deleteButtonLoader = screen.getByRole('progressbar');

        expect(deleteButtonLoader).toBeInTheDocument();
    });

    test('should close confirmation model, when cancel button is clicked in confirmation modal', async () => {
        render(<DeletionForm isBusinessArea={false} />);

        const deleteButton = screen.getByRole('button');
        const reasonInput = screen.getByRole('textbox');

        act(() => {
            fireEvent.input(reasonInput, { target: { value: 'a' } });
            fireEvent.click(deleteButton);
        });

        const cancelationButton = await screen.findByText(Translator.trans('alerts.btn.cancel'));
        const confirmationModal = await screen.findByText(Translator.trans('user.delete.confirm-text'));

        act(() => {
            fireEvent.click(cancelationButton);
        });

        await waitFor(() => {
            expect(confirmationModal).not.toBeInTheDocument();
        });
    });

    test('should redirect after successfully deleting', async () => {
        mock.onPost('/user/delete').reply(() => {
            return [
                200,
                {
                    success: true,
                    isAppleSubscriber: false,
                },
            ];
        });

        render(<DeletionForm isBusinessArea={false} />);

        const deleteButton = screen.getByRole('button');
        const reasonInput = screen.getByRole('textbox');

        act(() => {
            fireEvent.input(reasonInput, { target: { value: 'a' } });
            fireEvent.click(deleteButton);
        });

        const confirmationButton = await screen.findByText(Translator.trans('alerts.btn.ok'));

        Object.defineProperty(window, 'location', {
            writable: true,
            value: { href: 'http://localhost/', assign: jest.fn() },
        });
        const originalHref = window.location.href;

        act(() => {
            fireEvent.click(confirmationButton);
        });

        await waitFor(() => {
            expect(window.location.href).not.toEqual(originalHref);
        });
    });

    test('should show server error after failing deletion', async () => {
        mock.onPost('/user/delete').reply(() => {
            return [
                200,
                {
                    error: 'Server Error!',
                },
            ];
        });

        render(<DeletionForm isBusinessArea={false} />);

        const deleteButton = screen.getByRole('button');
        const reasonInput = screen.getByRole('textbox');

        act(() => {
            fireEvent.input(reasonInput, { target: { value: 'a' } });
            fireEvent.click(deleteButton);
        });

        const confirmationButton = await screen.findByText(Translator.trans('alerts.btn.ok'));

        act(() => {
            fireEvent.click(confirmationButton);
        });

        const serverError = await screen.findByText('Server Error!');

        expect(serverError).toBeInTheDocument();
    });
});
