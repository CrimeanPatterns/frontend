import './PodcastPage.scss';
import '@Bem/block/footer';
import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { AudioManagerProvider } from '@Root/Contexts/AudioManagerContext';
import { DesktopMainPlayer } from './Components/DesktopMainPlayer/DesktopMainPlayer';
import { DisclosureText } from './Components/DisclosureText/DisclosureText';
import { ErrorPane } from '@UI/Feedback/ErrorPane/ErrorPane';
import { Header } from './Components/Header/Header';
import { MobileMainPlayer } from './Components/MobileMainPlayer/MobileMainPlayer';
import { Podcasts } from './Components/Podcasts/Podcasts';
import { PodcastsProvider, usePodcastsActions } from './PodcastContext';
import { SkeletonPage } from './Components/SkeletonPage/SkeletonPage';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import { useFetchPodcasts } from './UseFetchPodcasts';
import { useScrollToHashOnRender } from './Hooks/UseScrollToHash';
import React, { useEffect } from 'react';

export default function PodcastsPageWrapper() {
    useEffect(() => {
        hideGlobalLoader();
    }, []);
    return (
        <AppSettingsProvider>
            <AudioManagerProvider>
                <PodcastsProvider>
                    <PodcastsPage />
                </PodcastsProvider>
            </AudioManagerProvider>
        </AppSettingsProvider>
    );
}

function PodcastsPage() {
    const { podcasts, isLoading, error } = useFetchPodcasts();
    const { setAllPodcasts } = usePodcastsActions();

    useEffect(() => {
        if (podcasts) {
            setAllPodcasts(podcasts);
        }
    }, [podcasts]);

    useScrollToHashOnRender();

    if (isLoading) return <SkeletonPage />;

    if (error) return <ErrorPane />;

    return (
        <>
            <Header />
            <DisclosureText />
            <Podcasts />
            <DesktopMainPlayer />
            <MobileMainPlayer />
        </>
    );
}
