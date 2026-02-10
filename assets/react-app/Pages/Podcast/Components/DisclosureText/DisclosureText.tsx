import AwIcon from './aw_icon.svg';
import React from 'react';
import classes from './DisclosureText.module.scss';

export function DisclosureText() {
    return (
        <div className={classes.disclosure}>
            <p className={classes.disclosureText}>
                <AwIcon className={classes.disclosureIcon} />
                <span>
                    AwardWallet receives compensation from advertising partners for links in this email and on our blog.
                    The opinions expressed here are our own and have not been reviewed, provided, or approved by any
                    bank advertiser. Here’s our complete list of{' '}
                    <a target="_blank" href="blog/american-airlines-cruises/">
                        Advertisers
                    </a>
                    .
                </span>
            </p>
        </div>
    );
}
