<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch;

use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\Google\GoogleRequestFailedException;
use AwardWallet\Common\Geo\Google\PlaceDetails;
use AwardWallet\Common\Geo\Google\PlaceDetailsParameters;
use AwardWallet\Common\Geo\Google\PlaceTextSearchParameters;
use Doctrine\DBAL\Connection;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HotelsFillLinkCommand extends Command
{
    public const CACHE_SKIP_PREFIX = 'hotelsFillWebsiteSkip2_';
    public static $defaultName = 'aw:hotels-fill-link';

    private Connection $connection;
    private \Memcached $memcached;
    private GoogleApi $googleApi;
    private SerializerInterface $serializer;

    /*
    private const REQUEST_TIMEOUT = 5;
    private const SEARCH_PAUSE = 3;
    private const ATTEMPTS_COUNT_SWITCH = 10;
    private const MODE_RANDOM = 'random';
    private const MODE_POINT_DESC = 'pointdesc';
    private const HOTELS_LIMIT = 100;

    private const IS_ILLUMINATI = true;


    protected int $attempt = 0;

    private OutputInterface $output;
    private string $mode = self::MODE_POINT_DESC;

    private bool $skip = false;
    private ContainerInterface $services;
    */

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        \Memcached $memcached,
        GoogleApi $googleApi,
        SerializerInterface $serializer
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
        $this->memcached = $memcached;
        $this->googleApi = $googleApi;
        $this->serializer = $serializer;
    }

    public function configure()
    {
        parent::configure();
        // $this->addOption('mode', null, InputOption::VALUE_OPTIONAL, '"' . self::MODE_RANDOM . '" or "' . self::MODE_POINT_DESC . '"');
        $this->addOption('skip', null, InputOption::VALUE_OPTIONAL, 'Skip recently checked');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /*
        $this->mode = in_array($input->getOption('mode'), [self::MODE_RANDOM, self::MODE_POINT_DESC])
            ? $input->getOption('mode')
            : self::MODE_POINT_DESC;
        $this->output = $output;
        */
        $this->skip = 'false' === $input->getOption('skip');

        $this->fillDataFromGoogle($output);
        // $this->postProcess($output); // check & clean exists website links
    }

    private function fillDataFromGoogle(OutputInterface $output): void
    {
        $hotels = $this->connection->fetchAllAssociative('
            SELECT a.*, h.Matches
            FROM (
                SELECT HotelID, Name, Address, ProviderID, GooglePlaceDetails, Website, Phones, GoogleUpdateAttempts
                FROM Hotel
                WHERE
                        GoogleUpdateAttempts < 10
                    AND (
                            Website IS NULL
                         OR Website = \'\'
                         OR Phones IS NULL
                         OR Phones = \'\'
                    )
                    AND (
                            GoogleUpdateDate IS NULL
                        OR  TIMESTAMPDIFF(MONTH, GoogleUpdateDate, NOW()) >= 6
                    )
            ) a JOIN Hotel h ON (h.HotelID = a.HotelID)
        ');

        $output->writeln('Match ' . count($hotels) . ' hotels with empty website or phones');
        $index = 0;
        $found = ['website' => 0, 'phones' => 0];

        foreach ($hotels as $hotel) {
            $hotelId = (int) $hotel['HotelID'];
            $hotelName = $hotel['Name'];
            $hotelAddress = $hotel['Address'];

            if (++$index % 100 === 0) {
                $output->writeln('processed ' . $index . '...');
            }

            if ($this->isSkipHotel($hotelId)) {
                $output->writeln(' - skipped [' . $hotelId . '], ' . $hotelName);

                continue;
            }
            $this->setSkipHotel($hotelId);

            $placeDetails = $results = null;
            $updateData = [
                'GoogleUpdateDate' => date('Y-m-d H:i:s'),
                'GoogleUpdateAttempts' => ++$hotel['GoogleUpdateAttempts'],
            ];

            if (!empty($hotel['GooglePlaceDetails'])) {
                /** @var PlaceDetails $placeDetails */
                $placeDetails = $this->serializer->deserialize(
                    $hotel['GooglePlaceDetails'],
                    PlaceDetails::class,
                    'json'
                );
                $placeId = $placeDetails->getPlaceId();

                if (!empty($hotel['Website']) && !empty($hotel['Phones'])) {
                    throw new \Exception('Error in sql on fetch, hotelId: ' . $hotelId);
                }

                if (empty($placeId)) {
                    throw new \Exception('Error in data GooglePlaceDetails, hotelId: ' . $hotelId);
                }

                $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);

                try {
                    $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
                } catch (GoogleRequestFailedException $e) {
                    $output->writeln('GoogleRequest failed by "place_id" = ' . $placeId);
                }
            } else {
                $placesResponse = $this->googleApi->placeTextSearch(
                    PlaceTextSearchParameters::makeFromQuery(urldecode($hotelName . ' ' . $hotelAddress))
                        ->setType(GoogleApi::PLACE_TYPE_LODGING)
                        ->setLanguage('en')
                );

                $results = $placesResponse->getResults();

                if (1 === count($results)) {
                    $placeId = $results[0]->getPlaceId();
                    $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);

                    try {
                        $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
                    } catch (GoogleRequestFailedException $e) {
                        $output->writeln('GoogleRequest failed by "place_id" = ' . $placeId);
                    }
                }
            }

            $matches = json_decode($hotel['Matches']);

            if (empty($placeDetails) && null !== $results && !empty($matches)) {
                $this->logger->info('HotelsFillLinks: found multiple results, hotelId: ' . $hotelId . ', "' . $hotelName . ' ' . $hotelAddress . '"');

                foreach ($results as $result) {
                    $placeId = $result->getPlaceId();

                    $scanPlaceDetails = $this->googleApi
                        ->placeDetails(PlaceDetailsParameters::makeFromPlaceId($placeId))
                        ->getResult();

                    if (null !== $scanPlaceDetails) {
                        $lat = $scanPlaceDetails->getGeometry()->getLocation()->getLat();
                        $lng = $scanPlaceDetails->getGeometry()->getLocation()->getLng();

                        foreach ($matches as $match) {
                            if (
                                $hotelAddress === $scanPlaceDetails->getFormattedAddress()
                                || $hotelAddress === $scanPlaceDetails->getAdrAddress()
                                || $hotelAddress === strip_tags($scanPlaceDetails->getAdrAddress())
                                || (!empty($match->Address) && (
                                    $match->Address === $scanPlaceDetails->getFormattedAddress()
                                    || $match->Address === $scanPlaceDetails->getAdrAddress()
                                ))
                                || ($lat == $match->Lat && $lng == $match->Lng)
                            ) {
                                $placeDetails = $scanPlaceDetails;

                                break 2;
                            }
                        }

                        if (empty($placeDetails) && !empty($hotel['Phones']) && $hotel['Phones'] === $scanPlaceDetails->getInternationalPhoneNumber()) {
                            $placeDetails = $scanPlaceDetails;

                            break;
                        }
                    }
                }
            }

            if (null !== $placeDetails) {
                $updateData['GooglePlaceDetails'] = $this->serializer->serialize($placeDetails, 'json');

                $phone = trim($placeDetails->getInternationalPhoneNumber() ?? '');

                if (!empty($phone)) {
                    $updateData['Phones'] = $phone;
                    $found['phones']++;
                }

                $website = trim($this->cleanUrlQuery($placeDetails->getWebsite() ?? ''));

                if (!empty($website)) {
                    $updateData['Website'] = $website;
                    $found['website']++;
                }
            }

            $this->connection->update('Hotel', $updateData, ['HotelID' => $hotelId]);
        }

        $output->writeln([
            '--',
            'done.',
            'found phones: ' . $found['phones'],
            'found website: ' . $found['website'],
        ]);

        $withoutWebsite = $this->connection->fetchOne("SELECT COUNT(*) FROM Hotel WHERE (Website IS NULL OR Website = '')");
        $withoutPhones = $this->connection->fetchOne("SELECT COUNT(*) FROM Hotel WHERE (Phones IS NULL OR Phones = '')");
        $output->writeln([
            '==',
            'Hotels without website: ' . $withoutWebsite,
            'Hotels without phones: ' . $withoutPhones,
        ]);
    }

    private function postProcess(OutputInterface $output): bool
    {
        $hotels = $this->connection->fetchAllAssociative("SELECT HotelID, Website FROM Hotel WHERE Website LIKE '%?%'");

        foreach ($hotels as $hotel) {
            $link = $this->cleanUrlQuery($hotel['Website']);
            $this->connection->update('Hotel', ['Website' => $link], ['HotelID' => $hotel['HotelID']]);
            $output->writeln($hotel['HotelID'] . ': [' . $hotel['Website'] . '] ==> [' . $link . ']');
        }

        return true;
    }

    private function cleanUrlQuery($url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $parse = parse_url($url);

        if (!array_key_exists('path', $parse)) {
            $parse['path'] = '';
        }
        $queryParams = [];

        if (!empty($parse['query'])) {
            parse_str($parse['query'], $parseQuery);
        }

        if (!empty($parseQuery)) {
            foreach ($parseQuery as $qVar => $qVal) {
                if (0 === strpos($qVar, 'utm_')
                    || in_array($qVar, [
                        'y_source',
                        'src',
                        'scid',
                        'SEO_id',
                        '?SEO_id',
                        'cm_mmc',
                        'WT_mc_id',
                        'propertyID',
                        'trackingSource',
                        'CID',
                        'defaultTab',
                        'partner',
                        'SWAQ',
                        'mc',
                        'NCK',
                        'merchantid',
                        'sourceid',
                    ])) {
                    continue;
                }
                $queryParams[$qVar] = $qVal;
            }
        }

        $result = $parse['scheme'] . '://' . $parse['host'];

        if (!empty(trim($parse['path'], '/'))) {
            $result .= '/' . trim($parse['path'], '/');
        }

        if (!empty($queryParams)) {
            $result .= '?' . http_build_query($queryParams);
            $this->logger->info('check QueryParams :: ' . $result);
        }

        return $result;
    }

    private function isSkipHotel(int $hotelId): bool
    {
        return !$this->skip && !empty($this->memcached->get(self::CACHE_SKIP_PREFIX . $hotelId));
    }

    private function setSkipHotel(int $hotelId): bool
    {
        return $this->memcached->set(self::CACHE_SKIP_PREFIX . $hotelId, true, 86400 * 14);
    }

    /*
    protected function getCurlDriver(): \CurlDriver
    {
        return $this->curlDriver;
    }

    protected function initBrowserSettings(): void
    {
        $this->http->maxRequests = 500;
        $this->http->TimeLimit = 290;
    }

    private function hilton(): void
    {
        $this->initCurl();
        $this->logger2->info(strtoupper(__FUNCTION__));
        $autocompleteSearchResultUri = 'https://www.hilton.com/dx-customer/autocomplete';
        $searchHotelsListUri = 'https://www.hilton.com/en/search/hilton-honors/';

        $hiltonHotels = $this->fetchListHotels(Provider::HILTON_ID);
        $attemptsCount = 0;

        foreach ($hiltonHotels as $rowHiltonHotel) {
            $hotelId = (int) $rowHiltonHotel['HotelID'];

            if ($this->isSkipHotel($hotelId)) {
                echo 'skipped [' . $hotelId . '], ' . $rowHiltonHotel['Name'] . PHP_EOL;

                continue;
            }
            $this->setSkipHotel($hotelId);
            $hotelName = $rowHiltonHotel['Name'];
            $matches = json_decode($rowHiltonHotel['Matches']);

            // alternative names
            $extend = [];
            $hotelNameStrLength = strlen($hotelName);

            if (' OK' === substr($hotelName, -3)) {
                $extend[] = (object) ['HotelName' => substr($hotelName, 0, $hotelNameStrLength - 3)];
            }

            foreach ($matches as $match) {
                $extend[] = (object) [
                    'HotelName' => str_replace(' by Hilton ', ' by Hilton Hotel ', $match->HotelName),
                ];
            }
            $extend[] = (object) ['HotelName' => str_replace(' by Hilton ', ' by Hilton Hotel ', $hotelName)];

            $matches = array_merge($matches, $extend);
            // alternative names

            $this->logger2->info('HotelID: ' . $hotelId . ', HotelName: <' . $hotelName . '>');

            if (++$attemptsCount === self::ATTEMPTS_COUNT_SWITCH) {
                $attemptsCount = 0;
                $this->http->cleanup();
                $this->initCurl(true);
            }

            $url = $autocompleteSearchResultUri . '?'
                . http_build_query([
                    'input' => $hotelName,
                    'language' => 'en',
                ]);
            $response = $this->sendRequest($url);
            $list = json_decode($response->body);

            if (empty($list->predictions)) {
                $this->logger2->info('Empty autocomplete Response for "' . $hotelName . '" (' . $url . ')');

                continue;
            }

            $hotelData = [];
            $matchHotelData = [];

            foreach ($list->predictions as $hotel) {
                if ('property' === $hotel->type
                    && 0 === strpos($hotel->place_id, 'dx-hotel:')) {
                    if ($this->cleanString($hotelName) === $this->cleanString($hotel->structured_formatting->main_text)) {
                        $hotelData[] = $hotel;
                    }

                    foreach ($matches as $match) {
                        if ($this->cleanString($match->HotelName) === $this->cleanString($hotel->structured_formatting->main_text)
                        ) {
                            $matchHotelData[] = $hotel;
                        }
                    }
                }
            }

            if (empty($hotelData) && !empty($matchHotelData)) {
                $matchedAlternative = true;
                $hotelData = $matchHotelData;
            }

            if (empty($hotelData)) {
                $this->logger2->info('Search in autocomplete Hilton NOT FOUND for [' . $hotelId . '] "' . $hotelName . '" <' . $url . '>');

                continue;
            }

            if (count($hotelData) > 1 && !isset($matchedAlternative)) {
                $this->logger2->info(
                    'Multiple found for "' . $hotelName . '" (matchedAlternative: ' . var_export(
                        isset($matchedAlternative),
                        true
                    ) . ')'
                );

                continue;
            }

            $hotel = $hotelData[0];
            $query = $hotel->structured_formatting->main_text . ', ' . $hotel->address->city . ', ' . $hotel->address->country;

            $url = $searchHotelsListUri . '?'
                . http_build_query([
                    'query' => $query,
                    'placeId' => $hotel->place_id,
                ]);
            $response = $this->sendRequest($url);
            $html = $response->body;
            $this->logger2->info('json url: ' . $url);

            $pattern = "#<\s*?script id\b[^>]*>(.*?)</script\b[^>]*>#s";
            preg_match($pattern, $html, $matches);

            if (empty($matches[1])) {
                //$pattern = '/<script id="__NEXT_DATA?.*>(.*)<\/script>/s';
                //preg_match($pattern, $html, $matches);
                $this->logger2->info('Hilton script JSON not found (' . $url . ')');

                continue;
            }
            $found = json_decode(trim($matches[1]));

            if (!empty($found)) {
                if (isset($found->props->pageProps->initialData->locationPage->hotelSummaryOptions->hotels)) {
                    $hotels = $found->props->pageProps->initialData->locationPage->hotelSummaryOptions->hotels;
                } elseif (isset($found->props->initialProps->pageProps->initialData->locationPage->hotelSummaryOptions->hotels)) {
                    $hotels = $found->props->initialProps->pageProps->initialData->locationPage->hotelSummaryOptions->hotels;
                } elseif (isset($found->props->initialState->hotels->hotels)) {
                    $hotels = $found->props->initialState->hotels->hotels;
                } else {
                    $hotels = null;
                    $queries = $found->props->pageProps->dehydratedState->queries;
                    foreach ($queries as $query) {
                        if (isset($query->state->data->geocode->hotelSummaryOptions->hotels)) {
                            $hotels = $query->state->data->geocode->hotelSummaryOptions->hotels;
                        }
                    }
                }

                if (empty($hotels)) {
                    $this->logger2->info('hilton: "hotels" key not found');
                    continue;
                }

                $hotelPlaceId = strtoupper(substr($hotel->place_id, 10));

                if (is_array($hotels)) {
                    foreach ($hotels as $_hotel) {
                        if ($_hotel->_id === strtoupper($hotelPlaceId)) {
                            $website = $_hotel->facilityOverview->homeUrl;
                            $this->setHotelWebsite(Provider::HILTON_ID, $hotelId, $hotelName, $website);
                            break;
                        }
                    }
                    foreach ($hotels as $_hotel) {
                        foreach ($matches as $match) {
                            //if ($this->cleanString($match->HotelName) === $this->cleanString($_hotel->name)
                            //    && isset($_hotel->facilityOverview->homeUrl)) {
                            //    $website = $_hotel->facilityOverview->homeUrl;
                            //    $this->setHotelWebsite(Provider::HILTON_ID, $hotelId, $hotelName, $website);
                            //    break 2;
                            //}
                        }
                    }
                } elseif (property_exists($hotels, $hotelPlaceId)) {
                    $website = $hotels->{$hotelPlaceId}->facilityOverview->homeUrl;
                    $this->setHotelWebsite(Provider::HILTON_ID, $hotelId, $hotelName, $website);
                } else {
                    throw new \Exception('hotels data is wrong [' . $hotelId . ']');
                }
            }

            if (is_object($this->http) && method_exists($this->http, 'setRandomUserAgent')) {
                $this->http->setRandomUserAgent();
            }

            sleep(self::SEARCH_PAUSE);
        }
    }

    private function marriottSelenium()
    {
        $this->logger2->info(strtoupper(__FUNCTION__));

        $driver = $this->getSeleniumWebDriver(\SeleniumFinderRequest::BROWSER_CHROMIUM);
        if (null === $driver) {
            throw new \Exception('Selenium not initialized');
        }

        $listHotels = $this->fetchListHotels(Provider::MARRIOTT_ID);
        $attemptsCount = 0;
        foreach ($listHotels as $rowHotel) {
            $hotelId = (int) $rowHotel['HotelID'];

            if ($this->isSkipHotel($hotelId)) {
                continue;
            }
            $this->setSkipHotel($hotelId);
            $hotelName = $rowHotel['Name'];
            $matches = json_decode($rowHotel['Matches']);

            $this->logger2->info('HotelID: ' . $hotelId . ', HotelName: <' . $hotelName . '>');

            if (++$attemptsCount === self::ATTEMPTS_COUNT_SWITCH) {
                $attemptsCount = 0;
                $this->http->cleanup();
                $driver = $this->getSeleniumWebDriver(\SeleniumFinderRequest::BROWSER_CHROMIUM, null, true);
            }

            try {
                $driver->get('https://www.marriott.com/default.mi');
            } catch (\TimeOutException|\ScriptTimeoutException $e) {
                $this->logger2->info("Exception: " . $e->getMessage());
                $driver->executeScript('window.stop();');
            }

            sleep(5);

            $hotelInput = $driver->findElement(\WebDriverBy::cssSelector('input[name="destinationAddress.destination"]'));
            //$this->saveResponse();

            if (!$hotelInput) {
                $this->logger2->info('input field not found');

                return false;
            }

            //$hotelInput->click();
            //$hotelInput->sendKeys('');

            //$hotelInput->sendKeys($hotelName);
            $driver->executeScript("
                jQuery('input[name=\"destinationAddress.destination\"]').val('" . $this->cleanString($hotelName) . "');
            ");
            sleep(2);
            $driver->executeScript("jQuery('div.l-hsearch-find-homepage button').click();");

            sleep(10);
            $elements = $this->waitForElement(\WebDriverBy::cssSelector('div.property-record-item'), 10);
            if (!$elements) {
                $this->logger2->info('block with list hotels not found');

                return false;
            }

            $elements = $driver->findElements(\WebDriverBy::cssSelector('div.property-record-item'));
            $website = null;
            $originCleanHotelName = $this->cleanString($hotelName);

            $_replace = ['by Marriott'];
            foreach ($elements as $item) {
                try {
                    $blockHotelName = $item->findElement(\WebDriverBy::cssSelector('span.l-property-name'));
                } catch (\Exception $e) {
                    $this->logger2->info('blockHotelName not found');
                    continue;
                }
                $cleanBlockHotelName = $this->cleanString($blockHotelName->getText());

                $isFoundMatch = false;
                foreach ($matches as $match) {
                    if ($originCleanHotelName === $this->cleanString($match->HotelName)
                        || $originCleanHotelName === $this->cleanString($match->AlternativeHotelName)
                        || $this->cleanString(str_replace($_replace, '',
                            $originCleanHotelName)) === $this->cleanString(str_replace($_replace, '',
                            $match->HotelName))
                        || $this->cleanString(str_replace($_replace, '',
                            $originCleanHotelName)) === $this->cleanString(str_replace($_replace, '',
                            $match->AlternativeHotelName))
                    ) {
                        $isFoundMatch = true;
                    }
                }

                if ($isFoundMatch
                    || $originCleanHotelName === $cleanBlockHotelName
                    || $this->cleanString(str_replace($_replace, '',
                        $originCleanHotelName)) === $this->cleanString(str_replace($_replace, '', $cleanBlockHotelName))
                ) {
                    $linkOpen = $item->findElement(\WebDriverBy::cssSelector('a.js-hotel-quickview-link'), 5);

                    if ($linkOpen) {
                        $hotelLink = $linkOpen->getAttribute('href');
                        $this->http->NormalizeURL($hotelLink);

                        $driver->get($hotelLink);
                        sleep(10);

                        $hotelNameFound = false;
                        try {
                            $hotelNameFound = $driver->findElement(\WebDriverBy::xpath("//*[contains(text(), '" . $hotelName . "')]"));
                        } catch (\Exception $e) {
                        }
                        if (!$hotelNameFound) {
                            $this->logger2->info('not found hotel name on page');
                            continue;
                        }

                        $learnMore = $driver->findElement(\WebDriverBy::cssSelector('a.learn-more-link'));
                        if ($learnMore) {
                            $website = $learnMore->getAttribute('href');
                            break;
                        }

                        $hotelNameLink = $driver->findElement(\WebDriverBy::cssSelector('a.t-alt-link'));
                        if (!$hotelNameLink) {
                            $this->logger2->info('head hotelname not found');
                            continue;
                        }
                        $website = $hotelNameLink->getAttribute('href');

                        //$popupHotelName = $driver->findElement(\WebDriverBy::)
                        //$popupHotelName = $this->cleanString($popupHotelName->getText());

                        //if ($this->cleanString($popupHotelName) === $this->cleanString($hotelName)) {
                            //$link = $driver->findElement(\WebDriverBy::cssSelector('div.t-hotel-website a'));

                            //if ($link) {
                                //$website = $link->getAttribute('href');
                            //} else {
                                //$link = $driver->findElement(\WebDriverBy::xpath("//a[contains(text(), 'View Hotel Website')]"));
                                //if ($link) {
                                    //$website = $link->getAttribute('href');
                                //}
                            //}
                        //}
                    }
                }
            }

            if (!empty($website)) {
                $this->setHotelWebsite(Provider::MARRIOTT_ID, $hotelId, $hotelName, $website);
            } else {
                $this->logger2->info('HotelFillLink NotFound - HotelID: ' . $hotelId . '; HotelName: "' . $hotelName . '"',
                    ['hotelId' => $hotelId, 'hotelName' => $hotelName]);
            }

            sleep(self::SEARCH_PAUSE);
        }
    }

    private function marriott(): void
    {
        $this->logger2->info(strtoupper(__FUNCTION__));

        $autocompleteSearchResultUri = 'https://www.marriott.com/aries-search/v2/autoComplete.comp';
        $searchUrl = 'https://www.marriott.com/aries-search/v2/autoComplete.comp';

        $hotels = $this->fetchListHotels(Provider::MARRIOTT_ID);;

        $setRequestHeaders = [
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4501.0 Safari/537.36 Edg/92.0.891.1',
            'Upgrade-Insecure-Requests' => 1,
            'x-xss-protection' => '1; mode=block',
            //'x-requested-with' => 'XMLHttpRequest',
        ];

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);

        $attemptsCount = 0;

        foreach ($hotels as $rowHotel) {
            $hotelId = (int) $rowHotel['HotelID'];

            if ($this->isSkipHotel($hotelId)) {
                echo 'skipped [' . $hotelId . '], ' . $rowHotel['Name'] . PHP_EOL;

                continue;
            }
            $this->setSkipHotel($hotelId);
            $hotelName = $rowHotel['Name'];
            $matches = json_decode($rowHotel['Matches']);
            $uuid = $this->generateUuid();

            $this->logger2->info('HotelID: ' . $hotelId . ', HotelName: <' . $hotelName . '>');

            if (++$attemptsCount === self::ATTEMPTS_COUNT_SWITCH) {
                $attemptsCount = 0;
                $this->http->cleanup();
                $this->initCurl(true);
            }

            $url = $autocompleteSearchResultUri . '?' . http_build_query([
                    'searchTerm' => $hotelName,
                    'suggestionSortOrder' => 'city,property,airport,poi,state,country',
                    'latitude' => '0',
                    'longitude' => '0',
                    'uuid' => $uuid,
                ]);
            $response = $this->sendRequest($url, $setRequestHeaders);
            $list = json_decode($response->body, true);

            if (empty($list['suggestions'])) {
                $this->logger2->info(
                    'Search in autocomplete Marriott NOT FOUND for [' . $hotelId . '] "' . $hotelName . '" <' . $url . '>'
                );

                continue;
            }

            $hotelData = [];
            $matchHotelData = [];

            $list = $list['suggestions'];

            foreach ($list as $hotel) {
                if ($this->cleanString($hotel['primaryDescription']) === $this->cleanString($hotelName)) {
                    $hotelData[] = $hotel;
                }

                foreach ($matches as $match) {
                    if ($this->cleanString($match->HotelName) === $this->cleanString($hotel['primaryDescription'])) {
                        $matchHotelData[] = $hotel;
                    }
                }
            }

            if (empty($hotelData) && empty($matchHotelData)) {
                $replaced = [
                    'by Marriott Hotel' => 'Hotel by Marriott',
                    'Residence Inn' => 'Residence Inn by Marriott',
                ];

                foreach ($replaced as $searchWords => $replaceWords) {
                    $tmpName = str_replace($searchWords, $replaceWords, $hotelName);

                    foreach ($list as $hotel) {
                        if ($this->cleanString($hotel['primaryDescription']) === $this->cleanString($tmpName)) {
                            $hotelData[] = $hotel;
                        }

                        foreach ($matches as $match) {
                            $tmp2Name = str_replace($searchWords, $replaceWords, $match->HotelName);

                            if ($this->cleanString($tmp2Name) === $this->cleanString($hotel['primaryDescription'])) {
                                $matchHotelData[] = $hotel;
                            }
                        }
                    }
                }
            }

            if (empty($hotelData) && !empty($matchHotelData)) {
                if (1 === count($matchHotelData)) {
                    $hotelData[] = $matchHotelData[0];
                } else {
                    $this->logger2->info('Multiple MATCH found for "' . $hotelName . '" ');

                    continue;
                }
            }

            if (empty($hotelData)) {
                $this->logger2->info('Hotel not found [' . $hotelId . '] "' . $hotelName . '" <' . $url . '>');

                continue;
            }

            $url = $searchUrl . '?'
                . http_build_query([
                    'placeId' => $hotelData[0]['placeId'],
                    'uuid' => $uuid,
                ]);
            $response = $this->sendRequest($url, $setRequestHeaders);
            $list = json_decode($response->body, true);

            if (empty($list['suggestions'][0])) {
                $this->logger2->info('Hotel Details info not found for [' . $hotelId . '] "' . $hotelName . '" <' . $url . '>');

                continue;
            }

            $found = $list['suggestions'][0];

            if (empty($found['details']['location']['property'])) {
                $this->logger2->info('Empty location property for [' . $hotelId . '] ' . $hotelName);

                continue;
            }

            $pageInfoUrl = 'https://www.marriott.com/search/hotelQuickView.mi?'
                . http_build_query([
                    'propertyId' => $found['details']['location']['property'],
                    'brandCode' => $found['details']['location']['brand'],
                    'marshaCode' => $found['details']['location']['property'],
                ]);
            $response = $this->sendRequest($pageInfoUrl, $setRequestHeaders);

            $doc->loadHTML('<?xml encoding="UTF-8">' . trim($response->body));
            $xpath = new \DOMXPath($doc);

            // //a[contains(text(), 'View Hotel Website')]/@href
            $websiteLink = $xpath->query("//a[text()='Learn more']/@href");

            if (!$websiteLink->length) {
                $this->logger2->info('Link not found for uri "' . $pageInfoUrl . '"');

                continue;
            }

            $website = substr(trim($websiteLink[0]->C14N()), 5); // href=
            $website = trim($website, '"=\'');
            $website = 'https://www.marriott.com/' . trim($website, '/');

            $this->setHotelWebsite(Provider::MARRIOTT_ID, $hotelId, $hotelName, $website);

            if (is_object($this->http) && method_exists($this->http, 'setRandomUserAgent')) {
                $this->http->setRandomUserAgent();
            }

            sleep(self::SEARCH_PAUSE);
        }
    }

    private function hyatt()
    {
        $this->logger2->info(strtoupper(__FUNCTION__));

        $driver = $this->getSeleniumWebDriver(
            \SeleniumFinderRequest::BROWSER_FIREFOX,
            \SeleniumFinderRequest::FIREFOX_53
        );
        if (null === $driver) {
            throw new \Exception('Selenium not initialized');
        }

        $listHotels = $this->fetchListHotels(Provider::HYATT_ID);
        $attemptsCount = 0;
        foreach ($listHotels as $rowHotel) {
            $hotelId = (int) $rowHotel['HotelID'];

            if ($this->isSkipHotel($hotelId)) {
                continue;
            }
            $this->setSkipHotel($hotelId);
            $hotelName = $rowHotel['Name'];
            $matches = json_decode($rowHotel['Matches']);

            $this->logger2->info('HotelID: ' . $hotelId . ', HotelName: <' . $hotelName . '>');

            if (++$attemptsCount === self::ATTEMPTS_COUNT_SWITCH) {
                $attemptsCount = 0;
                $this->http->cleanup();
                $driver = $this->getSeleniumWebDriver(
                    \SeleniumFinderRequest::BROWSER_FIREFOX,
                    \SeleniumFinderRequest::FIREFOX_53,
                    true
                );
            }

            try {
                $driver->get('https://www.hyatt.com');
            } catch (\TimeOutException|\ScriptTimeoutException $e) {
                $this->logger2->info("Exception: " . $e->getMessage());
                $driver->executeScript('window.stop();');
            }

            sleep(5);

            $hotelInput = $this->waitForElement(\WebDriverBy::xpath('//input[@name="location"]'), 10);
            if (!$hotelInput) {
                $hotelInput = $this->waitForElement(
                    \WebDriverBy::cssSelector('input.quickbookDestinationSearchField'),
                    10
                );
            }
            $this->saveResponse();

            sleep(1);

            if (!$hotelInput) {
                $this->logger2->info('input field not found');

                if ($this->http->FindPreg('/fingerprint\/script\/kpf\.js\?url=/')) {
                    throw new \CheckRetryNeededException(3, 5);
                }

                return false;
            }
            $hotelInput->sendKeys('');

            $hotelInput->click();
            $hotelInput->sendKeys($hotelName);

            usleep(rand(400000, 1300000));
            $button = $this
                ->waitForElement(\WebDriverBy::xpath("//button[contains(@class, 'quickbookSearchFormButton')]"), 10);

            if (!$button) {
                $this->logger2->info('button not found');
                $this->saveResponse();

                return false;
            }

            $button->click();
            sleep(4);

            $link = $this->waitForElement(\WebDriverBy::xpath("//a[contains(text(), 'HOTEL WEBSITE')]"), 30);
            $this->saveResponse();

            $elements = $driver->findElements(\WebDriverBy::cssSelector('div.p-hotel-card-hbj'));
            $website = null;
            $originCleanHotelName = $this->cleanString($hotelName);

            $_replace = [' at '];
            foreach ($elements as $item) {
                $blockHotelName = $item->findElement(\WebDriverBy::cssSelector('div.hotel-name'));
                $cleanBlockHotelName = $this->cleanString($blockHotelName->getText());

                if (
                    $originCleanHotelName === $cleanBlockHotelName
                    || $this->cleanString(str_replace($_replace, '',
                        $originCleanHotelName)) === $this->cleanString(str_replace($_replace, '', $cleanBlockHotelName))
                    || str_replace('/ ', '', $hotelName) === $this->cleanString($cleanBlockHotelName)
                ) {
                    $link = $item->findElement(\WebDriverBy::xpath("//a[contains(text(), 'HOTEL WEBSITE')]"));

                    if ($link) {
                        $website = $link->getAttribute('href');
                        $this->http->NormalizeURL($website);

                        break;
                    }
                }

                foreach ($matches as $match) {
                    if (
                        $this->cleanString($match->HotelName) === $cleanBlockHotelName
                        || $this->cleanString(str_replace($_replace, '',
                            $match->HotelName)) === $this->cleanString(str_replace($_replace, '', $cleanBlockHotelName))
                        || str_replace('/ ', '', $match->HotelName) === $cleanBlockHotelName
                    ) {
                        $link = $item->findElement(\WebDriverBy::xpath("//a[contains(text(), 'HOTEL WEBSITE')]"));

                        if ($link) {
                            $website = $link->getAttribute('href');
                            $this->http->NormalizeURL($website);

                            break 2;
                        }
                    }
                }
            }

            if (!empty($website)) {
                $this->setHotelWebsite(Provider::HYATT_ID, $hotelId, $hotelName, $website);
            } else {
                $this->logger2->info('HotelFillLink NotFound - HotelID: ' . $hotelId . '; HotelName: "' . $hotelName . '"',
                    ['hotelId' => $hotelId, 'hotelName' => $hotelName]);
            }

            sleep(self::SEARCH_PAUSE);
        }
    }

    private function ihg()
    {
        $this->logger2->info(strtoupper(__FUNCTION__));

        $driver = $this->getSeleniumWebDriver(\SeleniumFinderRequest::BROWSER_CHROMIUM);
        if (null === $driver) {
            throw new \Exception('Selenium not initialized');
        }

        $listHotels = $this->fetchListHotels(Provider::IHG_REWARDS_ID);
        $attemptsCount = 0;

        foreach ($listHotels as $rowHotel) {
            $hotelId = (int) $rowHotel['HotelID'];

            if ($this->isSkipHotel($hotelId)) {
                continue;
            }
            $this->setSkipHotel($hotelId);
            $hotelName = $rowHotel['Name'];
            $matches = json_decode($rowHotel['Matches']);

            $this->logger2->info('HotelID: ' . $hotelId . ', HotelName: <' . $hotelName . '>');

            if (++$attemptsCount === self::ATTEMPTS_COUNT_SWITCH) {
                $attemptsCount = 0;
                $this->http->cleanup();
                $driver = $this->getSeleniumWebDriver(\SeleniumFinderRequest::BROWSER_CHROMIUM, null, true);
            }

            try {
                $this->http->GetURL('https://www.ihg.com/hotels/us/en/reservation');
            } catch (\TimeOutException|\ScriptTimeoutException $e) {
                $this->logger2->info("Exception: " . $e->getMessage());
                $driver->executeScript('window.stop();');
            }

            sleep(5);

            $hotelInput = $this->waitForElement(\WebDriverBy::id('dest-input'), 10);
            $this->saveResponse();

            sleep(1);

            if (!$hotelInput) {
                $this->logger2->info('input field not found');

                return false;
            }
            $hotelInput->sendKeys('');

            $hotelInput->click();
            $hotelInput->sendKeys($hotelName);

            usleep(rand(400000, 1300000));
            $button = $this->waitForElement(\WebDriverBy::cssSelector('button.search-button'), 10);

            if (!$button) {
                $this->logger2->info('button search not found');
                $this->saveResponse();

                return false;
            }

            $button->click();
            sleep(1);

            $found = $this->waitForElement(\WebDriverBy::xpath("//span[contains(text(), ' Hotels Found')]"), 30);
            sleep(3);

            $boxLists = $driver->findElements(\WebDriverBy::cssSelector('hotel-offer hotel-header > a'));
            $originCleanHotelName = $this->cleanString($hotelName);
            $website = null;

            foreach ($boxLists as $hotelOffer) {
                $spliteName = [];

                foreach ($hotelOffer->findElements(\WebDriverBy::tagName('span')) as $nameAndBrand) {
                    $spliteName[] = $nameAndBrand->getText();
                }
                $cleanBlockHotelName = $this->cleanString(implode(' ', $spliteName));

                if ($cleanBlockHotelName === $originCleanHotelName || $this->cleanString($hotelOffer->getText()) === $originCleanHotelName) {
                    $website = $hotelOffer->getAttribute('href');

                    break;
                }

                foreach ($matches as $match) {
                    if ($this->cleanString($match->HotelName) === $cleanBlockHotelName) {
                        $website = $hotelOffer->getAttribute('href');

                        break 2;
                    }
                }
            }

            if (!empty($website)) {
                $parts = parse_url($website);

                if (!empty($parts['path']) && false !== strpos($parts['path'], 'hoteldetail')) {
                    $website = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
                    $this->setHotelWebsite(Provider::IHG_REWARDS_ID, $hotelId, $hotelName, $website);
                } else {
                    echo 'HotelID: ' . $hotelId . ', website link not contain "hoteldetail" ' . PHP_EOL;
                }
            } else {
                $this->logger2->info('HotelFillLink NotFound - HotelID: ' . $hotelId . '; HotelName: "' . $hotelName . '"',
                    ['hotelId' => $hotelId, 'hotelName' => $hotelName]);
            }

            sleep(self::SEARCH_PAUSE);
        }
    }

    private function setHotelWebsite(int $providerId, int $hotelId, string $hotelName, string $website)
    {
        $this->logger2->info('SET -- ProviderID:' . $providerId . ' - HotelID:' . $hotelId . ', HotelName: "' . $hotelName . '", URL: "' . $website . '"');
        $this->connection->update('Hotel', ['Website' => $website], ['HotelID' => $hotelId]);
    }

    private function cleanString(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace(['/\s{2,}/', '/[\t\n]/'], ' ', $text);

        return preg_replace('/[^a-z0-9 ]/i', '', $text);
    }

    private function getOrderBy(): string
    {
        return self::MODE_RANDOM === $this->mode
            ? 'ORDER BY RAND()'
            : 'ORDER BY PointValue DESC';
    }

    private function sendRequest($urlOrRequest, array $requestHeaders = []): \HttpDriverResponse
    {
        if (is_string($urlOrRequest)) {
            $request = new \HttpDriverRequest($urlOrRequest, Request::METHOD_GET, null, [], self::REQUEST_TIMEOUT);
        } else {
            $request = $urlOrRequest;
        }
        //$request->proxyAddress = 'dop.awardwallet.com';
        //$request->proxyPort = 3128;
        if (!empty($requestHeaders)) {
            $request->headers = $requestHeaders;
        }

        return $this->curlDriver->request($request);
    }

    private function generateUuid(): string
    {
        $pr_bits = null;
        $fp = fopen('/dev/urandom', 'rb');

        if ($fp !== false) {
            $pr_bits .= fread($fp, 16);
            fclose($fp);
        } else {
            throw new \Exception('error generate dev/urandom');
        }

        $time_low = bin2hex(substr($pr_bits, 0, 4));
        $time_mid = bin2hex(substr($pr_bits, 4, 2));
        $time_hi_and_version = bin2hex(substr($pr_bits, 6, 2));
        $clock_seq_hi_and_reserved = bin2hex(substr($pr_bits, 8, 2));
        $node = bin2hex(substr($pr_bits, 10, 6));

        $time_hi_and_version = hexdec($time_hi_and_version);
        $time_hi_and_version >>= 4;
        $time_hi_and_version |= 0x4000;

        $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
        $clock_seq_hi_and_reserved >>= 2;
        $clock_seq_hi_and_reserved |= 0x8000;

        return sprintf(
            '%08s-%04s-%04x-%04x-%012s',
            $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node
        );
    }

    private function initCurl(bool $newSession = false): \HttpBrowser
    {
        $this->http = new \HttpBrowser(null, $this->curlDriver);
        //$this->http->SetProxy($this->proxyDOP());
        if (self::IS_ILLUMINATI) {
            $this->setProxyBrightData($newSession);
        }

        //$this->initSelenium(\SeleniumFinderRequest::BROWSER_CHROMIUM);
        //$this->getDriver(\SeleniumFinderRequest::BROWSER_CHROMIUM);

        return $this->http;
    }

    private function initSelenium(string $browser = \SeleniumFinderRequest::BROWSER_FIREFOX)
    {
        $this->construct_SeleniumCheckerHelper();
        $this->setSeleniumConnector($this->seleniumConnector);
        $this->UseSelenium();

        if ($browser === \SeleniumFinderRequest::BROWSER_CHROMIUM) {
            $this->useChromium();
        } else {
            $this->useFirefox(\SeleniumFinderRequest::FIREFOX_53);
        }

        $this->http->saveScreenshots = false;
        $this->usePacFile(false);

        return $this->getDriver($browser);
    }

    private function getSeleniumWebDriver(
        string $browser = \SeleniumFinderRequest::BROWSER_FIREFOX,
        string $version = null,
        $isProxyNewSession = false
    ): \RemoteWebDriver {
        $this->construct_SeleniumCheckerHelper();
        $this->UseSelenium();
        $this->usePacFile(false);
        if (self::IS_ILLUMINATI) {
            $this->setProxyBrightData($isProxyNewSession);
        }

        if ($browser === \SeleniumFinderRequest::BROWSER_CHROMIUM) {
            $this->useChromium($version);
        } else {
            $this->useFirefox($version);
        }

        $this->http->start();
        $this->http->driver->start();
        $this->driver = $this->http->driver->webDriver;

        return $this->driver;
    }

    private function getDriver(string $browser): ?\RemoteWebDriver
    {
        if (self::IS_ILLUMINATI) {
            $this->setProxyBrightData();
        }

        $fp = $browser === \SeleniumFinderRequest::BROWSER_CHROMIUM
            ? $this->fingerPrint->getOne([\AwardWallet\Common\Selenium\FingerprintRequest::chrome()])
            : $this->fingerPrint->getOne([\AwardWallet\Common\Selenium\FingerprintRequest::firefox()]);

        if (null !== $fp) {
            $this->logger2->info("selected fingerprint {$fp->getId()}, {{$fp->getBrowserFamily()}}:{{$fp->getBrowserVersion()}}, {{$fp->getPlatform()}}, {$fp->getUseragent()}");
            $this->State['Fingerprint'] = $fp->getFingerprint();
            $this->State['UserAgent'] = $fp->getUseragent();
            $this->State['Resolution'] = [$fp->getScreenWidth(), $fp->getScreenHeight()];
        }

        if (isset($this->State['Fingerprint'])) {
            $this->logger2->debug("set fingerprint");
            $this->seleniumOptions->fingerprint = $this->State['Fingerprint'];
        }

        if (!isset($this->State['UserAgent'])) {
            $this->http->setRandomUserAgent(5, false);

            return null;
        }
        $this->http->setUserAgent($this->State['UserAgent']);

        $this->http->start();

        return $this->getWebDriver();
    }

    private function fetchListHotels(int $providerId, $where = ''): ?array
    {
        return $this->connection->fetchAll('
            SELECT a.*, h.Matches
            FROM (
                SELECT HotelID, Name
                FROM Hotel
                WHERE ProviderID = ' . $providerId . ' AND Website IS NULL
                ' . (empty($where) ? '' : 'AND ' . $where) . '
                ' . $this->getOrderBy() . '
                LIMIT ' . self::HOTELS_LIMIT . '
            ) a JOIN Hotel h ON (h.HotelID = a.HotelID)
        ');
    }

    private function fillDetailsFromGoogleApiCache(OutputInterface $output)
    {
        $hotels = $this->connection->fetchAllAssociative('
            SELECT a.*, h.Matches
            FROM (
                SELECT HotelID, Name, ProviderID
                FROM Hotel
                WHERE GooglePlaceDetails IS NULL
            ) a JOIN Hotel h ON (h.HotelID = a.HotelID)
        ');

        define('GET_ONLY_FROM_CACHE', true);

        $cachePrefixSearch = 'google_place_text_search';
        $cachePrefixDetails = 'google_place_details';
        foreach ($hotels as $hotel) {
            $hotelId = (int) $hotel['HotelID'];
            $hotelName = $hotel['Name'];
            $matches = json_decode($hotel['Matches']);
            $output->writeln($hotelName);

            $parameters = PlaceTextSearchParameters::makeFromQuery(urldecode($hotelName));
            $parameters->setType(GoogleApi::PLACE_TYPE_LODGING);
            $parameters->setLanguage('en');

            if (GET_ONLY_FROM_CACHE) {
                $cacheKey = $cachePrefixSearch . '_' . $parameters->getHash();
                $responseBody = $this->memcached->get($cacheKey);
                $results = json_decode($responseBody);
                if (empty($results->results)) {
                    $output->writeln(' - not found in cache');
                    continue;
                }
                $results = $results->results;
            } else {
                $placesResponse = $this->googleApi->placeTextSearch($parameters);
                $results = $placesResponse->getResults();
            }


            if (1 === count($results)) {
                if (GET_ONLY_FROM_CACHE) {
                    $placeId = $results[0]->place_id;
                } else {
                    $placeId = $results[0]->getPlaceId();
                }

                $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);

                if (GET_ONLY_FROM_CACHE) {
                    $cacheKey = $cachePrefixDetails . '_' . $parameters->getHash();
                    $responseBody = $this->memcached->get($cacheKey);
                    $results = json_decode($responseBody);
                    if (empty($results->result)) {
                        continue;
                    }
                    $placeDetails = $results->result;
                } else {
                    $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
                }

                if (GET_ONLY_FROM_CACHE) {
                    $jsonDetails = json_encode($placeDetails);
                } else {
                    $jsonDetails = $this->serializer->serialize($placeDetails, 'json');
                }

                $this->connection->update('Hotel',
                    ['GooglePlaceDetails' => $jsonDetails],
                    ['HotelId' => $hotelId]
                );
            } elseif (!empty($matches)) {
                $matchByLocation = false;
                foreach ($results as $result) {
                    if (GET_ONLY_FROM_CACHE) {
                        $placeId = $result->place_id;
                    } else {
                        $placeId = $result->getPlaceId();
                    }

                    $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);

                    if (GET_ONLY_FROM_CACHE) {
                        $cacheKey = $cachePrefixDetails . '_' . $parameters->getHash();
                        $responseBody = $this->memcached->get($cacheKey);
                        $results = json_decode($responseBody);
                        if (empty($results->result)) {
                            continue;
                        }
                        $placeDetails = $results->result;
                    } else {
                        $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
                    }

                    if (null === $placeDetails) {
                        continue;
                    }

                    if (GET_ONLY_FROM_CACHE) {
                        $lat = $placeDetails->geometry->location->lat;
                        $lng = $placeDetails->geometry->location->lng;
                    } else {
                        $lat = $placeDetails->getGeometry()->getLocation()->getLat();
                        $lng = $placeDetails->getGeometry()->getLocation()->getLng();
                    }

                    foreach ($matches as $match) {
                        if ($lat == $match->Lat && $lng == $match->Lng) {
                            $matchByLocation = true;
                            break;
                        }
                    }
                }

                if ($matchByLocation) {
                    if (GET_ONLY_FROM_CACHE) {
                        $jsonDetails = json_encode($placeDetails);
                    } else {
                        $jsonDetails = $this->serializer->serialize($placeDetails, 'json');
                    }

                    $this->connection->update('Hotel',
                        ['GooglePlaceDetails' => $jsonDetails],
                        ['HotelId' => $hotelId]
                    );
                }
            }
        }
    }

    private function fillPhonesFromGoogleApiJson(OutputInterface $output)
    {
        $hotels = $this->connection->fetchAllAssociative("
            SELECT HotelID, Name, ProviderID, GooglePlaceDetails
            FROM Hotel
            WHERE
                    (Phones IS NULL OR Phones = '')
                AND GooglePlaceDetails IS NOT NULL
        ");

        foreach ($hotels as $hotel) {
            $hotelId = (int) $hotel['HotelID'];
            $placeDetails = json_decode($hotel['GooglePlaceDetails']);
            if (empty($placeDetails)) {
                $output->writeln('!! json_decode - empty result, hotelId = ' . $hotelId);
                continue;
            }

            $phone = $placeDetails->international_phone_number ?? '';
            $phone = trim($phone);
            if (empty($phone)) {
                continue;
            }

            $output->writeln('set phone ' . $hotel['Name'] . ' :: ' . $phone);
            $this->connection->update('Hotel',
                ['Phones' => $phone],
                ['HotelID' => $hotelId]
            );
        }
    }

    private function fillPhonesFromGoogleApi(OutputInterface $output)
    {
        $hotels = $this->connection->fetchAllAssociative('
            SELECT a.*, h.Matches
            FROM (
                SELECT HotelID, Name, ProviderID, Address
                FROM Hotel
                WHERE Phones IS NULL OR Phones = \'\'
            ) a JOIN Hotel h ON (h.HotelID = a.HotelID)
            ORDER BY a.HotelID ASC
        ');

        foreach ($hotels as $hotel) {
            $hotelId = (int) $hotel['HotelID'];
            $hotelName = $hotel['Name'];
            if ($this->isSkipHotel($hotelId)) {
                $output->writeln('skipped [' . $hotelId . '], ' . $hotelName);
                //    continue;
            }
            $output->writeln($hotelId . ' : ' . $hotelName);
            $this->setSkipHotel($hotelId);
            $providerId = (int) $hotel['ProviderID'];
            $matches = json_decode($hotel['Matches']);

            $parameters = PlaceTextSearchParameters::makeFromQuery(urldecode($hotelName . ' ' . $hotel['Address']));
            $parameters->setType(GoogleApi::PLACE_TYPE_LODGING);
            $parameters->setLanguage('en');
            $placesResponse = $this->googleApi->placeTextSearch($parameters);

            $results = $placesResponse->getResults();
            if (1 === count($results)) {
                $placeId = $results[0]->getPlaceId();
                $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);

                try {
                    $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
                    $phone = $placeDetails->getInternationalPhoneNumber() ?? '';
                    $phone = trim($phone);
                    if (empty($phone)) {
                        continue;
                    }

                    $output->writeln(' - found uniq');
                    $this->connection->update('Hotel',
                        [
                            'Phones' => $phone,
                            'GooglePlaceDetails' => $this->serializer->serialize($placeDetails, 'json')
                        ],
                        ['HotelID' => $hotelId]
                    );
                } catch (GoogleRequestFailedException $e) {
                    $output->writeln('GoogleRequest failed by "place_id" = ' . $placeId);
                }
            } elseif (!empty($matches)) {
                $phone = false;
                $foundDetails = null;
                foreach ($results as $result) {
                    $placeId = $result->getPlaceId();
                    $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);
                    $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
                    if (null === $placeDetails) {
                        continue;
                    }

                    $lat = $placeDetails->getGeometry()->getLocation()->getLat();
                    $lng = $placeDetails->getGeometry()->getLocation()->getLng();

                    foreach ($matches as $match) {
                        if ($lat == $match->Lat && $lng == $match->Lng) {
                            $foundDetails = $placeDetails;
                            $phone = $placeDetails->getInternationalPhoneNumber() ?? '';
                            $phone = trim($phone);
                            if (!empty($phone)) {
                                break 2;
                            }
                        }
                    }
                }

                if (!empty($phone)) {
                    $output->writeln(' - found in multiple');
                    $this->connection->update('Hotel',
                        [
                            'Phones' => $phone,
                            'GooglePlaceDetails' => $this->serializer->serialize($foundDetails, 'json')
                        ],
                        ['HotelID' => $hotelId]
                    );
                }
            }
        }

        $output->writeln('--');

        $withoutPhones = $this->connection->fetchOne("
            SELECT COUNT(*) FROM Hotel
            WHERE (Phones IS NULL OR Phones = '')
        ");
        $output->writeln('Hotels without phones: ' . $withoutPhones);

        $countDetails = $this->connection->fetchOne("
            SELECT COUNT(*) FROM Hotel
            WHERE GooglePlaceDetails IS NOT NULL
        ");
        $output->writeln('Hotels with filled GooglePlaceDetails: ' . $countDetails);

        $output->writeln('--');
        $output->writeln('List Hotels without phones:');
        $withoutPhones = $this->connection->fetchAllAssociative("
            SELECT HotelID, Name FROM Hotel
            WHERE (Phones IS NULL OR Phones = '')
        ");
        foreach ($withoutPhones as $hotel) {
            $output->writeln($hotel['HotelID'] . ' - ' . $hotel['Name']);
        }
    }

    private function fillFromGoogleApi(OutputInterface $output)
    {
        $hotels = $this->connection->fetchAllAssociative('
            SELECT a.*, h.Matches
            FROM (
                SELECT HotelID, Name, ProviderID
                FROM Hotel
                WHERE Website IS NULL
            ) a JOIN Hotel h ON (h.HotelID = a.HotelID)
        ');

        foreach ($hotels as $hotel) {
            $hotelId = (int) $hotel['HotelID'];
            $hotelName = $hotel['Name'];
            if ($this->isSkipHotel($hotelId)) {
                $output->writeln('skipped [' . $hotelId . '], ' . $hotelName);
                continue;
            }
            $this->setSkipHotel($hotelId);
            $providerId = (int) $hotel['ProviderID'];
            $matches = json_decode($hotel['Matches']);

            $parameters = PlaceTextSearchParameters::makeFromQuery(urldecode($hotelName));
            $parameters->setType(GoogleApi::PLACE_TYPE_LODGING);
            $parameters->setLanguage('en');
            $placesResponse = $this->googleApi->placeTextSearch($parameters);

            $results = $placesResponse->getResults();
            if (1 === count($results)) {
                $placeId = $results[0]->getPlaceId();
                $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);

                try {
                    $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
                    $website = $this->cleanUrlQuery($placeDetails->getWebsite());
                    if (!empty($website)) {
                        $this->setHotelWebsite($providerId, $hotelId, $hotelName, $website);
                    }
                } catch (GoogleRequestFailedException $e) {
                    $output->writeln('GoogleRequest failed by "place_id" = ' . $placeId);
                }
            } elseif (!empty($matches)) {
                $found = [];
                foreach ($results as $result) {
                    $placeId = $result->getPlaceId();
                    $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);
                    $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
                    if (null === $placeDetails) {
                        continue;
                    }

                    $lat = $placeDetails->getGeometry()->getLocation()->getLat();
                    $lng = $placeDetails->getGeometry()->getLocation()->getLng();

                    foreach ($matches as $match) {
                        if ($lat == $match->Lat
                            && $lng == $match->Lng
                            && !empty($website = $this->cleanUrlQuery($placeDetails->getWebsite()))
                        ) {
                            $found[] = $website;
                            break;
                        }
                    }
                }

                if (1 === count($found)) {
                    $this->setHotelWebsite($providerId, $hotelId, $hotelName, $found[0]);
                }
            }
        }
    }
*/
}
