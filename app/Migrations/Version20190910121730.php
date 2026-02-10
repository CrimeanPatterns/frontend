<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20190910121730 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $connection = $this->container->get('doctrine.orm.entity_manager')->getConnection();
        [$providers, $partners, $data] = $this->fetchData();

        foreach ($data as $col) {
            $row = [
                'SourceProviderID' => $providers[$col[1]],
                'TargetProviderID' => $partners[$col[0]],
                'SourceRate' => $col[3],
                'TargetRate' => $col[4],
            ];
            $connection->insert('TransferStat', $row);
        }
    }

    public function down(Schema $schema): void
    {
    }

    private function fetchData()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        // https://redmine.awardwallet.com/issues/17508#note-47         Transfer Ratios
        $csv = 'X	Program	Transferrable Currency	Type	Minimum Transfer	Receive	Value Multiplier	NOTES																			
Aeromexico (Club Premier)	Capital One	Airline	1000	750	0,75		1,3																		
Air Canada Aeroplan (Altitude)	Capital One	Airline	1000	750	0,75		3,2	2,4																	
Air France (Flying Blue)	Capital One	Airline	1000	750	0,75																				
Alitalia (MilleMiglia)	Capital One	Airline	1000	750	0,75																				
Avianca (LifeMiles)	Capital One	Airline	1000	750	0,75																				
Cathay Pacific (Asia Miles)	Capital One	Airline	1000	750	0,75																				
"Emirates (Skywards &amp; Business Rewards)"	Capital One	Airline	1000	500	0,50																				
Etihad Airways (Etihad Guest)	Capital One	Airline	1000	750	0,75																				
Eva Air (Infinity MileageLands)	Capital One	Airline	1000	750	0,75																				
Finnair Plus	Capital One	Airline	1000	750	0,75																				
Hainan Airlines (Fortune Wings Club)	Capital One	Airline	1000	750	0,75																				
JetBlue Airways (trueBlue)	Capital One	Airline	1000	500	0,50																				
Qantas (Frequent Flyer)	Capital One	Airline	1000	750	0,75																				
Qatar Airways (Qmiles)	Capital One	Airline	1000	750	0,75																				
Singapore Airlines (KrisFlyer)	Capital One	Airline	1000	500	0,50																				
                                                                                                    
Aer Lingus (AerClub)	Chase	Airline	1000	1000	1,00																				
Air France (Flying Blue)	Chase	Airline	1000	1000	1,00																				
British Airways (Executive Club)	Chase	Airline	1000	1000	1,00																				
Iberia Plus	Chase	Airline	1000	1000	1,00																				
JetBlue Airways (trueBlue)	Chase	Airline	1000	1000	1,00																				
Singapore Airlines (KrisFlyer)	Chase	Airline	1000	1000	1,00																				
Southwest Airlines (Rapid Rewards)	Chase	Airline	1000	1000	1,00																				
United Airlines (Mileage Plus)	Chase	Airline	1000	1000	1,00																				
Virgin Atlantic (Flying Club)	Chase	Airline	1000	1000	1,00																				
Hyatt (World of Hyatt)	Chase	Hotel	1000	1000	1,00																				
IHG Rewards Club	Chase	Hotel	1000	1000	1,00																				
Marriott Bonvoy	Chase	Hotel	1000	1000	1,00																				
                                                                                                    
