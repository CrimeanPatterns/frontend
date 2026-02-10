<?php

use Doctrine\DBAL\Connection;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Elitelevel;
use AwardWallet\MainBundle\Entity\Country;

require_once __DIR__ . '/BaseOfferPlugin.php';

class AircanadastatusmatchOfferPlugin extends BaseOfferPlugin
{
    private const TIER_PROVIDERS = [
        'American Airlines' => Provider::AA_ID,
        'Alaska Airlines' => Provider::ALASKA_ID,
        'Delta Air Lines' => Provider::DELTA_ID,
        'Hawaiian Airlines' => Provider::HAWAIIAN_ID,
        'Jetblue' => Provider::JETBLUE_ID,
        'Southwest' => Provider::SOUTHWEST_ID,
    ];

    public function searchUsers(): int
    {
        $this->log('Setting time limit to 59 seconds...');
        flush();
        set_time_limit(59);
        $usersId = getSymfonyContainer()->get('doctrine.orm.entity_manager')->getConnection()->fetchFirstColumn('
            SELECT DISTINCT u.UserID
            FROM Account a
            JOIN Usr u ON (a.UserID = u.UserID AND u.CountryID = ' . Country::UNITED_STATES . ')
            WHERE
                    a.ProviderID IN (:providersId)
                AND a.State = ' . ACCOUNT_ENABLED . '
                AND u.CountryID = ' . Country::UNITED_STATES . '
                AND u.UserID NOT IN (SELECT UserID FROM GroupUserLink WHERE SiteGroupID = 50)
                -- AND u.UserID NOT IN (SELECT UserID FROM OfferUser WHERE OfferID = ' . ((int) $this->offerId) . ')
                AND (
                       YEAR(a.EmailParseDate) = :year
                    OR YEAR(a.UpdateDate) = :year
                    OR YEAR(a.ModifyDate) = :year
                )
            ',
            [
                'providersId' => self::TIER_PROVIDERS,
                'year' => date('Y'),
            ],
            [
                'providersId' => Connection::PARAM_INT_ARRAY,
                'year' => \PDO::PARAM_INT,
            ],
        );

        $u = 0;
        foreach ($usersId as $userId) {
            $found = $this->getFoundTier($userId);
            if (empty($found)) {
                continue;
            }
            $params = [
                'loyalty_tier' => $found['pattern']['id'],
                'accountId' => $found['accountId'],
            ];
            if (!empty($found['existingLogin'])) {
                $params['new_program_id'] = $found['existingLogin'];
            }
            if (!empty($found['member_id'])) {
                $params['member_id'] = $found['member_id'];
            }

            $this->addUser($userId, $params);
            if (++$u % 100 == 0) {
                set_time_limit(59);
                $this->log($u . ' users so far...');
                flush();
            }
        }

        return $u;
    }

    public function checkUser($userId, $offerUserId): bool
    {
        $found = $this->getFoundTier($userId);

        return !empty($found);
    }

