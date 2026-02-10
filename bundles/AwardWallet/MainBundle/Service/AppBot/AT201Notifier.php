<?php

namespace AwardWallet\MainBundle\Service\AppBot;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;

class AT201Notifier
{
    private const SLACK_BOT_NAME = 'AT 201 Notification';

    private const TYPE_SUBSCRIBE = 1;
    private const TYPE_UNSUBSCRIBE = 2;
    private const TYPE_RECURRING = 3;

    /** @var AppBot */
    private $bot;
    private string $basePath;

    public function __construct(AppBot $bot, string $basePath)
    {
        $this->bot = $bot;
        $this->basePath = $basePath;
    }

    public function subscribed(Cart $cart): void
    {
        $item = $cart->getAT201Item();

        if ($item === null) {
            return;
        }
        $this->bot->send(
            Slack::CHANNEL_AW_AT101_MODS,
            $this->buildSlackMessage(
                [
                    'duration' => $item->getMonths(),
                    'name' => $cart->getUser()->getFullName(),
                    'email' => $cart->getUser()->getEmail(),
                    'payment' => $cart->getPaymenttype(),
                ],
                self::TYPE_SUBSCRIBE
            ),
            self::SLACK_BOT_NAME,
            ':aw:'
        );
    }

    public function unsubscribed(Cart $cart, $isManually = false): void
    {
        $item = $cart->getAT201Item();

        if ($item === null) {
            return;
        }

        $this->bot->send(
            Slack::CHANNEL_AW_AT101_MODS,
            $this->buildSlackMessage(
                [
                    'duration' => $item->getMonths(),
                    'name' => $cart->getUser()->getFullName(),
                    'email' => $cart->getUser()->getEmail(),
                    'payment' => $cart->getPaymenttype(),
                    'isManually' => $isManually,
                    'facebookUrl' => !empty($cart->getUser()->getFacebookUserId()) ?
                        sprintf('https://facebook.com/%s', $cart->getUser()->getFacebookUserId()) : null,
                ],
                self::TYPE_UNSUBSCRIBE
            ),
            self::SLACK_BOT_NAME,
            ':aw:'
        );
    }

    public function recurringPayment(Cart $cart): void
    {
        $item = $cart->getAT201Item();

        if (null === $item) {
            return;
        }

        $this->bot->send(
            Slack::CHANNEL_AW_AT101_MODS,
            $this->buildSlackMessage(
                [
                    'duration' => $item->getMonths(),
                    'name' => $cart->getUser()->getFullName(),
                    'email' => $cart->getUser()->getEmail(),
                    'payment' => $cart->getPaymenttype(),
                ],
                self::TYPE_RECURRING
            ),
            self::SLACK_BOT_NAME,
            ':aw:'
        );
    }

    private function buildSlackMessage(array $params, int $type): string
    {
        $messages = [
            self::TYPE_SUBSCRIBE => 'Award Travel 201 member started paying the subscription!',
            self::TYPE_UNSUBSCRIBE => 'Award Travel 201 member stopped paying the subscription!',
            self::TYPE_RECURRING => 'Award Travel 201 member renewed their subscription (*no action required*)!',
        ];
        $subscriptionTypes = [
            1 => 'Monthly',
            6 => 'Semi-Annual',
            12 => 'Yearly',
        ];
        $paymentMethods = [
            Cart::PAYMENTTYPE_CREDITCARD => 'Credit Card',
            Cart::PAYMENTTYPE_TEST_CREDITCARD => 'Test Credit Card',
            Cart::PAYMENTTYPE_PAYPAL => 'PayPal',
        ];
        $message = $messages[$type] ?? '';
        $subscriptionType = $subscriptionTypes[$params['duration']] ?? '';
        $paymentMethod = $paymentMethods[$params['payment']] ?? '';

        $result = "_{$message}_\n" .
            "*Name*: {$params['name']}\n" .
            "*Email*: {$params['email']}\n" .
            "*Subscription type*: {$subscriptionType}\n" .
            "*Payment method*: {$paymentMethod}\n";

        /* cancelation reason: canceled manually by the user (или failed to automatically charge the user) */
        if ($type === self::TYPE_UNSUBSCRIBE) {
            $result .= '*Cancelation Reason*: ' . ($params['isManually'] ? 'canceled manually by the user' : 'failed to automatically charge the user') . "\n";

            if (!empty($params['facebookUrl'])) {
                $result .= "<{$params['facebookUrl']}|*Facebook Profile*>";
            }
        }

        if (in_array($type, [self::TYPE_SUBSCRIBE, self::TYPE_RECURRING], true)) {
            $url = $this->basePath . "/manager/sonata/at-201-group/list?filter[userid__email][value]={$params['email']}";
            $result .= "<{$url}|View on AwardWallet>";
        }

        return $result;
    }
}
