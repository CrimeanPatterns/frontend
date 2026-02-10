<?php

namespace AwardWallet\MainBundle\Service\Account;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Класс для работы с динамическими фильтрами, использующимися в списке аккаунтов.
 */
class SearchHintsDynamicFilters
{
    private const CATEGORY_AIRLINES = 'airlines';
    private const CATEGORY_ALLIANCES = 'alliances';
    private const CATEGORY_BALANCES = 'balances';
    private const CATEGORY_DOCUMENTS = 'documents';
    private const CATEGORY_EXPIRES = 'expires';
    private const CATEGORY_FAMILY_MEMBERS_AIRLINES = 'family_members_airlines';
    private const CATEGORY_FAMILY_MEMBERS_HOTELS = 'family_members_hotels';
    private const CATEGORY_FICO = 'fico';
    private const CATEGORY_GIFT_CARDS = 'gift_cards';
    private const CATEGORY_STATUSES = 'statuses';

    /**
     * Максимальное количество динамических фильтров, возвращаемых пользователю.
     */
    private const MAX_FILTERS_COUNT = 10;
    /**
     * Максимальное значение баланса (мили) для аккаунтов авиалиний для фильтра по балансу.
     */
    private const MAX_AIRLINE_BALANCE = 12000;
    /**
     * Максимальное значение баланса (поинты) для аккаунтов отелей для фильтра по балансу.
     */
    private const MAX_HOTEL_BALANCE = 10000;

    /**
     * @var array массив со всеми аккаунтами, категориями и владельцами
     */
    private array $data = [];
    /**
     * @var array итоговый массив, содержащий фильтры из всех категорий
     */
    private array $filters = [];

    private Formatter $formatter;
    private ElitelevelRepository $eliteLevelRepository;
    private TranslatorInterface $translator;

    public function __construct(Formatter $formatter, ElitelevelRepository $eliteLevelRepository, TranslatorInterface $translator)
    {
        $this->formatter = $formatter;
        $this->eliteLevelRepository = $eliteLevelRepository;
        $this->translator = $translator;
    }

    public static function getCategoriesArray(): array
    {
        return [
            self::CATEGORY_AIRLINES,
            self::CATEGORY_ALLIANCES,
            self::CATEGORY_BALANCES,
            self::CATEGORY_DOCUMENTS,
            self::CATEGORY_EXPIRES,
            self::CATEGORY_FAMILY_MEMBERS_AIRLINES,
            self::CATEGORY_FAMILY_MEMBERS_HOTELS,
            self::CATEGORY_FICO,
            self::CATEGORY_GIFT_CARDS,
            self::CATEGORY_STATUSES,
        ];
    }

    /**
     * Запустить генерацию динамических фильтров для текущего пользователя.
     *
     * @param array $data массив со всеми аккаунтами, категориями и владельцами
     */
    public function run(array $data): array
    {
        $this->data = $data;

        $this->addAirlines();
        $this->addAlliances();
        $this->addBalances();
        $this->addDocuments();
        $this->addExpiresAccounts();
        $this->addFamilyMembersAndAirlines();
        $this->addFamilyMembersAndHotels();
        $this->addFicoScores();
        $this->addGiftCards();
        $this->addStatuses();

        $result = [];
        $maxCount = 0;

        foreach (self::getCategoriesArray() as $value) {
            if (count($this->filters[$value]) > $maxCount) {
                $maxCount = count($this->filters[$value]);
            }
        }

        for ($i = 0; $i <= $maxCount; $i++) {
            foreach (self::getCategoriesArray() as $value) {
                if (isset($this->filters[$value][$i])) {
                    $result[] = $this->filters[$value][$i];
                }

                if (count($result) === self::MAX_FILTERS_COUNT) {
                    break 2;
                }
            }
        }

        return $result;
    }

