<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Repositories;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TripRepositoryCest extends KernelTestCase
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }

    public function testSearchByCategoryName()
    {
        $products = $this->entityManager
            ->getRepository(Product::class)
            ->searchByCategoryName('foo')
        ;

        $this->assertCount(1, $products);
    }
}
