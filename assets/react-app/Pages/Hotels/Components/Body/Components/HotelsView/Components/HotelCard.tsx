import { Hotel } from '../../../../../Contexts/HotelContext/HotelContext';
import { Icon } from '@UI/Icon';
import { Image } from '@UI/Layout/Image';
import { Modal } from '@UI/Popovers/Modal';
import { ModalContent } from './ModalContent/ModalContent';
import { PrimaryButton } from '@UI/Buttons/PrimaryButton';
import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { RedemptionAssessment } from './RedemptionAssesment';
import { TextButton } from '@UI/Buttons/TextButton';
import { Translator } from '@Services/Translator';
import { getHotelProviderLogo } from '@Root/Pages/Hotels/Utilities';
import { useAppSettingsContext } from '@Root/Contexts/AppSettingsContext';
import { useReactMediaQuery } from '@Root/Contexts/MediaQueryContext';
import React, { memo, useCallback, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './HotelCard.module.scss';

interface HotelCardProps {
    hotel: Hotel;
}

export const HotelCard = memo(({ hotel }: HotelCardProps) => {
    const { localeForIntl } = useAppSettingsContext();

    const showSeparator = useReactMediaQuery('>xl');

    const [isRedemptionPopoverOpen, setIsRedemptionPopoverOpen] = useState(false);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const redemptionRef = useRef<null | HTMLDivElement>(null);

    const onClose = useCallback(() => {
        setIsModalOpen(false);
    }, []);

    const formatter = useRef(
        new Intl.DateTimeFormat(localeForIntl, { month: 'long', day: 'numeric', year: 'numeric' }),
    ).current;

    const onHandleClick = useCallback(() => {
        if (hotel.transferOptions && hotel.transferOptions.length > 0) {
            setIsModalOpen(true);
            return;
        }
    }, []);

    const onRedemptionBlockMouseIn = useCallback(() => {
        setIsRedemptionPopoverOpen(true);
    }, []);
    return (
        <li className={classes.cardContainer}>
            <div className={classes.leftContentContainer}>
                <Image
                    src={hotel.thumb}
                    classes={{ img: classes.image, loadingContainer: classes.image, errorContainer: classes.image }}
                />
                <div>
                    <h3 className={classes.hotelName}>{hotel.name}</h3>
                    <p className={classes.hotelAddress}>{hotel.address}</p>
                    <div className={classes.hotelAdditionalInfoContainer}>
                        <div className={classes.dateInfo}>
                            <Icon type="Calendar" size="small" />
                            <span>{`${formatter.format(new Date(hotel.checkInDate))} - ${formatter.format(
                                new Date(hotel.checkOutDate),
                            )}`}</span>
                        </div>
                        {hotel.rating && (
                            <div className={classes.rate}>
                                <Icon type="Star" size="small" color="primary" />
                                <span>{hotel.ratingFormatted}</span>
                            </div>
                        )}
                        <div className={classes.distanceInfo}>{`${hotel.distanceFormatted} ${Translator.trans(
                            /** @Desc("km") */ 'km',
                        )}`}</div>
                    </div>
                    <div className={classes.assessmentsContainer}>
                        <div className={classes.redemptionBlock}>
                            <span className={classes.redemptionValue}>4.54 ¢</span>
                            <span className={classes.redemptionDescription}>
                                {Translator.trans('redemption-value')}
                            </span>
                        </div>
                        {showSeparator && <Separator />}
                        <div
                            className={classes.redemptionBlock}
                            onMouseEnter={onRedemptionBlockMouseIn}
                            onMouseLeave={() => {
                                setIsRedemptionPopoverOpen(false);
                            }}
                            ref={redemptionRef}
                        >
                            <span className={classes.redemptionValue}>Excellent Redemption</span>
                            <span className={classes.redemptionDescription}>
                                {Translator.trans(/** @Desc("AwardWallet Assessment") */ 'awardwallet-assessment')}
                            </span>
                            <RedemptionAssessment anchor={redemptionRef} isPopoverOpen={isRedemptionPopoverOpen} />
                        </div>
                        {showSeparator && <Separator />}
                        <div className={classes.redemptionBlock}>
                            <span className={classes.averagePrice}>{hotel.cashPerNightFormatted}</span>
                            <span className={classes.redemptionDescription}>
                                {Translator.trans(/** @Desc("Avg / per Night") */ 'avg-per-night')}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div className={classes.rightContentContainer}>
                <div className={classes.bookContainer}>
                    <div className={classes.brandAndCostContainer}>
                        {hotel.transferOptions && hotel.transferOptions.length > 0 ? (
                            <div className={classes.exchangeLogoContainer}>
                                <div style={{ position: 'absolute', width: '100%', maxWidth: 54, height: 28 }}>
                                    <div className={classNames(classes.exchangeLogo, classes.exchangeLogoFrom)}>
                                        {getHotelProviderLogo(hotel.transferOptions[0]?.fromBrand as ProviderBrand)}
                                    </div>
                                    <div className={classes.exchangeArrow}>
                                        <svg
                                            width="9"
                                            height="12"
                                            viewBox="0 0 9 12"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                opacity="0.3"
                                                fillRule="evenodd"
                                                clipRule="evenodd"
                                                d="M0.57232 0.963674C2.05889 1.1654 3.35251 1.8166 4.45317 2.91726C5.02216 3.48625 5.48178 4.22056 5.83204 5.1202C6.1823 6.01984 6.34415 7.01463 6.31758 8.10454L6.33648 8.30951L7.75866 6.88733L8.71008 7.83875L5.50684 11.042L2.13489 7.67005L3.05201 6.75294L4.54909 8.25002L4.57427 7.63721C4.54861 6.99128 4.42081 6.34955 4.19088 5.71201C3.96095 5.07447 3.64077 4.55049 3.23036 4.14008C2.46549 3.37521 1.57832 2.89122 0.568849 2.68809L0.57232 0.963674Z"
                                                fill="#5C6373"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div className={classNames(classes.exchangeLogo, classes.exchangeLogoTo)}>
                                    {getHotelProviderLogo(hotel.transferOptions[0]?.toBrand as ProviderBrand)}
                                </div>
                            </div>
                        ) : (
                            <div className={classes.logoContainer}>
                                {getHotelProviderLogo(hotel.providercode, classes.logo)}
                            </div>
                        )}

                        <div>
                            <span className={classes.amountPoints}>{hotel.pointsPerNightFormatted}</span>
                            <span className={classes.amountPointsDescription}>
                                {Translator.trans(/** @Desc("Points / per night") */ 'points-per-night')}
                            </span>
                        </div>
                    </div>

                    <PrimaryButton
                        text={
                            hotel.transferOptions && hotel.transferOptions.length > 0
                                ? `${Translator.trans(
                                      'itineraries.trip.transfer.phones.title',
                                      {},
                                      'trips',
                                  )} & ${Translator.trans(/** @Desc("Book") */ 'book')}`
                                : Translator.trans('book')
                        }
                        onClick={onHandleClick}
                    />
                    {hotel.transferOptions && hotel.transferOptions.length > 0 && (
                        <div className={classes.transferPointsDescription}>
                            {Translator.trans(
                                /** @Desc("You will transfer %amount% %brand% points") */ 'hotels-reward-transfer-description',
                                {
                                    amount: hotel.transferOptions[0]?.pointsExchange ?? '',
                                    brand: hotel.transferOptions[0]?.fromBrand ?? '',
                                },
                            )}
                        </div>
                    )}
                </div>
                {hotel.transferOptions && hotel.transferOptions.length > 0 && (
                    <TextButton
                        text={`${hotel.transferOptions.length - 1} ${Translator.transChoice(
                            /** @Desc("{0}Other choices|{1}Other choice|[2,Inf]Other choices") */ 'other-choice',
                            hotel.transferOptions.length - 1,
                        )}`}
                        className={{ button: classes.otherChoiceButton }}
                        onClick={onHandleClick}
                    />
                )}
            </div>
            {hotel.transferOptions && hotel.transferOptions.length > 0 && (
                <Modal open={isModalOpen} onClose={onClose}>
                    <ModalContent hotel={hotel} />
                </Modal>
            )}
        </li>
    );
});

HotelCard.displayName = 'HotelCard';

function Separator() {
    return <div className={classes.separator}></div>;
}
