import { IconColor } from '.';
import { Theme } from '../Theme';

export function getColor(color: IconColor, theme: Theme): string {
    switch (color) {
        case 'primary':
            return theme.iconColor.primary;
        case 'active':
            return theme.iconColor.active;
        case 'secondary':
            return theme.iconColor.secondary;
        case 'disabled':
            return theme.iconColor.disabled;
        case 'warning':
            return theme.warningColor.main;
        default:
            return theme.iconColor.secondary;
    }
}
