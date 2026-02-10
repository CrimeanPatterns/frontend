import { Skeleton } from '@UI/Feedback/Skeleton';
import React from 'react';
import classes from './HotelCard.module.scss';

export const HotelCardSkeleton = () => {
    return (
        <div className={classes.cardContainer}>
            <div className={classes.leftContentContainer}>
                <Skeleton width={314} height={202} rounded />
                <div className={classes.mainInfoContainer}>
                    <Skeleton rounded className={classes.hotelNameSkeleton} />
                    <Skeleton rounded className={classes.hotelAddressSkeleton} />
                    <div className={classes.hotelAdditionalInfoContainer}>
                        <Skeleton rounded className={classes.dateInfoSkeleton} />
                        <Skeleton rounded className={classes.rateSkeleton} />
                    </div>
                    <div className={classes.assessmentsContainerSkeleton}>
                        <div className={classes.redemptionBlockSkeleton}>
                            <Skeleton rounded className={classes.redemptionValueSkeleton} />
                            <Skeleton rounded className={classes.redemptionDescriptionSkeleton} />
                        </div>
                        <div className={classes.redemptionBlockSkeletonBig}>
                            <Skeleton rounded className={classes.redemptionValueSkeleton} />
                            <Skeleton rounded className={classes.redemptionDescriptionSkeleton} />
                        </div>
                        <div className={classes.redemptionBlockSkeleton}>
                            <Skeleton rounded className={classes.redemptionValueSkeleton} />
                            <Skeleton rounded className={classes.redemptionDescriptionSkeleton} />
                        </div>
                    </div>
                </div>
            </div>
            <Skeleton rounded className={classes.bookContainerSkeleton} height={150} />
        </div>
    );
};
