<?php

namespace AwardWallet\MainBundle\Service\ProgramStatus;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Routing\RouterInterface;

class DesktopContactUsDescriptor extends AbstractDescriptor implements TranslationContainerInterface
{
    public static function getTranslationMessages()
    {
        return [
            (new Message('aa-note', 'contactus'))->setDesc('
                Unfortunately, %bold_on%American Airlines%bold_off% won\'t let us automatically track your AAdvantage miles for you (business or personal accounts).
                If you want to voice your opinion to American Airlines, we invite you to fill out this petition: %petition_link_on%%petition_link%%petition_link_off%
                or send an email to %email_link_on%%email%%email_link_off%.
                %br%%br%
                If you wish to track any account on AwardWallet manually, you can do so by adding a custom account via this form:
                %form_link_on%%form_link%%form_link_off%
            '),
            (new Message('aa-note.mobile', 'contactus'))->setDesc('
                %row_start%Unfortunately, %bold_on%American Airlines%bold_off% won\'t let us automatically track your AAdvantage miles for you (business or personal accounts).
                If you want to voice your opinion to American Airlines, we invite you to fill out this petition:%row_end% %petition_link_on%%petition_link%%petition_link_off%
                %row_start%or send an email to%row_end%
                %email_link_on%%email%%email_link_off%
                %row_margin_start%If you wish to track any account on AwardWallet manually, you can do so by adding a custom account via this form:%row_margin_end%
                %form%
            '),
            (new Message('delta-note', 'contactus'))->setDesc('
                Unfortunately, %bold_on%Delta Air Lines%bold_off% forced us to stop supporting their loyalty programs by accessing their
                website; however, - we can update your Delta account from the email statement that they send you.
                %br%
                Please %edit_link_on%click here%edit_link_off% to read the instructions on how to do that.
                %br%
                %br%
                There is a petition going on at change.org:
                %br%
                %br%
                %petition_link_on%%petition_link%%petition_link_off%
                %br%
                %br%
                If you care about this problem, please sign this petition.
                %br%
                %br%
                Finally, please voice your opinion by tweeting it to %twitter_link_on%%twitter_link%%twitter_link_off%
            '),
            (new Message('delta-note.mobile', 'contactus'))->setDesc('
                %row_start%Unfortunately, %bold_on%Delta Air Lines%bold_off% forced us to stop supporting their loyalty programs by accessing their
                website; however, - we can update your Delta account from the email statement that they send you.%row_end%
                %row_start%Please %edit_link_on%click here%edit_link_off% to read the instructions on how to do that.%row_end%
                %row_start%There is a petition going on at change.org:%row_end%
                %petition_link_on%%petition_link%%petition_link_off%
                %row_start%If you care about this problem, please sign this petition.%row_end%
                %row_start%Finally, please voice your opinion by tweeting it to:%row_end%
                %twitter_link_on%%twitter_link%%twitter_link_off%
            '),
            (new Message('mileageplus-and-rapidrewards-note', 'contactus'))->setDesc('
                Unfortunately, %bold_on%%providerName%%bold_off% forced us to stop supporting their loyalty programs by accessing their
                website; however, - we can update your %providerShortName% account from the email statement that they send you.
                %br%
                Please %edit_link_on%click here%edit_link_off% to read the instructions on how to do that.
                %br%
                %br%
                Please voice your opinion by tweeting it to %twitter_link_on%%twitter_link%%twitter_link_off%
            '),
            (new Message('mileageplus-and-rapidrewards-note.mobile', 'contactus'))->setDesc('
                %row_start%Unfortunately, %bold_on%%providerName%%bold_off% forced us to stop supporting their loyalty programs by accessing their
                website; however, - we can update your %providerShortName% account from the email statement that they send you.%row_end%
                %row_start%Please %edit_link_on%click here%edit_link_off% to read the instructions on how to do that.%row_end%
                %row_start%Please voice your opinion by tweeting it to:%row_end%
                %twitter_link_on%%twitter_link%%twitter_link_off%
            '),
            (new Message('program-already-considering-to-adding', 'contactus'))->setDesc('
                We are %bold_on%already considering%bold_off% to add %bold2_on%%program%%bold2_off% program.
                We add support for new programs based on demand, so please click %bold3_on%+1%bold3_off%
                next to it on our %status_link_on%status page%status_link_off% this way you will also get
                an automated email when this program is implemented.
                %bold2_on%%votes%%bold2_off% other AwardWallet users requested this program.
            '),
            (new Message('program-already-considering-to-adding.mobile', 'contactus'))->setDesc('
                %row_start%We are %bold_on%already considering%bold_off% to add %bold2_on%%program%%bold2_off% program.%row_end%
                %row_start%We add support for new programs based on demand, so please click %bold_on%+1
                above%bold_off% this way you will also get
                an automated email when this program is implemented.%row_end%
            '),
            (new Message('program-adding-not-feasible', 'contactus'))->setDesc('
                We have already looked into adding %bold_on%%program%%bold_off% but determined that it is %bold2_on%not feasible%bold2_off%.
                Most common problem for not being able to add support for a program is that it simply doesn\'t show any type of reward balance on their website.
                If you know that this is not the case, i.e. you can both login to the site and actually see your point balance then please let us know.
            '),
            (new Message('program-adding-not-feasible.mobile', 'contactus'))->setDesc('
                %row_start%We have already looked into adding %bold_on%%program%%bold_off% but determined that it is %bold2_on%not feasible%bold2_off%.%row_end%
                %row_start%Most common problem for not being able to add support for a program is that it simply doesn\'t show any type of reward balance on their website.%row_end%
                %row_start%If you know that this is not the case, i.e. you can both login to the site and actually see your point balance then please let us know.%row_end%
            '),
            (new Message('program-broken-suspicion', 'contactus'))->setDesc('
                According to our records the success rate on %bold_on%%program%%bold_off% is %bold_on%%successRate%%bold_off% (in the last 24 hours %successRate% of accounts successfully updated).
            '),
            (new Message('program-broken-suspicion.mobile', 'contactus'))->setDesc('
                According to our records the success rate on %bold_on%%program%%bold_off% is %bold2_on%%successRate%%bold2_off% (in the last 24 hours %successRate% of accounts successfully updated).
            '),
            (new Message('program-not-broken', 'contactus'))->setDesc('
                According to our records %bold_on%%program%%bold_off% is %bold2_on%not broken%bold2_off%.
            '),
            (new Message('program-success-rate', 'contactus'))->setDesc('
                The success rate on this program is %bold_on%%successRate%%bold_off% (in the last 24 hours %successRate% of accounts successfully updated).
            '),
            (new Message('mark-program-broken', 'contactus'))->setDesc('
                You can mark this program as broken on our %status_link_on%status page%status_link_off% by clicking %bold_on%+1%bold_off% next to it. When it is fixed you will get an email.
            '),
            (new Message('mark-program-broken.mobile', 'contactus'))->setDesc('
                You can mark this program as broken by clicking %bold_on%+1 above%bold_off%. When it is fixed you will get an email.
            '),
            (new Message('program-already-marked-broken', 'contactus'))->setDesc('
                %bold_on%%program%%bold_off% is %bold2_on%already marked as broken%bold2_off% in our system and we are looking to get it fixed.
                There is no need to contact us about this program.
                If you want to get notified when it is fixed please click %bold3_on%+1%bold3_off% on our %status_link_on%status page%status_link_off%.
            '),
            (new Message('program-already-marked-broken.mobile', 'contactus'))->setDesc('
                %row_start%%bold_on%%program%%bold_off% is %bold2_on%already marked as broken%bold2_off% in our system and we are looking to get it fixed.%row_end%
                %row_start%There is no need to contact us about this program.%row_end%
                %row_start%If you want to get notified when it is fixed please click %bold2_on%+1 above%bold2_off%.%row_end%
            '),
        ];
    }

    final protected function mapProvider(array $providerFields, ?Usr $user): ?array
    {
        $providerFields['DisplayName'] = htmlspecialchars_decode($providerFields['DisplayName']);

        if (!empty($providerFields['Note'])) {
            $providerFields['Note'] = htmlspecialchars_decode($providerFields['Note']);
        }

        if (in_array($providerFields['Code'], ['aa', 'extraa'])) {
            return $this->getAAInfo($providerFields, $user);
        } elseif ($providerFields['Code'] === 'delta') {
            return $this->getDeltaInfo($providerFields, $user);
        } elseif ($providerFields['Code'] === 'mileageplus') {
            return $this->getMileageplusInfo($providerFields, $user);
        } elseif ($providerFields['Code'] === 'rapidrewards') {
            return $this->getRapidrewardsInfo($providerFields, $user);
        } elseif ($providerFields['State'] == PROVIDER_COLLECTING_ACCOUNTS || $providerFields['CollectingRequests']) {
            return $this->getCollectingAccountsInfo($providerFields, $user);
        } elseif ($providerFields['State'] == PROVIDER_DISABLED) {
            return $this->getDisabledInfo($providerFields, $user);
        } elseif (in_array($providerFields['State'], [PROVIDER_ENABLED, PROVIDER_CHECKING_OFF, PROVIDER_CHECKING_EXTENSION_ONLY, PROVIDER_CHECKING_WITH_MAILBOX])) {
            $successRate = $this->providerRepository->getSuccessRateProvider($providerFields['ProviderID']);

            return $this->getMarkBrokenInfo($providerFields, $user, $successRate);
        } elseif ($providerFields['State'] == PROVIDER_FIXING) {
            return $this->getFixingInfo($providerFields, $user);
        } else {
            return null;
        }
    }

    protected function getAAInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('aa-note', [
                '%bold_on%' => '<span class="blue-bolder">',
                '%bold_off%' => '</span>',
                '%petition_link_on%' => '<a target="_blank" href="https://www.change.org/AA-AwardWallet" rel="noopener noreferrer">',
                '%petition_link_off%' => '</a>',
                '%petition_link%' => 'https://www.change.org/AA-AwardWallet',
                '%email_link_on%' => '<a target="_blank" href="mailto:AmericanAirlinesCustomerRelations@aa.com">',
                '%email_link_off%' => '</a>',
                '%email%' => 'AmericanAirlines<wbr>CustomerRelations<wbr>@aa<wbr>.com',
                '%br%' => '<br>',
                '%form_link_on%' => sprintf('<a target="%s" href="%s#/custom">', $this->getTargetAAFormLink(), $this->router->generate('aw_select_provider', [], RouterInterface::ABSOLUTE_URL)),
                '%form_link%' => sprintf('%s#/custom', $this->router->generate('aw_select_provider', [], RouterInterface::ABSOLUTE_URL)),
                '%form_link_off%' => '</a>',
            ], 'contactus'),
        ];
    }

    protected function getDeltaInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('delta-note', [
                '%bold_on%' => '<span class="blue-bolder">',
                '%bold_off%' => '</span>',
                '%br%' => '<br>',
                '%edit_link_on%' => sprintf('<a target="_blank" href="%s">', $this->getProgramLink(7, $user)),
                '%edit_link_off%' => '</a>',
                '%petition_link_on%' => '<a target="_blank" href="http://www.change.org/petitions/delta-airlines-reverse-your-recent-lockout-of-awardwallet-service#" rel="noopener noreferrer">',
                '%petition_link_off%' => '</a>',
                '%petition_link%' => 'http://www.change.org/petitions/delta-airlines-reverse-your-recent-lockout-of-awardwallet-service#',
                '%twitter_link_on%' => '<a href="https://twitter.com/Delta" target="_blank" rel="noopener noreferrer">',
                '%twitter_link_off%' => '</a>',
                '%twitter_link%' => 'https://twitter.com/Delta',
            ], 'contactus'),
        ];
    }

    protected function getMileageplusInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('mileageplus-and-rapidrewards-note', [
                '%bold_on%' => '<span class="blue-bolder">',
                '%bold_off%' => '</span>',
                '%br%' => '<br>',
                '%providerName%' => 'United Airlines',
                '%providerShortName%' => 'United',
                '%edit_link_on%' => sprintf('<a target="_blank" href="%s">', $this->getProgramLink(26, $user)),
                '%edit_link_off%' => '</a>',
                '%twitter_link_on%' => '<a href="https://twitter.com/united" target="_blank" rel="noopener noreferrer">',
                '%twitter_link_off%' => '</a>',
                '%twitter_link%' => 'https://twitter.com/united',
            ], 'contactus'),
        ];
    }

    protected function getRapidrewardsInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('mileageplus-and-rapidrewards-note', [
                '%bold_on%' => '<span class="blue-bolder">',
                '%bold_off%' => '</span>',
                '%br%' => '<br>',
                '%providerName%' => 'Southwest Airlines',
                '%providerShortName%' => 'Southwest',
                '%edit_link_on%' => sprintf('<a target="_blank" href="%s">', $this->getProgramLink(16, $user)),
                '%edit_link_off%' => '</a>',
                '%twitter_link_on%' => '<a href="https://twitter.com/SouthwestAir" target="_blank" rel="noopener noreferrer">',
                '%twitter_link_off%' => '</a>',
                '%twitter_link%' => 'https://twitter.com/SouthwestAir',
            ], 'contactus'),
        ];
    }

    protected function getCollectingAccountsInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('program-already-considering-to-adding', [
                '%bold_on%' => '<b>',
                '%bold_off%' => '</b>',
                '%bold2_on%' => '<span class="blue-bolder">',
                '%bold2_off%' => '</span>',
                '%bold3_on%' => '<span class="green">',
                '%bold3_off%' => '</span>',
                '%bold4_on%' => '<span class="blue-bolder">',
                '%bold4_off%' => '</span>',
                '%status_link_on%' => sprintf('<a href="%s" target="_blank">', $this->router->generate('aw_status_index', [], RouterInterface::ABSOLUTE_URL)),
                '%status_link_off%' => '</a>',
                '%votes%' => $providerFields['Votes'],
                '%program%' => $providerFields['DisplayName'],
            ], 'contactus'),
            'note' => $providerFields['Note'] ?? null,
        ];
    }

    protected function getDisabledInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('program-adding-not-feasible', [
                '%bold_on%' => '<span class="blue-bolder">',
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
            $texts[] = $this->translator->trans('program-broken-suspicion', [
                '%bold_on%' => '<span class="blue-bolder">',
                '%bold_off%' => '</span>',
                '%program%' => $providerFields['DisplayName'],
                '%successRate%' => $this->localizeService->formatNumber($successRate) . '%',
            ], 'contactus');
        } else {
            $texts[] = $this->translator->trans('program-not-broken', [
                '%bold_on%' => '<span class="blue-bolder">',
                '%bold_off%' => '</span>',
                '%bold2_on%' => '<b>',
                '%bold2_off%' => '</b>',
                '%program%' => $providerFields['DisplayName'],
            ], 'contactus');

            if (!is_null($successRate)) {
                $texts[] = $this->translator->trans('program-success-rate', [
                    '%bold_on%' => '<span class="blue-bolder">',
                    '%bold_off%' => '</span>',
                    '%successRate%' => $this->localizeService->formatNumber($successRate) . '%',
                ], 'contactus');
            }
        }

        $texts[] = $this->translator->trans('mark-program-broken', [
            '%status_link_on%' => sprintf('<a href="%s" target="_blank">', $this->router->generate('aw_status_index', [], RouterInterface::ABSOLUTE_URL)),
            '%status_link_off%' => '</a>',
            '%bold_on%' => '<span class="green">',
            '%bold_off%' => '</span>',
        ], 'contactus');

        return [
            'msg' => implode('', $texts),
            'note' => $providerFields['Note'] ?? null,
        ];
    }

    protected function getFixingInfo(array $providerFields, ?Usr $user): array
    {
        return [
            'msg' => $this->translator->trans('program-already-marked-broken', [
                '%bold_on%' => '<span class="blue-bolder">',
                '%bold_off%' => '</span>',
                '%program%' => $providerFields['DisplayName'],
                '%bold2_on%' => '<b>',
                '%bold2_off%' => '</b>',
                '%bold3_on%' => '<span class="green">',
                '%bold3_off%' => '</span>',
                '%status_link_on%' => sprintf('<a href="%s" target="_blank">', $this->router->generate('aw_status_index', [], RouterInterface::ABSOLUTE_URL)),
                '%status_link_off%' => '</a>',
            ], 'contactus'),
            'note' => $providerFields['Note'] ?? null,
        ];
    }

    protected function getTargetAAFormLink(): string
    {
        return '_blank';
    }

    protected function getProgramLink(int $providerId, ?Usr $user): string
    {
        if (!$user) {
            return $this->router->generate('aw_account_add', ['providerId' => $providerId], RouterInterface::ABSOLUTE_URL);
        }

        $options = $this->optionsFactory
            ->createDefaultOptions()
            ->set(Options::OPTION_USER, $user)
            ->set(Options::OPTION_FILTER, " AND p.ProviderID = $providerId")
            ->set(Options::OPTION_COUPON_FILTER, " AND 0 = 1")
            ->set(Options::OPTION_LOAD_PHONES, false)
            ->set(Options::OPTION_LOAD_SUBACCOUNTS, false)
            ->set(Options::OPTION_LOAD_PROPERTIES, false);

        $list = $this->accountListManager->getAccountList($options);

        if (!count($list)) {
            return $this->router->generate('aw_account_add', ['providerId' => $providerId], RouterInterface::ABSOLUTE_URL);
        }

        $account = $list->current();

        return $this->router->generate('aw_account_edit', ['accountId' => $account['ID']], RouterInterface::ABSOLUTE_URL);
    }
}
