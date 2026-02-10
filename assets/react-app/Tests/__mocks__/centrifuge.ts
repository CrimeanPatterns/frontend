import { CentrifugeConfig } from '@Root/Contexts/CentrifugeContext';

class MockCentrifuge {
    connected: boolean;
    subscribedChannels: Map<string, (data: unknown) => void>;

    constructor(public config: CentrifugeConfig) {
        this.connected = false;
        this.subscribedChannels = new Map();
    }

    connect() {
        this.connected = true;
    }

    subscribe(channel: string, callback: (data: unknown) => void) {
        if (!this.connected) {
            throw new Error('Not connected to Centrifuge server');
        }
        this.subscribedChannels.set(channel, callback);

        return {
            unsubscribe: (channel: string, callback?: () => void) => {
                this.unsubscribe(channel);
                callback?.();
            },
        };
    }

    unsubscribe(channel: string) {
        if (!this.connected) {
            throw new Error('Not connected to Centrifuge server');
        }
        this.subscribedChannels.delete(channel);
    }

    disconnect() {
        this.connected = false;
        this.subscribedChannels.clear();
    }

    isConnected() {
        return this.connected;
    }
}

export default MockCentrifuge;
