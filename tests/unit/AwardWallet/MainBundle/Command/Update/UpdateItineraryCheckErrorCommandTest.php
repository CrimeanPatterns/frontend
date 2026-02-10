<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Command\Update\UpdateItineraryCheckErrorCommand;
use AwardWallet\MainBundle\Service\ParserNoticeProvider;
use AwardWallet\Tests\Unit\CommandTester;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class UpdateItineraryCheckErrorCommandTest extends CommandTester
{
    protected $time;
    protected $logger;
    protected $em;
    protected $accountId;

    public function _before()
    {
        parent::_before();
        $this->time = time();
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->em = $this->container->get('doctrine.orm.default_entity_manager');
        $this->accountId = $this->aw->createAwAccount($this->user->getId(), 'testprovider',
            'test' . $this->user->getId());
    }

    public function _after()
    {
        $this->cleanCommand();
        $ids = $this->db->query("SELECT ItineraryCheckErrorID FROM ItineraryCheckError WHERE AccountID = ?",
            [$this->accountId])->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($ids)) {
            $this->db->executeQuery("DELETE FROM ItineraryCheckError WHERE ItineraryCheckErrorID IN (" . implode(", ", $ids) . ")");
        }
        parent::_after();
    }

    public function testNoData()
    {
        $parserNotice = $this->createMock(ParserNoticeProvider::class);
        $parserNotice
            ->expects(self::once())
            ->method('search')
            ->willReturn([false, []]);

        $this->initCommand(new UpdateItineraryCheckErrorCommand($this->logger, $this->em, $parserNotice));
        $this->runCommand();
        $this->logContains(UpdateItineraryCheckErrorCommand::MessageNoData);
    }

    public function testDataInCache()
    {
        $parserNotice = $this->createMock(ParserNoticeProvider::class);
        $parserNotice
            ->expects(self::once())
            ->method('search')
            ->willReturn([true, []]);

        $this->initCommand(new UpdateItineraryCheckErrorCommand($this->logger, $this->em, $parserNotice));
        $this->runCommand();
        $this->logContains(UpdateItineraryCheckErrorCommand::MessageDataInCache);
    }

    public function testNoCacheDryRun()
    {
        $data = $this->getData();
        $cnt = count($data);

        $parserNotice = $this->createMock(ParserNoticeProvider::class);
        $parserNotice
            ->expects(self::once())
            ->method('search')
            ->willReturn([false, $data]);

        $this->initCommand(new UpdateItineraryCheckErrorCommand($this->logger, $this->em, $parserNotice));
        $this->runCommand(true);
        $this->logContains(sprintf(UpdateItineraryCheckErrorCommand::MessageFinal, $cnt, $cnt, 0, 0));
    }

    public function testNoCacheNotDryRun()
    {
        $data = $this->getData();
        $cnt = count($data);

        $parserNotice = $this->createMock(ParserNoticeProvider::class);
        $parserNotice
            ->expects(self::any())
            ->method('search')
            ->willReturn([false, $data]);

        $command = new UpdateItineraryCheckErrorCommand($this->logger, $this->em, $parserNotice);
        $this->initCommand($command);
        $this->runCommand();
        $this->logContains(sprintf(UpdateItineraryCheckErrorCommand::MessageFinal, $cnt, $cnt, 0, 0));

        $this->runCommand();
        $this->logContains(sprintf(UpdateItineraryCheckErrorCommand::MessageFinal, $cnt, 0, 0, $cnt));
    }

    public function testDuplicateErrors()
    {
        $data = $this->getData();
        $data[1] = $data[0];
        $data[1]['DetectionDate'] = strtotime("2 seconds", $data[1]['DetectionDate']);

        $cnt = count($data);

        $parserNotice = $this->createMock(ParserNoticeProvider::class);
        $parserNotice
            ->expects(self::any())
            ->method('search')
            ->willReturn([false, $data]);

        $command = new UpdateItineraryCheckErrorCommand($this->logger, $this->em, $parserNotice);
        $this->initCommand($command);
        $this->runCommand();
        $this->logContains(sprintf(UpdateItineraryCheckErrorCommand::MessageFinal, $cnt, $cnt - 1, 0, 1));
    }

    private function runCommand(bool $dryRun = false, ?string $startsDate = null, ?string $endDate = null)
    {
        $this->logs->clear();

        if ($dryRun) {
            $this->executeCommand([
                '--dry-run' => true,
                '--startDate' => $startsDate,
                '--endDate' => $endDate,
            ]);
        } else {
            $this->executeCommand([
                '--startDate' => $startsDate,
                '--endDate' => $endDate,
            ]);
        }
    }

    private function getData()
    {
        return [
            0 => [
                'DetectionDate' => $this->time,
                'ProviderID' => 1,
                'AccountId' => $this->accountId,
                'ErrorMessage' => 'rental-0: testing error message',
            ],
            1 => [
                'DetectionDate' => $this->time,
                'ProviderID' => 2,
                'AccountId' => $this->accountId,
                'ErrorMessage' => 'hotel-1: testing error message',
            ],
        ];
    }
}
