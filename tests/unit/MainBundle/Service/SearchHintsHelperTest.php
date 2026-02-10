<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\Account\SearchHintsDynamicFilters;
use AwardWallet\MainBundle\Service\Account\SearchHintsHelper;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class SearchHintsHelperTest extends BaseContainerTest
{
    private const SUCCESS_MESSAGE = 'Saving was successful';

    private ?Usr $user;
    private ?EntityManagerInterface $entityManager;
    private ?SearchHintsHelper $helper;

    public function _before()
    {
        parent::_before();
        $this->user = new Usr();
        $this->entityManager = $this->makeEmpty(EntityManagerInterface::class);
        $this->helper = new SearchHintsHelper(
            $this->entityManager,
            $this->makeEmpty(AwTokenStorageInterface::class, ['getUser' => $this->user]),
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(SearchHintsDynamicFilters::class),
            $this->makeEmpty(CacheManager::class)
        );
    }

    public function _after()
    {
        $this->user = null;
        $this->helper = null;
        parent::_after();
    }

    /**
     * Добавляет подсказку в пустой список.
     */
    public function testAddHintToEmptyArray()
    {
        $response = $this->helper->update('Aegean Airlines');

        $this->assertEquals(self::SUCCESS_MESSAGE, $response->getMessage());
        $this->assertEquals(['Aegean Airlines'], $response->getData());
        $this->assertEquals(['Aegean Airlines'], $this->user->getSearchHints());
    }

    /**
     * Добавляет подсказку в список меньше 5 элементов.
     */
    public function testAddHintToArray()
    {
        $this->user->setSearchHints(['Air Canada', 'Aeromexico']);
        $this->entityManager->flush();
        $response = $this->helper->update('Air China');

        $this->assertEquals(self::SUCCESS_MESSAGE, $response->getMessage());
        $this->assertEquals(['Air China', 'Air Canada', 'Aeromexico'], $response->getData());
        $this->assertEquals(['Air China', 'Air Canada', 'Aeromexico'], $this->user->getSearchHints());
    }

    /**
     * Добавляет подсказку в полный список из 5 элементов.
     */
    public function testAddHintToFullArray()
    {
        $this->user->setSearchHints(['All Nippon Airways', 'Alaska Airlines', 'Air China', 'Air Canada', 'Aeromexico']);
        $this->entityManager->flush();
        $response = $this->helper->update('American Airlines');

        $this->assertEquals(self::SUCCESS_MESSAGE, $response->getMessage());
        $this->assertEquals(['American Airlines', 'All Nippon Airways', 'Alaska Airlines', 'Air China', 'Air Canada'], $response->getData());
        $this->assertEquals(['American Airlines', 'All Nippon Airways', 'Alaska Airlines', 'Air China', 'Air Canada'], $this->user->getSearchHints());
    }

    /**
     * Добавляет подсказку, которая уже есть в списке.
     */
    public function testAddDefaultHintToArray()
    {
        $this->user->setSearchHints(['Aeromexico']);
        $this->entityManager->flush();
        $response = $this->helper->update('Aeromexico');

        $this->assertEquals('"Value" must be unique.', $response->getMessage());
        $this->assertEquals(['Aeromexico'], $response->getData());
        $this->assertEquals(['Aeromexico'], $this->user->getSearchHints());
    }
}
