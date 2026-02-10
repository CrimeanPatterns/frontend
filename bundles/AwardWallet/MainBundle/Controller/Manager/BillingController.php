<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Dumper;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Paypal\AgreementHack;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\PayPalAPI\GetTransactionDetailsReq;
use PayPal\PayPalAPI\GetTransactionDetailsRequestType;
use PayPal\PayPalAPI\RefundTransactionReq;
use PayPal\PayPalAPI\RefundTransactionRequestType;
use PayPal\PayPalAPI\TransactionSearchReq;
use PayPal\PayPalAPI\TransactionSearchRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class BillingController extends AbstractController
{
    private array $paypalParams;
    private RouterInterface $router;
    private PaypalRestApi $paypalRestApi;

    public function __construct(array $paypal, RouterInterface $router, PaypalRestApi $paypalRestApi)
    {
        $this->paypalParams = $paypal;
        $this->router = $router;
        $this->paypalRestApi = $paypalRestApi;
    }

    /**
     * @Route("/manager/billing/agreement/{id}", name="aw_manager_billing_agreement", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_ADMINCART')")
     */
    public function showBillingAgreement($id)
    {
        $apiContext = $this->paypalRestApi->getApiContext();
        $agreement = AgreementHack::get($id, $apiContext);
        $transactions = Agreement::searchTransactions($agreement->getId(), ['start_date' => date('Y-m-d', strtotime('-15 years')), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $apiContext);

        return new Response("<pre>" . json_encode(["agreement" => $agreement->toArray(), "transactions" => $transactions->toArray()], JSON_PRETTY_PRINT) . "</pre>
        <form action='" . $this->router->generate("aw_manager_billing_agreement_delete", ["id" => $id]) . "' method=post
        onsubmit='return window.confirm(\"Delete agreement?\")'>
        <input type=submit value=Delete></form>");
    }

    /**
     * @Route("/manager/billing/agreement/delete/{id}", name="aw_manager_billing_agreement_delete", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_DELETE_USER')")
     */
    public function deleteBillingAgreement($id)
    {
        $apiContext = $this->paypalRestApi->getApiContext();
        $agreement = AgreementHack::get($id, $apiContext);

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Deleting the agreement");
        $agreement->cancel($agreementStateDescriptor, $apiContext);

        return new Response("Agreement $id deleted");
    }

    /**
     * @Route("/manager/billing/transaction/{id}", name="aw_manager_billing_transaction", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_ADMINCART')")
     */
    public function showTransaction($id)
    {
        $service = $this->getPaypalService();
        $request = new GetTransactionDetailsReq();
        $request->GetTransactionDetailsRequest = new GetTransactionDetailsRequestType();
        $request->GetTransactionDetailsRequest->TransactionID = $id;
        $response = $service->GetTransactionDetails($request);

        return new Response("<pre>" . json_encode(Dumper::filterEmpty($response), JSON_PRETTY_PRINT) . "</pre>
        <form action='" . $this->router->generate("aw_manager_billing_transaction_refund", ["id" => $id]) . "' method=post
        onsubmit='return window.confirm(\"Refund transaction?\")'>
        <input type=submit value=Refund></form>");
    }

    /**
     * @Route("/manager/billing/transaction/refund/{id}", name="aw_manager_billing_transaction_refund", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_DELETE_USER')")
     */
    public function refundTransaction($id)
    {
        $service = $this->getPaypalService();
        $request = new RefundTransactionReq();
        $request->RefundTransactionRequest = new RefundTransactionRequestType();
        $request->RefundTransactionRequest->TransactionID = $id;
        $response = $service->RefundTransaction($request);

        if ($response->Ack == "Failure" && $response->Errors[0]->LongMessage == 'This transaction has already been fully refunded') {
            $status = "already refunded";
        } elseif ($response->Ack != "Success") {
            throw new \Exception("failed to refund transaction: " . json_encode($response->Errors));
        } else {
            $status = 'refunded';
        }

        return new Response($status);
    }

    /**
     * @Route("/manager/billing/transactions/user/{id}", name="aw_manager_billing_transactions_user", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_ADMINCART')")
     */
    public function showTransactions($id, EntityManagerInterface $entityManager)
    {
        /** @var Usr $user */
        $user = $entityManager->getRepository(Usr::class)->find($id);
        $service = $this->getPaypalService();
        $request = new TransactionSearchReq();
        $startDate = strtotime("-1 month");
        $request->TransactionSearchRequest = new TransactionSearchRequestType(date('Y-m-d\TH:i:s\Z', $startDate));
        $request->TransactionSearchRequest->Payer = $user->getEmail();
        $response = $service->TransactionSearch($request);

        return new Response("<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>");
    }

    protected function getPaypalService()
    {
        $config = $this->paypalParams['profiles']['live'];

        if (isset($this->paypalParams['globals'])) {
            $config = array_merge($config, $this->paypalParams['globals']);
        }

        //        $config['log.LogEnabled'] = '1';
        //        $config['log.LogLevel'] = 'FINE';
        //        $config['log.FileName'] = "/var/log/www/awardwallet/billing.log";

        $service = new PayPalAPIInterfaceServiceService($config);

        return $service;
    }
}
