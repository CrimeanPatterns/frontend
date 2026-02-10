<?php

namespace Codeception\Module;

use AwardWallet\MainBundle\DependencyInjection\PublicTestAliasesCompilerPass;
use AwardWallet\MainBundle\Service\Notification\PushCollector;
use AwardWallet\MainBundle\Service\Notification\Sender;
use Codeception\TestCase;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class Push extends \Codeception\Module
{
    /**
     * @var  Sender
     */
    private $sender;

    public function _before(TestCase $test)
    {
        parent::_before($test);
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $symfony->persistService(PublicTestAliasesCompilerPass::TEST_SERVICE_PREFIX . PushCollector::class);
    }

    public function seePushTo($userId, $message)
    {
        assertTrue($this->hasPush($userId, $message), "Can't see push to {$userId} with message '{$message}'");
    }

    public function dontSeePushTo($userId, $message)
    {
        assertFalse($this->hasPush($userId, $message), "Can see push to {$userId} with message '{$message}'");
    }

    public function getPushes(): array
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        /** @var PushCollector $collector */
        $collector = $symfony->grabService(PushCollector::class);

        return $collector->getCollected();
    }

    public function _after(TestCase $test)
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        /** @var PushCollector $collector */
        $collector = $symfony->grabService(PushCollector::class);
        $collector->clear();

        parent::_after($test);
    }

    protected function hasPush($userId, $message): bool
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        /** @var PushCollector $collector */
        $collector = $symfony->grabService(PushCollector::class);

        foreach ($collector->getCollected() as $push) {
            if (
                ($push->getUserId() === $userId)
                && preg_match($message, $push->getMessage())
            ) {
                return true;
            }
        }

        return false;
    }
}
