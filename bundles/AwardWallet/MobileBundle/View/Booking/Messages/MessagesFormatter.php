<?php

namespace AwardWallet\MobileBundle\View\Booking\Messages;

use AwardWallet\MainBundle\Entity\AbInvoiceMiles;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbPhoneNumber;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler as Str;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\MainBundle\Security\Voter\BookingMessageVoter;
use AwardWallet\MainBundle\Service\AvatarJpegHelper;
use AwardWallet\MainBundle\Service\Booking\MessageFormatter;
use AwardWallet\MainBundle\Service\UserAvatar;
use AwardWallet\MobileBundle\View\Booking\Block\Message;
use AwardWallet\MobileBundle\View\Date;
use AwardWallet\MobileBundle\View\DateFormatted;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class MessagesFormatter
{
    /**
     * Increase after template changes, data fixes etc.
     */
    public const MESSAGE_INTERNAL_DATE_OFFSET = 7;

    /**
     * Increase after YouCanBookMe callback messages change.
     */
    public const AUTO_REPLY_MESSAGE_INTERNAL_DATE_OFFSET = 0;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var BookingMessageVoter
     */
    private $messageVoter;

    /**
     * @var LocalizeService
     */
    private $localizeService;

    /**
     * @var ApiVersioningService
     */
    private $apiVersioningService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $host;

    /**
     * @var AbRequestRepository
     */
    private $requestRep;

    /**
     * @var UseragentRepository
     */
    private $useragentRep;

    /**
     * @var UsrRepository
     */
    private $userRep;
    /**
     * @var AvatarJpegHelper
     */
    private $avatarJpegHelper;

    /**
     * @var DefaultBookerParameter
     */
    private $defaultBooker;
    /**
     * @var UserAvatar
     */
    private $userAvatar;

    public function __construct(
        Environment $twig,
        EntityManagerInterface $em,
        BookingMessageVoter $messageVoter,
        $rootDir,
        LocalizeService $localizeService,
        ApiVersioningService $apiVersioningService,
        TranslatorInterface $translator,
        RouterInterface $router,
        AvatarJpegHelper $avatarJpegHelper,
        UserAvatar $userAvatar,
        $host,
        DefaultBookerParameter $defaultBooker
    ) {
        $this->twig = $twig;
        $this->em = $em;
        $this->rootDir = $rootDir;
        $this->messageVoter = $messageVoter;
        $this->localizeService = $localizeService;
        $this->apiVersioningService = $apiVersioningService;
        $this->translator = $translator;
        $this->router = $router;
        $this->host = $host;
        $this->defaultBooker = $defaultBooker;

        $this->requestRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);
        $this->useragentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $this->userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->avatarJpegHelper = $avatarJpegHelper;
        $this->userAvatar = $userAvatar;
    }

    public function formatMessage(AbMessage $abMessage, MessageCriterion $messageCriterion, bool $asHtml = false): Message
    {
        $currentUser = $messageCriterion->viewer;
        $currentUserId = $currentUser->getUserid();
        $messageId = $abMessage->getAbMessageID() ?: MessagesLoader::AUTO_REPLY_MESSAGE_ID;
        $fromBooker = $abMessage->getFromBooker();
        $messageUser = $abMessage->getUser();

        if ($messageUser->isBusiness()) {
            $businessUser = $messageUser;
        } else {
            $businessUser = $this->userRep->getBusinessByUser($messageUser);
        }

        if (
            isset($messageCriterion->messageVersions[$messageId])
            && (null !== ($internalDate = $this->getInternalDate($abMessage, $messageId)))
            && ($messageCriterion->messageVersions[$messageId] >= $internalDate)
        ) {
            return (new Message())
                ->setId($messageId)
                ->setHidden(true)
                ->setInternalDateTimestamp($messageCriterion->messageVersions[$messageId]);
        }

        $formatted = Message::create($abMessage)
            ->setId($messageId)
            ->setBox($fromBooker ? Message::BOX_INCOME : Message::BOX_OUTCOME)
            ->setDate($this->formatDate($abMessage->getCreateDate()))
            ->setLastUpdate(
                ($lastUpdateDate = $abMessage->getLastUpdateDate()) ?
                    $this->formatDate($lastUpdateDate) :
                    null
            )
            ->setInternalDate($this->getInternalDate($abMessage, $messageId))
            ->setReaded(
                ($messageUser->getUserid() == $currentUser->getUserid())
                || (
                    array_key_exists($currentUserId, $messageCriterion->lastReadByUser) ?
                    (
                        (false === $messageCriterion->lastReadByUser[$currentUserId]) // read mark absents, so message is read
                        || (
                            ($messageCriterion->lastReadByUser[$currentUserId] instanceof \DateTimeInterface)
                            && $abMessage->isReaded($messageCriterion->lastReadByUser[$currentUserId])
                        )
                    ) :
                    false
                )
            )
            ->setRequestUpdateDate(new DateFormatted(
                $abMessage->getCreateDate()->getTimestamp(),
                sprintf(
                    "%s, %s",
                    $this->localizeService->formatDateTime(
                        $date = $this->localizeService->correctDateTime($abMessage->getCreateDate()),
                        'medium',
                        null
                    ),
                    $this->localizeService->formatDateTime($date, null, 'short')
                )
            ));

        if (
            $abMessage->getFromBooker()
            && $businessUser
            && $businessUser->getPicturever()
            && !Str::isEmpty($avatar = $businessUser->getAvatarLink('small'))
            && !str::isEmpty($avatar = @file_get_contents($this->rootDir . '/../web' . $avatar))
            && false !== $avatar
        ) {
            if ($this->apiVersioningService->supports(MobileVersions::AVATAR_JPEG)) {
                $formatted->setAvatar($this->avatarJpegHelper->getUserAvatarUrl($businessUser, UrlGeneratorInterface::ABSOLUTE_URL));
            } else {
                $formatted->setAvatar(
                    'data:image/gif;charset=utf-8;base64,' .
                    base64_encode($avatar)
                );
            }
        }

        if ($abMessage->getFromBooker() && $messageUser->isBooker()) {
            $avatarUser = $messageUser;
        } elseif ($abMessage->getFromBooker()) {
            if ($messageUser->getPicturever()) {
                $avatarUser = $messageUser;
            } else {
                $avatarUser = $this->userRep->getBookerByUser($messageUser);
            }
        } elseif ($messageUser->getPicturever()) {
            $avatarUser = $messageUser;
        }

        if (
            isset($avatarUser)
            && $avatarUser->getPicturever()
            && !Str::isEmpty($avatar = $avatarUser->getAvatarLink('small'))
            && !str::isEmpty($avatar = @file_get_contents($this->rootDir . '/../web' . $avatar))
            && (false !== $avatar)
        ) {
            if ($this->apiVersioningService->supports(MobileVersions::AVATAR_JPEG)) {
                $formatted->setAvatar($this->avatarJpegHelper->getUserAvatarUrl($avatarUser, UrlGeneratorInterface::ABSOLUTE_URL));
            } else {
                $formatted->setAvatar(
                    'data:image/gif;charset=utf-8;base64,' .
                    base64_encode($avatar)
                );
            }
        }

        if (
            isset($avatarUser)
            && StringUtils::isEmpty($formatted->avatar)
        ) {
            $formatted->setAvatar($this->userAvatar->getUserUrl($avatarUser));
        }

        if ($messageUser->isBooker()) {
            $company = $messageUser->getCompany();
            $author = StringUtils::isNotEmpty($company) ?
                $company :
                $messageUser->getFullName();
        } elseif ($abMessage->getFromBooker()) {
            $bookerInfo = $abMessage->getRequest()->getBooker()->getBookerInfo();

            if ($bookerInfo) {
                $author =
                    $messageUser->getFullName() .
                    (
                        ($bookerInfo->getUserID()->getUserid() !== $this->defaultBooker->get()) ?
                            ' @ ' . $bookerInfo->getServiceName() :
                            ''
                    );
            } else {
                $author = $messageUser->getFullName() . ' @ ' . $abMessage->getRequest()->getBooker()->getCompany();
            }
        } else {
            $author = $abMessage->getRequest()->getContactName();
        }

        $formatted->setAuthor($author);

        if (MessagesLoader::AUTO_REPLY_MESSAGE_ID === $messageId) {
            $formatted
                ->setCanEdit(false)
                ->setCanDelete(false);
        } else {
            $token = new PostAuthenticationGuardToken($currentUser, 'none', $currentUser->getRoles());
            $formatted->setCanEdit($this->messageVoter->edit($token, $abMessage));
            $formatted->setCanDelete($this->messageVoter->delete($token, $abMessage));
        }

        $newFormat = !$asHtml && $this->apiVersioningService->supports(MobileVersions::BOOKING_MESSAGES_FORMAT_V2);
        $renderContext = [
            'message' => $abMessage,
            'msgReplacedVars' => MessageFormatter::getMessageReplacedVars($abMessage->getRequest()),
        ];
        $renderAsHtml = false;

        switch (true) {
            case $formatted instanceof Message\Invoice:
                $type = 'invoice';

                if ($newFormat) {
                    $this->formatInvoice($formatted, $abMessage);
                }

                break;

            case $formatted instanceof Message\ShareAccountsRequest:
                $type = 'shareAccounts';
                $renderContext['shareAction'] = 'request';
                $renderContext['uaRep'] = $this->useragentRep;

                if ($newFormat) {
                    $this->formatShareAccounts($formatted, $abMessage);
                }

                break;

            case $formatted instanceof Message\ShareAccountsResponse:
                $type = 'shareAccounts';
                $renderContext['shareAction'] = 'response';
                $renderContext['uaRep'] = $this->useragentRep;

                if ($newFormat) {
                    $this->formatShareAccounts($formatted, $abMessage);
                }

                break;

            case $formatted instanceof Message\SeatAssignments:
                $type = 'seatAssignments';

                if ($newFormat) {
                    $this->formatSeatAssignments($formatted, $abMessage);
                }

                break;

            case $formatted instanceof Message\UserText:
                $type = 'userText';
                $renderAsHtml = true;

                break;

            case $formatted instanceof Message\Ycb:
                $type = 'ycb';
                $renderAsHtml = true;

                break;

            default:
                $type = 'service';
                $renderContext['reqRep'] = $this->requestRep;
                $renderContext['usrRep'] = $this->userRep;

                if ($newFormat) {
                    if ($abMessage->getFromBooker() && $this->userRep->getBookerByUser($abMessage->getUser())) {
                        $author = $abMessage->getUser()->getFullName();
                    } else {
                        $author = $abMessage->getRequest()->getContactName();
                    }

                    switch (true) {
                        case $formatted instanceof Message\UpdateRequest:
                            $this->formatUpdateRequest($formatted, $abMessage, $author);

                            break;

                        case $formatted instanceof Message\ChangeStatusRequest:
                            $this->formatChangeStatusRequest($formatted, $abMessage, $author);

                            break;

                        case $formatted instanceof Message\InvoicePaid:
                            $this->formatInvoicePaid($formatted, $abMessage, $author);

                            break;

                        case $formatted instanceof Message\WriteCheck:
                            $this->formatWriteCheck($formatted, $abMessage, $author);

                            break;
                    }
                }
        }

        if (!$newFormat || $renderAsHtml) {
            if (!$newFormat) {
                $formatted->setType($type);
            }
            $formatted->setBody($this->renderHtml($type, $renderContext));
        }

        return $formatted;
    }

    /**
     * Formats html markup to be compatible with substandard html-rendering
     * in mobile devices.
     */
    private function renderHtml(string $type, array $renderContext): string
    {
        $html = $this->twig->render("@AwardWalletMobile/Booking/Messages/{$type}.html.twig", $renderContext);
        $html = \preg_replace('/[\n\r]/', '', $html);
        $html = \preg_replace('/[ \t]+/', ' ', $html);

        return (string) $html;
    }

    private function formatInvoice(Message\Invoice $formatted, AbMessage $message): void
    {
        $tr = $this->translator;
        $l = $this->localizeService;
        $abRequest = $message->getRequest();
        $bookerInfo = $abRequest->getBooker()->getBookerInfo();
        $invoice = $message->getInvoice();
        $currencyCode = $bookerInfo->getCurrency()->getCode();
        $contactName = $abRequest->getContactName();

        $formatted
            ->setIntro($tr->trans('invoice.message.intro', ['%contactname%' => htmlspecialchars($contactName)], 'booking'))
            ->setBookerLogoSrc($bookerInfo ? ($this->host . '/' . $bookerInfo->getMobileInvoiceLogo()) : '')
            ->setBookerAddress($bookerInfo ? $bookerInfo->getAddress() : '')
            ->setBookerEmail($bookerInfo ? $bookerInfo->getFromEmail() : '')
            ->addHeader($tr->trans('invoice.invoice', [], 'booking'), $invoice->getId())
            ->addHeader($tr->trans('invoice.message.terms', [], 'booking'), $tr->trans('invoice.message.due-on-receipt', [], 'booking'))
            ->addHeader($tr->trans('invoice.message.invoice-date', [], 'booking'), $l->formatDateTime($l->correctDateTime($message->getCreateDate()), 'medium', 'none'))
            ->addHeader($tr->trans('invoice.message.bill-to', [], 'booking'), [
                'contactName' => $contactName,
                'contactPhone' => $abRequest->getContactPhone(),
                'contactEmail' => $abRequest->getContactEmail(),
            ], 'billTo');

        foreach ($invoice->getItems() as $item) {
            $discount = null;
            $total = null;

            if ($item->getDiscount() > 0) {
                $discount = $l->formatNumber($item->getDiscount()) . "% " . $tr->trans('invoice.message.discount', [], 'booking');
                $total = $l->formatCurrency($item->getTotal(), $currencyCode);
            }
            $formatted->addItem(
                $item->getDescription(),
                $l->formatNumber($item->getQuantity()),
                $l->formatCurrency($item->getPrice(), $currencyCode),
                $l->formatCurrency($item->getPriceTotal(), $currencyCode),
                $tr,
                $discount,
                $total
            );
        }

        /** @var AbInvoiceMiles $program */
        foreach ($invoice->getMiles() as $program) {
            $formatted->addMiles(
                $tr->trans('invoice.message.program-miles', [
                    '%program%' => $program->getCustomName(),
                    '%owner%' => $program->getOwner(),
                ], 'booking'),
                $tr->trans('business.transactions.amount', [], 'messages'),
                $l->formatNumber($program->getBalance())
            );
        }

        $formatted
            ->setTotalLabel($tr->trans('invoice.form.total-label', [], 'booking'))
            ->setTotal($l->formatCurrency($invoice->getTotal(), $currencyCode))
            ->setIsPaid($invoice->isPaid())
            ->setFooter($tr->trans('invoice.message.footer', [], 'booking'));

        if (!$invoice->isPaid()) {
            $formatted
                ->setProceedButton($tr->trans('invoice.message.payment-button', [], 'booking'))
                ->setProceedButtonUrl(
                    $this->router->generate('aw_booking_view_index', [
                        'id' => $abRequest->getAbRequestID(),
                        'invoice' => $message->getAbMessageID(),
                        'KeepDesktop' => 1,
                    ], UrlGeneratorInterface::ABSOLUTE_URL)
                );
        }
    }

    private function formatShareAccounts(Message\ShareAccountsResponse $formatted, AbMessage $message): void
    {
        $isResponse = !($formatted instanceof Message\ShareAccountsRequest);
        $tr = $this->translator;
        $abRequest = $message->getRequest();
        $notShared = 0;

        foreach ($abRequest->getAccounts() as $account) {
            if (!$account->getRequested()) {
                continue;
            }

            if (in_array($account->getAbAccountProgramID(), $message->getMetadata()->getAPR())) {
                $a = $account->getAccount();
                $accessLevel = $this->useragentRep->getAccessLevel($a->getUser(), $a, $abRequest->getBooker(), false);

                if (is_empty($accessLevel)) {
                    $notShared++;
                }
                $formatted->addAccount(
                    $a->getProviderid()->getName(),
                    !is_null($a->getBalance()) ? $this->localizeService->formatNumber($a->getBalance()) : '-',
                    $a->getOwnerFullName(),
                    $tr
                );
            }
        }

        foreach ($abRequest->getCustomPrograms() as $account) {
            if (!$account->getRequested()) {
                continue;
            }

            if (in_array($account->getAbCustomProgramID(), $message->getMetadata()->getCPR())) {
                $notShared++;
                $formatted->addAccount(
                    is_empty($account->getProvider()) ? $account->getName() : $account->getProvider()->getName(),
                    !is_null($account->getBalance()) ? $this->localizeService->formatNumber($account->getBalance()) : '-',
                    $account->getOwner(),
                    $tr
                );
            }
        }

        if ($isResponse) {
            if ($notShared > 0) {
                $introMessage = $tr->trans('booking.share.user-share-requested', [], 'booking');
            } else {
                $introMessage = $tr->trans('booking.share.user-share-shared', [
                    '%booker%' => ($bookerInfo = $abRequest->getBooker()->getBookerInfo()) ?
                        $bookerInfo->getServiceName() :
                        $abRequest->getBooker()->getCompany(),
                ], 'booking');
            }
        } else {
            $introMessage = $tr->trans('booking.share.user-share-requested', [], 'booking');

            if ($notShared > 0) {
                /** @var Message\ShareAccountsRequest $formatted */
                $formatted
                    ->setShareButton($tr->trans('award.account.list.menu.actions.share', [], 'messages'))
                    ->setShareButtonUrl($this->router->generate('aw_booking_share_index', [
                        'id' => $abRequest->getAbRequestID(),
                        'KeepDesktop' => 1,
                    ], UrlGeneratorInterface::ABSOLUTE_URL));
            }
        }

        $formatted->setMessage($introMessage);
    }

    private function formatSeatAssignments(Message\SeatAssignments $formatted, AbMessage $message)
    {
        $tr = $this->translator;
        $formatted->setMessage($tr->trans('booking.seat-assignments.message', [], 'booking'));

        /** @var AbPhoneNumber $number */
        foreach ($message->getPhoneNumbers() as $number) {
            $formatted->addPhoneNumber($number->getProvider(), $number->getPhone(), $tr);
        }
    }

    private function formatUpdateRequest(Message\UpdateRequest $formatted, AbMessage $message, $author)
    {
        $formatted->setMessage($this->translator->trans('booker.message.post.update-request', [
            '%user_name%' => $author,
        ], 'booking'));
    }

    private function formatChangeStatusRequest(Message\ChangeStatusRequest $formatted, AbMessage $message, $author)
    {
        $tr = $this->translator;
        $status = $message->getMetadata()->getStatus();
        $formatted->setStatusCode($this->requestRep->getStatusCode($status));
        $formatted->setStatusDesc($tr->trans($this->requestRep->getStatusDescription($status), [], 'booking'));

        if ($status === AbRequest::BOOKING_STATUS_PENDING && !$message->getFromBooker()) {
            $formatted->setMessage($tr->trans('booker.message.post.status-request.pending', [
                '%user_name%' => $author,
            ], 'booking'));
        } else {
            $formatted->setMessage($tr->trans('booker.message.post.status-request.other', [
                '%user_name%' => '%author%',
                '%request_status%' => '%status%',
            ], 'booking'));
            $formatted->setReplacements([
                '%author%' => $author,
                '%status%' => $formatted->statusDesc,
            ]);
        }
    }

    private function formatInvoicePaid(Message\InvoicePaid $formatted, AbMessage $message, $author)
    {
        $request = $message->getRequest();
        $booker = $request->getBooker();
        $invoiceId = $message->getMetadata()->getInvoiceId();

        if (!$invoiceId) {
            if ($lastInvoice = $request->getLastInvoice()) {
                $invoiceId = $lastInvoice->getId();
            } else {
                $invoiceId = '-';
            }
        }
        $totalInvoice = $message->getMetadata()->getTotalInvoice();
        $amount = '-';

        if ($totalInvoice) {
            $amount = $this->localizeService->formatCurrency($totalInvoice, ($bookerInfo = $booker->getBookerInfo()) ? $bookerInfo->getCurrency()->getCode() : null);
        }

        $formatted->setBody($this->translator->trans('booker.message.post.invoice-paid', [
            '%user_name%' => $author,
            '%amount%' => $amount,
            '%invoiceId%' => $invoiceId,
        ], 'booking'));
    }

    private function formatWriteCheck(Message\WriteCheck $formatted, AbMessage $message, $author)
    {
        $request = $message->getRequest();
        $booker = $request->getBooker();
        $totalInvoice = $message->getMetadata()->getTotalInvoice();
        $amount = '-';

        if ($totalInvoice) {
            $amount = $this->localizeService->formatCurrency($totalInvoice, ($bookerInfo = $booker->getBookerInfo()) ? $bookerInfo->getCurrency()->getCode() : null);
        }

        $formatted
            ->setMessage($this->translator->trans('booker.message.post.write-check', [
                '%user_name%' => '%author%',
                '%booker_name%' => '%booker%',
                '%booker_address%' => '%address%',
                '%check_amount%' => '%amount%',
            ], 'booking'))
            ->setReplacements([
                '%author%' => htmlspecialchars($author),
                '%booker%' => htmlspecialchars($booker->getFullName()),
                '%address%' => ($bookerInfo = $booker->getBookerInfo()) ? $bookerInfo->getAddress() : '',
                '%amount%' => $amount,
            ]);
    }

    /**
     * @return Date|DateFormatted
     */
    private function formatDate(\DateTime $dateTime)
    {
        return $this->apiVersioningService->supports(MobileVersions::REGIONAL_SETTINGS) ?
            new DateFormatted(
                $dateTime->getTimestamp(),
                $this->localizeService->formatDateTime(
                    $this->localizeService->correctDateTime($dateTime),
                    'medium',
                    null
                )
            ) :
            new Date($dateTime);
    }

    /**
     * @param int $messageId
     * @return int|null
     */
    private function getInternalDate(AbMessage $abMessage, $messageId)
    {
        if ($messageId === MessagesLoader::AUTO_REPLY_MESSAGE_ID) {
            $abRequest = $abMessage->getRequest();
            $abBookerInfo = $abRequest->getBooker()->getBookerInfo();

            if ($abBookerInfo) {
                $internalDate = clone max(
                    $abBookerInfo->getUpdateDate(),
                    $abRequest->getCreateDate()
                );
            } else {
                $internalDate = $abRequest->getCreateDate();
            }

            $internalDate->add(new \DateInterval(sprintf('PT%dS', self::AUTO_REPLY_MESSAGE_INTERNAL_DATE_OFFSET)));
        } elseif ($abMessage->isUserText()) {
            $internalDate = clone $abMessage->getVersionDate();
        } else {
            return null;
        }

        return $internalDate->add(new \DateInterval(sprintf('PT%dS', self::MESSAGE_INTERNAL_DATE_OFFSET)))->getTimestamp();
    }
}
