export class MockAudio extends EventTarget {
    static instance: MockAudio | null = null;

    src: string = '';
    currentTime: number = 0;
    duration: number = 0;
    volume: number = 1;
    play = jest.fn(() => {
        const event = new Event('play');
        MockAudio.instance?.dispatchEvent(event);
        return Promise.resolve();
    });

    pause = jest.fn(() => {
        const event = new Event('pause');
        MockAudio.instance?.dispatchEvent(event);
    });

    canPlayType = jest.fn().mockReturnValue('');

    constructor() {
        super();
        if (MockAudio.instance) {
            return MockAudio.instance;
        }
        MockAudio.instance = this;
    }

    load() {
        return null;
    }
}
