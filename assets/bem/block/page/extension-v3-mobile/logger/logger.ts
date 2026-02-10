interface LoggerOptions {
    serverUrl?: string;
    sendLogs?: boolean;
    maxBufferAge?: number;
    flushDelay?: number;
    sessionId?: string;
}

interface ILogger {
    log(...args: any[]): void;

    pushLogs(...args: any[]): void;

    flush(): void;

    clearSession(): void;

    setSessionId(sessionId: string): void;

    setServerUrl(url: string): void;
}

export class Logger implements ILogger {
    private serverUrl: string | undefined;
    private readonly sendLogs: boolean;
    private readonly maxBufferAge: number;
    private sessionId: string | undefined;
    private buffer: any[];
    private maxBufferAgeTimeout: ReturnType<typeof setTimeout> | null;

    constructor({serverUrl, sessionId, sendLogs = true, maxBufferAge = 10 * 1000}: LoggerOptions = {}) {
        this.serverUrl = serverUrl;
        this.sendLogs = sendLogs;
        this.buffer = [];
        this.maxBufferAge = maxBufferAge;
        this.maxBufferAgeTimeout = null;
        this.sessionId = sessionId;

        this.log = this.log.bind(this);
        this.flush = this.flush.bind(this);
        this.setSessionId = this.setSessionId.bind(this);
        this.setServerUrl = this.setServerUrl.bind(this);
        this.clearSession = this.clearSession.bind(this);
    }

    setSessionId(sessionId: string): void {
        this.sessionId = sessionId;
    }

    setServerUrl(url: string): void {
        this.serverUrl = url;
    }

    private addToBuffer(args: any[]): void {
        this.printToConsole(...args);

        if (!this.sendLogs || !this.serverUrl) {
            return;
        }

        this.buffer.push({time: new Date().toUTCString(), message: args});
        this.startTimer();
    }

    private startTimer() {
        if (!this.maxBufferAgeTimeout && this.buffer.length > 0) {
            this.maxBufferAgeTimeout = setTimeout(this.flush, this.maxBufferAge);
        }
    }

    flush(): void {
        if (!this.sendLogs || !this.serverUrl || this.buffer.length === 0) {
            return;
        }

        const logsToSend = [...this.buffer];
        this.buffer = [];
        this.sendToServer(logsToSend);

        if (this.maxBufferAgeTimeout) {
            clearTimeout(this.maxBufferAgeTimeout);
            this.maxBufferAgeTimeout = null;
            this.startTimer();
        }
    }

    private async sendToServer(logs: any[]): Promise<any> {
        if (this.serverUrl) {
            try {
                const response = await fetch(this.serverUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        extensionSessionId: this.sessionId,
                        logs,
                    }),
                });

                return await response.json();
            } catch (error) {
                console.error('Error sending logs to server:', error);
                throw error;
            }
        }
    }

    private printToConsole(...args: any[]): void {
        console.log(...args);
    }

    log(...args: any[]): void {
        this.addToBuffer(args);
    }

    pushLogs(...args: any[]): void {
        if (!this.sendLogs || !this.serverUrl) {
            return;
        }
        this.buffer.push(...args);
        this.startTimer();
    }

    clearSession() {
        this.sessionId = undefined;
        this.serverUrl = undefined;
    }
}

export class Logs {
    logs: any[] = [];

    push(...args: any[]) {
        console.log(...args);
        this.logs.push({time: new Date().toUTCString(), message: args});
    }
}

export const logger = new Logger({
    serverUrl: document.location.origin + '/api/extension/v1/logs',
});
