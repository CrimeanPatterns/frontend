<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Service\ABTestManager;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class ABTestManagerTest extends BaseContainerTest
{
    private ?ABTestManager $manager;

    public function _before()
    {
        parent::_before();

        $this->manager = $this->container->get(ABTestManager::class);
        $this->db->executeQuery("DELETE FROM ABTest WHERE TestID = 'testId'");
    }

    public function _after()
    {
        $this->manager = null;

        parent::_after();
    }

    public function test()
    {
        $this->db->dontSeeInDatabase('ABTest', ['TestID' => 'testId']);
        $variant = $this->manager->getNextVariant('testId', ['variant1', 'variant2', 'variant3']);
        $this->db->seeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant1', 'ExposureCount' => 0]);
        $this->db->dontSeeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant2', 'ExposureCount' => 0]);
        $this->db->dontSeeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant3', 'ExposureCount' => 0]);
        $this->assertEquals('variant1', $variant);

        $variant = $this->manager->getNextVariant('testId', ['variant1', 'variant2', 'variant3']);
        $this->db->seeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant1', 'ExposureCount' => 0]);
        $this->db->seeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant2', 'ExposureCount' => 0]);
        $this->db->dontSeeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant3', 'ExposureCount' => 0]);
        $this->assertEquals('variant2', $variant);

        $variant = $this->manager->getNextVariant('testId', ['variant1', 'variant2', 'variant3']);
        $this->db->seeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant1', 'ExposureCount' => 0]);
        $this->db->seeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant2', 'ExposureCount' => 0]);
        $this->db->seeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant3', 'ExposureCount' => 0]);
        $this->assertEquals('variant3', $variant);
        $this->manager->incrementExposureCount('testId', 'variant1');
        $this->db->seeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant1', 'ExposureCount' => 1]);
        $this->manager->incrementExposureCount('testId', 'variant3');
        $this->db->seeInDatabase('ABTest', ['TestID' => 'testId', 'Variant' => 'variant3', 'ExposureCount' => 1]);

        $variant = $this->manager->getNextVariant('testId', ['variant1', 'variant2', 'variant3']);
        $this->assertEquals('variant2', $variant);
        $this->manager->incrementExposureCount('testId', 'variant2');
        $this->manager->incrementExposureCount('testId', 'variant2');
        $this->assertEquals('variant1', $this->manager->getNextVariant('testId', ['variant1', 'variant2', 'variant3']));
    }
}
