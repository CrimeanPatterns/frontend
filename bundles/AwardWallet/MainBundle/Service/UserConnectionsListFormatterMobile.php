<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\Connection;
use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\UserConnectionsQuery;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iter\fromCallable;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class UserConnectionsListFormatterMobile
{
    public const WAITING_PERIOD_SEC = DateTimeUtils::SECONDS_PER_DAY * 3;

    public const ACTION_DELETE = 'delete';
    public const ACTION_RESEND = 'resend';
    public const ACTION_INVITE = 'invite';
    public const ACTION_CANCEL_INVITE = 'cancel_invite';
    public const ACTION_APPROVE = 'approve';
    public const ACTION_DENY = 'deny';
    public const ACTION_FULL_ACCESS = 'full_access';
    public const ACTION_READ_ONLY = 'read_only';

    public const STATUS_WAITING = 'waiting';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_EXPIRED = 'expired';

    public const TYPE_FAMILY_MEMBER = 'family_member';
    public const TYPE_CONNECTED_USER = 'connected_user';
    public const TYPE_INVITE = 'invite';

    private TranslatorInterface $translator;

    private LegacyUrlGenerator $legacyUrlGenerator;

    private UserConnectionsQuery $userConnectionsQuery;

    private ApiVersioningService $apiVersioning;

    private AvatarJpegHelper $avatarJpegHelper;

    public function __construct(
        TranslatorInterface $translator,
        LegacyUrlGenerator $legacyUrlGenerator,
        UserConnectionsQuery $userConnectionsQuery,
        ApiVersioningService $apiVersioning,
        AvatarJpegHelper $avatarJpegHelper
    ) {
        $this->translator = $translator;
        $this->legacyUrlGenerator = $legacyUrlGenerator;
        $this->userConnectionsQuery = $userConnectionsQuery;
        $this->apiVersioning = $apiVersioning;
        $this->avatarJpegHelper = $avatarJpegHelper;
    }

    public function loadFormattedData(Usr $user): array
    {
        $connectionsData = $this->userConnectionsQuery->run($user);

        [$connections, $familyMembers] =
            it($connectionsData['connections'] ?? [])
            ->partition(function ($connectionData) { return isset($connectionData['ClientID']); });

        return [
            'connections' => $this->formatConnectionsList($connections)
                ->chain(
                    it($connectionsData['emailInvites'] ?? [])
                    ->map(function ($emailInviteData) { return $this->formatInvite($emailInviteData); })
                )
                ->toArray(),
            'familyMembers' => $this->formatConnectionsList($familyMembers)
                ->toArray(),
            'pendingConnections' => it($connectionsData['pendingConnections'] ?? [])
                ->map(function (Connection $pendingConnectionData) { return $this->formatPending($pendingConnectionData); })
                ->toArray(),
        ];
    }

    public function formatConnection(Connection $connectionData): array
    {
        return [
            'id' => (int) $connectionData['UserAgentID'],
            'name' => $connectionData['FullName'],
            'email' => StringUtils::notEmptyOrNull($connectionData['UserEmail']) ??
                StringUtils::notEmptyOrNull($connectionData['Email']),
            'actions' => it(fromCallable(function () use ($connectionData) {
                if (isset($connectionData['ClientID'])) {
                    if (!$connectionData['IsApproved']) {
                        yield self::ACTION_RESEND;
                    }

                    yield self::ACTION_DELETE;
                } else {
                    if (
                        StringUtils::isNotEmpty($connectionData['ShareDate'])
                        && (time() - strtotime($connectionData['ShareDate']) <= self::WAITING_PERIOD_SEC)
                    ) {
                        yield self::ACTION_CANCEL_INVITE;
                    } else {
                        yield self::ACTION_INVITE;

                        yield self::ACTION_DELETE;
                    }
                }
            }))
                ->toArray(),
            'edit' => call(function () use ($connectionData) {
                if (isset($connectionData['ClientID'])) {
                    return (bool) $connectionData['IsApproved'];
                }

                return true;
            }),
            'status' => call(function () use ($connectionData) {
                if (isset($connectionData['ClientID'])) {
                    return $connectionData['IsApproved'] ?
                            self::STATUS_APPROVED :
                            self::STATUS_WAITING;
                } elseif (StringUtils::isNotEmpty($connectionData['ShareDate'])) {
                    return (time() - strtotime($connectionData['ShareDate']) <= self::WAITING_PERIOD_SEC) ?
                            self::STATUS_WAITING :
                            self::STATUS_EXPIRED;
                }

                return null;
            }),
            'type' => isset($connectionData['ClientID']) ?
                    self::TYPE_CONNECTED_USER :
                    self::TYPE_FAMILY_MEMBER,
            'avatar' => isset($connectionData['ClientID']) ?
                    ( // user
                        StringUtils::isAllNotEmpty($connectionData['UserPictureVer'], $connectionData['UserPictureExt']) ?
                            (
                                $this->apiVersioning->supports(MobileVersions::AVATAR_JPEG) ?
                                    $this->avatarJpegHelper->getUserAvatarUrlByParts(
                                        (int) $connectionData['AgentID'],
                                        (int) $connectionData['UserPictureVer'],
                                        UrlGeneratorInterface::ABSOLUTE_URL
                                    ) :
                                    $this->legacyUrlGenerator->generateAbsoluteUrl(Usr::generateAvatarLink(
                                        $connectionData['AgentID'],
                                        $connectionData['UserPictureVer'],
                                        $connectionData['UserPictureExt'],
                                        'small'
                                    ))
                            ) :
                            null
                    ) :
                    ( // family member
                        StringUtils::isAllNotEmpty($connectionData['PictureVer'], $connectionData['PictureExt']) ?
                            (
                                $this->apiVersioning->supports(MobileVersions::AVATAR_JPEG) ?
                                    $this->avatarJpegHelper->getUserAvatarUrlByParts(
                                        (int) $connectionData['UserAgentID'],
                                        (int) $connectionData['PictureVer'],
                                        UrlGeneratorInterface::ABSOLUTE_URL
                                    ) :
                                    $this->legacyUrlGenerator->generateAbsoluteUrl(
                                        Useragent::generateAvatarSrc(
                                            $connectionData['UserAgentID'],
                                            $connectionData['PictureVer'],
                                            $connectionData['PictureExt']
                                        ) ?? ''
                                    )
                            ) :
                            null
                    ),
        ];
    }

    private function formatConnectionsList(iterable $connections): IteratorFluent
    {
        return
            it($connections)
            ->map(function (Connection $connectionData) { return $this->formatConnection($connectionData); });
    }

    private function formatPending(Connection $pendingConnectionData): array
    {
        return [
            'id' => $pendingConnectionData['UserAgentID'],
            'name' => $pendingConnectionData['FullName'],
            'actions' => [
                self::ACTION_APPROVE,
                self::ACTION_DENY,
            ],
        ];
    }

    private function formatInvite(Invitecode $emailInviteData): array
    {
        return [
            'id' => $emailInviteData->getInvitecodeid(),
            'email' => $emailInviteData->getEmail(),
            'type' => self::TYPE_INVITE,
            'status' => self::STATUS_WAITING,
            'actions' => [
                self::ACTION_RESEND,
                self::ACTION_DELETE,
            ],
        ];
    }
}
