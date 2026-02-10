<?php

namespace AwardWallet\Tests\Unit;

class MemoryTest extends BaseContainerTest
{
    /**
     * @dataProvider memoryLeakDataProvider
     */
    public function testMemoryLeaks()
    {
        $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $user->find(7);
    }

    public function memoryLeakDataProvider()
    {
        $runs = [];

        foreach (range(1, (int) getenv('TESTS_MEMORY_LEAKS_MAX_RUNS')) as $i) {
            $runs[] = [$i];
        }

        return $runs;
    }
}
