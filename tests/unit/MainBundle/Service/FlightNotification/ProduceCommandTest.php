<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Service\FlightNotification\OffsetHandler;
use AwardWallet\MainBundle\Service\FlightNotification\ProduceCommand;
use AwardWallet\MainBundle\Service\FlightNotification\Producer;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Unit\CommandTester;
use Codeception\Stub\Expected;

/**
 * @group frontend-unit
 */
class ProduceCommandTest extends CommandTester
{
    private const DEP_DATE = '2022-01-05 10:00:00';

    /**
     * @var ProduceCommand
     */
    protected $command;

    public function _after()
    {
        $this->cleanCommand();

        parent::_after();
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(array $offsets, \DateTime $now, bool $expected)
    {
        $this->addTrip();
        $this->createCommand(
            [636 => $this->offset($offsets)],
            $expected ? Expected::once(fn () => true) : Expected::never()
        );
        $this->runCommand(null, $now);
        $this->logContains('filter by userId: [' . $this->user->getId() . ']');
        $this->logContains(sprintf('processed %d segments', $expected ? 1 : 0));
    }

    public function dataProvider(): array
    {
        return [
            'checkin, too early' => [
                [OffsetHandler::KIND_CHECKIN => 24], self::modifyDepDate('-27 hours -1 minute', true), false,
            ],

            'checkin, start boundary' => [
                [OffsetHandler::KIND_CHECKIN => 24], self::modifyDepDate('-27 hours', true), true,
            ],

            'checkin, 24h' => [
                [OffsetHandler::KIND_CHECKIN => 24], self::modifyDepDate('-24 hours', true), true,
            ],

            'checkin, end boundary' => [
                [OffsetHandler::KIND_CHECKIN => 24], self::modifyDepDate('-3 hours', false), true,
            ],

            'checkin, end boundary 2' => [
                [OffsetHandler::KIND_CHECKIN => 24], self::modifyDepDate('-2 hours -59 minutes', false), false,
            ],

            'departure, start boundary' => [
                [OffsetHandler::KIND_DEPARTURE => 4], self::modifyDepDate('-7 hours -1 minute', true), false,
            ],

            'departure, start boundary 2' => [
                [OffsetHandler::KIND_DEPARTURE => 4], self::modifyDepDate('-7 hours', true), true,
            ],

            'departure, end boundary' => [
                [OffsetHandler::KIND_DEPARTURE => 4], self::modifyDepDate('-3 hours', false), true,
            ],

            'departure, end boundary 2' => [
                [OffsetHandler::KIND_DEPARTURE => 4], self::modifyDepDate('-2 hours -59 minutes', false), false,
            ],

            'boarding, start boundary' => [
                [OffsetHandler::KIND_BOARDING => 1], self::modifyDepDate('-4 hours -1 minute', true), false,
            ],

            'boarding, start boundary 2' => [
                [OffsetHandler::KIND_BOARDING => 1], self::modifyDepDate('-4 hours', true), true,
            ],

            'boarding, end boundary' => [
                [OffsetHandler::KIND_BOARDING => 1], self::modifyDepDate('-30 minutes', false), true,
            ],

            'boarding, end boundary 2' => [
                [OffsetHandler::KIND_BOARDING => 1], self::modifyDepDate('-29 minutes', false), false,
            ],

            'precheckin, too early' => [
                [OffsetHandler::KIND_CHECKIN => 24, OffsetHandler::KIND_PRECHECKIN => 24.25], self::modifyDepDate('-27 hours -16 minute', true), false,
            ],

            'precheckin, start boundary' => [
                [OffsetHandler::KIND_CHECKIN => 24, OffsetHandler::KIND_PRECHECKIN => 24.25], self::modifyDepDate('-27 hours -15 minute', true), true,
            ],
        ];
    }

    public function testDryRun()
    {
        $this->addTrip();
        $this->createCommand([636 => $this->offset([OffsetHandler::KIND_CHECKIN => 24])], Expected::once(fn () => true));
        $this->runCommand(null, self::modifyDepDate('-27 hours'));
        $this->logNotContains('dry run');
        $this->logContains('processed 1 segments');

        $this->createCommand([636 => $this->offset([OffsetHandler::KIND_CHECKIN => 24])], Expected::never());
        $this->runCommand(null, self::modifyDepDate('-27 hours'), null, true);
        $this->logContains('dry run');
        $this->logContains('processed 1 segments');
    }

    private function addTrip(): int
    {
        return $this->dbBuilder->makeTrip(
            new Trip(
                'XXX1',
                [
                    new TripSegment(
                        'ABC',
                        'ABC',
                        self::depDate(),
                        'QWE',
                        'QWE',
                        new \DateTime('2022-01-05 13:00:00'),
                        null,
                        [
                            'DepartureTerminal' => 'Main Terminal',
                            'ArrivalTerminal' => 'Terminal 2',
                            'FlightNumber' => '001',
                        ]
                    ),
                ],
                null,
                [
                    'UserID' => $this->user->getId(),
                    'ProviderID' => 636,
                    'Category' => TRIP_CATEGORY_AIR,
                ]
            )
        );
    }

    private function createCommand(array $offsetMap, $publish): void
    {
        $command = new ProduceCommand(
            $this->container->get($this->loggerService),
            $this->em,
            $this->em->getConnection(),
            $this->make(OffsetHandler::class, [
                'getOffsetMap' => $offsetMap,
            ]),
            $this->make(Producer::class, [
                'publish' => $publish,
            ])
        );
        $this->initCommand($command);
    }

    private function offset(array $push, ?array $mail = null): array
    {
        $offset = [OffsetHandler::CATEGORY_PUSH => $push];

        if (is_array($mail)) {
            $offset[OffsetHandler::CATEGORY_MAIL] = $mail;
        }

        return $offset;
    }

    private function runCommand(
        ?int $userId = null,
        ?\DateTimeInterface $baseDate = null,
        ?int $providerId = null,
        bool $dryRun = false
    ) {
        $this->clearLogs();
        $args = [
            '--baseDate' => isset($baseDate) ? $baseDate->format('Y-m-d H:i:s') : 'now',
            '--userId' => $userId ?? [$this->user->getId()],
            '--dry-run' => $dryRun,
        ];

        if ($providerId) {
            $args['--providerId'] = $providerId;
        }

        $this->executeCommand($args);
    }

    private static function depDate(): \DateTime
    {
        return date_create(self::DEP_DATE);
    }

    private static function modifyDepDate(string $str, ?bool $addMinTz = null): \DateTime
    {
        $date = self::depDate()->modify($str);

        if (is_bool($addMinTz)) {
            if ($addMinTz) {
                $date = $date->modify(sprintf('-%d hours', ProduceCommand::MIN_TIMEZONE));
            } else {
                $date = $date->modify(sprintf('+%d hours', ProduceCommand::MAX_TIMEZONE));
            }
        }

        return $date;
    }
}
