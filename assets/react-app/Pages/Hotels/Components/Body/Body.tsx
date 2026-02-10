import { HotelsView } from './Components/HotelsView/HotelsView';
import { HotelsViewSettings } from './Components/HotelsViewSettings/HotelsViewSettings';
import React from 'react';
import classes from './Body.module.scss';

export function Body() {
    return (
        <div className={classes.bodyContainer}>
            <HotelsViewSettings />
            <HotelsView />
        </div>
    );
}
