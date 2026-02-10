import { FamilyMembers } from './Context/FamilyMemberContext';
import { LinkIdToChoosingFamilyMember, StepIdWithFiles } from './Constant';
import { Translator } from '@Services/Translator';
import Step10Img from './Assets/step10.jpg';
import Step10ImgRetina from './Assets/step10@2x.jpg';
import Step11Img from './Assets/step11.jpg';
import Step11ImgRetina from './Assets/step11@2x.jpg';
import Step12Img from './Assets/step12.jpg';
import Step12ImgRetina from './Assets/step12@2x.jpg';
import Step13Img from './Assets/step13.jpg';
import Step13ImgRetina from './Assets/step13@2x.jpg';
import Step1Img from './Assets/step1.jpg';
import Step1ImgRetina from './Assets/step1@2x.jpg';
import Step2Img from './Assets/step2.jpg';
import Step2ImgRetina from './Assets/step2@2x.jpg';
import Step3Img from './Assets/step3.jpg';
import Step3ImgRetina from './Assets/step3@2x.jpg';
import Step4Img from './Assets/step4.jpg';
import Step4ImgRetina from './Assets/step4@2x.jpg';
import Step5Img from './Assets/step5.jpg';
import Step5ImgRetina from './Assets/step5@2x.jpg';
import Step6Img from './Assets/step6.jpg';
import Step6ImgRetina from './Assets/step6@2x.jpg';
import Step7Img from './Assets/step7.jpg';
import Step7ImgRetina from './Assets/step7@2x.jpg';
import Step8Img from './Assets/step8.jpg';
import Step8ImgRetina from './Assets/step8@2x.jpg';
import Step9Img from './Assets/step9.jpg';
import Step9ImgRetina from './Assets/step9@2x.jpg';

type SettingStep = {
    description: string;
    imgUrl: string;
    imgForRetina: string;
    id?: string;
};

export const getInitialData = (
    filesCount: number | undefined,
    familyMember: FamilyMembers | null,
    alternativeAddress: string,
) => {
    const userLogin = document.getElementById('content')?.dataset['userlogin'];

    if (!filesCount || !userLogin) return [];

    const stepsData: SettingStep[] = createStepsData(userLogin, filesCount, alternativeAddress, familyMember?.value);

    return stepsData;
};

