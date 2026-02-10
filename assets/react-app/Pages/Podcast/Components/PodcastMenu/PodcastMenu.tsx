import { Image } from '@UI/Layout';
import { PlayingIcon } from '../PlayingIcon/PlayingIcon';
import { Podcast } from '../../UseFetchPodcasts';
import { Scrollbar, ScrollbarRef } from '@UI/Layout/Scrollbar/Scrollbar';
import { TextButton } from '@UI/Buttons/TextButton';
import { useAppSettingsContext } from '@Root/Contexts/AppSettingsContext';
import { useAudioManager } from '@Root/Contexts/AudioManagerContext';
import { useCurrentPodcast, usePodcastsActions } from '../../PodcastContext';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './PodcastMenu.module.scss';

type PodcastMenuProps = {
    podcasts: Podcast[];
    onClose: () => void;
};

type SeasonName = { seasonName: string; id: number };

export function PodcastMenu({ podcasts, onClose }: PodcastMenuProps) {
    const [seasonNames, setSeasonsNames] = useState<SeasonName[]>([]);
    const [selectedSeason, setSelectedSeason] = useState<SeasonName | null>(null);
    const [shownPodcasts, setShownPodcasts] = useState<Podcast[]>([]);

    const locale = useAppSettingsContext().localeForIntl;

    const scrollbarSeasonContentRef = useRef<HTMLDivElement>(null);
    const scrollbarSeasonContainerRef = useRef<HTMLDivElement>(null);
    const scrollbarPodcastsContentRef = useRef<HTMLDivElement>(null);
    const scrollbarPodcastsContainerRef = useRef<HTMLDivElement>(null);
    const scrollbarRef = useRef<ScrollbarRef>(null);

    const { currentPodcast } = useCurrentPodcast();
    const { setCurrentPodcast } = usePodcastsActions();
    const { isCurrentAudioPlaying } = useAudioManager();

    const dateFormatter = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric',
            }),
        [locale],
    );

    const setSelectedSeasonAndResetScrollPosition = (seasonName: SeasonName) => {
        setSelectedSeason(seasonName);
        scrollbarRef.current?.resetScrollPosition();
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

    useEffect(() => {
        const seasonNames: { seasonName: string; id: number }[] = [];
        const addedSeasons: number[] = [];

        podcasts.forEach((podcasts) => {
            if (!addedSeasons.includes(podcasts.seasonNumber)) {
                seasonNames.push({ seasonName: podcasts.season, id: podcasts.seasonNumber });
                addedSeasons.push(podcasts.seasonNumber);
            }
        });

        seasonNames.sort((a, b) => {
            return a.id - b.id;
        });

        setSeasonsNames(seasonNames);
    }, [podcasts]);

    useEffect(() => {
        if (currentPodcast) {
            const selectedSeason = seasonNames.find((season) => season.id === currentPodcast.seasonNumber);

            if (selectedSeason) {
                setSelectedSeasonAndResetScrollPosition(selectedSeason);
            }
        }
    }, [currentPodcast, seasonNames]);

    useEffect(() => {
        const filteredPodcasts = podcasts
            .filter((podcast) => podcast.seasonNumber === selectedSeason?.id)
            .sort((a, b) => b.episodeNumber - a.episodeNumber);

        setShownPodcasts(filteredPodcasts);
    }, [selectedSeason]);
    return (
        <div className={classes.podcastMenu}>
            <div ref={scrollbarSeasonContainerRef} className={classes.podcastMenuTabsWrapper}>
                <Scrollbar
                    contentRef={scrollbarSeasonContentRef}
                    containerRef={scrollbarSeasonContainerRef}
                    hideScrollbarPadding
                    fullHeightScrollbar
                >
                    <div className={classes.podcastMenuTabs} ref={scrollbarSeasonContentRef}>
                        {seasonNames.map((seasonName) => {
                            const onSelectSeason = () => {
                                setSelectedSeasonAndResetScrollPosition(seasonName);
                            };
                            return (
                                <TextButton
                                    key={seasonName.id}
                                    className={{
                                        button: classNames(classes.podcastMenuTab, {
                                            [classes.podcastMenuTabActive as string]:
                                                seasonName.id === selectedSeason?.id,
                                        }),
                                    }}
                                    onClick={onSelectSeason}
                                    text={seasonName.seasonName}
                                />
                            );
                        })}
                    </div>
                </Scrollbar>
            </div>
            <div ref={scrollbarPodcastsContainerRef} className={classes.podcastMenuPodcastsWrapper}>
                <Scrollbar
                    contentRef={scrollbarPodcastsContentRef}
                    containerRef={scrollbarPodcastsContainerRef}
                    fullHeightScrollbar
                    ref={scrollbarRef}
                >
                    <div ref={scrollbarPodcastsContentRef} className={classes.podcastMenuPodcastsContainer}>
                        {shownPodcasts.map((podcast) => {
                            const handleStartPodcast = () => {
                                if (currentPodcast?.id === podcast.id && isCurrentAudioPlaying) {
                                    onClose();
                                    scrollToPodcastById(podcast.id);
                                    return;
                                }
                                podcast.playAudio?.();
                                setCurrentPodcast(podcast);
                            };
                            return (
                                <div
                                    key={podcast.id}
                                    className={classes.podcastMenuPodcast}
                                    onClick={handleStartPodcast}
                                >
                                    <Image
                                        src={podcast.imageUrl || ''}
                                        classes={{
                                            container: classes.podcastMenuPodcastImg,
                                            imageActionIconContainer: classes.podcastMenuPodcastPlayingButton,
                                        }}
                                        alwaysShowActionButton={
                                            currentPodcast?.id === podcast.id && isCurrentAudioPlaying
                                        }
                                        actionElement={
                                            currentPodcast?.id === podcast.id &&
                                            isCurrentAudioPlaying && <PlayingIcon />
                                        }
                                    />
                                    <div className={classes.podcastMenuPodcastInfo}>
                                        <div className={classes.podcastMenuPodcastInfoMeta}>
                                            <p className={classes.podcastMenuPodcastSeason}>{podcast.season}</p>
                                            <p className={classes.podcastMenuPodcastDate}>
                                                {dateFormatter.format(podcast.releaseDate)}
                                            </p>
                                        </div>
                                        <p className={classes.podcastMenuPodcastTitle}>{podcast.title}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </Scrollbar>
            </div>
        </div>
    );
}
