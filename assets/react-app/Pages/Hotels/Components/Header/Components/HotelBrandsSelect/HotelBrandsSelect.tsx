import { Align } from '@UI/Popovers';
import { HotelBrandCard } from './HotelBrandCard';
import { Popover } from '@UI/Popovers/Popover';
import { PrimaryButton } from '@UI/Buttons/PrimaryButton';
import { ProviderBrand } from '@Root/Pages/Hotels/Entities';
import { SecondaryButton } from '@UI/Buttons/SecondaryButton';
import { TextButton } from '@UI/Buttons/TextButton';
import { Translator } from '@Services/Translator';
import { useHotelPageInitialData } from '@Root/Pages/Hotels/Contexts/HotelPageInitialDataContext';
import React, { memo, useCallback, useMemo, useRef, useState } from 'react';
import classes from './HotelBrandsSelect.module.scss';

interface HotelBrandsProps {
    activeBrands: ProviderBrand[];
    onActiveBrandsChange: (activeBrands: ProviderBrand[]) => void;
    onSubmit: () => void;
    isSubmitButtonDisabled: boolean;
}

export const HotelBrandsSelect = memo(
    ({ onSubmit, activeBrands, onActiveBrandsChange, isSubmitButtonDisabled }: HotelBrandsProps) => {
        const { providers } = useHotelPageInitialData();

        const [isModalOpen, setIsModalOpen] = useState(false);

        const buttonRef = useRef<HTMLButtonElement>(null);

        const openModal = useCallback(() => {
            setIsModalOpen(true);
        }, []);
        const closeModal = useCallback(() => {
            setIsModalOpen(false);
        }, []);
        const onSubmitHandler = useCallback(() => {
            onSubmit();
            closeModal();
        }, []);

        const anchorButtonClasses = useMemo(
            () => ({ button: classes.anchorButton, text: classes.anchorButtonText }),
            [],
        );
        const actionButtonClasses = useMemo(() => ({ button: classes.button }), []);

        return (
            <>
                <TextButton
                    text={Translator.transChoice(/** @Desc("{0}brands|{1}brand|[2,Inf]brands") */ 'brand', 0)}
                    onClick={openModal}
                    iconType="Brand"
                    ref={buttonRef}
                    type="button"
                    className={anchorButtonClasses}
                />

                <Popover
                    open={isModalOpen}
                    onClose={closeModal}
                    anchor={buttonRef}
                    showShadow
                    align={Align.Left}
                    offsetFromAnchorInPx={20}
                    classNames={{ popoverContainer: classes.hotelBrandsPopover }}
                >
                    <div className={classes.modalContentContainer}>
                        <h3 className={classes.modalTitle}>
                            {Translator.trans(/**@Desc("Hotel Programs") */ 'hotel-programs')}
                        </h3>
                        <div className={classes.hotelBrandsContainer}>
                            {Object.keys(providers).map((provider, index) => {
                                const brand = providers[provider as ProviderBrand];

                                const onClick = (isActive: boolean) => {
                                    if (isActive && !activeBrands.includes(brand.code)) {
                                        onActiveBrandsChange([...activeBrands, brand.code]);
                                        return;
                                    }

                                    onActiveBrandsChange(activeBrands.filter((provider) => provider !== brand.code));
                                };

                                const active = activeBrands.includes(brand.code);

                                return (
                                    <HotelBrandCard
                                        key={index}
                                        brand={brand.shortName}
                                        points={brand.balance}
                                        logo={brand.logo}
                                        active={active}
                                        onClick={onClick}
                                    />
                                );
                            })}
                        </div>
                        <div className={classes.buttonsContainer}>
                            <SecondaryButton
                                text={Translator.trans('alerts.btn.cancel')}
                                className={actionButtonClasses}
                                onClick={closeModal}
                            />
                            <PrimaryButton
                                text={Translator.trans('button.apply', undefined, 'mobile-native')}
                                className={actionButtonClasses}
                                onClick={onSubmitHandler}
                                disabled={isSubmitButtonDisabled}
                            />
                        </div>
                    </div>
                </Popover>
            </>
        );
    },
);

HotelBrandsSelect.displayName = 'HotelBrandsSelect';
