<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Entity\Stationcode;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAddressStationsCommand extends Command
{
    protected static $defaultName = 'aw:update-stations-address';

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        GoogleGeo $googleGeo
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->googleGeo = $googleGeo;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('update address in StationCode from google')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $google = $this->googleGeo;

        /** @var EntityManager $em */
        $em = $this->entityManager;

        $codes = $google_result = $no_result = $wrong_coord = [];
        $flushCounter = 0;
        $stationCodeRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Stationcode::class);
        /** @var Stationcode[] $result */
        $result = $stationCodeRep->findAll();

        foreach ($result as $i => $stationCode) {
            $address = null;

            $lat = $stationCode->getLat();
            $lng = $stationCode->getLng();

            if ($stationCode->getStationcode() === 'XHO') {
                $lat = 23.050;
                $lng = 114.083;
            } elseif ($stationCode->getStationcode() === 'QDH') {
                $lat = 51.1436;
                $lng = 0.87448;
            }

            if (!empty($lat) && !empty($lng)) {
                $name = null;
                $address = $google->FindReverseGeoTag($name, $lat, $lng);

                if (!empty($address)) {
                    $address = $google->FindGeoTag($address['Formatted']);
                }

                if ($address['CountryCode'] != $stationCode->getCountrycode()) {
                    $wrong_coord[$stationCode->getStationcode()][] = $address;
                    $address = $google->FindGeoTag($stationCode->getStationname() . ', ' . $stationCode->getCountrycode());
                    $wrong_coord[$stationCode->getStationcode()][] = $address;
                }
            } else {
                $address = $google->FindGeoTag($stationCode->getStationname());
            }

            if (isset($address) && !empty($address)) {
                if (isset($address['CountryCode']) && $address['CountryCode'] === $stationCode->getCountrycode()) {
                    if (!$input->getOption("dry-run")) {
                        $stationCode->setAddressLine($address['FoundAddress']);
                        $stationCode->setCityName($address['City']);
                        $stationCode->setStateName($address['State']);

                        if (isset($address['StateCode'])) {
                            $stationCode->setStateCode($address['StateCode']);
                        }
                        $stationCode->setCountry($address['Country']);
                        $stationCode->setPostalCode($address['PostalCode']);
                        $stationCode->setLat($address['Lat']);
                        $stationCode->setLng($address['Lng']);

                        $em->persist($stationCode);

                        $flushCounter++;

                        if ($flushCounter % 1000 == 0) {
                            $em->flush();
                            $em->clear();
                            $output->writeln("{$flushCounter} records processed...");
                        }
                    }
                } else {
                    $codes[] = $stationCode->getStationcode();
                    $google_result[$stationCode->getStationcode()] = $address;
                }
            } else {
                $codes[] = $stationCode->getStationcode();
                $no_result[] = $stationCode->getStationcode();
            }
        }

        if (!$input->getOption('dry-run')) {
            $em->flush();
        }

        if (!empty($codes)) {
            $output->writeln("send to manual search: " . count($codes));
            //            $output->writeln(var_export($codes, true));
            $output->writeln("google results: " . count($google_result));
            //            $output->writeln(var_export($google_result, true));
            $output->writeln("no result for codes: " . count($no_result));
            //            $output->writeln(var_export($no_result, true));
            $output->writeln("wrong coordinates:" . count($wrong_coord));
        //            $output->writeln(var_export($wrong_coord, true));
        } else {
            $output->writeln("everethings OK");
        }

        return 0;
    }
}
