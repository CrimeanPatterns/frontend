<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\Fixture\AirHelpView;
use AwardWallet\MainBundle\Service\EmailTemplate\Query;
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Driver\ResultStatement;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;

abstract class AbstractAirHelpCompensation extends AbstractFailTolerantDataProvider
{
    protected const CANCELLED = 'cancelled';
    protected const DELAYED = 'delayed';

    protected const EXCLUDED_USERS = [174548, 356981];

    /**
     * @var LazyVal<\Twig_TemplateWrapper>
     */
    protected $twigTemplate;

    /**
     * @var int
     */
    protected $processed = 0;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var LocalizeService
     */
    protected $localizer;
    /**
     * @var \AwardWallet\MainBundle\Entity\Repositories\UsrRepository
     */
    private $userRepository;

    public function __construct(ContainerInterface $container, EmailTemplate $template)
    {
        parent::__construct($container, $template);

        $this->entityManager = $container->get('doctrine.orm.default_entity_manager');
        $this->userRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->localizer = $container->get(LocalizeService::class);
        $this->twigTemplate = lazy(function () use ($container): \Twig_TemplateWrapper {
            return $container->get('twig')->load($this->getListTemplatePath());
        });
    }

    public function getQuery()
    {
        $parentQuery = parent::getQuery();
        $fieldsMapper = function (array $fields) {
            return $this->mapFieldsFromJson($fields);
        };

        return new class($parentQuery, $fieldsMapper) extends Query {
            /**
             * @var Query
             */
            private $parentQuery;
            /**
             * @var callable
             */
            private $fieldsMapper;

            public function __construct(Query $parentQuery, callable $fieldsMapper)
            {
                $this->parentQuery = $parentQuery;
                $this->fieldsMapper = $fieldsMapper;
            }

            public function getConnection()
            {
                return $this->parentQuery->getConnection();
            }

            public function setConnection($connection)
            {
                return $this->parentQuery->setConnection($connection);
            }

            public function getPreparedSql()
            {
                return $this->parentQuery->getPreparedSql();
            }

            public function setPreparedSql($preparedSql)
            {
                return $this->parentQuery->setPreparedSql($preparedSql);
            }

            public function getStatement()
            {
                $parentStatement = $this->parentQuery->getStatement();

                return new class($parentStatement, $this->fieldsMapper) extends PDOStatement implements \IteratorAggregate {
                    /**
                     * @var ResultStatement
                     */
                    private $innerStatement;
                    /**
                     * @var callable
                     */
                    private $fieldsMapper;

                    public function __construct(ResultStatement $innerStatement, callable $fieldsMapper)
                    {
                        $this->innerStatement = $innerStatement;
                        $this->fieldsMapper = $fieldsMapper;
                    }

                    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
                    {
                        $fields = $this->innerStatement->fetch($fetchMode, $cursorOrientation, $cursorOffset);

                        if (false === $fields) {
                            return false;
                        }

                        return ($this->fieldsMapper)($fields);
                    }

                    public function getIterator()
                    {
                        while (false !== ($res = $this->fetch(\PDO::FETCH_ASSOC))) {
                            yield $res;
                        }
                    }
                };
            }

            public function setStatement($statement)
            {
                return $this->parentQuery->setStatement($statement);
            }

            public function getPreparedCountSql()
            {
                return $this->parentQuery->getPreparedCountSql();
            }

            public function setPreparedCountSql($preparedCountSql)
            {
                return $this->parentQuery->setPreparedCountSql($preparedCountSql);
            }

            public function getCount()
            {
                return $this->parentQuery->getCount();
            }

            public function setCount($count)
            {
                return $this->parentQuery->setCount($count);
            }

            public function getFields()
            {
                return $this->parentQuery->getFields();
            }

            public function setFields($fields)
            {
                return $this->parentQuery->setFields($fields);
            }

            public function addDebug($message, $data = null)
            {
                return $this->parentQuery->addDebug($message, $data);
            }

            public function getDebug()
            {
                return $this->parentQuery->getDebug();
            }
        };
    }

    public function getMessage(Mailer $mailer)
    {
        return parent::getMessage($mailer); // TODO: Change the autogenerated stub
    }

    public function next()
    {
        $next = parent::next();

        if ($this->processed++ === 100) {
            $this->entityManager->clear();
        }

        return $next;
    }

