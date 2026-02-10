import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import './toast.scss';

type ToastType = 'success' | 'error' | 'info';

interface ToastOptions {
    message: string;
    type?: ToastType;
    duration?: number;
    showCloseButton?: boolean;
    customContent?: string | HTMLElement;
    onClose?: () => void;
}

export const showToast = ({
    message,
    type = 'info',
    duration = 5000,
    showCloseButton = true,
    customContent,
    onClose,
}: ToastOptions) => {
    const toastContainer = document.createElement('div');
    toastContainer.className = `toast-container ${type}`;

    const sideStrip = document.createElement('div');
    sideStrip.className = `toast-side-strip ${type}`;
    toastContainer.appendChild(sideStrip);

    const contentContainer = document.createElement('div');
    contentContainer.className = 'toast-content';

    if (customContent) {
        const customContentContainer = document.createElement('div');

        if (typeof customContent === 'string') {
            customContentContainer.innerHTML = customContent;
        } else {
            customContentContainer.appendChild(customContent);
        }

        contentContainer.appendChild(customContentContainer);
    } else {
        const messageElement = document.createElement('div');
        messageElement.className = 'toast-message';
        messageElement.textContent = message;
        contentContainer.appendChild(messageElement);
    }

    toastContainer.appendChild(contentContainer);

    const controlsContainer = document.createElement('div');
    controlsContainer.className = 'toast-controls';

    if (showCloseButton) {
        const separator = document.createElement('div');
        separator.className = 'toast-separator';
        controlsContainer.appendChild(separator);

        const closeButton = document.createElement('button');
        closeButton.className = 'toast-close-button';
        closeButton.innerHTML = `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12.9281 5.85656C12.811 5.7394 12.621 5.7394 12.5038 5.85656L10.0441 8.31636L7.54066 5.81297C7.4235 5.69581 7.23355 5.69581 7.1164 5.81297L5.81345 7.11591C5.6963 7.23307 5.6963 7.42302 5.81345 7.54017L8.31684 10.0436L5.85705 12.5034C5.73989 12.6205 5.73989 12.8105 5.85705 12.9276L7.07281 14.1434C7.18996 14.2605 7.37991 14.2605 7.49707 14.1434L9.95687 11.6836L12.4603 14.187C12.5774 14.3041 12.7674 14.3041 12.8845 14.187L14.1875 12.884C14.3046 12.7669 14.3046 12.5769 14.1875 12.4598L11.6841 9.95638L14.1439 7.49658C14.261 7.37942 14.261 7.18948 14.1439 7.07232L12.9281 5.85656Z" fill="currentColor"/>
        </svg>
            `;
        controlsContainer.appendChild(closeButton);
    }

    toastContainer.appendChild(controlsContainer);

    const position = window.innerWidth < 568 ? 'center' : 'right';

    const toast = Toastify({
        node: toastContainer,
        duration: duration,
        close: false,
        gravity: 'bottom',
        position: position,
        stopOnFocus: true,
        className: 'toast-wrapper',
        onClick: function () {},
    });

    toast.showToast();

    if (showCloseButton) {
        const closeButton = toastContainer.querySelector('.toast-close-button');
        closeButton?.addEventListener('click', () => {
            toast.hideToast();
            if (onClose) {
                onClose();
            }
        });
    }

    return toast;
};

export const showSuccessToast = (message: string, options: Partial<Omit<ToastOptions, 'type' | 'message'>> = {}) => {
    return showToast({ message, type: 'success', ...options });
};

export const showErrorToast = (message: string, options: Partial<Omit<ToastOptions, 'type' | 'message'>> = {}) => {
    return showToast({ message, type: 'error', ...options });
};

export const showInfoToast = (message: string, options: Partial<Omit<ToastOptions, 'type' | 'message'>> = {}) => {
    return showToast({ message, type: 'info', ...options });
};
