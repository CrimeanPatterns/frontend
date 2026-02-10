<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProvider;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\PlusItems;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProviderAbstract;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\AwPlusExpired;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\AwPlusExpireSoon;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class ExpireAwPlusDataProvider extends DataProviderAbstract
{
    protected EntityManager $em;

    protected LoggerInterface $logger;

    protected Mailer $mailer;

    /**
     * @var int[]
     */
    protected array $users = [];

    protected bool $notifyExpiresSoon = false;

    private Sender $notificationSender;

    private LocalizeService $localizer;

    /**
     * @var float
     */
    private $lastPrice;

    private ?int $lastType = null;

    /**
     * @var \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    private $query;

    /**
     * @var Usr
     */
    private $currentUser;

    /**
     * @var CartRepository
     */
    private $cartRep;

    /**
     * @var bool
     */
    private $filterByDate;

    private PlusManager $plusManager;

    private int $processed = 0;

    private ExpirationCalculator $expirationCalculator;

    public function __construct(
        EntityManager $em,
        LoggerInterface $logger,
        Mailer $mailer,
        Sender $sender,
        LocalizeService $localizer,
        PlusManager $plusManager,
        ExpirationCalculator $expirationCalculator
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->notificationSender = $sender;
        $this->localizer = $localizer;

        $this->cartRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $this->plusManager = $plusManager;
        $this->expirationCalculator = $expirationCalculator;
    }

    /**
     * @param int[] $users
     * @return ExpireAwPlusDataProvider
     */
    public function setFilterUsersIds(array $users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @param bool $notifyExpiresSoon
     * @return ExpireAwPlusDataProvider
     */
    public function setNotifyExpiresSoon($notifyExpiresSoon)
    {
        $this->notifyExpiresSoon = $notifyExpiresSoon;

        return $this;
    }

    public function setFilterByDate($byDate)
    {
        $this->filterByDate = $byDate;

        return $this;
    }

    public function next()
    {
        if (empty($this->query)) {
            $this->executeSQL();
        }

        $row = $this->query->current();
        $this->query->next();
        $result = $row !== null;

        if ($result) {
            $this->currentUser = $row;
        }
        $this->processed++;

        if (($this->processed % 100) == 0) {
            $this->em->clear();
            $this->logger->info("processed {$this->processed} users, mem: " . round(memory_get_usage(true) / 1024 / 1024, 1) . " Mb..");
        }

        return $result;
    }

    public function getMessage(Mailer $mailer)
    {
        $this->logger->debug(__METHOD__, ["UserID" => $this->currentUser->getId()]);
        $template = new AwPlusExpired($this->currentUser);

        if ($this->lastPrice == 0) {
            if ($this->isTrialAccount($this->currentUser)) {
                $template->trial = true;
            }
        } else {
            $template->lastPayment = round($this->lastPrice, 2);
            $template->lastType = $this->lastType;
        }
        $this->logger->debug("getMessage done", ["UserID" => $this->currentUser->getId()]);

        return $mailer->getMessageByTemplate($template);
    }

    public function canSendEmail()
    {
        $this->logger->debug(__METHOD__, ["UserID" => $this->currentUser->getId()]);

        if ($this->currentUser->getAccountlevel() != ACCOUNT_LEVEL_AWPLUS) {
            $this->plusManager->correctExpirationDate($this->currentUser, null, "not plus");

            return false;
        }

        $expireData = $this->expirationCalculator->getAccountExpiration($this->currentUser->getId());
        $refund = false;

        if (is_null($expireData['lastPrice'])) {
            $this->log($this->currentUser, "was downgraded, but email was not sent - no payments data (refund?)");
            $refund = true;
        }
        $this->plusManager->correctExpirationDate($this->currentUser, $expireData['date'], "expiration recalculated");
        $this->lastPrice = $expireData['lastPrice'];
        $this->lastType = $expireData['lastItemType'];

        $hasActivity = $this->currentUser->getAccounts() > 0;

        if ($hasActivity
            && date(DATE_FORMAT, $expireData['date']) == date(DATE_FORMAT, strtotime('+1 month'))
            && empty($this->currentUser->getPaypalrecurringprofileid())
            && empty($this->plusManager->getBusinessConnections($this->currentUser))
            && is_null($this->cartRep->getActiveAwSubscription($this->currentUser))
            && $this->notifyExpiresSoon
        ) {
            // send notification
            $template = new AwPlusExpireSoon($this->currentUser);
            $template->expireDate = new \DateTime("@" . $expireData['date']);
            $template->lastType = $this->lastType;
            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send($message);
            $this->log($this->currentUser, "was noticed about expiration");
            $this->sendPush($this->currentUser, $template->expireDate);
        }

        $downgraded = $this->plusManager->checkExpirationAndDowngrade($this->currentUser);

        return $downgraded && $hasActivity && !$refund;
    }

    protected function sendPush(Usr $user, \DateTime $expirationDate)
    {
        $this->logger->debug(__METHOD__, ["UserID" => $user->getUserid()]);
        $devices = $this->notificationSender->loadDevices([$user], MobileDevice::TYPES_ALL, Content::TYPE_MEMBERSHIP_EXPIRES);

        if (!$devices) {
            return;
        }

        $this->notificationSender->send(
            new Content(
                new Trans("plus_expires_soon.subject", [], "email"),
                new Trans("plus_expires_soon.desc", [
                    '%span_on%' => '',
                    '%span_off%' => '',
                    '%span2_on%' => '',
                    '%span2_off%' => '',
                    '%date%' => function ($id, $params, $domain, $locale) use ($expirationDate) {
                        return $this->localizer->formatDateTime($expirationDate, 'medium', 'none', $locale);
                    },
                ], "email"),
                Content::TYPE_MEMBERSHIP_EXPIRES,
                Content::TARGET_PAY,
                (new Options())
                    ->setInterruptionLevel(InterruptionLevel::ACTIVE)
            ),
            $devices
        );
    }

    protected function executeSQL()
    {
        $this->logger->debug(__METHOD__);
        $builder = $this->em->createQueryBuilder();

        $builder
            ->select('u')
            ->from(Usr::class, 'u')
            ->where('u.accountlevel = :level');
        $builder->setParameter(':level', ACCOUNT_LEVEL_AWPLUS, \PDO::PARAM_INT);

        // add user filter
        if (sizeof($this->users)) {
            $builder->andWhere('u.userid in (:userId)');
            $builder->setParameter(':userId', $this->users, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        $builder->andWhere('u.plusExpirationDate <= :date');

        if ($this->notifyExpiresSoon) {
            $date = new \DateTime("+35 day");
        } // add gap for rounding errors when -6 month, + 5 month
        else {
            $date = new \DateTime("-" . PlusManager::SUBSCRIPTION_GRACE_PERIOD . " day");
        }
        $this->logger->info("filtering by date", ["date" => $date]);
        $builder->setParameter("date", $date);

        $this->logger->debug("creating query");
        $this->query = $builder->getQuery()->toIterable();
        $this->logger->debug("query created");
    }

    private function isTrialAccount(Usr $user): bool
    {
        $sql = "
            SELECT
			    ci.TypeID
		    FROM
			    Cart c
			    JOIN CartItem ci ON c.CartID = ci.CartID
		    WHERE
			    c.PayDate IS NOT NULL
			    AND c.UserID = ?
			    AND ci.TypeID IN (" . implode(',', PlusItems::getTypes()) . ")
		    ORDER BY c.PayDate DESC
		    LIMIT 2
        ";

        $result = $this->em->getConnection()->executeQuery($sql, [$user->getId()], [\PDO::PARAM_INT]);
        $row = $result->fetchAssociative();

        return $row !== false
            && in_array((int) $row['TypeID'], CartItem::TRIAL_TYPES, true)
            && $result->fetchAssociative() === false;
    }

    private function log(Usr $user, $str)
    {
        $this->logger->info(sprintf("%s (%d) %s", $user->getEmail(), $user->getUserid(), $str));
    }
}
