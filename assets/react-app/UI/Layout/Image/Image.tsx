import '../../Styles/UtilityStyles.scss';
import { Icon, IconType } from '@UI/Icon';
import { Translator } from '@Services/Translator';
import { ViewMediaModal } from '@UI/Popovers';
import { useOnScreen } from '@Utilities/Hooks/UseOnScreen';
import React, { ReactNode, memo, useCallback, useMemo, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './Image.module.scss';

type ImageClasses = {
    container?: string;
    previewImg?: string;
    loadingContainer?: string;
    errorContainer?: string;
    img?: string;
    imageActionIconContainer?: string;
    imgWrapper?: string;
};

export type ImageProps = {
    src: string;
    srcSet?: string;
    alt?: string;
    classes?: ImageClasses;
    preview?: boolean;
    previewSrc?: string;
    previewSrcSet?: string;
    actionIconType?: IconType;
    actionElement?: ReactNode;
    actionCallback?: () => void;
    isActionBlocked?: boolean;
    hideErrorMessage?: boolean;
    alwaysShowActionButton?: boolean;
};

const ImageBase = ({
    src,
    alt,
    srcSet,
    classes: externalClasses,
    preview,
    previewSrc,
    previewSrcSet,
    actionIconType,
    actionCallback,
    actionElement,
    isActionBlocked,
    hideErrorMessage,
    alwaysShowActionButton,
}: ImageProps) => {
    const [isPreviewOpen, setIsPreviewOpen] = useState(false);

    const [isError, setIsError] = useState(false);
    const [isLoaded, setIsLoaded] = useState(false);

    const containerRef = useRef<HTMLDivElement | null>(null);

    const isVisible = useOnScreen(containerRef, '100px');

    const errorText = useMemo(() => Translator.trans(/** @Desc("Loading Error") */ 'alerts.loading-error'), []);

    const onImageLoadError = useCallback(() => {
        setIsError(true);
    }, []);
    const onImageLoad = useCallback(() => {
        setIsLoaded(true);
    }, []);
    const onButtonClick = useCallback(() => {
        setIsPreviewOpen(true);
    }, []);
    const closePreview = useCallback(() => {
        setIsPreviewOpen(false);
    }, []);

    if (isError)
        return (
            <div
                className={classNames(
                    classes.imageError,
                    externalClasses?.container,
                    externalClasses?.previewImg,
                    externalClasses?.errorContainer,
                )}
                aria-label={alt}
            >
                {!hideErrorMessage && !alwaysShowActionButton && (
                    <>
                        <Icon type="Warning" size="big" />
                        {errorText}
                    </>
                )}
                {alwaysShowActionButton && (
                    <ImageActionButton
                        actionCallback={actionCallback}
                        actionElement={actionElement}
                        actionIconType={actionIconType}
                        alwaysShowActionButton={alwaysShowActionButton}
                        isActionBlocked={isActionBlocked}
                        classes={externalClasses}
                    />
                )}
            </div>
        );

    return (
        <div
            className={classNames(classes.imageContainer, externalClasses?.container, {
                ['skeleton']: !isLoaded,
                [classes.imageContainerLoading as string]: !isLoaded,
                [externalClasses?.previewImg || '']: !isLoaded,
                [classes.imageContainerWithPreview as string]: preview,
                [externalClasses?.loadingContainer || '']: !isLoaded,
                [classes.imageContainerLoaded as string]: isLoaded,
            })}
            ref={containerRef}
        >
            <div className={classNames(classes.imageWrapper, externalClasses?.imgWrapper)}>
                {(isVisible || isLoaded) && (
                    <img
                        className={classNames(
                            classes.image,
                            {
                                [classes.imageLoaded as string]: isLoaded,
                            },
                            externalClasses?.img,
                        )}
                        src={src}
                        srcSet={srcSet}
                        alt={alt}
                        onError={onImageLoadError}
                        onLoad={onImageLoad}
                    />
                )}

                {(actionIconType || actionElement) && (
                    <ImageActionButton
                        actionCallback={actionCallback}
                        actionElement={actionElement}
                        actionIconType={actionIconType}
                        alwaysShowActionButton={alwaysShowActionButton}
                        isActionBlocked={isActionBlocked}
                        classes={externalClasses}
                    />
                )}
                {preview && isLoaded && !actionIconType && !actionElement && (
                    <>
                        <button type="button" className={classes.imagePreviewButton} onClick={onButtonClick}>
                            <div
                                className={classNames(
                                    classes.imagePreviewIconContainer,
                                    externalClasses?.imageActionIconContainer,
                                )}
                            >
                                <Icon type="Expand" size="medium" color="active" />
                            </div>
                        </button>
                        <ViewMediaModal
                            open={isPreviewOpen}
                            src={previewSrc || src}
                            srcSet={previewSrcSet}
                            onClose={closePreview}
                            alt={alt}
                        />
                    </>
                )}
            </div>
        </div>
    );
};

export const Image = memo(ImageBase);

type ImageActionButtonProps = Pick<
    ImageProps,
    'alwaysShowActionButton' | 'actionCallback' | 'isActionBlocked' | 'actionIconType' | 'actionElement' | 'classes'
>;

const ImageActionButton = ({
    alwaysShowActionButton,
    actionCallback,
    isActionBlocked,
    actionIconType,
    actionElement,
    classes: externalClasses,
}: ImageActionButtonProps) => {
    const onActionButtonClick = useCallback(() => {
        if (!isActionBlocked) {
            actionCallback?.();
        }
    }, [isActionBlocked, actionCallback]);
    return (
        <div
            className={classNames(classes.imagePreviewButton, {
                [classes.imagePreviewButtonAlwaysShow as string]: alwaysShowActionButton,
            })}
            onClick={onActionButtonClick}
        >
            <button
                type="button"
                disabled={isActionBlocked}
                className={classNames(classes.imagePreviewIconContainer, externalClasses?.imageActionIconContainer)}
            >
                {!actionElement && actionIconType && <Icon type={actionIconType} size="medium" color="active" />}
                {actionElement}
            </button>
        </div>
    );
};
