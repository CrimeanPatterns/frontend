export class CustomEventManager<E extends Record<string, string>> {
    private emitter = new CustomEmitter();
    private eventNames: E;

    constructor(eventNames: E) {
        this.eventNames = eventNames;
    }

    getEventNames() {
        return this.eventNames;
    }

    publish<T>(eventName: E[keyof E], data?: T): void {
        this.emitter.dispatchCustomEvent(eventName, data);
    }

    subscribe<T = unknown>(eventName: E[keyof E], listener: (event: CustomEvent<T>) => void): void {
        this.emitter.addCustomEventListener(eventName, listener);
    }

    unsubscribe<T = unknown>(eventName: E[keyof E], listener: (event: CustomEvent<T>) => void): void {
        this.emitter.removeCustomEventListener(eventName, listener);
    }
}

export class CustomEmitter extends EventTarget {
    dispatchCustomEvent<E extends string, T>(eventName: E, data: T) {
        const customEvent = new CustomEvent<T>(eventName, {
            detail: data,
        });
        this.dispatchEvent(customEvent);
    }

    addCustomEventListener<E extends string, T>(eventName: E, callback: (event: CustomEvent<T>) => void) {
        this.addEventListener(eventName, callback as EventListenerOrEventListenerObject);
    }

    removeCustomEventListener<E extends string, T>(eventName: E, callback: (event: CustomEvent<T>) => void) {
        this.removeEventListener(eventName, callback as EventListenerOrEventListenerObject);
    }
}
