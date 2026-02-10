import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { ErrorPage } from './Components/ErrorPage';
import { FamilyMember } from './Components/FamilyMember';
import { FamilyMemberProvider, useFamilyMember } from './Context/FamilyMemberContext';
import { FiltersMetaProvider, useFiltersMeta } from './Context/FiltersMetaContext';
import { LinkIdToChoosingFamilyMember, LinkIdToFiles, StepIdWithFiles } from './Constant';
import { SkeletonPage } from './Components/SkeletonPage';
import { Step } from './Components/Step';
import { Translator } from '@Services/Translator';
import { getFilesNames, handleDownloadClick } from './Utilities';
import { getInitialData } from './GetInitialData';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import React, { useCallback, useEffect, useRef } from 'react';
import classes from './GmailForwardingPage.module.scss';

export default function GmailForwardingPage() {
    useEffect(() => {
        hideGlobalLoader();
    }, []);
    return (
        <AppSettingsProvider>
            <FiltersMetaProvider>
                <FamilyMemberProvider>
                    <PageWrapper />
                </FamilyMemberProvider>
            </FiltersMetaProvider>
        </AppSettingsProvider>
    );
}

export function PageWrapper() {
    const { filtersMeta, loading: isRequiredPageDataLoading, error: requiredPageDataError } = useFiltersMeta();
    const { alternativeAddress, selectedFamilyMember } = useFamilyMember();

    const filesString = getFilesNames(filtersMeta?.listCount);

    const choosingFamilyMemberRef = useRef<HTMLParagraphElement>(null);

    const stepsData = getInitialData(filtersMeta?.listCount, selectedFamilyMember, alternativeAddress);

    const onLinkToStepWithFilesClick = useCallback((event: MouseEvent) => {
        event.preventDefault();

        const stepWithFiles = document.getElementById(StepIdWithFiles);

        stepWithFiles?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }, []);

    const onLinkToChoosingFamilyMemberClick = useCallback((event: MouseEvent) => {
        event.preventDefault();

        choosingFamilyMemberRef.current?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }, []);

    useEffect(() => {
        if (stepsData.length === 0) return;

        const linkToStepWithFiles = document.getElementById(LinkIdToFiles);

        if (linkToStepWithFiles) {
            linkToStepWithFiles.onclick = onLinkToStepWithFilesClick;
        }

        const linkToChoosingFamilyMember = document.getElementById(LinkIdToChoosingFamilyMember);

        if (linkToChoosingFamilyMember) {
            linkToChoosingFamilyMember.onclick = onLinkToChoosingFamilyMemberClick;
        }

        const downloadFileLinks = document.querySelectorAll<HTMLAnchorElement>('[download]');

        downloadFileLinks.forEach((link) => {
            link.onclick = handleDownloadClick;
        });
    }, [stepsData]);

    if (isRequiredPageDataLoading) {
        return <SkeletonPage />;
    }
    if (requiredPageDataError) {
        return <ErrorPage />;
    }

    return (
        <div className={classes.gmailForwardingPage}>
            <h2 className={classes.gmailForwardingPageTitle}>
                {Translator.trans(
                    /** @Desc("Gmail Travel Confirmation Email Forwarding Instructions.") */ 'gmail.filter.title',
                )}
            </h2>
            <p
                className={classes.gmailForwardingPageDescription}
                dangerouslySetInnerHTML={{
                    __html: `${Translator.trans(
                        /** @Desc("The easiest way to import all your trips into AwardWallet is to %link_on%link your mailbox%link_off% to your account. We
                     are officially approved by Google, Microsoft, Yahoo, and AOL to do this. We are secure. However, if you
                     are not willing to do that and are a Gmail user, please follow these easy steps to set up automatic
                     forwarding of your travel confirmation emails into AwardWallet; you will need to use a desktop computer
                     for this") */ 'gmail.filter.description',
                        {
                            link_on: '<a href="/mailboxes/" target="_blank">',
                            link_off: '</a>',
                        },
                    )}${filtersMeta?.familyMembers && filtersMeta.familyMembers.length > 1 ? '.' : ':'}`,
                }}
            ></p>

            <FamilyMember ref={choosingFamilyMemberRef} />

            <div className={classes.gmailForwardingPageGrid}>
                {stepsData.map((step, index) => (
                    <Step
                        id={step.id}
                        key={index}
                        number={index + 1}
                        description={step.description}
                        imgUrl={step.imgUrl}
                        imgRetina={step.imgForRetina}
                    />
                ))}
            </div>
            <div className={classes.gmailForwardingPageNoticeWrapper}>
                <p
                    className={classes.gmailForwardingPageNotice}
                    dangerouslySetInnerHTML={{
                        __html: Translator.trans(
                            /** @Desc("Please note that the %files% files that we provided in %link_on%step 8%link_off% get updated as we develop more parsers for new travel providers; we recommend that you update your filters from time to time by deleting the existing filter and repeating steps 8 - 13.") */ 'gmail.filter.note',
                            {
                                link_on: `<a id='${LinkIdToFiles}' href='#${StepIdWithFiles}'>`,
                                link_off: '</a>',
                                files: filesString,
                            },
                        ),
                    }}
                ></p>
            </div>
        </div>
    );
}
