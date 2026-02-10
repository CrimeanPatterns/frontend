/* eslint-disable @typescript-eslint/prefer-promise-reject-errors */
import { Axios, AxiosError } from 'axios';
import { reauthEventManager } from '@Services/Event/ReauthEvents';
import isObject from 'lodash/isObject';

export enum ReauthHeader {
    Context = 'x-aw-reauth-context',
    Required = 'x-aw-reauth-required',
    Input = 'x-aw-reauth-input',
    Resent = 'x-aw-reauth-intent',
    Error = 'x-aw-reauth-error',
    Retry = 'x-aw-reauth-retry',
    Success = 'x-aw-reauth-success',
}

export enum ReauthContext {
    Password = 'password',
    Code = 'code',
}

export type ReauthRequiredEventData = {
    context: ReauthContext;
    labelText?: string;
    onSubmit: (inputValue: string) => void;
    onCancel: () => void;
    onResend: () => void;
};

type ReauthRequired = {
    inputValue?: string;
    resend?: boolean;
};

export function reauthInterceptor(axios: Axios) {
    axios.interceptors.response.use(
        (response) => {
            if (response.headers[ReauthHeader.Success]) {
                reauthEventManager.publish(reauthEventManager.getEventNames().reauthSuccess);
            }

            return response;
        },
        (rejection: AxiosError) => {
            const { response, config } = rejection;

            if (isObject(response)) {
                const { headers } = response;

                if (isObject(headers)) {
                    if (headers[ReauthHeader.Error]) {
                        if (headers[ReauthHeader.Retry] && isObject(config)) {
                            reauthEventManager.publish(
                                reauthEventManager.getEventNames().reauthError,
                                headers[ReauthHeader.Error],
                            );

                            delete config.headers[ReauthHeader.Context];
                            delete config.headers[ReauthHeader.Input];
                            delete config.headers[ReauthHeader.Resent];

                            return axios.request(config);
                        }

                        return Promise.reject(rejection);
                    }

                    if (headers[ReauthHeader.Required]) {
                        const promise = new Promise<ReauthRequired>((resolve, reject) => {
                            function onSubmit(inputValue: string) {
                                resolve({ inputValue });
                            }

                            function onResend() {
                                resolve({ resend: true });
                            }

                            function onCancel() {
                                reject();
                            }

                            const context = headers[ReauthHeader.Context] as ReauthContext;

                            reauthEventManager.publish<ReauthRequiredEventData>(
                                reauthEventManager.getEventNames().reauthRequired,
                                {
                                    onSubmit,
                                    onCancel,
                                    onResend,
                                    context,
                                    labelText:
                                        context === ReauthContext.Code
                                            ? (headers[ReauthHeader.Required] as string)
                                            : undefined,
                                },
                            );
                        });

                        return promise
                            .then(({ inputValue, resend }) => {
                                if (isObject(config)) {
                                    if (inputValue) {
                                        config.headers[ReauthHeader.Input] = inputValue;
                                    }

                                    if (resend) {
                                        config.headers[ReauthHeader.Resent] = resend;
                                    }

                                    config.headers[ReauthHeader.Context] = headers[
                                        ReauthHeader.Context
                                    ] as ReauthContext;

                                    return axios.request(config);
                                }

                                return Promise.reject(rejection);
                            })
                            .catch(() => {
                                reauthEventManager.publish(reauthEventManager.getEventNames().reauthError, null);

                                return Promise.reject();
                            });
                    }

                    if (headers[ReauthHeader.Success]) {
                        reauthEventManager.publish(reauthEventManager.getEventNames().reauthSuccess);
                    }
                }
            }

            return Promise.reject(rejection);
        },
    );
}
