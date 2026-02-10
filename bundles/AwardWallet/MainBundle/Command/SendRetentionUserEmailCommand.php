<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 13.07.15
 * Time: 16:15.
 */

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\RetentionUser;
use AwardWallet\MainBundle\Service\AccountCounter\Counter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendRetentionUserEmailCommand extends Command
{
    protected static $defaultName = 'aw:send-email:retention-user';

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    private $output;
    /**
     * @var Connection
     */
    private $connection;

    private $totalErrorsCounter = 0;
    /**
     * @var EmailLog|object
     */
    private $emailLog;
    private EntityManagerInterface $entityManager;
    private Mailer $mailer;
    private Reader $geoIpReader;
    private $geoIpCountryParameter;

    private Counter $accountCounter;

    public function __construct(
        EntityManagerInterface $entityManager,
        Mailer $mailer,
        Reader $geoIpReader,
        EmailLog $emailLog,
        $geoIpCountryPathParameter,
        Counter $accountCounter
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->mailer = $mailer;
        $this->geoIpReader = $geoIpReader;
        $this->emailLog = $emailLog;
        $this->geoIpCountryParameter = $geoIpCountryPathParameter;
        $this->accountCounter = $accountCounter;
    }

    public function notifyUser($userId, $ad = null)
    {
        $em = $this->entityManager;
        $repo = $em->getRepository(Usr::class);
        /** @var Usr $user */
        $user = $repo->findBy(['userid' => $userId]);

        if (empty($user)) {
            return false;
        }

        $user = $user[0];
        $accountsCount = $this->accountCounter->calculate($userId)->getCountAccounts(0);

        try {
            $countryCode = $this->geoIpReader
                ->country($user->getRegistrationip())->country->isoCode;
        } catch (\Exception $e) {
            $countryCode = null;
        }

        $fromUS = $countryCode === 'US';
        $template = new RetentionUser($user);
        $template->fromUS = $fromUS;
        $template->accountsCount = $accountsCount;
        $template->ad = $fromUS ? $ad : null;
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message);
        $this->emailLog->recordEmailToLog($userId, EmailLog::MESSAGE_KIND_RETENTION_USER);

        return true;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send Emails card advert and user retention')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED, 'send test email to this user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        if (!empty($input->getOption('userId'))) {
            $this->notifyUser($input->getOption('userId'));

            return 0;
        }

        $query =
<<<SQL
      SELECT
          a.UserID, a.Login, a.Email, a.RegistrationIP, IFNULL(b.EmailLogID, 0) as EmailLogID
      FROM
          Usr a LEFT OUTER JOIN EmailLog b ON a.UserID = b.UserID AND b.MessageKind = :MESSAGEKIND
          LEFT OUTER JOIN AbBookerInfo booker ON booker.UserID = a.OwnedByBusinessID
      WHERE a.UsGreeting = 0
      AND a.CreationDateTime <= DATE_SUB(NOW(), INTERVAL 24 hour)
      AND a.CreationDateTime >= DATE_SUB(NOW(), INTERVAL 48 hour)
      AND b.MessageKind IS NULL
      AND booker.AbBookerInfoID IS NULL

      AND a.AccountLevel <> :ACCOUNT_LEVEL_BUSINESS 
      LIMIT 1000 /* limit to prevent span in case of some error */
SQL;

        $result = $this->connection
                        ->executeQuery($query, ['MESSAGEKIND' => EmailLog::MESSAGE_KIND_RETENTION_USER, 'ACCOUNT_LEVEL_BUSINESS' => ACCOUNT_LEVEL_BUSINESS])
                        ->fetchAll();

        $queryMess =
<<<SQL
        SELECT
        	SocialAdID, Content
        FROM
            SocialAd
        WHERE
            Kind = :kind
        AND
            ((now() BETWEEN BeginDate AND EndDate) OR (BeginDate IS NULL AND EndDate IS NULL))
SQL;
        $messages = $this->connection
            ->executeQuery($queryMess, ['kind' => ADKIND_RETENTION])
            ->fetchAll();

        $reader = new Reader($this->geoIpCountryParameter);

        foreach ($result as $i => $row) {
            if (!empty($row['RegistrationIP'])) {
                try {
                    $record = $reader->country($row['RegistrationIP']);
                } catch (AddressNotFoundException $e) {
                    $this->output->writeln("[" . $i . "/" . count($result) . "]");
                    $this->output->writeln("LOGIN: {$row['Login']}, ERROR: {$e->getMessage()};");
                    $sendRes = $this->notifyUser($row['UserID']);

                    continue;
                }
                $countryCode = $record->country->isoCode;

                if (!sizeof($messages)) {
                    $ad = null;
                } else {
                    $ad = $messages[array_rand($messages)]['Content'];
                }
            } else {
                $ad = null;
                $countryCode = 'UNKNOWN';
            }
            $sendRes = $this->notifyUser($row['UserID'], $ad);
            $this->output->writeln("[" . $i . "/" . count($result) . "]");
            $this->output->writeln("LOGIN: {$row['Login']}, COUNTRY_CODE: {$countryCode}; SEND: " . ($sendRes ? 'true' : 'false') . ";");
        }

        return 0;
    }
}
