import { Shadow } from '@UI/Layout/Shadow/Shadow';
import React, {
    ComponentType,
    PropsWithChildren,
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from 'react';

type ModalManagerContext = {
    addModal: <T extends ModalProps>(modal: ModalItem<T>) => void;
    updateProps: (id: string, props: ModalProps) => void;
    closeModal: (id: string) => void;
};
const ModalManagerContext = createContext<ModalManagerContext | undefined>(undefined);

interface ModalProps {
    [key: string]: unknown;
}

export enum ModalPriority {
    Low = 0,
    Medium = 1,
    High = 2,
}

export type ModalItem<T> = {
    modalComponent: ComponentType<T>;
    id: string;
    props: T;
    priority: ModalPriority;
};

export const ModalManagerProvider = ({ children }: PropsWithChildren) => {
    const [modals, setModals] = useState<ModalItem<ModalProps>[]>([]);

    const addModal = useCallback(<T extends ModalProps>(modal: ModalItem<T>) => {
        function sortModalsByPriority(a: ModalItem<T>, b: ModalItem<T>): number {
            return a.priority - b.priority;
        }
        // @ts-expect-error TS can't infer the type correctly
        setModals((prevModals) => [...prevModals, modal].sort(sortModalsByPriority));
    }, []);

    const updateProps = useCallback((id: string, props: ModalProps) => {
        setModals((prev) => {
            const newModals = prev.map((modal) => {
                if (modal.id === id) {
                    return { ...modal, props };
                }
                return modal;
            });

            return newModals;
        });
    }, []);

    const closeModal = useCallback((id: string) => {
        setModals((prevModals) => {
            return prevModals.filter((modal) => modal.id !== id);
        });
    }, []);

    const contextValue = useMemo(() => ({ addModal, closeModal, updateProps }), []);

    useEffect(() => {
        if (modals.length > 0) {
            const scrollWidth = window.innerWidth - document.documentElement.clientWidth;

            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = `${scrollWidth}px`;
        } else {
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = `0`;
        }

        return () => {
            document.body.style.overflow = 'auto';
        };
    }, [modals]);

    return (
        <ModalManagerContext.Provider value={contextValue}>
            <Shadow show={modals.length > 0}>
                {modals.map((modal) => {
                    const Modal = modal.modalComponent;
                    return <Modal key={modal.id} {...modal.props} />;
                })}
            </Shadow>

            {children}
        </ModalManagerContext.Provider>
    );
};

export function useModalManager() {
    const context = useContext(ModalManagerContext);
    if (context === undefined) {
        throw new Error('useModalManager must be used within a ModalManagerProvider');
    }
    return context;
}
