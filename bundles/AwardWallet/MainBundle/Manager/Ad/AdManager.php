<?php

namespace AwardWallet\MainBundle\Manager\Ad;

use AwardWallet\MainBundle\Entity\Socialad;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;

class AdManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var GeoLocation
     */
    protected $geoLocation;

    public function __construct(EntityManagerInterface $em, GeoLocation $geoLocation)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->geoLocation = $geoLocation;
    }

    /**
     * @param bool $adWasShown record the show in statistics
     * @return Socialad|null
     */
    public function getAdvt(Options $options, $adWasShown = false)
    {
        $builder = $this->conn->createQueryBuilder();
        $e = $builder->expr();

        $user = $options->user;

        if (!isset($options->clientIp)) {
            if (isset($user)) {
                $options->clientIp = $user->getLastKnownIp();
            } elseif (count($options->accounts) > 0) {
                foreach ($options->accounts as $account) {
                    if ($account->getUserid()) {
                        $options->clientIp = $account->getUserid()->getLastKnownIp();

                        break;
                    }
                }
            }
        }
        [$kinds, $accounts] = $this->getProviderFilters($options);

        $builder
            ->select('a.*')
            ->from('SocialAd', 'a')
            ->where(
                $e->andX(
                    $e->orX(
                        $e->isNull('a.BeginDate'),
                        $e->lt('a.BeginDate', 'now()')
                    ),
                    $e->orX(
                        $e->isNull('a.EndDate'),
                        $e->gt('a.EndDate', 'now()')
                    ),
                    $e->eq('a.Kind', ':kind')
                )
            );
        $builder->setParameter(":kind", $options->kind, \PDO::PARAM_INT);

        if (isset($options->filter)) {
            $builder->andWhere($options->filter);
        }

        if ($options->kind == ADKIND_EMAIL && !empty($options->emailType)) {
            $builder->leftJoin('a', 'AdTypeMail', 'am', $e->eq("a.SocialAdID", "am.SocialAdID"));
            $builder->andWhere($e->orX(
                $e->eq("am.TypeMail", ":typeMail"),
                $e->isNull("am.TypeMail")
            ));
            $builder->setParameter(":typeMail", $options->emailType, \PDO::PARAM_STR);
        }

        if (isset($user) && $user->getDefaultBooker(true)) {
            $booker = $user->getDefaultBooker(true);

            if (!$booker->isBooker()) {
                $booker = null;
            }
        }

        if (isset($booker)) {
            $bookerID = $booker->getUserid();
            $disableAd = $booker->getBookerInfo()->getDisableAd();
            $builder->leftJoin('a', 'AdBooker', 'ab', $e->eq('a.SocialAdID', 'ab.SocialAdID'));

            if ($disableAd) {
                $builder->andWhere($e->eq('ab.BookerID', ':booker'));
            } else {
                $builder->andWhere(
                    $e->orX(
                        $e->eq('ab.BookerID', ':booker'),
                        $e->isNull('ab.BookerID')
                    )
                );
            }
            $builder->setParameter(":booker", $bookerID, \PDO::PARAM_INT);
        }

        // geo groups
        $builder->andWhere($e->orX(...$this->getGeoGroupFilters($options, $builder)));

        $ads = [];

        if (count($accounts) > 0) {
            $cloneBuilder = clone $builder;
            $cloneBuilder->addSelect('ap.ProviderID')
                ->join('a', 'AdProvider', 'ap', $e->eq('a.SocialAdID', 'ap.SocialAdID'))
                ->andWhere($e->in('ap.ProviderID', ':providerIds'));
            $cloneBuilder->setParameter(":providerIds", array_keys($accounts), Connection::PARAM_INT_ARRAY);
            $stmt = $cloneBuilder->execute();
            $max = 0;

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $cur = $accounts[$row['ProviderID']];

                if ($cur > $max) {
                    $max = $cur;
                    $ads = [$row['SocialAdID'] => $row];
                } else {
                    if ($cur == $max) {
                        $ads[$row['SocialAdID']] = $row;
                    }
                }
            }
        }

        if (count($ads) == 0) {
            $filter = [];

            if (count($kinds) > 0) {
                $filter[] = $e->in('a.ProviderKind', ':providerKind');
                $builder->setParameter(":providerKind", array_keys($kinds), Connection::PARAM_INT_ARRAY);
            }
            $filter[] = $e->eq('a.AllProviders', 1);
            $builder->andWhere($e->orX(...$filter));

            $stmt = $builder->execute();

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $ads[$row['SocialAdID']] = $row;
            }
        }

        if (count($ads) == 0) {
            return null;
        } else {
            $ad = $ads[array_rand($ads)];
            $ad = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Socialad::class)
                ->find($ad['SocialAdID']);

            if ($ad) {
                $this->recordStat($ad->getSocialadid(), 'Sent');

                if ($adWasShown) {
                    $this->recordStat($ad->getSocialadid());
                }

                return $ad;
            }
        }
    }

    /**
     * @param int $id SocialAdID
     * @param string $field
     */
    public function recordStat($id, $field = 'Messages')
    {
        $this->conn->executeQuery(
            "INSERT INTO AdStat(SocialAdID, StatDate, {$field}) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE $field = $field + 1",
            [$id, date("Y-m-d H:i:s"), 1],
            [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT]
        );
    }

    private function getProviderFilters(Options $options)
    {
        $kinds = [];
        $accounts = [];

        if (count($options->accounts)) {
            foreach ($options->accounts as $account) {
                if (!$account->getProviderid()) {
                    continue;
                }
                $p = $account->getProviderid();
                $pid = $p->getProviderid();

                if (!isset($accounts[$pid]) || $accounts[$pid] < $account->getChangecount()) {
                    $accounts[$pid] = $account->getChangecount();
                }
                $kinds[$p->getKind()] = $p->getKind();
            }
        }

        if (count($options->providers)) {
            foreach ($options->providers as $provider) {
                if (!isset($accounts[$provider->getProviderid()])) {
                    $accounts[$provider->getProviderid()] = 0;
                }
                $kinds[$provider->getKind()] = $provider->getKind();
            }
        }

        if (count($options->flatData)) {
            foreach ($options->flatData as $pid => $data) {
                if (!isset($data['ChangeCount'])) {
                    $data['ChangeCount'] = 0;
                }

                if (!isset($accounts[$pid]) || $accounts[$pid] < $data['ChangeCount']) {
                    $accounts[$pid] = $data['ChangeCount'];
                }

                if (isset($data['Kind'])) {
                    $kinds[$data['Kind']] = $data['Kind'];
                }
            }
        }

        return [$kinds, $accounts];
    }

    private function getGeoGroupFilters(Options $options, QueryBuilder $builder)
    {
        $e = $builder->expr();
        $groups = 0;
        $filter = [$e->isNull('a.GeoGroups')];

        if (isset($options->clientIp)) {
            $countryId = $this->geoLocation->getCountryIdByIp($options->clientIp);

            if ($countryId === 230) {
                $groups |= Socialad::GEO_GROUP_US;
            } else {
                $groups |= Socialad::GEO_GROUP_NON_US;
            }
        }

        if ($groups > 0) {
            $filter[] = "a.GeoGroups & :groups > 0";
            $builder->setParameter(":groups", $groups, \PDO::PARAM_INT);
        }

        return $filter;
    }
}
