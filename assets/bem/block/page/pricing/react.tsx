import React, { useEffect, useState } from 'react';
import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { FullReviewModal } from './components/FullReviewModal';
import ReactDOM from 'react-dom/client';

const App = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [reviewText, setReviewText] = useState('');
    const [reviewAuthor, setReviewAuthor] = useState('');

    const openModalWithContent = (text: string, author: string) => {
        setReviewText(text);
        setReviewAuthor(author);
        setIsModalOpen(true);
    };

    const closeModal = () => setIsModalOpen(false);

    useEffect(() => {
        (window as any).openModalWithContent = openModalWithContent;
    }, []);

    return (
        <AppSettingsProvider>
            <FullReviewModal
                open={isModalOpen}
                onClose={closeModal}
                reviewText={reviewText}
                reviewAuthor={reviewAuthor}
            />
        </AppSettingsProvider>
    );
};

const reactRoot = document.getElementById('fullReviewPopup');

if (reactRoot) {
    ReactDOM.createRoot(reactRoot).render(
        <React.StrictMode>
            <App />
        </React.StrictMode>,
    );
}
