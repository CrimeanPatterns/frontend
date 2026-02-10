import { Dropdown } from '@UI/Popovers/Dropdown/Dropdown';
import { Switcher } from '@UI/Inputs/Switcher';
import { TextInput } from '@UI/Inputs/TextInput';
import { Translator } from '@Services/Translator';
import { useFamilyMember } from '../Context/FamilyMemberContext';
import { useFiltersMeta } from '../Context/FiltersMetaContext';
import React, { ChangeEvent, forwardRef, useCallback, useEffect, useMemo, useState } from 'react';
import classNames from 'classnames';
import classes from './FamilyMember.module.scss';

export const FamilyMember = forwardRef<HTMLParagraphElement>((_, ref) => {
    const [showAlternativeAddress, setShowAlternativeAddress] = useState(false);

    const { filtersMeta } = useFiltersMeta();
    const { selectedFamilyMember, setSelectedFamilyMember, alternativeAddress, setAlternativeAddress } =
        useFamilyMember();

    const customRightLabelComponent = useMemo(
        () => (
            <div className={classes.familyMembersAlternativeLabelBlock}>
                <span className={classes.familyMembersAlternativeLabel}>
                    {Translator.trans(
                        /** @Desc("Do you want to specify an alternate 'To' address?") */ 'gmail.filter.want.specify.alternate.address',
                    )}
                </span>
                <span className={classes.familyMembersAlternativeDescription}>
                    {Translator.trans(
                        /** @Desc("This is uncommon; You can use this if you use aliases or tags such as username+tag@gmail.com") */ 'gmail.filter.specify.address.description',
                    )}
                </span>
            </div>
        ),
        [],
    );

    const onAlternativeAddressChange = useCallback((event: ChangeEvent<HTMLInputElement>) => {
        setAlternativeAddress(event.target.value);
    }, []);

    const onShowAlternativeAddressChange = useCallback((newValue: boolean) => {
        setShowAlternativeAddress(newValue);

        if (!newValue) {
            setAlternativeAddress('');
        }
    }, []);

    useEffect(() => {
        if (!filtersMeta) return;

        if (filtersMeta.familyMembers.length > 1) {
            const accountOwner =
                filtersMeta.familyMembers.find((familyMember) => familyMember.value.length === 0) || null;

            setSelectedFamilyMember(accountOwner);
        }
    }, [filtersMeta]);

    if (!filtersMeta?.familyMembers || filtersMeta.familyMembers.length <= 1) {
        return null;
    }
    return (
        <div className={classes.familyMembersExtraSettingsBlock}>
            <div ref={ref} className={classes.familyMembers}>
                <div className={classes.familyMembersUserSelection}>
                    <div className={classes.familyMembersUserSelectionLabelBlock}>
                        <span className={classes.familyMembersUserSelectionLabel}>
                            {Translator.trans(/** @Desc("AwardWallet User") */ 'gmail.filter.awardwallet.user')}
                        </span>
                        <span className={classes.familyMembersUserSelectionDescription}>
                            {Translator.trans(
                                /** @Desc("Please select the AwardWallet user you want to configure these filters for:") */ 'gmail.filter.select.family',
                            )}
                        </span>
                    </div>

                    <Dropdown
                        items={filtersMeta.familyMembers}
                        selectedItem={selectedFamilyMember}
                        onSelect={setSelectedFamilyMember}
                        classes={{
                            anchor: classes.familyMembersDropdownAnchor,
                        }}
                    />
                </div>
                <div className={classes.familyMembersAlternative}>
                    <Switcher
                        active={showAlternativeAddress}
                        onChange={onShowAlternativeAddressChange}
                        customRightLabelComponent={customRightLabelComponent}
                    />
                </div>
            </div>
            <div
                className={classNames(classes.alternativeAddress, {
                    [classes.alternativeAddressOpen as string]: showAlternativeAddress,
                })}
            >
                <div className={classes.alternativeAddressWrapper}>
                    <div className={classes.alternativeAddressInner}>
                        <label className={classes.alternativeAddressLabel}>
                            {Translator.trans(
                                /** @Desc("Alternate 'To:' address:") */ 'gmail.filter.alternative.address',
                            )}
                        </label>
                        <TextInput
                            value={alternativeAddress}
                            onChange={onAlternativeAddressChange}
                            hint={Translator.trans(
                                /** @Desc("i.e. username+tag@gmail.com or an alias") */ 'gmail.filter.alternative.input.placeholder',
                            )}
                            classes={{
                                containerWithError: classes.alternativeAddressInput,
                            }}
                            hideError
                        />
                    </div>
                </div>
            </div>
        </div>
    );
});

FamilyMember.displayName = 'FamilyMember';
