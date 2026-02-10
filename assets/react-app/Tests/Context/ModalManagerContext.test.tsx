import { ModalManagerProvider, ModalPriority, useModalManager } from '@Root/Contexts/ModalManagerContext';
import { act, render as originalRender, renderHook } from '@testing-library/react';
import { screen } from '../TestUtils';
import React from 'react';

describe('ModalManagerContext', () => {
    beforeAll(() => {
        jest.spyOn(console, 'warn').mockImplementation(() => {});
    });

    test("shouldn't change DOM, when there is no modal", () => {
        const { container } = originalRender(<ModalManagerProvider />);

        expect(container.firstChild).toBeNull();

        expect(container).toBeEmptyDOMElement();
    });

    test('should add modal component', () => {
        const { result } = renderHook(() => useModalManager(), {
            wrapper: ModalManagerProvider,
        });
        const Modal = () => <div>Modal Window</div>;

        act(() => {
            result.current.addModal({ modalComponent: Modal, id: '1', priority: ModalPriority.Low, props: {} });
        });

        const modal = screen.getByText(/modal window/i);

        expect(modal).toBeInTheDocument();
    });

    describe('body scroll', () => {
        test('be locked', () => {
            const { result } = renderHook(() => useModalManager(), {
                wrapper: ModalManagerProvider,
            });
            const Modal = () => <div>Modal Window</div>;

            act(() => {
                result.current.addModal({ modalComponent: Modal, id: '1', priority: ModalPriority.Low, props: {} });
            });

            const computedStyle = window.getComputedStyle(document.body);

            expect(computedStyle.overflow).toBe('hidden');
        });

        test('be unlocked', () => {
            const { result } = renderHook(() => useModalManager(), {
                wrapper: ModalManagerProvider,
            });
            const Modal = () => <div>Modal Window</div>;

            act(() => {
                result.current.addModal({ modalComponent: Modal, id: '1', priority: ModalPriority.Low, props: {} });
            });

            act(() => {
                result.current.closeModal('1');
            });

            const computedStyle = window.getComputedStyle(document.body);

            expect(computedStyle.overflow).toBe('auto');
        });
    });

    test('should update modal component props', () => {
        const { result } = renderHook(() => useModalManager(), {
            wrapper: ModalManagerProvider,
        });
        const Modal = ({ count }: { count: number }) => <div>Modal Window {count}</div>;

        act(() => {
            result.current.addModal({
                modalComponent: Modal,
                id: '1',
                priority: ModalPriority.Low,
                props: { count: 0 },
            });

            result.current.updateProps('1', {
                count: 1,
            });
        });

        const modal = screen.getByText(/1/i);

        expect(modal).toBeInTheDocument();
    });

    test('should close modal', () => {
        const { result } = renderHook(() => useModalManager(), {
            wrapper: ModalManagerProvider,
        });
        const Modal = ({ count }: { count: number }) => <div>Modal Window {count}</div>;

        act(() => {
            result.current.addModal({
                modalComponent: Modal,
                id: '1',
                priority: ModalPriority.Low,
                props: { count: 0 },
            });

            result.current.closeModal('1');
        });

        const modal = screen.queryByText(/0/i);

        expect(modal).not.toBeInTheDocument();
    });

    test('should close modal', () => {
        const { result } = renderHook(() => useModalManager(), {
            wrapper: ModalManagerProvider,
        });
        const Modal = ({ count }: { count: number }) => <div>Modal Window {count}</div>;

        act(() => {
            result.current.addModal({
                modalComponent: Modal,
                id: '1',
                priority: ModalPriority.Low,
                props: { count: 0 },
            });

            result.current.closeModal('1');
        });

        const modal = screen.queryByText(/0/i);

        expect(modal).not.toBeInTheDocument();
    });

    describe('with many modals', () => {
        test('render one shadow', () => {
            const { result } = renderHook(() => useModalManager(), {
                wrapper: ModalManagerProvider,
            });
            const Modal = ({ count }: { count: number }) => <div>Modal Window {count}</div>;

            act(() => {
                result.current.addModal({
                    modalComponent: Modal,
                    id: '1',
                    priority: ModalPriority.Low,
                    props: { count: 0 },
                });
            });

            const shadowElements = document.body.querySelectorAll('.shadow');

            expect(shadowElements.length).toBe(1);
        });

        test('save adding modal order', () => {
            const { result } = renderHook(() => useModalManager(), {
                wrapper: ModalManagerProvider,
            });
            const Modal = ({ count }: { count: number }) => <div>Modal Window {count}</div>;

            act(() => {
                result.current.addModal({
                    modalComponent: Modal,
                    id: '1',
                    priority: ModalPriority.Low,
                    props: { count: 0 },
                });

                result.current.addModal({
                    modalComponent: Modal,
                    id: '2',
                    priority: ModalPriority.Low,
                    props: { count: 1 },
                });

                result.current.addModal({
                    modalComponent: Modal,
                    id: '3',
                    priority: ModalPriority.Low,
                    props: { count: 2 },
                });
            });

            const modalWindows = screen.getAllByText(/Modal Window/i);

            //Check the order of modals
            modalWindows.forEach((modal, index) => {
                expect(modal.textContent?.includes(String(index))).toBeTruthy();
            });
        });

        test('correct order with priority', () => {
            const { result } = renderHook(() => useModalManager(), {
                wrapper: ModalManagerProvider,
            });
            const Modal = ({ count }: { count: number }) => <div>Modal Window {count}</div>;

            act(() => {
                result.current.addModal({
                    modalComponent: Modal,
                    id: '1',
                    priority: ModalPriority.High,
                    props: { count: 0 },
                });

                result.current.addModal({
                    modalComponent: Modal,
                    id: '2',
                    priority: ModalPriority.Medium,
                    props: { count: 1 },
                });

                result.current.addModal({
                    modalComponent: Modal,
                    id: '3',
                    priority: ModalPriority.Low,
                    props: { count: 2 },
                });

                result.current.addModal({
                    modalComponent: Modal,
                    id: '4',
                    priority: ModalPriority.Low,
                    props: { count: 3 },
                });
            });

            const expectedModalOrder = ['2', '3', '1', '0'];

            const modalWindows = screen.getAllByText(/Modal Window/i);

            //Check the order of modals
            modalWindows.forEach((modal, index) => {
                const expectedModal = expectedModalOrder[index];

                if (!expectedModal) {
                    throw new Error('Expected modal order is set incorrectly');
                }

                expect(modal.textContent?.includes(expectedModal)).toBeTruthy();
            });
        });
    });

    test('should correct handle complicated scenario', () => {
        const { result } = renderHook(() => useModalManager(), {
            wrapper: ModalManagerProvider,
        });
        const Modal = ({ count }: { count: number }) => <div>Modal Window {count}</div>;

        act(() => {
            result.current.addModal({
                modalComponent: Modal,
                id: '1',
                priority: ModalPriority.High,
                props: { count: 0 },
            });

            result.current.addModal({
                modalComponent: Modal,
                id: '2',
                priority: ModalPriority.Medium,
                props: { count: 1 },
            });

            result.current.addModal({
                modalComponent: Modal,
                id: '3',
                priority: ModalPriority.Low,
                props: { count: 2 },
            });

            result.current.addModal({
                modalComponent: Modal,
                id: '4',
                priority: ModalPriority.Low,
                props: { count: 3 },
            });

            result.current.addModal({
                modalComponent: Modal,
                id: '5',
                priority: ModalPriority.Low,
                props: { count: 4 },
            });

            result.current.addModal({
                modalComponent: Modal,
                id: '6',
                priority: ModalPriority.High,
                props: { count: 5 },
            });

            result.current.addModal({
                modalComponent: Modal,
                id: '7',
                priority: ModalPriority.High,
                props: { count: 6 },
            });

            result.current.closeModal('4');
            result.current.closeModal('1');
        });

        const expectedModalOrder = ['2', '4', '1', '5', '6'];

        const modalWindows = screen.getAllByText(/Modal Window/i);

        //Check the order of modals
        modalWindows.forEach((modal, index) => {
            const expectedModal = expectedModalOrder[index];

            if (!expectedModal) {
                throw new Error('Expected modal order is set incorrectly');
            }

            expect(modal.textContent?.includes(expectedModal)).toBeTruthy();
        });
    });
});
