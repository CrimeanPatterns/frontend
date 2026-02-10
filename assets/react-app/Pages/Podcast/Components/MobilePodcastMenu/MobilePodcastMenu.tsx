import { Image } from '@UI/Layout/Image';
import { PlayingIcon } from '../PlayingIcon/PlayingIcon';
import { Podcast } from '../../UseFetchPodcasts';
import { TextButton } from '@UI/Buttons/TextButton';
import { useAppSettingsContext } from '@Root/Contexts/AppSettingsContext';
import { useAudioManager } from '@Root/Contexts/AudioManagerContext';
import { useCurrentPodcast, usePodcastsActions, usePodcastsValues } from '../../PodcastContext';
import React, { forwardRef, useEffect, useImperativeHandle, useMemo, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './MobilePodcastMenu.module.scss';

export type MobilePodcastMenuRef = {
    open: () => void;
};

type SeasonName = { seasonName: string; id: number };

export const MobilePodcastMenu = forwardRef<MobilePodcastMenuRef>((_, ref) => {
    const [seasonNames, setSeasonsNames] = useState<SeasonName[]>([]);
    const [selectedSeason, setSelectedSeason] = useState<SeasonName | null>(null);
    const [shownPodcasts, setShownPodcasts] = useState<Podcast[]>([]);
    const [isVisible, setIsVisible] = useState(false);
    const [isDragging, setIsDragging] = useState(false);
    const dragYRef = useRef(0);
    const startYRef = useRef(0);

    const { allPodcasts } = usePodcastsValues();
    const { currentPodcast } = useCurrentPodcast();
    const { setCurrentPodcast } = usePodcastsActions();

    const menuRef = useRef<HTMLDivElement>(null);

    const { isCurrentAudioPlaying } = useAudioManager();

    const locale = useAppSettingsContext().localeForIntl;

    const dateFormatter = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric',
            }),
        [locale],
    );

    const openPodcastMenu = () => {
        setIsVisible(true);
        dragYRef.current = 0;
    };

    const closePodcastMenu = () => {
        setIsVisible(false);
        dragYRef.current = 0;
        if (menuRef.current) {
            menuRef.current.style.transform = '';
        }
    };

    const handleDragMove = (e: TouchEvent) => {
        const currentY = e.touches[0]?.clientY;

        if (currentY) {
            const deltaY = currentY - startYRef.current;
            dragYRef.current = deltaY > 0 ? deltaY : 0;
        }

        if (menuRef.current) {
            if (dragYRef.current) {
                menuRef.current.style.transform = `translateY(${dragYRef.current}px)`;
            } else {
                menuRef.current.style.transform = '';
            }
        }
    };

    const handleDragEnd = () => {
        const menuHeight = menuRef.current?.clientHeight || 0;

        if (dragYRef.current > menuHeight / 2) {
            closePodcastMenu();
        } else {
            dragYRef.current = 0;
            if (menuRef.current) {
                menuRef.current.style.transform = '';
            }
        }

        setIsDragging(false);

        document.removeEventListener('touchmove', handleDragMove);
        document.removeEventListener('touchend', handleDragEnd);
    };

    const handleDragStart = (e: React.TouchEvent) => {
        const startPosition = e.touches[0]?.clientY;

        if (startPosition) {
            startYRef.current = startPosition;
        }

        setIsDragging(true);

        document.addEventListener('touchmove', handleDragMove);
        document.addEventListener('touchend', handleDragEnd);
    };

    function scrollToPodcastById(id: string) {
        const element = document.getElementById(id);
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }
    }

    useImperativeHandle(
        ref,
        () => ({
            open: openPodcastMenu,
        }),
        [],
    );

    useEffect(() => {
        if (isVisible) {
            const scrollWidth = window.innerWidth - document.documentElement.clientWidth;

            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = `${scrollWidth}px`;
        } else {
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = `0`;
        }

        return () => {
            document.body.style.overflow = 'auto';
        };
    }, [isVisible]);

    useEffect(() => {
        const seasonNames: { seasonName: string; id: number }[] = [];
        const addedSeasons: number[] = [];

        allPodcasts.forEach((podcasts) => {
            if (!addedSeasons.includes(podcasts.seasonNumber)) {
                seasonNames.push({ seasonName: podcasts.season, id: podcasts.seasonNumber });
                addedSeasons.push(podcasts.seasonNumber);
            }
        });

        seasonNames.sort((a, b) => {
            return a.id - b.id;
        });

        setSeasonsNames(seasonNames);
    }, [allPodcasts]);

    useEffect(() => {
        if (currentPodcast) {
            const selectedSeason = seasonNames.find((season) => season.id === currentPodcast.seasonNumber);

            if (selectedSeason) {
                setSelectedSeason(selectedSeason);
            }
        }
    }, [currentPodcast, seasonNames]);

    useEffect(() => {
        const filteredPodcasts = allPodcasts
            .filter((podcast) => podcast.seasonNumber === selectedSeason?.id)
            .sort((a, b) => b.episodeNumber - a.episodeNumber);

        setShownPodcasts(filteredPodcasts);
    }, [selectedSeason, allPodcasts]);
    return (
        <>
            <div
                className={classNames(classes.mobilePodcastMenuBackground, {
                    [classes.mobilePodcastMenuBackgroundVisible as string]: isVisible,
                })}
                onClick={closePodcastMenu}
            />
            <div
                className={classNames(classes.mobilePodcastMenu, {
                    [classes.mobilePodcastMenuVisible as string]: isVisible,
                })}
                style={isDragging ? { transitionDuration: '0.1s' } : undefined}
                ref={menuRef}
            >
                <TextButton
                    text={null}
                    className={{ button: classes.mobilePodcastMenuDragHandler }}
                    onTouchStart={handleDragStart}
                />

                <div className={classes.mobilePodcastMenuTabs}>
                    {seasonNames.map((seasonName) => {
                        const onSelectSeason = () => {
                            setSelectedSeason(seasonName);
                        };
                        return (
                            <TextButton
                                key={seasonName.id}
                                className={{
                                    button: classNames(classes.mobilePodcastMenuTab, {
                                        [classes.mobilePodcastMenuTabActive as string]:
                                            seasonName.id === selectedSeason?.id,
                                    }),
                                }}
                                onClick={onSelectSeason}
                                text={seasonName.seasonName}
                            />
                        );
                    })}
                </div>
                <div className={classes.mobilePodcastMenuPodcasts}>
                    {shownPodcasts.map((podcast) => {
                        const handleStartPodcast = () => {
                            if (currentPodcast?.id === podcast.id && isCurrentAudioPlaying) {
                                closePodcastMenu();
                                scrollToPodcastById(podcast.id);
                                return;
                            }

                            podcast.playAudio?.();
                            setCurrentPodcast(podcast);
                        };
                        return (
                            <div
                                key={podcast.id}
                                className={classes.mobilePodcastMenuPodcast}
                                onClick={handleStartPodcast}
                            >
                                <Image
                                    src={podcast.imageUrl || ''}
                                    classes={{
                                        container: classes.mobilePodcastMenuPodcastImg,
                                        imageActionIconContainer: classes.mobilePodcastMenuPodcastPlayingButton,
                                    }}
                                    actionElement={
                                        currentPodcast?.id === podcast.id &&
                                        isCurrentAudioPlaying && <PlayingIcon size="small" />
                                    }
                                    alwaysShowActionButton={currentPodcast?.id === podcast.id && isCurrentAudioPlaying}
                                />
                                <div className={classes.mobilePodcastMenuPodcastInfo}>
                                    <div className={classes.mobilePodcastMenuPodcastMeta}>
                                        <div className={classes.mobilePodcastMenuPodcastSeason}>{podcast.season}</div>
                                        <div className={classes.mobilePodcastMenuPodcastDate}>
                                            {dateFormatter.format(podcast.releaseDate)}
                                        </div>
                                    </div>
                                    <h3 className={classes.mobilePodcastMenuPodcastTitle}>{podcast.title}</h3>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </>
    );
});

MobilePodcastMenu.displayName = 'MobilePodcastMenu';
