import { Podcast } from './UseFetchPodcasts';
import React, { PropsWithChildren, createContext, useCallback, useContext, useMemo, useState } from 'react';

type PodcastsActions = {
    setAllPodcasts: (podcasts: Podcast[]) => void;
    setCurrentPodcast: (podcast: Podcast) => void;
};
type PodcastsValues = {
    allPodcasts: Podcast[];
    latestPodcast: Podcast | null;
};

type CurrentPodcastValues = {
    currentPodcast: Podcast | null;
    setPreviousPodcast: () => void;
    setNextPodcast: () => void;
};

const PodcastsValuesContext = createContext<PodcastsValues | undefined>(undefined);
const PodcastsActionsContext = createContext<PodcastsActions | undefined>(undefined);
const CurrentPodcastContext = createContext<CurrentPodcastValues | undefined>(undefined);

export const PodcastsProvider = ({ children }: PropsWithChildren) => {
    const [allPodcasts, setAllPodcasts] = useState<Podcast[]>([]);
    const [latestPodcast, setLatestPodcast] = useState<Podcast | null>(null);
    const [currentPodcast, setCurrentPodcast] = useState<Podcast | null>(null);

    const retrieveLatestPodcast = useCallback((podcasts: Podcast[]) => {
        return podcasts.reduce((latest, current) => {
            return new Date(current.releaseDate) > new Date(latest.releaseDate) ? current : latest;
        });
    }, []);

    const setAllPodcastsAndLatestPodcast = (podcasts: Podcast[]) => {
        setAllPodcasts(podcasts);
        const latestPodcast = retrieveLatestPodcast(podcasts);
        setLatestPodcast(latestPodcast);
        setCurrentPodcast(latestPodcast);
    };

    const setPreviousPodcast = () => {
        if (currentPodcast) {
            let previousPodcastEpisode = currentPodcast.episodeNumber - 1;
            let previousPodcastSeason = currentPodcast.seasonNumber;

            if (previousPodcastEpisode < 0 && previousPodcastSeason === 1) {
                setCurrentPodcast(latestPodcast);
                latestPodcast?.playAudio?.();
                return;
            } else if (previousPodcastEpisode < 0) {
                previousPodcastSeason -= 1;
                const lastPodcastInPreviousSeason = allPodcasts.filter(
                    (podcast) => podcast.seasonNumber === previousPodcastSeason,
                );
                const lastEpisodeInPreviousSeason = lastPodcastInPreviousSeason.sort(
                    (a, b) => b.episodeNumber - a.episodeNumber,
                )[0];

                if (lastEpisodeInPreviousSeason) {
                    previousPodcastEpisode = lastEpisodeInPreviousSeason.episodeNumber;
                }
            }

            const previousPodcast = allPodcasts.find(
                (podcast) =>
                    podcast.seasonNumber === previousPodcastSeason && podcast.episodeNumber === previousPodcastEpisode,
            );

            if (previousPodcast) {
                setCurrentPodcast(previousPodcast);
                previousPodcast.playAudio?.();
            }
        }
    };

    const setNextPodcast = () => {
        if (currentPodcast) {
            let nextPodcastEpisode = currentPodcast.episodeNumber + 1;
            let nextPodcastSeason = currentPodcast.seasonNumber;

            const episodesInCurrentSeason = allPodcasts
                .filter((podcast) => podcast.seasonNumber === nextPodcastSeason)
                .sort((a, b) => a.episodeNumber - b.episodeNumber);

            const maxEpisodeInCurrentSeason =
                // In current season exists at least one episode
                // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
                episodesInCurrentSeason[episodesInCurrentSeason.length - 1]!.episodeNumber;

            if (nextPodcastEpisode > maxEpisodeInCurrentSeason) {
                nextPodcastSeason += 1;
                nextPodcastEpisode = 0;

                const episodesInNextSeason = allPodcasts
                    .filter((podcast) => podcast.seasonNumber === nextPodcastSeason)
                    .sort((a, b) => a.episodeNumber - b.episodeNumber);

                const firstEpisodeInNextSeason = episodesInNextSeason[0];

                if (firstEpisodeInNextSeason) {
                    nextPodcastEpisode = firstEpisodeInNextSeason.episodeNumber;
                } else {
                    const firstSeason = Math.min(...allPodcasts.map((podcast) => podcast.seasonNumber));
                    const episodesInFirstSeason = allPodcasts
                        .filter((podcast) => podcast.seasonNumber === firstSeason)
                        .sort((a, b) => a.episodeNumber - b.episodeNumber);

                    const firstEpisodeInFirstSeason = episodesInFirstSeason[0];

                    if (firstEpisodeInFirstSeason) {
                        nextPodcastSeason = firstSeason;
                        nextPodcastEpisode = firstEpisodeInFirstSeason.episodeNumber;
                    }
                }
            }

            const nextPodcast = allPodcasts.find(
                (podcast) => podcast.seasonNumber === nextPodcastSeason && podcast.episodeNumber === nextPodcastEpisode,
            );

            if (nextPodcast) {
                setCurrentPodcast(nextPodcast);
                nextPodcast.playAudio?.();
            }
        }
    };

    const podcastsValues: PodcastsValues = useMemo(
        () => ({
            allPodcasts,
            latestPodcast,
        }),
        [allPodcasts, latestPodcast],
    );

    const podcastsActionsValues: PodcastsActions = useMemo(
        () => ({
            setAllPodcasts: setAllPodcastsAndLatestPodcast,
            setCurrentPodcast,
        }),
        [allPodcasts, latestPodcast],
    );

    const currentPodcastValues: CurrentPodcastValues = useMemo(
        () => ({
            currentPodcast,
            setPreviousPodcast,
            setNextPodcast,
        }),
        [currentPodcast, setPreviousPodcast, setNextPodcast],
    );

    return (
        <PodcastsValuesContext.Provider value={podcastsValues}>
            <PodcastsActionsContext.Provider value={podcastsActionsValues}>
                <CurrentPodcastContext.Provider value={currentPodcastValues}>{children}</CurrentPodcastContext.Provider>
            </PodcastsActionsContext.Provider>
        </PodcastsValuesContext.Provider>
    );
};

export const usePodcastsValues = (): PodcastsValues => {
    const context = useContext(PodcastsValuesContext);
    if (!context) {
        throw new Error('usePodcastsValues must be used within a PodcastsProvider');
    }
    return context;
};

export const usePodcastsActions = (): PodcastsActions => {
    const context = useContext(PodcastsActionsContext);
    if (!context) {
        throw new Error('usePodcastsActions must be used within a PodcastsProvider');
    }
    return context;
};

export const useCurrentPodcast = (): CurrentPodcastValues => {
    const context = useContext(CurrentPodcastContext);
    if (!context) {
        throw new Error('useCurrentPodcast must be used within a PodcastsProvider');
    }
    return context;
};
