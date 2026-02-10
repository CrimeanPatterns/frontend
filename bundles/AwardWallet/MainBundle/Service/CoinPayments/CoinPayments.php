<?php

namespace AwardWallet\MainBundle\Service\CoinPayments;

use AwardWallet\MainBundle\Entity\Cart;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CoinPayments
{
    private $publicKey;

    private $privateKey;

    private RouterInterface $router;

    private \HttpDriverInterface $httpDriver;

    public function __construct($coinPaymentsPublicKey, $coinPaymentsPrivateKey, RouterInterface $router, \HttpDriverInterface $httpDriver)
    {
        $this->publicKey = $coinPaymentsPublicKey;
        $this->privateKey = $coinPaymentsPrivateKey;
        $this->router = $router;
        $this->httpDriver = $httpDriver;
    }

    public function call($cmd, $req = [])
    {
        $public_key = $this->publicKey;
        $private_key = $this->privateKey;

        // Set the API command and required fields
        $req['version'] = 1;
        $req['cmd'] = $cmd;
        $req['key'] = $public_key;
        $req['format'] = 'json'; // supported values are json and xml

        // Generate the query string
        $post_data = http_build_query($req, '', '&');
        // Calculate the HMAC signature on the POST data
        $hmac = hash_hmac('sha512', $post_data, $private_key);

        $response = $this->httpDriver->request(new \HttpDriverRequest(
            'https://www.coinpayments.net/api.php', 'POST', $req, ['HMAC' => $hmac], 30)
        );

        return json_decode($response->body, true);
    }

    public function createTransaction(Cart $cart, $currencyCode)
    {
        $payload = [
            'amount' => $cart->getTotalPrice(),
            'currency1' => 'USD',
            'currency2' => $currencyCode,
            'buyer_name' => $cart->getUser()->getFullName(),
            'item_name' => (string) $cart,
            'custom' => $cart->getCartid(),
            'ipn_url' => $this->router->generate('aw_cart_coinpayments_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        return $this->call('create_transaction', $payload);
    }

    public function transactionInfo($txid)
    {
        return $this->call('get_tx_info', [
            'txid' => $txid,
        ]);
    }
}
