import { Image } from '@UI/Layout';
import { ManualStep } from '../ManualStep/ManualStep';
import React from 'react';

import { Translator } from '@Services/Translator';
import SafariInstallationInstructionStep1 from '../../Assets/safari-installation-instruction-step6.png';
import SafariInstallationInstructionStep1Retina from '../../Assets/safari-installation-instruction-step6@2x.png';
import SafariInstallationInstructionStep2 from '../../Assets/safari-installation-instruction-step7.png';
import SafariInstallationInstructionStep2Retina from '../../Assets/safari-installation-instruction-step7@2x.png';
import SafariInstallationInstructionStep3 from '../../Assets/safari-installation-instruction-step8.png';
import SafariInstallationInstructionStep3Retina from '../../Assets/safari-installation-instruction-step8@2x.png';

export function SafariGrantPermissionManual() {
    return (
        <ul>
            <ManualStep
                stepNumber={1}
                text={Translator.trans(
                    /** @Desc("Click the AwardWallet icon in the toolbar.") */ 'extension.install.safari.step6',
                )}
                extraContent={
                    <Image
                        src={SafariInstallationInstructionStep1}
                        srcSet={`${SafariInstallationInstructionStep1} 1x, ${SafariInstallationInstructionStep1Retina} 2x`}
                    />
                }
            />
            <ManualStep
                stepNumber={2}
                text={Translator.trans(
                    /** @Desc('Select the last option: %highlight_on%"Always Allow on Every Website."%highlight_off%') */ 'extension.install.safari.step7',
                    {
                        highlight_on: '<strong>',
                        highlight_off: '</strong>',
                    },
                )}
                extraContent={
                    <Image
                        src={SafariInstallationInstructionStep2}
                        srcSet={`${SafariInstallationInstructionStep2} 1x, ${SafariInstallationInstructionStep2Retina} 2x`}
                    />
                }
            />
            <ManualStep
                stepNumber={3}
                text={Translator.trans(
                    /** @Desc('Confirm your choice by clicking %highlight_on%"Always Allow on Every Website"%highlight_off% again.') */ 'extension.install.safari.step8',
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
        </ul>
    );
}
