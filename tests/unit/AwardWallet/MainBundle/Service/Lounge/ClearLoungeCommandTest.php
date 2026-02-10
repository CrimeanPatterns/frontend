<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\ClearLoungeCommand;
use AwardWallet\MainBundle\Service\Lounge\Storage;
use AwardWallet\Tests\Modules\DbBuilder\Lounge;
use AwardWallet\Tests\Modules\DbBuilder\LoungeAction;
use AwardWallet\Tests\Modules\DbBuilder\LoungeSource;
use AwardWallet\Tests\Modules\DbBuilder\LoungeSourceChange;
use AwardWallet\Tests\Unit\CommandTester;

class ClearLoungeCommandTest extends CommandTester
{
    /**
     * @var ClearLoungeCommand
     */
    protected $command;

    public function testClearLoungesChanges()
    {
        $this->dbBuilder->makeLoungeSource(
            (new LoungeSource('AAA', 'Test', StringHandler::getRandomCode(5)))
                ->addLoungeSourceChange(
                    $change = new LoungeSourceChange('Terminal', ['OldVal' => 1, 'NewVal' => 2])
                )
        );
        $this->runCommand();
        $this->db->seeInDatabase('LoungeSourceChange', [
            'LoungeSourceChangeID' => $change->getId(),
        ]);
        $this->db->updateInDatabase('LoungeSourceChange', [
            'ChangeDate' => date('Y-m-d H:i:s', strtotime('-95 day')),
        ], ['LoungeSourceChangeID' => $change->getId()]);
        $this->runCommand();
        $this->db->dontSeeInDatabase('LoungeSourceChange', [
            'LoungeSourceChangeID' => $change->getId(),
        ]);
    }

    public function testRemovingUselessLoungesSources()
    {
        $loungeSourceId = $this->dbBuilder->makeLoungeSource(
            new LoungeSource('AAA', 'Test', StringHandler::getRandomCode(5))
        );
        $this->runCommand();
        $this->db->seeInDatabase('LoungeSource', [
            'LoungeSourceID' => $loungeSourceId,
        ]);
        $this->db->updateInDatabase('LoungeSource', [
            'DeleteDate' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ], ['LoungeSourceID' => $loungeSourceId]);
        $this->runCommand();
        $this->db->dontSeeInDatabase('LoungeSource', [
            'LoungeSourceID' => $loungeSourceId,
        ]);
    }

    public function testRemovingActions()
    {
        $this->dbBuilder->makeLounge(
            (new Lounge('AAA', 'Test'))
                ->addLoungeAction($action = new LoungeAction(['Action' => '{}']))
        );
        $this->runCommand();
        $this->db->seeInDatabase('LoungeAction', [
            'LoungeActionID' => $action->getId(),
        ]);
        $this->db->updateInDatabase('LoungeAction', [
            'DeleteDate' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ], ['LoungeActionID' => $action->getId()]);
        $this->runCommand();
        $this->db->dontSeeInDatabase('LoungeAction', [
            'LoungeActionID' => $action->getId(),
        ]);
    }

    private function runCommand()
    {
        $this->initCommand(
            new ClearLoungeCommand(
                $this->em->getConnection(),
                $this->container->get(Storage::class)
            ));
        $this->clearLogs();
        $this->executeCommand();
    }
}
