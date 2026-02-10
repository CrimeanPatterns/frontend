<?php

namespace AwardWallet\MobileBundle\View\Booking;

use AwardWallet\MainBundle\Entity\AbAccountProgram;
use AwardWallet\MainBundle\Entity\AbCustomProgram;
use AwardWallet\MainBundle\Entity\AbPassenger;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbSegment;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use AwardWallet\MainBundle\Service\SocksMessaging\BookingMessaging;
use AwardWallet\MobileBundle\View\Booking\Block\Dashboard;
use AwardWallet\MobileBundle\View\Booking\Block\Field;
use AwardWallet\MobileBundle\View\Booking\Block\Header;
use AwardWallet\MobileBundle\View\Booking\Block\Note;
use AwardWallet\MobileBundle\View\Booking\Block\PaymentCash;
use AwardWallet\MobileBundle\View\Booking\Block\Request;
use AwardWallet\MobileBundle\View\Booking\Block\Subheader;
use AwardWallet\MobileBundle\View\Booking\Block\Table;
use AwardWallet\MobileBundle\View\Booking\Block\Text;
use AwardWallet\MobileBundle\View\Booking\Block\TimeAgo;
use AwardWallet\MobileBundle\View\Booking\Block\Toggle;
use AwardWallet\MobileBundle\View\Booking\Messages\MessageCriterion;
use AwardWallet\MobileBundle\View\Booking\Messages\MessagesLoader;
use AwardWallet\MobileBundle\View\Date;
use AwardWallet\MobileBundle\View\DateFormatted;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Details implements TranslationContainerInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LocalizeService
     */
    private $localizer;

    /**
     * @var string
     */
    private $host;
    /**
     * @var MessagesLoader
     */
    private $messagesLoader;
    /**
     * @var BookingMessaging
     */
    private $bookingMessaging;
    /**
     * @var AwTwigExtension
     */
    private $twigExt;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var ProviderTranslator
     */
    private $providerTranslator;
    /**
     * @var AbRequestRepository
     */
    private $abRep;
    /**
     * @var SafeExecutorFactory
     */
    private $safeExecutorFactory;

    public function __construct(
        EntityManager $em,
        TranslatorInterface $translator,
        LocalizeService $localizer,
        MessagesLoader $messagesLoader,
        BookingMessaging $bookingMessaging,
        $host,
        AwTwigExtension $twigExt,
        ApiVersioningService $apiVersioning,
        ProviderTranslator $providerTranslator,
        SafeExecutorFactory $safeExecutorFactory
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->localizer = $localizer;
        $this->host = $host;
        $this->messagesLoader = $messagesLoader;
        $this->bookingMessaging = $bookingMessaging;
        $this->twigExt = $twigExt;
        $this->apiVersioning = $apiVersioning;
        $this->providerTranslator = $providerTranslator;
        $this->abRep = $em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);
        $this->safeExecutorFactory = $safeExecutorFactory;
    }

    /**
     * @param Usr $user current user
     * @return array
     */
    public function getView(Usr $user)
    {
        $lastUnread = $this->abRep->getLastUnreadByUser($user, false);

        $this->em->beginTransaction();

        try {
            /** @var AbRequest[] $abRequests */
            $abRequests = $this->em->createQueryBuilder()
                ->select('r')
                ->from(AbRequest::class, 'r')
                ->where('r.User = :user')
                ->setParameter(':user', $user)
                ->orderBy('r.LastUpdateDate', 'desc')
                ->addOrderBy('r.AbRequestID', 'desc')
                ->getQuery()->getResult();

            $createPropertyFetcher = function (QueryBuilder $partialQueryBuilder) {
                return function ($alias, $property) use ($partialQueryBuilder) {
                    $builder = (clone $partialQueryBuilder);
                    $builder->addSelect('property')
                        ->leftJoin("{$alias}.{$property}", 'property')
                        ->getQuery()->execute();
                };
            };

            $fetchProperty = $createPropertyFetcher(
                $this->em->createQueryBuilder()
                    ->select('partial request.{AbRequestID}')
                    ->from(AbRequest::class, 'request')
                    ->where('request.AbRequestID IN (:requests)')
                    ->setParameter(':requests', $abRequests)
            );

            foreach (['BookerUser', 'RequestsMark', 'Segments', 'Passengers', 'Accounts', 'CustomPrograms'] as $property) {
                $fetchProperty('request', $property);
            }

            $bookers = [];

            foreach ($abRequests as $abRequest) {
                $bookers[] = $abRequest->getBooker();
            }

            $fetchProperty = $createPropertyFetcher(
                $this->em->createQueryBuilder()
                    ->select('partial user.{userid}')
                    ->from(Usr::class, 'user')
                    ->where('user.userid IN (:users)')
                    ->setParameter(':users', $bookers)
            );

            foreach (['BookerInfo', 'businessInfo'] as $property) {
                $fetchProperty('user', $property);
            }

            $accounts = [];

            foreach ($abRequests as $abRequest) {
                foreach ($abRequest->getAccounts() as $abAccountProgram) {
                    $accounts[] = (int) $abAccountProgram->getAccount()->getAccountid();
                }
            }

            $this->em->createQueryBuilder()
                ->select('a')
                ->from(Account::class, 'a')
                ->where('a.accountid IN (:account)')
                ->setParameter(':account', $accounts)
                ->getQuery()
                ->execute();

            if ($accounts) {
                $fetchProperty = $createPropertyFetcher(
                    $partialQueryBuilder = $this->em->createQueryBuilder()
                        ->select('partial account.{accountid}')
                        ->from(Account::class, 'account')
                        ->where('account.accountid IN (:accounts)')
                        ->setParameter(':accounts', $accounts, Connection::PARAM_INT_ARRAY)
                );

                $fetchProperty('account', 'providerid');
                $builder = (clone $partialQueryBuilder);
                $builder
                    ->addSelect('ap')
                    ->addSelect('pp')
                    ->leftJoin('account.Properties', 'ap')
                    ->leftJoin('ap.providerpropertyid', 'pp')
                    ->getQuery()->execute();
            }
        } finally {
            $this->em->rollback();
        }

        $result = [
            'requests' => [],
            'dashboard' => (new Dashboard())
                    ->setActive((int) $this->abRep->getActiveRequestsCountByUser($user))
                    ->setLastUnread(isset($lastUnread) ? $lastUnread->getAbRequestID() : null),
            'channel' => $this->bookingMessaging->getUserMessagesChannel($user),
        ];

        if (sizeof($abRequests)) {
            $messagesViews = $this->messagesLoader->loadMessageViews(
                array_map(function ($request) use ($user) {
                    return
                        (new MessageCriterion($request, $user))
                        ->setFlags(
                            MessageCriterion::FLAG_LOAD_LAST_UNREAD |
                            MessageCriterion::FLAG_LOAD_CHUNK
                        );
                }, $abRequests)
            );

            foreach ($abRequests as $i => $abRequest) {
                $this->safeExecutorFactory
                    ->make(function () use ($abRequest, $user, $messagesViews, &$result) {
                        $result['requests'][] = $this->formatRequest($abRequest, $user, $messagesViews);
                    })
                    ->run();
            }
        }

        return $result;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('booking.date.ideal-label', 'booking'))->setDesc('Ideal'),
        ];
    }

    private function addDetails(Request $req, AbRequest $abRequest)
    {
        $req->addRow(new Header($this->t('booking.request') . " <span class=\"bold\">#" . $abRequest->getAbRequestID() . "</span>"));
        $cabins = [];

        if ($abRequest->getCabinEconomy()) {
            $cabins[] = $this->t('booking.economy.class');
        }

        if ($abRequest->getCabinBusiness()) {
            $cabins[] = $this->t('booking.business.class');
        }

        if ($abRequest->getCabinFirst()) {
            $cabins[] = $this->t('booking.first.class');
        }

        if (sizeof($cabins) > 0) {
            $req->addRow(new Field($this->t('booking.class'), implode(", ", $cabins)));
        }
        $req->addRow(new TimeAgo($this->t('booking.table.headers.create-date'), new Date($abRequest->getCreateDate())));
        $req->addRow(new TimeAgo($this->t('booking.table.headers.last-update'), new Date($abRequest->getLastUpdateDate())));
        $req->addRow(new Field($this->t('booking.table.headers.status'), $req->status));

        if (sizeof($abRequest->getPassengers()) > 0) {
            $this->addTravelers($req, $abRequest->getPassengers());
        }

        if (sizeof($abRequest->getSegments()) > 0) {
            $this->addSegments($req, $abRequest->getSegments());
        }

        if ($abRequest->getPaymentCash()) {
            $req->addRow(new PaymentCash($this->t('booking.paying_money_instead_miles.iam')));
        }

        $accounts = $abRequest->getAccounts();
        $programs = $abRequest->getCustomPrograms();

        if ($accounts->count() > 0 || $programs->count() > 0) {
            $req->addRow(new Header($this->t('booking.miles')));
            $toggle = new Toggle();
            $table = new Table([
                $this->t('booking.display.name'),
                $this->t('booking.owner'),
                $this->t('booking.elite.status'),
                $this->t('booking.balance'),
                $this->t('booking.table.headers.last-update'),
            ]);

            if ($accounts->count() > 0) {
                $this->addAccounts($toggle, $table, $accounts);
            }

            if ($programs->count() > 0) {
                $this->addCustomPrograms($toggle, $table, $programs);
            }
            $table->removeEmptyColumns();
            $toggle->addToTablet($table);
            $req->addRow($toggle);
        }

        $req->addRow(new Header($this->t('booking.contact.info')));
        $req->addRow(new Field($this->t('booking.name'), $abRequest->getContactName()));
        $req->addRow(new Field($this->t('booking.phone'), $abRequest->getContactPhone()));
        $req->addRow(new Field($this->t('booking.contactemail'), $abRequest->getMainContactEmail()));

        if (!empty($value = $abRequest->getPriorSearchResults())) {
            if ($value != '-') {
                $req->addRow(new Note(
                    $this->t('booking.prior.searches'),
                    nl2br($this->twigExt->auto_link(
                        $this->t('prior.search.title', [
                            '%booker%' => ($bookerInfo = $abRequest->getBooker()->getBookerInfo()) ? $bookerInfo->getServiceName() : $abRequest->getBooker()->getCompany(),
                        ]) . ":<br>" .
                        htmlspecialchars($value)
                    ))
                ));
            }
        }

        if (!empty($value = $abRequest->getNotes())) {
            $req->addRow(new Note(
                $this->t('booking.notes'),
                nl2br($this->twigExt->auto_link(htmlspecialchars($value)))
            ));
        }
    }

    private function t($key, $params = [], $domain = 'booking')
    {
        return $this->translator->trans(/** @Ignore */ $key, $params, $domain);
    }

    private function addTravelers(Request $req, $passengers)
    {
        $req->addRow(new Header($this->t('booking.travelers')));
        $toggle = new Toggle();

        $table = new Table([
            $this->t('booking.name'),
            $this->t('booking.birthday'),
            $this->t('booking.citizenship'),
            $this->t('booking.passenger.gender'),
        ]);

        /** @var AbPassenger $passenger */
        foreach ($passengers as $passenger) {
            // Mobile
            $toggle->addToPhone(new Subheader($passenger->getFullName(), 'icon-user'));
            $bday = '';

            if ($passenger->getBirthday()) {
                $bday = $this->localizer->formatDateTime($passenger->getBirthday(), 'short', 'none');
            }
            $toggle->addToPhone(new Field($this->t('booking.birthday'), $bday));
            $toggle->addToPhone(new Field($this->t('booking.citizenship'), $passenger->getNationality()));

            if ($passenger->getGender() == 'M') {
                $gender = $this->t('booking.passenger.gender.male');
            } else {
                $gender = $this->t('booking.passenger.gender.female');
            }
            $toggle->addToPhone(new Field($this->t('booking.passenger.gender'), $gender));

            // Tablet
            $table->addRow([
                new Text($passenger->getFullName()),
                new Text($bday),
                new Text($passenger->getNationality()),
                new Text($gender),
            ]);
        }
        $table->removeEmptyColumns();
        $toggle->addToTablet($table);

        $req->addRow($toggle);
    }

    private function addSegments(Request $req, $segments)
    {
        $req->addRow(new Header($this->t('booking.destination')));
        $toggle = new Toggle();

        $table = new Table([
            $this->t('booking.table.headers.from-to'),
            $this->t('booking.table.headers.departure'),
        ]);

        /** @var AbSegment $segment */
        foreach ($segments as $segment) {
            $toggle->addToPhone(new Subheader($segment->getDep() . " - " . $segment->getArr()));
            $dep = $idl = null;
            [$dep, $idl] = $this->formatSegmentDates(
                $segment->getDepDateFrom(),
                $segment->getDepDateTo(),
                $segment->getDepDateIdeal()
            );

            if (isset($dep)) {
                $toggle->addToPhone(new Field($this->t('booking.table.headers.departure'), $dep));
            }

            if (isset($idl)) {
                $toggle->addToPhone(new Field($this->t('booking.date.ideal-label'), $idl));
            }

            if (isset($dep) && isset($idl)) {
                $val = $dep . " (" . $this->t('booking.date.ideal', ['%date%' => $idl]) . ")";
            } elseif (isset($dep) && !isset($idl)) {
                $val = $dep;
            } else {
                $val = $idl;
            }
            $table->addRow([
                new Text($segment->getDep() . " - " . $segment->getArr()),
                new Text($val),
            ]);

            if ($segment->isRoundTrip()) {
                $toggle->addToPhone(new Subheader($segment->getArr() . " - " . $segment->getDep()));
                $dep = $idl = null;
                [$dep, $idl] = $this->formatSegmentDates(
                    $segment->getReturnDateFrom(),
                    $segment->getReturnDateTo(),
                    $segment->getReturnDateIdeal()
                );

                if (isset($dep)) {
                    $toggle->addToPhone(new Field($this->t('booking.table.headers.departure'), $dep));
                }

                if (isset($idl)) {
                    $toggle->addToPhone(new Field($this->t('booking.date.ideal-label'), $idl));
                }

                if (isset($dep) && isset($idl)) {
                    $val = $dep . " (" . $this->t('booking.date.ideal', ['%date%' => $idl]) . ")";
                } elseif (isset($dep) && !isset($idl)) {
                    $val = $dep;
                } else {
                    $val = $idl;
                }
                $table->addRow([
                    new Text($segment->getArr() . " - " . $segment->getDep()),
                    new Text($val),
                ]);
            }
        }
        $table->removeEmptyColumns();
        $toggle->addToTablet($table);

        $req->addRow($toggle);
    }

    private function formatSegmentDates($from, $to, $ideal)
    {
        $dep = $idl = null;

        if (isset($from) && !empty($from) && isset($to) && !empty($to) && isset($ideal) && !empty($ideal)) {
            $dep = $this->localizer->formatDateTime($from, 'short', 'none') .
                ' - ' . $this->localizer->formatDateTime($to, 'short', 'none');
            $idl = $this->localizer->formatDateTime($ideal, 'short', 'none');
        } elseif (isset($from) && !empty($from) && isset($to) && !empty($to)) {
            $dep = $this->localizer->formatDateTime($from, 'short', 'none') .
                ' - ' . $this->localizer->formatDateTime($to, 'short', 'none');
        } elseif (isset($ideal) && !empty($ideal)) {
            $idl = $this->localizer->formatDateTime($ideal, 'short', 'none');
        }

        return [$dep, $idl];
    }

    /**
     * @param AbAccountProgram[] $accounts
     */
    private function addAccounts(Toggle $toggle, Table $table, $accounts)
    {
        foreach ($accounts as $account) {
            $eliteLevel = $date = null;
            $lpAccount = $account->getAccount();
            $toggle->addToPhone(new Subheader($this->providerTranslator->translateDisplayNameByEntity($lpAccount->getProviderid())));
            $toggle->addToPhone(new Field($this->t('booking.owner'), $lpAccount->getOwnerFullName()));

            if (!empty($eliteLevel = $lpAccount->getEliteLevel())) {
                $toggle->addToPhone(new Field($this->t('booking.elite.status'), $eliteLevel));
            }
            $toggle->addToPhone(
                new Field(
                    $this->t('booking.balance'),
                    $balance = !is_null($lpAccount->getBalance())
                        ? $this->localizer->formatNumber($lpAccount->getBalance())
                        : '-'
                )
            );

            if ($successDate = $lpAccount->getSuccesscheckdate()) {
                $toggle->addToPhone(
                    new TimeAgo(
                        $this->t('booking.table.headers.last-update'),
                        $date = new Date($successDate)
                    )
                );
            }

            $table->addRow([
                new Text($this->providerTranslator->translateDisplayNameByEntity($lpAccount->getProviderid())),
                new Text($lpAccount->getOwnerFullName()),
                !empty($eliteLevel) ? new Text($eliteLevel) : null,
                new Text($balance),
                !empty($date) ? new TimeAgo(null, $date) : null,
            ]);
        }
    }

    /**
     * @param AbCustomProgram[] $programs
     */
    private function addCustomPrograms(Toggle $toggle, Table $table, $programs)
    {
        foreach ($programs as $program) {
            $owner = $eliteLevel = null;

            if ($program->getProvider()) {
                $toggle->addToPhone(new Subheader($progName = $program->getProvider()->getName()));
            } else {
                $toggle->addToPhone(new Subheader($progName = $program->getName()));
            }

            if (!empty($owner = $program->getOwner())) {
                $toggle->addToPhone(new Field($this->t('booking.owner'), $owner));
            }

            if (!empty($eliteLevel = $program->getEliteStatus())) {
                $toggle->addToPhone(new Field($this->t('booking.elite.status'), $eliteLevel));
            }
            $toggle->addToPhone(
                new Field(
                    $this->t('booking.balance'),
                    $balance = !is_null($program->getBalance())
                        ? $this->localizer->formatNumber($program->getBalance())
                        : '-'
                )
            );

            $table->addRow([
                new Text($progName),
                !empty($owner) ? new Text($owner) : null,
                !empty($eliteLevel) ? new Text($eliteLevel) : null,
                new Text($balance),
                null,
            ]);
        }
    }

    private function getStatusIcon(AbRequest $request)
    {
        switch ($request->getRealStatus(false)) {
            case AbRequest::BOOKING_STATUS_NOT_VERIFIED:
                return 'not-verified';

            case AbRequest::BOOKING_STATUS_PENDING:
                return 'opened';

            case AbRequest::BOOKING_STATUS_BOOKED:
                return 'booked';

            case AbRequest::BOOKING_STATUS_PROCESSING:
                return 'paid';

            case AbRequest::BOOKING_STATUS_CANCELED:
                return 'canceled';

            case AbRequest::BOOKING_STATUS_FUTURE:
                return 'future';

            default:
                return 'opened';
        }
    }

    private function getListTitle(AbRequest $request)
    {
        $result = [];
        $oldSegment = null;

        /** @var AbSegment $segment */
        foreach ($request->getSegments() as $segment) {
            if ($oldSegment != $segment->getDep()) {
                $result[] = $segment->getDep();
            }
            $result[] = $oldSegment = $segment->getArr();
        }

        return implode(" - ", $result);
    }

    private function formatRequest(AbRequest $abRequest, Usr $user, array $messagesViews): Request
    {
        $req = new Request();
        $req->setContactName($abRequest->getContactName());
        $req->setContactUid($abRequest->getUser()->getUserid());
        $req->setId($abRequest->getAbRequestID());

        $bookerInfo = $abRequest->getBooker()->getBookerInfo();
        $req->setBookerName($bookerInfo ? $bookerInfo->getServiceName() : $abRequest->getBooker()->getCompany());
        $req->setBookerIcon(
            $bookerInfo ?
                (
                    $this->host . '/' .
                    $bookerInfo->getEmailLogo()
                ) :
                ''
        );
        $req->setActive($abRequest->isActive());
        $req->setLastUpdateDate(
            $this->apiVersioning->supports(MobileVersions::REGIONAL_SETTINGS) ?
                new DateFormatted(
                    $abRequest->getLastUpdateDate()->getTimestamp(),
                    sprintf(
                        "%s, %s",
                        $this->localizer->formatDateTime(
                            $date = $this->localizer->correctDateTime($abRequest->getLastUpdateDate()),
                            'medium',
                            null
                        ),
                        $this->localizer->formatDateTime($date, null, 'short')
                    )
                ) :
                new Date($abRequest->getLastUpdateDate())
        );

        $req->setNewMessage(!$this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->isRequestReadByUser($abRequest, $user, false));
        $req->setListTitle($this->getListTitle($abRequest));
        $req->setChannels([
            BookingMessaging::CHANNEL_MESSAGES => $this->bookingMessaging->getMessagesChannel($abRequest),
            BookingMessaging::CHANNEL_ONLINE => $this->bookingMessaging->getOnlineChannel($abRequest),
            BookingMessaging::CHANNEL_BOOKER_ONLINE => $this->bookingMessaging->getBookerOnlineChannel($abRequest),
        ]);
        $req->setStatusCode($abRequest->getRealStatus(false));
        $req->setStatus(
            $this->translator->trans(/** @Ignore */
                $this->abRep->getStatusDescription($abRequest->getRealStatus(false)),
                [],
                'booking'
            )
        );
        $req->setStatusIcon($this->getStatusIcon($abRequest));
        /** @var \DateTime $startDate */
        /** @var AbSegment $firstSegment */
        $firstSegment = $abRequest->getSegments()->first();

        if ($firstSegment->getDepDateFrom()) {
            $startDate = $firstSegment->getDepDateFrom();
        }

        if ($firstSegment->getDepDateIdeal()) {
            $startDate = $firstSegment->getDepDateIdeal();
        }

        $req->setStartDate(
            $this->apiVersioning->supports(MobileVersions::REGIONAL_SETTINGS) ?
                new DateFormatted(
                    $startDate->getTimestamp(),
                    [
                        'd' => $this->localizer->patternDateTime($startDate, 'd'),
                        'm' => trim(mb_strtoupper($this->localizer->patternDateTime($startDate, 'LLL')), '.'),
                    ]
                ) :
                new Date($startDate)
        );
        $this->addDetails($req, $abRequest);

        if (isset($messagesViews[$abRequest->getAbRequestID()])) {
            $req->setMessages($messagesViews[$abRequest->getAbRequestID()]);
        }

        return $req;
    }
}