    public function getParams($offerUserId, $preview = false, $params = null)
    {
        $params = parent::getParams($offerUserId, $preview, $params);

        $tier = $this->getTier($params['loyalty_tier']);
        $offer = $this->doctrine->getConnection()->fetchAssociative('
            SELECT ou.UserID, o.ApplyURL
            FROM OfferUser ou
            JOIN Offer o ON (ou.OfferID = o.OfferID)
            WHERE OfferUserID = ' . (int) $offerUserId);
        /** @var Usr $user */
        $user = $this->doctrine->getRepository(Usr::class)->find($offer['UserID']);
        $agent = $this->doctrine->getConnection()
            ->fetchAssociative('
                SELECT ua.FirstName, ua.LastName, ua.MidName
                FROM Account a
                JOIN UserAgent ua ON (ua.UserAgentID = a.UserAgentID)
                WHERE a.AccountID = :accountId 
            ', ['accountId' => $params['accountId']], ['accountId' => \PDO::PARAM_INT]
        );
        $fullName = empty($agent)
            ? $user->getFullName()
            : trim($agent['FirstName'] . (!empty($agent['MidName']) ? (' ' . $agent['MidName']) : '') . ' ' . $agent['LastName']);

        $params = array_merge($params, [
            'name' => ucwords($user->getFirstname()),
            'lastname' => $user->getLastname(),
            'refCode' => $user->getRefcode(),
            'fullName' => ucwords($fullName),
            'email' => $user->getEmail(),
            'upgradeProgram' => 'Air Canada Aeroplan ' . $tier['Air Canada Match'],
            'matchProgram' => $tier['program_id'],
            'matchStatus' => $tier['tier_name'],
            'providerId' => $tier['providerId'],
            'existsAirCanadaId' => $params['new_program_id'] ?? null,
        ]);
        $params['redirectUrl'] = $this->getAgreedUrl($params, $offer['ApplyURL']);

        return $params;
    }

    public function getAgreedUrl(array $params, string $url): string
    {
        $data = array(
            'partner_id' => '1',
            'program_id' => '21',
            'unique_id' => $params['refCode'],
            'first_name' => $params['name'],
            'last_name' => $params['lastname'],
            'email_address' => $params['email'],
            'loyalty_tier' => $params['loyalty_tier'],
        );
        if (!empty($params['existsAirCanadaId'])) {
            $data['new_program_id'] = $params['existsAirCanadaId'];
        }
        if (!empty($params['member_id'])) {
            $data['member_id'] = $params['member_id'];
        }

        $json = json_encode($data);
        openssl_public_encrypt($json, $encrypted, $this->getPublicKey());
        $encrypted_hex = bin2hex($encrypted);

        $data = parse_url($url);

        return $url . (empty($data['query']) ? '?' : '&') . 'awuser=' . $encrypted_hex;
    }

    public function getFoundTier(int $userId): array
    {
        $tiers = $this->getTiers();
        $accounts = $this->getAccounts($userId);
        $status = $this->getAccountsStatus($accounts);

        $providerTiers = $this->getProvidersTier($tiers);
        $eliteLevels = $this->getEliteLevels();

        $actualStatus = [];
        foreach ($accounts as $account) {
            $accountId = (int) $account['AccountID'];
            $providerId = (int) $account['ProviderID'];
            $agentId = empty($account['UserAgentID']) ? 'my' : 'fm';
            if (!array_key_exists($accountId, $status)) {
                continue;
            }

            $accountStatus = $status[$accountId]['Val'];

            $levels = $eliteLevels[$providerId];
            foreach ($levels as $level) {
                if (!in_array(strtolower($accountStatus), $level['patterns'])) {
                    continue;
                }

                foreach ($providerTiers[$providerId] as $pattern) {
                    $priority = $pattern['priority'] ?? 0;
                    $pattern['tier_name'] = strtolower($pattern['tier_name']);

                    foreach ($level['patterns'] as $keyword) {
                        if ($keyword === $pattern['tier_name']) {
                            if (!empty($actualStatus[$agentId][$providerId]) && $actualStatus[$agentId][$providerId]['priority'] > $priority) {
                                continue;
                            }
                            $actualStatus[$agentId][$providerId] = [
                                'priority' => $priority,
                                'pattern' => $pattern,
                                'eliteLevel' => $level,
                                'accountId' => $accountId,
                                'member_id' => $this->getAccountNumber($accountId),
                            ];
                        }
                    }
                }
            }
        }

        $maxRating = [
            'my' => [],
            'fm' => [],
        ];
        foreach ($actualStatus as $agentKey => $actualStatusAgents) {
            foreach ($actualStatusAgents as $found) {
                if (empty($maxRating[$agentKey]) || $found['priority'] > $maxRating[$agentKey]['priority']) {
                    $maxRating[$agentKey] = $found;
                }
            }
        }

        $result = !empty($maxRating['my']) ? $maxRating['my'] : $maxRating['fm'];

        return $this->checkStatusExists($userId, $result);
    }

    private function checkStatusExists(int $userId, array $found): array
    {
        if (empty($found)) {
            return $found;
        }

        $accounts = $this->getAccounts($userId, [Provider::AIRCANADA_ID]);
        if (empty($accounts)) {
            return $found;
        }

        $matchFound = strtolower($found['pattern']['Air Canada Match']);
        $status = $this->getAccountsStatus($accounts);
        $eliteLevels = $this->getEliteLevels([Provider::AIRCANADA_ID]);

        $patterns = [];
        foreach ($eliteLevels[Provider::AIRCANADA_ID] as $levels) {
            if (isset($levels['Name']) && strtolower($levels['Name']) === $matchFound) {
                $patterns = $levels['patterns'];
            }
        }

        foreach ($status as $accountId => $values) {
            $currentStatus = strtolower($values['Val']);
            if (in_array($currentStatus, $patterns)) {
                return [];
            }

            foreach ($eliteLevels[Provider::AIRCANADA_ID] as $levels) {
                if (in_array($currentStatus, $levels['patterns'])
                    && isset($levels['Rank'])
                    && $levels['Rank'] > $found['eliteLevel']['Rank']
                ) {
                    return [];
                }
            }
        }

        if (!empty($accounts)) {
            $accountNumber = $this->getAccountNumber($accounts[0]['AccountID']);
            if (!empty($accountNumber)) {
                $found['existingLogin'] = $accountNumber;
            }
        }

        return $found;
    }

    private function getAccountNumber(int $accountId)
    {
        return getSymfonyContainer()->get('doctrine.orm.entity_manager')->getConnection()
            ->fetchOne('
                        SELECT ap.Val
                        FROM AccountProperty ap
                        JOIN ProviderProperty pp ON (ap.ProviderPropertyID = pp.ProviderPropertyID AND pp.Kind = ' . PROPERTY_KIND_NUMBER . ')
                        WHERE ap.AccountID = :accountId
                        ',
                ['accountId' => $accountId],
                ['accountId' => \PDO::PARAM_INT]
            );
    }

    private function getEliteLevels(array $providersId = self::TIER_PROVIDERS): array
    {
        $levels = getSymfonyContainer()->get('doctrine.orm.entity_manager')->getConnection()
            ->fetchAllAssociative('
                SELECT
                    el.ProviderID, el.Rank, el.Name, el.Description,
                    tel.ValueText
                FROM EliteLevel el
                LEFT JOIN TextEliteLevel tel ON (el.EliteLevelID = tel.EliteLevelID)
                WHERE
                    el.ProviderID IN (:providersId)
                ORDER BY el.Rank ASC
            ',
                ['providersId' => $providersId],
                ['providersId' => Connection::PARAM_INT_ARRAY],
            );

        $result = [];
        foreach ($levels as $level) {
            if (!array_key_exists($level['ProviderID'], $result)) {
                $result[$level['ProviderID']][] = [];
            }
            if (!array_key_exists($level['Rank'], $result[$level['ProviderID']])) {
                $result[$level['ProviderID']][$level['Rank']] = $level;
                $result[$level['ProviderID']][$level['Rank']]['patterns'] = [];
            }

            $result[$level['ProviderID']][$level['Rank']]['patterns'][] = str_replace(['С', 'с'], ['C', 'c'],
                $level['Name']);
            $result[$level['ProviderID']][$level['Rank']]['patterns'][] = str_replace(['С', 'с'], ['C', 'c'],
                $level['ValueText']);

            //if ('MVP Gold 75k' === $level['Name']) {
            //    $result[$level['ProviderID']][$level['Rank']]['patterns'][] = 'MVP 75k';
            //}
        }

        foreach ($result as &$ranks) {
            foreach ($ranks as &$rank) {
                $rank['patterns'] = array_map('strtolower', $rank['patterns']);
                $rank['patterns'] = array_unique($rank['patterns']);
                usort($rank['patterns'], fn($b, $a) => strtolower($a) <=> strtolower($b));
            }
        }

        return $result;
    }

    private function getAccounts(int $userId, array $providersId = self::TIER_PROVIDERS): array
    {
        return getSymfonyContainer()->get('doctrine.orm.entity_manager')->getConnection()
            ->fetchAllAssociative('
                SELECT
                    AccountID, UserID, ProviderID, Login, UserAgentID
                FROM Account
                WHERE
                        UserID = :userId 
                    AND ProviderID IN (:providersId)
                    AND State = ' . ACCOUNT_ENABLED . '
                    AND (
                           YEAR(EmailParseDate) = :year
                        OR YEAR(UpdateDate) = :year
                        OR YEAR(ModifyDate) = :year
                    )
                ',
                ['userId' => $userId, 'providersId' => $providersId, 'year' => date('Y')],
                ['userId' => PDO::PARAM_INT, 'providersId' => Connection::PARAM_INT_ARRAY, 'year' => PDO::PARAM_INT],
            );
    }

    private function getAccountsStatus(array $accounts): array
    {
        $ppKindStatus = getSymfonyContainer()->get('doctrine.orm.entity_manager')->getConnection()
            ->fetchAllAssociative('
                SELECT
                    ap.AccountID,
                    pp.Name, pp.Code, ap.Val, pp.ProviderPropertyID, pp.ProviderID
                FROM AccountProperty ap FORCE INDEX (AccountID)
                JOIN ProviderProperty pp FORCE INDEX (`PRIMARY`) ON (ap.ProviderPropertyID = pp.ProviderPropertyID)
                WHERE
                        ap.AccountID IN (:accountsId)
                    AND ap.SubAccountID IS NULL
                    AND pp.Kind = ' . PROPERTY_KIND_STATUS . '
                ',
                ['accountsId' => array_column($accounts, 'AccountID')],
                ['accountsId' => Connection::PARAM_INT_ARRAY]
            );

        return array_combine(array_column($ppKindStatus, 'AccountID'), $ppKindStatus);
    }

    private function getProvidersTier(array $tiers): array
    {
        $providersTier = [];
        foreach ($tiers as $tier) {
            if (!array_key_exists($tier['providerId'], $providersTier)) {
                $providersTier[$tier['providerId']] = [];
            }
            $providersTier[$tier['providerId']][] = $tier;
        }

        foreach ($providersTier as &$items) {
            usort($items, fn($a, $b) => strlen($b['tier_name']) <=> strlen($a['tier_name']));
        }

        return $providersTier;
    }

    private function getTiers(): array
    {
        $text = '
id,program_id,tier_name,Air Canada Match,priority
38,American Airlines,Concierge Key,Super Elite,5
37,American Airlines,Executive Platinum,75K,4
34,American Airlines,Gold,25K,1
35,American Airlines,Platinum,35K,2
36,American Airlines,Platinum Pro,50K,3
72,Alaska Airlines,MVP,25K,1
74,Alaska Airlines,MVP 75K,50K,3
73,Alaska Airlines,MVP Gold,35K,2
281,Delta Air Lines,Delta 360,Super Elite,5
188,Delta Air Lines,Diamond Medallion,75K,4
186,Delta Air Lines,Gold Medallion,35K,2
187,Delta Air Lines,Platinum Medallion,50K,3
185,Delta Air Lines,Silver Medallion,25K,1
228,Hawaiian Airlines,Gold,25K,1
229,Hawaiian Airlines,Platinum,25K,1
231,Jetblue,Mosaic,25K,1
232,Southwest,A-List,25K,1
233,Southwest,A-List Preferred,35K,2
234,Southwest,Companion Pass,50K,3
        ';

        $csv = array_map('str_getcsv', explode("\n", trim($text)));
        if (1 === count($csv[count($csv) - 1])) {
            unset($csv[count($csv) - 1]);
        }
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
            unset($a['']);
        });
        array_shift($csv);

