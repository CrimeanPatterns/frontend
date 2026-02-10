<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\State;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ExpirationDateResolver;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ProgramStatusResolver;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountTotalCalculator;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Security\Voter\SessionVoter;
use AwardWallet\MainBundle\Service\AmericanAirlinesAAdvantageDetector;
use AwardWallet\MainBundle\Service\BalanceFormatter;
use AwardWallet\MainBundle\Service\BalanceWatch\Timeout;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Mapper extends MapperAbstract
{
    protected UserMailboxCounter $userMailboxCounter;
    /**
     * @var string[]
     */
    private $internalErrors = ['Can\'t determine login state', 'Login form not found', 'Password form not found', 'Itinerary form not found'];

    private Timeout $bwTimeout;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        ProviderTranslator $providerTranslator,
        LocalizeService $localizer,
        SessionVoter $sessionVoter,
        DateTimeIntervalFormatter $intervalFormatter,
        ExpirationDateResolver $expirationResolver,
        ProgramStatusResolver $statusResolver,
        BalanceFormatter $balanceFormatter,
        Timeout $bwTimeout,
        UserMailboxCounter $userMailboxCounter,
        PropertyFormatter $propertyFormatter,
        ClockInterface $clock,
        MileValueCards $mileValueCards,
        MileValueService $mileValueService
    ) {
        parent::__construct(
            $em,
            $translator,
            $providerTranslator,
            $localizer,
            $sessionVoter,
            $intervalFormatter,
            $expirationResolver,
            $statusResolver,
            $balanceFormatter,
            $propertyFormatter,
            $clock,
            $mileValueCards,
            $mileValueService
        );
        $this->bwTimeout = $bwTimeout;
        $this->userMailboxCounter = $userMailboxCounter;
    }

    public function map(MapperContext $mapperContext, $accountID, $accountFields, $accountsIds)
    {
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);

        // init permissions
        if (!isset($mapperContext->rights) || $mapperContext->rightsTag != serialize($accountsIds)) {
            $mapperContext->rightsTag = serialize($accountsIds);
            $this->initPermissions($mapperContext, $accountsIds);
        }

        if (isset($mapperContext->dataTemplate) && array_key_exists('Shares', $mapperContext->dataTemplate)) {
            if (!isset($mapperContext->shares) || $mapperContext->rightsTag != serialize($accountsIds)) {
                $this->initShares($mapperContext, $accountsIds);
            }
        }

        // TODO: move to DataLoader
        if (!empty($accountFields['BalanceWatchStartDate'])) {
            $watchExpire = $this->bwTimeout->getTimeoutSecondsByAccountId($accountFields['ID']);
            $accountFields['BalanceWatchExpirationDate'] = $watchExpire > 0 ? \strtotime($accountFields['BalanceWatchStartDate']) + $watchExpire : 0;
        }

        // original MileValue
        if (isset($accountFields['MileValue'])) {
            $accountFields['OriginalMileValue'] = $accountFields['MileValue'];
        }

        if (StringUtils::isNotEmpty($accountFields['CustomFields'])) {
            $accountFields['CustomFields'] = \json_decode($accountFields['CustomFields'], true);
        }

        switch ($accountFields["TableName"]) {
            case "Account":
                $accountFields = $this->mapAccount($mapperContext, $accountID, $accountFields);

                break;

            case "Coupon":
                $accountFields = $this->mapCoupon($mapperContext, $accountID, $accountFields);

                break;
        }
        // Error message for Account Details
        $accountFields['ProgramMessage'] = $this->statusResolver->getStatus($user, $accountFields, $locale);

        $accountFields = $this->mapSubAccounts($mapperContext, $accountID, $accountFields);
        $accountFields = $this->mapProperties($mapperContext, $accountID, $accountFields);
        $accountFields = $this->formatTotals($mapperContext, $accountFields);
        $accountFields = $this->filter($mapperContext, $accountID, $accountFields);

        if (isset($accountFields['Balance'])) {
            $accountFields['Balance'] = $this->normalizeBalance($accountFields['Balance']);
        }

        if (isset($accountFields['LastBalance'])) {
            $accountFields['LastBalance'] = $this->normalizeBalance($accountFields['LastBalance']);
        }

        if (isset($accountFields["SubAccountsArray"]) && is_array($accountFields["SubAccountsArray"])) {
            foreach ($accountFields['SubAccountsArray'] as $k => &$subAccount) {
                if (isset($subAccount['Balance'])) {
                    $subAccount['Balance'] = $this->normalizeBalance($subAccount['Balance']);
                }

                if (isset($subAccount['LastBalance'])) {
                    $subAccount['LastBalance'] = $this->normalizeBalance($subAccount['LastBalance']);
                }
            }
            $accountFields['SubAccountsArray'] = array_values($accountFields['SubAccountsArray']);
        }

        if (isset($accountFields['comment'])) {
            $accountFields['comment'] = $this->normalizeComment($accountFields['comment']);

            if ($mapperContext->options->has(Options::OPTION_COMMENT_LENGTH_LIMIT)) {
                $accountFields['comment'] = StringHandler::strLimit($accountFields['comment'], $mapperContext->options->get(Options::OPTION_COMMENT_LENGTH_LIMIT), '');
            }
        }

        if (!empty($accountFields['ProviderID'])) {
            $accountFields['DisplayName'] = $this->providerTranslator->translateDisplayNameByScalars(
                $accountFields['ProviderID'],
                $accountFields['DisplayName'],
                $locale
            );
        }

        // output specific data
        $template = $mapperContext->dataTemplate;

        if (is_array($template) && sizeof($template)) {
            $result = $this->array_intersect_key_recursive($accountFields, $template);

            return $result;
        }

        return $accountFields;
    }

    public function formatDocumentProperties(array $accountFields, ?string $locale): array
    {
        $isCustomFields = !empty($accountFields['CustomFields']);
        $isVaccineCard = $isCustomFields && array_key_exists(Providercoupon::FIELD_KEY_VACCINE_CARD, $accountFields['CustomFields']);
        $isInsuranceCard = $isCustomFields && array_key_exists(Providercoupon::FIELD_KEY_INSURANCE_CARD, $accountFields['CustomFields']);
        $isVisa = $isCustomFields && array_key_exists(Providercoupon::FIELD_KEY_VISA, $accountFields['CustomFields']);
        $isDriverLicense = $isCustomFields && array_key_exists(Providercoupon::FIELD_KEY_DRIVERS_LICENSE, $accountFields['CustomFields']);
        $isPassport = $isCustomFields && array_key_exists(Providercoupon::FIELD_KEY_PASSPORT, $accountFields['CustomFields']);
        $isPriorityPass = $isCustomFields && array_key_exists(Providercoupon::FIELD_KEY_PRIORITY_PASS, $accountFields['CustomFields']);

        if ($isVaccineCard) {
            $customFieldKey = Providercoupon::FIELD_KEY_VACCINE_CARD;
            $vaccineCard = &$accountFields['CustomFields'][$customFieldKey];

            if (!empty($vaccineCard['disease'])) {
                $accountFields['DisplayName'] = trim($vaccineCard['disease'] . ' ' . $accountFields['DisplayName']);
            }

            if (!empty($vaccineCard['countryIssue'])) {
                $vaccineCard['countryIssue'] = $this->getLocalizedCountry($vaccineCard['countryIssue']);
            }

            $dateFields = ['firstDoseDate', 'secondDoseDate', 'boosterDate', 'secondBoosterDate', 'dateOfBirth', 'certificateIssued'];
            $propertiesFields = [
                'firstDoseDate' => $this->translator->trans('first-dose-date', [], 'messages', $locale),
                'firstDoseVaccine' => $this->translator->trans('first-dose-vaccine', [], 'messages', $locale),
                'secondDoseDate' => $this->translator->trans('second-dose-date', [], 'messages', $locale),
                'secondDoseVaccine' => $this->translator->trans('second-dose-vaccine', [], 'messages', $locale),
                'boosterDate' => $this->translator->trans('booster-date', [], 'messages', $locale),
                'boosterVaccine' => $this->translator->trans('booster-vaccine', [], 'messages', $locale),
                'secondBoosterDate' => $this->translator->trans('second-booster-date', [], 'messages', $locale),
                'secondBoosterVaccine' => $this->translator->trans('second-booster-vaccine', [], 'messages', $locale),
            ];
        } elseif ($isInsuranceCard) {
            $customFieldKey = Providercoupon::FIELD_KEY_INSURANCE_CARD;
            $insuranceCard = &$accountFields['CustomFields'][$customFieldKey];
            $accountFields['AccountStatus'] = '';
            $accountFields['DisplayName'] = trim($insuranceCard['insuranceCompany'] . ' ' . $accountFields['DisplayName']);

            $insuranceCard['insuranceType'] = ArrayVal(Providercoupon::INSURANCE_TYPE_LIST, $insuranceCard['insuranceType'], $insuranceCard['insuranceType']);
            $insuranceCard['insuranceType2'] = ArrayVal(Providercoupon::INSURANCE_TYPE2_LIST, $insuranceCard['insuranceType2'], $insuranceCard['insuranceType2']);
            $insuranceCard['policyHolder'] = ArrayVal(Providercoupon::INSURANCE_POLICY_HOLDER_LIST, $insuranceCard['policyHolder'], $insuranceCard['policyHolder']);
            $insuranceCard['expirationDate'] = empty($accountFields['ExpirationDateTime']) ? null : $this->localizer->formatDate($accountFields['ExpirationDateTime'], 'long', $locale);

            $propertiesFields = [
                'insuranceType2' => $this->translator->trans('insurance-type', [], 'messages', $locale),
                'effectiveDate' => $this->translator->trans('effective-date', [], 'messages', $locale),
                'expirationDate' => $this->translator->trans('card.expiration', [], 'messages', $locale),
                'memberServicePhone' => $this->translator->trans('member-service-phone', [], 'messages', $locale),
                'preauthPhone' => $this->translator->trans('preauthorization-phone', [], 'messages', $locale),
                'otherPhone' => $this->translator->trans('other-phone', [], 'messages', $locale),
            ];
        } elseif ($isVisa) {
            $customFieldKey = Providercoupon::FIELD_KEY_VISA;
            $visa = &$accountFields['CustomFields'][$customFieldKey];
            $accountFields['AccountStatus'] = '';

            $visa['validUntil'] = empty($accountFields['ExpirationDateTime']) ? null : $this->localizer->formatDate($accountFields['ExpirationDateTime'], 'long', $locale);
            $visa['countryVisa'] = $this->getLocalizedCountry($visa['countryVisa']);
            $accountFields['DisplayName'] = $this->translator->trans('visa-for', [
                '%name%' => $visa['countryVisa'],
            ], 'messages', $locale);

            $dateFields = ['issueDate', 'validFrom'];
            $propertiesFields = [
                'visaNumber' => $this->translator->trans('visa-number', [], 'messages', $locale),
                'category' => $this->translator->trans('coupon.category', [], 'messages', $locale),
                'durationInDays' => $this->translator->trans('duration-in-days', [], 'messages', $locale),
                'issuedIn' => $this->translator->trans('issued-in', [], 'messages', $locale),
            ];
        } elseif ($isDriverLicense) {
            $customFieldKey = Providercoupon::FIELD_KEY_DRIVERS_LICENSE;
            $driverLicense = &$accountFields['CustomFields'][$customFieldKey];
            $accountFields['AccountStatus'] = '';

            $driverLicense['country'] = $this->getLocalizedCountry($driverLicense['country']);
            $driverLicense['state'] = $this->getState($driverLicense['state']);

            if (!empty($driverLicense['state'])) {
                $accountFields['DisplayName'] = trim($this->translator->trans('title-drivers-license', ['%name%' => $driverLicense['state']], 'messages', $locale));
            } else {
                $accountFields['DisplayName'] = trim($this->translator->trans('title-drivers-license', ['%name%' => $driverLicense['country']], 'messages', $locale));
            }

            $dateFields = ['issueDate', 'dateOfBirth'];
            $propertiesFields = [
                'sex' => $this->translator->trans('sex', [], 'messages', $locale),
                'eyes' => $this->translator->trans('eyes', [], 'messages', $locale),
                'height' => $this->translator->trans('height', [], 'messages', $locale),
                'class' => $this->translator->trans('class', [], 'messages', $locale),
                'organDonor' => [
                    'name' => $this->translator->trans('organ-donor', [], 'messages', $locale),
                    'extend' => ['hideVal' => true, 'checked' => true],
                ],
            ];
        } elseif ($isPassport) {
            $customFieldKey = Providercoupon::FIELD_KEY_PASSPORT;
            $passport = &$accountFields['CustomFields'][$customFieldKey];

            if (isset($passport['country'])) {
                $accountFields['DisplayName'] = $accountFields['DisplayName'] . ' (' . $passport['country'] . ')';
            }

            if (isset($passport['countryId'])
                && Country::UNITED_STATES === (int) $passport['countryId']
                && !empty($accountFields['Blogs']['BlogIdsMileExpiration'][0])
            ) {
                $post = $accountFields['Blogs']['BlogIdsMileExpiration'][0];
                $accountFields['ExpirationBlogPost'] = [
                    'title' => $post['title'],
                    'imageURL' => $post['imageURL'],
                    'postURL' => StringUtils::replaceVarInLink(
                        $post['postURL'],
                        ['cid' => 'acct-details-exp', 'mid' => 'web'],
                        true
                    ),
                    'isShowDetailsPopup' => true,
                ];
            }
        } elseif ($isPriorityPass) {
            $customFieldKey = Providercoupon::FIELD_KEY_PRIORITY_PASS;
            $priorityPass = &$accountFields['CustomFields'][$customFieldKey];
            $priorityCard = !empty($priorityPass['creditCardId'])
                ? StringHandler::supCopyrightSymbols($this->getCreditCard((int) $priorityPass['creditCardId']) ?? '')
                : '';
            $accountFields['AccountStatus'] = '';

            if (!empty($priorityCard)) {
                $priorityPass['creditCardId'] = $priorityCard;
            }

            $propertiesFields = [
                'creditCardId' => $this->translator->trans('credit-card', [], 'messages', $locale),
                'isSelect' => [
                    'name' => $this->translator->trans('aw.onecard.th.select', [], 'messages', $locale),
                    'extend' => ['hideVal' => true, 'checked' => true],
                ],
            ];
        }

        if (empty($propertiesFields)) {
            return $accountFields;
        }

        $localizer = $this->localizer;
        $fillPropertiesList = function (array $fields, array $data) use ($localizer, $locale) {
            $result = [];

            foreach ($fields as $key => $title) {
                $item = $data[$key] ?? '';
                $extend = [];

                if ((is_string($item) || is_int($item)) && !empty($item)) {
                    $value = $item;
                } elseif (is_array($item) && array_key_exists('date', $item)) {
                    $value = $localizer->formatDate(new \DateTime($item['date']), 'long', $locale);
                } elseif (is_array($title) && array_key_exists('name', $title) && !empty($item)) {
                    $extend = $title['extend'] ?? [];
                    $value = '';
                    $title = $title['name'];
                } else {
                    continue;
                }

                $result[$key] = [
                    'Name' => $title,
                    'Code' => $key,
                    'Val' => $value,
                    'Visible' => '1',
                ] + $extend;
            }

            return $result;
        };

        $accountFields['Properties'] = array_merge(
            $accountFields['Properties'],
            $fillPropertiesList($propertiesFields, $accountFields['CustomFields'][$customFieldKey])
        );

        if (!empty($dateFields)) {
            foreach ($dateFields as $key) {
                if (isset($accountFields['CustomFields'][$customFieldKey][$key])
                    && is_array($accountFields['CustomFields'][$customFieldKey][$key])
                    && array_key_exists('date', $accountFields['CustomFields'][$customFieldKey][$key])) {
                    $accountFields['CustomFields'][$customFieldKey][$key] = $this->localizer->formatDate(
                        new \DateTime($accountFields['CustomFields'][$customFieldKey][$key]['date']),
                        'long',
                        $locale
                    );
                }
            }
        }

        $status = ['first' => '', 'second' => ''];

        if ($isVaccineCard) {
            if (!empty($vaccineCard['secondDoseVaccine'])) {
                $status['first'] = $vaccineCard['secondDoseVaccine'];

                if (!empty($vaccineCard['secondDoseDate'])) {
                    $status['first'] .= ' (' . $vaccineCard['secondDoseDate'] . ')';
                }
            } else {
                $status['first'] = $vaccineCard['firstDoseVaccine'] . ' (' . $vaccineCard['firstDoseDate'] . ')';
            }
            $vaccineCard['status'] = $status;
        } elseif ($isInsuranceCard) {
            $status['first'] = $insuranceCard['memberNumber'];

            if (!empty($insuranceCard['groupNumber'])) {
                $status['first'] .= ' (' . $insuranceCard['groupNumber'] . ')';
            }
            $insuranceCard['status'] = $status;
        } elseif ($isVisa) {
            if (!empty($visa['visaNumber'])) {
                $status['first'] = $visa['visaNumber'];
            }
            $visa['status'] = $status;
        } elseif ($isDriverLicense) {
            $status['first'] = $driverLicense['licenseNumber'];
            $driverLicense['status'] = $status;
        } elseif ($isPriorityPass) {
            $status['first'] = $priorityPass['accountNumber'];
            $priorityPass['status'] = $status;
        }

        return $accountFields;
    }

    protected function mapAccount(MapperContext $mapperContext, $accountID, $accountFields)
    {
        /** @var Usr $user */
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);
        // Fixing types
        $accountFields['ID'] = (int) $accountFields['ID'];
        $accountFields['FID'] = 'a' . $accountFields['ID'];
        $accountFields['Kind'] = (int) $accountFields['Kind'];
        $accountFields['SavePassword'] = (int) $accountFields['SavePassword'];
        $accountFields['isCustom'] = empty($accountFields['ProviderID']);

        if ($accountFields['UserID']) {
            $accountFields['UserID'] = (int) $accountFields['UserID'];
        }

        if ($accountFields['UserAgentID']) {
            $accountFields['UserAgentID'] = (int) $accountFields['UserAgentID'];
        }

        if (!empty($accountFields['CanSavePassword'])) {
            $accountFields['CanSavePassword'] = (int) $accountFields['CanSavePassword'];
        }

        // Account Owner
        if (isset($accountFields['ShareUserAgentID']) && !empty($accountFields['ShareUserAgentID'])) {
            $accountFields['AccountOwner'] = (int) $accountFields['ShareUserAgentID'];
            $accountFields['IsShareable'] = false;
        } elseif (isset($accountFields['UserAgentID']) && !empty($accountFields['UserAgentID'])) {
            $accountFields['AccountOwner'] = (int) $accountFields['UserAgentID'];
            $accountFields['IsShareable'] = true;
        } else {
            $accountFields['AccountOwner'] = 'my';
            $accountFields['IsShareable'] = true;
        }

        // Access
        if (isset($mapperContext->rights['account'])) {
            foreach (array_keys($mapperContext->rights['account']) as $rightName) {
                $accountFields['Access'][$rightName] = $mapperContext->isGranted($rightName, $accountFields['ID'], 'account');
            }
        }

        // Shares
        if (isset($mapperContext->shares['account']) && isset($mapperContext->shares['account'][$accountFields['ID']])) {
            $accountFields['Shares'] = $mapperContext->shares['account'][$accountFields['ID']];
        }

        // SuccessCheckDate
        if (isset($accountFields['SuccessCheckDate'])) {
            $accountFields['SuccessCheckDateTs'] = strtotime($accountFields['SuccessCheckDate']);
            $accountFields['SuccessCheckDateYMD'] = date('c', $accountFields['SuccessCheckDateTs']);
            $accountFields['SuccessCheckDate'] =
                $this->localizer->formatDateTime(
                    $accountFields['SuccessCheckDateTime'] = $this->localizer->correctDateTime(new \DateTime('@' . $accountFields['SuccessCheckDateTs'])),
                    'full',
                    'short',
                    $locale
                );
        }

        // Update date
        if (isset($accountFields['RawUpdateDate'])) {
            $accountFields['UpdateDateTs'] = strtotime($accountFields['RawUpdateDate']);
            $accountFields['UpdateDate'] =
                $this->localizer->formatDateTime(
                    $accountFields['UpdateDateTime'] = $this->localizer->correctDateTime(new \DateTime('@' . $accountFields['UpdateDateTs'])),
                    'full',
                    'short',
                    $locale
                );
        }

        // EmailDate (if exist)
        if (isset($accountFields['EmailDate'])) {
            $accountFields['EmailDateTs'] = strtotime($accountFields['EmailDate']);
            $accountFields['EmailDate'] =
                $this->localizer->formatDateTime(
                    $accountFields['EmailDateTime'] = $this->localizer->correctDateTime(new \DateTime('@' . $accountFields['EmailDateTs'])),
                    'short',
                    'short',
                    $locale
                );
        }

        // Coupon? (Groupon, Livinsocial..)
        $isCoupon = $this->isCouponAccount($accountFields);
        // Last Change
        $changeRaw = null;

        if (isset($accountFields['Balance']) && isset($accountFields['LastBalance'])) {
            $changeRaw = $accountFields['Balance'] - $accountFields['LastBalance'];

            if (abs($changeRaw) > 0.001) {
                $change = $this->localizer->formatNumber(abs($changeRaw), 2, $locale);

                if ('' != ($trimmed = trim($accountFields['BalanceFormat'])) && 'function' != $trimmed) {
                    $change = preg_replace(['/\%0?\.2f/ims', '/\%d/ims'], $change, $accountFields['BalanceFormat']);
                }

                if ($changeRaw > 0) {
                    $change = "+" . $change;
                    $accountFields['ChangedPositive'] = true;
                } else {
                    $change = "-" . $change;
                    $accountFields['ChangedPositive'] = false;
                }
                $accountFields['LastChange'] = $change;
                $accountFields['LastChangeRaw'] = (float) $changeRaw;
            }
        }

        // State bar (leftmost indicator in the list of accounts): error or change balance
        $stateBar = null;

        if (
            !empty($accountFields["ProviderID"])
            && ($accountFields["State"] == ACCOUNT_DISABLED || $accountFields["ErrorCode"] != ACCOUNT_CHECKED)
            && (
                $accountFields["CanCheck"] == 1
                || (isset($accountFields['ForceErrorDisplay']) && $accountFields['ForceErrorDisplay'])
            )
        ) {
            $stateBar = 'error';

            if ($accountFields['ErrorCode'] == ACCOUNT_PROVIDER_ERROR && in_array($accountFields['ErrorMessage'], $this->internalErrors)) {
                $accountFields['ErrorCode'] = ACCOUNT_ENGINE_ERROR;
                $accountFields['ErrorMessage'] = $this->translator->trans('updater2.messages.fail.updater', [], 'messages', $locale);
            }
        }

        // Last change in period (Options::OPTION_CHANGE_PERIOD)
        if (!empty($accountFields['LastChangeDate'])) {
            $lc = strtotime($accountFields['LastChangeDate']);
            $accountFields['LastChangeDateTs'] = $lc;
            $currentTs = strtotime($mapperContext->options->get(Options::OPTION_CHANGE_PERIOD));

            if (empty($stateBar)) {
                $dayAgoTs = strtotime('-1 day');

                if ($lc > $dayAgoTs && isset($changeRaw) && abs($changeRaw) > 0.001) {
                    if ($changeRaw > 0) {
                        $stateBar = 'inc';
                    } else {
                        $stateBar = 'dec';
                    }
                }
            }

            if ($lc > $currentTs && isset($changeRaw) && abs($changeRaw) > 0.001) {
                if ($changeRaw > 0) {
                    $accountFields['ChangedOverPeriodPositive'] = true;
                } else {
                    $accountFields['ChangedOverPeriodPositive'] = false;
                }
            }
            $accountFields['LastChangeDateTs'] = $lc;
            $accountFields['LastChangeDate'] = $this->localizer->formatDateTime(
                $accountFields['LastChangeDateTime'] = $this->localizer->correctDateTime(new \DateTime('@' . $accountFields['LastChangeDateTs'])),
                'full',
                'short',
                $locale
            );
        }
        $accountFields["StateBar"] = $stateBar;

        // Last Updated, the nearest date from SuccessCheckDate and LastChangeDate
        if (!empty($accountFields['SuccessCheckDateTs']) && !empty($accountFields['LastChangeDateTs'])) {
            $accountFields['LastUpdatedDateTs'] = max($accountFields['SuccessCheckDateTs'], $accountFields['LastChangeDateTs']);
        } elseif (!empty($accountFields['SuccessCheckDateTs'])) {
            $accountFields['LastUpdatedDateTs'] = $accountFields['SuccessCheckDateTs'];
        } elseif (!empty($accountFields['LastChangeDateTs'])) {
            $accountFields['LastUpdatedDateTs'] = $accountFields['LastChangeDateTs'];
        }

        // FullBalance
        $accountFields['BalanceRaw'] = (!empty($accountFields['Balance'])) ? (float) $accountFields['Balance'] : $accountFields['Balance'];

        if ($isCoupon) {
            $currency = null;

            if (isset($accountFields['Properties']['Currency'])) {
                $currency = $accountFields['Properties']['Currency']['Val'];
            }
            $accountFields['Balance'] = $this->localizer->formatCurrency($accountFields["Balance"], $currency, true, $locale);
        } else {
            if (!isset($accountFields['Properties'])) {
                $accountFields['Properties'] = [];
            }

            $accountFields['Balance'] = $this->formatBalance($accountFields, $locale, $accountFields['Properties']);
            $accountFields['LastBalance'] = $this->formatBalance(array_merge($accountFields, ['Balance' => $accountFields['LastBalance']]), $locale, $accountFields['Properties']);

            if (!empty($accountFields['LastChange'])) {
                $change = $this->formatBalance(array_merge($accountFields, ['Balance' => abs($accountFields['LastChangeRaw'])]), $locale, $accountFields['Properties']);
                $sign = $accountFields['ChangedPositive'] ? '+' : '-';
                $accountFields['LastChange'] = sprintf('%s%s', $sign, $change);
            }
        }

        // Custom Display Name
        if ($accountFields['CustomDisplayName'] == '1') {
            $accountFields['DisplayName'] = call_user_func(["TAccountChecker" . ucfirst($accountFields['ProviderCode']), "DisplayName"], $accountFields);
        }

        // Expiration Date
        $accountFields = array_merge($accountFields, $this->expirationResolver->getExpirationInfo(
            $accountFields['ExpirationDate'],
            [
                'Balance' => $accountFields['BalanceRaw'] ?? null,
                'TableName' => $accountFields['TableName'],
                'hasCoupons' => $isCoupon,
                'isCustom' => empty($accountFields['ProviderID']),
                'isSubaccount' => false,
                'isCoupon' => false,
                'CanCheckExpiration' => $accountFields['CanCheckExpiration'],
                'ExpirationAlwaysKnown' => $accountFields['ExpirationAlwaysKnown'],
                'DontTrackExpiration' => $accountFields['DontTrackExpiration'],
                'ExpirationAutoSet' => $accountFields['ExpirationAutoSet'],
                'ExpirationDateNote' => $accountFields['ExpirationDateNote'],
                'Currency' => $accountFields['Currency'],
                'hasError' => $accountFields['ErrorCode'] != ACCOUNT_CHECKED,
                'ExpirationWarning' => [
                    'DisplayName' => $accountFields['DisplayName'],
                    'ProviderCode' => $accountFields['ProviderCode'],
                    'ExpirationDateNote' => $accountFields['ExpirationDateNote'],
                    'ExpirationUnknownNote' => $accountFields['ExpirationUnknownNote'],
                    'ExpirationWarning' => $accountFields['ExpirationWarning'],
                    'Properties' => $accountFields['Properties'] ?? null,
                ],
                'locale' => $locale,
            ]
        ));

        // Average update time
        $accountFields['LastDurationWithoutPlans'] = isset($accountFields['LastDurationWithoutPlans']) ? (float) $accountFields['LastDurationWithoutPlans'] : 9;
        $accountFields['LastDurationWithPlans'] = isset($accountFields['LastDurationWithPlans']) ? (float) $accountFields['LastDurationWithPlans'] : 20;

        $accountFields['ProviderManualUpdate'] = (bool) $accountFields['ProviderManualUpdate'];

        if ($accountFields['UserID'] != $user->getUserid()) {
            switch ($accountFields["AccessLevel"]) {
                case ACCESS_READ_NUMBER:
                    $accountFields['Balance'] = "";
                    $accountFields['Goal'] = null;

                    break;
            }
        }

        // Goal progress
        if (!empty($accountFields['Goal'])) {
            // round changed to floor, refs #11349
            $accountFields['GoalProgress'] = floor(min(intval($accountFields['BalanceRaw']), $accountFields['Goal']) / $accountFields['Goal'] * 100);
        }

        // Active account
        $accountFields['IsActive'] = (
            floatval($accountFields['TotalBalance']) > 0
            && $accountFields['IsActiveTab'] != ACTIVE_ACCOUNT_REMOVE
            && !$accountFields['Disabled']
        ) || $accountFields['IsActiveTab'] == ACTIVE_ACCOUNT_ADD;

        // Archived account
        $accountFields['IsArchived'] = (bool) $accountFields['IsArchived'];

        if (empty($accountFields['PwnedTimes'])) {
            unset($accountFields['PwnedTimes']);
        }

        if ($accountFields['isCustom']) {
            if (empty($accountFields['AccountStatus']) && !empty($accountFields['CustomEliteLevel'])) {
                $accountFields['AccountStatus'] = $accountFields['CustomEliteLevel'];
            }
        }

        return $accountFields;
    }

    protected function mapCoupon(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);

        // Fixing types
        $accountFields['ID'] = (int) $accountFields['ID'];
        $accountFields['FID'] = 'c' . $accountFields['ID'];
        $accountFields['Kind'] = (int) $accountFields['Kind'];

        if ($accountFields['UserID']) {
            $accountFields['UserID'] = (int) $accountFields['UserID'];
        }

        if ($accountFields['UserAgentID']) {
            $accountFields['UserAgentID'] = (int) $accountFields['UserAgentID'];
        }

        // Coupon Owner
        if (isset($accountFields['ShareUserAgentID']) && !empty($accountFields['ShareUserAgentID'])) {
            $accountFields['AccountOwner'] = (int) $accountFields['ShareUserAgentID'];
            $accountFields['IsShareable'] = false;
        } elseif (isset($accountFields['UserAgentID']) && !empty($accountFields['UserAgentID'])) {
            $accountFields['AccountOwner'] = (int) $accountFields['UserAgentID'];
            $accountFields['IsShareable'] = true;
        } else {
            $accountFields['AccountOwner'] = 'my';
            $accountFields['IsShareable'] = true;
        }

        // Access
        if (isset($mapperContext->rights['coupon'])) {
            foreach (array_keys($mapperContext->rights['coupon']) as $rightName) {
                $accountFields['Access'][$rightName] = $mapperContext->isGranted($rightName, $accountFields['ID'], 'coupon');
            }
        }

        // Shares
        if (isset($mapperContext->shares['coupon']) && isset($mapperContext->shares['coupon'][$accountFields['ID']])) {
            $accountFields['Shares'] = $mapperContext->shares['coupon'][$accountFields['ID']];
        }

        // Expiration Date
        $accountFields = array_merge($accountFields, $this->expirationResolver->getExpirationInfo(
            $accountFields['ExpirationDate'],
            [
                'Balance' => $accountFields['Value'] ?? null,
                'TableName' => $accountFields['TableName'],
                'isCustom' => false,
                'isSubaccount' => false,
                'isCoupon' => false,
                'CanCheckExpiration' => $accountFields['CanCheckExpiration'],
                'ExpirationAlwaysKnown' => $accountFields['ExpirationAlwaysKnown'],
                'DontTrackExpiration' => $accountFields['DontTrackExpiration'],
                'ExpirationAutoSet' => $accountFields['ExpirationAutoSet'],
                'ExpirationDateNote' => $accountFields['ExpirationDateNote'],
                'Currency' => $accountFields['Currency'],
                'hasError' => false,
                'locale' => $locale,
                'isPassport' => PROVIDER_KIND_DOCUMENT === (int) $accountFields['Kind']
                    && is_array($accountFields['CustomFields'])
                    && array_key_exists(Providercoupon::FIELD_KEY_PASSPORT, $accountFields['CustomFields']),
            ]
        ));

        $accountFields['AccountStatus'] = !empty($accountFields['TypeName'])
            ? $accountFields['TypeName']
            : (array_key_exists($accountFields['TypeID'], Providercoupon::TYPES) ? Providercoupon::TYPES[$accountFields['TypeID']] : '');
        $accountFields['LoginFieldFirst'] = $accountFields['CardNumber'];
        $accountFields['LoginFieldLast'] = $accountFields['Description'];
        $accountFields['isCustom'] = true;
        $accountFields['Access'] = array_merge($accountFields['Access'], [
            'read_extproperties' => true,
            'autologin' => false,
            'autologinExtension' => false,
            'update' => false,
        ]);

        // Archived coupon
        $accountFields['IsArchived'] = (bool) $accountFields['IsArchived'];

        if (!empty($country = $accountFields['CustomFields']['passport']['country'] ?? null)) {
            $accountFields['CustomFields']['passport']['countryId'] = $country;
            $accountFields['CustomFields']['passport']['country'] = $this->getLocalizedCountry($country);
        }

        if (!empty($accountFields['Balance'])) {
            $accountFields['BalanceRaw'] = filterBalance($accountFields['Balance'] ?? null, true);
            $accountFields['Balance'] = $this->formatBalance($accountFields, $locale);
        }

        return $accountFields;
    }

    protected function mapSubAccounts(MapperContext $mapperContext, $accountID, $accountFields)
    {
        /** @var Usr $user */
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);

        if (!isset($accountFields["SubAccountsArray"]) || !is_array($accountFields["SubAccountsArray"])) {
            return $accountFields;
        }

        $isCouponAccount = $this->isCouponAccount($accountFields);
        $mapperContext->hasBalanceInTotalSumProperty[$accountFields['ID']] = [];

        foreach ($accountFields['SubAccountsArray'] as $k => &$subAccount) {
            if (!array_key_exists('DisplayName', $subAccount)) {
                continue;
            }
            $isCoupon = (isset($subAccount['isCoupon']) && $subAccount['isCoupon']);

            // Display name
            $subAccount['DisplayName'] = html_entity_decode($subAccount['DisplayName']);
            // Last Change
            $changeRaw = null;

            if (isset($subAccount['LastBalance']) && isset($subAccount['Balance']) && !$isCoupon) {
                $subAccount['LastBalanceRaw'] = $subAccount['LastBalance'];
                $changeRaw = $subAccount['Balance'] - $subAccount['LastBalance'];
                $change = $this->accountRep->formatFullBalance(abs($changeRaw), $accountFields['ProviderCode'], $accountFields['BalanceFormat'], false);

                if ($changeRaw > 0) {
                    $change = "+" . $change;
                    $subAccount['ChangedPositive'] = true;
                } else {
                    $change = "-" . $change;
                    $subAccount['ChangedPositive'] = false;
                }
                $subAccount['LastChange'] = $change;
                $subAccount['LastChangeRaw'] = (float) $changeRaw;
            }

            // State bar (leftmost indicator in the list of accounts): error or change balance
            $stateBar = (isset($accountFields["StateBar"]) && $accountFields["StateBar"] == 'error') ? $accountFields["StateBar"] : null;

            // Last change in period (Options::OPTION_CHANGE_PERIOD)
            if (!empty($subAccount['LastChangeDate'])) {
                $lc = strtotime($subAccount['LastChangeDate']);
                $currentTs = strtotime($mapperContext->options->get(Options::OPTION_CHANGE_PERIOD));

                if (empty($stateBar)) {
                    $dayAgoTs = strtotime('-1 day');

                    if ($lc > $dayAgoTs && isset($changeRaw) && abs($changeRaw) > 0.001) {
                        if ($changeRaw > 0) {
                            $stateBar = 'inc';
                        } else {
                            $stateBar = 'dec';
                        }
                    }
                }

                if ($lc > $currentTs && isset($changeRaw) && abs($changeRaw) > 0.001) {
                    if ($changeRaw > 0) {
                        $subAccount['ChangedOverPeriodPositive'] = true;
                    } else {
                        $subAccount['ChangedOverPeriodPositive'] = false;
                    }
                }
                $subAccount['LastChangeDateTs'] = $lc;
                $subAccount['LastChangeDate'] = $this->localizer->formatDateTime(
                    $subAccount['LastChangeDateTime'] = $this->localizer->correctDateTime(new \DateTime('@' . $subAccount['LastChangeDateTs'])),
                    'full',
                    'short',
                    $locale
                );
            }
            $subAccount["StateBar"] = $stateBar;

            if (isset($subAccount['Balance'])) {
                $subAccount['BalanceRaw'] = (float) $subAccount['Balance'];
            }

            if (isset($subAccount['Properties'])) {
                $tmpBalance = $accountFields['Balance'];

                foreach (['Balance', 'LastBalance'] as $fieldToFormat) {
                    $accountFields['Balance'] = $subAccount[$fieldToFormat];
                    $subAccount[$fieldToFormat] = $this->formatBalance($accountFields, $locale, array_merge($subAccount['Properties'], [
                        'SubAccountCode' => [
                            'AccountID' => $accountFields['ID'],
                            'SubAccountID' => $subAccount['SubAccountID'],
                            'Name' => 'SubAccountCode',
                            'Code' => 'SubAccountCode',
                            'Val' => $subAccount['SubAccountCode'],
                            'Kind' => null,
                            'Visible' => 0,
                        ],
                    ]));
                }
                $accountFields['Balance'] = $tmpBalance;

                foreach ($subAccount['Properties'] as $i => $v) {
                    if (in_array($i, ['Certificates'])) {
                        continue;
                    }

                    $subAccount['Properties'][$i]['Val'] = $this->hideCreditCard($v['Code'], $v['Val'], $accountFields['Kind']);
                    $subAccount['Properties'][$i]['Val'] = $this->propertyFormatter->format(
                        $subAccount['Properties'][$i]['Val'],
                        $subAccount['Properties'][$i]['Type'] ?? null,
                        $locale
                    );
                }
            } else {
                foreach (['Balance', 'LastBalance'] as $fieldToFormat) {
                    $subAccount[$fieldToFormat] = $this->formatBalance(array_merge($accountFields, ['Balance' => $subAccount[$fieldToFormat]]), $locale, [
                        'SubAccountCode' => [
                            'AccountID' => $accountFields['ID'],
                            'SubAccountID' => $subAccount['SubAccountID'],
                            'Name' => 'SubAccountCode',
                            'Code' => 'SubAccountCode',
                            'Val' => $subAccount['SubAccountCode'],
                            'Kind' => null,
                            'Visible' => 0,
                        ],
                    ]);
                }
            }

            // Expiration Date
            if ($isCoupon) {
                if ($subAccount['ExpirationAutoSet'] == EXPIRATION_UNKNOWN) {
                    $subAccount['ExpirationAutoSet'] = EXPIRATION_AUTO;
                }
            }
            $subAccount = array_merge($subAccount, $this->expirationResolver->getExpirationInfo(
                $subAccount['ExpirationDate'],
                [
                    'Balance' => $subAccount['BalanceRaw'] ?? null,
                    'TableName' => null,
                    'isCustom' => false,
                    'isSubaccount' => true,
                    'isCoupon' => $isCoupon,
                    'CanCheckExpiration' => $accountFields['CanCheckExpiration'],
                    'ExpirationAlwaysKnown' => $accountFields['ExpirationAlwaysKnown'],
                    'DontTrackExpiration' => $accountFields['DontTrackExpiration'],
                    'ExpirationAutoSet' => $subAccount['ExpirationAutoSet'],
                    'ExpirationDateNote' => $accountFields['ExpirationDateNote'],
                    'Currency' => null,
                    'hasError' => $accountFields["ErrorCode"] != ACCOUNT_CHECKED,
                    'locale' => $locale,
                ]
            ));

            if (
                isset($subAccount['Balance'])
                && $subAccount['Balance'] == $this->translator->trans('na', [], 'messages', $locale)
                && !(
                    isset($subAccount['Properties']['AllowNAInBalance'])
                    && $subAccount['Properties']['AllowNAInBalance']
                )
            ) {
                $subAccount['Balance'] = '';
            }

            if (
                isset($subAccount['Properties']['BalanceInTotalSum'])
                && (
                    (int) $subAccount['Properties']['BalanceInTotalSum']['Val'] === 1
                    || $subAccount['Properties']['BalanceInTotalSum']['Val'] === 'true'
                )
                && isset($subAccount['BalanceRaw'])
            ) {
                $mapperContext->hasBalanceInTotalSumProperty[$accountFields['ID']][] = $subAccount['SubAccountID'];
            }

            if (!$isCoupon) {
                continue;
            }

            if (isset($subAccount['Properties'])) {
                $cert = $subAccount['Properties']['Certificates']['Val'] = @unserialize($subAccount['Properties']['Certificates']['Val']);

                if ($cert !== false) {
                    $subAccount['Properties']['Quantity']['Val'] = 0;

                    foreach ($cert as $idCoupon => $singleCoupon) {
                        if ($singleCoupon['Used'] == false && time() < $singleCoupon['ExpiresAt']) {
                            $subAccount['Properties']['Quantity']['Val']++;
                        }
                    }
                } else {
                    unset($accountFields['SubAccountsArray'][$k]);

                    continue;
                }
                unset($cert);

                if ($subAccount['Properties']['Quantity']['Val'] == 0 && $mapperContext->options->get(Options::OPTION_ONLY_ACTIVE_DEALS)) {
                    unset($accountFields['SubAccountsArray'][$k]);

                    continue;
                }

                // Balance
                $currency = null;

                if (isset($subAccount['Properties']['Currency'])) {
                    $currency = $subAccount['Properties']['Currency']['Val'];
                }
                $subAccount['Balance'] = $this->localizer->formatCurrency(
                    $subAccount['Balance'],
                    $currency,
                    true,
                    $locale
                ) . " &times; " . $subAccount['Properties']['Quantity']['Val'];

                if (isset($subAccount['Properties']['Price']['Val'])) {
                    $subAccount['Properties']['Price']['Val'] =
                        $this->localizer->formatCurrency($subAccount['Properties']['Price']['Val'], $currency, true, $locale);
                }

                if (isset($subAccount['Properties']['Value']['Val'])) {
                    $subAccount['Properties']['Value']['Val'] =
                        $this->localizer->formatCurrency($subAccount['Properties']['Value']['Val'], $currency, true, $locale);
                }

                if (isset($subAccount['Properties']['Save']['Val'])) {
                    $subAccount['Properties']['Save']['Val'] .= "%";
                }

                if (isset($subAccount['Balance'])) {
                    $subAccount['Balance'] = '';
                }
            }
        }

        // Sort deals
        if ($isCouponAccount && sizeof($accountFields['SubAccountsArray'])) {
            uasort(
                $accountFields['SubAccountsArray'],
                function ($a, $b) use ($accountFields) {
                    if (!isset($a['ExpirationDateTs']) || !isset($b['ExpirationDateTs'])) {
                        if ($accountFields['SavePassword'] == SAVE_PASSWORD_DATABASE) {
                            DieTrace('Daily Deals error. Coupon expiration date not found.', false);
                        }

                        return 0;
                    }

                    if ($a['Properties']['Quantity']['Val'] == $b['Properties']['Quantity']['Val']) {
                        if ($a['ExpirationDateTs'] == $b['ExpirationDateTs']) {
                            return 0;
                        }

                        return ($a['ExpirationDateTs'] < $b['ExpirationDateTs']) ? 1 : -1;
                    }

                    return ($a['Properties']['Quantity']['Val'] < $b['Properties']['Quantity']['Val']) ? 1 : -1;
                }
            );
        }

        return $accountFields;
    }

    protected function mapProperties(MapperContext $mapperContext, $accountID, $accountFields)
    {
        /** @var Usr $user */
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);
        $isDocument = PROVIDER_KIND_DOCUMENT === $accountFields['Kind'];

        if (isset($accountFields['MainProperties']['Number'])) {
            $accountFields['MainProperties']['Number']['Number'] = $this->hideCreditCard($accountFields['MainProperties']['Number']['Field'], $accountFields['MainProperties']['Number']['Number'], $accountFields['Kind']);
        }

        if (isset($accountFields['MainProperties']['Status']['Field']) && isset($accountFields['Properties'][$accountFields['MainProperties']['Status']['Field']])) {
            unset($accountFields['Properties'][$accountFields['MainProperties']['Status']['Field']]);
        }

        if (isset($accountFields['Properties'])) {
            foreach ($accountFields['Properties'] as &$property) {
                if (!$property['Visible']) {
                    continue;
                }
                $property['Val'] = $this->hideCreditCard($property['Code'], $property['Val'], $accountFields['Kind']);
                $property['Val'] = $this->propertyFormatter->format($property['Val'], $property['Type'] ?? null, $locale);
            }
        }

        // Elitism, rank
        if (isset($accountFields['MainProperties']['Status']['Status'])) {
            $status = $accountFields['MainProperties']['Status']['Status'];

            if ($accountFields['CheckInBrowser'] != CHECK_IN_CLIENT) {
                $eliteLevelFields = $this->elRep->getEliteLevelFields($accountFields['ProviderID'], $status);
            }
        }

        if (isset($accountFields['ProviderID'])) {
            $eliteLevels = $this->elRep->getEliteLevelFields($accountFields['ProviderID']);
            $currentRank = isset($eliteLevelFields, $status) ? $eliteLevelFields['Rank'] : -1;
            $levels = [];

            foreach ($eliteLevels as $level) {
                $level['Rank'] = intval($level['Rank']);

                if ($level['Rank'] == 0) {
                    continue;
                }

                if (!isset($levels[$level['Rank']])) {
                    if ($currentRank == $level['Rank']) {
                        $levels[$level['Rank']] = $status;
                    } elseif ($level['ByDefault'] == "1") {
                        $levels[$level['Rank']] = $level['Name'];
                    }
                }
            }
            ksort($levels);
            $accountFields['EliteLevelsCount'] = count($levels);
            $accountFields['EliteStatuses'] = array_values($levels);
        }

        if (isset($eliteLevelFields)) {
            $eliteLevelsCount = intval($accountFields['EliteLevelsCount']);
            $accountFields['Elitism'] = ($eliteLevelsCount == 0) ? 0 : round($eliteLevelFields['Rank'] / $eliteLevelsCount, 2);
            $accountFields['Elitism'] *= 100;
            $accountFields['Rank'] = $eliteLevelFields['Rank'];

            if ($accountFields['Elitism'] > 100) {
                $accountFields['Elitism'] = 100;
            }
        }

        if (!isset($accountFields['Properties']) || !is_array($accountFields['Properties'])) {
            $accountFields['Properties'] = [];
        }

        // Comment
        if (!empty($accountFields['comment'])) {
            $accountFields['Properties']['AccountComment'] = [
                'Name' => $this->translator->trans('account.label.comment', [], 'messages', $locale),
                'Code' => 'AccountComment',
                'Val' => StringHandler::strLimit($accountFields['comment'], 35),
                'Visible' => "1",
            ];
        }
        // Queue Date
        $value = null;

        $userMailboxCount = $this->userMailboxCounter->myOrFamilyMember($accountFields['UserID'], $accountFields['UserAgentID']);

        if ($accountFields['ProviderState'] == PROVIDER_CHECKING_OFF || $accountFields['ProviderState'] == PROVIDER_CHECKING_EXTENSION_ONLY
            || $accountFields['AccountLevel'] == ACCOUNT_LEVEL_FREE
            || ($accountFields['ProviderState'] == PROVIDER_CHECKING_WITH_MAILBOX && $userMailboxCount === 0)
            || $accountFields['BackgroundCheck'] == "0"
            || !$accountFields['CanCheck']) {
            $value = '<a data-role="tooltip" title="' . $this->translator->trans(/** @Desc("Unfortunately it is not feasible to turn background updating on for this account. Please update this account by pressing the 'update' button.") */ 'turned-off-tooltip', [], 'messages', $locale) . '">' . $this->translator->trans('account.background-updating-off', [], 'messages', $locale) . '</a>';
        } elseif (!empty($accountFields['Disabled'])) {
            $value = $this->translator->trans('account.background-updating-disabled', [], 'messages', $locale);
        } else {
            if ($accountFields['SavePassword'] == SAVE_PASSWORD_LOCALLY && $accountFields['ProviderCode'] != 'aa') {
                $value = $this->translator->trans('account.background-updating-never', [], 'messages', $locale);
            } else {
                if (isset($accountFields['QueueDate']) && !empty($accountFields['QueueDate'])) {
                    $ts = strtotime($accountFields['QueueDate']);

                    // future only
                    if ($ts - time() <= 0) {
                        $ts = time() + 60 * 60 - 1;
                    }
                    $value = $this->intervalFormatter->longFormatViaDateTimes(
                        $this->clock->current()->getAsDateTime(),
                        new \DateTime('@' . $ts),
                        true,
                        true,
                        $locale
                    );
                }
            }
        }

        if ($value && (
            in_array($accountFields['ProviderState'], [PROVIDER_ENABLED, PROVIDER_WSDL_ONLY])
                || ACCOUNT_LEVEL_AWPLUS === (int) $accountFields['AccountLevel']
                || (PROVIDER_CHECKING_WITH_MAILBOX === (int) $accountFields['ProviderState'] && $userMailboxCount > 0)
        )) {
            $accountFields['Properties']['NextAccountUpdate'] = [
                'Name' => $this->translator->trans('account.next-update', [], 'messages', $locale),
                'Code' => 'NextAccountUpdate',
                'Val' => $value,
                'Visible' => "1",
            ];
        }

        if ($isDocument) {
            $accountFields = $this->formatDocumentProperties($accountFields, $locale);
        }

        return $accountFields;
    }

    /**
     * filter properties, balances.
     */
    protected function filter(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $user = $mapperContext->options->get(Options::OPTION_USER);

        if ($accountFields['TableName'] == 'Account') {
            if (!isset($accountFields['Access']['read_balance']) || !$accountFields['Access']['read_balance']) {
                $accountFields = array_merge($accountFields, ExpirationDateResolver::getDefaultFields());

                if (isset($accountFields["SubAccountsArray"]) && is_array($accountFields["SubAccountsArray"])) {
                    foreach ($accountFields['SubAccountsArray'] as $k => &$subAccount) {
                        $subAccount = array_merge($subAccount, ExpirationDateResolver::getDefaultFields());
                    }
                }
                unset(
                    $accountFields['Balance'],
                    $accountFields['BalanceRaw'],
                    $accountFields['LastBalance'],
                    $accountFields['LastChange'],
                    $accountFields['LastChangeDate'],
                    $accountFields['LastChangeDateTs'],
                    $accountFields['LastChangeRaw'],
                    $accountFields['SubAccountsArray'],
                    $accountFields['Complex'],
                    $accountFields['TotalBalance'],
                    $accountFields['TotalBalanceChange'],
                    $accountFields['USDCash'],
                    $accountFields['TotalUSDCash'],
                    $accountFields['TotalUSDCashRaw'],
                    $accountFields['TotalUSDCashChange'],
                    $accountFields['MileValue'],
                    $accountFields['OriginalMileValue'],
                );

                if (isset($accountFields["SubAccountsArray"]) && is_array($accountFields["SubAccountsArray"])) {
                    foreach ($accountFields['SubAccountsArray'] as $k => &$subAccount) {
                        unset(
                            $subAccount['Balance'],
                            $subAccount['BalanceRaw'],
                            $subAccount['LastBalance'],
                            $subAccount['LastChange'],
                            $subAccount['LastChangeDate'],
                            $subAccount['LastChangeDateTs'],
                            $subAccount['LastChangeRaw']
                        );
                    }
                }
            }

            if (!isset($accountFields['Access']['read_number']) || !$accountFields['Access']['read_number']) {
                unset(
                    $accountFields['MainProperties']['Number']
                );
                $accountFields['Login'] = '';
            }

            if (!isset($accountFields['Access']['read_extproperties']) || !$accountFields['Access']['read_extproperties']) {
                unset(
                    $accountFields['SuccessCheckDateTs'],
                    $accountFields['SuccessCheckDate'],
                    $accountFields['LastUpdatedDateTs'],
                    $accountFields['Disabled'],
                    $accountFields['StateBar']
                );
            }
        }

        if ($accountFields['TableName'] == 'Coupon') {
            if (!isset($accountFields['Access']['read_value']) || !$accountFields['Access']['read_value']) {
                $accountFields['Value'] = '';
                $accountFields['CardNumber'] = '';
                $accountFields['Balance'] = '';
                $accountFields['LoginFieldFirst'] = '';
            }

            if (!isset($accountFields['Access']['read_expiration']) || !$accountFields['Access']['read_expiration']) {
                $accountFields['ExpirationDate'] = null;
                $accountFields['ExpirationDateTs'] = ExpirationDateResolver::EXPIRE_EMPTY_TS;
                unset($accountFields['ExpirationState']);
                unset($accountFields['ExpirationKnown']);
                unset($accountFields['ExpirationMode']);
            }
        }

        return $accountFields;
    }

    protected function formatBalance($accountFields, ?string $locale, $properties = [])
    {
        // Fix properties
        $oldPropertiesView = [];

        foreach ($properties as $v) {
            $oldPropertiesView[$v['Code']] = $v['Val'];
        }

        if ($accountFields['TableName'] === 'Coupon') {
            return $this->balanceFormatter->formatCustomFields(
                $accountFields,
                false,
                $this->translator->trans('na', [], 'messages', $locale),
                $locale
            );
        }

        return $this->balanceFormatter->formatFields(
            array_merge($accountFields, ['ManualCurrencySign' => '']),
            $oldPropertiesView,
            false,
            $this->translator->trans('na', [], 'messages', $locale),
            $locale
        );
    }

    protected function getLocalizedCountry($countryId): ?string
    {
        if (empty($this->localizedCountries)) {
            $this->localizedCountries = $this->localizer->getLocalizedCountries();
        }

        if (array_key_exists($countryId, $this->localizedCountries)) {
            return $this->localizedCountries[$countryId];
        }

        return null;
    }

    protected function getState($stateId): ?string
    {
        if (empty($stateId)) {
            return null;
        }

        $state = $this->em->getRepository(State::class)->find($stateId);

        if (!empty($state)) {
            return $state;
        }

        return null;
    }

    protected function getCreditCard(int $creditCardId): ?string
    {
        if (empty($creditCardId)) {
            return null;
        }

        $creditCard = $this->em->getRepository(CreditCard::class)->find($creditCardId);

        if (!empty($creditCard)) {
            return $creditCard->getCardFullName() ?? $creditCard->getName();
        }

        return null;
    }

    protected function isPassport(array $accountFields, ?int $countryId = null): bool
    {
        $isPassport = $accountFields['isCustom']
            && is_array($accountFields['CustomFields'])
            && array_key_exists(Providercoupon::FIELD_KEY_PASSPORT, $accountFields['CustomFields']);

        if (!$isPassport || null === $countryId) {
            return $isPassport;
        }

        return $countryId === (int) ($accountFields['CustomFields'][Providercoupon::FIELD_KEY_PASSPORT]['countryId'] ?? 0);
    }

    private function getCashRaw($cardId, $usdCash, $subAccountBalance, $totalBalance): ?float
    {
        $costMileValueCard = empty($cardId)
            ? null
            : $this->mileValueCards->getCardMileValueCost($cardId);

        if (null !== $costMileValueCard && null !== $costMileValueCard->getPrimaryValue()) {
            $cashRaw = ($costMileValueCard->getPrimaryValue() * $subAccountBalance);

            if ($costMileValueCard->getCobrandProviderId() || !$costMileValueCard->isCashBackOnly()) {
                $cashRaw /= 100;
            }
        } elseif (null === $usdCash) {
            return 0;
        } else {
            $cashRaw = ($usdCash * $subAccountBalance) / $totalBalance;
        }

        return $cashRaw;
    }

    private function formatTotals(MapperContext $mapperContext, $accountFields): array
    {
        /** @var Usr $user */
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);

        if ($accountFields['TableName'] === 'Coupon') {
            if (!is_numeric($accountFields['Value'])) {
                $accountFields['TotalBalance'] = (float) 0;

                return $accountFields;
            }

            $isAccountCurrencyUSD = in_array($accountFields['ManualCurrencyCode'], AccountTotalCalculator::USD_CURRENCY);

            if ($isAccountCurrencyUSD) {
                $accountFields['USDCashRaw'] = (float) $accountFields['Value'];
                $accountFields['USDCash'] = $this->localizer->formatCurrency($accountFields['USDCashRaw'], 'USD', true, $locale);
                $accountFields['TotalUSDCash'] = (float) $accountFields['Value'];
            } else {
                $accountFields['TotalBalance'] = (float) $accountFields['Value'];
            }
        } else {
            $accountFields['TotalBalance'] = (float) ($accountFields['TotalBalance'] ?? 0);
            $isSumInTotalBalance = isset($mapperContext->hasBalanceInTotalSumProperty[$accountFields['ID']])
                && count($mapperContext->hasBalanceInTotalSumProperty[$accountFields['ID']]) > 0;
            $isAccountCurrencyUSD =
                (
                    isset($accountFields['Properties']['Currency'])
                    && in_array($accountFields['Properties']['Currency']['Val'], AccountTotalCalculator::USD_CURRENCY)
                ) || (
                    isset($accountFields['BalanceFormat'])
                    && 0 === strpos($accountFields['BalanceFormat'], '$%')
                ) || (
                    empty($accountFields['ProviderID'])
                    && in_array($accountFields['ManualCurrencyCode'], AccountTotalCalculator::USD_CURRENCY)
                );
            $isHaveMileValue = !is_null($approxValue = $accountFields['OriginalMileValue']['approximate']['raw'] ?? null);

            if (empty($accountFields['ProviderID']) && !$isAccountCurrencyUSD && is_numeric($accountFields['RawBalance'])) {
                $accountFields['TotalBalance'] = (float) $accountFields['RawBalance'];
            }

            if ($isHaveMileValue) {
                $accountFields['USDCashMileValue'] = true;
                $accountFields['USDCashRaw'] = (float) $approxValue;
                $accountFields['USDCash'] = $accountFields['OriginalMileValue']['approximate']['value'];
                $accountFields['TotalUSDCash'] = (float) $approxValue;

                if (!is_null($accountFields['OriginalMileValue']['balanceChange']['changedPositive'] ?? null) && isset($accountFields['ChangedOverPeriodPositive'])) {
                    if ($accountFields['OriginalMileValue']['balanceChange']['changedPositive']) {
                        $accountFields['TotalUSDCashChange'] = (float) ($accountFields['TotalUSDCashChange'] ?? 0) + abs($accountFields['OriginalMileValue']['balanceChange']['raw']);
                    } else {
                        $accountFields['TotalUSDCashChange'] = (float) ($accountFields['TotalUSDCashChange'] ?? 0) - abs($accountFields['OriginalMileValue']['balanceChange']['raw']);
                    }
                }
            } elseif (is_numeric($accountFields['RawBalance']) && $isAccountCurrencyUSD) {
                $accountFields['USDCashRaw'] = (float) $accountFields['RawBalance'];
                $accountFields['USDCash'] = $this->localizer->formatCurrency($accountFields['USDCashRaw'], 'USD', true, $locale);
                $accountFields['TotalUSDCash'] = (float) $accountFields['RawBalance'];

                if (isset($accountFields['ChangedOverPeriodPositive'])) {
                    if ($accountFields['ChangedOverPeriodPositive']) {
                        $accountFields['TotalUSDCashChange'] = (float) ($accountFields['TotalUSDCashChange'] ?? 0) + abs($accountFields['LastChangeRaw']);
                    } else {
                        $accountFields['TotalUSDCashChange'] = (float) ($accountFields['TotalUSDCashChange'] ?? 0) - abs($accountFields['LastChangeRaw']);
                    }
                }
            }
        }

        if (!$isAccountCurrencyUSD && isset($accountFields['ChangedOverPeriodPositive'])) {
            if ($accountFields['ChangedOverPeriodPositive']) {
                $accountFields['TotalBalanceChange'] = (float) ($accountFields['TotalBalanceChange'] ?? 0) + abs($accountFields['LastChangeRaw']);
            } else {
                $accountFields['TotalBalanceChange'] = (float) ($accountFields['TotalBalanceChange'] ?? 0) - abs($accountFields['LastChangeRaw']);
            }
        }

        if (isset($accountFields['SubAccountsArray']) && is_array($accountFields['SubAccountsArray'])) {
            if ($isSumInTotalBalance && count($mapperContext->hasBalanceInTotalSumProperty[$accountFields['ID']]) > 1) {
                $accountFields['Complex'] = true;
            }

            $isSubAccountCurrencyUSD = static fn (array $subAccount): bool => (
                isset($subAccount['Properties']['Currency'])
                && in_array($subAccount['Properties']['Currency']['Val'], AccountTotalCalculator::USD_CURRENCY)
            ) || (
                isset($accountFields['BalanceFormat'])
                && 0 === strpos($accountFields['BalanceFormat'], '$%')
            );
            $isDependentSubAccount = static fn (array $subAccount): bool => $isSumInTotalBalance && in_array($subAccount['SubAccountID'], $mapperContext->hasBalanceInTotalSumProperty[$accountFields['ID']]);
            $isUnhideable = static fn (array $subAccount): bool => isset($accountFields['Complex'])
                && $accountFields['Complex'] === true
                && $isDependentSubAccount($subAccount);
            $isMileValueSubAccount = static fn (array $subAccount): bool => !$isSubAccountCurrencyUSD($subAccount)
                && $isDependentSubAccount($subAccount) && isset($subAccount['BalanceRaw']) && is_numeric($subAccount['BalanceRaw']);
            $sameMileValue = $isHaveMileValue
                && $accountFields['USDCashRaw'] > 0
                && it($accountFields['SubAccountsArray'])
                ->filter($isMileValueSubAccount)
                ->reduce(static fn (float $result, array $subAccount) => round($result + $subAccount['BalanceRaw'], 2), 0) == $accountFields['RawBalance'];
            $cost = $isHaveMileValue ? $this->calculateCost($accountFields) : [];
            $isAmexProvider = Provider::AMEX_ID === (int) $accountFields['ProviderID'] || 'amex' === $accountFields['Code'];

            $totalBalanceByCashEq = 0;
            $skipCalculateTotalsByCashEq = [];

            if ($isSumInTotalBalance
                && $isHaveMileValue
                && Provider::CITI_ID === (int) $accountFields['ProviderID']
                && ($accountRawBalance = (float) $accountFields['RawBalance']) > 0 && count($accountFields['SubAccountsArray']) > 1
            ) {
                foreach ($accountFields['SubAccountsArray'] as $subAccount) {
                    if (!isset($subAccount['Properties']['BalanceInTotalSum'])) {
                        continue;
                    }

                    if (array_key_exists('BalanceRaw', $subAccount) && $accountRawBalance === (float) $subAccount['BalanceRaw']) {
                        $skipCalculateTotalsByCashEq[$subAccount['SubAccountID']] = true;
                    }
                }

                if (count($skipCalculateTotalsByCashEq) < 2) {
                    $skipCalculateTotalsByCashEq = [];
                } else {
                    $totalBalanceByCashEq = $this->mileValueService->calculateCashEquivalent(
                        $this->mileValueService->getProviderValue(
                            $accountFields['ProviderID'],
                            MileValueService::PRIMARY_CALC_FIELD,
                            false
                        ),
                        $accountRawBalance
                    )['raw'];
                }
            }

            foreach ($accountFields['SubAccountsArray'] as &$subAccount) {
                if ($isUnhideable($subAccount)) {
                    $subAccount['Unhideable'] = true;
                }

                if ($isSumInTotalBalance) {
                    $costMileValueCard = empty($subAccount['CreditCardID'])
                        ? null
                        : $this->mileValueCards->getCardMileValueCost($subAccount['CreditCardID']);

                    if (($sameMileValue && $isMileValueSubAccount($subAccount))
                        || (
                            null !== $costMileValueCard
                            && isset($subAccount['BalanceRaw'])
                            && null !== $costMileValueCard->getPrimaryValue()
                        )
                    ) {
                        $subAccount['USDCashMileValue'] = true;
                        $cashRaw = $this->getCashRaw($subAccount['CreditCardID'], $accountFields['USDCashRaw'] ?? null, $subAccount['BalanceRaw'], $accountFields['RawBalance']);
                        $subAccount['USDCashRaw'] = round($cashRaw, $cashRaw >= 10 ? 0 : 2);
                        $subAccount['USDCash'] = $this->localizer->formatCurrency($subAccount['USDCashRaw'], 'USD', true, $locale);

                        if (isset($subAccount['ChangedOverPeriodPositive'], $subAccount['LastChangeRaw'])) {
                            $cashRaw = $this->getCashRaw($subAccount['CreditCardID'], $accountFields['USDCashRaw'] ?? null, $subAccount['LastChangeRaw'], $accountFields['RawBalance']);
                            $cashRaw = round($cashRaw, $cashRaw >= 10 ? 0 : 2);
                            $subAccount['USDCashChange'] = sprintf(
                                '%s%s',
                                $subAccount['ChangedOverPeriodPositive'] ? '+' : '-',
                                $this->localizer->formatCurrency($cashRaw, 'USD', true, $locale)
                            );
                        }
                    } elseif ($isSubAccountCurrencyUSD($subAccount)
                        && isset($subAccount['BalanceRaw'])
                        && is_numeric($subAccount['BalanceRaw'])
                        && !(
                            ($isAmexProvider && isset($subAccount['Properties']['AmountToSpend']))
                            || (isset($subAccount['Properties']['IsSpentSum']) && true === filter_var($subAccount['Properties']['IsSpentSum']['Val'], FILTER_VALIDATE_BOOLEAN))
                        )
                    ) {
                        $subAccount['USDCashRaw'] = (float) $subAccount['BalanceRaw'];
                        $subAccount['USDCash'] = $this->localizer->formatCurrency($subAccount['BalanceRaw'], 'USD', true, $locale);

                        if (!$isDependentSubAccount($subAccount) && $subAccount['IsHidden'] == '0') {
                            $accountFields['TotalUSDCash'] = (float) ($accountFields['TotalUSDCash'] ?? 0) + $subAccount['USDCashRaw'];

                            if (isset($subAccount['ChangedOverPeriodPositive'])) {
                                if ($subAccount['ChangedOverPeriodPositive']) {
                                    $accountFields['TotalUSDCashChange'] = (float) ($accountFields['TotalUSDCashChange'] ?? 0) + abs($subAccount['LastChangeRaw']);
                                } else {
                                    $accountFields['TotalUSDCashChange'] = (float) ($accountFields['TotalUSDCashChange'] ?? 0) - abs($subAccount['LastChangeRaw']);
                                }
                            }
                        }
                    }

                    if (!empty($subAccount['USDCashRaw']) && !array_key_exists($subAccount['SubAccountID'], $skipCalculateTotalsByCashEq)) {
                        $totalBalanceByCashEq += $subAccount['USDCashRaw'];
                    }
                } elseif (
                    $isSubAccountCurrencyUSD($subAccount) && isset($subAccount['BalanceRaw']) && is_numeric($subAccount['BalanceRaw']) && !(
                        ($accountFields['Code'] === 'amex' && isset($subAccount['Properties']['AmountToSpend']))
                        || (isset($subAccount['Properties']['IsSpentSum']) && true === filter_var($subAccount['Properties']['IsSpentSum']['Val'], FILTER_VALIDATE_BOOLEAN))
                    )
                ) {
                    $subAccount['USDCashRaw'] = (float) $subAccount['BalanceRaw'];
                    $subAccount['USDCash'] = $this->localizer->formatCurrency($subAccount['BalanceRaw'], 'USD', true, $locale);

                    if ($subAccount['IsHidden'] == '0') {
                        $accountFields['TotalUSDCash'] = (float) ($accountFields['TotalUSDCash'] ?? 0) + $subAccount['USDCashRaw'];

                        if (isset($subAccount['ChangedOverPeriodPositive'])) {
                            if ($subAccount['ChangedOverPeriodPositive']) {
                                $accountFields['TotalUSDCashChange'] = (float) ($accountFields['TotalUSDCashChange'] ?? 0) + abs($subAccount['LastChangeRaw']);
                            } else {
                                $accountFields['TotalUSDCashChange'] = (float) ($accountFields['TotalUSDCashChange'] ?? 0) - abs($subAccount['LastChangeRaw']);
                            }
                        }
                    }
                } elseif (
                    !empty($subAccount['CreditCardID'])
                    && isset($subAccount['BalanceRaw'])
                    && ($costMileValueCard = $this->mileValueCards->getCardMileValueCost($subAccount['CreditCardID']))
                    && null !== $costMileValueCard->getPrimaryValue()
                ) {
                    $costMileValueCard = null;
                    $subAccount['USDCashMileValue'] = true;
                    $cashRaw = $this->getCashRaw($subAccount['CreditCardID'], $accountFields['USDCashRaw'] ?? null, $subAccount['BalanceRaw'], $accountFields['RawBalance']);
                    $subAccount['USDCashRaw'] = round($cashRaw, $cashRaw >= 10 ? 0 : 2);
                    $subAccount['USDCash'] = $this->localizer->formatCurrency($subAccount['USDCashRaw'], 'USD', true, $locale);
                }

                if (// $isAmexProvider &&
                    !empty($cost['subAccountCost'][$subAccount['SubAccountID']])) {
                    $subAccount['USDCash'] = $this->localizer->formatCurrency($cost['subAccountCost'][$subAccount['SubAccountID']], 'USD', true, $locale);
                    $subAccount['USDCashRaw'] = $cost['subAccountCost'][$subAccount['SubAccountID']];
                }

                if (empty($subAccount['USDCashRaw'])
                    && isset($subAccount['Properties']['Currency']['Val'])
                    && '$' === $subAccount['Properties']['Currency']['Val']
                    && !empty($subAccount['Balance'])
                    && !empty($subAccount['BalanceRaw'])
                ) {
                    $subAccount['USDCash'] = $subAccount['Balance'];
                    $subAccount['USDCashRaw'] = $subAccount['BalanceRaw'];
                }
            }

            if (count($skipCalculateTotalsByCashEq)) {
                unset($accountFields['MileValue']['balanceChange']);
            }

            $isReplaceTotalSum = array_key_exists('Complex', $accountFields) && true === $accountFields['Complex'];

            if (!$isReplaceTotalSum) {
                $subAccountsWithCards = array_column($accountFields['fullSubAccountsArray'] ?? $accountFields['SubAccountsArray'], 'CreditCardID');
                $subAccountsWithBalanceInTotalSum = array_column(array_column($accountFields['fullSubAccountsArray'] ?? $accountFields['SubAccountsArray'], 'Properties'), 'BalanceInTotalSum');
                $subAccountsWithCards = array_filter($subAccountsWithCards);

                if (count($subAccountsWithCards) === count($subAccountsWithBalanceInTotalSum)) {
                    $isReplaceTotalSum = true;
                }
            }

            if (// $isAmexProvider &&
                isset($cost['isBalanceInTotalSum'])
                && true === $cost['isBalanceInTotalSum']
                && !empty($cost['costSumSubAccountsProvider'])
            ) {
                $isReplaceTotalSum = true;
                $totalBalanceByCashEq = $cost['costSumSubAccountsProvider'];
            }

            if ($totalBalanceByCashEq && $isReplaceTotalSum) {
                if ($totalBalanceByCashEq > 10) {
                    $totalBalanceByCashEq = round($totalBalanceByCashEq);
                }

                $accountFields['USDCashRaw'] =
                $accountFields['TotalUSDCashRaw'] =
                $accountFields['MileValue']['approximate']['raw'] = $totalBalanceByCashEq;
                $accountFields['USDCash'] =
                $accountFields['TotalUSDCash'] =
                $accountFields['MileValue']['approximate']['value'] = $this->localizer->formatCurrency($totalBalanceByCashEq, 'USD', true, $locale);
            }
        }

        if (isset($accountFields['TotalUSDCash'])) {
            $totalBalanceByCashEq = empty($totalBalanceByCashEq) ? null : $totalBalanceByCashEq;
            $accountFields['TotalUSDCashRaw'] = (float) ($accountFields['TotalUSDCashRaw'] ?? $totalBalanceByCashEq ?? $accountFields['TotalUSDCash']);
            $accountFields['TotalUSDCash'] = $this->localizer->formatCurrency($accountFields['TotalUSDCashRaw'], 'USD', true, $locale);
        }

        if ($accountFields['isCustom'] && empty($accountFields['MileValue']) && !empty($accountFields['ProgramName']) && AmericanAirlinesAAdvantageDetector::isMatchByName($accountFields['ProgramName'])) {
            $accountFields['MileValue'] = ['isDeniedSet' => true];
        }

        return $accountFields;
    }

    private function calculateCost(array $accountFields): array
    {
        $subAccountsArray = $accountFields['fullSubAccountsArray'] ?? $accountFields['SubAccountsArray'] ?? [];
        $subAccsWithBalanceIn = array_column(array_column($subAccountsArray, 'Properties'), 'BalanceInTotalSum');
        $result = [
            'isBalanceInTotalSum' => count($subAccsWithBalanceIn) > 0,
            'providerCostValue' => null,
            'costAccountProvider' => 0,
            'costSumSubAccountsProvider' => 0,
            'sumBalanceInTotal' => 0,
            'sumSubAccountsByCard' => 0,
            'subAccountCost' => [],
        ];

        if (!empty($accountFields['ProviderID'])) {
            if (empty($accountFields['OriginalMileValue']['isSimulated'])
                && null !== ($providerMileValueItem = $this->mileValueService->getProviderMileValueItem($accountFields['ProviderID']))
            ) {
                $result['providerCostValue'] = $providerMileValueItem->getPrimaryValue(MileValueService::PRIMARY_CALC_FIELD);
            } else {
                $result['providerCostValue'] = $accountFields['OriginalMileValue']['awEstimate']['raw'];
            }
        }

        // хак для расчета тотал у аккаунта ситибанк, когда есть субакк с балансом но без cardId, и есть субакк с cardId аналогичный в detectedCards но без баланса, а карта зарабатывает кэшбек
        if (Provider::CITI_ID === (int) $accountFields['ProviderID']
            && $result['isBalanceInTotalSum']
            && ($isMultipleNamesInOne = false !== strpos(
                implode(' ', array_column($subAccountsArray, 'DisplayName')),
                CreditCardMatcher::DETECT_STOP[Provider::CITI_ID][0]
            ))
            && (1 === count($subAccsWithBalanceIn) && empty($accountFields['SubAccountsArray'][$subAccsWithBalanceIn[0]['SubAccountID']]['CreditCardID']))
            && 1 === count($detectedCashBack = array_column($accountFields['MainProperties']['DetectedCards']['DetectedCards'], 'IsCashBackOnly'))
            && true === $detectedCashBack[0]
            && ($detectedCardId = array_column($accountFields['MainProperties']['DetectedCards']['DetectedCards'], 'CreditCardID')[0]) > 0
        ) {
            $mvCost = $this->mileValueCards->getCardMileValueCost((int) $detectedCardId);
            $result['isCostReplacedCard'] = true;
            $result['providerCostValue'] = $mvCost->getPrimaryValue();

            if (CreditCard::CASHBACK_TYPE_POINT === $mvCost->getCashBackType()) {
                $result['providerCostValue'] *= 100;
            }
        }

        $isAccountUsdCurrency = Currency::USD_ID === (int) $accountFields['Currency'];

        foreach ($subAccountsArray as $subAccount) {
            $subAccountBalance = (float) ($subAccount['BalanceRaw'] ?? $subAccount['Balance'] ?? 0);

            if (empty($subAccountBalance)) {
                continue;
            }

            $subAccountId = $subAccount['SubAccountID'];
            $isBalanceInTotalSum = isset($subAccount['Properties']['BalanceInTotalSum']);
            $isUsdCurrency = (isset($subAccount['Properties']['Currency']) && in_array($subAccount['Properties']['Currency']['Val'], AccountTotalCalculator::USD_CURRENCY))
                || (!isset($subAccount['Properties']['Currency']) && $isAccountUsdCurrency);
            $isCreditCardCost = false;

            if (!empty($subAccount['CreditCardID']) && !$isUsdCurrency) {
                $mvCost = $this->mileValueCards->getCardMileValueCost($subAccount['CreditCardID']);

                if (null !== $mvCost->getPrimaryValue()) {
                    $costValue = $mvCost->getPrimaryValue();

                    if (CreditCard::CASHBACK_TYPE_POINT === $mvCost->getCashBackType()) {
                        $costValue *= 100;
                    }
                    $cost = $this->mileValueService->calculateCashEquivalent($costValue, $subAccountBalance);

                    $result['sumSubAccountsByCard'] += $cost['raw'];
                    $result['subAccountCost'][$subAccountId] = $cost['raw'];
                    $isCreditCardCost = true;
                }
            }

            if ($isUsdCurrency
                && (
                    !$result['isBalanceInTotalSum']
                    || $isBalanceInTotalSum
                )
                && (
                    !empty($subAccount['CreditCardID'])
                    || (isset($subAccount['Properties']) && 1 === count($subAccount['Properties']))
                )
            ) {
                $result['subAccountCost'][$subAccountId] = $subAccountBalance;
            }

            if ($isBalanceInTotalSum
                && !$isCreditCardCost
                && !$isUsdCurrency
            ) {
                $result['sumBalanceInTotal'] += $subAccountBalance;
                $result['subAccountCost'][$subAccountId] = $this->mileValueService->calculateCashEquivalent(
                    $result['providerCostValue'],
                    $subAccountBalance
                )['raw'];
            }
        }

        if (!$result['providerCostValue']) {
            return $result;
        }

        $rawBalance = (float) ($accountFields['RawBalance'] ?? 0);

        if (!empty($isMultipleNamesInOne) && $rawBalance > 0 && $result['sumBalanceInTotal'] === $rawBalance) {
            $result['costSumSubAccountsProvider'] = $this->mileValueService->calculateCashEquivalent(
                $result['providerCostValue'],
                $rawBalance
            )['raw'];
        } elseif ($result['isBalanceInTotalSum']) {
            $result['costSumSubAccountsProvider'] = array_sum($result['subAccountCost']);
        } elseif (!empty($rawBalance)) {
            $result['costAccountProvider'] = $this->mileValueService->calculateCashEquivalent(
                $result['providerCostValue'],
                $rawBalance
            )['raw'];
        }

        return $result;
    }

    private function hideCreditCard($code, $value, $kindProvider)
    {
        if (is_numeric($value)
            && $kindProvider == PROVIDER_KIND_CREDITCARD
            && in_array($code, ['Account', 'Login'])
            && preg_match("/^\d{12}(\d{4})$/ims", $value, $match)) {
            return "xxxx xxxx xxxx " . $match[1];
        }

        return $value;
    }

    private function isCouponAccount($accountFields)
    {
        if (!isset($accountFields['SubAccountsArray']) || !is_array($accountFields['SubAccountsArray'])) {
            return false;
        }
        $isCoupon = false;

        foreach ($accountFields['SubAccountsArray'] as $subAccount) {
            if (isset($subAccount['isCoupon']) && $subAccount['isCoupon']) {
                $isCoupon = true;

                break;
            }
        }

        return $isCoupon;
    }
}
