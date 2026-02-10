<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;

class ProviderSource
{
    private EntityManagerInterface $entityManager;

    private ProviderTranslator $providerTranslator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProviderTranslator $providerTranslator
    ) {
        $this->entityManager = $entityManager;
        $this->providerTranslator = $providerTranslator;
    }

    /**
     * get list of providers for adding.
     *
     * @param Usr|bool $user
     * @param string   $filter
     * @param string   $orderBy
     * @param string   $fields
     * @param string   $stateFilter
     */
    public function getListProvidersForAdding($user, $filter = '', $orderBy = "ORDER BY Corporate, Name", $fields = null, $stateFilter = null): array
    {
        $conn = $this->entityManager->getConnection();

        if (!isset($fields)) {
            $fields = "
                   p.ProviderID ,
			       loginURL   ,
			       ProgramName,
			       DisplayName,
			       p.Name       ,
			       Kind
			";
        }

        if ($user) {
            if (!isset($stateFilter)) {
                $stateFilter = $user->getProviderFilter();
            }

            $sql = "
                SELECT $fields
                FROM   Provider p
                WHERE  {$stateFilter}
                       AND p.ProviderID <> 4
                       $filter
                $orderBy
            ";
        } else {
            if (!isset($stateFilter)) {
                $stateFilter = '(p.State > 0 OR p.State IS NULL)';
            }

            $sql = "
                SELECT $fields
                FROM   Provider p
                WHERE  {$stateFilter}
                       AND p.ProviderID <> 4
                       $filter
                $orderBy
            ";
        }
        $stmt = $conn->executeQuery($sql);
        $providers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($user) {
            if (!$user->isBusiness()) {
                $accountRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
                $sql = $accountRep->getAccountsSQLByUserAgent($user->getUserid());
                $stmt = $conn->query($sql);
                $added = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $_added = [];

                foreach ($added as $row) {
                    $_added[$row['ProviderID']] = isset($_added[$row['ProviderID']]) ? $_added[$row['ProviderID']] + 1 : 1;
                }
                unset($added);
            }

            foreach ($providers as &$provider) {
                $provider['Has'] = $_added[$provider['ProviderID']] ?? 0;
            }
        } else {
            foreach ($providers as &$provider) {
                $provider['href'] = $this->getUrlbyName($provider['DisplayName']);
            }
        }

        // translate DisplayName if possible
        foreach ($providers as &$provider) {
            $provider['DisplayName'] = $this->providerTranslator->translateDisplayNameByScalars($provider['ProviderID'], $provider['DisplayName']);

            if (!empty($provider['ProgramName'])) {
                $cleanProgramName = preg_replace('/[^A-Za-z ]/', '', $provider['ProgramName']);
                $cleanName = preg_replace('/[^A-Za-z ]/', '', $provider['Name']);
                $cleanDisplayName = preg_replace('/[^A-Za-z ]/', '', $provider['DisplayName']);

                if (false === stripos($cleanName, $cleanProgramName)
                    && false === stripos($cleanDisplayName, $cleanProgramName)) {
                    $provider['ExtendedProgramName'] = $provider['ProgramName'];
                }
            }
        }

        return $providers;
    }

    private function getUrlbyName($name)
    {
        $sLink = preg_replace("/[^a-zA-Zа-яёА-ЯЁ\.]/ums", "-", $name);
        $sLink = preg_replace("/\-{2,}/ims", "-", $sLink);
        $sLink = preg_replace("/^\-|\-$/ims", "", $sLink);

        return $sLink;
    }
}