        foreach ($csv as &$row) {
            if (!array_key_exists($row['program_id'], self::TIER_PROVIDERS)) {
                throw new \Exception('Program - providerId not found');
            }
            $row['providerId'] = self::TIER_PROVIDERS[$row['program_id']];
        }

        return $csv;
    }

    private function getTier($id): array
    {
        $tiers = $this->getTiers();
        foreach ($tiers as $tier) {
            if ((int) $tier['id'] === (int) $id) {
                return $tier;
            }
        }

        throw new \Exception('Tier not found');
    }

    private function getPublicKey(): string
    {
        return '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAn2lpi611D+Z7hyI53LFJ
efh8xy9LXfedJhtm1DfJtiNDSilAbQEwUH0G8OjYjru+h1zldhiOHa/46YcbL7i1
sV7Xm2f9E1nzTtIO1n+rK7jMjSkjEDcuO0xZjs0qolc9LZusxD9eqvZ/cLKTbKJZ
IjRYLdvcOAsCpOFR2Fxnk9ZgSvWpjHj0gkyJTJJhgnVfPNoD9CBBzQMbd44zNeNA
6J/wJJZolSFhM/q6S2p2dVVe1fzh4aSnE/TwQ2kvxWoA4uDgG5aGVW6FIutJ/wi7
yq5fj9a0NbFBIjfeBg+1PVAHgzu4352DAQmoauScQd8ctocG1im2GMRL/AQ5x0BX
G3gNyGp1dGWozzUDLxrX+gNQAHGTCVfZn199AmV5rKUpUilJ9cT9vmAuPrc/E7w+
mnqLl2AwVJFvWfokaReVrntaFVKmbweALE8SrdhG7qML98zKBEPqNjTGAuKm6DJ4
eLI9OUzeWiPkIVf7jmpd8F/uOHmAOaeYrSj6/jOJO/bEcVk1sJT2gWcpnaecvsPA
NvceMw+jh/KHBlfNBWVRTO8g8Dy3dRXlLoq0TH7RUgpWG+ZGejiJfL3RSKDNnubP
rBx1ggAOjD8gfpXli9xgTlINpOyhqg1KuYkejEf9ZB54nrP0/I9CezKpqGIRCNzI
E2xz2GLPnU4PBYU5JXJmUbMCAwEAAQ==
-----END PUBLIC KEY-----';
    }
}
