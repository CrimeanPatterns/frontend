<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\Exception\PayPalConnectionException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixCancelPaypalProfileCommand extends Command
{
    protected static $defaultName = 'aw:fix-cancel-paypal-profile';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaypalRestApi
     */
    private $paypalRestApi;

    /**
     * @var PaypalSoapApi
     */
    private $paypalSoapApi;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        $paymentLogger,
        PaypalRestApi $paypalRestApi,
        PaypalSoapApi $paypalSoapApi
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->logger = $paymentLogger;
        $this->paypalRestApi = $paypalRestApi;
        $this->paypalSoapApi = $paypalSoapApi;
    }

    protected function configure()
    {
        $this
            ->setDescription('Cancel PayPal profiles when mobile subscription issued')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED, 'filter by UserID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var EntityManagerInterface $em */
        $em = $this->entityManager;

        $processed = 0;
        $cancelled = 0;

        $qb = $em->createQueryBuilder();
        $e = $qb->expr();

        $qb
            ->select('u')
            ->from(Usr::class, 'u')
            ->where(
                $e->andX(
                    $e->eq('u.accountlevel', ACCOUNT_LEVEL_AWPLUS),
                    $e->eq('u.subscription', Usr::SUBSCRIPTION_MOBILE),
                    $e->isNotNull('u.paypalrecurringprofileid')
                )
            );

        if (!empty($input->getOption("userId"))) {
            $qb->andWhere(
                $e->eq('u.userid', intval($input->getOption("userId")))
            );
        }

        $users = $qb->getQuery();

        foreach ($users->iterate() as $user) {
            /** @var Usr $user */
            $user = $user[0];

            try {
                if (stripos($user->getPaypalrecurringprofileid(), 'CARD-') === 0) {
                    $this->logger->info("delete saved card", ['UserID' => $user->getUserid()]);
                    $this->deleteSavedCard($user);
                } else {
                    $this->logger->info("cancel paypal profile", ['UserID' => $user->getUserid()]);
                    $this->cancelPayPal($user);
                }
                $user->setPaypalrecurringprofileid(null);
                $em->flush();
                $cancelled++;
            } catch (\Exception $e) {
                $this->logger->critical("failed to cancel recurring payment profile, CANCEL THROUGH PAYPAL: " . $e->getMessage(), ["UserID" => $user->getUserid(), "ProfileID" => $user->getPaypalrecurringprofileid(), "exception" => $e]);
            }

            $processed++;

            if (($processed % 100) == 0) {
                $em->clear();
            }
        }

        $output->writeln("done, processed $processed users, cancelled: $cancelled");

        return 0;
    }

    private function cancelPayPal(Usr $user)
    {
        $profile = new \ManageRecurringPaymentsProfileStatusRequestDetailsType();
        $profile->ProfileID = $user->getPaypalrecurringprofileid();
        $profile->Action = 'Cancel';

        $manageRPPStatusReqest = new \ManageRecurringPaymentsProfileStatusRequestType();
        $manageRPPStatusReqest->ManageRecurringPaymentsProfileStatusRequestDetails = $profile;

        $manageRPPStatusReq = new \ManageRecurringPaymentsProfileStatusReq();
        $manageRPPStatusReq->ManageRecurringPaymentsProfileStatusRequest = $manageRPPStatusReqest;

        $this->paypalSoapApi->getPaypalService()->ManageRecurringPaymentsProfileStatus($manageRPPStatusReq);
    }

    /**
     * @throws PayPalConnectionException
     */
    private function deleteSavedCard(Usr $user)
    {
        try {
            $this->paypalRestApi->deleteSavedCard($user->getPaypalrecurringprofileid());
        } catch (PayPalConnectionException $e) {
            $data = @json_decode($e->getData(), true);
            $this->logger->warning("paypal api exception: ", ['paypal_error' => $data, "UserID" => $user->getUserid()]);

            if (!empty($data['name']) && $data['name'] == 'INVALID_RESOURCE_ID') {
                $this->logger->warning("credit card already deleted", ["UserID" => $user->getUserid(), "profileId" => $user->getPaypalrecurringprofileid()]);
            } else {
                throw $e;
            }
        }
    }
}
