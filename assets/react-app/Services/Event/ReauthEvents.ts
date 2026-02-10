import { CustomEventManager } from './Events';

const reauthEvents = {
    reauthRequired: 'reauth-required',
    reauthSuccess: 'reauth-success',
    reauthError: 'reauth-error',
} as const;

export const reauthEventManager = new CustomEventManager<typeof reauthEvents>(reauthEvents);
