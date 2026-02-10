<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\FrameworkExtension\Monolog\Handler\FlashBagHandlerFactory;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryGroupFinder;
use AwardWallet\MainBundle\Service\HotelPointValue\PatternLoader;
use Doctrine\DBAL\Connection;
use Monolog\Logger;

class ShoppingCategoryGroup extends \TBaseSchema
{
    private ShoppingCategoryGroupFinder $groupFinder;

    private Logger $logger;

    private FlashBagHandlerFactory $flashBagHandlerFactory;

    private Connection $connection;

    private MerchantMatcher $merchantMatcher;

    public function __construct(
        ShoppingCategoryGroupFinder $groupFinder,
        Logger $logger,
        FlashBagHandlerFactory $flashBagHandlerFactory,
        Connection $connection,
        MerchantMatcher $merchantMatcher
    ) {
        parent::__construct();

        $this->groupFinder = $groupFinder;
        $this->logger = $logger;
        $this->flashBagHandlerFactory = $flashBagHandlerFactory;
        $this->connection = $connection;
        $this->merchantMatcher = $merchantMatcher;
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        $result['Patterns']['InputType'] = 'textarea';
        $result['Patterns']['Note'] = PatternLoader::PATTERNS_SYNTAX_HELP;

        $options = $this->connection->fetchAllKeyValue("select ShoppingCategoryGroupID, Name from ShoppingCategoryGroup order by Name");

        $groupManager = new \TTableLinksFieldManager();
        $groupManager->TableName = "ShoppingCategoryGroupChildren";
        $groupManager->KeyField = "ParentGroupID";
        $groupManager->Fields = [
            "ChildGroupID" => [
                "Type" => "integer",
                "Caption" => "Child Group",
                "Options" => $options,
                "Required" => true,
            ],
        ];
        $groupManager->CanEdit = true;

        $result['Children'] = [
            "Manager" => $groupManager,
        ];

        return $result;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        $result['Categories'] = [
            'Type' => 'string',
            'Database' => false,
        ];
        $result['BonusEarns'] = [
            'Type' => 'string',
            'Database' => false,
        ];

        unset($result['ClickURL']);

        return $result;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);

        $form->OnCheck = function () use ($form) {
            return PatternLoader::validate($form->Fields['Patterns']['Value'] ?? '');
        };

        $form->OnSave = function () use ($form) {
            if ($form->Fields['Patterns']['Value'] !== $form->Fields['Patterns']['OldValue']) {
                $this->flashBagHandlerFactory->push();

                try {
                    $this->groupFinder->updateAll();
                } finally {
                    $this->flashBagHandlerFactory->pop();
                }
            }

            $this->merchantMatcher->clearCache();
        };
    }
}
