import { Checkbox } from '@UI/Inputs/Checkbox';
import { Controller, useForm } from 'react-hook-form';
import { Dropdown } from '@UI/Popovers/Dropdown/Dropdown';
import { MenuItem } from '@UI/Layout/Menu/Menu';
import { PrimaryButton } from '@UI/Buttons';
import { PurchaseType } from '../../UseInitialData';
import { Translator } from '@Services/Translator';
import { useAppSettingsContext } from '@Root/Contexts/AppSettingsContext';
import { usePrePaymentPay } from '../../UsePrePaymentPay';
import AWLogo from '../../Assets/aw-logo.svg';
import React, { useEffect, useRef, useState } from 'react';
import classes from './Form.module.scss';

const Purchase_Type_Field_Name = 'purchaseType';
const Buy_New_Subscription_Field_Name = 'buyingNewSubscription';

export interface FormFields {
    [Purchase_Type_Field_Name]: number;
    [Buy_New_Subscription_Field_Name]: boolean;
}

type FormProps = {
    price: string;
    purchaseTypes: PurchaseType[];
    hash: string;
    refCode: string;
    canBuyNewSubscription: boolean;
    appleSubscription: string | undefined;
    membershipExpiration: string | undefined;
};

export function Form({
    price,
    purchaseTypes,
    hash,
    refCode,
    canBuyNewSubscription,
    appleSubscription,
    membershipExpiration,
}: FormProps) {
    const [menuItems, setMenuItems] = useState<MenuItem<number, PurchaseType>[]>([]);
    const [selectedItem, setSelectedItem] = useState<MenuItem<number, PurchaseType> | null>(null);
    const { control, handleSubmit, setValue } = useForm<FormFields>({
        defaultValues: {
            [Purchase_Type_Field_Name]: 1,
            [Buy_New_Subscription_Field_Name]: canBuyNewSubscription,
        },
    });
    const { localeForIntl } = useAppSettingsContext();
    const [newMembershipExpiration, setNewMembershipExpiration] = useState('');

    const { prePaymentPay, isPending } = usePrePaymentPay();

    const onSelectItem = (item: MenuItem<number, PurchaseType> | null) => {
        setSelectedItem(item);
        setValue(Purchase_Type_Field_Name, item?.value.value as number);

        if (item) {
            const membershipExpirationDate = calculateSubscriptionExpiration(item.value.value);
            setNewMembershipExpiration(formatter.format(membershipExpirationDate));
        }
    };

    const onSubmitForm = (data: FormFields) => {
        prePaymentPay({
            purchaseType: data[Purchase_Type_Field_Name],
            addSubscription: data[Buy_New_Subscription_Field_Name],
            hash,
            refCode,
        });
    };

    const formatter = useRef(
        new Intl.DateTimeFormat(localeForIntl, { month: 'numeric', day: 'numeric', year: 'numeric' }),
    ).current;

    const calculateSubscriptionExpiration = (addedYears: number) => {
        let currentExpirationDate = new Date();
        if (membershipExpiration) {
            currentExpirationDate = new Date(membershipExpiration);
        }
        currentExpirationDate.setFullYear(currentExpirationDate.getFullYear() + addedYears);

        return currentExpirationDate;
    };

    useEffect(() => {
        const menuItems: MenuItem<number, PurchaseType>[] = purchaseTypes.map((type) => {
            return {
                key: type.value,
                label: type.label,
                classes: { container: classes.formDropdownItem },
                hideDescriptionInAnchor: true,
                description: `(${type.priceFormatted})`,
                value: type,
            };
        });

        setMenuItems(menuItems);

        if (menuItems[0]) {
            const maxValueItem = menuItems.reduce((maxItem, currentItem) => {
                return currentItem.value.value > maxItem.value.value ? currentItem : maxItem;
            }, menuItems[0]);

            setSelectedItem(maxValueItem);

            const membershipExpirationDate = calculateSubscriptionExpiration(Number(maxValueItem.value.value));
            setNewMembershipExpiration(formatter.format(membershipExpirationDate));

            setValue(Purchase_Type_Field_Name, maxValueItem.value.value);
        }
    }, []);
    return (
        <form className={classes.form} onSubmit={handleSubmit(onSubmitForm)}>
            <div className={classes.formDropdownBlock}>
                <Controller
                    name={Purchase_Type_Field_Name}
                    control={control}
                    rules={{ required: true }}
                    render={() => (
                        <Dropdown
                            items={menuItems}
                            selectedItem={selectedItem}
                            onSelect={onSelectItem}
                            classes={{
                                anchor: classes.formDropdown,
                                anchorText: classes.formDropdownAnchorText,
                                anchorActive: classes.formDropdownActive,
                                anchorLabel: classes.formDropdownAnchorLabel,
                                anchorStaticInfo: classes.formDropdownAnchorStaticInfo,
                            }}
                            label={Translator.trans(/** @Desc("Period") */ 'pre-payment.dropdown.label')}
                            staticInfo={price}
                        />
                    )}
                />

                <div className={classes.formDescription}>
                    <div className={classes.formLabel}>
                        <div className={classes.formLabelTextBlock}>
                            <p>
                                {Translator.trans(/** @Desc("Your Price for AwardWallet") */ 'pre-payment.your.price')}
                            </p>
                            <AWLogo className={classes.formLabelTextBlockLogo} />
                        </div>
                        <p className={classes.formLabelActualPrice}>{selectedItem?.value.priceFormatted}</p>
                        {selectedItem?.value.newPriceFormatted && (
                            <p className={classes.formLabelOldPrice}>{selectedItem.value.newPriceFormatted}</p>
                        )}
                    </div>
                    <PrimaryButton
                        type="submit"
                        text={Translator.trans(/** @Desc("Proceed to Checkout") */ 'pre-payment.proceed.checkout')}
                        className={{ button: classes.formButton }}
                        loading={isPending}
                        disabled={isPending}
                    />
                </div>
                {canBuyNewSubscription && (
                    <div className={classes.formCheckbox}>
                        <Controller
                            name={Buy_New_Subscription_Field_Name}
                            control={control}
                            render={({ field }) => (
                                <Checkbox
                                    checked={field.value || false}
                                    onChange={field.onChange}
                                    label={
                                        <span className={classes.formCheckboxLabelText}>
                                            {Translator.trans(
                                                /**@Desc("Keep me subscribed to AwardWallet Plus at $49.99 per year")*/ 'pre-payment.new.subscription.checkbox.label',
                                            )}
                                        </span>
                                    }
                                />
                            )}
                        />

                        <p className={classes.formCheckboxDescription}>
                            {Translator.trans(
                                /**@Desc("Uncheck this box to make a one-time payment without creating an ongoing subscription. If you choose this option, your account will be automatically downgraded on %date%.")*/ 'pre-payment.new.subscription.checkbox.explanation',
                                {
                                    date: newMembershipExpiration,
                                },
                            )}
                        </p>
                    </div>
                )}
                {appleSubscription === 'true' && (
                    <div className={classes.formWarningContainer}>
                        <p className={classes.formWarning}>
                            {Translator.trans(
                                /**@Desc("You are making a one-time payment today which will extend your AwardWallet Plus membership through %date%.")*/ 'pre-payment.apple.subscriber.description',
                                {
                                    date: newMembershipExpiration,
                                },
                            )}
                        </p>
                        <p
                            className={classes.formWarning}
                            dangerouslySetInnerHTML={{
                                __html: Translator.trans(
                                    /**@Desc("%highlight_on%Important:%highlight_off% AwardWallet is unable to cancel recurring payments set up with Apple. After checkout, you will receive instructions to cancel payments via Apple.")*/ 'pre-payment.apple.subscriber.warning',
                                    {
                                        highlight_on: '<strong>',
                                        highlight_off: '</strong>',
                                    },
                                ),
                            }}
                        ></p>
                    </div>
                )}
            </div>
        </form>
    );
}
