import { IconButton, IconButtonProps } from '../IconButton';
import { copyToClipboard } from '@Bem/ts/service/utils';
import { toast } from '@Utilities/Toast';
import { useUAParser } from '@Utilities/Hooks/UseUAParser';
import React from 'react';
import classNames from 'classnames';
import classes from './ShareButton.module.scss';

type ShareButtonProps = { url: string; title?: string; text?: string } & Omit<
    IconButtonProps,
    'onClick' | 'type' | 'iconType'
>;

export const ShareButton = ({ url, title, text, className, ...props }: ShareButtonProps) => {
    const { device } = useUAParser();
    const handleShare = async () => {
        const isMobile = device.type === 'mobile' || device.type === 'tablet';
        // eslint-disable-next-line @typescript-eslint/no-unnecessary-condition
        if (isMobile && navigator.share) {
            try {
                await navigator.share({
                    title,
                    text,
                    url,
                });
            } catch (error) {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return;
                }
            }
        } else {
            const isCopiedSuccessfully = await copyToClipboard(url);

            if (isCopiedSuccessfully) {
                toast('Copied', {
                    toastId: 'SuccessfulCopied',
                    type: 'success',
                });
            } else {
                toast('Error copying the link', {
                    toastId: 'FailedShare',
                    type: 'error',
                });
            }
        }
    };

    return (
        <IconButton
            {...props}
            onClick={handleShare}
            iconType="Share"
            className={{
                ...className,
                button: classNames(classes.shareButton, className?.button),
            }}
        />
    );
};
