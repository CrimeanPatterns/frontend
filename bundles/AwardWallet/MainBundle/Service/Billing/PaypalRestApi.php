<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Api\Amount;
use PayPal\Api\CreditCard;
use PayPal\Api\CreditCardToken;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Psr\Log\LoggerInterface;
use Stripe\Exception\CardException;

class PaypalRestApi
{
    /**
     * @var ApiContext
     */
    private $apiContext;

    private $ratio = 1;
    /**
     * @var LoggerInterface
     */
    private $logger;

    //    private $ratio = 0.001; // for tests

    public function __construct($clientId, $secret, $live, LoggerInterface $logger)
    {
        $this->apiContext = new ApiContext(new OAuthTokenCredential(
            $clientId,
            $secret
        ));
        $this->apiContext->setConfig([
            'mode' => $live ? 'live' : 'sandbox',
            'log.LogEnabled' => true,
            'log.FileName' => '/tmp/paypal.log',
            'log.LogLevel' => 'DEBUG',
        ]);
        $this->logger = $logger;
    }

    public function getApiContext()
    {
        return $this->apiContext;
    }

    /**
     * @return string - transaction id
     */
    public function payWithCard(Cart $cart, $cardData, $total, ?\Closure $itemFilter = null): string
    {
        $this->logger->info("payWithCard", ["UserID" => $cart->getUser()->getUserid(), "CartID" => $cart->getCartid(), "Total" => $total]);
        $card = new CreditCard();
        $card->setType(strtolower($cardData['cardType']))
            ->setNumber($cardData['cardNumber'])
            ->setExpireMonth($cardData['expirationMonth'])
            ->setExpireYear($cardData['expirationYear'])
            ->setCvv2($cardData['securityCode']);

        $fi = new FundingInstrument();
        $fi->setCreditCard($card);

        return $this->payFromFundingInstrument($cart, $fi, $total, $itemFilter);
    }

    public function saveCreditCard(array $cardData, $firstName, $lastName, $userId, $cartId)
    {
        $cardId = $userId . ":" . time() . "." . StringHandler::getPseudoRandomString(5);
        $this->logger->info("saving card $cardId", ["UserID" => $userId]);

        $card = new CreditCard();

        $card->setType(strtolower($cardData['cardType']))
            ->setNumber($cardData["cardNumber"])
            ->setExpireMonth($cardData["expirationMonth"])
            ->setExpireYear($cardData["expirationYear"])
            ->setCvv2($cardData["securityCode"])
            ->setFirstName($firstName)
            ->setLastName($lastName)
        ;

        $card->setMerchantId("awardwallet");
        $card->setExternalCardId($cardId);
        $card->setExternalCustomerId($userId);

        $card->create($this->apiContext);

        $this->logger->info("saved card $cardId, id: " . $card->getId(), ["UserID" => $userId]);

        return $card->getId();
    }

    public function payWithSavedCard(Cart $cart, $cardId, $total, ?\Closure $itemFilter = null)
    {
        $this->logger->info("payWithSavedCard $cardId", ["UserID" => $cart->getUser()->getUserid(), "CartID" => $cart->getCartid(), "Total" => $total]);
        $this->checkCardId($cardId);

        $creditCardToken = new CreditCardToken();
        $creditCardToken->setCreditCardId($cardId);

        $fi = new FundingInstrument();
        $fi->setCreditCardToken($creditCardToken);

        return $this->payFromFundingInstrument($cart, $fi, $total, $itemFilter);
    }

    /**
     * @return CreditCard
     */
    public function getCardInfo($cardId)
    {
        return CreditCard::get($cardId, $this->apiContext);
    }

    public function deleteSavedCard($cardId)
    {
        $this->logger->info("delete saved card $cardId");
        $this->checkCardId($cardId);
        $card = CreditCard::get($cardId, $this->apiContext);
        $card->delete($this->apiContext);
    }

    public function checkSavedCard($cardId, Usr $user)
    {
        $this->logger->info("checkSavedCard $cardId", ["UserID" => $user->getUserid()]);
        $this->checkCardId($cardId);

        $creditCardToken = new CreditCardToken();
        $creditCardToken->setCreditCardId($cardId);

        $fi = new FundingInstrument();
        $fi->setCreditCardToken($creditCardToken);

        $payer = $this->userToPayer($user);
        $payer
            ->setPaymentMethod("credit_card")
            ->setFundingInstruments([$fi]);

        $amount = new Amount();
        $amount
            ->setCurrency("USD")
            ->setTotal(0.01);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription('Credit Card check');

        $payment = new Payment();
        $payment->setIntent("authorize")
            ->setPayer($payer)
            ->setTransactions([$transaction]);

        $payment->create($this->apiContext);

        $transactions = $payment->getTransactions();
        $relatedResources = $transactions[0]->getRelatedResources();
        $authorization = $relatedResources[0]->getAuthorization();
        $authorization->void($this->apiContext);
    }

    /**
     * @return \PayPal\Api\AgreementTransaction[]
     */
    public function getProfileTransactions($profileId)
    {
        return Agreement::searchTransactions($profileId, ['start_date' => '1980-01-01', 'end_date' => date('Y-m-d', strtotime('+5 days'))], $this->apiContext)->agreement_transaction_list;
    }

    public function cancelAgreement($agreementId)
    {
        $this->logger->info("cancelling agreement", ["agreementId" => $agreementId]);
        $agreement = Agreement::get($agreementId, $this->apiContext);

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Deleting the agreement");
        $agreement->cancel($agreementStateDescriptor, $this->apiContext);
    }

    public function getPaymentInfo(string $paymentId): Payment
    {
        return Payment::get($paymentId, $this->apiContext);
    }

    private function checkCardId($cardId)
    {
        if (empty($cardId)) {
            throw new \Exception("Empty saved card");
        }

        if (!preg_match("#^CARD\-#ims", $cardId)) {
            throw new CardException("Invalid saved card: $cardId");
        }
    }

    private function payFromFundingInstrument(Cart $cart, FundingInstrument $fi, $total, ?\Closure $itemFilter = null)
    {
        $payer = $this->userToPayer($cart->getUser());
        $payer
            ->setPaymentMethod("credit_card")
            ->setFundingInstruments([$fi]);

        $transaction = $this->cartToTransaction($cart, $total, $itemFilter);
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setTransactions([$transaction]);

        $payment->create($this->apiContext);

        return $payment->getId();
    }

    private function cartToTransaction(Cart $cart, $total, ?\Closure $itemFilter = null)
    {
        $itemList = new ItemList();

        foreach ($cart->getItems() as $cartItem) {
            if (!empty($itemFilter) && !$itemFilter($cartItem)) {
                continue;
            }
            $item = new Item();
            $item->setName($cartItem->getName())
                ->setDescription($cartItem->getDescription())
                ->setCurrency('USD')
                ->setQuantity(max($cartItem->getCnt(), 1))
                ->setPrice($cartItem->getTotalPrice() / $cartItem->getCnt() * $this->ratio);
            $itemList->addItem($item);
        }

        $amount = new Amount();
        $amount
            ->setCurrency("USD")
            ->setTotal($total * $this->ratio);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription('Order #' . $cart->getCartid())
            ->setInvoiceNumber($cart->getCartid() . "-" . time());

        return $transaction;
    }

    private function userToPayer(Usr $user)
    {
        $payer = new Payer();
        $payer
                    ->setPayerInfo(new PayerInfo([
                        'first_name' => $user->getFirstname(),
                        'last_name' => $user->getLastname(),
                        'email' => $user->getEmail(),
                    ]))
        ;

        return $payer;
    }
}
