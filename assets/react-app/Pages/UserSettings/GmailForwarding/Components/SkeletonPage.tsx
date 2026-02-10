import '@UI/Styles/UtilityStyles.scss';
import React from 'react';
import classNames from 'classnames';
import classes from './SkeletonPage.module.scss';

export function SkeletonPage() {
    return (
        <div role="page-loader" className={classes.SkeletonGmailPage}>
            <h2 className={classNames(classes.SkeletonGmailPageTitle, 'skeleton')}></h2>
            <p className={classNames(classes.SkeletonGmailPageDescription, 'skeleton')}></p>
            <div className={classes.SkeletonGmailPageGrid}>
                {new Array(8).fill(0).map((_, index) => {
                    return (
                        <div key={index} className={classes.SkeletonGmailPageStepContainer}>
                            <div className={classNames(classes.SkeletonGmailPageStepNumber, 'skeleton')}></div>
                            <div className={classNames(classes.SkeletonGmailPageStepDescription, 'skeleton')}></div>
                            <div className={classNames(classes.SkeletonGmailPageStepImgBlock, 'skeleton')}></div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
