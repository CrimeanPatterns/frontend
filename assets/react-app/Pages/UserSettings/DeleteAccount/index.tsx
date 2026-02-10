import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { DeletionForm } from './Components/DeletionForm';
import { Icon } from '@UI/Icon';
import { Modal } from '@UI/Popovers/Modal';
import { PrimaryButton } from '@UI/Buttons/PrimaryButton';
import { ReauthService } from '@Services/Axios/Reauth/ReauthService';
import { Router } from '@Services/Router';
import { Term } from '@UI/Layout/Term/Term';
import { Translator } from '@Services/Translator';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import { useInitialData } from './Hook/UseGetInitialData';
import React, { useEffect } from 'react';
import classes from './DeleteAccount.module.scss';

export default function DeleteUserAccountPage() {
    const { showWarningPopup, businessText1, businessText3, isBusinessArea } = useInitialData();

    const onCancelClick = () => {
        location.href = '/';
    };
    useEffect(() => {
        hideGlobalLoader();
    }, []);
    return (
        <AppSettingsProvider>
            <div className={classes.deletePage}>
                <h1 className={classes.title}>
                    {Translator.trans(/**@Desc("Saying Farewell? Let's Do It Right.")*/ 'user.delete.title')}
                </h1>
                <h2 className={classes.subtitle}>{Translator.trans('user.delete.info1')}</h2>
                <p
                    className={classes.description}
                    dangerouslySetInnerHTML={{
                        __html: Translator.trans(
                            /**@Desc("At AwardWallet, we value your experience and are always here to assist you. If you encountered any
        issues or have feedback, please don't hesitate to %link_on%send us a note%link_off%. We would appreciate it if you could let us know why you decided to leave AwardWallet.")*/ 'user.delete.info3',
                            {
                                link_on: `<a href="${Router.generate('aw_contactus_index')}" target="_blank">`,
                                link_off: '</a>',
                            },
                        ),
                    }}
                ></p>
                <ul className={classes.terms}>
                    <Term
                        iconType="Trash"
                        title={Translator.trans(/**@Desc("Data Deleted:")*/ 'user.delete.term.title1')}
                        description={Translator.trans(
                            /**@Desc("Upon account deletion, we will remove your personal information, loyalty accounts, travel plans, transaction history, and any other data associated with your account.")*/ 'user.delete.term1',
                        )}
                    />
                    <Term
                        iconType="Copy"
                        title={Translator.trans(/**@Desc("Data Retained:")*/ 'user.delete.term.title2')}
                        description={Translator.trans(
                            /**@Desc("For legal and audit purposes, we may retain anonymized meta data records without any personal identifiers.")*/ 'user.delete.term2',
                        )}
                    />
                    <Term
                        iconType="Clock"
                        title={Translator.trans(/**@Desc("Retention Period:")*/ 'user.delete.term.title3')}
                        description={Translator.trans(
                            /**@Desc("Any retained data will be kept for a period of at least 1 year following account deletion.")*/ 'user.delete.term3',
                        )}
                    />
                </ul>
                <DeletionForm isBusinessArea={isBusinessArea} />
            </div>
            <Modal
                open={showWarningPopup}
                hideCross
                className={{ container: classes.warningPopoverModalContainer }}
                blockInteraction
            >
                <div className={classes.warningPopoverContainer}>
                    <h3 className={classes.warningPopoverTitle}>
                        <Icon type="Warning" size="big" color="warning" />
                        {`${Translator.trans('alerts.warning')}!`}
                    </h3>
                    <div className={classes.warningPopoverContent}>
                        {businessText1 && (
                            <p
                                className={classes.warningPopoverCompanyInfo}
                                dangerouslySetInnerHTML={{ __html: businessText1 }}
                            />
                        )}

                        <p className={classes.warningPopoverDescription}>
                            {Translator.trans('user.delete.popup-text2')}
                        </p>

                        {businessText3 && (
                            <p
                                className={classes.warningPopoverDescription}
                                dangerouslySetInnerHTML={{
                                    __html: businessText3,
                                }}
                            />
                        )}
                    </div>
                    <PrimaryButton
                        className={{ button: classes.warningPopoverButton }}
                        text={Translator.trans('button.cancel')}
                        onClick={onCancelClick}
                    />
                </div>
            </Modal>

            <ReauthService />
        </AppSettingsProvider>
    );
}
