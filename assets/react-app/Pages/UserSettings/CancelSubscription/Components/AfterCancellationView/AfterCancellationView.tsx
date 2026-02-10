import { Router } from '@Services/Router';
import { Translator } from '@Services/Translator';
import AwBrokenImg from '../../Assets/aw-broken.png';
import AwBrokenImgRetina from '../../Assets/aw-broken@2x.png';
import React from 'react';
import classes from './AfterCancellationView.module.scss';

export function AfterCancellationView() {
    return (
        <>
            <div className={classes.afterCancellationContainer}>
                <img
                    className={classes.afterCancellationIcon}
                    srcSet={`${AwBrokenImg} 1x, ${AwBrokenImgRetina} 2x`}
                ></img>
                <h1 className={classes.afterCancellationTitle}>
                    {Translator.trans(
                        /**@Desc("Your AwardWallet Plus subscription has been canceled.")*/ 'cancel-subscription.after.cancelling.title',
                    )}
                </h1>
                <p
                    className={classes.afterCancellationDescription}
                    dangerouslySetInnerHTML={{
                        __html: Translator.trans(
                            /**@Desc("Changed your mind? %link_on%Click here%link_off% to re-subscribe.")*/ 'cancel-subscription.after.cancelling.text1',
                            {
                                link_on: `<a href="${Router.generate('aw_users_pay')}">`,
                                link_off: '</a>',
                            },
                        ),
                    }}
                ></p>
            </div>
        </>
    );
}
