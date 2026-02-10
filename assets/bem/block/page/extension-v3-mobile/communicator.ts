import Centrifuge from "centrifuge";
import SockJS from 'sockjs';

export class Communicator {
    private connection: Centrifuge | undefined;
    private gotHistory = false;
    private subscription: Centrifuge.Subscription | undefined;
    private messagesBuffer: any[] = []
    private readonly config: any;
    private readonly historyStartIndex: number;
    private tickCallback: ((events: any[]) => number)| undefined
    private queueIndex: number | undefined = 0;


    constructor({config, historyStartIndex}: {config: any; historyStartIndex: number}, tickCallback: ((events: any[]) => number) | undefined) {
        this.config = config;
        this.historyStartIndex = historyStartIndex;
        this.tickCallback = tickCallback;
    }

    createConnection = () => {
        return new Promise<void>((resolve) => {
            if (!this.connection) {
                this.connection = new Centrifuge({...this.config, sockJS: SockJS});
            }
            if (!this.connection.isConnected()) {
                this.connection.on('connect', (context) => {
                    console.log('centrifuge connected', context);
                    resolve();
                });
                this.connection.connect()
            } else {
                resolve()
            }
        })
    }

    connect  = async (channel: string) => {
        await this.createConnection();
        this.subscription = this.connection?.subscribe(channel, this.onChannelMessage);
        await this.restoreHistory(channel);
    }

    disconnect = () => {
        if (this.subscription) {
            this.subscription.unsubscribe();
            this.subscription.removeAllListeners();
            this.subscription = undefined;
        }
        if (this.connection && this.connection.isConnected()) {
            this.connection.disconnect();
        }
        this.connection = undefined;
        this.tickCallback = undefined;
    }

    onChannelMessage = (message: { data: any[] }) => {
        if (this.gotHistory) {
            const events = message.data.map((value) => {
                return value[1];
            });

            this.queueIndex = this.tickCallback?.(events);
        } else {
            this.messagesBuffer = this.messagesBuffer.concat(message.data);
        }
    }

    restoreHistory = async (channel: string) => {
        const history = await this.subscription?.history();
        this.gotHistory = true;
        // @ts-ignore
        history.data.forEach((channelHistory: { channel: string; data: any[]; }) => {
            if (channelHistory.channel === channel) {
                this.messagesBuffer = this.messagesBuffer.concat(channelHistory.data.reverse());
            }
        });
        if (this.messagesBuffer.length > 0) {
            this.messagesBuffer.sort((a, b) => a[0] - b[0]);
            this.onChannelMessage({data: this.messagesBuffer.splice(this.historyStartIndex)});
            this.messagesBuffer = [];
        }
    }
}
