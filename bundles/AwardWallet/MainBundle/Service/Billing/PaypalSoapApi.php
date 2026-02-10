<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\EBLBaseComponents\PaymentTransactionType;
use PayPal\PayPalAPI\GetTransactionDetailsReq;
use PayPal\PayPalAPI\GetTransactionDetailsRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Psr\Log\LoggerInterface;

class PaypalSoapApi
{
    /**
     * @var PayPalAPIInterfaceServiceService
     */
    private $service;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var array
     */
    private $paypalParams;

    private LoggerInterface $logger;

    private PasswordDecryptor $passwordDecryptor;

    public function __construct(array $paypalParams, EntityManagerInterface $em, LoggerInterface $logger, PasswordDecryptor $passwordDecryptor)
    {
        $this->paypalParams = $paypalParams;

        $config = $paypalParams['profiles']['live'];

        if (isset($paypalParams['globals'])) {
            $config = array_merge($config, $paypalParams['globals']);
        }

        $this->service = new PayPalAPIInterfaceServiceService($config);
        $this->em = $em;
        $this->logger = $logger;
        $this->passwordDecryptor = $passwordDecryptor;
    }

    /**
     * @return PaymentTransactionType|null
     */
    public function getTransactionDetails($transactionId)
    {
        $request = new GetTransactionDetailsReq();
        $request->GetTransactionDetailsRequest = new GetTransactionDetailsRequestType();
        $request->GetTransactionDetailsRequest->TransactionID = $transactionId;
        $details = $this->service->GetTransactionDetails($request);

        if ($details->Ack != 'Success') {
            return null;
        }

        return $details->PaymentTransactionDetails;
    }

    /**
     * @return Cart|null
     */
    public function findCartByTransactionId($transactionId)
    {
        $details = $this->getTransactionDetails($transactionId);

        if (
            preg_match('#(\d+)paid on#ims', $details->PaymentItemInfo->InvoiceID, $matches)
            || preg_match('#^(\d+)\-\d+$#ims', $details->PaymentItemInfo->InvoiceID, $matches)
        ) {
            $cart = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)->find($matches[1]);

            return $cart;
        }

        return null;
    }

    /**
     * @return PayPalAPIInterfaceServiceService
     */
    public function getPaypalService()
    {
        return $this->service;
    }

    public function getPaypalServiceForCart($debug = false, Cart $cart)
    {
        if ($cart->isSandboxMode()) {
            $config = $this->paypalParams['profiles']['sandbox'];
        } else {
            $config = $this->paypalParams['profiles']['live'];
        }

        if (isset($this->paypalParams['globals'])) {
            $config = array_merge($config, $this->paypalParams['globals']);
        }

        if ($invoiceId = $cart->getBookingInvoiceId()) {
            $invoice = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbInvoice::class)->find($invoiceId);

            if (!$invoice) {
                throw new \Exception(sprintf("Invoice #%d not found", $invoiceId));
            }

            if (!$debug) {
                $bookerInfo = $invoice->getMessage()->getRequest()->getBooker()->getBookerInfo();
                $paypalPassword = $bookerInfo->getPayPalPassword();

                if (empty($paypalPassword)) {
                    throw new \Exception(sprintf("Unfortunately %s is not yet set up to accept credit card payments, please contact them to enable this option", $bookerInfo->getServiceName()));
                }
                $code = strtolower($bookerInfo->getServiceShortName());
                $this->logger->critical("trying to load paypal profile for booker {$code}");

                // @TODO: migrate to config
                if (!file_exists("/usr/paypal/cert/{$code}.ppd")) {
                    throw new \Exception(sprintf("File %s.ppd not found", $code));
                }
                $ppd = unserialize(file_get_contents("/usr/paypal/cert/{$code}.ppd"));
                $config = [
                    'mode' => $ppd['environment'],
                    'acct1.UserName' => $ppd['username'],
                    'acct1.Password' => $this->passwordDecryptor->decrypt($paypalPassword),
                    'acct1.CertPath' => $ppd['certificateFile'],
                ];
            }
        }

        $config['log.LogEnabled'] = '1';
        $config['log.LogLevel'] = 'FINE';
        $config['log.FileName'] = "/tmp/paypal.log";

        return new PayPalAPIInterfaceServiceService($config);
    }
}
