import { Align, Popover, PositionFromAnchor } from '@UI/Popovers';
import { AudioPlayer } from '@UI/Media/AudioPlayer/AudioPlayer';
import { CircularProgress } from '@UI/Feedback/CircleProgress/CircleProgress';
import { IconButton, TextButton } from '@UI/Buttons';
import { PodcastMenu } from '../PodcastMenu/PodcastMenu';
import { PodcastMenuButton } from '../PodcastMenuButton/PodcastMenuButton';
import { useAudioManager } from '@Root/Contexts/AudioManagerContext';
import { useCurrentPodcast, usePodcastsValues } from '../../PodcastContext';
import React, { useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './DesktopMainPlayer.module.scss';

export function DesktopMainPlayer() {
    const { allPodcasts } = usePodcastsValues();
    const { currentPodcast, setPreviousPodcast, setNextPodcast } = useCurrentPodcast();

    const [isMenuPopoverOpen, setIsMenuPopoverOpen] = useState(false);
    const [isCollapsed, setIsCollapsed] = useState(false);
    const [isExpanded, setIsExpanded] = useState(true);

    const audioManager = useAudioManager();
    const popoverAnchorRef = useRef<HTMLDivElement>(null);

    const openPopover = () => {
        setIsMenuPopoverOpen(true);
    };

    const closePopover = () => {
        setIsMenuPopoverOpen(false);
    };

    const onMenuButtonClick = () => {
        openPopover();
    };

    const openPanel = () => {
        setIsCollapsed(false);
        if (audioManager.isCurrentAudioPlaying) {
            audioManager.pauseCurrentAudio();
        } else {
            audioManager.playCurrentAudio();
        }
    };

    const collapsePanel = () => {
        setIsCollapsed(true);
        setIsExpanded(false);
    };

    const handleTransitionEnd = () => {
        if (!isCollapsed) {
            setIsExpanded(true);
        }
    };

    const progress =
        audioManager.duration && audioManager.currentTime
            ? (audioManager.currentTime / audioManager.duration) * 100
            : 0;

    return (
        <div
            className={classNames(classes.mainPlayer, {
                [classes.mainPlayerCollapsed as string]: isCollapsed,
                [classes.mainPlayerExpanded as string]: !isCollapsed,
            })}
            ref={popoverAnchorRef}
            onTransitionEnd={handleTransitionEnd}
        >
            {!isCollapsed && isExpanded && (
                <div className={classes.mainPlayerContentWrapper}>
                    <PodcastMenuButton
                        currentEpisodeNumber={currentPodcast?.episodeNumber}
                        currentSeason={currentPodcast?.season}
                        onButtonClick={onMenuButtonClick}
                    />
                    <div className={classes.mainPlayerControlButtons}>
                        <IconButton
                            iconType="Previous"
                            className={{ button: classes.mainPlayerControlButton }}
                            iconColor="disabled"
                            onClick={setPreviousPodcast}
                        />
                        <IconButton
                            iconType="Next"
                            className={{ button: classes.mainPlayerControlButton }}
                            iconColor="disabled"
                            onClick={setNextPodcast}
                        />
                    </div>
                    <AudioPlayer
                        variant="secondary"
                        classes={{ container: classes.mainPlayerAudioPlayerContainer }}
                        durationInSec={audioManager.currentAudio?.duration}
                        src=""
                        audio={audioManager.currentAudio}
                    />
                    <TextButton
                        className={{ button: classes.mainPlayerWrapButton }}
                        text={null}
                        onClick={collapsePanel}
                    />
                    <Popover
                        open={isMenuPopoverOpen}
                        anchor={popoverAnchorRef}
                        onClose={closePopover}
                        positionFromAnchor={PositionFromAnchor.Above}
                        align={Align.Left}
                        offsetFromAnchorInPx={34}
                        lockGlobalScroll
                        closeTrigger="click"
                    >
                        {currentPodcast && <PodcastMenu podcasts={allPodcasts} onClose={closePopover} />}
                    </Popover>
                </div>
            )}
            {isCollapsed && (
                <div className={classes.mainPlayerContentWrapper}>
                    <CircularProgress
                        percent={progress}
                        radius={35}
                        classes={{
                            container: classes.mainPlayerCircularProgress,
                        }}
                    >
                        <IconButton
                            type="button"
                            iconType={audioManager.isCurrentAudioPlaying ? 'Pause' : 'Play'}
                            className={{ button: classes.mainPlayerPlayButton }}
                            onClick={openPanel}
                        />
                    </CircularProgress>
                </div>
            )}
        </div>
    );
}
