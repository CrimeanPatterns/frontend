<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 17/04/15
 * Time: 12:54.
 */

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Contactus;
use AwardWallet\MainBundle\Service\GoogleClient;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContactUsAnswersCheckCommand extends Command
{
    protected static $defaultName = 'aw:contactusanswers:check';

    protected $connection;

    private $messageType = [
        'criteria' => 'SUBJECT "AwardWallet.com request type: \':REQUESTTYPE\', #:CONTACTUSID"',
    ];
    private EntityManagerInterface $entityManager;
    private string $notificationsMailboxLogin;
    private LoggerInterface $logger;

    public function __construct(GoogleClient $gmailClient, EntityManagerInterface $entityManager, string $notificationsMailboxLogin, LoggerInterface $logger)
    {
        parent::__construct();
        $this->googleClient = $gmailClient;
        $this->entityManager = $entityManager;
        $this->notificationsMailboxLogin = $notificationsMailboxLogin;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Checking answers for "Contact Us" requests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        /** @var GoogleClient $gmailClient */
        $gmailClient = $this->googleClient;
        $gmailClient->fetchAccessTokenWithRefreshToken();
        $service = new \Google_Service_Gmail($gmailClient);
        $entityManager = $this->entityManager;
        $dt = date_create()->modify('-20 days');
        $criteria = Criteria::create()->where(Criteria::expr()->eq('replied', 0))
                                      ->andWhere(Criteria::expr()->gt("datesubmitted", $dt));
        $rows = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Contactus::class)->matching($criteria);

        $login = $this->notificationsMailboxLogin;
        $logger = $this->logger;

        foreach ($rows as $row) {
            /** @var Contactus $row */
            $ContactUsId = $row->getContactusid();
            $RequestType = $row->getRequesttype();
            $criteria = str_replace(':CONTACTUSID', $ContactUsId, $this->messageType['criteria']);
            $criteria = str_replace(':REQUESTTYPE', $RequestType, $criteria);
            $retries = 0;

            do {
                try {
                    $email = $service->users_messages->listUsersMessages($login, ['q' => $criteria]);

                    break;
                } catch (\Google_Service_Exception $exception) {
                    $logger->info($exception->getMessage());

                    if ($exception->getCode() == 429) {
                        sleep(2 ** $retries);
                    } else {
                        throw $exception;
                    }
                }
                $retries++;
            } while ($retries < 5);

            if (!$email) {
                $logger->info('No answer for ContactUs #: ' . $ContactUsId);

                continue;
            }

            $row->setReplied(true);
            $entityManager->persist($row);
            $logger->info('ContactUs #' . $ContactUsId . ' \'Replied\' set to 1');
        }

        $entityManager->flush();
        $logger->info("ContactUs Answers Check DONE!");

        return 0;
    }
}
