import { CentrifugeProvider, CentrifugePublishData, useCentrifuge } from '@Root/Contexts/CentrifugeContext';
import { act } from 'react-dom/test-utils';
import { renderHook } from '../TestUtils';
import MockCentrifuge from '../__mocks__/centrifuge';
import React, { PropsWithChildren } from 'react';

const centrifugeConfig = {
    authEndpoint: 'http://awardwallet.docker/centrifuge/auth/',
    channelName: 'test',
    debug: true,
    info: '{"username":"User","isBooker":false}',
    timestamp: '134',
    token: '122',
    url: 'http://awardwallet.docker',
    user: '1',
};

describe('CentrifugeContext', () => {
    let subscribeSpy: jest.SpyInstance;
    let connectSpy: jest.SpyInstance;
    let unsubscribeSpy: jest.SpyInstance;
    let disconnectSpy: jest.SpyInstance;

    beforeEach(() => {
        subscribeSpy = jest.spyOn(MockCentrifuge.prototype, 'subscribe');
        connectSpy = jest.spyOn(MockCentrifuge.prototype, 'connect');
        unsubscribeSpy = jest.spyOn(MockCentrifuge.prototype, 'unsubscribe');
        disconnectSpy = jest.spyOn(MockCentrifuge.prototype, 'disconnect');
    });

    afterEach(() => {
        subscribeSpy.mockRestore();
        connectSpy.mockRestore();
        unsubscribeSpy.mockRestore();
        disconnectSpy.mockRestore();
    });

    test('should connect and subscribe', () => {
        const { result } = renderHook(() => useCentrifuge(), {
            wrapper: ({ children }: PropsWithChildren) => {
                return <CentrifugeProvider centrifugeConfig={centrifugeConfig}>{children}</CentrifugeProvider>;
            },
        });

        act(() => {
            result.current.subscribe('channel', () => {});
        });

        expect(subscribeSpy).toHaveBeenCalled();
        expect(connectSpy).toHaveBeenCalled();
    });

    test('should unsubscribe and disconnect', () => {
        const { result } = renderHook(() => useCentrifuge(), {
            wrapper: ({ children }: PropsWithChildren) => {
                return <CentrifugeProvider centrifugeConfig={centrifugeConfig}>{children}</CentrifugeProvider>;
            },
        });

        act(() => {
            result.current.subscribe('channel', () => {});

            result.current.unsubscribe('channel');
        });

        expect(subscribeSpy).toHaveBeenCalled();
        expect(connectSpy).toHaveBeenCalled();
        expect(unsubscribeSpy).toHaveBeenCalled();
        expect(disconnectSpy).toHaveBeenCalled();
    });

    test('should connect once, even there are many subscriptions', () => {
        const { result } = renderHook(() => useCentrifuge(), {
            wrapper: ({ children }: PropsWithChildren) => {
                return <CentrifugeProvider centrifugeConfig={centrifugeConfig}>{children}</CentrifugeProvider>;
            },
        });

        act(() => {
            result.current.subscribe('channel', () => {});
            result.current.subscribe('channel1', () => {});
            result.current.subscribe('channel2', () => {});
            result.current.subscribe('channel3', () => {});
        });

        expect(subscribeSpy).toHaveBeenCalledTimes(4);
        expect(connectSpy).toHaveBeenCalledTimes(1);
    });

    test('should disconnect once, even there were many subscriptions', () => {
        const { result } = renderHook(() => useCentrifuge(), {
            wrapper: ({ children }: PropsWithChildren) => {
                return <CentrifugeProvider centrifugeConfig={centrifugeConfig}>{children}</CentrifugeProvider>;
            },
        });

        act(() => {
            result.current.subscribe('channel', () => {});
            result.current.subscribe('channel1', () => {});
            result.current.subscribe('channel2', () => {});
            result.current.subscribe('channel3', () => {});

            result.current.unsubscribe('channel3');
            result.current.unsubscribe('channel2');
            result.current.unsubscribe('channel1');
            result.current.unsubscribe('channel');
        });

        expect(unsubscribeSpy).toHaveBeenCalledTimes(4);
        expect(disconnectSpy).toHaveBeenCalledTimes(1);
    });

    test('should receive data, when publishing', () => {
        jest.spyOn(MockCentrifuge.prototype, 'subscribe').mockImplementation(
            (channel: string, callback: (data: CentrifugePublishData<string>) => void) => {
                callback({ data: 'published data', channel, uid: '12' });
                return {
                    unsubscribe: () => null,
                };
            },
        );

        const { result } = renderHook(() => useCentrifuge(), {
            wrapper: ({ children }: PropsWithChildren) => {
                return <CentrifugeProvider centrifugeConfig={centrifugeConfig}>{children}</CentrifugeProvider>;
            },
        });

        act(() => {
            result.current.subscribe('channel', (data: CentrifugePublishData<string>) => {
                expect(data.data).toEqual('published data');
            });
        });
    });
});
