import 'rc-slider/assets/index.css';
import { Icon } from '@UI/Icon/Icon';
import { PlayButton } from './Components/PlayButton/PlayButton';
import { isMobile } from 'react-device-detect';
import { useAudioManager } from '@Root/Contexts/AudioManagerContext';
import { useUpdateEffect } from '@Utilities/Hooks/UseUpdateEffect';
import React, { forwardRef, useCallback, useEffect, useImperativeHandle, useMemo, useRef, useState } from 'react';
import SliderRC from 'rc-slider';
import classNames from 'classnames';
import classes from './AudioPlayer.module.scss';

type AudioPlayerClasses = {
    container?: string;
};

type AudioPlayerProps = {
    audio?: HTMLAudioElement | null;
    src: string;
    variant: 'secondary' | 'primary';
    onAudioToggle?: (isPlaying: boolean) => void;
    onAudioChangeLoadingState?: (isLoading: boolean) => void;
    onAudioPlayButtonClick?: () => void;
    durationInSec?: number;
    classes?: AudioPlayerClasses;
    preload?: boolean;
    defaultCurrent?: boolean;
};

export type AudioPlayerRef = {
    togglePlayPause: () => void;
    audio: HTMLAudioElement | null;
};

const AudioPlayerBase = forwardRef<AudioPlayerRef, AudioPlayerProps>(
    (
        {
            src,
            variant,
            onAudioToggle,
            durationInSec,
            onAudioChangeLoadingState,
            onAudioPlayButtonClick,
            classes: externalClasses,
            audio: externalAudio = null,
            preload,
            defaultCurrent,
        },
        ref,
    ) => {
        const [audio, setAudio] = useState<HTMLAudioElement>(externalAudio || new Audio());
        const [isLoading, setIsLoading] = useState(false);
        const [currentTime, setCurrentTime] = useState(0);
        const [duration, setDuration] = useState(durationInSec || 0);
        const [includeHours, setIncludeHours] = useState(duration >= 3600);
        const [isVolumeVisible, setIsVolumeVisible] = useState(false);
        const [isVolumeDragging, setIsVolumeDragging] = useState(false);

        const isAudioLoadedRef = useRef(externalAudio !== null);
        const shouldPlayAfterLoadingRef = useRef(false);

        const audioManager = useAudioManager();
        const isPlaying = audio === audioManager.currentAudio && audioManager.isCurrentAudioPlaying;

        const containerClasses = useMemo(
            () =>
                classNames(
                    classes.audioPlayer,
                    {
                        [classes.audioPlayerPrimary as string]: variant === 'primary',
                        [classes.audioPlayerSecondary as string]: variant === 'secondary',
                    },
                    externalClasses?.container,
                ),
            [variant],
        );

        const timeClasses = useMemo(
            () =>
                classNames(classes.audioPlayerTime, {
                    [classes.audioPlayerTimePrimary as string]: variant === 'primary',
                    [classes.audioPlayerTimeSecondary as string]: variant === 'secondary',
                }),
            [variant],
        );

        const audioTrackClasses = useMemo(
            () => ({
                rail: classes.audioPlayerDurationSliderRail,
                track: classes.audioPlayerDurationSliderTrack,
                handle: classes.audioPlayerDurationSliderHandle,
            }),
            [],
        );

        const volumeTrackClasses = useMemo(
            () => ({
                rail: classes.audioPlayerVolumeSliderRail,
                track: classes.audioPlayerVolumeSliderTrack,
                handle: classes.audioPlayerVolumeSliderHandle,
            }),
            [],
        );

        const loadAudio = useCallback(
            (isCurrentAudio: boolean) => {
                if (isCurrentAudio) {
                    audioManager.setIsCurrentAudioLoading(true);
                } else {
                    setIsLoading(true);
                }

                if (audio.src !== src) {
                    audio.src = src;
                    audio.load();
                }
            },
            [src],
        );

        const togglePlayPause = useCallback(async () => {
            if (isPlaying) {
                audio.pause();
            } else {
                await audio.play();
            }
            return;
        }, [audio, audioManager, isPlaying]);

        const handleVolumeChange = useCallback((volume: number | number[]) => {
            if (typeof volume === 'number') {
                audioManager.setCurrentVolume(volume);
            }

            handleVolumeDragStart();
        }, []);

        const handleVolumeDragStart = () => {
            setIsVolumeDragging(true);
        };

        const handleVolumeDragEnd = () => {
            setIsVolumeDragging(false);
        };

        const handleTimeChange = useCallback(
            (currentTime: number | number[]) => {
                if (typeof currentTime === 'number') {
                    audio.currentTime = currentTime;
                }
            },
            [audio],
        );

        const handleVolumeButtonMouseEnter = useCallback(() => {
            setIsVolumeVisible(true);
        }, []);

        const handleVolumeButtonMouseLeave = useCallback(() => {
            setIsVolumeVisible(false);
        }, []);

        const handleVolumeButtonClick = useCallback(() => {
            if (audioManager.currentVolume > 0) {
                audioManager.previousVolumeRef.current = audioManager.currentVolume;
                audioManager.setCurrentVolume(0);
                return;
            }
            audioManager.setCurrentVolume(audioManager.previousVolumeRef.current || 1);
        }, [audioManager.currentVolume]);

        const handlePlayButtonClick = useCallback(async () => {
            onAudioPlayButtonClick?.();
            if (audioManager.currentAudio !== audio) {
                audioManager.setCurrentAudio(audio);
                audioManager.setDuration(duration);
            }

            if (!isAudioLoadedRef.current) {
                shouldPlayAfterLoadingRef.current = true;
                loadAudio(true);
                return;
            }

            await togglePlayPause();
        }, [togglePlayPause, loadAudio, audioManager, audio]);

        const formatTime = useCallback(
            (timeInSeconds: number) => {
                if (timeInSeconds === 0 && includeHours) {
                    return '00:00:00';
                }

                const startDate = new Date(0);
                startDate.setSeconds(timeInSeconds);

                const options: Intl.DateTimeFormatOptions = {
                    hour: includeHours ? '2-digit' : undefined,
                    minute: '2-digit',
                    second: '2-digit',
                    timeZone: 'UTC',
                    hour12: false,
                };

                const formatter = new Intl.DateTimeFormat('en-US', options);

                return formatter.format(startDate);
            },
            [includeHours],
        );

        useImperativeHandle(
            ref,
            () => ({
                togglePlayPause: handlePlayButtonClick,
                audio,
            }),
            [togglePlayPause],
        );

        useEffect(() => {
            onAudioChangeLoadingState?.(isLoading);
        }, [isLoading]);

        useEffect(() => {
            const setAudioData = () => {
                if (audio === audioManager.currentAudio) {
                    audioManager.setDuration(audio.duration);
                } else {
                    setIncludeHours(audio.duration >= 3600);
                    setDuration(audio.duration);
                }
            };
            const handleCanPlayThrough = () => {
                isAudioLoadedRef.current = true;

                if (audioManager.currentAudio === audio) {
                    audioManager.setIsCurrentAudioLoading(false);
                    if (shouldPlayAfterLoadingRef.current) {
                        shouldPlayAfterLoadingRef.current = false;
                        togglePlayPause().catch(() => {});
                    }
                } else {
                    setIsLoading(false);
                }
            };

            audio.addEventListener('loadedmetadata', setAudioData);
            audio.addEventListener('canplaythrough', handleCanPlayThrough);

            return () => {
                audio.removeEventListener('loadedmetadata', setAudioData);
                audio.removeEventListener('canplaythrough', handleCanPlayThrough);
            };
        }, [audio, audioManager]);

        useEffect(() => {
            audio.volume = audioManager.currentVolume;
        }, [audio, audioManager.currentVolume]);

        useUpdateEffect(() => {
            onAudioToggle?.(isPlaying);
        }, [isPlaying]);

        useUpdateEffect(() => {
            if (externalAudio) {
                setAudio(externalAudio);
                isAudioLoadedRef.current = true;

                if (externalAudio === audioManager.currentAudio) {
                    setIsLoading(audioManager.isCurrentAudioLoading);
                    setCurrentTime(audioManager.currentTime);
                    setDuration(audioManager.duration);
                }
            }
        }, [externalAudio]);

        useEffect(() => {
            if (preload && !isAudioLoadedRef.current) {
                loadAudio(false);
            }
        }, [preload]);

        useUpdateEffect(() => {
            if (audioManager.currentAudio === audio) {
                setIsLoading(audioManager.isCurrentAudioLoading);
            }
        }, [audioManager]);

        useUpdateEffect(() => {
            if (audioManager.currentAudio === audio) {
                setCurrentTime(audioManager.currentTime);
            }
        }, [audioManager.currentTime]);

        useUpdateEffect(() => {
            if (audioManager.currentAudio === audio) {
                setDuration(audioManager.duration);
            }
        }, [audioManager.duration]);

        useEffect(() => {
            if (defaultCurrent) {
                audioManager.setCurrentAudio(audio);

                if (!isAudioLoadedRef.current) {
                    loadAudio(true);
                }
            }
        }, []);

        return (
            <div className={containerClasses}>
                <PlayButton
                    variant={variant === 'primary' ? 'secondary' : 'primary'}
                    onClick={handlePlayButtonClick}
                    isPlaying={isPlaying}
                    isLoading={isLoading}
                />
                <span className={timeClasses}>{formatTime(currentTime)}</span>
                <SliderRC
                    min={0}
                    max={duration}
                    step={0.01}
                    value={currentTime}
                    onChange={handleTimeChange}
                    className={classes.audioPlayerDurationSlider}
                    classNames={audioTrackClasses}
                />
                <span className={timeClasses}>{formatTime(duration)}</span>
                {!isMobile && (
                    <button
                        className={classes.audioPlayerVolumeButton}
                        onMouseEnter={handleVolumeButtonMouseEnter}
                        onMouseLeave={handleVolumeButtonMouseLeave}
                        onClick={handleVolumeButtonClick}
                    >
                        <Icon
                            type={audioManager.currentVolume === 0 ? 'MuteVolume' : 'Volume'}
                            color={variant === 'primary' ? 'primary' : 'secondary'}
                        />
                        {(isVolumeVisible || isVolumeDragging) && (
                            <div
                                className={classes.audioPlayerVolumeSlider}
                                onClick={(e) => {
                                    e.stopPropagation();
                                }}
                            >
                                <SliderRC
                                    min={0}
                                    max={1}
                                    step={0.01}
                                    value={audioManager.currentVolume}
                                    onChange={handleVolumeChange}
                                    vertical
                                    classNames={volumeTrackClasses}
                                    onChangeComplete={handleVolumeDragEnd}
                                />
                            </div>
                        )}
                    </button>
                )}
            </div>
        );
    },
);

AudioPlayerBase.displayName = 'AudioPlayer';

export const AudioPlayer = React.memo(AudioPlayerBase);
