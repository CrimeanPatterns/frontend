import {
    ReauthContext,
    ReauthHeader,
    ReauthRequiredEventData,
    reauthInterceptor,
} from '@Services/Axios/Reauth/ReauthInterceptor';
import { reauthEventManager } from '@Services/Event/ReauthEvents';
import MockAdapter from 'axios-mock-adapter';
import axios, { AxiosError } from 'axios';

describe('Reauth Interceptor', () => {
    reauthInterceptor(axios);
    let mock = new MockAdapter(axios);

    beforeAll(() => {
        mock = new MockAdapter(axios);
    });

    afterEach(() => {
        mock.reset();
    });

    afterAll(() => {
        mock.restore();
    });

    test('should catch response with reauth required, then finish successfully', async () => {
        const onReauthRequire = (event: CustomEvent<ReauthRequiredEventData>) => {
            event.detail.onSubmit('user_password');
        };

        reauthEventManager.subscribe<ReauthRequiredEventData>(
            reauthEventManager.getEventNames().reauthRequired,
            onReauthRequire,
        );

        mock.onAny().reply((config) => {
            const originalHeaders = config.headers;

            if (originalHeaders?.[ReauthHeader.Input]) {
                return [200, {}, { ...originalHeaders, [ReauthHeader.Success]: true }];
            }

            const headers = {
                ...originalHeaders,
                [ReauthHeader.Required]: 'Reauth required',
                [ReauthHeader.Context]: ReauthContext.Password,
            };

            return [401, {}, headers];
        });

        const response = await axios.get('/url');

        expect(response.status).toBe(200);

        reauthEventManager.unsubscribe(reauthEventManager.getEventNames().reauthRequired, onReauthRequire);
    });

    test('should catch response with reauth required and cancel it without any errors', async () => {
        const onReauthRequired = (event: CustomEvent<ReauthRequiredEventData>) => {
            event.detail.onCancel();
        };

        reauthEventManager.subscribe<ReauthRequiredEventData>(
            reauthEventManager.getEventNames().reauthRequired,
            onReauthRequired,
        );

        let isReauthRequiredSent = false;

        mock.onAny().reply((config) => {
            const originalHeaders = config.headers;

            if (!isReauthRequiredSent) {
                const headers = {
                    ...originalHeaders,
                    [ReauthHeader.Required]: 'Reauth required',
                    [ReauthHeader.Context]: ReauthContext.Password,
                };

                isReauthRequiredSent = true;
                return [403, {}, headers];
            }

            return [403];
        });

        try {
            await axios.get('/url');
        } catch (error) {
            expect(error).toBeUndefined();
        }

        reauthEventManager.unsubscribe(reauthEventManager.getEventNames().reauthRequired, onReauthRequired);
    });

    test('should catch response with reauth required, request resend, then finish successfully', async () => {
        let isCodeResent = false;
        const onReauthRequire = (event: CustomEvent<ReauthRequiredEventData>) => {
            if (!isCodeResent) {
                event.detail.onResend();
            } else {
                event.detail.onSubmit('code_from_email');
            }
        };

        reauthEventManager.subscribe<ReauthRequiredEventData>(
            reauthEventManager.getEventNames().reauthRequired,
            onReauthRequire,
        );

        mock.onAny().reply((config) => {
            const originalHeaders = config.headers;

            if (originalHeaders?.[ReauthHeader.Input]) {
                return [200, {}, { ...originalHeaders, [ReauthHeader.Success]: true }];
            }

            const headers = {
                ...originalHeaders,
                [ReauthHeader.Required]: 'Reauth required',
                [ReauthHeader.Context]: ReauthContext.Password,
            };

            isCodeResent = true;
            return [401, {}, headers];
        });

        const response = await axios.get('/url');
        expect(response.status).toBe(200);

        reauthEventManager.unsubscribe(reauthEventManager.getEventNames().reauthRequired, onReauthRequire);
    });

    test('should catch response with reauth required and return error', async () => {
        mock.onAny().reply((config) => {
            const originalHeaders = config.headers;

            const headers = {
                ...originalHeaders,
                [ReauthHeader.Error]: 'Error!',
            };

            return [403, {}, headers];
        });

        try {
            await axios.get('/url');
        } catch (error) {
            expect((error as AxiosError).response?.status).toBe(403);
        }
    });

    test('should catch response with reauth required and retry successfully', async () => {
        const onReauthError = (event: CustomEvent<string>) => {
            expect(event.detail).toBe('Error!');
        };

        reauthEventManager.subscribe<string>(reauthEventManager.getEventNames().reauthError, onReauthError);

        let isErrorSent = false;
        mock.onAny().reply((config) => {
            const originalHeaders = config.headers;

            if (!isErrorSent) {
                const headers = {
                    ...originalHeaders,
                    [ReauthHeader.Error]: 'Error!',
                    [ReauthHeader.Retry]: 'true',
                };
                isErrorSent = true;

                return [403, {}, headers];
            }

            return [200];
        });

        const response = await axios.get('/url');
        expect(response.status).toBe(200);

        reauthEventManager.unsubscribe(reauthEventManager.getEventNames().reauthError, onReauthError);
    });
});
