<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\Common\CurrencyConverter\CurrencyConverter;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\Storage;
use Google\Service\Storage\StorageObject;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadAndroidPaymentsCommand extends Command
{
    private const BUCKET = 'pubsite_prod_rev_08107448149206387697';
    private const SMALL_DATE_DIFF_DAYS = 30;

    protected static $defaultName = 'aw:billing:download-android-payments';
    private LoggerInterface $logger;
    private Storage $storage;
    private \Google_Client $client;
    private EntityManagerInterface $entityManager;
    private PlusManager $plusManager;
    private InputInterface $input;
    private Billing $billing;
    private Provider $provider;
    private CurrencyConverter $currencyConverter;

    private ?\DateTime $startFrom = null;
    private bool $success = true;
    private Manager $cartManager;

    public function __construct(
        LoggerInterface $paymentLogger,
        Storage $storage,
        \Google_Client $googleClient,
        EntityManagerInterface $entityManager,
        PlusManager $plusManager,
        Billing $billing,
        Provider $provider,
        CurrencyConverter $currencyConverter,
        Manager $cartManager
    ) {
        parent::__construct();
        $this->logger = new ContextAwareLoggerWrapper($paymentLogger);
        $this->logger->pushContext(['command' => self::$defaultName]);
        $this->storage = $storage;
        $this->client = $googleClient;
        $this->entityManager = $entityManager;
        $this->plusManager = $plusManager;
        $this->billing = $billing;
        $this->provider = $provider;
        $this->currencyConverter = $currencyConverter;
        $this->cartManager = $cartManager;
    }

    public function configure()
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply changes')
            ->addOption('apply-small-date-fixes', null, InputOption::VALUE_NONE, 'apply pay date changes for small differences, less than ' . self::SMALL_DATE_DIFF_DAYS . ' days')
            ->addOption('apply-refunds', null, InputOption::VALUE_NONE)
            ->addOption('apply-recovers', null, InputOption::VALUE_NONE)
            ->addOption('orderNumber', null, InputOption::VALUE_REQUIRED, 'google play order id, like GPA.1325-3925-1410-76607..6')
            ->addOption('startFrom', null, InputOption::VALUE_REQUIRED, 'start date in format Y-m-d')
            ->addOption('lastDays', null, InputOption::VALUE_REQUIRED, 'process only N last days')
            ->addOption('purchaseToken', null, InputOption::VALUE_REQUIRED, 'purchase token when recovering order')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        if ($input->getOption('startFrom') && $input->getOption('lastDays')) {
            $this->logger->error('both startFrom and lastDays options are set, only one is allowed');

            return 1;
        }

        if ($input->getOption('startFrom')) {
            $this->startFrom = new \DateTime($input->getOption('startFrom'));
        }

        if ($input->getOption('lastDays')) {
            $this->startFrom = new \DateTime("-" . $input->getOption('lastDays') . " days");
        }

        $rows = $this->downloadSales();
        $rows = $this->collapseChargesAndRefunds($rows);

        if ($this->startFrom) {
            $rows = array_filter($rows, fn (array $row) => $row['Order Charged Timestamp'] >= $this->startFrom->getTimestamp());
            $this->logger->info("filtered orders, starting from " . $this->startFrom->format("Y-m-d") . ", count: " . count($rows));
        }

        foreach ($rows as $row) {
            $this->processTransaction($row);
        }

        if ($this->success) {
            $this->logger->info("success");
        } else {
            $this->logger->warning("there are some errors, see logs");
        }

        return 0;
    }

    public function findCart(string $orderNumber): ?Cart
    {
        return $this->entityManager->createQuery(
            "select c from AwardWalletMainBundle:Cart c where c.billingtransactionid = :googleOrderId
            and c.paydate is not null"
        )
            ->setParameter("googleOrderId", $orderNumber)
            ->getOneOrNullResult();
    }

    private function downloadSales(): array
    {
        $this->logger->info("downloading google play payments");
        $cacheFolder = sys_get_temp_dir() . "/google-play-payments";

        if (!file_exists($cacheFolder)) {
            mkdir($cacheFolder);
        }

        /** @var Storage\StorageObject[] $reports */
        $reports = $this->storage->objects->listObjects(self::BUCKET, ["prefix" => "sales/"])->getItems();
        $this->logger->info("got " . count($reports) . " reports");
        $http = $this->client->authorize();

        $rows = [];

        foreach ($reports as $report) {
            $rows = array_merge($rows, $this->downloadReport($report, $cacheFolder, $http));
        }

        $this->logger->info("downloaded " . count($rows) . " rows");

        return $rows;
    }

    private function unzip(string $zipFile, string $csvFile): void
    {
        if (file_exists($csvFile) && filesize($csvFile) > 0) {
            return;
        }

        $zip = new \ZipArchive();
        $zip->open($zipFile);

        if ($zip->count() !== 1) {
            throw new \Exception("unexpected number of files in $zipFile");
        }

        file_put_contents($csvFile, $zip->getFromIndex(0));
    }

    private function downloadReport(StorageObject $report, string $cacheFolder, Client $http): array
    {
        if (
            $this->startFrom
            && preg_match('#(\d\d\d\d)(\d\d)\.zip$#ims', $report->getName(), $matches)
            && mktime(0, 0, 0, (int) $matches[2], 1, (int) $matches[1]) < mktime(0, 0, 0, $this->startFrom->format("m"), 1, $this->startFrom->format("Y"))
        ) {
            return [];
        }

        $this->logger->info($report->getName() . ", " . $report->getSize() . " bytes");
        $dir = $cacheFolder . "/" . dirname($report->getName());

        if (!file_exists($dir)) {
            mkdir($dir);
        }

        $zipFile = $cacheFolder . "/" . $report->getName();
        $csvFile = str_replace(".zip", ".csv", $zipFile);

        $this->downloadFile($report, $zipFile, $http);
        $this->unzip($zipFile, $csvFile);

        return $this->loadCsv($csvFile);
    }

    private function downloadFile(StorageObject $report, string $zipFile, Client $http): void
    {
        if (file_exists($zipFile) && filesize($zipFile) == $report->getSize()) {
            $this->logger->info("file already downloaded");

            return;
        }

        $response = $http->get($report->getMediaLink());
        $content = $response->getBody()->getContents();

        if (strlen($content) != $report->getSize()) {
            throw new \Exception("downloaded only " . strlen($content) . " bytes");
        }

        file_put_contents($zipFile, $content);
        $this->logger->info("downloaded");
    }

    private function loadCsv(string $csvFile): array
    {
        $f = fopen($csvFile, "r");
        $header = fgetcsv($f);

        $result = [];

        while ($row = fgetcsv($f)) {
            $result[] = array_combine($header, $row);
        }
        fclose($f);

        return $result;
    }

    private function processTransaction(array $row): void
    {
        // Order Number,Order Charged Date,Order Charged Timestamp,Financial Status,Device Model,Product Title,Product ID,Product Type,SKU ID,Currency of Sale,Item Price,Taxes Collected,Charged Amount,City of Buyer,State of Buyer,Postal Code of Buyer,Country of Buyer,Base Plan ID,Offer ID,Group ID,First USD 1M Eligible,Promotion ID,Coupon Value,Discount Rate,Featured Product ID
        // GPA.3369-1538-8116-43056,2024-01-01,1704112948,Charged,d2x,AwardWallet Plus subscription for 1 year (AwardWallet: Track Rewards),com.itlogy.awardwallet,subscription,plus_subscription_1year,GBP,23.33,4.66,27.99,,,,GB,p1y,,4994392072746424206,Yes,,,,
        if ($this->input->getOption('orderNumber') && $row['Order Number'] !== $this->input->getOption('orderNumber')) {
            return;
        }

        /** @var Cart $cart */
        $cart = $this->findCart($row['Order Number']);
        $this->logger->pushProcessor(function (array $record) use ($cart, $row) {
            $record['context']['GoogleOrderNumber'] = $row['Order Number'];

            if ($cart) {
                $record['context']['CartID'] = $cart->getCartid();
            }

            if ($cart && $cart->getUser()) {
                $record['context']['UserID'] = $cart->getUser()->getId();
            }

            return $record;
        });

        try {
            $this->logger->info("processing transaction, date: " . date("Y-m-d H:i:s", $row['Order Charged Timestamp']) . ", status: " . $row['Financial Status']);

            if ($row['Financial Status'] == 'Charged') {
                $this->processCharge($row, $cart);
            } elseif ($row['Financial Status'] == 'Refund') {
                $this->processRefund($row, $cart);
            } else {
                throw new \Exception("Unknown status: " . $row['Financial Status']);
            }
        } finally {
            $this->logger->popProcessor();
        }
    }

    private function processCharge(array $row, ?Cart $cart): void
    {
        $this->logger->info("processing charge " . $row['Order Number']);

        if ($cart !== null) {
            $this->logger->info("charge found, cart: {$cart->getCartid()}");
            $this->checkPaymentDate($cart, $row['Order Charged Timestamp']);

            return;
        }

        $this->logger->info("cart not found");
        $userId = $this->findUserId($row['Order Number']);

        if ($userId !== null) {
            $this->recoverOrder($row, $userId);

            return;
        }

        // may be it is possible to recover them using purchase token (available in the play orders report)
        // but these orders are already expired, so ignore them
        $ignoredOrders = [
            'GPA.3373-9108-6830-36585', // 2019, expired
            'GPA.3387-5273-9295-73291', // 2019, expired
            'GPA.3354-3649-5123-59932..0', // 2024, user deleted himself
            'GPA.3391-1899-8562-30740', // 2020, expired
            'GPA.3351-9870-6549-92298..0', // 2020, user deleted
        ];

        if (in_array($row['Order Number'], $ignoredOrders)) {
            $this->logger->info("ignored, unknown order");

            return;
        }

        $this->logger->critical("unknown charge: {$row['Order Number']}");
        $this->success = false;
    }

    private function processRefund(array $row, ?Cart $cart): void
    {
        $this->logger->info("processing refund " . $row['Order Number']);

        if ($cart === null) {
            $this->logger->info("cart not found, ok for refund");

            return;
        }

        $this->logger->warning("cart found for refund {$row['Order Number']}, removing", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser() ? $cart->getUser()->getId() : null]);

        if (!$this->input->getOption('apply') && !$this->input->getOption('apply-refunds')) {
            $this->logger->warning("dry run, skipping");
            $this->success = false;

            return;
        }

        $this->cartManager->refund($cart);
    }

    private function checkPaymentDate(Cart $cart, int $date): void
    {
        $daysBetween = floor(abs($cart->getPaydate()->getTimestamp() - $date) / 86400);

        if ($daysBetween < 1) {
            return;
        }

        $this->logger->warning("correcting payment date: CartID: {$cart->getCartid()}, PayDate: {$cart->getPaydate()->format("Y-m-d H:i:s")}, Charge Date: " . date("Y-m-d H:i:s", $date) . ", days between: {$daysBetween}", ["UserID" => $cart->getUser() ? $cart->getUser()->getId() : null]);

        if (!$this->input->getOption('apply') && !($this->input->getOption('apply-small-date-fixes') && $daysBetween <= self::SMALL_DATE_DIFF_DAYS)) {
            $this->logger->warning("dry run, skipping");
            $this->success = false;

            return;
        }

        $cart->setPaydate(new \DateTime("@" . $date));
        $this->entityManager->flush();

        if ($cart->getUser() !== null) {
            $this->plusManager->recalcExpirationDateAndAccountLevel($cart->getUser());
        }

        $this->logger->warning("fixed cart payment date", ["UserID" => $cart->getUser() ? $cart->getUser()->getId() : null]);
    }

    private function collapseChargesAndRefunds(array $rows): array
    {
        usort($rows, function (array $a, array $b) {
            return $a['Order Charged Timestamp'] <=> $b['Order Charged Timestamp'];
        });

        $result = [];

        foreach ($rows as $row) {
            $result[$row['Order Number']] = $row;
        }

        $this->logger->info("got " . count($result) . " rows after charge/refund collapses");

        return $result;
    }

    private function recoverOrder(array $row, int $userId): void
    {
        $this->logger->warning("recovering order {$row['Order Number']} for user $userId");
        $user = $this->entityManager->find(Usr::class, $userId);

        if ($user === null) {
            throw new \Exception("user not found: $userId");
        }

        $productId = $this->provider->getProductId($row['SKU ID']);
        $purchase = AbstractSubscription::create($productId, $user, Cart::PAYMENTTYPE_ANDROIDMARKET, $row['Order Number'], new \DateTime("@" . $row['Order Charged Timestamp']));
        $purchase->setPurchaseToken($this->input->getOption('purchaseToken') ?? $this->getPreviousPurchaseToken($row['Order Number']));

        if (!$this->input->getOption('apply') && !$this->input->getOption('apply-recovers')) {
            $this->logger->warning("dry run, skipping");
            $this->success = false;

            return;
        }

        $amount = round($this->currencyConverter->convertToUsd($row['Item Price'], $row['Currency of Sale']) / 10) * 10;

        $cart = $this->billing->tryUpgrade($purchase, false);
        $cart->getPlusItem()->setPrice($amount);
        $cart->getPlusItem()->setDescription(preg_replace('# \(starting from [^\)]+\)#ims', '', $cart->getPlusItem()->getDescription()));
        $this->entityManager->flush();
        $this->logger->info("set price to: $amount");
    }

    private function getFirstOrderNumber(string $orderNumber): string
    {
        return preg_replace('#\.\.\d+$#ims', '', $orderNumber);
    }

    private function findUserId(string $orderNumber): ?int
    {
        $orderToUserMap = [
            'GPA.3386-9795-7881-59347' => 438802,
            'GPA.3333-3833-0478-71884' => 645996,
            'GPA.3344-5335-4210-35362' => 683214,
            'GPA.3331-4439-0989-17347' => 659224,
            'GPA.3358-4610-3661-58178' => 553679,
            'GPA.3308-7427-2557-26041' => 673982,
        ];

        $firstOrderNumber = $this->getFirstOrderNumber($orderNumber);
        $userId = $orderToUserMap[$firstOrderNumber] ?? null;

        if ($userId !== null) {
            return $userId;
        }

        $firstCart = $this->findCart($firstOrderNumber);

        if ($firstCart === null) {
            return null;
        }

        if ($firstCart->getUser()) {
            return $firstCart->getUser()->getId();
        }

        $this->logger->info("first cart found, but user was deleted");

        return null;
    }

    private function getPreviousPurchaseToken(string $orderNumber): ?string
    {
        $cart = $this->findCart($this->getFirstOrderNumber($orderNumber));

        if ($cart === null) {
            return null;
        }

        return $cart->getPurchaseToken();
    }
}
