import React, {useEffect} from "react";
import _ from "lodash";
import {logger} from "./logger";
import {checkPermissions} from "../hooks/permissions";
import {useExtensionInfo} from "../hooks";

export const Logger: React.FunctionComponent<{
    extensionInfo:  ReturnType<typeof useExtensionInfo>;
}> = ({extensionInfo}) => {
    useEffect(() => {
        if (extensionInfo) {
            logger.log('extension info', extensionInfo);

            const {permissions, hasPermissions} = extensionInfo;

            if (_.isArray(permissions?.origins)) {
                const noPermissions = permissions?.origins.length < 1;
                const isVisible = noPermissions || !hasPermissions;
                const hasHostPermissions = permissions?.origins.includes(`*://*.${document.location.hostname}/*`);
                const hasOtherWebsitesPermission = hasHostPermissions && checkPermissions(permissions?.origins);

                if (isVisible) {
                    logger.log('check permissions', permissions);
                    logger.log('hasHostPermissions:', hasHostPermissions);
                    logger.log('hasOtherWebsitesPermission:', checkPermissions(permissions?.origins));

                    if (!hasOtherWebsitesPermission) {
                        logger.log('show instruction how get all websites permissions');
                    } else {
                        logger.log('show instruction how enable and get permissions');
                    }
                }
            }
        }
    }, [extensionInfo]);

    return null;
}