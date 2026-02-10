import { Podcast } from '../../UseFetchPodcasts';
import { PodcastsCard } from './Components/PodcastCard';
import { usePodcastsValues } from '../../PodcastContext';
import React, { useEffect, useState } from 'react';
import classes from './Podcasts.module.scss';

export function Podcasts() {
    const [filteredPodcasts, setFilteredPodcasts] = useState<Podcast[]>([]);

    const { allPodcasts, latestPodcast } = usePodcastsValues();

    useEffect(() => {
        setFilteredPodcasts(allPodcasts.filter((podcast) => podcast.id !== latestPodcast?.id));
    }, [allPodcasts]);
    return (
        <div className={classes.podcasts}>
            {filteredPodcasts.map((podcast) => {
                return <PodcastsCard key={podcast.id} podcast={podcast} />;
            })}
        </div>
    );
}
