import { Skeleton } from '@UI/Feedback/Skeleton';
import React from 'react';
import classes from './SkeletonPodcastCard.module.scss';

export function SkeletonPodcastCard() {
    return (
        <div className={classes.skeletonPodcastCard}>
            <Skeleton className={classes.skeletonPodcastCardPreview} rounded />
            <div className={classes.skeletonPodcastCardMeta}>
                <Skeleton rounded width={61} height={14} />
                <Skeleton rounded width={61} height={14} />
            </div>
            <Skeleton className={classes.skeletonPodcastCardTitle} width="50%" height={20} rounded />
            <Skeleton className={classes.skeletonPodcastCardDescription} width="90%" height={52} rounded />
            <Skeleton className={classes.skeletonPodcastCardPlayer} height={54} rounded />
        </div>
    );
}
