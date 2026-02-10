import React from 'react';
import classes from './Notification.module.scss';

type NotificationProps = {
    title?: string;
    description?: string;
};

export function Notification({ description, title }: NotificationProps) {
    return (
        <div className={classes.notification}>
            {title && <h2 className={classes.notificationTitle}>{title}</h2>}
            {description && <p className={classes.notificationDescription}>{description}</p>}
        </div>
    );
}
