import { Skeleton } from '@UI/Feedback';
import { SkeletonPodcastCard } from '../SkeletonPodcastCard/SkeletonPodcastCard';
import React from 'react';
import classes from './SkeletonPage.module.scss';

export function SkeletonPage() {
    return (
        <>
            <div className={classes.skeletonPageHeader}>
                <div className={classes.skeletonPageHeaderMeta}>
                    <Skeleton rounded width={61} height={14} />
                    <Skeleton rounded width={61} height={14} />
                </div>
                <Skeleton className={classes.skeletonPageHeaderTitle} width="75%" height={30} rounded />
                <Skeleton className={classes.skeletonPageHeaderDescription} height={72} rounded />
                <Skeleton className={classes.skeletonPageHeaderPlayer} width="100%" height={54} rounded />

                <Skeleton className={classes.skeletonPageHeaderPreview} rounded />
            </div>
            <div className={classes.skeletonPageContent}>
                <SkeletonPodcastCard />
                <SkeletonPodcastCard />
                <SkeletonPodcastCard />
            </div>
        </>
    );
}
