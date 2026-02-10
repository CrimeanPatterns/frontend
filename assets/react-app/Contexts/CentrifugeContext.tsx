import Centrifuge from 'centrifuge';
import React, { PropsWithChildren, createContext, useContext, useEffect, useRef } from 'react';

interface CentrifugeContextValue {
    centrifugeConfig: CentrifugeConfig;
    subscribe: SubscribeMethod;
    unsubscribe: (channelName: string, callback?: () => void) => void;
}

type SubscribeMethod = (
    channelName: string,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onPublic: (response: CentrifugePublishData<any>) => void,
    additionalMethods?: SubscribeAdditionalMethods,
) => void;

interface SubscribeAdditionalMethods {
    onSubscribe?: () => void;
}

const CentrifugeContext = createContext<CentrifugeContextValue | null>(null);

export interface CentrifugeConfig {
    authEndpoint: string;
    channelName: string;
    debug: boolean;
    info: string;
    timestamp: string;
    token: string;
    url: string;
    user: string;
}

interface CentrifugeProviderProps extends PropsWithChildren {
    centrifugeConfig: CentrifugeConfig;
}

export interface CentrifugePublishData<T> {
    channel: string;
    data: T;
    uid: string;
}

export function CentrifugeProvider({ centrifugeConfig, children }: CentrifugeProviderProps) {
    //Wrong build in types
    const client = useRef<Centrifuge>(new Centrifuge(centrifugeConfig as unknown as string)).current;

    const subscriptions = useRef(new Map<string, Centrifuge.Subscription>()).current;

    const openConnectionIfNecessary = () => {
        if (!client.isConnected()) {
            client.connect();
        }
    };
    const closeConnectionIfNecessary = () => {
        if (client.isConnected()) {
            client.disconnect();
        }
    };

    const subscribe: SubscribeMethod = (channel, callback, additionalMethods) => {
        openConnectionIfNecessary();
        if (!subscriptions.has(channel)) {
            const subscription = client.subscribe(channel, callback);
            subscriptions.set(channel, subscription);

            if (additionalMethods?.onSubscribe) {
                subscription.on('subscribe', additionalMethods.onSubscribe);
            }
        }
    };

    const unsubscribe = (channel: string, callback?: () => void) => {
        if (subscriptions.has(channel)) {
            subscriptions.get(channel)?.unsubscribe();
            subscriptions.delete(channel);
            if (Array.from(subscriptions.keys()).length === 0) {
                closeConnectionIfNecessary();
            }
            callback?.();
        }
    };

    useEffect(() => {
        return () => {
            closeConnectionIfNecessary();
        };
    }, []);

    return (
        <CentrifugeContext.Provider value={{ centrifugeConfig, subscribe, unsubscribe }}>
            {children}
        </CentrifugeContext.Provider>
    );
}

export function useCentrifuge() {
    const context = useContext(CentrifugeContext);
    if (context === null) {
        throw new Error('useCentrifuge must be used within a CentrifugeProvider');
    }
    return context;
}
