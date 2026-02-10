import { useAppSettingsContext } from '@Root/Contexts/AppSettingsContext';
import { Icon } from '@UI/Icon';
import { Modal, ModalMobileViewOption } from '@UI/Popovers';
import classNames from 'classnames';
import React from 'react';

type FullReviewModalProps = { open: boolean; onClose: () => void; reviewText: string; reviewAuthor: string };

export function FullReviewModal({ onClose, open, reviewAuthor, reviewText }: FullReviewModalProps) {
    const { theme } = useAppSettingsContext();
    return (
        <Modal
            className={{
                container: classNames('page-pricing__review-modal', {
                    ['page-pricing__review-modal--light']: theme === 'light',
                    ['page-pricing__review-modal--dark']: theme === 'dark',
                }),
            }}
            open={open}
            onClose={onClose}
            mobileView={ModalMobileViewOption.Centered}
        >
            <div className="page-pricing__review-modal-content">
                <div className="stars-rating">
                    <Icon type={'Star'} color="warning" />
                    <Icon type={'Star'} color="warning" />
                    <Icon type={'Star'} color="warning" />
                    <Icon type={'Star'} color="warning" />
                    <Icon type={'Star'} color="warning" />
                </div>
                <p
                    className={classNames('page-pricing__review-modal-text', {
                        ['page-pricing__review-modal-text--light']: theme === 'light',
                        ['page-pricing__review-modal-text--dark']: theme === 'dark',
                    })}
                >
                    {reviewText}
                </p>
                <h2
                    className={classNames('page-pricing__review-modal-author', {
                        ['page-pricing__review-modal-author--light']: theme === 'light',
                        ['page-pricing__review-modal-author--dark']: theme === 'dark',
                    })}
                >
                    {reviewAuthor}
                </h2>
            </div>
        </Modal>
    );
}
