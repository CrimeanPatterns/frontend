<?php

namespace AwardWallet\MainBundle\Command\Update;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

class UpdateAirCraftIcaoCommand extends Command
{
    use \AwardWallet\Engine\ProxyList;

    private const URL_WIKI = 'https://en.wikipedia.org/wiki/List_of_aircraft_type_designators';
    private const URL_GITPERSON = 'https://gist.githubusercontent.com/nagarjun/56488d54e106ea3f8f8e580d89d64daa/raw/b1fff671545e8a305404a113824aaaf5bbb98e5d/aircraftIcaoIata.json';
    private const URL_AVCODES_UK = 'https://www.avcodes.co.uk/acrtypes.asp';

    protected $http;
    protected static $defaultName = 'aw:update-aircrafts-icao';

    private EntityManagerInterface $entityManager;

    /** @var \CurlDriver */
    private $curlDriver;

    public function __construct(
        EntityManagerInterface $entityManager,
        \CurlDriver $curlDriver
    ) {
        $this->entityManager = $entityManager;
        $this->curlDriver = $curlDriver;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Update AirCraft ICAO codes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dataWiki = $this->fetchDataFromWiki();
        $dataAvCodes = $this->fetchDataFromAvCodesUk();
        $dataGitPerson = $this->fetchDataFromGitPerson();

        $data = array_merge($dataWiki, $dataAvCodes, $dataGitPerson);

        $already = [];

        foreach ($data as $code) {
            $icao = trim($code['icao']);
            $iata = trim($code['iata']);
            $model = trim($code['model']);

            if (
                array_key_exists($icao, $already)
                || empty($icao)
                || 'n/a' === strtolower($icao)
                || false !== stripos($model, 'deprecated')
                || false !== stripos($iata, 'determined')
            ) {
                continue;
            }

            $this->entityManager->getConnection()->executeQuery('
                UPDATE Aircraft
                SET IcaoCode = :icao
                WHERE
                        IcaoCode IS NULL
                    AND IataCode = :iata',
                ['icao' => $icao, 'iata' => $iata],
                ['icao' => \PDO::PARAM_STR, 'iata' => \PDO::PARAM_STR]
            );

            $already[$icao] = true;
        }

        $this->notFoundCodesInTable($data, $output);

        return 0;
    }

    private function fetchDataFromWiki(): array
    {
        $request = new \HttpDriverRequest(self::URL_WIKI);
        $response = $this->curlDriver->request($request);

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadHTML($response->body);

        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query("//table/caption[text()[contains(.,'ICAO aircraft')]]/../tbody");

        $head = $data = [];
        $rowIndex = -1;

        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            foreach ($node->childNodes as $row) {
                if ('tr' === strtolower($row->nodeName)) {
                    ++$rowIndex;

                    foreach ($row->childNodes as $col) {
                        $nodeName = strtolower($col->nodeName);

                        if ('th' === $nodeName) {
                            $head[] = $col->nodeValue;
                        } elseif ('td' === $nodeName) {
                            $data[$rowIndex][] = trim($col->nodeValue);
                        }
                    }
                }
            }
        }
        $data = array_values($data);

        foreach ($head as $key => $item) {
            if (false !== stripos($item, 'ICAO')) {
                $head[$key] = 'icao';
            } elseif (false !== stripos($item, 'IATA')) {
                $head[$key] = 'iata';
            } elseif (false !== stripos($item, 'Model')) {
                $head[$key] = 'model';
            } else {
                unset($head[$key]);
            }
        }

        if (3 !== count($head) || 3 !== count($data[0])) {
            throw new \Exception('Invalid data contents');
        }

        array_walk($data, static function (&$a) use ($head) {
            $a = array_combine($head, $a);
        });

        return $data;
    }

    private function fetchDataFromAvCodesUk(): array
    {
        $this->http = new \HttpBrowser('none', $this->curlDriver);

        $proxies = $this->getRecaptchaProxies();

        foreach ($proxies as $proxy) {
            $request = new \HttpDriverRequest(self::URL_AVCODES_UK);
            $request->proxyAddress = $proxy;
            // $request->proxyPort = $proxyPort;

            $response = $this->curlDriver->request($request);

            if (Response::HTTP_OK === $response->httpCode && !empty($response->body)) {
                break;
            }
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;

        $doc->loadHTML($response->body);

        $xpath = new \DOMXPath($doc);
        $tables = $xpath->query("//table//th[text()[contains(.,'Manufacturer and Aircraft')]]/../../..");

        $head = $data = [];
        $rowIndex = -1;

        foreach ($tables as $table) {
            $node = $xpath->query('//tr', $table);

            foreach ($node as $row) {
                if ('tr' === strtolower($row->nodeName)) {
                    ++$rowIndex;

                    foreach ($row->childNodes as $col) {
                        $nodeName = strtolower($col->nodeName);

                        if ('th' === $nodeName) {
                            $head[$rowIndex][] = $col->nodeValue;
                        } elseif ('td' === $nodeName) {
                            $data[$rowIndex][] = trim($col->nodeValue);
                        }
                    }
                }
            }
        }

        $head = array_values($head)[0];
        $raw = array_values($data);

        foreach ($head as $key => $item) {
            $item = trim($item);

            if (false !== stripos($item, 'ICAO')) {
                $head[$key] = 'icao';
            } elseif (false !== stripos($item, 'IATA')) {
                $head[$key] = 'iata';
            } elseif (false !== stripos($item, 'Model')) {
                $head[$key] = 'model';
            } else {
                unset($head[$key]);
            }
        }

        $data = [];

        foreach ($raw as $key => &$item) {
            if (4 === count($item)) {
                array_pop($item);
                $data[] = $item;
            }
        }

        if (3 !== count($head) || 3 !== count($data[0])) {
            throw new \Exception('Invalid data contains');
        }

        array_walk($data, static function (&$a) use ($head) {
            $a = array_combine($head, $a);
        });

        return $data;
    }

    private function fetchDataFromGitPerson(): ?array
    {
        $json = trim(file_get_contents(self::URL_GITPERSON));
        $json = json_decode($json, true);

        if (empty($json) || 3 !== count($json[0])) {
            return null;
        }

        $data = [];

        foreach ($json as $item) {
            $data[] = [
                'icao' => $item['icaoCode'],
                'iata' => $item['iataCode'],
                'model' => $item['description'],
            ];
        }

        return $data;
    }

    private function notFoundCodesInTable(array $data, OutputInterface $output): void
    {
        $exists = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT IcaoCode, IataCode FROM Aircraft
        ');

        $iata = array_column($exists, 'IataCode');

        $xIata = array_column($data, 'iata');
        $xIata = array_unique($xIata);
        $xIata = array_filter($xIata, fn ($code) => !empty($code) && 'n/a' !== strtolower($code));

        $iataDiff = array_diff($xIata, $iata);

        if (!empty($iataDiff)) {
            $output->writeln('');
            $output->writeln('IATA codes not found in AirCraft table: ');
            $output->writeln(implode(', ', $iataDiff));
            $output->writeln('');
        }

        $icao = array_column($exists, 'IcaoCode');

        $xIcao = array_column($data, 'icao');
        $xIcao = array_unique($xIcao);
        $xIcao = array_filter($xIcao, fn ($code) => !empty($code) && 'n/a' !== strtolower($code));

        $icaoDiff = array_diff($xIcao, $icao);

        if (!empty($icaoDiff)) {
            $output->writeln('');
            $output->writeln('ICAO codes not found in AirCraft table: ');
            $output->writeln(implode(', ', $icaoDiff));
            $output->writeln('');
        }
    }
}
