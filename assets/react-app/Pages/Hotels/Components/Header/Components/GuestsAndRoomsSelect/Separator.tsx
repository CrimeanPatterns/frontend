import { Theme } from '@UI/Theme';
import { useTheme } from 'react-jss';
import React from 'react';

export function Separator() {
    const theme = useTheme<Theme>();
    return (
        <svg width="7" height="6" viewBox="0 0 7 6" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect
                x="-1"
                y="3"
                width="6"
                height="6"
                rx="3"
                transform="rotate(-45 -1 3)"
                fill={theme.iconColor.disabled}
            />
        </svg>
    );
}
