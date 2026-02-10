<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\Dumper;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Fix10752Command extends Command
{
    protected static $defaultName = 'aw:fix-10752';

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
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var array
     */
    private $warnings = [];

    private $corrections = [];

    /**
     * @var PlusManager
     */
    private $plusManager;

    /**
     * @var UsrRepository
     */
    private $userRep;

    private EntityManagerInterface $entityManager;
    private $paypalParameter;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaypalRestApi $paypalApi,
        PlusManager $plusManager,
        $paypalParameter
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->paypalApi = $paypalApi;
        $this->plusManager = $plusManager;
        $this->userRep = $entityManager->getRepository(Usr::class);
        $this->paypalParameter = $paypalParameter;
    }

    protected function configure()
    {
        $this
            ->setDescription('fix users affected by paypal error 10752')
            ->addArgument('mode', InputArgument::REQUIRED, 'one of: show, fixMissing, findCarts, showTransaction, refund, fixPaypal, fix1Year')
            ->addOption('cartId', 'c', InputOption::VALUE_REQUIRED, 'CartID for findCarts mode')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED, 'UserID for refund mode')
            ->addOption('transactionId', 't', InputOption::VALUE_REQUIRED, 'TransactionID for showTransaction, refund mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        try {
            switch ($input->getArgument('mode')) {
                case 'show':
                    $this->showTransactions($this->downloadTransactions());

                    break;

                case 'fixMissing':
                    $this->fixMissingTransactions($this->downloadTransactions());

                    break;

                case 'fix1Year':
                    $this->fix1Year($this->downloadTransactions());

                    break;

                case 'findCarts':
                    $this->findCarts($this->downloadTransactions(), $input->getOption('cartId'));

                    break;

                case 'showTransaction':
                    $this->io->writeln(json_encode($this->getTransactionDetails($input->getOption('transactionId')), JSON_PRETTY_PRINT));

                    break;

                case 'fixPaypal':
                    $this->fixPaypal($input->getOption('userId'));

                    break;

                case 'refund':
                    $details = $this->getTransactionDetails($input->getOption('transactionId'), false);
                    $status = $this->refundTransaction($input->getOption('transactionId'));
                    $this->addCorrection("issued refund ($status) to " . $this->transactionInfo($details));

                    break;

                default:
                    throw new \Exception('Unknown mode');
            }
            $this->io->success("done");
            $this->showWarnings();
            $warnings = $this->warnings;
            $this->warnings = [];

            foreach ($warnings as $warning) {
                $this->correctWarning($warning);
            }

            if (!empty($this->corrections)) {
                $this->io->title("applied " . count($this->corrections) . " corrections");

                foreach ($this->corrections as $message) {
                    $this->io->writeln($message);
                }
            }
            $this->showWarnings();
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
            $this->io->writeln("at " . $e->getFile() . ":" . $e->getLine());

            return 0;
        }

        return 0;
    }

    /**
     * @param \PaymentTransactionSearchResultType[] $transactions
     */
    protected function fix1Year(array $transactions)
    {
        $carts = $this->connection->executeQuery("select 
        c.CartID, u.FirstName, u.LastName, u.Email, c.PayDate, ci.CartItemID, ci.Name
        from Cart c join CartItem ci on c.CartID = ci.CartID join Usr u on c.UserID = u.UserID
        where ci.TypeID = " . AwPlus1Year::TYPE . " and ci.Price = 0 and c.PayDate > '2016-12-20'")->fetchAll(\PDO::FETCH_ASSOC);
        $this->output->writeln("found " . count($carts) . " 1 year upgrades with zero price");

        foreach ($carts as $cart) {
            $paid = array_filter($transactions, function (\PaymentTransactionSearchResultType $transaction) use ($cart) {
                $matched =
                    !empty($transaction->GrossAmount)
                    && round($transaction->GrossAmount->value) > 0
                    && $transaction->Type == 'Payment'
                    && $transaction->Status == 'Completed'
                    && strtolower($transaction->Payer) == strtolower($cart['Email']);

                if ($matched) {
                    $details = $this->getTransactionDetails($transaction->TransactionID);
                    $info = $this->parsePaypalDetails($details);

                    if ($info['upgrades'] == 0) {
                        $matched = false;
                    }
                }

                return $matched;
            });

            if (!empty($paid)) {
                $transaction = array_shift($paid);
                $this->addCorrection("correcting price in order {$cart['CartID']} from {$cart['PayDate']}, by transaction {$transaction->TransactionID}");
                $this->connection->executeUpdate("update CartItem set Price = 10 where CartItemID = {$cart['CartItemID']}");
                $this->connection->executeUpdate("update Cart set BillingTransactionID = :trId where CartID = {$cart['CartID']}", ["trId" => $transaction->TransactionID]);
            }
        }
    }

    protected function fixPaypal($userId)
    {
        $sql = "select u.UserID, u.FirstName, u.LastName, u.Email, max(c.CartID) as CartID, 
        min(c.PayDate) as PayDate,
        count(distinct c.CartID) as Carts
        from Cart c join Usr u on c.UserID = u.UserID
        join CartItem i on c.CartID = i.CartID
        where c.PaymentType = 5 and c.PayDate > '2017-01-01'
        and i.TypeID = 16 and i.Price > 0
        " . (!empty($userId) ? "and c.UserID = $userId" : "") . "
        group by c.UserID
        having count(distinct c.CartID) > 1
        order by min(c.PayDate)";
        $carts = $this->connection->executeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $this->io->title("refunding");
        $this->io->table(array_keys($carts[0]), $carts);

        foreach ($carts as $cart) {
            $trId = $this->connection->executeQuery("select BillingTransactionID from Cart where CartID = {$cart['CartID']}")->fetchColumn(0);

            if (empty($trId)) {
                throw new \Exception("missing transactionId for cart {$cart['CartID']}");
            }
            $status = $this->refundTransaction($trId);
            $this->io->writeln("{$status} {$trId} for cart {$cart['CartID']}");
            $this->deleteOrder($cart);
            $this->addCorrection("{$status} transaction {$trId} for cart {$cart['CartID']} of user {$cart['UserID']}");
        }
    }

    protected function refundTransaction($trId)
    {
        $service = $this->getPaypalService();
        $request = new \RefundTransactionReq();
        $request->RefundTransactionRequest = new \RefundTransactionRequestType();
        $request->RefundTransactionRequest->TransactionID = $trId;
        $response = $service->RefundTransaction($request);

        //            $response = new \RefundTransactionResponseType();
        //            $response->Ack = "Success";
        if ($response->Ack == "Failure" && $response->Errors[0]->LongMessage == 'This transaction has already been fully refunded') {
            $status = "already refunded";
        } elseif ($response->Ack != "Success") {
            throw new \Exception("failed to refund transaction {$trId} for cart {$cart['CartID']}");
        } else {
            $status = 'refunded';
        }

        return $status;
    }

    protected function deleteOrder(array $cart)
    {
        $expiration = $this->userRep->getAccountExpiration($cart['UserID']);
        $oldExp = date("Y-m-d", $expiration['date']);
        $this->connection->executeUpdate("delete from Cart where CartID = :cartId", ["cartId" => $cart['CartID']]);
        $expiration = $this->userRep->getAccountExpiration($cart['UserID']);
        $newExp = date("Y-m-d", $expiration['date']);
        /** @var Usr $user */
        $user = $this->userRep->find($cart['UserID']);
        $this->plusManager->correctExpirationDate($user, $expiration['date'], 'fixing paypal, refund');
        $this->io->writeln("delete order {$cart['CartID']} of user {$cart['UserID']} {$user->getEmail()} {$user->getFirstname()} {$user->getLastname()}, old exp date: $oldExp, new exp date: $newExp");
    }

    protected function findCarts($transactions, $cartId)
    {
        if (empty($cartId)) {
            throw new \Exception("specify --cartId=xxx");
        }

        $transactions = array_filter($transactions, function (\PaymentTransactionSearchResultType $transaction) {
            return
                !empty($transaction->GrossAmount)
                && round($transaction->GrossAmount->value) == 10
                && $transaction->Type == 'Payment'
                && $transaction->Status == 'Completed';
        });

        $transactions = array_filter($transactions, function (\PaymentTransactionSearchResultType $transaction) use ($cartId) {
            $cart = $cart = $this->findTransactionCart($transaction);

            return !empty($cart) && $cart['CartID'] == $cartId;
        });

        $this->showTransactions($transactions);
    }

    /**
     * @return \PaymentTransactionSearchResultType[]
     * @throws \Exception
     */
    protected function downloadTransactions()
    {
        $startDate = strtotime("2016-12-20");
        $endDate = strtotime("2017-01-01");
        //        $endDate = ceil(time() / 3600) * 3600;
        $fullCacheFile = '/tmp/paypalTransactions' . date("Y-m-dTH:i:s", $startDate) . '-' . date("Y-m-dTH:i:s", $endDate) . '.cache';

        if (file_exists($fullCacheFile)) {
            $this->output->writeln("loaded transactions from cache");

            return unserialize(file_get_contents($fullCacheFile));
        }

        $service = $this->getPaypalService();
        $step = 3600;
        $this->output->writeln("downloading transactions");
        $result = [];
        $n = 0;
        $tps = [];
        $reduces = 0;

        do {
            $caches = glob("/tmp/paypalTransactions" . date('Y-m-d\TH:i:s\Z', $startDate) . "-*.cache");

            if (count($caches) == 1) {
                if (preg_match('#(.{20})\.cache#ims', $caches[0], $matches)) {
                    $cachedStep = strtotime($matches[1]) - $startDate;

                    if ($cachedStep > 0) {
                        $step = $cachedStep;
                    } else {
                        unlink($caches[0]);
                    }
                }
            }
            $cacheFile = "/tmp/paypalTransactions" . date('Y-m-d\TH:i:s\Z', $startDate) . "-" . date('Y-m-d\TH:i:s\Z', $startDate + $step) . ".cache";

            if (file_exists($cacheFile)) {
                $response = unserialize(file_get_contents($cacheFile));
            } else {
                $request = new \TransactionSearchReq();
                $request->TransactionSearchRequest = new \TransactionSearchRequestType(date('Y-m-d\TH:i:s\Z', $startDate));
                $request->TransactionSearchRequest->EndDate = date('Y-m-d\TH:i:s\Z', $startDate + $step);
                $response = $service->TransactionSearch($request);

                if ($response->Ack == "SuccessWithWarning" && !empty($response->Errors) && $response->Errors[0]->ErrorCode == "11002") {
                    $step = floor($step / 2);
                    $this->output->writeln("too many results at " . date('Y-m-d\TH:i:s\Z', $startDate) . ", reducing step to " . $step);
                    $reduces++;

                    continue;
                }

                if ($response->Ack != "Success") {
                    throw new \Exception("failed to download transactions, ack: " . $response->Ack);
                }

                if (!empty($response->Errors)) {
                    throw new \Exception("failed to download transactions, errors: " . json_encode($response->Errors, JSON_PRETTY_PRINT));
                }

                if ($response->PaymentTransactions === null) {
                    $response->PaymentTransactions = [];
                }
                $caches = glob("/tmp/paypalTransactions" . date('Y-m-d\TH:i:s\Z', $startDate) . "-*.cache");

                foreach ($caches as $file) {
                    unlink($file);
                }
                file_put_contents($cacheFile, serialize($response));
                $this->output->writeln(date("c", $startDate) . " - " . date("c", $startDate + $step) . ", step $step: " . count($response->PaymentTransactions));
            }
            $result = array_merge($result, $response->PaymentTransactions);
            $startDate += $step;
            $count = count($response->PaymentTransactions);

            $tps[] = $count / $step;

            if (count($tps) > 5) {
                array_shift($tps);
            }
            $avgTps = array_sum($tps) / count($tps);

            if ($avgTps > 0 && $reduces == 0) {
                $suggestedStep = round(100 / $avgTps);
                $suggestedChange = abs($step - $suggestedStep) / $step;

                if ($suggestedChange > 1.5) {
                    $oldStep = $step;
                    $change = min(max($suggestedChange, 0.5), 2);
                    $step = $step + round($step * $change) * gmp_sign(strval($suggestedStep - $step));
                    $this->io->writeln("increasing step from $oldStep to $step, because suggested step is $suggestedStep");
                }
            }
            $nextHour = ceil(($startDate + $step) / 3600) * 3600;

            if ($nextHour != ($startDate + $step) && $step >= 3600) {
                $step = $nextHour - $startDate;
                $this->io->writeln("correcting step to hour start: $step");
            }
            $n++;

            if (($n % 100) == 0) {
                $this->output->writeln("$n requests, " . date("c", $startDate) . " - " . date("c", $startDate + $step));
            }

            if ($reduces > 0) {
                $reduces--;
            }
        } while ($startDate < $endDate);

        $this->output->writeln("done, downloaded " . count($result) . " transactions");
        usort($result, function ($a, $b) {
            /** @var \PaymentTransactionSearchResultType $a */
            /** @var \PaymentTransactionSearchResultType $b */
            return strcmp($a->Timestamp, $b->Timestamp);
        });
        file_put_contents($fullCacheFile, serialize($result));

        return $result;
    }

    /**
     * @param \PaymentTransactionSearchResultType[] $transactions
     */
    protected function showTransactions(array $transactions)
    {
        $rows = array_map(function ($transaction) {
            /** @var \PaymentTransactionSearchResultType $transaction */
            if (!empty($transaction->GrossAmount)) {
                $amount = $transaction->GrossAmount->value;
            } else {
                $amount = null;
            }

            return [$transaction->Timestamp, $transaction->Type, $transaction->PayerDisplayName, $transaction->TransactionID, $transaction->Status, $amount];
        }, $transactions);
        $this->io->table(['Time', 'Type', 'Payer', 'TransactionID', 'Status', 'Amount'], $rows);
    }

    /**
     * @param \PaymentTransactionSearchResultType[] $transactions
     */
    protected function fixMissingTransactions(array $transactions)
    {
        $transactions = array_filter($transactions, function ($transaction) {
            /** @var \PaymentTransactionSearchResultType $transaction */
            return
                !empty($transaction->GrossAmount)
                && in_array(round($transaction->GrossAmount->value), [10, 20, 30, 40, 50])
                && ($transaction->Type == 'Payment' || $transaction->Type == 'Recurring Payment')
                && $transaction->Status == 'Completed';
        });

        $count = 0;
        $cartToTran = [];

        foreach ($transactions as $transaction) {
            $cart = $this->findTransactionCart($transaction);

            if (!empty($cart)) {
                $cartToTran[$cart['CartID']][] = $transaction;
            }
            $count++;

            if (($count % 100) == 0) {
                $this->output->writeln("processed $count transactions..");
            }
        }

        $doubles = array_filter($cartToTran, function (array $items) {
            return count($items) > 1;
        });

        foreach ($doubles as $cartId => $linkedTxs) {
            $linkedTxs = array_map(function (\PaymentTransactionSearchResultType $tx) {
                $details = $this->getTransactionDetails($tx->TransactionID);

                return [
                    'TransactionID' => $tx->TransactionID,
                    'PayDate' => $tx->Timestamp,
                    'Amount' => $details->PaymentTransactionDetails->PaymentInfo->GrossAmount->value,
                    'InvoiceID' => $details->PaymentTransactionDetails->PaymentItemInfo->InvoiceID,
                    'Payer' => $details->PaymentTransactionDetails->PayerInfo->Payer
                        . " " . $details->PaymentTransactionDetails->PayerInfo->PayerName->FirstName
                        . " " . $details->PaymentTransactionDetails->PayerInfo->PayerName->LastName,
                    'Items' => array_map(function (\PaymentItemType $item) {
                        return [
                            'Name' => $item->Name,
                            'Cnt' => $item->Quantity,
                            'Price' => $item->Amount->value,
                        ];
                    }, $details->PaymentTransactionDetails->PaymentItemInfo->PaymentItem),
                ];
            }, $linkedTxs);
            $cartInfo = $this->loadAndShowCart($cartId);
            $this->addWarning("cart contains multiple transactions", ["cart" => $cartInfo, 'transactions' => $linkedTxs]);
        }
    }

    protected function correctWarning(array $warning)
    {
        switch ($warning['message']) {
            case 'cart found, but is not paid':
                $this->giveAWPlus($warning['context']['cart'], $warning['context']['transaction']);

                break;

            case 'cart found, but total is 0':
                $this->correctTotal($warning['context']['cart'], $warning['context']['transaction']);

                break;

            case 'could not find cart':
                $this->correctMissingCart($warning['context']['transaction']);

                break;

            case 'cart contains multiple transactions':
                $this->correctMultipleTransactions($warning['context']['cart'], $warning['context']['transactions']);

                break;
        }
    }

    protected function correctMultipleTransactions(array $cart, array $transactions)
    {
        $dates = array_map(function (array $tran) {
            return strtotime($tran['PayDate']);
        }, $transactions);
        $range = max($dates) - min($dates);

        if ($range < SECONDS_PER_DAY * 5) {
            array_shift($transactions);

            foreach ($transactions as $tx) {
                $this->addCorrection("refunding duplicate transaction {$tx['TransactionID']} for cart {$cart['user']['CartID']}"
                . ", user {$cart['user']['UserID']}, {$cart['user']['FirstName']} {$cart['user']['LastName']} {$cart['user']['Email']}");
                $this->refundTransaction($tx['TransactionID']);
            }
        } else {
            $this->addWarning(
                "time range is too big",
                ["cart" => $cart, "transactions" => $transactions]
            );
        }
    }

    protected function correctMissingCart(\PaymentTransactionSearchResultType $transaction)
    {
        $details = $this->getTransactionDetails($transaction->TransactionID, false);

        if ($details->PaymentTransactionDetails->PaymentInfo->PaymentStatus == 'Refunded') {
            $this->addWarning("transaction: " . $this->transactionInfo($details) . " is already refunded, reset cache");

            return;
        } else {
            $this->addWarning("Can't understand how to find transaction " . $this->transactionInfo($details));
        }
    }

    protected function transactionInfo(\GetTransactionDetailsResponseType $details)
    {
        return "{$details->PaymentTransactionDetails->PaymentInfo->TransactionID} {$details->PaymentTransactionDetails->PaymentInfo->PaymentDate} {$details->PaymentTransactionDetails->PayerInfo->PayerName->FirstName} {$details->PaymentTransactionDetails->PayerInfo->PayerName->LastName} {$details->PaymentTransactionDetails->PayerInfo->Payer}";
    }

    protected function giveAWPlus(array $cart, \PaymentTransactionSearchResultType $transaction)
    {
        global $arCartItemName, $objCart;

        $details = $this->getTransactionDetails($transaction->TransactionID, false);

        if ($details->PaymentTransactionDetails->PaymentInfo->PaymentStatus == 'Refunded') {
            $this->addWarning("cart {$cart['user']['CartID']}, user: {$cart['user']['UserID']}, by transaction: {$transaction->TransactionID} is already refunded, reset cache");

            return;
        }

        if ($this->userHasUpgradeNear($cart['user']['UserID'], strtotime($details->PaymentTransactionDetails->PaymentInfo->PaymentDate))) {
            $status = $this->refundTransaction($transaction->TransactionID);
            $this->addCorrection("issued refund ($status) to user " . $this->userInfo($cart['user']['UserID']) . ", was already given upgrade, transaction: {$transaction->TransactionID}, cart {$cart['user']['CartID']}");

            return;
        }
        $this->addCorrection("given AWPlus to unpaid cart {$cart['user']['CartID']}, user: " . $this->userInfo($cart['user']['UserID']) . ", by transaction: {$transaction->TransactionID}");

        throw new \Exception("migrate to new CartManager, if you need this code");
        //        $objSecurity = \TSecurity::getInstance();
        //       	$objSecurity->LoginUser($cart['user']['UserID'], true);
        //        $objCartManager = new \TCartManager();
        //        $objCart->Clear();
        //       	$objCart->Add(CART_ITEM_AWPLUS_1, 0, $cart['user']['UserID'], $arCartItemName[CART_ITEM_AWPLUS_1], 1, 10, null, 0, "" );
        //       	$objCart->SetPaymentType(PAYMENTTYPE_CREDITCARD);
        //       	$objCartManager->manageRecurringProfile = false;
        //       	$objCartManager->MarkAsPayed();
        //        $this->connection->executeUpdate("update Cart set BillingTransactionID = :tranId
        //        where CartID = :cartId", ["tranId" => $transaction->TransactionID, 'cartId' => $objCart->ID]);
    }

    protected function userHasUpgradeNear($userId, $date)
    {
        $cart = $this->connection->executeQuery("select c.CartID, c.PayDate
        from Cart c join CartItem ci on c.CartID = ci.CartID 
        where UserID = :userId 
        and ci.TypeID in (" . implode(", ", [AwPlusSubscription::TYPE, AwPlus1Year::TYPE, AwPlus::TYPE, AwPlusRecurring::TYPE]) . ")
        and c.PayDate >= adddate(:date, -30) 
        and c.PayDate <= adddate(:date, 30)",
            ['userId' => $userId, 'date' => date('Y-m-d', $date)])->fetch(\PDO::FETCH_ASSOC);

        if (!empty($cart)) {
            $this->io->writeln("found existing upgrade {$cart['CartID']} for user $userId at {$cart['PayDate']}");

            return true;
        } else {
            return false;
        }
    }

    protected function userInfo($userId)
    {
        $user = $this->connection->executeQuery("select UserID, FirstName, LastName, Email, AccountLevel, PlusExpirationDate
        from Usr where UserID = :userId", ['userId' => $userId])->fetch(\PDO::FETCH_ASSOC);

        return implode(", ", $user);
    }

    protected function correctTotal(array $cart, \PaymentTransactionSearchResultType $transaction)
    {
        $this->io->title("correcting total on cart {$cart['CartID']}, tr id: {$transaction->TransactionID}");
        $cartInfo = $this->loadAndShowCart($cart['CartID']);
        $details = $this->getTransactionDetails($transaction->TransactionID, false);

        if ($details->PaymentTransactionDetails->PaymentInfo->PaymentStatus == 'Refunded') {
            $this->addCorrection("deleting refunded cart {$cart['CartID']}, tr id: {$transaction->TransactionID}");
            $this->deleteOrder($cart);

            return;
        }

        if ($details->PaymentTransactionDetails->PaymentInfo->PaymentStatus != 'Completed') {
            throw new \Exception("transaction {$transaction->TransactionID} in invalid state: {$details->PaymentTransactionDetails->PaymentInfo->PaymentStatus}");
        }

        $paypal = $this->parsePaypalDetails($details);

        if ($paypal['total'] != 10) {
            throw new \Exception("invalid total {$paypal['total']} in cart {$cart['CartID']}");
        }

        $onecardsLeft = getOneCardsCount($cart['UserID'])['Left'];
        $this->io->writeln("detected upgrades: {$paypal['upgrades']}, onecards: {$paypal['onecards']}, onecards left: $onecardsLeft");

        if ($paypal['onecards'] == 0 && $paypal['upgrades'] == 0) {
            throw new \Exception("can't determine cart contents, cart {$cart['CartID']}, user: {$cart['UserID']}");
        }

        if ($paypal['onecards'] > 0 && $paypal['upgrades'] == 0) {
            throw new \Exception("missing onecard in cart {$cart['CartID']}, user: {$cart['UserID']}, by transaction: {$transaction->TransactionID}, refund and delete cart?");
        }

        //        if($onecards > 0) {
        //            $cardRow = $this->connection->executeQuery("select * from CartItem
        //            where CartID = :cartId and TypeID = " . CART_ITEM_ONE_CARD, ["cartId" => $cart['CartID']])->fetch(\PDO::FETCH_ASSOC);
        //            if(empty($cardRow)){
        //                $this->addCorrection("inserting onecards into zero cart {$cart['CartID']}, user: {$cart['UserID']}, by transaction: {$transaction->TransactionID}");
        //                $this->connection->executeUpdate("insert into CartItem
        //                (CartID,	TypeID,	ID,	Name,	Cnt,	Price,	Discount)
        //                values(:cartId, " . CART_ITEM_ONE_CARD . ", :userId, :name, :cnt, 10, 0)",
        //                ["cartId" => $cart['CartID'], 'userId' => $cart['UserID'], 'name' => $arCartItemName[CART_ITEM_ONE_CARD], 'cnt' => $onecards]);
        //                return;
        //            }
        //        }
        //
        if ($paypal['upgrades'] > 0) {
            if ($cartInfo['onecards'] > 0 && $cartInfo['upgrades'] == 0) {
                if ($onecardsLeft == 0) {
                    $this->addCorrection("applying \$10 to onecard in cart {$cart['CartID']}, user: {$cart['UserID']}, by transaction: {$transaction->TransactionID}");
                    $affected = $this->connection->executeUpdate("update CartItem set Price = 10 
                    where CartID = :cartId and TypeID = " . CART_ITEM_ONE_CARD, ["tranId" => $transaction->TransactionID, 'cartId' => $cart['CartID']]);
                } else {
                    $this->addCorrection("converting onecard to upgrade and applying \$10 in cart {$cart['CartID']}, user: {$cart['UserID']}, by transaction: {$transaction->TransactionID}");
                    $affected = $this->connection->executeUpdate("update CartItem 
                    set Price = 10, TypeID = " . AwPlusSubscription::TYPE . ", Name = :name 
                    where CartID = :cartId and TypeID = " . CART_ITEM_ONE_CARD,
                        ["tranId" => $transaction->TransactionID, 'cartId' => $cart['CartID'], 'name' => $paypal['plusName']]);
                }
            } else {
                $this->addCorrection("applying \$10 to upgrade in cart {$cart['CartID']}, user: {$cart['UserID']}, by transaction: {$transaction->TransactionID}");
                $affected = $this->connection->executeUpdate("update CartItem set Price = 10 
                where CartID = :cartId and TypeID = 4", ["tranId" => $transaction->TransactionID, 'cartId' => $cart['CartID']]);
            }

            if ($affected == 0) {
                throw new \Exception("Failed to correct price in Cart {$cart['CartID']}");
            }
        }
    }

    protected function parsePaypalDetails(\GetTransactionDetailsResponseType $details)
    {
        $result = [
            'onecards' => 0,
            'upgrades' => 0,
            'total' => 0,
            'rows' => [],
            'plusName' => 'AwardWallet Plus 1 Year',
        ];

        foreach ($details->PaymentTransactionDetails->PaymentItemInfo->PaymentItem as $item) {
            /** @@var \PaymentItemType $item */
            if (preg_match('#OneCard Credits#ims', $item->Name)) {
                $result['onecards']++;
                $result['total'] += intval($item->Quantity) * 10;
            } elseif (preg_match('#AwardWallet 12 meses|Set up recurring payment|Account upgrade|subscription|AwardWallet Plus#ims', $item->Name)) {
                $result['upgrades']++;
                $result['plusName'] = $item->Name;
                $result['total'] += intval($item->Quantity) * 10;
            } else {
                throw new \Exception("unknown item: " . $item->Name . " in transaction");
            }
            $result['rows'][] = [$item->Name, $item->Quantity, $item->Amount->value];
        }
        $this->io->writeln("PayPal contents, total: " . $details->PaymentTransactionDetails->PaymentInfo->GrossAmount->value
            . ", PayDate: " . $details->PaymentTransactionDetails->PaymentInfo->PaymentDate
            . ", onecards: {$result['onecards']}, upgrades: {$result['upgrades']}");
        $this->io->table(["Name", "Quantity", "Price"], $result['rows']);

        return $result;
    }

    protected function loadAndShowCart($cartId)
    {
        $user = $this->connection->executeQuery("select c.CartID, c.PaymentType, u.UserID, u.Email, u.FirstName, u.LastName, u.AccountLevel, 
        u.PaypalRecurringProfileID, c.PayDate  
        from Cart c join Usr u on c.UserID = u.UserID 
        where c.CartID = :cartId", ['cartId' => $cartId])->fetch(\PDO::FETCH_ASSOC);
        $this->io->writeln("User info");
        $this->io->table(array_keys($user), [$user]);
        $items = $this->connection->executeQuery("select TypeID, Name, Description, Price, Cnt, Discount 
        from CartItem where CartID = :cartId", ['cartId' => $cartId])->fetchAll(\PDO::FETCH_ASSOC);
        $result = [
            'user' => $user,
            'upgrades' => 0,
            'onecards' => 0,
            'items' => $items,
        ];

        foreach ($items as $item) {
            if (!isset($itemTypes[$item['TypeID']])) {
                $itemTypes[$item['TypeID']] = $item['Cnt'];
            } else {
                $itemTypes[$item['TypeID']] += $item['Cnt'];
            }

            if (in_array($item['TypeID'], [CART_ITEM_ONE_CARD])) {
                $result['onecards']++;
            }

            if (in_array($item['TypeID'], [AwPlus::TYPE, AwPlus1Year::TYPE, AwPlusRecurring::TYPE, AwPlusSubscription::TYPE])) {
                $result['upgrades']++;
            }
        }

        if (empty($items)) {
            $this->io->writeln("cart {$cartId} is empty");
        } else {
            $this->io->writeln("Database contents, onecards: {$result['onecards']}, upgrades: {$result['upgrades']}");
            $this->io->table(array_keys($result['items'][0]), $result['items']);
        }

        return $result;
    }

    protected function addCorrection($message)
    {
        $this->io->writeln("CORRECTION: " . $message);
        $this->corrections[] = $message;
    }

    protected function findTransactionCart(\PaymentTransactionSearchResultType $transaction)
    {
        $result = null;
        $carts = $this->connection->executeQuery("select UserID, CartID, PayDate, PaymentType
        from Cart where BillingTransactionID = :transactionId", ['transactionId' => $transaction->TransactionID])->fetchAll(\PDO::FETCH_ASSOC);

        if (count($carts) > 1) {
            throw new \Exception("Too many carts for {$transaction->TransactionID}");
        }

        if (empty($carts)) {
            $details = $this->getTransactionDetails($transaction->TransactionID);
            $result = $this->findCartByTranDetails($details);

            if (empty($result)) {
                $this->addWarning("could not find cart", ['transaction' => $transaction]);
            } else {
                $cartDate = strtotime($result['PayDate']);
                $payDate = strtotime($transaction->Timestamp);
                $details = $this->getTransactionDetails($transaction->TransactionID);
                $this->io->writeln("transaction: " . $this->transactionInfo($details));
                $diff = abs($cartDate - $payDate);
                $paypal = $this->parsePaypalDetails($details);
                $cartInfo = $this->loadAndShowCart($result['CartID']);

                if ($cartInfo['upgrades'] > 0 && $paypal['upgrades'] > 0) {
                    $maxDiff = SECONDS_PER_DAY * 30;
                } else {
                    $maxDiff = 3600;
                }

                if (empty($result['PayDate'])) {
                    $this->addWarning("cart found, but is not paid", ['transaction' => $transaction, 'cart' => $cartInfo]);
                    $result = null;
                } elseif (empty($result['PaymentType'])) {
                    $this->io->writeln("cart found {$result['CartID']}, but paid as free");
                    $result = null;
                } elseif ($diff > $maxDiff) {
                    $this->io->writeln("diff: $diff, maxDiff: $maxDiff");
                    $this->addWarning("cart found, but pay dates does not match", ['transaction' => $details, 'cart' => $cartInfo]);
                    $result = null;
                } elseif ($cartInfo['upgrades'] == 0 && $cartInfo['onecards'] == 0) {
                    $this->io->writeln("cart found, but does not contain upgrades or onecards");
                    $result = null;
                }
            }
        } else {
            $result = $carts[0];
        }

        if (!empty($result)) {
            $result['Total'] = $this->connection->executeQuery("select sum(Cnt * Price * ((100 - Discount)/100))
            from CartItem where CartID = :cartId", ['cartId' => $result['CartID']])->fetchColumn(0);

            if ($result['Total'] == 0) {
                $this->addWarning('cart found, but total is 0', ['transaction' => $transaction, 'cart' => $result]);
                $result = null;
            }
        }

        return $result;
    }

    protected function findCartByTranDetails(\GetTransactionDetailsResponseType $details)
    {
        if (
            preg_match('#(\d+)paid on#ims', $details->PaymentTransactionDetails->PaymentItemInfo->InvoiceID, $matches)
            || preg_match('#^(\d+)\-\d+$#ims', $details->PaymentTransactionDetails->PaymentItemInfo->InvoiceID, $matches)
        ) {
            $cart = $this->connection->executeQuery("select CartID, UserID, BillingTransactionID, PaymentType, PayDate 
            from Cart where CartID = :cartId", ['cartId' => $matches[1]])->fetch(\PDO::FETCH_ASSOC);

            if (!empty($cart)) {
                return $cart;
            }
        }

        return null;
    }

    protected function showUserTransactions($userName, $transactions)
    {
        $this->io->title("Transactions of $userName");
        $rows = array_map(function ($transaction) {
            /** @var \PaymentTransactionSearchResultType $transaction */
            if (!empty($transaction->GrossAmount)) {
                $amount = $transaction->GrossAmount->value;
            } else {
                $amount = null;
            }

            return [$transaction->Timestamp, $transaction->Type, $transaction->TransactionID, $transaction->Status, $amount];
        }, $transactions);
        $this->io->table(['Time', 'Type', 'TransactionID', 'Status', 'Amount'], $rows);
    }

    protected function searchUser($email, $name, $transactionId)
    {
        $user = $this->loadUserBy(['Email' => $email]);

        if (!empty($user)) {
            return $user;
        }

        $user = $this->connection->executeQuery("select u.UserID, u.Email, u.FirstName, u.LastName, u.AccountLevel
            from Usr u join Cart c on u.UserID = c.UserID where c.BillingTransactionID = :tranId",
            ['tranId' => $transactionId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!empty($user)) {
            return $user;
        }

        $cart = $this->findCartByTranDetails($this->getTransactionDetails($transactionId));

        if (!empty($cart)) {
            $user = $this->loadUserBy(["UserID" => $cart['UserID']]);

            if (!empty($user)) {
                return $user;
            }
        }

        $users = $this->connection->executeQuery("select UserID, Email, FirstName, LastName, AccountLevel
            from Usr where concat(FirstName, ' ', LastName) = :name",
            ['name' => $name]
        )->fetchAll(\PDO::FETCH_ASSOC);

        if (count($users) > 1) {
            $this->io->table(array_keys($users[0]), $users);

            throw new \Exception("Too many users found for $email, $name");
        }

        if (empty($users)) {
            throw new \Exception("user $email, $name not found");
        }

        return $users[0];
    }

    protected function loadUserBy($criteria)
    {
        $user = $this->connection->executeQuery("select UserID, Email, FirstName, LastName, AccountLevel
            from Usr where " . ImplodeAssoc(" = :", " and ", array_combine(array_keys($criteria), array_keys($criteria))), $criteria
        )->fetch(\PDO::FETCH_ASSOC);

        return $user;
    }

    protected function getPaypalService()
    {
        $paypalParams = $this->paypalParameter;
        $config = $paypalParams['profiles']['live'];

        if (isset($paypalParams['globals'])) {
            $config = array_merge($config, $paypalParams['globals']);
        }

        $config['log.LogEnabled'] = '1';
        $config['log.LogLevel'] = 'FINE';
        $config['log.FileName'] = "/var/log/www/awardwallet/fix10752.log";

        $service = new \PayPalAPIInterfaceServiceService($config);

        return $service;
    }

    /**
     * @return \GetTransactionDetailsResponseType
     */
    private function getTransactionDetails($transactionId, $useCache = true)
    {
        $cacheFile = "/tmp/paypal-tran-" . $transactionId . ".cache";

        if ($useCache && file_exists($cacheFile)) {
            return unserialize(file_get_contents($cacheFile));
        }

        $service = $this->getPaypalService();
        $request = new \GetTransactionDetailsReq();
        $request->GetTransactionDetailsRequest = new \GetTransactionDetailsRequestType();
        $request->GetTransactionDetailsRequest->TransactionID = $transactionId;
        $response = $service->GetTransactionDetails($request);

        file_put_contents($cacheFile, serialize($response));

        return $response;
    }

    private function addWarning($message, array $context = [])
    {
        $this->io->warning($message);

        foreach ($context as $key => $value) {
            if (!empty($value)) {
                $this->io->title($key);
                $this->io->writeln(json_encode(Dumper::filterEmpty($value), JSON_PRETTY_PRINT));
            }
        }
        $this->warnings[] = ["message" => $message, "context" => $context];
    }

    private function showWarnings()
    {
        $totals = [];

        foreach ($this->warnings as $warning) {
            if (!isset($totals[$warning['message']])) {
                $totals[$warning['message']] = 0;
            }
            $totals[$warning['message']]++;
        }

        if (!empty($totals)) {
            $rows = [];

            foreach ($totals as $key => $value) {
                $rows[] = [$key, $value];
            }
            $this->io->table(["warning", "count"], $rows);
        }

        if (!empty($this->warnings)) {
            $this->io->error("found " . count($this->warnings) . " warnings, see above");
        }
    }
}