const createStepsData = (userLogin: string, filesCount: number, alternativeAddress: string, alias?: string) => {
    const aliasString = alias ? `.${alias}` : '';
    const userEmail = `${userLogin}${aliasString}+f@email.AwardWallet.com`;
    const aliasPart = aliasString.length > 0 ? `/${aliasString.replace('.', '')}` : '';
    const alternativeAddressPart = alternativeAddress.length > 0 ? `?to=${encodeURIComponent(alternativeAddress)}` : '';

    let filesString = '';

    for (let i = 1; i <= filesCount; i++) {
        filesString += `<a href='/user/get-filter/${
            i - 1
        }${aliasPart}${alternativeAddressPart}' download='gmailFilter${i}.xml'>gmailFilter${i}.xml</a>`;

        if (i !== filesCount && i !== filesCount - 1) {
            filesString += ', ';
        }
        if (i === filesCount - 1) {
            filesString += ' and ';
        }
    }

    return [
        {
            description: Translator.trans(
                /** @Desc("In the GMail interface, click the 'Settings' gear -> 'See all settings'.") */ 'gmail.filter.step1',
            ),
            imgUrl: Step1Img,
            imgForRetina: Step1ImgRetina,
        },
        {
            description: Translator.trans(
                /** @Desc("Go to the 'Forwarding and POP/IMAP' tab".) */ 'gmail.filter.step2',
            ),
            imgUrl: Step2Img,
            imgForRetina: Step2ImgRetina,
        },
        {
            description: Translator.trans(
                /** @Desc("Click the 'Forwarding and POP/IMAP' tab, and under the 'Forwarding:' heading, click the 'Add a forwarding address' button.") */ 'gmail.filter.step3',
            ),
            imgUrl: Step3Img,
            imgForRetina: Step3ImgRetina,
        },

        {
            description: `${Translator.trans(
                /** @Desc("In the 'Add a forwarding address' popup box, input %link_on%%email%%link_off% and click 'Next'.") */ 'gmail.filter.step4',
                {
                    email: userEmail,
                    link_on: '<a target="_blank" href="mailto:' + `${userEmail}">`,
                    link_off: '</a>',
                },
            )} ${
                alias !== undefined
                    ? ` ${Translator.trans(
                          /** @Desc("If you wish to set this up for your family members, please (1) select the right person at the <a id='%link_id%' href="#">top of this page</a> and (2) make sure you do this entire setup in their Google Mailbox, not yours.") */ 'gmail.filter.step4.family',
                          {
                              link_id: LinkIdToChoosingFamilyMember,
                          },
                      )}`
                    : ''
            }`,
            imgUrl: Step4Img,
            imgForRetina: Step4ImgRetina,
        },
        {
            description: Translator.trans(
                /** @Desc("Google may want to authenticate you; if so, please follow the prompts.") */ 'gmail.filter.step5',
            ),
            imgUrl: Step5Img,
            imgForRetina: Step5ImgRetina,
        },
        {
            description: Translator.trans(
                /** @Desc("Confirm that you want to set up forwarding by clicking 'Proceed'.") */ 'gmail.filter.step6',
            ),
            imgUrl: Step6Img,
            imgForRetina: Step6ImgRetina,
        },

        {
            description: Translator.trans(
                /** @Desc("Google will tell you that a confirmation link was sent to <a href="mailto:%userEmail%" target="_blank">%userEmail%</a> to verify permission. Please note that AwardWallet will auto-approve such a request; you do not need to contact our support. Instead, give it a few minutes and refresh the page. Typically, this takes 0 - 3 minutes; however, if our servers are busy, it may take a few hours, so you may have to come back to finish this up later.") */ 'gmail.filter.step7',
                {
                    userEmail,
                },
            ),
            imgUrl: Step7Img,
            imgForRetina: Step7ImgRetina,
        },

        {
            description: Translator.trans(
                /** @Desc("Download the %files% files. These files list the addresses from which we can parse travel confirmation emails. Feel free to inspect them in your text editor of choice. You will need to repeat steps 9 - 11 for each file separately.") */ 'gmail.filter.step8',
                {
                    files: filesString,
                },
            ),
            imgUrl: Step8Img,
            imgForRetina: Step8ImgRetina,
            id: StepIdWithFiles,
        },

        {
            description: Translator.trans(
                /** @Desc("Go to the 'Filters and Blocked Addresses' tab, click 'Import filters' -> 'Choose File', and select the just downloaded 'gmailFilter.xml' file from your Downloads folder.") */ 'gmail.filter.step9',
            ),
            imgUrl: Step9Img,
            imgForRetina: Step9ImgRetina,
        },

        {
            description: Translator.trans(/** @Desc("Click 'Open File'.") */ 'gmail.filter.step10'),
            imgUrl: Step10Img,
            imgForRetina: Step10ImgRetina,
        },

        {
            description: Translator.trans(
                /** @Desc("Scroll to the bottom and click 'Create filters'.") */ 'gmail.filter.step11',
            ),
            imgUrl: Step11Img,
            imgForRetina: Step11ImgRetina,
        },

        {
            description: Translator.trans(
                /** @Desc("Google may want to verify your identity again; if so, complete the verification.") */ 'gmail.filter.step12',
            ),
            imgUrl: Step12Img,
            imgForRetina: Step12ImgRetina,
        },
        {
            description: Translator.trans(
                /** @Desc("You are done; your AwardWallet filter has been created, and your travel plans will be built automatically for you going forward.") */ 'gmail.filter.step13',
            ),
            imgUrl: Step13Img,
            imgForRetina: Step13ImgRetina,
        },
    ];
};
