<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Entity\ProviderMileValue as ProviderMileValueEntity;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;

class ProviderMileValue extends \TBaseSchema
{
    public const VALUE_FIELDS = [
        'AvgPointValue',
        'RegionalEconomyMileValue',
        'GlobalEconomyMileValue',
        'RegionalBusinessMileValue',
        'GlobalBusinessMileValue',
    ];

    /**
     * @var array
     */
    private static $historyValues;

    /**
     * @var array
     */
    private static $currentValues;
    /**
     * @var CacheManager
     */
    private $cacheManager;
    /**
     * @var MileValueService
     */
    private $mileValueService;

    public function __construct(CacheManager $cacheManager, MileValueService $mileValueService)
    {
        parent::__construct();

        $this->cacheManager = $cacheManager;
        $this->mileValueService = $mileValueService;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();
        unset($result['CertifiedByUserID']);

        return $result;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        foreach ($list->Fields as &$field) {
            $field['Caption'] = preg_replace('# ((Point|Mile) Value)#ims', '<br/>$1', $field['Caption']);
        }
        unset($field);

        $list->ShowExport = false;
        $list->ShowImport = false;

        // $list->OnDelete = [$this, "clearMileValueCache"];
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        $result['ProviderID']['InputAttributes'] = " onchange=\"this.form.DisableFormScriptChecks.value = '1'; this.form.submit();\"";

        return $result;
    }

    public function CreateForm()
    {
        $form = parent::CreateForm();

        $form->OnLoaded = function () use ($form) {
            $providerId = (int) $form->Fields['ProviderID']['Value'];

            if ($providerId > 0) {
                foreach ($this->getCurrentValues($providerId) as $field => $calculatedValue) {
                    $form->Fields[$field]['InputAttributes'] = ' placeholder="' . $calculatedValue['value'] . '"';
                }
            }
        };

        // $form->OnSave = [$this, "clearMileValueCache"];

        return $form;
    }

    /**
     * @internal
     */
    public function clearMileValueCache()
    {
        $this->cacheManager->invalidateTags([Tags::TAG_MILE_VALUE]);
        $this->cacheManager->invalidateTags([Tags::TAG_MILE_VALUE], false);
    }

    public function getHistoryValues(int $providerMileValueId): array
    {
        if (self::$historyValues === null) {
            self::$historyValues =
                $this->mileValueService->fetchCombinedMileValueData(array_keys(ProviderMileValueEntity::STATUSES), [], true)
                + $this->mileValueService->fetchCombinedHotelValueData(array_keys(ProviderMileValueEntity::STATUSES), [], true);
        }

        return $this->extractFromCache(self::$historyValues, $providerMileValueId);
    }

    public function getCurrentValues(int $providerId): array
    {
        if (self::$currentValues === null) {
            self::$currentValues =
                $this->mileValueService->fetchCombinedMileValueData(array_keys(ProviderMileValueEntity::STATUSES), [], false)
                + $this->mileValueService->fetchCombinedHotelValueData(array_keys(ProviderMileValueEntity::STATUSES), [], false);
        }

        return $this->extractFromCache(self::$currentValues, $providerId);
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === "ProviderID") {
            return SQLToArray("select ProviderID, DisplayName from Provider where Kind in (" . PROVIDER_KIND_AIRLINE . ', ' . PROVIDER_KIND_HOTEL . ',' . PROVIDER_KIND_CREDITCARD . ") order by DisplayName",
                "ProviderID", "DisplayName");
        }

        if ($field === "Status") {
            return ProviderMileValueEntity::STATUSES;
        }

        if ($field === "CertifiedByUserID") {
            return ['' => ''] + $this->getStaffUsers();
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }

    private function extractFromCache(array $cache, int $key): array
    {
        $result = [];

        foreach (self::VALUE_FIELDS as $field) {
            if (!array_key_exists($key, $cache)) {
                continue;
            }

            $auto = $cache[$key]->getAutoValues();
            $autoValue = $auto ? ($auto[$field] ?? null) : null;

            if ($autoValue !== null) {
                $result[$field] = [
                    'value' => round($autoValue['value'], 2),
                    'title' => ' (Based on ' . $autoValue['count'] . ' bookings)',
                ];
            }
        }

        return $result;
    }

    private function getStaffUsers(): array
    {
        return SQLToArray("
        SELECT u.UserID, CONCAT(u.FirstName, ' ', u.LastName, ' (', u.Login, ')') as uName
        FROM Usr u
        JOIN GroupUserLink gul ON u.UserID = gul.UserID
        JOIN SiteGroup sg ON gul.SiteGroupID = sg.SiteGroupID
        WHERE sg.GroupName = 'staff'
        ORDER BY u.FirstName, u.LastName
        ", 'UserID', 'uName');
    }
}
