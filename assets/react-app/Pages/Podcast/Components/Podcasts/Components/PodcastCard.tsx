import { AudioPlayer, AudioPlayerRef } from '@UI/Media/AudioPlayer/AudioPlayer';
import { Icon } from '@UI/Icon/Icon';
import { Image } from '@UI/Layout/Image';
import { Loader } from '@UI/Icon/Loader/Loader';
import { Podcast } from '@Root/Pages/Podcast/UseFetchPodcasts';
import { ShareButton } from '@UI/Buttons/ShareButton/ShareButton';
import { useAppSettingsContext } from '@Root/Contexts/AppSettingsContext';
import { usePodcastsActions } from '@Root/Pages/Podcast/PodcastContext';
import React, { forwardRef, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './PodcastCard.module.scss';

type PodcastCardProps = {
    podcast: Podcast;
};

export const PodcastsCard = forwardRef<HTMLDivElement, PodcastCardProps>(({ podcast }, ref) => {
    const [isAudioPlaying, setIsAudioPlaying] = useState(false);
    const [isAudioLoading, setIsAudioLoading] = useState(false);

    const audioPlayerRef = useRef<null | AudioPlayerRef>(null);
    const { setCurrentPodcast } = usePodcastsActions();

    const locale = useAppSettingsContext().localeForIntl;

    const formattedDate = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric',
            }).format(podcast.releaseDate),
        [podcast.releaseDate, locale],
    );

    const toggleAudioPlayPause = useCallback(() => {
        audioPlayerRef.current?.togglePlayPause();
    }, []);

    const onAudioPlay = () => {
        setCurrentPodcast(podcast);
    };

    useEffect(() => {
        podcast.playAudio = toggleAudioPlayPause;
    }, []);
    return (
        <div ref={ref} className={classes.podcastCard} id={podcast.id}>
            <Image
                actionElement={
                    isAudioLoading ? (
                        <Loader
                            size="medium"
                            classes={{
                                circle: classes.podcastCardImageLoaderCircle,
                                backgroundCircle: classes.podcastCardImageLoaderBackground,
                            }}
                        />
                    ) : (
                        <Icon
                            className={classNames(classes.podcastCardImageActionIcon, {
                                [classes.podcastCardImageActionIconPlay as string]: !isAudioPlaying,
                            })}
                            type={isAudioPlaying ? 'Pause' : 'Play'}
                        />
                    )
                }
                actionCallback={toggleAudioPlayPause}
                src={podcast.imageUrl || ''}
                classes={{
                    container: classes.podcastCardPreviewContainer,
                    img: classes.podcastCardPreview,
                    previewImg: classes.podcastCardPreviewContainer,
                    imageActionIconContainer: classes.podcastCardImageActionIconContainer,
                }}
                isActionBlocked={isAudioLoading}
                hideErrorMessage
                alwaysShowActionButton
            />
            <div className={classes.podcastCardMetaInfoWrapper}>
                <div className={classes.podcastCardMetaInfoContainer}>
                    <span className={classes.podcastCardSeason}>{podcast.season}</span>
                    <span className={classes.podcastCardReleaseDate}>{formattedDate}</span>
                </div>
                <ShareButton url={`${window.location.protocol}//${window.location.host}/podcast#${podcast.id}`} />
            </div>
            <h3 className={classes.podcastCardTitle}>{podcast.title}</h3>
            <p className={classes.podcastCardDescription} dangerouslySetInnerHTML={{ __html: podcast.description }}></p>
            <div className={classes.podcastCardPlayerContainer}>
                <AudioPlayer
                    ref={audioPlayerRef}
                    src={podcast.audioUrl}
                    variant="secondary"
                    onAudioToggle={setIsAudioPlaying}
                    durationInSec={podcast.duration}
                    onAudioChangeLoadingState={setIsAudioLoading}
                    onAudioPlayButtonClick={onAudioPlay}
                />
            </div>
        </div>
    );
});

PodcastsCard.displayName = 'PodcastsCard';
