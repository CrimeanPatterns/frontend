/* eslint-disable @typescript-eslint/no-unsafe-member-access */
/* eslint-disable @typescript-eslint/no-unsafe-assignment */
import { AudioManagerProvider } from '@Root/Contexts/AudioManagerContext';
import { AudioPlayer, AudioPlayerRef } from '@UI/Media/AudioPlayer/AudioPlayer';
import { MockAudio } from '@Root/Tests/__mocks__/audio';
import { act, fireEvent, render, screen, waitFor } from '@Root/Tests/TestUtils';
import React from 'react';

describe('AudioPlayer', () => {
    beforeAll(() => {
        jest.spyOn(console, 'warn').mockImplementation(() => {});
    });

    beforeEach(() => {
        global.Audio = jest.fn().mockImplementation(() => {
            const audio = new MockAudio();
            return audio;
        });
    });

    afterEach(() => {
        (global.Audio as jest.Mock).mockReset();
        const mockAudioInstance = new MockAudio();
        mockAudioInstance.play.mockClear();
        mockAudioInstance.pause.mockClear();
        mockAudioInstance.canPlayType.mockClear();
    });

    test('should render', () => {
        render(
            <AudioManagerProvider>
                <AudioPlayer src="" variant="secondary" />
            </AudioManagerProvider>,
        );

        const audioPlayer = document.querySelector('.audioPlayer');

        expect(audioPlayer).toBeInTheDocument();
    });

    test('should render primary variant', () => {
        render(
            <AudioManagerProvider>
                <AudioPlayer src="" variant="primary" />
            </AudioManagerProvider>,
        );

        const audioPlayer = document.querySelector('.audioPlayerPrimary');

        expect(audioPlayer).toBeInTheDocument();
    });

    test('should render secondary variant', () => {
        render(
            <AudioManagerProvider>
                <AudioPlayer src="" variant="secondary" />
            </AudioManagerProvider>,
        );

        const audioPlayer = document.querySelector('.audioPlayerSecondary');

        expect(audioPlayer).toBeInTheDocument();
    });

    test('loads and plays the audio when the play button is clicked', async () => {
        const audioPlayerRef = React.createRef<AudioPlayerRef>();

        render(
            <AudioManagerProvider>
                <AudioPlayer ref={audioPlayerRef} src="test.mp3" variant="secondary" />
            </AudioManagerProvider>,
        );

        const playButton = document.querySelector('.playButton');
        if (!playButton) {
            throw new Error("PlayButton wasn't found");
        }

        act(() => {
            fireEvent.click(playButton);
        });

        await waitFor(() => {
            const loader = screen.getByRole('progressbar');
            expect(loader).toBeInTheDocument();
        });

        act(() => {
            const event = new Event('canplaythrough');
            audioPlayerRef.current?.audio?.dispatchEvent(event);
        });

        await waitFor(() => {
            const mockAudioInstance = (global.Audio as jest.Mock).getMockImplementation()?.();
            expect(mockAudioInstance).toBeDefined();
            expect(mockAudioInstance?.play).toHaveBeenCalled();
        });
    });

    test('the play button toggles the audio playing', async () => {
        const audioPlayerRef = React.createRef<AudioPlayerRef>();

        render(
            <AudioManagerProvider>
                <AudioPlayer ref={audioPlayerRef} src="test.mp3" variant="secondary" />
            </AudioManagerProvider>,
        );

        const playButton = document.querySelector('.playButton');
        if (!playButton) {
            throw new Error("PlayButton wasn't found");
        }

        act(() => {
            fireEvent.click(playButton);
        });

        act(() => {
            const event = new Event('canplaythrough');
            audioPlayerRef.current?.audio?.dispatchEvent(event);
        });

        act(() => {
            fireEvent.click(playButton);
        });

        act(() => {
            fireEvent.click(playButton);
        });

        await waitFor(() => {
            const mockAudioInstance = (global.Audio as jest.Mock).getMockImplementation()?.();
            expect(mockAudioInstance).toBeDefined();
            expect(mockAudioInstance?.play).toHaveBeenCalledTimes(2);
            expect(mockAudioInstance?.pause).toHaveBeenCalledTimes(1);
        });
    });

    test('should call callback when loading state has changed', async () => {
        const audioPlayerRef = React.createRef<AudioPlayerRef>();
        const onLoadingStateChange = jest.fn();

        render(
            <AudioManagerProvider>
                <AudioPlayer
                    ref={audioPlayerRef}
                    src="test.mp3"
                    variant="secondary"
                    onAudioChangeLoadingState={onLoadingStateChange}
                />
            </AudioManagerProvider>,
        );

        const playButton = document.querySelector('.playButton');
        if (!playButton) {
            throw new Error("PlayButton wasn't found");
        }

        act(() => {
            fireEvent.click(playButton);
        });

        await waitFor(() => {
            expect(onLoadingStateChange).toHaveBeenCalledTimes(2);
        });

        act(() => {
            const event = new Event('canplaythrough');
            audioPlayerRef.current?.audio?.dispatchEvent(event);
        });

        await waitFor(() => {
            expect(onLoadingStateChange).toHaveBeenCalledTimes(3);
        });
    });

    test('should call callback when isPlaying state has changed', async () => {
        const audioPlayerRef = React.createRef<AudioPlayerRef>();
        const onPlayingStateChanged = jest.fn();

        render(
            <AudioManagerProvider>
                <AudioPlayer
                    ref={audioPlayerRef}
                    src="test.mp3"
                    variant="secondary"
                    onAudioToggle={onPlayingStateChanged}
                />
            </AudioManagerProvider>,
        );

        const playButton = document.querySelector('.playButton');
        if (!playButton) {
            throw new Error("PlayButton wasn't found");
        }

        act(() => {
            fireEvent.click(playButton);
        });

        act(() => {
            const event = new Event('canplaythrough');
            audioPlayerRef.current?.audio?.dispatchEvent(event);
        });

        act(() => {
            fireEvent.click(playButton);
        });

        await waitFor(() => {
            expect(onPlayingStateChanged).toHaveBeenCalledTimes(2);
        });
    });
});
