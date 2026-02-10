<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Entity\ShoppingCategory as SCEntity;
use AwardWallet\MainBundle\FrameworkExtension\Monolog\Handler\FlashBagHandlerFactory;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryGroupFinder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;

class ShoppingCategoryList extends \TBaseList
{
    private RouterInterface $router;

    private Connection $connection;

    private FlashBagInterface $flashBag;

    private ShoppingCategoryGroupFinder $groupFinder;

    private FlashBagHandlerFactory $flashBagHandlerFactory;

    public function __construct(
        string $table,
        array $fields,
        RouterInterface $router,
        Connection $connection,
        FlashBagInterface $flashBag,
        ShoppingCategoryGroupFinder $groupFinder,
        FlashBagHandlerFactory $flashBagHandlerFactory
    ) {
        parent::__construct($table, $fields);
        $this->router = $router;
        $this->connection = $connection;
        $this->flashBag = $flashBag;
        $this->groupFinder = $groupFinder;
        $this->flashBagHandlerFactory = $flashBagHandlerFactory;
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if ($output === "html") {
            if ($this->Query->Fields["Transactions"] === null) {
                $this->Query->Fields["Transactions"] = "Unknown";
            }

            if ($this->Query->Fields["TransactionsInLast6Months"] === null) {
                $this->Query->Fields["TransactionsInLast6Months"] = "Unknown";
            }

            $url = $this->router->generate("aw_manager_transaction_list", ["ShoppingCategoryID" => $this->OriginalFields['ShoppingCategoryID']]);
            $this->Query->Fields["Transactions"] = "<a href=\"{$url}\" target=\"_blank\">{$this->Query->Fields["Transactions"]}</a>";

            $url = $this->router->generate("aw_manager_transaction_list", ["ShoppingCategoryID" => $this->OriginalFields['ShoppingCategoryID'], "StartDate" => date("m/d/Y", strtotime("-6 month"))]);
            $this->Query->Fields["TransactionsInLast6Months"] = "<a href=\"{$url}\" target=\"_blank\">{$this->Query->Fields["TransactionsInLast6Months"]}</a>";
        }
    }

    public function DrawButtonsInternal()
    {
        $result = parent::DrawButtonsInternal();

        echo "<select id=\"set_group\" onchange=\"this.form.action.value = 'set_group:' + this.value; form.submit();\">";
        echo "<option>Set category</option>";
        echo "<option value='0'>Clear (match by patterns)</option>";

        foreach ($this->connection->executeQuery("select ShoppingCategoryGroupID, Name from ShoppingCategoryGroup order by Name")->fetchAllAssociative() as $group) {
            echo "<option value='{$group['ShoppingCategoryGroupID']}'>{$group['Name']}</option>";
        }
        echo "</select>";
        $triggers[] = ['set_group_button', 'Set group'];

        return $result;
    }

    public function ProcessAction($action, $ids): void
    {
        parent::ProcessAction($action, $ids);

        if (preg_match('#^set_group:(\d+)$#ims', $action, $matches)) {
            if (count($ids) === 0) {
                $this->flashBag->add("error", "Select some records");

                return;
            }

            $groupId = (int) $matches[1];
            $linkedBy = SCEntity::LINKED_TO_GROUP_BY_MANUALLY;

            if ($groupId === 0) {
                $groupId = null;
                $linkedBy = null;
            }

            $updated = $this->connection->executeStatement(
                "update ShoppingCategory set
                    ShoppingCategoryGroupID = ?,
                    LinkedToGroupBy = ?
                where
                    ShoppingCategoryID in (?)",
                [$groupId, $linkedBy, $ids],
                [ParameterType::INTEGER, ParameterType::INTEGER, Connection::PARAM_INT_ARRAY]
            );
            $this->flashBag->add("success", "Updated group for {$updated} records");

            if ($groupId === null) {
                $this->flashBagHandlerFactory->push();

                try {
                    $this->groupFinder->updateAll();
                } finally {
                    $this->flashBagHandlerFactory->pop();
                }
            }
        }
    }
}
