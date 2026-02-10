import { TextButton } from '@UI/Buttons/TextButton';
import React from 'react';
import classes from './PodcastMenuButton.module.scss';

type PodcastMenuButtonProps = {
    currentEpisodeNumber?: number;
    currentSeason?: string;
    onButtonClick: () => void;
};

export function PodcastMenuButton({ currentEpisodeNumber, currentSeason, onButtonClick }: PodcastMenuButtonProps) {
    return (
        <TextButton
            className={{ text: classes.podcastMenuButtonText, button: classes.podcastMenuButton }}
            text={
                <>
                    <div className={classes.podcastMenuButtonIcon} />
                    {`${currentSeason} #${currentEpisodeNumber}`}
                </>
            }
            onClick={onButtonClick}
        />
    );
}
