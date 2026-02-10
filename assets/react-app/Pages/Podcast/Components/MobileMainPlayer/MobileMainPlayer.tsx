import { AudioPlayer } from '@UI/Media/AudioPlayer/AudioPlayer';
import { IconButton } from '@UI/Buttons';
import { MobilePodcastMenu, MobilePodcastMenuRef } from '../MobilePodcastMenu/MobilePodcastMenu';
import { PodcastMenuButton } from '../PodcastMenuButton/PodcastMenuButton';
import { useAudioManager } from '@Root/Contexts/AudioManagerContext';
import { useCurrentPodcast } from '../../PodcastContext';
import React, { useRef } from 'react';
import classes from './MobileMainPlayer.module.scss';

export function MobileMainPlayer() {
    const audioManager = useAudioManager();
    const { currentPodcast, setNextPodcast, setPreviousPodcast } = useCurrentPodcast();
    const mobilePodcastMenuRef = useRef<MobilePodcastMenuRef>(null);

    const onMenuButtonClick = () => {
        mobilePodcastMenuRef.current?.open();
    };

    return (
        <>
            <div className={classes.mobileMainPlayer}>
                <div className={classes.mobileMainPlayerTools}>
                    <PodcastMenuButton
                        currentEpisodeNumber={currentPodcast?.episodeNumber}
                        currentSeason={currentPodcast?.season}
                        onButtonClick={onMenuButtonClick}
                    />
                    <div className={classes.mobileMainPlayerControlButtons}>
                        <IconButton
                            iconType="Previous"
                            className={{ button: classes.mobileMainPlayerControlButton }}
                            iconColor="disabled"
                            onClick={setPreviousPodcast}
                        />
                        <IconButton
                            iconType="Next"
                            className={{ button: classes.mobileMainPlayerControlButton }}
                            iconColor="disabled"
                            onClick={setNextPodcast}
                        />
                    </div>
                </div>
                <AudioPlayer
                    classes={{ container: classes.mobileMainPlayerAudioPlayer }}
                    audio={audioManager.currentAudio}
                    src=""
                    variant="secondary"
                />
            </div>
            <MobilePodcastMenu ref={mobilePodcastMenuRef} />
        </>
    );
}
