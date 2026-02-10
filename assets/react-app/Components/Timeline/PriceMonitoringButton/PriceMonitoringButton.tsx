import '@Root/Styles/GlobalStyles.scss';
import { Popover } from '@UI/Popovers/Popover';
import { Translator } from '@Services/Translator';
import AWLogo from './Assets/aw_logo.svg';
import React, { useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './PriceMonitoringButton.module.scss';

export default function PriceMonitoringButton() {
    const [isPopoverOpen, setIsPopoverOpen] = useState(false);
    const anchorRef = useRef(null);

    const onMouseEnter = () => {
        setIsPopoverOpen(true);
    };
    const onMouseLeave = () => {
        setIsPopoverOpen(false);
    };

    return (
        <span
            className={classNames('btn-silver small', classes.priceMonitoringButton)}
            ref={anchorRef}
            onMouseEnter={onMouseEnter}
            onMouseLeave={onMouseLeave}
        >
            <div className={classes.priceMonitoringButtonRadar} />

            <span>
                {Translator.trans(/** @Desc("Price drop monitoring is on") */ 'price-drop-monitoring-on', {}, 'trips')}
            </span>
            <Popover
                open={isPopoverOpen}
                anchor={anchorRef}
                classNames={{ popoverContainer: classes.priceMonitoringButtonPopover }}
                offsetFromAnchorInPx={15}
            >
                <AWLogo className={classes.priceMonitoringButtonPopoverLogo} />
                <h2 className={classes.priceMonitoringButtonPopoverTitle}>
                    {Translator.trans(
                        /** @Desc("AwardWallet is keeping an eye on your reservation for any better deals.") */ 'price-drop-monitoring-on.tooltip.header',
                        {},
                        'trips',
                    )}
                </h2>
                <p className={classes.priceMonitoringButtonPopoverDescription}>
                    {Translator.trans(
                        /** @Desc("If we find an option that might be more advantageous, we'll inform you via email. Please ensure that emails from AwardWallet.com don't end up in your spam folder.") */ 'price-drop-monitoring-on.tooltip.body',
                        {},
                        'trips',
                    )}
                </p>
            </Popover>
        </span>
    );
}
