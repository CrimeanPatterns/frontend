import { PrimaryButton } from '@UI/Buttons';
import { Translator } from '@Services/Translator';
import React from 'react';
import classes from './ErrorPage.module.scss';

type ErrorPageProps = {
    hideDefaultButton?: boolean;
    errorText?: string;
};

export function ErrorPage({ errorText, hideDefaultButton }: ErrorPageProps) {
    const onClick = () => {
        location.reload();
    };
    return (
        <div className={classes.errorPage}>
            <div className={classes.errorPageImageContainer}>
                <svg width="160" height="49" viewBox="0 0 160 49" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="159" height="49" />
                    <path
                        d="M18.0799 34.8041H5.45291C2.43706 34.8041 0 32.3518 0 29.3512V19.6639C0 18.0037 0.746344 16.511 1.91918 15.5057C2.87876 14.6984 4.11253 14.1958 5.45291 14.1958H18.0799V34.8041Z"
                        fill="#ADB1BB"
                    />
                    <path
                        d="M47.5081 45.5577H22.2846C18.3854 45.5577 15.2324 42.4048 15.2324 38.5055V10.4946C15.2324 6.59533 18.3854 3.44238 22.2846 3.44238H47.5081V45.5577Z"
                        fill="#D8DADF"
                    />
                    <path
                        d="M53.0673 0.929123V48.0709C53.0673 48.5887 52.6561 49 52.1382 49H48.437C47.9191 49 47.5078 48.5887 47.5078 48.0709V0.929123C47.5078 0.411249 47.9191 0 48.437 0H52.1382C52.6561 0 53.0673 0.411249 53.0673 0.929123Z"
                        fill="#ECEEF3"
                    />
                    <path
                        d="M53.0684 18.1713V10.9972H70.2343C72.2144 10.9972 73.829 12.6117 73.829 14.5918C73.829 16.5719 72.2297 18.1865 70.2343 18.1865H53.0684V18.1713Z"
                        fill="#CED0D5"
                    />
                    <path
                        d="M53.0684 38.0028V30.8287H70.2343C72.2144 30.8287 73.829 32.428 73.829 34.4234C73.829 36.4035 72.2297 38.018 70.2343 38.018H53.0684V38.0028Z"
                        fill="#CED0D5"
                    />
                    <path d="M55.5801 18.1759V11.0018H53.0669V18.1759H55.5801Z" fill="#ADB1BB" />
                    <path d="M55.584 37.996V30.8219H53.0708V37.996H55.584Z" fill="#ADB1BB" />
                    <path
                        d="M140.986 34.8041H153.613C156.629 34.8041 159.066 32.3518 159.066 29.3512V19.6639C159.066 18.0037 158.32 16.511 157.147 15.5057C156.187 14.6984 154.954 14.1958 153.613 14.1958H140.986V34.8041Z"
                        fill="#ADB1BB"
                    />
                    <path
                        d="M111.561 45.5577H136.784C140.683 45.5577 143.836 42.4048 143.836 38.5055V10.4946C143.836 6.59533 140.683 3.44238 136.784 3.44238H111.561V45.5577Z"
                        fill="#D8DADF"
                    />
                    <path
                        d="M106 0.929123V48.0709C106 48.5887 106.411 49 106.929 49H110.63C111.148 49 111.56 48.5887 111.56 48.0709V0.929123C111.56 0.411249 111.148 0 110.63 0H106.929C106.411 0 106 0.411249 106 0.929123Z"
                        fill="#ECEEF3"
                    />
                </svg>
            </div>
            <div className={classes.errorPageTextContainer}>
                <h2 className={classes.errorPageTitle}>{Translator.trans('error.server.other.title')}</h2>
                <p className={classes.errorPageDescription}>
                    {errorText === undefined &&
                        Translator.trans(
                            /** @Desc("This page didn’t load correctly. Try loading the page to fix this.") */ 'page.loaded.with.error',
                        )}
                    {errorText}
                </p>
            </div>
            {!hideDefaultButton && (
                <PrimaryButton
                    text={Translator.trans('reload')}
                    className={{ button: classes.errorPageButton }}
                    onClick={onClick}
                />
            )}
        </div>
    );
}
