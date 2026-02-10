<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Advertise
{
    public const ALLOW_COUNTRIES_ID = [
        Country::UNITED_STATES,
    ];

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $statLogger;

    /* @var CacheManager */
    private $cache;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $statLogger,
        CacheManager $cache,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->statLogger = $statLogger;
        $this->cache = $cache;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getListForLanding(int $limit = 3): array
    {
        return $this->entityManager->getRepository(CreditCard::class)->findBy(
            ['visibleOnLanding' => true],
            null,
            $limit
        );
    }

    public function getListByUser(Usr $user): array
    {
        if (($user->isAwPlus() && $user->isListAdsDisabled())
            || !in_array($user->getCountryid(), self::ALLOW_COUNTRIES_ID)) {
            return [];
        }

        $listKindsCard = $this->cache->load(new CacheItemReference(
            Tags::getCreditCardAdKey($user->getUserid()),
            Tags::getCreditCardAdTags($user->getUserid()),
            function () use ($user): array {
                $cards = $this->fetchCards($user);

                if (empty($cards)) {
                    return [];
                }

                $userKinds = $this->fetchUserKinds($user);
                $applicableAds = $this->filterByApplicability($user, $cards);

                $listKindsCard = [];
                $skippedAds = [];

                foreach ($userKinds as $kind) {
                    if (\count($applicableAds)) {
                        $keyFirst = array_key_first($applicableAds);
                        $priority = $applicableAds[$keyFirst]->getSortIndex();
                        $priorityAds = array_filter($applicableAds, static function (CreditCard $card) use ($priority) {
                            return $card->getSortIndex() === $priority;
                        });

                        $adId = array_rand($priorityAds);
                        $listKindsCard[$kind] = $priorityAds[$adId];
                        $applicableAds = array_diff_key($applicableAds, $priorityAds);
                        unset($priorityAds[$adId]);
                        $skippedAds = array_merge($skippedAds, $priorityAds);
                    } elseif (\count($skippedAds)) {
                        $adId = array_rand($skippedAds);
                        $listKindsCard[$kind] = $skippedAds[$adId];
                        unset($skippedAds[$adId]);
                    }
                }

                return $listKindsCard;
            }
        ));

        $ads = [];
        $mid = $this->authorizationChecker->isGranted('SITE_MOBILE_APP') ? 'mobile' : 'web';

        /** @var CreditCard $card */
        foreach ($listKindsCard as $kind => $card) {
            if (empty($card->getDirectClickURL())) {
                $this->statLogger->notice('credit card ad - DirectClickURL field is not filled', ['UserID' => $user->getUserid(), 'Category' => $kind, 'CreditCardID' => $card->getId()]);

                continue;
            }
            $ads[$kind] = new Advertise\Ad(
                $card->getId(),
                $card->getSortIndex() ?? 0,
                $card->getPicturePath(),
                empty($card->getCardFullName()) ? $card->getName() : $card->getCardFullName(),
                $card->getText(),
                StringHandler::var2TrackingModify($card->getDirectClickURL(), ['source' => 'aw_app', 'cid' => 'accountlist', 'mid' => $mid]),
                $card->isVisibleInList()
            );

            $this->statLogger->info('credit card ad', ['UserID' => $user->getUserid(), 'Category' => $kind, 'CreditCardID' => $card->getId()]);
        }

        return $ads;
    }

    private function fetchCards(Usr $user): array
    {
        $isBusinessUser = \count($this->detectedBusinessCards($user)) > 0;
        $cards = $this->entityManager->getRepository(CreditCard::class)->findBy(
            ['visibleInList' => true],
            ['sortIndex' => 'ASC']
        );

        $list = [];

        /** @var CreditCard $card */
        foreach ($cards as $card) {
            $isCondition = $isBusinessUser || (!$isBusinessUser && !$card->isBusiness());

            if (!$isCondition) {
                continue;
            }

            $list[] = $card;
        }

        return $list;
    }

    private function filterByApplicability(Usr $user, array $cards): array
    {
        $userDetectedCardsId = $this->connection->executeQuery('
            SELECT
                    ucc.CreditCardID
            FROM UserCreditCard ucc
            WHERE
                    ucc.UserID = ?
                AND ucc.IsClosed = 0',
            [$user->getUserid()],
            [\PDO::PARAM_INT]
        )->fetchAll(FetchMode::COLUMN);

        $applicableAds = [];
        $excludedCardsId = [];

        /** @var CreditCard $card */
        foreach ($cards as $card) {
            if (in_array($card->getId(), $userDetectedCardsId)) {
                if (null !== $card->getExcludeCardsId()) {
                    $excludedCardsId = array_merge($excludedCardsId, $card->getExcludeCardsId());
                }

                continue;
            }
            $applicableAds[$card->getId()] = $card;
        }
        $excludedCardsId = array_unique($excludedCardsId);
        $applicableAds = array_filter($applicableAds, static function (CreditCard $card) use ($excludedCardsId) {
            return $card->isVisibleInList() && !in_array($card->getId(), $excludedCardsId, true);
        });
        usort($applicableAds, static function (CreditCard $cardA, CreditCard $cardB) {
            return $cardA->getSortIndex() <=> $cardB->getSortIndex();
        });

        return $applicableAds;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchUserKinds(Usr $user): array
    {
        $kinds = $this->connection->executeQuery('
            SELECT
                    DISTINCT p.Kind
            FROM Account a
            JOIN Provider p ON (a.ProviderID = p.ProviderID) 
            WHERE
                    a.UserID = ?
                AND a.UserAgentID IS NULL
                AND a.State NOT IN (?)  
                AND ' . $user->getProviderFilter(),
            [$user->getUserid(), [ACCOUNT_PENDING, ACCOUNT_IGNORED]],
            [\PDO::PARAM_INT, Connection::PARAM_INT_ARRAY]
        )->fetchAll(\PDO::FETCH_COLUMN);

        return array_intersect(array_keys(Provider::getKinds()), $kinds);
    }

    private function detectedBusinessCards(Usr $user): array
    {
        return $this->connection->fetchAll("
            SELECT
                    cc.CreditCardID, cc.CardFullName
            FROM UserCreditCard ucc
            JOIN CreditCard cc ON (ucc.CreditCardID = cc.CreditCardID)
            WHERE
                    ucc.UserID = ?
                AND ucc.IsClosed = 0  
                AND cc.CardFullName LIKE '%business%'",
            [$user->getUserid()],
            [\PDO::PARAM_INT]
        );
    }
}
