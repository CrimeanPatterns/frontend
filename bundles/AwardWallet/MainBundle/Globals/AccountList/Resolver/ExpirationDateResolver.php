<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Resolver;

use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\PropertyFormatter;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExpirationDateResolver implements TranslationContainerInterface
{
    use TranslationCacheTrait;

    public const EXPIRE_UNKNOWN_TS = 9999999999;
    public const EXPIRE_DONT_EXPIRE_TS = 10000000000;
    public const EXPIRE_EMPTY_TS = 10000000001;

    // dont expire
    public const EXPIRE_STATE_TYPE_NOT_EXPIRE = 1;
    // far
    public const EXPIRE_STATE_TYPE_FAR = 2;
    // unknown date
    public const EXPIRE_STATE_TYPE_UNKNOWN = 3;
    // soon
    public const EXPIRE_STATE_TYPE_SOON = 4;
    // expired + error
    public const EXPIRE_STATE_TYPE_EXPIREDERR = 5;
    // expired + set by user
    public const EXPIRE_STATE_TYPE_EXPIREDSET = 6;
    // expired without error
    public const EXPIRE_STATE_TYPE_EXPIRED = 7;

    // set date by user
    public const EXPIRE_MODE_TYPE_MANUAL = 1;
    // set dont expire by user
    public const EXPIRE_MODE_TYPE_NEVER = 2;
    // date calculated by us
    public const EXPIRE_MODE_TYPE_CALC = 3;

    public const EXPIRE_MODE_TYPE_UNKNOWN = 4;

    public const FAR_DAYS_DEFAULT = -90;
    public const FAR_DAYS_PASSPORT = -360;

    /**
     * date is not being updated by AwardWallet automatically
     * First check account - set expiration date, second check - unknown expiration date.
     */
    public const EXPIRE_MODE_TYPE_NOTAUTO = 4;

    protected EntityManagerInterface $em;

    protected LocalizeService $localizer;

    private ProviderTranslator $providerTranslator;
    private ClockInterface $clock;
    private PropertyFormatter $propertyFormatter;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        ProviderTranslator $providerTranslator,
        LocalizeService $localizer,
        ClockInterface $clock,
        PropertyFormatter $propertyFormatter
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->localizer = $localizer;
        $this->providerTranslator = $providerTranslator;
        $this->clock = $clock;
        $this->propertyFormatter = $propertyFormatter;
    }

    /**
     * @param $options array:
     *
     *  TableName, hasCoupons, isCustom, isSubaccount, isCoupon, Balance
     *  CanCheckExpiration, ExpirationAlwaysKnown, DontTrackExpiration, ExpirationAutoSet, ExpirationDateNote
     *  Currency, hasError, locale
     *  ExpirationWarning => [DisplayName, ProviderCode, ExpirationDateNote, ExpirationUnknownNote, ExpirationWarning, Properties]
     * @return array
     */
    public function getExpirationInfo($date, $options)
    {
        $defaults = $result = self::getDefaultFields();

        $isAccount = isset($options['TableName']) && $options['TableName'] == 'Account';
        $hasCoupons = isset($options['hasCoupons']) && $options['hasCoupons'];
        $isCustom = isset($options['isCustom']) && $options['isCustom'];
        $isCoupon = isset($options['TableName']) && $options['TableName'] == 'Coupon';
        $isSubaccount = isset($options['isSubaccount']) && $options['isSubaccount'];
        $isSubCoupon = isset($options['isCoupon']) && $options['isCoupon'];
        $isPassport = $options['isPassport'] ?? false;

        if (!$isSubaccount && !$isCoupon && empty($options['Balance'])) {
            return $result;
        }

        if (!$isSubaccount && !$isCoupon
            && (empty($options['Balance']) && (empty($date)
                    && (1 == $options['ExpirationAlwaysKnown'] || EXPIRATION_UNKNOWN == $options['ExpirationAutoSet'] || CAN_CHECK_EXPIRATION_YES == $options['CanCheckExpiration']))
            )
        ) {
            return $result;
        }

        $farDays = self::FAR_DAYS_DEFAULT;

        if ($isPassport) {
            $farDays = self::FAR_DAYS_PASSPORT;
        }

        if (!empty($date)) {
            $result['ExpirationKnown'] = true;
            $result['ExpirationDateTs'] = strtotime($date);
            $result['ExpirationDate'] = $this->localizer->formatDateTime(
                $result['ExpirationDateTime'] = new \DateTime('@' . $result['ExpirationDateTs']), 'short', 'none', $options['locale']
            );

            $expires = ($this->clock->current()->getAsSecondsInt() - $result['ExpirationDateTs']) / SECONDS_PER_DAY;

            if ($expires > $farDays && $expires <= 0) {
                $result['ExpirationState'] = "soon";
            } elseif ($expires <= $farDays) {
                $result['ExpirationState'] = "far";
            } elseif ($expires > 0) {
                $result['ExpirationState'] = "expired";
            }
        }

        // "don't expire" state
        if (
            // for account
            (
                $isAccount
                && $options["CanCheckExpiration"] == CAN_CHECK_EXPIRATION_NEVER_EXPIRES
                && $options['ExpirationAlwaysKnown'] == '1'
            )    // for account or custom program
            || (
                ($isAccount || $isCustom || $isCoupon)
                && $options['DontTrackExpiration'] == '1'
            )    // for account - manually set in parser SetExpirationDateNever()
            || (
                $isAccount
                && $options['ExpirationAutoSet'] == EXPIRATION_AUTO
                && empty($date)
            )    // for subaccount-coupon
            || (
                $isSubCoupon
                && empty($date)
                && $options['ExpirationAutoSet'] != EXPIRATION_UNKNOWN
            )
            // coupon always expire
        ) {
            $result['ExpirationDate'] = $this->translator->trans('do.not.expire', [], 'messages', $options['locale']);

            if ($options['ExpirationDateNote']) {
                $result['ExpirationDetails'] = html_entity_decode($options['ExpirationDateNote']);
            }
            $result['ExpirationDateTs'] = self::EXPIRE_DONT_EXPIRE_TS;

            if ((empty($date) && EXPIRATION_UNKNOWN == $options['ExpirationAutoSet'])
                || (CAN_CHECK_EXPIRATION_NEVER_EXPIRES == $options['CanCheckExpiration'] && empty($options['DontTrackExpiration']))) {
                $result['ExpirationKnown'] = false;
            } else {
                $result['ExpirationKnown'] = true;
            }
            $result['ExpirationState'] = 'far';
            $result['ExpirationStateType'] = self::EXPIRE_STATE_TYPE_NOT_EXPIRE;

            if (isset($options['ExpirationWarning']) && isset($options['ExpirationWarning']['ExpirationWarning']) && empty($options['DontTrackExpiration'])) {
                $result['ExpirationDate'] = $options['ExpirationWarning']['ExpirationWarning'];
            }
        }
        // "far" state
        elseif (
            !empty($result['ExpirationDateTs'])
            && !empty($result['ExpirationState'])
            && $result['ExpirationState'] == 'far'
        ) {
            $result['ExpirationStateType'] = self::EXPIRE_STATE_TYPE_FAR;
        }
        // "unknown" state
        elseif (
            (   // for account
                $isAccount
                && empty($date)
                && !$hasCoupons
                && $options['ExpirationAutoSet'] == EXPIRATION_UNKNOWN
            )
            || (   // for custom program and coupon
                ($isCustom || $isCoupon)
                && empty($date)
            )
        ) {
            $result['ExpirationDate'] = $this->translator->trans(
                'award.account.list.currency-expiration-unknown',
                [],
                'messages',
                $options['locale']
            );
            $result['ExpirationDateTs'] = self::EXPIRE_UNKNOWN_TS;
            $result['ExpirationKnown'] = false;
            $result['ExpirationState'] = 'soon';
            $result['ExpirationStateType'] = self::EXPIRE_STATE_TYPE_UNKNOWN;

            if ($isCustom || $isCoupon) {
                unset($options['ExpirationWarning']);

                if (empty($options['Balance'])) {
                    $result['ExpirationDate'] = '';
                    $result['ExpirationStateType'] = null;
                }
            }
        }
        // "soon" state
        elseif (
            !empty($result['ExpirationDateTs'])
            && !empty($result['ExpirationState'])
            && $result['ExpirationState'] == 'soon'
        ) {
            $result['ExpirationStateType'] = self::EXPIRE_STATE_TYPE_SOON;
        }
        // "expired" state
        elseif (
            !empty($result['ExpirationDateTs'])
            && !empty($result['ExpirationState'])
            && $result['ExpirationState'] == 'expired'
        ) {
            // "expired + error"
            if ($options['hasError'] && ($isAccount || $isSubaccount)) {
                $result['ExpirationStateType'] = self::EXPIRE_STATE_TYPE_EXPIREDERR;
            } elseif (
                (
                    $isAccount
                    && $options['ExpirationAutoSet'] == EXPIRATION_USER
                )
                    || ($isCustom || $isCoupon)
            ) {
                $result['ExpirationStateType'] = self::EXPIRE_STATE_TYPE_EXPIREDSET;
            } else {
                $result['ExpirationStateType'] = self::EXPIRE_STATE_TYPE_EXPIRED;
            }
        } else {
            return $defaults;
        }

        if ($isAccount && $result['ExpirationKnown']) {
            if ($options['DontTrackExpiration'] == 1) {
                $result["ExpirationMode"] = 'pen';
                $result["ExpirationModeType"] = self::EXPIRE_MODE_TYPE_NEVER;
            } elseif (in_array($options['ExpirationAutoSet'], [EXPIRATION_USER]) && !empty($result['ExpirationDateTs'])) {
                $result["ExpirationMode"] = 'pen';
                $result["ExpirationModeType"] = self::EXPIRE_MODE_TYPE_MANUAL;
            } elseif (!empty($options['ExpirationDateNote'])
                && !empty($result['ExpirationDateTs'])
                && $result['ExpirationStateType'] != self::EXPIRE_STATE_TYPE_NOT_EXPIRE) {
                $result["ExpirationMode"] = 'calc';
                $result["ExpirationModeType"] = self::EXPIRE_MODE_TYPE_CALC;
            } elseif (in_array($options['ExpirationAutoSet'], [EXPIRATION_UNKNOWN]) && !empty($result['ExpirationDateTs'])) {
                $result["ExpirationMode"] = 'warn';
                $result["ExpirationModeType"] = self::EXPIRE_MODE_TYPE_NOTAUTO;
            }
        }

        // if expiration not retrieved and expirationDate is set (case #14251)
        if ($date && $options['ExpirationAutoSet'] == EXPIRATION_UNKNOWN) {
            $result["ExpirationModeType"] = self::EXPIRE_MODE_TYPE_UNKNOWN;
            $result["ExpirationMode"] = 'warn';
        }

        if (isset($options['ExpirationWarning'])
            && ($result['ExpirationKnown'] || $result['ExpirationStateType'] == self::EXPIRE_STATE_TYPE_UNKNOWN)) {
            $result['ExpirationDetails'] = $this->getExpirationWarning(
                array_merge($result, ['ExpirationBalance' => ($options['Balance'] ?? null)]),
                $options['ExpirationWarning'],
                $options['locale']
            );
        }

        // refs 14782#note-25 - AccountExpirationWarning
        if (isset($options['ExpirationWarning']['ExpirationWarning'])) {
            if ('do not expire with elite status' == $options['ExpirationWarning']['ExpirationWarning']) {
                $result['ExpirationDate'] = $options['ExpirationWarning']['ExpirationWarning'];
                $result['ExpirationDateTime'] = null;
                $result['ExpirationKnown'] = true;
                $result['ExpirationState'] = 'far';
                $result['ExpirationStateType'] = self::EXPIRE_STATE_TYPE_NOT_EXPIRE;
                $result['ExpirationDetails'] = '';
                $result['ExpirationMode'] = '';
                $result['ExpirationModeType'] = '';
            } elseif (!empty($options['ExpirationWarning']['ExpirationWarning']) && !empty($result['ExpirationDateTime'])) {
                $result['ExpirationMode'] = 'calc';
                $result['ExpirationModeType'] = self::EXPIRE_MODE_TYPE_CALC;
                $result['ExpirationDetails'] = $options['ExpirationWarning']['ExpirationWarning'];
            }
        }

        // refs #15917
        //        if(
        //            isset($result['ExpirationDetails']) &&
        //            isset($result['ExpirationMode']) &&
        //            $result['ExpirationMode'] === 'calc'
        //        ){
        //            $result["ExpirationMode"] = 'info';
        //        }

        return $result;
    }

    public static function getDefaultFields()
    {
        return [
            'ExpirationDate' => null,
            'ExpirationDateTs' => self::EXPIRE_EMPTY_TS, // for sort
            'ExpirationDateTime' => null, // \DateTime object
            'ExpirationKnown' => false, // if known, it can be hidden for ACCOUNT_LEVEL_FREE
            'ExpirationState' => null, // soon/far/expired
            'ExpirationStateType' => null, // example for tooltips
            'ExpirationMode' => null, // pen/calc/warn (only account or coupon, not subaccount)
            'ExpirationModeType' => null, // for tooltips (only account or coupon, not subaccount)
            'ExpirationWarning' => null, // for popup
        ];
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('account.expire.note.calculated'))
                ->setDesc('This expiration date was calculated by AwardWallet and not by %displayName%,
                so this date could be inaccurate. AwardWallet has no way to guarantee the accuracy of this calculated expiration date.
                Please remember that the most accurate way to determine your point expiration date
                is to contact %displayName% directly. Here is how we got this value: %instruction%'),
            (new Message('account.last-activity.unknown'))->setDesc('Unknown date'), // Unknown activity date for expiration popup
            (new Message('account.expire.default-warning'))->setDesc('The balance on this award program [ExpireAction] on %date%'),
            (new Message('account.expire.warning.without-note'))
                ->setDesc('%displayName% on their website state that the balance on this award program is due to expire on %date%'), // If empty expiration date note and expiration date warning and date in future
            (new Message('account.expire.action.due-to-expire'))->setDesc('due to expire'),
            (new Message('account.expire.action.might-have-expired'))->setDesc('might have expired'),
            (new Message('account.expire.manual-warning'))->setDesc('You have specified that the balance on this award program is due to expire on %date%'),
            (new Message('account.expire.dont-expire-warning'))
                ->setDesc('We\'ve determined that no points or miles are
                    due to expire on this reward program. We cannot guarantee this, as reward program rules
                    change all the time, so the best way to check is to contact [DisplayName] directly.
                    We will do our best at monitoring this reward program, and notifying you of any changes.'),
            (new Message('account.expire.unknown-expire-warning'))
                ->setDesc('At this point AwardWallet doesn’t know how to get your expiration date for this program.
                    We are constantly working on figuring out how to calculate expiration dates for reward programs.
                    If you happen to know a way to determine expiration date for [DisplayName] by looking at
                    your profile please send us a note with a detailed description of what you do to figure out
                    expiration and we will attempt to implement it.'),
            (new Message('do.not.expire'))->setDesc('do not expire'),
        ];
    }

    private function getExpirationWarning($expireFields, $options, ?string $locale = null)
    {
        $result = null;

        if ($expireFields['ExpirationStateType'] == self::EXPIRE_STATE_TYPE_UNKNOWN) {
            if (empty($options['ExpirationUnknownNote'])) {
                return html_entity_decode(str_ireplace("[DisplayName]",
                    $options['DisplayName'],
                    $this->trans('account.expire.unknown-expire-warning', [], 'messages', $locale)
                ));
            } else {
                return html_entity_decode($options['ExpirationUnknownNote']);
            }
        } elseif ($expireFields['ExpirationStateType'] == self::EXPIRE_STATE_TYPE_NOT_EXPIRE
            && !in_array($expireFields['ExpirationModeType'], [self::EXPIRE_MODE_TYPE_MANUAL, self::EXPIRE_MODE_TYPE_NEVER])) {
            $result = str_ireplace(
                "[DisplayName]",
                $options['DisplayName'],
                $this->trans('account.expire.dont-expire-warning', [], 'messages', $locale)
            );

            if (!empty($options["ExpirationDateNote"])) {
                $result .= "<br><br>" . $options["ExpirationDateNote"];
            }

            return html_entity_decode($result);
        } elseif (!in_array($expireFields['ExpirationStateType'], [
            self::EXPIRE_STATE_TYPE_NOT_EXPIRE,
            self::EXPIRE_STATE_TYPE_UNKNOWN,
        ])) {
            $note = '';

            if (
                ($expireFields['ExpirationModeType'] == self::EXPIRE_MODE_TYPE_CALC && !empty($options['ExpirationDateNote']))
                || (
                    isset($options['ExpirationAutoSet'])
                    && $expireFields['ExpirationModeType'] == self::EXPIRE_MODE_TYPE_UNKNOWN
                    && $expireFields['ExpirationDate']
                    && $options['ExpirationDateNote']
                    && EXPIRATION_UNKNOWN == $options['ExpirationAutoSet']
                    && in_array($expireFields['ExpirationStateType'], [self::EXPIRE_STATE_TYPE_FAR, self::EXPIRE_STATE_TYPE_SOON])
                )
            ) {
                $lastActivity = null;
                $earningDate = null;

                if ($options['Properties'] && is_array($options['Properties'])) {
                    foreach ($options['Properties'] as $property) {
                        if (isset($property['Kind']) && $property['Kind'] == PROPERTY_KIND_LAST_ACTIVITY) {
                            $lastActivity = $this->formatDateTime($property, $locale);
                        } elseif ($property['Code'] == 'EarningDate') {
                            $earningDate = $this->formatDateTime($property, $locale);
                        }
                    }
                }
                $replacement = '$1';

                if (!isset($lastActivity)) {
                    $replacement = '$2';
                }
                $options['ExpirationDateNote'] = preg_replace(
                    '/\[activity\](.*)\[\/activity\]\s*\[noactivity\](.*)\[\/noactivity\]/ims',
                    $replacement,
                    $options['ExpirationDateNote'],
                    1
                );
                $note = $this->trans('account.expire.note.calculated', [
                    '%displayName%' => $options['DisplayName'],
                    '%instruction%' => $options['ExpirationDateNote'],
                ], 'messages', $locale);
                $isLastActivityWordFound = stripos($note, '[LastActivity]');
                $isEarningDateWordFound = stripos($note, '[EarningDate]');

                $default = $this->trans('account.last-activity.unknown', [], 'messages', $locale);
                $note = str_ireplace("[LastActivity]", $lastActivity ?? $default, $note);
                // # New property is used in calculating Expiration Date for preflight, lukoil etc.
                $note = str_ireplace("[EarningDate]", $earningDate ?? $default, $note);

                if (false !== stripos($note, '[ExpiringBalance]')) {
                    if (!$isLastActivityWordFound && !$isEarningDateWordFound) {
                        $note = $options['ExpirationDateNote'];
                    }
                    $expBalance = isset($options['Properties']['ExpiringBalance']['Val'])
                    && (
                        $this->localizer->isFormattedNumber($options['Properties']['ExpiringBalance']['Val'])
                        || $options['Properties']['ExpiringBalance']['Val'] > 0
                    )
                        ? $options['Properties']['ExpiringBalance']['Val']
                        : $expireFields['ExpirationBalance'];

                    if (!$this->localizer->isFormattedNumber($expBalance)) {
                        $expBalance = $this->localizer->formatNumber($expBalance, null, $locale);
                    }
                    $note = str_ireplace('[ExpiringBalance]', $expBalance, $note);
                }
            }
            $note = html_entity_decode($note);
            $result = $this->trans('account.expire.default-warning', [
                '%date%' => $expireFields['ExpirationDate'],
            ], 'messages', $locale);

            if (!empty($options["ExpirationWarning"])) {
                $result = str_ireplace("[NoNote]", "", $options["ExpirationWarning"]);

                if ($result != $options["ExpirationWarning"]) {
                    $note = "";
                }
            }

            if (!empty($note)) {
                $note = '<br><br>' . $note;
            }
            $result .= $note;

            if (array_key_exists('Properties', $options) && is_array($options['Properties'])) {
                foreach ($options['Properties'] as $prop) {
                    if (!empty($prop['ProviderID'])) {
                        $options['DisplayName'] = $this->providerTranslator->translateDisplayNameByScalars(
                            $prop['ProviderID'],
                            $options['DisplayName']
                        );

                        break;
                    }
                }
            }

            // refs #8918
            if (empty($note)
                && empty($options["ExpirationWarning"])
                && in_array($expireFields['ExpirationStateType'], [self::EXPIRE_STATE_TYPE_FAR, self::EXPIRE_STATE_TYPE_SOON])) {
                $result = $this->trans('account.expire.warning.without-note', [
                    '%displayName%' => $options['DisplayName'],
                    '%date%' => $expireFields['ExpirationDate'],
                ], 'messages', $locale);
            }
            $expireAction = $this->trans('account.expire.action.due-to-expire', [], 'messages', $locale);

            if (in_array($expireFields['ExpirationStateType'], [
                self::EXPIRE_STATE_TYPE_EXPIRED,
                self::EXPIRE_STATE_TYPE_EXPIREDERR,
                self::EXPIRE_STATE_TYPE_EXPIREDSET,
            ])) {
                $expireAction = $this->trans('account.expire.action.might-have-expired', [], 'messages', $locale);
            }

            if ($expireFields["ExpirationModeType"] == self::EXPIRE_MODE_TYPE_MANUAL) {
                $result = $this->trans('account.expire.manual-warning', [
                    '%date%' => $expireFields['ExpirationDate'],
                ], 'messages', $locale);
            }
            $result = str_ireplace("[ExpireAction]", $expireAction, $result);
        }

        return $result;
    }

    private function formatDateTime(array $property, ?string $locale): ?string
    {
        if (isset($property['Type']) && $property['Type'] == Providerproperty::TYPE_DATE) {
            $property['Val'] = $this->propertyFormatter->format($property['Val'], $property['Type'], $locale);
            $date = date_create($property['Val']);

            if ($date) {
                $date = $this->localizer->formatDateTime($date, 'short', 'none', $locale);
            } else {
                $date = $property['Val'];
            }
        } else {
            $date = $property['Val'];
        }

        return $date;
    }
}