Aer Lingus (AerClub)	Amex	Airline	1000	1000	1,00																				
Aeromexico (Club Premier)	Amex	Airline	1000	1600	1,60																				
Air Canada Aeroplan (Altitude)	Amex	Airline	1000	1000	1,00																				
Air France (Flying Blue)	Amex	Airline	1000	1000	1,00																				
Alitalia (MilleMiglia)	Amex	Airline	1000	1000	1,00																				
All Nippon Airways (ANA Mileage Club)	Amex	Airline	1000	1000	1,00																				
Avianca (LifeMiles)	Amex	Airline	1000	1000	1,00																				
British Airways (Executive Club)	Amex	Airline	1000	1000	1,00																				
Cathay Pacific (Asia Miles)	Amex	Airline	1000	1000	1,00																				
Delta Air Lines (SkyMiles)	Amex	Airline	1000	1000	1,00																				
EL AL Israel Airlines (Matmid)	Amex	Airline	1000	20	0,02																				
"Emirates (Skywards &amp; Business Rewards)"	Amex	Airline	1000	1000	1,00																				
Etihad Airways (Etihad Guest)	Amex	Airline	1000	1000	1,00																				
Hawaiian Airlines (HawaiianMiles)	Amex	Airline	1000	1000	1,00																				
Iberia Plus	Amex	Airline	1000	1000	1,00																				
JetBlue Airways (trueBlue)	Amex	Airline	250	200	0,80																				
Qantas (Frequent Flyer)	Amex	Airline	500	500	1,00																				
Singapore Airlines (KrisFlyer)	Amex	Airline	1000	1000	1,00																				
Virgin Atlantic (Flying Club)	Amex	Airline	1000	1000	1,00																				
Marriott Bonvoy	Amex	Hotel	1000	1000	1,00																				
Hilton (Honors)	Amex	Hotel	1000	2000	2,00																				
Choice Hotels (Choice Privileges)	Amex	Hotel	1000	1000	1,00																				
                                                                                                    
Air France (Flying Blue)	Citi	Airline	1000	1000	1,00																				
Avianca (LifeMiles)	Citi	Airline	1000	1000	1,00																				
Cathay Pacific (Asia Miles)	Citi	Airline	1000	1000	1,00																				
Etihad Airways (Etihad Guest)	Citi	Airline	1000	1000	1,00																				
Eva Air (Infinity MileageLands)	Citi	Airline	1000	1000	1,00																				
JetBlue Airways (trueBlue)	Citi	Airline	1000	1000	1,00																				
Malaysia Airlines (Enrich)	Citi	Airline	1000	1000	1,00																				
Qantas (Frequent Flyer)	Citi	Airline	1000	1000	1,00																				
Qatar Airways (Qmiles)	Citi	Airline	1000	1000	1,00																				
Singapore Airlines (KrisFlyer)	Citi	Airline	1000	1000	1,00																				
Thai Airways (Royal Orchid Plus)	Citi	Airline	1000	1000	1,00																				
"Turkish Airlines (Miles&amp;Smiles)"	Citi	Airline	1000	1000	1,00																				
Virgin Atlantic (Flying Club)	Citi	Airline	1000	1000	1,00																				
                                                                                                    