    protected function mapFieldsFromJson(array $fields): array
    {
        $aggregatedTrips = \json_decode($fields['aggregated_trips'], true);
        $index = isset($aggregatedTrips[0]) ? 0 : 1;
        $user = $this->userRepository->find($fields['UserID']);
        $this->localizer->setLocale($user->getLocale());
        $airHelpMap = [];

        foreach ($aggregatedTrips[$index] as [
            'locale' => $locale,
            'record_locator' => $record_locator,
            'url' => $url,
            'flight_status' => $flight_status,
            'delay_info' => $delay_info,
            'ec261_compensation_gross' => $ec261_compensation_gross,
            'ec261_compensation_currency' => $ec261_compensation_currency,
            'departure_city' => $departure_city,
            'localized_departure_city' => $localized_departure_city,
            'flight_start' => $flight_start,
            'flight_scheduled_departure' => $flight_scheduled_departure,
            'arrival_city' => $arrival_city,
            'localized_arrival_city' => $localized_arrival_city,
            'flight_end' => $flight_end,
            'flight_scheduled_arrival' => $flight_scheduled_arrival,
        ]
        ) {
            $groupingKey = \implode('_', [$flight_start, $flight_end, $flight_scheduled_departure, $flight_scheduled_arrival]);

            if (isset($airHelpMap[$groupingKey])) {
                continue;
            }

            $airHelpMap[$groupingKey] = new AirHelpView(
                $locale,
                $record_locator,
                $url,
                $flight_status,
                $this->getFlightStatusUpper($flight_status),
                $this->getFlightStatusLower($flight_status),
                $this->getFlightStatusTextBlock($flight_status),
                $delay_info,
                $this->localizer->formatCurrency(
                    $ec261_compensation_gross,
                    $ec261_compensation_currency,
                    true
                ),
                $this->decideLocalizedCity($departure_city, $localized_departure_city),
                $localized_departure_city,
                $flight_start,
                $this->localizer->formatDateTime($dateTime = new \DateTime($flight_scheduled_departure)),
                $dateTime,
                $this->getTopDepartureDate($dateTime),
                $this->decideLocalizedCity($arrival_city, $localized_arrival_city),
                $localized_arrival_city,
                $flight_end,
                $this->localizer->formatDateTime(new \DateTime($flight_scheduled_arrival))
            );
        }

        $airHelpList = \array_values($airHelpMap);
        \usort($airHelpList, function (AirHelpView $a, AirHelpView $b) {
            return $b->flight_scheduled_departure <=> $a->flight_scheduled_departure;
        });
        $fields['airHelpViewList'] = $airHelpList;

        return $fields;
    }

    abstract protected function getTopDepartureDate(\DateTime $dateTime): string;

    abstract protected function decideLocalizedCity(string $normalized, string $localized): string;

    abstract protected function getFlightStatusTextBlock(string $flightStatus): string;

    abstract protected function getFlightStatusUpper(string $flight_status): string;

    abstract protected function getFlightStatusLower(string $flight_status): string;

    abstract protected function getListTemplatePath(): string;

    /**
     * @param AirHelpView[] $airHelpViewList
     */
    abstract protected function getBottomFlightEnd(array $airHelpViewList): string;

    /**
     * @param AirHelpView[] $airHelpViewList
     */
    abstract protected function getAirHelpTitle(array $airHelpViewList): string;

    protected function renderTemplateParts(AbstractTemplate $template): void
    {
        /** @var \Twig_TemplateWrapper $twigTemplate */
        $twigTemplate = $this->twigTemplate->getValue();
        $airHelpViewList = $this->fields['airHelpViewList'];
        /** @var AirHelpView $firstAirHelpView */
        $firstAirHelpView = $airHelpViewList[0];

        $this->fields['airHelpTitle'] = $this->getAirHelpTitle($airHelpViewList);
        $this->fields['bottomFlightEnd'] = $this->getBottomFlightEnd($airHelpViewList);

        $this->fields['airHelpViewList'] =
            it($airHelpViewList)
            ->map(function (AirHelpView $airHelpView) use ($twigTemplate) { return $twigTemplate->render(['airHelpView' => $airHelpView]); })
            ->joinToString('
                <center style="padding: 20px 0">
                    <div style="height: 1px  !important; width:100%; font-size: 0 !important; border-top: 1px solid #d6dae6"><span style="font-size: 16px; line-height: 1.5; color: #535457">&nbsp;</span></div>
                </center> 
            ');

        $this->fields['subject_pnr'] = $firstAirHelpView->recordLocator;
        $this->fields['subject_status'] = $firstAirHelpView->flight_status_lower;
        $this->fields['subject_arrival_city'] = $firstAirHelpView->arrival_city;

        parent::renderTemplateParts($template);
    }
}
