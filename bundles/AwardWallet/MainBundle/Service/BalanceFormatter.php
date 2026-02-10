<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Psr\Log\LoggerInterface;

class BalanceFormatter
{
    private AccountRepository $accountRep;

    private LocalizeService $localizer;

    private LoggerInterface $logger;

    public function __construct(
        AccountRepository $accountRep,
        LocalizeService $localizer,
        LoggerInterface $logger
    ) {
        $this->accountRep = $accountRep;
        $this->localizer = $localizer;
        $this->logger = $logger;
    }

    /**
     * @param mixed $customValue , false - take a balance from  property, null or empty string - n/a
     * @param string|null $locale
     */
    public function formatAccount(Account $account, $customValue = false, $naValue = null, $locale = null)
    {
        $accountFields = $this->getAccountFields($account);
        $accountProps = $this->getAccountProperties($account);

        return $this->formatFields($accountFields, $accountProps, $customValue, $naValue, $locale);
    }

    /**
     * @param mixed $customValue , false - take a balance from  property, null or empty string - n/a
     * @param string|null $locale
     */
    public function formatSubAccount(Subaccount $subaccount, $customValue = false, $naValue = null, $locale = null)
    {
        $accountFields = $this->getAccountFields($subaccount->getAccountid());
        $accountFields['Balance'] = $subaccount->getBalance();
        $accountProps = $this->getAccountProperties($subaccount->getAccountid(), $subaccount);
        $accountProps['SubAccountCode'] = $subaccount->getCode();

        return $this->formatFields($accountFields, $accountProps, $customValue, $naValue, $locale);
    }

    /**
     * @param mixed $customValue , false - take a balance from  property, null or empty string - n/a
     * @param string|null $locale
     */
    public function formatFields(
        array $accountFields,
        array $properties,
        $customValue = false,
        $naValue = null,
        $locale = null
    ) {
        $isCustom = empty($accountFields['ProviderID']);
        $balanceFormat = !$isCustom ? trim($accountFields['BalanceFormat']) : null;
        $balance = $customValue === false ? $accountFields['Balance'] : filterBalance($customValue, true);

        if (
            !$isCustom
            && ($accountFields["CanCheck"] == 0 || $accountFields["CanCheckBalance"] == 0)
            && empty($balanceFormat)
        ) {
            return $naValue;
        } elseif ($balanceFormat === 'function') {
            return $this->formatViaChecker(
                $accountFields['ProviderCode'],
                array_merge($accountFields, ['Balance' => $balance]),
                $properties,
                $naValue
            );
        } elseif ($isCustom) {
            return $this->formatCustomFields($accountFields, $balance, $naValue, $locale);
        }

        return $this->formatNumber(
            $balance,
            $accountFields["AllowFloat"] == 1,
            $balanceFormat,
            $naValue,
            $locale
        );
    }

    public function formatCustomFields(
        array $accountFields,
        $customValue = false,
        $naValue = null,
        $locale = null
    ) {
        if ($customValue === false) {
            $balanceRaw = $accountFields['Balance'];
            $balance = $accountFields['Balance'];
        } else {
            $balanceRaw = $customValue;
            $balance = filterBalance($customValue, true);
        }

        if (!empty($accountFields['ManualCurrencyCode'])) {
            $balance = filterBalance($balance, 1 === (int) ($accountFields['AllowFloat'] ?? 1));

            return trim($this->localizer->formatCurrency($balance, $accountFields['ManualCurrencyCode'], true, $locale));
        } elseif (!empty($accountFields['ManualCurrencySign'])) {
            $after = true;
            $allowFloat = $accountFields["AllowFloat"] == 1;

            if (($fmt = numfmt_create($locale, \NumberFormatter::CURRENCY)) === false) {
                $fmt = numfmt_create('en_US', \NumberFormatter::CURRENCY);
            }

            $pattern = numfmt_get_pattern($fmt);

            if (mb_stripos($pattern, '¤') === 0) {
                $after = false;
            }

            if (in_array($accountFields['ManualCurrencySign'], ['points', 'miles'])) {
                $after = true;
                $allowFloat = false;
            }

            $balanceFormatted = $this->formatNumber(
                $balance,
                $allowFloat,
                null,
                null,
                $locale
            );

            return ($after) ? $balanceFormatted . ' ' . $accountFields['ManualCurrencySign'] : $accountFields['ManualCurrencySign'] . $balanceFormatted;
        }

        if ($accountFields['TableName'] === 'Coupon' && !is_numeric($balanceRaw)) {
            return $balanceRaw;
        }

        return $this->formatNumber(
            $balance,
            $accountFields['AllowFloat'] == 1,
            null,
            $naValue,
            $locale
        );
    }

    /**
     * @param bool $allowFloat
     * @param string|null $valueFormat
     * @param string|null $locale
     * @param int $fraction
     */
    public function formatNumber($value, $allowFloat = true, $valueFormat = null, $naValue = null, $locale = null, $fraction = 2)
    {
        if (is_null($value) || $value === '') {
            return $naValue;
        }

        if (!$allowFloat) {
            $value = intval($value);
        }
        $valueFormat = trim($valueFormat);

        if (!empty($valueFormat) && $valueFormat != 'function') {
            $value = preg_replace_callback("/\%(?:(?:0?\.(?<fraction>\d+)f)|d)/ims", function ($matches) use ($value, $locale, $valueFormat, $allowFloat) {
                if (isset($matches['fraction'])) {
                    if (!$allowFloat) {
                        $this->logger->critical('format balance error: AllowFloat = false and BalanceFormat contains %f', [
                            'value' => $value,
                            'format' => $valueFormat,
                        ]);
                    }
                    $preparedFraction = intval($matches['fraction']);
                    $preparedValue = $value;
                } else {
                    $preparedFraction = null;
                    $preparedValue = intval($value);
                }

                return $this->localizer->formatNumber($preparedValue, $preparedFraction, $locale);
            }, $valueFormat);
        } else {
            $value = $this->localizer->formatNumber($value, $fraction, $locale);
        }

        return html_entity_decode($value);
    }

    private function formatViaChecker(string $providerCode, array $accountFields, array $accountProps, $naValue = null)
    {
        $value = call_user_func(['TAccountChecker' . ucfirst($providerCode), "FormatBalance"], $accountFields, $accountProps);

        if (is_null($value) || $value === '') {
            return $naValue;
        }

        return html_entity_decode($value);
    }

    private function getAccountFields(Account $account)
    {
        $accountRows = $this->accountRep->accountsToArrayFields([$account]);

        if (sizeof($accountRows) === 0) {
            throw new \InvalidArgumentException('Invalid account');
        }

        return $accountRows[0];
    }

    private function getAccountProperties(Account $account, ?Subaccount $subaccount = null)
    {
        $properties = $account->getProperties();

        if (!$properties || !$properties->count()) {
            return [];
        }

        $accountProps = [];

        foreach ($properties->filter(function (Accountproperty $p) use ($subaccount) {
            return is_null($p->getSubaccountid())
                || (
                    !is_null($subaccount)
                    && $subaccount->getSubaccountid() === $p->getSubaccountid()->getSubaccountid()
                );
        }) as $property) {
            /** @var Accountproperty $property */
            $accountProps[$property->getProviderpropertyid()->getCode()] = $property->getVal();
        }

        return $accountProps;
    }
}
