class MockResizeObserver {
    private callback: ResizeObserverCallback;
    private targets: Set<Element>;

    constructor(callback: ResizeObserverCallback) {
        this.callback = callback;
        this.targets = new Set();
    }

    observe(target: Element) {
        this.targets.add(target);
    }

    unobserve(target: Element) {
        this.targets.delete(target);
    }

    disconnect() {
        this.targets.clear();
    }

    trigger(entries: ResizeObserverEntry[]) {
        this.callback(entries, this);
    }
}

global.ResizeObserver = MockResizeObserver;
