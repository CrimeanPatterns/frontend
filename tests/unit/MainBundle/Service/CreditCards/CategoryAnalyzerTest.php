<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Entity\ShoppingCategoryGroup;
use AwardWallet\MainBundle\Service\CreditCards\CategoryAnalyzer;
use AwardWallet\Tests\Unit\BaseTest;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * @group frontend-unit
 */
class CategoryAnalyzerTest extends BaseTest
{
    public const CATEGORY1 = 23; // "gas stns & restaurants";
    public const CATEGORY2 = 25; // "dining";
    public const CATEGORY3 = 99;
    public const MULTIPLIER = [
        self::CATEGORY1 => 2,
        self::CATEGORY2 => 3,
        self::CATEGORY3 => 5,
    ];

    /**
     * @dataProvider transactionsData
     */
    public function testAnalyzeMerchantCategory($data, $resultCategory)
    {
        $priorities = [
            self::CATEGORY1 => 1,
            self::CATEGORY2 => 2,
            self::CATEGORY3 => 3,
        ];

        $repo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $repo->expects($this->any())
             ->method('find')
             ->willReturnCallback(function ($id) use ($priorities) {
                 return (new ShoppingCategory())->setGroup((new ShoppingCategoryGroup())->setPriority($priorities[$id]));
             });

        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
           ->method('getRepository')
           ->with(ShoppingCategory::class)
           ->willReturn($repo);

        $analyzer = new CategoryAnalyzer($em);
        $category = $analyzer->analyzeMerchantCategory($data);
        $this->assertEquals($category, $resultCategory);
    }

    public function transactionsData()
    {
        $data = [
            'merchant1' => [
                'ink' => [
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                ],
                'freedom' => [
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                ],
            ],
            'merchant1_1' => [
                'ink' => [
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                ],
                'freedom' => [
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY3, 'multiplier' => self::MULTIPLIER[self::CATEGORY3]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                ],
            ],
            'merchant2' => [
                'ink' => [
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                ],
                'sapphire' => [
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                ],
                'freedom' => [
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                ],
            ],
            'merchant3' => [
                'sapphire' => [
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                ],
                'freedom' => [
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                ],
                'ink' => [
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                ],
            ],
            'merchant3_1' => [
                'sapphire' => [
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                    ['category' => self::CATEGORY1, 'multiplier' => self::MULTIPLIER[self::CATEGORY1]],
                ],
                'freedom' => [
                    ['category' => self::CATEGORY3, 'multiplier' => self::MULTIPLIER[self::CATEGORY3]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                    ['category' => self::CATEGORY2, 'multiplier' => self::MULTIPLIER[self::CATEGORY2]],
                ],
                'ink' => [
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                    ['category' => 0, 'multiplier' => 0],
                ],
            ],
        ];

        return [
            [$data['merchant1'], self::CATEGORY2],
            [$data['merchant1_1'], self::CATEGORY3],
            [$data['merchant2'], self::CATEGORY2],
            [$data['merchant3'], self::CATEGORY2],
            [$data['merchant3_1'], self::CATEGORY3],
        ];
    }
}
