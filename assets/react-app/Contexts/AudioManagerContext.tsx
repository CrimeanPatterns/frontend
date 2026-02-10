import React, {
    MutableRefObject,
    PropsWithChildren,
    createContext,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';

type AudioManagerContext = {
    currentVolume: number;
    setCurrentVolume: (volume: number) => void;
    currentAudio: HTMLAudioElement | null;
    setCurrentAudio: (audio: HTMLAudioElement | null) => void;
    isCurrentAudioPlaying: boolean;
    isCurrentAudioLoading: boolean;
    setIsCurrentAudioLoading: (isLoading: boolean) => void;
    currentTime: number;
    duration: number;
    setDuration: (duration: number) => void;
    previousVolumeRef: MutableRefObject<number | null>;
    pauseCurrentAudio: () => void;
    playCurrentAudio: () => void;
};

const AudioContext = createContext<AudioManagerContext | undefined>(undefined);

export const AudioManagerProvider = ({ children }: PropsWithChildren) => {
    const [currentVolume, setCurrentVolume] = useState(1);
    const [currentAudio, setCurrentAudio] = useState<HTMLAudioElement | null>(null);
    const [isCurrentAudioPlaying, setIsCurrentAudioPlaying] = useState(false);
    const [isCurrentAudioLoading, setIsCurrentAudioLoading] = useState(false);
    const [currentTime, setCurrentTime] = useState(0);
    const [duration, setDuration] = useState(0);

    const previousVolumeRef = useRef<number | null>(null);

    const changeCurrentAudio = (audio: HTMLAudioElement | null) => {
        setCurrentAudio((prevAudio) => {
            if (prevAudio && prevAudio !== audio) {
                prevAudio.pause();
                setIsCurrentAudioPlaying(false);
            }
            return audio;
        });
    };
    const pauseCurrentAudio = () => {
        if (currentAudio) {
            currentAudio.pause();
        }
    };
    const playCurrentAudio = () => {
        if (currentAudio) {
            currentAudio.play().catch(() => {});
        }
    };

    const contextValue: AudioManagerContext = useMemo(
        () => ({
            currentVolume,
            setCurrentVolume,
            currentAudio,
            setCurrentAudio: changeCurrentAudio,
            currentTime,
            isCurrentAudioPlaying,
            isCurrentAudioLoading,
            setIsCurrentAudioLoading,
            duration,
            setDuration,
            previousVolumeRef,
            pauseCurrentAudio,
            playCurrentAudio,
        }),
        [
            currentVolume,
            currentAudio,
            isCurrentAudioPlaying,
            isCurrentAudioLoading,
            currentTime,
            duration,
            changeCurrentAudio,
        ],
    );

    useEffect(() => {
        const updateCurrentTime = () => {
            if (currentAudio) {
                setCurrentTime(currentAudio.currentTime);
            }
        };

        const handleLoadStart = () => {
            setIsCurrentAudioLoading(true);
        };

        const handleEnded = () => {
            setIsCurrentAudioPlaying(false);
        };

        const handleStartPlaying = () => {
            setIsCurrentAudioPlaying(true);
        };

        const handlePause = () => {
            setIsCurrentAudioPlaying(false);
        };

        if (currentAudio) {
            currentAudio.addEventListener('loadstart', handleLoadStart);
            currentAudio.addEventListener('timeupdate', updateCurrentTime);
            currentAudio.addEventListener('ended', handleEnded);
            currentAudio.addEventListener('play', handleStartPlaying);
            currentAudio.addEventListener('pause', handlePause);
        }
        return () => {
            if (currentAudio) {
                currentAudio.removeEventListener('loadstart', handleLoadStart);
                currentAudio.removeEventListener('timeupdate', updateCurrentTime);
                currentAudio.removeEventListener('ended', handleEnded);
                currentAudio.removeEventListener('play', handleStartPlaying);
                currentAudio.removeEventListener('pause', handlePause);
            }
        };
    }, [currentAudio]);

    return <AudioContext.Provider value={contextValue}>{children}</AudioContext.Provider>;
};

export const useAudioManager = (): AudioManagerContext => {
    const audioContext = useContext(AudioContext);
    if (!audioContext) {
        throw new Error('useAudioManager should be wrapped into AudioManagerProvider');
    }
    return audioContext;
};
