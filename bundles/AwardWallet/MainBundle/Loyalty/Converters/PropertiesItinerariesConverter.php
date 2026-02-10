<?php

namespace AwardWallet\MainBundle\Loyalty\Converters;

use AwardWallet\Common\API\Converter\V2\Loader;
use AwardWallet\Common\Parsing\Solver\Exception as SolverException;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Extra\ProviderData;
use AwardWallet\Common\Parsing\Solver\MasterSolver;
use AwardWallet\Common\Parsing\Solver\MissingDataException;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Loyalty\Filters\ItineraryFilter;
use AwardWallet\Schema\Itineraries\Itinerary;
use AwardWallet\Schema\Parser\Component\InvalidDataException;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\Component\Options;
use AwardWallet\Schema\Parser\Util\ArrayConverter;
use Psr\Log\LoggerInterface;

class PropertiesItinerariesConverter
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var MasterSolver
     */
    private $solver;

    public function __construct(LoggerInterface $logger, MasterSolver $solver)
    {
        $this->logger = $logger;
        $this->solver = $solver;
    }

    public function extractItinerariesFromProperties(Provider $provider, array $properties): array
    {
        $itineraries = [];

        foreach (['Itineraries' => 'T', 'Reservations' => 'R', 'Rentals' => 'L', 'Restaurants' => 'E', 'Parking' => 'P'] as $key => $kind) {
            if (isset($properties[$key])) {
                $itineraries = array_merge(
                    $itineraries,
                    array_filter(array_map(
                        function ($it) use ($kind) {
                            if (!is_array($it)) {
                                return null;
                            }

                            $it['Kind'] = $kind;

                            return $it;
                        },
                        $properties[$key]
                    ))
                );
            }
        }

        if (!is_array($itineraries)) {
            $this->logger->error("Expected itineraries as array, got " . print_r($itineraries, true));

            return [];
        }

        return $this->convertArrayToSchema($itineraries, $provider);
    }

    /**
     * @return Itinerary[]
     */
    public function convertArrayToSchema(array $itinerariesArray, Provider $provider): array
    {
        try {
            $master = new Master('itineraries', new Options());
            $master->addPsrLogger($this->logger);
            ArrayConverter::convertMaster(['Itineraries' => $itinerariesArray], $master);
            $master->checkValid();
        } catch (InvalidDataException $e) {
            $this->logger->notice($e->getMessage());

            return [];
        }
        $extra = new Extra();

        try {
            $extra->provider = ProviderData::fromArray([
                'Code' => $provider->getCode(),
                'ProviderID' => $provider->getProviderid(),
                'IATACode' => $provider->getIATACode(),
                'Kind' => $provider->getKind(),
                'ShortName' => $provider->getShortname(),
            ]);
            $extra->context->partnerLogin = 'awardwallet';
            $this->solver->solve($master, $extra);
        } catch (SolverException $e) {
            $this->logger->notice($e->getMessage());

            return [];
        } catch (MissingDataException $e) {
            $this->logger->notice($e->getMessage());

            return [];
        } catch (InvalidDataException $e) {
            $this->logger->critical($e->getMessage());

            return [];
        }

        if ($master->getNoItineraries() === true) {
            return \TAccountChecker::getNoItinerariesArray();
        }
        $loader = new Loader();
        $filter = new ItineraryFilter();
        $result = [];

        foreach ($master->getItineraries() as $itinerary) {
            $converted = $loader->convert($itinerary, $extra);

            if ($filter->filter($converted)) {
                $result[] = $converted;
            }
        }

        return $result;
    }
}
