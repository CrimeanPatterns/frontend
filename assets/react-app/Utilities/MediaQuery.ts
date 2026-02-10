import { Breakpoints, BreakpointsDesignation } from '../UI/Theme';
import { MediaQuery } from '@Root/Contexts/MediaQueryContext';

export function getMediaQueryExpression(mediaQuery: MediaQuery, breakpoints: Breakpoints): string {
    const matchResult = mediaQuery.match(/([<>]=?)([a-z]+)$/);

    if (!matchResult) {
        return '';
    }
    const [compactionSign, designation] = matchResult.slice(1);
    const breakpointValue = breakpoints[designation as BreakpointsDesignation];

    switch (compactionSign) {
        case '>':
            return `@media (min-width:${breakpointValue + 1}px)`;
        case '<':
            return `@media (max-width:${breakpointValue - 1}px)`;
        case '>=':
            return `@media (min-width:${breakpointValue - 1}px)`;
        case '<=':
            return `@media (max-width:${breakpointValue}px)`;
        default:
            return '';
    }
}