    /**
     * Добавляет фильтры по активным авиалиниям. Итоговый массив сортируется по балансу по убыванию.
     *
     * @return void
     */
    private function addAirlines()
    {
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return $account['Kind'] == PROVIDER_KIND_AIRLINE && $account['TotalBalance'] > 0;
        });

        $result = $this->getAirlineAccountsArray($data);

        $this->sortByBalance($result);
        $groupedFilters = [];

        foreach ($result as $account) {
            $groupedFilters[$account['name']][] = $account;
        }

        $this->filters[self::CATEGORY_AIRLINES] = [];

        foreach ($result as $account) {
            if (count($groupedFilters[$account['name']]) === 1) {
                $this->filters[self::CATEGORY_AIRLINES][] = $account['name'];
            } else {
                $this->filters[self::CATEGORY_AIRLINES][] = $account['name'] . ' ' . $account['owner'];
            }
        }
    }

    /**
     * Добавляет фильтр по альянсу авиалиний, в котором имеется аккаунт с самым большим балансом.
     *
     * @return void
     */
    private function addAlliances()
    {
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return $account['Kind'] == PROVIDER_KIND_AIRLINE && $account['TotalBalance'] > 0 && $account['AllianceID'] !== null;
        });

        $result = $this->getAirlineAccountsArray($data);

        $this->sortByBalance($result);
        $this->filters[self::CATEGORY_ALLIANCES] = [];

        if (!empty($result)) {
            $this->filters[self::CATEGORY_ALLIANCES][] = 'alliance:' . reset($result)['alliance'];
        }
    }

    /**
     * Добавляет фильтр по балансу для аккаунтов категорий: авиалинии и отели.
     *
     * @return void
     */
    private function addBalances()
    {
        $result = [];
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return in_array($account['Kind'], [PROVIDER_KIND_AIRLINE, PROVIDER_KIND_HOTEL]) && $account['TotalBalance'] > 0;
        });
        $kindAirline = $this->translator->trans(/** @Ignore */ Provider::getKinds()[PROVIDER_KIND_AIRLINE]);
        $kindHotel = $this->translator->trans(/** @Ignore */ Provider::getKinds()[PROVIDER_KIND_HOTEL]);

        foreach ($data as $account) {
            $name = trim(strtolower($account['ProviderName']));

            if ($account['Kind'] == PROVIDER_KIND_AIRLINE && $account['TotalBalance'] > self::MAX_AIRLINE_BALANCE) {
                $result['airlines'][] = $name . ' ' . $this->findOwner($account);
            }

            if ($account['Kind'] == PROVIDER_KIND_HOTEL && $account['TotalBalance'] > self::MAX_HOTEL_BALANCE) {
                $result['hotels'][] = $name . ' ' . $this->findOwner($account);
            }
        }

        $this->filters[self::CATEGORY_BALANCES] = [];

        foreach ($result as $key => $filters) {
            if (count(array_unique($filters)) > 1) {
                $balance = ($key === 'airlines') ? self::MAX_AIRLINE_BALANCE : self::MAX_HOTEL_BALANCE;
                $kind = ($key === 'airlines') ? $kindAirline : $kindHotel;
                $this->filters[self::CATEGORY_BALANCES][] = 'kind:' . mb_strtolower($kind) . ' and balance > ' . $balance;
            }
        }
    }

    /**
     * Добавляет фильтры по документам пользователя.
     *
     * @return void
     */
    private function addDocuments()
    {
        $result = [];
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return $account['Kind'] == PROVIDER_KIND_DOCUMENT;
        });

        foreach ($data as $account) {
            if ($account['TypeID'] == Providercoupon::TYPE_PASSPORT) {
                $result['passport'][] = 'passport ' . $this->findOwner($account);
            }

            if ($account['TypeID'] == Providercoupon::TYPE_TRUSTED_TRAVELER) {
                $result['trusted traveler'][] = 'trusted traveler ' . $this->findOwner($account);
            }
        }

        $this->filters[self::CATEGORY_DOCUMENTS] = [];

        foreach ($result as $key => $filters) {
            if (count($filters) === 1) {
                $this->filters[self::CATEGORY_DOCUMENTS][] = $key;
            } else {
                $this->filters[self::CATEGORY_DOCUMENTS] = array_merge($this->filters[self::CATEGORY_DOCUMENTS], array_unique($filters));
            }
        }
    }

    /**
     * Добавляет фильтры по дате истечения аккаунта. Итоговый массив сортируется по продолжительности срока действия
     * по возрастанию.
     *
     * @return void
     */
    private function addExpiresAccounts()
    {
        $result = [];
        $today = new \DateTime();
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return $account['ExpirationDate'] !== null;
        });

        foreach ($data as $account) {
            $endDate = \DateTime::createFromFormat('Y-m-d H:i:s', $account['ExpirationDate']);

            if (
                $endDate && $endDate->format('Y-m-d H:i:s') == $account['ExpirationDate']
                && $endDate->getTimestamp() > $today->getTimestamp()
            ) {
                $duration = $this->formatter->formatDuration($today, $endDate, false, true, false, 'en');
                [$value, $unit] = explode(' ', $duration);
                $result[$unit][] = ceil($value) . ' ' . $unit;
            }
        }

        $this->filters[self::CATEGORY_EXPIRES] = [];

        foreach (['month', 'months', 'year', 'years'] as $unit) {
            if (isset($result[$unit])) {
                asort($result[$unit], SORT_NATURAL);
                $filters = array_map(function ($value) {
                    return 'expire in ' . $value;
                }, $result[$unit]);

                $this->filters[self::CATEGORY_EXPIRES] = array_merge($this->filters[self::CATEGORY_EXPIRES], array_unique($filters));
            }
        }
    }

    /**
     * Добавляет составной фильтр по членам семьи и авиалиниям.
     * Сначала группируем все аккаунты по названиям авиакомпаний, после этого считаем суммарный баланс по всем
     * пользователям для каждой из них. Затем берём 2 авиалинии с самым большим балансом и до 3-х владельцев.
     *
     * @return void
     */
    private function addFamilyMembersAndAirlines()
    {
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return $account['Kind'] == PROVIDER_KIND_AIRLINE && $account['TotalBalance'] > 0;
        });

        $result = $this->getAirlineAccountsArray($data);
        $groupedFilters = [];

        foreach ($result as $account) {
            $groupedFilters[$account['name']][] = $account;
        }

        // Считаем суммарный баланс по всем пользователям для каждой авиалинии
        $airlines = $this->getSummaryBalance($result);

        $this->filters[self::CATEGORY_FAMILY_MEMBERS_AIRLINES] = [];

        if (count($airlines) > 1) {
            arsort($airlines);
            $airlines = array_slice(array_keys($airlines), 0, 2);
            $filter = '(' . implode(' or ', $airlines) . ')';

            $owners = [];

            foreach ($airlines as $airline) {
                foreach ($groupedFilters[$airline] as $account) {
                    $owners[] = $account['owner'];
                }
            }

            $owners = array_slice(array_unique($owners), 0, 3);

            if (count($owners) > 1) {
                $kind = $this->translator->trans(/** @Ignore */ Provider::getKinds()[PROVIDER_KIND_AIRLINE]);
                $filter .= ' and (' . implode(' or ', $owners) . ') and (kind:' . mb_strtolower($kind) . ')';
                $this->filters[self::CATEGORY_FAMILY_MEMBERS_AIRLINES][] = $filter;
            }
        }
    }

    /**
     * Добавляет составной фильтр по членам семьи и отелям.
     *
     * @see SearchHintsDynamicFilters::addFamilyMembersAndAirlines()
     * @return void
     */
    private function addFamilyMembersAndHotels()
    {
        $result = [];
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return $account['Kind'] == PROVIDER_KIND_HOTEL && $account['TotalBalance'] > 0;
        });

        foreach ($data as $account) {
            if ($this->findOwner($account) === '') {
                continue;
            }

            $result[] = [
                'name' => trim(strtolower($account['ProviderName'])),
                'balance' => $account['TotalBalance'],
                'owner' => $this->findOwner($account),
            ];
        }

        $groupedFilters = [];

        foreach ($result as $account) {
            $groupedFilters[$account['name']][] = $account;
        }

        // Считаем суммарный баланс по всем пользователям для каждого отеля
        $hotels = $this->getSummaryBalance($result);

        $this->filters[self::CATEGORY_FAMILY_MEMBERS_HOTELS] = [];

        if (count($hotels) > 1) {
            arsort($hotels);
            $hotels = array_slice(array_keys($hotels), 0, 2);
            $filter = '(' . implode(' or ', $hotels) . ')';

            $owners = [];

            foreach ($hotels as $hotel) {
                foreach ($groupedFilters[$hotel] as $account) {
                    $owners[] = $account['owner'];
                }
            }

            $owners = array_slice(array_unique($owners), 0, 3);

            if (count($owners) > 1) {
                $kind = $this->translator->trans(/** @Ignore */ Provider::getKinds()[PROVIDER_KIND_HOTEL]);
                $filter .= ' and (' . implode(' or ', $owners) . ') and (kind:' . mb_strtolower($kind) . ')';
                $this->filters[self::CATEGORY_FAMILY_MEMBERS_HOTELS][] = $filter;
            }
        }
    }

    /**
     * Добавляет фильтры по подаккаунтам FICO Score и Vantage Score, если они имеются у кредитных карт.
     *
     * @return void
     */
    private function addFicoScores()
    {
        $result = [];
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return $account['Kind'] == PROVIDER_KIND_CREDITCARD && isset($account['SubAccountsArray']);
        });

        foreach ($data as $account) {
            foreach ($account['SubAccountsArray'] as $subAccount) {
                if (
                    isset($subAccount['Properties']['FICOScoreUpdatedOn'])
                    && preg_match('/(?<name>fico|vantage).*?score/i', $subAccount['DisplayName'], $matches)
                ) {
                    $result[] = strtolower($matches['name']);
                }
            }
        }

        $this->filters[self::CATEGORY_FICO] = [];

        if (!empty($result)) {
            $result = array_unique($result);
            $this->filters[self::CATEGORY_FICO][] = count($result) === 1 ? $result[0] : implode(' or ', $result);
        }
    }

    /**
     * Добавляет фильтры по Gift cards.
     *
     * @return void
     */
    private function addGiftCards()
    {
        $result = [];
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return $account['TableName'] === 'Coupon' && $account['TypeID'] == Providercoupon::TYPE_GIFT_CARD;
        });

        foreach ($data as $account) {
            if ($this->findOwner($account) === '') {
                continue;
            }

            $result[] = 'gift card ' . $this->findOwner($account);
        }

        $this->filters[self::CATEGORY_GIFT_CARDS] = [];

        if (count($result) === 1) {
            $this->filters[self::CATEGORY_GIFT_CARDS][] = 'gift card';
        } else {
            $this->filters[self::CATEGORY_GIFT_CARDS] = array_unique($result);
        }
    }

    /**
     * Добавляет фильтры по полю "Статус". Предварительный массив сортируется по количеству повторений каждого
     * статуса, после чего они объединяются с помощью "or" (до 3-х статусов).
     *
     * @return void
     */
    private function addStatuses()
    {
        $result = [];
        $data = array_filter($this->data['rawAccounts'], function ($account) {
            return isset($account['MainProperties']['Status']);
        });

        foreach ($data as $account) {
            $eliteLevel = $this->eliteLevelRepository->getEliteLevelFields($account['ProviderID'], $account['MainProperties']['Status']['Status']);

            if (!isset($eliteLevel) || $eliteLevel['Rank'] < 1) {
                continue;
            }

            $name = strtolower($account['MainProperties']['Status']['Status']);

            if (isset($result[$name])) {
                $result[$name]++;
            } else {
                $result[$name] = 1;
            }
        }

        arsort($result);
        $this->filters[self::CATEGORY_STATUSES] = [];

        if (count($result) > 0) {
            $filters = [];

            foreach ($result as $status => $count) {
                $tempArray[] = 'status:' . $status;
                $filters[] = implode(' or ', $tempArray);

                if (count($filters) > 2) {
                    break;
                }
            }

            $this->filters[self::CATEGORY_STATUSES] = array_reverse($filters);
        }
    }

    /**
     * Ищет владельца аккаунта и возвращает его имя.
     */
    private function findOwner(array $account): string
    {
        if ($account['UserID'] == $this->data['user']['ID']) {
            return $this->findOwnerById($account['UserAgentID'] ?? 'my');
        } else {
            $parts = explode(' ', $account['UserName']);

            return strtolower(reset($parts));
        }
    }

    /**
     * Ищет в массиве `agents` владельца по его идентификатору. Если пользователя с таким же именем нет в списке,
     * то возвращается только имя, а если есть, то имя и фамилия.
     *
     * @param int|string $id
     */
    private function findOwnerById($id): string
    {
        $fullName = '';
        $names = [];

        foreach ($this->data['agents'] as $agent) {
            $parts = explode(' ', $agent['name']);
            $firstName = strtolower(reset($parts));

            if (isset($names[$firstName])) {
                $names[$firstName]++;
            } else {
                $names[$firstName] = 1;
            }

            if ($agent['ID'] == $id) {
                $fullName = strtolower($agent['name']);
            }
        }

        if ($fullName !== '') {
            $parts = explode(' ', $fullName);
            $firstName = reset($parts);

            return ($names[$firstName] > 1) ? $fullName : $firstName;
        }

        return $fullName;
    }

    /**
     * Получить список аккаунтов категории "авиалинии".
     *
     * @param array $data массив с raw данными
     * @return array массив, содержащий элементы: название провайдера, баланс, владелец и название альянса
     */
    private function getAirlineAccountsArray(array $data): array
    {
        $result = [];

        foreach ($data as $account) {
            if ($this->findOwner($account) === '') {
                continue;
            }

            $name = preg_replace('/\sair(lines|ways)*/i', '', $account['ProviderName']);
            $result[] = [
                'name' => trim(strtolower($name)),
                'balance' => $account['TotalBalance'],
                'owner' => $this->findOwner($account),
                'alliance' => $account['AllianceAlias'],
            ];
        }

        return $result;
    }

    /**
     * Считает суммарный баланс по всем пользователям для каждого провайдера (авиалинии, отели).
     *
     * @param array $data массив со списком аккаунтов
     */
    private function getSummaryBalance(array $data): array
    {
        $groupedFilters = [];

        foreach ($data as $account) {
            $groupedFilters[$account['name']][] = $account;
        }

        $result = [];

        foreach ($data as $account) {
            if (count($groupedFilters[$account['name']]) > 1) {
                if (isset($result[$account['name']])) {
                    $result[$account['name']] += $account['balance'];
                } else {
                    $result[$account['name']] = (int) $account['balance'];
                }
            }
        }

        return $result;
    }

    /**
     * Сортирует аккаунты по значениям в свойстве `balance`.
     */
    private function sortByBalance(array &$data)
    {
        usort($data, function ($a, $b) {
            if ($a['balance'] == $b['balance']) {
                return 0;
            }

            return ($a['balance'] > $b['balance']) ? -1 : 1;
        });
    }
}
