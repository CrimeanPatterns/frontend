<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\CountryRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PopularityHandler
{
    public $unsupportedProviders;

    private $countries = [
        'US', // United States
        'CA', // Canada
        'AU', // Australia
        'GB', // United Kingdom
        'RU', // Russia
        'BR', // Brazil
        'PY', // Portugal
        'ES', // Spain
        'MX', // Mexico
        'DE', // Germany
        'CN', // China
    ];

    private EntityManagerInterface $em;

    private GeoLocation $geolocation;

    private RequestStack $request;

    private ProviderSource $providerSource;

    private CountryRepository $countryRep;

    private ProviderRepository $providerRep;

    public function __construct(
        EntityManagerInterface $manager,
        GeoLocation $geolocation,
        RequestStack $requestStack,
        ProviderSource $providerSource
    ) {
        $this->em = $manager;
        $this->geolocation = $geolocation;
        $this->request = $requestStack;
        $this->providerSource = $providerSource;
        $this->countryRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Country::class);
        $this->providerRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $this->unsupportedProviders = implode(',', Provider::BIG3_PROVIDERS);
    }

    public function startPopularityTransaction(?OutputInterface &$output = null)
    {
        foreach ($this->countries as $country) {
            $result = $this->em->getConnection()->executeQuery("
                SELECT
                    a.ProviderID as providerId,
                    c.CountryID as countryId,
                    count(a.AccountID) AS popularity
                FROM
                    Usr u
                INNER JOIN Country c ON u.CountryID = c.CountryID
                INNER JOIN Account a ON a.UserID = u.UserID
                WHERE
                    a.ProviderID IS NOT NULL
                AND c. CODE = '{$country}'
                GROUP BY
                    a.ProviderID, c.CountryID
                ORDER BY
                    Popularity DESC
            ");

            foreach ($result->fetchAll() as $row) {
                $this->em->getConnection()
                    ->prepare('INSERT INTO Popularity (ProviderID, CountryID, Popularity) VALUES (:pid, :cid, :popularity) ON DUPLICATE KEY UPDATE Popularity = :popularity')
                    ->execute(['pid' => $row['providerId'], 'cid' => $row['countryId'], 'popularity' => $row['popularity']]);
            }
            $output->write("Collected popularity for $country successfully... \n");
        }
    }

    public function getPopularPrograms(
        ?Usr $user = null,
        $filter = '',
        $orderBy = 'ORDER BY Popularity DESC, p.Accounts DESC',
        $stateFilter = null,
        bool $disableBig3Sorting = false
    ) {
        $ip = $this->request->getCurrentRequest()->getClientIp();
        $countryId = $this->geolocation->getCountryIdByIp($ip);
        $select = '';

        if (!$disableBig3Sorting) {
            $select = "IF(p.ProviderID IN ($this->unsupportedProviders), 1, 0) AS Down,";
        }

        return $this->providerSource->getListProvidersForAdding(
            $user,
            $filter,
            $orderBy,
            "
					p.ProviderID,
					p.Code as ProviderCode,
					p.DisplayName,
					p.ProgramName,
					p.Name,
					p.KeyWords,
					p.Kind,
   					p.Accounts AS OldPopularity,
                    (
                        SELECT pop.Popularity FROM Popularity pop 
                        WHERE pop.ProviderID = p.ProviderID AND pop.CountryID = '$countryId'
                    ) AS Popularity,
					$select
					p.Corporate
					",
            $stateFilter
        );
    }

    public function defineCountry(Usr &$usr, $byLastKnownIp = false)
    {
        if ($byLastKnownIp) {
            $ip = $usr->getLastKnownIp();
        } elseif ($this->request->getCurrentRequest()) {
            $ip = $this->request->getCurrentRequest()->getClientIp();
        }

        if (!empty($ip)) {
            $countryId = $this->geolocation->getCountryIdByIp($ip);
        }

        if (!empty($countryId)) {
            $usr->setCountryid($countryId);
        }
    }
}
