<?php

/** @noinspection SqlDialectInspection */

/** @noinspection SqlNoDataSourceInspection */

namespace AwardWallet\MainBundle\Email;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class EmailAddressManager
{
    private const CACHE_KEY_TEMPLATE = 'gmail_forward_list_%d_%d';
    private const CACHE_TTL = 60 * 60;
    /*
     * proved working with random addresses
        499 addresses
        bare list size
        15836
        whole file size
        16557
     */

    private const DEFAULT_LENGTH = 15000;
    private const DEFAULT_COUNT = 370;

    private Connection $db;

    private \Memcached $memcached;

    public function __construct(Connection $db, \Memcached $memcached)
    {
        $this->db = $db;
        $this->memcached = $memcached;
    }

    public function write(string $from, array $types): void
    {
        $from = strtolower(preg_replace('/^[^@]*@/', '', $from));

        try {
            $period = (new \DateTime())->format('Y-m-01');
            $date = (new \DateTime())->format('Y-m-d H:i:s');
            $this->db->executeQuery("insert into EmailFromAddress (Domain, Verified, LastReceivedDate)
                            values (?, 0, ?)
                            on duplicate key update LastReceivedDate = ?", [$from, $date, $date]);
            $id = $this->db->executeQuery('select EmailFromAddressID from EmailFromAddress where Domain = ?', [$from])->fetchOne();
            $this->db->executeQuery("insert into EmailFromAddressCnt (
                                 EmailFromAddressID,
                                 PeriodMonth,
                                 Cnt,
                                 CntItinerary,
                                 CntStatement,
                                 CntDiscovered,
                                 CntOtc,
                                 CntBp,
                                 CntOther)
                            values (?, ?, 1, ?, ?, ?, ?, ?, ?)
                            on duplicate key update
                                Cnt = Cnt + 1,
                                CntItinerary = CntItinerary + ?,
                                CntStatement = CntStatement + ?,
                                CntDiscovered = CntDiscovered + ?,
                                CntOtc = CntOtc + ?,
                                CntBp = CntBp + ?,
                                CntOther = CntOther + ?", array_merge([$id, $period], $types, $types));
        } catch (Exception $e) {
        }
    }

    public function writeBatch(array $ids, int $verified): void
    {
        $this->db->executeQuery("update EmailFromAddress set Verified = {$verified}, LastUpdateDate = curdate() where EmailFromAddressID in (" . implode(',', $ids) . ")");
    }

    public function getList(int $pos): array
    {
        $result = $this->getAllLists();

        return $result[$pos] ?? [];
    }

    public function getMeta(?Usr $user = null): array
    {
        $lists = $this->getAllLists();
        $updateDate = $this->db->executeQuery('select max(LastUpdateDate) from EmailFromAddress')->fetchOne();
        $result = ['listCount' => count($lists), 'lengths' => [], 'lastUpdateDate' => $updateDate];

        foreach ($lists as $i => $list) {
            $result['lengths'][$i] = count($list);
        }
        $result['users'] = [];

        if (!is_null($user)) {
            $result['users'][] = ['name' => $user->getFullName(), 'alias' => ''];

            foreach ($user->getFamilyMembers() as $familyMember) {
                if ($familyMember->getAlias()) {
                    $result['users'][] = ['name' => $familyMember->getFullName(), 'alias' => $familyMember->getAlias()];
                }
            }
        }

        return $result;
    }

    public function clearCache()
    {
        $this->memcached->delete($this->getCacheKey());
    }

    public function getFullList(): array
    {
        try {
            return $this->db->executeQuery('
                select e.Domain,
                       e.EmailFromAddressID as ID,
                       e.LastReceivedDate,
                       e.Verified,
                       ec.EmailFromAddressID,
                       sum(ec.Cnt) as Total,
                       sum(ec.CntItinerary) as TotalItinerary,
                       sum(ec.CntStatement) as TotalStatement,
                       sum(ec.CntDiscovered) as TotalDiscovered,
                       sum(ec.CntOtc) as TotalOtc,
                       sum(ec.CntBp) as TotalBp,
                       sum(ec.CntOther) as TotalOther,
                       if(e.Verified = 0, 10, e.Verified) as VSort
                from EmailFromAddressCnt ec left join EmailFromAddress e on ec.EmailFromAddressID = e.EmailFromAddressID
                where PeriodMonth > date_add(curdate(), interval -1 year)
                    group by EmailFromAddressID
                    order by VSort desc, e.Domain asc
                ')->fetchAllAssociative();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getAllLists(): array
    {
        $result = $this->memcached->get($this->getCacheKey());

        if (!$result) {
            $result = $this->split();
            $this->memcached->set($this->getCacheKey(), $result, self::CACHE_TTL);
        }

        return $result;
    }

    private function split(): array
    {
        $result = [];
        $query = $this->db->executeQuery('
                select e.Domain, sum(ec.Cnt) as Total
                from EmailFromAddressCnt ec
                    left join EmailFromAddress e on ec.EmailFromAddressID = e.EmailFromAddressID
                where e.Verified = 1 group by e.Domain order by Total desc');
        $cur = 0;
        $length = 0;
        $cnt = 0;
        $list = [];

        while ($domain = $query->fetchOne()) {
            $list[] = $domain;
            $length += (strlen($domain) + 5);
            $cnt++;

            if ($length >= self::DEFAULT_LENGTH || $cnt >= self::DEFAULT_COUNT) {
                $result[$cur] = $list;
                $cur++;
                $length = 0;
                $cnt = 0;
                $list = [];
            }
        }

        if (count($list) > 0 && !isset($result[$cur])) {
            $result[$cur] = $list;
        }

        return $result;
    }

    private function getCacheKey(): string
    {
        return sprintf(self::CACHE_KEY_TEMPLATE, self::DEFAULT_LENGTH, self::DEFAULT_COUNT);
    }
}