Aegean Airlines (Miles & Bonus)	Marriott	Airline	3000	1000	0,33																				
Aeroflot Bonus	Marriott	Airline	3000	1000	0,33																				
Aeromexico (Club Premier)	Marriott	Airline	3000	1000	0,33																				
Air Canada Aeroplan (Altitude)	Marriott	Airline	3000	1000	0,33																				
Air China (PhoenixMiles)	Marriott	Airline	3000	1000	0,33																				
Air France (Flying Blue)	Marriott	Airline	3000	1000	0,33																				
Air New Zealand (Airpoints)	Marriott	Airline	3120	16	0,01																				
Alaska Airlines (Mileage Plan)	Marriott	Airline	3000	1000	0,33																				
Alitalia (MilleMiglia)	Marriott	Airline	3000	1000	0,33																				
All Nippon Airways (ANA Mileage Club)	Marriott	Airline	3000	1000	0,33																				
American Airlines (AAdvantage)	Marriott	Airline	3000	1000	0,33																				
Asiana Airlines (Asiana Club)	Marriott	Airline	3000	1000	0,33																				
Avianca (LifeMiles)	Marriott	Airline	3000	1000	0,33																				
British Airways (Executive Club)	Marriott	Airline	3000	1000	0,33																				
Cathay Pacific (Asia Miles)	Marriott	Airline	3000	1000	0,33																				
China Eastern (Eastern Miles)	Marriott	Airline	3000	1000	0,33																				
China Southern (Sky Pearl)	Marriott	Airline	3000	1000	0,33																				
Copa Airlines ConnectMiles (Prefer)	Marriott	Airline	3000	1000	0,33																				
Delta Air Lines (SkyMiles)	Marriott	Airline	3000	1000	0,33																				
"Emirates (Skywards &amp; Business Rewards)"	Marriott	Airline	3000	1000	0,33																				
Etihad Airways (Etihad Guest)	Marriott	Airline	3000	1000	0,33																				
Frontier Airlines (EarlyReturns)	Marriott	Airline	3000	1000	0,33																				
Hainan Airlines (Fortune Wings Club)	Marriott	Airline	3000	1000	0,33																				
Hawaiian Airlines (HawaiianMiles)	Marriott	Airline	3000	1000	0,33																				
Iberia Plus	Marriott	Airline	3000	1000	0,33																				
Japan Airlines (JMB)	Marriott	Airline	3000	1000	0,33																				
Jet Airways (JetPrivilege)	Marriott	Airline	3000	500	0,17		1,2	0,204																	
JetBlue Airways (trueBlue)	Marriott	Airline	3000	1000	0,33																				
Korean Air (SkyPass)	Marriott	Airline	3000	1000	0,33		5,2	1,733333333																	
LATAM (LATAM Pass)	Marriott	Airline	3000	1000	0,33																				
Qantas (Frequent Flyer)	Marriott	Airline	3000	1000	0,33																				
Qatar Airways (Qmiles)	Marriott	Airline	3000	1000	0,33																				
Saudi Arabian Airlines (Alfursan)	Marriott	Airline	3000	1000	0,33																				
Singapore Airlines (KrisFlyer)	Marriott	Airline	3000	1000	0,33																				
South African Airways (Voyager)	Marriott	Airline	3000	1000	0,33																				
Southwest Airlines (Rapid Rewards)	Marriott	Airline	3000	1000	0,33																				
"TAP Portugal (Miles&amp;Go)"	Marriott	Airline	3000	1000	0,33																				
Thai Airways (Royal Orchid Plus)	Marriott	Airline	3000	1000	0,33																				
"Turkish Airlines (Miles&amp;Smiles)"	Marriott	Airline	3000	1000	0,33																				
United Airlines (Mileage Plus)	Marriott	Airline	3000	1000	0,33																				
Virgin Atlantic (Flying Club)	Marriott	Airline	3000	1000	0,33																				
';

        // assign name by column-2
        $providers = [
            'Capital One' => 104,
            'Chase' => 87,
            'Amex' => 84,
            'Citi' => 364,
            'Marriott' => 17,
        ];

        // assign name by column-1
        $partners = [];
        $data = [];
        $notfoundPartner = [];
        $csv = explode("\n", trim($csv));

        foreach ($csv as $col) {
            $cols = explode("	", trim($col));
            $cols = array_map('trim', $cols);

            if ('X' === $cols[0] || empty($cols[0])) {
                continue;
            }

            $providerName = trim($cols[0], '"');
            //$provider = $entityManager->getRepository(Provider::class)->findProviderByContainsText($providerName);
            $quoteName = $entityManager->getConnection()->quote($providerName);
            $provider = $entityManager->getConnection()->fetchAssoc('select ProviderID from Provider where (DisplayName LIKE ' . $quoteName . ' OR Name LIKE ' . $quoteName . ')');

            if (empty($provider)) {
                $quoteName = $entityManager->getConnection()->quote(htmlentities($providerName));
                $provider = $entityManager->getConnection()->fetchAssoc('select ProviderID from Provider where (DisplayName LIKE ' . $quoteName . ' OR Name LIKE ' . $quoteName . ')');
            }

            if (is_array($provider) && array_key_exists('ProviderID', $provider)) {
                $partners[$cols[0]] = $provider['ProviderID'];
                $data[] = $cols;
            } else {
                $notfoundPartner[] = $providerName;
            }

            if (!isset($providers[$cols[1]])) {
                throw new \Exception('Unknown provider');
            }

            if (empty($cols[5])) {
                throw new \Exception('Bad multiplier');
            }
        }

        echo 'Not found partners: ', implode('; ', $notfoundPartner), PHP_EOL;

        return [
            $providers,
            $partners,
            $data,
        ];
    }
}
