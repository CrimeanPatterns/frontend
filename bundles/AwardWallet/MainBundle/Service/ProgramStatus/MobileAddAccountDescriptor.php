<?php

namespace AwardWallet\MainBundle\Service\ProgramStatus;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;

class MobileAddAccountDescriptor extends DesktopContactUsDescriptor
{
    protected function getAAInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('aa-note.mobile', [
                '%row_start%' => '<div class="text">',
                '%row_end%' => '</div>',
                '%bold_on%' => '<span class="greenBold">',
                '%bold_off%' => '</span>',
                '%petition_link_on%' => '<div class="linkWrap"><a class="row blue" href="https://www.change.org/AA-AwardWallet" rel="noopener noreferrer">',
                '%petition_link_off%' => '</a></div>',
                '%petition_link%' => 'https://www.change.org/AA-AwardWallet',
                '%email_link_on%' => '<div class="linkWrap"><a class="row blue" href="mailto:AmericanAirlinesCustomerRelations@aa.com">',
                '%email_link_off%' => '</a></div>',
                '%email%' => 'AmericanAirlines<wbr>CustomerRelations<wbr>@aa<wbr>.com',
                '%row_margin_start%' => '<div class="text marginBottom">',
                '%row_margin_end%' => '</div>',
                '%form%' => '<custom-account></custom-account>',
            ], 'contactus'),
        ];
    }

    protected function getDeltaInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('delta-note.mobile', [
                '%row_start%' => '<div class="row">',
                '%row_end%' => '</div>',
                '%bold_on%' => '<span class="greenBold">',
                '%bold_off%' => '</span>',
                '%edit_link_on%' => sprintf('<a href="%s">', $this->getProgramLink(7, $user)),
                '%edit_link_off%' => '</a>',
                '%petition_link_on%' => '<div class="linkWrap"><a href="http://www.change.org/petitions/delta-airlines-reverse-your-recent-lockout-of-awardwallet-service#" rel="noopener noreferrer">',
                '%petition_link_off%' => '</a></div>',
                '%petition_link%' => 'http://www.change.org/petitions/delta-airlines-reverse-your-recent-lockout-of-awardwallet-service#',
                '%twitter_link_on%' => '<div class="linkWrap"><a href="https://twitter.com/Delta" rel="noopener noreferrer">',
                '%twitter_link_off%' => '</a></div>',
                '%twitter_link%' => 'https://twitter.com/Delta',
            ], 'contactus'),
        ];
    }

    protected function getMileageplusInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('mileageplus-and-rapidrewards-note.mobile', [
                '%row_start%' => '<div class="row">',
                '%row_end%' => '</div>',
                '%bold_on%' => '<span class="greenBold">',
                '%bold_off%' => '</span>',
                '%providerName%' => 'United Airlines',
                '%providerShortName%' => 'United',
                '%edit_link_on%' => sprintf('<a href="%s">', $this->getProgramLink(26, $user)),
                '%edit_link_off%' => '</a>',
                '%twitter_link_on%' => '<div class="linkWrap"><a href="https://twitter.com/united" rel="noopener noreferrer">',
                '%twitter_link_off%' => '</a></div>',
                '%twitter_link%' => 'https://twitter.com/united',
            ], 'contactus'),
        ];
    }

    protected function getRapidrewardsInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('mileageplus-and-rapidrewards-note.mobile', [
                '%row_start%' => '<div class="row">',
                '%row_end%' => '</div>',
                '%bold_on%' => '<span class="greenBold">',
                '%bold_off%' => '</span>',
                '%providerName%' => 'Southwest Airlines',
                '%providerShortName%' => 'Southwest',
                '%edit_link_on%' => sprintf('<a href="%s">', $this->getProgramLink(16, $user)),
                '%edit_link_off%' => '</a>',
                '%twitter_link_on%' => '<div class="linkWrap"><a href="https://twitter.com/SouthwestAir" rel="noopener noreferrer">',
                '%twitter_link_off%' => '</a></div>',
                '%twitter_link%' => 'https://twitter.com/SouthwestAir',
            ], 'contactus'),
        ];
    }

    protected function getCollectingAccountsInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'action' => [
                'id' => $providerFields['ProviderID'],
                'name' => $providerFields['DisplayName'],
                'kind' => Provider::getKinds()[$providerFields['Kind']],
                'votes' => $providerFields['Votes'],
                'voted' => (bool) $providerFields['Voted'],
            ],
            'msg' => $this->translator->trans('program-already-considering-to-adding.mobile', [
                '%row_start%' => '<div class="text marginBottom">',
                '%row_end%' => '</div>',
                '%bold_on%' => '<b>',
                '%bold_off%' => '</b>',
                '%bold2_on%' => '<span class="greenBold">',
                '%bold2_off%' => '</span>',
                '%program%' => $providerFields['DisplayName'],
            ], 'contactus'),
            'note' => $providerFields['Note'] ?? null,
        ];
    }

    protected function getDisabledInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('program-adding-not-feasible.mobile', [
                '%row_start%' => '<div class="text">',
                '%row_end%' => '</div>',
                '%bold_on%' => '<span class="greenBold">',
                '%bold_off%' => '</span>',
                '%program%' => $providerFields['DisplayName'],
                '%bold2_on%' => '<b>',
                '%bold2_off%' => '</b>',
            ], 'contactus'),
            'note' => $providerFields['Note'] ?? null,
        ];
    }

    protected function getMarkBrokenInfo(array $providerFields, ?Usr $user, ?float $successRate): array
    {
        $texts = [];

        if (!is_null($successRate) && $successRate < 75) {
            $texts[] = sprintf('<div class="text">%s</div>', $this->translator->trans('program-broken-suspicion.mobile', [
                '%bold_on%' => '<span class="greenBold">',
                '%bold_off%' => '</span>',
                '%bold2_on%' => '<b>',
                '%bold2_off%' => '</b>',
                '%program%' => $providerFields['DisplayName'],
                '%successRate%' => $this->localizeService->formatNumber($successRate) . '%',
            ], 'contactus'));
        } else {
            $texts[] = sprintf('<div class="text">%s</div>', $this->translator->trans('program-not-broken', [
                '%bold_on%' => '<span class="greenBold">',
                '%bold_off%' => '</span>',
                '%bold2_on%' => '<b>',
                '%bold2_off%' => '</b>',
                '%program%' => $providerFields['DisplayName'],
            ], 'contactus'));

            if (!is_null($successRate)) {
                $texts[] = sprintf('<div class="text">%s</div>', $this->translator->trans('program-success-rate', [
                    '%bold_on%' => '<b>',
                    '%bold_off%' => '</b>',
                    '%successRate%' => $this->localizeService->formatNumber($successRate) . '%',
                ], 'contactus'));
            }
        }

        $texts[] = sprintf('<div class="text marginBottom">%s</div>', $this->translator->trans('mark-program-broken.mobile', [
            '%bold_on%' => '<span class="greenBold">',
            '%bold_off%' => '</span>',
        ], 'contactus'));

        return [
            'action' => [
                'id' => $providerFields['ProviderID'],
                'name' => $providerFields['DisplayName'],
                'kind' => Provider::getKinds()[$providerFields['Kind']],
                'votes' => $providerFields['Votes'],
                'voted' => (bool) $providerFields['Voted'],
            ],
            'msg' => implode('', $texts),
            'note' => $providerFields['Note'] ?? null,
        ];
    }

    protected function getFixingInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'action' => [
                'id' => $providerFields['ProviderID'],
                'name' => $providerFields['DisplayName'],
                'kind' => Provider::getKinds()[$providerFields['Kind']],
                'votes' => $providerFields['Votes'],
                'voted' => (bool) $providerFields['Voted'],
            ],
            'msg' => $this->translator->trans('program-already-marked-broken.mobile', [
                '%row_start%' => '<div class="text">',
                '%row_end%' => '</div>',
                '%bold_on%' => '<span class="greenBold">',
                '%bold_off%' => '</span>',
                '%program%' => $providerFields['DisplayName'],
                '%bold2_on%' => '<b>',
                '%bold2_off%' => '</b>',
            ], 'contactus'),
            'note' => $providerFields['Note'] ?? null,
        ];
    }
}
