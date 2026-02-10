<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Entity\Mediacontact;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\Events;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\SendEvent;
use AwardWallet\MainBundle\Service\EmailTemplate\Query;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DataMediaContacts extends AbstractFailTolerantDataProvider
{
    protected const FROM_EMAIL = 'alexi@AwardWallet.com';
    protected const FROM_NAME = 'Alexi Vereschaga';

    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(ContainerInterface $container, EmailTemplate $template)
    {
        parent::__construct($container, $template);
        $this->memcached = $container->get(\Memcached::class);

        $this->dispatcher->addListener(Events::EVENT_PRE_SEND, function (SendEvent $event) {
            $message = $event->getMessage();

            $message->setFrom(self::FROM_EMAIL, self::FROM_NAME);
            $message->setReplyTo(self::FROM_EMAIL, self::FROM_NAME);
        });

        if (\php_sapi_name() === 'cli') {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $mediaContactRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Mediacontact::class);

            $this->dispatcher->addListener(Events::EVENT_POST_SEND, function (SendEvent $event) use ($mediaContactRep, $em) {
                if (!$event->isSuccess()) {
                    return;
                }

                $message = $event->getMessage();

                foreach (\array_keys($message->getTo()) as $email) {
                    /** @var Mediacontact $mediaContact */
                    if ($mediaContact = $mediaContactRep->findOneByEmail($email)) {
                        $this->memcached->set(
                            $this->getCacheById($mediaContact->getMediacontactid()),
                            1,
                            SECONDS_PER_DAY * 30
                        );
                        $em->detach($mediaContact);
                    }
                }
            });
        }
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        $em = $this->container->get("doctrine.orm.default_entity_manager");
        $queryOptions = $this->getQueryOptions();
        $params = [];
        $paramsTypes = [];
        $sql =
            "select
                mc.MediaContactID as UserID,
                IF(mc.FirstName = '' or mc.FirstName is null, '', CONCAT(' ', mc.FirstName)) as FirstName,
                mc.LastName,
                mc.Email,
                CONCAT('MediaContactLogin', mc.MediaContactID) as Login,
                CONCAT('MediaContactRegistrationIP', mc.MediaContactID) as RegistrationIP,
                CONCAT('MediaContactLatLogonIP', mc.MediaContactID) as LastLogonIP,
                0 as isBusiness,
                CONCAT('MediaContactRefCode', mc.MediaContactID) as RefCode,
                CONCAT('MediaContactZip', mc.MediaContactID) as Zip
            from MediaContact mc
            left join DoNotSend dns on mc.Email collate utf8_general_ci = dns.Email
            where
                mc.Unsubscribed = 0 and
                mc.NDR <> :emailndr and
                dns.DoNotSendID is null AND
                mc.Email is not null and mc.Email <> ''
            ";
        $params[':emailndr'] = \EMAIL_NDR;
        $paramsTypes[':emailndr'] = \PDO::PARAM_INT;

        if (\count($queryOptions[0]->userId) > 0) {
            $sql .= ' and mc.MediaContactID  IN (:users)';
            $params[':users'] = $queryOptions[0]->userId;
            $paramsTypes[':users'] = Connection::PARAM_INT_ARRAY;
        }

        if (\count($queryOptions[0]->notUserId) > 0) {
            $sql .= ' and mc.MediaContactID NOT IN (:notUsers)';
            $params[':notUsers'] = $queryOptions[0]->notUserId;
            $paramsTypes[':notUsers'] = Connection::PARAM_INT_ARRAY;
        }

        if (isset($queryOptions[0]->limit)) {
            $sql .= ' limit :limit';
            $params[':limit'] = $queryOptions[0]->limit;
            $paramsTypes[':limit'] = \PDO::PARAM_INT;
        }

        /** @var Connection $connection */
        $connection = $em->getConnection();
        $statementMaker = function () use ($sql, $params, $paramsTypes, $connection): Statement {
            $innerStatement = $connection->executeQuery($sql, $params, $paramsTypes);

            $existsInCache = function (int $mediaContactId) {
                return 1 === $this->memcached->get($this->getCacheById($mediaContactId));
            };

            return new class($innerStatement, $existsInCache) extends PDOStatement implements \IteratorAggregate {
                /**
                 * @var ResultStatement
                 */
                private $innerStatement;
                /**
                 * @var callable
                 */
                private $existInCacheChecker;

                public function __construct(ResultStatement $innerStatement, callable $existInCacheChecker)
                {
                    $this->innerStatement = $innerStatement;
                    $this->existInCacheChecker = $existInCacheChecker;
                }

                protected function isValidContact(array $fields): bool
                {
                    $email = $fields['Email'];
                    $emailWithoutLastDot = \substr($email, 0, -1);

                    return
                        !($this->existInCacheChecker)((int) $fields['UserID'])
                        && (
                            (\filter_var($email, \FILTER_VALIDATE_EMAIL, \FILTER_FLAG_EMAIL_UNICODE) === $email)
                            || (
                                ($email[-1] === '.')
                                && (\filter_var($emailWithoutLastDot, \FILTER_VALIDATE_EMAIL, \FILTER_FLAG_EMAIL_UNICODE) === $emailWithoutLastDot)
                            )
                        );
                }

                public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
                {
                    do {
                        $fields = $this->innerStatement->fetch($fetchMode);
                    } while ($fields && !$this->isValidContact($fields));

                    return $fields;
                }

                public function getIterator()
                {
                    while (false !== ($res = $this->fetch(\PDO::FETCH_ASSOC))) {
                        yield $res;
                    }
                }
            };
        };

        $query = new class($statementMaker) extends Query {
            /**
             * @var callable
             */
            private $statementMaker;

            public function __construct(callable $statementMaker)
            {
                $this->statementMaker = $statementMaker;
            }

            public function getStatement()
            {
                if (isset($this->statement)) {
                    return $this->statement;
                }

                return $this->statement = ($this->statementMaker)();
            }

            public function getCount()
            {
                if (\is_int($this->count)) {
                    return $this->count;
                }

                return $this->count = it(($this->statementMaker)())->count();
            }
        };

        $query->setFields([
            'UserID' => 'User ID (or business admin ID)',
            'FirstName' => 'First name of user (or business admin)',
            'LastName' => 'Last name of user (or business admin)',
            'Email' => 'Email of user (or business admin)',
            'Login' => 'Login of user (or business admin)',
            'RegistrationIP' => 'IP of user at registration (or business admin)',
            'LastLogonIP' => 'IP of user at last login (or business admin)',
            'isBusiness' => '0/1 - whether user is a business (1) or not (0)',
            'RefCode' => 'Referral code',
            'Zip' => 'Zip-code (5-digits)',
        ]);
        $query->addDebug("SQL", $sql);
        $query->addDebug("No SQL Params");

        return $query;
    }

    public function getDescription(): string
    {
        return 'All valid contacts from <a href="https://awardwallet.com/manager/list.php?Schema=MediaContact" target="_blank">Media Contact</a> scheme';
    }

    public function getTitle(): string
    {
        return 'Media contacts';
    }

    public function canBeExcludedInAdminInterface(): bool
    {
        return false;
    }

    protected function getCacheById(int $mediaContactId): string
    {
        return
            'media_contact_email_log_' .
            $this->emailTemplate->getEmailTemplateID() . '_' .
            $mediaContactId;
    }
}
