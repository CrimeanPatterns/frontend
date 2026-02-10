<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use Monolog\Logger;
use PayPal\EBLBaseComponents\ErrorType;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Exception\PPConfigurationException;
use PayPal\Exception\PPConnectionException;
use PayPal\Exception\PPInvalidCredentialException;
use PayPal\Exception\PPMissingCredentialException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;

class AbstractController extends BaseController
{
    protected LoggerInterface $logger;
    protected LoggerInterface $mainLogger;

    public function __construct(LoggerInterface $paymentLogger, LoggerInterface $logger)
    {
        $this->logger = $paymentLogger;
        $this->mainLogger = $logger;
    }

    protected function processPayPalException(\Exception $e, ?Cart $cart = null)
    {
        $logLevel = Logger::ERROR;

        if ($e instanceof PPConnectionException) {
            $detailed_message = "Error connecting to " . $e->getUrl();
        } elseif ($e instanceof PPMissingCredentialException || $e instanceof PPInvalidCredentialException) {
            $detailed_message = $e->errorMessage();
        } elseif ($e instanceof PPConfigurationException) {
            $detailed_message = "Invalid configuration";
        } elseif ($e instanceof PayPalConnectionException) {
            if ($e->getCode() != 400) {
                $logLevel = Logger::CRITICAL;
            }
            $data = @json_decode($e->getData(), true);

            if (!empty($data['message'])) {
                $detailed_message = $data['message'];

                if (!empty($data['code']) && $data['code'] != 400) {
                    $logLevel = Logger::CRITICAL;
                }
            } else {
                $detailed_message = $e->getMessage();
            }
        } else {
            $detailed_message = "PayPal returned error";
            $logLevel = Logger::CRITICAL;
        }

        $logMessage = sprintf("PayPal error: %s, %s, at %s:%d, CartID: %d, %s, %s", $detailed_message, $e->getMessage(), $e->getFile(), $e->getLine(), isset($cart) ? $cart->getCartid() : 0, get_class($e), $e->getMessage());

        if (isset($cart)) {
            $context = ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()];
        } else {
            $context = [];
        }

        // payment logger
        $this->logger->log($logLevel, $logMessage, $context);
        // main logger
        $this->mainLogger->log($logLevel, $logMessage, $context);

        return $detailed_message;
    }

    protected function ppErrorsToString($errors)
    {
        if (!is_array($errors)) {
            $errors = [$errors];
        }
        $err = [];

        foreach ($errors as $error) {
            /** @var ErrorType $error */
            if (is_object($error) && isset($error->LongMessage)) {
                $err[] = $error->LongMessage . " ({$error->ErrorCode})";
            } else {
                $err[] = (string) $error;
            }
        }

        return implode(', ', $err);
    }
}
