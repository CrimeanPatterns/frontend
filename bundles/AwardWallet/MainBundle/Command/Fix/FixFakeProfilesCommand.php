<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use Doctrine\DBAL\Connection;
use PayPal\Exception\PayPalConnectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixFakeProfilesCommand extends Command
{
    protected static $defaultName = 'aw:fix-fake-profiles';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var PaypalRestApi
     */
    private $paypalApi;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $warnings = [];

    public function __construct(
        Connection $connection,
        PaypalRestApi $paypalRestApi
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->paypalApi = $paypalRestApi;
    }

    protected function configure()
    {
        $this
            ->setDescription('fix paypal fake profiles');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $users = $this->connection->executeQuery("select UserID, FirstName, LastName, Email, AccountLevel 
        from Usr 
        where PaypalRecurringProfileID like 'fake%'")->fetchAll(\PDO::FETCH_ASSOC);
        $output->writeln("fixing fake profiles, " . count($users) . " users found");

        foreach ($users as $user) {
            $this->fixUser($user);
        }

        $output->writeln("done, " . count($users) . " users");

        foreach ($this->warnings as $warning) {
            $output->writeln($warning);
        }

        return 0;
    }

    private function fixUser(array $user)
    {
        $this->output->writeln("user: {$user['UserID']}, {$user['FirstName']} {$user['LastName']}, {$user['Email']}");

        if ($user['AccountLevel'] != ACCOUNT_LEVEL_AWPLUS) {
            throw new \Exception("User is not Plus, stopping");
        }
        $cart = $this->loadCart($user['UserID']);
        $cardData = $this->getCardData($cart, $user);
        $this->output->writeln("card: " . json_encode($cardData));
        $cardId = null;

        if (empty($cardData['securityCode'])) {
            $this->addWarning($user, $cart, 'Empty CVV2');
        }

        try {
            $cardId = $this->paypalApi->saveCreditCard($cardData, $user['FirstName'], $user['LastName'], $user['UserID'], $cart['CartID']);
        } catch (PayPalConnectionException $e) {
            if (stripos($e->getData(), '{"field":"number","issue":"Value is invalid"}')) {
                $this->addWarning($user, $cart, "Failed to save card: " . $e->getData());
            } else {
                throw $e;
            }
        }

        if (!empty($cardId)) {
            $this->output->writeln("saved card, id: $cardId");
            $rows = $this->connection->executeUpdate("update Usr set 
            PaypalRecurringProfileID = :cardId, Subscription = " . Usr::SUBSCRIPTION_SAVED_CARD . "
            where UserID = :userId", ["cardId" => $cardId, "userId" => $user['UserID']]);

            if ($rows != 1) {
                throw new \Exception("Could not save cardId");
            }
        }
    }

    private function loadCart($userId)
    {
        $lastCart = $this->connection->executeQuery("select c.CartID, c.PayDate, c.PaymentType, c.CreditCardNumber 
        from Cart c
        join CartItem ci on c.CartID = ci.CartID
        where c.UserID = {$userId} and c.PayDate is not null
        and ci.TypeID = " . AwPlusSubscription::TYPE . "
        order by c.PayDate desc")->fetch(\PDO::FETCH_ASSOC);

        if (empty($lastCart)) {
            throw new \Exception("No paid carts found for this user, stopping");
        }
        $this->output->writeln("last paid cart: {$lastCart['CartID']}, {$lastCart['PayDate']}, PaymentType: {$lastCart['PaymentType']}, Card: {$lastCart['CreditCardNumber']}");

        if ($lastCart['PaymentType'] != PAYMENTTYPE_CREDITCARD) {
            throw new \Exception("Cart was not paid by credit card, stopping");
        }

        if (empty($lastCart['CreditCardNumber'])) {
            throw new \Exception("No credit card recorded, stopping");
        }
        $payDate = strtotime($lastCart['PayDate']);

        if ($payDate < strtotime("2016-12-24") || $payDate > strtotime("2017-01-07")) {
            throw new \Exception("Invalid date range");
        }

        return $lastCart;
    }

    private function getCardData(array $cart, array $user)
    {
        $command = "zgrep 'CartID: {$cart['CartID']}' /www/awardwallet/data/payments/*.gz -B 12 -A 10";
        exec($command, $output, $exitCode);

        if ($exitCode != 1) {
            throw new \Exception("failed to run zgrep");
        }

        if (count($output) == 0) {
            throw new \Exception("payments logs not found");
        }
        file_put_contents('/tmp/cartLogs.log', implode("\n", $output) . "\n");
        $ar = array_filter($output, function ($text) use ($cart) { return stripos($text, "(10752), CartID: " . $cart['CartID']) !== false; });

        if (empty($ar)) {
            throw new \Exception("10752 not found");
        }
        $index = array_keys($ar)[0];
        $this->output->writeln(array_pop($ar) . ", line " . $index);
        $request = $this->findRequest(
            $output,
            [substr($cart['CreditCardNumber'], -4) . '</ebl:CreditCardNumber>', 'Order #' . $cart['CartID']]
        );
        $this->output->writeln($request);
        $data = $this->extractCardData($request);

        if (empty($data['securityCode'])) {
            $data['securityCode'] = $this->findCVV2($data['cardNumber']);
        }

        return $data;
    }

    private function findRequest(array $output, array $lookFor)
    {
        for ($index = 0; $index < count($output); $index++) {
            $line = $output[$index];

            if (stripos($line, 'Request: ') !== false) {
                foreach ($lookFor as $str) {
                    if (stripos($line, $str) === false) {
                        continue 2;
                    }
                }
                $result = substr($line, stripos($line, 'Request: ') + strlen('Request: '));

                return $result;
            }
        }

        throw new \Exception("Request not found");
    }

    private function extractCardData($request)
    {
        $data = [
            'cardType' => strtolower($this->getTagValue($request, 'ebl:CreditCardType')),
            'cardNumber' => $this->getTagValue($request, 'ebl:CreditCardNumber'),
            'expirationMonth' => intval($this->getTagValue($request, 'ebl:ExpMonth')),
            'expirationYear' => intval($this->getTagValue($request, 'ebl:ExpYear')),
            'securityCode' => $this->getTagValue($request, 'ebl:CVV2', true),
        ];

        if ($data['expirationMonth'] < 1 || $data['expirationMonth'] > 12) {
            throw new \Exception("Invalid expiration month");
        }

        if ($data['expirationYear'] < 2016 || $data['expirationYear'] > 2050) {
            throw new \Exception("Invalid expiration year");
        }

        return $data;
    }

    private function findCVV2($cardNumber)
    {
        exec("zgrep $cardNumber /www/awardwallet/data/payments/*.gz", $output, $exitCode);

        if ($exitCode != 1) {
            throw new \Exception("failed to exec zgrep");
        }

        foreach ($output as $line) {
            $cvv = $this->getTagValue($line, "ebl:CVV2", true);

            if (!empty($cvv)) {
                return $cvv;
            }
        }

        return '';
    }

    private function getTagValue($text, $tag, $allowEmpty = false)
    {
        if (!preg_match("#<" . preg_quote($tag) . ">([^<]+)</" . preg_quote($tag) . ">#ims", $text, $matches)) {
            if ($allowEmpty) {
                return '';
            } else {
                throw new \Exception("failed to find $tag");
            }
        }

        return $matches[1];
    }

    private function addWarning(array $user, array $cart, $message)
    {
        $this->warnings[] = "user {$user['UserID']}, cart {$cart['CartID']}: " . $message;
        $this->output->writeln("WARNING: " . $message);
    }
}
