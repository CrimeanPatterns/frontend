<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Service\HistoryAnalyzer\Analyzer;
use Aws\S3\S3Client;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/manager/history/analyzer")
 */
class HistoryAnalyzerController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/account/{id}", name="aw_manager_history_analyzer_account", methods={"GET"}, requirements={"id"="[a-z\d]+"})
     * @ParamConverter("account", class="AwardWalletMainBundle:Account")
     */
    public function accountAction(Account $account, Analyzer $analyzer, ConnectionInterface $replicaUnbufferedConnection)
    {
        $rows = $replicaUnbufferedConnection->executeQuery("
            select 
                h.*,
                ta.ProviderID,
                ta.UserID,
                ta.UserAgentID
            from
                AccountHistory h
                join Account ta on h.AccountID = ta.AccountID
            where
                h.PostingDate >= adddate(now(), interval -1 year)
                and h.AccountID = :accountId
            ",
            ["accountId" => $account->getId()]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $response = $analyzer->analyze($rows);

        return $this->render("@AwardWalletMain/Manager/HistoryAnalyzer/account.html.twig", ["account" => $account, "response" => $response]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/cross-airlines-report", name="aw_manager_history_analyzer_report", methods={"GET"})
     */
    public function crossAirlinesReportAction(Request $request, S3Client $s3Client, string $awsS3Bucket)
    {
        $data = json_decode($s3Client->getObject(['Key' => 'cross_airlines_report.json', 'Bucket' => $awsS3Bucket])->get('Body'), true);

        return $this->render(
            "@AwardWalletMain/Manager/HistoryAnalyzer/crossAirlinesReport.html.twig",
            array_merge($data, ['withLinks' => $request->query->get('links', 'on') == 'on'])
        );
    }
}
