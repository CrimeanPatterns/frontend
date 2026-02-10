<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\AbAccountProgram;
use AwardWallet\MainBundle\Entity\AbBookerInfo;
use AwardWallet\MainBundle\Entity\AbCustomProgram;
use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbPassenger;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbRequestMark;
use AwardWallet\MainBundle\Entity\AbSegment;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\BookingServiceFee;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\CreditCardFee;
use AwardWallet\MainBundle\Entity\Type\AbMessageMetadata;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Entity\VerifiedEmail;
use AwardWallet\MainBundle\Event\BookingMessage;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\AbstractBookingTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\AcceptBookingInvoice;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\BookingChangeStatus;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\BookingInvoice;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\BookingRespond;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\BookingShareAccounts;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\NewBookingRequest;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\Exception\PaymentException;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\MainBundle\Service\BalanceFormatter;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Service\SocksMessaging\BookingMessaging;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BookingRequestManager
{
    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var \AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer */
    protected $mailer;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Usr
     */
    protected $user;

    protected $environment;

    /** @var ProgramShareManager */
    protected $programShareManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /** @var \Symfony\Bridge\Monolog\Logger */
    protected $logger;

    /**
     * @var BookingMessaging
     */
    protected $messaging;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    /**
     * @var Counter
     */
    private $counter;
    /**
     * @var Process
     */
    private $process;
    /**
     * @var BalanceFormatter
     */
    private $balanceFormatter;
    /**
     * @var EmailLog
     */
    private $emailLog;

    /**
     * @var LocalizeService
     */
    private $localizer;

    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @var DefaultBookerParameter
     */
    private $defaultBooker;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        Mailer $mailer,
        RouterInterface $router,
        ProgramShareManager $programShareManager,
        $env,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        BookingMessaging $messaging,
        EventDispatcherInterface $eventDispatcher,
        Counter $counter,
        Process $process,
        BalanceFormatter $balanceFormatter,
        EmailLog $emailLog,
        LocalizeService $localizer,
        ConnectionManager $connectionManager,
        DefaultBookerParameter $defaultBooker
    ) {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->tokenStorage = $tokenStorage;
        $this->environment = $env;
        $this->router = $router;
        $this->programShareManager = $programShareManager;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->messaging = $messaging;
        $this->eventDispatcher = $eventDispatcher;
        $this->authorizationChecker = $authorizationChecker;
        $this->counter = $counter;
        $this->process = $process;
        $this->balanceFormatter = $balanceFormatter;
        $this->emailLog = $emailLog;
        $this->localizer = $localizer;
        $this->connectionManager = $connectionManager;
        $this->defaultBooker = $defaultBooker;
    }

    public function create(AbRequest $request)
    {
        $this->user = $request->getUser();

        if ($this->authorizationChecker->isGranted("BOOKER", $request) && $request->getByBooker()) {
            $this->addMembers($request);
        }

        if (empty($request->getBooker()->getUserid())) {
            $this->logger->info("booker is empty, setting AW");
            $request->setBooker($this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->defaultBooker->get()));
        }
        $this->logger->addDebug('CustomPrograms after add members: ' . $request->getCustomPrograms()->count());

        if ($request->getStatus() === AbRequest::BOOKING_STATUS_NOT_VERIFIED && $this->isEMailVerified($request->getUser(), $request->getMainContactEmail())) {
            $this->logger->info("marking email as verified, because we've found request with verified email");
            $request->setStatus(AbRequest::BOOKING_STATUS_PENDING);
        }
        $this->em->persist($request);
        $this->em->flush();

        if (!$this->authorizationChecker->isGranted("BOOKER", $request)) {
            foreach ($request->getAccountOwners() as $user) {
                $agent = $this->programShareManager->connectToBooker($user, $request->getBooker());
                $this->connectionManager->shareAllTimelines($agent, $user);
            }
        }

        if (!$this->authorizationChecker->isGranted("BOOKER", $request)) {
            $this->sendEmailOnNewRequest($request);
        }

        $bookerAgents = $this->getRelatedUsers([$request->getBooker()->getUserid()], [], [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER]);
        $this->markAsUnread($request, $bookerAgents);

        foreach ($bookerAgents as $k => $bookerAgentId) {
            $booker = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($bookerAgentId);
            $template = new NewBookingRequest();
            $template->request = $request;
            $template->toUser($booker, true);
            $this->sendEmail($template, true, $k === 0);
        }
    }

    public function update(AbRequest $request)
    {
        if ($this->authorizationChecker->isGranted("BOOKER", $request) && $request->getByBooker()) {
            $this->addMembers($request);
        }
        // Last update date
        $request->setLastUpdateDate(new \DateTime());

        $m = $this->createMessage($this->getCurrentUser(), $request, AbMessage::TYPE_UPDATE_REQUEST);
        $request->addMessage($m);
        $this->addMessage($m);
        $this->em->flush();

        if (!$m->getFromBooker()) {
            $template = new NewBookingRequest();
            $template->request = $request;
            $this->sendEmail($template, true, true);
        }

        $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($m), 'aw.booking.message.new');
    }

    /**
     * @param int   $status
     * @return AbMessage|void
     */
    public function changeStatus(AbRequest $request, $status)
    {
        $request->setStatus($status);
        $request->setLastUpdateDate(new \DateTime());

        // Send email notify
        $toBooker = true;

        if ($this->authorizationChecker->isGranted("BOOKER", $request)) {
            $toBooker = false;
        }

        $realStatus = $request->getRealStatus($toBooker);

        $template = new BookingChangeStatus();
        $template->request = $request;
        $template->status = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getStatusDescription($realStatus);
        $this->sendEmail($template, $toBooker);

        if ($status == AbRequest::BOOKING_STATUS_PROCESSING && $this->getCurrentUser() == $request->getUser()) {
            return;
        }

        $m = $this->createMessage($this->getCurrentUser(), $request, AbMessage::TYPE_STATUS_REQUEST);
        $data = new AbMessageMetadata();
        $data->setStatus($realStatus);
        $m->setMetadata($data);
        $request->addMessage($m);
        $this->addMessage($m);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($m, ['action' => 'statusChange']), 'aw.booking.message.new');
    }

    public function addMessage(AbMessage $message)
    {
        $this->em->persist($message);
        // Last update date
        //		if ($message->getType() >= AbMessage::TYPE_COMMON && $message->getType() != AbMessage::TYPE_INTERNAL)
        $message->getRequest()->setLastUpdateDate(new \DateTime());

        // TODO: send email (only common messages). Pass BookingRequestEvent
        // mark request as unread
        if ($message->getType() >= AbMessage::TYPE_COMMON) {
            //			switch ($message->getType()) {
            //				case AbMessage::TYPE_INTERNAL:
            //					$mark = array($message->getRequest()->getBooker()->getUserid());
            //					$excluding = array($this->getCurrentUser()->getUserid());
            //					break;
            //                case AbMessage::TYPE_REQUEST_SHARE_ACCOUNTS:
            //					$mark = array($message->getRequest()->getUser()->getUserid());
            //					$excluding = array();
            //					break;
            //				default:
            //					$mark = array(
            //						$message->getRequest()->getUser()->getUserid(),
            //						$message->getRequest()->getBooker()->getUserid(),
            //					);
            //					$excluding = array($message->getUser()->getUserid());
            //					break;
            //			}
            if ($message->getFromBooker() && !in_array($message->getType(), [AbMessage::TYPE_INTERNAL, AbMessage::TYPE_YCB_SCHEDULE])) {
                $related = $this->getRelatedUsers([$message->getRequest()->getBooker()->getUserid()]);
                $this->markAsRead($message->getRequest(), $related, array_pad([], count($related), $message->getCreateDate()));
            }
        }

        $this->markAsRead($message->getRequest(), $message->getUser(), $message->getCreateDate());
    }

    public function addInvoice(AbInvoice $invoice, AbRequest $abRequest)
    {
        if ($abRequest->getBooker()->getBookerInfo()->isIncludeCreditCardFee()) {
            $locale = $abRequest->getUser()->getLocale();
            $lang = $abRequest->getUser()->getLanguage();
            $item = (new CreditCardFee())
                ->setDescription(
                    $this->translator->trans('cart.item.type.booking.fee-cc', [
                        '%percent%' => $this->localizer->formatNumber(round(Manager::PAYPAL_FEE * 100, 1), null, $locale),
                    ], 'messages', $lang)
                )->setPrice(round(Manager::PAYPAL_FEE * $invoice->getTotalWithoutDiscount(), 2));
            $invoice->addItem($item);
        }
        $message = $this->createMessage($this->getCurrentUser(), $abRequest, AbMessage::TYPE_COMMON);
        $message->setInvoice($invoice);
        $this->addMessage($message);
        $this->em->persist($invoice);
        $this->em->flush();

        $template = new BookingInvoice();
        $template->request = $message->getRequest();
        $template->invoice = $invoice;
        $this->sendEmail($template, false);

        $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($message), 'aw.booking.message.new');
    }

    public function requestSharing(Usr $admin, AbRequest $request, array $requested)
    {
        $this->programShareManager->setUser($request->getUser());

        $message_data = ['custom' => [], 'account' => []];
        $i = 0;

        foreach ($requested as $program) {
            if ($program instanceof AbAccountProgram) {
                $message_data['account'][] = $program->getAbAccountProgramID();
                $i++;

                $this->programShareManager->requestShareForProgram($program);
            }

            if ($program instanceof AbCustomProgram) {
                $message_data['custom'][] = $program->getAbCustomProgramID();
                $i++;

                $this->programShareManager->requestShareForCustomProgram($program);
            }
        }

        if (!empty($requested)) {
            $m = $this->createMessage($admin, $request, AbMessage::TYPE_REQUEST_SHARE_ACCOUNTS);
            $m->setPost('');
            $data = new AbMessageMetadata();
            $data->setRequested($message_data);
            $m->setMetadata($data);
            $request->addMessage($m);
            $this->addMessage($m);
            $this->em->flush();

            $template = new BookingShareAccounts();
            $template->request = $request;
            $template->programs = $requested;

            $this->sendEmail($template, false);

            $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($m), 'aw.booking.message.new');
        }
    }

    public function shareAccounts(Usr $admin, AbRequest $request, array $sharedTotal, array $sharedNewIds)
    {
        $this->programShareManager->setUser($request->getUser());

        $programs = [];
        $message_data = ['custom' => [], 'account' => []];
        $i = 0;

        foreach ($sharedTotal as $program) {
            if ($program instanceof AbAccountProgram) {
                $provider = $program->getAccount()->getProviderid();
                $message_data['account'][] = $program->getAbAccountProgramID();
                $programs[$i]['ProgramName'] = $provider->getName();
                $programs[$i]['Owner'] = $program->getAccount()->getOwnerFullName();
                $programs[$i]['Total'] = $this->balanceFormatter->formatNumber(
                    $program->getAccount()->getBalance(),
                    $provider->getAllowfloat(),
                    null,
                    '-'
                );
                $i++;
            }
        }

        if (!empty($programs)) {
            $updatedMessages = [];
            $removedMessages = [];
            $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

            /** @var AbMessage $message */
            foreach ($request->getMessages() as $message) {
                if (in_array($message->getType(), [AbMessage::TYPE_SHARE_ACCOUNTS, AbMessage::TYPE_SHARE_ACCOUNTS_INTERNAL])) {
                    $message_data['account'] = array_merge($message_data['account'], $message->getMetadata()->getAPR());
                    $message_data['custom'] = array_merge($message_data['custom'], $message->getMetadata()->getCPR());

                    $removedMessages[] = ['messageId' => $message->getAbMessageID(), 'message' => $message];
                    $this->em->remove($message);
                } elseif ($message->isShareRequest()) {
                    $isNotShared = 0;

                    foreach ($request->getAccounts() as $account) {
                        if (!$account->getRequested()) {
                            continue;
                        }

                        if (in_array($account->getAbAccountProgramID(), $message->getMetadata()->getAPR())) {
                            $accessLevel = $uaRep->getAccessLevel($account->getAccount()->getUserid(), $account->getAccount(), $request->getBooker(), false);

                            if (empty($accessLevel)) {
                                $isNotShared++;
                            }
                        }
                    }

                    foreach ($request->getCustomPrograms() as $account) {
                        if (!$account->getRequested()) {
                            continue;
                        }

                        if (in_array($account->getAbCustomProgramID(), $message->getMetadata()->getCPR())) {
                            $isNotShared++;
                        }
                    }

                    if ($isNotShared == 0 && sizeof(array_intersect($sharedNewIds, $message->getMetadata()->getAPR())) > 0) {
                        $message->setLastUpdateDate(new \DateTime());
                        $updatedMessages[] = $message;
                    }
                }
            }
            $this->em->flush();

            foreach ($updatedMessages as $message) {
                $this->eventDispatcher->dispatch(new BookingMessage\EditEvent($message), 'aw.booking.message.edit');
            }

            foreach ($removedMessages as $removedMessage) {
                $this->eventDispatcher->dispatch(new BookingMessage\DeleteEvent($removedMessage['message'], $removedMessage['messageId']), 'aw.booking.message.delete');
            }

            $message_data['account'] = array_unique($message_data['account']);
            $message_data['custom'] = array_unique($message_data['custom']);

            $m = $this->createMessage($admin, $request, AbMessage::TYPE_SHARE_ACCOUNTS);
            $m->setPost('');
            $data = new AbMessageMetadata();
            $data->setRequested($message_data);
            $m->setMetadata($data);
            $request->addMessage($m);
            $this->addMessage($m);
            $this->flush();

            $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($m), 'aw.booking.message.new');

            $m = $this->createMessage($admin, $request, AbMessage::TYPE_SHARE_ACCOUNTS_INTERNAL);
            $m->setPost('');
            $data = new AbMessageMetadata();
            $data->setRequested($message_data);
            $m->setMetadata($data);
            $request->addMessage($m);
            $this->addMessage($m);
            $this->flush();

            $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($m), 'aw.booking.message.new');
        }
    }

    /**
     * Mark request as read until date.
     *
     * @param Usr|int[] $users
     * @param \DateTime|\DateTime[] $dates
     */
    public function markAsRead(AbRequest $request, $users, $dates, bool $forceDate = false): void
    {
        if (!is_array($users)) {
            $users = [$users];
        }

        if (!$users) {
            return;
        }

        if (!is_array($dates)) {
            $dates = [$dates];
        }

        if (count($dates) !== count($users)) {
            throw new \InvalidArgumentException('Invalid dates for users');
        }

        $users = array_map(function ($user) {
            return $user instanceof Usr ? $user->getUserid() : $user;
        }, $users);

        $userDates = array_combine($users, $dates);
        $values = [];
        $params = [];
        $types = [];

        /** @var \DateTime $date */
        foreach ($userDates as $user => $date) {
            $values[] = '(?, ?, ?)';

            $params[] = $date->format('Y-m-d H:i:s');
            $types[] = \PDO::PARAM_STR;

            $params[] = $user;
            $types[] = \PDO::PARAM_INT;

            $params[] = $request->getAbRequestID();
            $types[] = \PDO::PARAM_INT;
        }

        $values = implode(', ', $values);

        $updateStrategy = $forceDate ?
            "ReadDate = VALUES(ReadDate)" :
            "ReadDate = IF(ReadDate < VALUES(ReadDate), VALUES(ReadDate), ReadDate)";

        $this->em->getConnection()->executeQuery(
            "
            INSERT INTO `AbRequestMark` (
                ReadDate, UserID, RequestID
            ) VALUES 
            {$values}
            ON DUPLICATE KEY UPDATE {$updateStrategy}",
            $params,
            $types
        );
    }

    /**
     * @param Usr|int[] $users
     */
    public function markAsUnread(AbRequest $request, $users): void
    {
        if (!is_array($users)) {
            $users = [$users];
        }

        $users = array_map(function ($user) {
            return $user instanceof Usr ? $user->getUserid() : $user;
        }, $users);

        if (!$users) {
            return;
        }

        $connection = $this->em->getConnection();
        $lastMessages = $connection->createQueryBuilder()
                            ->select(...array_merge(
                                array_map(function ($user) {
                                    return "MAX(IF(abrm.UserID = ?, abrm.ReadDate, NULL)) as last_read_by_{$user}";
                                }, $users),
                                array_map(function ($user) {
                                    return "MAX(IF(abm.UserID <> ?, abm.CreateDate, NULL)) as last_write_by_others_for_{$user}";
                                }, $users),
                                [
                                    'abr.CreateDate',
                                ]
                            ))
                            ->from('AbRequest', 'abr')
                            ->leftJoin('abr', 'AbMessage', 'abm', 'abm.RequestID  = abr.AbRequestID')
                            ->leftJoin('abr', 'AbRequestMark', 'abrm', 'abrm.RequestID = abr.AbRequestID')
                            ->where('abr.AbRequestID = ' . $request->getAbRequestID())
                            ->groupBy('abr.CreateDate')
                            ->setParameters(array_merge($users, $users), array_pad([], 2 * count($users), \PDO::PARAM_INT))
                            ->execute()
                            ->fetchAll()[0];

        $updateMarks = [];

        foreach ($users as $user) {
            $lastReadByUser = $lastMessages["last_read_by_{$user}"];
            $lastWriteByOthers = $lastMessages["last_write_by_others_for_{$user}"];

            // user read messages
            if (isset($lastReadByUser)) {
                // at least one other user wrote
                if (isset($lastWriteByOthers)) {
                    // if read all the messages
                    if ($lastReadByUser >= $lastWriteByOthers) {
                        // so move read backwards before last write by others
                        $updateMarks[$user] = (new \DateTime($lastWriteByOthers))->modify('-1 second');
                    }
                } else {
                    // other users did not write messages, request not read
                    $updateMarks[$user] = new \DateTime($lastMessages['CreateDate']);
                }
            } else {
                // user did not read messages, request not read
                $updateMarks[$user] = new \DateTime($lastMessages['CreateDate']);
            }
        }

        if ($updateMarks) {
            // move read marks in past
            $this->markAsRead($request, array_keys($updateMarks), array_values($updateMarks), true);
        }
    }

    public function getMarkRead(AbRequest $request, Usr $user)
    {
        /** @var AbRequestMark[] $rms */
        $rms = [];

        try {
            $rms = $this->em->createQueryBuilder()->select('rm')->from(AbRequestMark::class, 'rm')
                ->andWhere('rm.UserID  = ' . $user->getId())
                ->andWhere('rm.RequestID = ' . $request->getAbRequestID())
                ->getQuery()->getResult();
        } catch (NoResultException $e) {
        }

        if ($rms) {
            return $rms[0]->getReadDate();
        }

        return null;
    }

    public function createMessage(Usr $user, AbRequest $request, $type)
    {
        $m = new AbMessage();
        $m->setRequest($request);
        $m->setUser($user);
        $m->setType($type);

        if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            $m->setFromBooker(true);
        } else {
            $m->setFromBooker(false);
        }

        return $m;
    }

    public function getEmptyBookingRequest($options = [])
    {
        $options = array_merge([
            'for_booker' => false,
        ], $options);
        $r = new AbRequest();
        $p = new AbPassenger();
        $user = $options['user'] ?? null;

        if (!$user && ($this->tokenStorage->getToken() !== null)) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        /** @var Usr $user */
        if ($user instanceof Usr) {
            if (!$options['for_booker']) {
                $r->setUser($user);
                $r->setContactEmail($user->getEmail());
                $r->setContactName($user->getFullName());
                $r->setContactPhone($user->getPhone1());
                $p->setFirstName($user->getFirstname());
                $p->setMiddleName($user->getMidname());
                $p->setLastName($user->getLastname());
                $passengers = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbPassenger::class)->getPassengerTemplates($user);

                foreach ($passengers as $passenger) {
                    if ($p->getFirstName() == $passenger['FirstName']
                        && $p->getLastName() == $passenger['LastName']
                    ) {
                        if (!empty($passenger['Birthday'])) {
                            $p->setBirthday(\DateTime::createFromFormat('Y-m-d', $passenger['Birthday']));
                        }

                        if (!empty($passenger['Gender'])) {
                            $p->setGender($passenger['Gender']);
                        }

                        if (!empty($passenger['Nationality'])) {
                            $p->setNationality($passenger['Nationality']);
                        }
                    }
                }
            }

            if ($options['for_booker']) {
                $r->addAccount(new AbAccountProgram());
                $r->setByBooker(true);
                $r->setStatus(AbRequest::BOOKING_STATUS_PENDING);
            } else {
                //                if ($user->getAccounts() == 0 && $r->getCustomPrograms()->count() == 0)
                //                    $r->addCustomProgram(new AbCustomProgram());
            }
        } else {
            //            $r->addCustomProgram(new AbCustomProgram());
        }

        if ($user instanceof Usr && $options['for_booker']) {
            $booker = $user->getBooker();
        } else {
            $booker = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)
                ->find($this->defaultBooker->get());
        }
        $r->setBooker($booker);

        // add passenger
        if (!isset($options['for_partner']) || $options['for_partner'] == false) {
            $r->addPassenger($p);
        }
        // add segment
        $r->addSegment(new AbSegment());

        return $r;
    }

    public function flush()
    {
        $this->em->flush();
    }

    /**
     * @param bool $sendCopyToBooker - only for default booker
     */
    public function sendEmail(AbstractBookingTemplate $template, bool $toBooker = false, bool $sendCopyToBooker = true)
    {
        $request = $template->request;
        $template->toBooker = $toBooker;
        $template->confirm = !$toBooker && $request->getStatus() == AbRequest::BOOKING_STATUS_NOT_VERIFIED;

        if ($template instanceof BookingRespond) {
            $template->enableUnsubscribe($toBooker, $toBooker);
        }

        $booker = $request->getBooker();
        $bookerFromEmail = $booker->getBookerInfo()->getFromEmail();
        $options = [
            Mailer::OPTION_SEPARATE_CC => true,
            Mailer::OPTION_SEND_ATTEMPTS => 2,
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ];

        if ($booker->getBookerInfo()->hasCustomSmtp()) {
            $options[Mailer::OPTION_TRANSPORT] = $booker->getBookerInfo()->getCustomSmtpTransport();
            $options[Mailer::OPTION_ON_SUCCESSFUL_SEND] = $this->registerSmtpDelivery($request->getUser());
        }
        $extendMailOptions = function ($message) use ($booker, &$options) {
            if ($booker->getBookerInfo()->hasCustomSmtp()) {
                $options[Mailer::OPTION_ON_FAILED_SEND] = $this->registerSmtpError($booker, $message);
            }
        };

        $cc = [];

        if ($toBooker) {
            if (($assigned = $request->getAssignedUser()) && $assigned->isEmailBookingMessages()) {
                $template->toUser($assigned, true);
            } elseif (
                !$template->hasEmail()
                && !empty($bookerFromEmail)
            ) {
                $template->toEmail($bookerFromEmail, true);
            }
        } else {
            if ($request->getSendMailUser()) {
                $cc = $request->getContactEmails();
                $emailTo = array_shift($cc);
                $template->toUser($request->getUser(), false, $emailTo);
            }
        }

        if ($template->hasEmail()) {
            $message = $this->mailer->getMessageByTemplate($template);
            $this->setBookerEmailHeaders($booker, $message);

            if (count($cc) > 0) {
                foreach ($cc as $email) {
                    $message->addTo($email);
                }
            }
            $extendMailOptions($message);
            $this->mailer->send($message, $options);
        }

        if (
            $toBooker
            && $sendCopyToBooker
            && $booker->getUserid() === $this->defaultBooker->get()
            && !empty($bookerFromEmail)
        ) {
            $to = $bookerFromEmail;

            if (
                !(
                    $template->hasEmail()
                    && (
                        $template->getEmail() === $to
                        || (count($cc) > 0 && in_array($to, $cc))
                    )
                )
            ) {
                $template->toEmail($to, true);
                $message = $this->mailer->getMessageByTemplate($template);
                $this->setBookerEmailHeaders($booker, $message);
                $message->setCc([]);
                $message->setBcc([]);
                $extendMailOptions($message);
                $this->mailer->send($message, $options);
            }
        }
    }

    public function markAsPaid(AbInvoice $invoice, $total, $paymentType)
    {
        if ($invoice->getStatus() == AbInvoice::STATUS_PAID && $paymentType == $invoice->getPaymentType()) {
            throw new PaymentException($this->translator->trans(/** @Desc("You have already indicated that a check has been sent so this action is not required.") */ 'invoice_already_paid_by_check', [], 'booking'));
        }
        $request = $invoice->getMessage()->getRequest();
        $requestStatusBefore = $request->getStatus();
        $invoice->setPaymentType($paymentType);
        $message = $this->createMessage($this->getCurrentUser(), $request, $paymentType == AbInvoice::PAYMENTTYPE_CREDITCARD ? AbMessage::TYPE_INVOICE_PAID : AbMessage::TYPE_WRITE_CHECK);
        $total_verbose = $this->localizer->formatCurrency($total, $request->getBooker()->getBookerInfo()->getCurrency()->getCode(), true, $request->getUser()->getLocale());

        if ($paymentType == AbInvoice::PAYMENTTYPE_CREDITCARD) {
            if (!$invoice->getMessage()->isAutoreplyInvoice()) {
                $this->changeStatus($request, AbRequest::BOOKING_STATUS_PROCESSING);
            }
            $invoice->setStatus(AbInvoice::STATUS_PAID);
            $invoice->setPaidTo($request->getBooker());
            $invoice->getMessage()->setLastUpdateDate(new \DateTime());
            /** @Desc("%name% paid %total% for this request via credit card") */
            $text = $this->translator->trans('invoice_paid_by_cc', ['%name%' => $this->getCurrentUser()->getFullName(), '%total%' => $total_verbose], 'booking');
        } else { /** @Desc("%name% paid %total% for this request via check") */
            $text = $this->translator->trans('invoice_paid_by_check', ['%name%' => $this->getCurrentUser()->getFullName(), '%total%' => $total_verbose], 'booking');
        }
        $message->setPost($text);
        $meta = new AbMessageMetadata();
        $meta->setInvoiceId($invoice->getId());
        $meta->setTotalInvoice($total);
        $message->setMetadata($meta);
        $this->em->persist($invoice);
        $this->em->persist($message);
        $this->em->flush();
        $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($message), 'aw.booking.message.new');

        if ($paymentType == AbInvoice::PAYMENTTYPE_CREDITCARD) {
            $this->eventDispatcher->dispatch(new BookingMessage\EditEvent($invoice->getMessage(), ['action' => 'statusChange']), 'aw.booking.message.edit');
        }

        // Send Email
        $template = new AcceptBookingInvoice();
        $template->request = $request;
        $template->invoice = $invoice;
        $template->checkSent = $paymentType == AbInvoice::PAYMENTTYPE_CHECK;
        $this->sendEmail($template, true);
    }

    /**
     * @param string $host
     * @param string $scheme
     * @return string|null
     */
    public function getPublicLink($host, $scheme = 'http')
    {
        if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            $referrals = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Sitead::class)->findBy(['Booker' => $this->getCurrentUser()->getBooker()]);

            if (count($referrals)) {
                $referral = $referrals[0];

                if ($this->authorizationChecker->isGranted('USER_BOOKING_PARTNER') && !$this->authorizationChecker->isGranted('USER_BOOKING_MANAGER')) {
                    foreach ($referrals as $ref) {
                        foreach ($ref->getUsers() as $user) {
                            if ($user == $this->getCurrentUser()) {
                                $referral = $ref;

                                break;
                            }
                        }
                    }
                }
                $sid = $referral->getSiteAdID();

                return $scheme . "://" . $host .
                    $this->router->generate('aw_booking_add_index', ['ref' => $sid]);
            }
        }

        return null;
    }

    /**
     * @return bool|string
     */
    public function checkUnreadMessagesAndGetRedirect()
    {
        $redirectUrl = false;
        $abReq = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);

        if ($user = $this->getCurrentUser()) {
            if ($lastUnreadReq = $abReq->getLastUnreadByUser($user, false)) {
                $redirectUrl = $this->router->generate('aw_booking_view_index', ['id' => $lastUnreadReq->getAbRequestID()]);
            } else {
                $balancesCount = $this->counter->getTotalAccounts($user->getUserid());

                if ($balancesCount == 0) {
                    $openRequests = $abReq->getActiveRequestsCountByUser($user);

                    if ($openRequests > 1) {
                        $redirectUrl = $this->router->generate('aw_booking_list_requests');
                    } elseif ($openRequests == 1) {
                        $lastRequest = $abReq->getLastActiveRequestByUser($user);

                        if ($lastRequest) {
                            $redirectUrl = $this->router->generate('aw_booking_view_index', ['id' => $lastRequest->getAbRequestID()]);
                        }
                    }
                }
            }
        }

        return $redirectUrl;
    }

    public function sendEmailOnNewRequest(AbRequest $request)
    {
        $template = new NewBookingRequest();
        $template->request = $request;
        $this->sendEmail($template, false);
    }

    public function confirmContactEmail(AbRequest $abRequest)
    {
        $verEmailRep = $this->em->getRepository(VerifiedEmail::class);
        /** @var VerifiedEmail $verEmail */
        $verEmail = $verEmailRep->findOneBy(['email' => $abRequest->getMainContactEmail()]);

        if (!is_null($verEmail)) {
            $verEmail->setVerificationDate(new \DateTime('now'));
        } else {
            $verEmail = new VerifiedEmail(
                $abRequest->getMainContactEmail(),
                new \DateTime('now')
            );
            $this->em->persist($verEmail);
        }

        if ($abRequest->getStatus() == AbRequest::BOOKING_STATUS_NOT_VERIFIED) {
            $abRequest->setStatus(AbRequest::BOOKING_STATUS_PENDING);
        }

        $this->em->flush();
    }

    public function addAutoReplyInvoice(AbRequest $abRequest)
    {
        $options = $abRequest->getBooker()->getBookerInfo()->getAutoreplyInvoiceOptions();

        $message = new AbMessage();
        $message
            ->setCreateDate($abRequest->getCreateDate())
            ->setRequest($abRequest)
            ->setUser($abRequest->getBooker())
            ->setPost($abRequest->getBooker()->getBookerInfo()->getAutoReplyMessage())
            ->setFromBooker(true)
            ->setType(AbMessage::TYPE_COMMON);

        $invoice = new AbInvoice();
        $invoice->addItem(
            (new BookingServiceFee())
                ->setDescription($this->translator->trans($options['description'], [], 'booking'))
                ->setPrice($options['price'])
                ->setQuantity((int) $options['quantity'])
                ->setDiscount(0)
        );

        $message->setInvoice($invoice);
        $abRequest->addMessage($message);

        $this->em->flush();
    }

    public function addMailchimpSubscriber(AbRequest $abRequest)
    {
        $user = $abRequest->getUser();

        $subscriber = [
            'email_address' => $abRequest->getContactEmail(),
            'status' => 'subscribed',
            'merge_fields' => [
                'FNAME' => $user->getFirstname(),
                'LNAME' => $user->getLastname(),
            ],
        ];

        $task = new Task(
            'aw.mailchimp.api',
            StringUtils::getRandomCode(20),
            null,
            [$subscriber]
        );

        $this->process->execute($task);
    }

    /**
     * @return Usr
     */
    protected function getCurrentUser()
    {
        if (!empty($this->tokenStorage->getToken()) && $this->tokenStorage->getToken()->getUser() != 'anon.') {
            return $this->tokenStorage->getToken()->getUser();
        } else {
            return $this->user;
        }
    }

    protected function getRelatedUsers($users, $excluding = [], $accessLevel = [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY])
    {
        $connection = $this->em->getConnection();
        $sql = "
			SELECT DISTINCT
				COALESCE(u2.UserID, u.UserID) AS UserID
			FROM
				Usr u
				LEFT OUTER JOIN UserAgent ua ON ua.ClientID = u.UserID AND u.AccountLevel = ? AND ua.AccessLevel in (?) AND ua.IsApproved = ?
				LEFT OUTER JOIN Usr u2 ON u2.UserID = ua.AgentID
			WHERE
				u.UserID IN (?)
		";
        $stmt = $connection->executeQuery(
            $sql,
            [ACCOUNT_LEVEL_BUSINESS, $accessLevel, 1, $users],
            [\PDO::PARAM_STR, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, \PDO::PARAM_STR, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
        );
        $result = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (in_array($row['UserID'], $excluding)) {
                continue;
            }
            $result[] = $row['UserID'];
        }

        return $result;
    }

    /** @param AbRequest $request */
    private function addMembers($request)
    {
        /** @var AbPassenger $passenger */
        foreach ($request->getPassengers() as $passenger) {
            if (!$passenger->getUseragent()) {
                $this->addMember($passenger->getRequest()->getBooker(), $passenger);
            }
        }
    }

    private function setBookerEmailHeaders(Usr $booker, \Swift_Message $message)
    {
        /** @var AbBookerInfo $info */
        $info = $booker->getBookerInfo();

        if (!empty($booker->getBookerInfo()->getFromEmail())) {
            $fromEmail = $booker->getBookerInfo()->getFromEmail();
        } else {
            $fromEmail = $this->mailer->getEmail('from');
        }

        $message->setFrom($fromEmail, $info->getServiceName());
        $message->setReplyTo($fromEmail, $info->getServiceName());
        $message->setReturnPath(is_array($fromEmail) ? key($fromEmail) : $fromEmail);

        return $message;
    }

    private function registerSmtpError(Usr $booker, \Swift_Message $message)
    {
        $em = $this->em;

        return function (\Exception $e) use ($booker, $em, $message) {
            /** @var AbBookerInfo $info */
            $info = $booker->getBookerInfo();
            $info->setSmtpError(mb_substr($e->getMessage(), 0, 250, 'utf-8'));
            $info->setSmtpErrorDate(new \DateTime());
            $em->flush();
            $this->mailer->send($message, [Mailer::OPTION_FIX_BODY => false]);
        };
    }

    private function registerSmtpDelivery(Usr $user)
    {
        return function (Mailer $mailer) use ($user) {
            $this->emailLog->recordEmailToLog($user->getUserid(), EmailLog::MESSAGE_KIND_SMTP_BOOKER);
        };
    }

    private function addMember(Usr $user, AbPassenger $passenger)
    {
        $agent = new Useragent();
        $agent->setAgentid($user);
        $agent->setFirstname($passenger->getFirstName());
        $agent->setLastname($passenger->getLastName());
        $agent->setAccesslevel(ACCESS_WRITE);
        $agent->setIsapproved(true);
        $this->em->persist($agent);
        $passenger->setUseragent($agent);
    }

    private function isEMailVerified(Usr $user, string $email): bool
    {
        $q = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->matching(
            Criteria::create()
                ->where(Criteria::expr()->eq('User', $user))
                ->andWhere(Criteria::expr()->eq('ContactEmail', $email))
                ->andWhere(Criteria::expr()->neq('Status', AbRequest::BOOKING_STATUS_NOT_VERIFIED))
                ->andWhere(Criteria::expr()->neq('Status', AbRequest::BOOKING_STATUS_CANCELED))
        );
        $verifiedEmail = $this->em->getRepository(VerifiedEmail::class)->findOneBy(['email' => $email]);

        if (!$q->isEmpty() || null !== $verifiedEmail) {
            return true;
        }

        return false;
    }
}
