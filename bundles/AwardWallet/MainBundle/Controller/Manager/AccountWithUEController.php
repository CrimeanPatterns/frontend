<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/manager/account-with-ue")
 */
class AccountWithUEController extends AbstractController
{
    /** @var Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ACCOUNT_WITH_UE')")
     * @Route("", name="aw_manager_account_with_ue")
     */
    public function indexAction(Request $request)
    {
        global $arProviderKind;
        global $arProviderState;
        $data = $this->getData();
        $response = $this->render('@AwardWalletMain/Manager/Support/Account/accountWithUE.html.twig', [
            "data" => $data,
            "kind" => $arProviderKind,
            "state" => $arProviderState,
            "contentTitle" => sprintf("All accounts with UE older than 24 hours - %d provider(s)", count($data)),
        ]);

        return $response;
    }

    private function getData()
    {
        $q = $this->connection->executeQuery("
            SELECT 
                A.ProviderID,
                MAX(p.PasswordRequired) AS PasswordRequired,
	            MAX(p.Code) AS Code,
	            MAX(p.DisplayName) AS DisplayName,
	            MAX(p.Kind) AS Kind,
	            MAX(p.State) AS State,
	            COUNT(A.AccountID) AS TotalUE,
	            SUM(IF((A.SavePassword = 1 AND A.Pass<> '') OR A.AuthInfo IS NOT NULL,1,0)) AS SavePWD
            FROM Account A
            	INNER JOIN Provider p ON (p.ProviderID = A.ProviderID)
            WHERE 
                A.ErrorCode = :errorCode 
	            AND A.ProviderID NOT IN (7, 16, 26, 636, 145)
	            AND A.State = :accState 
	            AND A.Disabled = :disabled 
	            AND A.UpdateDate < NOW() - INTERVAL 1 DAY
	            AND p.State >= :provStateLow
	            AND p.State <> :provStateExcl1
                AND p.State <> :provStateExcl2
            GROUP BY ProviderID
            HAVING (SavePWD > 0 OR PasswordRequired<>1)
            ORDER BY Code
        ", [
            ':errorCode' => ACCOUNT_ENGINE_ERROR,
            ':accState' => ACCOUNT_ENABLED,
            ':disabled' => 0,
            ':provStateLow' => PROVIDER_ENABLED,
            ':provStateExcl1' => PROVIDER_CHECKING_EXTENSION_ONLY,
            ':provStateExcl2' => PROVIDER_COLLECTING_ACCOUNTS,
        ]);

        return $q->fetchAllAssociative();
    }
}
