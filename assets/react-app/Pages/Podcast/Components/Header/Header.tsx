import { AudioPlayer, AudioPlayerRef } from '@UI/Media/AudioPlayer/AudioPlayer';
import { Icon } from '@UI/Icon/Icon';
import { Image } from '@UI/Layout/Image';
import { Loader } from '@UI/Icon/Loader/Loader';
import { ShareButton } from '@UI/Buttons/ShareButton/ShareButton';
import { Translator } from '@Services/Translator';
import { useAppSettingsContext } from '@Root/Contexts/AppSettingsContext';
import { usePodcastsActions, usePodcastsValues } from '../../PodcastContext';
import { useReactMediaQuery } from '@Root/Contexts/MediaQueryContext';
import AwardTravelLogo from '../../Assets/award-travel-logo@1x.png';
import AwardTravelLogoRetina from '../../Assets/award-travel-logo@2x.png';
import React, { memo, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './Header.module.scss';

export const Header = memo(() => {
    const [isAudioPlaying, setIsAudioPlaying] = useState(false);
    const [isAudioLoading, setIsAudioLoading] = useState(false);
    const [isFooterShown, setIsFooterShown] = useState(false);
    const [isFooterWatched, setIsFooterWatched] = useState(false);
    const footerContentRef = useRef<HTMLDivElement>(null);
    const isMobileView = useReactMediaQuery('<=md');

    const locale = useAppSettingsContext().localeForIntl;
    const audioPlayerRef = useRef<null | AudioPlayerRef>(null);

    const { latestPodcast, allPodcasts } = usePodcastsValues();
    const { setCurrentPodcast } = usePodcastsActions();

    const formattedDate = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'numeric', day: 'numeric' }).format(
                latestPodcast?.releaseDate || undefined,
            ),
        [locale, latestPodcast],
    );

    const toggleAudioPlayPause = useCallback(() => {
        audioPlayerRef.current?.togglePlayPause();
    }, []);

    const toggleFooterContent = useCallback(() => {
        if (isMobileView) {
            if (!isFooterWatched) {
                setIsFooterWatched(true);
            }
            setIsFooterShown((prev) => !prev);
            if (footerContentRef.current) {
                if (footerContentRef.current.style.height) {
                    footerContentRef.current.style.height = '';
                } else {
                    footerContentRef.current.style.height = `${footerContentRef.current.scrollHeight + 22}px`;
                }
            }
        }
    }, [isMobileView, isFooterWatched]);

    const onAudioPlay = () => {
        if (latestPodcast) {
            setCurrentPodcast(latestPodcast);
        }
    };

    useEffect(() => {
        if (allPodcasts.length > 0) {
            const lastPodcastInAllPodcasts = allPodcasts.reduce((latest, current) => {
                return new Date(current.releaseDate) > new Date(latest.releaseDate) ? current : latest;
            });
            lastPodcastInAllPodcasts.playAudio = audioPlayerRef.current?.togglePlayPause;
        }
    }, [allPodcasts]);

    if (!latestPodcast) {
        return null;
    }
    return (
        <div className={classes.header} id={latestPodcast.id}>
            <div className={classes.headerTag}>
                <svg width="17" height="14" viewBox="0 0 17 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        fillRule="evenodd"
                        clipRule="evenodd"
                        d="M0.694398 2.82813L12.8314 0.0186381C13.2372 -0.0750116 13.5931 0.193426 13.6181 0.617972L14.1862 9.63332C14.2112 10.0579 13.899 10.4762 13.4994 10.5698L1.35619 13.3793C0.950373 13.473 0.594536 13.2045 0.569563 12.7862L0.00140007 3.77084C-0.0235732 3.34629 0.288583 2.92799 0.694398 2.83434V2.82813Z"
                        fill="white"
                    />
                    <path
                        opacity="0.3"
                        d="M16.5411 7.45428H15.8169C15.5235 7.45428 15.28 7.2108 15.28 6.91737C15.28 6.62393 15.5172 6.38045 15.8169 6.38045H16.4475L16.2102 3.68958C16.1728 3.27128 15.7982 2.92163 15.3799 2.92163H14.8804L15.3049 9.56452C15.3674 10.526 14.6806 11.45 13.7504 11.6623L6.05859 13.4416H16.2976C16.7159 13.4416 17.0343 13.0982 16.9969 12.6799L16.5411 7.45428Z"
                        fill="white"
                    />
                </svg>
                Podcasts
            </div>
            <a href="/blog/advertiser-disclosure/" target="_blank" className={classes.headerAdvertiserDiscloserLink}>
                {Translator.trans('advertiser.disclosure')}
            </a>
            <div className={classes.headerContentContainer}>
                <div className={classes.headerPodcastMetaWrapper}>
                    <div className={classes.headerPodcastMetaContainer}>
                        <span className={classes.headerPodcastMetaSeason}>{latestPodcast.season} </span>
                        <span className={classes.headerPodcastMetaDate}>{formattedDate}</span>
                    </div>
                    <ShareButton
                        url={`${window.location.protocol}//${window.location.host}/podcast#${latestPodcast.id}`}
                        iconColor="primary"
                    />
                </div>
                <h2 className={classes.headerPodcastTitle}>{latestPodcast.title}</h2>
                <p
                    className={classes.headerPodcastDescription}
                    dangerouslySetInnerHTML={{ __html: latestPodcast.description }}
                ></p>
                <div className={classes.headerPodcastAudioPlayer}>
                    <AudioPlayer
                        src={latestPodcast.audioUrl}
                        variant="primary"
                        ref={audioPlayerRef}
                        onAudioToggle={setIsAudioPlaying}
                        onAudioChangeLoadingState={setIsAudioLoading}
                        durationInSec={latestPodcast.duration}
                        defaultCurrent
                        onAudioPlayButtonClick={onAudioPlay}
                    />
                </div>
                <Image
                    actionElement={
                        isAudioLoading ? (
                            <Loader
                                size="medium"
                                classes={{
                                    circle: classes.headerImgActionIconLoaderCircle,
                                    backgroundCircle: classes.headerImgActionIconLoaderBackground,
                                }}
                            />
                        ) : (
                            <Icon
                                className={classNames(classes.headerImgActionIcon, {
                                    [classes.headerImgActionIconPlay as string]: !isAudioPlaying,
                                })}
                                type={isAudioPlaying ? 'Pause' : 'Play'}
                            />
                        )
                    }
                    actionCallback={toggleAudioPlayPause}
                    src={latestPodcast.imageUrl || ''}
                    classes={{
                        container: classes.headerImgContainer,
                        img: classes.headerImg,
                        previewImg: classes.headerImgStateContainer,
                        imageActionIconContainer: classes.headerImgActionIconContainer,
                    }}
                    isActionBlocked={isAudioLoading}
                    hideErrorMessage
                    alwaysShowActionButton
                />
            </div>
            {latestPodcast.footer && (
                <div className={classes.headerConclusion}>
                    <div className={classes.headerConclusionContainer}>
                        <div className={classes.headerConclusionMainBlock} onClick={toggleFooterContent}>
                            <Image
                                src={AwardTravelLogo}
                                srcSet={`${AwardTravelLogo} 1x, ${AwardTravelLogoRetina} 2x`}
                                alt="AwardTravel Logo"
                                classes={{ container: classes.headerConclusionImg }}
                            />
                            <h3 className={classes.headerConclusionTitle}>Where to Find Us</h3>
                            <div className={classes.headerConclusionMobileIconsContainer}>
                                <div
                                    className={classNames(classes.headerConclusionMobileIconIndicator, {
                                        [classes.headerConclusionMobileIconIndicatorWatched as string]: isFooterWatched,
                                    })}
                                />
                                <div
                                    className={classNames(classes.headerConclusionMobileArrow, {
                                        [classes.headerConclusionMobileArrowShown as string]: isFooterShown,
                                    })}
                                >
                                    <Icon type="ArrowDown" size="small" />
                                </div>
                            </div>
                        </div>

                        <div
                            ref={footerContentRef}
                            className={classNames(classes.headerConclusionContent, {
                                [classes.headerConclusionContentShown as string]: isFooterShown,
                            })}
                            dangerouslySetInnerHTML={{ __html: latestPodcast.footer }}
                        />
                    </div>
                </div>
            )}
        </div>
    );
});

Header.displayName = 'Header';
