import { Image } from '@UI/Layout';
import { ManualStep } from '../ManualStep/ManualStep';
import { PrimaryButton } from '@UI/Buttons';
import { Translator } from '@Services/Translator';
import React from 'react';
import SafariInstallationInstructionStep2a from '../../Assets/safari-installation-instruction-step2a.png';
import SafariInstallationInstructionStep2aRetina from '../../Assets/safari-installation-instruction-step2a@2x.png';
import SafariInstallationInstructionStep2b from '../../Assets/safari-installation-instruction-step2b.png';
import SafariInstallationInstructionStep2bRetina from '../../Assets/safari-installation-instruction-step2b@2x.png';
import SafariInstallationInstructionStep3 from '../../Assets/safari-installation-instruction-step3.png';
import SafariInstallationInstructionStep3Retina from '../../Assets/safari-installation-instruction-step3@2x.png';
import SafariInstallationInstructionStep4 from '../../Assets/safari-installation-instruction-step4.png';
import SafariInstallationInstructionStep4Retina from '../../Assets/safari-installation-instruction-step4@2x.png';
import SafariInstallationInstructionStep5 from '../../Assets/safari-installation-instruction-step5.png';
import SafariInstallationInstructionStep5Retina from '../../Assets/safari-installation-instruction-step5@2x.png';
import SafariInstallationInstructionStep6 from '../../Assets/safari-installation-instruction-step6.png';
import SafariInstallationInstructionStep6Retina from '../../Assets/safari-installation-instruction-step6@2x.png';
import SafariInstallationInstructionStep7 from '../../Assets/safari-installation-instruction-step7.png';
import SafariInstallationInstructionStep7Retina from '../../Assets/safari-installation-instruction-step7@2x.png';
import SafariInstallationInstructionStep8 from '../../Assets/safari-installation-instruction-step8.png';
import SafariInstallationInstructionStep8Retina from '../../Assets/safari-installation-instruction-step8@2x.png';
import classes from './SafariManual.module.scss';

export function SafariManual() {
    const onReloadClick = () => {
        window.location.reload();
    };
    return (
        <ul>
            <ManualStep
                stepNumber={1}
                text={Translator.trans(
                    /** @Desc('On a Mac, the AwardWallet Browser Helper for Safari is installed via App Store. Please %highlight_on%click the button below%highlight_off% to launch the Apple App Store:') */ 'extension.install.safari.step1',
                    {
                        highlight_on: '<strong>',
                        highlight_off: '</strong>',
                    },
                )}
                extraContent={
                    <a
                        href="https://apps.apple.com/us/app/awardwallet/id1473828829"
                        target="_blank"
                        className={classes.safariInstructionInstallationLink}
                        rel="noopener noreferrer"
                    >
                        {Translator.trans(/** @Desc('Open %place_holder%') */ 'extension.install.button.open', {
                            place_holder: 'App Store',
                        })}
                    </a>
                }
            />
            <ManualStep
                stepNumber={2}
                text={Translator.trans(
                    /** @Desc('%highlight_on%Download%highlight_off% and %highlight_on%Open%highlight_off% the AwardWallet App, after %highlight_on%follow the steps%highlight_off% on the screen to enable the Safari browser extension:') */ 'extension.install.safari.step2',
                    {
                        highlight_on: '<strong>',
                        highlight_off: '</strong>',
                    },
                )}
                extraContent={
                    <div className={classes.safariInstructionImagesContainer}>
                        <Image
                            src={SafariInstallationInstructionStep2a}
                            srcSet={`${SafariInstallationInstructionStep2a} 1x, ${SafariInstallationInstructionStep2aRetina} 2x`}
                            classes={{ imgWrapper: classes.safariInstructionImageWrapper }}
                        />
                        <Image
                            src={SafariInstallationInstructionStep2b}
                            srcSet={`${SafariInstallationInstructionStep2b} 1x, ${SafariInstallationInstructionStep2bRetina} 2x`}
                            classes={{ imgWrapper: classes.safariInstructionImageWrapper }}
                        />
                    </div>
                }
            />
            <ManualStep
                stepNumber={3}
                text={Translator.trans(
                    /** @Desc('%highlight_on%Press the "Quit and Open Safari Settings..."%highlight_off% button to open Safari Extensions.') */ 'extension.install.safari.step3',
                    {
                        highlight_on: '<strong>',
                        highlight_off: '</strong>',
                    },
                )}
                extraContent={
                    <Image
                        src={SafariInstallationInstructionStep3}
                        srcSet={`${SafariInstallationInstructionStep3} 1x, ${SafariInstallationInstructionStep3Retina} 2x`}
                    />
                }
            />
            <ManualStep
                stepNumber={4}
                text={Translator.trans(
                    /** @Desc('Check the checkbox by the AwardWallet browser extension to enable it.') */ 'extension.install.safari.step4',
                )}
                extraContent={
                    <Image
                        src={SafariInstallationInstructionStep4}
                        srcSet={`${SafariInstallationInstructionStep4} 1x, ${SafariInstallationInstructionStep4Retina} 2x`}
                    />
                }
            />
            <ManualStep
                stepNumber={5}
                text={Translator.trans(
                    /** @Desc('You may need to %highlight_on%authenticate yourself%highlight_off% to do this.') */ 'extension.install.safari.step5',
                    {
                        highlight_on: '<strong>',
                        highlight_off: '</strong>',
                    },
                )}
                extraContent={
                    <Image
                        src={SafariInstallationInstructionStep5}
                        srcSet={`${SafariInstallationInstructionStep5} 1x, ${SafariInstallationInstructionStep5Retina} 2x`}
                    />
                }
            />

            <ManualStep
                stepNumber={6}
                text={Translator.trans('extension.install.safari.step6')}
                extraContent={
                    <Image
                        src={SafariInstallationInstructionStep6}
                        srcSet={`${SafariInstallationInstructionStep6} 1x, ${SafariInstallationInstructionStep6Retina} 2x`}
                    />
                }
            />
            <ManualStep
                stepNumber={7}
                text={Translator.trans('extension.install.safari.step7', {
                    highlight_on: '<strong>',
                    highlight_off: '</strong>',
                })}
                extraContent={
                    <Image
                        src={SafariInstallationInstructionStep7}
                        srcSet={`${SafariInstallationInstructionStep7} 1x, ${SafariInstallationInstructionStep7Retina} 2x`}
                    />
                }
            />
            <ManualStep
                stepNumber={8}
                text={Translator.trans('extension.install.safari.step8', {
                    highlight_on: '<strong>',
                    highlight_off: '</strong>',
                })}
                extraContent={
                    <Image
                        src={SafariInstallationInstructionStep8}
                        srcSet={`${SafariInstallationInstructionStep8} 1x, ${SafariInstallationInstructionStep8Retina} 2x`}
                    />
                }
            />
            <ManualStep
                stepNumber={9}
                text={Translator.trans('reload-page', {
                    'strong-on': '<strong>',
                    'strong-off': '</strong>',
                }).replace(':', '')}
                extraContent={
                    <PrimaryButton
                        text="Reload"
                        onClick={onReloadClick}
                        className={{ button: classes.safariInstructionReloadButton }}
                    />
                }
            />
        </ul>
    );
}
