import { Image } from '@UI/Layout/Image';
import React from 'react';
import classes from './Step.module.scss';

interface StepProps {
    id?: string;
    number: number;
    description: string;
    imgUrl: string;
    imgRetina: string;
}

export function Step({ number, description, imgUrl, imgRetina, id }: StepProps) {
    return (
        <div id={id} className={classes.settingStepContainer}>
            <div className={classes.settingStepNumber}>{number < 10 ? `0${number}` : number}</div>
            <div className={classes.settingStepDescriptionContainer}>
                <p className={classes.settingStepDescription} dangerouslySetInnerHTML={{ __html: description }}></p>
            </div>
            <div className={classes.settingStepImgGridCell}>
                <Image
                    src={imgUrl}
                    classes={{ img: classes.settingStepImg, previewImg: classes.settingStepImgPreview }}
                    srcSet={`${imgUrl} 1x, ${imgRetina} 2x`}
                    preview
                    previewSrc={imgRetina}
                />
            </div>
        </div>
    );
}
