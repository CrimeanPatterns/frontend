<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Resolver;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\DateTimeHandler;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use Clock\ClockInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProgramStatusResolver implements TranslationContainerInterface
{
    use TranslationCacheTrait;

    public const TYPE_COUPON = 1;
    public const TYPE_CUSTOM = 2;
    public const TYPE_UNCHECKED = 3;
    public const TYPE_ONLY_AUTOLOGIN = 4;
    public const TYPE_CHECKED = 5;
    public const TYPE_BIG3_UNCHECKED = 6;

    /** @var DateTimeIntervalFormatter */
    protected $intervalFormatter;

    private ProviderTranslator $providerTranslator;
    private ClockInterface $clock;

    public function __construct(
        TranslatorInterface $translator,
        ProviderTranslator $providerTranslator,
        DateTimeIntervalFormatter $intervalFormatter,
        ClockInterface $clock
    ) {
        $this->translator = $translator;
        $this->providerTranslator = $providerTranslator;
        $this->intervalFormatter = $intervalFormatter;
        $this->clock = $clock;
    }

    /**
     * @param $accountFields
     * array[
     *     string 'Type'
     *     string 'Title' title
     *     string 'BeforeError'
     *     string 'Error'
     *     string 'Description'
     *     string 'DateInfo' information about last (success) update date
     * ]
     */
    public function getStatus(Usr $user, $accountFields, ?string $locale)
    {
        $result = [
            'Type' => null,
            'Title' => null, // title, e.g. "Invalid logon"
            'BeforeError' => null, // e.g. "Virgin America returned the following error"
            'Error' => null, // error message from Account.ErrorMessage
            'Description' => null, // after error message
            'DateInfo' => null, // e.g. last time your rewards info was retrieved from the %displayName% web site on: %updateDate%
        ];

        if ($accountFields["TableName"] == "Coupon") {
            $result['Type'] = self::TYPE_COUPON;
            $result['Title'] = $this->trans('custom-coupon.title', [], 'messages', $locale);
            $result['Description'] = $this->trans('custom-coupon.notice', [], 'messages', $locale);
        } else {
            if (empty($accountFields['ProviderID'])) {
                $result['Type'] = self::TYPE_CUSTOM;
                $result['Title'] = $this->trans('custom-program.title', [], 'messages', $locale);
                $result['Description'] = $this->trans('custom-program.notice', [], 'messages', $locale);
            } elseif ($accountFields["CanCheck"] == 1
                || (isset($accountFields['ForceErrorDisplay']) && $accountFields['ForceErrorDisplay'])
            ) {
                $result['Type'] = self::TYPE_CHECKED;
                $result = $this->getProgramMessage($accountFields, $result, $locale);
            } else {
                if ($accountFields["CanCheckBalance"] == 1) {
                    $result['Type'] = self::TYPE_UNCHECKED;
                    $result['Title'] = $this->trans('unchecked.title', [], 'messages', $locale);
                    $result['Description'] = $this->trans('unchecked.notice', [], 'messages', $locale);
                } elseif (in_array($accountFields["ProviderID"], Provider::BIG3_PROVIDERS)) {
                    $result['Type'] = self::TYPE_BIG3_UNCHECKED;
                } else {
                    $result['Type'] = self::TYPE_ONLY_AUTOLOGIN;
                    $result['Title'] = $this->trans('only-autologin.title', [], 'messages', $locale);
                    $result['Description'] = $this->trans('only-autologin.notice', [], 'messages', $locale);
                }
            }
        }

        return $result;
    }

    public function getStatusByAccount($account, Usr $user, ?string $locale)
    {
        if ($account instanceof Account) {
            $provider = $account->getProviderid();
            $date = new DateTimeHandler();
            $regionalDate = $date->getDateFormats($user->getDateformat());
            $accountFields = [
                'TableName' => 'Account',
                'ProviderID' => $provider ? $provider->getId() : null,
                'CanCheck' => $provider ? $provider->getCancheck() : 0,
                'CanCheckBalance' => $provider ? $provider->getCancheckbalance() : 0,
                'SuccessCheckDate' => $account->getSuccesscheckdate() ? date($regionalDate['datelong'], $account->getSuccesscheckdate()->getTimestamp()) : '',
                'SuccessCheckDateTs' => $account->getSuccesscheckdate() ? $account->getSuccesscheckdate()->getTimestamp() : 0,
                'UpdateDate' => $account->getUpdatedate() ? date($regionalDate['datelong'], $account->getUpdatedate()->getTimestamp()) : '',
                'UpdateDateTs' => $account->getUpdatedate() ? $account->getUpdatedate()->getTimestamp() : 0,
                'RawUpdateDate' => $account->getUpdatedate() ? $account->getUpdatedate()->format('Y-m-d H:i:s') : null,
                'ErrorCode' => $account->getErrorcode(),
                'DisplayName' => $provider ? $provider->getDisplayname() : '',
                'ErrorMessage' => $account->getErrormessage(),
                'Site' => $provider ? $provider->getSite() : '',
                'FAQ' => $provider ? $provider->getFaq() : '',
            ];
            $ret = $this->getStatus($user, $accountFields, $locale);

            return $ret;
        }

        return [];
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('custom-coupon.notice'))->setDesc('This is a custom coupon which can\'t be updated automatically by AwardWallet.'),
            (new Message('custom-program.notice'))->setDesc('This is a custom program that you added. Award Wallet cannot automatically check the balance on this program.'),
            (new Message('unchecked.notice'))->setDesc('The balance information for this award program cannot be checked through AwardWallet.com. This is probably due to a technical difficulty. We will do our best to make it work again in the future. For now you can only use this program for auto login to the provider\'s website.'),
            (new Message('only-autologin.notice'))->setDesc('There is no balance information for this award program. You can only use this program for auto login to the provider\'s website.'),
            (new Message('custom-coupon.title'))->setDesc('Custom Coupon'),
            (new Message('custom-program.title'))->setDesc('Custom Program'),
            (new Message('unchecked.title'))->setDesc('Unchecked'),
            (new Message('only-autologin.title'))->setDesc('Auto-login only'),
            (new Message('notice'))->setDesc('Notice'),
            new Message('award.account.info-not-retrieved-yet'),
            new Message('error.award.account.invalid-logon.title'),
            new Message('error.award.account.missing-password.title'),
            new Message('error.award.account.missing-password.text'),
            new Message('error.award.account.locked-out.title'),
            new Message('error.award.account.other.title'),
            new Message('error.award.account.invalid-credentials.title'),
            new Message('error.award.account.invalid-credentials.text'),
            new Message('error.award.account.security-question.title'),
            new Message('error.award.account.security-question.text'),
            (new Message('error.award.account.timeout.title'))->setDesc('Time out'),
            (new Message('error.award.account.timeout.text'))->setDesc('Account update has timed out'),
            new Message('provider-return-error'),
            new Message('provider-return-error-manually-check'),
            new Message('provider-return-error-invalid-password'),
            new Message('provider-description-login-and-find'),
        ];
    }

    protected function getProgramMessage($accountFields, $result, ?string $locale)
    {
        if (!empty($accountFields['SuccessCheckDate'])) {
            $lastSuccessCheckDate = $accountFields['SuccessCheckDate'];
            $lastSuccessCheckAgo = $this->intervalFormatter->longFormatViaDateTimes(
                $this->clock->current()->getAsDateTime(),
                new \DateTime('@' . $accountFields['SuccessCheckDateTs']),
                true,
                true,
                $locale
            );
        }

        if (!empty($accountFields['UpdateDate'])) {
            $lastUpdateDate = $accountFields['UpdateDate'];
            $lastUpdateAgo = $this->intervalFormatter->longFormatViaDateTimes(
                $this->clock->current()->getAsDateTime(),
                new \DateTime('@' . $accountFields['UpdateDateTs']),
                true,
                true,
                $locale
            );
        }

        if (!empty($accountFields['ProviderID'])) {
            $accountFields['DisplayName'] = $this->providerTranslator->translateDisplayNameByScalars(
                $accountFields['ProviderID'],
                $accountFields['DisplayName'],
                $locale
            );
        }

        switch ($accountFields['ErrorCode']) {
            case ACCOUNT_UNCHECKED:
                $result['Title'] = $this->trans('notice', [], 'messages', $locale);
                $result['Description'] = $this->trans('award.account.info-not-retrieved-yet', [
                    '%displayName%' => $accountFields['DisplayName'],
                ], 'messages', $locale);

                break;

            case ACCOUNT_WARNING:
                $result['Title'] = $this->trans('notice', [], 'messages', $locale);

                if (isset($lastUpdateDate, $lastUpdateAgo, $accountFields['UpdateDateTs'])) {
                    $result['DateInfo'] = $this->trans('last-time-rewards-retrieving-desktop-v2', [
                        '%displayName%' => $accountFields['DisplayName'],
                        '%time-ago%' => $lastUpdateAgo,
                        '%span_on%' => sprintf(
                            '<span data-tip data-calc title="%s" data-date="%d" class="date">',
                            $lastUpdateDate,
                            $accountFields['UpdateDateTs']
                        ),
                        '%span_off%' => '</span>',
                        '%updateDate%' => $lastUpdateDate,
                    ], 'messages', $locale);
                }

                break;

            case ACCOUNT_INVALID_PASSWORD:
                $result['Title'] = $this->trans('error.award.account.invalid-logon.title', [], 'messages', $locale);

                break;

            case ACCOUNT_MISSING_PASSWORD:
                $result['Title'] = $this->trans('error.award.account.missing-password.title', [], 'messages', $locale);
                $result['Description'] = $this->trans('error.award.account.missing-password.text', [], 'messages', $locale);

                break;

            case ACCOUNT_LOCKOUT:
                $result['Title'] = $this->trans('error.award.account.locked-out.title', [], 'messages', $locale);

                break;

            case ACCOUNT_PROVIDER_ERROR:
                $result['Title'] = $this->trans('error.award.account.other.title', [], 'messages', $locale);

                break;

            case ACCOUNT_ENGINE_ERROR:
                $result['Title'] = $this->trans('error.award.account.other.title', [], 'messages', $locale);

                break;

            case ACCOUNT_PREVENT_LOCKOUT:
                $result['Title'] = $this->trans('error.award.account.invalid-credentials.title', [], 'messages', $locale);
                $result['Description'] = $this->trans('error.award.account.invalid-credentials.text', [], 'messages', $locale);

                break;

            case ACCOUNT_QUESTION:
                $result['Title'] = $this->trans('error.award.account.security-question.title', [], 'messages', $locale);
                $result['Description'] = $this->trans('error.award.account.security-question.text', [], 'messages', $locale);

                break;

            case ACCOUNT_TIMEOUT:
                $result['Title'] = $this->trans('error.award.account.timeout.title', [], 'messages', $locale);
                $result['Description'] = $this->trans('error.award.account.timeout.text', [], 'messages', $locale);

                break;
        }

        if (in_array($accountFields['ErrorCode'], [
            ACCOUNT_CHECKED, ACCOUNT_INVALID_PASSWORD, ACCOUNT_MISSING_PASSWORD,
            ACCOUNT_LOCKOUT, ACCOUNT_PROVIDER_ERROR, ACCOUNT_ENGINE_ERROR, ACCOUNT_PREVENT_LOCKOUT, ACCOUNT_QUESTION,
        ]) && isset($lastSuccessCheckDate, $lastSuccessCheckAgo, $accountFields['SuccessCheckDateTs'])) {
            $result['DateInfo'] = $this->trans('last-time-account-retrieving-desktop-v2', [
                '%time-ago%' => $lastSuccessCheckAgo,
                '%span_on%' => sprintf(
                    '<span data-tip data-calc title="%s" data-date="%d" class="date">',
                    $lastSuccessCheckDate,
                    $accountFields['SuccessCheckDateTs']
                ),
                '%span_off%' => '</span>',
                '%lastUpdate%' => $lastSuccessCheckDate,
            ], 'messages', $locale);
        }

        if (!empty($accountFields['ErrorMessage'])
            && $accountFields['ErrorMessage'] != "Unknown engine error"
            && $accountFields['ErrorCode'] != ACCOUNT_PREVENT_LOCKOUT) {
            if (in_array($accountFields['ErrorCode'], [ACCOUNT_ENGINE_ERROR, ACCOUNT_PROVIDER_ERROR, ACCOUNT_INVALID_PASSWORD])) {
                $result['BeforeError'] = $this->trans(
                    'provider-return-error',
                    ['%displayName%' => $accountFields['DisplayName']],
                    'messages',
                    $locale
                );
            }
        } elseif (in_array($accountFields['ErrorCode'], [ACCOUNT_ENGINE_ERROR, ACCOUNT_PROVIDER_ERROR])) {
            $result['Description'] = $this->trans(
                'provider-return-error-manually-check',
                ['%displayName%' => $accountFields['DisplayName']],
                'messages',
                $locale
            );
        } elseif ($accountFields['ErrorCode'] == ACCOUNT_INVALID_PASSWORD) {
            $result['Description'] = $this->trans(
                'provider-return-error-invalid-password',
                ['%site%' => $accountFields['Site']],
                'messages',
                $locale
            );
        }

        if (in_array($accountFields['ErrorCode'], [ACCOUNT_ENGINE_ERROR, ACCOUNT_INVALID_PASSWORD, ACCOUNT_PROVIDER_ERROR, ACCOUNT_PREVENT_LOCKOUT])) {
            if (!isset($accountFields['FAQ']) || $accountFields['FAQ'] == '') {
                $accountFields['FAQ'] = 9;
            } // hardcode!
            $result['Description'] = $this->trans(
                'provider-description-login-and-find',
                [
                    '%site%' => $accountFields['Site'],
                    '%displayName%' => $accountFields['DisplayName'],
                    '%faqId%' => $accountFields['FAQ'],
                ],
                'messages',
                $locale
            );
        }

        if (!empty($accountFields['ErrorMessage'])) {
            $result['Error'] = $accountFields['ErrorMessage'];
        }

        return $result;
    }
}
